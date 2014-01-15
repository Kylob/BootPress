<?php

class Compiler {

  private $uri;
  private $info;
  private $type;
  private $code;
  private $file;
  private $analyze;
  
  public function __construct ($type, $code, $less=array()) {
    $this->uri = str_replace('\\', '/', dirname(__FILE__)) . '/';
    if (!in_array($type, array('html', 'xml', 'js', 'css', 'less'))) {
      trigger_error("The Compiler is not equipped to deal with files of type: '{$type}'");
      return false;
    }
    $this->info['time'] = microtime(true);
    $code = (array) $code;
    if ($type == 'less') {
      include_once $this->uri . 'classes/lessc.php';
      foreach ($code as $key => $compile) $code[$key] = $this->less($compile, $less);
      $type = 'css';
    } else {
      foreach ($code as $key => $compile) {
        if (BASE == substr($compile, 0, strlen(BASE))) { // a file
          $code[$key] = file_get_contents($compile);
        } elseif (substr($compile, 0, 4) == 'http') { // a website
          $code[$key] = file_get_contents($compile);
        } else { // a string
          $code[$key] = $compile;
        }
      }
    }
    $this->type = $type;
    $this->code = implode("\n\n", $code);
    $this->file = $this->uri . 'tmp/' . sha1($this->code) . '.txt';
    $this->info['originalSize'] = strlen($this->code);
  }
  
  public function css_min () {
    if ($this->type != 'css') return $this;
    include_once $this->uri . 'classes/CSSmin.php';
    $css = new CSSmin;
    $this->code = $css->run($this->code);
    unset($css);
    return $this;
  }
  
  public function google ($params=array()) {
    $type = strtolower($this->type);
    $function = 'google_' . $type;
    switch ($type) {
      case 'html': // http://code.google.com/p/htmlcompressor/
      case 'xml': // http://code.google.com/p/htmlcompressor/
      case 'js': // http://code.google.com/p/closure-compiler/
      case 'css': // http://code.google.com/p/closure-stylesheets/
        return $this->$function($params);
        break;
    }
    return $this;
  }
  
  public function yui ($params=array()) { // http://developer.yahoo.com/yui/compressor/
    if (!in_array($this->type, array('js', 'css'))) return $this;
    $options = array_merge(array(
      'nomunge' => false, // Minify only, do not obfuscate
      'semi' => false, // Preserve all semicolons
      'nooptimize' => false // Disable all micro optimizations
    ), $params);
    $cmd = escapeshellarg($this->uri . 'java/yuicompressor.jar');
    $cmd .= ' ' . escapeshellarg($this->file);
    $cmd .= ' --charset UTF-8';
    $cmd .= ' --type ' . $this->type;
    if ($this->type == 'js') {
      if ($options['nomunge']) $cmd .= ' --nomunge';
      if ($options['semi']) $cmd .= ' --preserve-semi';
      if ($options['nooptimize']) $cmd .= ' --disable-optimizations';
    }
    return $this->java($cmd);
  }
  
  public function jslint ($params=array()) {
    if ($this->type != 'js') return $this;
    $options = array(
      'anon' => false, // If the space may be omitted in anonymous function declarations
      'bitwise' => false, // If bitwise operators should be allowed
      'browser' => false, // If the standard browser globals should be predefined
      'continue' => false, // If the continuation statement should be tolerated
      'css' => false, // If css workarounds should be tolerated
      'debug' => false, // If debugger statements should be allowed
      'devel' => false, // If logging should be allowed (console, alert, etc.)
      'encoding' => false, // Specify the input encoding
      'eqeq' => false, // If == should be allowed
      'es5' => false, // If es5 syntax should be allowed
      'evil' => false, // If eval should be allowed
      'forin' => false, // If for in statements need not filter
      'fragment' => false, // If html fragments should be allowed
      'help' => false, // Display usage information Default: false
      'indent' => false, // The indentation factor
      'maxerr' => false, // The maximum number of errors to allow
      'maxlen' => false, // The maximum length of a source line
      'newcap' => false, // If constructor names capitalization is ignored
      'node' => false, // If node.js globals should be predefined
      'nomen' => false, // If names may have dangling _
      'on' => false, // If html event handlers should be allowed
      'passfail' => false, // If the scan should stop on first error
      'plusplus' => false, // If increment/decrement should be allowed
      'predef' => false, // The names of predefined global variables
      'properties' => false, // If all property names must be declared with /*properties*/
      'regexp' => false, // If the . should be allowed in regexp literals
      'report' => false, // Display report in different formats: plain, xml, junit, checkstyle and report
      'rhino' => false, // If the rhino environment globals should be predefined
      'sloppy' => false, // If the 'use strict'; pragma is optional
      'stupid' => false, // If really stupid practices are tolerated
      'sub' => false, // If all forms of subscript notation are tolerated
      'timeout' => false, // Maximum number of seconds JSLint can run for Default: 0
      'todo' => false, // If todo comments are tolerated
      'undef' => false, // If variables can be declared out of order
      'unparam' => false, // If unused parameters should be tolerated
      'vars' => false, // If multiple var statements per function should be allowed
      'version' => false, // Show the version of JSLint in use.
      'warnings' => false, // Enable additional warnings (jslint4java)
      'white' => false, // If sloppy whitespace is tolerated
      'windows' => false // If ms windows-specific globals should be predefined
    );
    foreach ($params as $var) if (isset($options[$var])) $options[$var] = true;
    $cmd = escapeshellarg($this->uri . 'java/jslint.jar');
    foreach ($options as $var => $value) if ($value) $cmd .= ' --' . $var;
    $cmd .= ' ' . escapeshellarg($this->file);
    return $this->java($cmd);
  }
  
