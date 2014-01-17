<?php

// may need some changes to be compatible with Bootstrap 3.x - notably preg_match dashes (-) in variables

class BootstrapStyles {

  private $uri;
  private $styles = array('amelia', 'cerulean', 'cosmo', 'cyborg', 'flatly', 'journal', 'readable', 'simplex', 'slate', 'spacelab', 'united', 'yeti');
   
  public function __construct () {
    global $page;
    $get = $page->plugin('info');
    $this->uri = $get['uri'];
  }
  
  public function stylesheets ($variables=array()) {
    $variables = array_merge($this->get_variables($this->uri . 'less/variables.less'), $variables);
    $sorted = $variables;
    ksort($sorted);
    $dir = $this->uri . 'css/' . sha1(serialize($sorted)) . '/';
    $bootstrap_file = $dir . 'bootstrap.css';
    $variables_file = $dir . 'variables.less';
    if (!is_dir(dirname($bootstrap_file))) {
      global $page;
      $page->plugin('Compiler');
      #-- Bootstrap --#
      $bootstrap = new Compiler('less', $this->uri . 'less/bootstrap.less', array('comments'=>true, 'variables'=>$variables));
      $bootstrap->save($bootstrap_file);
      unset($bootstrap);
      #-- Variables --#
      $file_variables = array();
      foreach ($variables as $key => $value) $file_variables[] = "@{$key}: {$value};";
      file_put_contents($variables_file, implode("\n", $file_variables));
    }
    return array($bootstrap_file, $variables_file);
  }
  
  public function variables_used ($sortby='files') {
    $variables = $this->get_variables($this->uri . 'less/variables.less');
    foreach ($variables as $key => $value) $variables[$key] = array();
    $imports = $this->get_imports();
    $imports = array_flip($imports);
    foreach ($imports as $less => $array) {
      $file = $this->uri . 'less/' . $less;
      if (file_exists($file)) {
        $file = file_get_contents($file);
        $used = (preg_match_all('/@({)?([a-zA-Z0-9]*)(})?/i', $file, $matches)) ? array_unique($matches[2]) : array();
        foreach ($used as $key => $value) if (!isset($variables[$value])) unset($used[$key]);
        $imports[$less] = $used;
        foreach ($used as $variable) {
          if (isset($variables[$variable])) $variables[$variable][] = $less;
        }
      }
    }
    $variables = '<pre>Variable => Less Files ' . print_r($variables, true) . '</pre>';
    $imports = '<pre>Less File => Variables ' . print_r($imports, true) . '</pre>';
    return ($sortby == 'files') ? $imports : $variables;
  }
  
  public function compare ($less) {
    global $page;
    $version = '2.2.1';
    $compare = $custom = array();
    $file = file_get_contents($this->uri . 'less/' . $version . '/variables.less');
    $variables = array();
    if (preg_match_all('/@([a-z0-9]*):([^;]*);/i', $less, $matches)) {
      foreach ($matches[1] as $key => $value) $variables[$value] = trim($matches[2][$key]);
    }
    preg_match_all('/@([a-z0-9]*):([^;]*);/i', $file, $matches);
    $defaults = array_flip($matches[1]);
    foreach ($variables as $var => $value) {
      if (isset($defaults[$var])) {
        $key = $defaults[$var];
        $original = trim($matches[2][$key]);
        if ($original != $value) {
          $file = str_replace($matches[0][$key], str_replace($original, $value . '; // ' . $original, $matches[0][$key]), $file);
          $compare[$var] = $value;
        }
      } else {
        $custom[$var] = $value;
      }
    }
    if (!empty($custom)) {
      $lengths = array();
      foreach ($custom as $var => $value) $lengths[] = strlen($var);
      $pad = max($lengths) + 4;
      foreach ($custom as $var => $value) $custom[$var] = '@' . str_pad($var . ':', $pad, ' ') . $value . ';';
      $file = "// Custom\n// -------------------------\n" . implode("\n", $custom) . "\n\n\n" . $file;
    }
    if (preg_match_all('/@import\s*(.*);/i', $less, $matches)) {
      $file = "// Import(s)\n// -------------------------\n// " . implode("\n// ", $matches[0]) . "\n\n\n" . $file;
    }
    $code = sha1($file);
    $variables = $this->get_variables($file);
    $bootstrap_uri = $this->uri . 'css/' . $code . '.css';
    if (!file_exists($bootstrap_uri)) {
      $page->plugin('Compiler');
      #-- Bootstrap --#
      $bootstrap = new Compiler('less', $this->uri . 'less/' . $version . '/bootstrap.less', array('comments'=>true, 'variables'=>$variables));
      $bootstrap->yui()->save($bootstrap_uri);
      unset($bootstrap);
      #-- Variables --#
      $handler = fopen($bootstrap_uri, 'a');
      fwrite($handler, "\n/*!\n * Compiled using the php-ease.com Bootstrap plugin\n *\n" . $file . "\n */");
      fclose($handler);
    }
    return strlen($code);
    return $file;
  }
  
  public function get_variables ($less) {
    $variables = array();
    if (preg_match_all('/@([a-z0-9]*):([^;]*);/i', $less, $matches)) {
      foreach ($matches[1] as $key => $value) $variables[$value] = trim($matches[2][$key]);
    }
    return $variables;
  }
  
  public function get_imports () {
    $bootstrap = file_get_contents($this->uri . 'less/bootstrap.less');
    $bootstrap = (preg_match_all('/\n@import\s*[\'"]+(\S*)[\'"]+;/i', $bootstrap, $matches)) ? $matches[1] : array();
    $imports = $bootstrap;
    $variables = array_search('variables.less', $imports);
    if ($variables !== false) unset($imports[$variables]);
    return $imports;
  }
  
  public function compare_styles ($name='') {
    $vars = array();
    $default = $this->get_variables($this->uri . 'less/variables.less');
    foreach ($default as $key => $value) $vars[$key][] = $value; // to preserve order
    $styles = (!empty($name) && in_array($name, $this->styles)) ? array($name) : $this->styles;
    foreach ($styles as $style) {
      $compare = $this->get_variables($this->uri . 'bootswatch/' . $style . '.less');
      foreach ($compare as $key => $value) {
        if (isset($default[$key]) && $default[$key] != $value && !in_array($value, $vars[$key])) {
          $vars[$key][] = $value;
        }
      }
    }
    foreach ($vars as $key => $values) if (count($values) == 1) unset($vars[$key]);
    return $vars;
  }
  
}

?>