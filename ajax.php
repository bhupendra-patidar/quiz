<?php

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_login();

$action = required_param('action', PARAM_ALPHA);
$attemptid = required_param('attemptid', PARAM_INT);
$qid = required_param('qid', PARAM_INT);
// $userid = required_param('userid', PARAM_INT);

global $DB;

$data = $DB->get_record('quiz_response', ['attemptid' => $attemptid]);

if (!empty($data)) {
    $grade = 0;
    $feedbackdesc = '';
    $errormessage = ($data->errormessage == '') ? NULL : $data->errormessage;
    if ($data->question) {
        $stored_questions = json_decode($data->question, true);
        // Loop over existing and apply new grade/feedback
        foreach ($stored_questions as &$q) {
            if ($q['id'] == $qid) {
                $grade = $q['grade'];
                $feedbackdesc = $q['feedbackdesc'];
                break;
            }
        }
    }

    switch ($action) {
        case 'getgrades':
            $response = ['status' => 1, 'grade' => $grade, 'feedback' => $feedbackdesc,'errormessage' => $errormessage];
            break;
        default:
            $response = ['status' => 0, 'message' => 'Invalid action'];
            break;
    }

} else {
    $response = ['status' => 0];
}

echo json_encode($response);
die;
