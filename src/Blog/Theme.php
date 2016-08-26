<?php

namespace BootPress\Blog;

use BootPress\Page\Component as Page;
use BootPress\Asset\Component as Asset;
use BootPress\Bootstrap\Component as Bootstrap;
use Symfony\Component\Yaml\Yaml;
use Smarty;

class Theme
{
    public $bp;
    private $page;
    private $blog;
    private $asset;
    private $smarty;
    private $vars = array();

    public function __construct(Blog $blog)
    {
        $page = Page::html();
        $this->blog = $blog;
        $this->page = new PageClone();
        $this->bp = Bootstrap::version($this->blog->config('themes', 'bootstrap'));
    }

    /**
     * Establishes global vars that will be accessible to all of your Smarty templates.
     *
     * @param string|array $name  The vars variable.  You can make this an ``array($name => $value, ...)`` to set multiple vars at once.
     * @param mixed        $value Of your vars $name if it is not an array.
     *
     * @return <type>
     */
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

    /**
     * Gives your Smarty templates additional functionality via ``$page->$name(...)``.
     *
     * @param string   $name     To access the $function with.
     * @param callable $function Does something.
     *
     * @throws LogicException If the $funcion is not callable.
     */
    public function addPageMethod($name, $function)
    {
        if (!is_callable($function, false, $method)) {
            throw new \LogicException("'{$method}' cannot be called");
        }
        $this->page->additional[$name] = $function;
    }

    /**
     * Fetches a Smarty template $file.
     *
     * @param string|array $file    The template file.
     * @param array        $vars    To pass to the Smarty template.
     * @param mixed        $testing If anything but (bool) false, then we will lint check the $file.
     *
     * @return string Unless ``$testing !== false`` then we will return (bool) true if the lint check passes, or an error string if it doesn't.
     *
     * @throws LogicException If the $file does not exist, or if it is not in the Blog's 'content' or 'themes' folders.
     */
    public function fetchSmarty($file, array $vars = array(), $testing = false)
    {
        if (is_null($this->smarty)) {
            $functions = array('var_dump', 'preg_replace', 'number_format', 'implode', 'explode', 'array_keys', 'array_values', 'array_flip', 'array_reverse', 'array_shift', 'array_unshift', 'array_pop', 'array_push', 'array_combine', 'array_merge');
            $this->smarty = new Smarty();
            $this->smarty->addPluginsDir($this->blog->folder.'smarty/plugins/');
            $this->smarty->setCompileDir($this->blog->folder.'smarty/templates_c/');
            $this->smarty->setConfigDir($this->blog->folder.'smarty/configs/');
            $this->smarty->setCacheDir($this->blog->folder.'smarty/cache/');
            $this->smarty->setTemplateDir($this->blog->folder);
            $this->smarty->error_reporting = false;
            $security = new \Smarty_Security($this->smarty);
            $security->php_functions = array_merge(array('isset', 'empty', 'count', 'in_array', 'is_array', 'date', 'time', 'nl2br'), $functions); // Smarty defaults (except date)
            $security->allow_super_globals = false;
            $security->allow_constants = false;
            $this->smarty->enableSecurity($security);
            $this->smarty->registerPlugin('modifier', 'asset', array($this, 'asset'));
            $this->smarty->assign('bp', new BPClone($this->bp));
        }
        if (is_array($file)) {
            $vars = (isset($file['vars']) && is_array($file['vars'])) ? $file['vars'] : array();
            $default = (isset($file['default']) && is_dir($file['default'])) ? rtrim($file['default'], '/').'/' : null;
            $file = (isset($file['file']) && is_string($file['file'])) ? $file['file'] : '';
            if ($default && $template = $this->getFiles($file, $default)) {
                $file = array_pop($template);
            }
        }
        $page = Page::html();
        if (strpos($file, $this->blog->folder.'content/') === 0) {
            $dir = dirname($file).'/';
            $file = basename($file);
        } elseif (strpos($file, $this->blog->folder.'themes/') === 0) {
            $dir = $this->blog->folder.'themes/';
            $file = substr($file, strlen($dir));
            $theme = strstr($file, '/', true).'/';
            $dir .= $theme;
            $file = substr($file, strlen($theme));
        } else {
            $file = str_replace($page->dir['page'], '', $file);
            throw new \LogicException("'{$file}' is not in the Blog's 'content' or 'themes' folders.");
        }
        if (!is_file($dir.$file)) {
            $file = substr($file, strlen($this->blog->folder));
            throw new \LogicException("The Blog's '{$file}' file does not exist.");
        }
        $this->asset = array(
            'url' => $page->path('page', substr($dir, strlen($page->dir['page']), -1)),
            'chars' => $page->url['chars'],
        );
        $vars = array_merge($vars, $this->vars);
        unset($vars['bp']);
        $vars['page'] = $this->page;
        $this->smarty->assign($vars);
        try {
            $html = $this->smarty->fetch(substr($dir.$file, strlen($this->blog->folder)));
            if (!empty($vars)) {
                $this->smarty->clearAssign(array_keys($vars));
            }
        } catch (\Exception $e) {
            $page = str_replace('/', DIRECTORY_SEPARATOR, $page->dir['page']);
            $error = str_replace(array($page, '\\'), array('', '/'), $e->getMessage());
            if ($testing) {
                return htmlspecialchars_decode($error);
            }
            $html = '<p>'.$error.'</p>';
        }

        return ($testing) ? true : $html;
    }

