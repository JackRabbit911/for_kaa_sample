<?php
use WN\Core\Core;
use WN\DB\{DB, Select};

$users_options = [
    'columns'   => [
        'id integer(11) pk_ai',
        'email varchar(128) null unique',
        'phone varchar(16) null unique',
        'password varchar(128)',
        'username varchar(128)',
        'name varchar(128)',
        'surname varchar(128)',
        'sex int(1)',
        'dob int(11)',
    ],
    'index' => [
        'index (email)',
        'index (phone)',
        'index (password)'
    ],
];

$groups_options =[
    'columns'   => [
        'id integer(11) primary key',
        'parent_id integer(11)',
        'title varchar(128) unique',
        'desc varchar(255)',
    ],
    'index' => ['foreign key(parent_id) references `groups`(id) cascade']
];

$roles_options = [
    'columns' => [
        'user_id integer(11) not null',
        'group_id integer(11) not null',
        'role integer(3) not null',
    ],
    'index' => [
        'unique (user_id, group_id)',
        'foreign key(user_id) references `users`(id) cascade',
        'foreign key(group_id) references `groups`(id) cascade'
    ],
];

$db = DB::instance('sqlite.test');

$users = $db->create('users')->set($users_options);
$groups = $db->create('groups')->set($groups_options);
$roles = $db->create('users_groups')->set($roles_options);

echo '<pre><code class="hljs sql">';
echo $users->render(true), '<hr>';
echo '</code></pre>', '<pre><code class="hljs sql">';
echo $groups->render(true), '<hr>';
echo '</code></pre>', '<pre><code class="hljs sql">';
echo $roles->render(true);
echo '</code></pre>';

// $db->exec($users, $groups, $roles);