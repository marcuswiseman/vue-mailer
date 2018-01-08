<?php

// ----------------------------------------------------------------------------
// This class will manage database actions.
//
// DONE:1 have a function to return row results in an array format
// DONE:2 have a last() funciton to get the last query run as a string
// DONE:3 increase security of query
// DONE:4 setup DB migrations
// ----------------------------------------------------------------------------

$x_outter_link = NULL;
$x_last_query = "";

class DB {
  public const HOST = 'localhost', USER = 'root', PASS = '', DB = 'db_hiss';

  public static function connect ($host=DB::HOST, $user=DB::USER,
                                  $pass=DB::PASS, $db=DB::DB) {
    global $x_outter_link;
    $x_outter_link = new mysqli($host, $user, $pass, $db);
    if ($x_outter_link->connect_errno) {
        die("Connect failed: " . mysqli_error($x_outter_link));
    }
  }

  public static function query ($query, $strip=true) {
    global $x_outter_link;
    global $x_last_result;
		global $x_last_query;
		$x_last_query = $query;
    if (isset($x_outter_link) && $x_outter_link->ping()) {
      $x_last_result = ($strip == true ? $x_outter_link->query(strip_tags($query)) : $x_outter_link->query($query));
      return $x_last_result;
    } else {
      die('Database not connected.');
    }
  }

  public static function query_num ($query, $strip=true) {
		global $x_outter_link;
		global $x_last_query;
		$x_last_query = $query;
    if ($x_outter_link->ping()) {
			$results = DB::query($query, $strip);
      return (isset($results->num_rows) ? $results->num_rows : 0);
    } else {
      die('Database not connected.');
    }
	}

  public static function assoc () {
    global $x_last_result;
    if (isset($x_last_result)) {
      return $x_last_result->fetch_assoc();
    }
  }

  public static function assoc_all () {
    global $x_last_result;
		$arr = array();
    if (isset($x_last_result)) {
			while ($row = $x_last_result->fetch_assoc()) {
				$arr[] = $row;
			}
      return $arr;
    }
  }

	public static function last () {
		global $x_last_query;
		return $x_last_query . "<br>";
	}

  public static function insert_id () {
    global $x_outter_link;
    return $x_outter_link->insert_id;
  }

  public static function escape ($string) {
    global $x_outter_link;
    return $x_outter_link->real_escape_string($string);
  }

  public static function error () {
    global $x_outter_link;
    if (mysqli_error($x_outter_link) != "") {
        echo("A mySQLi error occured: " . mysqli_error($x_outter_link));
    }
  }

  public static function close () {
    global $x_outter_link;
    mysqli_close($x_outter_link);
  }

  public static function migrate () {
    $sql_path = __DIR__ . '//migrate';
    if (is_dir($sql_path)) {
      $queries = scandir($sql_path);
      $queries = array_diff($queries, ['.', '..']);
      foreach ($queries as $query) {
        $fullpath = __DIR__ . '//migrate//' . $query;
        if (is_file($fullpath)) {
           DB::query(file_get_contents($fullpath));
        }
      }
    } else {
      die("Migrate directory not found");
    }
  }
}

?>
