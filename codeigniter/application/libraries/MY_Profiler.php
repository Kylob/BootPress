<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Profiler extends CI_Profiler {

	public function __construct($config = array())
	{
		unset($config['benchmarks'], $config['memory_usage']); // These two must always be displayed
		$this->_available_sections[] = 'console';
		$this->_available_sections[] = 'smarty';
		$this->_available_sections[] = 'files';
		$this->_profiler_view = 'profiler_custom';
		parent::__construct($config);
	}

	protected function _compile_benchmarks()
	{
		$output = parent::_compile_benchmarks();
		foreach ($output as $benchmark => $time) {
			$output[$benchmark] = $this->milliseconds($time);
		}
		return $output;
	}
	
	protected function _compile_queries()
	{
		$output = array();
		$input = parent::_compile_queries();
		if (!empty($input)) {
			$this->CI->load->helper('text');
			$highlight = array('SELECT', 'DISTINCT', 'FROM', 'WHERE', 'AND', 'LEFT&nbsp;JOIN', 'ORDER&nbsp;BY', 'GROUP&nbsp;BY', 'LIMIT', 'INSERT', 'INTO', 'VALUES', 'UPDATE', 'OR&nbsp;', 'HAVING', 'OFFSET', 'NOT&nbsp;IN', 'IN', 'LIKE', 'NOT&nbsp;LIKE', 'COUNT', 'MAX', 'MIN', 'ON', 'AS', 'AVG', 'SUM', '(', ')');
			foreach ($input as $database => $info) {
				$database = $this->base_uri($database);
				$output[$database] = array(
					'name' => array_shift($info),
					'total' => $this->milliseconds(array_shift($info))
				);
				foreach ($info as $db) {
					$db['query'] = highlight_code($db['query']);
					foreach ($highlight as $bold) $db['query'] = str_replace($bold, '<strong>'.$bold.'</strong>', $db['query']);
					$output[$database][] = array(
						'query' => $db['query'],
						'time' => $this->milliseconds($db['time'])
					);
				}
			}
		}
		return $output;
	}
	
	protected function _compile_memory_usage()
	{
		return $this->get_file_size(parent::_compile_memory_usage());
	}
	
	protected function _compile_console()
	{
		$output = $this->CI->logs;
		foreach ($output as $key => $log) {
			$output[$key]['memory'] = $this->get_file_size($log['memory']);
			$output[$key]['file'] = $this->base_uri($log['file']);
			$output[$key]['time'] = $this->milliseconds($log['time']);
			$output[$key]['data'] = '<pre>'.htmlspecialchars(stripslashes(print_r($log['data'], TRUE))).'</pre>';
		}
		return $output;
	}
	
	protected function _compile_smarty()
	{
		global $page;
		$smarty = (is_object($page)) ? $page->info('Smarty') : array();
		foreach ($smarty as $key => $value) {
			$smarty[$key]['memory'] = $this->get_file_size($value['memory']);
			$smarty[$key]['file'] = $this->base_uri($value['file']);
			$smarty[$key]['start'] = $this->milliseconds($value['start']);
			$smarty[$key]['time'] = $this->milliseconds($value['time']);
		}
		return $smarty;
	}
	
	protected function _compile_files()
	{
		$files = get_included_files();
		foreach ($files as $key => $file) $files[$key] = $this->base_uri($file);
		return $files;
	}
	
	private function milliseconds($time)
	{
		return round($time * 1000) . ' ms';
	}
	
	private function base_uri($file)
	{
		$file = str_replace(array('\\', BASE_URI, BASE), array('/', 'BASE_URI . ', 'BASE . '), $file);
		return (substr($file, 0, 4) != 'BASE') ? basename($file) : $file;
	}
	
	private function get_file_size($size, $retstring = null)
	{
		if (is_null($retstring)) $retstring = '%01.2f %s';
		$sizes = array('bytes', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		$lastsizestring = end($sizes);
		foreach ($sizes as $sizestring) {
			if ($size < 1024) break;
			if ($sizestring != $lastsizestring) $size /= 1024;
		}
		// Bytes aren't normally fractional.
		if ($sizestring == $sizes[0]) $retstring = '%01d %s';
		return sprintf($retstring, $size, $sizestring);
	}

}

/* End of file MY_Profiler.php */
/* Location: ./application/libraries/MY_Profiler.php */