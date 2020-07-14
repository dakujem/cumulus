<?php

/**
 * This file is a part of dakujem/cumulus package.
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */
//
$dir = '.';

// run the tests
foreach (['urlconfig', 'lazyiterator',] as $test) {
    $time1 = microtime(true);
    require_once($dir . '/' . $test . '.phpt');
    print '<hr/><pre>urlconfig.phpt | Finished at: ' . date('Y-m-d H:i:s') . ' | Runtime: ' . (microtime(true) - $time1) . 's</pre>';
}
