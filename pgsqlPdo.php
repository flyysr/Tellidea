<?php
$config = include_once './config.php';

class pgsqlPdo extends PDO{

    public $pdoSts;
    private $table;
    private $preparedSql;
    private $value;
    private $method;

    public function __construct($dsn, $username, $passwd, $options)
    {
        parent::__construct($dsn, $username, $passwd, $options);
    }

    /**
     * convert the array to sql string
     * @param array $arr
     * @return string
     * in: array('cid'=>1, 'aid'=>2)
     * out: cid='1',aid='2'
     */
    private function update2sql($arr) {
        $s = '';
        foreach($arr as $k=>$v) {
            $s .= "$k=?,";
        }
        return rtrim($s, ',');
    }
    private function select2sql($arr) {
        $s = '';
        foreach($arr as $k=>$v) {
            $v = addslashes($v);
            $s .= "$v,";
        }
        return rtrim($s, ',');
    }
    private function where2sql($arr) {
        $s = '';
        foreach($arr as $k=>$v) {
            $s .= "$k=? AND ";
        }
        return rtrim($s, 'AND ');
    }
    private function insert2sql($arr) {
        $fields = ' (';
        $vals = '(';
        foreach($arr as $k=>$v) {
//            $v = addslashes($v);
            $fields .= $k .',';

            $type = gettype($v);
            if($type == 'integer' || $type == 'double'){
                $vals .= $v . ',';
            }else{
                $vals .= '\'' . $v . '\',';
            }
        }
        $fields = rtrim($fields, ',');
        $vals = rtrim($vals, ',');
        $fields .=')';
        $vals .=')';
        return $fields . 'VALUES' . $vals;
    }

    private function arr2val($arr) {
        $s = [];
        foreach($arr as $k=>$v) {
            $s[] = $v;
        }
        return $s;
    }

    public function select($arr){
        $this->method = 'select';
        $type = gettype($arr);
        if($type == 'string' && $arr =='*'){
            $str = '*';
        }else{
            $str = $this->select2sql($arr);
        }
        $this->preparedSql .= "select $str from TABLE ";
        return $this;
    }

    public function insert($arr){
        $this->method = 'insert';
        $str = $this->insert2sql($arr);
        $this->value = $this->arr2val($arr);
        $this->preparedSql = "insert into TABLE $str";
        return $this;
    }

    public function update($arr){
        $this->method = 'update';
        $str = $this->update2sql($arr);
        $this->value = $this->arr2val($arr);
        $this->preparedSql = "update TABLE set $str";
        return $this;
    }

    public function delete(){
        $this->method = 'delete';
        $this->preparedSql = 'delete from TABLE ';
        return $this;
    }

    public function where($arr){
        $str = $this->where2sql($arr);
        $this->preparedSql .= " where $str";
        $val = $this->arr2val($arr);
        foreach ($val as $v){
            $this->value[] = $v;
        }
        return $this;
    }

    public function exc($table)
    {
        $this->table = $table;
        try {
            $this->preparedSql = str_replace('TABLE', $table, $this->preparedSql);
            $this->pdoSts = parent::prepare($this->preparedSql);
            if($this->method == 'insert'){
                $res = $this->pdoSts->execute();
            }elseif($this->method == 'update'){
                $res = $this->pdoSts->execute($this->value);
            }elseif($this->method == 'select'){
                $this->pdoSts->execute($this->value);
                $res = $this->pdoSts->fetchAll(parent::FETCH_ASSOC);
            }else{
                $this->pdoSts->execute($this->value);
                return $this->pdoSts->rowCount();
            }
        } catch (PDOException $e) {
            return  "error :".$e->getMessage();
        }
        return $res;
    }

    public function debug(){
        $this->pdoSts->debugDumpParams();
    }

    public function getId(){
        $seq = $this->table . '_id_seq';
        return $this->lastInsertId($seq);
    }
}
$dsn = $config['dbtype'] . ':host='.$config['host'].';dbname='.$config['dbname'].';port='.$config['port'];
$options =  array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
$pdo = new pgsqlPdo($dsn, $config['username'], $config['password'], $options);

echo "<pre>";
header('content-type: text/html ;charset=utf8');
echo "<pre>";
//select
$res = $pdo->select('*')->where(['id' => 3])->exc('users');

//update
//$res = $pdo->update([
//    'age' => 24,
//])->where(['id'=>3])->exc('users');

/*insert*/
//$res = $pdo->insert([
//    'name' => 'fanglang',
//    'age' => 27,
//])->exc('users');

/*delete*/
//$res = $pdo->delete()->where(['age'=>27])->exc('users');

$err = $pdo->errorInfo();
$errcode = $pdo->errorCode();
$insertId = $pdo->getId();
//$affectRows = $pdo->
var_dump($err);
var_dump($errcode);
//foreach ($res as $v){
//    var_dump($v);
//}
var_dump($res);
$pdo->debug();
var_dump($insertId);