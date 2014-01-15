<?php

class UsersView extends UsersDatabase {

  public function __construct () {
    global $page;
    parent::__construct();
    if (isset($_GET['login'])) {
      $row = $this->db->row('SELECT id, name FROM users WHERE email = ? LIMIT 1', array($_GET['login']));
      if ($row) {
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['name'] = $row['name'];
      }
      $page->eject($page->url('delete', '', 'login'));
    }
  }
  
  public function view () {
    global $page;
    $html = '';
    $page->plugin('Bootstrap', array('Pagination', 'Navigation'));
    $page->title = 'View Users';
    $html .= '<div class="row">';
      $html .= '<div class="col-sm-9">';
        $confirmed = $this->db->value('SELECT COUNT(*) FROM users WHERE confirmed = ?', array('Y'));
        $unconfirmed = $this->db->value('SELECT COUNT(*) FROM users WHERE confirmed = ?', array('N'));
        $total = $confirmed + $unconfirmed;
        $url = $page->get('url') . 'users/view/';
        $links = array();
        $links['View All Users (' . $total . ')'] = $url;
        $links['Confirmed (' . $confirmed . ')'] = $url . 'confirmed/';
        $links['Unconfirmed (' . $unconfirmed . ')'] = $url . 'unconfirmed/';
        $nav = new BootstrapNavigation;
        $html .= $nav->menu('pills', $links, array('align'=>'horizontal', 'active'=>$page->url()));
        unset($nav);
      $html .= '</div>';
      $html .= '<div class="col-sm-3">';
        $placeholder = (isset($_GET['search'])) ? $_GET['search'] : 'Search';
        $html .= '<form class="form-inline" method="get" action="' . $url . '" autocomplete="off">';
          $html .= '<div class="input-group">';
            $html .= '<input type="text" name="search" class="form-control" placeholder="' . $placeholder . '">';
            $html .= '<div class="input-group-btn">';
              $html .= '<button type="submit" class="btn btn-default" title="Submit"><i class="glyphicon glyphicon-search"></i></button>';
            $html .= '</div>';
          $html .= '</div>';
        $html .= '</form>';
      $html .= '</div>';
    $html .= '</div>';
    $html .= '<br>';
    $view = $page->next_uri(array('admin', 'view'));
    $links = new BootstrapPagination(100);
    $query = 'SELECT id, name, email, approval, confirmed, admin, date(registered) FROM users';
    $params = array();
    $where = ' ';
    if (isset($_GET['search'])) {
      $where .= 'WHERE email LIKE ? OR name LIKE ?';
      $params = array('%' . $_GET['search'] . '%', '%' . $_GET['search'] . '%');
      if (!$links->num_pages()) $links->count($this->db->value('SELECT COUNT(*) FROM users' . $where, $params));
    } elseif ($view == 'confirmed') {
      $where .= 'WHERE confirmed = ?';
      $params[] = 'Y';
      $links->count($confirmed);
    } elseif ($view == 'unconfirmed') {
      $where .= 'WHERE confirmed = ?';
      $params[] = 'N';
      $links->count($unconfirmed);
    } else {
      $links->count($total);
    }
    $html .= $this->table($query . $where . ' ORDER BY id DESC' . $links->limit(), $params);
    $html .= $links->display(5, 'left', 'small');
    return $html;
  }
  
  private function table ($query, $params) {
    global $page;
    $html = '';
    $html .= '<table class="table table-condensed table-striped">';
    $html .= '<thead>';
      $html .= '<tr>';
        $html .= '<th><i class="glyphicon glyphicon-pencil"></i></th>';
        $html .= '<th style="text-align:center; width:70px;">Confirmed</th>';
        $html .= '<th>Email</th>';
        $html .= '<th>Name</th>';
        $html .= '<th>Date</th>';
        $html .= '<th style="text-align:center; width:40px;">Admin</th>';
      $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    $this->db->query($query, $params);
    $edit = $page->get('url') . 'users/';
    while (list($id, $name, $email, $approval, $confirmed, $admin, $date) = $this->db->fetch('row')) {
      $confirmed = ($confirmed == 'Y') ? '<i class="glyphicon glyphicon-ok"></i>' : '';
      $admin = (!empty($admin)) ? '<i class="icon-ok"></i> ' . $admin : '';
      if ($approval == 'N') $name = '<span class="text-danger" title="Unapproved User" style="cursor:pointer;">' . $name . '</span>';
      $email = '<a href="' . $page->url('add', '', 'login', $email) . '">' . $email . '</a>';
      $html .= ($approval == 'N') ? '<tr class="warning">' : '<tr>';
        $html .= '<td><a href="' . $edit . '?edit=' . $id . '">' . $id . '</a></td>';
        $html .= '<td style="text-align:center;">' . $confirmed . '</td>';
        $html .= '<td>' . $email . '</td>';
        $html .= '<td>' . $name . '</td>';
        $html .= '<td>' . date('d M Y', strtotime($date)) . '</td>';
        $html .= '<td style="text-align:center;">' . $admin . '</td>';
      $html .= '</tr>';
    }
    $html .= '</tbody>';
    $html .= '</table>';
    return $html;
  }
  
}

?>