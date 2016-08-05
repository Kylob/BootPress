<?php

namespace BootPress\Auth;

use BootPress\Page\Component as Page;
use BootPress\SQLite\Component as SQLite;
use Symfony\Component\Yaml\Yaml;

/*
http://stackoverflow.com/questions/549/the-definitive-guide-to-form-based-website-authentication#477579
http://stackoverflow.com/questions/244882/what-is-the-best-way-to-implement-remember-me-for-a-website?lq=1
http://security.stackexchange.com/questions/63435/why-use-an-authentication-token-instead-of-the-username-password-per-request/63438#63438
    - Store token hash (a weak one is fine) in database, and original in cookie
https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
    - Query database on a unique id (and not the token) for speed, and to thwart a timing attack
Charles Miller - http://fishbowl.pastiche.org/2004/01/19/persistent_login_cookie_best_practice/)
    - Change token with each new session to give attacker a smaller window of opportunity to exploit a stolen cookie
    - If an id (username) and token do not match, then remove all of the users persistent logins
    - Do not allow access to sensitive actions on a cookie-based login, but only through typing a valid password
Barry Jaspan - http://jaspan.com/improved_persistent_login_cookie_best_practice)
    - If attacker steals cookie, he becomes the legitimate user (with an updated token), whilst the victim must login again
    - Deleting all sessions for a user submitting an invalid token (only) makes it too easy for an attacker to log everyone out
    - If a series (constant) token is submitted with an invalid (variable) token, then a theft has occurred
    - If anything is missing from the cookie, then just ignore it
http://security.stackexchange.com/questions/19676/token-based-authentication-securing-the-token#19677
    - In the Barry Jaspan improved plan, an attacker can fly under the radar if they delete the original cookie
*/

class Component
{
    
    /** @var object  This property gives you access to the SQLite3 (or custom) users database. */
    public $db;
    
    /** @var object  BootPress\Page\Component instance. */
    private $page;
    
    /** @var null|string  The HTTP Authenticated username or null. */
    private $basic;
    
    /** @var array  Password hashing options. */
    private $password;
    
    /** $var array  Information we user to sanity check with a cookie.  Has '**id**', '**series**', '**token**', '**time**', and '**user_agent**' keys. */
    private $session = array();
    
    /** var array  Stored information about a logged in user.  Has '**id**', '**name**', '**email**', '**admin**', and '**login**' keys. */
    private $user = array();

