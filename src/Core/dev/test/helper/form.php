<?php

use WN\Core\Helper\Form;
use WN\Core\Request;

$attr['form'] = ['enctype' => 'multipart/form-data'];
$attr['username'] = ['id' => 'username', 'size' => 27];
$attr['file'] = ['id' => 'file', 'multiple'];

$action = Request::current()->uri().'#re';
 
$form = Form::open($action, $attr['form']).PHP_EOL."\t"
        .Form::label('username', __('username')).PHP_EOL."\t"
        .Form::input('username', '', $attr['username']).PHP_EOL."\t<br>".PHP_EOL."\t"
        .Form::label('file', __('File')).PHP_EOL."\t"
        .Form::file('file[]', $attr['file']).PHP_EOL."\t<br>".PHP_EOL."\t"
        .Form::label('male', __('male')).PHP_EOL."\t"
        .Form::radio('sex', 'male', false, ['id'=>'male']).PHP_EOL."\t"
        .Form::label('sex', __('female')).PHP_EOL."\t"
        .Form::radio('sex', 'female').PHP_EOL."\t"
        .Form::label('sex', __('other')).PHP_EOL."\t"
        .Form::radio('sex', 'other').PHP_EOL."\t<br>".PHP_EOL."\t"
        .Form::label('agree', __('agree')).PHP_EOL."\t"
        .Form::checkbox('agree').PHP_EOL."\t"
        .Form::checkbox('agree1').PHP_EOL."\t<br>".PHP_EOL."\t"
        .Form::select('select[]', ['foo'=>'foo', 'bar'=>'bar', 'baz'=>'baz'], [])
        .PHP_EOL."\t<br>".PHP_EOL."\t"
        .Form::submit(null, 'submit').PHP_EOL
        .Form::close();

echo '<pre><code class="hljs html">';
echo htmlspecialchars($form);
echo '</code></pre><hr>';

echo $form.'<hr>';

var_dump($_POST);
echo '<br>';
// var_dump($_FILES);