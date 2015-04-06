<?php

function ImageMagick ($command, $params, $dir='') {
  $current = getcwd();
  if (!empty($dir)) chdir($dir);
  $cmd = array(escapeshellarg(IMAGEMAGICK_PATH . $command));
  if (is_array($params)) {
    foreach ($params as $key => $value) {
      if (strpos($value, BASE) !== false) $value = escapeshellarg($value);
      if (!is_numeric($key)) {
        $cmd[] = '-' . $key . ' ' . $value;
      } else {
        $cmd[] = $value;
      }
    }
  } else {
    $cmd[] = $params;
  }
  $return = null;
  $output = array();
  $cmd = implode(' ', $cmd);
  exec($cmd . ' 2>&1', $output, $return);
  chdir($current);
  if ($return != 0) log_message('error', "ImageMagick command failed:\n\n" . $cmd . "\n\n" . implode("\n", $output));
  return array('cmd'=>$cmd, 'output'=>$output, 'return'=>$return);
}

?>