    /**
     * Establishes the authentication settings, and runs all of our security checks.  Authentication is either session-based, or via HTTP Basic Auth.  Session-based authentication relies on the user database.  HTTP Basic Auth can use either the database, an array, or a YAML file of usernames and passwords.  A user can be both HTTP and session authenticated as long as Basic Auth is not using the database.  This allows an administrator to be logged in as a regular user, yet still retain super admin privileges.
     * 
     * @param array $options  Allows you to customize the authorization settings.  You can set the:
     * 
     * - '**db**' - A custom BootPress\Database\Component instance.  The default is an SQLite Users.db we will automatically create.
     * - '**basic**' - Either ``null`` (the default) to use the users database, an ``array('username'=>'password', ...)`` of users, or a YAML file of username's and password's.
     * - '**password**' - '**algo**' and '**options**' for hashing passwords.
     */
    public function __construct(array $options = array()) {
        extract(array_merge(array(
            'db' => null,
            'basic' => null,
            'password' => array(),
        ), $options));
        $this->password = array_merge(array(
            'algo' => \PASSWORD_DEFAULT,
            'options' => array(),
        ), (array) $password);
        $this->page = Page::html();
        $this->db = ($db instanceof \BootPress\Database\Component) ? $db : null;
        if (is_null($this->db)) {
            $this->db = new SQLite($this->page->dir['page'].'Users.db');
            if ($this->db->created) {
                $this->db->create('user_group_names', array(
                    'id' => 'INTEGER PRIMARY KEY',
                    'name' => 'TEXT UNIQUE COLLATE NOCASE',
                ));
                $this->db->create('user_groups', array(
                    'user_id' => 'INTEGER NOT NULL DEFAULT 0',
                    'group_id' => 'INTEGER NOT NULL DEFAULT 0',
                ), array('unique' => 'user_id, group_id'));
                $this->db->create('user_sessions', array(
                    'id' => 'INTEGER PRIMARY KEY',
                    'user_id' => 'INTEGER NOT NULL DEFAULT 0',
                    'adjourn' => 'INTEGER NOT NULL DEFAULT 0', // 'last_activity' + 'relapse'
                    'relapse' => 'INTEGER NOT NULL DEFAULT 0', // unsigned integer
                    'last_activity' => 'INTEGER NOT NULL DEFAULT 0', // time()
                    'ip_address' => 'TEXT NOT NULL DEFAULT ""', // of last updated session
                    'user_agent' => 'TEXT NOT NULL DEFAULT ""', // up to 255 varchar constant
                    'series' => 'TEXT NOT NULL DEFAULT ""', // sha1(salt) constant
                    'token' => 'TEXT NOT NULL DEFAULT ""', // sha1(salt) updated
                    'login' => 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP', // original date('Y-m-d H:i:s')
                ), 'user_id, adjourn');
                $this->db->create('users', array(
                    'id' => 'INTEGER PRIMARY KEY',
                    'name' => 'TEXT NOT NULL DEFAULT ""',
                    'email' => 'TEXT UNIQUE COLLATE NOCASE',
                    'admin' => 'INTEGER NOT NULL DEFAULT 0', // unsigned integer
                    'password' => 'TEXT NOT NULL DEFAULT ""', // up to 255 varchar hash
                    'approved' => 'TEXT NOT NULL DEFAULT "Y"', // 'Y' or 'N' char
                    'registered' => 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP', // date('Y-m-d H:i:s')
                    'last_activity' => 'INTEGER NOT NULL DEFAULT 0', // time()
                ));
            }
        }
        $username = $this->page->request->getUser();
        $password = $this->page->request->getPassword();
        if (!empty($username) && !empty($password)) {
            if (is_null($basic)) { // use the user database (default)
                if ($user_id = $this->check($username, $password, 'approved = "Y"')) {
                    $this->user = $this->db->row('SELECT id, name, email, admin FROM users WHERE id = ?', $user_id, 'assoc');
                    $this->user['login'] = 0;
                }
            } elseif (is_array($basic)) { // an array of users
                if (isset($basic[$username]) && $password == $basic[$username]) {
                    $this->basic = $username;
                }
            } elseif (substr($basic, -4) == '.yml' && is_file($basic)) { // a YAML file of users
                $users = array();
                $current = (array) Yaml::parse(file_get_contents($basic));
                foreach ($current as $name => $pw) {
                    if (!is_numeric($name) && !empty($name) && !empty($pw)) {
                        $users[$name] = (substr($pw, 0, 4) == '$2y$') ? $pw : password_hash($pw, $this->password['algo'], $this->password['options']);
                    }
                }
                if (isset($users[$username]) && password_verify($password, $users[$username])) {
                    if (password_needs_rehash($users[$username], $this->password['algo'], $this->password['options'])) {
                        $users[$username] = password_hash($password, $this->password['algo'], $this->password['options']);
                    }
                    $this->basic = $username;
                }
                if ($users != $current) {
                    file_put_contents($basic, Yaml::dump($users));
                }
            }
            
            return; // HTTP Basic Authentication takes precedence
        }
        $user = false;
        $session = $this->page->session->get('bootpress/cookie'); // vs. bootpress/verified
        if ($cookie = $this->page->request->cookies->get('bootpress')) {
            $cookie = base64_decode($cookie);
        }
        if ($session && $session != $cookie) { // These two must match if they both exist
            // We set the $session so we know that's good, but ...
            // if $session doesn't equal $cookie then it has been compromised
            // if $session and no $cookie, then it's assumed to have been stolen and deleted
            $this->logout($this->db->value('SELECT user_id FROM user_sessions WHERE id = ?', strstr($session.' ', ' ', true)));
            
            return $this->setCookie();
        }
        if ($cookie) { // We can have a $cookie and no $session (just log them in again)
            list($id, $series, $token) = explode(' ', $cookie.'   ');
            if (!$user = $this->db->row(array(
                'SELECT u.id, u.name, u.email, u.admin, strftime("%s", s.login) AS login, s.user_agent, s.last_activity, s.relapse, s.adjourn, s.token, u.approved',
                'FROM user_sessions AS s',
                'INNER JOIN users AS u ON s.user_id = u.id',
                'WHERE s.id = ? AND s.series = ?',
            ), array($id, sha1($series)), 'assoc')) {
                return $this->setCookie(); // ie. unset the cookie - it is defective, but not necessarily malicious
            }
            $session = array(
                'id' => $id,
                'series' => $series,
                'token' => $token,
                'time' => time(),
                'user_agent' => trim(substr($this->page->request->headers->get('User-Agent'), 0, 255)),
            );
            if ($user['user_agent'] != $session['user_agent'] ||
                $user['token'] != sha1($session['token']) ||
                $user['adjourn'] <= $session['time'] ||
                $user['approved'] != 'Y') {
                // Something we take seriously has been changed
                $this->logout($user['id']);
                
                return $this->setCookie();
            }
            // Update records every 5 minutes
            if (($user['last_activity'] + 300) <= $session['time'] && !$this->page->request->isXmlHttpRequest()) {
                $session['token'] = $this->salt();
                $this->db->update('user_sessions', 'id', array($session['id'] => array(
                    'ip_address' => $this->page->request->getClientIp(),
                    'last_activity' => $session['time'],
                    'adjourn' => $session['time'] + $user['relapse'],
                    'token' => sha1($session['token']),
                )));
                $this->db->update('users', 'id', array($user['id'] => array('last_activity' => $session['time'])));
                $this->setCookie(implode(' ', array($session['id'], $session['series'], $session['token'])), $user['relapse']);
            }
            $this->session = $session; // 'id', 'series', 'token', 'time', 'user_agent'
            $this->user = array_slice($user, 0, 5, true); // 'id', 'name', 'email', 'admin', 'login'
            $this->user['login'] = time() - $this->user['login'];
        }
    }
    
