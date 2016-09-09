<?php

namespace BootPress\Blog;

use BootPress\Page\Component as Page;
use BootPress\Asset\Component as Asset;
use BootPress\Pagination\Component as Pagination;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Aptoma\Twig\Extension\MarkdownExtension;
use Aptoma\Twig\Extension\MarkdownEngine\PHPLeagueCommonMarkEngine;

class Theme
{
    public static $templates = array();
    private $vars = array();
    private $blog;
    private $page;
    private $twig;
    private $asset;

    public function __construct(Blog $blog)
    {
        $this->blog = $blog;
        $this->page = new \BootPress\Blog\Page();
    }
    
    /**
     * Establishes global vars that will be accessible to all of your Twig templates.
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
     * Gives your Twig templates additional functionality via ``$page->$name(...)``.
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
     * Fetches a Twig template $file.
     *
     * @param string|array $file The template file.
     * @param array        $vars To pass to the Twig template.
     *
     * @return string
     *
     * @throws LogicException If the $file does not exist, or if it is not in the Blog's 'content' or 'themes' folders.
     */
    public function fetchTwig($file, array $vars = array())
    {
        $page = Page::html();
        if (is_null($this->twig)) {
            foreach (array('content', 'plugins', 'themes') as $dir) {
                if (!is_dir($this->blog->folder.$dir)) {
                    mkdir($this->blog->folder.$dir, 0755, true);
                }
            }
            $loader = new \Twig_Loader_Filesystem($page->dir());
            $loader->addPath($this->blog->folder.'plugins/', 'plugin');
            $this->twig = new \Twig_Environment($loader, array(
                'cache' => $this->blog->folder.'cache/twig/',
                'auto_reload' => true,
                'autoescape' => false,
            ));
            $this->twig->addGlobal('page', $this->page);
            $this->twig->addGlobal('pagination', new Pagination());
            $this->twig->addFilter(new \Twig_SimpleFilter('asset', array($this, 'asset')));
            $this->twig->addExtension(new MarkdownExtension(new PHPLeagueCommonMarkEngine()));
            $this->twig->addFunction(new \Twig_SimpleFunction('dump', array($this, 'dump'), array('is_safe'=>'html')));
            /*
            $twig->registerUndefinedFunctionCallback(function ($name) {
                switch ($name) {
                    case 'isset': // null test
                    case 'empty': // empty test
                    case 'count': // length filter ?
                    case 'in_array': // 
                    case 'is_array': // 
                    case 'date': // date function ?
                    case 'time': // 
                    case 'nl2br': // nl2br filter
                    case 'var_dump': // dump function
                    case 'preg_replace': // 
                    case 'number_format': // number_format filter
                    case 'implode': // join filter
                    case 'explode': // split filter
                    case 'array_keys': // keys filter
                    case 'array_values': // 
                    case 'array_flip': // 
                    case 'array_reverse': // reverse filter
                    case 'array_shift': // 
                    case 'array_unshift': // 
                    case 'array_pop': // 
                    case 'array_push': // 
                    case 'array_combine': // 
                    case 'array_merge': // merge filter
                    
                    // PCRE Functions
                    case 'preg_filter': // Perform a regular expression search and replace
                    case 'preg_grep': // Return array entries that match the pattern
                    case 'preg_match_all': // Perform a global regular expression match
                    case 'preg_match': // Perform a regular expression match
                    case 'preg_quote': // Quote regular expression characters
                    case 'preg_replace': // Perform a regular expression search and replace
                    case 'preg_split': // Split string by a regular expression
                    
                    // String Functions
                    case 'ltrim': // Strip whitespace (or other characters) from the beginning of a string
                    case 'rtrim': // Strip whitespace (or other characters) from the end of a string
                    case 'str_pad': // Pad a string to a certain length with another string
                    case 'str_repeat': // Repeat a string
                    case 'strpos': // Find the position of the first occurrence of a substring in a string
                    case 'strrpos': // Find the position of the last occurrence of a substring in a string
                    case 'strstr': // Find the first occurrence of a string
                    case 'wordwrap': // Wraps a string to a given number of characters

                        return new Twig_SimpleFunction($name, $name);
                        break;
                }
                return false;
            });
            */
        }
        if (is_array($file)) {
            $vars = (isset($file['vars']) && is_array($file['vars'])) ? $file['vars'] : array();
            $default = (isset($file['default']) && is_dir($file['default'])) ? rtrim($file['default'], '/').'/' : null;
            $file = (isset($file['file']) && is_string($file['file'])) ? $file['file'] : '';
            if ($default && $template = $this->getFiles($file, $default)) {
                $file = array_pop($template);
            }
        }
        if (strpos($file, $this->blog->folder.'themes/') === 0) {
            $dir = $this->blog->folder.'themes/';
            $file = substr($file, strlen($dir));
            $theme = strstr($file, '/', true).'/';
            $dir .= $theme;
            $file = substr($file, strlen($theme));
            $loader = $this->twig->getLoader();
            $loader->addPath($dir, 'theme');
        } elseif (strpos($file, $page->dir()) === 0) {
            $dir = dirname($file).'/';
            $file = basename($file);
        } else {
            throw new \LogicException("The '{$file}' is not in your website's Page::dir folder.");
        }
        if (!is_file($dir.$file)) {
            $file = substr($file, strlen($page->dir()));
            throw new \LogicException("The '{$file}' file does not exist.");
        }
        $this->asset = array(
            'url' => $page->path('page', substr($dir, strlen($page->dir['page']), -1)),
            'chars' => $page->url['chars'],
        );
        $template = substr($dir.$file, strlen($page->dir()));
        $vars = array_merge($this->vars, $vars);
        unset($vars['page']);
        self::$templates[] = array('template'=>$template, 'vars'=>$vars);
        try {
            $html = $this->twig->render($template, $vars);
        } catch (\Exception $e) {
            $html = '<p>'.$e->getMessage().'</p>';
        }

        return $html;
    }

    /**
     * Takes an asset string (eg. image.jpg) that is relative to the main index.html.twig being fetched, and prepends the url path to it.
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
     * Dumps a beautifully formatted debug string of your $var.
     * 
     * @param mixed $var If you don't have one, then we will pass the current template name and vars that we initially gave you.  Objects will only be named, and not displayed.
     * 
     * @return string
     */
    public function dump($var = null)
    {
        if (func_num_args() == 0) {
            $var = array_slice(self::$templates, -1);
            $var = array_shift($var);
        } elseif (is_object($var)) {
            $var = get_class($var).' Object';
        } elseif (is_array($var)) {
            array_walk_recursive($var, function (&$value) {
                $value = is_object($value) ? get_class($value).' Object' : $value;
            });
        }
        $dumper = new HtmlDumper();
        $cloner = new VarCloner();
        $cloner->setMaxString(100);
        $output = '';
        $dumper->dump($cloner->cloneVar($var), function ($line, $depth) use (&$output) {
            $output .= ($depth >= 0) ? str_repeat("    ", $depth).$line."\n" : '';
        });
        return trim($output);
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
            return $theme($html, $this->vars);
        } elseif (is_file($theme)) {
            return $page->load($theme, array(
                'content' => $html,
                'vars' => $this->vars,
            ));
        } elseif (!$index = $this->getFiles('index.html.twig', __DIR__.'/theme/')) {
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

        return $this->fetchTwig(array_pop($index), $vars);
    }

    /**
     * Gets all the file ``$name``'s within the selected theme.
     *
     * @param string $name    The file you are looking for eg. 'index.html.twig'
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
                $path = $this->blog->config('blog', 'theme');
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
