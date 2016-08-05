<?php

namespace BootPress\Theme;

use BootPress\Page\Component as Page;
use BootPress\Bootstrap\Component as Bootstrap;
use Symfony\Component\Yaml\Yaml;
use Smarty;

class Component
{
    public $bp;
    private $page;
    private $vars;
    private $config;
    private $folder;

    /**
     * Theme folder getter.
     * 
     * @param string $name 
     * 
     * @return null|string
     */
    public function __get($name)
    {
        switch ($name) {
            case 'folder':
                return $this->$name;
                break;
        }
    }

    public function __construct($folder = 'themes')
    {
        $this->folder = Page::html()->dir($folder);
        if (!is_dir($this->folder)) {
            mkdir($this->folder, 0755, true);
        }
        $file = $this->folder.'config.yml';
        $this->config = (is_file($file)) ? (array) Yaml::parse(file_get_contents($file)) : array();
        if (!isset($this->config['default']) || !isset($this->config['bootstrap'])) {
            $this->config = array_merge(array(
                'default' => '',
                'bootstrap' => '3.3.6',
            ), $this->config);
            file_put_contents($file, Yaml::dump($this->config, 3));
        }
        $this->vars = array();
        $this->page = new PageClone();
        $this->bp = Bootstrap::version($this->config['bootstrap']);
    }

    public function layout($html)
    {
        $page = Page::html();
        $theme = $page->theme;
        if ($theme === false) {
            return $html;
        } elseif (is_callable($theme)) {
            return $theme($html, $this->config);
        } elseif (is_file($theme)) {
            return $page->load($theme, array(
                'content' => $html,
                'config' => $this->config,
            ));
        } elseif (!$index = $this->getTemplate('index.tpl')) {
            return $html;
        }
        $vars = array(
            'content' => $html,
            'config' => $this->config
        );
        if ($index['dir'] != $this->folder) {
            $theme = substr($index['dir'], mb_strlen($this->folder)); // with trailing slash
            $parent = strstr($theme, '/', true); // good thing we had that trailing slash
            $path = substr($index['dir'], mb_strlen($page->dir()));
            $page->url('set', 'theme', $page->url['base'].'page/'.$path);
            $config = array();
            $previous = '';
            foreach (explode('/', substr($theme, 0, -1)) as $folder) {
                $previous .= $folder . '/';
                $file = $this->folder . $previous . 'config.yml';
                if (is_file($file)) { // any child values will override the parents
                    $config = array_merge($config, (array) Yaml::parse(file_get_contents($file)));
                }
            }
            $vars['config'] += $config; // The original config values override them all
        }
        
        return $this->fetchSmarty($index['file'], $vars);
    }

    public function globalVars($name, $value = null)
    {
        $vars = (is_array($name)) ? $name : array($name => $value);
        foreach ($vars as $name => $value) {
            if (is_array($value) && isset($this->vars[$name]) && is_array($this->vars[$name])) {
                $this->vars[$name] = array_merge($this->vars[$name], $value);
            } else {
                $this->vars[$name] = $value;
            }
        }
    }

    public function addPageMethod($name, $function)
    {
        if (!is_callable($function, false, $method)) {
            throw new \LogicException("'{$method}' cannot be called");
        }
        $this->page->additional[$name] = $function;
    }

    public function fetchSmarty($file, array $vars = array(), $testing = false)
    {
        static $smarty = null;
        if (is_null($smarty)) {
            $functions = array('var_dump', 'preg_replace', 'number_format', 'implode', 'explode', 'array_keys', 'array_values', 'array_flip', 'array_reverse', 'array_shift', 'array_unshift', 'array_pop', 'array_push', 'array_combine', 'array_merge');
            $smarty = new Smarty();
            $smarty->addPluginsDir($this->folder.'smarty/plugins/');
            $smarty->setCompileDir($this->folder.'smarty/templates_c/');
            $smarty->setConfigDir($this->folder.'smarty/configs/');
            $smarty->setCacheDir($this->folder.'smarty/cache/');
            $smarty->error_reporting = false;
            $security = new \Smarty_Security($smarty);
            $security->php_functions = array_merge(array('isset', 'empty', 'count', 'in_array', 'is_array', 'date', 'time', 'nl2br'), $functions); // Smarty defaults (except date)
            $security->allow_super_globals = false;
            $security->allow_constants = false;
            $smarty->enableSecurity($security);
            $smarty->assign('bp', new BPClone($this->bp));
        }
        $default = null;
        if (is_array($file)) {
            if (isset($file['default']) && is_dir($file['default'])) {
                $default = rtrim($file['default'], '/').'/';
            }
            if (isset($file['vars']) && is_array($file['vars'])) {
                $vars = $file['vars'];
            }
            $file = (isset($file['file']) && is_string($file['file'])) ? $file['file'] : '';
        }
        $page = Page::html();
        if (is_file($file) && strpos($file, $page->dir['page']) === 0) {
            if (strpos($file, $this->folder) === 0) {
                $dir = $this->folder;
                $file = substr($file, strlen($this->folder));
            } else {
                $dir = dirname($file).'/';
                $file = basename($file);
            }
            $page->url('set', 'folder', 'page/'.substr($dir, strlen($page->dir['page']), -1));
        } elseif ($template = $this->getTemplate($file, $default)) {
            $dir = $this->folder;
            $file = substr($template['file'], strlen($this->folder));
            $page->url('set', 'folder', 'page/'.$template['folder']);
        } else {
            return '<p>The "'.$file.'" file does not exist.</p>';
        }
        $vars = array_merge($vars, $this->vars);
        unset($vars['bp']);
        $vars['page'] = $this->page;
        $smarty->assign($vars);
        $smarty->setTemplateDir($dir);
        try {
            $html = $smarty->fetch($file);
            if (!empty($vars)) {
                $smarty->clearAssign(array_keys($vars));
            }
        } catch (\Exception $e) {
            $dir = str_replace('/', DIRECTORY_SEPARATOR, $this->folder);
            $page = str_replace('/', DIRECTORY_SEPARATOR, $page->dir['page']);
            $error = str_replace(array($dir, $page, '\\'), array('', '', '/'), $e->getMessage());
            if ($testing) {
                return htmlspecialchars_decode($error);
            }
            $html = '<p>'.$error.'</p>';
        }

        return ($testing) ? true : $html;
    }

    /**
     * Gets a template $file following a given pecking order.  If a ``$page->theme`` has been set and the folder exists, it looks there first.  If not and a default theme folder (that exists) has been established in the config file, then we will look for one there.  Otherwise we look in the root theme folder where the config file is.
     * 
     * @param string $file    The file name you are looking for eg. 'index.tpl'
     * @param string $default A file path to a default template if no other is available.  This will be saved (only once) in the root theme folder alongside the config file.
     * 
     * @return array Same as is returned from the ``$page->folder()`` method.
     */
    public function getTemplate($file, $default = null)
    {
        if (empty($file)) {
            return;
        }
        $page = Page::html();
        if (!is_null($default) && !is_file($this->folder.$file) && is_file($default.$file)) {
            copy($default.$file, $this->folder.$file);
        }
        if (!empty($page->theme) && is_string($page->theme) && is_dir($this->folder.$page->theme)) {
            $dir = $this->folder.$page->theme;
        } elseif (!empty($this->config['default']) && is_dir($this->folder.$this->config['default'])) {
            $dir = $this->folder.$this->config['default'];
        } else {
            $dir = $this->folder;
        }
        $path = substr($dir, strlen($this->folder));

        return $page->folder($this->folder, $path, $file);
    }
}
