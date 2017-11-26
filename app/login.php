<?php
require('main.php');

$temp_pass = "unicorns";

if (isset($_GET['check'])) {
	echo "test";
	if (isset($_SESSION['login']) && $_SESSION['login'] == $temp_pass) {
		http_response_code(200); die();
	} else {
		http_response_code(201); die();
	}
}

if (isset($_GET['login'])) {
	$data = get_post();
	if ($data->password == $temp_pass) {
		$_SESSION['login'] = $data->password;
		http_response_code(200); die();
	} else {
		http_response_code(201); die();
	}
}



?>
