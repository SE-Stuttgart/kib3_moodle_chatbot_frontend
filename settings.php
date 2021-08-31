<?php

/**
 * Settings for the 'block_chatbot' component.
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
	
	/*
	 * Properties
	 */
	
	// Server Name
	$name = 'block_chatbot_server_name';
	$title = get_string('server_name', 'block_chatbot');
	$description = get_string('server_name_desc', 'block_chatbot');
	$default = $_SERVER['SERVER_NAME'];
	$settings->add(new admin_setting_configtext($name, $title, $description, $default, PARAM_CLEAN));

	// Server Port
	$name = 'block_chatbot_server_port';
	$title = get_string('server_port', 'block_chatbot');
	$description = get_string('server_port_desc', 'block_chatbot');
	$default = 8000;
	$settings->add(new admin_setting_configtext($name, $title, $description, $default, PARAM_INT));
	
	// Chat Container
	$name = 'block_chatbot_container';
	$title = get_string('chat_container', 'block_chatbot');
	$description = get_string('chat_container_desc', 'block_chatbot');
	$default = 'Body';
	$choices = array('body' => 'Body', '#page' => 'Page');
	$settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

}