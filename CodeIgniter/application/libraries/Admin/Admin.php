<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin extends CI_Driver_Library {

  public $blog;
  protected $valid_drivers = array('users', 'errors', 'setup', 'php', 'pages', 'layouts', 'resources', 'databases', 'analytics');
  
  public function __construct ($params) {
    global $ci, $page;
    $this->blog = $ci->blog;
    if ($this->blog->get('page') != '#admin#') { // so that this driver may only be used as intended
      $page->eject();
    } elseif (!is_admin(2)) { // if not signed in, then they must be in the process of doing so
      if ($params['file'] != 'users') $page->eject(ADMIN . '/users');
    } elseif ($this->blog->get('name') == '' && $params['file'] != 'setup') { // this blog is just getting started
      $page->eject(ADMIN . '/setup');
    } elseif (empty($params['file'])) { // automatically redirect to someplace useful
      $page->eject(ADMIN . '/pages');
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
    $blog = ($this->blog->get('name') != '') ? $this->blog->get('name') : 'Blog';
    $page->title = 'Admin &raquo; ' . str_replace('Php', 'PHP', ucfirst($params['file'])) . ' &raquo; ' . $blog;
    $page->robots = false;
  }
  
  public function admin ($content=false) {
    global $bp, $ci, $page;
    $html = '';
    $html .= '<div id="adminForms" style="margin-top:70px; margin-bottom:20px;">';
      $nav = $bp->navbar()->brand(($this->blog->get('name') != '') ? $this->blog->get('name') : 'Blog', BASE_URL)->text('Admin')->fixed('top')->text('&nbsp;', 'right');
      $url = BASE_URL . ADMIN . '/';
      $menu = array();
      if (is_admin(1)) {
        $menu = array($ci->session->userdata('name') => array(
          'Edit Your Profile' => $url . 'users',
          'Register User' => $url . 'users/register',
          'View Users' => $url . 'users/list?view=all',
          'Logout' => $url . 'users/logout'
        ));
      } elseif (is_admin(2)) {
        $menu = array($ci->session->userdata('name') => array(
          'Edit Your Profile' => $url . 'users',
          'Logout' => $url . 'users/logout'
        ));
      } else {
        $menu = array('Sign In' => $url . 'users');
      }
      $links = array();
      if (is_admin(2)) {
        $links['Setup'] = $url . 'setup';
        if ($this->blog->get('name') != '') {
          $links['PHP'] = $url . 'php';
          $links['Pages'] = $url . 'pages';
          $links['Layouts'] = $url . 'layouts';
          $links['Resources'] = $url . 'resources';
          if (is_admin(1)) $links['Databases'] = $url . 'databases';
          $links['Analytics'] = $url . 'analytics';
          $checked = ($ci->sitemap->caching()) ? ' ' : ' checked="checked" ';
          $nav->text('<label style="margin:0;"><input type="checkbox"' . $checked . 'style="margin:0;" id="suspend" value="Y"> Suspend Caching</label>');
          $checked = ($ci->session->native->userdata('profiler')) ? ' checked="checked" ' : ' ';
          $nav->text('<label style="margin:0;"><input type="checkbox"' . $checked . 'style="margin:0;" id="profiler" value="Y"> Enable Profiler</label>');
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
      if (!is_admin(2)) $links = array();
      $sidebar = '';
      // $sidebar .= '<div style="position:relative;"><div data-spy="affix">';
        $sidebar .= $ci->admin->errors->btn() . $bp->pills($links, array('active'=>$page->url('delete', '', '?'), 'align'=>'stacked'));
      // $sidebar .= '</div></div>';
      $html .= $nav->menu($menu, array('pull'=>'right'))->close();
      $html .= $bp->row('sm', array(
        $bp->col(2, $sidebar),
        $bp->col(10, $content)
      ));
    $html .= '</div>';
    $html .= $this->wyciwyg();
    $page->link('<style type="text/css">
      .media-body span.space { margin-right:25px; }
      textarea.input-sm { font-family: Menlo, Monaco, Consolas, "Courier New", monospace; }
    </style>');
    return '<div class="container">' . $html . '</div>';
  }
  
  public function file_put_post ($file, $post, $remove_empty=true) { // the $_POST key
    $code = $this->code($post);
    if (empty($code) && $remove_empty) {
      if (file_exists($file)) unlink($file);
      return true;
    }
    #-- Check the PHP $output --#
    if (substr($file, -4) == '.php' && !empty($code)) {
      if (defined('PHP_PATH') && constant('PHP_PATH') != '') {
        $linter = BASE_URI . 'blog/syntax-check.php';
        file_put_contents($linter, $code);
        exec(PHP_PATH . ' -l ' . escapeshellarg($linter) . ' 2>&1', $output);
        unlink($linter);
        $output = trim(implode("\n", $output));
        if (!empty($output) && strpos($output, 'No syntax errors') === false) {
          $output = str_replace($linter, 'php file', $output);
          return $output; // ie. false
        }
      }
    #-- Check the Smarty $output --#
    } elseif (substr($file, -4) == '.tpl' && !empty($code)) {
      $output = $this->blog->smarty('blog', $code, 'testing');
      if ($output !== true) return $output; // ie. false
    }
    #-- Create / Update the file --#
    if (!is_dir(dirname($file))) mkdir(dirname($file), 0755, true);
    file_put_contents($file, $code);
    return true;
  }
  
  public function code ($post) {
    $code = (ini_get('magic_quotes_gpc') == '1') ? stripslashes($_POST[$post]) : $_POST[$post];
    return str_replace("\r\n", "\n", base64_decode(base64_encode($code)));
  }
  
  public function wyciwyg () {
    global $page;
    $page->plugin('CDN', 'link', 'ace/1.1.3/min/ace.js');
    $page->plugin('CDN', 'link', 'bootbox/4.2.0/bootbox.min.js');
    $page->link(BASE_URL . 'CodeIgniter/application/libraries/Admin/wyciwyg.js');
    $html = '';
    
    $html .= '<div id="wyciwyg" style="padding-top:5px; display:none;">';
  
      $html .= '<div id="toolbar" class="btn-toolbar">';
      
        $html .= '<div class="btn-group btn-group-xs markup">';
          $html .= '<button class="btn btn-default dropdown-toggle" data-toggle="dropdown"><i class="glyphicon glyphicon-header"></i> <span class="caret"></span></button>';
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
          $html .= '<button class="btn btn-default insert" data-value="&lt;p&gt;|&lt;/p&gt;">&para; Paragraph</button>';
          $html .= '<button class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>';
          $html .= '<ul class="dropdown-menu">';
            $html .= '<li><a href="#" class="insert" data-value="&lt;blockquote&gt;&#10;&#09;&lt;p&gt;|&lt;/p&gt;&#10;&lt;/blockquote&gt;"><b>&nbsp;&quot;&nbsp;</b> Blockquote</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;p class=&quot;text-left&quot;&gt;|&lt;/p&gt;"><i class="glyphicon glyphicon-align-left"></i> Left Aligned</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;p class=&quot;text-center&quot;&gt;|&lt;/p&gt;"><i class="glyphicon glyphicon-align-center"></i> Center Aligned</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;p class=&quot;text-right&quot;&gt;|&lt;/p&gt;"><i class="glyphicon glyphicon-align-right"></i> Right Aligned</a></li>';
          $html .= '</ul>';
        $html .= '</div>';
        
        $html .= '<div class="btn-group btn-group-xs markup">';
          $html .= '<button class="btn btn-default dropdown-toggle" data-toggle="dropdown"><i class="glyphicon glyphicon-list"></i> List <span class="caret"></span></button>';
          $html .= '<ul class="dropdown-menu">';
            $html .= '<li><a href="#" class="insert" data-value="&lt;ol&gt;&#10;&#09;&lt;li&gt;|&lt;/li&gt;&#10;&#09;&lt;li&gt;&lt;/li&gt;&#10;&lt;/ol&gt;">Ordered</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;ul&gt;&#10;&#09;&lt;li&gt;|&lt;/li&gt;&#10;&#09;&lt;li&gt;&lt;/li&gt;&#10;&lt;/ul&gt;">Unordered</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;ul class=&quot;unstyled&quot;&gt;&#10;&#09;&lt;li&gt;|&lt;/li&gt;&#10;&#09;&lt;li&gt;&lt;/li&gt;&#10;&lt;/ul&gt;">Unstyled</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;ul class=&quot;inline&quot;&gt;&#10;&#09;&lt;li&gt;|&lt;/li&gt;&#10;&#09;&lt;li&gt;&lt;/li&gt;&#10;&lt;/ul&gt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Inline</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;dl&gt;&#10;&#09;&lt;dt&gt;|&lt;/dt&gt;&#10;&#09;&lt;dd&gt;&lt;/dd&gt;&#10;&lt;/dl&gt;">Definition</a></li>';
            $html .= '<li><a href="#" class="insert" data-value="&lt;dl class=&quot;dl-horizontal&quot;&gt;&#10;&#09;&lt;dt&gt;|&lt;/dt&gt;&#10;&#09;&lt;dd&gt;&lt;/dd&gt;&#10;&lt;/dl&gt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Horizontal</a></li>';
          $html .= '</ul>';
        $html .= '</div>';
          
        $html .= '<div class="btn-group btn-group-xs markup">';
          $html .= '<button class="btn btn-default insert" data-value="&lt;a href=&quot;|&quot;&gt;|&lt;/a&gt;" title="Link"><i class="glyphicon glyphicon-link"></i></button>';
          $html .= '<button class="btn btn-default insert" data-value="&lt;img src=&quot;|&quot; width=&quot;&quot; height=&quot;&quot; alt=&quot;&quot;&gt;" title="Image"><i class="glyphicon glyphicon-picture"></i></button>';
          $html .= '<button class="btn btn-default insert" data-value="&lt;table class=&quot;table&quot;&gt;&#10;&#09;&lt;thead&gt;&#10;&#09;&#09;&lt;tr&gt;&#10;&#09;&#09;&#09;&lt;th&gt;|&lt;/th&gt;&#10;&#09;&#09;&#09;&lt;th&gt;&lt;/th&gt;&#10;&#09;&#09;&lt;/tr&gt;&#10;&#09;&lt;/thead&gt;&#10;&#09;&lt;tbody&gt;&#10;&#09;&#09;&lt;tr&gt;&#10;&#09;&#09;&#09;&lt;td&gt;&lt;/td&gt;&#10;&#09;&#09;&#09;&lt;td&gt;&lt;/td&gt;&#10;&#09;&#09;&lt;/tr&gt;&#10;&#09;&lt;/tbody&gt;&#10;&lt;/table&gt;" title="Table"><i class="glyphicon glyphicon-th-large"></i></button>';
        $html .= '</div>';
        
        $html .= '<div class="btn-group btn-group-xs markup">';
          $html .= '<button class="btn btn-default insert" data-value="&lt;strong&gt;|&lt;/strong&gt;" title="Bold"><i class="glyphicon glyphicon-bold"></i></button>';
          $html .= '<button class="btn btn-default insert" data-value="&lt;em&gt;|&lt;/em&gt;" title="Italic"><i class="glyphicon glyphicon-italic"></i></button>';
          $html .= '<button class="btn btn-default insert" data-value="&lt;u&gt;|&lt;/u&gt;" title="Underline"><u><b>U</b></u></button>';
        $html .= '</div>';
        
        $html .= '<div class="text-right" style="margin-bottom:10px;">';
          $html .= '<button class="btn btn-link btn-xs return" title="Click to Return">Return</button>';
          $html .= '<button class="btn btn-primary btn-xs send">Save Changes</button>';
          $html .= '<div class="btn-group btn-group-xs pull-right" style="margin-left:5px;">';
            $html .= '<button class="btn btn-default increase" title="Increase Font Size"><i class="glyphicon glyphicon-plus"></i></button>';
            $html .= '<button class="btn btn-default decrease" title="Decrease Font Size"><i class="glyphicon glyphicon-minus"></i></button>';
            $html .= '<button class="btn btn-default wordwrap" title="Toggle Wordwrap"><i class="glyphicon glyphicon-transfer"></i></button>';
          $html .= '</div>';
        $html .= '</div>';
        
      $html .= '</div>'; // end #toolbar
      
      $html .= '<div id="editor"></div>';
      
    $html .= '</div>'; // end #wyciwyg
    
    return $html;
  }
  
}

/* End of file Admin.php */
/* Location: ./application/libraries/Admin/Admin.php */