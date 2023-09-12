<?php

if (file_exists('../vendor/autoload.php')) {
    require('../vendor/autoload.php');
} else {
    require('../../../autoload.php');
}
define('KAART_TESTDIRECTORY', realpath('./tmp/'));
#define('KAART_SERVER_HOSTNAME', 'localhost');
#define('KAART_SERVER_PATH', '/');

