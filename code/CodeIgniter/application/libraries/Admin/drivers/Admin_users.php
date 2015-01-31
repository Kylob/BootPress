<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_users extends CI_Driver {

  private $url;
  private $view;
  
  public function view ($params) {
    global $ci, $page;
    $ci->load->driver('auth');
    $this->url = BASE_URL . ADMIN . '/users';
    $this->view = (isset($params['action'])) ? $params['action'] : false;
    switch ($this->view) {
      case 'logout':
        $ci->auth->logout();
        $page->eject($this->url);
        break;
      case 'register':
        if (is_admin(1)) $html = $this->register_user();
        break;
      case 'edit':
        if (is_admin(1)) $html = $this->edit_user($ci->input->get('id'));
        break;
      case 'list':
        if (is_admin(1)) $html = $this->list_users($ci->input->get('view'));
        break;
      default:
        if (is_admin(2)) {
          $html = $this->edit_profile();
        } else {
          $html = $this->sign_in();
        }
        break;
    }
    return $this->display($html);
  }
  
  private function sign_in () {
    global $bp, $ci, $page;
    $page->title = 'Sign In for Admin Users';
    $html = '';
    $form = $page->plugin('Form', 'name', 'admin_sign_in');
    $form->captcha(3, 2, 50, 'email');
    $form->values('remember', 'N');
    $form->menu('remember', array('Y'=>'Keep me signed in at this computer for 30 days'));
    $form->validate('email', 'Email', 'required|email');
    $form->validate('password', 'Password', 'required|nowhitespace|minlength[6]');
    $form->validate('remember', '', 'YN');
    if ($form->submitted() && empty($form->errors)) {
      if ($id = $ci->auth->check($form->vars['email'], $form->vars['password'])) {
        if ($ci->auth->user_is_admin($id, 2)) {
          $form->reset_attempts();
          $ci->auth->login($id, ($form->vars['remember'] == 'Y') ? 30 : 1, 'single');
          $page->eject(ADMIN);
        }
        $form->errors['email'] = 'Only administrators may sign in here.';
      } elseif ($id = $ci->auth->check($form->vars['email'])) {
        $form->errors['password'] = 'The password entered was incorrect.';
      } else {
        $form->errors['email'] = 'The email address provided has not been registered.';
      }
    }
    $html .= $form->header();
    $html .= $form->fieldset($bp->icon('user') . ' Sign In',
      $form->field('email', 'text', array('prepend'=>$bp->icon('envelope'))),
      $form->field('password', 'password', array('prepend'=>$bp->icon('lock'))),
      $form->field('remember', 'checkbox')
    );
    $html .= $form->submit('Sign In');
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
  private function edit_profile () {
    global $bp, $ci, $page;
    $page->title = 'Edit Your Profile at ' . $ci->blog->name;
    $html = '';
    if (!$edit = is_user()) return $html;
    $form = $page->plugin('Form', 'name', 'edit_profile');
    $form->values($ci->auth->info($edit));
    $form->validate('name', 'Name', 'required');
    $form->validate('password', 'Password', 'nowhitespace|minlength[6]');
    $form->validate('confirm', 'Confirm', 'matches[password]');
    if ($form->submitted() && empty($form->errors)) {
      $update = array();
      if (!empty($form->vars['password'])) {
        $form->message('success', 'Thank you. The password has been updated.');
        $update['password'] = $form->vars['password'];
      }
      if (!empty($form->vars['name']) && $form->vars['name'] != $ci->session->cookie->userdata('name')) {
        $ci->session->cookie->set_userdata('name', $form->vars['name']);
        $update['name'] = $form->vars['name'];
      }
      if (!empty($update)) $ci->auth->update($edit, $update);
      $page->eject($form->eject);
    }
    $html .= $form->header();
    $html .= $form->fieldset($bp->icon('user') . ' Edit Your Profile',
      $form->field('Email', '<p class="help-block">' . $form->values('email') . '</p>'),
      $form->field('name', 'text'),
      $form->field('password', 'password'),
      $form->field('confirm', 'password'),
      $form->submit('Edit Profile')
    );
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
  private function list_users ($view='') {
    global $bp, $ci, $page;
    $html = '';
    $page->title = 'View Users at ' . $ci->blog->name;
    $total = $ci->auth->db->value('SELECT COUNT(*) FROM ci_users');
    $query = $ci->auth->db->query('SELECT COUNT(DISTINCT user_id) FROM ci_sessions WHERE last_activity > 0 AND user_id > 0');
    $active = ($row = $query->row_array()) ? array_shift($row) : 0;
    $admin = $ci->auth->db->value('SELECT COUNT(*) FROM ci_users WHERE admin > 0');
    $groups = array();
    $ci->auth->db->query('SELECT g.id, g.name, COUNT(*) FROM ci_user_groups AS u INNER JOIN ci_groups AS g ON u.group_id = g.id GROUP BY u.group_id ORDER BY g.name ASC');
    while (list($id, $group, $count) = $ci->auth->db->fetch('row')) $groups[$id] = array('name'=>$group, 'count'=>$count);
    $links = array();
    $links[$bp->icon('user') . ' View Users ' . $bp->badge($total)] = $page->url('add', '', 'view', 'all');
    $links['Active ' . $bp->badge($active)] = $page->url('add', '', 'view', 'active');
    $links['Admin ' . $bp->badge($admin)] = $page->url('add', '', 'view', 'admin');
    if (!empty($groups)) {
      foreach ($groups as $id => $group) {
        $links['Groups'][$group['name'] . ' ' . $bp->badge($group['count'])] = $page->url('add', '', 'view', $id);
      }
    }
    $html .= $bp->row('sm', array(
      $bp->col(8, $bp->pills($links, array('align'=>'horizontal', 'active'=>$page->url()))),
      $bp->col(4, $bp->search($page->url()))
    )) . '<br>';
    $bp->listings->display(100);
    $ids = array();
    if (isset($_GET['search'])) {
      $where = 'WHERE email LIKE ? OR name LIKE ?';
      $params = array('%' . $_GET['search'] . '%', '%' . $_GET['search'] . '%');
      if (!$bp->listings->set) $bp->listings->count($ci->auth->db->value('SELECT COUNT(*) FROM ci_users ' . $where, $params));
      $ci->auth->db->query('SELECT id FROM ci_users ' . $where . ' ORDER BY id DESC' . $bp->listings->limit(), $params);
      while (list($id) = $ci->auth->db->fetch('row')) $ids[] = $id;
    } elseif ($view == 'active') {
      if (!$bp->listings->set) $bp->listings->count($active);
      $query = $ci->auth->db->query('SELECT user_id FROM ci_sessions WHERE last_activity > 0 AND user_id > 0 GROUP BY user_id ORDER BY last_activity DESC' . $bp->listings->limit());
      foreach ($query->result() as $row) $ids[] = $row->user_id;
    } elseif ($view == 'admin') {
      if (!$bp->listings->set) $bp->listings->count($admin);
      $ci->auth->db->query('SELECT id FROM ci_users WHERE admin > 0 ORDER BY id, admin ASC' . $bp->listings->limit());
      while (list($id) = $ci->auth->db->fetch('row')) $ids[] = $id;
    } elseif (is_numeric($view)) {
      if (isset($groups[$view])) {
        $form = $page->plugin('Form', 'name', 'edit_group');
        $form->validate('group', 'Group', 'required', 'The only thing you should edit here is capitalization.  If you rename this group to something else, then you will also need to manually change the hard-coded values that created this group in the first place, as all assigned users will be transferred over as well.');
        $form->values('group', $groups[$view]['name']);
        if ($form->submitted() && empty($form->errors)) {
          $group_id = $ci->auth->db->value('SELECT id FROM ci_groups WHERE name = ?', array($form->vars['group']));
          $ci->auth->db->update('ci_groups', 'id', array($group_id => array('name'=>$form->vars['group'])));
          if ($group_id != $view) {
            $html .= 'view: ' . $view;
            $users = $ci->auth->get_groups_users($view);
            $ci->auth->remove_from_group($users, $view);
            $ci->auth->add_to_group($users, $group_id);
            $form->eject = $page->url('add', $form->eject, 'view', $group_id);
          }
          $page->eject($form->eject);
        }
        $html .= $form->header();
        $html .= $form->field('group', 'text', array('append'=>$bp->button('warning', 'Edit', array('type'=>'submit', 'data-loading-text'=>'Submitting...'))));
        $html .= $form->close();
        unset($form);
        if (!$bp->listings->set) $bp->listings->count($groups[$view]['count']);
        $ci->auth->db->query('SELECT user_id FROM ci_user_groups WHERE user_id > ? AND group_id = ? ORDER BY user_id ASC', array(0, $view));
        while (list($id) = $ci->auth->db->fetch('row')) $ids[] = $id;
      }
    } else {
      if (!$bp->listings->set) $bp->listings->count($total);
      $ci->auth->db->query('SELECT id FROM ci_users ORDER BY id DESC' . $bp->listings->limit());
      while (list($id) = $ci->auth->db->fetch('row')) $ids[] = $id;
    }
    $html .= $bp->table->open('class=condensed striped');
    $html .= $bp->table->head();
    $html .= $bp->table->cell('', '&nbsp;');
    $html .= $bp->table->cell('', 'ID');
    $html .= $bp->table->cell('', 'Name');
    $html .= $bp->table->cell('', 'Email');
    $html .= $bp->table->cell('style=text-align:center;', 'Registered');
    $html .= $bp->table->cell('style=text-align:center; width:40px;', 'Admin');
    $html .= $bp->table->cell('style=text-align:center; width:60px;', 'Approved');
    $html .= $bp->table->cell('style=text-align:right; width:200px;', 'Last Activity');
    if (!empty($ids)) {
      $analytics = $ci->session->native->userdata('analytics');
      $info = $ci->auth->info($ids);
      foreach ($info as $id => $user) {
        $html .= $bp->table->row();
        $html .= $bp->table->cell('', '<a href="' . $page->url('add', $this->url . '/edit', 'id', $id) . '" title="Edit User">' . $bp->icon('pencil') . '</a>');
        $html .= $bp->table->cell('', $user['id']);
        $html .= $bp->table->cell('', $user['name']);
        $html .= $bp->table->cell('', $user['email']);
        $html .= $bp->table->cell('align=center', date('M d Y', $user['registered'] - $analytics['offset']));
        $html .= $bp->table->cell('align=center', $user['admin']);
        $html .= $bp->table->cell('align=center', $user['approved']);
        $html .= $bp->table->cell('align=right', ($user['last_activity'] > 0) ? '<span class="timeago" title="' . date('c', $user['last_activity']) . '"></span>' : '');
      }
    }
    $html .= $bp->table->close();
    $html .= '<div class="text-center">' . $bp->listings->pagination() . '</div>';
    $page->plugin('CDN', 'link', 'jquery.timeago/1.3.0/jquery.timeago.min.js');
    $page->plugin('jQuery', 'code', '$("span.timeago").timeago();');
    return $html;
  }
  
  private function edit_user ($user_id) {
    global $bp, $ci, $page;
    $page->title = 'Edit User at ' . $ci->blog->name;
    $html = '';
    $edit = $ci->auth->info($user_id);
    if (empty($edit)) return '<h3>User Not Found</h3>';
    $form = $page->plugin('Form', 'name', 'edit_user');
    $form->values($edit);
    $form->menu('admin', range(0, 10));
    $form->menu('approved', array('Y'=>$edit['name'] . ' is authorized to sign in at ' . $ci->blog->name));
    $form->validate('name', 'Name', 'required');
    $form->validate('email', 'Email', 'required|email');
    $form->validate('password', 'Password', 'nowhitespace|minlength[6]');
    $form->validate('admin', 'Admin', 'inarray[menu]');
    $form->validate('groups', 'Groups');
    $form->validate('approved', 'Approved', 'YN');
    if ($form->submitted() && empty($form->errors)) {
      if ($edit['email'] != $form->vars['email'] && $ci->auth->check($form->vars['email'])) {
        $form->errors['email'] = 'Sorry, the email submitted has already been registered.';
      } else {
        if (empty($form->vars['password'])) unset($form->vars['password']);
        $ci->auth->update($user_id, $form->vars);
        $ci->auth->db->ci->simple_query('DELETE FROM ci_user_groups WHERE user_id = ' . $user_id);
        $ci->auth->add_to_group($user_id, explode(',', $form->vars['groups']));
        $page->eject($form->eject);
      }
    }
    $html .= $form->header();
    $html .= $form->fieldset($bp->icon('user') . ' Edit User',
      $form->field('name', 'text', array('prepend'=>$bp->icon('user'))),
      $form->field('email', 'text', array('prepend'=>$bp->icon('envelope'))),
      $form->field('password', 'text', array('prepend'=>$bp->icon('lock'), 'append'=>$ci->auth->random_password(), 'placeholder'=>'Leave empty to keep current password')),
      $form->field('admin', 'select'),
      $form->field('groups', 'tags'),
      $form->field('approved', 'checkbox')
    );
    $html .= $form->submit('Edit User');
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
  private function register_user () {
    global $bp, $ci, $page;
    $page->title = 'Register User at ' . $ci->blog->name;
    $html = '';
    $form = $page->plugin('Form', 'name', 'edit_user'); // so that our status message show up when we eject to edit user
    $form->values('password', $ci->auth->random_password());
    $form->validate('name', 'Name', 'required');
    $form->validate('email', 'Email', 'required|email');
    $form->validate('password', 'Password', 'required|nowhitespace|minlength[6]');
    if ($form->submitted() && empty($form->errors)) {
      list($new_user, $user_id) = $ci->auth->register($form->vars['name'], $form->vars['email'], $form->vars['password']);
      if ($new_user) {
        $form->message('success', 'Thank you.  ' . $form->vars['name'] . ' at "' . $form->vars['email'] . '" has been registered as a new user.');
      } else {
        $form->message('warning', 'The email "' . $form->vars['email'] . '" has already been registered.');
      }
      $page->eject($page->url('add', $this->url . '/edit', 'id', $user_id));
    }
    $html .= $form->header();
    $html .= $form->fieldset($bp->icon('user') . ' Register User',
      $form->field('name', 'text', array('prepend'=>$bp->icon('user'))),
      $form->field('email', 'text', array('prepend'=>$bp->icon('envelope'))),
      $form->field('password', 'text', array('prepend'=>$bp->icon('lock')))
    );
    $html .= $form->submit('Register User');
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
}

/* End of file Admin_users.php */
/* Location: ./application/libraries/Admin/drivers/Admin_users.php */