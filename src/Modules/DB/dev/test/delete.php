<?php
use WN\Core\Core;
use WN\DB\DB;

$db = DB::instance('sqlite.test');


$delete = $db->delete('my_table')
            ->where('title', '=', 'Колосов');

echo '<pre><code class="hljs sql">';
echo 'sql:'."\t", $delete->render(1, true), '<br>params:'."\t"; 
print_r($delete->params()); 
// echo ' ', $delete->execute(), '<br>';