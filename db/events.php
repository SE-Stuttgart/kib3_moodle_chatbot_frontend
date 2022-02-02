<?php

defined('MOODLE_INTERNAL') || die();

// catching all moodle events: eventname = *
$observers = array(
    // array(
    //     'eventname'   => '\core\event\course_module_completion_updated',
    //     'callback'    => '\block_chatbot\observer::course_module_completion_updated',
    //     'schedule'    => 'instant',
    //     // 'includefile' => '/blocks/chatbot/classes/event_handler.php',
    // ),
    // array(
    //     'eventname'   => '\mod_hvp\event\attempt_submitted',
    //     'callback'    => '\block_chatbot\observer::course_module_completion_updated',
    //     'schedule'    => 'instant',
    // ),
    // array(
    //     'eventname'   => '\mod_hvp\*',
    //     'callback'    => '\block_chatbot\observer::course_module_completion_updated',
    //     'schedule'    => 'instant',
    // ),
    // array(
    //     'eventname'   => '\core\event\*',
    //     'callback'    => '\block_chatbot\observer::course_module_completion_updated',
    //     'schedule'    => 'instant',
    // ),
    array(
        'eventname'   => '*',
        'callback'    => '\block_chatbot\observer::course_module_completion_updated',
        'schedule'    => 'instant',
    ),
    // array(
    //     'eventname'   => '\core\event\grade_item_created',
    //     'callback'    => '\block_chatbot\observer::course_module_completion_updated',
    //     'schedule'    => 'instant',
    // ),
    // array(
    //     'eventname'   => '\core\event\grade_item_updated',
    //     'callback'    => '\block_chatbot\observer::course_module_completion_updated',
    //     'schedule'    => 'instant',
    // ),
);


// 'eventname'   => '\mod_hvp\event\attempt_submitted'