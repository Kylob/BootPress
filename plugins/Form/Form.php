<?php

class Form {
  
  private $form_validation; // class object
  
  // Populated in $this->__construct()
  private $name = '';
  private $type = 'post';
  private $url = '';
  private $uri = '';
  
  // Populated in $this->attempts()
  private $db;
  private $log = false; // else $name
  private $disabled = false;
  private $warning = '';
  private $captcha = false;
  
  // Populated in $this->upload()
  private $upload = array();
  
  // Populated in $this->validate()
  private $ids = array(); // managed in $this->id()
  private $info = array();
  private $labels = array();
  private $rules = array();
  private $validate = array();
  private $required = array();
  private $messages = array();
  
  // Populated in $this->submitted()
  public $vars = array();
  public $errors = array(); // public so that we can add to and check if and how many
  public $eject = '';
  
  private $prepend = array(); // managed in $this->menu() for select menus
  private $menus = array(); // managed in $this->menu()
  private $values = array(); // managed in $this->values()
  private $input = ''; // managed in $this->input($size)
  private $prompt = array(); // managed in $this->prompt()
  private $process = array(); // managed in $this->process(), dispensed in $this->multi()
  private $placeholders = false; // detected in $this->form_control_class()
  
  // Populated in $this->align()
  private $align = 'form-horizontal';
  private $size = 'sm'; // col(umn)
  private $indent = 2;

  public function __construct ($name, $plugin) {
    global $ci, $page;
    $ci->load->library('form_validation');
    $this->form_validation = new My_Form_validation; // so that we can have multiple forms without any conflicts
    if (is_array($name)) list($type, $name) = each($name);
    $this->name = $name;
    $this->type = (isset($type) && strtolower($type) == 'get') ? 'get' : 'post';
    $this->url = $plugin['url'];
    $this->uri = $plugin['uri'];
    $page->plugin('CDN', 'links', array(
      'jquery.form/3.50/jquery.form.min.js', // malsup.com/jquery/form/
      'jquery.validation/1.11.1/jquery.validate.min.js' // jqueryvalidation.org
    ));
    $page->link($this->url . 'js/form.js');
  }
  
