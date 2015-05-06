<?php

// Enter the following information and rename this file to just params.php

$admin = array(
  'name'     => '', // however you like to see it written
  'email'    => '', // what you will use to sign in
  'password' => '', // for all of your websites
  'folder'   => 'admin' // the directory in which you would like to administer your site
);

$config['compress_output'] = false; // if you are getting compression errors (a blank page) then set this to false

define ('IMAGEMAGICK_PATH', ''); // (optional) to the command line

define ('PHP_PATH', ''); // (optional) used to sanitary (lint) check PHP files before they are saved

// -----------------------------------------------------------------------------
// END OF USER CONFIGURABLE SETTINGS.  DO NOT EDIT BELOW THIS LINE
// -----------------------------------------------------------------------------

/*
 * -----------------------------------------------------------------------------
 *  Make these (soon to be) classes globally available
 * -----------------------------------------------------------------------------
 */
 
 	$bp = $ci = $page = null;

/*
 * -----------------------------------------------------------------------------
 *  Define the paths to the BootPress (BASE) and $website (BASE_URI) folders
 * -----------------------------------------------------------------------------
 */
 
 	$website = (isset($website)) ? preg_replace('/[^-.a-z0-9]/', '', strtolower($website)) : '';
 	if (empty($website)) exit('Please include a $website in your index.php page.');
	define('BASE', str_replace('\\', '/', dirname(__FILE__)) . '/');
	define('BASE_URI', BASE . 'websites/' . $website . '/');
	date_default_timezone_set('GMT');
	
/*
 * -----------------------------------------------------------------------------
 * Load CodeIgniter
 * -----------------------------------------------------------------------------
 *
 * Along with the $config keys that we want to be set in stone ...
 *
 */
 
 	$config['index_page'] = '';
 	$config['allow_get_array'] = TRUE;
	$config['enable_query_strings'] = FALSE;
	$config['time_reference'] = 'GMT';
	$config['sess_cookie_name'] = 'codeigniter';
	$config['cache_path'] = BASE . 'codeigniter/application/cache/' . $website . '/';

	require_once(BASE . 'codeigniter/index.php');
	
/* End of file params.php */
/* Location: ./params.php */
