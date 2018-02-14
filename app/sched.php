<?php
include('main.php'); 
use Medoo\Medoo;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$DB->insert('tbl_email_logs', [
	'content' => "TEST CRONJOB",
	'from' => "CRONJOB",
	'to' => "CRONJOB"
]);
	
?>