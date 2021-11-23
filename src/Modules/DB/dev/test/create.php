<?php
use WN\Core\Core;
use WN\DB\DB;

$sqlite_db = DB::instance('sqlite');
$my_sql_db = DB::instance('mysql');

$options = [
    'columns' => [
        '`id` int(11) pk_ai', 
        '`parent_id` int(11)', 
        '`title` varchar(64) not null',
    ],
    'index' => [
        'index (`parent_id`)',
        'index (`title`)',
        'foreign key(`parent_id`) references my_table(`id`) cascade',
    ],
    'collate' => ['utf8_general_ci', 'utf8', 'InnoDB'],
];

$sqlite = $sqlite_db->create('my_table')->set($options);
$my_sql = $my_sql_db->create('my_table')->set($options);

echo '<h5>Sqlite</h5>', '<pre><code class="hljs sql">';
echo $sqlite->render(true), ' ', $sqlite->exec(), '</code></pre>';
echo '<h5>Mysql</h5>', '<pre><code class="hljs sql">';
echo $my_sql->render(true), ' ', $my_sql->exec(), '</code></pre>';