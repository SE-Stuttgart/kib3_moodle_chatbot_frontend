<?php
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/lib.php');


class block_chatbot extends block_base {
    public function init() {
		$this->blockname = get_class($this);
        $this->title = get_string('chatbot', 'block_chatbot');
    }

	function has_config() {return true;} // required to enable global settings
 
    public function get_content() {
		global $CFG, $PAGE, $USER, $DB, $COURSE;
		


		
		$enabled = true;
		if(!in_array(strval($COURSE->id), explode(",", $DB->get_field('config_plugins', 'value', array('plugin' => 'block_chatbot', 'name' => 'courseids'))))) {
			// check if the chatbot is enabled for the current course.
			// if not, don't render it
			$enabled = false;
		} else if($DB->record_exists('chatbot_usersettings', array('userid' => $USER->id))) {
			// check if the user has enabled chatbot.
			// if not, don't render it
			$usersettings = $DB->get_record('chatbot_usersettings', array('userid' => $USER->id));
			if($usersettings->enabled == 0) {
				$enabled = false;
			}
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

		// try to get token for slidefinder webservice
		$slidefinder_token = "";
		// check if slidefinder plugin is installed first
		if ($DB->record_exists('external_services_functions', array('functionname' => 'block_slidefinder_get_searched_locations'))) {
			// get id of slidefinder service
			$slidefinder_service_id = $DB->get_record('external_services_functions', 
													   array('functionname' => 'block_slidefinder_get_searched_locations'),
												       'externalserviceid'
			)->externalserviceid;
			$slidefinder_token = $DB->get_record('external_tokens', 
												  array('externalserviceid' => $slidefinder_service_id),
												  'token'
			)->token;
		}
		// Init javascript
		$data = array(
			"server_name" => block_chatbot_get_server_name(), 
			"server_port" => block_chatbot_get_server_port(), 
			"wwwroot" => $CFG->wwwroot,
			// block_chatbot_get_chat_container(),
			"userid" => $USER->id,
			'username' => $USER->username,
			"courseid" => $COURSE->id,
			"slidefindertoken" => $slidefinder_token,
			"wsuserid" => $DB->get_field("user", "id", array(
				"username" => "kib3_webservice",
				"firstname" => "KIB3 Webservice",
				"lastname" => "KIB3 Webservice"
			)),
			"timestamp" => (new DateTime("now", core_date::get_server_timezone_object()))->getTimestamp()
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
