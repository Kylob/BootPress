# Welcome to BootPress

BootPress was designed to make building websites as simple and easy as possible, by using the best tools that have been created by the big brains that be:

- [Ace Editor](http://ace.c9.io/#nav=about)
- [Adminer](http://www.adminer.org/)
- [Bootstrap](http://getbootstrap.com) v.3.2.0
- [CodeIgniter](https://ellislab.com/codeigniter) v.3.0
- [HTML5 Boilerplate](http://html5boilerplate.com/)
- [ImageMagick](http://www.imagemagick.org/)
- [jsDeliver](http://www.jsdelivr.com/)
- [jQuery](http://jquery.com/)
- [Less CSS](http://lesscss.org/)
- [Parsedown](http://parsedown.org/)
- [PHP](http://php.net/) (requires v.5.3+)
- [Smarty](http://www.smarty.net/) v.3.1.19
- [SQLite](http://www.sqlite.org/) 3 (must be enabled)

What BootPress brings to the table is the functionality offered by the [$bp](http://bootpress.org/twitter-bootstrap-class/), [$ci](http://bootpress.org/codeigniter-classes/), and [$page](http://bootpress.org/page-class/) global variables whose sole purpose in life is to make your code more:

- Compact
- Efficient
- Readable
- Reliable
- Succinct

BootPress comes with a complete admin interface that allows you to quickly access and manage every aspect of your website:

- Caching
- Code
- Databases
- Errors
- Layouts
- Plugins
- Resources
- Traffic
- Users

The big idea is to make your applications:

- Better
- Faster
- Modular
- Organized
- Portable
- Searchable
- Secure

By providing a simple framework and structure that allows you to keep everything in the root directory where it is safe and accessible to all of your websites.  One install, that's all.  To get started:

## Step 1) [Download](http://github.com/paralogizing/bootpress/) To "bootpress" Folder In Private (Root) Directory

Or better yet, *clone* the code to your private folder where it cannot be accessed directly by hacker Joe, but is accessible to every website you ever hope to create.

## Step 2) Set Global Vars

Open up the "params_blank.php" file in the bootpress folder, and save as "params.php" after you have filled in the blanks like so:

``` php
<?php

// Enter the following information and rename this file to just params.php

$admin = array(
  'name'     => '', // however you like to see it written
  'email'    => '', // what you will use to sign in
  'password' => '', // for all of your websites
  'folder'   => 'admin' // the directory in which you would like to administer your site
);

$config['compress_output'] = false; // if you are getting compression errors (a blank page) then set this to false

$config['encryption_key'] = md5(serialize($admin));

define ('IMAGEMAGICK_PATH', ''); // (optional) to the command line

define ('PHP_PATH', ''); // (optional) used to sanitary (lint) check PHP files before they are saved

?>
```

## Step 3) Create An ".htaccess" File In Your Website's Public Folder

``` htaccess
# Prevent directory browsing
Options All -Indexes

# Turn on URL re-writing (remove 'your-domain.com/' if not on localhost)
RewriteEngine On
RewriteBase /your-domain.com/

# If the file exists, then that's all folks
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule .+ - [L]

# Bootpress from here on out
RewriteRule ^(.*)$ index.php [L]
``` 

It doesn't have to be index.php, just so long as it corresponds with the next step:

## Step 4) Create An "index.php" File In Your Website's Public Folder

``` php
<?php

// define ('BLOG', 'blog'); // An optional folder where you would like your blog listings, tags, archives, etc to reside.

$website = 'your-domain.com'; // A BASE . 'websites/' . $website . '/' folder to place all of your code in @ BASE_URI

$config['base_url'] = 'http://localhost/your-domain.com/'; // The desired BASE_URL with a trailing slash

$config['url_suffix'] = '/'; // How you would like your uri's to end: '.html', '.php', '.asp', '/', '', ...

$config['cookie_domain'] = false; // Set to '.your-domain.com' for site-wide cookies, or (bool) false for localhost

// $_SERVER['CI_ENV'] = 'production'; // uncomment when you would like to hide errors

require_once ('../bootpress/params.php');

?>
```

## Step 5) Go to your-domain.com

If you get nothing then just keep adding ../'s to your require_once in Step 4 until you hit the spot.

You, the admin user, will be directed to ``BASE_URL . ADMIN . '/users'`` where you will need to sign in with the credentials you placed in the params.php file.  Then you will be escorted to ``BASE_URL . ADMIN . '/setup'`` where you must enter your blog's (website) name.  Click submit and there you go.

## Enjoy

This code is the culmination of many years of countless rewrites and untold hours trying to perfect the most efficient (easy to fix), fast (for server and coder), and reliable way to create websites that are dynamic, responsive, and good looking.  I think I have finally nailed it.  There may be bugs.  Just let me know if you find any, and I will do my best to fix them.