  public function packer () {
    if ($this->type != 'js') return $this;
    include_once $this->uri . 'classes/JavaScriptPacker.php';
    $packer = new JavaScriptPacker($this->code);
    $this->code = $packer->pack();
    unset($packer);
    return $this;
  }
  
  public function code () {
    return $this->code;
  }
  
  public function info () {
    $info = $this->info;
    $info['time'] = round(microtime(true) - $info['time'], 2);
    $info['compressedSize'] = strlen($this->code);
    $info['code'] = $this->code;
    $info['analyze'] = $this->analyze;
    return $info;
  }
  
  public function save ($file) {
    if (!is_dir(dirname($file))) mkdir(dirname($file), 0755, true);
    return file_put_contents($file, $this->code);
  }
  
  public function analyze ($params=array()) {
    if ($this->type == 'html') {
      $params['analyze'] = true;
      return $this->google_html($params)->code();
    } elseif ($this->type == 'css') {
      $this->google_css($params);
    }
    return (string) $this->analyze;
  }
  
  private function java ($cmd) {
    $file = $this->file;
    if ($this->save($this->file) === false) return $this;
    $java = 'java -jar ' . $cmd . ' 2>&1';
    // http://helpx.adobe.com/crx/kb/outOfProcessTextExtraction.html - java -Xmx32m -jar
    exec($java, $output, $error);
    unlink($this->file);
    $this->code = implode("\n", $output);
    if ($error != 0) $this->analyze = $this->code;
    return $this;
  }
  
  private function google_html ($params) {
    $remove = array_merge(array(
      'analyze' => false, // display report of all that the compressor can do
      'js' => false, // 'yui', 'google' - Enable inline JavaScript compression
      'css' => false, // Enable inline CSS compression using YUICompressor
      'preserve' => false, // string or array - 'php', 'server-script', 'ssi'
      'comments' => true, // Remove comments
      'multi-spaces' => true, // Remove multiple spaces
      'intertag-spaces' => true, // Remove intertag spaces
      'line-breaks' => true, // Remove line breaks
      'quotes' => true, // Remove unneeded quotes
      'doctype' => true, // Change doctype to <!DOCTYPE html>
      'style-attr' => true, // Remove TYPE attribute from STYLE tags
      'link-attr' => true, // Remove TYPE attribute from LINK tags
      'script-attr' => true, // Remove TYPE and LANGUAGE from SCRIPT tags
      'form-attr' => true, // Remove METHOD="GET" from FORM tags
      'input-attr' => true, // Remove TYPE="TEXT" from INPUT tags
      'bool-attr' => true, // Remove values from boolean tag attributes
      'js-protocol' => true, // Remove "javascript:" from inline event handlers
      'http-protocol' => true, // Remove "http:" from tag attributes
      'https-protocol' => true, // Remove "https:" from tag attributes
      'surrounding-spaces' => 'all' // 'min', 'max', 'all', '[custom_list]' - Remove surrounding spaces
    ), $params);
    $cmd = escapeshellarg($this->uri . 'java/htmlcompressor.jar');
    $cmd .= ' --type ' . $this->type;
    if ($remove['js']) $cmd .= ' --compress-js';
    if ($remove['js'] == 'google') $cmd .= ' --js-compressor closure';
    if ($remove['analyze'] === false) {
      if ($remove['css']) $cmd .= ' --compress-css';
      if ($remove['preserve']) {
        $preserve = (array) $remove['preserve'];
        foreach ($preserve as $code) {
          switch ($code) {
            case 'php':
            case 'server-script':
            case 'ssi':
             $cmd .= ' --preserve-' . $code;
             break;
          }
        }
      }
      if ($remove['comments'] === false) $cmd .= ' --preserve_comments';
      if ($remove['multi-spaces'] === false) $cmd .= ' --preserve-multi-spaces';
      if ($remove['line-breaks'] === false) $cmd .= ' --preserve-line-breaks';
      if ($remove['intertag-spaces']) $cmd .= ' --remove-intertag-spaces';
      if ($remove['quotes']) $cmd .= ' --remove-quotes';
      if ($remove['doctype']) $cmd .= ' --simple-doctype';
      if ($remove['style-attr']) $cmd .= ' --remove-style-attr';
      if ($remove['link-attr']) $cmd .= ' --remove-link-attr';
      if ($remove['script-attr']) $cmd .= ' --remove-script-attr';
      if ($remove['form-attr']) $cmd .= ' --remove-form-attr';
      if ($remove['input-attr']) $cmd .= ' --remove-input-attr';
      if ($remove['bool-attr']) $cmd .= ' --simple-bool-attr';
      if ($remove['js-protocol']) $cmd .= ' --remove-js-protocol';
      if ($remove['http-protocol']) $cmd .= ' --remove-http-protocol';
      if ($remove['https-protocol']) $cmd .= ' --remove-https-protocol';
      if (!empty($remove['surrounding-spaces'])) $cmd .= ' --remove-surrounding-spaces ' . $remove['surrounding-spaces'];
    } else {
      $cmd .= ' --analyze';
    }
    $cmd .= ' ' . escapeshellarg($this->file);
    return $this->java($cmd);
  }
  
