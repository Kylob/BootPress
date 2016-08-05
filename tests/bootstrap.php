<?php

// Enable Composer autoloader
/** @var \Composer\Autoload\ClassLoader $autoloader */
$autoloader = require '../vendor/autoload.php';

// Register test classes
$autoloader->addPsr4('BootPress\Tests\\', __DIR__);
$autoloader->addPsr4('BootPress\\', dirname(__DIR__).'/src');
