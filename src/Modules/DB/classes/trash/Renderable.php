<?php
namespace WN\DB;

interface Renderable
{
    public function render($prepare_mode = null, $strict = null);

    public function params();
}