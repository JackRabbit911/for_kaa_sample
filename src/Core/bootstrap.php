<?php

if(static::enviroment() > TESTING)
{
    static::$errors = TRUE;
    static::$cache = FALSE;
    // echo 'Core<br>';
}
elseif(static::enviroment() > STAGING)
{
    static::$errors = TRUE;
    static::$cache = TRUE;
}