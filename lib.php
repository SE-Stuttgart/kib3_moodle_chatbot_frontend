<?php
defined('MOODLE_INTERNAL') || die();


function any($array) {
    return array_reduce($array, function($carry, $item) {
        return $carry || $item;
    }, false);
}

function all($array) {
    return array_reduce($array, function($carry, $item) {
        return $carry && $item;
    }, true);
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
	if(empty($filtered_section_module_ids)) {
		return array();
	}
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


function course_module_is_completed($userid, $cmid) {
	global $DB;
	return $DB->record_exists_sql("SELECT * FROM {course_modules_completion}
								   WHERE userid = :userid
								   AND coursemoduleid = :cmid
								   AND completionstate = 1",
								array(
									"userid" => $userid,
									"cmid" => $cmid
								)
							);
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



function get_module_type_name($cmid) {
	global $DB;
	return $DB->get_field_sql("SELECT {modules}.name 
							   FROM {modules}
							   JOIN {course_modules} ON {course_modules}.module = {modules}.id
							   WHERE {course_modules}.id = :cmid",
							array(
								"cmid" => $cmid
							)
				);
}

function get_course_module_name_and_typename($cmid) {
	global $DB;
	$typename = get_module_type_name($cmid);
	$cm = $DB->get_record("course_modules", array("id" => $cmid), "instance,section");
	if($typename == "book") {
		$cmname = $DB->get_field("book", "name", array("id" => $cm->instance));
	} else if($typename == "assign") {
		$cmname = $DB->get_field("assign", "name", array("id" => $cm->instance));
	} else if($typename == "resource") {
		$cmname = $DB->get_field("resource", "name", array("id" => $cm->instance));
	} else if($typename == "glossary") {
		$cmname = "Glossar";
	} else if($typename == "h5pactivity") {
		$cmname = $DB->get_field("h5pactivity", "name", array("id" => $cm->instance));
	} else if($typename == "page") {
		// Section hat einen Attribut name, dass module selbst nicht
		$cmname = $DB->get_field("course_sections", "name", array("id" => $cm->section));
	} else if($typename == "url") {
		$cmname = $DB->get_field("url", "name", array("id" => $cm->instance));
	} else if($typename == "icecreamgame") {
		$cmname = "Spiel zum Einstieg: Bestellen Sie Eis!";
	} else {
		$cmname = null;
	}
	return [$cmname, $typename];
}

function _clean_name($name) {
	# handle some special cases where names were not entered consistently in the ZQ content
	if($name == "Thema C1-1: Das Koordinatensystem - Was ist wo?") {
		return "Thema C1-1: Das Koordinatensystem";
	} else if($name == "Quizzes zum Thema C1-1: Das Koordinatensystem") {
		return "Quizzes zum Thema C1-1: Das Koordinatensystem - Was ist wo?";
	}
	return $name;
}

function is_quiz_section($sectionname) {
	return str_contains(strtolower($sectionname), 'quiz');
}

function get_quiz_section_id($content_section_id, $section_name) {
	global $DB;
	if(is_quiz_section($section_name)) {
		return $content_section_id;
	} 
	# find related quiz section
	$_likesql_quiz_section_name = $DB->sql_like("name", ":sectionname");
	$result = $DB->get_field_sql("SELECT id FROM {course_sections} WHERE $_likesql_quiz_section_name",
								 array(
									"sectionname" => "Quizzes zum " . _clean_name($section_name)
								)
							);
	return $result;
}

function get_content_section_id($quiz_section_id, $section_name) {
	global $DB;
	if(!is_quiz_section($section_name)) {
		return $quiz_section_id;
	}
	$_likesql_content_section_name = $DB->sql_like("name", ":sectionname");
	$result = $DB->get_field_sql("SELECT id FROM {course_sections} WHERE $_likesql_content_section_name",
								 array(
									"sectionname" => str_replace('Quizzes zum ', "", _clean_name($section_name))
								)
							);
	return $result;
}


function get_finalgrade_bygradeitem($userid, $gradeitemid) {
	global $DB;
	return $DB->get_field("grade_grades", "finalgrade", array(
			"userid" => $userid,
			"itemid" => $gradeitemid
		)
	);
}

function _recursive_availability($json_tree, $userid) {
	$condition_values = array();
	foreach($json_tree->c as $condition) {
		if(property_exists($condition, 'c')) {
			// nested condition
			array_push($condition_values, _recursive_availability($condition, $userid));
		} else {
			if($condition->type == "completion") {
				$completed = course_module_is_completed($userid, $condition->cm);
				array_push($condition_values, $completed);
			} else if($condition->type == 'grade') {
				$finalgrade = get_finalgrade_bygradeitem($userid, $condition->id);
				if((!is_null($finalgrade))) {
					if(property_exists($condition, "min")) {
						array_push($condition_values, $finalgrade >= $condition->min);
					}
					if(property_exists($condition, "max")) {
						array_push($condition_values, $finalgrade <= $condition->max);
					}
				}
			} else {
				// TODO time etc. -> implement later
			}
		}
	}
	if(empty($condition_values)) {
		// no conditions found -> none to evaluate
		return true;
	}

	// evaluate all condition outcomes
	if($json_tree->op == '|') {
		// or
		return any($condition_values);
	} else {
		// and
		return all($condition_values);
	}
}

function is_available($json_conditions, $userid) {
	if(is_null($json_conditions) || empty($json_conditions)) {
		# no json at all - is available
		return true;
	}
	// there are conditions - parse json
	$data = json_decode($json_conditions);
	if(!property_exists($data, 'c')) {
		// no conditions in json
		return true;
	}
	return _recursive_availability($data, $userid);
}

function is_available_course_section($userid, $sectionid, $sectionname, $visible, $json_conditions) {
	if(is_quiz_section($sectionname)) {
		return get_content_section_id($sectionid, $sectionname);
	}
	return $visible && is_available($json_conditions, $userid);
}

function is_available_course_module($userid, $cmid) {
	global $DB;

	// first, check if the module's section is available 
	$section = $DB->get_record_sql("SELECT section.id, section.name
								    FROM {course_sections} as section
									JOIN {course_modules} as cm ON cm.section = section.id
									WHERE cm.id = :cmid", array(
		"cmid" => $cmid 
	));
	if(is_quiz_section($section->name)) {
		// some quiz sections are available, even though the content section is not.
		// this fixes it.
		$content_section = $DB->get_record("course_sections",
			array(
				"id" => get_content_section_id($section->id, $section->name),
			),
			"id,name,visible,availability"
		);
		if((!is_null($content_section)) && (!is_available_course_section($userid, $content_section->id, $content_section->name, $content_section->visible, $content_section->availability))) {
			return false;
		}
	} else {
		// some content sections are available, even though the quiz section is not.
		// this fixes it.
		$quiz_section = $DB->get_record("course_sections",
			array(
				"id" => get_quiz_section_id($section->id, $section->name),
			),
			"id,name,visible,availability"
		);
		if((!is_null(($quiz_section)) && (!is_available_course_section($userid, $quiz_section->id, $quiz_section->name, $quiz_section->visible, $quiz_section->availability)))) {
			return false;
		}
	}

	// sections are available, check module availablity
	$cm = $DB->get_record("course_modules",
		array(
			"id" => $cmid
		),
		"visible,availability"
	);

	return $cm->visible && is_available($cm->availability, $userid);
}

function get_section_id_and_name($cmid) {
	global $DB;
	$result = $DB->get_records_sql_menu("SELECT {course_sections}.id, {course_sections}.name 
								FROM {course_sections}
								JOIN {course_modules} ON {course_sections}.id = {course_modules}.section
								WHERE {course_modules}.id = :cmid
								LIMIT 1",
								array("cmid" => $cmid)
							);
	$sectionid = array_keys($result)[0];
	return array(
		$sectionid, 
		$result[$sectionid]
	);
}


function get_user_course_completion_percentage($userid, $courseid, $includetypes) {
	global $DB;
	// calculate current course progress percentage
	[$_insql_types, $_insql_types_params] = $DB->get_in_or_equal(explode(",", $includetypes), SQL_PARAMS_NAMED, 'types');
	$total_num_modules = $DB->count_records_sql("SELECT COUNT({course_modules}.id)
												FROM {course_modules}
												JOIN {modules} ON {modules}.id = {course_modules}.module
												WHERE {course_modules}.course = :courseid
												AND {modules}.name $_insql_types",
										array_merge(
											array("courseid" => $courseid),
											$_insql_types_params    
										)
	);
	$done_modules = $DB->count_records_sql("SELECT COUNT({course_modules_viewed}.id)
											FROM {course_modules_viewed}
											JOIN {course_modules} ON {course_modules}.id = {course_modules_viewed}.coursemoduleid
											JOIN {modules} ON {modules}.id = {course_modules}.module
											WHERE {course_modules_viewed}.userid = :userid
											AND {course_modules}.course = :courseid
											AND {modules}.name $_insql_types",
										array_merge(
											array("userid" => $userid,
												"courseid" => $courseid),
											$_insql_types_params    
										)
	);
	return $done_modules / $total_num_modules;
}


function get_badge_id_by_name($name) {
	global $DB;
	$_likesql_badgename = $DB->sql_like('name', ':badgename');
	return $DB->get_field_sql("SELECT id FROM {badge} WHERE $_likesql_badgename",
							 array("badgename" => $name . "%"));
}

function get_badge_completion_percentage($userid, $cmids) {
	// calculate percentage of completed modules in the list of given course module ids
	$todo_modules = array();
	foreach($cmids as $cmid) {
		if(!course_module_is_completed($userid, $cmid)) {
			array_push($todo_modules, intval($cmid));
		}
	}
	return array(
		1.0 - count($todo_modules) / count($cmids),
		$todo_modules
	);
}