<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     block_chatbot
 * @category    admin
 * @copyright   2022 Universtity of Stuttgart <dirk.vaeth@ims.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once("$CFG->libdir/externallib.php");


// require(__DIR__.'/../../config.php');
// require_login();

class block_chatbot_external extends external_api {

    public static function get_usersettings_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'user id'),
            )
        );
    }
    public static function get_usersettings_returns() {
        return new external_single_structure(
            array(
                'userid' => new external_value(PARAM_INT, 'user id'),
                'enabled' => new external_value(PARAM_BOOL, 'should chatbot be enabled'),
                'logging' => new external_value(PARAM_BOOL, 'should chatbot be logging'),
                'preferedcontenttypeid' => new external_value(PARAM_TEXT, 'what content type should be displayed by default'),
                'numsearchresults' => new external_value(PARAM_INT, 'number of returned search results'),
                'numreviewquizzes' => new external_value(PARAM_INT, 'number of quizzes asked in review session'),
                'openonlogin' => new external_value(PARAM_BOOL, 'on login'),
                'openonquiz' => new external_value(PARAM_BOOL, 'on quiz completion'),
                'openonsection' => new external_value(PARAM_BOOL, 'on section completion'),
                'openonbranch' => new external_value(PARAM_BOOL, 'on branch completion'),
                'openonbadge' => new external_value(PARAM_BOOL, 'on badge completion'),
                'warnings' => new external_warnings(),
            )
        );
    }
    public static function get_usersettings($userid) {
        global $DB;
        $params = self::validate_parameters(self::get_usersettings_parameters(), array('userid' => $userid));
        $warnings = array();
        
        if(!$DB->record_exists('chatbot_usersettings', array('userid'=>$userid))) {
            $book_id = $DB->get_record('modules', array('name'=>'book'))->id;
            $DB->insert_record('chatbot_usersettings', array(
                'userid' => $userid,
                'enabled' => true,
                'logging' => true,
                'preferedcontenttypeid' => $book_id,
                'numsearchresults' => 5,
                'numreviewquizzes' => 3,
                'openonlogin' => true,
                'openonquiz' => true,
                'openonsection' => true,
                'openonbranch' => true,
                'openonbadge' => true
            ));
        }
        
        $settings = $DB->get_record('chatbot_usersettings', array('userid'=>$userid));
        return array(
            'userid' => $settings->userid,
            'enabled' => $settings->enabled,
            'logging' => $settings->logging,
            'preferedcontenttypeid' => $settings->preferedcontenttype,
            'numsearchresults' => $settings->numsearchresults,
            'numreviewquizzes' => $settings->numreviewquizzes,
            'openonlogin' => $settings->openonlogin,
            'openonquiz' => $settings->openonquiz,
            'openonsection' => $settings->openonsection,
            'openonbranch' => $settings->openonbranch,
            'openonbadge' => $settings->openonbadge
        );
    }


    public static function get_section_id_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'course module id'),
            )
        );
    }
    public static function get_section_id_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'section id for given course module id'),
                'name' => new external_value(PARAM_TEXT, 'section name'),
                'warnings' => new external_warnings(),
            )
        );
    }
    public static function get_section_id($cmid) {
        global $DB;
        $params = self::validate_parameters(self::get_section_id_parameters(), array('cmid' => $cmid));
        [$sectionid, $name] = get_section_id_and_name($cmid);
        return array(
            'id' => $sectionid, 
            'name' => $name
        );
    }



    public static function get_section_completionstate_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'user id'),
                'sectionid' => new external_value(PARAM_INT, 'section id'),
                'includetypes' => new external_value(PARAM_TEXT, 'comma-seperated whitelist of module types, e.g. url, book, resource, quiz, h5pactivity')
            )
        );
    }
    public static function get_section_completionstate_returns() {
        return new external_single_structure(
            array(
                'completed' => new external_value(PARAM_BOOL, 'true, if section is completed, else false'),
                'warnings' => new external_warnings(),
            )
        );
    }
    public static function get_section_completionstate($userid, $sectionid, $includetypes) {
        $params = self::validate_parameters(self::get_section_completionstate_parameters(), array('userid' => $userid, 'sectionid' => $sectionid, 'includetypes' => $includetypes));
        return array(
            'completed' => section_is_completed($userid, $sectionid, explode(",", $includetypes))
        );
    }


    public static function get_branch_quizes_if_complete_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'user id'),
                'sectionid' => new external_value(PARAM_INT, 'course module id from the branch to be checked'),
                'includetypes' => new external_value(PARAM_TEXT, 'comma-seperated whitelist of module types, e.g. url, book, resource, quiz, h5pactivity')
            )
        );
    }
    public static function get_branch_quizes_if_complete_returns() {
        return new external_single_structure(
            array(
                'completed' => new external_value(PARAM_BOOL, 'true, if branch is completed, else false'),
                'branch' => new external_value(PARAM_TEXT, 'the letter of the topic branch'),
                'candidates' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                        'cmid' => new external_value(PARAM_INT, 'the quiz id (as listed in the course module table)'),
                        'grade' => new external_value(PARAM_FLOAT, 'the quiz grade scored by the given user')
                ))),
                'warnings' => new external_warnings(),
            )
        );
    }
    public static function get_branch_quizes_if_complete($userid, $sectionid, $includetypes=["url", "book", "resource", "quiz", "h5pactivity"]) {
        $params = self::validate_parameters(self::get_branch_quizes_if_complete_parameters(), array('userid' => $userid, 'sectionid' => $sectionid, 'includetypes' => $includetypes));
        global $DB;
        
		// find all sections belonging to the same topic branch
        [$topicletter, $sectionids] = get_all_branch_section_ids($userid, $sectionid);
        // var_dump($sectionids);
        // check if there is any incomplete section
        foreach($sectionids as $_sectionid) {
            if(!section_is_completed($userid, $_sectionid, explode(",", $includetypes))) {
                // there is an incomplete section -> this branch is not ready for review yet!
                // -> stop checking and return empty list
                return array(
                    'completed' => false,
                    'branch' => $topicletter,
                    'candidates' => array(),
                );
            }
        }

        // all sections are completed - collect list of review quiz candidates
        [$_insql_sectionids, $_insql_sectionids_params] = $DB->get_in_or_equal($sectionids, SQL_PARAMS_NAMED, 'sectionids');
        $candidates = $DB->get_records_sql_menu("SELECT cm.id, {grade_grades}.finalgrade / {grade_grades}.rawgrademax 
                                    FROM {course_modules} as cm
                                    JOIN {grade_items} ON cm.instance = {grade_items}.iteminstance
                                    JOIN {grade_grades} ON {grade_items}.id = {grade_grades}.itemid
                                    WHERE cm.section $_insql_sectionids
                                    AND {grade_grades}.userid = :userid
                                    AND {grade_items}.itemmodule = 'h5pactivity'
                                    AND {grade_items}.itemtype = 'mod'
                                    ORDER BY {grade_grades}.finalgrade ASC,
                                            {grade_grades}.timemodified ASC",
                                    array_merge($_insql_sectionids_params,
                                     array('userid' => $userid)
                                    )
                                );
        // var_dump($candidates);
        // convert into return type and return
        $result = array();
        foreach($candidates as $cmid => $grade) {
            array_push($result, array(
                "cmid" => $cmid,
                "grade" => $grade
            ));
        }
        return array(
            "completed" => true,
            "branch" => $topicletter,
            "candidates" => $result
        );
    }



    public static function has_seen_any_course_modules_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'user id'),
                'courseid' => new external_value(PARAM_INT, 'course id')
            )
        );
    }
    public static function has_seen_any_course_modules_returns() {
        return new external_single_structure(
            array(
                'seen' => new external_value(PARAM_BOOL, 'true, if the given user has seen at least one module in the given course'),
                'warnings' => new external_warnings(),
            )
        );
    }
    public static function has_seen_any_course_modules($userid, $courseid) {
        global $DB;
        $params = self::validate_parameters(self::has_seen_any_course_modules_parameters(), array('userid' => $userid, 'courseid' => $courseid));
        
        $result = $DB->record_exists_sql(
                                "SELECT {course_modules_viewed}.coursemoduleid FROM {course_modules_viewed}
                                JOIN {course_modules} ON {course_modules}.id = {course_modules_viewed}.coursemoduleid
                                WHERE {course_modules_viewed}.userid = :userid
                                AND {course_modules}.course = :courseid",
                            array("userid" => $userid,
                                    "courseid" => $courseid)
                            );
        // var_dump($result);
        return array(
            'seen' => $result
        );
    }



    public static function get_last_viewed_course_modules_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'user id'), 
                'courseid' => new external_value(PARAM_INT, 'course id'), 
                'completed' => new external_value(PARAM_BOOL, 'whether the module status should be viewed or completed'), 
            )
        );
    }
    public static function get_last_viewed_course_modules_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'cmid' => new external_value(PARAM_INT, 'id of the course module'),
                    'section' => new external_value(PARAM_INT, "id of the course module's section"),
                    'timeaccess' => new external_value(PARAM_INT, "timestamp of the course module's last access"),
                    'completionstate' => new external_value(PARAM_INT, "course module's completionstate"),
                    'warnings' => new external_warnings(),
                )
            )
        );
    }
    public static function get_last_viewed_course_modules($userid, $courseid, $completed) {
        global $DB;
        $params = self::validate_parameters(self::get_last_viewed_course_modules_parameters(), array('userid' => $userid, 'courseid' => $courseid, 'completed' => $completed));
        
        $results = $DB->get_records_sql("SELECT ra.cmid, ra.timeaccess, ra.completionstate, cm.section FROM {chatbot_recentlyaccessed} AS ra
                              JOIN {course_modules} as cm 
                                ON cm.id = ra.cmid
                              WHERE ra.userid = :userid
                              AND ra.courseid = :courseid
                              AND ra.completionstate = :completionstate
                              ORDER BY timeaccess DESC", array(
            "courseid" => $courseid,
            "completionstate" => $completed,
            "userid" => $userid
        ));
        // var_dump($results);
        return $results;
    }



    public static function get_first_available_course_module_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'user id'), 
                'sectionid' => new external_value(PARAM_INT, 'section id (where to look for for the first course module)'),
                'includetypes' => new external_value(PARAM_TEXT, 'comma-seperated whitelist of module types, e.g. url, book, resource, quiz, h5pactivity'),
                'allowonlyunfinished' => new external_value(PARAM_BOOL, 'if True, will filter for only course modules that were not completed by the user')
            )
        );
    }
    public static function get_first_available_course_module_returns() {
        return new external_single_structure(
            array(
                'cmid' => new external_value(PARAM_INT, 'id of the course module'),
                'warnings' => new external_warnings(),
            )
        );
    }
    public static function get_first_available_course_module($userid, $sectionid, $includetypes, $allowonlyunfinished) {
        global $DB;
        $params = self::validate_parameters(self::get_first_available_course_module_parameters(), array(
            'userid' => $userid, 
            'sectionid' => $sectionid,
            'includetypes' => $includetypes, 
            'allowonlyunfinished' => $allowonlyunfinished));
        
        # get name + all modules from current section
        $section = $DB->get_record("course_sections", array(
            "id" => $sectionid
        ), "name,sequence");

        foreach(explode(",", $section->sequence) as $cmid) {
            # loop over all course modules in current section
            // echo "\nCMID: " . $cmid . " -> completed: ";
            // echo "\nCOMPLETED: " .  course_module_is_completed($userid, $cmid);
            // echo "\nTYPE: " . get_module_type_name($cmid) . " -> " . in_array(get_module_type_name($cmid), explode(",", $includetypes));
            // echo "\nAVAILABLE: " . is_available_course_module($userid, $cmid);
            if(in_array(get_module_type_name($cmid), explode(",", $includetypes)) && is_available_course_module($userid, $cmid) && (($allowonlyunfinished && !course_module_is_completed($userid, $cmid)) || !$allowonlyunfinished)) {
                return array(
                    "cmid" => $cmid
                );
            }
        }

        return array("cmid" => null);
    }


    public static function get_course_module_content_link_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'course module id'), 
                'alternativedisplaytext' => new external_value(PARAM_TEXT, 'alternative display text for html a element')
            )
        );
    }
    public static function get_course_module_content_link_returns() {
        return new external_single_structure(
            array(
                'url' => new external_value(PARAM_RAW, 'embeddable html href link (a) element'),
                'warnings' => new external_warnings(),
            )
        );
    }
    public static function get_course_module_content_link($cmid, $alternativedisplaytext) {
        global $DB;
        global $CFG;

        $params = self::validate_parameters(self::get_course_module_content_link_parameters(), array(
            'cmid' => $cmid,
            'alternativedisplaytext' => $alternativedisplaytext
        ));
        
        $base_path = $CFG->wwwroot;
        // get course module name and type. name can be used as default display text.
        [$display_name, $type_name] = get_course_module_name_and_typename($cmid);
        if(!empty($alternativedisplaytext)) {
            // overwrite display text
            $display_name = $alternativedisplaytext;
        }
        // construct and return link
        $url = "<a href=\"{$base_path}/mod/{$type_name}/view.php?id={$cmid}\">{$display_name}</a>";
        // var_dump("<a href=\"{$base_path}/mod/{$type_name}/view.php?id={$cmid}\">{$display_name}</a>");
        return array("url" => $url);
    }


    public static function get_available_new_course_sections_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'user id'), 
                'courseid' => new external_value(PARAM_INT, 'course id'), 
            )
        );
    }
    public static function get_available_new_course_sections_returns() {
        return new external_single_structure(
            array(
                'sectionids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'section id')
                )
            )
        );
    }
    public static function get_available_new_course_sections($userid, $courseid) {
        global $DB;

        $params = self::validate_parameters(self::get_available_new_course_sections_parameters(), array(
            'userid' => $userid,
            'courseid' => $courseid
        ));
        
        // get all course sections for the given course, then check availability
        $available = array();
        $all_sections = $DB->get_records("course_sections",
                                            array("course" => $courseid),
                                            '',
                                            "id,name,visible,availability,sequence");
        foreach($all_sections as $section) {
            if(is_available_course_section($userid, $section->id, $section->name,$section->visible,$section->availability) && !section_is_completed($userid, $section->id)) {
                $section_cmids = explode(",", $section->sequence);
                $all_course_modules_available = true;
                foreach($section_cmids as $cmid) {
                    if(!is_available_course_module($userid, $cmid)) {
                        $all_course_modules_available = false;
                        break;
                    }
                }
                if($all_course_modules_available) {
                    array_push($available, $section->id);
                }
            }
        }
        
        return array('sectionids' => $available);
    }



    public static function get_icecreamgame_course_module_id_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'), 
            )
        );
    }
    public static function get_icecreamgame_course_module_id_returns() {
        new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'ice cream game id for the given course'),
                'warnings' => new external_warnings(),
            )
        );
    }
    public static function get_icecreamgame_course_module_id($courseid) {
        global $DB;

        $params = self::validate_parameters(self::get_icecreamgame_course_module_id_parameters(), array(
            'courseid' => $courseid
        ));
        
        $icecreamgame_cmid = $DB->get_field_sql("SELECT cm.id
                                                 FROM {course_modules} as cm
                                                 JOIN {modules} ON {modules}.id = cm.module
                                                 WHERE {modules}.name = :modulename
                                                 AND cm.course = :courseid",
                                                 array(
                                                    "courseid" => $courseid,
                                                    "modulename" => 'icecreamgame')
                                            );
        return array("id" => $icecreamgame_cmid->id);
    }



    public static function  get_next_available_course_module_id_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'user id'),
                'cmid' => new external_value(PARAM_INT, 'current course module id'),
                'includetypes' => new external_value(PARAM_TEXT, 'comma-seperated whitelist of module types, e.g. url, book, resource, quiz, h5pactivity'),
                'allowonlyunfinished' => new external_value(PARAM_BOOL, 'if True, will filter for only course modules that were not completed by the user'),
                'currentcoursemodulecompletion' => new external_value(PARAM_BOOL, 'TODO')
            )
        );
    }
    public static function get_next_available_course_module_id_returns() {
        return new external_single_structure(
            array(
                'cmid' => new external_value(PARAM_INT, 'next course module id'),
                'warnings' => new external_warnings(),
            )
        );
    }
    public static function get_next_available_course_module_id($userid, $cmid, $includetypes, $allowonlyunfinished, $currentcoursemodulecompletion) {
        global $DB;

        $params = self::validate_parameters(self::get_next_available_course_module_id_parameters(), array(
            'userid' => $userid,
            'cmid' => $cmid,
            'includetypes' => $includetypes,
            'allowonlyunfinished' => $allowonlyunfinished,
            'currentcoursemodulecompletion' => $currentcoursemodulecompletion
        ));

        // get all course modules from current section
        [$sectionid, $sectionname] = get_section_id_and_name($cmid);
        $sequence = explode(",", $DB->get_field('course_sections', 'sequence', array("id" => $sectionid)));
        $unfinished_modules = array();
        // var_dump($sequence);
        foreach($sequence as $index => $nextcmid) {
            // walk over all section modules
            $typename = get_module_type_name($nextcmid);
            // echo "\n";
            // echo "\nNEXT CMID {$nextcmid}";
            // echo "\nTYPE {$typename}";
            if(str_contains($includetypes, $typename) && is_available_course_module($userid, $nextcmid)) {
                // only look at modules that are 1) available and 2) whitelisted by type
                
                // take provided module completion if course module we look at is the one passed in, otherwise query database
                if($cmid == $nextcmid && $currentcoursemodulecompletion) {
                    // echo "\nCOMPLETED OVERRIDE {$currentcoursemodulecompletion}";
                    $completed = $currentcoursemodulecompletion;
                } else {
                    $completed = course_module_is_completed($userid, $nextcmid); 
                }
                $open_respecting_unfinished = ($allowonlyunfinished && !$completed) || (!$allowonlyunfinished);
                // echo "\nCOMPLETED {$completed}";
                // echo "\nOPEN {$open_respecting_unfinished}";
                // echo "\nCMID < 0? {{$cmid} < 0}";
                // echo "\n";

                if((!$completed) && $cmid == $nextcmid) {
                    // module not completed, but it's the currentModule: return, because it still has to be finished
                    // echo "\n 22 --> {$nextcmid} \n";
                    return array("cmid" => $nextcmid);
                }
                if((!$open_respecting_unfinished) && $cmid == $nextcmid) {
                    // echo "\n 33 --> {$nextcmid} \n";
                    // module is the current module, and it has been completed:
                    // get next module from section in sequence (if exists)
                    //  - if that hasen't been completed yet, return it
                    if(count($sequence) > $index + 1) {
                        $nextcandidateid = $sequence[$index + 1];
                        $completed = course_module_is_completed($userid, $nextcandidateid);
                        $open_respecting_unfinished = ($allowonlyunfinished && !$completed) || (!$allowonlyunfinished);
                        if($open_respecting_unfinished && str_contains($includetypes, get_module_type_name($nextcandidateid))) {
                            return array("cmid" => $nextcandidateid);
                        }
                    }
                }
                if($open_respecting_unfinished) {
                    // echo "\n 44 --> {$nextcmid} \n";
                    // keep track of all unfinished modules in the section
                    array_push($unfinished_modules, $nextcmid);
                }
            }
        }
        if(!empty($unfinished_modules)) {
            // we haven't returned from any of the conditions above, so just return 1st unfinished module
            // echo "\n 55 --> {$nextcmid} \n";
            return array("cmid" => $unfinished_modules[0]);
        }
        // echo "\n 66 --> {$nextcmid} \n";
        return array("cmid" => null); // no open modules in current section
    }



    public static function count_viewed_course_modules_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'user id'),
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'includetypes' => new external_value(PARAM_TEXT, 'comma-seperated whitelist of module types, e.g. url, book, resource, quiz, h5pactivity'),
                'starttime' => new external_value(PARAM_INT, 'start point of interval)'),
                'endtime' => new external_value(PARAM_INT, 'end point of interval (or 0, if there should be no time limit)')
            )
        );
    }
    public static function count_viewed_course_modules_returns() {
        return new external_single_structure(
            array(
                'count' => new external_value(PARAM_INT, 'number of viewed course modules in given course during specified time range'),
                'warnings' => new external_warnings(),
            )
        );
    }
    public static function count_viewed_course_modules($userid, $courseid, $includetypes, $starttime, $endtime) {
        global $DB;

        $params = self::validate_parameters(self::count_viewed_course_modules_parameters(), array(
            'userid' => $userid,
            'courseid' => $courseid,
            'includetypes' => $includetypes,
            'starttime' => $starttime,
            'endtime' => $endtime
        ));


        [$_insql_types, $_insql_types_params] = $DB->get_in_or_equal(explode(",", $includetypes), SQL_PARAMS_NAMED, 'types');
        if($endtime <= 0 || $endtime <= $starttime) {
            // no time interval - return count of all viewed course modules
            $count = $DB->count_records_sql("SELECT COUNT({course_modules_viewed}.id)
                                             FROM {course_modules_viewed}
                                             JOIN {course_modules} ON {course_modules}.id = {course_modules_viewed}.coursemoduleid
                                             JOIN {modules} ON {modules}.id = {course_modules}.module
                                             WHERE {course_modules_viewed}.userid = :userid
                                             AND {course_modules}.course = :courseid
                                             AND {modules}.name $_insql_types",
                                            array_merge(array(
                                                "userid" => $userid,
                                                "courseid" => $courseid,
                                            ), $_insql_types_params)
                                        );
        } else {
            // time interval - return count of viewed course modules during given interval only
            $count = $DB->count_records_sql("SELECT COUNT({course_modules_viewed}.id)
                                             FROM {course_modules_viewed}
                                             JOIN {course_modules} ON {course_modules}.id = cmv.coursemoduleid
                                             JOIN {modules} ON {modules}.id = {course_modules}.module
                                             WHERE {course_modules_viewed}.userid = :userid
                                             AND {course_modules_viewed}.timecreated >= :starttime
                                             AND {course_modules_viewed}.timecreated <= :endtime
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
        return array("count" => $count);
    }



    public static function get_user_statistics_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'user id'),
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'includetypes' => new external_value(PARAM_TEXT, 'comma-seperated whitelist of module types, e.g. url, book, resource, quiz, h5pactivity'),
                'updatedb' => new external_value(PARAM_BOOL, 'whether to update the progress column in the database with the newly calculated values')
            )
        );
    }
    public static function get_user_statistics_returns() {
        return new external_single_structure(
            array(
                'course_completion_percentage' => new external_value(PARAM_FLOAT, 'percentage of course completed by user so far'),
                'quiz_repetition_percentage' => new external_value(PARAM_FLOAT, 'percentage of quizzes repeated by the user so far'),
                'warnings' => new external_warnings(),
            )
        );
    }
    public static function get_user_statistics($userid, $courseid, $includetypes, $updatedb) {
        global $DB;

        $params = self::validate_parameters(self::get_user_statistics_parameters(), array(
            'userid' => $userid,
            'courseid' => $courseid,
            'includetypes' => $includetypes,
            'updatedb' => $updatedb
        ));

        // calculate current progress percentage for quizzes
        [$_insql_types, $_insql_types_params] = $DB->get_in_or_equal(explode(",", $includetypes), SQL_PARAMS_NAMED, 'types');
        $total_num_quizzes = $DB->count_records_sql("SELECT COUNT(id)
                                                     FROM {grade_items}
                                                     WHERE courseid = :courseid
                                                     AND itemmodule $_insql_types",
                                                array_merge(
                                                    array("courseid" => $courseid),
                                                    $_insql_types_params    
                                                ));
        $num_repeated_quizzes = count($DB->get_fieldset_sql("SELECT {grade_grades_history}.id
                                                        FROM {grade_grades_history}
                                                        JOIN {grade_items} ON {grade_items}.id = {grade_grades_history}.itemid
                                                        WHERE {grade_grades_history}.userid = :userid
                                                        AND {grade_items}.courseid = :courseid
                                                        AND {grade_grades_history}.finalgrade IS NOT NULL
                                                        AND {grade_grades_history}.source = :source
                                                        GROUP BY {grade_grades_history}.itemid
                                                        HAVING COUNT({grade_grades_history}.id) > 1
                                                    ",
                                                array("userid" => $userid,
                                                        "courseid" => $courseid,
                                                        "source" => "mod/h5pactivity")
                                                )
                                        );
        // var_dump($num_repeated_quizzes);
        $percentage_repeated_quizzes = $num_repeated_quizzes / $total_num_quizzes;
        
        $percentage_done = get_user_course_completion_percentage($userid, $courseid, $includetypes);
        if($updatedb) {
            // update database with newly calculated values
            $progress_summary_record = $DB->get_record("chatbot_progress_summary", array("userid" => $userid));
            $progress_summary_record->progress = $percentage_done;
            $progress_summary_record->timecreated = (new DateTime("now", core_date::get_server_timezone_object()))->getTimestamp();
            $DB->update_record("chatbot_progress_summary", $progress_summary_record);
        }

        return array(
            "course_completion_percentage" => $percentage_done,
            "quiz_repetition_percentage" => $percentage_repeated_quizzes
        );
    }




    public static function get_last_user_weekly_summary_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'user id'),
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'includetypes' => new external_value(PARAM_TEXT, 'comma-seperated whitelist of module types, e.g. url, book, resource, quiz, h5pactivity'),
                'updatedb' => new external_value(PARAM_BOOL, 'whether to update the timecreated timestamp and firstweek = false')
            )
        );
    }
    public static function get_last_user_weekly_summary_returns() {
        return new external_single_structure(
            array(
                'first_turn_ever' => new external_value(PARAM_BOOL, 'true, if the user is using the chatbot for the first time'),
                'first_week' => new external_value(PARAM_BOOL, "is this the user's first week using the chatbot"),
                'timecreated' => new external_value(PARAM_INT, 'timestamp for last update of this record'),
                'course_progress_percentage' => new external_value(PARAM_FLOAT, "last course progress displayed to the user"), 
                'warnings' => new external_warnings(),
            )
        );
    }
    public static function get_last_user_weekly_summary($userid, $courseid, $includetypes, $updatedb) {
        global $DB;

        $params = self::validate_parameters(self::get_last_user_weekly_summary_parameters(), array(
            'userid' => $userid,
            'courseid' => $courseid,
            'includetypes' => $includetypes,
            'updatedb' => $updatedb
        ));

        // check if we have a summary entry for the current user
        // if not, that means that the user is using the chatbot for the first time, 
        // and we should create this entry
        $first_turn_ever = !$DB->record_exists("chatbot_weekly_summary", array("userid" => $userid));
        // TODO should this also depend on the course id? 
        if($first_turn_ever) {
            // create first entry
            $timecreated = (new DateTime("now", core_date::get_server_timezone_object()))->getTimestamp();
            $DB->insert_record("chatbot_weekly_summary", (object)array(
                "userid" => $userid,
                "timecreated" => $timecreated,
                "firstweek" => 1
            ), false);
            
            $percentage_done = get_user_course_completion_percentage($userid, $courseid, $includetypes);
            $DB->insert_record("chatbot_progress_summary", (object)array(
                "userid" => $userid,
                "progress" => $percentage_done,
                "timecreated" => $timecreated
            ), false);

            $firstweek = 1;
        } else {
            $last_summary = $DB->get_record("chatbot_weekly_summary", array("userid" => $userid));
            if($updatedb) {
                // update record to current time and > first week
                $last_summary->firstweek = false;
                $last_summary->timecreated = (new DateTime("now", core_date::get_server_timezone_object()))->getTimestamp();
                $DB->update_record("chatbot_weekly_summary", $last_summary);
            }
            $firstweek = $last_summary->firstweek;
            $timecreated = $last_summary->timecreated;
            $percentage_done = $DB->get_field('chatbot_progress_summary', 'progress', array("userid" => $userid));
        }

        return array(
            "first_turn_ever" => $first_turn_ever,
            "first_week" => (bool)$firstweek,
            "timecreated" => $timecreated,
            "course_progress_percentage" => $percentage_done         
        );
    }



    
}