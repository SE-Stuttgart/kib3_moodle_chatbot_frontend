<?php
namespace block_chatbot\task;
require_once(__DIR__ . '/../../lib.php');

defined('MOODLE_INTERNAL') || die();

/**
 * An example of a scheduled task.
 */
class sync_course_module_completions extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_sync_course_module_completions', 'block_chatbot');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        sync_all_course_module_histories();
    }
}