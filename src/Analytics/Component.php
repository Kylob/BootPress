<?php

namespace BootPress\Analytics;

use BootPress\Page\Component as Page;
use BootPress\SQLite\Component as SQLite;
use Jenssegers\Agent\Agent;

/*
$analytics = new Analytics($auth->isUser());
if ($uri = $analytics->log()) {
    $uri['loaded'] = (float) 0.13;
    $uri['views'] = (int) 555;
    $analytics->post('javascript', 'alert("user");');
    exit($analytics->json()->send());
}
unset($analytics);
*/

class Component
{
    public $db;
    private $now;
    private $offset;
    private $user_id;
    private $started;
    private $ids = array();
    private $stmt = array();
    private $data = array();

    public function __construct($user_id = null, Database $db = null)
    {
        $this->db = $db;
        $this->now = $this->started = time();
        $page = Page::html();
        $analytics = $page->session->get('analytics');
        $this->offset = (isset($analytics['offset'])) ? (int) $analytics['offset'] : 0;
        $this->user_id = $user_id;
        if (is_null($this->db)) {
            $this->db = new SQLite($page->dir['page'].'Analytics.db');
        }
        if ($this->db instanceof SQLite && $this->db->created) {
            $this->setupDatabaseTables();
        }
        if ($started = $this->db->value('SELECT MIN(started) FROM analytics')) {
            $this->started = $started;
        }
    }

