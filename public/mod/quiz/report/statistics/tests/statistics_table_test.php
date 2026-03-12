<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace quiz_statistics;

use quiz_statistics_table;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/report/statistics/statistics_table.php');

/**
 * Class quiz_statistics_statistics_table_testcase
 *
 * @package    quiz_statistics
 * @category   test
 * @copyright  2018 Shamim Rezaie <shamim@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class statistics_table_test extends \advanced_testcase {

    public function test_format_percentage(): void {
        $table = new quiz_statistics_table();

        // The format_percentage method is protected. Use Reflection to call the method.
        $reflector = new \ReflectionClass('quiz_statistics_table');
        $method = $reflector->getMethod('format_percentage');

        $this->assertEquals(
                '84.758%',
                $method->invokeArgs($table, [0.847576, true, 3])
        );

        $this->assertEquals(
                '84.758%',
                $method->invokeArgs($table, [84.7576, false, 3])
        );
    }

    public function test_format_percentage_range(): void {
        $table = new quiz_statistics_table();

        // The format_percentage_range method is protected. Use Reflection to call the method.
        $reflector = new \ReflectionClass('quiz_statistics_table');
        $method = $reflector->getMethod('format_percentage_range');

        $this->assertEquals(
                '54.400% − 84.758%',
                $method->invokeArgs($table, [0.544, 0.847576, true, 3])
        );

        $this->assertEquals(
                '54.400% − 84.758%',
                $method->invokeArgs($table, [54.4, 84.7576, false, 3])
        );
    }

    public function test_format_range(): void {
        $table = new quiz_statistics_table();

        // The format_range method is protected. Use Reflection to call the method.
        $reflector = new \ReflectionClass('quiz_statistics_table');
        $method = $reflector->getMethod('format_range');

        $this->assertEquals(
                '5 − 10',
                $method->invokeArgs($table, [5, 10])
        );

        $this->assertEquals(
                'Some Text − 10',
                $method->invokeArgs($table, ['Some Text', 10])
        );
    }

    public function test_col_qtype(): void {
        $table = new quiz_statistics_table();
        $reflector = new \ReflectionClass('quiz_statistics_table');
        $method = $reflector->getMethod('col_qtype');

        // 1. Standard question.
        $stat = new \stdClass();
        $stat->question = (object) ['qtype' => 'multichoice', 'random' => false];
        $this->assertEquals(
            get_string('pluginname', 'qtype_multichoice'),
            $method->invokeArgs($table, [$stat])
        );

        // 2. Random question slot (qtype is null or empty, random is true).
        $randomstat = new \stdClass();
        $randomstat->question = (object) ['qtype' => null, 'random' => true];
        $this->assertEquals(
            get_string('random', 'quiz'),
            $method->invokeArgs($table, [$randomstat])
        );

        // 3. Calculated question summary row.
        $summarystat = new \core_question\statistics\questions\calculated_question_summary(
            (object) ['id' => 1, 'maxmark' => 1, 'number' => 1, 'qtype' => null, 'random' => true],
            1,
            []
        );
        $this->assertEquals(
            '',
            $method->invokeArgs($table, [$summarystat])
        );

        // 4. Missing question type.
        $missingstat = new \stdClass();
        $missingstat->question = (object) ['qtype' => 'nonexistent_qtype', 'random' => false];
        $this->assertEquals(
            get_string('pluginname', 'qtype_missingtype'),
            $method->invokeArgs($table, [$missingstat])
        );
    }
}

