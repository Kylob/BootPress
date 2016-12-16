<?php

namespace BootPress\Admin\Pages;

use BootPress\Admin\Component as Admin;

class Users
{
    private static $icon;

    public static function setup($auth, $path)
    {
        extract(Admin::params('bp', 'page'));
        static::$icon = $bp->icon('user', 'glyphicon', 'span style="margin-right:10px;"').' ';
        if ($user = $auth->http()) {
            $menu = array(static::$icon.$user => array(
                'Logout' => $page->url('admin', $path),
                'View Users' => $page->url('admin', $path, 'list').'?view=all',
                'Register User' => $page->url('admin', $path, 'register'),
            ));
            $links = array('', 'register', 'edit', 'list');
        } elseif ($auth->isAdmin(1)) {
            $menu = array(static::$icon.$auth->user('name') => array(
                'Edit Your Profile' => $page->url('admin', $path),
                'Register User' => $page->url('admin', $path, 'register'),
                'View Users' => $page->url('admin', $path, 'list').'?view=all',
                'Logout' => $page->url('admin', $path, 'logout'),
            ));
            $links = array('', 'logout', 'register', 'edit', 'list');
        } elseif ($auth->isAdmin(2)) {
            $menu = array(static::$icon.$auth->user('name') => array(
                'Edit Your Profile' => $page->url('admin', $path),
                'Logout' => $page->url('admin', $path, 'logout'),
            ));
            $links = array('', 'logout');
        } else {
            $menu = array(static::$icon.'Sign In' => $page->url('admin', $path));
            $links = null;
        }
        $page->navbar = '<div class="navbar-custom-menu">'.$bp->navbar->menu($menu).'</div>';

        return $links;
    }

    public static function page()
    {
        extract(Admin::params('bp', 'page', 'auth', 'path', 'method'));
        switch ($method) {
            case 'logout':
                $auth->logout();
                $page->session->remove('enable_debugbar');
                $page->eject($page->url('admin', $path));
                break;
            case 'register':
                $html = static::registerUser();
                break;
            case 'edit':
                $html = static::editUser($page->get('id'));
                break;
            case 'list':
                $html = static::listUsers($page->get('view'));
                break;
            default:
                if ($page->get('basic') == 'authentication') {
                    if ($auth->http()) {
                        $page->eject($page->url('admin', 'blog'));
                    }
                    $auth->realm('BPAdmin'); // to sign in
                } elseif ($auth->http()) {
                    $auth->realm('BPAdmin'); // to logout
                } elseif ($auth->isAdmin(2)) {
                    $html = static::editProfile($auth->isUser());
                } else {
                    $html = static::signIn();
                }
                break;
        }
        $page->link('https://cdn.jsdelivr.net/jquery.timeago/1.3.0/jquery.timeago.min.js');
        $page->jquery('$.timeago.settings.allowFuture = true; $("span.timeago").timeago();');

        return $html;
    }

    private static function signIn()
    {
        extract(Admin::params('bp', 'page', 'auth', 'path'));
        $page->title = 'Sign In for Admin Users';
        $html = '';
        $form = $bp->form('admin_sign_in');
        $form->values['remember'] = 'N';
        $form->menu('remember', array('Y' => 'Keep me signed in at this computer for 30 days'));
        $form->validator->set(array(
            'email' => 'required|email',
            'password' => 'required|noWhiteSpace|minLength[6]',
            'remember' => 'yesNo',
        ));
        if ($vars = $form->validator->certified()) {
            if ($id = $auth->check($vars['email'], $vars['password'])) {
                $user = $auth->info($id);
                if (in_array($user['admin'], array(1, 2))) {
                    $auth->login($id, ($vars['remember'] == 'Y') ? 30 : 1);
                    $page->eject($page->url('admin', 'blog'));
                }
                $form->validator->errors['email'] = 'Only administrators may sign in here.';
            } elseif ($id = $auth->check($vars['email'])) {
                $form->validator->errors['password'] = 'The password entered was incorrect.';
            } else {
                $form->validator->errors['email'] = 'The email address provided has not been registered.';
            }
        }
        $html .= $form->header();
        $html .= $form->field(array('Email',
            'Please enter your email address.',
        ), $form->group($bp->icon('envelope'), '', $form->text('email')));
        $html .= $form->field(array('Passord',
            'Please enter your password.',
        ), $form->group($bp->icon('lock'), '', $form->password('password')));
        $html .= $form->field(null, $form->checkbox('remember'));
        $html .= $form->submit('Sign In');
        $html .= $form->close();

        return Admin::box('default', array(
            'head with-border' => static::$icon.'Sign In',
            'body' => $html,
        ));
    }

