<?php

$functions = [

    'local_quiz_ai' => [
        'classname'   => 'local_quiz\\external\\externallib',
        'methodname'  => 'airesponse',
        'classpath'   => '',
        'description' => 'Insert ai response to a record into mdl_quiz_response table',
        'type'        => 'write',
        'ajax'        => false,
        'capabilities'=> ['moodle/quiz:view']
    ],
];
