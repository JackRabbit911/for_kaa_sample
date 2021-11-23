<?php
use WN\Core\Core;
echo '<hr><h6>Core::paths()</h6>';
var_dump(Core::paths());
echo '<hr><h6>Core::enviroment()</h6>';
$env = Core::enviroment();
$constant_name = array_search($env, get_defined_constants(true)['user']);
var_dump($env, $constant_name);
echo '<hr><h6>Core::find_file("init")</h6>';
var_dump(Core::find_file('init'), Core::find_file('init', true));