<?php

if (file_exists('../vendor/autoload.php')) {
    require('../vendor/autoload.php');
} else {
    require('../../../autoload.php');
}
define('KAART_TESTDIRECTORY', realpath('./tmp/'));
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