    /**
     * @return null|string  The basic username if HTTP authenticated, or null if not.
     */
    public function http()
    {
        return $this->basic;
    }
    
    /**
     * HTTP Authenticate a user for current directory and all subdirectories whether they are signed in or not.
     * 
     * @param string $name  Identifies the set of resources to which the username and password will apply.
     *
     * @see http://stackoverflow.com/questions/12701085/what-is-the-realm-in-basic-authentication
     *
     * ```php
     * if (!$auth->http()) {
     *     $auth->realm('Website');
     * }
     * ```
     */
    public function realm($name, $message = null)
    {
        $content = $message ?: '<h1>401 Unauthorized</h1><p>Access Denied</p>';
        $this->page->send($content, 401, array(
            'WWW-Authenticate' => 'Basic realm="'.htmlspecialchars($name).'"',
        ));
    }

    /**
     * Retrieve some information about the signed in user.
     * 
     * @param string $param  Can be any of the following:
     * - '**id**' - From the database's user table.
     * - '**name**' - Of the user.
     * - '**email**' - Of the user.
     * - '**admin**' level - The default is 0 meaning they have no admin privileges.
     * - '**login**' - The number of seconds ago that they actually signed in with a username and password for the current session.  If using HTTP Basic Authentication, this will always be 0.
     * 
     * @return array|string|null  An array if you don't set $param, a string if the user is logged in and the $param exists, or null if not.
     * 
     * ```php
     * echo 'Hello '.$auth->user('name');
     * ```
     */
    public function user($param = null)
    {
        if (is_null($param)) {
            return $this->user;
        }

        return (isset($this->user[$param])) ? $this->user[$param] : null;
    }

    /**
     * Gives you the following information about your user(s):
     *
     * - '**id**'
     * - '**name**'
     * - '**email**'
     * - '**admin**' - Integer.
     * - '**approved**' - Y (yes) or N (no).
     * - '**registered**' - A GMT timestamp.
     * - '**last_activity**' - A GMT timestamp (updated at 5 minute intervals) or 0 if we don't know.
     * - A '**groups**' ``array($group, ...)`` of groups they are in.
     * 
     * @param int|int[] $user_id  An integer, or an ``array($user_id, ...)``` of users you would like to return information for.
     * 
     * @return array  An associative array of information about your user(s).  If $user_id is an array, then this will be a multidimensional ``array($user_id => $info, ...)``` for every user in the order given.  If there was no record found for a given $user_id, then it will be an empty array.
     */
    public function info($user_id)
    {
        $single = (is_array($user_id)) ? false : true;
        $ids = ($single) ? array($user_id) : $user_id;
        $users = array();
        foreach ($ids as $id) {
            $users[$id] = array();
        }
        $groups = $this->getUsersGroups($ids);
        foreach ($this->db->all('SELECT * FROM users WHERE id IN('.implode(', ', $ids).')', '', 'assoc') as $row) {
            unset($row['password']);
            $users[$row['id']] = $row;
            $users[$row['id']]['registered'] = strtotime($row['registered']);
            $users[$row['id']]['groups'] = $groups[$row['id']];
        }

        return ($single) ? array_shift($users) : $users;
    }

