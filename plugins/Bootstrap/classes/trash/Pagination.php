<?php

class BootstrapPagination {

  private $num_pages;
  private $start;
  private $display;
  
  public function __construct ($display=10) {
    $this->num_pages = (isset($_GET['np']) && is_numeric($_GET['np']) && $_GET['np'] > 0) ? (int) $_GET['np'] : false;
    $this->start = (isset($_GET['s']) && is_numeric($_GET['s']) && $_GET['s'] > 0) ? (int) $_GET['s'] : 0;
    $this->display = (isset($_GET['display']) && is_numeric($_GET['display']) && $_GET['display'] > 0) ? (int) $_GET['display'] : (int) $display;
  }
  
  public function num_pages () {
    return $this->num_pages; // if (false) then we need to declare it in $this->count($total)
  }
  
  public function count ($total) {
    $this->num_pages = ($total > $this->display) ? ceil($total / $this->display) : 1;
  }
  
  public function limit () {
    return " LIMIT {$this->start}, {$this->display}";
  }
  
  public function last_page () {
    return ($this->num_pages == 1 || ($this->start / $this->display) >= $this->num_pages) ? true : false;
  }
  
  public function display ($split=5, $align="center", $size="normal") {
    global $page;
    $return = (is_array($split)) ? 'array' : 'string';
    if ($return == 'array') $split = array_shift($split);
    if ($this->num_pages === false || $this->num_pages == 1) return ($return == 'array') ? array() : '';
    $url = $page->url ('add', '', 'np', $this->num_pages);
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
    $pagination = array();
    $pagination['first'] = ($current_page != 1) ? $page->url('add', $url, 's', 0) : '';
    $pagination['previous'] = ($current_page != 1) ? $page->url('add', $url, 's', $this->start - $this->display) : '';
    $pagination['links'] = array();
    for ($i=$begin; $i<=$end; $i++) {
      $pagination['links'][$i] = ($i != $current_page) ? $page->url('add', $url, 's', ($this->display * ($i - 1))) : '';
    }
    $pagination['next'] = ($current_page != $this->num_pages) ? $page->url('add', $url, 's', $this->start + $this->display) : '';
    $pagination['last'] = ($current_page != $this->num_pages) ? $page->url('add', $url, 's', ($this->num_pages * $this->display) - $this->display) : '';
    #-- Return the Results --#
    if ($return == 'array') return $pagination;
    $class = 'pagination';
    switch ($size) {
      case 'large':
      case 'lg': $class .= ' pagination-lg'; break;
      case 'small':
      case 'sm': $class .= ' pagination-sm'; break;
      case 'mini':
      case 'xs': $class .= ' pagination-xs'; break;
      case 'normal':
      case 'md':
      default: break;
    }
    $ul = '<ul class="' . $class . '">';
    if (!empty($pagination['first'])) {
      $ul .= '<li><a title="First" href="' . $pagination['first'] . '">&laquo;</a></li>';
    } else {
      $ul .= '<li class="disabled"><span title="First">&laquo;</span></li>';
    }
    if (!empty($pagination['previous'])) {
      $ul .= '<li><a title="Previous" href="' . $pagination['previous'] . '">Previous</a></li>';
    } else {
      $ul .= '<li class="disabled"><span title="Previous">Previous</span></li>';
    }
    foreach ($pagination['links'] as $num => $link) {
      if (!empty($link)) {
        $ul .= '<li><a title="' . $num . '" href="' . $link . '">' . $num . '</a></li>';
      } else {
        $ul .= '<li class="active"><span title="' . $num . '">' . $num . '</span></li>';
      }
    }
    if (!empty($pagination['next'])) {
      $ul .= '<li><a title="Next" href="' . $pagination['next'] . '">Next</a></li>';
    } else {
      $ul .= '<li class="disabled"><span title="Next">Next</span></li>';
    }
    if (!empty($pagination['last'])) {
      $ul .= '<li><a title="Last" href="' . $pagination['last'] . '">&raquo;</a></li>';
    } else {
      $ul .= '<li class="disabled"><span title="Last">&raquo;</span></li>';
    }
    $ul .= '</ul>';
    switch ($align) {
      case 'left': break;
      case 'right': $ul = '<div style="text-align:right;">' . $ul . '</div>';  break;
      case 'center':
      default: $ul = '<div style="text-align:center;">' . $ul . '</div>';  break;
    }
    return $ul;
  }
  
  public function previous_next ($previous_link, $next_link, $align='center') {
    global $page;
    $links = array();
    if (!empty($previous_link)) {
      $class = ($align == 'sides') ? 'previous' : '';
      $disabled = (strpos($previous_link, 'a') == 1) ? '' : ' disabled';
      $links[] = '<li class="' . $class . $disabled . '">' . $previous_link . '</li>';
    }
    if (!empty($next_link)) {
      $class = ($align == 'sides') ? 'next' : '';
      $disabled = (strpos($next_link, 'a') == 1) ? '' : ' disabled';
      $links[] = '<li class="' . $class . $disabled . '">' . $next_link . '</li>';
    }
    return (!empty($links)) ? '<ul class="pager">' . implode('', $links) . '</ul>' : '';
  }
  
  public function previous_link ($title='Previous', $url='') {
    global $page;
    if ($this->num_pages === false || $this->num_pages == 1) return '';
    $current_page = ($this->start / $this->display) + 1;
    if ($current_page == 1) return '<span title="' . $title . '">&larr; ' . $title . '</span>';
    $url = $page->url('add', $url, 'np', $this->num_pages);
    $url = $page->url('add', $url, 's', $this->start - $this->display);
    return '<a title="' . $title . '" href="' . $url . '">&larr; ' . $title . '</a>';
  }
  
  public function next_link ($title='Next', $url='') {
    global $page;
    if ($this->num_pages === false || $this->num_pages == 1) return '';
    $current_page = ($this->start / $this->display) + 1;
    if ($current_page == $this->num_pages) return '<span title="' . $title . '">' . $title . '</span>';
    $url = $page->url('add', $url, 'np', $this->num_pages);
    $url = $page->url('add', $url, 's', $this->start + $this->display);
    return '<a title="' . $title . '" href="' . $url . '">' . $title . ' &rarr;</a>';
  }
  
}

?>