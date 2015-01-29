<?php
error_reporting(E_ALL);

// Require Composer autoloader
require '../vendor/autoload.php';

// Init application
$app = require_once( __DIR__ . '/../app/bootstrap.php');

// Execute application and return response
$app->run();
