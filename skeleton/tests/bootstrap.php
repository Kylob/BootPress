<?php

// Enable Composer autoloader
/** @var object \Composer\Autoload\ClassLoader */
$autoloader = require dirname(__DIR__) . '/vendor/autoload.php';

// Register test classes
$autoloader->addPsr4('BootPress\Tests\\', __DIR__);