    /**
     * This takes the submitted parameters and check to see if they exist in the users database.
     * 
     * @param string $email     This is the only required argument.  If you stop here then we are only checking to see if the email already exists.
     * @param string $password  The submitted password to check if the user is who they are claiming to be.  Encryption is handled in vitro.
     * @param string $and       Additional qualifier's to check against.
     * 
     * @return int|false  (bool) false if the record does not exist, or the id (integer) of the user if we have a match.
     *
     * ```php
     * if ($user_id = $auth->check('user@domain.com', 'password', 'approved = "Y"')) {
     *     // Then you may proceed with $user_id
     * }
     * ```
     */
    public function check($email, $password = null, $and = null)
    {
        $check = func_get_args();
        $email = array_shift($check);
        if (empty($check)) {
            return $this->db->value('SELECT id FROM users WHERE email = ?', $email);
        }
        $password = array_shift($check);
        $and = (!empty($check)) ? ' AND '.array_shift($check) : '';
        if ($user = $this->db->row('SELECT id, password AS hash FROM users WHERE email = ?'.$and, $email, 'assoc')) {
            if (password_verify($password, $user['hash'])) {
                if (password_needs_rehash($user['hash'], $this->password['algo'], $this->password['options'])) {
                    $this->db->update('users', 'id', array($user['id'] => array(
                        'password' => password_hash($password, $this->password['algo'], $this->password['options']),
                    )));
                }
                $this->page->session->set('bootpress/verified', $user['id']);

                return $user['id'];
            }
        }

        return false;
    }

    /**
     * Verifies whether or not an email address looks valid.
     * 
     * @param string $address  The email you would like to verify the looks of.
     * 
     * @return bool  True or False.  Whether the $address looks like a real email or not.
     *
     * ```php
     * if ($auth->isEmail('user@domain.com')) {
     *     // Then you may proceed
     * }
     * ```
     */
    public function isEmail($address)
    {
        return (bool) preg_match('/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/', $address);
    }

    /**
     * This creates a random password that you can suggest to a user, or use to reset and email them the new password, or to just create a random alpha-numeric string for yourself.
     * 
     * @param int $length  The desired length of your password
     * 
     * @return string  The random password.
     */
    public function randomPassword($length = 8)
    {
        $password = '';
        for ($i = 0; $i < $length; ++$i) {
            switch (mt_rand(0, 2)) {
                case 0:
                    $password .= mt_rand(0, 9);
                    break; // 0-9
                case 1:
                    $password .= chr(mt_rand(65, 90));
                    break; // A-Z
                case 2:
                    $password .= chr(mt_rand(97, 122));
                    break; // a-z
            }
        }

        return $password;
    }

    /**
     * This will ensure that a user is registered at the site.  If you get someone registering twice for whatever reason, then this will make sure they are in, and you can advise them whether or not they already hold an account with you.  If they are not a $new_user, then the name and password will not be saved (the email of course will remain the same).  You don't want somebody registering themselves access into someone else's account.
     * 
     * @param string $name      The user's name.
     * @param string $email     The user's email.
     * @param string $password  The user's password.  Do not encrypt!  We do that for you.
     * 
     * @return array  An ``array((bool) $new_user, (int) $user_id)`` where $new_user is either true or false, and $user_id is either the new or the old id depending.
     *
     * ```php
     * list($new_user, $user_id) = $auth->register('Joe Blow', 'name@domain.com', 'sekrit');
     * ```
     */
    public function register($name, $email, $password)
    {
        $new_user = ($user_id = $this->check($email)) ? false : true;
        if ($new_user) {
            $user_id = $this->db->insert('users', array(
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, $this->password['algo'], $this->password['options']),
                'admin' => 0,
                'approved' => 'Y',
                'registered' => date('Y-m-d H:i:s'),
                'last_activity' => 0,
            ));
        }

