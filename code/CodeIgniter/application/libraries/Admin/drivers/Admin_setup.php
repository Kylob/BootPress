<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_setup extends CI_Driver {

  public function view () {
    global $page;
    $page->plugin('jQuery', 'code', '
      $("#toolbar button.return").removeClass("return").addClass("refresh").click(function(){ window.location = location.href; });
    ');
    return $this->display('<p style="margin-top:10px;">Click on Setup and enter your website\'s name in the <code>$config[\'blog\']</code> array to begin.</p>');
  }
  
}

/* End of file Admin_setup.php */
/* Location: ./application/libraries/Admin/drivers/Admin_setup.php */