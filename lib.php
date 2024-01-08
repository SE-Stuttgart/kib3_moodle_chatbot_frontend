<?php
defined('MOODLE_INTERNAL') || die();


function array_any(array $array, callable $fn) {
    foreach ($array as $value) {
        if($fn($value)) {
            return true;
        }
    }
    return false;
}

function array_every(array $array, callable $fn) {
    foreach ($array as $value) {
        if(!$fn($value)) {
            return false;
        }
    }
    return true;
}


/**
 * SETTINGS FUNCTIONS
 *-------------------------------------------------------------------------------------------------*/


/**
 * Get server name.
 * @return string
 */
function block_chatbot_get_server_name() {
	global $CFG;

	// if (!empty($CFG->block_chatbot_server_name)) {
	// 	return $CFG->block_chatbot_server_name;
	// } else {
	return "127.0.0.1";
	// }
}

function block_chatbot_get_event_server_name() {
	// global $CFG;
	// if (!empty($CFG->block_chatbot_event_server_name)) {
	// 	return $CFG->block_chatbot_event_erver_name;
	// } else {
	return "chatbot";
	// }
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


function update_recently_viewed_completion($userid, $courseid, $coursemoduleid, $time, $completionstate) {
	// keeping tack of course module completions in our custom history tables
	global $DB;

	// check if we already have an entry
	if($DB->record_exists('chatbot_recentlyaccessed', array('userid' => $userid, 
													 		'courseid' => $courseid,
															'cmid' => $coursemoduleid))) {
		$item = $DB->get_record('chatbot_recentlyaccessed', array('userid' => $userid, 
																	'courseid' => $courseid,
																	'cmid' => $coursemoduleid));
		$item->completionstate = $completionstate;
		$item->timeaccess = $time;
		$DB->update_record('chatbot_recentlyaccessed', $item);
	} else {
		// create a new entry
		$item = new stdClass;
		$item->userid = $userid;
		$item->cmid = $coursemoduleid;
		$item->courseid = $courseid;
		$item->completionstate = $completionstate;
		$item->timeaccess = $time;
		$DB->insert_record('chatbot_recentlyaccessed', $item);	
	}
}

function update_recently_viewed($userid, $courseid, $coursemoduleid, $time) {
	// keeping tack of course module views in our custom history tables
	global $DB;
	// check if we already have an entry
	if($DB->record_exists('chatbot_recentlyaccessed', array('userid' => $userid, 
													 		'courseid' => $courseid,
															'cmid' => $coursemoduleid))) {
		$item = $DB->get_record('chatbot_recentlyaccessed', array('userid' => $userid, 
																	'courseid' => $courseid,
																	'cmid' => $coursemoduleid));
		$item->timeaccess = $time;
		$DB->update_record('chatbot_recentlyaccessed', $item);
	} else {
		// create a new entry
		$item = new stdClass;
		$item->userid = $userid;
		$item->cmid = $coursemoduleid;
		$item->courseid = $courseid;
		$item->completionstate = 0;
		$item->timeaccess = $time;
		$DB->insert_record('chatbot_recentlyaccessed', $item);
	}
}

function get_open_section_module_ids($userid, $sectionid, $include_types=["url", "book", "resource", "quiz", "h5pactivity"]) {
	// Get all the course modules with types whitelisted in $include_types for the specified section that are not marked as completed. 
	global $DB;
	// get all section module ids
	$all_section_module_ids = array_map('intval', explode(",", $DB->get_record("course_sections", array('id' => $sectionid), 'sequence')->sequence)); // array of course module ids
	// filter the section modules by the type whitelist
	// TODO use the moodle sql_ compatibility functions instead of custom execute
	[$_insql_sectionmoduleids, $_insql_sectionmoduleids_params] = $DB->get_in_or_equal($all_section_module_ids, SQL_PARAMS_NAMED, 'sectionmoduleids');
	[$_insql_types, $_insql_types_params] = $DB->get_in_or_equal($include_types, SQL_PARAMS_NAMED, 'types');

	$filtered_section_module_ids = $DB->get_fieldset_sql("SELECT cm.id 
												 FROM {course_modules} AS cm
												 JOIN {modules} ON cm.module = {modules}.id
												 WHERE cm.id $_insql_sectionmoduleids
												 AND {modules}.name $_insql_types
												 AND cm.visible = 1",
												 array_merge($_insql_sectionmoduleids_params, $_insql_types_params));
	// TODO add moodle sql comp functions here too
	[$_insql_filteredsectionmoduleids, $_insql_filteredsectionmoduleids_params] = $DB->get_in_or_equal($filtered_section_module_ids, SQL_PARAMS_NAMED, 'filteredsectionmoduleids');
	$completed_section_module_ids = $DB->get_fieldset_sql("SELECT coursemoduleid
												  FROM {course_modules_completion}
												  WHERE coursemoduleid $_insql_filteredsectionmoduleids
												  AND completionstate = 1
												  AND userid = :userid",
												array_merge($_insql_filteredsectionmoduleids_params,
														    array("userid" => $userid))
												);
	$difference = array_values(array_diff($filtered_section_module_ids, $completed_section_module_ids));
	// var_dump($filtered_section_module_ids);
	// var_dump($completed_section_module_ids);
	// var_dump($difference);
	return $difference;
}

function section_is_completed($userid, $sectionid, $include_types=["url", "book", "resource", "quiz", "h5pactivity"]) {
	// Check if all course modules with types whitelisted in $include types are completed for the given section.
	$open_module_ids = get_open_section_module_ids($userid, $sectionid, $include_types);
	// var_dump($open_module_ids);
	return count($open_module_ids) == 0;
}

function get_all_branch_section_ids($userid, $sectionid) {
	// Return all section ids that are part of the same branch as the given section.
	global $DB;

	// Figure out current branch
	$sectionname = $DB->get_field('course_sections', 'name', array('id' => $sectionid));
	// var_dump($sectionname);
	if(preg_match('/Thema ([A-Z])\d(-\d+)?:/', $sectionname, $matches)) {
		// Extract topic letter
		// $matches[0] contains the entire matched string
		// $matches[1] contains the value of the first capture group
		$topicletter = $matches[1]; // e.g., A, B, ...
		// var_dump($topicletter);

		// find all sections belonging to the same topic branch
		$_likesql_topicletter = $DB->sql_like('name', ':topicletter');
		$result = $DB->get_fieldset_sql("SELECT id FROM {course_sections} WHERE $_likesql_topicletter",
									 array("topicletter" => "%Thema " . $topicletter . "%"));
		// var_dump($result);
		return [$topicletter, $result];
	}

	return [null, []];
}