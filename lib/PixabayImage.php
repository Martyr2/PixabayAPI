<?php namespace pixabayapi\lib;

/**
 * Pixabay Image Class
 * 
 * Encapsulates an image result.
 * 
 * @author Martyr2
 * @copyright 2021 Martyr2
 * @link https://www.coderslexicon.com
 * 
 */

use pixabayapi\lib\PixabayException;
use stdClass;

class PixabayImage 
{
    private $imageObj = null;
    
    public function __construct(stdClass $imageResult) 
    {
        $this->imageObj = $imageResult;
    }

    public function __isset(string $propName)
    {
       return isset($this->imageObj->{$propName});
    }

    public function __get(string $propName) 
    {
        if (property_exists($this->imageObj, $propName)) {
            return $this->imageObj->{$propName};
        }

        $trace = debug_backtrace();
        trigger_error("Undefined property {$propName} in {$trace[0]['file']} on line {$trace[0]['line']}", E_USER_NOTICE);

        return null;
    }
}
