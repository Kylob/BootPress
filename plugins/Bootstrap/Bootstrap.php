<?php

class Bootstrap {

  private $url;
  private $uri;
  private $version = '3.0.3';
  private $themes = array('amelia', 'cerulean', 'cosmo', 'cyborg', 'flatly', 'journal', 'readable', 'simplex', 'slate', 'spacelab', 'united', 'yeti');
  
  public function __construct () {
    global $page;
    $plugin = $page->plugin('info');
    $this->url = $plugin['url'];
    $this->uri = $plugin['uri'];
  }
  
  public function load ($variables='', $custom='') {
    global $page;
    if (!is_string($variables)) $variables = '';
    $links = array();
    if (file_exists($variables)) {
      $links['bootstrap'] = $this->theme($variables);
    } elseif (in_array($variables, $this->themes)) {
      $links['bootstrap'] = $page->plugin('CDN', array('url'=>'bootswatch/' . $this->version . 'b/' . $variables . '/bootstrap.min.css'));
      $variables = BASE . 'plugins/CDN/jsdelivr/files/bootswatch/' . $this->version . 'b/' . $variables . '/variables.less';
    } else {
      $links['bootstrap'] = $page->plugin('CDN', array('url'=>'bootstrap/' . $this->version . '/css/bootstrap.min.css'));
      $variables = $this->uri . 'less/' . $this->version . '/variables.less';
    }
    if (!empty($custom)) $links['custom'] = $this->mixins($custom, $variables);
    $page->plugin('jQuery');
    $links['javascript'] = $page->plugin('CDN', array('url'=>'bootstrap/' . $this->version . '/js/bootstrap.min.js'));
    $page->link('<meta name="viewport" content="width=device-width, initial-scale=1.0">');
    $page->link($links, true);
  }
  
  private function theme ($variables) {
    global $page;
    $less = array('file'=>$variables, 'vars'=>file_get_contents($variables));
    $code = sha1($less['vars']);
    #-- Locate Bootstrap File URI --#
    $bootstrap = $this->uri . 'css/' . $this->version . '/' . $code . '/bootstrap.css';
    if (file_exists($bootstrap)) return $this->url($bootstrap);
    #-- Get Variables and Include Defaults --#
    $variables = $this->get_variables($less['vars']);
    $file = file_get_contents($this->uri . 'less/' . $this->version . '/variables.less');
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
    #-- Add Custom Variables --#
    if (!empty($variables)) {
      $lengths = array();
      foreach ($variables as $var => $value) $lengths[] = strlen($var);
      $pad = max($lengths) + 4;
      foreach ($variables as $var => $value) $variables[$var] = '@' . str_pad($var . ':', $pad, ' ') . $value . ';';
      $file = "// Custom\n// -------------------------\n" . implode("\n", $variables) . "\n\n\n" . $file;
    }
    #-- Put Imports First --#
    if (preg_match_all('/@import\s*(.*);/i', $less['vars'], $matches)) {
      $imports = $matches[0];
      $file = "// Import(s)\n// -------------------------\n" . implode("\n", $imports) . "\n\n\n" . $file;
    }
    $code = ($theme === false) ? sha1($less['vars']) : $theme;
    #-- Update The File --#
    $code = sha1($file); // update the code
    file_put_contents($less['file'], $file); // To reflect the (potentially) new code
    #-- Compile Less and Save --#
    $page->plugin('Compiler');
    $variables = $this->get_variables($file); // again ...
    $code = new Compiler('less', $this->uri . 'less/' . $this->version . '/bootstrap.less', array('comments'=>true, 'variables'=>$variables));
    $code->save($bootstrap);
    #-- Fix The File --#
    $code = str_replace("'../", "'../../../", file_get_contents($bootstrap)); // for the ../fonts/glyphicons-halflings-regular.[type]
    $code = "/*!\n * Compiled by BootPress.org\n *\n" . $file . "\n */\n" . $code;
    if (isset($imports)) $code = implode("\n", $imports) . "\n" . $code;
    file_put_contents($bootstrap, $code);
    return $this->url($bootstrap);
  }
  
  private function mixins ($custom, $variables) {
    global $page;
    $css = file_get_contents($custom);
    $custom = $this->uri . 'css/' . $this->version . '/' . filemtime($variables) . '/' . sha1($css) . '/custom.css';
    if (file_exists($custom)) return $this->url($custom);
    $page->plugin('Compiler');
    $variables = $this->get_variables($variables);
    $css = new Compiler('less', array($this->uri . 'less/' . $this->version . '/mixins.less', $css), array('variables'=>$variables));
    $css->save($custom);
    return $this->url($custom);
  }
  
  private function get_variables ($less) {
    $variables = array();
    if (preg_match_all('/@([a-z0-9-]*):([^;]*);/i', $less, $matches)) {
      foreach ($matches[1] as $key => $value) $variables[$value] = trim($matches[2][$key]);
    }
    return $variables;
  }
  
  private function url ($file) {
    return str_replace($this->uri, $this->url, $file);
  }
  
}

?>