  private function google_xml ($params) {
    $remove = array_merge(array(
      'comments' => true, // Remove comments
      'intertag-spaces' => true // Remove intertag spaces
    ), $params);
    $cmd = escapeshellarg($this->uri . 'java/htmlcompressor.jar');
    $cmd .= ' --type ' . $this->type;
    if ($remove['comments'] === false) $cmd .= ' --preserve-comments';
    if ($remove['intertag-spaces'] === false) $cmd .= ' --preserve-intertag-spaces';
    $cmd .= ' ' . escapeshellarg($this->file);
    return $this->java($cmd);
  }
  
  private function google_js ($params) {
    $options = array_merge(array(
      'format' => '', // 'PRETTY_PRINT', 'PRINT_INPUT_DELIMITER', 'SINGLE_QUOTES'
      'optimization' => 'SIMPLE_OPTIMIZATIONS' // 'WHITESPACE_ONLY', 'ADVANCED_OPTIMIZATIONS'
    ), $params);
    $cmd = escapeshellarg($this->uri . 'java/compiler.jar');
    if (!empty($options['format'])) {
      $cmd .= ' --formatting ' . $options['format'];
    }
    $cmd .= ' --compilation_level ' . $options['optimization'];
    $cmd .= ' --js=' . escapeshellarg($this->file);
    return $this->java($cmd);
  }
  
  private function google_css ($params) {
    $options = array_merge(array(
      'vendor' => '', // 'WEBKIT', 'MOZILLA', 'MICROSOFT', 'OPERA', 'KONQUEROR' (creates vendor specific output)
      'ignore-functions' => false, // an array (of functions to ignore) or true (to disable check altogether)
      'ignore-properties' => false, // an array (of properties to ignore) or true (to disable check altogehter - discouraged)
      'pretty' => false // or true (to make the output pretty)
    ), $params);
    $cmd = escapeshellarg($this->uri . 'java/closure-stylesheets.jar');
    if (!empty($options['vendor'])) $cmd .= ' --vendor ' . $options['vendor'];
    if ($options['ignore-functions']) {
      if (is_array($options['ignore-functions'])) {
        foreach ($options['ignore-functions'] as $function) $cmd .= ' --allowed-non-standard-function ' . $function;
      } else {
        $cmd .= ' --allow-unrecognized-functions';
      }
    }
    if ($options['ignore-properties']) {
      if (is_array($options['ignore-properties'])) {
        foreach ($options['ignore-properties'] as $function) $cmd .= ' --allowed-unrecognized-property ' . $function;
      } else {
        $cmd .= ' --allow-unrecognized-properties';
      }
    }
    if ($options['pretty']) $cmd .= ' --pretty-print';
    $cmd .= ' ' . escapeshellarg($this->file);
    return $this->java($cmd);
  }
  
  private function less ($css, $params) { // used in $this->__construct() to convert 'less' to 'css'
    $options = array_merge(array(
      'compress' => false, // compress all the unrequired whitespace
      'comments' => false, // preserve comments
      'variables' => false // or an array of variables to set
    ), $params);
    $less = new lessc;
    if ($options['compress']) $less->setFormatter("compressed");
    if ($options['comments']) $less->setPreserveComments(true);
    if ($options['variables']) $less->setVariables($options['variables']);
    $css = (BASE == substr($css, 0, strlen(BASE))) ? $less->compileFile($css) : $less->compile($css);
    unset($less);
    return $css;
  }
  
}

?>