<?php namespace pixabayapi\lib;

/**
 * Pixabay Video Class
 * 
 * Encapsulates a video result.
 * 
 * @author Martyr2
 * @copyright 2021 Martyr2
 * @link https://www.coderslexicon.com
 * 
 */

use pixabayapi\lib\PixabayException;
use stdClass;

class PixabayVideo 
{
    private $videoObj = null;
    
    public function __construct(stdClass $videoResult) 
    {
        $this->videoObj = $videoResult;
    }

    public function __isset(string $propName)
    {
       return isset($this->videoObj->{$propName});
    }

    public function __get(string $propName) 
    {
        if (property_exists($this->videoObj, $propName)) {
            return $this->videoObj->{$propName};
        }

        $trace = debug_backtrace();
        trigger_error("Undefined property {$propName} in {$trace[0]['file']} on line {$trace[0]['line']}", E_USER_NOTICE);

        return null;
    }
}
