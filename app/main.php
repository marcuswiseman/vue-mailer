<?php

session_start();

function get_post() {
	$json = file_get_contents('php://input');
	return json_decode($json);
}

?>
