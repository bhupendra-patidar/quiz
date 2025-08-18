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

/**
 * Settings file.
 *
 * @package   local_quiz
 * @copyright 2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {

    global $CFG, $PAGE;

    $settings = new admin_settingpage("local_quiz", get_string("pluginname", "local_quiz"));

    $ADMIN->add("localplugins", $settings);

    $setting = new admin_setting_configtextarea(
        "local_quiz/create_end_points",
        get_string("create_end_points", "local_quiz"),
        get_string("create_end_points", "local_quiz"),
        'https://genai-woodmontcollege-app.azurewebsites.net/api/StudentGrading/SubmitQuizAsync', PARAM_RAW);
    $settings->add($setting);
    
    $setting = new admin_setting_configtext(
        "local_quiz/api_keys",
        get_string("api_keys", "local_quiz"),
        get_string("api_keys", "local_quiz"),
        123456, PARAM_RAW);
    $settings->add($setting);

}
