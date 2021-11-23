<?php

$my_rule = function($value, $deny) {
    return ($value === $deny) ? 'invalid word: ":value"!' : true;};

$valid = new WN\Core\Validation();
$valid->rule('username', [$my_rule, 'sofisticated']);
// $valid->rule('email', 'email');
$valid->rule('password', 'password');
$valid->rule('confirm', [['confirm'], ['lenght, 2, 12']]);
// $valid->rule('item', ['in_array', ['qq', 'ww']]);
// $valid->rule('username', [$my_rule, 'sofisticated']);
$valid->rule('array', 'alpha');
$valid->rule('var', 'filter(FILTER_VALIDATE_INT)');

$post = [
    'username'  =>  'sofisticated', //'sofisticated',
    // 'email'     => 'email@@email.mail',
    'password'  => 'гыгы№12345',
    'confirm'   => 'password123',
    // 'item'      => 'qw',
    'array'     => ['abc', 'def'],
    'var'       => 'lala',
];

// var_dump($valid->rules['confirm']);
// exit;

var_dump($valid->check($post));
echo '<br>';

// echo '<pre><code class="hljs">';
// var_dump($valid->response['confirm']);
// echo '</code></pre>';

foreach($valid->response as $name => $response)
{
    echo ($response->status) ? 'true ' : 'false';
    echo '&nbsp'.__($name).' - '.$response->msg().'<br>';
}