    public function log()
    {
        $page = Page::html();
        $file = $page->dir['page'].'analytics.csv';
        $created = (is_file($file)) ? filemtime($file) : $this->now;
        $analytics = $page->session->get('analytics');
        if ($page->request->isXmlHttpRequest()) {
            $params = array();
            foreach (array('width', 'height', 'hemisphere', 'timezone', 'dst', 'offset', 'timer') as $key) {
                if (null === $value = $page->request->request->get($key)) {
                    return false;
                }
                $params[$key] = $value;
            }
            extract($params);
            if ($analytics) {
                $handle = fopen($file, 'ab');
                if ($analytics['javascript'] === false) {
                    $this->offset = (int) $offset;
                    $analytics['offset'] = (int) $offset;
                    $analytics['timezone'] = (string) $timezone;
                    $analytics['javascript'] = true;
                    fputcsv($handle, array(
                        'table' => 'analytics',
                        'tracker' => $analytics['tracker'],
                        'width' => $width,
                        'height' => $height,
                        'hemisphere' => $hemisphere,
                        'timezone' => $timezone,
                        'dst' => $dst,
                        'offset' => $offset,
                    ));
                }
                if ($this->user_id && !in_array($this->user_id, $analytics['users'])) {
                    $analytics['users'][] = $this->user_id;
                    fputcsv($handle, array(
                        'table' => 'users',
                        'user_id' => $this->user_id,
                        'tracker' => $analytics['tracker'],
                    ));
                }
                $page->session->set('analytics', $analytics);
                if ($timer) {
                    fputcsv($handle, array(
                        'table' => 'server',
                        'server' => $timer['server'],
                        'dns' => $timer['dns'],
                        'tcp' => $timer['tcp'],
                        'request' => $timer['request'],
                        'response' => $timer['response'],
                        'time' => $this->now,
                    ));
                }
                fputcsv($handle, array(
                    'table' => 'hits',
                    'tracker' => $analytics['tracker'],
                    'time' => $this->now,
                    'loaded' => ($timer) ? $timer['loaded'] : 0,
                    'server' => ($timer) ? $timer['server'] : 0,
                    'path' => $page->url['path'],
                    'query' => $page->url['query'],
                    'format' => $page->url['format'],
                ));
                fclose($handle);
                touch($file, $created);
                if ((time() - $created) > 180) {
                    $this->processHits();
                }
            }
            $uri = ($timer) ? $timer : array();
            $uri['views'] = $this->pageViews();

            return $uri;
            /*
            $image = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
            $response = new Response($image, Response::HTTP_OK, array(
                'Content-Type' => 'image/gif',
                'Cache-Control' => 'no-cache',
                'max-age' => 0,
                's-maxage' => 0,
                'must-revalidate' => true,
                'no-store' => true
            ));
            $response->setPrivate();
            exit($response->prepare($page->request)->send());
            */
        }
        $handle = fopen($file, 'ab');
        if (!is_array($analytics)) {
            $analytics = array(
                'tracker' => $this->now.'::'.$page->session->getId(),
                'users' => array(),
                'offset' => null,
                'timezone' => null,
                'javascript' => false,
                'agent' => array_fill_keys(array('robot', 'browser', 'version', 'mobile', 'desktop'), null),
            );
            $agent = new Agent();
            $agent->setUserAgent($page->request->headers->get('User-Agent'));
            if ($agent->isRobot()) {
                $analytics['agent']['robot'] = $agent->robot();
            } else {
                $browser = $agent->browser();
                $analytics['agent']['browser'] = $browser;
                $analytics['agent']['version'] = $agent->version($browser);
                if ($agent->isMobile()) {
                    $analytics['agent']['mobile'] = $agent->device();
                } else { // desktop
                    $platform = $agent->platform();
                    $analytics['agent']['desktop'] = trim($platform.' '.$agent->version($platform));
                }
            }
            unset($agent);
            extract($analytics['agent']);
            $page->session->set('analytics', $analytics);
            if ($referrer = $page->request->headers->get('referer')) {
                $ref = trim(strstr($referrer, ':'), '/');
                $self = trim(strstr($page->url['base'], ':'), '/');
                if (strpos($ref, $self) !== 0) {
                    $referrer = null;
                }
            }
            fputcsv($handle, array(
                'table' => 'sessions',
                'tracker' => $analytics['tracker'],
                'started' => $this->now,
                'session' => $page->session->getId(),
                'agent' => trim(substr($page->request->headers->get('User-Agent'), 0, 255)),
                'robot' => (string) $robot,
                'browser' => (string) $browser,
                'version' => (string) $version,
                'mobile' => (string) $mobile,
                'desktop' => (string) $desktop,
                'referrer' => (string) $referrer,
                'path' => $page->url['path'],
                'query' => $page->url['query'],
                'format' => $page->url['format'],
                'ip' => $page->request->getClientIp(),
            ));
        }
        if ($analytics['javascript'] === false || $page->url['format'] != 'html') {
            fputcsv($handle, array(
                'table' => 'hits',
                'tracker' => $analytics['tracker'],
                'time' => $this->now,
                'loaded' => 0,
                'server' => 0,
                'path' => $page->url['path'],
                'query' => $page->url['query'],
                'format' => $page->url['format'],
            ));
        }
        fclose($handle);
        touch($file, $created);
        if ($page->url['format'] == 'html') {
            $page->link($page->url($page->dirname(__CLASS__), 'analytics.js'));
            $page->filter('response', function () use ($file, $created) {
                $page = Page::html();
                $handle = fopen($file, 'ab');
                fputcsv($handle, array(
                    'table' => 404,
                    'time' => time(),
                    'ip' => $page->request->getClientIp(),
                    'path' => $page->url['path'],
                    'query' => $page->url['query'],
                ));
                fclose($handle);
                touch($file, $created);
            }, array(404));
        }

        return false;
    }

