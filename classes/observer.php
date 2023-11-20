<?php

namespace block_chatbot;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

class observer {
    public static function debug_to_console($text) {
        echo "<script type='text/javascript'>console.log('$text');</script>";
    }

    public static function alert($msg) {
        //echo "<script type='text/javascript'>alert('$msg');</script>";
        echo $msg;
    }

    public static function forward_event($url, $data){
        $curl = curl_init($url);
        $json = json_encode($data->get_data());
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);
        // echo $response;
    }

    // public static function course_module_completion_updated(\core\event\course_module_completion_updated $event) {
    public static function course_module_completion_updated(\core\event\base $event) {
        observer::debug_to_console($event->get_name());
        observer::debug_to_console($event->courseid);

        // Find course module associated with event.
        $cm = $DB->get_record('course_modules', array('id' => $event->courseid));

        // Check if course associated with update is in whitelist specified in settings.
        if (in_array($cm->course, explode(",", get_config('block_chatbot', "courseids"))) == false) {
            // If not in whitelist, then return.
            observer::forward_event("http://" . block_chatbot_get_event_server_name() . ":" . block_chatbot_get_server_port() . "/event", $event);
        }
    }
}
