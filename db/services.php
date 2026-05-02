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
 * Web service definitions for local_quickactions.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_quickactions_get_context' => [
        'classname'     => 'local_quickactions\external\get_context',
        'description'   => 'Get course context info: list of sections, target sections for move action.',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'local/quickactions:use',
    ],

    'local_quickactions_preview' => [
        'classname'     => 'local_quickactions\external\preview',
        'description'   => 'Preview what an action would do without applying.',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'local/quickactions:use',
    ],

    'local_quickactions_execute' => [
        'classname'     => 'local_quickactions\external\apply',
        'description'   => 'Execute a Quick Action on selected course module ids.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'local/quickactions:bulkupdate',
    ],

    'local_quickactions_undo' => [
        'classname'     => 'local_quickactions\external\undo',
        'description'   => 'Restore the snapshot recorded before a Quick Action.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'local/quickactions:use',
    ],
];
