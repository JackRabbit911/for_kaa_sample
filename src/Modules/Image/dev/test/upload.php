<?php
use WN\Core\{Validation, View};
use WN\Image\Image;


// Model::$driver = 'sqlite.test';
// Image::$dir = null; // '/public/test_upload';
$user_id = 11;

// $img = '/image';

$rules = [
    'alt'       => 'regexp(/^[\w\s@,.()-]*$/u)',
    'file'      => 'required|image|size(2M)',
];

$v = new Validation($rules);

if($v->check($_POST, $_FILES))
{
    $image = Image::upload($_FILES['file']);
    $image->purpose = 'img';
    $image->user = $user_id;
    $image->alt = $desc = $_POST['alt'];

    var_dump($image->save());

    // $image->create_thumbnail();

    $img = $image->src('image');

    $v->response['file']->msg = __('Upload successful');
}
else
{
    $img = '/~plh?text=200x200';
    $desc = '';
    // if($v->response['file']['status'] === true)
    //     $v->response['file'] = ['status' => null, 'value' => null, 'msg' => null];
}

echo View::factory('tests/test_form', $v->response)
    ->set('file_label', 'Choose file... Images only')
    ->set('img', $img)
    ->set('desc', $desc)
    ->render();