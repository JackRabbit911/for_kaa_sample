<?php
use WN\Core\Request;
use WN\User\User;
use WN\Core\Helper\HTML;

$user = User::auth();

echo '<div class="row h-100 justify-content-center align-items-center">';
if($user->role() > ROLE_GUEST)
{
    echo __('Hello').', '.$user->name().'! <br>'
    .HTML::anchor('~user/logout', 'Выйти');
}
else 
{   
    echo Request::factory('/~user/login')->execute();   
}
echo '</div>';