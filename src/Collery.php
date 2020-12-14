<?php 
namespace Collery;

use Collery\Exception\ColleryException;

use Lonfo\Walker;

class Collery{
    protected static $availabelConditions;

    /**
     * The array tree to search for
     *
     * @var string
     */
    protected $select;

    /**
     * The array data where to search in
     *
     * @var array
     */
    protected $data = [];
 

    /**
     * The array data where to search in
     *
     * @var string
     */
    protected $separator = '.';


    /**
     * The resultset after a search
     *
     * @var array
     */
    public    $resultSet = [];

    /**
     * The conditions to apply on data to filter resultset
     *
     * @var array
     */
    protected $conditions = [];

    /**
     * the sorter of resultset
     *
     * @var array
     */
    protected $sorters = [];


    /**
     * Initialize
     *
     * @param array $data
     * @param string $separator The array path separator
     */
    public function __construct($data = [],string $separator = '.'){
        self::loadConditions();
        $walkerName = Walker::class;

        if(!is_array($data) && !is_a($data,Walker::class)){
            throw new ColleryException("Collery accepts only {$walkerName} instances or array as first argument");
        }
        
        if(is_array($data)){
            $data = lonfo($data);
        }

        $this->data         = $data;
        $this->separator    = $separator;

        if(!defined('COLLERY_CURRENT_ITEM')){
            define('COLLERY_CURRENT_ITEM',$this->uniqidItemId());
        }
    }

    /**
     * Generatre a real uniq id for current item constant
     *
     * @param integer $lenght
     * @return string
     */
    protected function uniqidItemId($lenght = 20) {
        // uniqid gives 20 chars, but you could adjust it to your needs.
        if (function_exists("random_bytes")) {
            $bytes = random_bytes(ceil($lenght / 2));
        } elseif (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
        } else {
            throw new ColleryException("no cryptographically secure random function available");
        }
        return substr(bin2hex($bytes), 0, $lenght);
    }

    /**
     * traverse array and collect items
     *
     * @param string $path
     * @param Walker $subject
     * @return void
     */
    protected function traverse(string $path, $subject){

        $items      = $subject->xfind($path,$this->separator);

        if($items->iterable() && $items->count()){
            foreach($items->items() as $item){
                $allowed = ($this->applyConditions('and',$item) || $this->applyConditions('or',$item));
                if($allowed){
                    $this->resultSet[] = $item;
                }
            }
        }
        else{
            $this->resultSet[]=$items;
        }
    }

    /**
     * apply conditions on current item
     *
     * @param mixed $value
     * @return bool
     */
    protected function applyConditions(string $logicalOperator,$value){
        if($logicalOperator == 'and'){
            foreach($this->conditions as $condition){
                array_unshift($condition['params'],$value);
                if( $condition['logical'] == 'and' && !($condition['condition'])->apply(...$condition['params']) ){
                    return false;
                }
            }

            return true;
        }
        elseif($logicalOperator == 'or'){
            $allowed = [];
            foreach($this->conditions as $condition){
                array_unshift($condition['params'],$value);
                if( $condition['logical'] == 'or'){
                    $allowed[] = ($condition['condition'])->apply(...$condition['params']);
                }
            }
    
            return (in_array(true,$allowed));
        }
    }

    /**
     * Prepare the resulset to return;
     *
     * @return void
     */
    protected function prepare(){
        $selector = $this->select;
        if(!$selector){
            return $this->data;
        }

        $this->traverse($selector,$this->data);
        $this->sortResultset();
    }

    /**
     * Reset the query bilder, to perform anew query
     *
     * @return self
     */
    protected function reset(){
        $this->select       = null;
        $this->conditions   = [];
        $this->sorters      = [];
        $this->resultSet    = [];

        return $this;
    }

