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
 * Registry for Quick Actions.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\action;

/**
 * Class registry.
 */
class registry {
    /**
     * @return array<string, class-string<action_interface>>
     */
    public static function get_all(): array {
        return [
            visibility::get_id()           => visibility::class,
            dateshift::get_id()            => dateshift::class,
            section_duplicate::get_id()    => section_duplicate::class,
            move::get_id()                 => move::class,
            completion_template::get_id()  => completion_template::class,
            availability_template::get_id() => availability_template::class,
        ];
    }

    /**
     * @param string $id
     * @return class-string<action_interface>|null
     */
    public static function get(string $id): ?string {
        $all = self::get_all();
        return $all[$id] ?? null;
    }

    /**
     * get_metadata_for_context.
     */
    public static function get_metadata_for_context(\context_course $context): array {
        $items = [];
        foreach (self::get_all() as $id => $cls) {
            /** @var class-string<action_interface> $cls */
            $cap = $cls::get_required_capability();
            if (!has_capability($cap, $context)) {
                continue;
            }
            $items[] = [
                'id' => $id,
                'name' => $cls::get_name(),
                'description' => $cls::get_description(),
                'icon' => $cls::get_icon(),
            ];
        }
        return $items;
    }
}
