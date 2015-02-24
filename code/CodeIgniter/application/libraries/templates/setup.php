<?php

$config['blog'] = array(
  'name' => '', // This is the one and only parameter that we absolutely require
  'summary' => ''
); // Anything else you want to add here will be available in the Smarty template's {$blog} array

$config['bootstrap'] = '3.3.1'; // The version you would like to include

$config['pagination'] = 20; // The number of listings to show per page

$config['page_plugins'] = array( // Page plugins that you would like to be accessible within Smarty templates
  'CDN',
  'jQuery'
);

?>