<?php

namespace Dakujem\Cumulus;

/**
 * UrlConfig - provided for compatibility only.
 *
 * @deprecated Use the Dsn class direcly.
 *
 * @author Andrej Rypák (dakujem) <xrypak@gmail.com>
 */
class UrlConfig extends Dsn
{
    /**
     * Return a PDO string in format:
     * "mysql:host=localhost;dbname=my_db"
     *
     * @deprecated This is an alias to calling $conf->get('pdo', '')
     *
     * @return string
     */
    public function getPdoDsn()
    {
        return $this->get('pdo', '');
    }
}
