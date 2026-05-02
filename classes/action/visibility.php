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
 * Visibility Quick Action.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\action;

/**
 * Class visibility.
 */
class visibility implements action_interface {
    /**
     * get_id.
     */
    public static function get_id(): string {
        return 'visibility';
    }

    /**
     * get_name.
     */
    public static function get_name(): string {
        return get_string('action_visibility', 'local_quickactions');
    }

    /**
     * get_description.
     */
    public static function get_description(): string {
        return get_string('action_visibility_desc', 'local_quickactions');
    }

    /**
     * get_icon.
     */
    public static function get_icon(): string {
        return 'eye';
    }

    /**
     * get_required_capability.
     */
    public static function get_required_capability(): string {
        return 'local/quickactions:bulkupdate';
    }

    /**
     * validate.
     *
     * @param array $params
     * @param array $cmids
     * @param int $courseid
     * @param \context_course $context
     */
    public static function validate(array $params, array $cmids, int $courseid, \context_course $context): void {
        $mode = $params['mode'] ?? '';
        if (!in_array($mode, ['show', 'hide', 'stealth'], true)) {
            throw new \moodle_exception('error_actionnotfound', 'local_quickactions');
        }
        $sectionids = $params['sectionids'] ?? [];
        if (empty($cmids) && empty($sectionids)) {
            throw new \moodle_exception('error_noselection', 'local_quickactions');
        }
    }

    /**
     * preview.
     *
     * @param array $params
     * @param array $cmids
     * @param int $courseid
     * @param \context_course $context
     * @return array
     */
    public static function preview(array $params, array $cmids, int $courseid, \context_course $context): array {
        $mode = $params['mode'];
        $cmids = \local_quickactions\action\dateshift::expand_with_sections($cmids, $params['sectionids'] ?? [], $courseid);
        $modinfo = get_fast_modinfo($courseid);
        $rows = [];

        foreach ($cmids as $cmid) {
            $cm = $modinfo->cms[$cmid] ?? null;
            if (!$cm) {
                continue;
            }
            $before = self::format_state($cm->visible, $cm->visibleoncoursepage);
            $after = self::format_state(
                $mode !== 'hide' ? 1 : 0,
                $mode === 'show' ? 1 : ($mode === 'stealth' ? 0 : (int)$cm->visibleoncoursepage)
            );
            $rows[] = [
                'cmid' => $cmid,
                'label' => format_string($cm->name),
                'before' => $before,
                'after' => $after,
            ];
        }
        return $rows;
    }

    /**
     * execute.
     *
     * @param array $params
     * @param array $cmids
     * @param int $courseid
     * @param \context_course $context
     * @return array
     */
    public static function execute(array $params, array $cmids, int $courseid, \context_course $context): array {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/course/lib.php');

        $mode = $params['mode'];
        $sectionids = $params['sectionids'] ?? [];
        $success = 0;
        $failed = 0;
        $errors = [];

        // Capture pre-change state for undo.
        $expandedcmids = \local_quickactions\action\dateshift::expand_with_sections($cmids, $sectionids, $courseid);
        $modinfo = get_fast_modinfo($courseid);
        $snapshot = ['cms' => [], 'sections' => []];
        foreach ($expandedcmids as $cmid) {
            $cm = $modinfo->cms[$cmid] ?? null;
            if ($cm) {
                $snapshot['cms'][] = [
                    'cmid'         => (int)$cmid,
                    'visible'      => (int)$cm->visible,
                    'oncoursepage' => (int)$cm->visibleoncoursepage,
                ];
            }
        }
        foreach ($sectionids as $sid) {
            $rec = $DB->get_record('course_sections', ['id' => (int)$sid, 'course' => $courseid], 'id, visible');
            if ($rec) {
                $snapshot['sections'][] = [
                    'sectionid' => (int)$rec->id,
                    'visible'   => (int)$rec->visible,
                ];
            }
        }

        // Toggle section visibility flags first (Moodle 5.2 API).
        $course = get_course($courseid);
        $sectionactions = new \core_courseformat\local\sectionactions($course);
        $modinfo = get_fast_modinfo($courseid);
        foreach ($sectionids as $sid) {
            try {
                $section = $DB->get_record('course_sections', ['id' => (int)$sid, 'course' => $courseid]);
                if (!$section) {
                    continue;
                }
                $sectioninfo = $modinfo->get_section_info($section->section);
                if ($sectioninfo) {
                    $sectionactions->set_visibility($sectioninfo, $mode !== 'hide');
                }
            } catch (\Throwable $e) {
                $errors[] = ['cmid' => 0, 'message' => 'section ' . $sid . ': ' . $e->getMessage()];
            }
        }

        $cmids = $expandedcmids;
        foreach ($cmids as $cmid) {
            try {
                $cm = $modinfo->cms[$cmid] ?? null;
                if (!$cm) {
                    $failed++;
                    continue;
                }
                if ($mode === 'show') {
                    set_coursemodule_visible($cmid, 1, 1);
                } else if ($mode === 'hide') {
                    set_coursemodule_visible($cmid, 0, 0);
                } else if ($mode === 'stealth') {
                    set_coursemodule_visible($cmid, 1, 0);
                }
                $success++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = ['cmid' => $cmid, 'message' => $e->getMessage()];
            }
        }

        rebuild_course_cache($courseid, true);

        $undoid = 0;
        if ($success > 0 && (!empty($snapshot['cms']) || !empty($snapshot['sections']))) {
            $undoid = \local_quickactions\local\undo_store::record(
                (int)$USER->id,
                $courseid,
                self::get_id(),
                $snapshot
            );
        }

        return ['success' => $success, 'failed' => $failed, 'errors' => $errors, 'undoid' => $undoid];
    }

    /**
     * format_state.
     *
     * @param int $visible
     * @param int $oncoursepage
     * @return string
     */
    private static function format_state(int $visible, int $oncoursepage): string {
        if (!$visible) {
            return get_string('hidden');
        }
        if (!$oncoursepage) {
            return get_string('hiddenfromstudents');
        }
        return get_string('visible');
    }
}
