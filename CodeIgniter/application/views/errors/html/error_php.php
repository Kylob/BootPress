<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.2.4 or newer
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Academic Free License version 3.0
 *
 * This source file is subject to the Academic Free License (AFL 3.0) that is
 * bundled with this package in the files license_afl.txt / license_afl.rst.
 * It is also available through the world wide web at this URL:
 * http://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to obtain it
 * through the world wide web, please send an email to
 * licensing@ellislab.com so we can send you a copy immediately.
 *
 * @package		CodeIgniter
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (http://ellislab.com/)
 * @license		http://opensource.org/licenses/AFL-3.0 Academic Free License (AFL 3.0)
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container"><div class="col-sm-12">

<h4>A PHP Error was encountered</h4>

<p>Severity: <?php echo $severity; ?></p>
<p>Message:  <?php echo $message; ?></p>
<p>Filename: <?php echo $filepath; ?></p>
<p>Line Number: <?php echo $line; ?></p>

<p>Backtrace:</p>

<dl class="dl-horizontal">

<?php foreach (array_slice(debug_backtrace(false), 2, -3) as $error): ?>

	<?php if (isset($error['file']) && isset($error['line'])): ?>

		<dt><?php echo (isset($error['class'])) ? $error['class'] . '::' . $error['function'] : $error['function'] ?></dt>
		<dd>
			<?php echo str_replace(array(BASE_URI, BASE), array('BASE_URI . ', 'BASE . '), $error['file']) ?>
			<?php echo ' Line: ' . $error['line'] ?>
		</dd>

	<?php endif ?>

<?php endforeach ?>

</dl>

</div></div>