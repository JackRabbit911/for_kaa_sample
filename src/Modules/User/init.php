<?php
use WN\Core\Route;

const ROLE_GUEST = 0;
const ROLE_SUBUSER = 10;
const ROLE_USER = 20;
const ROLE_MEMBER = 30;
const ROLE_AUTHOR = 40;
const ROLE_MODERATOR = 50;
const ROLE_MANAGER = 60;
const ROLE_ADMIN = 70;
const ROLE_SUPERADMIN = 80;
const ROLE_OWNER = 90;
const ROLE_ROOT = 99;

Route::set('~user', '~user/<action>(/<view>)(?<query>)')
    ->defaults([
        'controller' => 'WN\User\Controller\User',
    ]);