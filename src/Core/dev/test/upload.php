<?php
use WN\Core\{Validation, View};
use WN\Core\Helper\{Upload, Dir};

Upload::$default_directory = 'public/tmp';
Dir::clean(Upload::$default_directory, null, 30);

$img = '/media/img/200x200.svg';
$file_label = 'Choose file... Images only';

$rules = [
    'alt'       => 'required|regexp(/^[\w\s@,.()-]*$/u)',
    'file'      => 'not_empty|image|size(3M)',
];

$v = new Validation($rules);

$ch = $v->check($_POST, $_FILES);

if($ch)
{
    if(($img = Upload::save($_FILES['file'])) !== false) {}

    $file_label = $v->response['file']->value['name'];
    $v->response['file']->msg = __('Upload successful');
    $desc = $_POST['alt'];  
}
else $desc = '';

$view = View::factory('tests/test_form', $v->response)
    ->set('file_label', $file_label)
    ->set('img', $img)
    ->set('desc', $desc);

echo $view->render();