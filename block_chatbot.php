<?php
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/lib.php');

use core_h5p\local\library\autoloader;

class block_chatbot extends block_base {
    public function init() {
		$this->blockname = get_class($this);
        $this->title = get_string('chatbot', 'block_chatbot');
    }

	function instance_create() {
		// the chatbot was inactive before: we need to sync the user course module activity
		sync_all_course_module_histories();
		return true;
	}


	function has_config() {return true;} // required to enable global settings
 
    public function get_content() {
		global $CFG, $PAGE, $USER, $DB, $COURSE;
		
		// check if plugin is enabled for current course -> if not, return nothing (performance)
		$enabled = true;
		try {
			if(!in_array(strval($COURSE->id), explode(",", $DB->get_field('config_plugins', 'value', array('plugin' => 'block_chatbot', 'name' => 'courseids'))))) {
				// check if the chatbot is enabled for the current course.
				// if not, don't render it
				$enabled = false;
			}
		} catch(exception $e) {
			$enabled = false;
		}
		if(!$enabled) {
			// chatbot is not enabled for this course or user -> don't render
			$this->content         =  new stdClass;
			$this->content->footer = '';
			$this->content->text = '';
			return $this->content;
		}

		if ($this->content !== null) {
			// cache
	    	return $this->content;
    	}

		// check if plugin is enabled for current user -> if not, return only settings button (performance)
		try {
			if($DB->record_exists('chatbot_usersettings', array('userid' => $USER->id))) {
				// check if the user has enabled chatbot.
				// if not, don't render it
				$usersettings = $DB->get_record('chatbot_usersettings', array('userid' => $USER->id));
				if($usersettings->enabled == 0) {
					$enabled = false;
				}
			}
		} catch(exception $e) {
			$enabled = false;
		}

		// try to get token for booksearch webservice
		try {
			// get id of booksearch service
			$booksearch_service_id = $DB->get_field('external_services_functions', 'externalserviceid',
													   array('functionname' => 'block_booksearch_get_searched_locations'),
												       
			);
			// get token
			$booksearch_token = $DB->get_field('external_tokens', 'token',
												array('externalserviceid' => $booksearch_service_id),
			);
		} catch(exception $e) {
			$booksearch_token = "";
		}

		if(!property_exists($this, 'h5presizerurl')) {
			try {
				$this->h5presizerurl = core_h5p\local\library\autoloader::get_h5p_core_library_url('js/h5p-resizer.js');
			} catch (exception $e) {
				$this->h5presizerurl = "";
			}
		}

		// Check if user settings exist. If not, create them.
		if(!$DB->record_exists('chatbot_usersettings', array('userid'=>$USER->id))) {
			$firstturn = true;
			$book_id = $DB->get_record('modules', array('name'=>'book'))->id;
            $DB->insert_record('chatbot_usersettings', array(
                'userid' => $USER->id,
                'enabled' => true,
                'logging' => false,
				'firstturn' => true,
                'preferedcontenttype' => $book_id,
                'numsearchresults' => 5,
                'numreviewquizzes' => 3,
                'openonlogin' => true,
                'openonquiz' => true,
                'openonsection' => false,
                'openonbranch' => false,
                'openonbadge' => true
            ));
		} else {
			$firstturn = $DB->get_field("chatbot_usersettings", "firstturn", array('userid' => $USER->id));
		}

		// Init javascript
		$data = array(
			"enabled" => $enabled,
			"firstturn" => $firstturn,
			"server_name" => block_chatbot_get_server_name(), 
			"server_port" => block_chatbot_get_server_port(), 
			"wwwroot" => $CFG->wwwroot,
			// block_chatbot_get_chat_container(),
			"userid" => $USER->id,
			'username' => $USER->username,
			"courseid" => $COURSE->id,
			"booksearchtoken" => $booksearch_token,
			"wsuserid" => $DB->get_field("user", "id", array(
				"username" => "kib3_webservice",
				"firstname" => "KIB3 Webservice",
				"lastname" => "KIB3 Webservice"
			)),
			"timestamp" => (new DateTime("now", core_date::get_server_timezone_object()))->getTimestamp(),
			'resizeurl' => $this->h5presizerurl	
		);
 
		// Renderer needed to use templates
        $renderer = $PAGE->get_renderer($this->blockname);
		$text = $renderer->render_from_template('block_chatbot/chatwindow', $data);
    	$this->content         =  new stdClass;
    	$this->content->footer = '';
		$this->content->text = $text;
    	return $this->content;
    } 
}
