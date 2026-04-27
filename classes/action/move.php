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
 * Move Quick Action.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\action;

/**
 * Class move.
 */
class move implements action_interface {
    /**
     * get_id.
     */
    public static function get_id(): string {
        return 'move';
    }

    /**
     * get_name.
     */
    public static function get_name(): string {
        return get_string('action_move', 'local_quickactions');
    }

    /**
     * get_description.
     */
    public static function get_description(): string {
        return get_string('action_move_desc', 'local_quickactions');
    }

    /**
     * get_icon.
     */
    public static function get_icon(): string {
        return 'arrow-right';
    }

    /**
     * get_required_capability.
     */
    public static function get_required_capability(): string {
        return 'local/quickactions:bulkupdate';
    }

    /**
     * validate.
     */
    public static function validate(array $params, array $cmids, int $courseid, \context_course $context): void {
        global $DB;
        $sectionids = $params['sectionids'] ?? [];
        if (empty($cmids) && empty($sectionids)) {
            throw new \moodle_exception('error_noselection', 'local_quickactions');
        }
        $targetsectionid = (int)($params['targetsectionid'] ?? 0);
        if ($targetsectionid <= 0) {
            throw new \moodle_exception('error_invalidsection', 'local_quickactions');
        }
        if (!$DB->record_exists('course_sections', ['id' => $targetsectionid, 'course' => $courseid])) {
            throw new \moodle_exception('error_invalidsection', 'local_quickactions');
        }
    }

    /**
     * preview.
     */
    public static function preview(array $params, array $cmids, int $courseid, \context_course $context): array {
        global $DB;
        $cmids = \local_quickactions\action\dateshift::expand_with_sections($cmids, $params['sectionids'] ?? [], $courseid);
        $targetsectionid = (int)$params['targetsectionid'];
        $targetsection = $DB->get_record('course_sections', ['id' => $targetsectionid]);
        $targetname = $targetsection
            ? ($targetsection->name ?: get_string('section') . ' ' . $targetsection->section)
            : '?';

        $modinfo = get_fast_modinfo($courseid);
        $rows = [];
        foreach ($cmids as $cmid) {
            $cm = $modinfo->cms[$cmid] ?? null;
            if (!$cm) {
                continue;
            }
            $currentsection = $modinfo->get_section_info($cm->sectionnum);
            $rows[] = [
                'cmid' => $cmid,
                'label' => format_string($cm->name),
                'before' => $currentsection
                    ? format_string($currentsection->name ?: get_string('section') . ' ' . $cm->sectionnum)
                    : '?',
                'after' => format_string($targetname),
            ];
        }
        return $rows;
    }

    /**
     * execute.
     */
    public static function execute(array $params, array $cmids, int $courseid, \context_course $context): array {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/course/lib.php');

        $cmids = \local_quickactions\action\dateshift::expand_with_sections($cmids, $params['sectionids'] ?? [], $courseid);
        $targetsectionid = (int)$params['targetsectionid'];
        $modinfo = get_fast_modinfo($courseid);

        $success = 0;
        $failed = 0;
        $errors = [];

        $targetsection = $DB->get_record('course_sections', ['id' => $targetsectionid, 'course' => $courseid], '*', MUST_EXIST);

        // Snapshot original section per cm for undo.
        $snapshot = ['records' => []];
        foreach ($cmids as $cmid) {
            $cm = $modinfo->cms[$cmid] ?? null;
            if (!$cm) {
                continue;
            }
            $origsection = $modinfo->get_section_info($cm->sectionnum);
            if ($origsection) {
                $snapshot['records'][] = [
                    'cmid'         => (int)$cmid,
                    'origsectionid' => (int)$origsection->id,
                ];
            }
        }

        foreach ($cmids as $cmid) {
            try {
                $cm = $modinfo->cms[$cmid] ?? null;
                if (!$cm) {
                    $failed++;
                    continue;
                }
                moveto_module($cm, $targetsection);
                $success++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = ['cmid' => $cmid, 'message' => $e->getMessage()];
            }
        }

        rebuild_course_cache($courseid, true);

        $undoid = 0;
        if ($success > 0 && !empty($snapshot['records'])) {
            $undoid = \local_quickactions\local\undo_store::record(
                (int)$USER->id,
                $courseid,
                self::get_id(),
                $snapshot
            );
        }

        return ['success' => $success, 'failed' => $failed, 'errors' => $errors, 'undoid' => $undoid];
    }
}
