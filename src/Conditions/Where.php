<?php 
namespace Collery\Conditions;

use BadMethodCallException;
use Collery\Common\Condition;
use Collery\Exception\ConditionException;
use Collery\Collery;
use Lonfo\Walker;

/**
 * Genertic where condition
 */
class Where extends Condition{

    public static $identifiers  = ['where'];
    
    protected $operators = [
        //scalar operators
        '='     =>'equal',
        'eq'    =>'equal',

        '!='    =>'notEqual',
        '<>'    =>'notEqual',
        'notEq' =>'notEqual',

        '<'     =>'minor',
        'lwr'   =>'minor',

        '>'     =>'major',
        'gt'    =>'major',
        
        '<='    =>'minorEqual',
        'lwrEq' =>'minorEqual',

        '>='    =>'majorEqual',
        'gtEq'  =>'majorEqual',

        'odd'   =>'odd',
        'even'  =>'even',

        'match'     =>'match',
        'notMatch'  =>'notMatch',

        //generic operator
        'is'    => 'getType',
        'empty' => 'isEmpty',

        //objects operator
        'instanceOf' => 'isInstance',

        //array operators
        'has'       => 'itemHas',
        'hasNot'    => 'itemHasNot'

    ];

    public function apply(){
        $args           = func_get_args();
        $value          = array_shift($args);
        $target         = array_shift($args);        
        $targetValue    = $value;

        //nested rules
        if(is_object($target) && is_a($target,\Closure::class)){
            $nested = new Collery([$targetValue],$this->builder->getSeparator());
            call_user_func($target,$nested);
            $nestedResults = $nested->select('*')->get();

            return !empty($nestedResults);
        }
        elseif($target != COLLERY_CURRENT_ITEM){
            $targetValue = $value->xfind($target,$this->builder->getSeparator());
        }


        $operator   = (string)array_shift($args);
        $compared   = array_shift($args);

        if(array_key_exists($operator,$this->operators)){
            $operatorFn = $this->operators[$operator];            
            return call_user_func(
                [$this,$operatorFn],
                (is_a($targetValue,\Lonfo\Value::class) || is_a($targetValue,\Lonfo\Walker::class))
                    ?$targetValue->primitiveValue()
                    :$targetValue,$compared
            );
        }
        else{
            throw new BadMethodCallException("Undefined operator `{$operator}`");
        }

    }

    /**
     * Check if value is equal to compared
     *
     * @param mixed $value
     * @param mixed $compared
     * @return bool
     */
    protected function equal($value,$compared){
        return $value == $compared;
    }

    /**
     * Check if value is  not equal to compared
     *
     * @param mixed $value
     * @param mixed $compared
     * @return bool
     */
    protected function notEqual($value,$compared){
        return $value != $compared;
    }

    /**
     * Check if value is empty as decribed in https://www.php.net/manual/en/function.empty.php
     *
     * @param mixed $value
     * @return bool
     */
    public function isEmpty($value){
        return empty($value);
    }

    /**
     * Check if value is minor then compared
     *
     * @param mixed $value
     * @param mixed $compared
     * @return bool
     */
    protected function minor($value,$compared){
        if(!is_scalar($value)){
            return false;
        }

        return $value < $compared;
    }

    /**
     * Check if value is major of compared
     *
     * @param mixed $value
     * @param mixed $compared
     * @return bool
     */
    protected function major($value,$compared){
        if(!is_scalar($value)){
            return false;
        }

        return $value > $compared;
    }

    /**
     * Check if value is minor equal of compared
     *
     * @param mixed $value
     * @param mixed $compared
     * @return bool
     */
    protected function minorEqual($value,$compared){
        if(!is_scalar($value)){
            return false;
        }

        return $value <= $compared;
    }

    /**
     * Check if value is major equal of compared
     *
     * @param mixed $value
     * @param mixed $compared
     * @return bool
     */
    protected function majorEqual($value,$compared){
        if(!is_scalar($value)){
            return false;
        }

        return $value >= $compared;
    }

    /**
     * Check if value is odd
     *
     * @param  $value
     * @return bool
     */
    protected function odd($value){
        if(!is_numeric($value)){
            return false;
        }

        return $value%2 == 0;
    }

    /**
     * Check if value is event
     *
     * @param  $value
     * @return bool
     */
    protected function even($value){
        if(!is_numeric($value)){
            return false;
        }

        return $value%2 > 0;
    }

    /**
     * Returns the type of value
     * as described in https://www.php.net/manual/en/function.gettype
     *
     * @param mixed $value
     * @param string $compared
     * @return bool
     */
    public function getType($value,$compared){
        if(!is_string($compared)){
            $compared = gettype($compared);            
        }

        return gettype($value) === $compared;
    }

    /**
     * Perform regula espression on value
     *
     * @param string|int|float $value
     * @param string $regexp
     * @return bool|ConditionException
     */
    public function match($value,$regexp){
        if(!is_scalar($value)){
            return false;
        }

        try{    
            return (bool)preg_match($regexp,$value);
        }
        catch(\Exception $e){
            throw new ConditionException($e->getMessage());
        }
    }

    /**
     * Perform regula espression on value
     *
     * @param string|int|float $value
     * @param string $regexp
     * @return bool|ConditionException
     */
    public function notMatch($value,$regexp){
        if(!is_scalar($value)){
            return false;
        }

        try{
            return !preg_match($regexp,$value);
        }
        catch(\Exception $e){
            throw new ConditionException($e->getMessage());
        }
    }


    /**
     * Check if item is instance of class
     *
     * @param mixed $value
     * @param  string $clsName
     * @return boolean
     */
    protected function isInstance($value,$clsName){
        return (is_object($value) && is_a($value,$clsName,false));
    }

    /**
     * check if object has property or array has key
     *
     * @param mixed $value
     * @param string $key
     * @return bool
     */
    protected function itemHas($value,$key){
        if(!is_array($value) || !is_object($value)){
            return false;
        }

        $tmp = is_object($value)
            ?json_decode(json_encode($value),true)
            :$value;

        $exists = (
            new Collery($tmp,$this->builder->getSeparator())
        )->select($key)->first();

        return $exists !== null;
    }


    /**
     * check if object has not property or array has key
     *
     * @param mixed $value
     * @param string $key
     * @return bool
     */
    protected function itemHasNot($value,$key){
        if(!is_array($value) || !is_object($value)){
            return false;
        }

        $tmp = is_object($value)
            ?json_decode(json_encode($value),true)
            :$value;

        $exists = (
            new Collery($tmp,$this->builder->getSeparator())
        )->select($key)->first();

        return $exists === null;
    }

}

?>