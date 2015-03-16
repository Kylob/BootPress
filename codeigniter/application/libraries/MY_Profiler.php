<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class My_Profiler extends CI_Profiler {

	// Overridden to show config hostname if database is not available
	protected function _compile_queries()
	{
		$dbs = array();

		// Let's determine which databases are currently connected to
		foreach (get_object_vars($this->CI) as $CI_object)
		{
			if (is_object($CI_object) && is_subclass_of(get_class($CI_object), 'CI_DB') )
			{
				$dbs[] = $CI_object;
			}
		}

		if (count($dbs) == 0)
		{
			$output  = "\n\n";
			$output .= '<fieldset id="ci_profiler_queries" style="border:1px solid #0000FF;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee">';
			$output .= "\n";
			$output .= '<legend style="color:#0000FF;">&nbsp;&nbsp;'.$this->CI->lang->line('profiler_queries').'&nbsp;&nbsp;</legend>';
			$output .= "\n";
			$output .= "\n\n<table style='border:none; width:100%;'>\n";
			$output .="<tr><td style='width:100%;color:#0000FF;font-weight:normal;background-color:#eee;padding:5px'>".$this->CI->lang->line('profiler_no_db')."</td></tr>\n";
			$output .= "</table>\n";
			$output .= "</fieldset>";

			return $output;
		}

		// Load the text helper so we can highlight the SQL
		$this->CI->load->helper('text');

		// Key words we want bolded
		$highlight = array('SELECT', 'DISTINCT', 'FROM', 'WHERE', 'AND', 'LEFT&nbsp;JOIN', 'ORDER&nbsp;BY', 'GROUP&nbsp;BY', 'LIMIT', 'INSERT', 'INTO', 'VALUES', 'UPDATE', 'OR&nbsp;', 'HAVING', 'OFFSET', 'NOT&nbsp;IN', 'IN', 'LIKE', 'NOT&nbsp;LIKE', 'COUNT', 'MAX', 'MIN', 'ON', 'AS', 'AVG', 'SUM', '(', ')');

		$output  = "\n\n";

		$count = 0;

		foreach ($dbs as $db)
		{
			$count++;

			$hide_queries = (count($db->queries) > $this->_query_toggle_count) ? ' display:none' : '';

			$show_hide_js = '(<span style="cursor: pointer;" onclick="var s=document.getElementById(\'ci_profiler_queries_db_'.$count.'\').style;s.display=s.display==\'none\'?\'\':\'none\';this.innerHTML=this.innerHTML==\''.$this->CI->lang->line('profiler_section_hide').'\'?\''.$this->CI->lang->line('profiler_section_show').'\':\''.$this->CI->lang->line('profiler_section_hide').'\';">'.$this->CI->lang->line('profiler_section_hide').'</span>)';

			if ($hide_queries != '')
			{
				$show_hide_js = '(<span style="cursor: pointer;" onclick="var s=document.getElementById(\'ci_profiler_queries_db_'.$count.'\').style;s.display=s.display==\'none\'?\'\':\'none\';this.innerHTML=this.innerHTML==\''.$this->CI->lang->line('profiler_section_show').'\'?\''.$this->CI->lang->line('profiler_section_hide').'\':\''.$this->CI->lang->line('profiler_section_show').'\';">'.$this->CI->lang->line('profiler_section_show').'</span>)';
			}

			$output .= '<fieldset style="border:1px solid #0000FF;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee">';
			$output .= "\n";
			
			// These two lines were added, and the next altered to conform
			$database = (empty($db->database)) ? substr($db->hostname, 7) : $db->database; // a pdo sqlite database
			if (strpos($database, BASE) !== false) $database = str_replace(array(BASE_URI, BASE), array('BASE_URI . ', 'BASE . '), $database);
			
			$output .= '<legend style="color:#0000FF;">&nbsp;&nbsp;'.$this->CI->lang->line('profiler_database').':&nbsp; '.$database.'&nbsp;&nbsp;&nbsp;'.$this->CI->lang->line('profiler_queries').': '.count($db->queries).'&nbsp;&nbsp;'.$show_hide_js.'</legend>';
			$output .= "\n";
			$output .= "\n\n<table style='width:100%;{$hide_queries}' id='ci_profiler_queries_db_{$count}'>\n";

			if (count($db->queries) == 0)
			{
				$output .= "<tr><td style='width:100%;color:#0000FF;font-weight:normal;background-color:#eee;padding:5px;'>".$this->CI->lang->line('profiler_no_queries')."</td></tr>\n";
			}
			else
			{
				foreach ($db->queries as $key => $val)
				{
					$time = number_format($db->query_times[$key], 4);

					$val = highlight_code($val, ENT_QUOTES);

					foreach ($highlight as $bold)
					{
						$val = str_replace($bold, '<strong>'.$bold.'</strong>', $val);
					}

					$output .= "<tr><td style='padding:5px; vertical-align: top;width:1%;color:#900;font-weight:normal;background-color:#ddd;'>".$time."&nbsp;&nbsp;</td><td style='padding:5px; color:#000;font-weight:normal;background-color:#ddd;'>".$val."</td></tr>\n";
				}
			}

			$output .= "</table>\n";
			$output .= "</fieldset>";

		}

		return $output;
	}

}

/* End of file MY_Profiler.php */
/* Location: ./application/libraries/MY_Profiler.php */