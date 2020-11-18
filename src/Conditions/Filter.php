<?php 
namespace Collery\Conditions;

use Collery\Common\Condition;
use Collery\Exception\ConditionException;

class Filter extends Condition{

    public static $identifiers = ['filter'];

    public function apply(){
        $args   = func_get_args();
        $value  = array_shift($args);
        $args   = array_values($args);

        if(!$args || !is_callable($args[0])){
            throw new ConditionException("filter require a valid callback to use");
        }

        $callback = array_shift($args);
        
        array_unshift($args,$value);

        return (bool)call_user_func_array($callback,$args);
    }
}

?>