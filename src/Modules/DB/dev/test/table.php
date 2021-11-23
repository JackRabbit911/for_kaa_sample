<?php

use WN\DB\{DB, Table};
use WN\DB\Lib\Sqlite;

// $driver = 'mysql';
$driver = 'sqlite.test';

$db = DB::instance($driver);

$table = $db->table('my_table');

echo '<pre><code class="hljs sql">';
// echo $table->prepare()->create($options)->render(1,true), '<br>';
$up = $table->prepare()->set(['title' => 'название2', 'desc' => 'описание1']);
echo $up->render(1, true), ';<br>';
print_r($up->params());
echo '</code></pre>';
