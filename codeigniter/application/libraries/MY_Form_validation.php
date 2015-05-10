<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Form_validation extends CI_Form_validation {

	public function min_length ($str, $val) // This method is overridden to merge with the jQuery Validation method
	{
		return $this->minlength($str, $val);
	}
	
	public function max_length ($str, $val) // This method is overridden to merge with the jQuery Validation method
	{
		return $this->maxlength($str, $val);
	}
	
	public function alpha ($str) // This method is overridden to allow whitespace
	{
		return (bool) preg_match('/^([a-z\s])+$/i', $str);
	}

	public function alpha_numeric ($str) // This method is overridden to allow whitespace
	{
		return (bool) preg_match('/^([a-z0-9\s])+$/i', $str);
	}

	public function numeric ($str) // This method is overridden to remove the assumed "+" sign
	{
		return (bool) preg_match('/^-?[0-9]*\.?[0-9]+$/', $str);
	}

	public function integer ($str) // This method is overridden to remove the assumed "+" sign
	{
		return (bool) preg_match('/^-?[0-9]+$/', $str);
	}
	
	public function decimal ($str) // This method is overridden to remove the assumed "+" sign
	{
		return (bool) preg_match('/^-?[0-9]+\.[0-9]+$/', $str);
	}

	public function gte ($num, $min)
	{
		if (!is_numeric($num)) return false;
		return $num >= $min;
	}
	
	public function lte ($num, $max)
	{
		if (!is_numeric($num)) return false;
		return $num <= $max;
	}
	
	public function email ($str)
	{
		return $this->valid_email($str);
	}
	
	public function url ($str)
	{
		$scheme = "(https?|ftp)\:\/\/";
		$user = "([A-Za-z0-9+!*(),;?&=\$_.-]+(\:[A-Za-z0-9+!*(),;?&=\$_.-]+)?@)?"; // user and pass if ftp
		$host = "([A-Za-z0-9-.]*)\.([A-Za-z]{2,4})";
		$port = "(\:[0-9]{2,5})?"; // again, if ftp
		$path = "(\/([A-Za-z0-9+\$_-]\.?)+)*\/?";
		$get = "(\?[A-Za-z+&\$_.-][A-Za-z0-9;:@&%=+\/\$_.-]*)?";
		$anchor = "(#[A-Za-z_.-][A-Za-z0-9+\$_.-]*)?";
		return (bool) preg_match('/' . $scheme . $user . $host . $port . $path . $get . $anchor . '/', $str);
	}
	
	public function ip ($ip, $which='')
	{
		return $this->valid_ip($ip, $which);
	}
	
	public function base64 ($str)
	{
		return $this->valid_base64($str);
	}
	
	public function regex ($str, $regex)
	{
		return $this->regex_match($str, $regex);
	}
	
	public function creditcard ($str)
	{
		if (preg_match("/[^0-9 \-]+/", $str)) return FALSE; // accept only spaces, digits and dashes
		$digits = str_split(preg_replace('/[^0-9]/', '', $str));
		$digits = array_reverse($digits);
		foreach (range(1, count($digits) - 1, 2) as $x) {
			$digits[$x] *= 2;
			if ($digits[$x] > 9) $digits[$x] = ($digits[$x] - 10) + 1;
		}
		$checksum = array_sum($digits);
		return ($checksum % 10) === 0;
	}
	
	public function date ($str)
	{
		return (strtotime($str)) ? TRUE : FALSE;
	}
	
	public function sqldate ($str)
	{
		return (!empty($str)) ? date('Y-m-d', strtotime($str)) : '';
	}
	
	public function alphanumeric ($str)
	{
		return $this->alpha_numeric($str);
	}

	public function minlength ($str, $min)
	{
		$length = (function_exists('mb_strlen')) ? mb_strlen($str) : strlen($str);
		return $length >= $min;
	}
	
	public function maxlength ($str, $max)
	{
		$length = (function_exists('mb_strlen')) ? mb_strlen($str) : strlen($str);
		return $length <= $max;
	}
	
	public function nowhitespace ($str)
	{
		return ( ! preg_match("/^\S+$/i", $str)) ? FALSE : TRUE;
	}
	
	public function inarray ($needle, $haystack)
	{
		return strpos(','.$haystack.',', ','.$needle.',') !== FALSE;
	}
	
	public function single_space ($str)
	{
		return preg_replace('/\s(?=\s)/', '', $str);
	}
	
}

/* End of file MY_Form_validation.php */
/* Location: ./application/libraries/MY_Form_validation.php */