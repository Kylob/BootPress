<?php

class ViewErrors {

  private $errors;
  private $db;
  
  public function __construct () {
    global $page;
    $this->errors = Error::Handler();
    $this->db = $this->errors->db();
    if (isset($_GET['delete']) && $_GET['delete'] == 'errors') {
      $this->db->exec('DELETE FROM errors');
      $this->db->exec('DELETE FROM backtraces');
      $page->eject($page->url('delete', '', 'delete'));
    }
  }
  
  public function page () {
    global $page;
    $page->title = 'View Errors';
    $html = '';
    $count = $this->errors->count();
    if (empty($count)) return $html;
    $html .= '<h4>View Errors</h4>';
    $domains = array();
    $this->db->query("SELECT id, domain FROM domains");
    while (list($id, $domain) = $this->db->fetch('row')) $domains[$id] = $domain;
    $this->db->query("SELECT group_concat(id), group_concat(domain_id), count(*), num, file, line, msg, datetime(min(date), 'localtime'), datetime(max(date), 'localtime') FROM errors GROUP BY file, line, msg");
    $rows = $this->db->fetch('row', 'all');
    $html .= '<table class="table table-striped table-bordered table-hover">';
    foreach ($rows as $row) {
      $html .= '<tr><td>';
      list($ids, $dids, $count, $num, $file, $line, $msg, $from, $to) = $row;
      $dids = array_unique(explode(',', $dids));
      foreach ($dids as $domain => $id) $dids[$domain] = $domains[$id];
      $html .= '<p>';
        $html .= '<span class="label label-danger">' . $count . '</span>';
        $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $html .= '<b>' . $this->error_types($num) . '</b>';
        $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $html .= ' @ ' . implode(', ', $dids);
      $html .= '</p>';
      $html .= '<p class="text-error">' . nl2br($msg) . '</p>';
      $html .= '<ul>';
      $backtraces = $this->backtrace($ids, $count);
      if (!isset($backtraces[$file]) || $backtraces[$file]['line'] != $line) {
        $html .= '<li class="text-error">@ ' . $this->edit_link($file, $line) . ' Line: ' . $line . '</li>';
        $file = false; // so that it doesn't $match
      }
      foreach ($backtraces as $match => $debug) {
        if ($match == $file) {
          $html .= '<li class="text-error">' . $debug['string'] . '</li>';
        } else {
          $html .= '<li>' . $debug['string'] . '</li>';
        }
      }
      $html .= '<li><b>From:</b> ' . date('m-d D g:i a', strtotime($from)) . ' <b>To:</b> ' . date('m-d D g:i a', strtotime($to)) . '</li>';
      $html .= '</ul>';
      $html .= '</td></tr>';
    }
    $html .= '</table>';
    $html .=  '<p><a class="btn btn-danger" href="' . $page->url('add', '', 'delete', 'errors') . '">Delete Error Messages</a></p><hr />';
    return $html;
  }
  
  private function backtrace ($errors, $num) { // comma-delimited error_id's
    $backtraces = array();
    $this->db->query("SELECT count(*), file, line, class, function FROM backtraces WHERE error_id IN ({$errors}) GROUP BY file, line ORDER BY id ASC");
    while (list($count, $file, $line, $class, $function) = $this->db->fetch('row')) {
      $count = ($count != $num) ? '<small><span class="badge">' . $count . '</span></small> ' : '';
      if (!empty($function)) {
        if (!empty($class)) $function = $class . '::' . $function;
        $function .= ' @ ';
      }
      $backtraces[$file]['line'] = $line;
      $backtraces[$file]['string'] = $count . $function . $this->edit_link($file, $line) . ' Line: ' . $line;
    }
    return $backtraces;
  }
  
  private function error_types ($type) {
    $return = '';
    if ($type & E_ERROR) $return.='| E_ERROR '; // 1
    if ($type & E_WARNING) $return.='| E_WARNING '; // 2
    if ($type & E_PARSE) $return.='| E_PARSE '; // 4
    if ($type & E_NOTICE) $return.='| E_NOTICE '; // 8
    if ($type & E_CORE_ERROR) $return.='| E_CORE_ERROR '; // 16
    if ($type & E_CORE_WARNING) $return.='| E_CORE_WARNING '; // 32
    if ($type & E_CORE_ERROR) $return.='| E_COMPILE_ERROR '; // 64
    if ($type & E_CORE_WARNING) $return.='| E_COMPILE_WARNING '; // 128
    if ($type & E_USER_ERROR) $return.='| E_USER_ERROR '; // 256
    if ($type & E_USER_WARNING) $return.='| E_USER_WARNING '; // 512
    if ($type & E_USER_NOTICE) $return.='| E_USER_NOTICE '; // 1024
    if ($type & E_STRICT) $return.='| E_STRICT '; // 2048
    if ($type & E_RECOVERABLE_ERROR) $return.='| E_RECOVERABLE_ERROR '; // 4096
    if ($type & E_DEPRECATED) $return.='| E_DEPRECATED '; // 8192
    if ($type & E_USER_DEPRECATED) $return.='| E_USER_DEPRECATED '; // 16384
    return (!empty($return)) ? substr($return, 2, -1) : '';
  }
  
  private function edit_link ($file, $line='') {
    global $page;
    $file = str_replace('\\', '/', $file);
    $url = $page->url('add', '', array('file'=>substr($file, strlen(BASE)), 'line'=>$line));
    $file = str_replace(array(BASE_URI, BASE), array('BASE_URI . ', 'BASE . '), $file);
    return '<a href="' . $url . '" title="View and Edit Code">' . $file . '</a>';
  }
  
}

?>