<?php
use WN\Core\Core;
use WN\DB\DB;

$db = DB::instance('sqlite.test');

// $data = [
//     ['id' => 3, 'key' => 'username', 'value' => 'Andrew'],
//     ['id' => 3, 'key' => 'email', 'value' => 'kaa67@email.com'],
//     ['id' => 3, 'key' => 'phone', 'value' => null],
// ];

$db1 = $db->insert('my_table')
        ->set('title', 'Первый')
        ->set('desc', 'Описание к Первый');
                        // ->columns('id', 'key', 'value')
                        // ->values($data)
                        // ;
                        // ->execute();

echo '<pre><code class="hljs sql">';
echo 'sql:'."\t", $db1->render(1, true), '<br>params:'."\t";
// var_dump(DB::$prepare_mode);
print_r($db1->params()); 
// echo ' ', $db1->execute(), '<br>';
echo '</code></pre>';