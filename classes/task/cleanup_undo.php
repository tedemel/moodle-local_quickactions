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
 * Scheduled task: expire stale undo records.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\task;

/**
 * Class cleanup_undo.
 */
class cleanup_undo extends \core\task\scheduled_task {

    /**
     * get_name.
     */
    public function get_name(): string {
        return get_string('task_cleanup_undo', 'local_quickactions');
    }

    /**
     * execute.
     */
    public function execute() {
        \local_quickactions\local\undo_store::expire_old();
    }
}
