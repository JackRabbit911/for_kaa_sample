<?php
use WN\Core\Core;
use WN\DB\{DB, Select, Lib};

/** TODO: не работает на sqlite. рвзобраться */

$db = DB::instance('sqlite');

$max = $db->select('id')->from('groups')->where('id', '=', 5);

$select = $db->select('sub.desc')
            ->from('groups')
            ->distinct()
            ->from([$db->select()->from('groups')
                    ->where('id', 'between', [2, $max]), 'sub'])
            ->join('files f', 'left')->using('id')
            ->where('sub.title', 'СЕО')
        // ->union()
        //     ->select('purpose')
        //         ->from('files')
        //         ->where('id', 1)
        //         ->or_where('id', 2)
        //         ->order_by('id')            
        // ->union('all')
        //     ->select('id')
        //     ->from('images')
        //     ->where('id', 1)
        // ->union_order_by('id')
        // ->union_limit(1)
        ;

// $select1 = $db->select(DB::expr('3 + 3'));

// $select->reset();

// $n = $db->select($db->expr('CEIL(', $db->select('AVG(id)')->from('groups')));

// $select1 = $db->select('title')
//     ->from('groups')
    // ->join('images')
    // ->on('groups.id', 'images.id')
    // // ->using('id')
    // ->on('images.id', '>', DB::expr(3))
    // ->where('name', DB::expr("CONCAT('image', ", $n, '))'))
    // ->where('groups.id', '`images`.`id`')
//     ->order_by('groups.id')
    // ->where('id', 'in', $db->select('id')->from('groups')->where('id', 'in', [1,2,3]))
                    // $db->select('AVG(id)')
                    // ->from('groups'))
    // ->where('parent_id', '>', 1)
    ;

echo '<pre><code class="hljs sql">';
echo ($sql = $select->render(null, true)), ';';
echo '</code></pre>';
echo 'Params:<br>'; var_dump($select->params(2));
echo '<br>Result:<br>';
// var_dump($select->execute()->fetchAll());

echo '<hr>';

$select = $db->select('title')
    ->from('groups');

echo '<pre><code class="hljs sql">';
echo $select->render(1, true), ';';
echo '</code></pre>';
echo 'Params:<br>'; var_dump($select->params());
echo '<br>Result:<br>';
// var_dump($select->execute()->fetchAll());
// var_dump(DB::$level);