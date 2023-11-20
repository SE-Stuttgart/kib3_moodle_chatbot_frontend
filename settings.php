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
	$default = "127.0.0.1";
	$settings->add(new admin_setting_configtext($name, $title, $description, $default, PARAM_HOST));

	// Event Server Name
	$name = 'block_chatbot_event_server_name';
	$title = get_string('event_server_name', 'block_chatbot');
	$description = get_string('event_server_name_desc', 'block_chatbot');
	$default = "chatbot";
	$settings->add(new admin_setting_configtext($name, $title, $description, $default, PARAM_HOST));

	// Server Port
	$name = 'block_chatbot_server_port';
	$title = get_string('server_port', 'block_chatbot');
	$description = get_string('server_port_desc', 'block_chatbot');
	$default = 44123;
	$settings->add(new admin_setting_configtext($name, $title, $description, $default, PARAM_INT));
	
	// Chat Container
	$name = 'block_chatbot_container';
	$title = get_string('chat_container', 'block_chatbot');
	$description = get_string('chat_container_desc', 'block_chatbot');
	$default = 'Body';
	$choices = array('body' => 'Body', '#page' => 'Page');
	$settings->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

	// Course configuration: which courses should the plugin be active for.
	$courses = get_courses("all", "c.sortorder ASC", "c.id,c.fullname");
	$courselist = array();
	// Get list of all courses and add those to the offered selection.
	foreach ($courses as $course) {
		$courselist[$course->id] = $course->fullname;
	}
	$settingspage->add(new admin_setting_configmulticheckbox(
		'block_chatbot/courseids',
		get_string('courses', 'block_chatbot'),
		get_string('courses_description', 'block_chatbot'),
		null,
		$courselist));

}
