<?php

/**
 * This file is a part of dakujem/cumulus package.
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */
//
$dir = '.';

// run the test
$time1 = microtime(TRUE);
require_once($dir . '/urlconfig.phpt');
print '<hr/><pre>urlconfig.phpt | Finished at: ' . date('Y-m-d H:i:s') . ' | Runtime: ' . (microtime(TRUE) - $time1 ) . 's</pre>';
