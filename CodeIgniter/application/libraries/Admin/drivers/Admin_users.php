<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_users extends CI_Driver {

  private $url;
  
  public function view () {
    global $ci, $page;
    $html = '';
    $this->url = $page->get('url') . $page->uri('first', 2);
    switch ($page->uri('number', 3)) {
      case 'logout':
        $this->blog->auth->logout();
        $page->eject($this->url);
        break;
      case 'register':
        if (is_admin(1)) $html .= $this->register_user();
        break;
      case 'edit':
        if (is_admin(1)) $html .= $this->edit_user($ci->input->get('id'));
        break;
      case 'list':
        if (is_admin(1)) $html .= $this->list_users($ci->input->get('view'));
        break;
      default:
        if (is_admin(2)) {
          $html .= $this->edit_profile();
        } else {
          $html .= $this->sign_in();
        }
        break;
    }
    return $this->admin($html);
  }
  
  private function sign_in () {
    global $bp, $page;
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
      if ($id = $this->blog->auth->check($form->vars['email'], $form->vars['password'])) {
        if ($this->blog->auth->user_is_admin($id, 2)) {
          $form->reset_attempts();
          $this->blog->auth->login($id, ($form->vars['remember'] == 'Y') ? 30 : 1, 'single');
          $page->eject(ADMIN);
        }
        $form->errors['email'] = 'Only administrators may sign in here.';
      } elseif ($id = $this->blog->auth->check($form->vars['email'])) {
        $form->errors['password'] = 'The password entered was incorrect.';
      } else {
        $form->errors['email'] = 'The email address provided has not been registered.';
      }
    }
    $html .= $form->header();
    $html .= $form->fieldset('Sign In',
      $form->label_field('email', 'text', array('prepend'=>$bp->icon('envelope'))),
      $form->label_field('password', 'password', array('prepend'=>$bp->icon('lock'))),
      $form->label_field('remember', 'checkbox')
    );
    $html .= $form->submit('Sign In');
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
  private function edit_profile () {
    global $ci, $page;
    $page->title = 'Edit Your Profile at ' . $this->blog->get('name');
    $html = '';
    if (!$edit = is_user()) return $html;
    $form = $page->plugin('Form', 'name', 'edit_profile');
    $form->values($this->blog->auth->info($edit));
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
      if (!empty($update)) $this->blog->auth->update($edit, $update);
      $page->eject($form->eject);
    }
    $html .= $form->header();
    $html .= $form->fieldset('Edit Your Profile',
      $form->label('Email', '<p class="help-block">' . $form->values('email') . '</p>'),
      $form->label_field('name', 'text'),
      $form->label_field('password', 'password'),
      $form->label_field('confirm', 'password'),
      $form->submit('Edit Profile')
    );
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
  private function list_users ($view='') {
    global $bp, $ci, $page;
    $html = '';
    $page->title = 'View Users at ' . $this->blog->get('name');
    $total = $this->blog->auth->db->value('SELECT COUNT(*) FROM users');
    $query = $ci->db->query('SELECT COUNT(DISTINCT user_id) FROM ci_sessions WHERE last_activity > 0 AND user_id > 0');
    $active = ($row = $query->row_array()) ? array_shift($row) : 0;
    $admin = $this->blog->auth->db->value('SELECT COUNT(*) FROM users WHERE admin > 0');
    $groups = array();
    $this->blog->auth->db->query('SELECT g.id, g.name, COUNT(*) FROM user_groups AS u INNER JOIN groups AS g ON u.group_id = g.id GROUP BY u.group_id ORDER BY g.name ASC');
    while (list($id, $group, $count) = $this->blog->auth->db->fetch('row')) $groups[$id] = array('name'=>$group, 'count'=>$count);
    $links = array();
    $links['View Users ' . $bp->badge($total)] = $page->url('add', '', 'view', 'all');
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
    $list = $bp->listings();
    $list->display(100);
    $ids = array();
    if (isset($_GET['search'])) {
      $where = 'WHERE email LIKE ? OR name LIKE ?';
      $params = array('%' . $_GET['search'] . '%', '%' . $_GET['search'] . '%');
      if (!$list->count()) $list->count($this->blog->auth->db->value('SELECT COUNT(*) FROM users ' . $where, $params));
      $this->blog->auth->db->query('SELECT id FROM users ' . $where . ' ORDER BY id DESC' . $list->limit(), $params);
      while (list($id) = $this->blog->auth->db->fetch('row')) $ids[] = $id;
    } elseif ($view == 'active') {
      if (!$list->count()) $list->count($active);
      $query = $ci->db->query('SELECT user_id FROM ci_sessions WHERE last_activity > 0 AND user_id > 0 GROUP BY user_id ORDER BY last_activity DESC' . $list->limit());
      foreach ($query->result() as $row) $ids[] = $row->user_id;
    } elseif ($view == 'admin') {
      if (!$list->count()) $list->count($admin);
      $this->blog->auth->db->query('SELECT id FROM users WHERE admin > 0 ORDER BY id, admin ASC' . $list->limit());
      while (list($id) = $this->blog->auth->db->fetch('row')) $ids[] = $id;
    } elseif (is_numeric($view)) {
      if (isset($groups[$view])) {
        $form = $page->plugin('Form', 'name', 'edit_group');
        $form->validate('group', 'Group', 'required', 'The only thing you should edit here is capitalization.  If you rename this group to something else, then you will also need to manually change the hard-coded values that created this group in the first place, as all assigned users will be transferred over as well.');
        $form->values('group', $groups[$view]['name']);
        if ($form->submitted() && empty($form->errors)) {
          $group_id = $this->blog->auth->db->value('SELECT id FROM groups WHERE name = ?', array($form->vars['group']));
          $this->blog->auth->db->update('groups', 'id', array($group_id => array('name'=>$form->vars['group'])));
          if ($group_id != $view) {
            $html .= 'view: ' . $view;
            $users = $this->blog->auth->get_groups_users($view);
            $this->blog->auth->remove_from_group($users, $view);
            $this->blog->auth->add_to_group($users, $group_id);
            $form->eject = $page->url('add', $form->eject, 'view', $group_id);
          }
          $page->eject($form->eject);
        }
        $html .= $form->header();
        $html .= $form->label_field('group', 'text', array('append'=>$bp->button('warning', 'Edit', array('type'=>'submit', 'data-loading-text'=>'Submitting...'))));
        $html .= $form->close();
        unset($form);
        if (!$list->count()) $list->count($groups[$view]['count']);
        $this->blog->auth->db->query('SELECT user_id FROM user_groups WHERE user_id > ? AND group_id = ? ORDER BY user_id ASC', array(0, $view));
        while (list($id) = $this->blog->auth->db->fetch('row')) $ids[] = $id;
      }
    } else {
      if (!$list->count()) $list->count($total);
      $this->blog->auth->db->query('SELECT id FROM users ORDER BY id DESC' . $list->limit());
      while (list($id) = $this->blog->auth->db->fetch('row')) $ids[] = $id;
    }
    $tb = $bp->table('class=condensed striped');
    $tb->head();
    $tb->cell('', '&nbsp;');
    $tb->cell('', 'ID');
    $tb->cell('', 'Name');
    $tb->cell('', 'Email');
    $tb->cell('style=text-align:center;', 'Registered');
    $tb->cell('style=text-align:center; width:40px;', 'Admin');
    $tb->cell('style=text-align:center; width:60px;', 'Approved');
    $tb->cell('style=text-align:right; width:200px;', 'Last Activity');
    if (!empty($ids)) {
      $analytics = $ci->session->native->userdata('analytics');
      $info = $this->blog->auth->info($ids);
      foreach ($info as $id => $user) {
        $tb->row();
        $tb->cell('', '<a href="' . $page->url('add', $this->url . '/edit', 'id', $id) . '" title="Edit User">' . $bp->icon('pencil') . '</a>');
        $tb->cell('', $user['id']);
        $tb->cell('', $user['name']);
        $tb->cell('', $user['email']);
        $tb->cell('align=center', date('M d Y', $user['registered'] - $analytics['offset']));
        $tb->cell('align=center', $user['admin']);
        $tb->cell('align=center', $user['approved']);
        $tb->cell('align=right', ($user['last_activity'] > 0) ? '<span class="timeago" title="' . date('c', $user['last_activity']) . '"></span>' : '');
      }
    }
    $html .= $tb->close();
    $html .= '<div class="text-center">' . $list->pagination() . '</div>';
    $page->plugin('CDN', 'link', 'jquery.timeago/1.3.0/jquery.timeago.min.js');
    $page->plugin('jQuery', 'code', '$("span.timeago").timeago();');
    return $html;
  }
  
  private function edit_user ($user_id) {
    global $bp, $page;
    $page->title = 'Edit User at ' . $this->blog->get('name');
    $html = '';
    $edit = $this->blog->auth->info($user_id);
    if (empty($edit)) return '<h3>User Not Found</h3>';
    $form = $page->plugin('Form', 'name', 'edit_user');
    $form->values($edit);
    $form->menu('admin', range(0, 10));
    $form->menu('approved', array('Y'=>$edit['name'] . ' is authorized to sign in at ' . $this->blog->get('name')));
    $form->validate('name', 'Name', 'required');
    $form->validate('email', 'Email', 'required|email');
    $form->validate('password', 'Password', 'nowhitespace|minlength[6]');
    $form->validate('admin', 'Admin', 'inarray[menu]');
    $form->validate('groups', 'Groups');
    $form->validate('approved', 'Approved', 'YN');
    if ($form->submitted() && empty($form->errors)) {
      if ($edit['email'] != $form->vars['email'] && $this->blog->auth->check($form->vars['email'])) {
        $form->errors['email'] = 'Sorry, the email submitted has already been registered.';
      } else {
        if (empty($form->vars['password'])) unset($form->vars['password']);
        $this->blog->auth->update($user_id, $form->vars);
        $this->blog->auth->db->ci->simple_query('DELETE FROM user_groups WHERE user_id = ' . $user_id);
        $this->blog->auth->add_to_group($user_id, explode(',', $form->vars['groups']));
        $page->eject($form->eject);
      }
    }
    $html .= $form->header();
    $html .= $form->fieldset('Edit User',
      $form->label_field('name', 'text', array('prepend'=>$bp->icon('user'))),
      $form->label_field('email', 'text', array('prepend'=>$bp->icon('envelope'))),
      $form->label_field('password', 'text', array('prepend'=>$bp->icon('lock'), 'append'=>$this->blog->auth->random_password(), 'placeholder'=>'Leave empty to keep current password')),
      $form->label_field('admin', 'select'),
      $form->label_field('groups', 'tags'),
      $form->label_field('approved', 'checkbox')
    );
    $html .= $form->submit('Edit User');
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
  private function register_user () {
    global $bp, $page;
    $page->title = 'Register User at ' . $this->blog->get('name');
    $html = '';
    $form = $page->plugin('Form', 'name', 'edit_user'); // so that our status message show up when we eject to edit user
    $form->values('password', $this->blog->auth->random_password());
    $form->validate('name', 'Name', 'required');
    $form->validate('email', 'Email', 'required|email');
    $form->validate('password', 'Password', 'required|nowhitespace|minlength[6]');
    if ($form->submitted() && empty($form->errors)) {
      list($new_user, $user_id) = $this->blog->auth->register($form->vars['name'], $form->vars['email'], $form->vars['password']);
      if ($new_user) {
        $form->message('success', 'Thank you.  ' . $form->vars['name'] . ' at "' . $form->vars['email'] . '" has been registered as a new user.');
      } else {
        $form->message('warning', 'The email "' . $form->vars['email'] . '" has already been registered.');
      }
      $page->eject($page->url('add', $this->url . '/edit', 'id', $user_id));
    }
    $html .= $form->header();
    $html .= $form->fieldset('Register User',
      $form->label_field('name', 'text', array('prepend'=>$bp->icon('user'))),
      $form->label_field('email', 'text', array('prepend'=>$bp->icon('envelope'))),
      $form->label_field('password', 'text', array('prepend'=>$bp->icon('lock')))
    );
    $html .= $form->submit('Register User');
    $html .= $form->close();
    unset($form);
    return $html;
  }
  
}

/* End of file Admin_users.php */
/* Location: ./application/libraries/Admin/drivers/Admin_users.php */