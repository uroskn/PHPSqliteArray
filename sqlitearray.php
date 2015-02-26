<?php

  /**
   *  SQLiteArray class
   *  Acting as PHP array but using SQLite as it's storage
   *  Usefull when you're dealing with more data than allowed by PHP
   *  
   *  Written by Uroš Knupleš <uros@knuples.net>
   *  Copyright and all that hardcore shit 2014
   *
   *  Distributed under WTFPL licence
   **/

  class SQLiteArray implements ArrayAccess, Iterator
  {
    // ============ Some misc functions ============ 
    
    static function CopyFromArray($array, $filename = null)
    {
      $obj = new SQLiteArray($filename);
      foreach ($array as $k => $v) $obj[$k] = $v;
      return $obj;
    }
  
    // ============ Main ============ 
  
    protected $handle;
    protected $filename;

    function __construct($filename = null)
    {
      $this->filename = $filename;
      if (!$this->filename) $this->filename = tempnam("", "phpsqlitearray-");
      $this->handle = $this->InitDB();
      $this->handle->exec("DROP TABLE IF EXISTS data");
      $this->handle->exec("CREATE TABLE data (".
                          "  key     TEXT PRIMARY KEY,".
                          "  key_str BLOB,".
                          "  value   BLOB".
                          ");");
    }
    
    function __destroy()
    {
      unlink($this->filename);
    }
    
    // Yes, serialization friendly as long as file remains in same place.
    function __sleep() { return array("filename"); }
    function __wakeup() { $this->handle = $this->InitDB(); }
    
    protected function InitDB()
    {
      $handle = new SQLite3($this->filename);
      $handle->exec("PRAGMA synchronous = OFF");
      $handle->exec("PRAGMA journal_mode = OFF");
      $handle->exec("PRAGMA cache_size = 1");
      return $handle;
    }

    protected function HashKey(&$key, $acceptnull = false)
    {
      if (($acceptnull) && (is_null($key))) $key = (int)$this->handle->querySingle("SELECT MAX(rowid) FROM data");
      if ((is_scalar($key)) && (!is_resource($key))) return sha1(serialize($key));
      throw new Exception("Key must be scalar!"); 
    }
    
    protected function GetRawValue($hash)
    {
      return base64_decode($this->handle->querySingle("SELECT value FROM data WHERE key = '$hash'"));
    }
    
    // ============ ArrayAccess ============ 
    
    public function offsetSet($key, $value)
    {
      $hash = $this->HashKey($key, true);
      $stmt = $this->handle->prepare("INSERT OR REPLACE INTO data (key, key_str, value) VALUES (:hash, :key, :value);");
      $stmt->bindValue(":hash",  $hash);
      $stmt->bindValue(":key",   serialize($key));
      $stmt->bindValue(":value", base64_encode(serialize($value)));
      $stmt->execute();
    }
    
    public function offsetExists($key)
    {
      return ($this->GetRawValue($this->HashKey($key)) != null);
    }
    
    public function offsetUnset($key)
    {
      $hash = $this->HashKey($key);
      $this->handle->exec("DELETE FROM data WHERE key = '$hash'");
    }
    
    public function offsetGet($key)
    {
      return unserialize($this->GetRawValue($this->HashKey($key))); 
    } 
    
    // ============ Iterator implementation ==============
    
    protected $indexno;
    
    protected $iter_keystr;
    protected $iter_value;
    
    protected $iter_valid; 
    
    public function current() { return $this->iter_value; }
    public function key() { return $this->iter_keystr; }
    public function rewind() { $this->indexno = -1; $this->next(); }
    public function valid() { return $this->iter_valid; }
    
    public function next() 
    { 
      $this->indexno++; 
      $index = $this->indexno;
      $data  = $this->handle->querySingle("SELECT key_str,value FROM data LIMIT $index,1", true);
      if (!@$data["key_str"])
      {
        $this->iter_valid = false;
        return;
      } 
      $this->iter_valid  = true;
      $this->iter_keystr = unserialize($data["key_str"]);
      $this->iter_value  = unserialize(base64_decode($data["value"]));
    }
    
  }

