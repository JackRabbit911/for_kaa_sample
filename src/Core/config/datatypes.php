<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

return [
    'username'     => ['regexp', '/^[\w\s-@.]+$/u'],
    'password'     => ['regexp', '/^[\w\s-@.]*$/u'],
    'email'        => ['filter', FILTER_VALIDATE_EMAIL],
    'integer'      => ['filter', FILTER_VALIDATE_INT],
    'alpha_num'    => ['regexp', '/^[a-zA-Z0-9]+$/'],
    'alpha_utf8'   => ['regexp', '/^[\pL]+$/u'],
    'alpha_num_utf8'=>['regexp', '/^[\w]+$/u'],
    'phone'        => ['regexp', '/^[\+\s\d-()]{3,20}$/'],
    'phone_strict' => ['regexp', '/^[\d]{11,11}$/'],
    // 'date'         => ['check_date'],
];

