<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin extends CI_Driver_Library {
  
  protected $valid_drivers = array('users', 'errors', 'files', 'setup', 'blog', 'themes', 'plugins', 'folders', 'databases', 'analytics', 'sitemap');
  private $view;
  
  public function __construct ($params) {
    global $ci, $page;
    if ($ci->blog->controller != '#admin#' && ADMIN != '') {
      $this->valid_drivers = array('files');
      return;
    }
    if (is_admin(2)) {
      $suspend = $ci->input->post('suspend'); // caching
      $preview = $ci->session->native->tempdata('preview_layout');
      if ($suspend == 'false') {
        $ci->sitemap->suspend_caching(0); // resume immediately
        if ($preview) $ci->session->native->unset_tempdata('preview_layout');
      } elseif ($suspend == 'true' || $ci->sitemap->caching() === false) {
        $ci->sitemap->suspend_caching(60); // suspend all caching for the next hour
        if ($preview) $ci->session->native->set_tempdata('preview_layout', $preview, 3000);
      }
      if ($suspend) exit;
      if ($profiler = $ci->input->post('profiler')) {
        if ($profiler == 'true') {
          $ci->session->native->set_userdata('profiler', true);
        } else {
          $ci->session->native->unset_userdata('profiler');
        }
        exit;
      }
    }
    $this->view = $params['file'];
    if (!is_admin(2)) { // if not signed in, then they must be in the process of doing so
      if ($this->view != 'users') $page->eject($page->url('admin', 'users'));
    } elseif ($ci->blog->name == '' && $this->view != 'setup') { // this blog is just getting started
      $page->eject($page->url('admin', 'setup'));
    } elseif ($ci->blog->name != '' && $this->view == 'setup') { // there is no reason for them to be here anymore
      $page->eject($page->url('admin', 'blog'));
    }
    $blog = ($ci->blog->name != '') ? $ci->blog->name : 'Blog';
    $page->title = 'Admin &raquo; ' . ucfirst($this->view) . ' &raquo; ' . $blog;
    $page->robots = false;
  }
  
  public function display ($content='') {
    global $bp, $ci, $page;
    $html = '';
    if ($ci->blog->controller != '#admin#' && ADMIN != '') {
      $page->filter('layout', 'prepend', '<div id="adminForms">');
      $page->filter('layout', 'append', '</div>' . $this->wyciwyg());
      $page->link('<style>textarea.input-sm { font-family: Menlo, Monaco, Consolas, "Courier New", monospace; }</style>');
      return $content;
    }
    $html .= '<div id="adminForms" style="margin-top:70px; margin-bottom:20px;">';
      $html .= '<div class="container">';
        $brand = ($ci->blog->name != '') ? $ci->blog->name : 'Blog';
        $html .= $bp->navbar->open(array($brand => $page->url('blog')), 'top');
        $html .= $bp->navbar->text('Admin');
        $menu = array();
        if (is_admin(1)) {
          $menu = array($bp->icon('user') . ' ' . $ci->session->userdata('name') => ($this->view == 'setup' ? '#' : array(
            'Edit Your Profile' => $page->url('admin', 'users'),
            'Register User' => $page->url('admin', 'users/register'),
            'View Users' => $page->url('admin', 'users/list?view=all'),
            'Logout' => $page->url('admin', 'users/logout')
          )));
        } elseif (is_admin(2)) {
          $menu = array($bp->icon('user') . ' ' . $ci->session->userdata('name') => array(
            'Edit Your Profile' => $page->url('admin', 'users'),
            'Logout' => $page->url('admin', 'users/logout')
          ));
        } else {
          $menu = array($bp->icon('user') . ' Sign In' => $page->url('admin', 'users'));
        }
        $links = array();
        if (is_admin(2)) {
          if (is_admin(1)) $links['Setup'] = '#';
          if ($ci->blog->name != '') {
            $links[$bp->icon('globe', 'fa') . ' Blog'] = $page->url('admin', 'blog');
            $links[$bp->icon('desktop', 'fa') . ' Themes'] = $page->url('admin', 'themes');
            if (is_admin(1)) {
              $links[$bp->icon('plug', 'fa') . ' Plugins'] = $page->url('admin', 'plugins');
              $links[$bp->icon('folder', 'fa') . ' Folders'] = $page->url('admin', 'folders');
              $links[$bp->icon('database', 'fa') . ' Databases'] = $page->url('admin', 'databases');
            }
            $links[$bp->icon('line-chart', 'fa') . ' Analytics'] = $page->url('admin', 'analytics');
            $links[$bp->icon('sitemap', 'fa') . ' Sitemap'] = $page->url('admin', 'sitemap');
            $checked = ($ci->sitemap->caching()) ? ' ' : ' checked="checked" ';
            $html .= $bp->navbar->text('<label style="margin:0;"><input type="checkbox"' . $checked . 'style="margin:0;" id="suspend" value="Y"> Suspend Caching</label>');
            $checked = ($ci->session->native->userdata('profiler')) ? ' checked="checked" ' : ' ';
            $html .= $bp->navbar->text('<label style="margin:0;"><input type="checkbox"' . $checked . 'style="margin:0;" id="profiler" value="Y"> Enable Profiler</label>');
            $page->plugin('jQuery', 'code', '
              $("#suspend").change(function(){
                var checked = $(this).is(":checked") ? "true" : "false";
                $.post(location.href, {suspend:checked});
              });
              $("#profiler").change(function(){
                var checked = $(this).is(":checked") ? "true" : "false";
                $.post(location.href, {profiler:checked});
              });
            ');
          }
        }
        $html .= $bp->navbar->menu($menu, array('pull'=>'right'));
        $html .= $bp->navbar->close();
        if ($this->view == 'errors') {
          $html .= $content;
        } else {
          $search = (count($links) > 1) ? $bp->search($page->url('admin', 'blog/published')) . '<br>' : '';
          $errors = ($this->view != 'setup' && in_array('errors', $this->valid_drivers)) ? $ci->admin->errors->btn() : '';
          $sidebar = $bp->pills($links, array('active'=>$page->url('admin', $this->view), 'align'=>'stacked'));
          $sidebar = str_replace('<a href="#">Setup</a>', '<a href="#" class="wyciwyg ini" data-retrieve="setup.ini" data-file="setup.ini">' . $bp->icon('cog', 'fa') . ' Setup</a>', $sidebar);
          $ci->admin->files->save(array('setup.ini' => array($ci->blog->post . 'setup.ini', $ci->blog->templates . 'setup.ini')));
          $html .= $bp->row('md', array(
            $bp->col(2, $search . $errors . $sidebar . '<br>'),
            $bp->col(10, $content)
          ));
        }
      $html .= '</div>'; // .container
    $html .= '</div>'; // #adminForms
    $html .= $this->wyciwyg();
    $page->link('<style>
      p.lead { font-size:22px; margin-bottom:10px; }
      .media-body span.space { margin-right:25px; }
      .media-right { white-space:nowrap; }
      textarea.input-sm { font-family: Menlo, Monaco, Consolas, "Courier New", monospace; }
    </style>');
    return $html;
  }
  
  private function wyciwyg () {
    global $bp, $page;
    $page->plugin('CDN', 'link', 'ace/1.1.8/min/ace.js');
    $page->link(BASE_URL . 'code/CodeIgniter/application/libraries/Admin/wyciwyg.js');
    $html = '<div id="wyciwyg" style="display:none;">';
      $html .= '<div class="container">';
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
      $html .= '</div>'; // .container
    $html .= '</div>'; // #wyciwyg
    return $html;
  }
  
}

/* End of file Admin.php */
/* Location: ./application/libraries/Admin/Admin.php */