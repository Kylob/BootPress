<?php

namespace BootPress\Admin\Pages;

use BootPress\Admin\Component as Admin;
use phpUri;

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
        return $html;
    }
    
    private static function signIn()
    {
        extract(Admin::params('bp', 'page', 'auth', 'path'));
        $page->title = 'Sign In for Admin Users';
        $html = '';
        $form = $bp->form('admin_sign_in');
        $form->values['remember'] = 'N';
        $form->menu('remember', array('Y'=>'Keep me signed in at this computer for 30 days'));
        $form->validator->set(array(
            'email' => 'required|email',
            'password' => 'required|noWhiteSpace|minLength[6]',
            'remember' => 'yesNo',
        ));
        if ($vars = $form->validator->certified()) {
            if ($id = $auth->check($vars['email'], $vars['password'])) {
                $user = $auth->info($id);
                if (in_array($user['admin'], array(1,2))) {
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
        ));
    }
    
    private static function listUsers()
    {
        extract(Admin::params('bp', 'page', 'auth', 'website'));
        $page->title = 'View Users at '.$website;
        $html = '';
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
        $form->menu('admin', range(0,10));
        $form->menu('approved', array('Y'=>$edit['name'].' is authorized to sign in at '.$website));
        $form->validator->set(array(
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'noWhiteSpace|minLength[6]',
            'admin' => 'digits|range[0,10]',
            'groups' => '',
            'approved' => 'yesNo',
        ));
        if ($vars = $form->validator->certified()) {
            if ($edit['email'] != $vars['email'] && $auth->check($vars['email'])) {
                $form->validator->errors['email'] = 'Sorry, the email submitted has already been registered.';
            } else {
                $update = $vars;
                unset($update['groups']);
                $auth->update($user_id, $update);
                $auth->db->exec('DELETE FROM user_groups WHERE user_id = ?', $user_id);
                $auth->addToGroup($user_id, explode(',', $vars['groups']));
                $page->eject($form->eject);
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
        ), $form->group($bp->icon('lock'), $auth->randomPassword(), $form->text('password', array('placeholder'=>'Leave empty to keep current password'))));
        $html .= $form->field(array('Admin',
            'Level 1 (like you) has complete access, and level 2 will have limited access to this Admin section.',
        ), $form->select('admin'));
        $html .= $form->field(array('Groups',
            'To further segregate your users into stereotypes.',
        ), $form->text('groups'));
        $html .= $form->field(array('Approved',
            'Uncheck if you never want them to log in again.',
        ), $form->checkbox('approved'));
        $html .= $form->submit('Edit User');
        $html .= $form->close();
        return Admin::box('default', array(
            'head with-border' => static::$icon.'Edit User',
            'body' => $html,
        ));
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
}
