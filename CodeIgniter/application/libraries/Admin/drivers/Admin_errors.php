<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_errors extends CI_Driver {

  private $logs;
  
  public function __construct () {
    $this->logs = APPPATH . 'logs/';
  }
  
  public function btn () {
    global $bp, $page;
    $btn = '';
    if (is_admin(1) && $page->get('uri') != ADMIN . '/errors') {
      $logs = $this->logs();
      $url = $page->url('add', BASE_URL . ADMIN . '/errors', 'redirect', $page->url());
      if (!empty($logs)) $btn = '<p>' . $bp->button('danger block', 'View Errors', array('href'=>$url)) . '</p>';
    }
    return $btn;
  }
  
  public function view () {
    global $bp, $page;
    $html = '';
    if (isset($_POST['retrieve'])) {
      echo (is_admin(1) && file_exists(BASE . $_POST['retrieve'])) ? file_get_contents(BASE . $_POST['retrieve']) : '';
      exit;
    }
    if (isset($_POST['wyciwyg']) && isset($_POST['field'])) {
      if (is_admin(1) && file_exists(BASE . $_POST['field'])) {
        $result = $this->file_put_post(BASE . $_POST['field'], 'wyciwyg', false);
        echo ($result === true) ? 'Saved' : $result;
        exit;
      }
      echo 'Error';
      exit;
    }
    $logs = $this->logs();
    if (isset($_GET['delete']) && $_GET['delete'] == 'errors') {
      foreach ($logs as $log) unlink($this->logs . $log);
      $page->eject((isset($_GET['redirect'])) ? $_GET['redirect'] : $page->url('delete', '', 'delete'));
    }
    if (empty($logs)) return $this->admin('<div class="page-header" style="margin-top:20px;"><h3>View Errors</h3></div>');
    $display = $this->errors($logs);
    $delete = $bp->button('primary', 'Delete Errors', array('href'=>$page->url('add', '', 'delete', 'errors')));
    $count = '<span class="label label-danger pull-right">' . count($display) . '</span>';
    $html .= '<div class="page-header" style="margin-top:20px;"><h3>View Errors ' . $delete . $count . '</h3></div>';
    if (isset($display[404])) {
      $li = array('404 Page Not Found');
      foreach ($display[404] as $link => $count) {
        $li[][] = '<span class="label label-danger">' . $count . '</span> ' . $link . '</p>';
      }
      $html .= $bp->lister('dl', $li, 'dl-horizontal');
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
      // $media .= '<p>' . $file . '</p>';
      $media .= '<p>' . $msg . '</p>';
      $dl = array();
      foreach ($backtraces as $error) {
        list($msg, $file) = $this->msg_file($error);
        $dl[] = $msg;
        $dl[][] = $file;
      }
      $media .= $bp->lister('dl', $dl, 'dl-horizontal');
      $html .= $bp->media(array($left, $media, $right));
    }
    $html .= '<hr><p>' . $delete . '</p>';
    $page->plugin('CDN', 'link', 'jquery.timeago/1.3.0/jquery.timeago.min.js');
    $page->plugin('jQuery', 'code', '$("span.timeago").timeago();');
    return $this->admin($html);
  }
  
  public function logs () {
    global $ci;
    $ci->load->helper('file');
    $logs = get_filenames($this->logs);
    array_shift($logs); // index.html
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
            if (isset($errors[404][$line[2]])) {
              $errors[404][$line[2]]++;
            } else {
              $errors[404][$line[2]] = 1;
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
    return '<a class="wyciwyg php" href="#" data-retrieve="' . substr($file, 7) . '" data-line="' . $line . '">' . $file . '</a> Line: ' . $line;
  }
  
}

/* End of file Admin_errors.php */
/* Location: ./application/libraries/Admin/drivers/Admin_errors.php */