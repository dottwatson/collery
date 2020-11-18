<?php 
use Collery\Collery;


if(!function_exists('collery')){
    function collery(array $array, string $separator = '.'){
        return new Collery($array,$separator);
    }
}
?>