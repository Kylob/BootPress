<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_setup extends CI_Driver {

  public function view () {
    global $bp, $page;
    $page->plugin('jQuery', 'code', '
      $("#toolbar button.return").removeClass("return").addClass("refresh").click(function(){ window.location = location.href; });
    ');
    return $this->display($this->box('default', array(
      'head with-border' => $bp->icon('cog', 'fa', 'i style="margin-right:10px;"') . ' Setup',
      'body' => '<p>Click on Setup (to your left) and enter the <code>blog[name]</code> of your website to begin.</p>'
    )));
  }
  
}

/* End of file Admin_setup.php */
/* Location: ./application/libraries/Admin/drivers/Admin_setup.php */