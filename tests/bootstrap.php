<?php

/**
 * This file is a part of dakujem/cumulus package.
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */


namespace Dakujem\Cumulus\Test;

define('ROOT', __DIR__);

require_once __DIR__ . '/../vendor/autoload.php';

use Tracy\Debugger,
	Tester\Environment;

// tester
Environment::setup();

// debugging - when not run via a browser
if (function_exists('getallheaders') && !empty(getallheaders())) {
	Debugger::$strictMode = TRUE;
	Debugger::enable();
	Debugger::$maxDepth = 10;
	Debugger::$maxLen = 500;
}


// dump shortcut
function dump($var, $return = FALSE)
{
	return Debugger::dump($var, $return);
}
