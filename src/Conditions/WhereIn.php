<?php 
namespace Collery\Conditions;

use Collery\Common\Condition;
use Collery\Exception\ConditionException;
use Collery\Collery;

/**
 * check if subject is in array set
 * 
 * whereIn(subject,dataSet)
 */
class WhereIn extends Condition{

    public static $identifiers = ['whereIn'];

    public function apply(){
        $args       = func_get_args();
        $value      = array_shift($args);
        $target     = array_shift($args);        
        
        if($target != COLLERY_CURRENT_ITEM){
            $value = (
                new Collery($value,$this->builder->getSeparator())
                )->select($target)->first();
        }
        $dataSet    = array_shift($args);

        if(!is_array($dataSet)){
            throw new ConditionException("whereIn requires an array as haystack to search");
        }

        $key        = array_shift($args);
        $value      = (new Collery($value))->select($key)->first();

        return in_array($value,$dataSet);
    }
}

?>