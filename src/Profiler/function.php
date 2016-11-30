<?php

if (!function_exists('profiler')) {
    function profiler($action, $params = null)
    {
        static $profiler = null;
        $params = func_get_args();
        $action = (string) array_shift($params);
        switch ($action) {
            case 'enable':
                if (is_null($profiler)) {
                    $enable = array_shift($params);
                    $messages = array_shift($params);
                    $enable = (is_array($enable)) ? $enable : array();
                    $messages = (is_array($messages)) ? $messages : array();
                    $profiler = new BootPress\Profiler\Component($enable, $messages);
                }
                break;
            case 'disable':
                if (is_null($profiler)) {
                    $profiler = false;
                }
                break;
            case 'start':
            case 'stop':
            case 'measure':
            case 'time':
            case 'exception':
                if ($profiler) {
                    call_user_func_array(array($profiler, $action), $params);
                }
                break;
            case 'breakpoint':
                call_user_func_array(array('BootPress\Profiler\Component', 'addBreakPoint'), $params);
                break;
            default:
                $message = (count($params) > 1) ? $params : array_shift($params);
                if (!empty($message)) {
                    $caller = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 1);
                    BootPress\Profiler\Component::addMessage($action, $message, $caller[0]['file'], $caller[0]['line']);
                }
                break;
        }
    }
}
