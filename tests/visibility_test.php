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
 * Unit tests for visibility action.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\action;

/**
 * @covers \local_quickactions\action\visibility
 */
final class visibility_test extends \advanced_testcase {

    public function test_validate_throws_on_no_selection(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $this->expectException(\moodle_exception::class);
        visibility::validate(['mode' => 'show'], [], $course->id, $context);
    }

    public function test_validate_throws_on_invalid_mode(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $this->expectException(\moodle_exception::class);
        visibility::validate(['mode' => 'invalid'], [1, 2], $course->id, $context);
    }

    public function test_execute_hides_modules(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $this->assertEquals(1, $DB->get_field('course_modules', 'visible', ['id' => $assign->cmid]));

        $result = visibility::execute(['mode' => 'hide'], [$assign->cmid], $course->id, $context);
        $this->assertEquals(1, $result['success']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(0, $DB->get_field('course_modules', 'visible', ['id' => $assign->cmid]));
    }

    public function test_execute_shows_modules(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);

        set_coursemodule_visible($assign->cmid, 0, 0);
        $this->assertEquals(0, $DB->get_field('course_modules', 'visible', ['id' => $assign->cmid]));

        $result = visibility::execute(['mode' => 'show'], [$assign->cmid], $course->id, $context);
        $this->assertEquals(1, $result['success']);
        $this->assertEquals(1, $DB->get_field('course_modules', 'visible', ['id' => $assign->cmid]));
    }
}
