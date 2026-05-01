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
 * Section duplicate Quick Action.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\action;

/**
 * Class section_duplicate.
 */
class section_duplicate implements action_interface {
    /**
     * get_id.
     */
    public static function get_id(): string {
        return 'section_duplicate';
    }

    /**
     * get_name.
     */
    public static function get_name(): string {
        return get_string('action_section_duplicate', 'local_quickactions');
    }

    /**
     * get_description.
     */
    public static function get_description(): string {
        return get_string('action_section_duplicate_desc', 'local_quickactions');
    }

    /**
     * get_icon.
     */
    public static function get_icon(): string {
        return 'copy';
    }

    /**
     * get_required_capability.
     */
    public static function get_required_capability(): string {
        return 'local/quickactions:duplicatesection';
    }

    /**
     * validate.
     */
    public static function validate(array $params, array $cmids, int $courseid, \context_course $context): void {
        $sectionid = (int)($params['sectionid'] ?? 0);
        if ($sectionid <= 0) {
            throw new \moodle_exception('error_invalidsection', 'local_quickactions');
        }
        $position = $params['position'] ?? 'after';
        if (!in_array($position, ['after', 'end'], true)) {
            throw new \moodle_exception('error_actionnotfound', 'local_quickactions');
        }
    }

    /**
     * preview.
     */
    public static function preview(array $params, array $cmids, int $courseid, \context_course $context): array {
        global $DB;
        $sectionid = (int)$params['sectionid'];
        $section = $DB->get_record('course_sections', ['id' => $sectionid, 'course' => $courseid]);
        if (!$section) {
            return [];
        }
        $modinfo = get_fast_modinfo($courseid);
        $sectioncms = $modinfo->sections[$section->section] ?? [];
        $count = count($sectioncms);

        return [[
            'cmid'   => 0,
            'label'  => format_string($section->name ?: get_string('section') . ' ' . $section->section),
            'before' => sprintf('%d activities', $count),
            'after'  => sprintf('Will be duplicated as new section (%d activities)', $count),
        ]];
    }

    /**
     * Duplicate the chosen section and all its modules.
     *
     * @todo Verify duplicate_module() and move_section_to() availability in Moodle 5.x.
     *       If API drift occurs, fall back to backup/restore mechanism.
     */
    public static function execute(array $params, array $cmids, int $courseid, \context_course $context): array {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/course/format/lib.php');

        $sectionid = (int)$params['sectionid'];
        $position = $params['position'];

        try {
            $section = $DB->get_record('course_sections', ['id' => $sectionid, 'course' => $courseid], '*', MUST_EXIST);
            $course = get_course($courseid);

            $newsection = course_create_section($courseid, 0, false);
            $newsection = $DB->get_record('course_sections', ['id' => $newsection->id], '*', MUST_EXIST);

            $newname = ($section->name ?: get_string('section') . ' ' . $section->section) . ' (' . get_string('copy') . ')';
            $DB->set_field('course_sections', 'name', $newname, ['id' => $newsection->id]);

            $modinfo = get_fast_modinfo($courseid);
            $sectioncms = $modinfo->sections[$section->section] ?? [];
            $duplicated = 0;
            foreach ($sectioncms as $cmid) {
                try {
                    $cm = get_coursemodule_from_id('', $cmid, $courseid, false, MUST_EXIST);
                    duplicate_module($course, $cm, $newsection->id);
                    $duplicated++;
                } catch (\Throwable $e) {
                    debugging('section_duplicate: could not duplicate cm ' . $cmid . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
                }
            }

            if ($position === 'after') {
                move_section_to($course, $newsection->section, $section->section + 1);
            }

            rebuild_course_cache($courseid, true);

            // Snapshot for undo: just the new section id — restore deletes it (and its content).
            $undoid = \local_quickactions\local\undo_store::record(
                (int)$USER->id,
                $courseid,
                self::get_id(),
                ['newsectionid' => (int)$newsection->id]
            );

            return ['success' => 1, 'failed' => 0, 'errors' => [], 'duplicated_cms' => $duplicated, 'undoid' => $undoid];
        } catch (\Throwable $e) {
            return ['success' => 0, 'failed' => 1, 'errors' => [['cmid' => 0, 'message' => $e->getMessage()]], 'undoid' => 0];
        }
    }
}
