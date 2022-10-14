<?php

namespace App\AmqpWrapper;

use PDO;

class Database
{
  private $pdo;
  private $sQuery;
  private $bConnected = false;
  private $log;
  private $parameters;
  private $database;

  public function __construct($database = "test.db")
  {
    $this->database = $database;

    $this->query("CREATE TABLE IF NOT EXISTS `jobs`(
      id          TEXT PRIMARY KEY NOT NULL, 
      jobname     TEXT, 
      jobdesc     TEXT, 
      startedAt   TEXT,
      completedAt TEXT);
    ");
  }

  private function Connect()
  {
    try {
      $this->pdo = new PDO($dsn = 'sqlite:' . $this->database);
      $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
      $this->bConnected = true;
    }
    catch (PDOException $e)
    {
      echo $this->ExceptionLog($e->getMessage());
      die();
    }
  }
     
  private function Init($query,$parameters = "")
  {
    if(!$this->bConnected) { 
      $this->Connect(); 
    }
    
    try {
      $this->sQuery = $this->pdo->prepare($query);
      $this->bindMore($parameters);

      if(!empty($this->parameters)) {
        foreach($this->parameters as $param)
        {
          $parameters = explode("\x7F",$param);
          $this->sQuery->bindParam($parameters[0],$parameters[1]);
        }                
      }

      $this->succes = $this->sQuery->execute();                
    }
    catch(PDOException $e)
    {
      echo $this->ExceptionLog($e->getMessage(), $query );
      die();
    }

    $this->parameters = array();
  }
       
  public function bind($para, $value)
  {        
    $this->parameters[sizeof($this->parameters)] = ":" . $para . "\x7F" . $value;
  }

  public function bindMore($parray)
  {
    if(empty($this->parameters) && is_array($parray)) {
      $columns = array_keys($parray);
      foreach($columns as $i => &$column) {
        $this->bind($column, $parray[$column]);
      }
    }
  }
                      
  public function query($query,$params = null,$fetchmode = PDO::FETCH_ASSOC,$fetchclass = null)
  {
    $query = trim($query);

    $this->Init($query,$params);

    if (stripos($query, 'select') === 0){
      if ($fetchmode == 8) {
        return $this->sQuery->fetchAll(PDO::FETCH_CLASS, $fetchclass);
      } else {
        return $this->sQuery->fetchAll($fetchmode);
      }
    } elseif (stripos($query, 'insert') === 0 || stripos($query, 'update') === 0 || stripos($query, 'delete') === 0) {
      return $this->sQuery->rowCount();        
    } else {
      return NULL;
    }
  }
      
  public function lastInsertId() 
  {
    return $this->pdo->lastInsertId();
  }        

  public function column($query,$params = null)
  {
    $this->Init($query,$params);
    $Columns = $this->sQuery->fetchAll(PDO::FETCH_NUM);                
    
    $column = null;

    foreach($Columns as $cells) {
            $column[] = $cells[0];
    }

    return $column;
  }        
       
  public function row($query,$params = null,$fetchmode = PDO::FETCH_ASSOC)
  {                                
    $this->Init($query,$params);
    return $this->sQuery->fetch($fetchmode);                        
  }

  public function single($query,$params = null)
  {
    $this->Init($query,$params);
    return $this->sQuery->fetchColumn();
  }

  private function ExceptionLog($message , $sql = "")
  {
    $message = "Unhandled Exception from PDO-DB-Class: ".$message." |||| Raw SQL: ".$sql;
    $message = trim(preg_replace('/\s\s+/', ' ', $message));
    error_log($message,0); 
    
    return $message;
  }                        
}
?>