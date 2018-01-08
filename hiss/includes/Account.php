<?php

// ----------------------------------------------------------------------------
// This class will manage the user account actions such as login, register
// and updating of profile details.
//
// DONE:1 setup register function
// DONE:2 setup login function
// TODO:3 update of user infos
// ----------------------------------------------------------------------------

class Account {
  protected $username;

  public function username () {
    return $this->username;
  }

  public static function login_token () {
    $token = substr("abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ", mt_rand(0, 51), 1).substr(md5(time()), 1);
    return hash('sha512', $token);
  }

  public static function login ($username, $password) {
    $password = hash('sha512', $password);
    $check = <<<_SQL_
      SELECT user_id, email, password
      FROM tbl_users
      WHERE username = "{$username}"
      AND password = "{$password}"
_SQL_;
    $usr_found = DB::query_num($check);
    if ($usr_found != 0) {
      $user = DB::assoc();
      Account::give_token($user['user_id']);
      echo "Logged In";
			return true;
    } else {
      echo "Invalid Details";
			return false;
    }
  }

  public static function give_token ($user_id) {
    $token = Account::login_token();
    setcookie("token", $user_id . "-" . $token, time()+60*60*24*365, "/");
    $_COOKIE["token"] = $user_id . "-" . $token;
    DB::query("UPDATE tbl_users SET token = '{$token}' WHERE user_id = {$user_id}");
  }

  public static function validate_token () {
    if (isset($_COOKIE['token'])) {
      $cookie = explode('-', $_COOKIE['token']);
      $check = <<<_SQL_
        SELECT token, first_login
        FROM tbl_users
        WHERE user_id = {$cookie[0]}
        AND token = '{$cookie[1]}'
_SQL_;
      $token_found = DB::query_num($check);
      if (isset($token_found) && $token_found != 0) {
        $row = DB::assoc();
        $_SESSION['user'] = $cookie[0];
        $_SESSION['first_login'] = $row['first_login'];
        return true;
      } else {
        unset($_SESSION['user']);
        unset($_COOKIE['token']);
        return false;
      }
    } else {
      unset($_SESSION['user']);
      return false;
    }
  }

	public static function gather_info() {
		if (isset($_COOKIE['token'])) {
			$cookie = explode('-', $_COOKIE['token']);
      $check = <<<_SQL_
        SELECT firstname, lastname, username, email, user_id, access
        FROM tbl_users
        WHERE user_id = {$cookie[0]}
        AND token = '{$cookie[1]}'
_SQL_;
      $token_found = DB::query_num($check);
			if (isset($token_found) && $token_found != 0) {
				return DB::assoc();
			} else {
				return "User not found...";
			}
		}
	}

	public static function register ($email, $password, $fullname, $username="") {
		$password = hash('sha512', $password);
    $username = DB::escape($username);

    if (isset($fullname[0]) && isset($fullname[1])) {
      $firstname = DB::escape($fullname[0]);
      $lastname = DB::escape($fullname[1]);
    } else {
      die("Fullname not in the correct format! Eg. Marcus Wiseman");
    }

		$check = <<<_SQL_
			SELECT email
			FROM tbl_users
			WHERE email = "{$email}"
_SQL_;
		$usr_found = DB::query_num($check);
		if ($usr_found == 0) {
			$query = <<<_SQL_
				INSERT INTO tbl_users
					(username, password, email, firstname, lastname)
				VALUES
					("{$username}", "{$password}",
					 "{$email}", "{$firstname}",
					 "{$lastname}")
_SQL_;
			DB::query($query);
      $new_id = DB::insert_id();
      Account::give_token($new_id);
      $query = <<<_SQL_
        INSERT INTO tbl_user_info
          (user_id)
        VALUES
          ({$new_id})
_SQL_;
      DB::query($query);
			echo "Account created!";
		} else {
			echo "Account with this email already exists!";
		}
	}
}

?>
