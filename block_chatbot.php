<?php
defined('MOODLE_INTERNAL') || die();

class block_chatbot extends block_base {
    public function init() {
		$this->blockname = get_class($this);
        $this->title = get_string('chatbot', 'block_chatbot');
    }

	function has_config() {return true;} // required to enable global settings
 
    public function get_content() {
		global $CFG, $PAGE, $USER, $DB, $COURSE;
		require_once(__DIR__ . '/lib.php');

    	if ($this->content !== null) {
	    	return $this->content;
    	}

		// check if the user has enabled chatbot.
		// if not, don't render it
		if($DB->record_exists('chatbot_usersettings', array('userid' => $USER->id))) {
			$usersettings = $DB->get_record('chatbot_usersettings', array('userid' => $USER->id));
			if($usersettings->enabled == 0) {
				// user has disabled chatbot -> don't load JS, don't render
				$this->content         =  new stdClass;
				$this->content->footer = '';
				$this->content->text = '';
				return $this->content;
			}
		}

		// try to get token for slidefinder webservice
		$slidefinder_token = "";
		// check if slidefinder plugin is installed first
		if ($DB->record_exists('external_services_functions', array('functionname' => 'block_slidefinder_get_searched_locations'))) {
			// echo "EXISTS";
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
			"server_url" => $CFG->wwwroot,
			// block_chatbot_get_chat_container(),
			"userid" => $USER->id,
			'username' => $USER->username,
			"courseid" => $COURSE->id,
			"slidefindertoken" => $slidefinder_token,
			"timestamp" => (new DateTime("now", core_date::get_server_timezone_object()))->getTimestamp()
			/*array(
				'close' => array(
					'img' => (string) $OUTPUT->image_url('close', 'block_chatbot'),
					'visibility' => 1
				),
				'minimize' => array(
					'img' => (string) $OUTPUT->image_url('minimize', 'block_chatbot'),
					'visibility' => 1
				),
				'maximize' => array(
					'img' => (string) $OUTPUT->image_url('maximize', 'block_chatbot'),
					'visibility' => 0
				)
			)*/
		);
		// $PAGE->requires->js_call_amd('block_chatbot/chatbot', 'init', $data);
 
		// Renderer needed to use templates
        $renderer = $PAGE->get_renderer($this->blockname);
		$text = $renderer->render_from_template('block_chatbot/chatwindow', $data);

    	$this->content         =  new stdClass;
    	$this->content->footer = '';
		$this->content->text = $text;
    	return $this->content;
    } 
}
