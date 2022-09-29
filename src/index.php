<?php

// Autoload files using the Composer autoloader.
require_once __DIR__ . '/vendor/autoload.php';

var_dump('share');
exit;

use WoonhomeShare\WoonhomeShare;
use Structure\Structure;

// Init structure
Structure::init();

$share = new WoonhomeShare();
echo $share->start();