<?php
use WN\Core\Core;
use WN\DB\{DB, Select};

$db = DB::instance('sqlite.test');

$update = $db->update('my_table')
    ->set(['title' => 'Название 1'])
    ->where(1);

// $update->execute();

echo '<pre><code class="hljs sql">';
echo $update->render(1), '<br>';
print_r($update->params(false));
echo '</code></pre>';