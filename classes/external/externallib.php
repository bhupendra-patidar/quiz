<?php
namespace local_quiz\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_course;
use context_module;
use moodle_exception;

class externallib extends external_api {
    
    // Define expected parameters for validation
    public static function airesponse_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'quizid' => new external_value(PARAM_INT, 'Quiz ID'),
            'attemptid' => new external_value(PARAM_INT, 'Attempt ID'),
            'status' => new external_value(PARAM_INT, 'Status (0 = not graded, 1 = graded)'),
            'grade' => new external_value(PARAM_RAW, 'Grade'),
            'feedbackdesc' => new external_value(PARAM_RAW, 'Feedback description'),
            'questions' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'ID'),
                    'grade' => new external_value(PARAM_FLOAT, 'Marks Awarded'),
                    'feedbackdesc' => new external_value(PARAM_TEXT, 'AI Feedback Description')
                ]),
                'Quiz Question'
            ),
            'errormessage' => new external_value(PARAM_RAW, 'Error Message', VALUE_OPTIONAL),
        ]);
    }

    public static function airesponse() {
        global $DB, $CFG;

        // Get raw POST data
        $rawdata = file_get_contents('php://input');
        if (!$rawdata) {
            throw new \moodle_exception('No input data received');
        }

        // Decode JSON
        $data = json_decode($rawdata, true);
        if ($data === null) {
            throw new \moodle_exception('Invalid JSON data');
        }        

        // Validate decoded JSON data
        $params = self::validate_parameters(self::airesponse_parameters(), $data);
        
        // Validation for grade as integer
        // if (!isset($params['grade']) || !is_numeric($params['grade']) || intval($params['grade'])) {
        //     throw new \moodle_exception('The grade must be an integer value.');
        // }

        $transaction = $DB->start_delegated_transaction();

        try {
            // Insert or update graderesponse in DB if teacher approval is 1
            $record = new \stdClass();
            $record->userid = $params['userid'];
            $record->courseid = $params['courseid'];
            $record->quizid = $params['quizid'];
            $record->attemptid = $params['attemptid'];
            $record->status = $params['status'];
            $record->grade = $params['grade'];
            $record->feedbackdesc = $params['feedbackdesc'];
            $record->errormessage = isset($params['errormessage']) ? $params['errormessage'] : NULL;
            $record->timemodified = time();
            // $record->question = json_encode($params['questions']);

            $existing = $DB->get_record('quiz_response', ['attemptid' => $params['attemptid']]);
            if ($existing) {

                $stored_questions = json_decode($params['questions'], true);

                // Loop over existing and apply new grade/feedback
                foreach ($stored_questions as &$q) {
                    foreach ($incoming_questions as $new) {
                        if ($q['id'] == $new['id']) {
                            $q['grade'] = $new['grade'];
                            $q['feedbackdesc'] = $new['feedbackdesc'];
                            break;
                        }
                    }
                }

                $record->question = json_encode($stored_questions, JSON_UNESCAPED_UNICODE);
                $record->id = $existing->id;
                $DB->update_record('quiz_response', $record);
                $message = 'Record has been successfully updated.';
            } else {
                /*$record->timecreated = time();
                $record->id = $DB->insert_record('quiz_response', $record);*/
                $message = 'Record has been successfully inserted.';
            }
            $transaction->allow_commit();

            return ['status' => true, 'message' => $message, 'recordid' => $record->id];

        } catch (Exception $e) {
            $transaction->rollback($e->getMessage());
            throw new \moodle_exception('Error saving grade: ' . $e->getMessage());
        }

    }

    public static function airesponse_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'True if grading succeeded'),
            'message' => new external_value(PARAM_TEXT, 'Result or warning message'),
            'recordid' => new external_value(PARAM_INT, 'Record ID'),
        ]);
    }

}
