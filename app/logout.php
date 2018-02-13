<?php
	require('main.php');

	unset($_SESSION['login']);
	unset($_SESSION['token']);

	unset($_COOKIE['login']);
	setcookie('login', '', 1, '/');
	
	unset($_COOKIE['token']);
	setcookie('token', '', 1, '/');
?>
