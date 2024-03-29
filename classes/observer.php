<?php

namespace block_chatbot;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

class observer {
    public static function debug_to_console($text) {
        echo "<script type='text/javascript'>console.log('Event: $text');</script>";
    }

    public static function alert($msg) {
        //echo "<script type='text/javascript'>alert('$msg');</script>";
        echo $msg;
    }
    public static function send($url, $data){
        $curl = curl_init($url);
        $json = json_encode($data);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // Verification fails on some SSL setups 
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        curl_close($curl);
        // echo $response;
    }
    
    public static function course_module_completion_viewed(\core\event\base $event) {
        global $DB;
        
        // update database
        $data = $event->get_data();
        $completionstate = array_key_exists("other", $data) && !is_null($data['other']) && array_key_exists("completionstate", $data['other'])? $data['other']['completionstate'] : 0;
        update_recently_viewed($data['userid'], $data['courseid'], $data['contextinstanceid'], $data['timecreated'], $completionstate);
        
        // forward event in case that completion/views are not being tracked - otherwise, we will miss section comletion events
        if($DB->get_field("course_modules", "completion", array("id" => $data['contextinstanceid'])) == 0) {
            $proxy_event_data = $data;
            $proxy_event_data['eventname'] = "\\core\\event\\course_module_completion_updated";
            observer::forward_event_to_chatbot($data['courseid'], $proxy_event_data);
        }
    }
    
    public static function course_module_completion_updated(\core\event\base $event) {
        // update database
        $data = $event->get_data();
        update_recently_viewed($data['userid'], $data['courseid'], $data['contextinstanceid'], $data['timecreated'], $data['other']['completionstate']);
        // forward event
        observer::forward_event_to_chatbot($event->courseid, $data);
    }

    public static function generic_event_fired(\core\event\base $event) {
        observer::forward_event_to_chatbot($event->courseid, $event->get_data());
    }

    public static function forward_event_to_chatbot($courseid, $eventdata) {
        // Check if course associated with update is in whitelist specified in settings.
        if (in_array($courseid, explode(",", get_config('block_chatbot', "courseids"))) == true) {
            // If not in whitelist, then return.
            observer::send(block_chatbot_get_protocol() . "://" . block_chatbot_get_event_server_name() . ":" . block_chatbot_get_server_port() . "/event", $eventdata);
        }
    }
}
