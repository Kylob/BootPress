<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_errors extends CI_Driver {

  private $logs;
  
  public function __construct () {
    $this->logs = APPPATH . 'logs/';
  }
  
  public function link () {
    global $page;
    return  (is_admin(1) && ($logs = $this->logs()) && !empty($logs)) ? $page->url('add', $page->url($this->url, 'errors'), 'redirect', $page->url()) : null;
  }
  
  public function view () {
    global $bp, $ci, $page;
    $html = '';
    if (!is_admin(1)) return $html;
    $ci->admin->files->save(BASE);
    $logs = $this->logs();
    if ($ci->input->get('delete') == 'errors') {
      foreach ($logs as $log) unlink($this->logs . $log);
      if ($redirect = $ci->input->get('redirect')) $page->eject($redirect);
      $page->eject($page->url('delete', '', 'delete'));
    }
    if (empty($logs)) return $this->display($html);
    $display = $this->errors($logs);
    if (isset($display[404])) {
      $li = array();
      foreach ($display[404] as $link => $info) {
        foreach ($info as $ip => $hits) {
          $li[] = '<p><span class="label label-danger">' . $hits . '</span> ' . $link . ' <span class="pull-right">' . $ip . '</span></p>';
        }
      }
      $html .= $bp->lister('dl dl-horizontal', array('404 Page Not Found' => $li));
      unset($display[404]);
    }
    foreach ($display as $row) {
      list($date, $severity, $error) = $row;
      list($msg, $file) = $this->msg_file($error);
      $count = array_pop($row);
      $backtraces = array_slice($row, 3);
      $left = '<span class="label label-danger">' . $count . '</span>';
      $right = '<span class="timeago" title="' . date('c', strtotime($date)) . '">' . $date . '</span>';
      $media = '<p><b>' . $this->severity(substr($severity, 10)) . '</b></p>';
      $media .= '<p>' . $msg . '</p>';
      $li = array();
      if (empty($backtraces)) {
        $li[] = $file;
      } else {
        foreach ($backtraces as $error) {
          list($msg, $file) = $this->msg_file($error);
          $li[] = '<b>' . $msg . '</b> ' . $file;
        }
      }
      $media .= $bp->lister('ul list-unstyled', $li);
      $html .= $bp->media(array($left, $media, $right));
    }
    $page->plugin('CDN', 'link', 'jquery.timeago/1.3.0/jquery.timeago.min.js');
    $page->plugin('jQuery', 'code', '$("span.timeago").timeago();');
    $delete = $bp->button('primary', 'Delete Errors&nbsp;&nbsp;' . $bp->badge(count($display)), array('href'=>$page->url('add', '', 'delete', 'errors')));
    $html = $this->box('default', array(
      'head' => array($delete),
      'body' => '<div style="margin:20px auto;">' . $html . '</div>' . $delete
    ));
    return $this->display($html);
  }
  
  public function logs () {
    global $ci;
    $ci->load->helper('file');
    $logs = array_diff(get_filenames($this->logs), array('index.html'));
    return $logs;
  }
  
  private function errors ($logs) {
    $errors = array();
    foreach ($logs as $path) {
      $file = file_get_contents($this->logs . $path);
      $file = preg_split('/\n(error|debug|info)\s-/i', $file);
      foreach ($file as $num => $line) {
        $line = array_map('trim', explode('-->', $line));
        if (isset($line[1])) {
          if (strpos($line[1], 'Severity:') !== false) {
            $key = md5(substr($line[2], 0, strrpos($line[2], ' '))); // without the file line number
            $count = (isset($errors[$key])) ? array_pop($errors[$key]) : 0;
            $errors[$key] = $line;
            $errors[$key][] = $count + 1;
          } elseif (substr($line[1], 0, 3) == 404) {
            if (isset($errors[404][$line[2]][$line[3]])) {
              $errors[404][$line[2]][$line[3]]++;
            } else {
              $errors[404][$line[2]][$line[3]] = 1;
            }
          }
        }
      }
    }
    return array_reverse($errors, true);
  }
  
  private function severity ($error) {
    if (!is_numeric($error)) return $error;
    switch ($error) {
      case 1: return 'Error'; break; // E_ERROR
      case 2: return 'Warning'; break; // E_WARNING
      case 4: return 'Parsing Error'; break; // E_PARSE
      case 8: return 'Notice'; break; // E_NOTICE
      case 16: return 'Core Error'; break; // E_CORE_ERROR
      case 32: return 'Core Warning'; break; // E_CORE_WARNING
      case 64: return 'Compile Error'; break; // E_COMPILE_ERROR
      case 128: return 'Compile Warning'; break; // E_COMPILE_WARNING
      case 256: return 'User Error'; break; // E_USER_ERROR
      case 512: return 'User Warning'; break; // E_USER_WARNING
      case 1024: return 'User Notice'; break; // E_USER_NOTICE
      case 2048: return 'Runtime Notice'; break; // E_STRICT
      case 4096: return 'Recoverable Error'; break; // E_RECOVERABLE_ERROR
      case 8192: return 'Deprecated'; break; // E_DEPRECATED
      case 16384: return 'User Deprecated'; break; // E_USER_DEPRECATED
      default: return $error; break;
    }
  }
  
  private function msg_file ($error) {
    $error = explode(' ', $error);
    $file = $this->edit_link(array_pop($error), array_pop($error));
    $msg = implode(' ', $error);
    if (strpos($msg, "\n") && strpos($msg, '<pre>') === false) {
      $msg = '<pre>' . $msg . '</pre>';
    }
    return array($msg, $file);
  }
  
  private function edit_link ($line, $file) {
    global $page;
    $file = str_replace('\\', '/', $file);
    $url = $page->url('add', '', array('file'=>substr($file, strlen(BASE)), 'line'=>$line));
    $file = str_replace(BASE, 'BASE . ', $file);
    return '<a class="wyciwyg php" href="#" data-retrieve="' . substr($file, 7) . '" data-file="' . basename($file) . '" data-line="' . $line . '">' . $file . '</a> Line: ' . $line;
  }
  
}

/* End of file Admin_errors.php */
/* Location: ./application/libraries/Admin/drivers/Admin_errors.php */