    private static function editProfile($user_id)
    {
        extract(Admin::params('bp', 'page', 'auth', 'website'));
        $page->title = 'Edit Your Profile at '.$website;
        $html = '';
        $form = $bp->form('edit_profile');
        $edit = $auth->info($user_id);
        $form->values = $edit;
        $form->validator->set(array(
            'name' => 'required',
            'password' => 'noWhiteSpace|minLength[6]',
            'confirm' => 'matches[password]',
        ));
        if ($vars = $form->validator->certified()) {
            $update = array();
            if (!empty($vars['password'])) {
                $form->message('info', 'Thank you.  Your password has been updated.');
                $update['password'] = $vars['password'];
            }
            if (!empty($vars['name'])) {
                $update['name'] = $vars['name'];
            }
            if (!empty($update)) {
                $auth->update($user_id, $update);
            }
            $page->eject($form->eject);
        }
        $html .= $form->header();
        $html .= $form->field(array('Email',
            'Your email address for signing into the site.',
        ), '<p>'.$edit['email'].'</p>');
        $html .= $form->field(array('Name',
            'Please enter your name.',
        ), $form->text('name'));
        $html .= $form->field(array('Password',
            'Please enter your desired password.',
        ), $form->password('password'));
        $html .= $form->field(array('Confirm',
            'Please confirm the password entered above.',
        ), $form->password('confirm'));
        $html .= $form->submit('Edit Profile');
        $html .= $form->close();

        return Admin::box('default', array(
            'head with-border' => static::$icon.'Edit Your Profile',
            'body' => $html,
        )).self::sessions($user_id);
    }

    private static function listUsers($view)
    {
        extract(Admin::params('bp', 'page', 'auth', 'website'));
        $page->title = 'View Users at '.$website;
        $html = '';

        // Count
        $count = array();
        $count['total'] = $auth->db->value('SELECT COUNT(*) FROM users');
        $count['active'] = $auth->db->value('SELECT COUNT(DISTINCT user_id) FROM user_sessions WHERE user_id > 0 AND last_activity > 0');
        $count['admin'] = $auth->db->value('SELECT COUNT(*) FROM users WHERE admin > 0');

        // Links
        $links = array();
        $url = $page->url('delete', '', '?');
        $links[$bp->icon('user').' View Users '.$bp->badge($count['total'])] = $page->url('add', $url, 'view', 'all');
        $links['Active '.$bp->badge($count['active'])] = $page->url('add', $url, 'view', 'active');
        $links['Admin '.$bp->badge($count['admin'])] = $page->url('add', $url, 'view', 'admin');

        // IDs
        $ids = array();
        $bp->pagination->set('page', 100);
        if ($user = $page->get('search')) {
            $where = ' WHERE email LIKE ? OR name LIKE ?';
            $params = array('%'.$user.'%', '%'.$user.'%');
            if (!$bp->pagination->set('page', 100)) {
                $bp->pagination->total($auth->db->value('SELECT COUNT(*) FROM users'.$where, $params));
            }
            $ids = $auth->db->ids(array(
                'SELECT id FROM users'.$where,
                'ORDER BY id DESC'.$bp->pagination->limit,
            ), $params);
        } elseif ($view == 'active') {
            if (!$bp->pagination->set('page', 100)) {
                $bp->pagination->total($count['active']);
            }
            $ids = $auth->db->ids(array(
                'SELECT user_id FROM user_sessions',
                'WHERE last_activity > 0 AND user_id > 0',
                'GROUP BY user_id ORDER BY last_activity DESC'.$bp->pagination->limit,
            ));
        } elseif ($view == 'admin') {
            if (!$bp->pagination->set('page', 100)) {
                $bp->pagination->total($count['admin']);
            }
            $ids = $auth->db->ids(array(
                'SELECT id FROM users',
                'WHERE admin > 0',
                'ORDER BY id, admin ASC'.$bp->pagination->limit,
            ));
        } else {
            if (!$bp->pagination->set('page', 100)) {
                $bp->pagination->total($count['total']);
            }
            $ids = $auth->db->ids(array(
                'SELECT id FROM users',
                'ORDER BY id DESC'.$bp->pagination->limit,
            ));
        }
        $html .= $bp->table->open('class=hover');
        $html .= $bp->table->head();
        $html .= $bp->table->cell('', 'ID');
        $html .= $bp->table->cell('', 'Name');
        $html .= $bp->table->cell('', 'Email');
        $html .= $bp->table->cell('style=text-align:center;', 'Registered');
        $html .= $bp->table->cell('style=text-align:center; width:40px;', 'Admin');
        $html .= $bp->table->cell('style=text-align:center; width:60px;', 'Approved');
        $html .= $bp->table->cell('style=text-align:right;', 'Last Activity');
        if (!empty($ids)) {
            $analytics = $page->session->get('analytics');
            foreach ($auth->info($ids) as $id => $user) {
                $html .= $bp->table->row();
                $html .= $bp->table->cell('', $bp->button('xs warning', $bp->icon('pencil').' '.$user['id'], array('href' => $page->url('admin', 'users/edit?id='.$id), 'title' => 'Edit User')));
                $html .= $bp->table->cell('', $user['name']);
                $html .= $bp->table->cell('', $user['email']);
                $html .= $bp->table->cell('align=center', date('M d Y', $user['registered'] - $analytics['offset']));
                $html .= $bp->table->cell('align=center', $user['admin']);
                $html .= $bp->table->cell('align=center', $bp->label(($user['approved'] == 'Y' ? 'success' : 'danger'), $user['approved']));
                $html .= $bp->table->cell('align=right', ($user['last_activity'] > 0) ? '<span class="timeago" title="'.date('c', $user['last_activity']).'"></span>' : '');
            }
        }
        $html .= $bp->table->close();
        $bp->pagination->html('links', array(
            'wrapper' => '<ul class="pagination pagination-sm no-margin">{{ value }}</ul>',
        ));

        return Admin::box('default', array(
            implode('', array(
                '<div class="box-header with-border no-padding">',
                    $bp->pills($links, array('align' => 'horizontal', 'active' => $page->url())),
                    '<div class="box-tools">',
                        '<div style="width:250px;">'.$bp->search($url, array('class' => 'form-collapse')).'</div>',
                    '</div>',
                '</div>',
            )),
            'body no-padding table-responsive' => $html,
            'foot clearfix' => $bp->pagination->links(),
        ));
    }

