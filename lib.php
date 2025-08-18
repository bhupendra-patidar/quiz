<?php

require_once(__DIR__ . '/../../config.php');

use core_course\customfield\course_handler;

function submission_event_data1($event, $type = 'submitted') {
    global $DB;
    try {
        // Get basic info
        $context = $event->get_context(); // context_module
        $courseid = $event->courseid;
        $userid = $event->userid;
        $user  = core_user::get_user($userid);


        $fieldshortname = 'ai_required'; // Replace with your field shortname
        $value = get_course_custom_field_value($courseid, $fieldshortname);
        if ($value != '' && $value == 1) {

            // Get course module ID from context
            $cmid = $context->instanceid;

            // Get assign ID from course_modules
            $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
            $assignid = $cm->instance;
            $assign = $DB->get_record('assign', ['id' => $assignid], 'Name, intro, grade');

            $submissionid = $event->objectid;

            if ($type == 'submitted') {

                // Get online text (if used)
                $online_text = '';
                if ($submission) {
                    $plugin_text = $DB->get_record('assignsubmission_onlinetext', ['submission' => $submissionid], 'onlinetext', IGNORE_MISSING);
                    if ($plugin_text) {
                        $online_text = $plugin_text->onlinetext;
                    }
                }

                // Get uploaded file IDs (if file submission is enabled)
                $fs = get_file_storage();
                $files = $fs->get_area_files(
                        $context->id,
                        'assignsubmission_file',
                        'submission_files',
                        $submissionid,
                        "itemid, filepath, filename",
                        false
                );
                $fileids = [];
                foreach ($files as $file) {
                    $fileids[] = $file->get_id();
                }

                $fileIDs = implode(',', $fileids);

                // Get rubric/guide data (if available)
                $gradingdata = '';
                $gradingmethod = '';
                if ($assignid) {
                    // Get the assignment's context and CM
                    $cm = get_coursemodule_from_instance('assign', $assignid, 0, false, MUST_EXIST);
                    $context = context_module::instance($cm->id);

                    // Use grading manager to access the rubric/guide controller
                    $gradingmanager = get_grading_manager($context, 'mod_assign', 'submissions');
                    $gradingmethod = $gradingmanager->get_active_method();
                    if ($gradingmanager->get_active_method()) {
                        $controller = $gradingmanager->get_controller($gradingmethod);
                        if ($controller && $controller->is_form_defined()) {
                            $gradingdata = $controller->get_definition();
                        }
                    } else {
                        $gradingmethod = '';
                    }
                }

                $previoussubmissions = get_previous_submisisons($userid, $assignid);
                $fieldshortname = 'indexing_required'; // Replace with your field shortname
                $courseindexing =     get_course_custom_field_value($courseid, $fieldshortname);

                $data = [
                    'submissionID' => $submissionid,
                    'assignmentID' => $assignid,
                    'assignmentName' => $assign->name,
                    'assignmentDesc' => $assign->intro,
                    'assignmentMaxScore' => $assign->grade,
                    'userAssignentText' => $online_text,
                    'userID' => $user->id,
                    'studentName' => $user->firstname,
                    'courseID' => $courseid,
                    'fileIDs' => $fileIDs,
                    'rubricID' => '',
                    'GradingType' => $gradingmethod,
                    'GradingData' => ($gradingdata) ? json_encode($gradingdata) : '',
                    'indexingFlag' => ($courseindexing == 1) ? true : false,
                    'previousSubmissions' => $previoussubmissions,
                ];

                $endpoint = 'https://genai-woodmontcollege-app.azurewebsites.net/api/StudentGrading/SubmitAssignmentAsync';
                $response = execute_curl_postapi($data, $endpoint);

                $record = new stdClass();
                $record->userid = $userid;
                $record->courseid = $courseid;
                $record->submissionid = $submissionid;
                $record->assignmentid = $assignid;
                $record->grade = " ";
                $record->cmid = $cmid;
                $record->feedbackdesc = '';
                $record->fileids = $fileIDs;
                $record->status = ($response->status) ? 1 : 0;
                $record->timemodified = $timecreated = time();
                $graderrow = $DB->get_record('assign_graderesponse', ['userid' => $userid, 'assignmentid' => $assignid, 'submissionid' => $submissionid], '*', IGNORE_MISSING);

                if (empty($graderrow)) {
                    $oldsubmissionid = $DB->get_field('assign_graderesponse', 'submissionid', ['userid' => $userid, 'assignmentid' => $assignid, 'isdeleted' => 0]);
                    if ($oldsubmissionid) {
                        $data = [
                            'submissionId' => $oldsubmissionid,
                            'userID' => $userid
                        ];
                        $sql = "UPDATE {assign_graderesponse} SET isdeleted = 1 where submissionid = $oldsubmissionid";
                        $DB->execute($sql);
                        $response = execute_curl_deleteapi($data);
                    }
                    $record->timecreated = $timecreated;
                    $DB->insert_record('assign_graderesponse', $record);
                } else {
                    $record->id = $graderrow->id;
                    $record->isdeleted = 0;
                    $DB->update_record('assign_graderesponse', $record);
                }
            } else {

                $data = [
                    'submissionId' => $submissionid,
                    'userID' => $userid
                ];
                $response = execute_curl_deleteapi($data);
                $graderrow = $DB->get_record('assign_graderesponse', ['userid' => $userid, 'assignmentid' => $assignid, 'submissionid' => $submissionid], '*', IGNORE_MISSING);
                if (!empty($graderrow)) {
                    $record = new stdClass();
                    $record->id = $graderrow->id;
                    $record->isdeleted = 1;
                    $record->timemodified = time();
                    $record->status = ($response->status) ? 1 : 0;
                    $DB->update_record('assign_graderesponse', $record);
                }
            }
        }
    } catch (Exception $e) {
        // Catch unexpected exceptions
        debugging('Unexpected error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}
