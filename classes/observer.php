<?php

namespace block_chatbot;
defined('MOODLE_INTERNAL') || die();

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

    public static function course_module_completion_updated(\core\event\course_module_completion_updated $event) {
        // file_put_contents('php://stderr', print_r("observed an event", TRUE));
        // observer::debug_to_console($event->get_name());
        // observer::alert($event->get_name());
        // echo "<script>console.log('Debug Objects: " . $event->get_name() . "' );</script>";
        global $PAGE;
        observer::forward_event('http://193.196.53.252:44123/event', $event);

        $PAGE->requires->js_init_call('M.block_chatbot.test_event', array('event' => $event->get_name()));	
    }
}