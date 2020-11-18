<?php 
namespace Collery\Conditions;

use Collery\Common\Condition;
use Collery\Conditions\Where;

class orWhere extends Condition{

    protected $logicalOperation = 'or';

    public static $identifiers = ['orWhere'];

    public function apply(){
        $args       = func_get_args();
        $result     = (new Where($this->builder))->apply(...$args);

        return $result;
    }
}

?>