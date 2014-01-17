<?php

/*
$theme = new BootstrapTheme;
$theme->header($page->get('header'));
$theme->content($page->get('content'), 8);
$theme->sidebar($page->get('sidebar'), 2, 'right');
$theme->altbar($page->get('altbar'), 2);
$theme->footer($page->get('footer'), '50');
echo $theme->display();
unset($theme);
*/

class BootstrapTheme {

  private $uri = '';
  private $url = '';
  private $columns = array();
  private $header = '';
  private $content = '';
  private $sidebar = false;
  private $sidebar_align = 'left';
  private $altbar = false;
  private $footer = '';
  private $footer_height = false;
  
  public function __construct ($css=array()) {
    global $page;
    $plugin = $page->plugin('info');
    $this->uri = $plugin['uri'];
    $this->url = $plugin['url'];
    $options = array_merge(array(
      'custom' => '', // url
      'variables' => '', // uri or theme name
      'version' => '3.0.3'
    ), $css);
    if (!empty($options['custom'])) $page->link($options['custom'], true);
    $page->plugin('CDN', array('prepend', 'link'=>'bootstrap/' . $options['version'] . '/js/bootstrap.min.js'));
    $themes = array('amelia', 'cerulean', 'cosmo', 'cyborg', 'flatly', 'journal', 'readable', 'simplex', 'slate', 'spacelab', 'united', 'yeti');
    if (in_array($options['variables'], $themes)) {
      $variables = $this->uri . 'less/themes/' . $options['variables'] . '.less';
      $page->link($this->files($variables, $options['version'], $options['variables']), true);
      // $page->plugin('CDN', array('prepend', 'link'=>'bootswatch/' . $options['version'] . 'b/' . $options['variables'] . '/bootstrap.min.css'));
    } elseif (!empty($options['variables']) && file_exists($options['variables'])) {
      $page->link($this->files($options['variables'], $options['version']), true);
    } else {
      $page->plugin('CDN', array('prepend', 'link'=>'bootstrap/' . $options['version'] . '/css/bootstrap.min.css'));
    }
    $page->link('<meta name="viewport" content="width=device-width, initial-scale=1.0">');
  }
  
  public function header ($html) {
    $this->header = $html;
    $html = '<div class="row">';
      $html .= '<div class="col-sm-12">';
        $html .= "\n  " . '<div id="header">' . $this->header . '</div>';
      $html .= '</div>';
    $html .= '</div>';
    $this->header = $html;
  }
  
  public function content ($html, $columns) {
    $this->content = "\n  " . '<div id="content">' . $html . '</div>';
    $this->columns['content'] = (int) $columns;
    $this->columns['page'][] = $this->columns['content'];
  }
  
  public function sidebar ($html, $columns, $dir='left') {
    $this->sidebar = "\n  " . '<div id="sidebar">' . $html . '</div>';
    $this->columns['sidebar'] = (int) $columns;
    $this->columns['page'][] = $this->columns['sidebar'];
    $this->sidebar_align = ($dir != 'left') ? 'right' : 'left';
  }
  
  public function altbar ($html, $columns) {
    $this->altbar = "\n  " . '<div id="altbar">' . $html . '</div>';
    $this->columns['altbar'] = (int) $columns;
    $this->columns['page'][] = $this->columns['altbar'];
  }
  
  public function footer ($html, $height=false) {
    $this->footer = $html;
    if (is_numeric($height)) $this->footer_height = (int) $height;
    $html = '<div class="row">';
      $html .= '<div class="col-sm-12">';
        $html .= "\n  " . '<div id="footer">' . $this->footer . '</div>';
      $html .= '</div>';
    $html .= '</div>';
    $this->footer = $html;
  }
  
