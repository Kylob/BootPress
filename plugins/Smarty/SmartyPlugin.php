<?php

class SmartyPlugin extends Smarty {

  public function __construct ($uri) {
    parent::__construct();
    $this->setTemplateDir($uri . 'templates/');
    $this->setCompileDir($uri . 'templates_c/');
    $this->setConfigDir($uri . 'configs/');
    $this->setCacheDir($uri . 'cache/');
    $this->error_reporting = false;
    $this->enableSecurity();
  }
  
}

?>