<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_analytics extends CI_Driver {

  private $begin;
  private $end;
  private $now;
  private $offset;
  
  public function view ($params) {
    global $bp, $ci, $page;
    $html = '';
    $ci->load->library('analytics');
    if ($ci->input->get('process') == 'hits') {
      $ci->analytics->process_hits();
      $page->eject($page->url('delete', '', 'process'));
    }
    if (!$row = $ci->analytics->db->row('SELECT MIN(time) AS just_starting, MAX(time) AS last_updated FROM hits')) return $this->display();
    $this->begin = (int) array_shift($row); // just_starting
    $this->end = (int) array_shift($row); // last_updated
    $this->now = time();
    $analytics = $ci->session->native->userdata('analytics');
    $this->offset = (isset($analytics['offset'])) ? (int) $analytics['offset'] : 0;
    $page->plugin('CDN', 'link', 'jquery.timeago/1.3.0/jquery.timeago.min.js');
    $page->plugin('jQuery', 'code', '$("span.timeago").timeago();');
    $button = $bp->button('sm default pull-right', $bp->icon('refresh') . ' Process Hits', array(
      'href' => $page->url('add', '', 'process', 'hits')
    ));
    $html .= '<p>Last updated <span class="timeago" title="' . date('c', $this->end) . '">' . $this->end . '</span> ' . $button . '</p><br>';
    $html .= $bp->tabs(array(
      'Visitors' => BASE_URL . ADMIN . '/analytics',
      'Users' => BASE_URL . ADMIN . '/analytics/users',
      'Pages' => BASE_URL . ADMIN . '/analytics/pages',
      'Referrers' => BASE_URL . ADMIN . '/analytics/referrers',
    ), array('active'=>'url', 'align'=>'justified')) . '<br>';
    $method = (isset($params['method'])) ? $params['method'] : 'visitors';
    $html .= $this->$method();
    return $this->display($html);
  }
  
  private function visitors () {
    global $bp, $ci;
    $visits = array();
    $visits['Since'] = array(date("M Y", $this->begin) => array($this->begin, $this->end));
    $visits['Past Day'] = $this->start_stop(24, 'hour', 'g:00a', array('This Hour', 'Last Hour'));
    $visits['Past Week'] = $this->start_stop(7, 'day', 'l', array('Today', 'Yesterday'));
    $visits['Past Month'] = $this->start_stop(5, 'week', '', array('This Week', 'Last Week', '2 weeks ago', '3 weeks ago', '4 weeks ago'));
    $visits['Past Year'] = $this->start_stop(12, 'month', "M 'y", array('This Month', 'Last Month'));
    foreach ($visits as $header => $values) {
      foreach ($values as $value => $info) {
        list($start, $stop) = $info;
        $visits[$header][$value][] = $ci->analytics->robot_hits($start, $stop, '-');
        $visits[$header][$value][] = $ci->analytics->user_hits($start, $stop, '-');
        $visits[$header][$value][] = $ci->analytics->avg_load_times($start, $stop, '-', ' seconds');
        $visits[$header][$value][] = $ci->analytics->avg_session_duration($start, $stop, '-', ' minutes');
      }
    }
    $html = '<h3>' . $bp->icon('line-chart', 'fa') . ' Visitors</h3><br>';
    $html .= $bp->table->open('class=table responsive bordered striped condensed');
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
    return $html;
  }
  
  private function users () {
    global $bp, $ci;
    $html = '<h3>' . $bp->icon('line-chart', 'fa') . ' Users <small>Last 30 Days</small></h3><br>';
    $total = 0;
    $platforms = array();
    $browsers = array();
    $versions = array();
    $phones = array();
    $ci->analytics->db->query(array(
      'SELECT s.hits, a.platform, a.browser, a.version, a.mobile',
      'FROM sessions AS s',
      'INNER JOIN agents AS a ON s.agent_id = a.id',
      'WHERE s.time > ' . ($this->end - 2592000) . ' AND s.bot = 0 AND s.admin != 1'
    ));
    while (list($hits, $platform, $browser, $version, $mobile) = $ci->analytics->db->fetch('row')) {
      $version = (int) $version;
      $total += $hits;
      if (!isset($platforms[$platform])) $platforms[$platform] = 0;
      $platforms[$platform] += $hits;
      if (!isset($browsers[$browser])) $browsers[$browser] = 0;
      $browsers[$browser] += $hits;
      if (!isset($versions[$browser][$version])) $versions[$browser][$version] = 0;
      $versions[$browser][$version] += $hits;
      if (!empty($mobile)) {
        if (!isset($phones[$mobile])) $phones[$mobile] = 0;
        $phones[$mobile] += $hits;
      }
    }
    foreach ($platforms as $platform => $hits) $platforms[$platform] = ($hits / $total) * 100;
    arsort($platforms);
    foreach ($browsers as $browser => $hits) {
      $browsers[$browser] = ($hits / $total) * 100;
      foreach ($versions[$browser] as $version => $hits) $versions[$browser][$version] = ($hits / $total) * 100;
      arsort($versions[$browser]);
    }
    arsort($browsers);
    foreach ($phones as $mobile => $hits) $phones[$mobile] = ($hits / $total) * 100;
    arsort($phones);
    $html .= $bp->table->open('class=table responsive bordered striped condensed');
    $html .= $bp->table->head();
    $html .= $bp->table->cell('colspan=3', 'Platforms');
    $html .= $bp->table->cell('style=text-align:center;', '% of Total');
    foreach ($platforms as $platform => $percent) {
      $html .= $bp->table->row();
      $html .= $bp->table->cell('colspan=3', $platform);
      $html .= $bp->table->cell('align=center', round($percent) . '%');
    }
    $html .= $bp->table->head();
    $html .= $bp->table->cell('colspan=3', 'Browsers <span class="pull-right">Version</span>');
    $html .= $bp->table->cell('style=text-align:center;', '% of Total');
    foreach ($browsers as $browser => $percent) {
      $html .= $bp->table->row();
      $html .= $bp->table->cell('colspan=3', $browser);
      $html .= $bp->table->cell('align=center', round($percent) . '%');
      foreach ($versions[$browser] as $version => $percent) {
        $html .= $bp->table->row();
        $html .= $bp->table->cell('colspan=3|align=right', $version);
        $html .= $bp->table->cell('align=center', round($percent) . '%');
      }
    }
    if (!empty($phones)) {
      $html .= $bp->table->head();
      $html .= $bp->table->cell('colspan=3', 'Mobile');
      $html .= $bp->table->cell('style=text-align:center;', '% of Total');
      foreach ($phones as $mobile => $percent) {
        $html .= $bp->table->row();
        $html .= $bp->table->cell('colspan=3', $mobile);
        $html .= $bp->table->cell('align=center', round($percent) . '%');
      }
    }
    $html .= $bp->table->close();
    return $html;
  }
  
  private function pages () {
    global $bp, $ci, $page;
    $html = '<h3>' . $bp->icon('line-chart', 'fa') . ' Pages <small>Viewed By Users</small></h3><br>';
    $html .= $bp->table->open('class=table responsive bordered striped condensed');
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
    $html .= $bp->table->open('class=table responsive bordered striped condensed');
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
  
  private function referrers () {
    global $bp, $ci;
    $html = '<h3>' . $bp->icon('line-chart', 'fa') . ' Referrers</h3><br>';
    $bp->listings->display(100);
    if (!$bp->listings->set) $bp->listings->count($ci->analytics->db->value(
      'SELECT COUNT(*) FROM sessions WHERE referrer != ? AND admin != 1'
    ), array(''));
    $query = $ci->analytics->db->query(array(
      'SELECT s.bot, s.hits, s.duration, s.time, s.referrer, u.uri, s.query',
      'FROM sessions AS s INNER JOIN uris AS u ON s.uri_id = u.id',
      'WHERE s.referrer != ? AND s.admin != 1 ORDER BY s.id DESC' . $bp->listings->limit()
    ), array(''));
    $rows = $ci->analytics->db->fetch('row', 'all');
    if (empty($rows)) return $html;
    $html .= $bp->table->open('class=table responsive bordered striped condensed');
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
      $html .= $bp->table->cell('', '<a href="' . $referrer . '">' . $website . '</a>');
      $html .= $bp->table->cell('', '<a href="' . BASE_URL . $uri . $query . '">' . $uri . '</a>');
      $html .= $bp->table->cell('align=center', $hits);
      $html .= $bp->table->cell('align=center', '<span class="timeago" title="' . date('c', $time) . '">' . $time . '</span>');
    }
    $html .= $bp->table->close();
    $html .= '<div class="text-center">' . $bp->listings->pagination() . '</div>';
    return $html;
  }
  
  private function start_stop ($count, $range, $label, $values) {
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