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
 * Dateshift Quick Action.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\action;

/**
 * Class dateshift.
 */
class dateshift implements action_interface {

    /**
     * get_id.
     */
    public static function get_id(): string {
        return 'dateshift';
    }

    /**
     * get_name.
     */
    public static function get_name(): string {
        return get_string('action_dateshift', 'local_quickactions');
    }

    /**
     * get_description.
     */
    public static function get_description(): string {
        return get_string('action_dateshift_desc', 'local_quickactions');
    }

    /**
     * get_icon.
     */
    public static function get_icon(): string {
        return 'calendar';
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
        $sectionids = $params['sectionids'] ?? [];
        if (empty($cmids) && empty($sectionids)) {
            throw new \moodle_exception('error_noselection', 'local_quickactions');
        }
        $target = (int)($params['targetdate'] ?? 0);
        if ($target <= 0) {
            throw new \moodle_exception('error_dateshift_zero', 'local_quickactions');
        }
    }

    /**
     * preview.
     */
    public static function preview(array $params, array $cmids, int $courseid, \context_course $context): array {
        $cmids = self::expand_with_sections($cmids, $params['sectionids'] ?? [], $courseid);
        $delta = self::compute_delta_seconds($params, $cmids, $courseid);
        $modinfo = get_fast_modinfo($courseid);
        $rows = [];

        foreach ($cmids as $cmid) {
            $cm = $modinfo->cms[$cmid] ?? null;
            if (!$cm) {
                continue;
            }
            $datefields = self::get_date_fields_for_module($cm->modname, $cm->instance);
            if (empty($datefields)) {
                continue;
            }
            $changes = [];
            foreach ($datefields as $field => $value) {
                if ($value > 0) {
                    $newvalue = $value + $delta;
                    $changes[] = sprintf(
                        '%s: %s → %s',
                        $field,
                        userdate($value, '%Y-%m-%d %H:%M'),
                        userdate($newvalue, '%Y-%m-%d %H:%M')
                    );
                }
            }
            if (!empty($changes)) {
                $rows[] = [
                    'cmid' => $cmid,
                    'label' => format_string($cm->name) . ' (' . $cm->modname . ')',
                    'before' => '',
                    'after' => implode(' | ', $changes),
                ];
            }
        }
        return $rows;
    }

    /**
     * execute.
     */
    public static function execute(array $params, array $cmids, int $courseid, \context_course $context): array {
        global $DB, $USER;
        $cmids = self::expand_with_sections($cmids, $params['sectionids'] ?? [], $courseid);
        $delta = self::compute_delta_seconds($params, $cmids, $courseid);
        $success = 0;
        $failed = 0;
        $errors = [];

        $modinfo = get_fast_modinfo($courseid);

        // Capture pre-change state for undo.
        $snapshot = ['records' => []];
        foreach ($cmids as $cmid) {
            $cm = $modinfo->cms[$cmid] ?? null;
            if (!$cm) {
                continue;
            }
            $fields = self::get_date_fields_for_module($cm->modname, (int)$cm->instance);
            if (!empty($fields)) {
                $snapshot['records'][] = [
                    'modname'  => $cm->modname,
                    'instance' => (int)$cm->instance,
                    'fields'   => $fields,
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
                $datefields = self::get_date_fields_for_module($cm->modname, $cm->instance);
                if (empty($datefields)) {
                    $success++;
                    continue;
                }
                $update = (object)['id' => $cm->instance];
                $hasupdate = false;
                foreach ($datefields as $field => $value) {
                    if ($value > 0) {
                        $update->$field = $value + $delta;
                        $hasupdate = true;
                    }
                }
                if ($hasupdate) {
                    $update->timemodified = time();
                    $DB->update_record($cm->modname, $update);
                }
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
                (int)$USER->id, $courseid, self::get_id(), $snapshot
            );
        }

        return ['success' => $success, 'failed' => $failed, 'errors' => $errors, 'undoid' => $undoid];
    }

    /**
     * Get all known date fields for a given module type and instance.
     *
     * @return array<string,int>
     */
    private static function get_date_fields_for_module(string $modname, int $instanceid): array {
        global $DB;

        $known = [
            'assign' => ['allowsubmissionsfromdate', 'duedate', 'cutoffdate', 'gradingduedate'],
            'quiz'   => ['timeopen', 'timeclose'],
            'forum'  => ['duedate', 'cutoffdate'],
            'lesson' => ['available', 'deadline'],
            'choice' => ['timeopen', 'timeclose'],
            'workshop' => ['submissionstart', 'submissionend', 'assessmentstart', 'assessmentend'],
            'feedback' => ['timeopen', 'timeclose'],
            'data'   => ['timeavailablefrom', 'timeavailableto', 'timeviewfrom', 'timeviewto', 'assesstimestart', 'assesstimefinish'],
            'scorm'  => ['timeopen', 'timeclose'],
            'chat'   => ['chattime'],
        ];

        $fields = $known[$modname] ?? [];
        if (empty($fields)) {
            return [];
        }

        $select = 'id, ' . implode(', ', $fields);
        $record = $DB->get_record($modname, ['id' => $instanceid], $select, IGNORE_MISSING);
        if (!$record) {
            return [];
        }

        $out = [];
        foreach ($fields as $f) {
            $out[$f] = (int)($record->$f ?? 0);
        }
        return $out;
    }

    /**
     * Compute delta seconds: target date minus the earliest existing date across all selected CMs.
     * If no existing dates, delta = target - now.
     */
    private static function compute_delta_seconds(array $params, array $cmids, int $courseid): int {
        $target = (int)$params['targetdate'];
        $earliest = self::find_earliest_date($cmids, $courseid);
        if ($earliest > 0) {
            return $target - $earliest;
        }
        return $target - time();
    }

    /**
     * find_earliest_date.
     */
    private static function find_earliest_date(array $cmids, int $courseid): int {
        $modinfo = get_fast_modinfo($courseid);
        $earliest = 0;
        foreach ($cmids as $cmid) {
            $cm = $modinfo->cms[$cmid] ?? null;
            if (!$cm) {
                continue;
            }
            $fields = self::get_date_fields_for_module($cm->modname, $cm->instance);
            foreach ($fields as $value) {
                if ($value > 0 && ($earliest === 0 || $value < $earliest)) {
                    $earliest = (int)$value;
                }
            }
        }
        return $earliest;
    }

    /**
     * Expand selected sections into their child cmids, merge with explicit cmids.
     */
    public static function expand_with_sections(array $cmids, array $sectionids, int $courseid): array {
        if (empty($sectionids)) {
            return array_values(array_unique(array_map('intval', $cmids)));
        }
        global $DB;
        $modinfo = get_fast_modinfo($courseid);
        $set = array_flip(array_map('intval', $cmids));
        $sections = $DB->get_records_list('course_sections', 'id', $sectionids, '', 'id, section');
        foreach ($sections as $s) {
            foreach (($modinfo->sections[$s->section] ?? []) as $cmid) {
                $set[(int)$cmid] = true;
            }
        }
        return array_keys($set);
    }
}
