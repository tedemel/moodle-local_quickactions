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
 * Common interface for Quick Actions.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\action;

/**
 * Interface action_interface.
 */
interface action_interface {
    /**
     * get_id.
     */
    public static function get_id(): string;

    /**
     * get_name.
     */
    public static function get_name(): string;

    /**
     * get_description.
     */
    public static function get_description(): string;

    /**
     * get_icon.
     */
    public static function get_icon(): string;

    /**
     * get_required_capability.
     */
    public static function get_required_capability(): string;

    /**
     * validate.
     */
    public static function validate(array $params, array $cmids, int $courseid, \context_course $context): void;

    /**
     * Build a preview of what the action would do.
     *
     * Returns a flat list of rows OR an array with keys
     * 'rows', 'applicable' (bool), 'reason' (string).
     */
    public static function preview(array $params, array $cmids, int $courseid, \context_course $context): array;

    /**
     * execute.
     */
    public static function execute(array $params, array $cmids, int $courseid, \context_course $context): array;
}