    /**
     * Collect all conditon classes available
     *
     * @return void
     */
    protected static function loadConditions(){
        if(is_null(self::$availabelConditions)){
            $files = glob(__DIR__.'/Conditions/*.php');
            foreach($files as $file){
                include($file);
                $clsName = "Collery\\Conditions\\".basename($file,'.php');
                foreach($clsName::$identifiers as $identifier){
                    self::$availabelConditions[$identifier] = $clsName;
                }
            }
        }
    }

    /**
     * call conditions
     *
     * @param string $name
     * @param array $args
     * @return self|Exception
     */
    public function __call($name,$args){
        if(array_key_exists($name,self::$availabelConditions)){
            $clsName = self::$availabelConditions[$name];
            $condition = new $clsName($this);
            $this->conditions[] = [
                'condition' =>$condition,
                'params'    =>$args,
                'logical'   =>$condition->getLogical()
            ];
        
            return $this;
        }
        else{
            throw new \BadMethodCallException("$name is not a valid method");
        }
    }

    /**
     * return current separator
     *
     * @return string
     */
    public function getSeparator(){
        return $this->separator;
    }

    /**
     * select tree
     *
     * @param string $path
     * @return self
     */
    public function select(string $path){
        $this->select = $path;
        return $this;
    }

    /**
     * returns the full data array
     *
     * @return array
     */
    public function all(){
        return $this->data;
    }

    /**
     * Returns the current resultset
     *
     * @return array
     */
    public function get(){
        $this->prepare();

        return $this->resultSet;
    }

    /**
     * Return first or N first items in the resultset
     *
     * @param integer $count
     * @return array
     */
    public function first($count = 1){
        $this->prepare();
        $total = count($this->resultSet);
        if($total == 0){
            return $this->resultSet;
        }

        $data = array_slice($this->resultSet,0,$count);
        
        return ($count == 1)?array_shift($data):$data;
    }

    /**
     * Return first or N first items in the resultset
     *
     * @param integer $count
     * @return array
     */
    public function last($count = 1){
        $this->prepare();
        $total = count($this->resultSet);
        if($total == 0){
            return $this->resultSet;
        }
        $data = array_slice($this->resultSet,$count*-1,$count);

        return ($count == 1)?array_shift($data):$data;
    }


    /**
     * Perform an arbitrary closure on each item in the resultset.
     *
     * @param Closure $closure
     * @return array
     */
    public function each(\Closure $closure){
        $this->prepare();
        foreach($this->resultSet as $key=>&$collectedItem){
            call_user_func_array($closure,[$collectedItem,$key,$this->resultSet]);    
        }
    
        return $this->resultSet;
    }

    /**
     * Sum all values in the resultset (if scalar values)
     *
     * @return int|float
     */
    public function sum(){
        $this->prepare();

        $result = 0;
        foreach($this->resultSet as $item){
            if(is_scalar($item)){
                $result+=$item;
            }
        }

        return $result;
    }

    /**
     * Split resultset into chucks of N items
     *
     * @param integer $perPage
     * @return array
     */
    public function paged($perPage = 15){
        $this->prepare();

        return array_chunk($this->resultSet,$perPage,false);
    }

    
    /**
     * Sort arrays results
     *
     * @param array $sort
     * @return self
     */
    public function sortBy(array $sort = []){
        foreach($sort as $key=>$orderType){
            $this->sorters[$key] = $orderType;
        }

        return $this;
    }

    /**
     * sort resultset
     *
     * @return void
     */
    protected function sortResultset(){
        // print_r($this->resultSet);
        $callbackValues = [];
        foreach($this->sorters as $sortKey=>$sortValue){
            $callbackValues[] = (new static($this->resultSet))->select("*.$sortKey")->get();
            $callbackValues[] = $sortValue;
        }

        if($callbackValues){
            // print_r($callbackValues);
            $callbackValues[]= &$this->resultSet;
            array_multisort(...$callbackValues);
        }
    }

    /**
     * return total count of items
     *
     * @return int
     */
    public function count(){
        $this->prepare();

        return count($this->resultSet);
    }
}

?>