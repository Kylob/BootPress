<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin extends CI_Driver_Library {
  
  protected $valid_drivers = array('users', 'errors', 'files', 'setup', 'blog', 'themes', 'plugins', 'folders', 'databases', 'analytics', 'sitemap');
  private $view;
  public $url;
  
  public function __construct ($params) {
    global $ci, $page;
    if ($ci->blog->controller != '#admin#') {
      $this->valid_drivers = array('files');
      return;
    }
    if (isset($params['exclude'])) {
      foreach ((array) $params['exclude'] as $driver) {
        if (($key = array_search($driver, $this->valid_drivers)) !== false) unset($this->valid_drivers[$key]);
      }
    }
    $this->view = $params['file'];
    $this->url = 'admin';
    if (!is_admin(2)) { // if not signed in, then they must be in the process of doing so
      if ($this->view != 'users') $page->eject($page->url($this->url, 'users'));
    } elseif ($ci->blog->name == '' && $this->view != 'setup') { // this blog is just getting started
      $page->eject($page->url($this->url, 'setup'));
    } elseif ($ci->blog->name != '' && $this->view == 'setup') { // there is no reason for them to be here anymore
      $page->eject($page->url($this->url, 'blog'));
    }
    $page->robots = false;
    $blog = ($ci->blog->name != '') ? $ci->blog->name : 'Blog';
    $page->title = 'Admin &raquo; ' . ucfirst($this->view) . ' &raquo; ' . $blog;
    if (is_admin(2) && ($field = $ci->input->post('field')) && ($checked = $ci->input->post('checked'))) {
      switch ($field) {
        case 'sidebar':
          if ($checked == 'true') {
            $ci->session->collapse_sidebar = true;
          } else {
            unset($_SESSION['collapse_sidebar']);
          }
          break;
        case 'suspend':
          if ($checked == 'false') {
            $ci->sitemap->suspend_caching(0); // resume caching immediately
            unset($_SESSION['preview_layout']);
          } elseif ($checked == 'true' || $ci->sitemap->caching() === false) {
            $ci->sitemap->suspend_caching(60); // suspend all caching for the next hour
            if ($ci->session->preview_layout) $ci->session->mark_as_temp('preview_layout', 3000);
          }
          break;
        case 'profiler':
          if ($checked == 'true') {
            $ci->session->profiler = true;
          } else {
            unset($_SESSION['profiler']);
          }
          break;
      }
      exit;
    }
  }
  
  public function display ($content='') {
    global $bp, $ci, $page;
    $html = '';
    if ($ci->blog->controller != '#admin#') {
      $page->filter('layout', 'prepend', '<div id="adminForms">');
      $page->filter('layout', 'append', '</div>' . $this->wyciwyg());
      $page->style('textarea.input-sm { font-family: Menlo, Monaco, Consolas, "Courier New", monospace; }');
      return $content;
    }
    $links = array();
    $driver = array_flip($this->valid_drivers);
    $page->theme = 'admin';
    $page->header = '<h1><a href="' . $page->url('base') . '">' . ($ci->blog->name != '' ? $ci->blog->name : 'Blog') . '</a></h1>';
    if (is_admin(2)) {
      if (isset($driver['setup']) && is_admin(1) && $this->view != 'errors') $links['Setup'] = '#';
      if ($ci->blog->name != '') {
        if (isset($driver['blog'])) $links[$bp->icon('globe', 'fa') . ' Blog'] = $page->url($this->url, 'blog');
        if (isset($driver['themes'])) $links[$bp->icon('desktop', 'fa') . ' Themes'] = $page->url($this->url, 'themes');
        if (is_admin(1)) {
          if (isset($driver['plugins'])) $links[$bp->icon('plug', 'fa') . ' Plugins'] = $page->url($this->url, 'plugins');
          if (isset($driver['folders'])) $links[$bp->icon('folder', 'fa') . ' Folders'] = $page->url($this->url, 'folders');
          if (isset($driver['databases'])) $links[$bp->icon('database', 'fa') . ' Databases'] = $page->url($this->url, 'databases');
        }
        if (isset($driver['analytics'])) $links[$bp->icon('line-chart', 'fa') . ' Analytics'] = $page->url($this->url, 'analytics');
        if (isset($driver['sitemap'])) $links[$bp->icon('sitemap', 'fa') . ' Sitemap'] = $page->url($this->url, 'sitemap');
        $suspend = ($ci->sitemap->caching()) ? ' ' : ' checked="checked" ';
        $profiler = ($ci->session->profiler) ? ' checked="checked" ' : ' ';
        $page->header = '<span id="suspend-profiler" class="pull-right">' . implode('', array(
          '<span class="pull-right"><input type="checkbox"' . $profiler . 'id="profiler" value="Y"><label>Enable Profiler</label></span>',
          '<span class="pull-right"><input type="checkbox"' . $suspend . 'id="suspend" value="Y"><label>Suspend Caching</label></span>'
        )) . '</span>' . $page->header;
        $page->link('<style>#suspend-profiler { display:none; } div.icheckbox_line-red { margin:0 0 10px 20px; }</style>');
        $page->plugin('CDN', 'links', array(
          'icheck/1.0.2/icheck.min.js',
          'icheck/1.0.2/skins/line/red.min.css'
        ));
        $page->plugin('jQuery', 'code', '
          $("a.sidebar-toggle").click(function(){
            var collapsed = ($("body").hasClass("sidebar-collapse") == true) ? "true" : "false";
            $.post(location.href, {field:"sidebar", checked:collapsed});
          });
          $("section.content-header input").each(function(){
            var self = $(this); var label = self.next(); var label_text = label.text(); label.remove();
            self.iCheck({checkboxClass:"icheckbox_line-red", insert:\'<div class="icheck_line-icon"></div>\' + label_text});
          });
          $("#suspend-profiler").show();
          $("#suspend, #profiler").on("ifChanged", function(){
            var field = $(this).attr("id");
            var checked = $(this).is(":checked") ? "true" : "false";
            $.post(location.href, {field:field, checked:checked});
          });
        ');
      }
    }
    if (isset($driver['users'])) {
      $user = $bp->icon('user', 'glyphicon', 'span style="margin-right:10px;"') . ' ';
      if (is_admin(1)) {
        $menu = array($user . $ci->auth->user('name') => ($this->view == 'setup' ? '#' : array(
          'Edit Your Profile' => $page->url($this->url, 'users'),
          'Register User' => $page->url($this->url, 'users/register'),
          'View Users' => $page->url($this->url, 'users/list?view=all'),
          'Logout' => $page->url($this->url, 'users/logout')
        )));
      } elseif (is_admin(2)) {
        $menu = array($user . $ci->auth->user('name') => array(
          'Edit Your Profile' => $page->url($this->url, 'users'),
          'Logout' => $page->url($this->url, 'users/logout')
        ));
      } else {
        $menu = array($user . 'Sign In' => $page->url($this->url, 'users'));
      }
      $page->navbar = '<div class="navbar-custom-menu">' . $bp->navbar->menu($menu) . '</div>';
    }
    $search = (isset($driver['blog']) && count($links) > 1) ? $bp->search($page->url($this->url, 'blog/published'), array(
      'class' => 'sidebar-form',
      'button' => $bp->button('flat', $bp->icon('search', 'fa'), array('title'=>'Search', 'type'=>'submit'))
    )) : '';
    if (isset($driver['errors']) && $this->view != 'setup' && in_array('errors', $this->valid_drivers) && $link = $ci->admin->errors->link()) {
      $links = array_merge(array($bp->icon('exclamation-triangle', 'fa', 'i class="text-danger"') . ' <span class="text-danger"><b>View Errors</b></span>'=>$link), $links);
    }
    $links = (!empty($links)) ? '<ul class="sidebar-menu">' . $bp->links('li treeview', $links, array('active'=>$page->url($this->url, $this->view))) . '</ul>' : '';
    if (isset($driver['blog'])) {
      $blog = 'Blog <i class="fa fa-angle-left pull-right"></i></a><ul class="treeview-menu">' . $bp->links('li', array(
        $bp->icon('circle-o', 'fa') . ' Visitors' => $page->url($this->url, 'analytics'),
        $bp->icon('circle-o', 'fa') . ' Referrers' => $page->url($this->url, 'analytics/referrers'),
        $bp->icon('circle-o', 'fa') . ' Pages' => $page->url($this->url, 'analytics/pages'),
        $bp->icon('circle-o', 'fa') . ' Users' => $page->url($this->url, 'analytics/users')
      ), array('active'=>'url')) . '</ul>';
      $blog = 'Blog <i class="fa fa-angle-left pull-right"></i></a>';
      $blog .= '<ul class="treeview-menu">';
        $blog .= $bp->links('li', $ci->admin->blog->links(), array('active'=>'url'));
      $blog .= '</ul>';
      $links = str_replace('Blog</a>', $blog, $links);
    }
    if (isset($driver['analytics'])) {
      $analytics = 'Analytics <i class="fa fa-angle-left pull-right"></i></a><ul class="treeview-menu">' . $bp->links('li', array(
        $bp->icon('circle-o', 'fa') . ' Visitors' => $page->url($this->url, 'analytics'),
        $bp->icon('circle-o', 'fa') . ' Referrers' => $page->url($this->url, 'analytics/referrers'),
        $bp->icon('circle-o', 'fa') . ' Pages' => $page->url($this->url, 'analytics/pages'),
        $bp->icon('circle-o', 'fa') . ' Users' => $page->url($this->url, 'analytics/users')
      ), array('active'=>'url')) . '</ul>';
      $links = str_replace('Analytics</a>', $analytics, $links);
    }
    $page->sidebar = $search . $links;
    if (isset($driver['setup']) && $this->view != 'errors') {
      $page->sidebar = str_replace('<a href="#">Setup</a>', '<a href="#" class="wyciwyg ini" data-retrieve="setup.ini" data-file="setup.ini">' . $bp->icon('cog', 'fa') . ' Setup</a>', $page->sidebar);
      $ci->admin->files->save(array('setup.ini' => array($ci->blog->post . 'setup.ini', $ci->blog->templates . 'setup.ini')));
    }
    if ($ci->session->collapse_sidebar || empty($page->sidebar)) $page->collapse = 'sidebar';
    $page->filter('layout', 'prepend', '<div id="adminForms">');
    $page->filter('layout', 'append', '</div>' . $this->wyciwyg());
    $page->style(array(
      '.box-header h3 .fa, .box-header h3 .glyphicon { margin-right:10px; }',
      '.media-body span.space { margin-right:25px; }',
      '.media-right { white-space:nowrap; }',
      'textarea.input-sm { font-family: Menlo, Monaco, Consolas, "Courier New", monospace; }'
    ));
    return $content;
  }
  
  public function box ($style, array $contents) {
    global $bp;
    $box = '';
    $classes = 'box box-' . implode(' box-', explode(' ', $style)); // solid, default, primary, info, warning, success, danger
    foreach ($contents as $section => $value) {
      if (!$class = strstr($section, ' ')) $class = ''; // with-border, no-padding
      switch (substr($section, 0, 4)) {
        case 'head':
          $params = (array) $value;
          $box .= '<div class="box-header' . $class . '"><h3 class="box-title">' . array_shift($params) . '</h3>';
          if (!empty($params)) {
            $box .= '<div class="box-tools pull-right">';
            foreach ($params as $action) {
              switch ($action) {
                case 'collapse':
                  $classes .= ' collapsed-box';
                  $box .= '<button class="btn btn-box-tool" data-widget="collapse">' . $bp->icon('plus', 'fa') . '</button>';
                  break;
                case 'expand':
                  $box .= '<button class="btn btn-box-tool" data-widget="collapse">' . $bp->icon('minus', 'fa') . '</button>';
                  break;
                case 'remove':
                  $box .= '<button class="btn btn-box-tool" data-widget="remove">' . $bp->icon('times', 'fa') . '</button>';
                  break;
                default: $box .= '<span class="btn-box-tool">' . $action . '</span>'; break;
              }
            }
            $box .= '</div>';
          }
          $box .= '</div>';
          break;
        case 'body': $box .= (!empty($value)) ? '<div class="box-body' . $class . '">' . $value . '</div>' : ''; break;
        case 'foot': $box .= (!empty($value)) ? '<div class="box-footer' . $class . '">' . $value . '</div>' : ''; break;
        default: $box .= (!empty($value)) ? $value : ''; break;
      }
    }
    return '<div class="' . $classes . '">' . $box . '</div>';
  }
  
  private function wyciwyg () {
    global $bp, $page;
    $page->plugin('CDN', 'link', 'ace/1.1.8/min/ace.js');
    $page->link(BASE_URL . 'codeigniter/application/libraries/Admin/wyciwyg.js');
    $html = '<div id="wyciwyg" style="display:none; width:100%; padding:0 10px;">';
      $html .= '<div id="toolbar" class="btn-toolbar">';
      
        $html .= '<div class="btn-group btn-group-xs markup">';
          $html .= '<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">' . $bp->icon('header', 'fa') . ' <span class="caret"></span></button>';
          $html .= '<ul class="dropdown-menu">';
            $html .= '<li><a href="#" class="insert" data-value="&lt;h1&gt;|&lt;/h1&gt;"><h1>Heading 1</h1></a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;h2&gt;|&lt;/h2&gt;"><h2>Heading 2</h2></a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;h3&gt;|&lt;/h3&gt;"><h3>Heading 3</h3></a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;h4&gt;|&lt;/h4&gt;"><h4>Heading 4</h4></a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;h5&gt;|&lt;/h5&gt;"><h5>Heading 5</h5></a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;h6&gt;|&lt;/h6&gt;"><h6>Heading 6</h6></a></li>';
          $html .= '</ul>';
        $html .= '</div>';
        
        $html .= '<div class="btn-group btn-group-xs markup">';
          $html .= '<button class="btn btn-default insert" data-value="&lt;p&gt;|&lt;/p&gt;">' . $bp->icon('paragraph', 'fa') . ' Paragraph</button>';
          $html .= '<button class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>';
          $html .= '<ul class="dropdown-menu">';
            $html .= '<li><a href="#" class="insert" data-value="&lt;blockquote&gt;&#10;&#09;&lt;p&gt;|&lt;/p&gt;&#10;&lt;/blockquote&gt;">' . $bp->icon('quote-right', 'fa') . ' Blockquote</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;p class=&quot;text-left&quot;&gt;|&lt;/p&gt;">' . $bp->icon('align-left', 'fa') . ' Left Aligned</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;p class=&quot;text-center&quot;&gt;|&lt;/p&gt;">' . $bp->icon('align-center', 'fa') . ' Center Aligned</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;p class=&quot;text-right&quot;&gt;|&lt;/p&gt;">' . $bp->icon('align-right', 'fa') . ' Right Aligned</a></li>';
          $html .= '</ul>';
        $html .= '</div>';
        
        $html .= '<div class="btn-group btn-group-xs markup">';
          $html .= '<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">' . $bp->icon('list-alt', 'fa') . ' List <span class="caret"></span></button>';
          $html .= '<ul class="dropdown-menu">';
            $html .= '<li><a href="#" class="insert" data-value="&lt;ol&gt;&#10;&#09;&lt;li&gt;|&lt;/li&gt;&#10;&#09;&lt;li&gt;&lt;/li&gt;&#10;&lt;/ol&gt;">' . $bp->icon('list-ol', 'fa') . ' Ordered</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;ul&gt;&#10;&#09;&lt;li&gt;|&lt;/li&gt;&#10;&#09;&lt;li&gt;&lt;/li&gt;&#10;&lt;/ul&gt;">' . $bp->icon('list-ul', 'fa') . ' Unordered</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;ul class=&quot;unstyled&quot;&gt;&#10;&#09;&lt;li&gt;|&lt;/li&gt;&#10;&#09;&lt;li&gt;&lt;/li&gt;&#10;&lt;/ul&gt;">Unstyled</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;ul class=&quot;inline&quot;&gt;&#10;&#09;&lt;li&gt;|&lt;/li&gt;&#10;&#09;&lt;li&gt;&lt;/li&gt;&#10;&lt;/ul&gt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Inline</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;dl&gt;&#10;&#09;&lt;dt&gt;|&lt;/dt&gt;&#10;&#09;&lt;dd&gt;&lt;/dd&gt;&#10;&lt;/dl&gt;">Definition</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;dl class=&quot;dl-horizontal&quot;&gt;&#10;&#09;&lt;dt&gt;|&lt;/dt&gt;&#10;&#09;&lt;dd&gt;&lt;/dd&gt;&#10;&lt;/dl&gt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Horizontal</a></li>';
          $html .= '</ul>';
        $html .= '</div>';
          
        $html .= '<div class="btn-group btn-group-xs markup">';
          $html .= '<button class="btn btn-default insert" data-value="&lt;a href=&quot;|&quot;&gt;|&lt;/a&gt;" title="Link">' . $bp->icon('link') . '</button>';
          $html .= '<button class="btn btn-default insert" data-value="&lt;img src=&quot;|&quot; width=&quot;&quot; height=&quot;&quot; alt=&quot;&quot;&gt;" title="Image">' . $bp->icon('picture') . '</button>';
          $html .= '<button class="btn btn-default insert" data-value="&lt;table class=&quot;table&quot;&gt;&#10;&#09;&lt;thead&gt;&#10;&#09;&#09;&lt;tr&gt;&#10;&#09;&#09;&#09;&lt;th&gt;|&lt;/th&gt;&#10;&#09;&#09;&#09;&lt;th&gt;&lt;/th&gt;&#10;&#09;&#09;&lt;/tr&gt;&#10;&#09;&lt;/thead&gt;&#10;&#09;&lt;tbody&gt;&#10;&#09;&#09;&lt;tr&gt;&#10;&#09;&#09;&#09;&lt;td&gt;&lt;/td&gt;&#10;&#09;&#09;&#09;&lt;td&gt;&lt;/td&gt;&#10;&#09;&#09;&lt;/tr&gt;&#10;&#09;&lt;/tbody&gt;&#10;&lt;/table&gt;" title="Table">' . $bp->icon('table', 'fa') . '</button>';
        $html .= '</div>';
        
        $html .= '<div class="btn-group btn-group-xs markup">';
          $html .= '<button class="btn btn-default insert" data-value="&lt;strong&gt;|&lt;/strong&gt;" title="Bold">' . $bp->icon('bold', 'fa') . '</button>';
          $html .= '<button class="btn btn-default insert" data-value="&lt;em&gt;|&lt;/em&gt;" title="Italic">' . $bp->icon('italic', 'fa') . '</button>';
          $html .= '<button class="btn btn-default insert" data-value="&lt;u&gt;|&lt;/u&gt;" title="Underline">' . $bp->icon('underline', 'fa') . '</button>';
        $html .= '</div>';
        
        $html .= '<div class="pull-right" style="margin-bottom:10px;">';
          $html .= '<button class="return eject btn btn-link btn-xs" title="Click to Return">' . $bp->icon('reply', 'fa') . ' Return</button>';
          $html .= '<button class="send btn btn-primary btn-xs">' . $bp->icon('save', 'fa') . ' Save Changes</button>';
          $html .= '<div class="btn-group btn-group-xs pull-right" style="margin-left:5px;">';
            $html .= '<button class="btn btn-default increase" title="Increase Font Size"><i class="glyphicon glyphicon-plus"></i></button>';
            $html .= '<button class="btn btn-default decrease" title="Decrease Font Size"><i class="glyphicon glyphicon-minus"></i></button>';
            $html .= '<button class="btn btn-default wordwrap" title="Toggle Wordwrap"><i class="glyphicon glyphicon-transfer"></i></button>';
          $html .= '</div>';
          $html .= '<div class="file pull-right" style="margin:1px 10px; font-weight:bold;"></div>';
          $html .= '<div class="status pull-right" style="margin:1px 0 1px 10px; font-weight:bold; display:none;"></div>';
        $html .= '</div>';
        
      $html .= '</div>'; // #toolbar
      $html .= '<div id="editor"></div>';
    $html .= '</div>'; // #wyciwyg
    return $html;
  }
  
}

/* End of file Admin.php */
/* Location: ./application/libraries/Admin/Admin.php */
