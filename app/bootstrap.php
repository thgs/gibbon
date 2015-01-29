<?php
// init our dependency injection container
$dice = new Dice\Dice;

// Create application object
$app = $dice->create('Gibbon\Application', [$dice]);

// Return application object
return $app;
