<?php

defined('MOODLE_INTERNAL') || die();


$observers = array(
    array(
        'eventname'   => '\core\event\course_module_completion_updated', // "*"
        'callback'    => '\block_chatbot\observer::course_module_completion_updated',
        // 'includefile' => '/blocks/chatbot/classes/event_handler.php',
    ),
);


// 'eventname'   => '\mod_hvp\event\attempt_submitted'