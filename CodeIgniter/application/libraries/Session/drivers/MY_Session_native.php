<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Session_native extends CI_Session_native {

	protected function initialize()
	{
		$this->_parent->params['sess_expiration'] = 7200;
		parent::initialize();
		unset($this->_parent->params['sess_expiration']);
	}

}

/* End of file MY_Session_native.php */
/* Location: ./application/libraries/Session/drivers/MY_Session_native.php */