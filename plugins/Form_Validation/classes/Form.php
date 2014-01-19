<?php

class Form {

  private $url = '';
  private $uri = '';
  private $name = '';
  private $legend = '';
  private $required = array();
  private $asterisk = true;
  private $info = array();
  private $placeholders = array();
  private $values = array();
  private $readonly = array();
  private $check = array();
  private $process = array();
  private $upload;
  private $vars = array();
  private $errors = array();
  private $align = 'form-horizontal';
  private $size = 'sm';
  private $indent = 2;

  function __construct ($name) {
    global $page;
    $get = $page->plugin('info');
    $this->url = $get['url'];
    $this->uri = $get['uri'];
    $this->name = $name;
    $page->link($this->url . 'js/form.js');
    $page->link($this->url . 'js/validation.js');
    $page->link($this->url . 'js/validation.addon.js');
  }
  
  public function get ($var) {
    if (in_array($var, array('url', 'uri', 'name'))) return $this->$var;
  }

  public function required ($required, $asterisk=false) {
    if (is_array($required)) foreach ($required as $value) $this->required[] = $value;
    if (in_array('recaptcha', $this->required)) include_once $this->uri . 'functions/recaptcha.php';
    if ($asterisk === false) $this->asterisk = $asterisk;
  }
  
  public function info ($info) {
    foreach ($info as $name => $msg) $this->info[$name] = $msg;
  }
  
  public function placeholders ($values) {
    global $page;
    foreach ($values as $name => $value) $this->placeholders[$name] = $value;
    // $page->link('<style type="text/css">input.placeholder, textarea.placeholder{color:#aaa;}</style>');
    $page->plugin('jQuery', array('code'=>'$("input, textarea").placeholder();'));
    $page->link($this->url . 'js/placeholder.js');
  }

  public function values ($values='') {
    if (!empty($values) && is_array($values)) foreach ($values as $name => $value) $this->values[$name] = $value;
    return $this->values;
  }
  
  public function readonly ($values) {
    foreach ($values as $readonly) $this->readonly[] = $readonly;
  }

  public function check ($check) {
    foreach ($check as $name => $filter) $this->check[$name] = $filter;
  }

  public function process ($process) {
    foreach ($process as $name => $filter) $this->process[$name] = $filter;
  }

  public function upload ($upload, $filesize=5, $options=array()) {
    list($name, $extensions) = each($upload);
    $upload = array();
    $upload['name'] = $name;
    $upload['extensions'] = $extensions;
    $upload['size'] = $filesize * 1048576; // megabytes to bytes
    foreach ($options as $key => $value) $upload[$key] = $value; // limit, crop, aspectRatio, minWidth
    $this->upload = new Upload($upload);
  }

  public function validate ($function='') {
    if (empty($function) || !function_exists($function)) $function = false;
    if (!empty($this->upload)) {
      $uploads = $this->upload->validate($this->name);
      $name = $this->upload->get('name');
      if (isset($_POST[$name])) {
        $this->vars[$name] = $uploads;
        $this->values[$name] = array_keys($uploads);
      }
    }
    $validate = new Validation;
    if (in_array('recaptcha', $this->required)) {
      $error = $validate->recaptcha();
      if (!empty($error)) $this->errors['recaptcha'] = $error;
    }
    $validate->jquery($this->name, $this->check, $this->required, $this->info);
    list($vars, $errors, $eject) = $validate->form(array('post'=>$this->name), $this->check, $this->required);
    $this->errors = array_merge($this->errors, $errors);
    foreach ($vars as $name => $value) {
      if (is_array($value)) {
        foreach ($value as $key => $data) {
          if (isset($this->placeholders[$name]) && $this->placeholders[$name] == $data) $data = '';
          $this->vars[$name][$key] = ($function) ? $function($data) : $data; // to ship out
          $this->values[$name][$key] = $data; // to preselect
        }
      } else {
        if (isset($this->placeholders[$name]) && $this->placeholders[$name] == $value) $value = '';
        $this->vars[$name] = ($function) ? $function($value) : $value; // to ship out
        $this->values[$name] = $value; // to preselect
      }
    }
    if (isset($_POST['process'])) {
      foreach ($this->process as $name => $filter) {
        foreach ($_POST['process'] as $id) {
          if (isset($_POST[$name][$id]) && !empty($_POST[$name][$id])) {
            $value = (get_magic_quotes_gpc()) ?  stripslashes($_POST[$name][$id]) : $_POST[$name][$id];
            $value = $validate->data($filter, $value);
          } else {
            $value = $validate->data($filter, ''); // so we at least get the default value of $filter
          }
          $this->vars['process'][$id][$name] = ($function) ? $function($value) : $value; // ship only, no preselect
        }
      }
    }
    unset($validate);
    return array($this->vars, $this->errors, $eject);
  }
  
