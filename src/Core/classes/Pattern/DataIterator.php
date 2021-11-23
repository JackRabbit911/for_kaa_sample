<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace WN\Core\Pattern;

/**
 * Description of DataIterator
 *
 * @author JackRabbit
 */
class DataIterator implements \Iterator
{
    protected $data = [];
    
    public function rewind()
    {
        reset($this->data);
    }
  
    public function current()
    {
        return current($this->data);
    }
  
    public function key() 
    {
        return key($this->data);
    }
  
    public function next() 
    {
        return next($this->data);
    }
  
    public function valid()
    {
        $key = key($this->data);
        return ($key !== NULL && $key !== FALSE);
    }
    
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }
    
    public function __get($key)
    {
        return (array_key_exists($key, $this->data)) ? $this->data[$key] : NULL;
    }
}
