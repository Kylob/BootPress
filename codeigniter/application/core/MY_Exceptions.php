<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class My_Exceptions extends CI_Exceptions {

	public function log_exception($severity, $message, $filepath, $line)
	{
		if (!(error_reporting() & $severity)) return; // This error code is not included in error_reporting
		
		$severity = ( ! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];

		log_message('error', 'Severity: '.$severity.'  --> '.$message. ' '.$filepath.' '.$line . $this->backtraces(), TRUE);
	}
	
	public function show_404($page = '', $log_error = TRUE)
	{
		$CI =& get_instance();
		$CI->sitemap->modify('uri', $CI->uri->uri_string(), 'delete');
		
		if (is_cli())
		{
			$heading = 'Not Found';
			$message = 'The controller/method pair you requested was not found.';
		}
		else
		{
			$heading = '404 Page Not Found';
			$message = 'The page you requested was not found.';
		}

		// By default we log this, but allow a dev to skip it
		if ($log_error)
		{
			log_message('error', $heading.' --> '.$page.' --> '.$CI->input->ip_address());
		}

		echo $this->show_error($heading, $message, 'error_404', 404);
		exit(4); // EXIT_UNKNOWN_FILE
	}

	private function backtraces()
	{
		$errors = array();
		$debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		foreach (array_slice($debug, 2, -3) as $error) { // from original error to controller
			if (isset($error['file']) && isset($error['line'])) {
				$function = (isset($error['function'])) ? $error['function'] : '';
				if (isset($error['class'])) $function = $error['class'] . '::' . $function;
				$errors[] = $function . ' ' . $error['file'] . ' ' . $error['line'];
			}
		}
		return (!empty($errors)) ? '  --> ' . implode('  --> ', $errors) : '';
	}
	
}

/* End of file MY_Exceptions.php */
/* Location: ./application/core/MY_Exceptions.php */