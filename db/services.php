
<?php
/// This file is part of Moodle - https://moodle.org/
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

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'block_chatbot_get_usersettings' => array(         //web service function name
        'classname'   => 'block_chatbot_external',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'get_usersettings',          //external function name
        'description' => 'Get user-specific chatbot configuration',    //human readable description of the web service function
        'type'        => 'read',                  //database rights of the web service function (read, write)
        'ajax' => true,        // is the service available to 'internal' ajax calls. 
        'services' => array(),    // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
    ),
    'block_chatbot_get_section_id' => array(
        'classname'   => 'block_chatbot_external',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'get_section_id',          //external function name
        'description' => 'Get section id from course module id',    //human readable description of the web service function
        'type'        => 'read',                  //database rights of the web service function (read, write)
        'ajax' => true,        // is the service available to 'internal' ajax calls. 
        'services' => array(),    // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
    ),
    'block_chatbot_get_section_completionstate' => array(
        'classname'   => 'block_chatbot_external',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'get_section_completionstate',          //external function name
        'description' => 'Get completion state of course section',    //human readable description of the web service function
        'type'        => 'read',                  //database rights of the web service function (read, write)
        'ajax' => true,        // is the service available to 'internal' ajax calls. 
        'services' => array(),    // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
    ),
    'block_chatbot_get_branch_quizes_if_complete' => array(
        'classname'   => 'block_chatbot_external',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'get_branch_quizes_if_complete',          //external function name
        'description' => 'Get sorted list of quiz candidates for review of a completed topic branch (Topics from A..Z with subtopics A1-1 etc.) if the branch is completed, else an empty list',    //human readable description of the web service function
        'type'        => 'read',                  //database rights of the web service function (read, write)
        'ajax' => true,        // is the service available to 'internal' ajax calls. 
        'services' => array(),    // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
    ),
    'block_chatbot_has_seen_any_course_modules' => array(
        'classname'   => 'block_chatbot_external',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'has_seen_any_course_modules',          //external function name
        'description' => 'Returns true if the given user has seen any course module in the given course',    //human readable description of the web service function
        'type'        => 'read',                  //database rights of the web service function (read, write)
        'ajax' => true,        // is the service available to 'internal' ajax calls. 
        'services' => array(),    // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
    ),
    'block_chatbot_get_last_viewed_course_modules' => array(
        'classname'   => 'block_chatbot_external',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'get_last_viewed_course_modules',          //external function name
        'description' => 'Returns the last viewed course module by the current user (that is completed, if completed = True), or an empty list, if the user has not yet accessed any course module (or not completed any)',    //human readable description of the web service function
        'type'        => 'read',                  //database rights of the web service function (read, write)
        'ajax' => true,        // is the service available to 'internal' ajax calls. 
        'services' => array(),    // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
    ),
    'block_chatbot_get_first_available_course_module' => array(
        'classname'   => 'block_chatbot_external',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'get_first_available_course_module',          //external function name
        'description' => 'Find the first course module in this section that is available.',    //human readable description of the web service function
        'type'        => 'read',                  //database rights of the web service function (read, write)
        'ajax' => true,        // is the service available to 'internal' ajax calls. 
        'services' => array(),    // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
    ),
    'block_chatbot_get_course_module_content_link' => array(
        'classname'   => 'block_chatbot_external',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'get_course_module_content_link',          //external function name
        'description' => 'Returns an embeddable html (a) href link element to the given course module id',    //human readable description of the web service function
        'type'        => 'read',                  //database rights of the web service function (read, write)
        'ajax' => true,        // is the service available to 'internal' ajax calls. 
        'services' => array(),    // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
    ),
    'block_chatbot_get_available_new_course_sections' => array(
        'classname'   => 'block_chatbot_external',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'get_available_new_course_sections',          //external function name
        'description' => 'Returns all course sections the user can start (according to section requirements), exluding already completed sections',    //human readable description of the web service function
        'type'        => 'read',                  //database rights of the web service function (read, write)
        'ajax' => true,        // is the service available to 'internal' ajax calls. 
        'services' => array(),    // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
    ),
    'block_chatbot_get_icecreamgame_course_module_id' => array(
        'classname'   => 'block_chatbot_external',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'get_icecreamgame_course_module_id',          //external function name
        'description' => 'Returns the course module if of an icecreamgame instance in the given course',    //human readable description of the web service function
        'type'        => 'read',                  //database rights of the web service function (read, write)
        'ajax' => true,        // is the service available to 'internal' ajax calls. 
        'services' => array(),    // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
    ),
    'block_chatbot_get_next_available_course_module_id' => array(
        'classname'   => 'block_chatbot_external',  //class containing the external function OR namespaced class in classes/external/XXXX.php
        'methodname'  => 'get_next_available_course_module_id',          //external function name
        'description' => 'Given a current course module (e.g. the most recently finished one) in this course section, find the course module the student should do next.',    //human readable description of the web service function
        'type'        => 'read',                  //database rights of the web service function (read, write)
        'ajax' => true,        // is the service available to 'internal' ajax calls. 
        'services' => array(),    // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) where the function will be included.  Services created manually via the Moodle interface are not supported.
        'capabilities' => '', // comma separated list of capabilities used by the function.
    ),
);