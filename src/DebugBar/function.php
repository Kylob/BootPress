<?php

if (!function_exists('debugbar')) {
    function debugbar($action, $params = null)
    {
        static $debugbar = null;
        $params = func_get_args();
        $action = (string) array_shift($params);
        switch ($action) {
            case 'class':
                return $debugbar ? $debugbar::$debugbar : false;
                break;
            case 'enable':
                if (is_null($debugbar)) {
                    $enable = array_shift($params);
                    $messages = array_shift($params);
                    $enable = (is_array($enable)) ? $enable : array();
                    $messages = (is_array($messages)) ? $messages : array();
                    $debugbar = new BootPress\DebugBar\Component($enable, $messages);
                }
                break;
            case 'disable':
                if (is_null($debugbar)) {
                    $debugbar = false;
                }
                break;
            case 'start':
            case 'stop':
            case 'measure':
            case 'time':
            case 'exception':
                if ($debugbar) {
                    call_user_func_array(array($debugbar, $action), $params);
                }
                break;
            case 'breakpoint':
                call_user_func_array(array('BootPress\DebugBar\Component', 'addBreakPoint'), $params);
                break;
            default:
                $message = (count($params) > 1) ? $params : array_shift($params);
                if (!empty($message)) {
                    $caller = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 1);
                    BootPress\DebugBar\Component::addMessage($action, $message, $caller[0]['file'], $caller[0]['line']);
                }
                break;
        }
    }
}