  public function message ($status, $msg) { // will show on page refresh
    $_SESSION['form-messenger'] = array('status'=>$status, 'msg'=>$msg, 'form'=>$this->name);
  }
  
  public function error ($name='', $msg='') {
    if (!empty($name) && !empty($msg)) $this->errors[$name] = $msg;
    return $this->errors;
  }
  
  public function align ($direction, $size='sm', $indent=2) {
    switch ($direction) {
      case 'inline': $this->align = 'form-inline'; break;
      case 'horizontal':
      default:
        $this->align = 'form-horizontal';
        $this->size = (in_array($size, array('xs', 'sm', 'md', 'lg'))) ? $size : 'sm';
        $this->indent = (is_numeric($indent) && $indent >= 0 && $indent < 12) ? $indent : 2;
        break;
    }
  }
  
  public function header ($options=array()) {
    global $page;
    if (is_string($options)) $options = array('prompt'=>$options);
    $vars = array_merge(array(
      'prompt' => '',
      'id' => $this->name,
      'method' => 'post',
      'class' => $this->align,
      'action' => $page->url('add', '', 'submitted', $this->name),
      'autocomplete' => 'off'
    ), $options);
    $prompt = array_shift($vars);
    $fields = array();
    if (!empty($this->upload)) {
      $vars['enctype'] = 'multipart/form-data';
      $fields[] = $this->field('hidden', 'MAX_FILE_SIZE', $this->upload->get('size'));
    }
    if ($vars['method'] == 'post') $fields[] = $this->field('hidden', $this->name, 'true');
    $divs = ($this->align == 'form-horizontal') ? '<div class="row"><div class="col-' . $this->size . '-12">' : '';
    $form = "\n  " . $divs . '<form' . $this->attributes($vars) . '>' . implode('', $fields);
    if (!empty($prompt)) {
      $this->legend = $prompt;
      $form .= "\n\t<fieldset><legend>{$this->legend}</legend>";
    }
    if (isset($_SESSION['form-messenger']) && $_SESSION['form-messenger']['form'] == $this->name) {
      $form .= '<div class="alert alert-' . $_SESSION['form-messenger']['status'] . ' alert-dismissable">';
        $form .= '<button type="button" class="close" data-dismiss="alert">&times;</button>';
        $form .= $_SESSION['form-messenger']['msg'];
      $form .= '</div>';
      unset($_SESSION['form-messenger']);
    }
    return $form;
  }

  public function field ($type, $name, $prompt='', $options='', $key='') {
    switch ($type) {
      case 'calendar': $control = $this->$type($name, $options); break;
      case 'checkbox': $control = $this->$type($name, $options); break;
      case 'file': $control = $this->$type($name, $options); break;
      case 'hidden': $control = $this->$type($name, $prompt, $options); break;
      case 'hierselect': $control = $this->$type($name, $options); break;
      case 'multicheck': $control = $this->$type($name, $options, $key); break;
      case 'multiselect': $control = $this->$type($name, $options, $key); break;
      case 'multitext': $control = $this->$type($name, $options, $key); break;
      case 'password': $control = $this->$type($name, $options); break;
      case 'radio': $control = $this->$type($name, $options); break;
      case 'select': $control = $this->$type($name, $options, $key); break;
      case 'submit': $control = $this->$type($name); break;
      case 'tags': $control = $this->$type($name, $options); break;
      case 'text': $control = $this->$type($name, $options); break;
      case 'textarea': $control = $this->$type($name, $options); break;
    }
    if (!isset($control)) return '';
    if (in_array($type, array('hidden', 'submit')) || $prompt == 'return') {
      return $control; // no additional markup needed
    } else {
      return $this->label($type, $name, $prompt, $control);
    }
  }
    
