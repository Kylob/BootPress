<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_analytics extends CI_Driver {

  private $begin;
  private $end;
  private $now;
  private $offset;
  
  public function view ($params) {
    global $bp, $ci, $page;
    $ci->load->library('analytics');
    if ($ci->input->get('process') == 'hits') {
      $ci->analytics->process_hits();
      $page->eject($page->url('delete', '', 'process'));
    }
    if (!$row = $ci->analytics->db->row('SELECT MIN(time) AS just_starting, MAX(time) AS last_updated FROM hits')) return $this->display();
    $this->begin = (int) array_shift($row); // just_starting
    $this->end = (int) array_shift($row); // last_updated
    $this->now = time();
    $analytics = $ci->session->analytics;
    $this->offset = (isset($analytics['offset'])) ? (int) $analytics['offset'] : 0;
    $page->plugin('CDN', 'link', 'jquery.timeago/1.3.0/jquery.timeago.min.js');
    $page->plugin('jQuery', 'code', '$("span.timeago").timeago();');
    $method = (isset($params['method'])) ? $params['method'] : 'visitors';
    $chart = '';
    $html = $this->$method();
    if (is_array($html)) {
      $chart = array_shift($html);
      $html = array_shift($html);
    }
    return $this->display($this->box('default', array(
      'head with-border' => array(
        $bp->icon('line-chart', 'fa') . ' ' . ucwords($method),
        $bp->label('info', 'Last updated <span class="timeago" title="' . date('c', $this->end) . '">' . $this->end . '</span>'),
        $bp->button('sm warning', $bp->icon('refresh') . ' Process Hits', array('href' => $page->url('add', '', 'process', 'hits')))
      ),
      'body' => $chart,
      'body no-padding table-responsive' => $html,
      'foot clearfix' => $bp->listings->pagination('sm no-margin')
    )));
  }
  
  private function visitors () {
    global $bp, $ci, $page;
    $page->plugin('CDN', 'links', array(
      'morris.js/0.5.1/morris.min.js',
      'morris.js/0.5.1/morris.css'
    ));
    $page->link('//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js', 'prepend');
    $chart = '<div id="visitors-chart" style="height:300px;"></div>';
    $data = array();
    foreach ($this->start_stop(31, 'day', 'Y-m-d') as $x => $info) { // D M j
      list($start, $stop) = $info;
      list($user, $hits) = $ci->analytics->user_hits($start, $stop);
      $data[] = '{x:"' . $x . '", hits:' . $hits . ', users:' . $user . ', avg:' . ($user > 0 ? round($hits / $user, 1) : 0) . '}';
    }
    $data = '[' . implode(',', $data) . ']';
    $page->plugin('jQuery', 'code', '
      new Morris.Area({
        behaveLikeLine: true,
        element: "visitors-chart",
        resize: true,
        data: ' . $data . ',
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
    $visits = array();
    $visits['Since'] = array(date("M Y", $this->begin) => array($this->begin, $this->end));
    $visits['Past Day'] = $this->start_stop(24, 'hour', 'g:00a', array('This Hour', 'Last Hour'));
    $visits['Past Week'] = $this->start_stop(7, 'day', 'l', array('Today', 'Yesterday'));
    $visits['Past Month'] = $this->start_stop(5, 'week', '', array('This Week', 'Last Week', '2 weeks ago', '3 weeks ago', '4 weeks ago'));
    $visits['Past Year'] = $this->start_stop(12, 'month', "M Y", array('This Month', 'Last Month'));
    foreach ($visits as $header => $values) {
      foreach ($values as $value => $info) {
        list($start, $stop) = $info;
        $visits[$header][$value][] = $ci->analytics->robot_hits($start, $stop, '-');
        $visits[$header][$value][] = $ci->analytics->user_hits($start, $stop, '-');
        $visits[$header][$value][] = $ci->analytics->avg_load_times($start, $stop, '-', ' seconds');
        $visits[$header][$value][] = $ci->analytics->avg_session_duration($start, $stop, '-', ' minutes');
      }
    }
    $html = $bp->table->open('class=hover');
    foreach ($visits as $header => $values) {
      if (empty($values)) continue;
      $html .= $bp->table->head();
      $html .= $bp->table->cell('', $header);
      $html .= $bp->table->cell('style=text-align:right;', 'Robots');
      $html .= $bp->table->cell('', 'Hits');
      $html .= $bp->table->cell('style=text-align:right;', 'Users');
      $html .= $bp->table->cell('', 'Hits');
      $html .= $bp->table->cell('style=text-align:center;', 'Avg Load Times');
      $html .= $bp->table->cell('style=text-align:center;', 'Avg Session Duration');
      foreach ($values as $value => $info) {
        list($start, $stop, $robot_hits, $user_hits, $avg_load_times, $avg_session_duration) = $info;
        $html .= $bp->table->row();
        $html .= $bp->table->cell('', $value);
        $html .= $bp->table->cell('align=right', array_shift($robot_hits));
        $html .= $bp->table->cell('align=left', array_shift($robot_hits));
        $html .= $bp->table->cell('align=right', array_shift($user_hits));
        $html .= $bp->table->cell('align=left', array_shift($user_hits));
        $html .= $bp->table->cell('align=center', $avg_load_times);
        $html .= $bp->table->cell('align=center', $avg_session_duration);
      }
    }
    $html .= $bp->table->close();
    return array($chart, $html);
  }
  
  private function referrers () {
    global $bp, $ci;
    $ci->load->helper('text');
    $bp->listings->display(100);
    if (!$bp->listings->set) $bp->listings->count($ci->analytics->db->value(
      'SELECT COUNT(*) FROM sessions WHERE referrer != "" AND admin != 1'
    ));
    $query = $ci->analytics->db->query(array(
      'SELECT s.bot, s.hits, s.duration, s.time, s.referrer, u.uri, s.query',
      'FROM sessions AS s INNER JOIN uris AS u ON s.uri_id = u.id',
      'WHERE s.referrer != "" AND s.admin != 1 ORDER BY s.id DESC' . $bp->listings->limit()
    ));
    $rows = $ci->analytics->db->fetch('row', 'all');
    if (empty($rows)) return $html;
    $html = $bp->table->open('class=hover');
    $html .= $bp->table->head();
    $html .= $bp->table->cell('', 'Referrer');
    $html .= $bp->table->cell('', 'Page');
    $html .= $bp->table->cell('style=text-align:center;', 'Hits');
    $html .= $bp->table->cell('style=text-align:center;', 'Date');
    foreach ($rows as $row) {
      list($bot, $hits, $duration, $time, $referrer, $uri, $query) = $row;
      preg_match('/\/\/([\S]+\.[a-z]{2,4})\//i', $referrer, $matches);
      $website = array_pop($matches);
      $html .= $bp->table->row();
      $html .= $bp->table->cell('', '<a href="' . $referrer . '">' . ellipsize($website, 50) . '</a>');
      $html .= $bp->table->cell('', '<a href="' . BASE_URL . $uri . $query . '">' . $uri . '</a>');
      $html .= $bp->table->cell('align=center', $hits);
      $html .= $bp->table->cell('align=center', '<span class="timeago" title="' . date('c', $time) . '">' . $time . '</span>');
    }
    $html .= $bp->table->close();
    return $html;
  }
  
  private function pages () {
    global $bp, $ci, $page;
    $html = $bp->table->open('class=hover');
    $html .= $bp->table->head();
    $html .= $bp->table->cell('', 'Most Popular (last 30 days)');
    $html .= $bp->table->cell('style=text-align:center;', 'Hits');
    $ci->analytics->db->query(array(
      'SELECT u.uri, COUNT(h.uri_id) AS hits',
      'FROM hits AS h',
      'INNER JOIN sessions AS s ON h.session_id = s.id',
      'INNER JOIN uris AS u ON h.uri_id = u.id',
      'WHERE s.time > ' . ($this->end - 2592000) . ' AND s.bot = 0 AND s.admin != 1',
      'GROUP BY h.uri_id ORDER BY hits DESC LIMIT 50'
    ));
    while (list($uri, $hits) = $ci->analytics->db->fetch('row')) {
      $html .= $bp->table->row();
      $html .= $bp->table->cell('', '<a href="' . BASE_URL . $uri . '">' . (empty($uri) ? '(index)' : $uri) . '</a>');
      $html .= $bp->table->cell('align=center', $hits);
    }
    $html .= $bp->table->close();
    $html .= $bp->table->open('class=hover');
    $html .= $bp->table->head();
    $html .= $bp->table->cell('', 'Most Recent (per user)');
    $html .= $bp->table->cell('style=text-align:center;', 'Date');
    $html .= $bp->table->cell('style=text-align:center;', 'IP');
    $ci->analytics->db->query(array(
      'SELECT h.time, u.uri, h.query, s.id, s.ip',
      'FROM hits AS h',
      'INNER JOIN sessions AS s ON h.session_id = s.id',
      'INNER JOIN uris AS u ON h.uri_id = u.id',
      'WHERE s.time > 0 AND s.bot = 0 AND s.admin != 1',
      'GROUP BY s.id ORDER BY h.id DESC LIMIT 50'
    ));
    while (list($time, $uri, $query, $id, $ip) = $ci->analytics->db->fetch('row')) {
      $html .= $bp->table->row();
      $html .= $bp->table->cell('', '<a href="' . BASE_URL . $uri . $query . '">' . (empty($uri) ? '(index)' : $uri) . '</a>');
      $html .= $bp->table->cell('align=center', '<span class="timeago" title="' . date('c', $time) . '">' . $time . '</span>');
      $html .= $bp->table->cell('align=center', $ip);
      // $html .= $bp->table->cell('align=center', '<a href="' . $page->url('add', '', 'id', $id) . '">' . $ip . '</a>');
    }
    $html .= $bp->table->close();
    return $html;
  }
  
  private function users ($data=null) {
    global $bp, $ci, $page;
    $page->plugin('CDN', 'link', 'chart.js/1.0.1/Chart.min.js');
    if (is_array($data)) {
      $colors = array('#F56954', '#00A65A', '#F39C12', '#00C0EF', '#3C8DBC', '#D2D6DE'); // red, green, orange, lt. blue, blue, lt. gray
      foreach ($data as $key => $value) {
        if (!empty($key) && ($percent = round($value)) > 0) {
          $color = array_shift($colors);
          $data[$key] = "{value:{$percent},color:\"{$color}\",label:\"{$key}\"}";
          array_push($colors, $color);
        } else {
          unset($data[$key]);
        }
      }
      return (!empty($data)) ? '[' . implode(', ', $data) . ']' : null;
    }
    $html = '';
    $options = array(
      'animation:false',
      'legendTemplate:"<ul class=\"<%=name.toLowerCase()%>-legend list-unstyled\"><% for (var i=0; i<segments.length; i++){%><li><p><i class=\"fa fa-circle-o\" style=\"color:<%=segments[i].fillColor%>; margin-right:10px;\"></i><%=segments[i].value%>% - <%=segments[i].label%></p></li><%}%></ul>"',
      'tooltipTemplate:"<%=value %>% - <%=label%>"'
    );
    $options = '{' . implode(', ', $options) . '}';
    $total = 0;
    $mobile = array();
    $platforms = array();
    $browsers = array();
    $versions = array();
    $ci->analytics->db->query(array(
      'SELECT s.hits, a.platform, a.browser, a.version, a.mobile',
      'FROM sessions AS s',
      'INNER JOIN agents AS a ON s.agent_id = a.id',
      'WHERE s.time > ' . ($this->end - 2592000) . ' AND s.bot = 0 AND s.admin != 1'
    ));
    while (list($hits, $platform, $browser, $version, $phone) = $ci->analytics->db->fetch('row')) {
      $total += $hits;
      if (!empty($phone)) {
        if (!isset($mobile[$phone])) $mobile[$phone] = 0;
        $mobile[$phone] += $hits;
      }
      if (!isset($platforms[$platform])) $platforms[$platform] = 0;
      $platforms[$platform] += $hits;
      if (!isset($browsers[$browser])) $browsers[$browser] = 0;
      $browsers[$browser] += $hits;
      $version = (int) $version;
      if (!isset($versions[$browser][$version])) $versions[$browser][$version] = 0;
      $versions[$browser][$version] += $hits;
    }
    #-- Mobile -#
    foreach ($mobile as $phone => $hits) $mobile[$phone] = ($hits / $total) * 100;
    arsort($mobile);
    if ($data = $this->users($mobile)) {
      $html .= '<br>' . $bp->row('sm', array(
        $bp->col('6 vcenter', '<div class="canvas-container"><canvas id="mobileChart" height="250"></canvas></div>'),
        $bp->col('5 vcenter', '<p class="lead">Mobile (' . round(array_sum($mobile)) . '% of Users)</p><div id="mobileChartLegend"></div>')
      )) . '<br>';
      $page->script(array(
        'var mobileChartCanvas = document.getElementById("mobileChart").getContext("2d");',
        'var mobileChart = new Chart(mobileChartCanvas).Doughnut(' . $data . ', ' . $options . ');',
        'document.getElementById("mobileChartLegend").innerHTML = mobileChart.generateLegend();',
      ));
    }
    #-- Platforms --#
    foreach ($platforms as $platform => $hits) $platforms[$platform] = ($hits / $total) * 100;
    arsort($platforms);
    if ($data = $this->users($platforms)) {
      $html .= '<br>' . $bp->row('sm', array(
        $bp->col('6 vcenter', '<div class="canvas-container"><canvas id="platformsChart" height="250"></canvas></div>'),
        $bp->col('5 vcenter', '<p class="lead">Platforms</p><div id="platformsChartLegend"></div>')
      )) . '<br>';
      $page->script(array(
        'var platformsChartCanvas = document.getElementById("platformsChart").getContext("2d");',
        'var platformsChart = new Chart(platformsChartCanvas).Doughnut(' . $data . ', ' . $options . ');',
        'document.getElementById("platformsChartLegend").innerHTML = platformsChart.generateLegend();',
      ));
    }
    #-- Browsers --#
    foreach ($browsers as $browser => $hits) {
      $browsers[$browser] = ($hits / $total) * 100;
      foreach ($versions[$browser] as $version => $hits) $versions[$browser][$version] = ($hits / $total) * 100;
      arsort($versions[$browser]);
    }
    arsort($browsers);
    if ($data = $this->users($browsers)) {
      $html .= '<br>' . $bp->row('sm', array(
        $bp->col('6 vcenter', '<div class="canvas-container"><canvas id="browsersChart" height="250"></canvas></div>'),
        $bp->col('5 vcenter', '<p class="lead">Browsers</p><div id="browsersChartLegend"></div>')
      )) . '<br>';
      $page->script(array(
        'var browsersChartCanvas = document.getElementById("browsersChart").getContext("2d");',
        'var browsersChart = new Chart(browsersChartCanvas).Doughnut(' . $data . ', ' . $options . ');',
        'document.getElementById("browsersChartLegend").innerHTML = browsersChart.generateLegend();',
      ));
    }
    #-- Versions --#
    $options = str_replace(' - ', ' - version ', $options);
    foreach ($browsers as $browser => $share) {
      if (!empty($browser) && ($percent = round($share)) > 0 && isset($versions[$browser]) && ($data = $this->users($versions[$browser]))) {
        $seo = $page->seo($browser);
        $html .= '<br>' . $bp->row('sm', array(
          $bp->col('6 vcenter', '<div class="canvas-container"><canvas id="' . $seo . 'Chart" height="250"></canvas></div>'),
          $bp->col('5 vcenter', '<p class="lead">' . $browser . ' (' . $percent . '% of Users)</p><div id="' . $seo . 'ChartLegend"></div>')
        )) . '<br>';
        $page->script(array(
          'var ' . $seo . 'ChartCanvas = document.getElementById("' . $seo . 'Chart").getContext("2d");',
          'var ' . $seo . 'Chart = new Chart(' . $seo . 'ChartCanvas).Doughnut(' . $data . ', ' . $options . ');',
          'document.getElementById("' . $seo . 'ChartLegend").innerHTML = ' . $seo . 'Chart.generateLegend();',
        ));
      }
    }
    $page->style(array(
      'canvas { display:inline; }',
      '.canvas-container { width:100%; text-align:center; }',
      '.vcenter' => array(
        'display: inline-block;',
        'vertical-align: middle;',
        'float: none;'
      )
    ));
    return '<div style="margin:20px;">' . $html . '</div>';
  }
  
  private function start_stop ($count, $range, $label, $values=array()) {
    $array = array();
    for ($i = 0; $i < $count; $i++) {
      switch ($range) {
        case 'hour': $time = $this->now - ($i * 3600); break;
        case 'day': $time = $this->now - ($i * 86400); break;
        case 'week': $time = $this->now - ($i * 604800); break;
        case 'month': $time = strtotime("today -{$i} month"); break;
      }
      list($start, $stop, $value) = $this->timerange($time, $range, $label, $this->offset);
      if ($stop < $this->begin) break;
      if (!empty($values)) $value = array_shift($values);
      if ($start < $this->end) $array[$value] = array($start, $stop);
    }
    return $array;
  }
  
  private function timerange ($time, $range, $value='', $offset=0) { // time() - gmt_to_local(time(), '...')
    $time = explode(' ', date('Y m j H N ' . $value, ($time - $offset)));
    list($Y, $m, $j, $H, $N) = $time;
    $value = implode(' ', array_slice($time, 5));
    switch ($range) {
      case 'week':
        $from = mktime(0, 0, 0, (int) $m, (int) ($j - $N) + 1, (int) $Y) + $offset;
        $to = mktime(23, 59, 59, (int) $m, (int) (($j - $N) + 7), (int) $Y) + $offset;
        break;
      case 'hour':
        $from = mktime($H, 0, 0, (int) $m, (int) $j, (int) $Y) + $offset;
        $to = mktime($H, 59, 59, (int) $m, (int) $j, (int) $Y) + $offset;
        break;
      case 'day':
        $from = mktime(0, 0, 0, (int) $m, (int) $j, (int) $Y) + $offset;
        $to = mktime(23, 59, 59, (int) $m, (int) $j, (int) $Y) + $offset;
        break;
      case 'month':
        $from = mktime(0, 0, 0, (int) $m, 1, (int) $Y) + $offset;
        $to = mktime(23, 59, 59, (int) $m + 1, 0, (int) $Y) + $offset;
        break;
      case 'year':
        $from = mktime(0, 0, 0, 1, 1, (int) $Y) + $offset;
        $to = mktime(23, 59, 59, 1, 0, (int) $Y) + $offset;
        break;
    }
    return array($from, $to, $value, date('Y-m-d H:i:s', $from), date('Y-m-d H:i:s', $to));
    /*
    $html .= '<pre>';
      $html .= print_r($this->timerange(time(), 'week', 'W'), true);
      $html .= print_r($this->timerange(time(), 'hour', 'g:00a'), true);
      $html .= print_r($this->timerange(time(), 'day', 'l'), true);
      $html .= print_r($this->timerange(time(), 'month', 'M'), true);
      $html .= print_r($this->timerange(time(), 'year', 'Y'), true);
    $html .= '</pre>';
    */
  }
  
}

/* End of file Admin_analytics.php */
/* Location: ./application/libraries/Admin/drivers/Admin_analytics.php */