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
 * Action: copy completion settings from a "template" CM to all other selected CMs.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\action;

/**
 * Class completion_template.
 */
class completion_template implements action_interface {
    /** Fields copied from template CM. */
    private const COMPLETION_FIELDS = [
        'completion',
        'completionview',
        'completiongradeitemnumber',
        'completionexpected',
        'completionpassgrade',
    ];

    /**
     * get_id.
     */
    public static function get_id(): string {
        return 'completion_template';
    }

    /**
     * get_name.
     */
    public static function get_name(): string {
        return get_string('action_completion_template', 'local_quickactions');
    }

    /**
     * get_description.
     */
    public static function get_description(): string {
        return get_string('action_completion_template_desc', 'local_quickactions');
    }

    /**
     * get_icon.
     */
    public static function get_icon(): string {
        return 'check-circle-o';
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
        // Expand any selected sections to their member cmids so the user can use sections too.
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
            return [];
        }
        $tplrec = $DB->get_record('course_modules', ['id' => $templatecmid], 'id, ' . implode(', ', self::COMPLETION_FIELDS));
        $tplsummary = self::summarise($tplrec);

        $rows = [];
        foreach ($cmids as $cmid) {
            if ((int)$cmid === $templatecmid) {
                continue;
            }
            $cm = $modinfo->cms[$cmid] ?? null;
            if (!$cm) {
                continue;
            }
            $rec = $DB->get_record('course_modules', ['id' => $cmid], 'id, ' . implode(', ', self::COMPLETION_FIELDS));
            $rows[] = [
                'cmid'   => (int)$cmid,
                'label'  => format_string($cm->name) . ' (' . $cm->modname . ')',
                'before' => self::summarise($rec),
                'after'  => $tplsummary,
            ];
        }
        return $rows;
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
        $tplrec = $DB->get_record('course_modules', ['id' => $templatecmid], 'id, ' . implode(', ', self::COMPLETION_FIELDS));
        if (!$tplrec) {
            return [
                'success' => 0,
                'failed' => 1,
                'errors' => [['cmid' => $templatecmid, 'message' => 'template missing']],
                'undoid' => 0,
            ];
        }

        // Snapshot before changes (skip template — it is unchanged).
        $snapshot = ['records' => []];
        foreach ($cmids as $cmid) {
            if ((int)$cmid === $templatecmid) {
                continue;
            }
            $rec = $DB->get_record('course_modules', ['id' => $cmid], 'id, ' . implode(', ', self::COMPLETION_FIELDS));
            if ($rec) {
                $snapshot['records'][] = [
                    'cmid'   => (int)$cmid,
                    'fields' => array_intersect_key((array)$rec, array_flip(self::COMPLETION_FIELDS)),
                ];
            }
        }

        $success = 0;
        $failed = 0;
        $errors = [];
        foreach ($cmids as $cmid) {
            if ((int)$cmid === $templatecmid) {
                continue;
            }
            try {
                $update = (object)['id' => (int)$cmid];
                foreach (self::COMPLETION_FIELDS as $f) {
                    $update->$f = $tplrec->$f;
                }
                $DB->update_record('course_modules', $update);
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
     * Human-readable summary of completion config for the preview table.
     */
    private static function summarise($rec): string {
        if (!$rec) {
            return '—';
        }
        $modes = [
            0 => get_string('completion_none', 'completion'),
            1 => get_string('completion_manual', 'completion'),
            2 => get_string('completion_automatic', 'completion'),
        ];
        $parts = [$modes[(int)$rec->completion] ?? (string)$rec->completion];
        if ((int)$rec->completionview) {
            $parts[] = 'view';
        }
        if (!is_null($rec->completiongradeitemnumber)) {
            $parts[] = 'grade';
        }
        if ((int)$rec->completionpassgrade) {
            $parts[] = 'passgrade';
        }
        if ((int)$rec->completionexpected > 0) {
            $parts[] = 'expected:' . userdate($rec->completionexpected, '%Y-%m-%d');
        }
        return implode(' · ', $parts);
    }
}
