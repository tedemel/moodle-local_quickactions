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
 * Unit tests for move action.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\action;

/**
 * Tests for move_test.
 *
 * @covers \local_quickactions\action\move
 */
final class move_test extends \advanced_testcase {
    /**
     * test_execute_moves_module.
     */
    public function test_execute_moves_module(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['numsections' => 3]);
        $context = \context_course::instance($course->id);

        $assign = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'section' => 1,
        ]);

        $section3id = $DB->get_field('course_sections', 'id', ['course' => $course->id, 'section' => 3]);

        $result = move::execute(
            ['targetsectionid' => $section3id],
            [$assign->cmid],
            $course->id,
            $context
        );

        $this->assertEquals(1, $result['success']);
        rebuild_course_cache($course->id, true);
        $modinfo = get_fast_modinfo($course->id);
        $this->assertEquals(3, $modinfo->cms[$assign->cmid]->sectionnum);
    }

    /**
     * test_validate_rejects_invalid_section.
     */
    public function test_validate_rejects_invalid_section(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $this->expectException(\moodle_exception::class);
        move::validate(['targetsectionid' => 99999], [1], $course->id, $context);
    }
}
