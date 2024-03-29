<?php
session_start();
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

require 'config.php';
require 'routers.php';
require 'vendor/autoload.php';

$core = new Core\Core();
$core->run();