  public function label ($type, $name, $prompt, $control) {
    #-- Begin div.form-group --#
    $field = '<div class="form-group';
    #-- Manage Errors --#
    if (in_array($type, array('multicheck', 'multitext'))) { // these fields cannot be required
      $error = '';
    } else {
      if (isset($this->errors[$name])) {
        $field .= ' has-error';
        if (in_array($type, array('select', 'multiselect', 'hierselect'))) { // instead of 'This field is required'
          $error = '<p class="validation help-block">Please make a selection</p>';
        } else {
          $error = '<p class="validation help-block">' . $this->errors[$name] . '</p>';
        }
      } else {
        $error = '<p class="validation help-block" style="display:none;"></p>';
      }
      if ($this->asterisk && !empty($prompt) && in_array($name, $this->required)) $prompt = '* ' . $prompt;
    }
    $field .= '">'; // closing the first div tag
    #-- Alignments and Prompts --#
    if ($this->align == 'form-inline') {
      if (!empty($prompt)) $field .= '<label class="sr-only" for="' . $name . '">' . $prompt . '</label>';
      $field .= $error . $control;
    } else { // $this->align == 'form-horizontal'
      $alignment = 'col-' . $this->size . '-' . (12 - $this->indent);
      if (!empty($prompt)) {
        if (isset($this->info[$name])) $prompt .= ' <span class="glyphicon glyphicon-question-sign" style="cursor:pointer;" title="' . $this->info[$name] . '"></span>';
        $field .= '<label class="control-label col-' . $this->size . '-' . $this->indent . '" for="' . $name . '">' . $prompt . '</label>';
      } else {
        $alignment .= ' ' . 'col-' . $this->size . '-offset-' . $this->indent;
      }
      $field .= '<div class="' . $alignment . '">' . $error . $control . '</div>';
    }
    #-- End div.form-group --#
    $field .= '</div>';
    return "\n\t" . $field;
  }
  
  public function recaptcha () { // http://jsfiddle.net/hqv27/
    global $page;
    if (!function_exists('recaptcha_get_html')) return '';
    $page->filter('styles', 'append', '<script type="text/javascript">var RecaptchaOptions = { theme:"custom", custom_theme_widget:"recaptcha_widget" };</script>');
    $page->link('<script type="text/javascript" src="//www.google.com/recaptcha/api/challenge?k=' . RECAPTCHA_PUBLIC_KEY . '"></script>');
    $html = '<div id="recaptcha_widget" style="display:none">';
      $html .= '<div class="control-group">';
        $html .= '<label class="control-label">* reCAPTCHA</label>';
        $html .= '<div class="controls">';
          $html .= '<a id="recaptcha_image" href="#" class="thumbnail"></a>';
	$html .= '</div>';
      $html .= '</div>';
      $class = (!empty($this->errors['recaptcha'])) ? 'control-group error' : 'control-group';
      $html .= '<div class="' . $class . '">';
        $html .= '<label class="recaptcha_only_if_image control-label">Enter the words above:</label>';
        $html .= '<label class="recaptcha_only_if_audio control-label">Enter the numbers you hear:</label>';
        $html .= '<div class="controls">';
          $html .= '<div class="input-append">';
	    $html .= '<input type="text" id="recaptcha_response_field" name="recaptcha_response_field" style="width:180px;">';
	    $html .= '<a class="btn" href="javascript:Recaptcha.reload()" title="Reload the CAPTCHA"><i class="icon-refresh"></i></a>';
	    $html .= '<a class="btn recaptcha_only_if_image" href="javascript:Recaptcha.switch_type(\'audio\')" title="Get an audio CAPTCHA"><i class="icon-headphones"></i></a>';
	    $html .= '<a class="btn recaptcha_only_if_audio" href="javascript:Recaptcha.switch_type(\'image\')" title="Get an image CAPTCHA"><i class="icon-picture"></i></a>';
	    $html .= '<a class="btn" href="javascript:Recaptcha.showhelp()" title="Get help with CAPTCHA"><i class="icon-question-sign"></i></a>';
	  $html .= '</div>';
	  $html .= '<span id="recaptcha_error_field" class="help-inline">';
	    if (isset($this->errors['recaptcha'])) $html .= $this->errors['recaptcha'];
	  $html .= '</span>'; // recaptcha_only_if_incorrect_sol 
	$html .= '</div>';
      $html .= '</div>';
    $html .= '</div>'; // end #recaptcha_widget
    $html .= '<noscript>';
      $html .= '<iframe src="//www.google.com/recaptcha/api/noscript?k=' . RECAPTCHA_PUBLIC_KEY . '" height="300" width="500" frameborder="0"></iframe><br>';
      $html .= '<textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>';
      $html .= '<input type="hidden" name="recaptcha_response_field" value="manual_challenge">';
    $html .= '</noscript>';
    return "\n\t" . $html;
  }
  
