<?php

namespace BootPress\DebugBar;

use BootPress\Page\Component as Page;
use Symfony\Component\HttpFoundation\Response;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DebugBar;

class Component
{
    protected static $breakpoints = array();
    protected static $files = array();
    protected static $logs = array();
    private $debugbar;
    private $renderer;
    private $messages;

    public static function get($info)
    {
        switch ($info) {
            case 'breakpoints':
            case 'files':
            case 'logs':
                return static::$$info;
                break;
        }
    }

    public static function addBreakPoint($name, $first = 'Page Load')
    {
        if (empty(static::$breakpoints)) {
            array_unshift(static::$logs, array('info', '"'.$first.'" BreakPoint', '', '', 0, 0));
            static::$breakpoints[] = $first;
        }
        $previous = array_pop(static::$breakpoints);
        foreach (get_included_files() as $new) {
            if (!isset(static::$files[$new])) {
                static::$files[$new] = '';
            }
        }
        $memory = memory_get_usage();
        $time = microtime(true);
        static::$breakpoints[] = array($previous, count(static::$files), $memory, $time);
        static::$logs[] = array('info', '"'.$name.'" BreakPoint', '', '', $memory, $time);
        static::$breakpoints[] = $name;
    }

    public static function addMessage($action, $message, $file = '', $line = '')
    {
        if (is_file($file)) {
            static::$files[$file] = '';
        }
        $file = str_replace('\\', '/', $file);
        static::$logs[] = array($action, $message, $file, $line, memory_get_usage(), microtime(true));
    }

