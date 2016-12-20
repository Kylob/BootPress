<?php

namespace BootPress\Analytics;

use BootPress\Page\Component as Page;
use BootPress\SQLite\Component as SQLite;
use Jenssegers\Agent\Agent;

/**
 * Aggregates robot (javascript disabled) and user (javascript enabled) traffic for you to analyze.
 *
 * @example
 *
 * ```php
 * use BootPress\Page\Component as Page;
 * use BootPress\Analytics\Component as Analytics;
 *
 * $page = Page::html();
 * if ($uri = Analytics::log()) {
 *     // Analytics::post('javascript', 'alert("'.$uri['views'].' pageviews");');
 *     $page->sendJson(Analytics::data());
 * }
 *
 * $html = ''; // ...
 *
 * $page->send($page->display($html));
 * // ``$page->display()`` includes the analytics.js last
 * // ``$page->send()`` logs the bots to avoid erroneous data
 * ```
 */
class Component
{
    private static $db;
    private static $ids = array();
    private static $stmt = array();
    private static $data = array();

    /**
     * @return object An *Analytics.db* BootPress\SQLite\Component instance with all your information
     */
    public static function database()
    {
        $page = Page::html();
        $db = new SQLite($page->file('Analytics.db'));
        if ($db->created) {
            // Keeps track of sessions and associated data - deleted after 24 hours
            $db->create('analytics', array(
                'id' => 'INTEGER PRIMARY KEY',
                'session' => 'TEXT NOT NULL DEFAULT ""',
                'started' => 'INTEGER NOT NULL DEFAULT 0',
                'ip' => 'TEXT NOT NULL DEFAULT ""',
                'referrer' => 'TEXT NOT NULL DEFAULT ""',
                'session_id' => 'INTEGER NOT NULL DEFAULT 0',
                'agent_id' => 'INTEGER NOT NULL DEFAULT 0',
                'path_id' => 'INTEGER NOT NULL DEFAULT 0',
                'query' => 'TEXT NOT NULL DEFAULT ""',
            ), array('started'));

            // User-agents, and whatever useful information we can glean from them
            $db->create('analytic_agents', array(
                'id' => 'INTEGER PRIMARY KEY',
                'browser' => 'TEXT NOT NULL DEFAULT ""',
                'version' => 'TEXT NOT NULL DEFAULT ""',
                'mobile' => 'TEXT NOT NULL DEFAULT ""',
                'desktop' => 'TEXT NOT NULL DEFAULT ""',
                'robot' => 'TEXT NOT NULL DEFAULT ""',
                'agent' => 'TEXT UNIQUE NOT NULL DEFAULT ""', // up to 255 varchar string
            ));

            // Bot (javascript disabled) hits - every page and asset
            $db->create('analytic_bots', array(
                'id' => 'INTEGER PRIMARY KEY',
                'time' => 'INTEGER NOT NULL DEFAULT 0',
                'ip' => 'TEXT NOT NULL DEFAULT ""',
                'analytic_id' => 'INTEGER NOT NULL DEFAULT 0', // for deleting when user
                'agent_id' => 'INTEGER NOT NULL DEFAULT 0',
                'path_id' => 'INTEGER NOT NULL DEFAULT 0',
                'query' => 'TEXT NOT NULL DEFAULT ""',
            ), array('time', 'ip', 'agent_id', 'path_id'));

            // User (javascript enabled) hits - html pages only
            $db->create('analytic_hits', array(
                'id' => 'INTEGER PRIMARY KEY',
                'time' => 'INTEGER NOT NULL DEFAULT 0',
                'loaded' => 'INTEGER NOT NULL DEFAULT 0', // microseconds
                'server' => 'INTEGER NOT NULL DEFAULT 0', // microseconds
                'dns' => 'INTEGER NOT NULL DEFAULT 0', // microseconds
                'tcp' => 'INTEGER NOT NULL DEFAULT 0', // microseconds
                'request' => 'INTEGER NOT NULL DEFAULT 0', // microseconds
                'response' => 'INTEGER NOT NULL DEFAULT 0', // microseconds
                'session_id' => 'INTEGER NOT NULL DEFAULT 0',
                'path_id' => 'INTEGER NOT NULL DEFAULT 0',
                'query' => 'TEXT NOT NULL DEFAULT ""',
            ), array('time', 'session_id', 'path_id'));

            // URL paths
            $db->create('analytic_paths', array(
                'id' => 'INTEGER PRIMARY KEY',
                'path' => 'TEXT UNIQUE NOT NULL DEFAULT ""',
            ));

            // User (javascript enabled) session data
            $db->create('analytic_sessions', array(
                'id' => 'INTEGER PRIMARY KEY',
                'started' => 'INTEGER NOT NULL DEFAULT 0',
                'hits' => 'INTEGER NOT NULL DEFAULT 0',
                'duration' => 'INTEGER NOT NULL DEFAULT 0', // seconds
                'width' => 'INTEGER NOT NULL DEFAULT 0',
                'height' => 'INTEGER NOT NULL DEFAULT 0',
                'hemisphere' => 'TEXT NOT NULL DEFAULT ""',
                'timezone' => 'TEXT NOT NULL DEFAULT ""',
                'dst' => 'INTEGER NOT NULL DEFAULT 0',
                'offset' => 'INTEGER NOT NULL DEFAULT 0', // seconds
                'ip' => 'TEXT NOT NULL DEFAULT ""',
                'referrer' => 'TEXT NOT NULL DEFAULT ""',
                'agent_id' => 'INTEGER NOT NULL DEFAULT 0',
                'path_id' => 'INTEGER NOT NULL DEFAULT 0',
                'query' => 'TEXT NOT NULL DEFAULT ""',
            ), array('started'));

            // Associates user(s) and their sessions
            $db->create('analytic_users', array(
                'session_id' => 'INTEGER NOT NULL DEFAULT 0',
                'user_id' => 'INTEGER NOT NULL DEFAULT 0',
            ), array('unique' => 'session_id, user_id'));
        }

        return $db;
    }