  public function buttons ($submit, $reset='') {
    $buttons = func_get_args();
    if (substr($submit, 0, 1) != '<') {
      $buttons[0] = '<button type="submit" class="btn btn-primary" data-loading-text="Submitting...">' . $submit . '</button>';
    }
    if (isset($buttons[1]) && substr($reset, 0, 1) != '<') {
      $buttons[1] = '<button type="reset" class="btn btn-default">' . $reset . '</button>';
    }
    if ($this->align == 'form-inline') {
      $html = implode(' ', $buttons);
    } else { // $this->align == 'form-horizontal'
      $html = '<div class="form-group">';
        $alignment = 'col-' . $this->size . '-' . (12 - $this->indent) . ' ' . 'col-' . $this->size . '-offset-' . $this->indent;
        $html .= '<div class="' . $alignment . '">' . implode(' ', $buttons) . '</div>';
      $html .= '</div>';
        
    }
    return "\n\t" . $html;
  }

  public function close () {
    $html = "\n  </form>";
    if (!empty($this->legend)) $html = "\n\t</fieldset>{$html}";
    if ($this->align == 'form-horizontal') $html .= '</div></div>';
    return $html;
  }
  
  #--Private Form Functions--#
  
  private function calendar ($name, $options) {
    global $page;
    $page->plugin('jQuery', array('code'=>'$("#' . $name . '").datepicker().on("changeDate", function(){ $(this).valid(); });'));
    $page->plugin('CDN', array('links'=>array(
      'bootstrap.datepicker-fork/1.2.0/css/datepicker.min.css',
      'bootstrap.datepicker-fork/1.2.0/js/bootstrap-datepicker.min.js'
    )));
    $options['id'] = $name;
    $options['name'] = $name;
    $field = $this->pre_append('<input type="text"' . $this->attributes($this->form_control_class($options), $name) . $this->defaultValue('date', $name) . '>', $options);
    return '<div style="width:102px;">' . $field . '</div>';
    return $form;
  }
  
  private function checkbox ($name, $options) {
    list($value, $description) = each($options); // there can only be one
    $field = '<input type="checkbox"' . $this->attributes(array('id'=>$name, 'name'=>$name, 'value'=>$value), $name) . $this->defaultValue('checkbox', $name, $value) . '> ' . $description;
    return '<div class="checkbox"><label>' . $field . '</label></div>';
  }
  
  private function file ($name, $options) {
    if (isset($this->values[$name])) $options['preselect'] = $this->values[$name];
    return $this->upload->field($options);
  }
  
  private function hidden ($name, $prompt, $options) {
    $options['name'] = ($name == 'process') ? 'process[]' : $name;
    $options['value'] = $prompt;
    return '<input type="hidden"' . $this->attributes($options) . '>';
  }
  
  private function hierselect ($name, $options) {
    global $page;
    $page->link($this->url . 'js/hierSelect.js');
    $json = array();
    if (!isset($options[0])) return $this->select($name, array());
    $multi = (isset($options['hier']) && substr($options['hier'], -2) == '[]') ? true : false;
    foreach ($options[0] as $id => $main) {
      if (is_array($main)) { // a select menu with optgroups
        foreach ($main as $id => $main) {
          $hier = ($multi) ? array() : array('&nbsp;');
          if (isset($options[$id])) foreach ($options[$id] as $key => $value) $hier[$main][$key] = $value;
          $json[$id] = $hier;
        }
      } else {
        $hier = ($multi) ? array() : array('&nbsp;');
        if (isset($options[$id])) foreach ($options[$id] as $key => $value) $hier[$key] = $value;
        $json[$id] = $hier;
      }
    }
    $page->plugin('jQuery', array('code'=>'$("#' . $name . '").hierSelect("' . $options['hier'] . '", ' . json_encode($json) . ');'));
    $select = (isset($options['preselect'])) ? $options['preselect'] : $options[0];
    return $this->select($name, $select);
  }
  
