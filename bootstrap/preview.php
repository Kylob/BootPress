<?php

$page->plugin('CDN', array('prepend', 'links'=>array(
  'bootstrap/' . $ci->blog->bootstrap . '/css/bootstrap.min.css',
  'bootstrap/' . $ci->blog->bootstrap . '/js/bootstrap.min.js',
)));

$html = '';

$html .= str_replace('container-fluid', 'container', $bp->navbar->open('Bootstrap Variables Preview', 'top'));
$html .= $bp->navbar->button('primary pull-right', $bp->icon('refresh') . ' Refresh', array('id'=>'refresh', 'style'=>'display:none;'));
$html .= $bp->navbar->close();
$page->plugin('jQuery', 'code', '
  less.pageLoadFinished.then(function(){ $("#refresh").show(); });
  $("#refresh").click(function(){
    var btn = $(this);
    if (!btn.is(":hidden")) {
      btn.hide();
      less.refresh(true).then(function(){btn.show();});
    }
  });
');

##
# Header
##

$header = '<h1>Sandbox</h1><p class="lead">A preview of changes in this swatch.</p>';
$header .= $bp->pills(array(
  'Viewport' => '#viewport',
  'Navigation' => '#navigation',
  'Buttons' => '#buttons',
  'Alerts' => '#alerts',
  'Typography' => '#typography',
  'Lists' => '#lists',
  'Tables' => '#tables',
  'Panels' => '#panels',
  'Wells' => '#wells',
  'Forms' => '#forms',
  'Icons' => '#icons'
));
$html .= '<header>' . $bp->row('sm', array(
  $bp->col(12, $header)
)) . '</header>';

##
# Viewport
##

$viewport = '<div class="page-header"><h2>Viewport</h2></div>';
$viewport .= $bp->row('xs', 'sm', array(
  $bp->col(6, 3, $bp->panel('default hidden-xs', array('head'=>'Extra Small (xs)', 'body'=>'Devices &lt;768px')) .
                 $bp->panel('primary visible-xs', array('head'=>'&#10004; Extra Small (xs)', 'body'=>'Devices &lt;768px'))),
  $bp->col(6, 3, $bp->panel('default hidden-sm', array('head'=>'Small (sm)', 'body'=>'Devices &ge;768px')) .
                 $bp->panel('primary visible-sm', array('head'=>'&#10004; Small (sm)', 'body'=>'Devices &ge;768px'))),
  $bp->col('clearfix visible-xs', '', ''),
  $bp->col(6, 3, $bp->panel('default hidden-md', array('head'=>'Medium (md)', 'body'=>'Devices &ge;992px')) .
                 $bp->panel('primary visible-md', array('head'=>'&#10004; Medium (md)', 'body'=>'Devices &ge;992px'))),
  $bp->col(6, 3, $bp->panel('default hidden-lg', array('head'=>'Large (lg)', 'body'=>'Devices &ge;1200px')) .
                 $bp->panel('primary visible-lg', array('head'=>'&#10004; Large (lg)', 'body'=>'Devices &ge;1200px'))),
));
$html .= '<section id="viewport">' . $viewport . '</section>';

##
# Navigation
##

$navigation = '<div class="page-header"><h2>Navigation</h2></div>';
$navigation .= $bp->navbar->open('Project Name');
$navigation .= $bp->navbar->menu(array(
  'Home' => '#',
  'Link' => '#',
  'Link' => '#',
  'Dropdown' => array(
    'Action' => '#',
    'One more separated link' => '#'
  )
), array('active'=>'Home'));
$navigation .= $bp->navbar->menu(array('Link'=>'#'), array('pull'=>'right'));
$navigation .= $bp->navbar->close();
$navigation .= $bp->navbar->open('Project Name', 'inverse');
$navigation .= $bp->navbar->menu(array(
  'Home' => '#',
  'Link' => '#',
  'Link' => '#',
  'Dropdown' => array(
    'Action' => '#',
    'One more separated link' => '#'
  )
), array('active'=>'Home'));
$navigation .= $bp->navbar->menu(array('Link'=>'#'), array('pull'=>'right'));
$navigation .= $bp->navbar->close();
$navigation .= $bp->row('sm', array(
  $bp->col(4, '<h3 id="breadcrumbs">Breadcrumbs</h3>' . 
              $bp->breadcrumbs(array('Home')) . 
              $bp->breadcrumbs(array(
                'Home' => '#',
                'Library' => '#',
                'Data' => '#'
              ))),
  $bp->col(4, '<h3 id="pagination">Pagination</h3>
              <ul class="pagination pagination-sm">
                <li><a href="#">&laquo;</a></li>
                <li class="active"><a href="#">1</a></li>
                <li><a href="#">2</a></li>
                <li><a href="#">3</a></li>
                <li class="disabled"><a href="#">...</a></li>
                <li><a href="#">8</a></li>
                <li><a href="#">9</a></li>
                <li><a href="#">&raquo;</a></li>
              </ul>
              <div style="text-align:center;">
                <ul class="pagination">
                  <li><a href="#">&larr;</a></li>
                  <li class="active"><a href="#">10</a></li>
                  <li class="disabled"><a href="#">...</a></li>
                  <li><a href="#">20</a></li>
                  <li><a href="#">&rarr;</a></li>
                </ul>
              </div>'),
  $bp->col(4, '<h3 id="pager">Pagers</h3>
              <ul class="pager">
                <li><a href="#">Previous</a></li>
                <li><a href="#">Next</a></li>
              </ul>
              <ul class="pager">
                <li class="previous disabled"><a href="#">&larr; Older</a></li>
                <li class="next"><a href="#">Newer &rarr;</a></li>
              </ul>')
));
$navigation .= $bp->row('sm', array(
  $bp->col(6, '<h3 id="tabs">Tabs</h3>' . 
              $bp->toggle('tabs', array(
                'Home' => 'Raw denim you probably haven\'t heard of them jean shorts Austin. Nesciunt tofu stumptown aliqua, retro synth master cleanse. Mustache cliche tempor, williamsburg carles vegan helvetica. Reprehenderit butcher retro keffiyeh dreamcatcher synth. Cosby sweater eu banh mi, qui irure terry richardson ex squid. Aliquip placeat salvia cillum iphone. Seitan aliquip quis cardigan american apparel, butcher voluptate nisi qui.',
                'Profile' => 'Food truck fixie locavore, accusamus mcsweeney\'s marfa nulla single-origin coffee squid. Exercitation +1 labore velit, blog sartorial PBR leggings next level wes anderson artisan four loko farm-to-table craft beer twee. Qui photo booth letterpress, commodo enim craft beer mlkshk aliquip jean shorts ullamco ad vinyl cillum PBR. Homo nostrud organic, assumenda labore aesthetic magna delectus mollit. Keytar helvetica VHS salvia yr, vero magna velit sapiente labore stumptown. Vegan fanny pack odio cillum wes anderson 8-bit, sustainable jean shorts beard ut DIY ethical culpa terry richardson biodiesel. Art party scenester stumptown, tumblr butcher vero sint qui sapiente accusamus tattooed echo park.',
                'Dropdown' => array(
                  'This' => 'Etsy mixtape wayfarers, ethical wes anderson tofu before they sold out mcsweeney\'s organic lomo retro fanny pack lo-fi farm-to-table readymade. Messenger bag gentrify pitchfork tattooed craft beer, iphone skateboard locavore carles etsy salvia banksy hoodie helvetica. DIY synth PBR banksy irony. Leggings gentrify squid 8-bit cred pitchfork. Williamsburg banh mi whatever gluten-free, carles pitchfork biodiesel fixie etsy retro mlkshk vice blog. Scenester cred you probably haven\'t heard of them, vinyl craft beer blog stumptown. Pitchfork sustainable tofu synth chambray yr.',
                  'That' => 'Trust fund seitan letterpress, keytar raw denim keffiyeh etsy art party before they sold out master cleanse gluten-free squid scenester freegan cosby sweater. Fanny pack portland seitan DIY, art party locavore wolf cliche high life echo park Austin. Cred vinyl keffiyeh DIY salvia PBR, banh mi before they sold out farm-to-table VHS viral locavore cosby sweater. Lomo wolf viral, mustache readymade thundercats keffiyeh craft beer marfa ethical. Wolf salvia freegan, sartorial keffiyeh echo park vegan.'
                )
              ), array('active'=>1, 'fade')) . '<br>' . 
              $bp->tabs(array(
                'Nav' => '#',
                'Tabs' => '#',
                'Justified' => '#'
              ), array('active'=>'Nav', 'align'=>'justified'))),
  $bp->col(6, '<h3 id="pills">Pills</h3>' . 
              $bp->pills(array(
                'Home ' . $bp->badge(42) => '#',
                'Profile ' . $bp->badge(0) => '#',
                'Messages ' . $bp->badge(3) => array(
                  'New! ' . $bp->badge(1) => '#',
                  'Read' => '#',
                  'Trashed' => '#',
                  '',
                  'Span ' . $bp->badge(2) => '#'
                ),
                'Disabled Link' => '#'
              ), array('active'=>1, 'disabled'=>'Disabled Link')) . '<br>' . 
              $bp->pills(array(
                'Home ' . $bp->badge(42, 'right') => '#',
                'Profile ' . $bp->badge(0, 'right') => '#',
                'Messages ' . $bp->badge(3, 'right') => '#'
              ), array('align'=>'stacked')) . '<br>' . 
              $bp->pills(array(
                'Nav' => '#',
                'Pills' => '#',
                'Justified' => '#'
              ), array('active'=>'Nav', 'align'=>'justified'))
  )
));
$navigation .= $bp->row('sm', array(
  $bp->col(12, '<h3 id="labels">Labels</h3>' . 
                $bp->lister('ul list-inline', array(
                  $bp->label('default', 'Default'),
                  $bp->label('primary', 'Primary'),
                  $bp->label('success', 'Success'),
                  $bp->label('info', 'Info'),
                  $bp->label('warning', 'Warning'),
                  $bp->label('danger', 'Danger')
                )))
)) . '<br>';
$html .= '<section id="navigation">' . $navigation . '</section>';

##
# Buttons
##

$buttons = '<div class="page-header"><h2>Buttons</h2></div>';
$buttons .= '<div>' . $bp->lister('ul list-inline', array(
  $bp->button('default', 'Default'),
  $bp->button('primary', 'Primary'),
  $bp->button('success', 'Success'),
  $bp->button('info', 'Info'),
  $bp->button('warning', 'Warning'),
  $bp->button('danger', 'Danger'),
  $bp->button('link', 'Link')
)) . '</div>';
$buttons .= '<div>' . $bp->lister('ul list-inline', array(
  $bp->button('xs primary', 'btn-xs'),
  $bp->button('sm primary', 'btn-sm'),
  $bp->button('primary', 'btn'),
  $bp->button('lg primary', 'btn-lg'),
  $bp->button('success disabled', 'Disabled Link', array('href'=>'#')),
  $bp->button('info disabled', 'Disabled Button'),
  $bp->button('warning', $bp->icon('map-marker') . ' Icon'),
  $bp->button('danger', array('split'=>'Dropdown'), array('dropdown'=>array(
    'Dropdown Header',
    'Action' => '#',
    'Another Action' => '#',
    'Something else here' => '#', 
    '', 
    'Separated link' => '#',
    'Disabled link' => '#'
  ), 'disabled'=>'Disabled link'))
)) . '</div>';
$buttons .= '<div class="btn-toolbar">' . $bp->group('', array(
  $bp->button('default', 'Left'),
  $bp->button('default', 'Middle'),
  $bp->button('default', 'Right')
)) . $bp->group('lg', array(
  $bp->button('default', 'btn'),
  $bp->button('default', 'group'),
  $bp->button('default', 'lg')
)) . $bp->group('sm', array(
  $bp->button('default', 'btn'),
  $bp->button('default', 'group'),
  $bp->button('default', 'sm')
)) . $bp->group('xs', array(
  $bp->button('default', 'btn'),
  $bp->button('default', 'group'),
  $bp->button('default', 'xs')
)) . '</div><br>';
$buttons .= $bp->group('justified', array(
  $bp->button('default', 'Default'),
  $bp->button('primary', 'Primary'),
  $bp->button('success', 'Success'),
  $bp->button('info', 'Info'),
  $bp->button('warning', 'Warning'),
  $bp->button('danger', 'Danger')
));
$html .= '<section id="buttons">' . $buttons . '</section>';

##
# Alerts
##

$alerts = '<div class="page-header"><h2>Alerts</h2></div>';
$alerts .= $bp->alert('warning', '<h4>Warning</h4><p>Best check yo self, you\'re not looking too good. Nulla vitae elit libero, a pharetra augue. Praesent commodo cursus magna.</p>');
$alerts .= $bp->row('sm', array(
  $bp->col(4, $bp->alert('danger', '<strong>Danger</strong> Change a few things up and try submitting again.', false)),
  $bp->col(4, $bp->alert('success', '<strong>Success</strong> You successfully read this important alert message.', false)),
  $bp->col(4, $bp->alert('info', '<strong>Info</strong> This alert needs your attention, but it\'s not super important.'))
));
$html .= '<section id="alerts">' . $alerts . '</section>';

##
# Typography
##

$typography = '<div class="page-header"><h2>Typography</h2></div>';
$typography .= $bp->row('sm', array(
  $bp->col(4, '<div class="well">
                <h1>Heading 1 <small>small</small></h1>
                <h2>Heading 2 <small>small</small></h2>
                <h3>Heading 3 <small>small</small></h3>
                <h4>Heading 4 <small>small</small></h4>
                <h5>Heading 5 <small>small</small></h5>
                <h6>Heading 6 <small>small</small></h6>
              </div>'),
  $bp->col(4, '<h3>Example body text</h3>
              <p>Nullam quis risus eget urna mollis ornare vel eu leo. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
              <p><small>SMALL <span class="text-muted">Muted vivamus sagittis lacus vel augue</span></small>, <big>BIG <span class="text-primary">Primary laoreet rutrum faucibus dolor auctor</span></big>. <strong>STRONG <span class="text-success">Success duis mollis est non commodo luctus</span></strong>, <b>B <span class="text-info">Info nisi erat porttitor ligula</span></b>. <em>EM <span class="text-warning">Warning eget lacinia odio sem nec elit</span></em>, <i>I <span class="text-danger">Danger donec sed odio dui</span></i>.</p>'),
  $bp->col(4, '<h3>Example addresses</h3>
              <address>
                <strong>Twitter, Inc.</strong><br>
                795 Folsom Ave, Suite 600<br>
                San Francisco, CA 94107<br>
                <abbr title="Phone">P:</abbr> (123) 456-7890
              </address>
              <address>
                <strong>Full Name</strong><br>
                <a href="mailto:#">first.last@gmail.com</a>
              </address>')
));
$typography .= $bp->row('sm', array(
  $bp->col(6, '<blockquote>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                <small>Someone famous in <cite title="Source Title">Source Title</cite></small>
              </blockquote>'),
  $bp->col(6, '<blockquote class="pull-right">
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                <small>Someone famous in <cite title="Source Title">Source Title</cite></small>
              </blockquote>')
));
$paragraphs = array(
  '<p class="text-left">Text Left Aligned</p>',
  '<p class="text-center">Text Center Aligned</p>',
  '<p class="text-right">Text Right Aligned</p>',
  '<p class="text-justify">Justified text.</p>',
  '<p class="text-nowrap">No wrap text.</p>',
  '<p class="text-lowercase">Lowercased text.</p>',
  '<p class="text-uppercase">Uppercased text.</p>',
  '<p class="text-capitalize">Capitalized text.</p>'
);
$typography .= $bp->row('sm', array(
  $bp->col(12, '<div class="col-sm-3 pull-right">' . $bp->button('danger block', '1. Pull Right') . '</div>' . 
               '<div class="col-sm-3 pull-left">' . $bp->button('warning block', '2. Pull Left') . '</div>' . 
               '<div style="width:25%;" class="center-block">' . $bp->button('success block', '3. Center Block') . '</div>' . 
               '<br>' . implode(' ', $paragraphs))
));
$typography .= '<pre class="pre-scrollable">' . htmlspecialchars(implode("\n", $paragraphs)) . '</pre>';
$html .= '<section id="typography">' . $typography . '</section>';

##
# Lists
##

$lists = '<div class="page-header"><h2>Lists</h2></div>';
$li = array(
  'Lorem ipsum dolor sit amet',
  'Consectetur adipiscing elit' => array(
    'Phasellus iaculis neque',
    'Purus sodales ultricies',
    'Vestibulum laoreet porttitor sem'
  ),
  'Faucibus porta lacus fringilla vel',
  'Aenean sit amet erat nunc'
);
$lists .= $bp->row('sm', array(
  $bp->col(4, '<h3>Ordered</h3>' . $bp->lister('ol', $li)),
  $bp->col(4, '<h3>Unordered</h3>' . $bp->lister('ul', $li)),
  $bp->col(4, '<h3>Unstyled</h3>' . $bp->lister('ul list-unstyled', $li))
));
$dl = array(
  'Malesuada porta' => array(
    'Vestibulum id ligula porta felis euismod semper eget lacinia odio sem nec elit.',
    'Donec id elit non mi porta gravida at eget metus.'
  ),
  'Felis euismod semper eget lacinia' => 'Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.'
);
$lists .= $bp->row('sm', array(
  $bp->col(4, '<h3>Description</h3>' . $bp->lister('dl', $dl)),
  $bp->col(8, '<h3>Inline</h3>' . $bp->lister('ul list-inline', array(
    'Lorem ipsum',
    'Phasellus iaculis',
    'Nulla volutpat'
  )) . '<h3>Horizontal</h3>' . $bp->lister('dl dl-horizontal', $dl))
));
$lists .= $bp->row('sm', array(
  $bp->col(4, $bp->list_group(array(
    'Unordered',
    'List',
    $bp->badge(1) . 'Group'
  ))),
  $bp->col(4, $bp->list_group(array(
    'Anchor' => '#',
    'List' => '#',
    'Group' . $bp->badge(2) => '#'
  ), 'Anchor')),
  $bp->col(4, $bp->list_group(array(
    '<h4>Custom Content</h4><p>Donec id elit non mi porta gravida.</p>' => '#',
    $bp->badge(3) . '<h4>Linked List Group</h4><p>Donec id elit non mi porta gravida.</p>' => '#'
  ), 1))
));
$html .= '<section id="lists">' . $lists . '</section>';

##
# Tables
##

$tables = '<div class="page-header"><h2>Tables</h2></div>';
$tables .= '<div class="table-responsive">';
$tables .= $bp->table->open('class=table bordered striped hover');
  $tables .= $bp->table->head();
  $tables .= $bp->table->cell('', '#');
  $tables .= $bp->table->cell('', 'First Name');
  $tables .= $bp->table->cell('', 'Last Name');
  $tables .= $bp->table->cell('', 'Username');
  $tables .= $bp->table->row();
  $tables .= $bp->table->cell('', '1');
  $tables .= $bp->table->cell('', 'Mark');
  $tables .= $bp->table->cell('', 'Otto');
  $tables .= $bp->table->cell('', '@mdo');
  $tables .= $bp->table->row();
  $tables .= $bp->table->cell('', '2');
  $tables .= $bp->table->cell('', 'Jacob');
  $tables .= $bp->table->cell('', 'Thornton');
  $tables .= $bp->table->cell('', '@fat');
  $tables .= $bp->table->row();
  $tables .= $bp->table->cell('', '3');
  $tables .= $bp->table->cell('', 'Larry');
  $tables .= $bp->table->cell('', 'the Bird');
  $tables .= $bp->table->cell('', '@twitter');
  $tables .= $bp->table->row();
  $tables .= $bp->table->cell('class=active', 'Active');
  $tables .= $bp->table->cell('class=success', 'Success');
  $tables .= $bp->table->cell('class=warning', 'Warning');
  $tables .= $bp->table->cell('class=danger', 'Danger');
  $tables .= $bp->table->close();
  $tables .= $bp->table->open('class=table bordered condensed');
  $tables .= $bp->table->head();
  $tables .= $bp->table->cell('', 'Condensed');
  $tables .= $bp->table->row('class=active');
  $tables .= $bp->table->cell('', 'Active');
  $tables .= $bp->table->row('class=success');
  $tables .= $bp->table->cell('', 'Success');
  $tables .= $bp->table->row('class=warning');
  $tables .= $bp->table->cell('', 'Warning');
  $tables .= $bp->table->row('class=danger');
  $tables .= $bp->table->cell('', 'Danger');
  $tables .= $bp->table->close();
$tables .= '</div>';
$html .= '<section id="tables">' . $tables . '</section>';

##
# Panels
##

$panels = '<div class="page-header"><h2>Panels</h2></div>';
$panels .= $bp->row('sm', array(
  $bp->col(2, $bp->panel('default', array('head'=>'Heading', 'body'=>'Default', 'foot'=>'Footer'))),
  $bp->col(2, $bp->panel('primary', array('head'=>'Heading', 'body'=>'Primary', 'foot'=>'Footer'))),
  $bp->col(2, $bp->panel('success', array('head'=>'Heading', 'body'=>'Success', 'foot'=>'Footer'))),
  $bp->col(2, $bp->panel('info', array('head'=>'Heading', 'body'=>'Info', 'foot'=>'Footer'))),
  $bp->col(2, $bp->panel('warning', array('head'=>'Heading', 'body'=>'Warning', 'foot'=>'Footer'))),
  $bp->col(2, $bp->panel('danger', array('head'=>'Heading', 'body'=>'Danger', 'foot'=>'Footer')))
));
$html .= '<section id="panels">' . $panels . '</section>';

##
# Wells
##

$wells = '<div class="page-header"><h2>Wells</h2></div>';
$wells .= $bp->row('sm', array(
  $bp->col(4, '<div class="well">Look, I\'m in a well!</div>'),
  $bp->col(4, '<div class="well well-lg">Look, I\'m in a large well!</div>'),
  $bp->col(4, '<div class="well well-sm">Look, I\'m in a small well!</div>')
));
$html .= '<section id="wells">' . $wells . '</section>';

##
# Forms
##

$forms = '<div class="page-header"><h2>Forms</h2></div>';
$forms .= $bp->row('sm', array(
  $bp->col(12, '
    <form class="form-inline well">
      <div class="form-group">
        <label class="col-sm-2"><h3>Inline&nbsp;Form</h3></label>
      </div>
      <div class="form-group">
        <input class="form-control col-sm-3" placeholder="Email" type="text">
      </div>
      <div class="form-group">
        <input class="form-control col-sm-3" placeholder="Password" type="password">
      </div>
      <button type="submit" class="btn btn-default">Sign in</button>
      <div class="checkbox">
        <label><input type="checkbox"> Remember me</label>
      </div>
    </form>
  ')
));
$forms .= $bp->row('sm', array(
  $bp->col(6, '
    <form class="form-horizontal well">
      <fieldset>
        <legend>Horizontal Form</legend>
        <div class="form-group">
          <label class="col-sm-2 control-label">Email</label>
          <div class="col-sm-10">
            <p class="form-control-static">email@example.com</p>
          </div>
        </div>
        <div class="form-group">
          <label class="col-sm-2 control-label" for="name">Name</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="name" placeholder="Name">
          </div>
        </div>
        <div class="form-group">
          <label class="col-sm-2 control-label" for="password">Password</label>
          <div class="col-sm-10">
            <input type="password" class="form-control" id="password" placeholder="Password">
          </div>
        </div>
        <div class="form-group">
          <div class="col-sm-offset-2 col-sm-10">
            <div class="checkbox">
              <label><input type="checkbox"> Remember me</label>
            </div>
          </div>
        </div>
        <hr />
        <div class="form-group">
          <div class="col-sm-offset-2 col-sm-10">
            <button type="submit" class="btn btn-primary">Sign in</button>
            <button type="reset" class="btn btn-default">Cancel</button>
            <button type="button" class="btn btn-link">Forgot Password</button>
          </div>
        </div>
      </fieldset>
    </form>
    
    <form class="form-horizontal well">
      <fieldset>
        <legend>Validation States</legend>
        <div class="form-group">
          <label class="col-sm-2 control-label">Email</label>
          <div class="col-sm-10">
            <p class="form-control-static">email@example.com</p>
          </div>
        </div>
        <div class="form-group">
          <label class="col-sm-2 control-label">Username</label>
          <div class="col-sm-10">
            <div class="input-group">
              <span class="input-group-addon">@</span>
              <input type="text" class="form-control" placeholder="Username">
            </div>
          </div>
        </div>
        <div class="form-group has-success">
          <p class="help-block col-sm-offset-2 col-sm-10">This field has success.  Good job.</p>
          <label class="col-sm-2 control-label" for="name">Name</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="name" placeholder="Name">
          </div>
        </div>
        <div class="form-group has-warning">
          <p class="help-block col-sm-offset-2 col-sm-10">This field has warning.  User beware.</p>
          <label class="col-sm-2 control-label" for="password">Password</label>
          <div class="col-sm-10">
            <input type="password" class="form-control" id="password" placeholder="Password">
          </div>
        </div>
        <div class="form-group has-error">
          <p class="help-block col-sm-offset-2 col-sm-10">This field has error.  You screwed up.</p>
          <div class="col-sm-offset-2 col-sm-10">
            <div class="checkbox">
              <label><input type="checkbox"> Remember me</label>
            </div>
          </div>
        </div>
        <hr />
        <div class="form-group">
          <div class="col-sm-offset-2 col-sm-10">
            <button type="submit" class="btn btn-primary">Submit</button>
          </div>
        </div>
      </fieldset>
    </form>
  '),
  $bp->col(6, '
    <form class="well">
      <fieldset>
        <legend>Form Controls</legend>
        <div class="form-group">
          <label class="control-label" for="textInput1">Inputs</label>
          <input class="form-control" id="textInput1" type="text">
          <p class="help-block">Example block-level help text here.</p>
        </div>
        <div class="form-group">
          <input class="form-control" type="text" placeholder="Disabled Input" disabled>
        </div>
        <div class="form-group">
          <input class="form-control input-lg" type="text" placeholder="Large Input">
        </div>
        <div class="form-group">
          <input class="form-control input-sm" type="text" placeholder="Small Input">
        </div>
        <div class="form-group">
          <div class="input-group">
            <span class="input-group-addon">$</span>
            <input type="text" class="form-control">
            <span class="input-group-addon">.00</span>
          </div>
        </div>
        <div class="form-group">
          <label class="control-label" for="textarea">Textarea</label>
          <textarea class="form-control" id="textarea" rows="3" placeholder="Default Input"></textarea>
        </div>
        <div class="form-group">
          <textarea class="form-control input-sm" id="textarea" rows="3" placeholder="Small Input"></textarea>
        </div>
        <div class="form-group">
          <div class="checkbox">
            <label><input type="checkbox" value="">Checkbox</label>
          </div>
        </div>
        <div class="form-group">
          <div class="checkbox">
            <label><input type="checkbox" value="" disabled checked>Can\'t Check This Out</label>
          </div>
        </div>
        <div class="form-group">
          <label class="checkbox-inline">
            <input type="checkbox" value=""> One
          </label>
          <label class="checkbox-inline">
            <input type="checkbox" value=""> Two
          </label>
          <label class="checkbox-inline">
            <input type="checkbox" value=""> Three
          </label>
        </div>
        <div class="form-group">
          <div class="radio">
            <label>
              <input type="radio" name="radios" id="radio5" value="five" checked> Radio
            </label>
          </div>
        </div>
        <div class="form-group">
          <div class="radio">
            <label>
              <input type="radio" name="radios" id="radio4" value="four"> Check
            </label>
          </div>
        </div>
        <div class="form-group">
          <label class="radio-inline">
            <input type="radio" name="radios" id="radio3" value="three"> Three
          </label>
          <label class="radio-inline">
            <input type="radio" name="radios" id="radio2" value="two"> Two
          </label>
          <label class="radio-inline">
            <input type="radio" name="radios" id="radio1" value="one"> One
          </label>
        </div>
        <div class="form-group">
          <label class="control-label" for="selectMenu">Select Menu</label>
          <div class="controls">
            <select class="form-control" id="selectMenu">
              <option>1</option>
              <option>2</option>
              <option>3</option>
              <option>4</option>
              <option>5</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <div class="controls">
            <select class="form-control" disabled>
              <option>1</option>
              <option>2</option>
              <option selected>Disabled Select Menu</option>
              <option>4</option>
              <option>5</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="control-label" for="selectMultiple">Multiple Select</label>
          <div class="controls">
            <select multiple class="form-control" id="selectMultiple">
              <option>1</option>
              <option>2</option>
              <option>3</option>
              <option>4</option>
              <option>5</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Button Block</button>
      </fieldset>
    </form>
  ')
));
$html .= '<section id="forms">' . $forms . '</section>';

##
# Progress Bars
##

$progress = '<h3>Progress bars</h3>';
$progress .= $bp->row('sm', array(
  $bp->col(4, $bp->progress(60, 'success')),
  $bp->col(4, $bp->progress(40, 'danger striped active')),
  $bp->col(4, $bp->progress(20, 'info striped', 'display'))
));
$progress .= $bp->row('sm', array(
  $bp->col(12, $bp->progress(array(25, 25, 25, 25), array('', 'warning', 'success', 'danger')))
));
$html .= '<section id="progressbars">' . $progress . '</section>';

##
# Icons
##

$icons = array('asterisk', 'plus', 'euro', 'eur', 'minus', 'cloud', 'envelope', 'pencil', 'glass', 'music', 'search', 'heart', 'star', 'star-empty', 'user', 'film', 'th-large', 'th', 'th-list', 'ok', 'remove', 'zoom-in', 'zoom-out', 'off', 'signal', 'cog', 'trash', 'home', 'file', 'time', 'road', 'download-alt', 'download', 'upload', 'inbox', 'play-circle', 'repeat', 'refresh', 'list-alt', 'lock', 'flag', 'headphones', 'volume-off', 'volume-down', 'volume-up', 'qrcode', 'barcode', 'tag', 'tags', 'book', 'bookmark', 'print', 'camera', 'font', 'bold', 'italic', 'text-height', 'text-width', 'align-left', 'align-center', 'align-right', 'align-justify', 'list', 'indent-left', 'indent-right', 'facetime-video', 'picture', 'map-marker', 'adjust', 'tint', 'edit', 'share', 'check', 'move', 'step-backward', 'fast-backward', 'backward', 'play', 'pause', 'stop', 'forward', 'fast-forward', 'step-forward', 'eject', 'chevron-left', 'chevron-right', 'plus-sign', 'minus-sign', 'remove-sign', 'ok-sign', 'question-sign', 'info-sign', 'screenshot', 'remove-circle', 'ok-circle', 'ban-circle', 'arrow-left', 'arrow-right', 'arrow-up', 'arrow-down', 'share-alt', 'resize-full', 'resize-small', 'exclamation-sign', 'gift', 'leaf', 'fire', 'eye-open', 'eye-close', 'warning-sign', 'plane', 'calendar', 'random', 'comment', 'magnet', 'chevron-up', 'chevron-down', 'retweet', 'shopping-cart', 'folder-close', 'folder-open', 'resize-vertical', 'resize-horizontal', 'hdd', 'bullhorn', 'bell', 'certificate', 'thumbs-up', 'thumbs-down', 'hand-right', 'hand-left', 'hand-up', 'hand-down', 'circle-arrow-right', 'circle-arrow-left', 'circle-arrow-up', 'circle-arrow-down', 'globe', 'wrench', 'tasks', 'filter', 'briefcase', 'fullscreen', 'dashboard', 'paperclip', 'heart-empty', 'link', 'phone', 'pushpin', 'usd', 'gbp', 'sort', 'sort-by-alphabet', 'sort-by-alphabet-alt', 'sort-by-order', 'sort-by-order-alt', 'sort-by-attributes', 'sort-by-attributes-alt', 'unchecked', 'expand', 'collapse-down', 'collapse-up', 'log-in', 'flash', 'log-out', 'new-window', 'record', 'save', 'open', 'saved', 'import', 'export', 'send', 'floppy-disk', 'floppy-saved', 'floppy-remove', 'floppy-save', 'floppy-open', 'credit-card', 'transfer', 'cutlery', 'header', 'compressed', 'earphone', 'phone-alt', 'tower', 'stats', 'sd-video', 'hd-video', 'subtitles', 'sound-stereo', 'sound-dolby', 'sound-5-1', 'sound-6-1', 'sound-7-1', 'copyright-mark', 'registration-mark', 'cloud-download', 'cloud-upload', 'tree-conifer', 'tree-deciduous', 'cd', 'save-file', 'open-file', 'level-up', 'copy', 'paste', 'alert', 'equalizer', 'king', 'queen', 'pawn', 'bishop', 'knight', 'baby-formula', 'tent', 'blackboard', 'bed', 'apple', 'erase', 'hourglass', 'lamp', 'duplicate', 'piggy-bank', 'scissors', 'bitcoin', 'btc', 'xbt', 'yen', 'jpy', 'ruble', 'rub', 'scale', 'ice-lolly', 'ice-lolly-tasted', 'education', 'option-horizontal', 'option-vertical', 'menu-hamburger', 'modal-window', 'oil', 'grain', 'sunglasses', 'text-size', 'text-color', 'text-background', 'object-align-top', 'object-align-bottom', 'object-align-horizontal', 'object-align-left', 'object-align-vertical', 'object-align-right', 'triangle-right', 'triangle-left', 'triangle-bottom', 'triangle-top', 'console', 'superscript', 'subscript', 'menu-left', 'menu-right', 'menu-down', 'menu-up');
foreach ($icons as $key => $value) {
  $icons[$key] = '<li title="' . $value . '"><span class="glyphicon glyphicon-' . $value . '" style="cursor:pointer;"></span></li>';
}
$list = '<ul class="list-inline" style="font-size:25px;">' . implode('', $icons) . '</ul>';
$icons = '<div class="page-header"><h2>Icons</h2></div>';
$icons .= $bp->row('sm', array(
  $bp->col(12, $list)
));
$html .= '<section id="icons">' . $icons . '</section><hr>';

echo '<div class="container" style="margin-top:50px;">' . $html . '</div><br>';

?>