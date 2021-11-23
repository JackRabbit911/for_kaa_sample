<?php

$config = WN\Core\Config::instance();

var_dump($config->get('datatypes.username'));
echo '<br>';
var_dump($config->get('mimes', 'pdf'));
echo '<br>';
var_dump($config->get('missing_file'));
echo '<br>';
var_dump($config->get('mimes.missing_key'));