    /**
     * Track and log the current page.  Relevancy determined in-house.
     *
     * @return bool|array Either ``false``, or an array of the following useful data:
     *
     * - **loaded** - The total time your user waited for the page to load from beginning to end.
     * - **server** - How long it took the server to process the (initial) request.
     * - **dns** - How long it took to lookup the domain name.  Included in **server**.
     * - **tcp** - How long it took to connect to the server.  Included in **server**.
     * - **request** - How long it took to receive that initial byte.  Included in **server**.
     * - **response** - How long it took to receive those bytes.  Included in **server**.
     * - **views** - The total number of views for the current page, not including your own (if you are logged in)
     */
    public static function log()
    {
        // Get initial values
        $now = time();
        $log = array(); // csv lines
        $page = Page::html();

        // Establish a SESSION analytics array
        $analytics = $page->session->get('analytics');
        if (!is_array($analytics) || $analytics['last'] < ($now - 1800)) { // create a new "session" after half an hour of inactivity
            $analytics = array(
                'hits' => 0,
                'last' => $now,
                'started' => $now,
                'session' => $page->session->getId(),
                'javascript' => false,
                'timezone' => null,
                'offset' => null,
                'users' => array(),
                'agent' => array_fill_keys(array('robot', 'browser', 'version', 'mobile', 'desktop'), null),
            );
            $agent = new Agent();
            $agent->setUserAgent($page->request->headers->get('User-Agent'));
            if ($agent->isRobot()) {
                $analytics['agent']['robot'] = $agent->robot();
            } else {
                $browser = $agent->browser();
                $analytics['agent']['browser'] = $browser;
                $analytics['agent']['version'] = intval($agent->version($browser));
                if ($agent->isMobile()) {
                    $analytics['agent']['mobile'] = $agent->device();
                } else { // desktop
                    $analytics['agent']['desktop'] = $agent->platform();
                }
            }
            unset($agent);
            extract($analytics['agent']);
            if ($referrer = trim(strip_tags($page->request->headers->get('referer')))) {
                $ref = trim(strstr($referrer, ':'), ':/');
                $self = trim(strstr($page->url['base'], ':'), ':/');
                if (strpos($ref, $self) === 0) {
                    $referrer = null;
                }
            }
            $log[] = array(
                '' => 'analytics',
                'session' => $analytics['session'],
                'started' => $analytics['started'],
                'agent' => trim(substr(strip_tags($page->request->headers->get('User-Agent')), 0, 255)),
                'robot' => (string) $robot,
                'browser' => (string) $browser,
                'version' => empty($version) ? '' : $version,
                'mobile' => (string) $mobile,
                'desktop' => (string) $desktop,
                'referrer' => (string) $referrer,
                'path' => $page->url['path'],
                'query' => $page->url['query'],
                'ip' => $page->request->getClientIp(),
            );
            $page->session->set('analytics', $analytics);
            self::file($log);
            $log = array();
        }

        if ($page->request->isXmlHttpRequest()) {

            // Process POST requests from analytics.js
            $params = array();
            foreach (array('width', 'height', 'hemisphere', 'timezone', 'dst', 'offset', 'timer') as $key) {
                if (null === $value = $page->post($key)) {
                    return false;
                }
                $params[$key] = $value;
            }
            extract($params);
            if ($analytics['javascript'] === false) {
                $analytics['javascript'] = true;
                $analytics['timezone'] = (string) $timezone;
                $analytics['offset'] = (int) $offset;
                $log[] = array(
                    'analytic' => 'sessions',
                    'session' => $analytics['session'],
                    'started' => $analytics['started'],
                    'width' => $width,
                    'height' => $height,
                    'hemisphere' => $hemisphere,
                    'timezone' => $timezone,
                    'dst' => $dst,
                    'offset' => $offset,
                );
            }
            if ($user = $page->session->get('bootpress')) {
                if (!in_array($user['id'], $analytics['users'])) {
                    $analytics['users'][] = $user['id'];
                    $log[] = array(
                        'analytic' => 'users',
                        'user_id' => (int) $user['id'],
                        'session' => $analytics['session'],
                        'started' => $analytics['started'],
                    );
                }
            }
            $log[] = array(
                'analytic' => 'hits',
                'session' => $analytics['session'],
                'started' => $analytics['started'],
                'path' => $page->url['path'],
                'query' => $page->url['query'],
                'time' => $now,
                'loaded' => ($timer) ? $timer['loaded'] : 0,
                'server' => ($timer) ? $timer['server'] : 0,
                'dns' => ($timer) ? $timer['dns'] : 0,
                'tcp' => ($timer) ? $timer['tcp'] : 0,
                'request' => ($timer) ? $timer['request'] : 0,
                'response' => ($timer) ? $timer['response'] : 0,
            );
            $analytics['hits'] += 1;
            $analytics['last'] = $now;
            $page->session->set('analytics', $analytics);
            if (self::file($log) > 180) {
                self::process(); // every 3 minutes
            }
            $uri = ($timer) ? $timer : array();
            $db = self::database();
            if ($user) {
                $views = $db->value(array(
                    'SELECT COUNT(*) AS views FROM analytic_hits AS h',
                    'LEFT JOIN analytic_users AS u ON h.session_id = u.session_id AND u.user_id = ?',
                    'WHERE h.path_id = (SELECT p.id FROM analytic_paths AS p WHERE p.path = ?)',
                    'AND u.user_id != ?',
                ), array($user['id'], $page->url['path'], $user['id']));
            } else {
                $views = $db->value(array(
                    'SELECT COUNT(*) AS views',
                    'FROM analytic_hits AS h',
                    'WHERE h.path_id = (SELECT p.id FROM analytic_paths AS p WHERE p.path = ?)',
                ), array($page->url['path']));
            }
            $db->connection()->close();
            $uri['views'] = ($views) ? $views : 0;

            return $uri;
        } elseif ($analytics['javascript'] === false && in_array($page->url['format'], array('html', 'pdf', 'txt', 'xml', 'rdf', 'rss', 'atom'))) {
            $log[] = array(
                'analytic' => 'bots',
                'session' => $analytics['session'],
                'started' => $analytics['started'],
                'ip' => $page->request->getClientIp(),
                'path' => $page->url['path'],
                'query' => $page->url['query'],
                'time' => $now,
            );
            $analytics['last'] = $now;
            $page->filter('response', function ($page) use ($log, $analytics) {
                $page->session->set('analytics', $analytics);
                self::file($log);
            }, array(200));
        }

        if (empty($analytics['agent']['robot'])) {
            $page->filter('body', function ($html) use ($page) {
                return $html."\n\t".'<script src="'.$page->url($page->dirname(__CLASS__), 'analytics.js').'"></script>';
            });
        }

        return false;
    }

