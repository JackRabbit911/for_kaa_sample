<?php

use WN\Core\{Validation, View};
use WN\Core\Helper\{Upload, Dir, Form, HTML};

Upload::$upload_multiple_auto = false;
Upload::$default_directory = 'public/tmp';
Dir::clean(Upload::$default_directory, null, 30);

$file_label = 'Choose file... Images only';

$v = new Validation(['file' => 'required|image|size(2M)']);

if($v->check($_FILES))
{
    $file_label = $v->response['file']->value;
    $v->response['file']->msg = 'Все файлы загружены успешно!';
}

$msg = nl2br($v->response['file']->msg());

$form = View::factory('tests/test_form_multiple', $v->response)
                ->set('file_label', $file_label);

echo $form->render(), '<hr>';

$n = 0;
foreach($v->files_to_upload AS $file)
{
    echo HTML::image(Upload::save($file), ['height' => '100px', 'style' => 'margin-right: 1px;']);
    ++$n;
}
if($n) echo "<p>$n files was uploaded successful</p>";