  private function multicheck ($name, $options, $key) {
    $checkboxes = array();
    $keys = (array) $key;
    foreach ($options as $value => $description) {
      $key = (!empty($keys)) ? array_shift($keys) : '';
      $field = '<input type="checkbox"' . $this->attributes(array('name'=>"{$name}[{$key}]", 'value'=>$value), $name) . '>' . $description;
      $checkboxes[] = '<label class="checkbox-inline">' . $field . '</label>';
    }
    return implode(' ', $checkboxes);
  }
  
  private function multiselect ($name, $options, $select=array()) {
    $size = (count($options) < 15) ? count($options) : 15;
    $select = array_merge(array(
      'id' => $name,
      'name' => $name . '[]',
      'multiple' => 'multiple',
      'size' => $size
    ), (array) $select);
    $field = '';
    foreach ($options as $key => $value) {
      if (is_array($value)) {
        $field .= '<optgroup label="' . $key . '">';
        foreach ($value as $key => $value) {
          $field .= '<option value="' . $key . '"' . $this->defaultValue('select', $name, $key) . '>' . $value . '</option>';
        }
        $field .= '</optgroup>';
      } else {
        $field .= '<option value="' . $key . '"' . $this->defaultValue('select', $name, $key) . '>' . $value . '</option>'; 
      }
    }
    return $this->pre_append('<select' . $this->attributes($this->form_control_class($select), $name) . '>' . $field . '</select>', $select);
  }
  
  private function multitext ($name, $options, $key) {
    $options['name'] = $name . '[' . $key . ']';
    if (isset($options['value'])) $options['value'] = stripslashes(htmlspecialchars(htmlspecialchars_decode($options['value'])));
    return $this->pre_append('<input type="text"' . $this->attributes($this->form_control_class($options), $name) . '>', $options);
  }
  
  private function password ($name, $options) {
    $options['id'] = $name;
    $options['name'] = $name;
    return $this->pre_append('<input type="password"' . $this->attributes($this->form_control_class($options), $name) . '>', $options);
  }
  
  private function radio ($name, $options) {
    $radios = array();
    foreach ($options as $value => $description) {
      $field = '<input type="radio"' . $this->attributes(array('name'=>$name, 'value'=>$value), $name) . $this->defaultValue('radio', $name, $value) . '>' . $description;
      $radios[] = '<label class="radio-inline">' . $field . '</label>';
    }
    return implode(' ', $radios);
  }
  
  private function select ($name, $options, $select=array()) {
    $select = array_merge(array(
      'id' => $name,
      'name' => $name
    ), (array) $select);
    $field = '<option value="">&nbsp;</option>';
    foreach ($options as $key => $value) {
      if (is_array($value)) { // then this is an optgroup
        $field .= '<optgroup label="' . $key . '">';
        foreach ($value as $key => $value) {
          $field .= '<option value="' . $key . '"' . $this->defaultValue('select', $name, $key) . '>' . $value . '</option>';
        }
        $field .= '</optgroup>';
      } else {
        $field .= '<option value="' . $key . '"' . $this->defaultValue('select', $name, $key) . '>' . $value . '</option>'; 
      }
    }
    return $this->pre_append('<select' . $this->attributes($this->form_control_class($select), $name) . '>' . $field . '</select>', $select);
  }
  
  private function submit ($name) {
    return '<button type="submit" class="btn btn-primary" data-loading-text="Processing...">' . $name . '</button>';
  }
  
  private function tags ($name, $options) {
    global $page;
    $options['id'] = $name;
    $options['name'] = $name;
    $options['prepend'] = 'glyphicon-tags';
    $options['append'] = '<button id="' . $name . 'Tag" class="btn btn-success"><span class="glyphicon glyphicon-plus"></span> Add</button>';
    if (isset($_POST[$name])) {
      $tags = $_POST[$name];
    } elseif (isset($this->values[$name])) {
      $tags = $this->values[$name];
    } else {
      $tags = '';
    }
    if (is_array($tags)) $tags = implode(',', $tags);
    $add = 'tags:"' . $tags . '"';
    if (isset($options['limit']) && is_numeric($options['limit'])) {
      $add .= ', limit:' . (int) $options['limit'];
      unset($options['limit']);
    }
    $page->link($this->url . 'js/addTags.js');
    $page->plugin('jQuery', array('plugin'=>'ui', 'code'=>'$("#' . $name . '").addTags({' . $add . '});'));
    $page->link('<style type="text/css">#' . $name . 'Tagged .label { margin-right:25px; }</style>');
    $field = $this->pre_append('<input type="text"' . $this->attributes($this->form_control_class($options), $name) . '>', $options);
    $field .= '<p id="' . $name . 'Tagged" class="help-block" style="display:none;"></p>';
    return $field;
  }
  
