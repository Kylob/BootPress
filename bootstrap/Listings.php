<?php

class BootstrapListings extends Bootstrap {

  private $set = false;
  private $start = 0;
  private $display = 10;
  private $num_pages = false;
  private $left = '&laquo;'; // &larr;
  private $right = '&raquo;'; // &rarr;
  
  public function __construct () {
    global $ci;
    $this->display($ci->blog->config['pagination']);
    if (($start = $ci->input->get('s')) && is_numeric($start) && $start >= 1) {
      $this->start = (int) $start;
      if (($pages = $ci->input->get('np')) && is_numeric($pages) && $pages >= 1) {
        $this->num_pages = (int) $pages;
      }
    }
  }
  
  public function __get ($name) {
    global $ci, $page;
    if ($name == 'set') {
      if (!$this->set) {
        $this->set = true;
        if ($this->start % $this->display != 0) { // then these figures have been compromised
          $eject = $page->url('delete', '', array('display', 'np', 's'));
          if ($display = $ci->input->get('display')) $eject = $page->url('add', $eject, 'display', abs($display));
          $page->eject($eject);
        }
        if ($this->num_pages) { // then we are relying on a $_GET value
          if (($this->display * $this->num_pages) <= $this->start) $this->num_pages = false;
        }
      }
      return ($this->num_pages) ? true : false;
    }
    return (isset($this->$name)) ? $this->$name : null;
  }
  
  public function display ($listings) {
    global $ci;
    if (!$this->set) {
      if (($display = $ci->input->get('display')) && is_numeric($display) && $display >= 1) {
        $this->display = (int) $display; // so that the actual user has ultimate power over this value
      } elseif (is_numeric($listings) && $listings >= 1) {
        $this->display = (int) $listings;
      }
    }
  }
  
  public function count ($total) {
    if ($this->set) $this->num_pages = ($total > $this->display) ? ceil($total / $this->display) : 1;
  }
  
  public function limit () { // a helper string for our database query
    return ($this->set) ? ' LIMIT ' . $this->start . ', ' . $this->display : '';
  }
  
  public function symbols ($left='', $right='') {
    $this->left = $left;
    $this->right = $right;
  }
  
  public function pagination ($class='md', $split=3, $previous='Previous', $next='Next') { // there's no need to sanity check anything before calling this method
    global $page;
    if (!$this->set || $this->num_pages === false || $this->num_pages == 1) return ''; // we're not rolling out the listings on this page
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
      if (!empty($this->left)) $links .= '<li><a title="First" aria-label="First" href="' . $page->url('add', $url, 's', 0) . '">' . $this->left . '</a></li>';
      if (!empty($previous)) $links .= '<li><a title="Previous" aria-label="Previous" href="' . $page->url('add', $url, 's', $this->start - $this->display) . '">' . $previous . '</a></li>';
    } else {
      if (!empty($this->left)) $links .= '<li class="disabled"><span title="First" aria-label="First">' . $this->left . '</span></li>';
      if (!empty($previous)) $links .= '<li class="disabled"><span title="Previous" aria-label="Previous">' . $previous . '</span></li>';
    }
    for ($i=$begin; $i<=$end; $i++) {
      if ($i != $current_page) {
        $links .= '<li><a title="' . $i . '" aria-label="' . $i . '" href="' . $page->url('add', $url, 's', ($this->display * ($i - 1))) . '">' . $i . '</a></li>';
      } else {
        $links .= '<li class="active"><span title="' . $i . '" aria-label="' . $i . '">' . $i . '</span></li>';
      }
    }
    if ($current_page != $this->num_pages) {
      if (!empty($next)) $links .= '<li><a title="Next" aria-label="Next" href="' . $page->url('add', $url, 's', $this->start + $this->display) . '">' . $next . '</a></li>';
      if (!empty($this->right)) $links .= '<li><a title="Last" aria-label="Last" href="' . $page->url('add', $url, 's', ($this->num_pages * $this->display) - $this->display) . '">' . $this->right . '</a></li>';
    } else {
      if (!empty($next)) $links .= '<li class="disabled"><span title="Next" aria-label="Next">' . $next . '</span></li>';
      if (!empty($this->right)) $links .= '<li class="disabled"><span title="Last" aria-label="Last">' . $this->right . '</span></li>';
    }
    return '<nav><ul class="' . $this->classes('pagination', $class, array('sm', 'lg')) . '">' . $links . '</ul></nav>';
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
    return (!empty($links)) ? '<nav><ul class="pager">' . $links . '</ul></nav>' : '';
  }
  
  public function previous ($title='Previous', $url='') { // sanity checking is done in method
    global $page;
    if (empty($url)) {
      if ($this->num_pages === false || $this->num_pages == 1) return '';
      $current_page = ($this->start / $this->display) + 1;
      if ($current_page == 1) return '<span title="' . $title . '"><span aria-hidden="true">' . $this->left . '</span> ' . $title . '</span>';
      $url = $page->url('add', $url, 'np', $this->num_pages);
      $url = $page->url('add', $url, 's', $this->start - $this->display);
    }
    return (!empty($url)) ? '<a title="' . $title . '" href="' . $url . '"><span aria-hidden="true">' . $this->left . '</span> ' . $title . '</a>' : '';
  }
  
  public function next ($title='Next', $url='') { // sanity checking is done in method
    global $page;
    if (empty($url)) {
      if ($this->num_pages === false || $this->num_pages == 1) return '';
      $current_page = ($this->start / $this->display) + 1;
      if ($current_page == $this->num_pages) return '<span title="' . $title . '">' . $title . ' <span aria-hidden="true">' . $this->right . '</span></span>';
      $url = $page->url('add', $url, 'np', $this->num_pages);
      $url = $page->url('add', $url, 's', $this->start + $this->display);
    }
    return (!empty($url)) ? '<a title="' . $title . '" href="' . $url . '">' . $title . ' <span aria-hidden="true">' . $this->right . '</span></a>' : '';
  }
  
  public function last_page () {
    if (!$this->set) return false; // how should we know?
    if ($this->num_pages === false) return true; // this page would be the first and the last then
    return ($this->num_pages == 1 || ($this->start / $this->display) >= $this->num_pages) ? true : false;
  }
  
}

?>