        return array($new_user, $user_id);
    }

    /**
     * Allows you to update a users account.
     * 
     * @param int   $user_id  The id of the user you would like to update.
     * @param array $user     An associative array of fields you would like to update.  The available fields are:
     *
     * - '**name**' => The users name.
     * - '**email**' => The users email.  If you are changing this, then make sure it is not already in use beforehand.
     * - '**password**' => The new password.  No encryption needed.
     * - '**admin**' => An integer >= 0.
     * - '**approved**' => '**Y**' (yes) or '**N**' (no).  If you set this to '**N**' (no), they will be logged out immediately wherever they may be.
     * - '**registered**' => 
     * - '**last_activity**' => 
     *
     * ... and any other fields that may exist if you have customized the database to suit your needs.
     * 
     * ```php
     * if ($auth->update($user_id, array(
     *     'approved' => 'N', // This will log them out and prevent them from ever signing in again.
     * ));
     * ```
     */
    public function update($user_id, array $user = array())
    {
        $update = array();
        foreach ($user as $key => $value) {
            switch ($key) {
                case 'name':
                    $update[$key] = $value;
                    break;
                case 'email':
                    if ($this->isEmail($value)) {
                        $update[$key] = $value;
                    }
                    break;
                case 'password':
                    if (!empty($value)) {
                        $update[$key] = password_hash($value, $this->password['algo'], $this->password['options']);
                    }
                    break;
                case 'admin':
                    $update[$key] = (is_numeric($value) && $value > 0) ? (int) $value : 0;
                    break;
                case 'approved':
                    $update[$key] = (empty($value) || strtoupper($value) == 'N') ? 'N' : 'Y';
                    break;
                case 'registered':
                    if (is_numeric($value)) {
                        $update[$key] = date('Y-m-d H:i:s', (int) $value);
                    }
                    break;
                case 'last_activity':
                    if (is_numeric($value)) {
                        $update[$key] = (int) $value;
                    }
                    break;
                default:
                    $update[$key] = $value;
                    break;
            }
        }
        if (!empty($update)) {
            $this->db->update('users', 'id', array($user_id => $update));
            if ($this->isUser($user_id)) {
                foreach (array('name', 'email', 'admin') as $value) {
                    if (isset($update[$value])) {
                        $this->user[$value] = $update[$value];
                    }
                }
            }
            if (isset($update['approved']) && $update['approved'] == 'N') {
                $this->logout($user_id);
            }
        }
    }
    
    /**
     * This will login a user to your site for a specified amount of time (of inactivity), and optionally log them out everywhere else.  Session and cookie based.
     * 
     * @param integer     $user_id  Of the user you want to login.
     * @param integer     $expires  The number of days (if less than or equal to 730) or seconds (if greater than 730) of inactivity after which you would like to require the user to sign back into your site.
     * @param false|mixed $single   If you set this to true (or to anything besides false), then they will be logged out of every other session that may be currently active.  Meaning if you sign in on a public computer, then realize you forgot to sign out, you can sign in again on any other computer and be signed out from all previous sessions if you use this feature.
     * 
     * ```php
     * $auth->login($user_id, 30, 'single'); // Sign in $user_id for 30 days here, and log them out everywhere else.
     * ```
     */
    public function login($user_id, $expires = 7200, $single = false)
    {
        if (empty($user_id)) {
            return;
        }
        $this->logout(($single !== false) ? $user_id : null);
        if ($user = $this->db->row('SELECT id, name, email, admin FROM users WHERE id = ? AND approved = ?', array($user_id, 'Y'), 'assoc')) {
            $this->session = array(
                'id' => '',
                'series' => $this->salt(),
                'token' => $this->salt(),
                'time' => time(),
                'user_agent' => trim(substr($this->page->request->headers->get('User-Agent'), 0, 255)),
            );
            $relapse = ($expires <= 730) ? $expires * 24 * 60 * 60 : $expires;
            $this->session['id'] = $this->db->insert('user_sessions', array(
                'user_id' => $user['id'],
                'adjourn' => $this->session['time'] + $relapse,
                'relapse' => $relapse,
                'last_activity' => $this->session['time'],
                'ip_address' => $this->page->request->getClientIp(),
                'user_agent' => $this->session['user_agent'],
                'series' => sha1($this->session['series']),
                'token' => sha1($this->session['token']),
                'login' => date('Y-m-d H:i:s', $this->session['time']),
            ));
            $this->db->update('users', 'id', array($user['id'] => array('last_activity' => $this->session['time'])));
            $this->setCookie(implode(' ', array($this->session['id'], $this->session['series'], $this->session['token'])), $relapse);
            $this->user = $user;
        }
    }

    /**
     * This will log a session authenticated user out of your site.
     * 
     * @param integer $user_id  If this is an integer, then $user_id will be logged out of all their sessions, everywhere.  If null (or not given), then the current user will be logged out of the current session.
     * 
     * ```php
     * $auth->logout(); // Log out the current user.
     * ```
     */
    public function logout($user_id = null)
    {
        if ($user_id) {
            $this->db->exec('DELETE FROM user_sessions WHERE user_id = ? AND adjourn <= ?', array($user_id, time()));
            if ($user_id == $this->isUser()) {
                $this->logout();
            }
        } elseif (isset($this->session['id'])) {
            $this->setCookie();
            $this->db->exec('DELETE FROM user_sessions WHERE id = ? AND adjourn <= ?', array($this->session['id'], time()));
            $this->session = array();
            $this->user = array();
        }
    }

    /**
     * Get the number of active, session authenticated users at your site within the $duration given.
     * 
     * @param integer $duration  How far back (in seconds) you would like to go.  The default is 600 seconds ie. 10 minutes.
     * 
     * @return integer  The total number of active users.  If this is 1, then that's you!
     * 
     * ```php
     * $auth->count(86400); // within the last 24 hours
     * ```
     */
    public function online($duration = 600)
    {
        return $this->db->value('SELECT COUNT(*) FROM users WHERE last_activity >= ?', array(time() - $duration));
    }

    /**
     * This will tell you if the person viewing the current page is a specific (optional) user, or not.  This does not apply if the user is HTTP Basic Authenticated from an array or YAML file of users.
     * 
     * @param integer $user_id  If you want to verify that this is a specific user, then you may indicate the user's id here.
     * 
     * @return false|integer  False if they are not a signed in user, and if you included a $user_id then false if they are not that specific user.  Otherwise this returns the id of the signed in user.
     * 
     * ```php
     * if ($user_id = $auth->isUser()) {
     *     // Now we may do something specifically for $user_id
     * }
     * 
     * if (!$auth->isUser($seller_id)) {
     *     $page->eject(); // not the real seller, get them out of here
     * }
     * 
     * if ($auth->isUser()) {
     *     // Display a logout link
     * }
     * ```
     */
    public function isUser($user_id = null)
    {
        if (!$user = $this->user('id')) {
            return false;
        }
        if (empty($user_id)) {
            return (!empty($user)) ? $user : false; // an id > 0 or false
        }

        return (!empty($user) && $user_id == $user) ? $user : false;
    }

    /**
     * Allows you to determine if ``$this->isUser()`` submitted a password during the current session, or if we're relying on a remember-me cookie.
     * 
     * @return false|integer  The current user's id if they submitted a password during the current session, or false if not.
     * 
     * ```php
     * if ($user_id = $auth->isVerified()) {
     *     // Allow them to change their password, charge their credit card, etc.
     * }
     * ```
     */
    public function isVerified()
    {
        return (($user_id = $this->isUser()) && $user_id == $this->page->session->get('bootpress/verified')) ? $user_id : false;
    }

    /**
     * This will tell you if the person viewing the current page has admin access greater than or equal to $level, or not.  There is no need to check if ``$this->isUser()`` first when using this function, unless you also want the $user_id, or to make sure this is a specific admin user.  HTTP Basic Authenticated users will always pass this test and returns (integer) 1, giving them super-admin privileges.
     * 
     * @param integer $level  The admin user must be greater than or equal to the level you indicate here.  This method manages admin permissions as follows:
     * 
     * 1. Is the end all and be all of admins, and can access anything.
     * 2. Does not have level 1 access, but can access 2, 3, 4, 5, etc.
     * 3. Cannot access level 1 or 2 admin pages, but can access 3, 4, 5, 6 ... you get the picture.
     * 
     * @return bool  False if they are not even a user in the first place, and false again if they don't have the level of access you desire.  Otherwise it returns the level of access they have.
     * 
     * ```php
     * if ($auth->isAdmin(1) || $auth->isUser($seller)) {
     *     // Now you and the seller can edit their info
     * }
     * 
     * if ($level = $auth->isAdmin(2)) {
     *     // Level 1 and level 2 admins can access this
     *     if ($level == 1) {
     *         // Now we are tightening down the hatches
     *     }
     * }
     * ```
     */
    public function isAdmin($level = 1)
    {
        if ($this->basic) {
            return 1;
        } elseif (!$admin = $this->user('admin')) {
            return false;
        }

        return (!empty($admin) && $admin <= $level) ? $admin : false;
    }

    /**
     * Checks to see if the signed in user is included in any or all of the group(s) you specify.
     * 
     * @param string|array $group  The name of the group.  To verify membership for the current user against 
     * @param string $check  If '**all**' (default) then they must be in every group specified.  If '**any**' then they must be in at least one of the groups specified.
     * 
     * @return bool  True or False.
     * 
     * ```php
     * if ($auth->inGroup(array('heaven', 'hell'), 'any')) {
     *     // Then this signed in user is dead.
     *     $auth->logout();
     * }
     * ```
     */
    public function inGroup($group, $check = 'all')
    {
        if ($this->basic) {
            return true;
        }
        return ($user_id = $this->isUser()) ? $this->userInGroup($user_id, $group, $check) : false;
    }

    /**
     * This will add $user_id(s) to the $group(s) you want to include them in.
     * 
     * @param integer $user_id  Of the user.  To add multiple users, make this an ``array($user_id, ...)`` of users to add to the $group(s).
     * @param string  $group    The name of the group you would like to include them in.  To include in multiple groups, make this an ``array($group, ...)`` of groups to add the $user_id(s) to.
     * 
     * ```php
     * $auth->addToGroup($user_id, 'rothschild');
     * ```
     */
    public function addToGroup($user_id, $group)
    {
        $users = (array) $user_id;
        $groups = (array) $this->groupId($group);
        $this->removeFromGroup($users, $groups);
        $stmt = $this->db->insert('user_groups', array('user_id', 'group_id'));
        foreach ($users as $user_id) {
            foreach ($groups as $group_id) {
                $this->db->insert($stmt, array($user_id, $group_id));
            }
        }
        $this->db->close($stmt);
    }

    /**
     * This will remove $user_id(s) from the $group(s) they no longer belong in.
     * 
     * @param integer $user_id  Of the user.  To remove multiple users, make this an ``array($user_id, ...)`` of users to remove from the $group(s).
     * @param string  $group    The name of the group you would like to remove them from.  To remove from multiple groups, make this an ``array($group, ...)`` of groups to remove the $user_id(s) from.
     * 
     * ```php
     * $auth->removeFromGroup($user_id, 'rothschild'); // $user_id should not be involved with them anyways
     * ```
     */
    public function removeFromGroup($user_id, $group)
    {
        $this->db->exec(array(
            'DELETE FROM user_groups',
            'WHERE user_id IN ('.implode(', ', (array) $user_id).')',
            'AND group_id IN('.implode(', ', (array) $this->groupId($group)).')',
        ));
    }

    /**
     * This will retrieve all of the groups that $user_id(s) belong to.
     * 
     * @param integer $user_id  Of the user.  If you want to retrieve the groups of multiple users at once, then make this an ``array($user_id, ...)`` of users.
     * 
     * @return array  A single ``array($group, ...)`` of groups if $user_id is an integer (single), or a multidimensional ``array($user_id => $groups, ...)`` for every $user_id in the order given.
     * 
     * ```php
     * $groups = $auth->getUsersGroups($user_id);
     * ```
     */
    public function getUsersGroups($user_id)
    {
        $single = (is_array($user_id)) ? false : true;
        $ids = ($single) ? array($user_id) : $user_id;
        $users = array();
        foreach ($ids as $id) {
            $users[$id] = array();
        }
        foreach ($this->db->all(array(
            'SELECT u.user_id, g.name AS group_name',
            'FROM user_groups AS u',
            'INNER JOIN user_group_names AS g ON u.group_id = g.id',
            'WHERE u.user_id IN('.implode(', ', $ids).')',
            'ORDER BY g.name ASC',
        ), '', 'assoc') as $row) {
            $users[$row['user_id']][] = $row['group_name'];
        }

        return ($single) ? array_shift($users) : $users;
    }

    /**
     * This will retrieve all of the user_id's that belong to the $group(s).
     * 
     * @param string $group  The name of the group.  If you want to retrieve the user_id's of multiple groups at once, then make this an ``array($group, ...)`` of groups.
     * 
     * @return array  A single ``array($user_id, ...)`` of users if $groups is a string (single), or a multidimensional ``array($group => (array) $user_ids, ...)`` for every $group in the order given.
     * 
     * ```php
     * $users = $auth->getGroupsUsers('rothschild');
     * ```
     */
    public function getGroupsUsers($group)
    {
        $single = (is_array($group)) ? false : true;
        $ids = ($single) ? array($group) : $group;
        $groups = array();
        foreach ($ids as $id) {
            $groups[$id] = array();
        }
        foreach ($this->db->all(array(
            'SELECT u.user_id, u.group_id, g.name AS group_name',
            'FROM user_groups AS u',
            'INNER JOIN user_group_names AS g ON u.group_id = g.id',
            'WHERE u.user_id > 0 AND u.group_id IN('.implode(', ', $this->groupId($ids)).')',
            'ORDER BY u.user_id, u.group_id ASC',
        ), '', 'assoc') as $row) {
            $key = (isset($groups[$row['group_id']])) ? $row['group_id'] : $row['group_name'];
            $groups[$key][] = $row['user_id'];
        }

        return ($single) ? array_shift($groups) : $groups;
    }

    /**
     * This will verify if $user_id is in '**any**' or '**all**' of the $group(s) specified.  If you are checking for the current user of your page, then do not use this method.  Use ``$this->inGroup()`` instead.
     * 
     * @param integer $user_id  Of the user whose $group(s) membership you want to verify.  This cannot be an array.  Sorry.
     * @param string  $group    The name of the group.  To verify $user_id against multiple groups then make this an ``array($group, ...)`` of groups.
     * @param string  $check    If '**all**' (default) then they must be in every group specified.  If '**any**' then they must be in at least one of the groups specified.
     * 
     * @return array  True or False, whether or not your conditions were met.
     * 
     * ```php
     * if ($auth->userInGroup($user_id, array('rothschild', 'white house', 'al-Qaeda'), 'any') {
     *     // Well then we've got quite the user on our hands.
     * }
     * ```
     */
    public function userInGroup($user_id, $group, $check = 'all') // or 'any'
    {
        $groups = implode(',', $this->getUsersGroups($user_id));
        if (empty($groups)) {
            return false;
        }
        $check = (in_array(strtolower($check), array('all', 'and', '&&'))) ? 'all' : 'any';
        foreach ((array) $group as $in) {
            if (mb_stripos(",{$groups},", ",{$in},") !== false) {
                if ($check == 'any') {
                    return true; // there is no sense in checking any more beyond this
                }
            } elseif ($check == 'all') {
                return false; // they are not in this group, so that is all we need to know
            }
        }

        return ($check == 'all') ? true : false;
    }

    /**
     * Retrieves a group's id.
     * 
     * @param string|array $name  Of the group.
     * 
     * @return integer|array  The group(s) id(s).
     */
    private function groupId($name)
    {
        $single = (is_array($name)) ? false : true;
        $groups = array();
        foreach ((array) $name as $group) {
            if (empty($group)) {
                $group_id = 0;
            } elseif (is_numeric($group)) {
                $group_id = $group;
            } else {
                $group_id = $this->db->value('SELECT id FROM user_group_names WHERE name = ?', $group);
                if (empty($group_id)) {
                    $group_id = $this->db->insert('user_group_names', array('name' => $group));
                }
            }
            $groups[$group] = $group_id;
        }

        return ($single) ? array_shift($groups) : $groups;
    }

    /**
     * Sets (or removes if the $value is empty) a cookie to verify the validity of a session.
     * 
     * @param string  $value    Of the cookie.
     * @param integer $expires  How long (in seconds) the cookie should exist.
     */
    private function setCookie($value = '', $expires = 0)
    {
        if (empty($value)) {
            $this->page->session->remove('bootpress/cookie');
            $expires =  1;
        } else {
            $this->page->session->set('bootpress/cookie', $value);
            $value = base64_encode($value);
            if ($expires != 0) {
                $expires += time();
            }
        }
        $match = session_get_cookie_params();
        setcookie('bootpress', $value, $expires, $match['path'], $match['domain'], $match['secure'], true);
    }

    /**
     * Generates the series and tokens we use to compare sessions and cookies with.
     * 
     * @return string  A random, 22 character string.
     */
    private function salt()
    {
        return substr(strtr(rtrim(base64_encode(random_bytes(16)), '='), '+', '.'), 0, 22);
    }
}