  private function text ($name, $options) {
    $options['id'] = $name;
    $options['name'] = $name;
    return $this->pre_append('<input type="text"' . $this->attributes($this->form_control_class($options), $name) . $this->defaultValue('text', $name) . '>', $options);
  }
  
  private function textarea ($name, $options) {
    $options['id'] = $name;
    $options['name'] = $name;
    if (!isset($options['rows'])) $options['rows'] = 3;
    return '<textarea' . $this->attributes($this->form_control_class($options), $name) . '>' . $this->defaultValue('textarea', $name) . '</textarea>';
  }
  
  private function form_control_class ($options) { // used to bootstrapify inputs
    $options['class'] = (isset($options['class'])) ? 'form-control ' . $options['class'] : 'form-control';
    return $options;
  }
  
  private function attributes ($vars, $name='') {
    if (empty($vars)) return '';
    $attributes = array();
    if (!empty($name)) {
      if (in_array($name, $this->readonly)) $vars['readonly'] = 'readonly';
      if (isset($this->placeholders[$name])) $vars['placeholder'] = $this->placeholders[$name];
    }
    foreach ($vars as $key => $value) {
      if ($key == 'prepend' || $key == 'append' || $key == 'input') continue; // for $this->pre_append() function
      $attributes[] = $key . '="' . $value . '"';
    }
    return (!empty($attributes)) ? ' ' .  implode(' ', $attributes) : '';
  }
  
  private function pre_append ($input, $options) { // for prepending and appending to text inputs
    if (!isset($options['prepend']) && !isset($options['append']) && !isset($options['input'])) return $input;
    $size = (isset($options['input'])) ? ' ' . $options['input'] : '';
    $html = '<div class="input-group' . $size . '">';
    if (isset($options['prepend'])) $html .= $this->addon($options['prepend']);
    $html .= $input;
    if (isset($options['append'])) $html .= $this->addon($options['append']);
    $html .= '</div>';
    return $html;
  }
  
  private function addon ($html) { // used in $this->pre_append()
    if (is_array($html)) {
      foreach ($html as $key => $code) $html[$key] = $this->addon($code);
      return implode(' ', $html);
    }
    if (substr($html, 0, 9) == 'glyphicon') {
      return '<span class="input-group-addon"><span class="glyphicon ' . $html . '"></span></span>';
    } elseif (substr($html, 0, 7) == '<button') {
      return '<span class="input-group-btn">' . $html . '</span>';
    } elseif (substr($html, 0, 4) == 'icon') {
      return '<span class="input-group-addon"><span class="' . $html . '"></span></span>';
    } else {
      return '<span class="input-group-addon">' . $html . '</span>';
    }
  }
  
  private function defaultValue ($type, $name, $select='') {
    $default = '';
    if (isset($this->values[$name]) || isset($_POST[$name])) {
      $selected = (isset($_POST[$name])) ? $_POST[$name] : $this->values[$name];
      if (empty($select)) { // then this is a text field
        if (!isset($_POST[$name])) $selected = htmlspecialchars_decode($selected);
        $value = stripslashes(htmlspecialchars($selected));
      } else { // we may preselect this option
        if (is_array($selected)) {
          $value = (in_array($select, $selected)) ? true : false;
        } else {
          $value = ($selected == $select) ? true : false;
        }
      }
      if (!empty($value)) {
        switch ($type) {
          case 'checkbox': $default .= ' checked="checked"'; break;
          case 'radio': $default .= ' checked="checked"'; break;
          case 'select': $default .= ' selected="selected"'; break;
          case 'date': $default .= ' value="' . date('m/d/Y', strtotime($value)) . '"'; break;
          case 'text': $default .= ' value="' . $value . '"'; break;
          case 'textarea': $default .= $value; break;
        }
      }
    }
    return $default;
  }
  
}

?>