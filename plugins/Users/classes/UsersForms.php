<?php

class UsersForms extends UsersDatabase {

  private $email = '';
  private $website = '';
  private $update = false;
  
  public function __construct ($website, $update=false) {
    global $page;
    if (isset($_GET['email'])) $this->email = $page->plugin('Form_Validation', array('filter'=>array('email'=>$_GET['email'])));
    $this->website = $website;
    if (is_string($update) && function_exists($update)) $this->update = $update;
    parent::__construct();
  }
  
  public function forms () {
    global $page;
    $page->robots = false;
    $page->plugin('Form_Validation');
    $html = '<div id="userForms" style="max-width:500px; margin:40px auto;">';
      $uri = explode('/', $page->get('uri'));
      array_shift($uri); // 'users'
      $action = array_shift($uri);
      if ($action == 'logout') {
        $page->access('user');
        $uri = implode('/', $uri);
        $eject = (!empty($uri)) ? BASE_URL . $uri : BASE_URL . 'users/';
        $this->logout($eject);
      } elseif ($user_id = $this->activate($action)) {
        $page->access('others');
        $html .= $this->manage_account($user_id, BASE_URL . implode('/', $uri));
      } elseif (is_user()) {
        $page->access('user');
        $user_id = (is_admin() && isset($_GET['edit'])) ? $_GET['edit'] : $_SESSION['user_id'];
        $html .= $this->manage_account($user_id);
      } else {
        $page->access('others');
        $page->title = (!empty($this->website)) ? 'Sign In at ' . $this->website : 'Sign In';
        $html .= $this->user_tabs();
        $html .= '<div class="tab-content">';
          $html .= '<div id="sign_in_form" class="tab-pane fade">' . $this->sign_in_form() . '</div>';
          $html .= '<div id="register_form" class="tab-pane fade">' . $this->register_form() . '</div>';
          $html .= '<div id="reset_pass_form" class="tab-pane fade">' . $this->reset_pass_form() . '</div>';
        $html .= '</div>';
      }
    $html .= '</div>';
    $page->link('<style type="text/css">
      #userForms input[type="text"], #userForms input[type="password"] {
        font-size: 16px;
        height: auto;
        padding: 7px 9px;
      }
      #userTabs li.active {
        font-size: 18px;
      }
    </style>');
    return $html;
  }
  
  private function manage_account ($user_id, $success_url='') {
    global $page;
    if (is_user()) {
      $page->title = 'Edit Your Profile';
    } else {
      $page->title = 'Create A Password';
    }
    $html = '';
    $row = $this->db->row('SELECT code, name, email, password, approval, confirmed, admin FROM users WHERE id = ?', array($user_id));
    if (empty($row)) return $html;
    list($code, $name, $email, $password, $approval, $confirmed, $admin) = array_values($row);
    $form = new Form('manage_account');
    if (is_user()) {
      $form->required(array('name'));
    } else {
      $form->required(array('name', 'password', 'confirm'));
    }
    $form->values(array('email'=>$email, 'name'=>$name, 'approval'=>$approval, 'confirmed'=>$confirmed, 'admin'=>$admin));
    $form->placeholders(array('email'=>'Email Address', 'name'=>'Full Name', 'password'=>'New Password', 'confirm'=>'Confirm Password'));
    $form->check(array('name'=>'', 'password'=>'pass', 'confirm'=>'password', 'approval'=>'YN', 'confirmed'=>'YN', 'admin'=>'int', 'persistent'=>'YN'));
    list($vars, $errors, $eject) = $form->validate();
    if (!empty($vars) && empty($errors)) {
      $html .= '<pre>' . print_r($vars, true) . '</pre>';
      $update = array();
      $update['name'] = $vars['name'];
      if (!empty($vars['password'])) $update['password'] = sha1(sha1($vars['password'] . $code, true));
      if (is_admin()) {
        $update['admin'] = $vars['admin'];
        $update['approval'] = $vars['approval'];
        $update['confirmed'] = $vars['confirmed'];
      }
      $this->db->update('users', $update, 'id', $user_id);
      $this->update_external($user_id);
      if (!is_user()) {
        $cookie = ($vars['persistent'] == 'Y') ? true : false;
        $this->login($user_id, $cookie);
        $page->eject($success_url);
      }
      $form->message('success', 'Thank you. Your profile has been updated.');
      $page->eject($eject);
    }
    $form->align('horizontal', 'xs', 0);
    if (is_admin()) {
      $html .= $form->header('Edit Your Profile <span style="float:right;"><small><a href="' . BASE_URL . 'users/view/">View Users</a></span></small>');
    } elseif (is_user()) {
      $html .= $form->header('Edit Your Profile');
    } else {
      $html .= $form->header('Create A Password');
    }
    $html .= $form->field('text', 'name', '');
    $html .= $form->field('text', 'email', '', array('disabled'=>'disabled'));
    $html .= $form->field('password', 'password', '');
    $html .= $form->field('password', 'confirm', '');
    if (is_user()) {
      if (is_admin()) {
        $html .= '<b>Admin:</b>' . $form->field('text', 'admin', '', array('input'=>'col-sm-1', 'maxlength'=>2));
        $html .= $form->field('checkbox', 'approval', '', array('Y'=>'Approved'));
        $html .= $form->field('checkbox', 'confirmed', '', array('Y'=>'Confirmed'));
      }
      $html .= $form->buttons('Submit');
    } else {
      $html .= $form->field('checkbox', 'persistent', '', array('Y'=>'Remember Me'));
      $html .= $form->buttons('Sign In');
    }
    $html .= $form->close();
    unset ($form);
    return $html;
  }
  
  private function update_external ($user_id) {
    if ($this->update) {
      $info = $this->db->row('SELECT id, name, email, approval, confirmed, admin FROM users WHERE id = ?', array($user_id));
      call_user_func($this->update, $info);
    }
  }
  
  private function user_tabs () {
    global $page;
    $html = '';
    $html .= '<ul class="nav nav-tabs" id="userTabs" style="margin-bottom:15px;">';
      $html .= '<li><a href="#sign_in_form">Sign In</a></li>';
      $html .= '<li><a href="#register_form">Register</a></li>';
      $html .= '<li style="display:none;"><a href="#reset_pass_form">Forgot Password</a></li>';
    $html .= '</ul>';
    $tab = 0;
    if (isset($_SESSION['form-messenger']) && in_array($_SESSION['form-messenger']['form'], array('sign_in', 'register', 'reset_password'))) {
      switch ($_SESSION['form-messenger']['form']) {
        case 'register':
          $tab = ($_SESSION['form-messenger']['status'] == 'success') ? 0 : 2;
          break;
        case 'reset_password':
          $tab = ($_SESSION['form-messenger']['status'] == 'success') ? 0 : 1;
          break;
      }
      $html .= '<div class="alert alert-' . $_SESSION['form-messenger']['status'] . '">';
        $html .= '<button type="button" class="close" data-dismiss="alert">&times;</button>';
        $html .= $_SESSION['form-messenger']['msg'];
      $html .= '</div>';
      unset($_SESSION['form-messenger']);
    }
    $jquery = '
	$("#userTabs li:eq(' . $tab . ') a").tab("show");
	$("#userTabs a").click(function(e){ e.preventDefault(); $(this).tab("show"); });
	$("#resetPassLink").click(function(e){ e.preventDefault(); $("#userTabs li:eq(2) a").tab("show"); });
    ';
    $page->plugin('jQuery', array('code'=>$jquery));
    return $html;
  }
  
  private function sign_in_form () {
    global $page;
    $html = '';
    $form = new Form('sign_in');
    $form->required(array('email', 'password'));
    $form->values(array('email'=>$this->email));
    $form->placeholders(array('email'=>'Email Address', 'password'=>'Password'));
    $form->check(array('email'=>'email', 'password'=>'pass', 'persistent'=>'YN'));
    list($vars, $errors, $eject) = $form->validate();
    if (!empty($vars) && empty($errors)) {
      $code = $this->db->value('SELECT code FROM users WHERE email = ?', array($vars['email']));
      if ($code === false) {
        $form->message('danger', 'We don\'t seem to have an account for "' . $vars['email'] . '".<br />Would you like to register it?');
      } else {
        $user_id = $this->db->value('SELECT id FROM users WHERE email = ? AND password = ? AND approval = ?', array($vars['email'], sha1(sha1($vars['password'] . $code, true)), 'Y'));
        if ($user_id) {
          $this->update_external($user_id);
          $cookie = ($vars['persistent'] == 'Y') ? true : false;
          $this->login($user_id, $cookie);
          $page->eject(BASE_URL . $page->get('uri'));
        }
        $password = $this->db->value('SELECT password FROM users WHERE email = ?', array($vars['email']));
        if ($password == '') {
          $form->message('danger', 'The password for "' . $vars['email'] . '" has been reset.<br />Please check your inbox so that you can sign back into the site.');
        } else {
          $form->message('danger', 'The password for "' . $vars['email'] . '" was incorrect.<br />Please try again.');
        }
      }
      $eject = $page->url('add', $eject, 'email', $vars['email']);
      $page->eject($eject);
    }
    $form->align('horizontal', 'xs', 0);
    $html .= $form->header();
    $html .= $form->field('text', 'email', '');
    $html .= $form->field('password', 'password', '');
    $html .= $form->field('checkbox', 'persistent', '', array('Y'=>'Remember Me'));
    $html .= $form->buttons('Sign In', '<a href="#" id="resetPassLink" class="btn btn-link">Forgot Password?</a>');
    $html .= $form->close();
    unset ($form);
    return $html;
  }
  
  private function register_form () {
    global $page;
    $html = '';
    $form = new Form('register');
    $form->required(array('name', 'register_email'));
    $form->values(array('register_email'=>$this->email));
    $form->placeholders(array('register_email'=>'Email Address', 'name'=>'Full Name'));
    $form->check(array('name'=>'', 'register_email'=>'email'));
    list($vars, $errors, $eject) = $form->validate();
    if (!empty($vars) && empty($errors)) {
      list($new_user, $user_id) = $this->register($vars['name'], $vars['register_email']);
      if ($new_user) {
        $this->send_email($user_id);
        $form->message('success', 'Thank you for registering!<br />We have sent an email to "' . $vars['register_email'] . '".<br />Please check your inbox so that you can sign into the site.');
      } else {
        $form->message('danger', 'It seems you have already registered with us.<br />Would you like us to send you a "Password Reset" email?');
      }
      $eject = $page->url('add', $eject, 'email', $vars['register_email']);
      $page->eject($eject);
    }
    $form->align('horizontal', 'xs', 0);
    $html .= $form->header();
    $html .= $form->field('text', 'register_email', '');
    $html .= $form->field('text', 'name', '');
    $html .= $form->buttons('Register');
    $html .= $form->close();
    unset ($form);
    return $html;
  }
  
  private function reset_pass_form () {
    global $page;
    $html = '';
    $form = new Form('reset_password');
    $form->required(array('reset_email'));
    $form->values(array('reset_email'=>$this->email));
    $form->placeholders(array('reset_email'=>'Email Address'));
    $form->check(array('reset_email'=>'email'));
    list($vars, $errors, $eject) = $form->validate();
    if (!empty($vars) && empty($errors)) {
      if ($this->reset_password($vars['reset_email'])) {
        if ($this->db->value('SELECT id FROM users WHERE email = ? AND approval = ?', array($vars['reset_email'], 'Y'))) {
          $this->send_email($vars['reset_email']);
        } // else we are going to just lead them on in a discouraging loop
        $form->message('success', 'Thanks for coming back!<br />The password for "' . $vars['reset_email'] . '" has been reset.<br />Please check your inbox so that you can sign back into the site.');
      } else {
        $form->message('danger', 'We don\'t seem to have an account for "' . $vars['reset_email'] . '".<br />Would you like to register it?');
      }
      $eject = $page->url('add', $eject, 'email', $vars['reset_email']);
      $page->eject($eject);
    }
    $form->align('horizontal', 'xs', 0);
    $html .= $form->header();
    $html .= $form->field('text', 'reset_email', '');
    $html .= $form->buttons('Reset My Password');
    $html .= $form->close();
    unset ($form);
    return $html;
  }
  
  private function send_email ($user) {
    global $page;
    if (empty($this->website)) return false;
    $new_user = (is_numeric($user)) ? true : false;
    $key = ($new_user) ? 'id' : 'email';
    $this->db->query('SELECT id, code, name, email FROM users WHERE ' . $key . ' = ?', array($user));
    list($user_id, $code, $name, $email) = $this->db->fetch('row');
    $message = array();
    $message[] = 'Hello ' . $name . ',';
    if ($new_user) {
      $message[] = 'Welcome to ' . $this->website . '!';
      $message[] = 'Please click on the following link to create a password, and sign in to your account:';
    } else {
      $message[] = 'Your password at ' . $this->website . ' has been reset.';
      $message[] = 'Please click on the following link to establish a new password, and sign in to your account:';
    }
    $message[] = BASE_URL . 'users/' . $user_id . $code . '/';
    $message[] = 'Thank you!';
    $message = implode("\r\n\r\n", $message);
    if (ini_get('sendmail_from') != '') {
      $subject = ($new_user) ? 'Registration Confirmation' : 'Password Reset';
      $headers = array();
      $headers[] = 'From: ' . ini_get('sendmail_from');
      $headers[] = 'Reply-To: ' . ini_get('sendmail_from');
      $headers = implode("\r\n", $headers);
      mail("{$name} <{$email}>", $subject, $message, $headers);
    } else {
      trigger_error($message);
    }
  }

}

?>