<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_sitemap extends CI_Driver {
  
  public function view () {
    global $bp, $ci, $page;
    $html = '';
    if ($ci->input->get('refresh') == 'sitemap') {
      $ci->load->library('sitemap');
      $ci->sitemap->refresh();
      $page->eject($page->url('delete', '', 'refresh'));
    }
    $page->plugin('CDN', 'link', 'jquery.timeago/1.3.0/jquery.timeago.min.js');
    $page->plugin('jQuery', 'code', '$("span.timeago").timeago();');
    $ci->load->library('analytics');
    if ($agent = $ci->input->get('agent')) {
      $html .= $this->secret($agent);
    } else {
      if (is_file(BASE_URI . 'sitemap/index.txt')) {
        $html .= '<p>' . $bp->button('sm default', $bp->icon('refresh') . ' Refresh Sitemap', array(
          'href' => $page->url('add', '', 'refresh', 'sitemap')
        )) . '</p>';
      }
      $html .= $this->robots();
    }
    return $this->display($html);
  }
  
  private function secret ($agent) {
    global $bp, $ci;
    if (!$row = $ci->analytics->db->row('SELECT agent, robot FROM agents WHERE id = ?', array($agent))) return;
    $html = '<h3>' . $bp->icon('sitemap', 'fa') . ' ' . (!empty($row['robot']) ? $row['robot'] : $row['agent']) . '</h3><br>';
    $bp->listings->display(100);
    if (!$bp->listings->set) $bp->listings->count($ci->analytics->db->value(array(
      'SELECT COUNT(*)',
      'FROM hits AS h',
      'INNER JOIN sessions AS s ON h.session_id = s.id',
      'WHERE s.time > 0 AND s.bot = 1 AND s.admin = 0 AND s.agent_id = ?'
    ), array($agent)));
    $ci->analytics->db->query(array(
      'SELECT u.uri, h.query, h.time, s.ip',
      'FROM hits AS h',
      'INNER JOIN sessions AS s ON h.session_id = s.id',
      'INNER JOIN uris AS u ON h.uri_id = u.id',
      'WHERE s.time > 0 AND s.bot = 1 AND s.admin = 0 AND s.agent_id = ?',
      'ORDER BY h.time DESC' . $bp->listings->limit()
    ), array($agent));
    $html .= $bp->table->open('class=table responsive bordered striped condensed');
    while (list($uri, $query, $time, $ip) = $ci->analytics->db->fetch('row')) {
      $html .= $bp->table->row();
      $html .= $bp->table->cell('', '<a href="' . BASE_URL . $uri . $query . '">' . $uri . '</a>');
      $html .= $bp->table->cell('', $ip);
      $html .= $bp->table->cell('', '<span class="timeago" title="' . date('c', $time) . '">' . $time . '</span>');
    }
    $html .= $bp->table->close();
    $html .= '<div class="text-center">' . $bp->listings->pagination() . '</div>';
    return $html;
  }
  
  private function robots () {
    global $bp, $ci;
    if (!$updated = $ci->analytics->db->value('SELECT MAX(time) FROM hits')) return;
    $ci->admin->files->save(array('robots.txt' => BASE_URI . 'blog/content/robots.txt'));
    $month = $updated - 2592000; // last 30 days
    $html = '<h3>' . $bp->icon('sitemap', 'fa') . ' Robots <small>Last 30 Days</small></h3><br>';
    $sitemaps = $ci->analytics->db->value('SELECT GROUP_CONCAT(id) AS ids FROM uris WHERE uri LIKE ? AND uri NOT LIKE ?', array('sitemap%.xml', '%/%'));
    $robots = $ci->analytics->db->value('SELECT id FROM uris WHERE uri = ?', array('robots.txt'));
    $html .= $bp->table->open('class=table responsive bordered striped condensed');
    $html .= $bp->table->head();
    $html .= $bp->table->cell('', 'User Agents');
    $html .= $bp->table->cell('style=text-align:center;', 'Hits');
    $html .= $bp->table->cell('style=text-align:center;', '<a href="#" class="wyciwyg txt text-nowrap" data-retrieve="robots.txt" data-file="robots.txt" title="Edit">' . $bp->icon('pencil') . ' robots.txt</a>');
    $html .= $bp->table->cell('style=text-align:center;', '<a href="' . BASE_URL . 'sitemap.xml" class="text-nowrap" target="sitemap">' . $bp->icon('new-window') . ' sitemap%.xml</a>');
    $html .= $bp->table->cell('style=text-align:center;', 'Checked');
    $bp->listings->display(100);
    if (!$bp->listings->set) $bp->listings->count(
      $ci->analytics->db->value('SELECT COUNT(DISTINCT agent_id) FROM sessions WHERE time > ' . $month . ' AND bot = 1')
    );
    $ci->analytics->db->query(array(
      'SELECT GROUP_CONCAT(s.id) AS ids, SUM(s.hits) AS hits, a.id, a.agent, a.robot',
      'FROM sessions AS s',
      'INNER JOIN agents AS a ON s.agent_id = a.id',
      'WHERE s.time > ' . $month . ' AND s.bot = 1',
      'GROUP BY s.agent_id ORDER BY hits DESC' . $bp->listings->limit()
    ));
    $agents = $ci->analytics->db->fetch('row', 'all');
    foreach ($agents as $row) {
      list($ids, $hits, $view, $agent, $robot) = $row;
      $link = '<a href="' . $page->url('admin', 'sitemap?agent=' . $view) . '">' . (!empty($robot) ? $robot : $agent) . '</a>';
      $txt = ($robots) ? $ci->analytics->db->value('SELECT COUNT(*) AS hits FROM hits WHERE time > ' . $month . ' AND uri_id = ' . $robots . ' AND session_id IN(' . $ids . ')') : false;
      $xml = ($sitemaps) ? $ci->analytics->db->row('SELECT COUNT(*) AS hits, MAX(time) AS checked FROM hits WHERE time > ' . $month . ' AND uri_id IN(' . $sitemaps . ') AND session_id IN(' . $ids . ')') : false;
      $html .= $bp->table->row();
      $html .= $bp->table->cell('', $link);
      $html .= $bp->table->cell('align=center', $hits);
      $html .= $bp->table->cell('align=center', ($txt) ? $txt : '-');
      $html .= $bp->table->cell('align=center', ($xml && !empty($xml['hits'])) ? $xml['hits'] : '-');
      $html .= $bp->table->cell('align=center', ($xml && !empty($xml['checked'])) ? '<span class="timeago" title="' . date('c', $xml['checked']) . '">' . $xml['checked'] . '</span>' : '');
    }
    $html .= $bp->table->close();
    $html .= '<div class="text-center">' . $bp->listings->pagination() . '</div>';
    return $html;
  }
  
}

/* End of file Admin_sitemap.php */
/* Location: ./application/libraries/Admin/drivers/Admin_sitemap.php */