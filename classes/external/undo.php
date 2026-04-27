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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Web service: undo a previous action.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class undo extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'undoid'   => new external_value(PARAM_INT, 'Undo record id'),
        ]);
    }

    public static function execute(int $courseid, int $undoid): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'undoid'   => $undoid,
        ]);

        $context = \context_course::instance((int)$params['courseid']);
        self::validate_context($context);
        require_capability('local/quickactions:use', $context);
        // The runner checks the action-specific capability internally.

        return \local_quickactions\local\undo_runner::restore((int)$params['undoid'], $context);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'restored' => new external_value(PARAM_INT, 'Number of items restored'),
        ]);
    }
}
