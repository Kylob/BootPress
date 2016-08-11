<?php

namespace BootPress\Page;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Spartz\TextFormatter\TextFormatter;
use ParsedownExtra; // erusev/parsedown-extra
use URLify; // jbroadway/urlify
use AltoRouter;

class Component
{
    private $request;
    private $dir = array();
    private $url = array();
    private $html = array();
    private $data = array(); // meta, ico, apple, css, style, other, js, jquery, script
    private $saved = array(); // managed in $this->save($name), and retrieved in $this->info($name) - for filters mainly
    private $filters = array(); // managed in $this->filter() (public), and retrieved in $this->process() (private)
    private $testing = false; // $this->send() exit's a Symfony Response if this is false
    private static $instance;
    private static $session;

    /**
     * This returns a singleton instance of the Page class so that you can access it anywhere.  Passing parameters will only make a difference when calling it for the first time, unless you $overthrow it.
     * 
     * @param array $url What you want your website's url to look like, and point to.
     * 
     * - '**dir**' - The base directory your website exists in.  We recommend that this be a root folder so that it is not publically accessible, but it can be if you're crazy.
     * - '**base**' - The root url.  If you specify this, then we will enforce it.  If it starts with https (secured), then your website will be inaccessible via http (insecure).  If you include a subdomain (eg. www) or not, it will be enforced.  This way you don't have duplicate content issues, and know exactly how your website will be accessed.
     * - '**suffix**' - What you want to come after all of your url (html) paths.  The options are:  '', '**\/**', '**.htm**', '**.html**', '**.shtml**', '**.phtml**', '**.php**', '**.asp**', '**.jsp**', '**.cgi**', '**.cfm**', and '**.pl**'.
     * - '**chars**' - This lets you specify which characters are permitted within your URLs.  You should restrict this to as few characters as possible.  The default is '**a-z0-9~%.:_-**'.
     * - '**testing**' - If you include and set this to anything, then any calls to ``$page->send()`` will not ``exit``.  This enables us to unit test responses and not halt the script.
     * @param object      $request   A Symfony Request object.
     * @param false|mixed $overthrow If anything but false, then the parameters you pass will overthrow the previous ones submitted.  This is especially useful when unit testing.
     * 
     * @return object A singleton Page instance.
     */
    public static function html(array $url = array(), Request $request = null, $overthrow = false)
    {
        if ($overthrow || null === static::$instance) {
            static::$instance = static::isolated($url, $request);
        }

        return static::$instance;
    }

    /**
     * This returns an isolated instance of the Page class so you can use it for whatever.
     * 
     * @param array  $url     The same as above.
     * @param object $request A Symfony Request object.
     * 
     * @return object An isolated instance of the Page class.
     */
    public static function isolated(array $url = array(), Request $request = null)
    {
        extract(array_merge(array(
            'dir' => null,
            'base' => null,
            'suffix' => null,
            'chars' => 'a-z0-9~%.:_-',
        ), $url), EXTR_SKIP);
        $enforce = (is_string($base)) ? true : false;
        $page = new static();
        if (isset($testing)) {
            $page->testing = $testing;
        }
        $page->request = (is_null($request)) ? Request::createFromGlobals() : $request;
        if (false === $folder = realpath($dir)) {
            $folders = array();
            $base = realpath('');
            $dir = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $dir);
            if (strstr($base, DIRECTORY_SEPARATOR, true) !== strstr($dir, DIRECTORY_SEPARATOR, true)) {
                $dir = $base.DIRECTORY_SEPARATOR.$dir;
            }
            foreach (array_filter(explode(DIRECTORY_SEPARATOR, $dir), 'strlen') as $folder) {
                if ($folder == '..') {
                    array_pop($folders);
                } elseif ($folder != '.') {
                    $folders[] = $folder;
                }
            }
            $folder = implode(DIRECTORY_SEPARATOR, $folders);
        }
        $page->dir('set', 'base', $folder);
        $page->dir('set', 'page', $folder);
        $page->url['full'] = '';
        $page->url['base'] = (!empty($base)) ? trim($base, '/').'/' : $page->request->getUriForPath('/');
        if (parse_url($page->url['base'], PHP_URL_SCHEME) === null) {
            $page->url['base'] = 'http://'.$page->url['base'];
        }
        $page->url['path'] = trim($page->request->getPathInfo(), '/'); // excludes leading and trailing slashes
        if ($page->url['suffix'] = pathinfo($page->url['path'], PATHINFO_EXTENSION)) {
            $page->url['suffix'] = '.'.$page->url['suffix']; // includes leading dot
            $page->url['path'] = substr($page->url['path'], 0, -strlen($page->url['suffix'])); // remove suffix from path
        }
        $page->url['query'] = (null !== $qs = $page->request->getQueryString()) ? '?'.$qs : '';
        $page->url['preg'] = preg_quote($page->url['base'], '/');
        $page->url['chars'] = 'a-z0-9'.preg_quote(str_replace(array('a-z', '0-9', '.', '/', '\\', '?', '#'), '', $chars), '/');
        $page->url['html'] = array('', '/', '.htm', '.html', '.shtml', '.phtml', '.php', '.asp', '.jsp', '.cgi', '.cfm', '.pl');
        if (empty($page->url['suffix']) || in_array($page->url['suffix'], $page->url['html'])) {
            $page->url['format'] = 'html';
        } else {
            $page->url['format'] = substr($page->url['suffix'], 1);
            $page->url['path'] .= $page->url['suffix']; // put it back on since it is relevant now
        }
        $page->url['method'] = $page->request->getMethod(); // eg. GET|POST
        $page->url['route'] = '/'.$page->url['path']; // includes leading slash and unfiltered path (below)
        $page->url('set', 'base', $page->url['base']);
        $page->url('set', 'dir', $page->url['base'].'page/');
        $page->url['path'] = preg_replace('/[^'.$page->url['chars'].'.\/]/i', '', $page->url['path']);
        $page->url['suffix'] = (!empty($suffix) && in_array($suffix, $page->url['html'])) ? $suffix : '';
        $page->url['full'] = $page->formatLocalPath($page->url['base'].$page->url['path'].$page->url['query']);
        if ($enforce && strcmp($page->url['full'], $page->request->getUri()) !== 0) {
            $page->eject($page->url['full'], 301);
        }
        $page->set(array(), 'reset');