    private static function editUser($user_id)
    {
        extract(Admin::params('bp', 'page', 'auth', 'website'));
        $page->title = 'Edit User at '.$website;
        $html = '';
        if (!$edit = $auth->info($user_id)) {
            return '<h3>User Not Found</h3>';
        }
        $form = $bp->form('edit_user');
        $form->values = $edit;
        $form->menu('admin', range(0, 10));
        $form->menu('approved', array('Y' => $edit['name'].' is authorized to sign in at '.$website));
        $form->validator->set(array(
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'noWhiteSpace|minLength[6]',
            'admin' => 'digits|range[0,10]',
            'approved' => 'yesNo',
        ));
        if ($vars = $form->validator->certified()) {
            if ($edit['email'] != $vars['email'] && $auth->check($vars['email'])) {
                $form->validator->errors['email'] = 'Sorry, the email submitted has already been registered.';
            } else {
                if ($auth->isUser() == $user_id) {
                    unset($vars['admin'], $vars['approved']);
                }
                $auth->update($user_id, $vars);
                $form->eject();
            }
        }
        $html .= $form->header();
        $html .= $form->field(array('Name',
            'Your users name.',
        ), $form->group($bp->icon('user'), '', $form->text('name')));
        $html .= $form->field(array('Email',
            'Your users email address.',
        ), $form->group($bp->icon('envelope'), '', $form->text('email')));
        $html .= $form->field(array('Password',
            'Enter a new password if you want to change it.',
        ), $form->group($bp->icon('lock'), $auth->randomPassword(), $form->text('password', array('placeholder' => 'Leave empty to keep current password'))));

        $disabled = ($auth->isUser() == $user_id) ? array('disabled' => 'disabled') : array();

        $html .= $form->field(array('Admin',
            'Level 1 (like you) has complete access, and level 2 will have limited access to this Admin section.',
        ), $form->select('admin', $disabled));
        $html .= $form->field(array('Approved',
            'Uncheck if you never want them to log in again.',
        ), $form->checkbox('approved', $disabled));
        $html .= $form->submit('Edit User');
        $html .= $form->close();

        return Admin::box('default', array(
            'head with-border' => static::$icon.'Edit User',
            'body' => $html,
        )).self::sessions($user_id);
    }

