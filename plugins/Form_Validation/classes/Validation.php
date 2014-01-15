<?php

class Validation {

  public function form ($type, $check, $required=array()) {
    global $page;
    $vars = array();
    $errors = array();
    $eject = $page->url('delete', '', 'submitted');
    if (is_array($type)) {
      list($type, $form) = each($type);
      if (!isset($_REQUEST[$form])) return array($vars, $errors, $eject);
    }
    $form = (strtolower($type) == 'get') ? $_GET : $_POST;
    if (get_magic_quotes_gpc()) $form = $this->stripslashes_deep($form);
    foreach ($check as $name => $filter) {
      $var = (isset($form[$name])) ? $form[$name] : '';
      if (is_array($var)) {
        foreach ($var as $key => $value) {
          $vars[$name][$key] = $this->data($filter, $value);
        }
      } else {
        if (isset($check["{$filter}"]) && $filter != $name) { // Then this is a cross check
          $value = $form[$filter];
          $vars[$name] = ($value == $var) ? $var : ''; // all that matters is if this value equals something or not, as it is not used but may be required
        } else {
          $vars[$name] = $this->data($filter, $var);
        }
      }
      if (empty($vars[$name]) && in_array($name, $required)) {
        $msg = $this->error($filter, $check);
        $errors[$name] = $msg;
      }
    }
    return array($vars, $errors, $eject);
  }
  
  private function stripslashes_deep ($value) { // used in $this->form()
    return (is_array($value)) ? array_map(array($this, 'stripslashes_deep'), $value) : stripslashes($value);
  }
  
  public function jquery ($form, $check, $required=array(), $info=array()) {
    global $page;
    foreach ($required as $name) {
      if (substr($name, -2) == '[]') { // a multiselect
        $name = substr($name, 0, -2);
        if (isset($check[$name])) {
          $check[$name.'[]'] = $check[$name];
          unset ($check[$name]);
        }
      }
    }
    $rules = array();
    foreach ($check as $name => $filter) {
      if (isset($multi[$name])) $name .= $multi[$name];
      list($filter, $param) = $this->filter_param ($filter);
      $rule = (in_array($name, $required)) ? array('required:true') : array();
      switch ($filter) {
        case 'a':		$rule[]='alpha:true'; break;
        case 'an':		$rule[]='alphanumeric:true'; break;
        case 'ap':		$rule[]='alphapunctuation:true'; break;
        case 'anp':		$rule[]='alphanumericpunctuation:true'; break;
        case 'num':		$rule[]='number:true'; break;
        case 'nummin':		$rule[]='number:true'; $rule[]="min:{$param[0]}"; break;
        case 'nummax':		$rule[]='number:true'; $rule[]="max:{$param[0]}"; break;
        case 'numrange':	$rule[]='number:true'; $rule[]="range:[{$param[0]},{$param[1]}]"; break;
        case 'int':		$rule[]='digits:true'; $rule[]="min:0"; break;
        case 'intmin':		$rule[]='digits:true'; $rule[]="min:{$param[0]}"; break;
        case 'intmax':		$rule[]='digits:true'; $rule[]="max:{$param[0]}"; break;
        case 'intrange':	$rule[]='digits:true'; $rule[]="range:[{$param[0]},{$param[1]}]"; break;
        case 'float':		$rule[]='number:true'; $rule[]="min:0"; break;
        case 'floatmin':	$rule[]='number:true'; $rule[]="min:{$param[0]}"; break;
        case 'floatmax':	$rule[]='number:true'; $rule[]="max:{$param[0]}"; break;
        case 'floatrange':	$rule[]='number:true'; $rule[]="range:[{$param[0]},{$param[1]}]"; break;
        case 'string':		break;
        case 'stringmin':	$rule[]="minlength:{$param[0]}"; break;
        case 'stringmax':	$rule[]="maxlength:{$param[0]}"; break;
        case 'stringrange':	$rule[]="rangelength:[{$param[0]},{$param[1]}]"; break;
        case 'wordmin':		$rule[]="minWords:{$param[0]}"; break;
        case 'wordmax':		$rule[]="maxWords:{$param[0]}"; break;
        case 'wordrange':	$rule[]="rangeWords:[{$param[0]},{$param[1]}]"; break;
        case 'datetime':	break;
        case 'date':		$rule[]='date:true'; break;
        case 'time':		break;
        case 'email':		$rule[]='email:true'; break;
        case 'pass':		$rule[]='password:true'; break;
        case 'url':		$rule[]='url:true'; break;
        case 'phone':		break;
        case 'zip':		break;
        case 'ssn':		break;
        case 'cc':		$rule[]='creditcard:true';
        case 'YN':		break;
        default:
          if (isset($check["{$filter}"])) { // then this is a crosscheck
            $rule[]='equalTo:"#' . $filter . '"';
          }
          break;
      }
      if (!empty($rule)) $rules[] = "\"{$name}\":{" . implode(',', $rule) . "}";
    }
    $jquery = '$("#' . $form . '").validate({';
    if (in_array('recaptcha', $required)) {
      $jquery .= 'onkeyup: function(element) { var element_id = jQuery(element).attr("id"); if (this.settings.rules[element_id].onkeyup !== false) jQuery.validator.defaults.onkeyup.apply(this, arguments); }, ';
      $rules[] = "\"recaptcha_response_field\":{required:true,recaptcha:true,onkeyup:false}";
    }
    $jquery .= 'rules:{' . implode(', ', $rules) . '}';
    $jquery .= ', errorClass:"has-error"';
    $jquery .= ', validClass:""';
    $jquery .= ', errorElement:"div"';
    $jquery .= ', highlight:highlight';
    $jquery .= ', unhighlight:unhighlight';
    $jquery .= ', errorPlacement:errorPlacement';
    $jquery .= ', submitHandler:submitHandler';
    $jquery .= '});'; // end validate
    if (!empty($info)) {
      $page->link('<style type="text/css">#' . $form . ' div.tooltip-inner { text-align:left; max-width:500px; }</style>');
      $jquery .= '$("#' . $form . ' span.glyphicon-question-sign").tooltip({html:true, placement:"right", container:"#' . $form . '"});';
    }
    $page->plugin('jQuery', array('code'=>$jquery));
  }
  
