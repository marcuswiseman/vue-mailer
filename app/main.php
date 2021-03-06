<?php

require('Medoo.php');
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use Medoo\Medoo;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'TemplateEditor.php'; 

// ini_set('memory_limit','124M');

session_start();

// // // Initialize DB
$DB = new Medoo([
    'database_type' => 'mysql',
    'database_name' => 'vuemailer',
    'server' => 'localhost',
    'username' => 'root',
    'password' => ''
]);

// LIVE
// $DB = new Medoo([
//     'database_type' => 'mysql',
//     'database_name' => 'ocidb_Kt9w151576',
//     'server' => '213.171.200.90',
//     'username' => 'vuemailer',
//     'password' => '@Un1c0rns!'
// ]);

$site_path = "https://localhost/";

function get_client_ip () {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
       $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else 
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

function logged_in () {
	global $DB;
	if (isset($DB)) {
		if (!isset($_COOKIE['login']) && !isset($_COOKIE['token'])) { die('Unauthorised! #000'); }
		else {
			$user = $DB->get('tbl_users', '*', ['id' => $_COOKIE['login']]);
			if ($user['login_token'] != $_COOKIE['token']) { die('Unauthorised! #001'); }
			if ($user['ip_address'] != get_client_ip()) { die('Unauthorised! #002'); }
		}
	} else { die('Unauthorised! #003'); }
}

function gen_token ($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function get_post () {
	$json = file_get_contents('php://input');
	return isset($json) ? json_decode($json) : $_POST;
}

function encrypt_string ($string, $key = '@Un1c0rns!') {
	$secret_key = $key;
	$secret_iv = $key;
	$output = false;
	$encrypt_method = "AES-256-CBC";
	$key = hash( 'sha256', $secret_key );
	$iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );
	return base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
}

function decrypt_string ($string, $key = '@Un1c0rns!') {
	$secret_key = $key;
	$secret_iv = $key;
	$output = false;
	$encrypt_method = "AES-256-CBC";
	$key = hash( 'sha256', $secret_key );
	$iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );
	return $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
}

function is_verified() {
	global $DB;
	if (isset($_COOKIE['login'])) {
		return $DB->get('tbl_users', 'date_verified', ['id'=>$_COOKIE['login']]) ? true : false;
	}
}

function internal_mail ($subject, $body, $to) {
	global $DB;
	$mail = new PHPMailer;
	$from = "no-reply@wisemailer.com";
	$mail->setFrom('no-reply@wisemailer.com', 'Wise Mailer');
	$mail->addAddress($to);
	$mail->Host = 'smtp.livemail.co.uk';
	$mail->Port = 587;
	$mail->SMTPSecure = 'tsl';
	$mail->SMTPAuth = true;
	$mail->Username = $from;
	// $mail->SMTPDebug = 3;
	$mail->Password = "Killer123!";
	$mail->isSMTP();
	$mail->isHTML();
	$mail->Subject = $subject;
	$mail->Body = $body;
	$DB->insert('tbl_email_logs', [
		'content' => $body,
		'from' => $from,
		'to' => $to
	]);
	if($mail->send()) {
		$DB->update('tbl_email_logs', ['sent' => 1], ['id' => $DB->id()]);
		return true;
	} else {
		return false;
	}
}

function external_mail ($test, $individual, $from, $company, $to, $subject, $body, $template_id, $list_id) {
	global $DB;
	$body = htmlspecialchars_decode($body);
	$user = $DB->get('tbl_users', [
		'smtp_host', 
		'smtp_username', 
		'smtp_password', 
		'smtp_port',
		'smtp_ssl'
	], ['id' => $_COOKIE['login']]);
	if ($user) {
		$mail = new PHPMailer;
		$mail->Host = $user['smtp_host'];
		$mail->Port = $user['smtp_port'];
		$mail->Username = decrypt_string($user['smtp_username']);
		$mail->Password = decrypt_string($user['smtp_password']);
		$mail->isSMTP();
		$mail->isHTML();
		if (get_client_ip() == "::1") {
			$mail->SMTPOptions = array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
				)
			);
		}
		$mail->SMTPAuth = true;
		$mail->AuthType = "LOGIN";
		$mail->SMTPSecure = ($user['smtp_ssl'] ? 'ssl' : 'tls');
		// $mail->SMTPDebug = 3;
		$mail->setFrom($from, $company);
		$mail->Subject = $subject;
		$mail->Body = $body;

		$DB->insert('tbl_email_logs', [
			'content' => $body,
			'user_id' => $_COOKIE['login'],
			'template_id' => $template_id,
			'list_id' => $list_id
		]);
		
		if (!$individual) {
			if (!$test) {
				foreach($to as $t) {
					$mail->AddAddress(decrypt_string($t));
				}
			} else {
				$mail->AddAddress($to[0]);
			}
			if($mail->send()) {
				$DB->update('tbl_email_logs', ['sent' => 1], ['id' => $DB->id()]);
				return true;
			} else {
				echo 'Mailer Error: ' . $mail->ErrorInfo;
				return false;
			}
		} else {
			foreach($to as $t) {
				$mail->ClearAllRecipients();
				if (!$test) {
					$mail->AddAddress(decrypt_string($t));
				} else {
					$mail->AddAddress($to[0]);
				}
				if($mail->send()) {
					$DB->update('tbl_email_logs', ['sent' => 1], ['id' => $DB->id()]);
				} else {
					echo 'Mailer Error: ' . $mail->ErrorInfo;
					return false;
				}
			}
		}
		
		return true;
	} else { 
		return false; 
	}
}

?>
