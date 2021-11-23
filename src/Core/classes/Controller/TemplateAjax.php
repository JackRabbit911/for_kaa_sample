<?php
namespace WN\Core\Controller;

/**
 * Description of Template
 *
 * @author JackRabbit
 */
abstract Class TemplateAjax extends Template
{
    /**
     * array of template blocks, which have changed in this controller
     * 
     * @var array
     */
    protected $vars = [];

    /**
     * unserialised array of form fields, recived by ajax by function serializeArray()
     * 
     * @var array
     */

    protected function _after()
    {
        if($this->request->is_ajax())
        {
            $data = $this->template->get();

            if(is_array($data))
                echo json_encode($data);
            else echo $data;
        }
        else
        {
            echo $this->template->render();
        }
    }

    protected function json_decode_recursive($json)
    {
        if(is_array($json))
        {
            foreach($json AS $key=>$value)
            {
                $array[$key] = $this->json_decode_recursive($value);
            }
            return $array;
        }
        else
        {
            $array = json_decode($json, TRUE);
            if($array !== NULL) return $array;
            else return $json;
        }
    }
}