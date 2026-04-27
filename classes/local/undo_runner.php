<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Restore snapshots produced by Quick Actions.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\local;

/**
 * Class undo_runner.
 */
class undo_runner {
    /**
     * Restore the snapshot identified by $undoid in the given course context.
     *
     * @return array ['restored' => int]
     */
    public static function restore(int $undoid, \context_course $context): array {
        global $USER, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $rec = undo_store::get($undoid);
        if (!$rec) {
            throw new \moodle_exception('error_actionnotfound', 'local_quickactions');
        }
        if ((int)$rec->userid !== (int)$USER->id) {
            throw new \moodle_exception('error_permissiondenied', 'local_quickactions');
        }
        if ((int)$rec->courseid !== (int)$context->instanceid) {
            throw new \moodle_exception('error_permissiondenied', 'local_quickactions');
        }
        if ((time() - (int)$rec->timecreated) > undo_store::TTL_SECONDS) {
            undo_store::delete($undoid);
            throw new \moodle_exception('error_actionnotfound', 'local_quickactions');
        }

        // Re-check the original action's capability — undo is at least as invasive.
        $cls = \local_quickactions\action\registry::get($rec->actionid);
        if ($cls !== null) {
            require_capability($cls::get_required_capability(), $context);
        }

        $snapshot = json_decode($rec->snapshot, true) ?: [];
        $courseid = (int)$rec->courseid;
        $restored = 0;

        switch ($rec->actionid) {
            case 'visibility':
                $restored = self::restore_visibility($snapshot, $courseid);
                break;
            case 'dateshift':
                $restored = self::restore_dateshift($snapshot, $courseid);
                break;
            case 'section_duplicate':
                $restored = self::restore_section_duplicate($snapshot, $courseid);
                break;
            case 'move':
                $restored = self::restore_move($snapshot, $courseid);
                break;
            case 'completion_template':
                $restored = self::restore_completion_template($snapshot, $courseid);
                break;
            case 'availability_template':
                $restored = self::restore_availability_template($snapshot, $courseid);
                break;
            default:
                throw new \moodle_exception('error_actionnotfound', 'local_quickactions');
        }

        rebuild_course_cache($courseid, true);
        undo_store::delete($undoid);

        return ['restored' => $restored];
    }

    /**
     * restore_visibility.
     */
    private static function restore_visibility(array $snapshot, int $courseid): int {
        $count = 0;
        foreach ($snapshot['cms'] ?? [] as $row) {
            try {
                set_coursemodule_visible((int)$row['cmid'], (int)$row['visible'], (int)$row['oncoursepage']);
                $count++;
            } catch (\Throwable $e) {
                debugging('undo visibility cm: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        if (!empty($snapshot['sections'])) {
            $course = get_course($courseid);
            $sectionactions = new \core_courseformat\local\sectionactions($course);
            $modinfo = get_fast_modinfo($courseid);
            foreach ($snapshot['sections'] as $row) {
                try {
                    $section = $modinfo->get_section_info_by_id((int)$row['sectionid'], IGNORE_MISSING);
                    if ($section) {
                        $sectionactions->set_visibility($section, (bool)$row['visible']);
                        $count++;
                    }
                } catch (\Throwable $e) {
                    debugging('undo visibility section: ' . $e->getMessage(), DEBUG_DEVELOPER);
                }
            }
        }
        return $count;
    }

    /**
     * restore_dateshift.
     */
    private static function restore_dateshift(array $snapshot, int $courseid): int {
        global $DB;
        $count = 0;
        foreach ($snapshot['records'] ?? [] as $row) {
            try {
                $modname = $row['modname'];
                $update = (object)['id' => (int)$row['instance']];
                foreach ($row['fields'] as $f => $v) {
                    $update->$f = (int)$v;
                }
                $update->timemodified = time();
                $DB->update_record($modname, $update);
                $count++;
            } catch (\Throwable $e) {
                debugging('undo dateshift cm: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        return $count;
    }

    /**
     * Undo section_duplicate: delete the section that was created.
     */
    private static function restore_section_duplicate(array $snapshot, int $courseid): int {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/course/format/lib.php');

        $newsectionid = (int)($snapshot['newsectionid'] ?? 0);
        if ($newsectionid <= 0) {
            return 0;
        }
        try {
            $course = get_course($courseid);
            $modinfo = get_fast_modinfo($courseid);
            $section = $modinfo->get_section_info_by_id($newsectionid, IGNORE_MISSING);
            if (!$section) {
                return 0;
            }
            $sectionactions = new \core_courseformat\local\sectionactions($course);
            $sectionactions->delete($section);
            return 1;
        } catch (\Throwable $e) {
            debugging('undo section_duplicate: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return 0;
        }
    }

    /**
     * Undo completion_template: restore prior completion fields per cm.
     */
    private static function restore_completion_template(array $snapshot, int $courseid): int {
        global $DB;
        $count = 0;
        foreach ($snapshot['records'] ?? [] as $row) {
            try {
                $update = (object)['id' => (int)$row['cmid']];
                foreach ($row['fields'] as $f => $v) {
                    // Allow null for completiongradeitemnumber.
                    $update->$f = is_null($v) ? null : $v;
                }
                $DB->update_record('course_modules', $update);
                $count++;
            } catch (\Throwable $e) {
                debugging('undo completion_template cm: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        rebuild_course_cache($courseid, true);
        return $count;
    }

    /**
     * Undo move: move each cm back to its original section.
     */
    private static function restore_move(array $snapshot, int $courseid): int {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');

        $count = 0;
        $modinfo = get_fast_modinfo($courseid);
        foreach ($snapshot['records'] ?? [] as $row) {
            try {
                $cm = $modinfo->cms[(int)$row['cmid']] ?? null;
                $targetsection = $DB->get_record('course_sections', [
                    'id' => (int)$row['origsectionid'],
                    'course' => $courseid,
                ]);
                if ($cm && $targetsection) {
                    moveto_module($cm, $targetsection);
                    $count++;
                }
            } catch (\Throwable $e) {
                debugging('undo move cm: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        return $count;
    }

    /**
     * Undo availability_template: restore prior availability JSON per cm.
     */
    private static function restore_availability_template(array $snapshot, int $courseid): int {
        global $DB;
        $count = 0;
        foreach ($snapshot['records'] ?? [] as $row) {
            try {
                $DB->set_field('course_modules', 'availability', $row['availability'], ['id' => (int)$row['cmid']]);
                $count++;
            } catch (\Throwable $e) {
                debugging('undo availability_template cm: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        rebuild_course_cache($courseid, true);
        return $count;
    }
}
