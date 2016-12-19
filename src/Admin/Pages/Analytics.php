<?php

namespace BootPress\Admin\Pages;

use BootPress\Admin\Component as Admin;
use BootPress\Analytics\Component as BPA;

class Analytics
{
    private $db;
    private $now;
    private $offset;
    private $user_id;

    public static function setup($auth, $path)
    {
        return ($auth->isAdmin(2)) ? array(Admin::$bp->icon('line-chart', 'fa').' Analytics' => array(
            Admin::$bp->icon('area-chart', 'fa').' Visitors' => '',
            Admin::$bp->icon('link', 'fa').' Referrers' => 'referrers',
            Admin::$bp->icon('files-o', 'fa').' Pages' => 'pages',
            Admin::$bp->icon('server', 'fa').' Server' => 'server',
            Admin::$bp->icon('apple', 'fa').' Users' => 'users',
            Admin::$bp->icon('sitemap', 'fa').' Robots' => 'robots',
        )) : false;
    }

    public static function page()
    {
        extract(Admin::params('bp', 'page', 'auth', 'path', 'method'));
        $bp->pagination->html('links', array('wrapper' => '<ul class="pagination pagination-sm no-margin">{{ value }}</ul>'));
        if (empty($method)) {
            $method = 'visitors';
        }
        BPA::process();
        $analytics = new self();

        return $analytics->$method();
    }

    private function __construct()
    {
        extract(Admin::params('page'));
        $this->db = BPA::database();
        $this->now = time();
        $this->offset = ($user = $page->session->get('analytics')) ? (int) $user['offset'] : 0;
        $this->user_id = ($user = $page->session->get('bootpress')) ? (int) $user['id'] : null;
    }