        return $page;
    }

    /**
     * Allows you to set HTML Page properties.
     * 
     * @param string|array $name  The ``$page->$name`` you would like to set.  You can do this one at a time, or make this an array and set everything at once.
     * @param mixed        $value The value if the $name (above) is a string.
     * 
     * ```php
     * $page->set(array(
     *     'title' => 'Sample Page',
     *     'description' => 'Snippet of information',
     *     'keywords' => 'Comma, Spearated, Tags',
     *     'thumb' => $page->url('base', 'image.jpg'),
     *     'author' => 'Full Name',
     *     'published' => 'Feb 7, 2015',
     * ));
     * ```
     */
    public function set($name, $value = '')
    {
        $html = (is_array($name)) ? $name : array($name => $value);
        if (is_array($name) && $value == 'reset') {
            $this->html = array(
                'doctype' => '<!doctype html>',
                'language' => 'en',
                'charset' => 'utf-8',
                'title' => '',
                'description' => '',
                'keywords' => '',
                'robots' => true,
                'body' => '',
            );
        }
        foreach ($html as $name => $value) {
            $this->html[strtolower($name)] = $value;
        }
    }

    /**
     * This is so that we can use multi-dimensional arrays with HTML Page properties.
     * 
     * @param string $name
     * 
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->html[$name]);
    }

    /**
     * Enables you to set HTML Page properties directly.
     * 
     * @param string $name  The ``$page->$name`` you would like to set.
     * @param mixed  $value Of the ``$page->$name``.
     */
    public function __set($name, $value)
    {
        $name = strtolower($name);
        if (is_null($value)) {
            unset($this->html[$name]);
        } else {
            $this->html[$name] = $value;
        }
    }

    /**
     * A magic getter for our private properties:.
     *
     * - '**session**' - The Symfony Session object.
     * - '**request**' - The Symfony Request object.
     * - '**plugin**' - A PHP callable that you have set up.
     * - '**dir**' - An array of dirs with the following keys:
     *   - '**base**' - The common dir among all that follow - don't ever rely on this to be anything in particular.
     *   - '**page**' - The submitted ``$url['dir']`` when this class was instantiated.
     *   - '**$name**' - The directory of the ``$name = $page->dirname(__CLASS__)``.
     *   - '**...**' - Whatever other classes you have ``$page->dirname()``ed.
     * - '**url**' - Information about your urls that may come in handy:
     *   - '**full**' - The complete url base, path, and query as presently constituted.
     *   - '**base**' - The base url.
     *   - '**path**' - The url path that comes after the base, and before the query.  If this is an html page then it does not include the url suffix - whatever you have set it to.
     *   - '**suffix**' - Either the currently constituted url suffix, or the desired ``$url['suffix']`` that was set when this class was instantiated.  Includes the leading dot.  If this is not an html page (eg. .pdf, .jpg, etc.), then this will be empty.
     *   - '**query**' - A string beginning with '**?**' if there are any url params, or blank if not.
     *   - '**preg**' - The url base ``preg_quote()``ed, and ready to go.
     *   - '**chars**' - The submitted ``$url['chars']`` when this class was instantiated, ``preg_quote()``ed, but with dot, slashes, question mark and hash tag removed so that we can include them as desired.
     *   - '**html**' - The array of acceptable ``$url['suffix']``'s that correspont to html pages.
     *   - '**format**' - The type of page you are currently working with.  Either '**html**' if the ``$page->url['suffix']`` is empty, or the ``$page->url['suffix']`` without the leading dot eg. pdf, jpg, etc.
     *   - '**method**' - How the page is being called eg. GET or POST
     *   - '**route**' - The ``$page->url['path']`` with a leading slash ie. ``'/'.$page->url['path']``
     *   - '**set**' - An ``array($name => $path, ...)`` of the ``$page->url('set', $name, $path)``'s you (and we) have set.
     * - '**html**' - The private propery from which every other $name will be retrieved.  You can access and modify these at any time.  The default ones are:
     *   - '**doctype**' => '<!doctype html>' - Goes at the top of your HTML page.
     *   - '**language**' => 'en' - Gets inserted just beneath the doctype in the html tag.  If your page no speaka any english, then you can change it to ``$page->language = 'sp';``, or any other [two-letter language abbreviation](http://www.loc.gov/standards/iso639-2/langcodes.html).
     *   - '**charset**' => 'utf-8' - This is the first meta tag that we insert just before the title ie. ``<meta charset="utf-8">``
     *   - '**title**' => '' - Defines the title of the page, and is inserted into the ``<head>`` section within ``<title>`` tags.
     *   - '**description**' => '' - Gets inserted into the meta description tag (if it is not empty) where you can give search engines and potential visitors a brief description of the content of your page ie. ``<meta name="description" content="...">``
     *   - '**keywords**' => '' - A comma-separated list of keywords that you think are relevant to the page at hand.  If it is not empty we put it in a meta keywords tag ie. ``<meta name="keywords" content="...">``
     *   - '**robots**' => true - If left alone this property does nothing, but if you set ``$page->robots = false;`` then we'll put ``<meta name="robots" content="noindex, nofollow">`` which tells the search engines (robots): "Don't add this page to your index" (noindex), and "Don't follow any links that may be here" (nofollow) either.  If you want one or the other, then just leave this property alone and you can spell it all out for them in ``$page->meta('name="robots" content="noindex"');``
     *   - '**body**' => '' - This used to be useful for Google Maps, and other ugly hacks before the advent of jQuery.  There are better ways to go about this, but it makes for a handy onload handler or to insert css styles for the body.  Whatever you set here will go inside the ``<body>`` tag.
     *
     * @param string $name The ``$page->$name`` whose value you are looking for.
     *
     * @return mixed
     */
    public function &__get($name)
    {
        // This method must return a reference and not use ternary operators for __set()ing multi-dimensional arrays
        // http://stackoverflow.com/questions/4310473/using-set-with-arrays-solved-but-why
        // http://stackoverflow.com/questions/5966918/return-null-by-reference-via-get
        switch ($name) {
            case 'session':
                if (is_null(static::$session)) {
                    static::$session = ($this->request->hasSession()) ? $this->request->getSession() : new Session();
                    if (!static::$session->isStarted()) {
                        static::$session->start();
                    }
                    if (!$this->request->hasSession()) {
                        $this->request->setSession(static::$session);
                    }
                }

                return static::$session;
                break;
            case 'plugin':
            case 'request':
            case 'dir':
            case 'url':
            case 'html':
                return $this->$name;
            break;
            default:
                return $this->html[strtolower($name)];
            break;
        }
    }

    /**
     * If one of your visitors gets lost, or you need to redirect them (eg. after a form has been submitted), the this method will eject theme for you.
     * 
     * @param string $url                Either the full url, or just the path.
     * @param int    $http_response_code The status code (302 by default).
     * 
     * ```php
     * $page->eject('users');
     * ```
     */
    public function eject($url = '', $http_response_code = 302)
    {
        $url = (!empty($url)) ? $this->formatLocalPath($url) : $this->url['base'];

        return $this->send(RedirectResponse::create(htmlspecialchars_decode($url), $http_response_code));
    }

    /**
     * This will ensure that the $url path you want to enforce matches the current path.
     * 
     * @param string $url      Either the full url, or just the path.
     * @param int    $redirect The status code (301 by default).
     * 
     * ```php
     * echo $page->url['path']; // 'details/former-title-1'
     * $page->enforce('details/current-title-1');
     * echo $page->url['path']; // 'details/current-title-1'
     * ```
     */
    public function enforce($url, $redirect = 301)
    {
        list($url, $path, $suffix, $query) = $this->formatLocalPath($url, 'array');
        $compare = $this->url['path'];
        if (!empty($path)) {
            $compare .= $this->url['suffix']; // to redirect 'index' to ''
        }
        if (!in_array($suffix, $this->url['html'])) { // images, css files, etc.
            if ($path.$suffix != $this->url['path']) {
                return $this->eject($this->url['base'].$path.$suffix, $redirect);
            }
        } elseif ($path.$suffix != $compare) {
            if (strpos($url, $this->url['base']) === 0) {
                return $this->eject($this->url['base'].$path.$suffix.$this->url['query'], $redirect);
            }
        }
    }

    /**
     * This takes a class and determines the directory it resides in so that you can refer to it in ``$page->dir()`` and ``$page->url()``.
     * 
     * @param string $class The class you want to reference.
     * 
     * @return string A slightly modified string of the $class for you to reference.
     * 
     * ```php
     * $name = $page->dirname(__CLASS__);
     * echo $page->dir($name); // The directory this file resides in
     * ```
     */
    public function dirname($class)
    {
        $class = trim(str_replace('/', '\\', $class), '\\');
        $name = str_replace('\\', '-', strtolower($class));
        if (!isset($this->dir[$name]) && class_exists($class)) {
            $ref = new \ReflectionClass($class);
            $this->dir('set', $name, dirname($ref->getFileName()));
            unset($ref);
        }

        return (isset($this->dir[$name])) ? $name : null;
    }

    /**
     * @param string $folder The path after ``$this->dir['page']``.  Every arg you include in the method will be another folder path.  If you want the directory to be relative to ``$name = $page->dirname(__CLASS__)``, then set the first parameter to $name, and the subsequent arguments (folders) relative to it.  Any empty args will be ignored.
     * 
     * @return string The directory path, and ensures it has a trailing slash.
     * 
     * ```php
     * $page->dir(); // returns $page->dir['page'] - the one where your website resides
     * $page->dir('folder', 'path'); // $page->dir['page'].'folder/path/'
     * $page->dir('folder', '', 'path'); // $page->dir['page'].'folder/path/'
     * $page->dir('folder/path'); // $page->dir['page'].'folder/path/'
     * $page->dir('/folder//path///'); // $page->dir['page'].'folder/path/'
     * $page->dir($page->dir['page'].'folder/path'); // $page->dir['page'].'folder/path/'
     * $page->dir($page->dir['page'], '/folder/path/'); // $page->dir['page'].'folder/path/'
     * $page->dir('page', '/folder', '/path/'); // $page->dir['page'].'folder/path/'
     * $page->dir('base', 'folder/path'); // $page->dir['page'].'folder/path/' - 'base' is an alias for 'page'
     * 
     * $name = $page->dirname(__CLASS__); // $page->dir[$name] is now the directory where the __CLASS__ resides
     * $page->dir($name, 'folder'); // the 'folder' relative to __CLASS__ (with trailing slash)
     * ```
     */
    public function dir($folder = null)
    {
        $folders = func_get_args();
        if ($folder == 'set') {
            list($folder, $name, $dir) = $folders;
            $this->dir[$name] = rtrim(str_replace('\\', '/', $dir), '/').'/';
            if (strpos($this->dir[$name], $this->dir['base']) !== 0) {
                $this->dir['base'] = $this->commonDir(array($this->dir[$name], $this->dir['base']));
            }

            return $this->dir[$name]; // all nicely formatted
        }
        $dir = $this->dir['page'];
        if ($folder == 'base') {
            array_shift($folders);
        } elseif (isset($this->dir[$folder])) {
            $dir = $this->dir[array_shift($folders)];
        } elseif (strpos($folder, $this->dir['base']) === 0) {
            $dir = rtrim(array_shift($folders), '/').'/';
        }
        if (empty($folders)) {
            return $dir;
        }
        $folders = array_filter(array_map(function ($path) {
            return trim($path, '/');
        }, $folders));

        return $dir.implode('/', $folders).'/';
    }

    /**
     * @param string $name Of the folder(s) and file.  Can span multiple arguments.
     * 
     * @return string The file path.  Works exactly the same as ``$page->dir(...)``, but this method doesn't include the trailing slash because it should be pointing to a file.
     */
    public function file($name)
    {
        return rtrim(call_user_func_array(array($this, 'dir'), func_get_args()), '/');
    }

    /**
     * Creates a url path (with trailing slash) that you can add to and work with, as opposed to ``$page->url()`` that always returns the ``$page->url['suffix']`` with it.
     * 
     * @param string $url Every argument given becomes part of the path, the same as ``$page->dir()`` only with a url.  The first argument can include the base url, be a ``$page->dirname()``, a reference that you ``$page->url('set', ...)``ed, or just be relative to ``$page->url['base']``.
     * 
     * @return string A url path with trailing slash - no suffix!
     * 
     * ```php
     * $page->path('folder'); // $page->url['base'].'folder/'
     * $page->path('base', 'folder'); // $page->url['base'].'folder/'
     * $page->path('page', 'folder'); // $page->url['base'].'page/folder/'
     * $page->path($page->url['base'], 'folder'); // $page->url['base'].'folder/'
     * ```
     */
    public function path($url = null)
    {
        $paths = func_get_args();
        $base_url = $this->url['base'];
        if ($url == 'base') {
            array_shift($paths);
        } elseif (isset($this->url['set'][$url])) {
            $base_url = $this->url['set'][array_shift($paths)];
        } elseif (strpos($url, $this->url['base']) === 0) {
            $base_url = rtrim(array_shift($paths), '/').'/';
        }
        if (empty($paths)) {
            return $base_url;
        }
        $paths = array_filter(array_map(function ($path) {
            return trim($path, '/');
        }, $paths));

        return $base_url.implode('/', $paths).'/';
    }

    /**
     * Allows you to either create a url, or manipulate it's query string and fragment.
     * 
     * @param string $action What you want this method to do.  The options are:
     * 
     * - '' (blank) - 
     * - '**params**' - To get an associative array of the ``$url`` query string.
     * - '**delete**' - To remove a param (or more) from the ``$url`` query string.
     * - '**add**' - To add a param (or more) to the ``$url`` query string.
     * - '**set**' - To ``$page->url['set'][$url] = $value`` that can be referred to here, and in ``$page->path()``.
     * - '**...**' - Anything else you do will create a url string in the same manner as ``$page->path()`` only with the ``$page->url['suffix']`` included.
     * @param string       $url   If empty then the ``$page->url['base']`` will be used.
     * @param string|array $key   What you would like to add to or take from the ``$url``.  This can be a query string parameter, a '**#**', a '**?**', or an array depending on the type of ``$action`` you are looking for.
     * @param string       $value If ``$action`` equals '**add**', and ``$key`` is not an array, then this is the ``$key``'s value.   Otherwise this argument means nothing.
     * 
     * @return string|array The url string if you are creating one, or else:
     * 
     * - If ``$action`` equals '**params**' then the ``$url`` query string is returned as an associative array.  Otherwise this method always returns a ``$url`` that has been ampified and is ready to be inserted into your html.
     * - If ``$action`` equals '**add** or '**delete**':
     *   - The ``$key``'s will be added to or deleted from the ``$url``'s query string or fragment if ``$key`` equals '#'.
     *   - If ``$key`` is an array, then foreach key and value, the ``$url`` will be added to or deleted from accordingly.
     * - If ``$action`` equals '**delete**' and ``$key`` equals '**?**' then the ``$url`` will be returned without any query string at all.
     * - If no parameters are given then the ``$page->url['full']`` is returned.
     */
    public function url($action = '', $url = '', $key = '', $value = null)
    {
        if (empty($action)) {
            return htmlspecialchars($this->url['full']);
        } elseif ($action == 'set') {
            return $this->url['set'][$url] = $key;
        } elseif (!in_array($action, array('params', 'add', 'delete'))) {
            $base_url = (is_array($action) && isset($action['url'])) ? $action['url'] : implode('/', (array) $action);
            if (isset($this->url['set'][$base_url])) {
                $base_url = $this->url['set'][$base_url];
            } elseif (isset($this->dir[$base_url])) {
                $base_url = $this->url['base'].$base_url.'/';
            }
            // get an array of all url $segments after the $base_url
            $segments = array_filter(array_slice(func_get_args(), 1));
            if (($num = count($segments)) > 0) {
                // trim each $segments slashes
                $segments = array_map('trim', $segments, array_fill(0, $num, '/'));
            }
            array_unshift($segments, rtrim($base_url, '/\\'));

            return htmlspecialchars($this->formatLocalPath(htmlspecialchars_decode(implode('/', $segments))));
        }
        $url = (!empty($url)) ? htmlspecialchars_decode($url) : $this->url['full'];
        $base = preg_replace('/[\?#].*$/', '', $url); // just the url and path
        $url = parse_url($url);
        if (!isset($url['query'])) {
            $params = array();
        } else {
            parse_str($url['query'], $params);
        }
        $fragment = (!empty($url['fragment'])) ? '#'.$url['fragment'] : '';
        switch ($action) {
            case 'params':
                return $params;
                break;
            case 'add':
                if ($key == '#') {
                    $fragment = '#'.urlencode($value);
                } else {
                    $params = array_merge($params, (is_array($key) ? $key : array($key => $value)));
                }
                break;
            case 'delete':
                if ($key == '?') {
                    $params = array();
                } elseif ($key == '#') {
                    $fragment = '';
                } else {
                    foreach ((array) $key as $value) {
                        unset($params[$value]);
                    }
                }
                break;
        }
        $query = (!empty($params)) ? '?'.http_build_query($params) : '';

        return htmlspecialchars($this->formatLocalPath($base.$query.$fragment));
    }

    /**
     * A shortcut for ``$page->request->query->get($key, $default)``.
     * 
     * @param string $key     The $_GET[$key].
     * @param mixed  $default The default value to return if the $_GET[$key] doesn't exits.
     * 
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->request->query->get($key, $default);
    }

    /**
     * A shortcut for ``$page->request->request->get($key, $default)``.
     * 
     * @param string $key     The $_POST[$key].
     * @param mixed  $default The default value to return if the $_POST[$key] doesn't exits.
     * 
     * @return mixed
     */
    public function post($key, $default = null)
    {
        return $this->request->request->get($key, $default);
    }

    /**
     * Takes the current (or custom) ``$page->url['method']``, and maps it to the paths you provide using the (AltoRouter)[http://altorouter.com/].
     * 
     * @param array $map An ``array($route => $target, ...)`` or just ``array($route, ...)``, or any combination thereof.  The $target could be a php file, a method name, or whatever you want that will help you to determine what comes next.  A $route is what you are expecting your uri to look like, and mapping them to variables that you can actually work with.
     * 
     * - **folder** will match 'folder'
     * - **users/[register|sign_in|forgot_password:action]** will match 'users/sign_in' with ``$params['action'] = 'sign_in'``
     * - **users/[i:id]** will match 'users/12' with ``$params['id'] = 12``
     * 
     * Notice that the '**i**' in '**[i:id]**' will match an integer and assign the paramter '**id**' to the value of '**i**'.  You can set or override these shortcuts in **$types** below.  The defaults are:
     * 
     * - __*__ - Match all request URIs
     * - __[i]__ - Match an integer
     * - __[i:id]__ - Match an integer as 'id'
     * - __[a:action]__ - Match alphanumeric characters as 'action'
     * - __[h:key]__ - Match hexadecimal characters as 'key'
     * - __[:action]__ - Match anything up to the next '__/__', or end of the URI as 'action'
     * - __[create|edit:action]__ - Match either 'create' or 'edit' as 'action'
     * - __[*]__ - Catch all (lazy)
     * - __[*:trailing]__ - Catch all as 'trailing' (lazy)
     * - __[**:trailing]__ - Catch all (possessive - will match the rest of the URI)
     * - __.[:format]?__ - Match an optional parameter as 'format'
     *   - When you put a '__?__' after the block (making it optional), a '__/__' or '__.__' before the block is also optional
     * 
     * A few more examples for the road:
     * 
     * - __posts/[*:title]-[i:id]__ - Matches 'posts/this-is-a-title-123'
     * - __posts/[create|edit:action]?/[i:id]?__ - Matches 'posts', 'posts/123', 'posts/create', and 'posts/edit/123'
     * - __output.[xml|json:format]?__ - Matches 'output', 'output.xml', 'output.json'
     * - __@\.(json|csv)$__ - Matches all requests that end with '.json' or '.csv'
     * - __!@^admin/__ - Matches all requests that _don't_ start with admin/
     * - __api/[*:key]/[*:name]__ - Matches 'api/123/456/gadd' where name = '456/gadd'
     * - __[:controller]?/[:action]?__ - Matches the typical controller/action format
     * - __[:controller]?/[:method]?/[**:uri]?__ - There's nothing that this won't cover
     * @param mixed $route If your don't want to use ``$page->url['method']``, then set this value to the path you want to match against.
     * @param array $types If you want to add to (or override) the shortcut regex's, then you can add them here.  The defaults are:
     * 
     * ```php
     * $types = array(
     *     'i'  => '[0-9]++', // integer
     *     'a'  => '[0-9A-Za-z]++', // alphanumeric
     *     'h'  => '[0-9A-Fa-f]++', // hexadecimal
     *     '*'  => '.+?', // anything (lazy)
     *     '**' => '.++', // anything (possessive)
     *     ''   => '[^/\.]++' // not a slash (/) or period (.)
     * );
     * ```
     * 
     * @return mixed False if nothing matches in which case you should ``show_404()``, or an array of information with the following keys:
     * 
     * - '**target**' - The route we successfully matched.  If the route is a key, then this is it's value.  Otherwise it is the route itself.
     * - '**params**' - All of the params we matched to the successful route.
     * - '**method**' - Either '**POST**' or '**GET**'.
     * 
     * ```php
     * $routes = array(
     *   '' => 'index.php',
     *   'listings' => 'listings.php',
     *   'details/[*:title]-[i:id]' => 'details.php',
     * );
     * if (is_admin()) $routes['admin/[:action]'] = 'admin.php';
     * 
     * if ($route = $page->routes($routes)) {
     *     include $route['target'];
     * } else {
     *     $page->send(404);
     * }
     * ```
     */
    public function routes(array $map, $route = null, array $types = array())
    {
        $path = (is_null($route)) ? $this->url['route'] : $route;
        $routes = array();
        foreach ($map as $route => $target) {
            if (is_numeric($route)) {
                $route = $target;
            }
            $routes[] = array($this->url['method'], ltrim($route, '/'), $target);
        }
        $router = new AltoRouter($routes, '', $types);
        if ($match = $router->match(ltrim($path, '/'), $this->url['method'])) {
            unset($match['name']);
        }

        return $match;
    }

    /**
     * Generates an html tag programatically.
     * 
     * @param string $name       The tag's name eg. 'div'
     * @param array  $attributes An ``array($key => $value, ...)`` of attributes.  If $value is an array (a good idea for classes) then we remove any duplicate or empty values, and implode them with a space in beween.  If the $value is an empty string we ignore the attribute entirely.  If the $key is numeric (ie. not set) then the attribute is it's $value (eg. '**multiple**' or '**selected**'), and we'll delete any $key of the same name (eg. multiple="multiple" or selected="selected").  If you want an empty attribute to be included, then set the $value to null.
     * @param string $content    All args supplied after the $attributes are stripped of any empty values, and ``implode(' ', ...)``ed.
     * 
     * @return string An opening html tag with attributes.  If $content is supplied then we add that, and a closing html tag.
     *
     * ```php
     * echo $page->tag('meta', array('name'=>'description', 'content'=>'')); // <meta name="description">
     * 
     * echo $page->tag('p', array('class'=>'lead'), 'Content', 'Coming'); // <p class="lead">Content Coming</p>
     * ```
     */
    public function tag($name, array $attributes, $content = null)
    {
        $args = func_get_args();
        $tag = array_shift($args);
        $attributes = array_shift($args);
        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $value = implode(' ', array_unique(array_filter($value)));
            }
            if ($value === '') {
                unset($attributes[$key]);
            } elseif (!is_numeric($key)) {
                $attributes[$key] = $key.'="'.$value.'"';
            } elseif (isset($attributes[$value])) {
                unset($attributes[$key]);
            }
        }
        $attributes = (!empty($attributes)) ? ' '.implode(' ', $attributes) : '';
        $html = '<'.$tag.$attributes.'>';
        if (!empty($args)) {
            $html .= implode(' ', array_filter($args));
            $html .= '</'.strstr($tag.' ', ' ', true).'>';
        }

        return $html;
    }

    /**
     * Some handy formatters that always come in handy.
     * 
     * @param string      $type    The type of string you are formatting:  Either '**url**', '**markdown**', or '**title**'.
     * @param string      $string  What you would like to format
     * @param false|mixed $slashes If anything but false, it will allow your url to have slashes.
     * 
     * @return string Depending on $type
     */
    public function format($type, $string, $slashes = false)
    {
        switch ($type) {
            case 'url':
                $url = ($slashes !== false) ? explode('/', $string) : array($string);
                foreach ($url as $key => $value) {
                    $url[$key] = URLify::filter($value);
                }
                $string = implode('/', $url);
                break;
            case 'markdown':
                $parser = new ParsedownExtra();
                $string = $parser->text($string);
                break;
            case 'title':
                $string = explode(' ', $string);
                foreach ($string as $key => $value) {
                    if (!empty($value) && mb_strtoupper($value) == $value) {
                        $string[$key] = mb_strtolower($value);
                    }
                }
                $string = TextFormatter::titleCase(implode(' ', $string));
                break;
        }

        return $string;
    }

    /**
     * Allows you to insert any meta tags (at any time) into the head section of your page.  We already take care of the description, keywords, and robots tags.  If there are any more you would like to add, then you may do so here.  You can only enter one meta tag at a time with this method.
     * 
     * @param mixed $args If ``$args`` is a string, then we just include the meta tag as is.  If it is an array then we use the key and value pairs to build the meta tag's attributes.
     * 
     * ```php
     * $page->meta('name="author" content="name"'); // or ...
     * 
     * $page->meta(array('name'=>'author', 'content'=>'name'));
     * ```
     */
    public function meta($args)
    {
        if (is_string($args)) {
            $this->data('meta', $args, false);
        } else {
            foreach ($args as $key => $value) {
                $args[$key] = $key.'="'.$value.'"';
            }
            $this->data('meta', implode(' ', $args), false);
        }
    }

    /**
     * @param mixed $link    This can be a string, or an array of javascript, css, and / or icon resources that will be added to the head section of your page.
     * @param mixed $prepend If this value is anything other than false (I like to use 'prepend'), then all of the ``$link``'s that you just included will be prepended to the stack, as opposed to being inserted after all of the other links you have included.
     * 
     * ```php
     * $page->link(array(
     *     $page->url('images/favicon.ico'),
     *     $page->url('css/stylesheet.css'),
     *     $page->url('js/functions.js'),
     * ));
     * ```
     */
    public function link($link, $prepend = false)
    {
        $link = (array) $link;
        if ($prepend !== false) {
            $link = array_reverse($link); // so they are added in the correct order
        }
        foreach ($link as $file) {
            $frag = (strpos($file, '<') === false) ? strstr($file, '#') : '';
            if (!empty($frag)) {
                $file = substr($file, 0, -strlen($frag));
            }
            if (preg_match('/\.(js|css|ico|apple)$/i', $file)) {
                $split = strrpos($file, '.');
                $ext = substr($file, $split + 1);
                $name = substr($file, 0, $split);
                switch ($ext) {
                    case 'js':
                        $this->data('js', $file.$frag, $prepend);
                        break;
                    case 'css':
                        $this->data('css', $file.$frag, $prepend);
                        break;
                    case 'ico':
                        $this->data['ico'] = $file.$frag;
                        break;
                    case 'apple':
                        $this->data['apple'] = $name.'.png';
                        break;
                }
            } elseif (substr($file, 1, 5) == 'style') {
                $this->data('style', $file, $prepend);
            } elseif (substr($file, 1, 6) == 'script') {
                $this->data('script', $file, $prepend);
            } else {
                $this->data('other', $file, $prepend);
            }
        }
    }

    /**
     * This will enclose the $css within ``<script>`` tags and place it in the ``<head>`` of your page.
     * 
     * @param string $code Your custom css code.
     * 
     * ```php
     * $page->script('body { background-color:red; }');
     * ```
     */
    public function style($code)
    {
        if (is_array($code)) {
            foreach ($code as $css => $rules) {
                if (is_array($rules)) {
                    $code[$css] = $css.' { '.implode(' ', $rules).' }';
                } elseif (!is_numeric($css)) {
                    $code[$css] = $css.' { '.$rules.' }';
                }
            }
            $code = implode("\n", $code);
        }
        $this->link('<style>'.(strpos($code, "\n") ? "\n".$this->indent($code)."\n\t" : trim($code)).'</style>');
    }

    /**
     * This will enclose the $javascript within ``<style>`` tags and place it at the bottom of your page.
     * 
     * @param string $code Your custom javascript code.
     * 
     * ```php
     * $page->script('alert("Hello World");');
     * ```
     */
    public function script($code)
    {
        if (is_array($code)) {
            $code = implode("\n", $code);
        }
        $this->link('<script>'.(strpos($code, "\n") ? "\n".$this->indent($code)."\n\t" : trim($code)).'</script>');
    }

    /**
     * jQuery itself is included automatically if you ever call this method, and is placed before any other included scripts on the page.  The default version is currently v.1.11.4, but you can change that by setting ``$page->jquery`` to the file you want to use.  To include the jQuery UI right after that then call ``$page->jquery('ui', $file)``, and if you leave out the $file then we will use v.1.12.3.
     * 
     * @param string|array $code    A string of jQuery.  All of the included code is compiled at the end of the page and placed into one ``$(document).ready(function(){...})``.  If this is a file or files (array), then they will be included via ``$page->link()``.
     * @param mixed        $prepend Passed to ``$page->link()`` if including files.
     * 
     * ```php
     * $page->jquery('$("button.continue").html("Next Step...");');
     * ```
     */
    public function jquery($code, $prepend = false)
    {
        if ($code == 'ui') {
            if (!isset($this->data['jquery']['ui'])) {
                $this->data['jquery']['ui'] = false;
            }
            if (!empty($prepend)) {
                $this->data['jquery']['ui'] = $prepend;
            }

            return;
        }
        foreach ((array) $code as $value) {
            if (!is_string($value) || strpos($value, 'http') !== 0) {
                $this->data['jquery'][] = $code;

                return;
            }
        }
        $this->link($code, $prepend);
        $this->data['jquery'][] = '';
    }

    /**
     * We use this in the Form component to avoid input name collisions.  We use it in the Bootstrap component for accordions, carousels, and the like.  The problem with just incrementing a number and adding it onto something else is that css and jQuery don't like numbered id's, and so we use roman numerals instead and that solves the problem for us.
     * 
     * @param string $prefix What you would like to come before the roman numeral.  This is not really needed, but when you are looking at your source code, it helps to know what you are looking at.
     * 
     * @return string A unique id.
     * 
     * ```php
     * // Assuming this method has not been called before:
     * echo $page->id('name'); // nameI
     * echo $page->id('unique'); // uniqueII
     * echo $page->id('unique'); // uniqueIII
     * ```
     */
    public function id($prefix = '')
    {
        static $id = 0;
        ++$id;
        $result = '';
        $lookup = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
        $number = $id;
        if ($number < 100) {
            $lookup = array_slice($lookup, 4);
        }
        foreach ($lookup as $roman => $value) {
            $matches = intval($number / $value);
            $result .= str_repeat($roman, $matches);
            $number = $number % $value;
        }

        return $prefix.$result;
    }

    /**
     * This allows you to map a $path to a folder and $file in $dir so that you can ``$page->load()`` it.  This will essentially make your $file a controller (following the MVC pattern if that means anything to you).
     * 
     * @param string $dir  The base directory whose folders you want to map to a $path.
     * @param string $path The ``$page->url['path']`` or whatever else you want to use.
     * @param string $file The filename that must be in the folder to make a match.
     * 
     * @return array|null If we have a match then we will return an array with the following info:
     * 
     * - '**file**' - The file path for which we made a match.
     * - '**dir**' - The dir in which the file resides (with trailing slash).
     * - '**assets**' - The url path (with trailing slash) that corresponds to the dir for including images and other files.
     * - '**url**' - The url path for linking to other pages that are relative to this dir.
     * - '**folder**' - The portion of your $path that got us to your $file.
     * - '**route**' - The remaining portion of your $path that your $file will have to figure out what to do with next.
     * 
     * ```php
     * // Assuming ``$dir = $page->dir('folders')``, and you have a $dir.'users/index.php' file:
     * if ($params = $page->folder($dir, 'users/sign_in')) {
     *     $html = $page->load($params['file'], $params);
     *     // $params = array(
     *     //     'file' => $dir.'users/index.php',
     *     //     'dir' => $dir.'users/',
     *     //     'assets' => $page->url['base'].'page/folders/users/',
     *     //     'url' => $page->url['base'].'folders/users/',
     *     //     'folder' => 'users/',
     *     //     'route' => '/sign_in',
     *     // );
     * }
     * ```
     */
    public function folder($dir, $path, $file = 'index.php')
    {
        $dir = $this->dir($dir);
        if (strpos($dir, $this->dir['page']) === 0 && is_dir($dir)) {
            $folder = substr($dir, strlen($this->dir['page']));
            $paths = array();
            $path = preg_replace('/[^'.$this->url['chars'].'\.\/]/', '', strtolower($path));
            foreach (explode('/', $path) as $dir) {
                if (false !== $extension = strstr($dir, '.')) {
                    $dir = substr($dir, 0, -strlen($extension)); // remove file extension
                }
                if (!empty($dir)) {
                    $paths[] = $dir; // remove empty $paths
                }
            }
            $paths = array_diff($paths, array('index')); // remove any reference to 'index'
            $path = '/'.implode('/', $paths); // includes leading slash and corresponds with $paths
            if ($extension) {
                $path .= $extension;
            }
            while (!empty($paths)) {
                $route = implode('/', $paths).'/'; // includes trailing slash
                if (is_file($this->file($folder, $route, $file))) {
                    return array(
                        'file' => $this->file($folder, $route, $file),
                        'dir' => $this->dir($folder, $route),
                        'assets' => $this->url['base'].'page/'.$folder.$route,
                        'url' => $this->url['base'].$folder.$route,
                        'folder' => substr($path, 1, strlen($route)), // remove leading slash
                        'route' => substr($path, strlen($route)), // remove trailing slash
                    );
                }
                array_pop($paths);
            }
            if (is_file($this->file($folder, $file))) {
                return array(
                    'file' => $this->file($folder, $file),
                    'dir' => $this->dir($folder),
                    'assets' => $this->url['base'].'page/'.$folder,
                    'url' => $this->url['base'].$folder,
                    'folder' => '',
                    'route' => $path,
                );
            }
        }

        return;
    }

    /**
     * Passes $params to a $file, and returns the output.
     * 
     * @param string $file   The file you want to ``include``.
     * @param array  $params Variables you would like your file to receive.
     * 
     * @return mixed Whatever you ``$export``ed (could be anything), or a string of all that you ``echo``ed.
     * 
     * ```php
     * $file = $page->file('folders/users/index.php');
     * 
     * // Assuming $file has the following code:
     *
     * <?php
     * extract($params);
     * $export = $action.' Users';
     * 
     * // Loading it like this would return 'Sign In Users'
     * 
     * echo $page->load($file, array('action'=>'Sign In'));
     * ```
     */
    public function load($file, array $params = array())
    {
        if (!is_file($file)) {
            return;
        }
        foreach ($params as $key => $value) {
            if (is_numeric($key) && is_string($value)) {
                $params[$value] = true; // makes it possible to extract(), and easier to check if isset()
                unset($params[$key]);
            }
        }
        $export = '';
        ob_start();
        include $file;
        $html = ob_get_clean();

        return ($export !== '') ? $export : $html;
    }

    /**
     * This method exists mainly for plugins.  It allows them to save some information for future use.  The saved information can be retrieved later by calling ``$page->info()``.
     * 
     * @param string $name  There is a potential for collision here.  I recommend using the ``$page->dirname(__CLASS__)`` exclusively.
     * @param mixed  $key   If you don't indicate a ``$value``, then this will be added to the ``$name``'s array which can be retrieved at ``$page->info($name)``.
     * @param mixed  $value if ``$key`` is a specific value that you want to save then this will be it's value, and it will override any value previously set.  It can be retrieved at ``$page->info($name, $key)``.
     * 
     * ```php
     * $name = $page->dirname(__CLASS__);
     * $page->save($name, 'one');
     * $page->save($name, 'two');
     * $page->save($name, 'skip', 'few');
     * ```
     */
    public function save($name, $key, $value = null)
    {
        if (func_num_args() == 2 || is_array($key)) {
            $this->saved[$name][] = $key;
        } else {
            $this->saved[$name][$key] = $value;
        }
    }

    /**
     * Returns the info saved in ``$page->save($name)``.
     * 
     * @param string $name The one you indicated in ``$page->save($name)``.
     * @param string $key  The specific value you ``$page->save($name, $key)``ed previously and now want to retrieve.
     * 
     * @return mixed If you indicate a ``$key`` then we will return the value if it exists, or null if it does not.  Otherwise we will return the whole array of info saved for ``$name`` (if any), or an empty array if not.
     * 
     * ```php
     * $name = $page->dirname(__CLASS__);
     * 
     * $page->info($name); // returns array();
     * 
     * $page->save($name, 'one');
     * $page->save($name, 'two');
     * $page->save($name, 'skip', 'few');
     * 
     * $page->info($name); // returns array('one', 'two');
     * $page->info($name, 'skip'); // returns 'few';
     * $page->info($name, 'set'); // returns null;
     * ```
     */
    public function info($name, $key = null)
    {
        if (!is_null($key)) {
            return (isset($this->saved[$name][$key])) ? $this->saved[$name][$key] : null;
        }
        $info = (isset($this->saved[$name])) ? $this->saved[$name] : array();
        foreach ($info as $key => $value) {
            if (!is_numeric($key)) {
                unset($info[$key]);
            }
        }

        return $info;
    }

    /**
     * Enables you to prepend, append, or modify just about anything throughout the creation process of your page.
     * 
     * @param string $section Must be one of (in the order we process them at ``$page->display()``):
     * 
     * - '**content**' - This comes first before all.  Presumably, it is the reason you are creating a website.  We create / process the content first, then we put the rest of the page together, which all revolves around the content.  If you have anything else to add (or edit), you may do so now.
     * - '**metadata**' - This contains the title tag and metadata, just before we start inserting all of your css stylesheets.
     * - '**css**' - After we insert any icon image links, we hand over the array of stylesheets we want to include for further processing (if any).
     * - '**styles**' - After all the stylesheets are up, then we check for anything else you would like to add just before the ``</head><body>`` tags.
     * - '**javascript**' - After your content we include jquery first, then all of your javascript files.
     * - '**scripts**' - After listing all of your javascript src files, then we include any scripts, and place the jQuery code last.
     * - '**head**' - Everything between the ``<head>`` ... ``</head>`` tags.
     * - '**body**' - Everything between the ``<body>`` ... ``</body>`` tags.
     * - '**page**' - The entire page from top to bottom.
     * - '**response**' - The final Symfony Response object if you ``$page->send()`` it.
     * @param mixed $function Can be either '**prepend**', '**append**', or a callable function or method.  If filtering the '**response**' then we'll pass the ``$page`` (this class instance), ``$response`` (what you are filtering), and ``$type`` ('html', 'json', 'redirect', or ``$page->url['format']``) of content that you are dealing with.
     * @param mixed $params
     *                        - If ``$section`` equals '**response**'
     *                        - These are the page *type* and response *code* conditions that the response must meet in order to be processed.  It can be an array or string.
     *                        - Elseif ``$function`` equals '**prepend**' or '**append**' then this must be a string.
     *                        - Elseif ``$function`` is a callable function or method:
     *                        - ``$params`` must be an array of arguments which are passed to the function or method.
     *                        - '**this**' must be listed as one of the ``$params``.
     *                        - If '**this**' is the only ``$param``, then instead of an array you can just pass the string '**this**'.
     *                        - '**this**' is the ``$section`` as currently constituted, and for which your filter would like to operate on.  If you don't return anything, then that section will magically disappear.
     * @param int   $order    The level of importance (or priority) that this filter should receive.  The default is 10.  All filters are called in the order specified here.
     * 
     * ```php
     * $page->filter('response', function ($page, $response, $type) {
     *     return $response->setContent($type);
     * }, array('html', 200));
     * 
     * $page->filter('response', function ($page, $response) {
     *     return $response->setContent('json');
     * }, 'json');
     * 
     * $page->filter('response', function ($page, $response) {
     *     return $response->setContent(404);
     * }, 404);
     * 
     * function prepend_facebook_like_button ($content) {
     *     return 'facebook_like_button '.$content;
     * }
     * 
     * $page->filter('content', 'prepend_facebook_like_button', array('this')); // or ...
     * 
     * $page->filter('content', 'prepend_facebook_like_button', 'this'); // or ...
     * 
     * $page->filter('content', 'prepend', 'facebook_like_button ');
     * ```
     * 
     * @throws \LogicException If something was not set up right.
     */
    public function filter($section, $function, $params = 'this', $order = 10)
    {
        $errors = array();
        if ($section == 'response') {
            if (!is_callable($function, false, $name)) {
                $errors[] = "'{$name}' cannot be called";
            }
            if (!is_array($params)) {
                $params = explode(' ', $params);
            }
            foreach ($params as $key => $value) {
                if (empty($value) || $value == 'this') {
                    unset($params[$key]);
                }
            }
            $key = false;
        } elseif (!in_array($section, array('metadata', 'css', 'styles', 'head', 'content', 'javascript', 'scripts', 'body', 'page'))) {
            $errors[] = "'{$section}' cannot be filtered";
        } elseif (in_array($function, array('prepend', 'append'))) {
            if (!is_string($params)) {
                $errors[] = "When using '{$function}', \$params must be a string";
            } elseif (in_array($section, array('css', 'javascript'))) {
                $this->filters[$function][$section][] = $params; // [prepend|append][css|javascript][] = (string)
                return;
            }
            $key = ''; // not applicable here
        } else {
            $params = (array) $params;
            $key = array_search('this', $params);
            if ($key === false) {
                $errors[] = "'this' must be listed in the \$params so that we can give you something to filter";
            }
            if (!is_callable($function, false, $name)) {
                $errors[] = "'{$name}' cannot be called";
            }
        }
        if (!empty($errors)) {
            throw new \LogicException(implode("\n\n", $errors));
        }
        $this->filters[$section][] = array('function' => $function, 'params' => $params, 'order' => $order, 'key' => $key);
    }

    /**
     * This method fulfills the measure of the whole Page component's existence: to be able to manipulate every part of an HTML page at any time.
     * 
     * @param string $content Of your page.
     * 
     * @return string The complete HTML page from top to bottom.
     */
    public function display($content)
    {
        $content = $this->process('content', $content);
        $head = array(
            $this->process('metadata', "\t".implode("\n\t", $this->metadata())),
            $this->process('styles', "\t".implode("\n\t", $this->styles())),
        );
        $body = array(
            $content,
            $this->process('scripts', "\t".implode("\n\t", $this->scripts())),
        );
        $html = array(
            $this->html['doctype'],
            '<html lang="'.$this->html['language'].'">',
            '<head>',
            $this->process('head', implode("\n", $head)),
            '</head>',
            (!empty($this->html['body'])) ? '<body '.$this->html['body'].'>' : '<body>',
            $this->process('body', implode("\n", $body)),
            '</body>',
            '</html>',
        );

        return $this->process('page', implode("\n", $html));
    }

    /**
     * Sends a Symfony Response object, and allows you to further process and ``$page->filter()`` it.
     * 
     * @param object|string|int $response Either a Symfony Response object, the content of your response, or just a quick status code eg. ``$page->send(404)``
     * @param int               $status   The status code if your ``$response`` is a content string.
     * @param array             $headers  A headers array if your ``response`` is a content string.
     * 
     * @return object If you set ``Page::html(array('testing'=>true))`` then we will return the Symfony Response object so that it doesn't halt your script, otherwise it will send the Response and exit the page.
     * 
     * ```php
     * if ($html = $page->load($page->file('index.php'))) {
     *     $page->send($page->display($html));
     * } else {
     *     $page->send(404);
     * }
     * ```
     */
    public function send($response = '', $status = 200, array $headers = array())
    {
        if (!$response instanceof Response) {
            if (func_num_args() == 1 && is_numeric($response)) {
                $status = (int) $response;
                $response = '';
            }
            $response = new Response($response, $status, $headers);
        }
        $status = $response->getStatusCode();
        if ($response instanceof RedirectResponse) {
            $type = 'redirect';
        } elseif ($response instanceof JsonResponse) {
            $type = 'json';
        } elseif (null === $type = $response->headers->get('Content-Type')) {
            $type = ($status == 304) ? $this->url['format'] : 'html';
        } elseif (stripos($type, 'html') !== false) {
            $type = 'html';
        }
        $this->process('response', $response, $status, $type);
        $response = $response->prepare($this->request)->send();

        return ($this->testing === false) ? exit : $response;
    }

    /**
     * Creates and sends a Symfony JsonResponse object.
     * 
     * @param mixed $data   The response data.
     * @param int   $status The response status code.
     */
    public function sendJson($data = '', $status = 200)
    {
        return $this->send(JsonResponse::create($data, $status));
    }

    /**
     * An internal method we use that comes in handy elsewhere as well.
     * 
     * @param array $files An array of files that share a common directory somewhere.
     * 
     * @return The common directory (with trailing slash) shared amongst your $files.
     */
    public function commonDir(array $files)
    {
        $files = array_values($files);
        $cut = 0;
        $count = count($files);
        $shortest = min(array_map('mb_strlen', $files));
        while ($cut < $shortest) {
            $char = $files[0][$cut];
            for ($i = 1; $i < $count; ++$i) {
                if ($files[$i][$cut] !== $char) {
                    break 2;
                }
            }
            ++$cut;
        }
        $dir = substr($files[0], 0, $cut);
        if (false !== $slash = strrpos($dir, '/')) {
            $dir = substr($dir, 0, $slash + 1);
        } elseif (false !== $slash = strrpos($dir, '\\')) {
            $dir = substr($dir, 0, $slash + 1);
        }

        return $dir; // with trailing slash (if any)
    }

    protected function process($section, $param, $code = 0, $type = '')
    {
        // Used in $this->send(), $this->display(), $this->styles(), and $this->scripts()
        if (!isset($this->filters[$section])) {
            return $param;
        }
        usort($this->filters[$section], function ($a, $b) {
            return $a['order'] - $b['order'];
        });
        foreach ($this->filters[$section] as $key => $filter) {
            if ($section == 'response') {
                foreach ($filter['params'] as $response) {
                    if (is_numeric($response)) {
                        if ($response != $code) {
                            continue 2;
                        }
                    } elseif (stripos($type, $response) === false) {
                        continue 2;
                    }
                }
                call_user_func($filter['function'], $this, $param, $type); // $page, $response, $type
            } elseif ($filter['function'] == 'prepend') {
                $param = $filter['params'].$param;
            } elseif ($filter['function'] == 'append') {
                $param .= $filter['params'];
            } else {
                $filter['params'][$filter['key']] = $param;
                $param = call_user_func_array($filter['function'], $filter['params']);
            }
            unset($this->filters[$section][$key]);
        }

        return $param;
    }

    protected function data($type, $value, $prepend)
    {
        // Used in $this->meta() and $this->link()
        if ($prepend !== false) {
            if (!isset($this->data[$type])) {
                $this->data[$type] = array();
            }
            array_unshift($this->data[$type], $value);
        } else {
            $this->data[$type][] = $value;
        }
    }

    protected function metadata()
    {
        // Used in $this->display()
        $metadata = array();
        $metadata[] = '<meta charset="'.$this->html['charset'].'">';
        $metadata[] = '<title>'.trim($this->html['title']).'</title>';
        if (!empty($this->html['description'])) {
            $metadata[] = '<meta name="description" content="'.trim($this->html['description']).'">';
        }
        if (!empty($this->html['keywords'])) {
            $metadata[] = '<meta name="keywords" content="'.trim($this->html['keywords']).'">';
        }
        if ($this->robots !== true) {
            $metadata[] = ($this->html['robots']) ? '<meta name="robots" content="'.$this->html['robots'].'">' : '<meta name="robots" content="noindex, nofollow">'; // ie. false or null
        }
        if (isset($this->data['meta'])) {
            foreach ($this->data['meta'] as $tag) {
                $metadata[] = '<meta '.$tag.'>';
            }
        }

        return $metadata;
    }

    protected function styles()
    {
        // Used in $this->display()
        $styles = array();
        if (isset($this->data['ico'])) {
            $styles[] = '<link rel="shortcut icon" href="'.$this->data['ico'].'">';
        }
        if (isset($this->data['apple'])) {
            $styles[] = '<link rel="apple-touch-icon" href="'.$this->data['apple'].'">';
        }
        if (isset($this->filters['prepend']['css'])) {
            $this->link($this->filters['prepend']['css'], 'prepend');
        }
        if (isset($this->filters['append']['css'])) {
            $this->link($this->filters['append']['css']);
        }
        unset($this->filters['prepend']['css'], $this->filters['append']['css']);
        $css = (isset($this->data['css'])) ? $this->data['css'] : array();
        $css = $this->process('css', array_unique($css));
        foreach ($css as $url) {
            $styles[] = '<link rel="stylesheet" href="'.$url.'">';
        }
        if (isset($this->data['style'])) {
            foreach ($this->data['style'] as $style) {
                $styles[] = $style;
            }
        }
        if (isset($this->data['other'])) {
            foreach ($this->data['other'] as $other) {
                $styles[] = $other;
            }
        }

        return $styles;
    }

    protected function scripts()
    {
        // Used in $this->display()
        $scripts = array();
        if (isset($this->filters['prepend']['javascript'])) {
            $this->link($this->filters['prepend']['javascript'], 'prepend');
        }
        $jquery = (isset($this->html['jquery'])) ? $this->html['jquery'] : false;
        $code = (isset($this->data['jquery'])) ? $this->data['jquery'] : false;
        if ($jquery || $code) {
            if (isset($code['ui'])) {
                $this->link($code['ui'] ? $code['ui'] : 'https://cdn.jsdelivr.net/jquery.ui/1.11.4/jquery-ui.min.js', 'prepend');
                unset($code['ui']);
            }
            $this->link($jquery ? $jquery : 'https://cdn.jsdelivr.net/jquery/1.12.3/jquery.min.js', 'prepend');
            if ($code) {
                $code = array_filter(array_unique($code));
            }
            if (!empty($code)) {
                foreach ($code as $key => $value) {
                    $code[$key] = $this->indent($value);
                }
                $this->script('$(document).ready(function(){'."\n".implode("\n", $code)."\n".'});');
            }
        }
        if (isset($this->filters['append']['javascript'])) {
            $this->link($this->filters['append']['javascript']);
        }
        unset($this->filters['prepend']['javascript'], $this->filters['append']['javascript']);
        $javascript = (isset($this->data['js'])) ? $this->data['js'] : array();
        $javascript = $this->process('javascript', array_unique($javascript));
        foreach ($javascript as $url) {
            $scripts[] = '<script src="'.$url.'"></script>';
        }
        if (isset($this->data['script'])) {
            foreach (array_unique($this->data['script']) as $script) {
                $scripts[] = $script;
            }
        }

        return $scripts;
    }

    protected function indent($string, $tab = "\t")
    {
        // Used in $this->style() and $this->script()
        $array = preg_split("/\r\n|\n|\r/", trim($string));
        $first = $tab.trim(array_shift($array));
        if (empty($array)) {
            return $first; // ie. no indentation at all
        }
        $spaces = array();
        foreach ($array as $value) {
            $spaces[] = strspn($value, " \t");
        }
        $spaces = min($spaces);
        foreach ($array as $key => $value) {
            $array[$key] = $tab.substr($value, $spaces);
        }
        array_unshift($array, $first);

        return implode("\n", $array);
    }

    protected function formatLocalPath($url, $array = false)
    {
        if (!preg_match('/^((?!((f|ht)tps?:)?\/\/)|'.$this->url['preg'].'?)(['.$this->url['chars'].'\/]+)?(\.[a-z0-9]*)?(.*)$/i', $url, $matches)) {
            return ($array) ? array($url, '', '', '') : $url;
        }
        list($full, $url, $not, $applicable, $path, $suffix, $query) = $matches;
        $url = $this->url['base'];
        $path = trim($path, '/');
        if ($path == 'index') {
            $path = '';
        }
        if (in_array($suffix, $this->url['html'])) {
            $suffix = (!empty($path)) ? $this->url['suffix'] : '';
        }

        return ($array) ? array($url, $path, $suffix, $query) : $url.$path.$suffix.$query;
    }

    protected function __construct()
    {
    }
}
