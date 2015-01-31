<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Session_cookie extends CI_Session_cookie {

	private function auth ($update)
	{
		$update['user_data'] = '';
		
		// Get the custom userdata, leaving out the defaults
		// (which get stored in the cookie)
		$userdata = array_diff_key($this->userdata, $this->defaults);

		// Did we find any custom data?
		if ( ! empty($userdata))
		{
			if (isset($userdata['user_id'])) // if not then we want to leave the user_id field as it was
			{
				$update['user_id'] = (int) $userdata['user_id'];
					
				if (isset($userdata['adjourn']) && $userdata['adjourn'] < $this->now)
				{
					$userdata = array();
				}
				elseif (isset($userdata['relapse']) && is_numeric($userdata['relapse']))
				{
					$userdata['adjourn'] = (int) $this->now + $userdata['relapse'];
				}
			}
				
			// Serialize the custom data array so we can store it
			if ( ! empty($userdata)) $update['user_data'] = serialize($userdata);
		}
		
		return $update;
	}

	// Overridden to utilize $this->auth()
	protected function _sess_update($force = FALSE)
	{
		// We only update the session every five minutes by default (unless forced)
		if ( ! $force && ($this->userdata['last_activity'] + $this->sess_time_to_update) >= $this->now)
		{
			return;
		}

		// Update last activity to now
		$this->userdata['last_activity'] = $this->now;

		// Save the old session id so we know which DB record to update
		$old_sessid = $this->userdata['session_id'];

		// Changing the session ID during an AJAX call causes problems
		if ( ! $this->CI->input->is_ajax_request())
		{
			// Get new id
			$this->userdata['session_id'] = $this->_make_sess_id();

			log_message('debug', 'Session: Regenerate ID');
		}

		// Check for database
		if ($this->sess_use_database === TRUE)
		{
			$this->CI->db->where('session_id', $old_sessid);

			if ($this->sess_match_ip === TRUE)
			{
				$this->CI->db->where('ip_address', $this->CI->input->ip_address());
			}

			if ($this->sess_match_useragent === TRUE)
			{
				$this->CI->db->where('user_agent', trim(substr($this->CI->input->user_agent(), 0, 120)));
			}

			// Update the session ID and last_activity field in the DB
			$this->CI->db->update($this->sess_table_name, $this->auth(array(
				'last_activity' => $this->now,
				'session_id' => $this->userdata['session_id']
			)));
		}

		// Write the cookie
		$this->_set_cookie();
	}

	// Overridden to utilize $this->auth()
	public function _update_db()
	{
		// Check for database and dirty flag and unsaved
		if ($this->sess_use_database === TRUE && $this->data_dirty === TRUE)
		{
			// Reset query builder values.
			$this->CI->db->reset_query();

			// Run the update query
			// Any time we change the session id, it gets updated immediately,
			// so our where clause below is always safe
			$this->CI->db->where('session_id', $this->userdata['session_id']);

			if ($this->sess_match_ip === TRUE)
			{
				$this->CI->db->where('ip_address', $this->CI->input->ip_address());
			}

			if ($this->sess_match_useragent === TRUE)
			{
				$this->CI->db->where('user_agent', trim(substr($this->CI->input->user_agent(), 0, 120)));
			}

			$this->CI->db->update($this->sess_table_name, $this->auth(array(
				'last_activity' => $this->userdata['last_activity']
			)));
			
			// Clear dirty flag to prevent double updates
			$this->data_dirty = FALSE;

			log_message('debug', 'CI_Session Data Saved To DB');
		}
	}


}

/* End of file MY_Session_cookie.php */
/* Location: ./application/libraries/Session/drivers/MY_Session_cookie.php */