    private function visitors()
    {
        extract(Admin::params('bp', 'website', 'page'));
        $page->title = 'Visitors at '.$website;
        $html = '';
        $data = array();
        foreach ($this->startStop(31, 'day', 'Y-m-d') as $x => $info) { // D M j
            list($start, $stop) = $info;
            list($user, $hits) = array_values($this->userHits($start, $stop));
            $data[] = '{x:"'.$x.'", hits:'.$hits.', users:'.$user.', avg:'.($user > 0 ? round($hits / $user, 1) : 0).'}';
        }
        $page->jquery('
            new Morris.Area({
                behaveLikeLine: true,
                element: "visitors-chart",
                resize: true,
                data: ['.implode(',', $data).'],
                xkey: "x",
                xLabels: "month",
                xLabelFormat: function(x){ var str = x.toDateString(); return str.substr(4,4) + str.substr(-4); },
                dateFormat: function(x){ return new Date(x).toDateString().slice(0,-5); },
                ykeys: ["hits", "users", "avg"],
                labels: ["Pageviews", "Number of Users", "Avg Views per User"],
                lineColors: ["#3C8DBC", "#00A65A", "#F56954"],
                hideHover: "auto"
            });
        ');
        $page->link(array(
            'https://cdn.jsdelivr.net/morris.js/0.5.1/morris.min.js',
            'https://cdn.jsdelivr.net/morris.js/0.5.1/morris.css',
        ));
        $page->link('https://cdnjs.cloudflare.com/ajax/libs/raphael/2.2.7/raphael.min.js', 'prepend');
        $html .= $bp->table->open('class=hover');
        $visits = array();
        $visits['Since'] = array(date('M Y', $this->started()) => array($this->started(), time()));
        $visits['Past Day'] = $this->startStop(24, 'hour', 'g:00a', array('This Hour', 'Last Hour'));
        $visits['Past Week'] = $this->startStop(7, 'day', 'l', array('Today', 'Yesterday'));
        $visits['Past Month'] = $this->startStop(5, 'week', '', array('This Week', 'Last Week', '2 weeks ago', '3 weeks ago', '4 weeks ago'));
        $visits['Past Year'] = $this->startStop(12, 'month', 'M Y', array('This Month', 'Last Month'));
        foreach ($visits as $header => $values) {
            $html .= $bp->table->head();
            $html .= $bp->table->cell('', $header);
            $html .= $bp->table->cell('class=text-right', 'Robots');
            $html .= $bp->table->cell('', 'Hits');
            $html .= $bp->table->cell('class=text-right', 'Users');
            $html .= $bp->table->cell('', 'Hits');
            $html .= $bp->table->cell('class=text-center', 'Avg Load Times');
            $html .= $bp->table->cell('class=text-center', 'Avg Session Duration');
            foreach ($values as $reference => $times) {
                list($start, $stop) = $times;
                $user = $this->userHits($start, $stop, '-');
                $robot = $this->robotHits($start, $stop, '-');
                $html .= $bp->table->row();
                $html .= $bp->table->cell('', $reference);
                $html .= $bp->table->cell('class=text-right', $robot['ips']);
                $html .= $bp->table->cell('', $robot['hits']);
                $html .= $bp->table->cell('class=text-right', $user['sessions']);
                $html .= $bp->table->cell('', $user['hits']);
                $html .= $bp->table->cell('class=text-center', $user['loaded']);
                $html .= $bp->table->cell('class=text-center', $user['duration']);
            }
        }
        $html .= $bp->table->close();

        return Admin::box('default', array(
            'head with-border' => $bp->icon('line-chart', 'fa').' Visitors',
            'body' => '<div id="visitors-chart" style="height:300px;"></div>',
            'body no-padding table-responsive' => $html,
        ));
    }

    private function referrers()
    {
        extract(Admin::params('bp', 'website', 'page'));
        $page->title = 'Referrer Analytics at '.$website;
        $db = BPA::database();
        $html = '';
        if (!$bp->pagination->set('page', 100)) {
            $bp->pagination->total($db->value(array(
                'SELECT COUNT(*) FROM analytic_sessions AS s',
                'LEFT JOIN analytic_users AS u ON s.id = u.session_id AND u.user_id = ?',
                'WHERE s.started > ? AND s.referrer != "" AND u.user_id IS NULL',
            ), array($this->user_id, ($this->now - 2592000))));
        }
        if ($result = $db->query(array(
            'SELECT s.hits, s.duration, s.started, s.referrer, p.path, s.query, s.hemisphere, s.timezone',
            'FROM analytic_sessions AS s',
            'INNER JOIN analytic_paths AS p ON s.path_id = p.id',
            'LEFT JOIN analytic_users AS u ON s.id = u.session_id AND u.user_id = ?',
            'WHERE s.started > ? AND s.referrer != "" AND u.user_id IS NULL',
            'ORDER BY s.started DESC'.$bp->pagination->limit,
        ), array($this->user_id, ($this->now - 2592000)), 'row')) {
            $html .= $bp->table->open('class=hover');
            $html .= $bp->table->head();
            $html .= $bp->table->cell('', 'Referrer');
            $html .= $bp->table->cell('', 'Page');
            $html .= $bp->table->cell('', 'Location');
            $html .= $bp->table->cell('class=text-center', 'Hits');
            $html .= $bp->table->cell('class=text-center', 'Date');
            while (list($hits, $duration, $started, $referrer, $path, $query, $hemisphere, $timezone) = $db->fetch($result)) {
                preg_match('/\/\/([\S]+\.[a-z]{2,4})\//i', $referrer, $matches);
                $website = array_pop($matches);
                $location = BPA::location($timezone, $hemisphere);
                $html .= $bp->table->row();
                $html .= $bp->table->cell('', '<a href="'.$referrer.'" title="'.$website.'">'.$this->ellipsize($website, 50).'</a>');
                $html .= $bp->table->cell('', '<a href="'.$page->url($path.$query).'" title="'.$path.'">'.$this->ellipsize($path, 50).'</a>');
                $html .= $bp->table->cell('', '<span title="'.$location.'">'.$this->ellipsize($location, 50).'</span>');
                $html .= $bp->table->cell('class=text-center', $hits);
                $html .= $bp->table->cell('class=text-center', '<span class="timeago" title="'.date('c', $started).'">'.$started.'</span>');
            }
            $html .= $bp->table->close();
            $db->close($result);
        }

        return Admin::box('default', array(
            'head with-border' => $bp->icon('link', 'fa').' Referrers <small style="margin-left:10px;">Last 30 Days</small>',
            'body no-padding table-responsive' => $html,
            'foot clearfix' => $bp->pagination->links(),
        ));
    }

    private function pages()
    {
        extract(Admin::params('bp', 'website', 'page'));
        $page->title = 'Analytic Pages at '.$website;
        $html = $bp->table->open('class=hover');
        $html .= $bp->table->head();
        $html .= $bp->table->cell('', 'Most Popular (last 30 days)');
        $html .= $bp->table->cell('class=text-center', 'Hits');
        if ($result = $this->db->query(array(
            'SELECT p.path, COUNT(h.path_id) AS hits',
            'FROM analytic_hits AS h',
            'INNER JOIN analytic_paths AS p ON h.path_id = p.id',
            'LEFT JOIN analytic_users AS u ON h.session_id = u.session_id AND u.user_id = ?',
            'WHERE h.time > ? AND u.user_id IS NULL',
            'GROUP BY h.path_id ORDER BY hits DESC LIMIT 50',
        ), array($this->user_id, ($this->now - 2592000)), 'row')) {
            while (list($path, $hits) = $this->db->fetch($result)) {
                $html .= $bp->table->row();
                $html .= $bp->table->cell('', '<a href="'.$page->url($path).'">'.(empty($path) ? '(index)' : $path).'</a>');
                $html .= $bp->table->cell('class=text-center', $hits);
            }
            $this->db->close($result);
        }
        $html .= $bp->table->close();
        $popular = Admin::box('default', array(
            'body no-padding table-responsive' => $html,
        ));

        $html = $bp->table->open('class=hover');
        $html .= $bp->table->head();
        $html .= $bp->table->cell('', 'Most Recent (per user)');
        $html .= $bp->table->cell('class=text-center', 'Date');
        $html .= $bp->table->cell('class=text-center', 'IP');
        if ($result = $this->db->query(array(
            'SELECT h.time, p.path, h.query, s.id, s.ip',
            'FROM analytic_hits AS h',
            'INNER JOIN analytic_paths AS p ON h.path_id = p.id',
            'INNER JOIN analytic_sessions AS s ON h.session_id = s.id',
            'LEFT JOIN analytic_users AS u ON h.session_id = u.session_id AND u.user_id = ?',
            'WHERE h.time > ? AND u.user_id IS NULL',
            'GROUP BY s.id ORDER BY h.id DESC LIMIT 50',
        ), array($this->user_id, ($this->now - 2592000)), 'row')) {
            while (list($time, $path, $query, $id, $ip) = $this->db->fetch($result)) {
                $html .= $bp->table->row();
                $html .= $bp->table->cell('', '<a href="'.$page->url($path.$query).'">'.(empty($path) ? '(index)' : $path).'</a>');
                $html .= $bp->table->cell('class=text-center', '<span class="timeago" title="'.date('c', $time).'">'.$time.'</span>');
                $html .= $bp->table->cell('class=text-center', $ip);
                // $html .= $bp->table->cell('class=text-center', '<a href="'.$page->url('add', '', 'id', $id).'">'.$ip.'</a>');
            }
        }
        $html .= $bp->table->close();
        $recent = Admin::box('default', array(
            'body no-padding table-responsive' => $html,
        ));

        return Admin::box('default', array(
            'head with-border' => $bp->icon('files-o', 'fa').' Pages <small style="margin-left:10px;">Last 30 Days</small>',
        )).$popular.$recent;
    }

    private function server()
    {
        extract(Admin::params('bp', 'website', 'page'));
        $page->title = 'Server Analytics at '.$website;
        $page->link(array(
            'https://cdn.jsdelivr.net/morris.js/0.5.1/morris.min.js',
            'https://cdn.jsdelivr.net/morris.js/0.5.1/morris.css',
        ));
        $page->link('https://cdnjs.cloudflare.com/ajax/libs/raphael/2.2.7/raphael.min.js', 'prepend');

        // http://morrisjs.github.io/morris.js/lines.html
        // xLabelFormat (below): function(x){ return x.toDateString().substr(4); },
        // dateFormat (hover): function(x){ return new Date(x).toTimeString().slice(0,5); },

        // http://www.w3schools.com/jsref/jsref_obj_date.asp
        // x.toDateString() - Wed Dec 14 2016
        // x.toTimeString() - 10:00:58 GMT-0900 (Alaskan Standard Time)
        // x.toString()     - Wed Dec 14 2016 10:02:01 GMT-0900 (Alaskan Standard Time)

        $html = '';

        // Prepare statement
        $stmt = $this->db->prepare(array(
            'SELECT',
            '   AVG(CASE WHEN h.loaded > 0 AND h.loaded < 20000 THEN h.loaded END) / 1000 AS loaded,',
            '   AVG(CASE WHEN h.server > 0 AND h.server < 20000 THEN h.server END) / 1000 AS server',
            'FROM analytic_hits AS h',
            'WHERE h.time >= ? AND h.time <= ?',
        ), 'row');

        // Past 3 Hours
        $data = array();
        foreach ($this->startStop(180, 'minute', 'Y-m-d H:i:s') as $x => $info) { // D M j
            list($start, $stop) = $info;
            $this->db->execute($stmt, array($start, $stop));
            list($loaded, $server) = $this->db->fetch($stmt);
            $data[] = '{x:"'.$x.'", loaded:'.round($loaded, 2).', server:'.round($server, 2).'}';
        }
        $page->jquery('
            var minute = new Morris.Area({
                behaveLikeLine: true,
                element: "minute-chart",
                resize: true,
                data: ['.implode(',', $data).'],
                xkey: "x",
                xLabels: "30min",
                dateFormat: function(x){ return new Date(x).toTimeString().slice(0,5); },
                ykeys: ["loaded", "server"],
                labels: ["Loaded", "Server"],
                lineColors: ["#a0d0e0", "#3c8dbc"],
                hideHover: "auto"
            });
        ');
        $html .= Admin::box('default', array(
            'head with-border' => $bp->icon('server', 'fa').' Past 3 Hours <small style="margin-left:10px;">By The Minute</small>',
            'body' => '<div class="chart" id="minute-chart" style="height:150px;"></div>',
        ));

        // Past Week
        $data = array();
        foreach ($this->startStop(168, 'hour', 'Y-m-d H:i:s') as $x => $info) { // D M j
            list($start, $stop) = $info;
            $this->db->execute($stmt, array($start, $stop));
            list($loaded, $server) = $this->db->fetch($stmt);
            $data[] = '{x:"'.$x.'", loaded:'.round($loaded, 2).', server:'.round($server, 2).'}';
        }
        $page->jquery('
            var hour = new Morris.Area({
                behaveLikeLine: true,
                element: "hour-chart",
                resize: true,
                data: ['.implode(',', $data).'],
                xkey: "x",
                xLabels: "day",
                xLabelFormat: function(x){ return x.toDateString().slice(0,-5); },
                dateFormat: function(x){ return new Date(x).toDateString().slice(0,4) + new Date(x).toTimeString().slice(0,5); },
                ykeys: ["loaded", "server"],
                labels: ["Loaded", "Server"],
                lineColors: ["#a0d0e0", "#3c8dbc"],
                hideHover: "auto"
            });
        ');
        $html .= Admin::box('default', array(
            'head with-border' => $bp->icon('server', 'fa').' Past Week <small style="margin-left:10px;">By The Hour</small>',
            'body' => '<div class="chart" id="hour-chart" style="height:150px;"></div>',
        ));

        // Past 6 Months
        $data = array();
        foreach ($this->startStop(180, 'day', 'Y-m-d H:i:s') as $x => $info) { // D M j
            list($start, $stop) = $info;
            $this->db->execute($stmt, array($start, $stop));
            list($loaded, $server) = $this->db->fetch($stmt);
            $data[] = '{x:"'.$x.'", loaded:'.round($loaded, 2).', server:'.round($server, 2).'}';
        }
        $page->jquery('
            var day = new Morris.Area({
                behaveLikeLine: true,
                element: "day-chart",
                resize: true,
                data: ['.implode(',', $data).'],
                xkey: "x",
                xLabels: "month",
                xLabelFormat: function(x){ return x.toDateString().substr(4); },
                dateFormat: function(x){ return new Date(x).toDateString().slice(0,-5); },
                ykeys: ["loaded", "server"],
                labels: ["Loaded", "Server"],
                lineColors: ["#a0d0e0", "#3c8dbc"],
                hideHover: "auto"
            });
        ');
        $html .= Admin::box('default', array(
            'head with-border' => $bp->icon('server', 'fa').' Past 6 Months <small style="margin-left:10px;">By The Day</small>',
            'body' => '<div class="chart" id="day-chart" style="height:150px;"></div>',
        ));

        // Close statement
        $this->db->close($stmt);

        return $html;
    }

    private function users($data = null)
    {
        if (is_array($data)) {
            $colors = array(
                '#F39C12', // orange
                '#F56954', // red
                '#00A65A', // green
                '#3C8DBC', // dk. blue
                '#00C0EF', // lt. blue
                '#D2D6DE', // lt. gray
            );
            // $colors = array('#F56954', '#00A65A', '#F39C12', '#00C0EF', '#3C8DBC', '#D2D6DE'); // red, green, orange, lt. blue, blue, lt. gray
            foreach ($data as $key => $value) {
                if (!empty($key) && ($percent = round($value)) > 0) {
                    $color = array_shift($colors);
                    $data[$key] = "{value:{$percent},color:\"{$color}\",label:\"{$key}\"}";
                    array_push($colors, $color);
                } else {
                    unset($data[$key]);
                }
            }

            return (!empty($data)) ? '['.implode(', ', $data).']' : null;
        }
        extract(Admin::params('bp', 'blog', 'website', 'page'));
        $page->title = 'User Analytics at '.$website;
        $page->style(array(
            'canvas { display:inline; }',
            '.canvas-container { width:100%; text-align:center; }',
            '.vcenter' => array(
                'display: inline-block;',
                'vertical-align: middle;',
                'float: none;',
            ),
        ));
        $page->link('https://cdn.jsdelivr.net/chart.js/1.0.1/Chart.min.js');
        $html = '';
        $options = array(
            'animation:false',
            'legendTemplate:"<ul class=\"<%=name.toLowerCase()%>-legend list-unstyled\"><% for (var i=0; i<segments.length; i++){%><li><p><i class=\"fa fa-circle-o\" style=\"color:<%=segments[i].fillColor%>; margin-right:10px;\"></i><%=segments[i].value%>% - <%=segments[i].label%></p></li><%}%></ul>"',
            'tooltipTemplate:"<%=value %>% - <%=label%>"',
        );
        $options = '{'.implode(', ', $options).'}';
        $total = 0;
        $mobile = array();
        $platforms = array();
        $browsers = array();
        $versions = array();
        if ($result = $this->db->query(array(
            'SELECT s.hits, a.browser, a.version, a.mobile, a.desktop',
            'FROM analytic_sessions AS s',
            'INNER JOIN analytic_agents AS a ON s.agent_id = a.id',
            'LEFT JOIN analytic_users AS u ON s.id = u.session_id AND u.user_id = ?',
            'WHERE s.started > ? AND u.user_id IS NULL',
        ), array($this->user_id, ($this->now - 2592000)), 'row')) {
            while (list($hits, $browser, $version, $phone, $desktop) = $this->db->fetch($result)) {
                // Total
                $total += $hits;
                // Mobile
                if (!empty($phone)) {
                    if (!isset($mobile[$phone])) {
                        $mobile[$phone] = 0;
                    }
                    $mobile[$phone] += $hits;
                }
                // Platforms
                if (!isset($platforms[$desktop])) {
                    $platforms[$desktop] = 0;
                }
                $platforms[$desktop] += $hits;
                // Browsers
                if (!isset($browsers[$browser])) {
                    $browsers[$browser] = 0;
                }
                $browsers[$browser] += $hits;
                // Versions
                $version = (int) $version;
                if (!isset($versions[$browser][$version])) {
                    $versions[$browser][$version] = 0;
                }
                $versions[$browser][$version] += $hits;
            }
            $this->db->close($result);
        }
        // Mobile
        foreach ($mobile as $phone => $hits) {
            $mobile[$phone] = ($hits / $total) * 100;
        }
        arsort($mobile);
        if ($data = $this->users($mobile)) {
            $html .= '<br>'.$bp->row('sm', array(
                $bp->col('6 vcenter', '<div class="canvas-container"><canvas id="mobileChart" height="250"></canvas></div>'),
                $bp->col('5 vcenter', '<p class="lead">Mobile ('.round(array_sum($mobile)).'% of Users)</p><div id="mobileChartLegend"></div>'),
            )).'<br>';
            $page->script(array(
                'var mobileChartCanvas = document.getElementById("mobileChart").getContext("2d");',
                'var mobileChart = new Chart(mobileChartCanvas).Doughnut('.$data.', '.$options.');',
                'document.getElementById("mobileChartLegend").innerHTML = mobileChart.generateLegend();',
            ));
        }
        // Platforms
        foreach ($platforms as $platform => $hits) {
            $platforms[$platform] = ($hits / $total) * 100;
        }
        arsort($platforms);
        if ($data = $this->users($platforms)) {
            $html .= '<br>'.$bp->row('sm', array(
                $bp->col('6 vcenter', '<div class="canvas-container"><canvas id="platformsChart" height="250"></canvas></div>'),
                $bp->col('5 vcenter', '<p class="lead">Platforms</p><div id="platformsChartLegend"></div>'),
            )).'<br>';
            $page->script(array(
                'var platformsChartCanvas = document.getElementById("platformsChart").getContext("2d");',
                'var platformsChart = new Chart(platformsChartCanvas).Doughnut('.$data.', '.$options.');',
                'document.getElementById("platformsChartLegend").innerHTML = platformsChart.generateLegend();',
            ));
        }
        // Browsers
        foreach ($browsers as $browser => $hits) {
            $browsers[$browser] = ($hits / $total) * 100;
            foreach ($versions[$browser] as $version => $hits) {
                $versions[$browser][$version] = ($hits / $total) * 100;
            }
            arsort($versions[$browser]);
        }
        arsort($browsers);
        if ($data = $this->users($browsers)) {
            $html .= '<br>'.$bp->row('sm', array(
                $bp->col('6 vcenter', '<div class="canvas-container"><canvas id="browsersChart" height="250"></canvas></div>'),
                $bp->col('5 vcenter', '<p class="lead">Browsers</p><div id="browsersChartLegend"></div>'),
            )).'<br>';
            $page->script(array(
                'var browsersChartCanvas = document.getElementById("browsersChart").getContext("2d");',
                'var browsersChart = new Chart(browsersChartCanvas).Doughnut('.$data.', '.$options.');',
                'document.getElementById("browsersChartLegend").innerHTML = browsersChart.generateLegend();',
            ));
        }
        // Versions
        $options = str_replace(' - ', ' - version ', $options);
        foreach ($browsers as $browser => $share) {
            if (!empty($browser) && ($percent = round($share)) > 0 && isset($versions[$browser]) && ($data = $this->users($versions[$browser]))) {
                $seo = $blog->url($browser);
                $html .= '<br>'.$bp->row('sm', array(
                    $bp->col('6 vcenter', '<div class="canvas-container"><canvas id="'.$seo.'Chart" height="250"></canvas></div>'),
                    $bp->col('5 vcenter', '<p class="lead">'.$browser.' ('.$percent.'% of Users)</p><div id="'.$seo.'ChartLegend"></div>'),
                )).'<br>';
                $page->script(array(
                    'var '.$seo.'ChartCanvas = document.getElementById("'.$seo.'Chart").getContext("2d");',
                    'var '.$seo.'Chart = new Chart('.$seo.'ChartCanvas).Doughnut('.$data.', '.$options.');',
                    'document.getElementById("'.$seo.'ChartLegend").innerHTML = '.$seo.'Chart.generateLegend();',
                ));
            }
        }
        if (!empty($html)) {
            $html = '<div style="margin:20px;">'.$html.'</div>';
        }

        return Admin::box('default', array(
            'head with-border' => $bp->icon('apple', 'fa').' Users <small style="margin-left:10px;">Last 30 Days</small>',
            'body' => $html,
        ));
    }

    private function robots()
    {
        extract(Admin::params('bp', 'blog', 'website', 'page'));
        $page->title = 'Robot Analytics at '.$website;
        $html = '';
        $url = $page->url('delete', '', '?');
        if (($agent = $page->get('agent')) && $row = $this->db->row(array(
            'SELECT id, agent, robot',
            'FROM analytic_agents',
            'WHERE agent = ?',
        ), $agent, 'assoc')) {
            $header = !empty($row['robot']) ? $row['robot'] : $row['agent'];
            if (!$bp->pagination->set('page', 100)) {
                $bp->pagination->total($this->db->value(array(
                    'SELECT COUNT(*) FROM analytic_bots WHERE agent_id = ?',
                ), $row['id']));
            }
            if ($result = $this->db->query(array(
                'SELECT p.path, b.query, b.time, b.ip',
                'FROM analytic_bots AS b',
                'INNER JOIN analytic_paths AS p ON b.path_id = p.id',
                'WHERE b.agent_id = ? ORDER BY time DESC'.$bp->pagination->limit,
            ), $row['id'], 'row')) {
                $html .= $bp->table->open('class=hover');
                $html .= $bp->table->head();
                $html .= $bp->table->cell('', 'URL');
                $html .= $bp->table->cell('', 'IP');
                $html .= $bp->table->cell('class=text-center', 'Accessed');
                $html .= $bp->table->cell('class=text-center', 'Next');
                $delayed = null;
                while (list($path, $query, $time, $ip) = $this->db->fetch($result)) {
                    $html .= $bp->table->row();
                    $html .= $bp->table->cell('', '<a href="'.$page->url($path.$query).'">'.$this->ellipsize($path, 50).'</a>');
                    $html .= $bp->table->cell('', '<a href="'.$page->url('add', $url, 'ip', $ip).'">'.$ip.'</a>');
                    $html .= $bp->table->cell('class=text-center', date('D, M m Y, h:i a', $time - $this->offset));
                    $html .= $bp->table->cell('class=text-center', $this->next($delayed, $time));
                }
                $html .= $bp->table->close();
                $this->db->close($result);
            }
        } elseif ($ip = $page->get('ip')) {
            $header = strip_tags($ip);
            if (!$bp->pagination->set('page', 100)) {
                $bp->pagination->total($this->db->value(array(
                    'SELECT COUNT(*) FROM analytic_bots WHERE ip = ?',
                ), $header));
            }
            if ($result = $this->db->query(array(
                'SELECT p.path, b.query, b.time, a.agent',
                'FROM analytic_bots AS b',
                'INNER JOIN analytic_paths AS p ON b.path_id = p.id',
                'INNER JOIN analytic_agents AS a ON b.agent_id = a.id',
                'WHERE b.ip = ? ORDER BY time DESC'.$bp->pagination->limit,
            ), $header, 'row')) {
                $html .= $bp->table->open('class=hover');
                $html .= $bp->table->head();
                $html .= $bp->table->cell('', 'URL');
                $html .= $bp->table->cell('', 'User Agent');
                $html .= $bp->table->cell('class=text-center', 'Accessed');
                $html .= $bp->table->cell('class=text-center', 'Next');
                $delayed = null;
                while (list($path, $query, $time, $agent) = $this->db->fetch($result)) {
                    $html .= $bp->table->row();
                    $html .= $bp->table->cell('', '<a href="'.$page->url($path.$query).'">'.$this->ellipsize($path, 50).'</a>');
                    $html .= $bp->table->cell('', '<a href="'.$page->url('add', $url, 'agent', $agent).'">'.$this->ellipsize($agent, 50).'</a>');
                    $html .= $bp->table->cell('class=text-center', date('D, M m Y, h:i a', $time - $this->offset));
                    $html .= $bp->table->cell('class=text-center', $this->next($delayed, $time));
                }
                $html .= $bp->table->close();
                $this->db->close($result);
            }
        } else {
            $header = 'Robots <small style="margin-left:10px;">Last 30 Days</small>';
            $file = $blog->folder.'content/robots.txt.twig';
            if (!is_file($file)) {
                file_put_contents($file, '');
            }
            \BootPress\Admin\Files::save(array('robots.txt' => $file));
            if (!$sitemaps = $this->db->ids(array(
                'SELECT id FROM analytic_paths WHERE path LIKE ? AND path NOT LIKE ?',
            ), array('sitemap%.xml', '%/%'))) {
                $sitemaps = array();
            }
            if (!$robots = $this->db->ids(array(
                'SELECT id FROM analytic_paths WHERE path = ?',
            ), 'robots.txt')) {
                $robots = array();
            }
            $month = time() - 2592000; // last 30 days
            if (!$bp->pagination->set('page', 100)) {
                $bp->pagination->total($this->db->value(array(
                    'SELECT COUNT(DISTINCT agent_id)',
                    'FROM analytic_bots',
                    'WHERE time > ?',
                ), $month));
            }
            if ($result = $this->db->query(array(
                'SELECT a.agent, a.robot,',
                '   MAX(b.time) AS time,',
                '   COUNT(b.agent_id) AS hits,',
                '   SUM(CASE WHEN b.path_id IN('.implode(',', $robots).') THEN 1 ELSE 0 END) AS robots,',
                '   SUM(CASE WHEN b.path_id IN('.implode(',', $sitemaps).') THEN 1 ELSE 0 END) AS sitemaps,',
                '   MAX(CASE WHEN b.path_id IN('.implode(',', array_merge($robots, $sitemaps)).') THEN b.time ELSE 0 END) AS checked',
                'FROM analytic_bots AS b',
                'INNER JOIN analytic_agents AS a ON b.agent_id = a.id',
                'WHERE b.time > ?',
                'GROUP BY b.agent_id',
                'ORDER BY hits DESC'.$bp->pagination->limit,
            ), $month, 'row')) {
                $html .= $bp->table->open('class=hover');
                $html .= $bp->table->head();
                $html .= $bp->table->cell('', 'User Agent');
                $html .= $bp->table->cell('class=text-center', 'Hits');
                $html .= $bp->table->cell('class=text-center', '<a href="#" class="wyciwyg txt text-nowrap" data-retrieve="robots.txt" data-file="robots.txt" title="Edit">'.$bp->icon('pencil-square-o', 'fa').' robots.txt</a>');
                $html .= $bp->table->cell('class=text-center', '<a href="'.$page->url('sitemap.xml').'" class="text-nowrap">sitemap%.xml</a>');
                $html .= $bp->table->cell('class=text-center', 'Checked');
                while (list($agent, $robot, $time, $hits, $robots, $sitemaps, $checked) = $this->db->fetch($result)) {
                    $html .= $bp->table->row();
                    $html .= $bp->table->cell('', '<a href="'.$page->url('add', $url, 'agent', $agent).'">'.(!empty($robot) ? $robot : $this->ellipsize($agent, 50)).'</a>');
                    $html .= $bp->table->cell('class=text-center', $hits);
                    $html .= $bp->table->cell('class=text-center', !empty($robots) ? $robots : '-');
                    $html .= $bp->table->cell('class=text-center', !empty($sitemaps) ? $sitemaps : '-');
                    $html .= $bp->table->cell('class=text-center', !empty($checked) ? '<span class="timeago" title="'.date('c', $checked).'">'.$checked.'</span>' : '-');
                }
                $html .= $bp->table->close();
                $this->db->close($result);
            }
        }

        return Admin::box('default', array(
            'head with-border' => $bp->icon('sitemap', 'fa').' '.$header,
            'body no-padding table-responsive' => $html,
            'foot clearfix' => $bp->pagination->links(),
        ));
    }

    private function ellipsize($string, $length)
    {
        $string = trim(strip_tags($string));

        return (mb_strlen($string) >= $length) ? mb_substr($string, 0, $length).'&hellip;' : $string;
    }

    private function next(&$delayed, $time)
    {
        $html = '-';
        if (!empty($delayed)) {
            $delay = $delayed - $time;
            if ($delay < 60) {
                $html = $delay.' s';
            } elseif ($delay < 3600) {
                $delay = round($delay / 60);
                $html = $delay.' min';
            }
        }
        $delayed = $time;

        return $html;
    }

    private function where($field, $start, $stop, array $and = array())
    {
        $where = array();
        if (!empty($start)) {
            $where[] = $field.' >= '.(int) $start;
        }
        if (!empty($stop)) {
            $where[] = $field.' <= '.(int) $stop;
        }
        foreach ($and as $value) {
            $where[] = $value;
        }

        return (!empty($where)) ? 'WHERE '.implode(' AND ', $where) : '';
    }

    private function started()
    {
        static $started = null;
        if (is_null($started)) {
            $started = ($time = $this->db->value('SELECT MIN(started) FROM analytic_sessions')) ? $time : $this->now;
        }

        return $started;
    }

    private function startStop($count, $range, $label = 'Y-m-d H:i:s', array $values = array())
    {
        $array = array();
        $range = substr($range, 0, 3);
        if (in_array($range, array('sec', 'min', 'hou', 'day', 'wee', 'mon', 'yea'))) {
            for ($i = 0; $i < $count; ++$i) {
                switch ($range) {
                    case 'sec':
                        $time = $this->now - $i;
                        break;
                    case 'min':
                        $time = $this->now - ($i * 60);
                        break;
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
                list($start, $stop, $value) = $this->timeRange($time, $range, $label);
                if ($stop < $this->started()) {
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

    private function timeRange($time, $range, $label = '')
    {
        // H - hour - 00 to 23
        // i - minute - 00 to 59
        // s - second - 00 to 59
        // n - month - 1 to 12
        // j - day - 1 to 31
        // Y - year - 2003
        // N - weekday - 1 to 7 (monday to sunday)
        $time = explode(' ', date('H i s n j Y N '.$label, ($time - $this->offset)));
        list($H, $i, $s, $n, $j, $Y, $N) = array_map('intval', $time);
        $label = implode(' ', array_slice($time, 7));
        // mktime(H (hour), i (minute), s (second), n (month), j (day), Y (year))
        switch (substr($range, 0, 3)) {
            case 'sec':
                $from = mktime($H, $i, $s, $n, $j, $Y) + $this->offset;
                $to = mktime($H, $i, $s, $n, $j, $Y) + $this->offset;
                break;
            case 'min':
                $from = mktime($H, $i, 0, $n, $j, $Y) + $this->offset;
                $to = mktime($H, $i, 59, $n, $j, $Y) + $this->offset;
                break;
            case 'hou':
                $from = mktime($H, 0, 0, $n, $j, $Y) + $this->offset;
                $to = mktime($H, 59, 59, $n, $j, $Y) + $this->offset;
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

    private function pageViews($path = null, $start = null, $stop = null, $default = 0)
    {
        if (is_null($path)) {
            $path = Page::html()->url['path'];
        }
        if (empty($this->user_id)) {
            $views = $this->db->value(array(
                'SELECT COUNT(*) AS views',
                'FROM analytic_hits AS h',
                $this->where('h.time', $start, $stop, array(
                    'h.path_id = (SELECT p.id FROM analytic_paths AS p WHERE p.path = ?)',
                )),
            ), array($path));
        } else {
            $views = $this->db->value(array(
                'SELECT COUNT(*) AS views',
                'FROM analytic_hits AS h',
                'LEFT JOIN analytic_users AS u ON h.session_id = u.session_id AND u.user_id = ?',
                $this->where('h.time', $start, $stop, array(
                    'h.path_id = (SELECT p.id FROM analytic_paths AS p WHERE p.path = ?)',
                    'u.user_id IS NULL',
                )),
            ), array($this->user_id, $path));
        }

        return ($views) ? $views : $default;
    }

    private function userHits($start = null, $stop = null, $default = 0)
    {
        if (empty($this->user_id)) {
            $row = $this->db->row(array(
                'SELECT',
                '   COUNT(DISTINCT h.session_id) AS sessions,',
                '   COUNT(*) AS hits,',
                '   AVG(CASE WHEN s.duration > 0 THEN s.duration END) / 60 AS duration,',
                '   AVG(CASE WHEN h.loaded > 0 AND h.loaded < 20000 THEN h.loaded END) / 1000 AS loaded,',
                '   AVG(CASE WHEN h.server > 0 AND h.server < 20000 THEN h.server END) / 1000 AS server',
                'FROM analytic_hits AS h',
                'INNER JOIN analytic_sessions AS s ON h.session_id = s.id',
                $this->where('h.time', $start, $stop),
            ), '', 'assoc');
        } else {
            $row = $this->db->row(array(
                'SELECT',
                '   COUNT(DISTINCT h.session_id) AS sessions,',
                '   COUNT(*) AS hits,',
                '   AVG(CASE WHEN s.duration > 0 THEN s.duration END) / 60 AS duration,',
                '   AVG(CASE WHEN h.loaded > 0 AND h.loaded < 20000 THEN h.loaded END) / 1000 AS loaded,',
                '   AVG(CASE WHEN h.server > 0 AND h.server < 20000 THEN h.server END) / 1000 AS server',
                'FROM analytic_hits AS h',
                'INNER JOIN analytic_sessions AS s ON h.session_id = s.id',
                'LEFT JOIN analytic_users AS u ON h.session_id = u.session_id AND u.user_id = ?',
                $this->where('h.time', $start, $stop, array(
                    'u.user_id IS NULL',
                )),
            ), array($this->user_id), 'assoc');
            // exit('<pre>'.print_r($row, true).'</pre>');
        }
        $user = array();
        $user['sessions'] = ($row && !empty($row['sessions'])) ? number_format($row['sessions']) : $default;
        $user['hits'] = ($row && !empty($row['hits'])) ? number_format($row['hits']) : $default;
        $user['duration'] = ($row && !empty($row['duration'])) ? number_format($row['duration'], 2).' minutes' : $default;
        $user['loaded'] = ($row && !empty($row['loaded'])) ? number_format($row['loaded'], 2).' seconds' : $default;
        $user['server'] = ($row && !empty($row['server'])) ? number_format($row['server'], 2).' seconds' : $default;

        return $user;
    }

    private function robotHits($start = null, $stop = null, $default = 0)
    {
        $row = $this->db->row(array(
            'SELECT',
            '   COUNT(DISTINCT b.ip) AS ips,',
            '   COUNT(DISTINCT b.agent_id) AS agents,',
            '   COUNT(*) AS hits',
            'FROM analytic_bots AS b',
            $this->where('b.time', $start, $stop),
        ), '', 'assoc');
        $robot = array();
        $robot['ips'] = ($row && !empty($row['ips'])) ? number_format($row['ips']) : $default;
        $robot['hits'] = ($row && !empty($row['hits'])) ? number_format($row['hits']) : $default;
        $robot['agents'] = ($row && !empty($row['agents'])) ? number_format($row['agents']) : $default;

        return $robot;
    }
}