    /**
     * Process the analytics file for up-to-the-second results.  We call this ourselves in 3 minute intervals, when you log user hits.
     */
    public static function process()
    {
        $page = Page::html();
        $file = $page->file('analytics.csv');
        $temp = $page->file('analytics-temp.csv');
        if (is_file($temp)) {
            if ((time() - filemtime($temp)) < 360) {
                return false; // we are already on it
            }
            touch($temp); // Houston, we had a problem
            $read = fopen($file, 'rb');
            $write = fopen($temp, 'ab');
            while (!feof($read)) {
                fwrite($write, fgets($read));
            }
            fclose($read);
            fclose($write);
            unset($read, $write);
            rename($temp, $file);
        }
        if (!is_file($file)) {
            return false;
        }

        // Open for business
        rename($file, $temp);
        self::$db = self::database();
        self::$db->exec('BEGIN IMMEDIATE');

        // Insert records from analytics.csv
        $fp = fopen($temp, 'rb');
        while ($row = fgetcsv($fp)) {
            switch (array_shift($row)) {
                case 'analytics':
                    list($session, $started, $agent, $robot, $browser, $version, $mobile, $desktop, $referrer, $path, $query, $ip) = $row;
                    $agent_id = self::id('agents', $agent, array(
                        $agent, $robot, $browser, $version, $mobile, $desktop,
                    ));
                    $path_id = self::id('paths', $path);
                    $analytic_id = self::exec('INSERT', 'analytics', array(
                        $session, $started, $agent_id, $path_id, $query, $referrer, $ip,
                    ));
                    self::$ids['analytics'][$session.'::'.$started] = $analytic_id;
                    break;

                case 'bots':
                    list($session, $started, $ip, $path, $query, $time) = $row;
                    if ($analytic_id = self::id('analytics', $session.'::'.$started)) {
                        $path_id = self::id('paths', $path);
                        self::exec('INSERT', 'bots', array(
                            $ip, $path_id, $query, $time, $analytic_id, $analytic_id,
                        ));
                    }
                    break;

                case 'sessions':
                    list($session, $started, $width, $height, $hemisphere, $timezone, $dst, $offset) = $row;
                    if ($analytic_id = self::id('analytics', $session.'::'.$started)) {
                        $session_id = self::exec('INSERT', 'sessions', array(
                            $width, $height, $hemisphere, $timezone, $dst, $offset, $analytic_id,
                        ));
                        self::exec('UPDATE', 'analytics', array($session_id, $analytic_id));
                        self::exec('DELETE', 'bots', array($analytic_id, $started));
                        self::$ids['sessions'][$session.'::'.$started] = $session_id;
                    }
                    break;

                case 'users':
                    list($user_id, $session, $started) = $row;
                    if ($session_id = self::id('sessions', $session.'::'.$started)) {
                        self::exec('INSERT', 'users', array($session_id, $user_id));
                    }
                    break;

                case 'hits':
                    list($session, $started, $path, $query, $time, $loaded, $server, $dns, $tcp, $request, $response) = $row;
                    if ($session_id = self::id('sessions', $session.'::'.$started)) {
                        $path_id = self::id('paths', $path);
                        self::exec('INSERT', 'hits', array(
                            $session_id, $path_id, $query, $time, $loaded, $server, $dns, $tcp, $request, $response,
                        ));
                    }
                    break;
            }
        }
        fclose($fp);

        // Remove analytics more than 24 hours old
        self::$db->exec("DELETE FROM analytics WHERE started <= strftime('%s', 'now', '-24 hours')");

        // Update analytic_sessions hits and duration
        if (isset(self::$ids['sessions'])) {
            $stmt = self::$db->update('analytic_sessions', 'id', array('hits', 'duration'));
            foreach (self::$db->all(array(
                'SELECT session_id AS id, COUNT(*) AS count, MAX(time) AS max, MIN(time) AS min',
                'FROM analytic_hits',
                'WHERE session_id IN('.implode(', ', self::$ids['sessions']).')',
                'GROUP BY session_id',
            ), '', 'assoc') as $row) {
                self::$db->update($stmt, $row['id'], array($row['count'], ($row['max'] - $row['min'])));
            }
            self::$db->close($stmt);
        }

        // Close up shop
        self::$db->exec('COMMIT');
        foreach (self::$stmt as $action => $tables) {
            foreach ($tables as $stmt) {
                self::$db->close($stmt);
            }
        }
        self::$db = null;
        self::$ids = array();
        self::$stmt = array();
        unlink($temp);
    }

