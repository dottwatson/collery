<?php 
namespace Collery\Conditions;

use Collery\Common\Condition;
use Collery\Conditions\WhereNot;

class orWhereNot extends Condition{

    protected $logicalOperation  = 'or';
    public static $identifiers = ['orWhereNot'];

    public function apply(){
        $args       = func_get_args();
        $result     = (new WhereNot($this->builder))->apply(...$args);

        return $result;
    }
}

?>