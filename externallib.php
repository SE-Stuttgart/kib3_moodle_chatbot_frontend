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
        $result = $DB->get_records_sql_menu("SELECT {course_sections}.id, {course_sections}.name 
                                    FROM {course_sections}
                                    JOIN {course_modules} ON {course_sections}.id = {course_modules}.section
                                    WHERE {course_modules}.id = :cmid
                                    LIMIT 1",
                                    array("cmid" => $cmid)
                                );
        $sectionid = array_keys($result)[0];
        return array(
            'id' => $sectionid, 
            'name' => $result[$sectionid]
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
}