    /**
     * Post information to a page that has already been loaded.  You can call this method repeatedly to add as many elements as you like.
     *
     * @param string $location Where you would like the **$code** to go.  It can be one of three things:
     *
     * - '**css**' - For styling any additional elements you are including.
     * - '**javascript**' - To execute after any additional html has been placed.
     * - A jQuery selector (likely an '**#id**'), where you would like to append some HTML
     * @param string $code Either CSS, JavaScript, or HTML depending on **$location**
     */
    public static function post($location, $code)
    {
        if (!empty($code)) {
            self::$data[$location][] = (is_array($code)) ? implode("\n\t", $code) : $code;
        }
    }

    /**
     * Organizes the information you ``post()``ed.
     *
     * @return array Of all your data
     */
    public static function data()
    {
        $page = Page::html();
        $data = self::$data;
        self::$data = array(); // reset
        foreach ($data as $key => $values) {
            $data[$key] = "\n\t".implode("\n\t", $values);
        }
        if (isset($data['css'])) { // move css to the beginning
            $data = array('css' => $data['css']) + $data;
        }
        if (isset($data['javascript'])) { // move javascript to the end
            $javascript = $data['javascript'];
            unset($data['javascript']);
            $data['javascript'] = $javascript;
        }

        return $data;
    }

