<?php
include('main.php');
use Medoo\Medoo;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function prepare_user () {
	global $DB;
	$user = $DB->select('tbl_users', ['id', 'email', 'company_name', 'temp_password', 'login_token', 'ip_address', 'date_verified', 'smtp_host', 'smtp_username', 'smtp_password', 'smtp_port', 'smtp_ssl'], ['id' => $_COOKIE['login']]);
	$email_lists = $DB->select('tbl_users_email_lists', ['id', 'name'], ['user_id' => $_COOKIE['login'], 'date_deleted' => NULL]);
	$user_templates = $DB->select('tbl_users_email_templates', ['id', 'name', 'template'], ['user_id' => $_COOKIE['login'], 'date_deleted' => NULL]);
	$templates = $DB->select('tbl_templates', '*');
	
	$history = $DB->query(
		"SELECT 
			logs.id as `id`,
			list.name as `list_name`,
			template.name as `template_name`,
			logs.sent as `sent`,
			logs.date_attempt as `date`
		 FROM tbl_email_logs logs
		 LEFT JOIN tbl_users_email_lists list
		 	ON list.id = logs.list_id
		 LEFT JOIN tbl_users_email_templates template
		 	ON template.id = logs.template_id
		 WHERE <logs.user_id> = :user_id
		 ORDER BY logs.date_attempt DESC
		 LIMIT 200", [
			 ':user_id' => $_COOKIE['login']
		]
	)->fetchAll();

	if (!isset($user)) { http_response_code(207); die(); }
	
	if (isset($user[0]['temp_password'])) { 
		$user[0]['temp_password'] = true; 
	} else { 
		$user[0]['temp_password'] = false; 
	}

	if (isset($user[0]['smtp_ssl']) && $user[0]['smtp_ssl'] == 1) { 
		$user[0]['smtp_ssl'] = true; 
	} else { 
		$user[0]['smtp_ssl'] = false; 
	}

	if (isset($user[0]['smtp_username'])) {
		$user[0]['smtp_username'] = decrypt_string($user[0]['smtp_username']);
	}

	if (isset($user[0]['smtp_password'])) {
		$user[0]['smtp_password'] = decrypt_string($user[0]['smtp_password']);
	}

	if (isset($email_lists)) { $user['available_email_lists'] = $email_lists; }
	if (isset($user_templates)) { $user['user_templates'] = $user_templates; }
	if (isset($templates)) { $user['templates'] = $templates; } 
	if (isset($history)) { $user['history'] = $history; }
	return $user;
}

if (isset($_GET['check'])) {
	if (isset($_COOKIE['login']) && isset($_COOKIE['token'])) {
		$user = prepare_user();
		if ($user[0]['login_token'] != $_COOKIE['token']) { http_response_code(201); die('NOTICE #1'); }
		if ($user[0]['ip_address'] != get_client_ip()) { http_response_code(201); die('NOTICE #2'); }
		if (isset($user)) {
			http_response_code(200); die(json_encode($user));
		} else { 
			http_response_code(201); die('NOTICE #3');
		}
	} else {
		http_response_code(201); die('NOTICE #4');
	}
}

if (isset($_GET['login'])) {
	$data = get_post();
	$encrypted_password = encrypt_string($data->password);
	$check = $DB->get('tbl_users', '*', ['email' => $data->email, 'password' => $encrypted_password]);
	$check2 = $DB->get('tbl_users', '*', ['email' => $data->email, 'temp_password' => $encrypted_password]);
	if ($check2) { $check = $check2; }
	if ($check || $check2) {
		if ($check['login_attempts'] >= 3) {
			$url = $site_path . "app/actions.php?a=unlock_account&vercode={$check['verification_code']}&email=" . encrypt_string($check['email']);
			$body = <<<_HTML_
				Hello,<br></br><br>

				<p>Your account has been locked due to too many inccorect logins. Please click the below link to unlock your account.</p>

				<a href="{$url}">Click here to unlock your account</a>

				<p>If you have forgotten your password please use the "forgot password" form to reset your password through our app.</p><br></br>

				Regards
_HTML_;
			$sent = internal_mail('Your Account is Locked', $body, $data->email); 
			http_response_code(202); die(); 
		}

		setcookie('login', '', 1, '/');
		setcookie('token', '', 1, '/');

		setcookie('login', $check['id'], time()+2678400, '/');
		$token = gen_token(32);
		setcookie('token', $token, time()+2678400, '/');

		$DB->update('tbl_users', ['login_attempts' => 0, 'login_token' => $token, 'ip_address' => get_client_ip()], ['id' => $check['id']]);
		$user = prepare_user();
		
		if ($user)	{
			http_response_code(200); die(json_encode($user));
		} else {
			http_response_code(201); die();
		}
	} else {
		$check = $DB->get('tbl_users', '*', ['email' => $data->email]);
		if ($check) {
			$DB->update('tbl_users', ['login_attempts[+]' => 1], ['email' => $data->email]);
			if ($check['login_attempts'] >= 3) { 
				$url = $site_path . "app/actions.php?a=unlock_account&vercode={$check['verification_code']}&email=" . encrypt_string($check['email']);
				$body = <<<_HTML_
					Hello,<br></br><br>

					<p>Your account has been locked due to too many inccorect logins. Please click the below link to unlock your account.</p>

					<a href="{$url}">Click here to unlock your account</a>

					<p>If you have forgotten your password please use the "forgot password" form to reset your password through our app.</p><br></br>

					Regards
_HTML_;
				$sent = internal_mail('Your Account is Locked', $body, $data->email); 
				http_response_code(202); die(); 
			} else {
				http_response_code(201); die();
			}
		} else {
			http_response_code(201); die();
		}
	}
}

if (isset($_GET['register'])) {
	$data = get_post();
	if ($data->email != $data->con_email) {
		http_response_code(202); die();
	}
	if ($data->password != $data->con_password) {
		http_response_code(203); die();
	}
	if (strlen($data->password) < 8) {
		http_response_code(204); die();
	}
	if (strpos($data->email, '@') === false) {
		http_response_code(205); die();
	}
	$check = $DB->select('tbl_users', '*', ['email' => $data->email]);
	if (!$check) {
		$encrypted_password = encrypt_string($data->password);
		$verification_code = gen_token(8);
		$DB->insert('tbl_users', [
			'email' => htmlspecialchars($data->email),
			'password' => htmlspecialchars($encrypted_password),
			'company_name' => htmlspecialchars($data->company_name),
			'verification_code' => $verification_code
		]);
		$body = <<<_HTML_
Hello,

Thank you for creating an account with us! Below you will find your verification code, you will need this after you have logged in.

{$verification_code}

Once you have verified your email address with us you will be able to send outgoing emails.

Regards
_HTML_;
		$sent = internal_mail('Activate Your Account', $body, $data->email);
		if ($sent) { 
			http_response_code(200); die(); 
		} else {
			http_response_code(201); die(); 
		}
	} else {
		http_response_code(201); die();
	}
}



?>
