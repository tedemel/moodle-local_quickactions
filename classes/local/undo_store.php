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
 * Undo snapshot persistence.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\local;

/**
 * Class undo_store.
 */
class undo_store {
    /** Records older than this become eligible for cleanup. */
    public const TTL_SECONDS = 1800; // 30 minutes.

    /**
     * record.
     */
    public static function record(int $userid, int $courseid, string $actionid, array $snapshot): int {
        global $DB;
        return (int)$DB->insert_record('local_quickactions_undo', (object)[
            'userid'      => $userid,
            'courseid'    => $courseid,
            'actionid'    => $actionid,
            'snapshot'    => json_encode($snapshot),
            'timecreated' => time(),
        ]);
    }

    /**
     * get.
     */
    public static function get(int $undoid): ?\stdClass {
        global $DB;
        $rec = $DB->get_record('local_quickactions_undo', ['id' => $undoid]);
        return $rec ?: null;
    }

    /**
     * delete.
     */
    public static function delete(int $undoid): void {
        global $DB;
        $DB->delete_records('local_quickactions_undo', ['id' => $undoid]);
    }

    /**
     * Delete records older than TTL.
     */
    public static function expire_old(): void {
        global $DB;
        $DB->delete_records_select(
            'local_quickactions_undo',
            'timecreated < :cutoff',
            ['cutoff' => time() - self::TTL_SECONDS]
        );
    }
}
