<?php



class exportPsqlToMySQL 
{
	/*
		Being Thankfull Towards Knowledge is extending it. 
		-Inspired By A Qoute From *ALI EBN ABITALEB*
	*/
	private $class_name;
	private $version;
	public $DBConnection;
	public $psql_username;
	public $psql_password;
	public $psql_database;
	public $psql_host;
	public $psql_port;
	public $psql_uri;
	public $drop_tables;
	public $char_set;
	public $time_zone;
	public $foreign_key_checks;
	public $sql_mode;


	public function __construct() 
	{
		$this->class_name = 'exportPsqlToMySQL';
		$this->version = '1.0';

		// PLACE YOUT DB CREDENTIALS

		$this->psql_username = '';
		$this->psql_password = '';
		$this->psql_database = '';
		$this->psql_host = '';
		$this->psql_port = '';

		// LEAVE ABOVE PARAMETERS EMPTY IF YOU ENTER `psql_uri`
		$this->psql_uri = "";

		$this->drop_tables = TRUE;
		$this->char_set = 'utf8mb4';
		$this->time_zone = '+00:00';
		$this->foreign_key_checks = '0';
		$this->sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
		if (!empty($this->psql_uri)) 
		{
			$this->DBConnection =  pg_connect($this->psql_uri) or die('connection failed, check URI'.PHP_EOL);
		}
		else 
		{
			$connectionString = "host=" . $this->psql_host . " port=" . $this->psql_port . " dbname=" . $this->psql_database . " user=" .$this->psql_username . " password=" . $this->psql_password . " sslmode=require";
			$this->DBConnection =  pg_connect($connectionString) or die('connection failed, check parameters'.PHP_EOL);
		}
		(empty($this->psql_database)) ? $this->psql_database = $this->getCurrentDB() : '' ;

	}
	public function getCurrentDB()
	{
		$DBNameQS = "SELECT current_database()";
		$result = pg_query($this->DBConnection, $DBNameQS);
		$row = pg_fetch_assoc($result);
		return $row['current_database'];
	}
	public function createDropTableString($table_name) 
	{
		return PHP_EOL . "DROP TABLE IF EXISTS `".$table_name."`;".PHP_EOL;
	}
	public function returnColumnsArray($table_name) 
	{
		$selectQS = "SELECT 
		   * 
		FROM 
		   information_schema.columns
		WHERE 
		   table_name = '" . $table_name . "';";
		   $result = pg_query($this->DBConnection, $selectQS);
		   $rows = pg_fetch_all($result);
		   return $rows;
	}
	public function createTableStructureString($table_name) 
	{
		$string = "CREATE TABLE `" . $table_name . "` (".PHP_EOL;
		$col_names = $this->returnColumnsArray($table_name);
		$total_col_num = count($col_names); 

		$num = 1;
		foreach ($col_names as $key => $value) 
		{
			//print_r($key);
			//print_r($value);
			//exit();
			
			if ($value['column_name']) 
			{
				$string .= "\t `". $value['column_name'] . "` ";
			}//data_type
			if ($value['data_type']) 
			{
				$string .= $value['data_type'];
			}
			if ($value['character_maximum_length'] && !empty($value['character_maximum_length'])) 
			{
				$string .= "(" . $value['character_maximum_length'] . ") ";
			}
			else 
			{
				$string .= " ";
			}

			if ($value['is_nullable'] && strtolower($value['is_nullable']) == 'no') 
			{
				$string .= " NOT NULL ";
			}
			//BY DEFAULT
			if ($value['identity_generation'] && $value['identity_generation'] == 'BY DEFAULT') 
			{
				$string .= "AUTO_INCREMENT ";
			}
			if ($value['is_identity'] == 'YES') 
			{
				$primaryKeyString = PHP_EOL . "\t PRIMARY KEY (" . $value['column_name'] . ")".PHP_EOL . ") ";
			}
			if ((int)$num == (int)$total_col_num) 
			{
				if (!empty($primaryKeyString)) 
				{
					$string .=  ", ";
					$string .= $primaryKeyString;
				}
				else 
				{

					$string .= PHP_EOL . " ) ";	
				}
				$string .= "ENGINE=InnoDB DEFAULT CHARSET=".$this->char_set.";" . PHP_EOL;
			}
			else 
			{
				$string .=  ", " . PHP_EOL;	
			}


			$num++;
		}
		return $string;

	}

