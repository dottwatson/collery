<?php 
namespace Collery\Common;

use Collery\Collery;

abstract class Condition{

    protected $subject;
    protected $result;
    protected $builder;
    protected $internalBuilder;
    protected $data = [];

    protected $logicalOperation = 'and';

    public static $identifier = '';

    public function __construct(Collery $builder){
        $this->builder          = $builder;
        $this->internalBuilder  = new Collery($this->builder->all());
    }

    abstract public function apply();

    public function getLogical(){
        return $this->logicalOperation;
    }

}

?>