    public function __construct(array $enable = array(), array $messages = array())
    {
        $started = microtime(true);
        $this->debugbar = new DebugBar();
        $page = Page::html();
        $plugin = $page->dirname('DebugBar\DebugBar');
        $url = $page->path($plugin, 'Resources');
        $dir = $page->dir($plugin, 'Resources');
        $this->renderer = $this->debugbar->getJavascriptRenderer($url, $dir);
        DataCollector::setDefaultDataFormatter(new \BootPress\DebugBar\DataFormatter());
        $map = array(
            'php' => 'DebugBar\DataCollector\PhpInfoCollector',
            'bootpress' => 'BootPress\DebugBar\Collector\BootPress',
            'timeline' => 'DebugBar\DataCollector\TimeDataCollector',
            'messages' => 'DebugBar\DataCollector\MessagesCollector',
            'files' => 'BootPress\DebugBar\Collector\Files',
            'exceptions' => 'DebugBar\DataCollector\ExceptionsCollector',
            'queries' => 'BootPress\DebugBar\Collector\Queries',
            'memory' => 'DebugBar\DataCollector\MemoryCollector',
        );
        if (empty($enable)) {
            $enable = array_keys($map);
        }
        foreach ($enable as $collector) {
            if ($collector instanceof DataCollector) {
                $this->debugbar->addCollector($collector);
            } elseif (class_exists($collector)) {
                $this->debugbar->addCollector(new $collector());
            } elseif (is_string($collector)) {
                $class = strtolower($collector);
                if (isset($map[$class])) {
                    $this->debugbar->addCollector(new $map[$class]());
                }
            }
        }
        if (empty($messages)) {
            $messages = array('log', 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency');
        }
        $this->messages = array_fill_keys($messages, true);
        $page->filter('css', function ($css) use ($page, $url, $dir) {
            foreach ($this->renderer->getAssets('css') as $file) {
                $css[] = str_replace($dir, $url, $file);
            }
            $css[] = $page->url($page->dirname(__CLASS__), 'debugbar.css');

            return $css;
        });
        $page->filter('javascript', function ($js) use ($url, $dir) {
            foreach ($this->renderer->getAssets('js') as $file) {
                $js[] = str_replace($dir, $url, $file);
            }

            return $js;
        });
        $page->filter('response', function ($page, $response, $type) {
            if (in_array($type, array('redirect', 'json', 'html'))) {
                self::addBreakPoint('Render DebugBar');
                array_pop(self::$logs);
                array_pop(self::$breakpoints);
                if ($this->debugbar->hasCollector('time')) {
                    $chunk = 0;
                    foreach (static::$breakpoints as $bp) {
                        if (is_array($bp)) {
                            list($name, $file, $memory, $time) = $bp;
                            $name = '('.DataCollector::getDefaultDataFormatter()->formatBytes($memory - $chunk).') "'.$name.'" BreakPoint';
                            $chunk = $memory;
                            if (!isset($start)) {
                                $start = $this->debugbar['time']->getRequestStartTime();
                            }
                            $this->time($name, $start, $time);
                            $start = $time;
                        } else {
                            $this->start('"'.$bp.'" BreakPoint');
                        }
                    }
                    $this->start('Render DebugBar');
                }
                if ($this->debugbar->hasCollector('bootpress')) {
                    $this->debugbar->getCollector('bootpress')->setResponse($response);
                }
                $files = str_replace('\\', '/', array_keys(static::$files));
                $base = $page->commonDir($files);
                $len = mb_strlen($base);
                $files = array_flip($files);
                foreach ($files as $file => $path) {
                    $files[$file] = substr($file, $len);
                }
                $files['base'] = $base;
                static::$files = $files;
                foreach (static::$logs as $log) {
                    $action = array_shift($log);
                    list($message, $file, $line, $memory, $time) = $log;
                    $this->$action($message, $file, $line, $memory, $time);
                }
                if ($type == 'html') {
                    $debugbar = $this->renderer->render();
                    $content = $response->getContent();
                    if (false !== $body = strripos($content, '</body>')) {
                        $content = substr($content, 0, $body).$debugbar.substr($content, $body);
                    } else {
                        $content .= $debugbar;
                    }
                    $response->setContent($content);
                } else {
                    $httpDriver = new SymfonyHttpDriver($page->session, $response);
                    $this->debugbar->setHttpDriver($httpDriver);
                    if ($type == 'json') {
                        $this->debugbar->sendDataInHeaders();
                    } else { // $type == 'redirect'
                        $this->debugbar->stackData();
                    }
                }
            }
        });
        $this->time('Enable DebugBar', $started, microtime(true));
    }

    public function __call($method, $arguments)
    {
        switch ($method) {
            case 'start':
                if ($this->debugbar->hasCollector('time')) {
                    call_user_func_array(array($this->debugbar['time'], 'startMeasure'), $arguments);
                }
                break;
            case 'stop':
                if ($this->debugbar->hasCollector('time')) {
                    call_user_func_array(array($this->debugbar['time'], 'stopMeasure'), $arguments);
                }
                break;
            case 'measure':
                if ($this->debugbar->hasCollector('time')) {
                    call_user_func_array(array($this->debugbar['time'], 'measure'), $arguments);
                }
                break;
            case 'time':
                if ($this->debugbar->hasCollector('time')) {
                    call_user_func_array(array($this->debugbar['time'], 'addMeasure'), $arguments);
                }
                break;
            case 'exception':
                if ($this->debugbar->hasCollector('exceptions')) {
                    call_user_func_array(array($this->debugbar['exceptions'], 'addException'), $arguments);
                }
                break;
            default:
                if ($this->debugbar->hasCollector('messages') && isset($this->messages[$method])) {
                    list($message, $file, $line, $memory, $time) = $arguments;
                    $is_string = true;
                    if (!is_string($message)) {
                        $message = DataCollector::getDefaultDataFormatter()->formatVar($message);
                        $is_string = false;
                    }
                    $prepend = array();
                    if (is_numeric($memory) && is_numeric($time) && $this->debugbar->hasCollector('time')) {
                        $memory = DataCollector::getDefaultDataFormatter()->formatBytes($memory);
                        $time = ($time > 0) ? $time - $this->debugbar['time']->getRequestStartTime() : 0;
                        $time = DataCollector::getDefaultDataFormatter()->formatDuration($time);
                        $prepend[] = '['.$memory.' | '.$time.']';
                    }
                    if (is_numeric($line) && isset(static::$files[$file]) && !empty(static::$files[$file])) {
                        $prepend[] = '['.static::$files[$file].' Ln:'.$line.']';
                    }
                    $message = trim(implode(' ', $prepend).' '.$message);
                    $this->debugbar['messages']->addMessage($message, $method, $is_string);
                }
                break;
        }
    }
}
