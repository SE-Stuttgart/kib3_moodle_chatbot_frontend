<?php
class block_chatbot extends block_base {
    public function init() {
        $this->title = get_string('chatbot', 'block_chatbot');
    }

   
    public function get_content() {
		global $CFG, $OUTPUT, $PAGE;
		require_once(__DIR__ . '/lib.php');

    	if ($this->content !== null) {
	    	return $this->content;
    	}

    	$this->content         =  new stdClass;
    	$this->content->footer = '';

		// Init javascript
		$data = array(
			block_chatbot_get_server_name(), 
			block_chatbot_get_server_port(), 
			$CFG->wwwroot,
			block_chatbot_get_chat_container(),
			array(),
			array()
			#array('id' => $USER->id, 'name' => $USER->firstname.' '.$USER->lastname),
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
		$jsmodule = array(
			'name' => 'module',
			'fullpath' => '/blocks/chatbot/module.js',
			'requires' => array('base', 'io', 'node', 'json', 'selector'),
			'strings' => array(
				#array('send-message', 'block_chatbot'),
				#array('connection-lost', 'block_chatbot')
			)
		);
		$PAGE->requires->js_init_call('M.block_chatbot.init', $data, false, $jsmodule);
 
    	return $this->content;
    } 
}
