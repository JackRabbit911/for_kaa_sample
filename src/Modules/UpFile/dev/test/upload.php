<?php
use WN\Core\{Validation, View};
use WN\Core\Helper\{Form, HTML};
use WN\UpFile\UpFile;

$user_id = 11;

$v = new Validation(['file' => 'required|size(4M)']);

if($v->check($_FILES))
{
    // var_dump($v->files_to_upload); exit;
    $file = UpFile::upload($_FILES['file'], '/tmp');
    $file->user = $user_id;

    var_dump($file->save());

    $v->response['file']->msg = __('Upload successful');
    $html = HTML::file_anchor($file->src());
}
else $html = null;

$form = Form::open(null, ['enctype' => 'multipart/form-data']).PHP_EOL."\t"
        .Form::label('file', __('File')).PHP_EOL."\t"
        .Form::file('file', ['id' => 'file']).PHP_EOL."\t<br>".PHP_EOL."\t"
        .'<p>'.$v->response['file']->msg().'</p>'
        .Form::submit(null, 'submit').PHP_EOL
        .Form::close();

echo $form, '<hr>', $html;