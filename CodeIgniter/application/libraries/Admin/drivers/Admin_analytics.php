<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_analytics extends CI_Driver {

  private $begin;
  private $end;
  private $now;
  private $offset;
  private $month;
  
  public function view () {
    global $bp, $ci, $page;
    $html = '';
    if ($ci->input->post('retrieve') == 'robots') {
      $ci->load->library('sitemap');
      exit($ci->sitemap->robots());
    }
    if ($ci->input->post('wyciwyg') && $ci->input->post('field') == 'robots') {
      $ci->load->library('sitemap');
      $ci->sitemap->robots($this->code('wyciwyg'));
      exit('Saved');
    }
    $ci->load->library('analytics');
    if ($ci->input->get('process') == 'hits') {
      $ci->analytics->process_hits();
      $page->eject($page->url('delete', '', 'process'));
    }
    if (!$row = $ci->analytics->db->row('SELECT MIN(time) AS just_starting, MAX(time) AS last_updated FROM hits')) return $html;
    $ci->load->helper('date');
    $this->begin = (int) array_shift($row); // just_starting
    $this->end = (int) array_shift($row); // last_updated
    $this->now = time();
    $analytics = $ci->session->native->userdata('analytics');
    $this->offset = (isset($analytics['offset'])) ? (int) $analytics['offset'] : 0;
    $this->month = $this->end - 2592000; // 30 days;
    $html .= $bp->pills(array(
      'Visits' => '#visits',
      'Robots' => '#robots',
      'Users' => '#users',
      'Pages' => '#pages',
      'Referrers' => '#referrers',
    )) . '<br>';
    $page->plugin('CDN', 'link', 'jquery.timeago/1.3.0/jquery.timeago.min.js');
    $page->plugin('jQuery', 'code', '$("span.timeago").timeago();');
    $button = $bp->button('sm default pull-right', $bp->icon('refresh') . ' Process Hits', array(
      'href' => $page->url('add', '', 'process', 'hits')
    ));
    $html .= '<p>Last updated <span class="timeago" title="' . date('c', $this->end) . '">' . $this->end . '</span> ' . $button . '</p>';
    $html .= $this->visits();
    $html .= $this->robots();
    $html .= $this->users();
    $html .= $this->pages();
    $html .= $this->referrers();
    return $this->admin($html);
  }
  
  private function visits () {
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
    $html = '<h3 id="visits">Visits</h3>';
    $tb = $bp->table('class=table responsive bordered striped condensed');
    foreach ($visits as $header => $values) {
      if (empty($values)) continue;
      $tb->head();
      $tb->cell('', $header);
      $tb->cell('style=text-align:right;', 'Robots');
      $tb->cell('', 'Hits');
      $tb->cell('style=text-align:right;', 'Users');
      $tb->cell('', 'Hits');
      $tb->cell('style=text-align:center;', 'Avg Load Times');
      $tb->cell('style=text-align:center;', 'Avg Session Duration');
      foreach ($values as $value => $info) {
        list($start, $stop, $robot_hits, $user_hits, $avg_load_times, $avg_session_duration) = $info;
        $tb->row();
        $tb->cell('', $value);
        $tb->cell('align=right', array_shift($robot_hits));
        $tb->cell('align=left', array_shift($robot_hits));
        $tb->cell('align=right', array_shift($user_hits));
        $tb->cell('align=left', array_shift($user_hits));
        $tb->cell('align=center', $avg_load_times);
        $tb->cell('align=center', $avg_session_duration);
      }
    }
    $html .= $tb->close();
    return $html;
  }
  
  private function robots () {
    global $bp, $ci;
    $html = '<h3 id="robots">Robots <small>Last 30 Days</small></h3>';
    $sitemaps = $ci->analytics->db->value('SELECT GROUP_CONCAT(id) AS ids FROM uris WHERE uri LIKE ? AND uri NOT LIKE ?', array('sitemap%.xml', '%/%'));
    $robots = $ci->analytics->db->value('SELECT id FROM uris WHERE uri = ?', array('robots.txt'));
    $tb = $bp->table('class=table responsive bordered striped condensed');
    $tb->head();
    $tb->cell('', 'User Agents');
    $tb->cell('style=text-align:center;', 'Hits');
    $tb->cell('style=text-align:center;', '<a href="#" class="wyciwyg txt" data-retrieve="robots" title="Edit">' . $bp->icon('pencil') . '&nbsp;robots.txt</a>');
    $tb->cell('style=text-align:center;', '<a href="' . BASE_URL . 'sitemap.xml">sitemap%.xml</a>');
    $tb->cell('style=text-align:center;', 'Checked');
    $ci->analytics->db->query(array(
      'SELECT GROUP_CONCAT(s.id) AS ids, SUM(s.hits) AS hits, a.agent, a.robot',
      'FROM sessions AS s',
      'INNER JOIN agents AS a ON s.agent_id = a.id',
      'WHERE s.time > ' . $this->month . ' AND s.bot = 1',
      'GROUP BY s.agent_id ORDER BY hits DESC'
    ));
    $agents = $ci->analytics->db->fetch('row', 'all');
    foreach ($agents as $row) {
      list($ids, $hits, $agent, $robot) = $row;
      $txt = ($robots) ? $ci->analytics->db->value('SELECT COUNT(*) AS hits FROM hits WHERE time > ' . $this->month . ' AND uri_id = ' . $robots . ' AND session_id IN(' . $ids . ')') : false;
      $xml = ($sitemaps) ? $ci->analytics->db->row('SELECT COUNT(*) AS hits, MAX(time) AS checked FROM hits WHERE time > ' . $this->month . ' AND uri_id IN(' . $sitemaps . ') AND session_id IN(' . $ids . ')') : false;
      $tb->row();
      $tb->cell('', (!empty($robot)) ? $robot : $agent);
      $tb->cell('align=center', $hits);
      $tb->cell('align=center', ($txt) ? $txt : '-');
      $tb->cell('align=center', ($xml && !empty($xml['hits'])) ? $xml['hits'] : '-');
      $tb->cell('align=center', ($xml && !empty($xml['checked'])) ? '<span class="timeago" title="' . date('c', $xml['checked']) . '">' . $xml['checked'] . '</span>' : '');
    }
    $html .= $tb->close();
    return $html;
  }
  
  private function users () {
    global $bp, $ci;
    $html = '<h3 id="users">Users <small>Last 30 Days</small></h3>';
    $total = 0;
    $platforms = array();
    $browsers = array();
    $versions = array();
    $phones = array();
    $ci->analytics->db->query(array(
      'SELECT s.hits, a.platform, a.browser, a.version, a.mobile',
      'FROM sessions AS s',
      'INNER JOIN agents AS a ON s.agent_id = a.id',
      'WHERE s.time > ' . ($this->end - 2592000) . ' AND s.bot = 0'
    ));
    while (list($hits, $platform, $browser, $version, $mobile) = $ci->analytics->db->fetch('row')) {
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
    $tb = $bp->table('class=table responsive bordered striped condensed');
    $tb->head();
    $tb->cell('colspan=3', 'Platforms');
    $tb->cell('style=text-align:center;', '% of Total');
    foreach ($platforms as $platform => $percent) {
      $tb->row();
      $tb->cell('colspan=3', $platform);
      $tb->cell('align=center', round($percent) . '%');
    }
    $tb->head();
    $tb->cell('colspan=3', 'Browsers');
    $tb->cell('style=text-align:center;', '% of Total');
    foreach ($browsers as $browser => $percent) {
      $tb->row();
      $tb->cell('colspan=3', $browser);
      $tb->cell('align=center', round($percent) . '%');
      foreach ($versions[$browser] as $version => $percent) {
        $tb->row();
        $tb->cell('colspan=3|align=right', $version);
        $tb->cell('align=center', round($percent) . '%');
      }
    }
    if (!empty($phones)) {
      $tb->head();
      $tb->cell('colspan=3', 'Mobile');
      $tb->cell('style=text-align:center;', '% of Total');
      foreach ($phones as $mobile => $percent) {
        $tb->row();
        $tb->cell('colspan=3', $mobile);
        $tb->cell('align=center', round($percent) . '%');
      }
    }
    $html .= $tb->close();
    return $html;
  }
  
  private function pages () {
    global $bp, $ci;
    $html = '<h3 id="pages">Pages <small>Viewed By Users</small></h3>';
    $tb = $bp->table('class=table responsive bordered striped condensed');
    $tb->head();
    $tb->cell('', 'Most Popular (last 30 days)');
    $tb->cell('style=text-align:center;', 'Hits');
    $ci->analytics->db->query(array(
      'SELECT u.uri, COUNT(h.uri_id) AS hits',
      'FROM hits AS h',
      'INNER JOIN sessions AS s ON h.session_id = s.id',
      'INNER JOIN uris AS u ON h.uri_id = u.id',
      'WHERE h.time > ' . ($this->end - 2592000) . ' AND s.bot = 0',
      'GROUP BY h.uri_id ORDER BY hits DESC LIMIT 10'
    ));
    while (list($uri, $hits) = $ci->analytics->db->fetch('row')) {
      $page = (empty($uri)) ? '(index)' : $uri;
      $tb->row();
      $tb->cell('', '<a href="' . BASE_URL . $uri . '">' . $page . '</a>');
      $tb->cell('align=center', $hits);
    }
    $tb->head();
    $tb->cell('', 'Most Recent (per user)');
    $tb->cell('style=text-align:center;', 'Date');
    $ci->analytics->db->query(array(
      'SELECT h.time, u.uri, h.query',
      'FROM hits AS h',
      'INNER JOIN sessions AS s ON h.session_id = s.id',
      'INNER JOIN uris AS u ON h.uri_id = u.id',
      'WHERE s.bot = 0',
      'GROUP BY s.id ORDER BY h.id DESC LIMIT 20'
    ));
    while (list($time, $uri, $query) = $ci->analytics->db->fetch('row')) {
      $page = (empty($uri)) ? '(index)' : $uri;
      $tb->row();
      $tb->cell('', '<a href="' . BASE_URL . $uri . $query . '">' . $page . '</a>');
      $tb->cell('align=center', '<span class="timeago" title="' . date('c', $time) . '">' . $time . '</span>');
    }
    $html .= $tb->close();
    return $html;
  }
  
  private function referrers () {
    global $bp, $ci;
    $html = '<h3 id="referrers">Referrers</h3>';
    $query = $ci->analytics->db->query(array(
      'SELECT s.bot, s.hits, s.duration, s.time, s.referrer, u.uri, s.query',
      'FROM sessions AS s INNER JOIN uris AS u ON s.uri_id = u.id',
      'WHERE s.referrer != \'\' ORDER BY s.id DESC LIMIT 100'
    ));
    $rows = $ci->analytics->db->fetch('row', 'all');
    if (empty($rows)) return $html;
    $tb = $bp->table('class=table responsive bordered striped condensed');
    $tb->head();
    $tb->cell('', 'Referrer');
    $tb->cell('', 'Page');
    $tb->cell('style=text-align:center;', 'Hits');
    $tb->cell('style=text-align:center;', 'Date');
    foreach ($rows as $row) {
      list($bot, $hits, $duration, $time, $referrer, $uri, $query) = $row;
      preg_match('/\/\/([\S]+\.[a-z]{2,4})\//i', $referrer, $matches);
      $website = array_pop($matches);
      $tb->row();
      $tb->cell('', '<a href="' . $referrer . '">' . $website . '</a>');
      $tb->cell('', '<a href="' . BASE_URL . $uri . $query . '">' . $uri . '</a>');
      $tb->cell('align=center', $hits);
      $tb->cell('align=center', '<span class="timeago" title="' . date('c', $time) . '">' . $time . '</span>');
    }
    $html .= $tb->close();
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
  
  private function local ($gmt) {
    return $gmt - $this->offset;
  }
  
  private function gmt ($local) {
    return $local + $this->offset;
  }
  
}

/* End of file Admin_analytics.php */
/* Location: ./application/libraries/Admin/drivers/Admin_analytics.php */