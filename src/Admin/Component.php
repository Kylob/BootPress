<?php

namespace BootPress\Admin;

use BootPress\Auth\Component as Auth;
use BootPress\Page\Component as Page;
use BootPress\Bootstrap\v3\Component as Bootstrap;

/*
use BootPress\Admin\Component as Admin;
use BootPress\Auth\Component as Auth;
use BootPress\Blog\Component as Blog;
use BootPress\Page\Component as Page;

$html = '';
$page = Page::html();
$blog = new Blog();
if ($admin = Admin::page('admin')) {
    $auth = new Auth(array(
        'basic' => array('admin'=>'password'),
    ));
    if ($class = Admin::setup($auth, $blog, array(
        'users' => 'BootPress\Admin\Pages\Users',
        'blog' => 'BootPress\Admin\Pages\Blog',
        'themes' => 'BootPress\Admin\Pages\Themes',
        'folders' => 'BootPress\Admin\Pages\Folders',
        'databases' => 'BootPress\Admin\Pages\Databases',
        'code' => 'BootPress\Admin\Pages\Code',
    ))) {
        $html .= $class::page();
    } else {
        $page->eject('admin/'.($auth->isAdmin(2) ? 'blog' : 'users'));
    }
}
$page->send(Asset::dispatch('html', $html));
*/

class Component {

    public static $website = 'Website'; // change this to actual site name
    public static $admin = ''; // base url path eg. 'admin'
    public static $page;
    public static $plugin;
    public static $offset;
    public static $user;
    public static $bp;
    public static $db;
    public static $auth;
    public static $path = '';
    public static $method = null;
    public static $route = [];
    private static $sidebar = [];

    public static function params ($get) {
        $params = [];
        if (is_string($get)) {
            $get = func_get_args();
        }
        foreach ($get as $param) {
            switch ($param) {
                case 'website':
                case 'admin':
                case 'page':
                case 'plugin':
                case 'offset':
                case 'user':
                case 'bp':
                case 'db':
                case 'auth':
                case 'path':
                case 'method':
                case 'route':
                    $params[$param] = static::$$param;
                    break;
                default:
					$params[$param] = (isset(self::$page::$global[$param])) ? self::$page->global[$param] : null;
                    break;
            }
        }

        return $params;
    }