    /**
     * Gives you a rough idea of where your user is located.
     *
     * @param string $timezone   As saved in the database
     * @param string $hemisphere Either *'N'* or *'S'*
     *
     * @return string
     */
    public static function location($timezone, $hemisphere = '')
    {
        static $timezones = array(
            'UM12' => 'Baker / Howland Islands',
            'UM11' => array(
                'N' => 'Midway Islands',
                'S' => 'American Samoa',
            ),
            'UM10' => array(
                'N' => 'Hawaii',
                'S' => 'Cook Islands',
            ),
            'UM95' => 'Marquesas Islands',
            'UM9' => array(
                'N' => 'Alaska',
                'S' => 'Gambier Islands',
            ),
            'UM8' => array(
                'N' => 'Los Angeles, Vancouver, Tijuana',
                'S' => 'Pitcairn Islands',
            ),
            'UM7' => 'Denver, Phoenix, Edmonton',
            'UM6' => 'Chicago, Dallas, Winnipeg, Central America',
            'UM5' => array(
                'N' => 'New York, Quebec, Western Caribbean, Colombia',
                'S' => 'Ecuador, Peru',
            ),
            'UM45' => 'Venezuela',
            'UM4' => array(
                'N' => 'Eastern Caribbean, Halifax',
                'S' => 'Central South America, Chile',
            ),
            'UM35' => 'Newfoundland',
            'UM3' => array(
                'N' => 'Greenland',
                'S' => 'Argentina, Brazil',
            ),
            'UM2' => 'South Georgia / South Sandwich Islands',
            'UM1' => 'Azores, Cape Verde Islands',
            'UTC' => array(
                'N' => 'United Kingdom, Portugal, West Africa',
                'S' => 'Saint Helena, Ascension, Tristan da Cunha',
            ),
            'UP1' => array(
                'N' => 'Europe, Central Africa',
                'S' => 'Angola, Namibia',
            ),
            'UP2' => array(
                'N' => 'Eastern Europe, Israel, Libya, Egypt',
                'S' => 'South Africa, Mozambique, Congo',
            ),
            'UP3' => array(
                'N' => 'Moscow, Iraq, Saudi Arabia, East Africa',
                'S' => 'Tanzania, Madagascar',
            ),
            'UP35' => 'Iran',
            'UP4' => array(
                'N' => 'Azerbaijan, Samara, Oman',
                'S' => 'Seychelles, Crozet Islands',
            ),
            'UP45' => 'Afghanistan',
            'UP5' => array(
                'N' => 'Uzbekistan, Pakistan, Maldives, Yekaterinburg',
                'S' => 'Kerguelen Islands',
            ),
            'UP55' => 'India, Sri Lanka',
            'UP575' => 'Nepal',
            'UP6' => 'Bangladesh, Bhutan, Omsk',
            'UP65' => 'Cocos Islands, Myanmar',
            'UP7' => 'Cambodia, Laos, Thailand, Vietnam, Krasnoyarsk',
            'UP8' => array(
                'N' => 'China, Philippines',
                'S' => 'Western Australia',
            ),
            'UP85' => 'North Korea',
            'UP875' => 'Eucla Australia',
            'UP9' => array(
                'N' => 'Japan, South Korea, Yakutsk',
                'S' => 'Timor-Leste, West Papua',
            ),
            'UP95' => 'Central Australia',
            'UP10' => array(
                'N' => 'Guam, Micronesia, Vladivostok',
                'S' => 'Eastern Australia',
            ),
            'UP105' => 'Lord Howe Island',
            'UP11' => array(
                'N' => 'Srednekolymsk',
                'S' => 'Solomon Islands, Vanuatu',
            ),
            'UP12' => array(
                'N' => 'Gilbert Islands, Kamchatka',
                'S' => 'Fiji, New Zealand',
            ),
            'UP1275' => 'Chatham Islands',
            'UP13' => 'Phoenix Islands, Samoa, Tonga',
            'UP14' => 'Line Islands',
        );
        if (!isset($timezones[$timezone])) {
            return;
        }
        if (is_string($timezones[$timezone])) {
            return $timezones[$timezone];
        }
        if (isset($timezones[$timezone][$hemisphere])) {
            return $timezones[$timezone][$hemisphere];
        }

        return implode(', ', $timezones[$timezone]);
    }