    public function processHits()
    {
        $dir = Page::html()->dir['page'];
        $file = $dir.'analytics.csv';
        $temp = $dir.'analytics-temp.csv';
        if (is_file($temp)) {
            if ((time() - filemtime($temp)) < 360) {
                return false; // we are already on it
            }
            touch($temp); // Houston, we had a problem
            $write = fopen($temp, 'ab');
            $read = fopen($file, 'rb');
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
        rename($file, $temp);
        $this->db->exec('BEGIN IMMEDIATE');
        $fp = fopen($temp, 'rb');
        while ($row = fgetcsv($fp)) {
            switch (array_shift($row)) {
                case 'sessions':
                    list($tracker, $started, $session, $agent, $robot, $browser, $version, $mobile, $desktop, $referrer, $path, $query, $format, $ip) = $row;
                    $analytic_id = $this->exec('INSERT', 'analytics', array(
                        $started,
                        $this->id('agents', $agent, array(
                            $agent, $robot, $browser, $version, $mobile, $desktop,
                        )),
                        $this->id('paths', $path, array(
                            $path, $format,
                        )),
                        $query,
                        $referrer,
                        $ip,
                    ));
                    $this->ids['analytics'][$tracker] = $this->exec('INSERT', 'sessions', array($analytic_id, $session));
                    break;

                case 'analytics':
                    list($tracker, $width, $height, $hemisphere, $timezone, $dst, $offset) = $row;
                    if ($analytic_id = $this->id('analytics', $tracker)) {
                        $this->exec('UPDATE', 'analytics', array( // SET bot = 0
                            $width, $height, $hemisphere, $timezone, $dst, $offset, $analytic_id,
                        ));
                        $this->exec('DELETE', 'hits', array($analytic_id)); // AND paths.format = 'html'
                    }
                    break;

                case 'users':
                    list($user_id, $tracker) = $row;
                    if ($analytic_id = $this->id('analytics', $tracker)) {
                        $this->exec('INSERT', 'users', array($analytic_id, $user_id));
                    }
                    break;

                case 'server':
                    $this->exec('INSERT', 'server', $row); // server, dns, tcp, request, response, time
                    break;

                case 'hits':
                    list($tracker, $time, $loaded, $server, $path, $query, $format) = $row;
                    if ($analytic_id = $this->id('analytics', $tracker)) {
                        $path_id = $this->id('paths', $path, array($path, $format));
                        $this->exec('INSERT', 'hits', array(
                            $analytic_id, $time, $loaded, $server, $path_id, $query,
                        ));
                    }
                    break;

                case '404':
                    list($time, $ip, $path, $query) = $row;
                    $this->exec('INSERT', 'not_found', array(
                        $time, $ip, $path, $query,
                    ));
                    break;
            }
        }
        fclose($fp);
        if (isset($this->ids['analytics'])) {
            $stmt = $this->db->update('analytics', 'id', array('hits', 'duration'));
            foreach ($this->db->all(array(
                'SELECT analytic_id AS id, COUNT(*) AS count, MAX(time) AS max, MIN(time) AS min',
                'FROM analytic_hits',
                'WHERE analytic_id IN('.implode(', ', $this->ids['analytics']).')',
                'GROUP BY analytic_id',
            ), '', 'assoc') as $row) {
                $this->db->update($stmt, $row['id'], array($row['count'], ($row['max'] - $row['min'])));
            }
            $this->db->close($stmt);
        }
        $this->db->exec(array(
            'DELETE FROM analytic_sessions WHERE analytic_id IN (',
            '    SELECT s.analytic_id',
            '    FROM analytic_sessions AS s',
            '    INNER JOIN analytics AS a ON s.analytic_id = a.id',
            "    WHERE a.started <= strftime('%s', 'now', '-24 hours')",
            ')',
        ));
        if ($ids = $this->db->ids("SELECT id FROM analytics WHERE started <= strftime('%s', 'now', '-31 days') AND bot = 1")) {
            $ids = '('.implode(', ', $ids).')';
            $this->db->exec('DELETE FROM analytics WHERE id IN'.$ids);
            $this->db->exec('DELETE FROM analytic_hits WHERE analytic_id IN'.$ids);
        }
        $this->db->exec('COMMIT');
        foreach ($this->stmt as $action => $tables) {
            foreach ($tables as $stmt) {
                $this->db->close($stmt);
            }
        }
        $this->stmt = array();
        $this->ids = array();
        unlink($temp);
    }

    public function post($location, $code)
    {
        if (!empty($code)) {
            $this->data[$location][] = (is_array($code)) ? implode("\n\t", $code) : $code;
        }
    }

    public function json()
    {
        $data = $this->data;
        $this->data = array(); // reset
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

        return Page::html()->sendJson($data);
    }

    public function startStop($count, $range, $label = 'Y-m-d H:i:s', array $values = array())
    {
        $array = array();
        $range = substr($range, 0, 3);
        if (in_array($range, array('hou', 'day', 'wee', 'mon', 'yea'))) {
            for ($i = 0; $i < $count; ++$i) {
                switch ($range) {
                    case 'hou':
                        $time = $this->now - ($i * 3600);
                        break;
                    case 'day':
                        $time = $this->now - ($i * 86400);
                        break;
                    case 'wee':
                        $time = $this->now - ($i * 604800);
                        break;
                    case 'mon':
                        $time = strtotime("today -{$i} month");
                        break;
                    case 'yea':
                        $time = strtotime("today -{$i} year");
                        break;
                }
                list($start, $stop, $value) = $this->timerange($time, $range, $label);
                if ($stop < $this->started) {
                    break;
                }
                if (!empty($values)) {
                    $value = array_shift($values);
                }
                if ($start < $this->now) {
                    $array[$value] = array($start, $stop);
                }
            }
        }

        return $array;
    }

    public function timeRange($time, $range, $label = '')
    {
        // G - hour - 0 to 23
        // n - month - 1 to 12
        // j - day - 1 to 31
        // N - day of week - 1 to 7 (monday to sunday)
        // Y - year - 2003
        $time = explode(' ', date('G n j N Y '.$label, ($time - $this->offset)));
        list($G, $n, $j, $N, $Y) = array_map('intval', $time);
        $label = implode(' ', array_slice($time, 5));
        // mktime(H (hour), i (minute), s (second), n (month), j (day), Y (year))
        switch (substr($range, 0, 3)) {
            case 'hou':
                $from = mktime($G, 0, 0, $n, $j, $Y) + $this->offset;
                $to = mktime($G, 59, 59, $n, $j, $Y) + $this->offset;
                break;
            case 'day':
                $from = mktime(0, 0, 0, $n, $j, $Y) + $this->offset;
                $to = mktime(23, 59, 59, $n, $j, $Y) + $this->offset;
                break;
            case 'wee':
                $from = mktime(0, 0, 0, $n, ($j - $N) + 1, $Y) + $this->offset;
                $to = mktime(23, 59, 59, $n, ($j - $N) + 7, $Y) + $this->offset;
                break;
            case 'mon':
                $from = mktime(0, 0, 0, $n, 1, $Y) + $this->offset;
                $to = mktime(23, 59, 59, $n + 1, 0, $Y) + $this->offset;
                break;
            case 'yea':
                $from = mktime(0, 0, 0, 1, 1, $Y) + $this->offset;
                $to = mktime(23, 59, 59, 1, 0, $Y + 1) + $this->offset;
                break;
            default:
                throw new \LogicException("The requested range ({$range}) is invalid");
        }

        return array($from, $to, $label, date('Y-m-d H:i:s', $from), date('Y-m-d H:i:s', $to));
    }

    public function pageViews($path = null, $start = null, $stop = null, $default = 0)
    {
        if (is_null($path)) {
            $path = Page::html()->url['path'];
        }
        if (empty($this->user_id)) {
            $views = $this->db->value(array(
                'SELECT COUNT(*) AS views',
                'FROM analytic_hits AS h',
                'WHERE h.analytic_id IN (',
                '    SELECT a.id',
                '    FROM analytics AS a',
                '    '.$this->where('a.started', $start, $stop, 'a.bot = 0'),
                ')',
                ' AND h.path_id = (SELECT p.id FROM analytic_paths AS p WHERE p.path = ?)',
            ), $path);
        } else {
            $views = $this->db->value(array(
                'SELECT COUNT(*) AS views',
                'FROM analytic_hits AS h',
                'WHERE h.analytic_id IN (',
                '    SELECT a.id',
                '    FROM analytics AS a',
                '    LEFT JOIN analytic_users AS u ON a.id = u.analytic_id AND u.user_id = '.$this->user_id,
                '    '.$this->where('a.started', $start, $stop, 'a.bot = 0', 'u.user_id IS NULL'),
                ')',
                ' AND h.path_id = (SELECT p.id FROM analytic_paths AS p WHERE p.path = ?)',
            ), $path);
        }

        return ($views) ? $views : $default;
    }

    public function userHits($start = null, $stop = null, $default = 0)
    {
        if (empty($this->user_id)) {
            $row = $this->db->row(array(
                'SELECT COUNT(*) AS user, SUM(hits) AS hits',
                'FROM analytics',
                $this->where('started', $start, $stop, 'bot = 0'),
            ), '', 'assoc');
        } else {
            $row = $this->db->row(array(
                'SELECT COUNT(*) AS user, SUM(a.hits) AS hits',
                'FROM analytics AS a',
                'LEFT JOIN analytic_users AS u ON a.id = u.analytic_id AND u.user_id = '.$this->user_id,
                $this->where('a.started', $start, $stop, 'a.bot = 0', 'u.user_id IS NULL'),
            ), '', 'assoc');
        }
        $row['user'] = (empty($row['user'])) ? $default : number_format($row['user']);
        $row['hits'] = (empty($row['hits'])) ? $default : number_format($row['hits']);

        return array_values($row);
    }

    public function robotHits($start = null, $stop = null, $default = 0)
    {
        $row = $this->db->row(array(
            'SELECT DISTINCT(agent_id) AS bots, SUM(hits) AS hits',
            'FROM analytics',
            $this->where('started', $start, $stop, 'bot = 1'),
        ), '', 'assoc');
        $row['bots'] = (empty($row['bots'])) ? $default : number_format($row['bots']);
        $row['hits'] = (empty($row['hits'])) ? $default : number_format($row['hits']);

        return array_values($row);
    }

    public function avgLoadTimes($start = null, $stop = null, $default = 0, $append = '')
    {
        if (empty($this->user_id)) {
            $avg = $this->db->value(array(
                'SELECT AVG(loaded) AS avg',
                'FROM analytic_hits',
                $this->where('time', $start, $stop, 'loaded > 0', 'loaded < 20000'),
            ));
        } else {
            $avg = $this->db->value(array(
                'SELECT AVG(h.loaded) AS avg',
                'FROM analytic_hits AS h',
                'LEFT JOIN analytic_users AS u ON h.analytic_id = u.analytic_id AND u.user_id = '.$this->user_id,
                $this->where('h.time', $start, $stop, 'h.loaded > 0', 'h.loaded < 20000', 'u.user_id IS NULL'),
            ));
        }
        if (empty($avg)) {
            return $default;
        }
        $avg = explode('.', round($avg / 1000, 2));

        return trim(array_shift($avg).'.'.str_pad(array_shift($avg), 2, 0).' '.$append);
    }

    public function avgSessionDuration($start = null, $stop = null, $default = 0, $append = '')
    {
        if (empty($this->user_id)) {
            $avg = $this->db->value(array(
                'SELECT ROUND(AVG(a.duration)) / 60000 AS avg',
                'FROM analytics AS a',
                'LEFT JOIN analytic_users AS u ON a.id = u.analytic_id',
                $this->where('a.started', $start, $stop, 'a.bot = 0', 'a.duration > 0'),
            ));
        } else {
            $avg = $this->db->value(array(
                'SELECT ROUND(AVG(a.duration)) / 60000 AS avg',
                'FROM analytics AS a',
                'LEFT JOIN analytic_users AS u ON a.id = u.analytic_id AND u.user_id = '.$this->user_id,
                $this->where('a.started', $start, $stop, 'a.bot = 0', 'a.duration > 0', 'u.user_id IS NULL'),
            ));
        }
        if (empty($avg)) {
            return $default;
        }
        $avg = explode('.', round($avg, 2));

        return trim(array_shift($avg).'.'.str_pad(array_shift($avg), 2, 0).' '.$append);
    }

    public function location($timezone, $hemisphere = '')
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

    private function where($field, $start, $stop)
    {
        $params = array_slice(func_get_args(), 3);
        $where = array();
        if (!empty($start)) {
            $where[] = $field.' >= '.(int) $start;
        }
        if (!empty($stop)) {
            $where[] = $field.' <= '.(int) $stop;
        }
        if (empty($where)) {
            $where[] = $field.' > 0';
        }
        foreach ($params as $value) {
            $where[] = $value;
        }

        return (!empty($where)) ? 'WHERE '.implode(' AND ', $where) : '';
    }

    private function id($table, $value, array $insert = array())
    {
        if (isset($this->ids[$table][$value])) {
            return $this->ids[$table][$value];
        }
        switch ($table) {
            case 'agents':
                if ($row = $this->exec('SELECT', 'agents', $value)) { // agent
                    $id = array_shift($row);
                    if ($row != $insert) {
                        list($agent, $robot, $browser, $version, $mobile, $desktop) = $insert;
                        $this->exec('UPDATE', 'agents', array($robot, $browser, $version, $mobile, $desktop, $id));
                    }
                } else {
                    $id = $this->exec('INSERT', 'agents', $insert);
                }
                break;
            case 'paths':
                if ($row = $this->exec('SELECT', 'paths', $value)) { // path
                    $id = array_shift($row);
                    list($path, $format) = $insert;
                    if ($row['format'] != $format) {
                        $this->exec('UPDATE', 'paths', array($format, $id));
                    }
                } else {
                    $id = $this->exec('INSERT', 'paths', $insert);
                }
                break;
            case 'analytics':
                if ($row = $this->exec('SELECT', 'analytics', explode('::', $value))) { // started::session
                    $id = array_shift($row);
                }
                break;
        }
        if (isset($id)) {
            $this->ids[$table][$value] = $id;
        }

        return (isset($this->ids[$table][$value])) ? $this->ids[$table][$value] : false;
    }

    private function exec($action, $table, $values = null)
    {
        $action = strtolower($action);
        if (!isset($this->stmt[$action][$table])) {
            switch ($action) {
                case 'select':
                    if ($table == 'analytics') {
                        $stmt = $this->db->prepare('SELECT id FROM analytics AS a INNER JOIN analytic_sessions AS s WHERE a.started = ? AND a.id = s.analytic_id AND s.session = ?');
                    } elseif ($table == 'agents') {
                        $stmt = $this->db->prepare('SELECT id, agent, robot, browser, version, mobile, desktop FROM analytic_agents WHERE agent = ?', 'assoc');
                    } elseif ($table == 'paths') {
                        $stmt = $this->db->prepare('SELECT id, format FROM analytic_paths WHERE path = ?', 'assoc');
                    }
                    break;
                case 'insert':
                    if ($table == 'analytics') {
                        $stmt = $this->db->insert('analytics', array('started', 'agent_id', 'path_id', 'query', 'referrer', 'ip'));
                    } elseif ($table == 'agents') {
                        $stmt = $this->db->insert('analytic_agents', array('agent', 'robot', 'browser', 'version', 'mobile', 'desktop'));
                    } elseif ($table == 'hits') {
                        $stmt = $this->db->insert('analytic_hits', array('analytic_id', 'time', 'loaded', 'server', 'path_id', 'query'));
                    } elseif ($table == 'not_found') {
                        $stmt = $this->db->insert('analytic_not_found', array('time', 'ip', 'path', 'query'));
                    } elseif ($table == 'paths') {
                        $stmt = $this->db->insert('analytic_paths', array('path', 'format'));
                    } elseif ($table == 'server') {
                        $stmt = $this->db->insert('analytic_server', array('server', 'dns', 'tcp', 'request', 'response', 'time'));
                    } elseif ($table == 'sessions') {
                        $stmt = $this->db->insert('analytic_sessions', array('analytic_id', 'session'));
                    } elseif ($table == 'users') {
                        $stmt = $this->db->insert('analytic_users', array('analytic_id', 'user_id'));
                    }
                    break;
                case 'update':
                    if ($table == 'analytics') {
                        $stmt = $this->db->update('analytics SET bot = 0,', 'id', array('width', 'height', 'hemisphere', 'timezone', 'dst', 'offset'));
                    } elseif ($table == 'agents') {
                        $stmt = $this->db->update('analytic_agents', 'id', array('robot', 'browser', 'version', 'mobile', 'desktop'));
                    } elseif ($table == 'paths') {
                        $stmt = $this->db->update('analytic_paths', 'id', array('format'));
                    }
                    break;
                case 'delete':
                    if ($table == 'hits') {
                        $stmt = $this->db->prepare('DELETE FROM analytic_hits WHERE id IN (SELECT h.id FROM analytic_hits AS h INNER JOIN analytic_paths AS p WHERE h.time > 0 AND h.analytic_id = ? AND h.path_id = p.id AND p.format = "html")');
                    }
                    break;
            }
            $this->stmt[$action][$table] = (isset($stmt)) ? $stmt : null;
        }
        $result = $this->db->execute($this->stmt[$action][$table], $values);
        if ($action == 'select') {
            $row = $this->db->fetch($this->stmt[$action][$table]);

            return (!empty($row)) ? $row : false;
        } else {
            return $result;
        }
    }

    private function setupDatabaseTables()
    {
        $this->db->create('analytics', array( // delete bots after 31 days
            'id' => 'INTEGER PRIMARY KEY',
            'hits' => 'INTEGER NOT NULL DEFAULT 0',
            'duration' => 'INTEGER NOT NULL DEFAULT 0', // seconds
            'started' => 'INTEGER NOT NULL DEFAULT 0',
            'bot' => 'INTEGER NOT NULL DEFAULT 1',
            'agent_id' => 'INTEGER NOT NULL DEFAULT 0',
            'path_id' => 'INTEGER NOT NULL DEFAULT 0',
            'query' => 'TEXT NOT NULL DEFAULT ""',
            'referrer' => 'TEXT NOT NULL DEFAULT ""',
            'ip' => 'TEXT NOT NULL DEFAULT ""',
            'width' => 'INTEGER NOT NULL DEFAULT 0',
            'height' => 'INTEGER NOT NULL DEFAULT 0',
            'hemisphere' => 'TEXT NOT NULL DEFAULT ""',
            'timezone' => 'TEXT NOT NULL DEFAULT ""',
            'dst' => 'INTEGER NOT NULL DEFAULT 0',
            'offset' => 'INTEGER NOT NULL DEFAULT 0', // seconds
        ), 'started, bot, agent_id');
        $this->db->create('analytic_agents', array(
            'id' => 'INTEGER PRIMARY KEY',
            'agent' => 'TEXT UNIQUE NOT NULL DEFAULT ""', // up to 255 varchar string
            'robot' => 'TEXT NOT NULL DEFAULT ""',
            'browser' => 'TEXT NOT NULL DEFAULT ""',
            'version' => 'TEXT NOT NULL DEFAULT ""',
            'mobile' => 'TEXT NOT NULL DEFAULT ""',
            'desktop' => 'TEXT NOT NULL DEFAULT ""',
        ));
        $this->db->create('analytic_hits', array( // delete bot hits after 31 days
            'id' => 'INTEGER PRIMARY KEY',
            'analytic_id' => 'INTEGER NOT NULL DEFAULT 0',
            'time' => 'INTEGER NOT NULL DEFAULT 0',
            'loaded' => 'INTEGER NOT NULL DEFAULT 0', // microseconds
            'server' => 'INTEGER NOT NULL DEFAULT 0', // microseconds
            'path_id' => 'INTEGER NOT NULL DEFAULT 0',
            'query' => 'TEXT NOT NULL DEFAULT ""',
        ), 'analytic_id, path_id');
        $this->db->create('analytic_not_found', array(
            'id' => 'INTEGER PRIMARY KEY',
            'time' => 'INTEGER NOT NULL DEFAULT 0',
            'ip' => 'TEXT NOT NULL DEFAULT ""',
            'path' => 'TEXT NOT NULL DEFAULT ""',
            'query' => 'TEXT NOT NULL DEFAULT ""',
        ));
        $this->db->create('analytic_paths', array(
            'id' => 'INTEGER PRIMARY KEY',
            'path' => 'TEXT UNIQUE NOT NULL DEFAULT ""',
            'format' => 'TEXT NOT NULL DEFAULT ""',
        ));
        $this->db->create('analytic_server', array(
            'id' => 'INTEGER PRIMARY KEY',
            'server' => 'INTEGER NOT NULL DEFAULT 0', // microseconds
            'dns' => 'INTEGER NOT NULL DEFAULT 0', // microseconds
            'tcp' => 'INTEGER NOT NULL DEFAULT 0', // microseconds
            'request' => 'INTEGER NOT NULL DEFAULT 0', // microseconds
            'response' => 'INTEGER NOT NULL DEFAULT 0', // microseconds
            'time' => 'INTEGER NOT NULL DEFAULT 0',
        ), 'time');
        $this->db->create('analytic_sessions', array( // delete after 24 hours
            'analytic_id' => 'INTEGER PRIMARY KEY',
            'session' => 'TEXT NOT NULL DEFAULT ""',
        ));
        $this->db->create('analytic_users', array(
            'analytic_id' => 'INTEGER NOT NULL DEFAULT 0',
            'user_id' => 'INTEGER NOT NULL DEFAULT 0',
        ), array('unique' => 'analytic_id, user_id'));
    }
}
