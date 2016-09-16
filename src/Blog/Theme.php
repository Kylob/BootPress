<?php

namespace BootPress\Blog;

use BootPress\Page\Component as Page;
use BootPress\Asset\Component as Asset;
use BootPress\Pagination\Component as Pagination;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Aptoma\Twig\Extension\MarkdownExtension;
use Aptoma\Twig\Extension\MarkdownEngineInterface;
use Aptoma\Twig\Extension\MarkdownEngine\PHPLeagueCommonMarkEngine;

class Theme
{
    public static $templates = array();
    public static $functions = array(
        // Array Functions
        'array_​change_​key_​case', // Changes the case of all keys in an array
        'array_​chunk', // Split an array into chunks
        'array_​column', // Return the values from a single column in the input array
        'array_​combine', // Creates an array by using one array for keys and another for its values
        'array_​count_​values', // Counts all the values of an array
        'array_​diff_​assoc', // Computes the difference of arrays with additional index check
        'array_​diff_​key', // Computes the difference of arrays using keys for comparison
        'array_​diff', // Computes the difference of arrays
        'array_​fill_​keys', // Fill an array with values, specifying keys
        'array_​fill', // Fill an array with values
        'array_​filter', // Filters elements of an array using a callback function
        'array_​flip', // Exchanges all keys with their associated values in an array
        'array_​intersect_​assoc', // Computes the intersection of arrays with additional index check
        'array_​intersect_​key', // Computes the intersection of arrays using keys for comparison
        'array_​intersect', // Computes the intersection of arrays
        'array_​key_​exists', // Checks if the given key or index exists in the array
        'array_​keys', // Return all the keys or a subset of the keys of an array
        'array_​map', // Applies the callback to the elements of the given arrays
        'array_​merge_​recursive', // Merge two or more arrays recursively
        'array_​merge', // Merge one or more arrays
        'array_​multisort', // Sort multiple or multi-dimensional arrays
        'array_​pad', // Pad array to the specified length with a value
        'array_​pop', // Pop the element off the end of array
        'array_​product', // Calculate the product of values in an array
        'array_​push', // Push one or more elements onto the end of array
        'array_​rand', // Pick one or more random entries out of an array
        'array_​replace_​recursive', // Replaces elements from passed arrays into the first array recursively
        'array_​replace', // Replaces elements from passed arrays into the first array
        'array_​reverse', // Return an array with elements in reverse order
        'array_​search', // Searches the array for a given value and returns the first corresponding key if successful
        'array_​shift', // Shift an element off the beginning of array
        'array_​slice', // Extract a slice of the array
        'array_​splice', // Remove a portion of the array and replace it with something else
        'array_​sum', // Calculate the sum of values in an array
        'array_​unique', // Removes duplicate values from an array
        'array_​unshift', // Prepend one or more elements to the beginning of an array
        'array_​values', // Return all the values of an array
        'array_​walk_​recursive', // Apply a user function recursively to every member of an array
        'array_​walk', // Apply a user supplied function to every member of an array
        // 'array', // Create an array
        'arsort', // Sort an array in reverse order and maintain index association
        'asort', // Sort an array and maintain index association
        'compact', // Create array containing variables and their values
        'count', // Count all elements in an array, or something in an object
        'current', // Return the current element in an array
        'each', // Return the current key and value pair from an array and advance the array cursor
        'end', // Set the internal pointer of an array to its last element
        // 'extract', // Import variables into the current symbol table from an array
        'in_​array', // Checks if a value exists in an array
        'key', // Fetch a key from an array
        'krsort', // Sort an array by key in reverse order
        'ksort', // Sort an array by key
        // 'list', // Assign variables as if they were an array
        'natcasesort', // Sort an array using a case insensitive "natural order" algorithm
        'natsort', // Sort an array using a "natural order" algorithm
        'next', // Advance the internal array pointer of an array
        'prev', // Rewind the internal array pointer
        'reset', // Set the internal pointer of an array to its first element
        'rsort', // Sort an array in reverse order
        'shuffle', // Shuffle an array
        'sort', // Sort an array

        // Date/Time Functions
        'date_parse', // Returns associative array with detailed info about given date
        'date_sun_info', // Returns an array with information about sunset/sunrise and twilight begin/end
        'getdate', // Get date/time information
        'gettimeofday', // Get current time
        'gmdate', // Format a GMT/UTC date/time
        'gmmktime', // Get Unix timestamp for a GMT date
        'microtime', // Return current Unix timestamp with microseconds
        'mktime', // Get Unix timestamp for a date
        'strtotime', // Parse about any English textual datetime description into a Unix timestamp
        'time', // Return current Unix timestamp
        
        // JSON Functions
        'json_decode', // Decodes a JSON string
        'json_encode', // Returns the JSON representation of a value
        
        // Misc Functions
        'pack', //  Pack data into binary string
        'unpack', // Unpack data from binary string
        
        // Multibyte String Functions
        'mb_convert_case', // Perform case folding on a string
        'mb_convert_encoding', // Convert character encoding
        'mb_strimwidth', // Get truncated string with specified width
        'mb_stripos', // Finds position of first occurrence of a string within another, case insensitive
        'mb_stristr', // Finds first occurrence of a string within another, case insensitive
        'mb_strlen', // Get string length
        'mb_strpos', // Find position of first occurrence of string in a string
        'mb_strrchr', // Finds the last occurrence of a character in a string within another
        'mb_strrichr', // Finds the last occurrence of a character in a string within another, case insensitive
        'mb_strripos', // Finds position of last occurrence of a string within another, case insensitive
        'mb_strrpos', // Find position of last occurrence of a string in a string
        'mb_strstr', // Finds first occurrence of a string within another
        'mb_strtolower', // Make a string lowercase
        'mb_strtoupper', // Make a string uppercase
        'mb_strwidth', // Return width of string
        'mb_substr_count', // Count the number of substring occurrences
        'mb_substr', // Get part of string
        
        // PCRE Functions
        'preg_filter', // Perform a regular expression search and replace
        'preg_grep', // Return array entries that match the pattern
        'preg_match_all', // Perform a global regular expression match
        'preg_match', // Perform a regular expression match
        'preg_quote', // Quote regular expression characters
        'preg_replace', // Perform a regular expression search and replace
        'preg_split', // Split string by a regular expression
        
        // String Functions
        'bin2hex', // Convert binary data into hexadecimal representation
        'chr', // Return a specific character - complements ord()
        'chunk_split', // Split a string into smaller chunks
        'explode', // Split a string by string
        'hex2bin', // Decodes a hexadecimally encoded binary string
        'htmlspecialchars', // Convert special characters to HTML entities
        'implode', // Join array elements with a string
        'lcfirst', // Make a string's first character lowercase
        'ltrim', // Strip whitespace (or other characters) from the beginning of a string
        'nl2br', // Inserts HTML line breaks before all newlines in a string
        'number_format', // Format a number with grouped thousands
        'ord', // Return ASCII value of character - complements chr()
        'rtrim', // Strip whitespace (or other characters) from the end of a string
        'str_ireplace', // Case-insensitive version of str_replace()
        'str_pad', // Pad a string to a certain length with another string
        'str_repeat', // Repeat a string
        'str_replace', // Replace all occurrences of the search string with the replacement string
        'str_rot13', // Perform the rot13 transform on a string
        'str_shuffle', // Randomly shuffles a string
        'str_split', // Convert a string to an array
        'str_word_count', // Return information about words used in a string
        'strip_tags', // Strip HTML and PHP tags from a string
        'stripos', // Find the position of the first occurrence of a case-insensitive substring in a string
        'stristr', // Case-insensitive strstr()
        'strlen', // Get string length
        'strpos', // Find the position of the first occurrence of a substring in a string
        'strrchr', // Find the last occurrence of a character in a string
        'strrev', // Reverse a string
        'strripos', // Find the position of the last occurrence of a case-insensitive substring in a string
        'strrpos', // Find the position of the last occurrence of a substring in a string
        'strstr', // Find the first occurrence of a string
        'strtok', // Tokenize string
        'strtolower', // Make a string lowercase
        'strtoupper', // Make a string uppercase
        'strtr', // Translate characters or replace substrings
        'substr_count', // Count the number of substring occurrences
        'substr', // Return part of a string
        'trim', // Strip whitespace (or other characters) from the beginning and end of a string
        'ucfirst', // Make a string's first character uppercase
        'ucwords', // Uppercase the first character of each word in a string
        'wordwrap', // Wraps a string to a given number of characters
        
        // Variable handling Functions
        'empty', // Determine whether a variable is empty
        'gettype', // Get the type of a variable
        'is_array', // Finds whether a variable is an array
        'is_bool', // Finds out whether a variable is a boolean
        'is_float', // Finds whether the type of a variable is float
        'is_int', // Find whether the type of a variable is integer
        'is_null', // Finds whether a variable is NULL
        'is_numeric', // Finds whether a variable is a number or a numeric string
        'is_string', // Find whether the type of a variable is string
        'isset', // Determine if a variable is set and is not NULL
        'serialize', // Generates a storable representation of a value
        'settype', // Set the type of a variable
        'unserialize', // Creates a PHP value from a stored representation
        'unset', // Unset a given variable
    );
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
            $this->twig->addExtension(new MarkdownExtension(new Markdown($this)));
            $this->twig->addFilter(new \Twig_SimpleFilter('asset', array($this, 'asset')));
            $this->twig->addFunction(new \Twig_SimpleFunction('dump', array($this, 'dump'), array('is_safe'=>'html')));
            $this->twig->registerUndefinedFunctionCallback(function ($name) {
                if (in_array($name, self::$functions)) {
                    return new \Twig_SimpleFunction($name, $name);
                }

                return false;
            });
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
     * Returns an HTML Markdown string from $content, and allows you to set your preferred Markdown provider.
     * 
     * @param string|callable $content 
     * 
     * @return string|null
     */
    public function markdown($content)
    {
        static $markdown = null;
        if ($content instanceof MarkdownEngineInterface) {
            $markdown = $content;
        } elseif (is_null($markdown)) {
            $markdown = new PHPLeagueCommonMarkEngine;
        }

        return (is_string($content)) ? $markdown->transform($content) : null;
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
