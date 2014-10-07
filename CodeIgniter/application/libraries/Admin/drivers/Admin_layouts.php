<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_layouts extends CI_Driver {

  private $template;
  private $preview;
  private $bootstrap;
  private $fonts;
  private $dir;
  
  public function __construct () {
    global $ci, $page;
    $this->template = (isset($_GET['template'])) ? $page->seo($_GET['template']) : 'default';
    $this->preview = ($ci->session->native->tempdata('preview_layout')) ? true : false;
    if ($this->preview && $this->preview != $this->template) {
      $ci->session->native->set_tempdata('preview_layout', $this->template, 3000);
    }
    $this->bootstrap = APPPATH . 'libraries/templates/bootstrap/';
    $this->fonts = str_repeat('../', count(explode('/', $page->get('domain'))) + 3) . 'CodeIgniter/application/libraries/templates/';
    $this->dir = BASE_URI . 'blog/templates/';
    if (!is_dir($this->dir . 'post/')) mkdir($this->dir . 'post/', 0755, true);
    if (!is_dir($this->dir . 'layout/')) mkdir($this->dir . 'layout/', 0755, true);
  }
  
  public function view () {
    global $bp, $ci, $page;
    if (isset($_GET['bootstrap'])) {
      if (file_exists($this->dir . $_GET['bootstrap'] . '.css')) {
        $page->link(BASE_URL . 'blog/templates/' . $_GET['bootstrap'] . '.css', 'prepend');
      } else {
        $page->plugin('CDN', 'link', 'bootstrap/3.1.1/css/bootstrap.min.css');
      }
      $page->plugin('CDN', 'link', 'bootstrap/3.1.1/js/bootstrap.min.js');
      $page->title = 'Bootstrap Theme Preview';
      exit($ci->filter_links($page->display($page->outreach($this->bootstrap . 'preview.html'))));
    }
    if (isset($_GET['delete']) && $_GET['delete'] == 'template') {
      if (file_exists($this->dir . $this->template . '.css')) unlink($this->dir . $this->template . '.css');
      if (file_exists($this->dir . $this->template . '.js')) unlink($this->dir . $this->template . '.js');
      if (file_exists($this->dir . 'post/' . $this->template . '.php')) unlink($this->dir . 'post/' . $this->template . '.php');
      if (file_exists($this->dir . 'layout/' . $this->template . '.php')) unlink($this->dir . 'layout/' . $this->template . '.php');
      $this->blog->db->delete('templates', 'template', $this->template);
      $page->eject($page->url('delete', '', array('delete', 'template')));
    }
    if (isset($_POST['retrieve'])) {
      switch ($_POST['retrieve']) {
        case 'css': echo str_replace($this->fonts, '../', $this->get_template($this->template . '.css')); break;
        case 'javascript': echo $this->get_template($this->template . '.js'); break;
        case 'postPHP': echo $this->get_template('post/' . $this->template . '.php'); break;
        case 'layoutPHP': echo $this->get_template('layout/' . $this->template . '.php'); break;
        default: echo $this->blog->templates($_POST['retrieve'], $this->template); break;
      }
      exit;
    }
    if (isset($_POST['wyciwyg']) && isset($_POST['field'])) {
      switch ($_POST['field']) {
        case 'variables':
        case 'custom': exit($this->compile_less($_POST['field'], $this->code('wyciwyg'))); break;
        case 'css':
          $result = $this->put_template($this->template . '.css');
          $file = $this->dir . $this->template . '.css';
          if (file_exists($file)) $this->file_put_converted(file_get_contents($file));
          exit($result);
          break;
        case 'javascript':
          if ($ci->input->post('wyciwyg') == 'default') {
            file_put_contents($this->dir . $this->template . '.js', file_get_contents($this->bootstrap . '3.2.0/bootstrap.min.js'));
            exit('Saved');
          } else {
            exit($this->put_template($this->template . '.js'));
          }
          break;
        case 'postPHP': exit($this->put_template('post/' . $this->template . '.php')); break;
        case 'layoutPHP': exit($this->put_template('layout/' . $this->template . '.php')); break;
        case 'header':
        case 'listings':
        case 'post':
        case 'page':
        case 'tags':
        case 'authors':
        case 'archives':
        case 'sidebar':
        case 'footer':
        case 'layout':
          $field = $_POST['field'];
          $template = $this->code('wyciwyg');
          $result = $this->blog->smarty('blog', $template, 'testing');
          if ($result === true) {
            $this->blog->db->update('templates', 'template', array($this->template => array($field => $template)));
            exit('Saved');
          }
          exit($result);
          break;
      }
      exit('Error');
    }
    $html = '';
    $html .= $this->form();
    $readOnly = (is_admin(1)) ? '' : ' readOnly';
    $wyciwyg = array(
      'Bootstrap::Variables' => '<a href="#" class="wyciwyg less noMarkup" data-retrieve="variables">Variables</a>',
      'Bootstrap::Custom' => '<a href="#" class="wyciwyg less noMarkup" data-retrieve="custom">Custom</a>',
      'Bootstrap::CSS' => '<a href="#" class="wyciwyg css noMarkup" data-retrieve="css">CSS</a>',
      'Bootstrap::Javascript' => '<a href="#" class="wyciwyg js noMarkup" data-retrieve="javascript">Javascript</a>',
      'PHP::Post' => '<a href="#" class="wyciwyg php noMarkup' . $readOnly . '" data-retrieve="postPHP">Post</a>',
      'PHP::Layout' => '<a href="#" class="wyciwyg php noMarkup' . $readOnly . '" data-retrieve="layoutPHP">Layout</a>',
      'Smarty::Header' => '<a href="#" class="wyciwyg tpl" data-retrieve="header">Header</a>',
      'Smarty::Listings' => '<a href="#" class="wyciwyg tpl" data-retrieve="listings">Listings</a>',
      'Smarty::Post' => '<a href="#" class="wyciwyg tpl" data-retrieve="post">Post</a>',
      'Smarty::Page' => '<a href="#" class="wyciwyg tpl" data-retrieve="page">Page</a>',
      'Smarty::Tags' => '<a href="#" class="wyciwyg tpl" data-retrieve="tags">Tags</a>',
      'Smarty::Authors' => '<a href="#" class="wyciwyg tpl" data-retrieve="authors">Authors</a>',
      'Smarty::Archives' => '<a href="#" class="wyciwyg tpl" data-retrieve="archives">Archives</a>',
      'Smarty::Sidebar' => '<a href="#" class="wyciwyg tpl" data-retrieve="sidebar">Sidebar</a>',
      'Smarty::Footer' => '<a href="#" class="wyciwyg tpl" data-retrieve="footer">Footer</a>',
      'Smarty::Layout' => '<a href="#" class="wyciwyg tpl" data-retrieve="layout">Layout</a>'
    );
    $layout = array(
      'Bootstrap' => array(
        $wyciwyg['Bootstrap::Variables'] => '<p>This is the Twitter Bootstrap variables.less file that you may edit to roll out your own theme.  Currently serving v3.2.0.  When you save, just sit still and relax.  It will take a minute or so.</p>',
        $wyciwyg['Bootstrap::Custom'] => '<p>This is LESS CSS that is processed alongside the <b>Variables</b> above, and placed after the main Bootstrap code in the <b>CSS</b> below.  You may use any of the same variables and mixins that Bootstrap uses, and / or create your own.</p>',
        $wyciwyg['Bootstrap::CSS'] => '<p>Submit a Bootstrap CSS file and any custom code that you would like to tack onto the end.  The <code>{$bp}</code> class currently supports version 3 of the framework.  If you leave this empty then we ignore the javascript below and include the Bootstrap v3.2.0 CSS and Javascript files by default.  Whatever you save here will override anything created by the <b>Variables</b> and <b>Custom</b> CSS above, and vice versa.</p>',
        $wyciwyg['Bootstrap::Javascript'] => '<p>This is the Bootstrap Javascript file that you can enter in whole, in part, or not.  If you include it, or use jQuery at all in your page, then jQuery v1.11.0 will be delivered by default.  To copy over the bootstrap.min.js file v.3.2.0 just type \'default\' and Save Changes.</p>'
      ),
      'PHP' => array(
        $wyciwyg['PHP::Post'] => '<p>This script is called after the page has been loaded, and only if javascript is enabled.  You should <code>$export</code> an array where the keys may be \'css\', \'javascript\', or a jQuery selector (likely an \'#id\') where the html (value) should go.  This is useful for user links, banner ads, and the like.</p>',
        $wyciwyg['PHP::Layout'] => '<p>If you choose to use the PHP Layout option (by <code>echo</code>ing a string of html), then the Smarty Layout below will be completely ignored, except for the <code>$content</code> portion that will be delivered if you <code>extract($page->get(\'params\'))</code> at the top (strongly encouraged).  This will cause your pages to load a little bit faster, but your client will not be able to tinker with their code.  You will also need to <code>$page->customize(\'...\')</code> the <b>header</b>, <b>sidebar</b>, and <b>footer</b> if desired.  If an empty value is returned (ie. you are merely performing some php magic behind the scenes), or if you <code>$export</code> an array of information, then a <code>{$php}</code> variable will be delivered to the rest of your Smarty templates below.</p>'
      ),
      'Smarty' => array(
        $wyciwyg['Smarty::Header'] => '<p>This will be delivered to your <b>Layout</b> as a <code>{$header}</code> variable.</p>',
        'Content' => '<p>This will be delivered to your <b>Layout</b> as a <code>{$content}</code> variable.</p><p>All of the <b>Content</b> templates here receive a <code>{$breadcrumbs}</code> array that can be utilized using <code>{$bp->breadcrumbs($breadcrumbs)}</code>, or in any other creative way you can think of.</p>' . $bp->lister('dl', array(
          $wyciwyg['Smarty::Listings'] => '<p>In some circles this is known as "The Loop", and the <code>{$blog.page}</code>\'s that use it are:</p>' . $bp->lister('ul', array(
            '\'<b>index</b>\' - the home page',
            '\'<b>search</b>\' - also the home page with a user supplied <code>{$search}</code> term',
            '\'<b>tag</b>\' - posts that have been tagged <code>{$tag}</code>',
            '\'<b>category</b>\' - a <code>{$category}</code> that is defined by groups of tags',
            '\'<b>author</b>\' - a specific <code>{$author}</code>\'s (array - same as below) posts',
            '\'<b>archive</b>\' - an <code>{$archive}</code> date that is being browsed through'
          )) . '<p>This template receives an array of <code>{$posts}</code> with the following keys that you can loop through and display:</p>' . $bp->lister('ul', array(
            '<code>{$post.thumb}</code> a url to an optional 200x200 jpg',
            '<code>{$post.url}</code> link to post or page',
            '<code>{$post.title}</code> needs no introduction',
            '<code>{$post.summary}</code> meta description',
            '<code>{$post.tags}</code> an array of \'name\' => \'url\' pairs',
            '<code>{$post.author}</code> an array of \'url\', \'thumb\', \'name\', and \'summary\' keys (if any)',
            '<code>{$post.archive}</code> a url if published',
            '<code>{$post.published}</code> and <code>{$post.updated}</code> GMT Unix timestamps'
          )) . '<p>Don\'t forget to use <code>{$bp->listings()->pagination()}</code> to present the full gamut of what you have to offer here.</p>',
          $wyciwyg['Smarty::Post'] => '<p>A <code>{$blog.page}</code> template that receives all of the same <code>{$post}</code> information as described above with an additional <code>{$post.content}</code> parameter as this is the main page where we let it all hang out.  We also include <code>{$previous}</code> and <code>{$next}</code> arrays (with title and url keys), and a <code>{$similar}</code> (posts) array that you can loop through the same as in <b>Listings</b> above.</p>',
          $wyciwyg['Smarty::Page'] => '<p>A <code>{$blog.page}</code> template that receives a <code>{$page}</code> array (same as <code>{$post}</code> above), except this is a page so it doesn\'t have any author or archive information associated with it.</p>',
          $wyciwyg['Smarty::Tags'] => '<p>A <code>{$blog.page}</code> that includes a currently used <code>{$tags}</code> array useful for creating a tag cloud with its <code>{$tag}</code> keys and:</p>' . $bp->lister('ul', array(
            '<code>{$links.count}</code> the number of times it is used',
            '<code>{$links.rank}</code> on a scale of 1 to 5 comparatively speaking with 1 being used the most and 5 being sparse',
            '<code>{$links.url}</code> where you may find <b>Listings</b> of all of the <code>{$tag}</code>ed posts'
          )),
          $wyciwyg['Smarty::Authors'] => '<p>A <code>{$blog.page}</code> with an array of published <code>{$authors}</code> at the site with:</p>' . $bp->lister('ul', array(
            '<code>{$author.count}</code> the number of posts they have created',
            '<code>{$author.url}</code> to all of this author\'s <b>Listings</b>',
            '<code>{$author.thumb}</code> an image of the authors face or whatever',
            '<code>{$author.name}</code> real or assumed',
            '<code>{$author.summary}</code> what makes them so great'
          )),
          $wyciwyg['Smarty::Archives'] => '<p>An <code>{$archives}</code> array with <code>{$Y}</code> keys (ie. YYYY) and:</p>' . $bp->lister('ul', array(
            '<code>{$years.count}</code> the number of posts created in this year',
            '<code>{$years.url}</code> to all of this year\'s <b>Listings</b>',
            '<code>{$years.months}</code> another array with <code>{$M}</code> keys (ie. Jan, Feb, Mar, etc.) and:' . $bp->lister('ul', array(
              '<code>{$months.count}</code> the number of posts created in this month (and year)',
              '<code>{$months.url}</code> to all of this month\'s <b>Listings</b>',
              '<code>{$months.time}</code> in case you don\'t like the supplied <code>{$M}</code>, then you can use this value (in seconds) to get the format your heart desires'
            ))
          ))
        ), 'dl-horizontal'),
        $wyciwyg['Smarty::Sidebar'] => '<p>This will be delivered to your <b>Layout</b> as a <code>{$sidebar}</code> variable.</p>',
        $wyciwyg['Smarty::Footer'] => '<p>This will be delivered to your <b>Layout</b> as a <code>{$footer}</code> variable.</p>',
        $wyciwyg['Smarty::Layout'] => '<p>This is the final layout for all of the pages that utilize this template.  It receives the <code>{$header}</code>, <code>{$content}</code>, <code>{$sidebar}</code>, and <code>{$footer}</code> variables for you to arrange as desired.</p>'
      )
    );
    foreach ($layout as $header => $content) {
      if ($header == 'Bootstrap') $header .= $bp->button('link', 'View Theme', array('href'=>$page->url('add', '', 'bootstrap', $this->template), 'style'=>'margin-left:10px;'));
      $html .= '<fieldset><legend>' . $header . '</legend>';
      if ($header == 'Smarty') {
        $html .= '<p>Every template here receives a basic <code>{$blog}</code> array of info:<p>' . $bp->lister('ul', array(
          '<code>{$blog.page}</code> that is being viewed so you can deliver page specific content',
          '<code>{$blog.name}</code> which says it all',
          '<code>{$blog.slogan}</code> an optional tagline',
          '<code>{$blog.summary}</code> meta description of what the blog is all about',
          '<code>{$blog.img}</code> the url where all of the blog\'s resources (most likely images) reside',
          '<code>{$blog.thumb}</code> a url to an optional 200x200 jpg that captures the essence of the blog',
          '<code>{$blog.url}</code> the <code>BASE_URL</code> that precedes them all'
        )) . '<p>In addition to the <code>{$bp}</code> BootPress object that makes integration with Twitter\'s Bootstrap markup a snap.</p>';
      }
      $html .= $bp->lister('dl', $content, 'dl-horizontal dl-left');
      $html .= '</fieldset><br>';
    }
    $page->link('<style>dl.dl-horizontal { margin-bottom:0; } .dl-horizontal dt { text-align:right; width:80px; } .dl-left dt { text-align:left; } .dl-horizontal dd { margin-left:90px; }</style>');
    return $this->admin($html);
  }
  
  private function get_template ($file) {
    $contents = (file_exists($this->dir . $file)) ? file_get_contents($this->dir . $file) : '';
    return $contents;
  }
  
  private function put_template ($file) {
    $result = $this->file_put_post($this->dir . $file, 'wyciwyg');
    return ($result === true) ? 'Saved' : $result;
  }
  
  private function compile_less ($file, $less) {
    $variables = ($file == 'variables') ? $this->merge_variables($less) : $this->blog->templates('variables', $this->template);
    $custom = ($file == 'custom') ? $less : $this->blog->templates('custom', $this->template);
    include_once $this->bootstrap . 'less.php/Less.php';
    try {
      $parser = new Less_Parser();
      $parser->parse($variables);
      $parser->parseFile($this->bootstrap . '3.2.0/bootstrap.less');
      if (!empty($custom)) $parser->parse($custom);
      $css = $parser->getCss();
    } catch (Exception $e) {
      $error = $e->getMessage();
    }
    if (!isset($css)) return $error;
    $this->file_put_converted($css);
    $this->blog->db->update('templates', 'template', array($this->template => array($file => $$file))); // variables or custom
    return 'Saved';
  }
  
  private function merge_variables ($less) {
    #-- Submitted $less variables --#
    $variables = array();
    if (preg_match_all('/@([a-z0-9-]*):([^;]*);/i', $less, $matches)) {
      foreach ($matches[1] as $key => $value) $variables[$value] = trim($matches[2][$key]);
    }
    #-- The default (master) variables --#
    $file = file_get_contents($this->bootstrap . '3.2.0/variables.less');
    preg_match_all('/@([a-z0-9-]*):([^;]*);/i', $file, $matches);
    $defaults = array_flip($matches[1]);
    foreach ($variables as $var => $value) {
      if (isset($defaults[$var])) {
        $key = $defaults[$var];
        $original = trim($matches[2][$key]);
        if ($original != $value) {
          $replace = substr($matches[0][$key], 0, strrpos($matches[0][$key], $original)) . $value . '; // ' . $original . ';';
          $file = str_replace($matches[0][$key], $replace, $file);
        }
        unset($variables[$var]);
      }
    }
    #-- Submitted variables that were not in the master file --#
    if (!empty($variables)) {
      $lengths = array();
      foreach ($variables as $var => $value) $lengths[] = strlen($var);
      $pad = max($lengths) + 4;
      foreach ($variables as $var => $value) $variables[$var] = '@' . str_pad($var . ':', $pad, ' ') . $value . ';';
      $file = "// Custom\n// --------------------------------------------------\n" . implode("\n", $variables) . "\n\n\n" . $file;
    }
    #-- Place the Imports up top --#
    if (preg_match_all('/@import\s*(.*);/i', $less, $matches)) {
      $imports = $matches[0];
      $file = "// Import(s)\n// --------------------------------------------------\n" . implode("\n", $imports) . "\n\n\n" . $file;
    }
    #-- Return the $less with all of the required variables included --#
    return $file;
  }
  
  private function file_put_converted ($css) {
    $css = preg_replace('/([\.\/]+)(fonts\/glyphicons-halflings-regular){1}/', $this->fonts . '$2', $css);
    file_put_contents($this->dir . $this->template . '.css', $css);
  }
  
  private function form () {
    global $bp, $ci, $page;
    $html = '';
    $form = $page->plugin('Form', 'name', 'layout_templates');
    $templates = array('default'=>'default');
    $this->blog->db->query('SELECT template FROM templates WHERE template != ? ORDER BY template ASC', array('default'));
    while (list($template) = $this->blog->db->fetch('row')) $templates[$template] = $template;
    $form->menu('template', $templates);
    $form->menu('state[]', array('preview'=>'Preview the selected template', 'cacheable'=>'This template is cacheable'));
    $form->menu('action', array(
      'copy' => '<b>Copy</b> will make a duplicate of this template if it does not already exist',
      'rename' => '<b>Rename</b> will change the name of this template as long as it does not already exist',
      'replace' => '<b>Replace</b> will delete the old and replace with this template only if the old actually exists'
    ));
    $cache = $this->dir . $this->template . '.cache';
    $values = $this->blog->templates(array(), $this->template);
    if ($this->preview) $values['state[]'][] = 'preview';
    if (file_exists($cache)) $values['state[]'][] = 'cacheable';
    $values['action'] = 'copy';
    $form->values($values);
    $form->validate('template', 'Template', 'required|inarray[menu]', 'Select the template you would like to edit.');
    $form->validate('state[]', '', 'inarray[menu]');
    $form->validate('save', 'Save As', '', 'Enter the name of the template for which you would like to Copy, Rename, or Replace.');
    $form->validate('action', '', 'required', 'inarray[menu]');
    if ($form->submitted() && empty($form->errors)) {
      if (in_array('preview', $form->vars['state'])) {
        $ci->sitemap->suspend_caching(60);
        $ci->session->native->set_tempdata('preview_layout', $this->template, 3000);
      } else {
        $ci->session->native->unset_tempdata('preview_layout');
      }
      if (in_array('cacheable', $form->vars['state'])) {
        if (!file_exists($cache)) file_put_contents($cache, '');
      } else {
        if (file_exists($cache)) unlink($cache);
      }
      if (!empty($form->vars['save'])) {
        $new_template = $page->seo($form->vars['save']);
        $exists = $this->blog->db->value('SELECT template FROM templates WHERE template = ?', array($new_template));
        $files = array(
          $this->dir . $this->template . '.css' => $this->dir . $new_template . '.css',
          $this->dir . $this->template . '.js' => $this->dir . $new_template . '.js',
          $this->dir . 'post/' . $this->template . '.php' => $this->dir . 'post/' . $new_template . '.php',
          $this->dir . 'layout/' . $this->template . '.php' => $this->dir . 'layout/' . $new_template . '.php'
        );
        switch ($form->vars['action']) {
          case 'copy':
            if (!$exists) {
              foreach ($files as $old => $new) if (file_exists($old)) copy($old, $new); 
              $insert = $this->blog->templates(array(), $this->template);
              $insert['template'] = $new_template;
              $this->blog->db->insert('templates', $insert);
            } else {
              $form->errors['action'] = 'The template name you are trying to <b>Save As</b> a <b>Copy</b> already exists.';
            }
            break;
          case 'rename':
            if (!$exists) {
              foreach ($files as $old => $new) if (file_exists($old)) rename($old, $new);
              $this->blog->db->update('templates', 'template', array($this->template => array('template' => $new_template)));
            } else {
              $form->errors['action'] = 'You cannot <b>Rename</b> and <b>Save As</b> a template that already exists.';
            }
            break;
          case 'replace':
            if ($exists) {
              foreach ($files as $new => $old) {
                if (file_exists($old)) unlink($old);
                if (file_exists($new)) rename($new, $old);
              }
              $this->blog->db->delete('templates', 'template', $new_template);
              $this->blog->db->update('templates', 'template', array($this->template => array('template' => $new_template)));
            } else {
              $form->errors['action'] = 'The template name you are trying to <b>Save As</b> and <b>Replace</b> does not exist.';
            }
            break;
        }
        // $html .= '<pre>' . print_r($form->vars, true) . '</pre>';
        if (empty($form->errors)) $page->eject($page->url('add', $form->eject, 'template', $new_template));
      } else { // $form->vars['save'] is empty
        $page->eject($form->eject);
      }
    }
    $html .= $form->header();
    $html .= $form->field('template', 'select');
    $html .= $form->field('state[]', 'checkbox');
    $html .= $form->field('save', 'text');
    $html .= $form->field('action', 'radio');
    $html .= $form->submit('Submit', $bp->button('danger pull-right delete', $bp->icon('trash')));
    $html .= $form->close();
    $page->plugin('jQuery', 'code', '
      $("#' . $form->id('template') . '").change(function(){
        window.location = "' . $page->url('delete', '', '?') . '?template=" + $(this).val();
      });
      $(".delete").click(function(){
        if (confirm("Are you sure you would like to delete this template?")) {
          window.location = "' . str_replace('&amp;', '&', $page->url('add', '', 'delete', 'template')) . '";
        }
      });
    ');
    unset($form);
    return $html;
  }
  
}

/* End of file Admin_layouts.php */
/* Location: ./application/libraries/Admin/drivers/Admin_layouts.php */