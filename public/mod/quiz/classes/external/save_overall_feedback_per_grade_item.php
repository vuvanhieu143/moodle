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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;
use mod_quiz\form\overallfeedback_form;
use mod_quiz\quiz_settings;
use stdClass;

/**
 * Validate and store overall feedback for each grade item form.
 *
 * @package   mod_quiz
 * @copyright 2024 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_overall_feedback_per_grade_item extends external_api {
    /**
     * Declare the method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'quizid' => new external_value(PARAM_INT, 'The quiz to save feedback.'),
            'gradeitemid' => new external_value(PARAM_INT, 'The grade item id to save the feedback.'),
            'formdata' => new external_value(PARAM_RAW, 'The feedback form data.'),
        ]);
    }

    /**
     * For quiz with grade items already set up, validate the form data in the correct format before creating it.
     *
     * @param int $quizid the id of the quiz to save feedback.
     * @param int $gradeitemid the grade item id of the quiz to save feedback.
     * @param string $formdata Form data needs to save.
     * @return stdClass Result info.
     */
    public static function execute(int $quizid, int $gradeitemid, string $formdata): stdClass {
        global $DB;

        [
            'formdata' => $formdata,
            'quizid' => $quizid,
            'gradeitemid' => $gradeitemid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'formdata' => $formdata,
            'quizid' => $quizid,
            'gradeitemid' => $gradeitemid,
        ]);

        $formdata = json_decode($formdata);
        if (!is_array($formdata) || empty($formdata)) {
            throw new \invalid_parameter_exception('Invalid form data.');
        }

        $quizobj = quiz_settings::create($quizid);
        $context = $quizobj->get_context();
        require_capability('mod/quiz:manage', $context);
        self::validate_context($context);
        $quizobj->get_structure()->verify_grade_item_is_ours($gradeitemid);

        // Remove first 100% data from formData, we don't need to validate its boundary.
        $firstdata = array_shift($formdata);
        $grade = $quizobj->get_quiz()->grade;
        $args = [
            'grade' => $grade,
            'context' => $context,
            'gradeItemId' => $gradeitemid,
        ];

        // Use form validation.
        $form = new overallfeedback_form(null, $args);
        [$errors, $data] = $form->validation(['grade' => $grade, 'formdata' => $formdata], []);

        $result = (object) [
            'warnings' => [],
            'errors' => json_encode([]),
            'total' => 0,
        ];

        if (count($errors) > 0) {
            $result->errors = json_encode($errors);
            return $result;
        }

        // Remove empty boundary rows before sorting and computing mingrade so empty rows do not leave gaps.
        $data = array_values(array_filter($data, static fn($item) => !empty($item->boundary)));

        // Sort the array by 'boundary' in descending order. This is necessary because users can enter a
        // list of overall feedback with boundary data out of order. For example, the list might be:
        // ["100%" => "100% feedback", "20%" => "20% feedback", "80%" => "80% feedback"].
        // When processed, the boundary data will be converted to corresponding decimal numbers: 1, 0.2, and 0.8,
        // respectively. The code ensures that these values are ordered in descending order before storing them in
        // the database. As a result, the final order will be 1, 0.8, and 0.2.
        usort($data, static fn($a, $b) => $b->boundary <=> $a->boundary);

        // Put the 100% boundary data back into the form.
        // Set the boundary default value for the first boundary.
        // The value should be a number greater than 1 to support the database query for
        // retrieving feedback based on the answer grade.
        $firstdata->boundary = 2;
        array_unshift($data, $firstdata);
        $formdata = $data;

        $transaction = $DB->start_delegated_transaction();

        // Remove old feedback files and records for this grade item before inserting the updated ones.
        $oldfeedbacks = $DB->get_records(
            'quiz_grade_item_feedbacks',
            ['quizid' => $quizid, 'gradeitemid' => $gradeitemid],
            '',
            'id'
        );
        $fs = get_file_storage();
        foreach ($oldfeedbacks as $oldfeedback) {
            $fs->delete_area_files($context->id, 'mod_quiz', 'grade_item_feedback', $oldfeedback->id);
        }
        $DB->delete_records('quiz_grade_item_feedbacks', ['quizid' => $quizid, 'gradeitemid' => $gradeitemid]);

        $index = 0;
        $total = 0;
        foreach ($formdata as $key => $item) {
            $index++;
            // Skip the 100% row when its feedback text is empty.
            if ($key === 0 && empty($item->feedback->text)) {
                continue;
            }
            $feedback = new stdClass();
            $feedback->quizid = $quizid;
            $feedback->gradeitemid = $gradeitemid;
            $feedback->feedbacktext = $item->feedback->text;
            $feedback->feedbacktextformat = $item->feedback->format;
            $feedback->mingrade = isset($formdata[$index]) ? $formdata[$index]->boundary : 0;
            $feedback->maxgrade = $item->boundary;
            $feedback->id = $DB->insert_record('quiz_grade_item_feedbacks', $feedback);
            $feedbacktext = file_save_draft_area_files(
                (int) $item->feedback->itemid,
                $context->id,
                'mod_quiz',
                'grade_item_feedback',
                $feedback->id,
                [
                    'subdirs' => false,
                    'maxfiles' => -1,
                    'maxbytes' => 0,
                ],
                $item->feedback->text
            );
            $DB->set_field(
                'quiz_grade_item_feedbacks',
                'feedbacktext',
                $feedbacktext,
                ['id' => $feedback->id]
            );
            $total++;
        }

        $transaction->allow_commit();
        $result->total = $total;

        return $result;
    }

    /**
     * Describes the return information.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'warnings' => new external_warnings(),
            'errors' => new external_value(PARAM_RAW, 'Error messages appear whenever form data is not valid.'),
            'total' => new external_value(PARAM_INT, 'The number of overall feedbacks records.'),
        ]);
    }
}