	public function createTableInsertString($table_name) 
	{
		$string = "INSERT INTO `" . $table_name . "` ";
		$col_names = $this->returnColumnsArray($table_name);
		$total_col_num = count($col_names);
		$num = 1;
		foreach ($col_names as $key => $value) {
			if ($num == 1) 
			{
				$string .= "( ";
			}
			
			if ($num == $total_col_num) 
			{
				$string .= "`" . $value['column_name'] . "` ) VALUES " .PHP_EOL;
			}
			else 
			{
				$string .= "`" . $value['column_name'] . "`, ";
			}
			$num++;
		}
		// fetch rows 
		$selectQS = "SELECT * FROM " . $table_name;
		$result = pg_query($this->DBConnection, $selectQS);
		$total_row_num = pg_num_rows($result);
		$num = 1;
		while($row = pg_fetch_assoc($result)) 
		{
			
			$col_names = $this->returnColumnsArray($table_name);
			$total_col_num = count($col_names);
			$c_num = 1;
			foreach ($col_names as $key => $value) 
			{
				if ($c_num == $total_col_num) 
				{
					if (is_numeric(trim($row[$value['column_name']]))) 
					{
						$string .= trim(addslashes($row[$value['column_name']])) . " ), ".PHP_EOL ;	
					}
					else 
					{
						$string .= "'". trim(addslashes($row[$value['column_name']])) . "' ), ".PHP_EOL;
					}
				} 
				elseif ($c_num == 1) 
				{
					if (is_numeric(trim($row[$value['column_name']]))) 
					{
						$string .= "( " . trim(addslashes($row[$value['column_name']])) . ", ";	
					}
					else 
					{
						$string .= "( '". trim(addslashes($row[$value['column_name']])) . "', ";
					}
				}
				else 
				{
					if (is_numeric(trim($row[$value['column_name']]))) 
					{
						$string .= trim(addslashes($row[$value['column_name']])) . ", ";	
					}
					else 
					{
						$string .= "'". trim(addslashes($row[$value['column_name']])) . "', ";
					}
				}
				$c_num++;
			}
			$num++;
		}
		$string = rtrim($string,PHP_EOL);
		$string = rtrim(trim($string), ',');
		$string .= ";";
		return $string;
	}

	public function returnTableNamesArray() 
	{
		// assuming there is more than one table ...
		$str = "SELECT
			array_to_string( array_agg(c.relname), ',' ) AS names
		FROM
			pg_catalog.pg_class c
		LEFT JOIN
			pg_catalog.pg_namespace n ON n.oid = c.relnamespace
		WHERE
			c.relkind IN ('r', '')
		AND
			n.nspname NOT IN ('pg_catalog', 'pg_toast')
		AND
			pg_catalog.pg_table_is_visible(c.oid)";

		$result = pg_query($this->DBConnection, $str);
		$names = pg_fetch_assoc($result);
		return explode(",", $names['names']);
	}

	public function buildDataBaseDumpString() 
	{
		$string = "-- " . $this->class_name . ' Version: ' . $this->version . ' MySQL dump' . PHP_EOL . PHP_EOL; 
		$string .= "SET NAMES ".$this->char_set.";" . PHP_EOL;
		$string .= "SET time_zone = '".$this->time_zone."';" . PHP_EOL;
		$string .= "SET foreign_key_checks = ".$this->foreign_key_checks.";" . PHP_EOL ;
		$string .= "SET sql_mode = '".$this->sql_mode."';" . PHP_EOL;
		//$string .= "USE `".$this->psql_database."`;" . PHP_EOL;

		return $string;
	}
	public function export() 
	{
		$string  = '';
		$string .= $this->buildDataBaseDumpString();
		$tables  = $this->returnTableNamesArray();
		foreach ($tables as $table) 
		{
			$string .= $this->createDropTableString($table);
			$string .= $this->createTableStructureString(trim($table));
			$string .= $this->createTableInsertString($table);
		}
		$string .= PHP_EOL . "-- " . date("Y-m-d H:m:s");
		return $string;
	}
}



