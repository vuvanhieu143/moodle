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

namespace mod_quiz\external;

use coding_exception;
use core_external\tests\externallib_testcase;
use core_question_generator;
use mod_quiz\output\attempt_summary_information;
use mod_quiz\question\display_options;
use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use required_capability_exception;

/**
 * Test for the service save_overall_feedback_per_grade_item.
 *
 * @package   mod_quiz
 * @category  external
 * @copyright 2024 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(save_overall_feedback_per_grade_item::class)]
final class grade_item_overall_feedbacks_test extends externallib_testcase {
    /**
     * Test service save_overall_feedback_per_grade_item for valid data.
     *
     * @param array $formdata Form data to test.
     */
    #[DataProvider('save_value_provider')]
    public function test_create_overall_feedback_works(array $formdata): void {
        $quizobj = $this->create_quiz_with_grade_item();

        $structure = $quizobj->get_structure();
        $item = array_values($structure->get_grade_items());

        $response = save_overall_feedback_per_grade_item::execute($quizobj->get_quizid(), $item[0]->id, json_encode($formdata));
        $structure = $quizobj->get_structure();
        $feedbacks = $structure->get_grade_item_feedbacks()[$item[0]->id];

        // There are no errors.
        $this->assertCount(0, json_decode($response->errors));
        // Three feedbacks have been created.
        $this->assertEquals(3, $response->total);

        $this->assertEquals('100% feedback', $feedbacks[0]->feedbacktext);
        $this->assertEquals('80% feedback', $feedbacks[1]->feedbacktext);
        $this->assertEquals('50% feedback', $feedbacks[2]->feedbacktext);
    }

    /**
     * Data provider for test_create_overall_feedback_works().
     *
     * @return array
     */
    public static function save_value_provider(): array {
        return [
            'Normal case' => [
                'formdata' => [
                    (object) [
                        'boundary' => 11,
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '100% feedback',
                        ],
                    ],
                    (object) [
                        'boundary' => '80%',
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '80% feedback',
                        ],
                    ],
                    (object) [
                        'boundary' => '50%',
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '50% feedback',
                        ],
                    ],
                ],
            ],
            'Gap data' => [
                'formdata' => [
                    (object) [
                        'boundary' => 11,
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '100% feedback',
                        ],
                    ],
                    (object) [
                        'boundary' => '',
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '',
                        ],
                    ],
                    (object) [
                        'boundary' => '80%',
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '80% feedback',
                        ],
                    ],
                    (object) [
                        'boundary' => '50%',
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '50% feedback',
                        ],
                    ],
                ],
            ],
            'Wrong order' => [
                'formdata' => [
                    (object) [
                        'boundary' => 11,
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '100% feedback',
                        ],
                    ],
                    (object) [
                        'boundary' => '50%',
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '50% feedback',
                        ],
                    ],
                    (object) [
                        'boundary' => '80%',
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '80% feedback',
                        ],
                    ],
                ],
            ],
            'Grade boundary' => [
                'formdata' => [
                    (object) [
                        'boundary' => 11,
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '100% feedback',
                        ],
                    ],
                    (object) [
                        'boundary' => 80,
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '80% feedback',
                        ],
                    ],
                    (object) [
                        'boundary' => 50,
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '50% feedback',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test service save_overall_feedback_per_grade_item.
     *
     * @param array $expecteddata Expected data after executing.
     * @param array $formdata Form data to test.
     */
    #[DataProvider('save_value_error_provider')]
    public function test_create_overall_feedback_with_error(array $expecteddata, array $formdata): void {
        $quizobj = $this->create_quiz_with_grade_item();
        $structure = $quizobj->get_structure();
        $item = array_values($structure->get_grade_items());

        $response = save_overall_feedback_per_grade_item::execute($quizobj->get_quizid(), $item[0]->id, json_encode($formdata));
        $errors = json_decode($response->errors);
        $this->assertEquals(0, $response->total);
        foreach ($expecteddata as $ex) {
            $this->assertEquals($ex['text'], $errors->{$ex['place']});
        }
    }

    /**
     * Data provider for test_create_overall_feedback_with_error().
     *
     * @return array
     */
    public static function save_value_error_provider(): array {
        return [
            'Duplicate boundaries' => [
                'expecteddata' => [
                    [
                        'place' => 'feedbackboundaries[1]',
                        'text' => 'Feedback grade boundaries must be unique. The value you ' .
                            'entered for boundary 2 has already been used.',
                    ],
                ],
                'formdata' => [
                    (object) [
                        'boundary' => 11,
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '100% feedback',
                        ],
                    ],
                    (object) [
                        'boundary' => '80%',
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '80% feedback',
                        ],
                    ],
                    (object) [
                        'boundary' => '80%',
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '80% feedback',
                        ],
                    ],
                ],
            ],
            'Invalid boundary data' => [
                'expecteddata' => [
                    [
                        'place' => 'feedbackboundaries[0]',
                        'text' => 'Feedback grade boundaries must be either a percentage or a number. The value you ' .
                            'entered in boundary 1 is not recognised.',
                    ],
                ],
                'formdata' => [
                    (object) [
                        'boundary' => 11,
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '100% feedback',
                        ],
                    ],
                    (object) [
                        'boundary' => 'Test',
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => 'Wrong data',
                        ],
                    ],
                ],
            ],
            'Boundary out of range' => [
                'expecteddata' => [
                    [
                        'place' => 'feedbackboundaries[0]',
                        'text' => 'Feedback grade boundaries must be between 0% and 100%. The value you entered in ' .
                            'boundary 1 is out of range.',
                    ],
                ],
                'formdata' => [
                    (object) [
                        'boundary' => 11,
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '100% feedback',
                        ],
                    ],
                    (object) [
                        'boundary' => '120%',
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => 'Wrong data',
                        ],
                    ],
                ],
            ],
            'Feedback without boundary' => [
                'expecteddata' => [
                    [
                        'place' => 'feedbacktext[1]',
                        'text' => 'You must fill in the feedback boxes without leaving any gaps.',
                    ],
                ],
                'formdata' => [
                    (object) [
                        'boundary' => 11,
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => '100% feedback',
                        ],
                    ],
                    (object) [
                        'boundary' => '',
                        'feedback' => (object) [
                            'itemid' => 0,
                            'format' => FORMAT_HTML,
                            'text' => 'Missing boundary',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test that saving overall feedback requires mod/quiz:manage capability.
     */
    public function test_save_overall_feedback_requires_capability(): void {
        global $SITE;
        $quizobj = $this->create_quiz_with_grade_item();
        $structure = $quizobj->get_structure();
        $item = array_values($structure->get_grade_items());

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $SITE->id, 'student');
        $this->setUser($student);

        $this->expectException(required_capability_exception::class);
        save_overall_feedback_per_grade_item::execute(
            $quizobj->get_quizid(),
            $item[0]->id,
            json_encode([
                (object) [
                    'boundary' => '100%',
                    'feedback' => (object) [
                        'itemid' => 0,
                        'format' => FORMAT_HTML,
                        'text' => 'Unauthorized',
                    ],
                ],
            ])
        );
    }

    /**
     * Test that saving overall feedback rejects a grade item from another quiz.
     */
    public function test_save_overall_feedback_invalid_grade_item(): void {
        global $SITE;
        $quizobj = $this->create_quiz_with_grade_item();
        /** @var \mod_quiz_generator $quizgenerator */
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $otherquiz = $quizgenerator->create_instance(['course' => $SITE->id]);
        $otheritem = $quizgenerator->create_grade_item(['quizid' => $otherquiz->id, 'name' => 'Other']);

        $this->expectException(coding_exception::class);
        save_overall_feedback_per_grade_item::execute(
            $quizobj->get_quizid(),
            $otheritem->id,
            json_encode([
                (object) [
                    'boundary' => '100%',
                    'feedback' => (object) [
                        'itemid' => 0,
                        'format' => FORMAT_HTML,
                        'text' => 'Wrong quiz',
                    ],
                ],
            ])
        );
    }

    /**
     * Test that saving empty feedback removes existing grade item feedbacks and attempt summary respects display options.
     */
    public function test_save_empty_and_attempt_summary_display_options(): void {
        global $PAGE;
        $quizobj = $this->create_quiz_with_grade_item();
        $structure = $quizobj->get_structure();
        $item = array_values($structure->get_grade_items())[0];

        save_overall_feedback_per_grade_item::execute(
            $quizobj->get_quizid(),
            $item->id,
            json_encode([
                (object) [
                    'boundary' => '100%',
                    'feedback' => (object) [
                        'itemid' => 0,
                        'format' => FORMAT_HTML,
                        'text' => 'Well done',
                    ],
                ],
            ])
        );

        // Simulate an attempt with grade item totals.
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $quizobj->get_courseid(), 'student');
        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $user->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);
        $attemptobj = quiz_attempt::create($attempt->id);
        $attemptobj->process_submit($timenow, false);
        $attemptobj->process_grade_submission($timenow);

        $renderer = $PAGE->get_renderer('mod_quiz');
        $options = new display_options();
        $options->marks = \question_display_options::MARK_AND_MAX;
        $options->overallfeedback = \question_display_options::VISIBLE;
        $summary = attempt_summary_information::create_for_attempt($attemptobj, $options);
        $summarydata = $summary->export_for_template($renderer);
        $titles = array_column($summarydata['items'], 'title');
        $this->assertContains('Feedback Listening', $titles);

        // When overallfeedback display option is hidden, grade item feedback must not be shown.
        $options->overallfeedback = \question_display_options::HIDDEN;
        $summaryhidden = attempt_summary_information::create_for_attempt($attemptobj, $options);
        $summaryhiddendata = $summaryhidden->export_for_template($renderer);
        $titleshidden = array_column($summaryhiddendata['items'], 'title');
        $this->assertNotContains('Feedback Listening', $titleshidden);

        // Saving empty feedback deletes existing feedback records.
        $response = save_overall_feedback_per_grade_item::execute(
            $quizobj->get_quizid(),
            $item->id,
            json_encode([
                (object) [
                    'boundary' => '100%',
                    'feedback' => (object) [
                        'itemid' => 0,
                        'format' => FORMAT_HTML,
                        'text' => '',
                    ],
                ],
            ])
        );
        $this->assertEquals(0, $response->total);
        $this->assertArrayNotHasKey($item->id, $quizobj->get_structure()->get_grade_item_feedbacks());
    }

    /**
     * Create a quiz of a shortanswer question, each contributing to a different grade item.
     *
     * @return quiz_settings the newly created quiz.
     */
    protected function create_quiz_with_grade_item(): quiz_settings {
        global $SITE;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Make a quiz.
        /** @var \mod_quiz_generator $quizgenerator */
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');

        $quiz = $quizgenerator->create_instance(['course' => $SITE->id]);

        // Create question.
        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $saq1 = $questiongenerator->create_question('shortanswer', null, ['category' => $cat->id]);

        // Add them to the quiz.
        quiz_add_quiz_question($saq1->id, $quiz, 0, 1);

        // Create quiz grade items.
        $listeninggrade = $quizgenerator->create_grade_item(['quizid' => $quiz->id, 'name' => 'Listening']);

        // Set the questions to use those grade items.
        $quizobj = quiz_settings::create($quiz->id);
        $structure = $quizobj->get_structure();
        $structure->update_slot_grade_item($structure->get_slot_by_number(1), $listeninggrade->id);
        $quizobj->get_grade_calculator()->recompute_quiz_sumgrades();

        return $quizobj;
    }
}