    /**
     * Appends csv lines to the analytics file.
     *
     * @param array $log The csv lines to add.  Must be an array of arrays ie. ``$log[] = array(...)``
     *
     * @return int How many seconds the file has been laying around
     */
    private static function file(array $log)
    {
        $page = Page::html();
        $file = $page->file('analytics.csv');
        $created = (is_file($file)) ? filemtime($file) : time();
        if (!empty($log)) {
            $handle = fopen($file, 'ab');
            foreach ($log as $line) {
                fputcsv($handle, $line);
            }
            fclose($handle);
            touch($file, $created);
        }

        return time() - $created;
    }

    /**
     * Looks up an id once, and saves it for later.
     *
     * @param string $table  Either *'agents'*, *'paths'*, *'analytics'*, or *'sessions'*
     * @param string $value  Unique for the **$table**
     * @param array  $insert User-agent data
     *
     * @return int
     */
    private static function id($table, $value, array $insert = array())
    {
        if (isset(self::$ids[$table][$value])) {
            return self::$ids[$table][$value];
        }
        switch ($table) {
            case 'agents':
                if ($row = self::exec('SELECT', 'agents', $value)) { // agent
                    $id = array_shift($row);
                    if ($row != $insert) {
                        list($agent, $robot, $browser, $version, $mobile, $desktop) = $insert;
                        self::exec('UPDATE', 'agents', array($robot, $browser, $version, $mobile, $desktop, $id));
                    }
                } else {
                    $id = self::exec('INSERT', 'agents', $insert);
                }
                break;
            case 'paths':
                if ($row = self::exec('SELECT', 'paths', $value)) { // path
                    $id = array_shift($row);
                } else {
                    $id = self::exec('INSERT', 'paths', array($value));
                }
                break;
            case 'analytics':
                list($session, $started) = explode('::', $value);
                if ($row = self::exec('SELECT', 'analytics', array($started, $session))) {
                    $id = array_shift($row);
                }
                break;
            case 'sessions':
                list($session, $started) = explode('::', $value);
                if ($row = self::exec('SELECT', 'sessions', array($started, $session))) {
                    $id = array_shift($row);
                }
                break;
        }
        if (isset($id)) {
            self::$ids[$table][$value] = $id;
        }

        return (isset(self::$ids[$table][$value])) ? self::$ids[$table][$value] : false;
    }

