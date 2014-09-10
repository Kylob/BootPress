<?php

class BootPressListings extends BootPress {

  private $display = false;
  private $num_pages = false;
  private $start = 0;
  private $left = '&laquo;'; // &larr;
  private $right = '&raquo;'; // &rarr;
  
  public function display () {
    global $page;
    if (func_num_args() == 0) { // so we know if the value has already been given before we brashly override it
      return ($this->display) ? true : false;
    } else { // we want to set $this->display value
      if (isset($_GET['display']) && is_numeric($_GET['display']) && $_GET['display'] > 0) {
        $this->display = (int) $_GET['display']; // so that the actual user has ultimate power over this value
      } else {
        $number = (int) func_get_arg(0);
        if ($number > 0) $this->display = $number;
      }
      if ($this->display) {
        $this->start = (isset($_GET['s']) && is_numeric($_GET['s']) && $_GET['s'] > 0) ? (int) $_GET['s'] : 0;
        $this->num_pages = (isset($_GET['np']) && is_numeric($_GET['np']) && $_GET['np'] > 0) ? (int) $_GET['np'] : false;
        if ($this->start % $this->display != 0) { // then these figures have been compromised
          $eject = $page->url('delete', '', array('display', 'np', 's'));
          if (isset($_GET['display'])) $eject = $page->url('add', $eject, 'display', abs($_GET['display']));
          $page->eject($eject);
        }
        if ($this->num_pages) { // then we are relying on a $_GET value
          if (($this->display * $this->num_pages) <= $this->start) $this->num_pages = false;
        }
      }
    }
  }
  
  public function count () {
    if (func_num_args() == 0) { // if (!$this->count()) we need to bother the database for this value, then come back and set it
      return ($this->num_pages) ? true : false;
    } elseif ($this->display()) { // this should already be established by now
      $total = (int) func_get_arg(0);
      $this->num_pages = ($total > $this->display) ? ceil($total / $this->display) : 1;
    }
  }
  
  public function limit () { // a helper string for our database query
    return ($this->display) ? ' LIMIT ' . $this->start . ', ' . $this->display : '';
  }
  
  public function symbols ($left='', $right='') {
    $this->left = $left;
    $this->right = $right;
    return $this;
  }
  
  public function pagination ($class='md', $split=3, $previous='Previous', $next='Next') { // there's no need to sanity check anything before calling this method
    global $page;
    if ($this->num_pages === false || $this->num_pages == 1) return ''; // we're not rolling out the listings on this page
    $url = $page->url('add', '', 'np', $this->num_pages);
    #-- Find the Beginning and the End --#
    $current_page = ($this->start / $this->display) + 1;
    $begin = $current_page - $split;
    $end = $current_page + $split;
    if ($begin < 1) {
      $begin = 1;
      $end = $split * 2;
    }
    if ($end > $this->num_pages) {
      $end = $this->num_pages;
      $begin = $end - ($split * 2);
      $begin++; // add one so that we get double the split at the end
      if ($begin < 1) $begin = 1;
    }
    #-- Pagination Links --#
    $links = '';
    if ($current_page != 1) {
      if (!empty($this->left)) $links .= '<li><a title="First" href="' . $page->url('add', $url, 's', 0) . '">' . $this->left . '</a></li>';
      if (!empty($previous)) $links .= '<li><a title="Previous" href="' . $page->url('add', $url, 's', $this->start - $this->display) . '">' . $previous . '</a></li>';
    } else {
      if (!empty($this->left)) $links .= '<li class="disabled"><span title="First">' . $this->left . '</span></li>';
      if (!empty($previous)) $links .= '<li class="disabled"><span title="Previous">' . $previous . '</span></li>';
    }
    for ($i=$begin; $i<=$end; $i++) {
      if ($i != $current_page) {
        $links .= '<li><a title="' . $i . '" href="' . $page->url('add', $url, 's', ($this->display * ($i - 1))) . '">' . $i . '</a></li>';
      } else {
        $links .= '<li class="active"><span title="' . $i . '">' . $i . '</span></li>';
      }
    }
    if ($current_page != $this->num_pages) {
      if (!empty($next)) $links .= '<li><a title="Next" href="' . $page->url('add', $url, 's', $this->start + $this->display) . '">' . $next . '</a></li>';
      if (!empty($this->right)) $links .= '<li><a title="Last" href="' . $page->url('add', $url, 's', ($this->num_pages * $this->display) - $this->display) . '">' . $this->right . '</a></li>';
    } else {
      if (!empty($next)) $links .= '<li class="disabled"><span title="Next">' . $next . '</span></li>';
      if (!empty($this->right)) $links .= '<li class="disabled"><span title="Last">' . $this->right . '</span></li>';
    }
    return '<ul class="' . $this->classes('pagination', $class, array('sm', 'lg')) . '">' . $links . '</ul>';
  }
  
  public function pager ($previous, $next, $align='center') {
    $links = '';
    if (!empty($previous)) {
      $class = ($align == 'sides') ? 'previous' : '';
      if (strpos($previous, 'a') != 1) $class .= ' disabled';
      $links .= '<li class="' . trim($class) . '">' . $previous . '</li> ';
    }
    if (!empty($next)) {
      $class = ($align == 'sides') ? 'next' : '';
      if (strpos($next, 'a') != 1) $class .= ' disabled';
      $links .= ' <li class="' . trim($class) . '">' . $next . '</li>';
    }
    return (!empty($links)) ? '<ul class="pager">' . $links . '</ul>' : '';
  }
  
  public function previous ($title='Previous', $url='') { // sanity checking is done in method
    global $page;
    if (empty($url)) {
      if ($this->num_pages === false || $this->num_pages == 1) return '';
      $current_page = ($this->start / $this->display) + 1;
      if ($current_page == 1) return '<span title="' . $title . '">' . $this->left . ' ' . $title . '</span>';
      $url = $page->url('add', $url, 'np', $this->num_pages);
      $url = $page->url('add', $url, 's', $this->start - $this->display);
    }
    return (!empty($url)) ? '<a title="' . $title . '" href="' . $url . '">' . $this->left . ' ' . $title . '</a>' : '';
  }
  
  public function next ($title='Next', $url='') { // sanity checking is done in method
    global $page;
    if (empty($url)) {
      if ($this->num_pages === false || $this->num_pages == 1) return '';
      $current_page = ($this->start / $this->display) + 1;
      if ($current_page == $this->num_pages) return '<span title="' . $title . '">' . $title . ' ' . $this->right . '</span>';
      $url = $page->url('add', $url, 'np', $this->num_pages);
      $url = $page->url('add', $url, 's', $this->start + $this->display);
    }
    return (!empty($url)) ? '<a title="' . $title . '" href="' . $url . '">' . $title . ' ' . $this->right . '</a>' : '';
  }
  
  public function last_page () {
    if ($this->num_pages === false) return true; // this page would be the first and the last then
    return ($this->num_pages == 1 || ($this->start / $this->display) >= $this->num_pages) ? true : false;
  }
  
}

?>