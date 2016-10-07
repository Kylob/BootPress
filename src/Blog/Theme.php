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
    private $vars = array();
    private $blog;
    private $page;
    private $twig;
    private $asset;
    private $plugin;

    public function __construct(Blog $blog)
    {
        $this->blog = $blog;
        $this->page = new \BootPress\Blog\Page();
    }

    /**
     * Gets the Twig_Environment instance.  If you get to this before we do, then you can customize the ``$options``.
     * 
     * @param array $options An array of options.
     * 
     * @return object
     *
     * @link http://twig.sensiolabs.org/doc/api.html#environment-options
     */
    public function getTwig(array $options = array())
    {
        if (is_null($this->twig)) {
            $page = Page::html();
            foreach (array('content', 'plugins', 'themes') as $dir) {
                if (!is_dir($this->blog->folder.$dir)) {
                    mkdir($this->blog->folder.$dir, 0755, true);
                }
            }
            $loader = new \Twig_Loader_Filesystem($page->dir());
            $loader->addPath($this->blog->folder.'plugins/', 'plugin');
            $this->twig = new \Twig_Environment($loader, array_merge(array(
                'cache' => $this->blog->folder.'cache/twig/',
                'auto_reload' => true,
                'autoescape' => false,
            ), $options));
            $this->twig->addGlobal('page', $this->page);
            $this->twig->addGlobal('pagination', new Pagination());
            $this->twig->addExtension(new MarkdownExtension(new Markdown($this)));
            $this->twig->addFilter(new \Twig_SimpleFilter('asset', array($this, 'asset')));
            $this->twig->addFunction(new \Twig_SimpleFunction('this', array($this, 'this')));
            $this->twig->addFunction(new \Twig_SimpleFunction('dump', array($this, 'dump'), array('is_safe' => 'html')));
            $this->twig->registerUndefinedFunctionCallback(function ($name) {
                switch ($name) {
                    // Array Functions
                    case 'array_change_key_case': // Changes the case of all keys in an array
                    case 'array_chunk': // Split an array into chunks
                    case 'array_column': // Return the values from a single column in the input array
                    case 'array_combine': // Creates an array by using one array for keys and another for its values
                    case 'array_count_values': // Counts all the values of an array
                    case 'array_diff_assoc': // Computes the difference of arrays with additional index check
                    case 'array_diff_key': // Computes the difference of arrays using keys for comparison
                    case 'array_diff': // Computes the difference of arrays
                    case 'array_fill_keys': // Fill an array with values, specifying keys
                    case 'array_fill': // Fill an array with values
                    case 'array_filter': // Filters elements of an array using a callback function
                    case 'array_flip': // Exchanges all keys with their associated values in an array
                    case 'array_intersect_assoc': // Computes the intersection of arrays with additional index check
                    case 'array_intersect_key': // Computes the intersection of arrays using keys for comparison
                    case 'array_intersect': // Computes the intersection of arrays
                    case 'array_key_exists': // Checks if the given key or index exists in the array
                    case 'array_keys': // Return all the keys or a subset of the keys of an array
                    case 'array_map': // Applies the callback to the elements of the given arrays
                    case 'array_merge_recursive': // Merge two or more arrays recursively
                    case 'array_merge': // Merge one or more arrays
                    case 'array_pad': // Pad array to the specified length with a value
                    case 'array_product': // Calculate the product of values in an array
                    case 'array_rand': // Pick one or more random entries out of an array
                    case 'array_replace_recursive': // Replaces elements from passed arrays into the first array recursively
                    case 'array_replace': // Replaces elements from passed arrays into the first array
                    case 'array_reverse': // Return an array with elements in reverse order
                    case 'array_search': // Searches the array for a given value and returns the first corresponding key if successful
                    case 'array_slice': // Extract a slice of the array
                    case 'array_sum': // Calculate the sum of values in an array
                    case 'array_unique': // Removes duplicate values from an array
                    case 'array_values': // Return all the values of an array
                    case 'count': // Count all elements in an array, or something in an object
                    case 'in_array': // Checks if a value exists in an array

                    // Date/Time Functions
                    case 'date_parse': // Returns associative array with detailed info about given date
                    case 'date_sun_info': // Returns an array with information about sunset/sunrise and twilight begin/end
                    case 'getdate': // Get date/time information
                    case 'gettimeofday': // Get current time
                    case 'gmdate': // Format a GMT/UTC date/time
                    case 'gmmktime': // Get Unix timestamp for a GMT date
                    case 'microtime': // Return current Unix timestamp with microseconds
                    case 'mktime': // Get Unix timestamp for a date
                    case 'strtotime': // Parse about any English textual datetime description into a Unix timestamp
                    case 'time': // Return current Unix timestamp

                    // JSON Functions
                    case 'json_decode': // Decodes a JSON string
                    case 'json_encode': // Returns the JSON representation of a value

                    // Mail Functions
                    case 'mail': // Send mail

                    // Math Functions
                    case 'abs': // Absolute value
                    case 'acos': // Arc cosine
                    case 'acosh': // Inverse hyperbolic cosine
                    case 'asin': // Arc sine
                    case 'asinh': // Inverse hyperbolic sine
                    case 'atan2': // Arc tangent of two variables
                    case 'atan': // Arc tangent
                    case 'atanh': // Inverse hyperbolic tangent
                    case 'base_convert': // Convert a number between arbitrary bases
                    case 'bindec': // Binary to decimal
                    case 'ceil': // Round fractions up
                    case 'cos': // Cosine
                    case 'cosh': // Hyperbolic cosine
                    case 'decbin': // Decimal to binary
                    case 'dechex': // Decimal to hexadecimal
                    case 'decoct': // Decimal to octal
                    case 'deg2rad': // Converts the number in degrees to the radian equivalent
                    case 'exp': // Calculates the exponent of e
                    case 'expm1': // Returns exp(number) - 1, computed in a way that is accurate even when the value of number is close to zero
                    case 'floor': // Round fractions down
                    case 'fmod': // Returns the floating point remainder (modulo) of the division of the arguments
                    case 'getrandmax': // Show largest possible random value
                    case 'hexdec': // Hexadecimal to decimal
                    case 'hypot': // Calculate the length of the hypotenuse of a right-angle triangle
                    case 'is_finite': // Finds whether a value is a legal finite number
                    case 'is_infinite': // Finds whether a value is infinite
                    case 'is_nan': // Finds whether a value is not a number
                    case 'lcg_value': // Combined linear congruential generator
                    case 'log10': // Base-10 logarithm
                    case 'log1p': // Returns log(1 + number), computed in a way that is accurate even when the value of number is close to zero
                    case 'log': // Natural logarithm
                    case 'mt_getrandmax': // Show largest possible random value
                    case 'mt_rand': // Generate a better random value
                    case 'mt_srand': // Seed the better random number generator
                    case 'octdec': // Octal to decimal
                    case 'pi': // Get value of pi
                    case 'pow': // Exponential expression
                    case 'rad2deg': // Converts the radian number to the equivalent number in degrees
                    case 'rand': // Generate a random integer
                    case 'round': // Rounds a float
                    case 'sin': // Sine
                    case 'sinh': // Hyperbolic sine
                    case 'sqrt': // Square root
                    case 'srand': // Seed the random number generator
                    case 'tan': // Tangent
                    case 'tanh': // Hyperbolic tangent

                    // Misc Functions
                    case 'pack': //  Pack data into binary string
                    case 'unpack': // Unpack data from binary string

                    // Multibyte String Functions
                    case 'mb_convert_case': // Perform case folding on a string
                    case 'mb_convert_encoding': // Convert character encoding
                    case 'mb_strimwidth': // Get truncated string with specified width
                    case 'mb_stripos': // Finds position of first occurrence of a string within another, case insensitive
                    case 'mb_stristr': // Finds first occurrence of a string within another, case insensitive
                    case 'mb_strlen': // Get string length
                    case 'mb_strpos': // Find position of first occurrence of string in a string
                    case 'mb_strrchr': // Finds the last occurrence of a character in a string within another
                    case 'mb_strrichr': // Finds the last occurrence of a character in a string within another, case insensitive
                    case 'mb_strripos': // Finds position of last occurrence of a string within another, case insensitive
                    case 'mb_strrpos': // Find position of last occurrence of a string in a string
                    case 'mb_strstr': // Finds first occurrence of a string within another
                    case 'mb_strtolower': // Make a string lowercase
                    case 'mb_strtoupper': // Make a string uppercase
                    case 'mb_strwidth': // Return width of string
                    case 'mb_substr_count': // Count the number of substring occurrences
                    case 'mb_substr': // Get part of string

                    // String Functions
                    case 'addcslashes': // Quote string with slashes in a C style
                    case 'addslashes': // Quote string with slashes
                    case 'bin2hex': // Convert binary data into hexadecimal representation
                    case 'chr': // Return a specific character - complements ord()
                    case 'chunk_split': // Split a string into smaller chunks
                    case 'explode': // Split a string by string
                    case 'hex2bin': // Decodes a hexadecimally encoded binary string
                    case 'htmlspecialchars': // Convert special characters to HTML entities
                    case 'implode': // Join array elements with a string
                    case 'lcfirst': // Make a string's first character lowercase
                    case 'ltrim': // Strip whitespace (or other characters) from the beginning of a string
                    case 'nl2br': // Inserts HTML line breaks before all newlines in a string
                    case 'number_format': // Format a number with grouped thousands
                    case 'ord': // Return ASCII value of character - complements chr()
                    case 'rtrim': // Strip whitespace (or other characters) from the end of a string
                    case 'str_ireplace': // Case-insensitive version of str_replace()
                    case 'str_pad': // Pad a string to a certain length with another string
                    case 'str_repeat': // Repeat a string
                    case 'str_replace': // Replace all occurrences of the search string with the replacement string
                    case 'str_rot13': // Perform the rot13 transform on a string
                    case 'str_shuffle': // Randomly shuffles a string
                    case 'str_split': // Convert a string to an array
                    case 'str_word_count': // Return information about words used in a string
                    case 'strip_tags': // Strip HTML and PHP tags from a string
                    case 'stripos': // Find the position of the first occurrence of a case-insensitive substring in a string
                    case 'stristr': // Case-insensitive strstr()
                    case 'strlen': // Get string length
                    case 'strpos': // Find the position of the first occurrence of a substring in a string
                    case 'strrchr': // Find the last occurrence of a character in a string
                    case 'strrev': // Reverse a string
                    case 'strripos': // Find the position of the last occurrence of a case-insensitive substring in a string
                    case 'strrpos': // Find the position of the last occurrence of a substring in a string
                    case 'strstr': // Find the first occurrence of a string
                    case 'strtok': // Tokenize string
                    case 'strtolower': // Make a string lowercase
                    case 'strtoupper': // Make a string uppercase
                    case 'strtr': // Translate characters or replace substrings
                    case 'substr_count': // Count the number of substring occurrences
                    case 'substr': // Return part of a string
                    case 'trim': // Strip whitespace (or other characters) from the beginning and end of a string
                    case 'ucfirst': // Make a string's first character uppercase
                    case 'ucwords': // Uppercase the first character of each word in a string
                    case 'wordwrap': // Wraps a string to a given number of characters

                    // Variable handling Functions
                    case 'gettype': // Get the type of a variable
                    case 'is_array': // Finds whether a variable is an array
                    case 'is_bool': // Finds out whether a variable is a boolean
                    case 'is_float': // Finds whether the type of a variable is float
                    case 'is_int': // Find whether the type of a variable is integer
                    case 'is_null': // Finds whether a variable is NULL
                    case 'is_numeric': // Finds whether a variable is a number or a numeric string
                    case 'is_string': // Find whether the type of a variable is string
                    case 'serialize': // Generates a storable representation of a value
                    case 'unserialize': // Creates a PHP value from a stored representation
                        return new \Twig_SimpleFunction($name, $name);
                        break;

                    // Return by reference, so we return directly
                    case 'array_pop': // Pop the element off the end of array
                    case 'array_shift': // Shift an element off the beginning of array
                    case 'array_splice': // Remove a portion of the array and replace it with something else
                    case 'arsort': // Sort an array in reverse order and maintain index association
                    case 'asort': // Sort an array and maintain index association
                    case 'krsort': // Sort an array by key in reverse order
                    case 'ksort': // Sort an array by key
                    case 'natcasesort': // Sort an array using a case insensitive "natural order" algorithm
                    case 'natsort': // Sort an array using a "natural order" algorithm
                    case 'rsort': // Sort an array in reverse order
                    case 'shuffle': // Shuffle an array
                    case 'sort': // Sort an array
                    case 'settype': // Set the type of a variable
                        return new \Twig_SimpleFunction($name, function () use ($name) {
                            $args = func_get_args();
                            $reference = array_shift($args);
                            switch (count($args)) {
                                case 0: $name($reference); break;
                                case 1: $name($reference, array_shift($args)); break;
                                case 2: $name($reference, array_shift($args), array_shift($args)); break;
                                default: $name($reference, array_shift($args), array_shift($args), array_shift($args)); break;
                            }

                            return $reference;
                        });
                        break;

                    // PCRE Functions
                    case 'preg_filter': // Perform a regular expression search and replace - 4
                    case 'preg_grep': // Return array entries that match the pattern
                    case 'preg_match_all': // Perform a global regular expression match
                    case 'preg_match': // Perform a regular expression match
                    case 'preg_quote': // Quote regular expression characters
                    case 'preg_replace': // Perform a regular expression search and replace - 4
                    case 'preg_split': // Split string by a regular expression - 3
                        return new \Twig_SimpleFunction($name, function () use ($name) {
                            $args = func_get_args();
                            $pattern = array_shift($args);
                            if ($name != 'preg_quote') {
                                $this->removeEval($pattern);
                            }
                            if (strpos($name, 'match')) {
                                $name($pattern, array_shift($args), $matches);

                                return $matches;
                            } else {
                                array_unshift($args, $pattern);

                                return call_user_func_array($name, $args);
                            }
                        });
                        break;
                }

                return false;
            });
        }

        return $this->twig;
    }

    /**
     * Renders a Twig template $file.
     *
     * @param string|array $file The template file.
     * @param array        $vars To pass to the Twig template.
     *
     * @return string
     *
     * @throws LogicException If the $file does not exist, or if it is not in the Blog's 'content' or 'themes' folders.
     */
    public function renderTwig($file, array $vars = array())
    {
        $page = Page::html();
        if (is_array($file)) {
            $vars = (isset($file['vars']) && is_array($file['vars'])) ? $file['vars'] : array();
            $default = (isset($file['default']) && is_dir($file['default'])) ? rtrim($file['default'], '/').'/' : null;
            $file = (isset($file['file']) && is_string($file['file'])) ? $file['file'] : '';
            if ($default && $template = $this->getFiles($file, $default)) {
                $file = array_pop($template);
            }
            if (empty($file)) {
                return (isset($vars['content'])) ? $vars['content'] : '';
            }
        }
        if (strpos($file, $this->blog->folder.'themes/') === 0) {
            $dir = $this->blog->folder.'themes/';
            $file = substr($file, strlen($dir));
            $theme = strstr($file, '/', true).'/';
            $dir .= $theme;
            $file = substr($file, strlen($theme));
            $loader = $this->getTwig()->getLoader();
            $loader->addPath($dir, 'theme');
        } elseif (strpos($file, $page->dir()) === 0) {
            $dir = dirname($file).'/';
            $file = basename($file);
        } else {
            throw new \LogicException("The '{$file}' is not in your website's Page::dir folder.");
        }
        if (!is_file($dir.$file)) {
            $file = substr($dir.$file, strlen($page->dir()));
            throw new \LogicException("The '{$file}' file does not exist.");
        }
        $this->asset = array(
            'dir' => $dir,
            'chars' => $page->url['chars'],
        );
        $template = substr($dir.$file, strlen($page->dir()));
        $vars = array_merge($this->vars, $vars);
        unset($vars['page']);
        self::$templates[] = array('template' => $template, 'vars' => $vars);
        try {
            $html = $this->getTwig()->render($template, $vars);
        } catch (\Exception $e) {
            $html = '<p>'.$e->getMessage().'</p>';
        }

        return $html;
    }

    /**
     * Establishes global vars that will be accessible to all of your Twig templates.
     *
     * @param string|array $name  The vars variable.  You can make this an ``array($name => $value, ...)`` to set multiple vars at once.
     * @param mixed        $value Of your vars $name if it is not an array.
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
            $markdown = new PHPLeagueCommonMarkEngine();
        }

        return (is_string($content)) ? $markdown->transform($content) : null;
    }

    /**
     * Prepends a url to ``$path``, relative to the main index.html.twig being fetched.
     *
     * @param string|array $path An asset string eg. image.jpg
     * @param object       $twig ``_self`` if the $path is relative to the current template.  This is useful for plugin macros and child themes.
     *
     * @return string|array Whatever the $path was.  If the $path's string is not a relative asset, then it is just returned as is.  If the $path is an array, then every key and value in it will be turned into a url if it is a relative asset, and the rest of the array will remain the same.
     */
    public function asset($path, \Twig_Template $twig = null)
    {
        if (is_string($path)) {
            if ($this->asset && preg_match('/^'.implode('', array(
                '(?!((f|ht)tps?:)?\/\/)',
                '['.$this->asset['chars'].'.\/]+',
                '\.('.Asset::PREG_TYPES.')',
                '.*',
            )).'$/i', ltrim($path), $matches)) {
                $page = Page::html();
                $dir = ($twig) ? str_replace('\\', '/', dirname($this->getTwig()->getLoader()->getCacheKey($twig->getTemplateName()))) : $this->asset['dir'];
                $asset = $page->url('page', substr($dir, strlen($page->dir['page'])), ltrim($matches[0], './'));
            }
        } elseif ($this->asset && is_array($path)) {
            $asset = array();
            foreach ($path as $key => $value) {
                $asset[$this->asset($key, $twig)] = $this->asset($value, $twig);
            }
        }

        return (isset($asset)) ? $asset : $path;
    }

    /**
     * A reference to the current template, sort of.  This enables your plugin macros to behave more like a class, and these are your properties.
     * 
     * @param object $twig  ``_self`` so we know who you are.
     * @param mixed  $key   What you want to either set or retrieve.  Pass ``null`` to remove them all.  Make it an array to set multiple values at once.
     * @param mixed  $value Of the ``$key`` if you are setting it.  Pass ``null`` to remove only this one.
     * 
     * @return mixed If you don't specify ``$key`` or ``$value``, then all of the "properties" (an array) will be returned.  If you don't include a ``$value``, then the ``$key`` will be returned if it exists.  Otherwise you get ``null``.
     *
     * ```twig
     * {{ this(_self, 'key', 'value') }}
     * ```
     */
    public function this(\Twig_Template $twig, $key = null, $value = null)
    {
        $name = $twig->getTemplateName();
        if (!isset($this->plugin[$name])) {
            $this->plugin[$name] = array();
        }
        if (func_num_args() == 1) {
            return $this->plugin[$name]; // return all values
        } elseif (is_null($key)) {
            $this->plugin[$name] = array(); // remove all values
        } elseif (is_array($key)) {
            $this->plugin[$name] = $key + $this->plugin[$name]; // set multiple values
        } elseif (func_num_args() == 3) {
            if (is_null($value)) {
                unset($this->plugin[$name][$key]); // remove a single value
            } else {
                $this->plugin[$name][$key] = $value; // set a single value
            }
        } elseif (isset($this->plugin[$name][$key])) {
            return $this->plugin[$name][$key]; // return a single value
        }
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
            $output .= ($depth >= 0) ? str_repeat('    ', $depth).$line."\n" : '';
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

        return $this->renderTwig(array_pop($index), $vars);
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

    /**
     * Remove 'e' eval modifier from pattern.
     * 
     * @param mixed $pattern Regex backslashes must be double-escaped to work properly ie. '//'
     * 
     * @return mixed
     *
     * @link http://php.net/manual/en/reference.pcre.pattern.modifiers.php#reference.pcre.pattern.modifiers.eval
     * @link http://twig.sensiolabs.org/doc/templates.html#comparisons
     */
    private function removeEval(&$pattern)
    {
        if (is_array($pattern)) {
            foreach ($pattern as $key => $value) {
                $pattern[$key] = $this->removeEval($value);
            }
        } elseif (is_string($pattern)) {
            $pattern = trim($pattern);
            $mods = strrpos($pattern, substr($pattern, 0, 1));
            $pattern = substr($pattern, 0, $mods).str_replace('e', '', substr($pattern, $mods));
        }

        return $pattern;
    }
}
