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

		// Init javascript
		$data = array(
			"server_name" => block_chatbot_get_server_name(), 
			"server_port" => block_chatbot_get_server_port(), 
			"server_url" => $CFG->wwwroot,
			// block_chatbot_get_chat_container(),
			"userid" => $USER->id,
			'username' => $USER->username,
			"courseid" => $COURSE->id,
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