  public function recaptcha () {
    if (!isset($_POST['recaptcha_response_field'])) return '';
    $answer = recaptcha_check_answer (RECAPTCHA_PRIVATE_KEY, $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
    $error = 'The reCAPTCHA entered was incorrect. Please try again.';
    if (isset($_POST['ajax']) && $_POST['ajax'] == 'request') {
      if ($answer->is_valid) {
        $_SESSION['recaptcha'] = 'passed';
        echo json_encode(true);
      } else {
        echo json_encode($error);
      }
      exit;
    }
    if (isset($_SESSION['recaptcha']) && $_SESSION['recaptcha'] == 'passed') {
      unset ($_SESSION['recaptcha']);
    } elseif (!$answer->is_valid) {
      return $error;
    }
    return '';
  }

  public function data ($filter, $data) {
    list($filter, $param) = $this->filter_param ($filter);
    #-- Check for a default value for $data --#
    $data = trim($data);
    $split = (is_array($filter)) ? false : strpos($filter, ' '); // returns false if there is no space
    if ($split !== false) {
      $array = explode(' ', trim($filter));
      $filter = array_shift($array);
      if (empty($data)) $data = implode(' ', $array); // this fields default value
    }
    switch ($filter) {
      case 'a':			return $this->alpha ($data); break;
      case 'an':		return $this->alphanumeric ($data); break;
      case 'ap':		return $this->alphapunctuation ($data); break;
      case 'anp':		return $this->alphanumericpunctuation ($data); break;
      case 'num':		return $this->number ($data); break;
      case 'nummin':		$num = $this->number ($data); return ($num >= $param[0]) ? $num : 0;
      case 'nummax':		$num = $this->number ($data); return ($num <= $param[0]) ? $num : 0;
      case 'numrange':		$num = $this->number ($data); return ($num >= $param[0] && $num <= $param[1]) ? $num : 0;
      case 'int':		return $this->integer ($data); break;
      case 'intmin':		$num = $this->integer ($data); return ($num >= $param[0]) ? $num : 0;
      case 'intmax':		$num = $this->integer ($data); return ($num <= $param[0]) ? $num : 0;
      case 'intrange':		$num = $this->integer ($data); return ($num >= $param[0] && $num <= $param[1]) ? $num : 0;
      case 'float':		return $this->float ($data); break;
      case 'floatmin':		$num = $this->float ($data); return ($num >= $param[0]) ? $num : 0;
      case 'floatmax':		$num = $this->float ($data); return ($num <= $param[0]) ? $num : 0;
      case 'floatrange':	$num = $this->float ($data); return ($num >= $param[0] && $num <= $param[1]) ? $num : 0;
      case 'string':		return $this->strip ($data); break;
      case 'stringmin':		$str = $this->strip ($data); $len = strlen($str); return ($len >= $param[0]) ? $str : '';
      case 'stringmax':		$str = $this->strip ($data); $len = strlen($str); return ($len <= $param[0]) ? $str : '';
      case 'stringrange':	$str = $this->strip ($data); $len = strlen($str); return ($len >= $param[0] && $len <= $param[1]) ? $str : '';
      case 'wordmin':		$str = $this->strip ($data); $words = explode(' ', $str); $len = count($words); return ($len >= $param[0]) ? $str : '';
      case 'wordmax':		$str = $this->strip ($data); $words = explode(' ', $str); $len = count($words); return ($len <= $param[0]) ? $str : '';
      case 'wordrange':		$str = $this->strip ($data); $words = explode(' ', $str); $len = count($words); return ($len >= $param[0] && $len <= $param[1]) ? $str : '';
      case 'datetime':		return $this->datetime ($data); break;
      case 'date':		$format = (isset($param[0])) ? $param[0] : 'Y-m-d'; return $this->date ($data, $format); break;
      case 'time':		$format = (isset($param[0])) ? $param[0] : 'H:i:s'; return $this->time ($data, $format); break;
      case 'email':		return $this->email ($data); break;
      case 'pass':		return $this->password ($data); break;
      case 'url':		return $this->url ($data); break;
      case 'phone':		return $this->phone ($data); break;
      case 'zip':		return $this->zip ($data); break;
      case 'ssn':		return $this->social_security ($data); break;
      case 'cc':		return $this->credit_card ($data); break;
      case 'YN':		return $this->yes_no ($data); break;
      default: if (!empty($filter) && is_array($filter)) return (in_array($data, $filter)) ? $data : ''; break;
    }
    return (!empty($data)) ? $this->safe($data) : '';
  }

  public function multidata ($filter, $data, $delimiter='<br />', $return='') {
    $array = array();
    $data = $this->strip($data, '<br>');
    $data = explode($delimiter, $data);
    foreach ($data as $value) {
      $temp = $this->data($filter, $value);
      if (!empty($temp)) $array[] = $temp;
    }
    if (empty($return)) $return = $delimiter;
    return implode($return, array_unique($array));
  }
  
  public function title ($string) {
    $string = ucwords(strtolower($string));
    $words = str_word_count($string, 2);
    $words = array_slice($words, 1, -1, true);
    foreach ($words as $pos => $word) {
      switch ($word) {
        case 'A':
        case 'An':
        case 'The':
        case 'But':
        case 'As':
        case 'If':
        case 'And':
        case 'Or':
        case 'Nor':
        case 'Of':
        case 'By':
          $lower = strtolower($word);
          $string{$pos} = $lower{0};
      }
    }
    return $string;
  }

  public function truncate ($string, $max=255, $moretext='...') {
    if (strlen($string) > $max) {
      $max -= strlen(strip_tags($moretext));
      $string = strrev(strstr(strrev(substr($string, 0, $max)), ' '));
      $string .= $moretext;
    }
    return $string;
  }

  // Inspired by:
  // http://htmlblog.net/seo-friendly-url-in-php/
  // http://php.dzone.com/news/generate-search-engine-friendl
  function seo ($string, $implode='-') {
    $string = strtolower(htmlentities($string, ENT_COMPAT, 'utf-8'));
    $string = preg_replace('`&(amp;)?#?[a-z0-9]+;`i', ' ', $string);
    $string = preg_replace('/[^a-z0-9\s]/', ' ', $string);
    $remove = array('a','and','the','an','it','is','with','can','of','why','not', 's');
    $words = explode(' ', $string);
    $seo = array();
    foreach ($words as $word) {
      if (!empty($word) && !in_array($word, $seo) && !in_array($word, $remove)) {
        $seo[] = $word;
      }
    }
    return implode($implode, $seo);
  }

  function columnize ($array, $spacing=3) { // $array should be multidimensional
    $html = '';
    $clean = array();
    foreach ($array as $num => $row) {
      foreach ($row as $column) {
        $clean[$num][] = $column;
      }
    }
    $widths = array();
    foreach ($clean as $row) {
      foreach ($row as $key => $col) {
        $column = html_entity_decode(strip_tags($col), ENT_QUOTES);
        if (!isset($widths[$key]) || (strlen($column) > $widths[$key])) {
          $widths[$key] = strlen($column);
        }
      }
    }
    $html .= '<pre>';
    foreach ($clean as $row) {
      $count = 0;
      foreach ($row as $key => $column) {
        if ($count++) {
          $html .= str_repeat (' ', $spacing);
        }
        $html .= str_pad($column, $widths[$key]);
      }
      $html .= "\n";
    }
    $html .= '</pre>';
    return $html;
  }

  private function alpha ($data) {
    return preg_replace('/\s(?=\s)/', '', preg_replace('/[^a-z\s]/i', '', $data));
  }

  private function alphanumeric ($data) {
    return preg_replace('/\s(?=\s)/', '', preg_replace('/[^a-z0-9\s]/i', '', $data));
  }

  private function alphapunctuation ($data) { // .,?!:;-_()[]'"/
    return preg_replace('/\s(?=\s)/', '', preg_replace('/[^a-z.,?!:;\-_()\[\]\'\"\/\s]/i', '', $data));	
  }

  private function alphanumericpunctuation ($data) {
    return preg_replace('/\s(?=\s)/', '', preg_replace('/[^a-z0-9.,?!:;\-_()\[\]\'\"\/\s]/i', '', $data));	
  }

  private function number ($number) { // a positive or negative number we can work with
    $number = preg_replace('/[^-.0-9]/', '', $number);
    $number = preg_replace('/(?<=.)-/', '', $number);
    return (!empty($number)) ? $number : 0;
  }

  private function integer ($number) { // a positive whole number
    $number = round($this->number($number), 0);
    return ($number > 0) ? $number : 0;
  }

  private function float ($number) { // a positive number with (optional) decimal
    $number = $this->number($number);
    return ($number > 0) ? $number : 0;
  }

  private function datetime ($time) {
    $unix = strtotime($time);
    if ($unix) return date('Y-m-d H:i:s', $unix);
    return '';
  }

  private function date ($day, $format='Y-m-d') { // or 'm/d/Y'
    $unix = strtotime($day);
    if ($unix) return date($format, $unix);
    return '';
  }

  private function time ($time, $format='H:i:s') {
    $unix = (!empty($time)) ? strtotime('today ' . $time) : false;
    if ($unix) return date($format, $unix);
    return '';
  }

  private function email ($address) {
    if ($this->validate($address, '/[a-zA-Z0-9_\.\-]+@[a-zA-Z0-9\.\-]+\.[a-zA-Z]{2,6}/')) return $address;
    return '';
  }

  private function password ($code) {
    if ($this->validate($code, '/^[\S]{6,}$/')) return $code; // At least 6 characters and no spaces
    return '';
  }
  
  private function url ($site) {
    $scheme = "(https?|ftp)\:\/\/";
    $user = "([A-Za-z0-9+!*(),;?&=\$_.-]+(\:[A-Za-z0-9+!*(),;?&=\$_.-]+)?@)?"; // user and pass if ftp
    $host = "([A-Za-z0-9-.]*)\.([A-Za-z]{2,4})";
    $port = "(\:[0-9]{2,5})?"; // again, if ftp
    $path = "(\/([A-Za-z0-9+\$_-]\.?)+)*\/?";
    $get = "(\?[A-Za-z+&\$_.-][A-Za-z0-9;:@&%=+\/\$_.-]*)?";
    $anchor = "(#[A-Za-z_.-][A-Za-z0-9+\$_.-]*)?";
    if ($this->validate($site, '/' . $scheme . $user . $host . $port . $path . $get . $anchor . '/')) return $site;
    if ($this->validate($site, '/' . $host . $path . $get . $anchor . '/')) return 'http://' . $site;
    return '';
  }

  private function phone ($number) {
    if (empty($number)) return '';
    $int = $ext = '';
    $number = preg_replace('/[^\+x0-9]/i', '', $number);
    if (substr($number, 0, 1) == '+') { // international number
      $int = preg_replace('/^\+((?:1|2[078]|3[0-469]|4[013-9]|5[1-8]|6[0-6]|7|8[1-469]|9[0-58])|[0-9]{3})?/', '', $number); // ie. everything but
      $int = str_replace($int, '', $number) . ' '; // get the but back
      $number = substr($number, strlen($int) - 1);
    }
    $pos = strripos($number, 'x');
    if ($pos !== false) {
      $ext = ' x' . substr($number, $pos + 1);
      $number = substr($number, 0, $pos);
    }
    $number = preg_replace('/[^0-9]/', '', $number);
    $length = strlen($number);
    if ($length < 7 || $length > 15) return '';
    $country = (!empty($int)) ? substr(trim($int), 1) : 1;
    if (in_array($country, array(1, 7, 31, 39, 41, 90, 386, 423))) { // rule of 4 thirds
      // USA and Canada, Russia, Netherlands, Italy, Switzerland, Turkey, Slovenia, Liechtenstein
      $four = substr($number, -4);
      $number = $this->str_rsplit(substr($number, 0, -4), 3);
      if (in_array($country, array(41, 90, 423))) { // split the 4 in 2's
        // Switzerland, Turkey, Liechtenstein
        $number = array_merge($number, str_split($four, 2));
      } else {
        $number = array_merge($number, (array) $four);
      }
    } elseif (in_array($country, array(49, 64))) { // rule of 2, 3, blah ...
      // Germany, New Zealand
      $number = array(substr($number, 0, 2), substr($number, 2, 3), substr($number, 5));
    } elseif (in_array($country, array(33, 52, 375))) { // rule of 2's
      $number = $this->str_rsplit($number, 2); // France, Mexico, Belarus
    } elseif (in_array($country, array(34, 593))) { // rule of 3's
      $number = $this->str_rsplit($number, 3); // Spain, Ecuador
    } elseif (in_array($country, array(44, 45, 47, 55, 61, 65, 86, 852))) { // rule of 4's
      $number = $this->str_rsplit($number, 4); // UK, Denmark, Norway, Brazil, Australia, Singapore, China, Hong Kong
    } elseif (in_array($country, array(91))) { // rule of 5's
      $number = $this->str_rsplit($number, 5); // India
    }
    if (is_array($number)) {
      $glue = ($country == 1) ? '-' : ' ';
      $number = implode($glue, $number);
    }
    return $int . $number . $ext;
  }

  private function str_rsplit ($string, $size=1) { // used in $this->phone
    $length = strlen($string);
    $size = min($size, $length);
    $mod = $length % $size;
    if (!$mod) return str_split($string, $size); // even split
    return array_merge(array(substr($string, 0, $mod)), str_split(substr($string, $mod), $size));
  }

  private function zip ($code) {
    if (empty($code)) return '';
    if ($this->validate($code, '/[ABCEGHJKLMNPRSTVXY][0-9][A-Z] [0-9][A-Z][0-9]/')) {
      return $code; // Postal code (Canada)
    } elseif ($this->validate($code, '/[A-Z]{1,2}[0-9][A-Z0-9]? [0-9][ABD-HJLNP-UW-Z]{2}/')) {
      return $code; // Postal code (UK)
    } else {
      $code = preg_replace('/[^0-9]/', '', $code);
      $zip = substr($code, 0, 5);
      if (strlen($code) > 5) $zip .= '-' . str_pad(substr($code, 5), 4, 0, STR_PAD_LEFT);
      if ($this->validate($zip, '/[0-9]{5}(?:-[0-9]{4})?/')) return $zip; // Zip code (US)
    }
    return '';
  }

  private function social_security ($number) {
    $number = preg_replace('/[^0-9]/', '', $number);
    if (strlen($number) == 9) return substr($number, 0, 3) . '-' . substr($number, 3, 2) . '-' . substr($number, 5);
    return '';
  }

  private function credit_card ($number) {
    if (empty($number)) return '';
    $card = array('number'=>preg_replace('/[^0-9]/', '', $number), 'type'=>'');
    if ($this->validate($card['number'], '^4[0-9]{12}(?:[0-9]{3})?$')) {
      $card['type'] = 'Visa';
    } elseif ($this->validate($card['number'], '^5[1-6][0-9]{14}$')) {
      $card['type'] = 'MasterCard';
    } elseif ($this->validate($card['number'], '^3[47][0-9]{13}$')) {
      $card['type'] = 'American Express';
    } elseif ($this->validate($card['number'], '^6011[0-9]{12}$')) {
      $card['type'] = 'Discover';
    } elseif ($this->validate($card['number'], '^3(?:0[0-5]|[68][0-9])[0-9]{11}$')) {
      $card['type'] = 'Diners Club';
    }
    if (empty($card['type'])) return ''; // else begin Mod 10 Algorithm
    $digits = str_split($card['number']);
    $digits = array_reverse($digits);
    foreach (range(1, count($digits) - 1, 2) as $x) {
      $digits[$x] *= 2;
      if ($digits[$x] > 9) $digits[$x] = ($digits[$x] - 10) + 1;
    }
    $checksum = array_sum($digits);
    return (($checksum % 10) == 0) ? $card['type'] . ' - ' . $card['number'] : '';
  }
  
  private function yes_no ($char) {
    if (in_array(strtolower($char), array('y', 'yes', 'true'))) return 'Y';
    return 'N';
  }

  private function strip ($data, $tags='') { // no extraneous spaces or code (except for $tags)
    return preg_replace('/\s(?=\s)/', '', strip_tags(str_replace(array("\r\n", "\r", "\n"), '<br />', $data), $tags));
  }

  public function safe ($data) {
    return str_replace('<br />', "\n", str_replace(array("\r\n", "\r", "\n") , '<br />', (htmlspecialchars($data))));
  }

  private function validate ($string, $regex) {
    if (preg_match($regex, $string)) return true;
    return false;
  }

  private function error ($filter, $check) {
    list($filter, $param) = $this->filter_param ($filter);
    switch ($filter) {
      case "a": $msg = "Please enter only letters."; break;
      case "an": $msg = "Please enter only letters and numbers."; break;
      case "ap": $msg = "Please enter only letters and punctuation."; break;
      case "anp": $msg = "Please enter only letters, numbers and punctuation."; break;
      case "num": $msg = "Please enter a valid number."; break;
      case "nummin": $msg = "Please enter a value greater than or equal to {$param[0]}."; break;
      case "nummax": $msg = "Please enter a value less than or equal to {$param[0]}."; break;
      case "numrange": $msg = "Please enter a value between {$param[0]} and {$param[1]}."; break;
      case "int": $msg = "Please enter a positive, whole number."; break;
      case "intmin": $msg = "Please enter a value greater than or equal to {$param[0]}."; break;
      case "intmax": $msg = "Please enter a value less than or equal to {$param[0]}."; break;
      case "intrange": $msg = "Please enter a value between {$param[0]} and {$param[1]}."; break;
      case "float": $msg = "Please enter a positive number."; break;
      case "floatmin": $msg = "Please enter a value greater than or equal to {$param[0]}."; break;
      case "floatmax": $msg = "Please enter a value less than or equal to {$param[0]}."; break;
      case "floatrange": $msg = "Please enter a value between {$param[0]} and {$param[1]}."; break;
      case "stringmin": $msg = "Please enter at least {$param[0]} characters."; break;
      case "stringmax": $msg = "Please enter no more than {$param[0]} characters."; break;
      case "stringrange": $msg = "Please enter a value between {$param[0]} and {$param[1]} characters long."; break;
      case "wordmin": $msg = "Please enter at least {$param[0]} words."; break;
      case "wordmax": $msg = "Please enter {$param[0]} words or less."; break;
      case "wordrange": $msg = "Please enter between {$param[0]} and {$param[1]} words."; break;
      case "date": $msg = "Please enter a valid (mm/dd/YYYY) date."; break;
      case "email": $msg = "Please enter a valid email address."; break;
      case "pass": $msg = "Please enter at least 6 characters with no spaces."; break;
      case "url": $msg = "Please enter a valid URL. Include http://"; break;
      case "phone": $msg = "Please enter a valid phone number."; break;
      case "zip": $msg = "Please enter a valid zip code."; break;
      case "ssn": $msg = "Please enter a valid social security number."; break;
      case "cc": $msg = "Please enter a valid credit card number."; break;
      case is_array($filter): $msg = "Please enter a valid value."; break;
      case isset($check[$filter]): $msg = "Please enter the same value again."; break;
      default: $msg = "This field is required."; break;
    }
    return $msg;
  }
  
  private function filter_param ($filter) {
    $param = (!empty($filter) && is_array($filter) && in_array($filter[0], array('nummin', 'nummax', 'numrange', 'intmin', 'intmax', 'intrange', 'floatmin', 'floatmax', 'floatrange', 'stringmin', 'stringmax', 'stringrange', 'wordmin', 'wordmax', 'wordrange', 'date'))) ? $filter : array();
    if (!empty($param)) $filter = trim(array_shift($param));
    return array($filter, $param);
  }

}

?>