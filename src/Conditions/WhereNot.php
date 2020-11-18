<?php 
namespace Collery\Conditions;

use Collery\Common\Condition;
use Collery\Conditions\Where;

class WhereNot extends Condition{

    public static $identifiers = ['whereNot'];

    public function apply(){
        $args       = func_get_args();

        $result = (new Where($this->builder))->apply(...$args);

        return !$result;
    }
}

?>