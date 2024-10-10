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

function startsWith( $haystack, $needle ) {
	$length = strlen( $needle );
	return substr( $haystack, 0, $length ) === $needle;
}

function strContains($haystack, $needle) {
	return (strpos($haystack, $needle) !== false);
}


/**
 * SETTINGS FUNCTIONS
 *-------------------------------------------------------------------------------------------------*/

function block_chatbot_get_protocol() {
	global $CFG;
	return startsWith($CFG->wwwroot, 'https://')? "https" : "http";
}

/**
 * Get server name.
 * @return string
 */
function block_chatbot_get_server_name() {
	$server_name = get_config("block_chatbot", "server_name");
	if (empty($server_name)) {
		$server_name = "127.0.0.1";
	}
	return $server_name;
}

function block_chatbot_get_event_server_name() {
	$event_server_name = get_config("block_chatbot", "event_server_name");
	if (empty($event_server_name)) {
		$event_server_name = "chatbot";
	}
	return $event_server_name;
}


/**
 * Get server port.
 * @return string
 */
function block_chatbot_get_server_port() {
	$server_port = get_config("block_chatbot", "server_port");
	if (empty($server_port)) {
		$server_port = 44122;
	}
	return $server_port;
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


function get_id_by_typename($typename) {
    global $DB;

	// Initialize the cache
	$typename_to_id_cache = cache::make('block_chatbot', 'typename_to_id_cache');
	$id_to_typename_cache = cache::make('block_chatbot', 'id_to_typename_cache');

	// get the value from the cache
	$id = $typename_to_id_cache->get($typename);

	// Check if the value exists in the cache
	if (!$id) {
		// Fetch the value from the database
		$id = $DB->get_field('modules', 'id', array('name' => $typename));
		
		// Store the value in the cache
		$id_to_typename_cache->set($id, $typename);
		$typename_to_id_cache->set($typename, $id);
	}

	return $id;
}
function get_typename_by_id($module_id) {
    global $DB;

    // Initialize the cache
    $typename_to_id_cache = cache::make('block_chatbot', 'typename_to_id_cache');
	$id_to_typename_cache = cache::make('block_chatbot', 'id_to_typename_cache');

	// get the value from the cache
	$typename = $id_to_typename_cache->get($module_id);

    // Check if the value exists in the cache
    if (!$typename) {
        // Fetch the value from the database
        $typename = $DB->get_field('modules', 'name', array('id' => $module_id));
        
        // Store the value in the cache
        $id_to_typename_cache->set($module_id, $typename);
		$typename_to_id_cache->set($typename, $module_id);
    }

    return $typename;
}


function course_modules_by_topic($topic, $courseid, $includetypes = ["url", "book", "resource", "quiz", "h5pactivity", "icecreamgame"]) {
    // returns all course modules for the topic or topic prefix (e.g., "A", "A1-1", "A2", ...) with
	// - course module id
	// - module type id
	// - instance id
	// - section id
    global $DB;

    $type_ids = array_map('get_id_by_typename', $includetypes);
    [$_insql_types, $_insql_types_params] = $DB->get_in_or_equal($type_ids, SQL_PARAMS_NAMED, 'types');

	$topic_like = $DB->sql_like('tag.rawname', ':topic');
    $course_modules = $DB->get_records_sql("SELECT cm.id as cmid, cm.module as module, cm.instance as instance, cm.section
                                       FROM {course_modules} as cm
									   JOIN {tag} as tag ON tag.rawname LIKE :topic
									   JOIN {tag_instance} as ti ON ti.tagid = tag.id
                                       WHERE cm.course = :courseid
                                       AND cm.module $_insql_types
                                       AND $topic_like ",
                                       array_merge(
											array(
												"courseid" => $courseid,
												"topic" => $topic . "%"
                                       		),
											$_insql_types_params
										)
    );
	return $course_modules;
}


function update_recently_viewed($userid, $courseid, $coursemoduleid, $time, $completionstate) {
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
		$item->completionstate = $completionstate;
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

function _extract_id_from_record($record) {
	return $record->id;
}

function get_open_section_module_ids($userid, $courseid, $topic, $include_types=["url", "book", "resource", "quiz", "h5pactivity", "icecreamgame"]) {
	// Get all the course modules with types whitelisted in $include_types for the specified section that are not marked as completed. 
	global $DB;
	// get all topic modules
	$filtered_section_module_ids = array_map('_extract_id_from_record', course_modules_by_topic($topic, $courseid, $include_types));
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
	// also exclude course modules that are not tracked for completion, but were seen once (which is why they have an entry in the chatbot history)
	$seen_but_not_tracked_section_module_ids = $DB->get_fieldset_sql(
		"SELECT ra.cmid
		 FROM {chatbot_recentlyaccessed} as ra
		 JOIN {course_modules} ON {course_modules}.id = ra.cmid
		 WHERE ra.userid = :userid
		 AND ra.courseid = :courseid
		 AND {course_modules}.completion = 0",
		array(
			"userid" => $userid,
			"courseid" => $courseid
		)
	);
	$difference = array_values(array_diff($filtered_section_module_ids, $completed_section_module_ids, $seen_but_not_tracked_section_module_ids));
	// var_dump($filtered_section_module_ids);
	// var_dump($completed_section_module_ids);
	// var_dump($difference);
	return $difference;
}

function section_is_completed($userid, $sectionid, $include_types=["url", "book", "resource", "quiz", "h5pactivity", "icecreamgame"]) {
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
							) || // Also count modules as completed that have been viewed already, but are not tracking completion
			$DB->record_exists_sql("SELECT * FROM {chatbot_recentlyaccessed} as ra
								    JOIN {course_modules} ON {course_modules}.id = ra.cmid
									WHERE ra.userid = :userid
									AND ra.cmid = :cmid
									AND {course_modules}.completion = 0",
								array(
									"userid" => $userid,
									"cmid" => $cmid
								));
}

function get_all_branch_section_ids($userid, $courseid, $sectionid) {
	// Return all section ids that are part of the same branch as the given section.
	global $DB;

	// Figure out current branch:
	// check topics for course modules in current section
	$cm_ids = $DB->get_fieldset_sql("SELECT id
									 FROM {course_modules}
									 WHERE section = :sectionid
									 AND course = :courseid",
					array("sectionid" => $sectionid,
								  "courseid" => $courseid)
	);
	[$_insql_cmids, $_insql_cmids_params] = $DB->get_in_or_equal($cm_ids, SQL_PARAMS_NAMED, 'cmids');
	// TODO only extract topic letter to keep current functionality
	// TODO update course id in function call
	$topic_names = $DB->get_field_sql("SELECT DISTINCT t.name
											FROM {tag} as t
											JOIN {tag_instance} as ti ON ti.tagid = t.id
											WHERE ti.itemid $_insql_cmids",
											$_insql_cmids_params
	);

	// get all tags matching the topic pattern
	$topic_branch = array();
	$sectionids = array();
	foreach($topic_names as $topic_name) {
		if(preg_match('/topic:([a-z])\d+(-\d+)?[a-z]:/', $topic_name, $matches)) {
			// Extract topic letter
			// $matches[0] contains the entire matched string
			// $matches[1] contains the value of the first capture group
			$topicletter = $matches[1]; // e.g., A, B, ...
			if(!in_array($topicletter, $topic_branch)) {
				array_push($topic_branch, $topicletter);

				// get all section ids that are part of the same branch
				$_likesql_topicletter = $DB->sql_like('name', ':topicletter');
				$topic_section_ids = $DB->get_fieldset_sql("SELECT DISTINCT cm.section
									FROM {course_modules} as cm
									JOIN {tag_instance} as ti ON ti.itemid = cm.id
									JOIN {tag} as t ON t.id = ti.tagid
									WHERE $_likesql_topicletter
									AND cm.course = :courseid",
						array_merge(array("courseid" => $courseid,
										  "topicletter" => "topic:" . $topicletter . "%")
								)
				);
				$sectionids[$topicletter] = array_merge($sectionids, $topic_section_ids);
			}
		}
	}
	return $sectionids;
}


function is_prefered_usercontenttype($userid, $cmid) {
	// checks whether the type of the given course module corresponds to the user's prefered content type
	global $DB;
	$prefered_type_id = $DB->get_field("chatbot_usersettings", "preferedcontenttype", array("userid" => $userid));
	$cm_type_id = $DB->get_field("course_modules", "module", array("id" => $cmid));
	return $prefered_type_id == $cm_type_id;
}

/**
 * Checks if two strings share the same prefix as specified by a regular expression read from the configuration:
 * `local_autocompleteactivities_matchingprefix`.
 *
 * The first group of the matches of both strings are used for the comparison.
 * All text is lowercased and trimmed for comparison.
 *
 * @param string $name First comparand
 * @param string $comparisonname Second comparand
 *
 * @return bool True if the prefixes are equal according to the comparison rules.
 */
function prefix_match($name, $comparisonname) {
	// Returns true if name and comparison name share a comon prefix up to the start of a bracket sign,
	// or are exactly the same, else false.
	// Prepare strings for regex search and comparison.
	$cleanname = strtolower($name);
	$cleancomparison = strtolower($comparisonname);

	// Find prefix matches.
	$namematches = [];
	preg_match('/(.*)[(]/U',  $cleanname, $namematches);
	$comparisonmatches = [];
	preg_match('/(.*)[(]/U',  $cleancomparison, $comparisonmatches);

	// At least one matching group per string expected, otherwise one of them doesn't have a prefix group.
	if (count($namematches) < 2 || count($comparisonmatches) < 2) {
		return false;
	}

	// Cleanup.
	$cleanname = trim($namematches[1]);
	$cleancomparison = trim($comparisonmatches[1]);

	// Compare prefixes.
	return $cleanname == $cleancomparison;
}

function get_prefered_usercontenttype_cmid($userid, $cmid) {
	// checks whether the given course module has an alternative in the user's preferered content style.
	// if so, return alternative - otherwise, return given cmid again
	global $DB;
	// check if module is a valid learning resource, and not assignment / quiz material
	if(in_array(get_module_type_name($cmid), array("url","book","resource"))) {
		// try to find same content with different format
		// -> first, get all course modules from module's section
		$prefered_type_id = $DB->get_field("chatbot_usersettings", "preferedcontenttype", array("userid" => $userid));
		$cmid_name = get_course_module_name($cmid);
		$section_id = $DB->get_field("course_modules", "section", array("id" => $cmid));
		$section_cmids = $DB->get_field("course_sections", "sequence", array("id" => $section_id));
		foreach(explode(",", $section_cmids) as $candidate_id) {
			$candidate_type = $DB->get_field("course_modules", "module", array("id" => $candidate_id));
			if($candidate_type == $prefered_type_id) {
				$candidate_name = get_course_module_name($candidate_id);
				if(prefix_match($cmid_name, $candidate_name)) {
					// found alternative content type for current course module
					return $candidate_id;
				}
			}
		}
	}
	return $cmid;
}


function get_module_type_name($cmid) {
	global $DB;
	return get_typename_by_id(
		$DB->get_field("course_modules", "module",
					 		  array("id" => $cmid))
	);
}

function get_course_module_name($cmid) {
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
	return $cmname;
}

function get_course_module_name_and_typename($cmid) {
	global $DB;
	$typename = get_module_type_name($cmid);
	$cmname = get_course_module_name($cmid);
	return [$cmname, $typename];
}

function is_quiz_section($sectionid) {
	// return true, if at least one module in this section is a quiz / h5pactivity
	global $DB;
	$course_module_types = $DB->get_fieldset_sql("SELECT module
												  FROM {course_modules}
												  WHERE section = :sectionid",
												array("sectionid" => $sectionid)
	);
	foreach($course_module_types as $type) {
		if(in_array(get_typename_by_id($type), array("quiz", "h5pactivity"))) {
			return true;
		}
	}
	return false;
}

function get_content_section_ids($quiz_section_tag) {
	global $DB;
	if(!is_quiz_section($quiz_section_tag)) {
		return $quiz_section_tag;
	}

	// todo finish this function
	// get all sections with course modules that match the given tag
	$section_ids = $DB->get_fieldset_sql("SELECT DISTINCT cm.section
										  FROM {course_modules} as cm
										  JOIN {tag_instance} as ti ON ti.itemid = cm.id
										  JOIN {tag} as t ON t.id = ti.tagid
										  WHERE t.id = :tagid",
										array("tagid" => $quiz_section_tag)
	);
	// go through all sections, and collect the sections that do not contain a quiz
	$content_section_ids = array();
	foreach($section_ids as $section_id) {
		if(!is_quiz_section($section_id)) {
			array_push($content_section_ids, $section_id);
		}
	}
	return $content_section_ids;
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


function is_available_course_section($userid, $topic_name) {
	global $DB;

	// for given topic id
	// 1. get section ids
	$infos = $DB->get_records_sql("SELECT cm.section as section, cm.module as module, cm.availability as cm_available, cm.visible as cm_visible, cs.availability as cs_available, cs.visible as cs_visible
									 	  FROM {course_modules} as cm
										  JOIN {course_section} as cs
										  JOIN {tag_instance} as ti ON ti.itemid = cm.id
										  JOIN {tag} as t ON t.id = ti.tagid
										  WHERE t.rawname = :topic_name",
							array("topic_name" => $topic_name)
						);
	// sort infos by section id
	$infos_by_section = array();
	foreach($infos as $info) {
		if(!array_key_exists($info->section, $infos_by_section)) {
			$infos_by_section[$info->section] = array();
		}
		array_push($infos_by_section[$info->section], $info);
	}

	// 2. check that all sections are available
	$found_non_quiz_cm = false;
	foreach($infos_by_section as $sectionid => $section_infos) {
		if($section_infos[0]->cs_visible == false || !is_available($section_infos[0]->cs_available, $userid)) {
			return false;
		}
		foreach($section_infos as $info) {
			if(get_typename_by_id($info->module) != "quiz" && get_typename_by_id($info->module) != "h5pactivity") {
				// current course module is not a quiz, check if it is available
				if($info->cm_visible == true && is_available($info->cm_available, $userid)) {
					$found_non_quiz_cm = true;
					break;
				}
			}
		}
	}
						
	// 3. check if at least 1 module is available that is not a quiz
	return $found_non_quiz_cm;
}

function is_available_course_module($userid, $cmid, $includetypes = "url,book,resource,h5pactivity,quiz,icecreamgame") {
	global $DB;

	// first, check if the module's section is available 
	$section = $DB->get_record_sql("SELECT section.id, section.name
								    FROM {course_sections} as section
									JOIN {course_modules} as cm ON cm.section = section.id
									WHERE cm.id = :cmid", array(
		"cmid" => $cmid 
	));
	$_like_topic_name = $DB->sql_like('tag.rawname', ':topicname');
	$cm_topic_tag_id = $DB->get_field_sql("SELECT ti.tagid
											FROM {tag_instance} as ti
											JOIN {tag} as t ON t.id = ti.tagid
											WHERE ti.itemid = :cmid
											AND $_like_topic_name",
											array("cmid" => $cmid,
												  "topicname" => "topic:%")
	);
	if(!is_available_course_section($userid, $cm_topic_tag_id)) {
		return false;
	}

	// sections are available, check module availablity
	$cm = $DB->get_record("course_modules",
		array(
			"id" => $cmid
		),
		"visible,availability"
	);
	if(!strContains($includetypes, get_module_type_name($cmid))) {
		// we don't care about labels etc.
		return $cm->visible;
	}

	return $cm->visible && is_available($cm->availability, $userid);
}

function get_topic_id_and_name($cmid) {
	global $DB;
	$result = $DB->get_record_sql("SELECT {tag}.id, {tag}.rawname
								FROM {tag}
								JOIN {tag_instance} ON {tag_instance}.tagid = {tag}.id
								WHERE {tag_instance}.itemid = :cmid
								LIMIT 1",
								array("cmid" => $cmid)
							);
	return array(
		$result->id,
		$result->rawname
	);
}

function count_completed_course_modules($userid, $courseid, $includetypes, $starttime, $endtime) {
	global $DB;

	[$_insql_types, $_insql_types_params] = $DB->get_in_or_equal(explode(",", $includetypes), SQL_PARAMS_NAMED, 'types');
	if($endtime <= 0 || $endtime <= $starttime) {
		// no time interval - return count of all viewed course modules
		$count = $DB->count_records_sql("SELECT COUNT({course_modules_completion}.id)
										 FROM {course_modules_completion}
										 JOIN {course_modules} ON {course_modules}.id = {course_modules_completion}.coursemoduleid
										 JOIN {modules} ON {modules}.id = {course_modules}.module
										 WHERE {course_modules_completion}.userid = :userid
										 AND {course_modules}.course = :courseid
										 AND {modules}.name $_insql_types",
										array_merge(array(
											"userid" => $userid,
											"courseid" => $courseid,
										), $_insql_types_params)
									);
	} else {
		// time interval - return count of viewed course modules during given interval only
		$count = $DB->count_records_sql("SELECT COUNT({course_modules_completion}.id)
										 FROM {course_modules_completion}
										 JOIN {course_modules} ON {course_modules}.id = {course_modules_completion}.coursemoduleid
										 JOIN {modules} ON {modules}.id = {course_modules}.module
										 WHERE {course_modules_completion}.userid = :userid
										 AND {course_modules_completion}.timemodified >= :starttime
										 AND {course_modules_completion}.timemodified <= :endtime
										 AND {course_modules}.course = :courseid
										 AND {modules}.name $_insql_types",
										array_merge(array(
											"userid" => $userid,
											"courseid" => $courseid,
											"starttime" => $starttime,
											"endtime" => $endtime
										), $_insql_types_params)
									);
	}
	return $count;
}

function get_user_course_completion_percentage($userid, $courseid, $includetypes) {
	global $DB;
	// calculate current course progress percentage (only including 1) whitelisted module types, and 2) only including modules that enable completion tracking)
	[$_insql_types, $_insql_types_params] = $DB->get_in_or_equal(explode(",", $includetypes), SQL_PARAMS_NAMED, 'types');
	$total_num_modules = $DB->count_records_sql("SELECT COUNT({course_modules}.id)
												FROM {course_modules}
												JOIN {modules} ON {modules}.id = {course_modules}.module
												WHERE {course_modules}.course = :courseid
												AND {course_modules}.completion > 0
												AND {modules}.name $_insql_types",
										array_merge(
											array("courseid" => $courseid),
											$_insql_types_params    
										)
	);
	$done_modules = count_completed_course_modules($userid, $courseid, $includetypes, 0, 0);
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

function is_quiz_with_requirements($cmid) {
	// Returns true if cmid is a quiz & it has requirements for availability => here: it is a quiz about a jupyer notebook/
	// We only want to recommend a jupyter notebook quiz once all other content of the section has been completed.
	global $DB;
	if(get_module_type_name($cmid) == "h5pactivity") {
		return !$DB->record_exists_sql("SELECT id FROM {course_modules} WHERE id = :cmid AND availability is NULL",
										array("cmid" => $cmid)
				);
	}
	return false;
}

function get_first_available_course_module_in_section($userid, $sectionid, $includetypes, $allowonlyunfinished) {
	global $DB;

	# get name + all modules from current section
	$section = $DB->get_record("course_sections", array(
		"id" => $sectionid
	), "name,sequence");
	$current_suggestion = null;
	$first_quiz_with_requirements = null;
	foreach(explode(",", $section->sequence) as $cmid) {
		# loop over all course modules in current section
		// echo "\nCMID: " . $cmid . " -> completed: ";
		// echo "\nCOMPLETED: " .  course_module_is_completed($userid, $cmid);
		// echo "\nTYPE: " . get_module_type_name($cmid) . " -> " . in_array(get_module_type_name($cmid), explode(",", $includetypes));
		// echo "\nAVAILABLE: " . is_available_course_module($userid, $cmid);
		if(in_array(get_module_type_name($cmid), explode(",", $includetypes)) && is_available_course_module($userid, $cmid) && (($allowonlyunfinished && !course_module_is_completed($userid, $cmid)) || !$allowonlyunfinished)) {
			$is_quiz_with_reqs = is_quiz_with_requirements($cmid);
			if($is_quiz_with_reqs) {
				$first_quiz_with_requirements = $cmid;
			}
			elseif(is_null($current_suggestion)) {
				// set suggestion to first candidate
				$current_suggestion = $cmid;
			}
			// if current module is prefered module type, change suggestion to this module
			// then break and return immediately
			if(is_prefered_usercontenttype($userid, $cmid)) {
				$current_suggestion = $cmid;
				break;
			}
		}
	}
	return $current_suggestion == null? $first_quiz_with_requirements : $current_suggestion;
}


function sync_course_module_history($courseid) {
	// This function synchronizes all views and completions of course modules that were done without the chatbot block being active 
	// with the chatbot user activity tracking table.
	// This could happen after 
	// a) adding the chatbot block
	// b) activating the chatbot plugin for a course in the settings
	global $DB;

	// First, we add all the completions
	$DB->execute("INSERT INTO {chatbot_recentlyaccessed}(userid, cmid, courseid, timeaccess, completionstate)
				  SELECT done.userid, done.coursemoduleid, cm.course, done.timemodified, done.completionstate 
				  FROM {course_modules_completion} as done
				  JOIN {course_modules} as cm ON cm.id = done.coursemoduleid
				  WHERE cm.course = :courseid
				  	AND NOT EXISTS (
						SELECT *
						FROM {chatbot_recentlyaccessed} as RAC
						WHERE RAC.userid = done.userid
							AND RAC.cmid = done.coursemoduleid
					)",
			array(
				"courseid" => $courseid
			)
	);

	// Then, we add the views that are not covered by the completions yet
	$DB->execute("INSERT INTO {chatbot_recentlyaccessed}(userid, cmid, courseid, timeaccess, completionstate)
				  SELECT viewed.userid, viewed.coursemoduleid, cm.course, viewed.timecreated, 0
				  FROM {course_modules_viewed} as viewed
				  JOIN {course_modules} as cm ON cm.id = viewed.coursemoduleid
				  WHERE cm.course = :courseid
				  	AND NOT EXISTS (
						SELECT *
						FROM {chatbot_recentlyaccessed} as RAC
						WHERE RAC.userid = viewed.userid
							AND RAC.cmid = viewed.coursemoduleid
					)",
			array(
				"courseid" => $courseid
			)
	);
}

function sync_all_course_module_histories() {
	global $DB;
	$active_course_ids = explode(",", $DB->get_field('config_plugins', 'value', array('plugin' => 'block_chatbot', 'name' => 'courseids')));
	foreach($active_course_ids as $courseid) {
		sync_course_module_history($courseid);
	}
}