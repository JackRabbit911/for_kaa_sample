<?php

use WN\Image\Image;

// $image = new Image(1);
// var_dump($image); exit;

if(!empty($collection = Image::collection()))
{
    $attr = ['height'=>'100%', 'hspace'=> 2]; // ['class' => 'img-thumbnail'];

    echo '<h5>original</h5>';
    echo '<div style="height: 200px;">';
    foreach($collection as $img) if($img->is_file) echo $img->html($attr);
    echo '</div>';
    echo '<hr><h5>image</h5>';
    foreach($collection as $img) echo $img->html($attr, 'image', null, 200);
    echo '<hr><h5>square</h5>';
    foreach($collection as $img) echo $img->html($attr, 'square', 100);
    echo '<hr><h5>thumbnail</h5>';
    foreach($collection as $img) echo $img->html($attr, 'thumb');
    echo '<hr><h5>avatar</h5>';
    foreach($collection as $img) echo $img->html($attr, 'avatar');
    echo '<hr><h5>base64</h5>';
    foreach($collection as $img) echo $img->html($attr, 'base64', null, 100);
}
else echo '<h5>Collection is empty!</h5>';