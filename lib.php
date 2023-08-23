<?php
defined('MOODLE_INTERNAL') || die();



/**
 * SETTINGS FUNCTIONS
 *-------------------------------------------------------------------------------------------------*/


/**
 * Get server name.
 * @return string
 */
function block_chatbot_get_server_name() {
	global $CFG;

	if (!empty($CFG->block_chatbot_server_name)) {
		return $CFG->block_chatbot_server_name;
	} else {
	    return "127.0.0.1";
	}
}


/**
 * Get server port.
 * @return string
 */
function block_chatbot_get_server_port() {
	global $CFG;

	if (!empty($CFG->block_chatbot_server_port)) {
		return $CFG->block_chatbot_server_port;
	} else {
	    return 44122;
	}
}


/**
 * Get chat container.
 * @return string
 */
function block_chatbot_get_chat_container() {
	global $CFG;

	if (!empty($CFG->block_chatbot_container)) {
		return $CFG->block_chatbot_container;
	} else {
	    return 'body';
	}
}