    /**
     * Takes an asset string (eg. image.jpg) that is relative to the main Smarty index.tpl being fetched, and prepends the url path to it.
     *
     * @param string|array $path
     *
     * @return string|array Whatever the $path was.  If the $path's string is not a relative asset, then it is just returned as is.  If the $path is an array, then every key and value in it will be turned into a url if it is a relative asset, and the rest of the array will remain the same.
     */
    public function asset($path)
    {
        if (is_string($path)) {
            if ($this->asset && preg_match('/^'.implode('', array(
                '(?!((f|ht)tps?:)?\/\/)',
                '['.$this->asset['chars'].'.\/]+',
                '\.('.Asset::PREG_TYPES.')',
                '.*',
            )).'$/i', ltrim($path), $matches)) {
                $asset = $this->asset['url'].ltrim($matches[0], './');
            }
        } elseif ($this->asset && is_array($path)) {
            $asset = array();
            foreach ($path as $key => $value) {
                $asset[$this->asset($key)] = $this->asset($value);
            }
        }

        return (isset($asset)) ? $asset : $path;
    }
    
    /**
     * Creates a layout using the ``$page->theme`` you have specified.
     *
     * @param string $html The main content of your page.
     *
     * @return string
     */
    public function layout($html)
    {
        $page = Page::html();
        $theme = $page->theme;
        if ($theme === false) {
            return $html;
        } elseif (is_callable($theme)) {
            return $theme($html, $this->bp, $this->vars);
        } elseif (is_file($theme)) {
            return $page->load($theme, array(
                'content' => $html,
                'bp' => $this->bp,
                'vars' => $this->vars,
            ));
        } elseif (!$index = $this->getFiles('index.tpl', __DIR__.'/theme/')) {
            return $html;
        }
        $vars = array(
            'content' => $html,
            'config' => array(),
        );
        if ($config = $this->getFiles('config.yml')) {
            foreach ($config as $file) { // any child values will override the parents
                $vars['config'] = array_merge($vars['config'], (array) Yaml::parse(file_get_contents($file)));
            }
        }

        return $this->fetchSmarty(array_pop($index), $vars);
    }

    /**
     * Gets all the file ``$name``'s within the selected theme.
     *
     * @param string $name    The file you are looking for eg. 'index.tpl'
     * @param string $default The file path to a default template if no other is available.  It will be copied to the theme folder's root.  Must include a trailing slash.
     *
     * @return array|null
     */
    private function getFiles($name, $default = null)
    {
        $files = array();
        if (!empty($name)) {
            $page = Page::html();
            $themes = $page->dir($this->blog->folder, 'themes');
            if (!empty($page->theme) && is_string($page->theme) && is_dir($themes.$page->theme)) {
                $path = str_replace('\\', '/', $page->theme);
            } else {
                $path = $this->blog->config('themes', 'default');
            }
            $paths = array_filter(explode('/', preg_replace('/[^a-z0-9-\/]/', '', $path)));
            if (!empty($paths)) {
                $previous = '';
                foreach ($paths as $level => $dir) {
                    $previous .= $dir.'/';
                    if (is_file($themes.$previous.$name)) {
                        $files[$level] = $themes.$previous.$name;
                    }
                }
                if (empty($files) && !empty($default) && is_file($default.$name)) {
                    $root = array_shift($paths);
                    if (!is_dir($themes.$root)) {
                        mkdir($themes.$root, 0755, true);
                    }
                    $files[] = $page->file($themes, $root, $name);
                    copy($default.$name, $files[0]);
                }
            }
        }

        return (!empty($files)) ? $files : null;
    }
}