    /**
     * Prepares a statement once, and saves it for future use.
     *
     * @param string $action Either *'SELECT'*, *'INSERT'*, *'UPDATE'*, or *'DELETE'*
     * @param string $table  Where you want the **$action** to take place
     * @param array  $values For the query
     *
     * @return mixed Depending on the query
     */
    private static function exec($action, $table, $values = null)
    {
        $action = strtolower($action);
        if (!isset(self::$stmt[$action][$table])) {
            switch ($action) {
                case 'select':
                    switch ($table) {
                        case 'agents':
                            $stmt = self::$db->prepare('SELECT id, agent, robot, browser, version, mobile, desktop FROM analytic_agents WHERE agent = ?', 'assoc');
                            break;
                        case 'paths':
                            $stmt = self::$db->prepare('SELECT id FROM analytic_paths WHERE path = ?', 'assoc');
                            break;
                        case 'analytics':
                            $stmt = self::$db->prepare('SELECT id FROM analytics WHERE started = ? AND session = ?');
                            break;
                        case 'sessions':
                            $stmt = self::$db->prepare('SELECT session_id AS id FROM analytics WHERE started = ? AND session = ?');
                            break;
                    }
                    break;
                case 'insert':
                    switch ($table) {
                        case 'analytics':
                            $stmt = self::$db->insert('analytics', array('session', 'started', 'agent_id', 'path_id', 'query', 'referrer', 'ip'));
                            break;
                        case 'agents':
                            $stmt = self::$db->insert('analytic_agents', array('agent', 'robot', 'browser', 'version', 'mobile', 'desktop'));
                            break;
                        case 'bots':
                            $stmt = self::$db->prepare(array(
                                'INSERT INTO analytic_bots',
                                "('ip', 'agent_id', 'path_id', 'query', 'time', 'analytic_id')",
                                'SELECT ?, agent_id, ?, ?, ?, ? FROM analytics WHERE id = ?',
                            ));
                            break;
                        case 'sessions':
                            $stmt = self::$db->prepare(array(
                                'INSERT INTO analytic_sessions',
                                "('started', 'agent_id', 'path_id', 'query', 'referrer', 'ip', 'width', 'height', 'hemisphere', 'timezone', 'dst', 'offset')",
                                'SELECT started, agent_id, path_id, query, referrer, ip, ?, ?, ?, ?, ?, ? FROM analytics WHERE id = ?',
                            ));
                            break;
                        case 'hits':
                            $stmt = self::$db->insert('analytic_hits', array('session_id', 'path_id', 'query', 'time', 'loaded', 'server', 'dns', 'tcp', 'request', 'response'));
                            break;
                        case 'paths':
                            $stmt = self::$db->insert('analytic_paths', array('path'));
                            break;
                        case 'users':
                            $stmt = self::$db->insert('analytic_users', array('session_id', 'user_id'));
                            break;
                    }
                    break;
                case 'update':
                    switch ($table) {
                        case 'analytics':
                            $stmt = self::$db->update('analytics', 'id', array('session_id'));
                            break;
                        case 'agents':
                            $stmt = self::$db->update('analytic_agents', 'id', array('robot', 'browser', 'version', 'mobile', 'desktop'));
                            break;
                    }
                    break;
                case 'delete':
                    switch ($table) {
                        case 'bots':
                            $stmt = self::$db->prepare('DELETE FROM analytic_bots WHERE analytic_id = ? AND time >= ?');
                            break;
                    }
                    break;
            }
            self::$stmt[$action][$table] = (isset($stmt)) ? $stmt : null;
        }
        $result = self::$db->execute(self::$stmt[$action][$table], $values);
        if ($action == 'select') {
            $row = self::$db->fetch(self::$stmt[$action][$table]);

            return (!empty($row)) ? $row : false;
        }

        return $result;
    }
}
