<?php
   // Anthill MySQL connection class
   // $Id: mysql.class.php,v 1.5 2002/07/24 21:35:21 vdanen Exp $
   //modify by andy  2007/2/13
class db_Mysql{
  // define connection info
  var $type   = "mysql";
  var $host   = "";
  var $dbase  = "";
  var $user   = "";
  var $passwd = "";

  // some config items
  var $debug         = 0; // 1=debug messages, 0=off
  // How to handle errors:
  // 0 = ignore errors
  // 1 = halt with message
  // 2 = ignore error, but print a warning
  var $halt_on_error = 1;
  var $auto_free     = 0; // 1 = auto mysql_free_result()

  // result array and current row number
  var $record = array();
  var $row    = "";

  // current error number and text
  var $errno  = 0;
  var $error  = "";

  // link and query handles
  var $link_id  = 0;
  var $query_id = 0;

 
		function db_Sql($query = "") {
			return $this->query($query);
		}
		function link_id() {
			return $this->link_id;
		}
		function query_id() {
			return $this->query_id;
		}

		function init_con( $host = "", $user = "", $passwd = "",$dbase = "") {

			$this->dbase=$dbase ;
			$this->host=$host;
		        $this->user=$user;;
			$this->passwd=$passwd ;
		}
	
		function create_con( $host = "", $user = "", $passwd = "",$dbase = "") {
		// defaults
			if($dbase == "") {
				$dbase = $this->dbase;
			}
			if($host == "") {
				$host = $this->host;
			}
			if($user == "") {
				$user = $this->user;
			}
			if($passwd == "") {
				$passwd = $this->passwd;
			}
			if($this->link_id == 0) {
				$this->link_id = mysql_connect($host, $user, $passwd);
				if(!$this->link_id) {
					#$this->halt("connect($host, $user, $passwd) failed.");
					return 0;
				}
				$this->query("set names 'utf8'");
				if(!@mysql_select_db($dbase,$this->link_id)) {
					$this->halt("cannot select database " . $dbase);
					return 0;
				}
			}
			return $this->link_id;
		}

		function free() {
			@mysql_free_result($this->query_id);
			$this->query_id = 0;
		}
		function connect($dbase = "", $host = "", $user = "", $passwd = "") {
			// defaults
			if($dbase == "") {
			$dbase = $this->dbase;
			}
			if($host == "") {
			$host = $this->host;
			}
			if($user == "") {
			$user = $this->user;
			}
			if($passwd == "") {
			$passwd = $this->passwd;
			}

			// make connection
			if($this->link_id == 0) {
			$this->link_id = mysql_connect($host, $user, $passwd);
			if(!$this->link_id) {
				$this->halt("connect($host, \$user, \$passwd) failed.");
				return 0;
			}
			if(!@mysql_select_db($dbase,$this->link_id)) {
				$this->halt("cannot select database " . $dbase);
				return 0;
			}
			
			}
			return $this->link_id;
  		}
		// perform a query
		function query($query_string) {
			if($query_string == "") {
			// no empty queries allowed
			return false;
			}
			if(!$this->connect()) {
			return false;
			}
			if($this->query_id) {
			// this is a new query so get rid of old results
			$this->free();
			}
			if($this->debug) {
			printf("Debug: query = %s<br>\n", $query_string);
			}

			$this->query_id = @mysql_query($query_string,$this->link_id);
			$this->row      = 0;
			$this->errno    = mysql_errno();
			$this->error    = mysql_error();
			if(!$this->query_id) {
			$this->halt("Invalid SQL: " . $query_string);
			return false;
			}
			return $this->query_id;
		}

		function affected_rows() {
			return @mysql_affected_rows($this->link_id);
		}

		function num_rows() {
			return @mysql_num_rows($this->query_id);
		}

		function num_fields() {
			return @mysql_num_fields($this->query_id);
		}

		function fetch_fieldname($num) {
			$temp = @mysql_fetch_field($this->query_id,$num);
			return $temp->name;
		}
		
	 /**added 12-13 yuanxingguo*/  
		function result($query, $row) {
		$query = @mysql_result($query, $row);
		return $query;
		}
		function get_insert_id(){
		//echo $this->link_id;
		//return ($this->link_id) ? @mysql_insert_id($this->link_id) : false;
		/**modified 12-13 yuanxingguo*/  
		return ($id = mysql_insert_id($this->link_id)) >= 0 ? $id : $this->result($this->query("SELECT last_insert_id()"), false );
		}
		function next_record() {
			if(!$this->query_id) {
			$this->halt("Next record called with no pending query.");
			return 0;
			}
			$this->record = @mysql_fetch_array($this->query_id,MYSQL_BOTH);
			$this->row   += 1;
			$this->errno  = mysql_errno();
			$this->error  = mysql_error();

			$stat = is_array($this->record);
			if(!$stat && $this->auto_free) {
			$this->free();
			}
			return $stat;
		}

		// error handling
		function halt($msg) {
			$this->error = @mysql_error($this->link_id);
			$this->errno = @mysql_errno($this->link_id);
			if($this->halt_on_error == "0") {
			return;
			}
			$this->haltmsg($msg);
			if($this->halt_on_error != "2") {
			die("Session halted.");
			}
		}

		function haltmsg($msg) {
			printf("<b>Database error:</b> %s<br>\n", $msg);
			printf("<b>MySQL Error</b>: %s (%s)<br>\n", $this->errno, $this->error);
		}

		function close()
		{
			if($this->link_id == 0)
				return 0;
			return @mysql_close($this->link_id);
		}

}
?>
