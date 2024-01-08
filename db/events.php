<?php

defined('MOODLE_INTERNAL') || die();

// catching all moodle events: eventname = *
$observers = array(
    array(
        'eventname'   => '\core\event\course_module_viewed',
        'callback'    => '\block_chatbot\observer::course_module_completion_viewed',
        'schedule'    => 'instant',
    ),
    array(
        'eventname'   => '\core\event\course_module_completion_updated',
        'callback'    => '\block_chatbot\observer::course_module_completion_updated',
        'schedule'    => 'instant',
    ),
    array(
        'eventname'   => '*',
        'callback'    => '\block_chatbot\observer::forward_event_to_chatbot',
        'schedule'    => 'instant',
    ),
);


// 'eventname'   => '\mod_hvp\event\attempt_submitted'