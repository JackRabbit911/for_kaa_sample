<?php
use WN\Core\Core;
use WN\DB\{DB, Select};

$db = DB::instance('sqlite.test');

$data = ['title'=>'Название 2', 'desc'=>'Описание 2'];
$query = $db->upsert('my_table')->set($data);

echo '<pre><code class="hljs sql">';
// var_dump($query->execute());
echo $query->render(1, true), '<br>';
print_r($query->params(false));

echo '</code></pre>';