  public function display () {
    global $page;
    $html = '';
    #-- Header --#
    if (!empty($this->header)) $html .= $this->header;
    #-- Page --#
    $html .= '<div class="row"><div class="col-sm-12">';
    if (!isset($this->columns['content'])) { // then what's the point?
      $html .= $this->content;
    } elseif (array_sum($this->columns['page']) != 12) {
      $html .= $this->content;
      trigger_error('Your Bootstrap column counts are not matching up.  Need to fix.');
    } else {
      $content = ''; // we may have to shuffle this around, so work with me here
      if ($this->sidebar) {
        $content_class = 'col-sm-' . $this->columns['content'];
        $sidebar_class = 'col-sm-' . $this->columns['sidebar'];
        if ($this->sidebar_align == 'left') {
          $content_class .= ' col-sm-push-' . $this->columns['sidebar']; // ' pull-right';
          $sidebar_class .= ' col-sm-pull-' . $this->columns['content']; // ' pull-left';
        }
        $content .= '<div class="row">';
          $content .= '<div class="' . $content_class . '">' . $this->content . '</div>';
          $content .= '<div class="' . $sidebar_class . '">' . $this->sidebar . '</div>';
        $content .= '</div>';
      } else {
        $content = $this->content;
      }
      if ($this->altbar) { // now we can start adding the $html
        $html .= '<div class="row">';
          $html .= '<div class="col-sm-' . (12 - $this->columns['altbar']) . '">' . $content . '</div>';
          $html .= '<div class="col-sm-' . $this->columns['altbar'] . '">' . $this->altbar . '</div>';
        $html .= '</div>';
      } else {
        $html .= $content;
      }
    }
    $html .= '</div></div>';
    #-- Footer --#
    if ($this->footer_height) {
      $html = '<div id="wrap"><div class="container">' . $html . '</div><div id="push"></div></div>';
      $html .= '<div id="foot"><div class="container">' . $this->footer . '</div></div>';
    } else {
      $html = '<div class="container">' . $html . $this->footer . '</div>';
    }
    return '  ' . $html;
  }
  
  public function files ($less, $version, $theme=false) { // used in $this->__construct()
    global $page;
    $less = array('file'=>$less, 'vars'=>file_get_contents($less));
    $code = ($theme === false) ? sha1($less['vars']) : $theme;
    $bootstrap = $this->uri . 'css/' . $version . '/' . $code . '/bootstrap.css';
    if (file_exists($bootstrap)) return $this->url($bootstrap);
    $variables = $this->get_variables($less['vars']);
    $file = file_get_contents($this->uri . 'less/' . $version . '/variables.less');
    preg_match_all('/@([a-z0-9-]*):([^;]*);/i', $file, $matches);
    $defaults = array_flip($matches[1]);
    foreach ($variables as $var => $value) {
      if (isset($defaults[$var])) {
        $key = $defaults[$var];
        $original = trim($matches[2][$key]);
        if ($original != $value) {
          $replace = substr($matches[0][$key], 0, strrpos($matches[0][$key], $original)) . $value . '; // ' . $original . ';';
          $file = str_replace($matches[0][$key], $replace, $file);
        }
        unset($variables[$var]);
      }
    }
    if (!empty($variables)) {
      $lengths = array();
      foreach ($variables as $var => $value) $lengths[] = strlen($var);
      $pad = max($lengths) + 4;
      foreach ($variables as $var => $value) $variables[$var] = '@' . str_pad($var . ':', $pad, ' ') . $value . ';';
      $file = "// Custom\n// -------------------------\n" . implode("\n", $variables) . "\n\n\n" . $file;
    }
    if (preg_match_all('/@import\s*(.*);/i', $less['vars'], $matches)) {
      $imports = $matches[0];
      $file = "// Import(s)\n// -------------------------\n" . implode("\n", $imports) . "\n\n\n" . $file;
    }
    $code = ($theme === false) ? sha1($less['vars']) : $theme;
    if ($theme === false) {
      $code = sha1($file); // update the code
      file_put_contents($less['file'], $file); // update the file to reflect the (potentially) new code
    }
    $bootstrap = $this->uri . 'css/' . $version . '/' . $code . '/bootstrap.css';
    $page->plugin('Compiler');
    $variables = $this->get_variables($file);
    #-- Bootstrap --#
    if (!file_exists($bootstrap)) {
      $code = new Compiler('less', $this->uri . 'less/' . $version . '/bootstrap.less', array('comments'=>true, 'variables'=>$variables));
      $code->save($bootstrap);
      $code = str_replace("'../", "'../../../", file_get_contents($bootstrap)); // for the ../fonts/glyphicons-halflings-regular.[type]
      $code = "/*!\n * Compiled by BootPress.org\n *\n" . $file . "\n */\n" . $code;
      if (isset($imports)) $code = implode("\n", $imports) . "\n" . $code;
      file_put_contents($bootstrap, $code);
      unset($code);
    }
    return $this->url($bootstrap);
  }
  
  private function get_variables ($less) { // used in $this->files()
    $variables = array();
    if (preg_match_all('/@([a-z0-9-]*):([^;]*);/i', $less, $matches)) {
      foreach ($matches[1] as $key => $value) $variables[$value] = trim($matches[2][$key]);
    }
    return $variables;
  }
  
  private function url ($file) { // used in $this->files()
    return str_replace($this->uri, $this->url, $file);
  }
  
}

?>