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
 * Action: copy availability (restrict-access) settings from a template CM to others.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\action;

/**
 * Class availability_template.
 */
class availability_template implements action_interface {

    /**
     * get_id.
     */
    public static function get_id(): string {
        return 'availability_template';
    }

    /**
     * get_name.
     */
    public static function get_name(): string {
        return get_string('action_availability_template', 'local_quickactions');
    }

    /**
     * get_description.
     */
    public static function get_description(): string {
        return get_string('action_availability_template_desc', 'local_quickactions');
    }

    /**
     * get_icon.
     */
    public static function get_icon(): string {
        return 'lock';
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
        $templatecmid = (int)($params['templatecmid'] ?? 0);
        if ($templatecmid <= 0) {
            throw new \moodle_exception('error_invalidcm', 'local_quickactions');
        }
        $expanded = \local_quickactions\action\dateshift::expand_with_sections(
            $cmids,
            $params['sectionids'] ?? [],
            $courseid
        );
        if (count($expanded) < 2) {
            throw new \moodle_exception('error_completion_needs_two', 'local_quickactions');
        }
        if (!in_array($templatecmid, array_map('intval', $expanded), true)) {
            throw new \moodle_exception('error_invalidcm', 'local_quickactions');
        }
    }

    /**
     * preview.
     */
    public static function preview(array $params, array $cmids, int $courseid, \context_course $context): array {
        global $DB;
        $cmids = \local_quickactions\action\dateshift::expand_with_sections(
            $cmids,
            $params['sectionids'] ?? [],
            $courseid
        );
        $templatecmid = (int)$params['templatecmid'];
        $modinfo = get_fast_modinfo($courseid);
        $template = $modinfo->cms[$templatecmid] ?? null;
        if (!$template) {
            return ['rows' => [], 'applicable' => false, 'reason' => ''];
        }
        $tplavailability = $DB->get_field('course_modules', 'availability', ['id' => $templatecmid]);
        $tplsummary = self::summarise($tplavailability);

        $rows = [];
        $changed = 0;
        foreach ($cmids as $cmid) {
            if ((int)$cmid === $templatecmid) {
                continue;
            }
            $cm = $modinfo->cms[$cmid] ?? null;
            if (!$cm) {
                continue;
            }
            $current = $DB->get_field('course_modules', 'availability', ['id' => $cmid]);
            if ((string)$current !== (string)$tplavailability) {
                $changed++;
            }
            $rows[] = [
                'cmid'   => (int)$cmid,
                'label'  => format_string($cm->name) . ' (' . $cm->modname . ')',
                'before' => self::summarise($current),
                'after'  => $tplsummary,
            ];
        }
        return [
            'rows' => $rows,
            'applicable' => $changed > 0,
            'reason' => $changed > 0 ? '' : get_string('reason_availability_template_match', 'local_quickactions'),
        ];
    }

    /**
     * execute.
     */
    public static function execute(array $params, array $cmids, int $courseid, \context_course $context): array {
        global $DB, $USER;
        $cmids = \local_quickactions\action\dateshift::expand_with_sections(
            $cmids,
            $params['sectionids'] ?? [],
            $courseid
        );
        $templatecmid = (int)$params['templatecmid'];
        $tplavailability = $DB->get_field('course_modules', 'availability', ['id' => $templatecmid]);

        // Snapshot for undo (skip template — it is unchanged).
        $snapshot = ['records' => []];
        foreach ($cmids as $cmid) {
            if ((int)$cmid === $templatecmid) {
                continue;
            }
            $current = $DB->get_field('course_modules', 'availability', ['id' => $cmid]);
            $snapshot['records'][] = [
                'cmid' => (int)$cmid,
                'availability' => $current,
            ];
        }

        $success = 0;
        $failed = 0;
        $errors = [];
        foreach ($cmids as $cmid) {
            if ((int)$cmid === $templatecmid) {
                continue;
            }
            try {
                $DB->set_field('course_modules', 'availability', $tplavailability, ['id' => (int)$cmid]);
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

    /**
     * Human-readable summary of an availability JSON for the preview table.
     *
     * @param string|null $availability
     * @return string
     */
    private static function summarise(?string $availability): string {
        if ($availability === null || $availability === '' || $availability === '{}') {
            return get_string('availability_summary_none', 'local_quickactions');
        }
        $decoded = json_decode($availability);
        if (!$decoded || !isset($decoded->c) || !is_array($decoded->c)) {
            return get_string('availability_summary_set', 'local_quickactions');
        }
        $count = count($decoded->c);
        $op = isset($decoded->op) ? (string)$decoded->op : '&';
        return get_string('availability_summary_count', 'local_quickactions', (object)[
            'count' => $count,
            'op'    => $op === '|' ? 'OR' : 'AND',
        ]);
    }
}
