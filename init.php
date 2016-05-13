<?php
/**
 * Package : RabbitMQ Manager
 * User: kontoulis
 * Date: 12/9/2015
 * Time: 1:24 μμ
 */
require_once __DIR__ . '/src/config.php';

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 'on');
}else{
    error_reporting(E_CORE_ERROR);
    ini_set('display_errors', 'off');
}

require_once __DIR__ . '/vendor/autoload.php';
