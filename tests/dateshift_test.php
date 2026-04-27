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
 * Unit tests for dateshift action.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\action;

/**
 * @covers \local_quickactions\action\dateshift
 */
final class dateshift_test extends \advanced_testcase {

    public function test_validate_rejects_no_target(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $this->expectException(\moodle_exception::class);
        dateshift::validate(['targetdate' => 0], [1], $course->id, $context);
    }

    public function test_validate_rejects_no_selection(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $this->expectException(\moodle_exception::class);
        dateshift::validate(['targetdate' => time() + DAYSECS], [], $course->id, $context);
    }

    public function test_execute_aligns_earliest_date_to_target(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $duedate = strtotime('2026-06-01 12:00');
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'duedate' => $duedate,
        ]);

        $target = strtotime('2026-06-08 12:00'); // +7 days.
        $result = dateshift::execute(
            ['targetdate' => $target],
            [$assign->cmid],
            $course->id,
            $context
        );

        $this->assertEquals(1, $result['success']);
        $this->assertEquals($target, (int)$DB->get_field('assign', 'duedate', ['id' => $assign->id]));
    }

    public function test_execute_preserves_relative_offsets(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $timeopen = strtotime('2026-06-01 09:00');
        $timeclose = strtotime('2026-06-01 11:00'); // 2 hours later.
        $quiz = $this->getDataGenerator()->create_module('quiz', [
            'course' => $course->id,
            'timeopen' => $timeopen,
            'timeclose' => $timeclose,
        ]);

        $target = strtotime('2026-07-01 09:00'); // earliest moves here.
        dateshift::execute(
            ['targetdate' => $target],
            [$quiz->cmid],
            $course->id,
            $context
        );

        $newopen = (int)$DB->get_field('quiz', 'timeopen', ['id' => $quiz->id]);
        $newclose = (int)$DB->get_field('quiz', 'timeclose', ['id' => $quiz->id]);
        $this->assertEquals($target, $newopen);
        $this->assertEquals($newclose - $newopen, $timeclose - $timeopen);
    }

    public function test_execute_with_section_expands_to_member_cmids(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['numsections' => 2]);
        $context = \context_course::instance($course->id);

        $duedate = strtotime('2026-06-01 12:00');
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'duedate' => $duedate,
            'section' => 1,
        ]);

        $sectionid = $DB->get_field('course_sections', 'id', ['course' => $course->id, 'section' => 1]);
        $target = strtotime('2026-06-15 12:00');

        $result = dateshift::execute(
            ['targetdate' => $target, 'sectionids' => [$sectionid]],
            [],
            $course->id,
            $context
        );

        $this->assertGreaterThanOrEqual(1, $result['success']);
        $this->assertEquals($target, (int)$DB->get_field('assign', 'duedate', ['id' => $assign->id]));
    }
}
