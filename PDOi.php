<?php
class PDOi extends PDO {

  public function __construct($dsn, $username='', $password='', $driver_options=array()) {
    $driver_options[PDO::ATTR_STRINGIFY_FETCHES] = false;  // Always return results in native PHP data types
    if(!defined('PDO_SUPPORT_DELAYED') || PDO_SUPPORT_DELAYED == false)
      $driver_options[PDO::ATTR_EMULATE_PREPARES] = false; // Turn off emulate prepares which is required for the above, but breaks multiple-named parameters and breaks insert delayed
    else
      $driver_options[PDO::ATTR_EMULATE_PREPARES] = true;  // Turning this on will prevent data types from being returned in native formats (everything will be returned as a string)
    parent::__construct($dsn, $username, $password, $driver_options);
  }

  public function prepare($sql) {
    $stmt = new PDOiStatement();

    // Parse out the named parameters if there are any and store their positions and orders
    if(preg_match_all('/:[a-zA-Z0-9_]+/', $sql, $matches)) {
      # echo "Found parameters\n";
      # print_r($matches);
      foreach($matches[0] as $i=>$m) {
        $stmt->map($m, $i+1);
      }
    }
    
    // Convert all named parameters to question marks
    $sql = preg_replace('/:[a-zA-Z0-9_]+/', '?', $sql);
    
    # echo 'New SQL: ' . $sql."\n";
    $PDOstmt = parent::prepare($sql);
    if($PDOstmt == FALSE) { 
      $error = $this->errorInfo();
      throw new PDOiException('Database Error: ' . $error[2]);
    }
    return $stmt->set($PDOstmt);
  }
  
  public function execute() {
    return parent::execute();
  }  

}

class PDOiStatement {
  private $_stmt;
  private $_map = array();

  public function set($stmt) {
    $this->_stmt = $stmt;
    return $this;
  }
  
  public function __call($name, $args) {
    return call_user_func_array(array($this->_stmt, $name), $args);
  }
  
  public function __get($key) {
    return $this->_stmt->$key;
  }

  public function map($name, $number) {
    $this->_map[$name][] = $number;
  }

  public function bindParam($parameter, &$variable, $data_type=PDO::PARAM_STR, $length=FALSE, $driver_options=FALSE) {
    $parameters = FALSE;
    if(preg_match('/:[a-zA-Z0-9_]+/', $parameter))
      $parameters = $this->_name_to_position($parameter);

    if(is_array($parameters)) {
      foreach($parameters as $p) {
        # echo "Binding '$value' to $p\n";
        $this->_stmt->bindParam($p, $variable, $data_type, $length, $driver_options);
      }
    } else {
      return $this->_stmt->bindParam($parameter, $variable, $data_type, $length, $driver_options);
    }
  }
  
  public function bindValue($parameter, $value, $data_type=PDO::PARAM_STR) {
    $parameters = FALSE;
    if(preg_match('/:[a-zA-Z0-9_]+/i', $parameter))
      $parameters = $this->_name_to_position($parameter);

    if(is_array($parameters)) {
      foreach($parameters as $p) {
        #echo "Binding '$value' to $p\n";
        $this->_stmt->bindValue($p, $value, $data_type);
      }
    } else {
      return $this->_stmt->bindValue($parameter, $value, $data_type);
    }
  }
  
  private function _name_to_position($name) {
    return array_key_exists($name, $this->_map) ? $this->_map[$name] : 0;
  }

}

class PDOiException extends Exception {

}