    public static function page (array $map = []) {
        $html = '';
        $page = Page::html();
        $page->navbar = [];
        static::$page = $page;
        static::$plugin = $page->dirname(__CLASS__);
        static::$offset = $page->session->get('analytics.offset', 0);
        static::$user = $page->session->get('auth.user', []);
        static::$bp = $page->global['bp'];
        static::$db = $page->global['db'];
        static::$auth = $page->global['auth'];
        $page->url('set', 'admin', rtrim($page->path(static::$admin), '/').'/');
        if (static::$auth->isAdmin(1) && $action = $page->get('debugbar')) {
            if ($action == 'enable') {
                $page->session->set('enable_debugbar', true);
            } else {
                $page->session->remove('enable_debugbar');
            }
            $page->eject($page->url('delete', '', 'debugbar'));
        }
        if (($field = $page->post('field')) && ($checked = $page->post('checked'))) {
            $session = [
                'sidebar' => 'collapse_sidebar',
            ];
            if (isset($session[$field])) {
                if ($checked == 'true') {
                    $page->session->set($session[$field], true);
                } else {
                    $page->session->remove($session[$field]);
                }
            }

            return $page->sendJson();
        }
        $page->jquery('
            $(document).on("click", "[data-toggle=\'offcanvas\']", function(){
                var checked = ($("body").hasClass("sidebar-collapse") == true) ? "true" : "false";
                $.post(location.href, {field:"sidebar", checked:checked});
            });
        ');
        if (empty(static::$admin) || strpos($page->url['path'].'/', static::$admin.'/') === 0) {
            $base = [];
            $routes = [];
            foreach ($map as $path => $class) {
                $link = false;
                // if false then the class may as well not even exist
                // if null the base link will be accessible, but not appear in the sidebar
                // if string a single base link will appear in the sidebar
                // if array of string keys the base and sub links will appear in the sidebar
                // if array of numeric keys the base and sub links will be accessible, but not appear in the sidebar
                if (class_exists($class) && method_exists($class, 'page')) {
                    $route = static::$admin.'/['.$path.':path]';
                    if (method_exists($class, 'setup')) {
                        $link = $class::setup(static::$auth, $path);
                        if (is_string($link)) {
                            static::sidebar([$link => $path]);
                        } elseif (is_array($link)) {
                            if (count($link) > 1) { // just an array of extra routes that will not be in the sidebar
                                $subs = $link; // eg. ['load', 'crop', 'contrast', 'rotate', 'ocr']
                            } else { // an array key of sublinks eg. [link => [sub, link]]
                                // list($link, $subs) = each($link);
								$subs = array_shift(array_values($link));
								$link = array_shift(array_keys($link));
								static::sidebar([$link => [$path => $subs]]);
                            }
                            $methods = [];
							$blank = ''; // index page
                            foreach ($subs as $sub) {
                                if (is_string($sub)) {
									if ($sub) {
										$methods[] = $sub;
									} else {
										$blank = '?';
									}
                                } elseif (is_array($sub)) {
                                    foreach ($sub as $method => $itional) {
										$add = $route . '/[' . $method . ':method]' . $itional;
										$routes[$add] = $class;
                                    }
                                }
                            }
                            $route .= '/['.implode('|', $methods).':method]'.$blank;
                        }
                    }
                    if ($link !== false) {
                        $base[] = static::$admin.'/['.$path.':path][*]';
                        $routes[$route] = $class;
                    }
                }
            }
			// exit('<pre>$routes: ' . print_r($routes, true) . '</pre>');
            if ($route = $page->routes($routes, null, ['s'=>'[^/]++'])) { // to make "admin" in url path optional
                $route['map'] = $routes;
                static::$path = $route['params']['path'];
                if (isset($route['params']['method'])) {
                    static::$method = $route['params']['method'];
                }
                static::$route = $route;
    
                return $route['target'];
            } elseif ($class = $page->routes($base)) {
                $page->eject(static::$admin.'/'.$class['params']['path']);
            }

            return true;
        }

        return false;
    }
	
    /*
    $admin->sidebar([
        'Databases' => 'databases',
        'Analytics' => ['analytics' => [
            'Visitors' => 'visitors',
            'Referrers' => 'referrers',
            'Pages' => 'pages',
            'Users' => 'users'
        ]],
    ]);
    // https://almsaeedstudio.com/themes/AdminLTE/index2.html
    // enable multilevel links more than 2 levels deep
    // add header? without removing search etc strings?
    */
    public static function sidebar ($links, $prepend = false) {
        if (is_array($links)) {
            $page = Page::html();
            foreach ($links as $name => $path) {
                if (isset(static::$sidebar[$name])) {
                    unset(static::$sidebar[$name]);
                }
                if (is_array($path)) {
                    // list($path, $subs) = each($path);
					$subs = array_shift(array_values($path));
					$path = array_shift(array_keys($path));
                    foreach ($subs as $sub => $link) {
                        if (is_array($link)) {
                            foreach (array_keys($link) as $key) {
                                $subs[$sub] = trim($path.'/'.$key, '/');
                            }
                        } else {
                            $subs[$sub] = trim($path.'/'.$link, '/');
                        }
                    }
                    $path = [$path => $subs];
                }
                $links[$name] = $path;
            }
            if ($prepend !== false) {
                static::$sidebar = $links + static::$sidebar;
            } else {
                static::$sidebar = array_merge(static::$sidebar, $links);
            }
        } elseif ($prepend !== false) {
            array_unshift(static::$sidebar, $links);
        } else {
            static::$sidebar[] = $links;
        }
    }

    public static function box ($style, array $contents) {
        $box = '';
        $classes = 'box box-'.implode(' box-', explode(' ', $style)); // solid, default, primary, info, warning, success, danger
        foreach ($contents as $section => $value) {
            if (!$class = strstr($section, ' ')) {
                $class = ''; // with-border, no-padding
            }
            switch (substr($section, 0, 4)) {
                case 'head':
                    $params = (array) $value;
                    $box .= '<div class="box-header'.$class.'"><h3 class="box-title">'.array_shift($params).'</h3>';
                    if (!empty($params)) {
                        $box .= '<div class="box-tools pull-right">';
                        foreach ($params as $action) {
                            switch ($action) {
                                case 'collapse':
                                    $classes .= ' collapsed-box';
                                    $box .= '<button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">'.$static::$bp->icon('plus', 'fa').'</button>';
                                    break;
                                case 'expand':
                                    $box .= '<button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Expand">'.static::$bp->icon('minus', 'fa').'</button>';
                                    break;
                                case 'remove':
                                    $box .= '<button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove">'.static::$bp->icon('times', 'fa').'</button>';
                                    break;
                                default: // labels, badges, pagination, tooltips, inputs, etc.
                                    $box .= $action;
                                    break;
                            }
                        }
                        $box .= '</div>';
                    }
                    $box .= '</div>';
                    break;
                case 'body':
                    $box .= (!empty($value)) ? '<div class="box-body'.$class.'">'.$value.'</div>' : '';
                    break;
                case 'foot':
                    $box .= (!empty($value)) ? '<div class="box-footer'.$class.'">'.$value.'</div>' : '';
                    break;
                default:
                    $box .= (!empty($value)) ? $value : '';
                    break;
            }
        }

        return '<div class="'.$classes.'">'.$box.'</div>';
    }

    # https://adminlte.io/themes/AdminLTE/documentation/index.html
    public static function display ($content) {
        extract(self::params('website', 'bp', 'page', 'admin'));
        $page->meta('http-equiv="X-UA-Compatible" content="IE=edge"');
        $page->meta('content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport"');

        // Config
        foreach ([
            'options' => [],
            'pad' => false, // set to true if you want to pad the sides of your page
            'nav' => 'side', // put your nav links at either the 'top' or 'side' of your page
            'skin' => 'blue', // blue, yellow, green, purple, red, and black with optional '-light' sidebar
            'logo' => $website, // shown above sidebar, or top-left corner of site anyways
            'mini' => '', // to be shown when sidebar is collapsed
            'navbar' => [],
            'header' => '', // '<h1><a href="'.$page->url('base').'">'.$website.'</a></h1>',
            'footer' => '',
        ] as $config => $default) {
            if (is_null($page->$config)) {
                $page->$config = $default;
            }
        }

        // DebugBar
		$debugbar = ($page->session->get('enable_debugbar')) ? 'Disable' : 'Enable';
		array_unshift($page->navbar, '<a href="'.$page->url('add', '', 'debugbar', strtolower($debugbar)).'">'. $bp->icon('bug', 'fa') . '</a>');
		// $page->footer .= '<span class="pull-right"><a href="'.$page->url('add', '', 'debugbar', strtolower($debugbar)).'">'.$debugbar.' DebugBar</a></span>';

        // Nav Links
        $nav = [];
        $menu = '';
		$top_nav = ($page->nav == 'top') ? true : false; // no sidebar
        if (!empty($admin)) $admin .= '/'; // so that if it is empty we leave off the slash
        foreach (static::$sidebar as $name => $path) {
            if (is_numeric($name)) {
                $nav[] = $path;
            } else {
                $class = [];
                if (!$top_nav) {
                    // Add span for sidebar-mini - the 12px default leaves an ugly space, so...
                    $name = preg_replace('/(.*<\/(i|span)>\s*)(.+)/', '$1<span style="padding-bottom:13px;">$3</span>', $name);
                }
                $submenu = '';
                if (is_array($path)) {
                    // list($path, $subs) = each($path);
                    $subs = array_shift(array_values($path));
                    $path = array_shift(array_keys($path));
                    if (!empty($subs)) {
                        if ($top_nav) {
							$class[] = 'dropdown';
                            $submenu .= '<ul class="dropdown-menu" role="menu">';
                        } else {
                            $class[] = 'treeview';
                            $name .= ' <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>';
                            $submenu .= '<ul class="treeview-menu">';
                        }
                        $active = '';
                        foreach ($subs as $subname => $subpath) {
                            if (!empty($subpath)) {
                                $url = $page->url('admin', $subpath);
								if ($top_nav) $subname = str_replace('pull-right', '', $subname);
                                $submenu .= '<li><a href="'.$url.'">'.$subname.'</a></li>';
                                if (strpos($page->url['path'], $admin.$subpath) === 0) {
                                    $active = $url; // to get the last, most relative link
                                }
                            } elseif ($top_nav) {
                                $submenu .= '<li class="divider"></li>';
                            }
                        }
                        $submenu .= '</ul>';
                        $submenu = str_replace('<li><a href="'.$active.'">', '<li class="active"><a href="'.$active.'">', $submenu);
                    }
                }
                if (strpos($page->url['path'], $admin.$path) === 0) {
                    $class[] = 'active';
                }
				$menu .= (!empty($class)) ? '<li class="'.implode(' ', $class).'">' : '<li>';
				$link = $page->url('admin', $path);
				if ($top_nav && $submenu) {
					$menu .= $page->tag('a', [
						'href' => $link,
						'class' => 'dropdown-toggle',
						'data-toggle' => 'dropdown',
						'aria-expanded' => 'false',
					], $name.' <span class="caret"></span>') . $submenu;
				} else {
					$menu .= '<a href="'.$link.'">'.$name.'</a>'.$submenu;
				}
				$menu .= '</li>';
            }
        }
        if ($top_nav) {
            $topbar = '';
            if (!empty($menu)) $topbar .= '<ul class="nav navbar-nav">'.$menu.'</ul>';
            if (!empty($nav))  $topbar .= implode(' ', $nav);
        } else {
            $sidebar = '';
            if (!empty($nav))  $sidebar .= implode(' ', $nav);
            if (!empty($menu)) $sidebar .= '<ul class="sidebar-menu">'.$menu.'</ul>';
        }

        // Body
        $layout = ['skin-'.$page->skin];
        if ($top_nav) { // $page->nav == 'top'
            $layout[] = 'layout-top-nav';
        } elseif ($page->pad) {
            $layout[] = 'layout-boxed';
        } else {
            $layout[] = 'fixed';
        }
        // $layout[] = 'hold-transition'; // don't know what this is for!
        if ($page->nav != 'top') {
            if ($page->mini) {
                $layout[] = 'sidebar-mini';
                $page->logo = implode('', [
                    '<span class="logo-mini">' . $page->mini . '</span>',
                    '<span class="logo-lg">' . $page->logo . '</span>',
                ]);
            }
            if (empty($sidebar) || $page->session->get('collapse_sidebar')) {
                $layout[] = 'sidebar-collapse';
            }
        }
        $page->body = 'class="'.implode(' ', $layout).'"';

        // Begin "wrapper"
        $html = '<div class="wrapper">';

        // Navigation
        if ($page->nav == 'top') {
            $html .= implode('', [
                '<header class="main-header">',
                '<nav class="navbar navbar-static-top" role="navigation">',
            ]);
                if ($page->pad) $html .= '<div class="container">';
                $html .= '<div class="navbar-header">';
                    $html .= '<a class="navbar-brand" href="' . $page->url('base') . '">'.$page->logo.'</a>';
                    if (!empty($topbar)) {
                        $html .= $page->tag('button', [
                            'type' => 'button',
                            'class' => 'navbar-toggle collapsed', // it is always collapsed on every new page load
                            'data-toggle' => 'collapse',
                            'data-target' => '#navbar-collapse',
                            'aria-expanded' => 'false',
                        ], '<i class="fa fa-bars"></i>');
                    }
                $html .= '</div>';
                if (!empty($topbar)) {
                    $html .= $page->tag('div', [
                        'id' => 'navbar-collapse',
                        'class' => 'collapse navbar-collapse pull-left',
                        'aria-expanded' => 'false',
                    ], $topbar);
                }
                if (!empty($page->navbar)) {
                    $html .= '<div class="navbar-custom-menu"><ul class="nav navbar-nav"><li>';
                    $html .= implode('</li><li>', $page->navbar);
                    $html .= '</li></ul></div>';
                }
                if ($page->pad) $html .= '</div>';
            $html .= implode('', ['</nav>', '</header>']);
        } else {
            // Top Nav
            $html .= '<header class="main-header">';
                $html .= '<a class="logo" href="' . $page->url('base') . '">'.$page->logo.'</a>';
                $html .= '<nav class="navbar navbar-static-top" role="navigation">';
                if (!empty($sidebar)) {
                    $html .= '<a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button"><span class="sr-only">Toggle navigation</span><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></a>';
                }
                if (!empty($page->navbar)) {
                    $html .= '<div class="navbar-custom-menu"><ul class="nav navbar-nav"><li>';
                    $html .= implode('</li><li>', $page->navbar);
                    $html .= '</li></ul></div>';
                }
                $html .= '</nav>';
            $html .= '</header>';
            // Side Nav
            $html .= '<aside class="main-sidebar">';
            $html .= '<section class="sidebar">'.$sidebar.'</section>';
            $html .= '</aside>';
        }

        // Header and Content
        $html .= '<div class="content-wrapper">';
        if ($page->pad && $page->nav == 'top') $html .= '<div class="container">';
        if ($page->header) {
            $html .= '<section class="content-header">'.$page->header.'</section>';
        }
        $html .= '<section class="content">'.$content.'</section>';
        if ($page->pad && $page->nav == 'top') $html .= '</div>';
        $html .= '</div>';

        // Main footer
        if ($page->footer) {
            if ($page->pad && $page->nav == 'top') $page->footer = '<div class="container">'.$page->footer.'</div>';
            $html .= '<footer class="main-footer">'.$page->footer.'</footer>';
        }

        // End "wrapper"
        $html .= '</div>';
        // self::wyciwyg();
		
        // Main CSS and JS Files
		$page->link('<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.3.11/js/app.min.js"></script>', 'prepend'); // this must come after the options, fastclick, and slimscroll below
		if (!empty($page->options)) {
			$page->link('<script>var AdminLTEOptions = { '.implode(', ', (array) $page->options).' };</script>', 'prepend');
		}
		$page->link([
			'https://cdn.jsdelivr.net/bootstrap/3.3.7/css/bootstrap.min.css',
			'https://cdn.jsdelivr.net/jquery/2.2.3/jquery.min.js', // 1.12.4
			'https://cdn.jsdelivr.net/bootstrap/3.3.7/js/bootstrap.min.js',
			'https://cdn.jsdelivr.net/fontawesome/4.7.0/css/font-awesome.min.css',
			'https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.3.11/css/AdminLTE.min.css',
			'https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.3.11/css/skins/skin-'.$page->skin.'.min.css',
			'https://cdn.jsdelivr.net/fastclick/1.0.6/fastclick.min.js', // not sure if this has been updated
			'https://cdn.jsdelivr.net/slimscroll/1.3.8/jquery.slimscroll.min.js',
			'https://cdn.jsdelivr.net/bootbox/4.4.0/bootbox.min.js',
			'https://cdn.jsdelivr.net/icheck/1.0.2/icheck.min.js',
			'https://cdn.jsdelivr.net/icheck/1.0.2/skins/line/red.min.css',
			'https://cdn.jsdelivr.net/ace/1.2.6/min/ace.js',
			'<!--[if lt IE 9]>
				<script src="https://cdn.jsdelivr.net/html5shiv/3.7.3/html5shiv.min.js"></script>
				<script src="https://cdn.jsdelivr.net/respond/1.4.2/respond.min.js"></script>
			<![endif]-->',
		], 'prepend');
		if (strpos($html, 'class="timeago"')) {
			$page->link('https://cdn.jsdelivr.net/jquery.timeago/1.4.1/jquery.timeago.min.js');
			$page->jquery('$("span.timeago").timeago();');
		}

        return $html;
    }
	
    /**
     * Makes a nice Ace Editor from a textarea, or a link with a '**wyciwyg**' class.  The data attributes are:
     *
     * '**file**' - The name of the file to display.
     * '**retrieve**' - The file path (for links only).
     *
     * Use in conjuction with Files::save()
     *
     * @return <type>
     */
    public static function wyciwyg () {
        extract(self::params('bp', 'page', 'plugin'));
		static $called = null;
		if ($called) return;
		$called = true; // only once
        $page->link($page->url($plugin, 'Pages/admin/wyciwyg.js'));
        $html = "\n\n".<<<EOT
<div id="wyciwyg" style="display:none; width:100%; padding:0 10px;">
    <div id="toolbar" class="btn-toolbar">

        <div class="btn-group btn-group-xs markup">
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">{$bp->icon('header', 'fa')} <span class="caret"></span></button>
            <ul class="dropdown-menu">
                <li><a href="#" class="insert" data-value="&lt;h1&gt;|&lt;/h1&gt;"><h1>Heading 1</h1></a></li>
                <li><a href="#" class="insert" data-value="&lt;h2&gt;|&lt;/h2&gt;"><h2>Heading 2</h2></a></li>
                <li><a href="#" class="insert" data-value="&lt;h3&gt;|&lt;/h3&gt;"><h3>Heading 3</h3></a></li>
                <li><a href="#" class="insert" data-value="&lt;h4&gt;|&lt;/h4&gt;"><h4>Heading 4</h4></a></li>
                <li><a href="#" class="insert" data-value="&lt;h5&gt;|&lt;/h5&gt;"><h5>Heading 5</h5></a></li>
                <li><a href="#" class="insert" data-value="&lt;h6&gt;|&lt;/h6&gt;"><h6>Heading 6</h6></a></li>
            </ul>
        </div>

        <div class="btn-group btn-group-xs markup">
            <button class="btn btn-default insert" data-value="&lt;p&gt;|&lt;/p&gt;">{$bp->icon('paragraph', 'fa')} Paragraph</button>
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
            <ul class="dropdown-menu">
                <li><a href="#" class="insert" data-value="&lt;blockquote&gt;&#10;&#09;&lt;p&gt;|&lt;/p&gt;&#10;&lt;/blockquote&gt;">{$bp->icon('quote-right', 'fa')} Blockquote</a></li>
                <li><a href="#" class="insert" data-value="&lt;p class=&quot;text-left&quot;&gt;|&lt;/p&gt;">{$bp->icon('align-left', 'fa')} Left Aligned</a></li>
                <li><a href="#" class="insert" data-value="&lt;p class=&quot;text-center&quot;&gt;|&lt;/p&gt;">{$bp->icon('align-center', 'fa')} Center Aligned</a></li>
                <li><a href="#" class="insert" data-value="&lt;p class=&quot;text-right&quot;&gt;|&lt;/p&gt;">{$bp->icon('align-right', 'fa')} Right Aligned</a></li>
            </ul>
        </div>

        <div class="btn-group btn-group-xs markup">
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">{$bp->icon('list-alt', 'fa')} List <span class="caret"></span></button>
            <ul class="dropdown-menu">
                <li><a href="#" class="insert" data-value="&lt;ol&gt;&#10;&#09;&lt;li&gt;|&lt;/li&gt;&#10;&#09;&lt;li&gt;&lt;/li&gt;&#10;&lt;/ol&gt;">{$bp->icon('list-ol', 'fa')} Ordered</a></li>
                <li><a href="#" class="insert" data-value="&lt;ul&gt;&#10;&#09;&lt;li&gt;|&lt;/li&gt;&#10;&#09;&lt;li&gt;&lt;/li&gt;&#10;&lt;/ul&gt;">{$bp->icon('list-ul', 'fa')} Unordered</a></li>
                <li><a href="#" class="insert" data-value="&lt;ul class=&quot;unstyled&quot;&gt;&#10;&#09;&lt;li&gt;|&lt;/li&gt;&#10;&#09;&lt;li&gt;&lt;/li&gt;&#10;&lt;/ul&gt;">Unstyled</a></li>
                <li><a href="#" class="insert" data-value="&lt;ul class=&quot;inline&quot;&gt;&#10;&#09;&lt;li&gt;|&lt;/li&gt;&#10;&#09;&lt;li&gt;&lt;/li&gt;&#10;&lt;/ul&gt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Inline</a></li>
                <li><a href="#" class="insert" data-value="&lt;dl&gt;&#10;&#09;&lt;dt&gt;|&lt;/dt&gt;&#10;&#09;&lt;dd&gt;&lt;/dd&gt;&#10;&lt;/dl&gt;">Definition</a></li>
                <li><a href="#" class="insert" data-value="&lt;dl class=&quot;dl-horizontal&quot;&gt;&#10;&#09;&lt;dt&gt;|&lt;/dt&gt;&#10;&#09;&lt;dd&gt;&lt;/dd&gt;&#10;&lt;/dl&gt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Horizontal</a></li>
            </ul>
        </div>

        <div class="btn-group btn-group-xs markup">
            <button class="btn btn-default insert" data-value="&lt;a href=&quot;|&quot;&gt;|&lt;/a&gt;" title="Link">{$bp->icon('link')}</button>
            <button class="btn btn-default insert" data-value="&lt;img src=&quot;|&quot; width=&quot;&quot; height=&quot;&quot; alt=&quot;&quot;&gt;" title="Image">{$bp->icon('picture')}</button>
            <button class="btn btn-default insert" data-value="&lt;table class=&quot;table&quot;&gt;&#10;&#09;&lt;thead&gt;&#10;&#09;&#09;&lt;tr&gt;&#10;&#09;&#09;&#09;&lt;th&gt;|&lt;/th&gt;&#10;&#09;&#09;&#09;&lt;th&gt;&lt;/th&gt;&#10;&#09;&#09;&lt;/tr&gt;&#10;&#09;&lt;/thead&gt;&#10;&#09;&lt;tbody&gt;&#10;&#09;&#09;&lt;tr&gt;&#10;&#09;&#09;&#09;&lt;td&gt;&lt;/td&gt;&#10;&#09;&#09;&#09;&lt;td&gt;&lt;/td&gt;&#10;&#09;&#09;&lt;/tr&gt;&#10;&#09;&lt;/tbody&gt;&#10;&lt;/table&gt;" title="Table">{$bp->icon('table', 'fa')}</button>
        </div>

        <div class="btn-group btn-group-xs markup">
            <button class="btn btn-default insert" data-value="&lt;strong&gt;|&lt;/strong&gt;" title="Bold">{$bp->icon('bold', 'fa')}</button>
            <button class="btn btn-default insert" data-value="&lt;em&gt;|&lt;/em&gt;" title="Italic">{$bp->icon('italic', 'fa')}</button>
            <button class="btn btn-default insert" data-value="&lt;u&gt;|&lt;/u&gt;" title="Underline">{$bp->icon('underline', 'fa')}</button>
        </div>

        <div class="pull-right" style="margin-bottom:10px;">
            <button class="eject btn btn-link btn-xs" title="Click to Return">{$bp->icon('reply', 'fa')} Return</button>
            <button class="send btn btn-primary btn-xs">{$bp->icon('save', 'fa')} Save Changes</button>
            <div class="btn-group btn-group-xs pull-right" style="margin-left:5px;">
                <button class="btn btn-default increase" title="Increase Font Size"><i class="glyphicon glyphicon-plus"></i></button>
                <button class="btn btn-default decrease" title="Decrease Font Size"><i class="glyphicon glyphicon-minus"></i></button>
                <button class="btn btn-default wordwrap" title="Toggle Wordwrap"><i class="glyphicon glyphicon-transfer"></i></button>
            </div>
            <div class="file pull-right" style="margin:1px 10px; font-weight:bold;"></div>
            <div class="status pull-right" style="margin:1px 0 1px 10px; font-weight:bold; display:none;"></div>
        </div>

    </div> <!-- #toolbar -->
    <div id="editor"></div>
</div> <!-- #wyciwyg -->
EOT;
        $page->filter('html', function ($prepend, $html, $append) {
            return $prepend.$html.$append;
        }, array('<div id="adminForms">', 'this', '</div>'.$html), 50);
    }
}