    private static function registerUser()
    {
        extract(Admin::params('bp', 'page', 'auth', 'path', 'website'));
        $page->title = 'Register User at '.$website;
        $html = '';
        $form = $bp->form('register_user');
        $form->values['password'] = $auth->randomPassword();
        $form->validator->set(array(
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|noWhiteSpace|minLength[6]',
        ));
        if ($vars = $form->validator->certified()) {
            list($new_user, $user_id) = $auth->register($vars['name'], $vars['email'], $vars['password']);
            if ($new_user) {
                $form->message('info', 'Thank you.  '.$vars['name'].' at "'.$vars['email'].'" has been registered as a new user.');
            } else {
                $form->message('warning', 'The email "'.$vars['email'].'" has already been registered.');
            }
            $page->eject($page->url('admin', $path, 'edit').'?id='.$user_id);
        }
        $html .= $form->header();
        $html .= $form->field(array('Name',
            'The name of your user.',
        ), $form->group($bp->icon('user'), '', $form->text('name')));
        $html .= $form->field(array('Email',
            'Your users email address.',
        ), $form->group($bp->icon('envelope'), '', $form->text('email')));
        $html .= $form->field(array('Password',
            'A random password to get them started.',
        ), $form->group($bp->icon('lock'), '', $form->text('password')));
        $html .= $form->submit('Register User');
        $html .= $form->close();

        return Admin::box('default', array(
            'head with-border' => static::$icon.'Register User',
            'body' => $html,
        ));
    }

    private static function sessions($user_id)
    {
        extract(Admin::params('bp', 'page', 'auth'));
        $html = '';
        if (!$bp->pagination->set('sessions', 10)) {
            $bp->pagination->total($auth->db->value('SELECT COUNT(*) FROM user_sessions WHERE user_id = ?', $user_id));
        }
        $bp->pagination->html('links', array(
            'wrapper' => '<ul class="pagination pagination-sm no-margin pull-right">{{ value }}</ul>',
        ));
        $logout = $page->get('logout');
        if ($result = $auth->db->query(array(
            'SELECT * FROM user_sessions WHERE user_id = ? ORDER BY user_id, adjourn DESC'.$bp->pagination->limit,
        ), $user_id, 'assoc')) {
            $offset = ($analytics = $page->session->get('analytics')) ? $analytics['offset'] : 0;
            $html .= $bp->table->open('class=table');
            $html .= $bp->table->head();
            $html .= $bp->table->cell('style=text-align:center; width:75px;', 'Current');
            $html .= $bp->table->cell('', 'Login');
            $html .= $bp->table->cell('', 'Expires');
            $html .= $bp->table->cell('', 'IP Address');
            $html .= $bp->table->cell('style=text-align:center; width:75px;', 'Logout');
            while ($row = $auth->db->fetch($result)) {
                $current = $row['adjourn'] > time();
                $login = strtotime($row['login']);
                $html .= $bp->table->row();
                $html .= $bp->table->cell('style=text-align:center;', $current ? $bp->icon('check text-green', 'fa') : '&nbsp;'); // Current
                $html .= $bp->table->cell('', date('D, j M Y @ g:i a', $login - $offset).' <span class="timeago" title="'.date('c', $login).'"></span>'); // Login
                $html .= $bp->table->cell('', '<span class="timeago" title="'.date('c', $row['adjourn']).'"></span>'); // Expires
                $html .= $bp->table->cell('', $row['ip_address']); // IP Address
                $html .= $bp->table->cell('style=text-align:center;', $current ? '<a href="'.$page->url('add', '', 'logout', $row['id']).'" title="Click to log user out of this session">'.$bp->icon('sign-out', 'fa').'</a>' : '&nbsp;'); // Logout
                if ($logout == $row['id']) {
                    $auth->db->exec('UPDATE user_sessions SET adjourn = last_activity WHERE id = ?', $row['id']);
                }
            }
            $html .= $bp->table->close();
            $auth->db->close($result);
        }
        if ($logout) {
            $page->eject($page->url('delete', '', 'logout'));
        }

        return Admin::box('default', array(
            'head with-border' => array(
                $bp->icon('laptop', 'fa').' Sessions',
                $bp->pagination->links(),
            ),
            'body no-padding table-responsive' => $html,
        ));
    }
}