  public function captcha ($after=0, $throttle=0, $limit=0, $name='') {
    global $ci, $page;
    if ($this->type != 'post') return;
    $current = $ci->session->captcha;
    if ($captcha = $ci->input->get('captcha')) {
      if ($captcha == 'image') {
        $ci->load->helper('captcha');
        $captcha = create_captcha(array(
          'img_path' => $this->uri . 'uploads/',
          'img_url' => $this->url . 'uploads/',
          'font_path' => $this->uri . 'fonts/' . rand(1,5) . '.ttf',
          'img_width' => 150,
          'img_height' => 34,
          'word_length' => 8,
          'pool' => '2346789abcdefghjkmnpqrtuvwxyzABCDEFGHJKMNPQRTUVWXYZ'
        )); // image, time, and word
        $ci->session->captcha = $captcha['word'];
        $ci->session->mark_as_flash('captcha');
        exit($ci->filter_links($captcha['image']));
      } else {
        header('Content-Type: application/json');
        if (strtolower($captcha) === strtolower($current)) {
          $ci->session->mark_as_flash('captcha');
          exit('true');
        }
        exit(json_encode('The CAPTCHA entered was incorrect.  Please click on the image to generate a new one.'));
      }
    }
    if ($current) $ci->session->mark_as_flash('captcha'); // we may be remote processing other items as well
    $this->attempts($after, $throttle, $limit, $name);
    if ($this->captcha) {
      $this->validate('captcha', '', 'required|remote');
      $page->plugin('jQuery', 'code', '$("#captchaimage").click(function(){
        $("input[name=captcha]").val("");
        $.get(location.href, {captcha:"image"}, function(data){
          $("#captchaimage").html(data);
          $("input[name=captcha]").focus();
        }, "html");
      });');
    }
  }
  
  public function upload ($name, $label, $rules='', $options=array()) {
    global $ci;
    $info = (isset($options['info'])) ? $options['info'] : '';
    $filesize = (isset($options['filesize'])) ? (int) $options['filesize'] : 5;
    $limit = (isset($options['limit'])) ? (int) $options['limit'] : 0;
    if (!strpos($name, '[]')) $limit = 1;
    $this->upload['name'] = $name;
    $this->upload['extensions'] = explode('|', $rules);
    $this->upload['size'] = $filesize * 1048576; // megabytes to bytes
    $this->upload['limit'] = $limit;
    $this->upload['required'] = false;
    if (!empty($this->upload['extensions']) && $this->upload['extensions'][0] == 'required') {
      $this->upload['required'] = array_shift($this->upload['extensions']);
    }
    if ($ci->input->get('submitted') == $this->name && isset($_FILES['blueimp']) && !empty($_FILES['blueimp']['name'])) {
      if (!is_dir($this->uri . 'uploads/')) mkdir($this->uri . 'uploads/', 0755, true);
      $config = array();
      $config['upload_path'] = $this->uri . 'uploads/';
      $config['allowed_types'] = implode('|', $this->upload['extensions']);
      $config['max_size'] = $this->upload['size'] / 1024; // bytes to kilobytes
      $config['encrypt_name'] = true;
      $ci->load->library('upload', $config);
      $result = array();
      if ($ci->upload->do_upload('blueimp')) {
        $data = $ci->upload->data();
        $result['success'] = $this->uploaded_file($data['full_path'], $data['client_name']);
      } else {
        $result['error'] = $ci->upload->display_errors('', '');
      }
      exit(json_encode($result));
    }
    $this->validate($name, $label, 'default[]', $info);
  }
  
  public function id ($name) {
    global $page;
    if (is_array($name)) { // to establish multiple id's at once
      $names = array();
      foreach ($name as $value) $names[$value] = $this->id($value);
      return $names;
    }
    if (!isset($this->ids[$name])) {
      $this->ids[$name] = ($split = strpos($name, '[')) ? $page->id(substr($name, 0, $split)) : $page->id($name);
    }
    return $this->ids[$name];
  }
  
  public function values ($name=null) {
    if (empty($name)) return $this->values; // they want it all
    if (is_array($name)) foreach ($name as $field => $value) $this->values[$field] = $value; // setting multiple values
    elseif (func_num_args() == 2) $this->values[$name] = func_get_arg(1); // setting a single value
    else return (isset($this->values[$name])) ? $this->values[$name] : ''; // retrieving a single value
  }
  
  public function menu ($name, $menu=array(), $prepend='') { // for radio, checkbox, and select menus
    $args = func_get_args();
    $name = array_shift($args);
    if (!empty($args)) {
      $this->menus[$name] = array_shift($args);
      if (!empty($args)) $this->prepend[$name] = array_shift($args);
    } else {
      return (isset($this->menus[$name])) ? $this->menus[$name] : array();
    }
  }
  
  public function validate ($name, $label='', $rules='', $info='') {
    global $ci, $page;
    if (is_array($name)) {
      foreach (func_get_args() as $args) call_user_func_array(array($this, 'validate'), $args);
      return;
    }
    $validate = array();
    $rules = explode('|', $rules);
    $this->messages[$name] = array();
    if (!is_array($info)) $info = (!empty($info)) ? array($info) : array();
    if (isset($info[0])) $this->info[$name] = $info[0];
    foreach ($rules as $key => $rule) {
      $param = '';
      if (preg_match("/(.*?)\[(.*)\]/", $rule, $match)) {
        $rule	= $match[1];
        $param	= $match[2];
      }
      if (isset($info[$rule])) $this->messages[$name][$rule] = $info[$rule];
      switch ($rule) {
        case 'required':            $validate['required'] = 'true'; $this->required[$name] = true; break;
        // Numbers:
        case 'numeric':             $validate['numeric'] = 'true'; $rules[] = 'callback_default_value[0]'; break;
        case 'integer':             $validate['integer'] = 'true'; $rules[] = 'callback_default_value[0]'; break;
        case 'decimal':             $validate['decimal'] = 'true'; $rules[] = 'callback_default_value[0.0]'; break;
        case 'gte':                 $validate['min'] = $param; break;
        case 'lte':                 $validate['max'] = $param; break;
        // Strings:
        case 'alpha':               $validate['alpha'] = 'true'; break;
        case 'alphanumeric':        $validate['alphanumeric'] = 'true'; break;
        case 'base64':              $validate['base64'] = 'true'; break; // defined but has no message
        case 'creditcard':          $validate['creditcard'] = 'true'; break;
        case 'date':                $validate['date'] = 'true'; $rules[] = 'sqldate'; break;
        case 'email':               $validate['email'] = 'true'; break;
        case 'ip':                  $validate['ip'] = substr($param, -2); break;
        case 'url':                 $validate['url'] = 'true'; break; // has message but not defined
        case 'regex':               $validate['regex'] = $param; break;
        case 'minlength':           $validate['minlength'] = $param; break;
        case 'maxlength':           $validate['maxlength'] = $param; break;
        case 'nowhitespace':        $validate['nowhitespace'] = 'true'; break;
        case 'matches':             $validate['equalTo'] = '#'.$this->id($param); break;
        case 'inarray':             if ($param == 'menu') $param = implode(',', array_keys($this->multi_to_single($this->menu($name))));
                                    $validate['inarray'] = $param; $rules[$key] = 'inarray[' . $param . ']'; break;
        case 'remote':              $validate['remote'] = $page->url(); unset($rules[$key]);
          // If we didn't unset($rules[$key]) then $this->errors[$name] will tell us to 'Please fix this field.'
          // If 'post'ed then you should verify when $this->submitted() && empty($this->errors)
          if ($ci->input->get($this->base($name))) {
            header('Content-Type: application/json');
            exit('false'); // we should have handled this request by now
          }
          break;
        case 'default':             $rules[] = 'callback_default_value[' . $param . ']'; unset($rules[$key]); break;
        // case 'custom':              break;
        // Filters: (beyond php itself)
        case 'YN':
        case 'yes_no':              $rules[$key] = array($this, 'yes_no'); break;
        case 'TF':
        case 'true_false':          $rules[$key] = array($this, 'true_false'); break;
        case 'single_space':        break;
        case 'prep_url':            break;
        case 'strip_image_tags':    break;
      }
    }
    array_unshift($rules, 'trim');
    $this->rules[$name] = $rules;
    if (!empty($validate)) $this->validate[$name] = $validate;
    $this->ids[$name] = $this->id($name);
    $this->labels[$name] = $label;
  }
  
  public function submitted () {
    global $ci, $page;
    if ($this->disabled || $ci->input->get('submitted') != $this->name) return false;
    $method = $this->type; // either get or post
    $process = array_flip(explode(',', (string) $ci->input->$method('process[fields]')));
    $ids = explode(',', (string) $ci->input->$method('process[ids]'));
    $this->form_validation->set_data($method == 'get' ? $_GET : $_POST);
    foreach ($this->rules as $name => $validate) {
      $field = $this->base($name);
      $label = strip_tags($this->labels[$name]);
      if (isset($process[$field])) {
        foreach ($ids as $id) $this->form_validation->set_rules($field . '[' . $id . ']', $label, $validate, $this->messages[$name]);
      } else {
        $this->form_validation->set_rules($name, $label, $validate, $this->messages[$name]);
      }
    }
    $this->form_validation->set_message('', ''); // This is stupid, but we don't like meaningless error messages either
    $this->form_validation->run(); // If we don't run this, then we can't pick out any errors
    foreach (array_keys($this->rules) as $name) {
      $field = $this->base($name);
      $var = $ci->input->post($field);
      $this->vars[$field] = (is_null($var)) ? '' : $var;
      if (isset($this->upload['name']) && $this->upload['name'] == $name) {
        $replace = array();
        foreach ((array) $this->vars[$field] as $url) {
          if (!empty($url)) {
            list($uri, $name) = $this->uri_name($url);
            $replace[$uri] = $name;
          }
        }
        $this->vars[$field] = $replace;
      }
      if (isset($process[$field])) {
        foreach ($ids as $id) {
          $error = $this->form_validation->error($field . '[' . $id . ']');
          if (!empty($error)) $this->errors[$field . '[' . $id . ']'] = strip_tags($error);
        }
      } else {
        $error = $this->form_validation->error($name);
        if (!empty($error)) $this->errors[$name] = strip_tags($error);
      }
    }
    if ($this->type == 'get') {
      $this->eject = $page->url('delete', '', array_keys($this->vars));
    } else {
      if ($this->captcha && !isset($this->errors['captcha']) && strtolower($this->vars['captcha']) !== strtolower($ci->session->captcha)) {
        $this->errors['captcha'] = 'The CAPTCHA entered was incorrect.  Please try again.';
      }
      $this->eject = $page->url('delete', '', 'submitted');
      if ($this->log !== false && empty($this->errors)) {
        $this->db->insert('attempts', array(
          'form' => $this->name,
          'submitted' => time(),
          'ip_address' => $ci->input->ip_address(),
          'field' => (!empty($this->log) && isset($this->vars[$this->log])) ? $this->vars[$this->log] : ''
        ));
      }
    }
    return (!empty($this->vars)) ? true : false;
  }
  
  public function process ($id=null) {
    global $ci;
    if (is_numeric($id)) $this->process[$id] = array();
    if (func_num_args() > 0) return;
    $info = array();
    $method = $this->type; // either get or post
    if (($fields = $ci->input->$method('process[fields]')) && ($ids = $ci->input->$method('process[ids]'))) {
      foreach (explode(',', (string) $fields) as $field) {
        foreach (explode(',', (string) $ids) as $id) {
          $info[$id][$field] = $ci->input->$method($field . '[' . $id . ']');
        }
      }
    }
    return $info;
  }
  
  public function reset_attempts () {
    global $ci;
    if ($this->log !== false) {
      $name = $ci->input->post($this->log);
      if (!empty($name)) {
        $this->db->query('DELETE FROM attempts WHERE form = ? AND submitted > 0 AND (ip_address = ? OR field = ?)', array($this->name, $ci->input->ip_address(), $name));
      } else {
        $this->db->query('DELETE FROM attempts WHERE form = ? AND submitted > 0 AND ip_address = ?', array($this->name, $ci->input->ip_address()));
      }
    }
  }
  
  public function message ($status, $message) { // will show on page refresh
    global $ci;
    $ci->session->form_messenger = array('status'=>$status, 'msg'=>$message, 'form'=>$this->name);
    $ci->session->mark_as_flash('form_messenger');
  }
  
  public function prompt ($place, $html, $required=false) {
    if ($place == 'prepend') {
      $this->prompt['prepend'] = array('html'=>$html, 'required'=>(bool) $required);
    } elseif ($place == 'append') {
      $this->prompt['append'] = $html;
    }
  }
  
  public function align ($direction='horizontal', $size='sm', $indent=2) {
    switch ($direction) {
      case 'collapse': $this->align = ''; break;
      case 'inline': $this->align = 'form-inline'; break;
      case 'horizontal':
      default:
        $this->align = 'form-horizontal';
        $this->size = (in_array($size, array('xs', 'sm', 'md', 'lg'))) ? $size : 'sm';
        $this->indent = (is_numeric($indent) && $indent >= 0 && $indent < 12) ? $indent : 2;
        break;
    }
  }
  
  public function input ($size) {
    $this->input = '';
    switch ($size) {
      case 'large':
      case 'lg': $this->input = ' input-lg'; break;
      case 'medium':
      case 'md': $this->input = ' input-md'; break;
      case 'small':
      case 'sm': $this->input = ' input-sm'; break;
    }
  }
  
  public function header ($attributes=array()) {
    global $bp, $ci, $page;
    $html = '';
    if (!empty($this->info)) {
      $page->link('<style type="text/css">#' . $this->name . ' div.tooltip-inner { text-align:left; max-width:500px; }</style>');
      $page->plugin('jQuery', 'code', '$("#' . $this->name . ' span.glyphicon-question-sign").tooltip({html:true, placement:"right", container:"#' . $this->name . '"});');
    }
    if (!empty($this->validate)) {
      $validate = (isset($attributes['validate']) && is_array($attributes['validate'])) ? $attributes['validate'] : array();
      unset($attributes['validate']);
      $validate = array_merge(array(
        'ignore' => '[]',
        'errorClass' => '"has-error"',
        'validClass' => '""',
        'errorElement' => '"span"',
        'highlight' => 'highlight',
        'unhighlight' => 'unhighlight',
        'errorPlacement' => 'errorPlacement',
        'submitHandler' => 'submitHandler',
        'onkeyup' => 'false'
      ), $validate);
      $rules = array();
      $messages = array();
      foreach ($this->validate as $name => $check) {
        if (strpos($name, '[') === false) { // otherwise we will get it in $this->field()
          foreach ($check as $key => $value) {
            $value = (is_numeric($value) || $value == 'true') ? $value : '"' . $value . '"';
            $check[$key] = $key . ':' . $value;
          }
          $rules[] = $name . ':{' . implode(',', $check) . '}';
          if (!empty($this->messages[$name])) {
            foreach ($this->messages[$name] as $key => $value) {
              $this->messages[$name][$key] = $key . ':"' . $value . '"';
            }
            $messages[] = $name . ':{' . implode(',', $this->messages[$name]) . '}';
          }
        }
      }
      $validate['rules'] = '{' . implode(',', $rules) . '}';
      if (!empty($messages)) $validate['messages'] = '{' . implode(',', $messages) . '}';
      foreach ($validate as $key => $value) $validate[$key] = $key . ':' . $value;
      $page->plugin('jQuery', 'code', '$("#' . $this->name . '").validate({' . implode(',', $validate) . '});');
    }
    if ($this->align == 'form-horizontal') $html .= '<div class="row"><div class="col-' . $this->size . '-12">';
    if (($flash = $ci->session->form_messenger) && $flash['form'] == $this->name) {
      $html .= ($flash['status'] == 'html') ? $flash['msg'] : $bp->alert($flash['status'], $flash['msg']);
    }
    if ($this->disabled) $page->link('<style type="text/css">#' . $this->name . ' { display:none; }</style>');
    if (!empty($this->warning)) $html .= $bp->alert('danger', $this->warning);
    $attributes = array_merge(array(
      'action' => ($this->type == 'get') ? $page->url() : $page->url('add', '', 'submitted', $this->name),
      'id' => $this->name,
      'class' => $this->align,
      'autocomplete' => 'off'
    ), $attributes);
    $attributes['method'] = $this->type; // This is not the time to be changing your mind here.
    $url = array_shift($attributes);
    $hidden = ($this->type == 'get')  ? $page->url('params', $url) : array();
    foreach ($hidden as $key => $value) {
      if (isset($_POST[$this->base($key)])) unset($hidden[$key]);
    }
    if (empty($this->upload)) {
      $header = $html . form_open($url, $attributes, $hidden);
    } else {
      $hidden['MAX_FILE_SIZE'] = $this->upload['size'];
      $header = $html . form_open_multipart($url, $attributes, $hidden);
    }
    return "\n  " . preg_replace("/\s+/S", " ", $header);
  }
  
  public function fieldset ($legend, $html='') {
    $args = func_get_args();
    $legend = array_shift($args);
    $html = array_shift($args);
    if (is_array($html)) $html = implode('', $html);
    if (!empty($args)) $html .= implode('', $args);
    return "\n\t<fieldset><legend>" . $legend . "</legend>" . $html . "\n\t</fieldset>";
  }
  
  public function field ($name, $field, $options=array()) {
    $html = '';
    #-- $prompt and Overrides --#
    $prompt = (isset($this->labels[$name])) ? $this->labels[$name] : $name;
    if (isset($options['label'])) $prompt = $options['label'];
    if (!empty($prompt)) {
      if (isset($this->prompt['prepend'])) {
        if (!$this->prompt['prepend']['required'] || isset($this->required[$name])) {
          $prompt = $this->prompt['prepend']['html'] . $prompt;
        }
      }
      if (isset($this->prompt['append'])) $prompt .= $this->prompt['append'];
      if (isset($this->info[$name])) {
        $prompt .= ' <span class="glyphicon glyphicon-question-sign" style="cursor:pointer;" title="' . html_escape($this->info[$name]) . '"></span>';
      }
    }
    if (isset($options['value'])) $this->values($name, $options['value']); // to establish or override the default
    unset($options['label'], $options['value']);
    #-- Form $field --#
    switch ($field) {
      case 'calendar':
      case 'checkbox':
      case 'file':
      case 'hidden':
      case 'password':
      case 'radio':
      case 'select':
      case 'tags':
      case 'text':
      case 'textarea':
        $this->multi($name, $options);
        if (isset($this->validate[$name]) && strpos($name, '[') !== false) {
          foreach ($this->validate[$name] as $validate => $param) {
            if (!isset($options["data-rule-{$validate}"])) $options["data-rule-{$validate}"] = $param;
          }
          if (!empty($this->messages[$name])) {
            foreach ($this->messages[$name] as $rule => $message) {
              if (!isset($options["data-msg-{$rule}"])) $options["data-msg-{$rule}"] = $message;
            }
          }
        }
        $field = $this->$field($name, $options);
        if ($prompt === false || func_get_arg(1) == 'hidden') return $field;
        break;
    }
    #-- Begin div.form-group --#
    $html = '<div class="form-group';
      #-- Manage Errors --#
      if (isset($this->errors[$this->multi($name)])) {
        $html .= ' has-error';
        $error = '<p class="validation help-block">' . $this->errors[$this->multi($name)] . '</p>';
      } else {
        $error = '<p class="validation help-block" style="display:none;"></p>';
      }
    $html .= '">'; // closing the first div tag
    #-- Alignments and Prompts --#
    $id = (isset($this->labels[$name])) ? $this->id($this->multi($name)) : '';
    if ($this->align == 'form-inline') {
      if (!empty($prompt)) $html .= '<label class="sr-only" for="' . $id . '">' . $prompt . '</label>';
      $html .= $error . $field;
    } elseif ($this->align == 'form-horizontal') {
      $alignment = 'col-' . $this->size . '-' . (12 - $this->indent);
      if (!empty($prompt)) {
        $html .= '<label class="control-label col-' . $this->size . '-' . $this->indent . $this->input . '" for="' . $id . '">' . $prompt . '</label>';
      } else {
        $alignment .= ' ' . 'col-' . $this->size . '-offset-' . $this->indent;
      }
      $html .= '<div class="' . $alignment . '">' . $error . $field . '</div>';
    } else { // collapse
      if (!empty($prompt)) $html .= '<label class="' . $this->input . '" for="' . $name . '">' . $prompt . '</label>';
      $html .= $error . $field;
    }
    #-- End div.form-group --#
    $html .= '</div>';
    return "\n\t" . $html;
  }
  
  public function submit ($submit='Submit', $reset='') {
    global $bp, $ci, $page;
    $html = '';
    if ($this->captcha) {
      $ci->load->helper('captcha');
      $captcha = create_captcha(array(
        'img_path' => $this->uri . 'uploads/',
        'img_url' => $this->url . 'uploads/',
        'font_path' => $this->uri . 'fonts/' . rand(1,5) . '.ttf',
        'img_width' => 150,
        'img_height' => 34,
        'word_length' => 8,
        'pool' => '2346789abcdefghjkmnpqrtuvwxyzABCDEFGHJKMNPQRTUVWXYZ'
      )); // image, time, and word
      $ci->session->captcha = $captcha['word'];
      $ci->session->mark_as_flash('captcha');
      $html .= $this->field('captcha', $bp->media(array(
        '<div id="captchaimage">' . $captcha['image'] . '</div>',
        form_input('captcha', '', 'class="form-control"')
      )) . '<span class="help-block">Please enter the characters as shown in the image above (case insensitive)</span>', array('label'=>false));
    }
    // never use name="submit" per: http://jqueryvalidation.org/reference/#developing-and-debugging-a-form
    $buttons = func_get_args();
    if (substr($submit, 0, 1) != '<') {
      $buttons[0] = '<button type="submit" class="btn btn-primary' . str_replace('input', 'btn', $this->input) . '" data-loading-text="Submitting...">' . $submit . '</button>';
    }
    if (isset($buttons[1]) && substr($reset, 0, 1) != '<') {
      $buttons[1] = '<button type="reset" class="btn btn-default' . str_replace('input', 'btn', $this->input) . '">' . $reset . '</button>';
    }
    if ($this->align == 'form-horizontal') {
      $html .= '<div class="form-group">';
        $alignment = 'col-' . $this->size . '-' . (12 - $this->indent) . ' ' . 'col-' . $this->size . '-offset-' . $this->indent;
        $html .= '<div class="' . $alignment . '">' . implode(' ', $buttons) . '</div>';
      $html .= '</div>';
    } else { // inline or collapse
      $html = implode(' ', $buttons);
    }
    return "\n\t" . $html;
  }

  public function close () {
    global $page;
    $html = '';
    if ($this->placeholders) {
      $page->plugin('CDN', 'link', 'jquery.placeholder/2.0.7/jquery.placeholder.min.js');
      $page->plugin('jQuery', 'code', '$("input, textarea").placeholder();');
    }
    if (!empty($this->process)) {
      $html .= '<input type="hidden" name="process[ids]" value="' . implode(',', array_keys($this->process)) . '" />';
      $html .= '<input type="hidden" name="process[fields]" value="' . implode(',', array_shift($this->process)) . '" />';
    }
    return $html . "\n  " . form_close(($this->align == 'form-horizontal') ? '</div></div>' : '');
  }
  
  #-- Private Form Methods --#
  
  private function calendar ($name, $options) {
    global $page;
    $page->plugin('CDN', 'links', array( // 1.3.0 does not preselect today if empty so...
      'bootstrap.datepicker-fork/1.2.0/css/datepicker.min.css',
      'bootstrap.datepicker-fork/1.2.0/js/bootstrap-datepicker.min.js'
    ));
    $page->plugin('jQuery', 'code', '$("#' . $this->id($this->multi($name)) . '").datepicker().on("changeDate", function(){ $(this).valid(); });');
    $date = $this->values($name);
    if (!empty($date)) $date = date('m/d/Y', strtotime($date));
    $options['name'] = $this->multi($name);
    $options['id'] = $this->id($this->multi($name));
    $options['value'] = $this->form_validation->set_value($this->multi($name), $date);
    return $this->pre_append('form_input', $options);
  }
  
  private function checkbox ($name, $options) {
    $boxes = array();
    $boxes[] = form_hidden($this->multi($name), '');
    $default = $this->values($name);
    foreach ($this->menu($name) as $value => $description) {
      $options['name'] = $this->multi($name);
      $options['value'] = $value;
      $options['checked'] = $this->form_validation->set_checkbox($this->multi($name), $value, (is_array($default)) ? in_array($value, $default) : $default == $value);
      if ($this->align == 'form-inline') {
        $boxes[] = '<label class="checkbox-inline' . $this->input . '">' . form_checkbox($options) . ' ' . $description . '</label>';
      } else {
        $boxes[] = '<div class="checkbox' . $this->input . '"><label>' . form_checkbox($options) . ' ' . $description . '</label></div>';
      }
    }
    return implode(' ', $boxes);
  }
  
  private function file ($name, $options) {
    global $bp, $page;
    $html = '';
    $id = $this->id($name);
    // 'bootstrap.filestyle/1.0.3/js/bootstrap-filestyle.min.js' - $(":file").filestyle();
    $page->plugin('CDN', 'links', array( // 9.9.0
      'jquery.fileupload/9.9.0/js/jquery.iframe-transport.js',
      'jquery.fileupload/9.9.0/js/jquery.fileupload.js'
    ));
    $page->link($this->url . 'js/blueimpFileUploader.js');
    $page->plugin('jQuery', array('ui'=>'1.10.4', 'code'=>'
      $("#' . $id . '").blueimpFileUploader({
        "size": ' . $this->upload['size'] . ',
        "limit": ' . $this->upload['limit'] . ',
        "accept": "' . implode('|', $this->upload['extensions']) . '"
      });
      $("#' . $id . 'Field").click(function(e){
        e.preventDefault();
        $("#' . $id . 'Field button").focus();
        $("#' . $id . '").click();
        return false;
      });
      $("#' . $id . 'Messages").sortable({items:"div[id^=' . $id . ']"});
      $("body").on("click", "#' . $id . 'Messages span[class*=glyphicon-trash]", function(){
        var upload = $(this).closest("div[id^=' . $id . ']");
        if (upload.hasClass("alert-success")) $("#' . $id . 'Upload").css("display", "block");
        upload.remove();
      });
    '));
    $data = array('name'=>'blueimp', 'id'=>$id);
    if (strpos($name, '[]')) $data['multiple'] = 'multiple';
    $data['style'] = 'display:none;';
    $html .= form_upload($data);
    #-- Upload Field --#
    $html .= '<div id="' . $id . 'Field" style="" title="Click to Upload">';
      $html .= '<div class="input-group">';
        $html .= '<input type="text" class="form-control' . $this->input . '">';
        $html .= '<span class="input-group-btn">';
          $html .= $bp->button('success' . $this->input, $bp->icon('folder-open') . '&nbsp;&nbsp;Choose File&nbsp;&hellip;');
        $html .= '</span>';
      $html  .= '</div>';
    $html .= '</div>';
    #-- Upload Messages --#
    $html .= '<div id="' . $id . 'Messages">';
      if (isset($this->vars[$this->base($name)])) $this->values($name, $this->vars[$this->base($name)]);
      if (isset($this->values[$name]) && is_array($this->values[$name])) {
        foreach ($this->values[$name] as $uri => $file) {
          $html .= '<div id="' . $id . preg_replace('/[^a-z0-9]/i', '', $file) . '" class="alert alert-success ' . $id . 'Upload" style="margin:10px 0 0; padding:8px;">' . $this->uploaded_file($uri, $file) . '</div>';
        }
      }
    $html .= '</div>';
    return $html;
  }
  
  private function hidden ($name, $options) {
    $options['name'] = $this->multi($name);
    $options['id'] = $this->id($this->multi($name));
    $options['value'] = $this->form_validation->set_value($this->multi($name), $this->values($name));
    return '<input type="hidden"' . _attributes_to_string($options) . ' />';
  }
  
  private function password ($name, $options) {
    $options['name'] = $this->multi($name);
    $options['id'] = $this->id($this->multi($name));
    return $this->pre_append('form_password', $options);
  }
  
  private function radio ($name, $options) {
    $radios = array();
    foreach ($this->menu($name) as $value => $description) {
      $options['name'] = $this->multi($name);
      $options['value'] = $value;
      $options['checked'] = $this->form_validation->set_radio($this->multi($name), $value, $this->values($name) == $value);
      if ($this->align == 'form-inline') {
        $radios[] = '<label class="radio-inline' . $this->input . '">' . form_radio($options) . ' ' . $description . '</label>';
      } else {
        $radios[] = '<div class="radio' . $this->input . '"><label>' . form_radio($options) . ' ' . $description . '</label></div>';
      }
    }
    return implode(' ', $radios);
  }
  
  private function select ($name, $options) {
    global $page;
    $select = $this->menu($name);
    if (isset($select['hier'])) { // a hierselect menu
      $hier = $select['hier'];
      unset($select['hier']);
      $json = array();
      $default = $this->values($hier); // only one value allowed ie. not a multiselect
      foreach ($select as $id => $values) {
        $menu = (isset($this->prepend[$name])) ? array($this->prepend[$name]) : array();
        foreach ($values as $key => $value) $menu[$key] = $value;
        $json[$id] = $menu;
        $value = $this->form_validation->set_select($this->multi($hier), $id, $id == $default);
        if (!empty($value)) $selected = $values;
      }
      $page->link($this->url . 'js/hierSelect.js');
      $page->plugin('jQuery', 'code', '$("#' . $this->id($this->multi($hier)) . '").hierSelect("#' . $this->id($this->multi($name)) . '", ' . json_encode($json) . ');');
      $select = (isset($selected)) ? $selected : array();
    }
    #-- Make the select $menu --#
    $menu = (isset($this->prepend[$name])) ? array($this->prepend[$name]) : array();
    foreach ($select as $key => $value) $menu[$key] = $value;
    #-- Extract the $selected values --#
    $selected = array();
    $values = $this->values($name);
    $values = (!empty($values)) ? array_flip((array) $values) : array(); // so that we don't get an array(''=>0)
    foreach ($this->multi_to_single($menu) as $key => $value) {
      if (!empty($key)) {
        $value = $this->form_validation->set_select($this->multi($name), $key, isset($values[$key]));
        if (!empty($value)) $selected[] = $key;
      }
    }
    #-- Establish the $options --#
    $options['id'] = $this->id($this->multi($name));
    if (strpos($this->multi($name), '[')) { // a multiselect menu (or not)
      if (isset($options['multiple']) && $options['multiple'] === false) {
        unset($options['multiple']);
      } else {
        $options['multiple'] = 'multiple';
        if (!isset($options['size'])) {
          $count = count($menu);
          foreach ($menu as $values) if (is_array($values)) $count += count($values);
          $options['size'] = min($count, 15);
        }
      }
    }
    #-- Create the $field --#
    if (isset($options['multiple'])) { // we are unable (or at least unwilling) to prepend and append data
      $options = $this->form_control_class($options);
      $field = form_dropdown($this->multi($name), $menu, $selected, $options);
    } else {
      list($prepend, $append, $options) = $this->pre_append('return', $options);
      $field = $prepend . form_dropdown($this->multi($name), $menu, array_shift($selected), $options) . $append;
    }
    return str_replace('value="0"', 'value=""', $field);
  }
  
  private function tags ($name, $options) {
    global $page;
    $page->plugin('CDN', 'links', array(
      'bootstrap.tagsinput/0.3.9/bootstrap-tagsinput.css',
      'bootstrap.tagsinput/0.3.9/bootstrap-tagsinput.min.js'
    ));
    $options[] = 'confirmKeys:[13,44,9]'; // shift, comma (not working?), tab - also: is tagClass screwed up, or what?
    $page->plugin('jQuery', 'code', '
      $("#' . $this->id($this->multi($name)) . '").tagsinput({' . implode(',', $options) . '});
      $("div.bootstrap-tagsinput").css("width", "100%");
    ');
    $value = $this->values($name);
    if (is_array($value)) $value = implode(',', $value);
    return form_input($this->multi($name), $this->form_validation->set_value($this->multi($name), $value), 'id="' . $this->id($this->multi($name)) . '"');
  }
  
  private function text ($name, $options) {
    $options['name'] = $this->multi($name);
    $options['id'] = $this->id($this->multi($name));
    $options['value'] = $this->form_validation->set_value($this->multi($name), $this->values($name));
    return $this->pre_append('form_input', $options);
  }
  
  private function textarea ($name, $options) {
    $options['name'] = $this->multi($name);
    $options['id'] = $this->id($this->multi($name));
    $options['value'] = $this->form_validation->set_value($this->multi($name), $this->values($name));
    return form_textarea($this->form_control_class($options));
  }
  
  private function pre_append ($field, $options) { // for prepending and appending to text inputs
    $prepend = (isset($options['prepend'])) ? $this->addon($options['prepend']) : '';
    $append = (isset($options['append'])) ? $this->addon($options['append']) : '';
    if (!empty($prepend) || !empty($append)) {
      $prepend = '<div class="input-group">' . $prepend;
      $append .= '</div>';
    }
    unset($options['prepend'], $options['append']);
    $options = $this->form_control_class($options);
    return ($field == 'return') ? array($prepend, $append, $options) : $prepend . $field($options) . $append;
  }
  
  private function addon ($html) { // used in $this->pre_append()
    if (is_array($html)) {
      foreach ($html as $key => $code) $html[$key] = $this->addon($code);
      return implode(' ', $html);
    }
    if (substr($html, 0, 9) == 'glyphicon') {
      return '<span class="input-group-addon' . $this->input . '"><span class="glyphicon ' . $html . '"></span></span>';
    } elseif (substr($html, 0, 7) == '<button') {
      return '<span class="input-group-btn' . $this->input . '">' . $html . '</span>';
    } elseif (substr($html, 0, 4) == 'icon') {
      return '<span class="input-group-addon' . $this->input . '"><span class="' . $html . '"></span></span>';
    } else {
      return '<span class="input-group-addon' . $this->input . '">' . $html . '</span>';
    }
  }
  
  private function form_control_class ($options) { // used to bootstrapify inputs
    if (isset($options['class'])) {
      $options['class'] .= ' form-control' . $this->input;
    } else {
      $options['class'] = 'form-control' . $this->input;
    }
    if (isset($options['placeholder'])) $this->placeholders = true;
    return $options;
  }
  
  private function multi_to_single ($array) {
    $single = array();
    if (isset($array['hier'])) unset($array['hier']);
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        foreach ($this->multi_to_single($value) as $key => $value) $single[$key] = $value;
      } else {
        $single[$key] = $value;
      }
    }
    return $single;
  }
  
  private function multi ($name, &$options=null) {
    static $names = array();
    if (is_null($options)) return (isset($names[$name])) ? $names[$name] : $name;
    if (!empty($this->process) && $split = strpos($name, '[')) {
      $base = substr($name, 0, $split);
      end($this->process);
      $id = key($this->process);
      if (!in_array($base, current($this->process))) {
        $names[$name] = $base . '[' . $id . ']';
        $this->process[$id][] = $base;
      } else {
        $names[$name] = $name;
      }
    }
  }
  
  private function base ($name) {
    return ($split = strpos($name, '[')) ? substr($name, 0, $split) : $name; 
  }
  
  private function uploaded_file ($uri, $name) {
    global $bp, $ci;
    $ci->load->library('resources');
    $name = preg_replace('/[^a-z0-9\.\-_]/i', '', $name);
    $url = $ci->resources->cache(str_replace(BASE, BASE_URL, $uri));
    $url = substr($url, 0, strrpos($url, '/') + 1) . $name;
    return $bp->media(array(
      '<span class="glyphicon glyphicon-ok"></span>',
      '<a class="alert-link" href="' . $url . '" target="preview">' . $name . '</a><input type="hidden" name="' . $this->upload['name'] . '" value="' . $url . '">',
      '<span class="glyphicon glyphicon-trash" title="Delete File" style="cursor:pointer;margin-right:0;"></span>'
    ));
  }
  
  private function uri_name ($url) {
    global $ci;
    if (strpos($url, BASE_URL) === false) return false;
    $url = str_replace(BASE_URL, '', $url);
    $name = substr($url, strpos($url, '/') + 1);
    $ci->load->library('resources');
    list($uris) = $ci->resources->file_paths($url);
    $uri = array_shift($uris);
    return array($uri, $name);
  }
  
  private function attempts ($after, $throttle, $limit, $name) {
    global $ci, $page;
    if (empty($after) && empty($limit)) {
      $this->captcha = true;
      return; // they may attempt to submit this form as much as they like (with captcha)
    }
    $this->log = $name;
    $this->db = $page->plugin('Database', 'sqlite', $this->uri . 'attempts/' . $page->get('domain') . '.db');
    if ($this->db->created) {
      $this->db->create('attempts', array(
        'form' => 'TEXT NOT NULL DEFAULT ""',
        'submitted' => 'INTEGER NOT NULL DEFAULT 0',
        'ip_address' => 'TEXT NOT NULL DEFAULT ""',
        'field' => 'TEXT NOT NULL DEFAULT ""'
      ), 'form, submitted, ip_address, field');
    }
    $name = $ci->input->post($name);
    if (!empty($name)) {
      $this->db->query('SELECT submitted FROM attempts WHERE form = ? AND submitted > ? AND (ip_address = ? OR field = ?) ORDER BY form, submitted ASC', array($this->name, time() - 604800, $ci->input->ip_address(), $name));
    } else {
      $this->db->query('SELECT submitted FROM attempts WHERE form = ? AND submitted > ? AND ip_address = ? ORDER BY form, submitted ASC', array($this->name, time() - 604800, $ci->input->ip_address()));
    }
    $attempts = array();
    while (list($time) = $this->db->fetch('row')) $attempts[] = $time;
    $total = count($attempts);
    if (!empty($limit) && $total >= $limit) {
      $this->disabled = true;
      $this->warning = "You have submitted this form more than {$limit} times.  You have been banned from any further attempts.";
      return; // this is all that we (and they) need to know
    }
    if (empty($after) || $total >= $after) $this->captcha = true;
    if (!$this->disabled && !empty($after) && !empty($throttle)) {
      $expired = time() - ($throttle * 60);
      foreach ($attempts as $key => $time) if ($expired > $time) unset($attempts[$key]);
      if (count($attempts) >= $after) {
        $this->disabled = true;
        $this->warning = "You may only submit this form {$after} times within any {$throttle} minute period.  This page will automatically reload itself when you may try again.";
        $reload = array_shift($attempts) - $expired + 3; // in seconds
        $page->link('<meta http-equiv="refresh" content="' . $reload . '">');
      }
    }
  }
  
  public function yes_no ($str) {
    return ( (is_numeric($str) && $str > 0) || (in_array(strtolower($str), array('y', 'yes', 'true'))) ) ? 'Y' : 'N';
  }
  
  public function true_false ($str) {
    return ( (is_numeric($str) && $str > 0) || (in_array(strtolower($str), array('y', 'yes', 'true'))) ) ? 1 : 0;
  }
  
  static function fileinfo ($uri) {
    if (!file_exists($uri)) return false;
    $name = basename($uri);
    if (false === ($ext = strrchr($name, '.'))) return false;
    $name = substr($name, 0, -strlen($ext));
    $file = array();
    $file['size'] = filesize($uri);
    $file['path'] = dirname($uri) . '/';
    $file['name'] = $name;
    $file['ext'] = $ext;
    $file['image'] = false;
    if (in_array($ext, array('.jpg', '.jpe', '.jpeg', '.pjpeg', '.gif', '.png', '.ico')) && false !== ($image = @getimagesize($uri))) {
      $file['width'] = array_shift($image);
      $file['height'] = array_shift($image);
      switch (array_shift($image)) {
        case 1: $file['image'] = '.gif'; break;
        case 2: $file['image'] = '.jpg'; break;
        case 3: $file['image'] = '.png'; break;
        case 17: $file['image'] = '.ico'; break;
      }
    }
    $file['type'] = ($file['image']) ? substr($file['image'], 1) : substr($file['ext'], 1);
    return $file;
  }
  
}

?>
