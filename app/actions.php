<?php
include('main.php'); 
use Medoo\Medoo;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$action = (isset($_GET['a']) ? $_GET['a'] : die('No action specified.'));
$special_acces = (isset($_GET['key']) ? $_GET['key'] : false);
$data = get_post();

/* ------------------------ LOGGED OUT ONLY FUNCTIONS ----------------------- */

# RESET PASSWORD 
if ($action == "reset_password") {
	$user = $DB->get('tbl_users', '*', ['email' => $data->email]);
	if ($user) {
		$new_password = gen_token(10);
		$body = <<<_HTML_
			Hello,<br></br><br>
	
			<p>You have requested a password reset. Below is your new password. Please use this to login, then change it from the settings menu.</p>
	
			Your new temporary password is: {$new_password}<br></br><br>
	
			Regards
_HTML_;
		$sent = internal_mail('New Password', $body, $data->email); 
		if ($sent) {
			$update = $DB->update('tbl_users', ['temp_password' => encrypt_string($new_password)] , ['email' => $data->email]);
			if ($update->rowCount() > 0) {
				http_response_code(200); die();
			} else {
				http_response_code(202); die();
			}
		} else {
			http_response_code(201); die();
		}
	} else {
		http_response_code(202); die();
	}
}

# UNLCOK ACCOUNT 
if ($action == "unlock_account") {
	$ver_code = $_GET['vercode'];
	$new_code = gen_token(8);
	$update = $DB->update('tbl_users', [
		'login_attempts' => 0,
		'verification_code' => $new_code
	], [
		'email' => decrypt_string($_GET['email']),
		'verification_code' => $ver_code
	]);
	if ($update->rowCount() > 0) {
		die("Account successfuly unlocked. Please try logging in now. :)");
	} else {
		die("Issue with validation. Please try again.");
	}
}

/* ------------------------ LOGGED IN ONLY FUNCTIONS ------------------------ */
logged_in();

# NEW PASSWORD 
if ($action == "new_password") {

	if (strlen($data->new_password) < 8) {
		http_response_code(202); die();
	}

	$check = $DB->get("tbl_users", '*', [
		'password' => encrypt_string($data->old_password), 
		'id' => $_SESSION['login']
	]);

	$check2 = $DB->get("tbl_users", '*', [
		'temp_password' => encrypt_string($data->old_password),
		'id' => $_SESSION['login']
	]);

	if ($check2) { $check = $check2; }

	if ($check) {
		$update = $DB->update("tbl_users", [
			'password' => encrypt_string($data->new_password), 
			'temp_password' => null
		], [
			'id' => $_SESSION['login']
		]);
		http_response_code(200); die();
	} else {
		http_response_code(201); die();
	}
}

# VERIFY VERIFICATION CODE
if ($action == "verify_code") {
	$check = $DB->get('tbl_users', 'verification_code', ['id' => $_SESSION['login'], 'login_token' => $_SESSION['token'], 'verification_code' => $data->code]);
	echo $DB->last();
	if ($check) {
		$DB->update('tbl_users', [
			'date_verified' => date("Y-m-d H:i:s")
		], [
			'id' => $_SESSION['login']
		]);
		http_response_code(200); die();
	} else {
		http_response_code(201); die();
	}
}
	
# NEW VERIFICATION CODE
if ($action == "new_verify_code") {
	$verification_code = gen_token(8);
	$DB->update('tbl_users', [
		'verification_code' => $verification_code
	],[
		'id' => $_SESSION['login']
	]);
	$user_email = $DB->get('tbl_users', 'email', ['id' => $_SESSION['login']]);
	$body = <<<_HTML_
		Hello,<br></br><br>

		Thank you for creating an account with us! Below you will find your verification code, you will need this after you have logged in.
		<br><br>
		{$verification_code}
		<br><br>
		Once you have verified your email address with us you will be able to send outgoing emails.
		<br></br><br>
		Regards
_HTML_;
	$sent = internal_mail('Activate Your Account', $body, $user_email);
	if ($sent) {
		http_response_code(200); die();
	} else {
		http_response_code(201); die();
	}
}

# NEW EMAIL LIST
if ($action == "new_email_list") {
	$check = $DB->select('tbl_users_email_lists', '*', ['name' => $data->name, 'user_id' => $_SESSION['login'], 'date_deleted' => NULL]);
	if (!$check) {
		$DB->insert('tbl_users_email_lists', [
			'user_id' => $_SESSION['login'],
			'name' => htmlspecialchars($data->name)
		]);
		http_response_code(200); die(json_encode( ['id' => $DB->id()] ));
	} else {
		http_response_code(202); die();
	}
}

# NEW TEMPLATE
if ($action == "new_template") {
	$check = $DB->select('tbl_users_email_templates', '*', ['name' => $data->name, 'user_id' => $_SESSION['login'], 'date_deleted' => NULL]);
	if (!$check) {
		$DB->insert('tbl_users_email_templates', [
			'user_id' => $_SESSION['login'],
			'name' => htmlspecialchars($data->name)
		]);
		http_response_code(200); die(json_encode( ['id' => $DB->id()] ));
	} else {
		http_response_code(202); die();
	}
}

# DELETE TEMPLATE (SOFT DELETE)
if ($action == "delete_template") {
	$update_template = $DB->update('tbl_users_email_templates', [
		'date_deleted' => date("Y-m-d H:i:s")
	], [
		'id' => $data->id,
		'user_id' => $_SESSION['login']
	]);
	if ($update_template) { http_response_code(200); die(); } else { http_response_code(202); die(); }
}

# GET TEMPLATE
if ($action == "get_template") {
	$template = $DB->select('tbl_users_email_templates',
		['id', 'template'],
		[
			'id' => $data->id,
			'user_id' => $_SESSION['login'],
			'date_deleted' => NULL,
			'LIMIT' => 1
		]
	);
	if ($template) {
		$template[0]['template'] = htmlspecialchars_decode($template[0]['template']);
		http_response_code(200); die(json_encode($template));
	} else {
		http_response_code(202); die();
	}
}

# SAVE TEMPLATE
if ($action == "save_template") {
	$update_template = $DB->update('tbl_users_email_templates', [
		'template' => htmlspecialchars($data->template)
	], [
		'id' => $data->id,
		'user_id' => $_SESSION['login']
	]);
	echo $DB->last();
	if ($update_template) { http_response_code(200); die(); } else { http_response_code(202); die(); }
}

# DELETE EMAIL LIST (SOFT DELETE)
if ($action == "delete_email_list") {
	$update_list = $DB->update('tbl_users_email_lists', [
		'date_deleted' => date("Y-m-d H:i:s")
	], [
		'id' => $data->list_id,
		'user_id' => $_SESSION['login']
	]);
	$update_address = $DB->update('tbl_users_email_addresses', [
		'date_deleted' => date("Y-m-d H:i:s")
	], [
		'list_id' => $data->list_id,
		'user_id' => $_SESSION['login']
	]);
	if ($update_list) { http_response_code(200); die(); } else { http_response_code(202); die(); }
}

# GET EMAIL LIST
if ($action == "get_email_list") {
	$email_list = $DB->select('tbl_users_email_addresses',
		['id', 'email'],
		[
			'list_id' => $data->id,
			'user_id' => $_SESSION['login'],
			'date_deleted' => NULL,
			'ORDER' => ['date_added' => 'DESC']
		]
	);
	if ($email_list) {
		foreach($email_list as $key=>$e) {
			$email_list[$key]['email'] = decrypt_string($email_list[$key]['email']);
		}
		http_response_code(200); die(json_encode($email_list));
	} else {
		http_response_code(202); die();
	}
}

# ADD EMAIL
if ($action == "add_email") {
	$insert = $DB->insert('tbl_users_email_addresses', [
		'list_id' => $data->id,
		'user_id' => $_SESSION['login'],
		'email' => encrypt_string(htmlspecialchars($data->email))
	]);
	if ($insert) { http_response_code(200); die(json_encode(['id' => $DB->id()])); } else { http_response_code(202); die(); }
}

# REMOVE EMAIL (SOFT DELETE)
if ($action == "remove_email") {
	$update = $DB->update('tbl_users_email_addresses', [
		'date_deleted' => date("Y-m-d H:i:s")
	], [
		'id' => $data->index_id,
		'list_id' => $data->list_id,
		'user_id' => $_SESSION['login']
	]);
	if ($update) { http_response_code(200); die(); } else { http_response_code(202); die(); }
}

# SEND PREVIEW
if ($action == "send_preview") {
	if (is_verified()) {
		$template = $DB->get('tbl_users_email_templates', 'template', [
			'id' => $data->template_id, 
			'user_id' => $_SESSION['login']
		]);
		$DB->update('tbl_users', [
			'company_name' => htmlspecialchars($data->company_name)
		], [
			'id' => $_SESSION['login']
		]);
		if (external_mail(true, false, $data->from, $data->company_name, [0 => $data->from], $data->subject, $template, $data->template_id, null)) {
			http_response_code(200); die(); 
		} else {
			http_response_code(201); die();
		}
	} else {
		http_response_code(202); die();
	}
}

# SEND ALL MAIL
if ($action == "send_all") {
	if (is_verified()) {
		$template = $DB->get('tbl_users_email_templates', 'template', [
			'id' => $data->template_id, 
			'user_id' => $_SESSION['login']
		]);
		$email_list = $DB->select('tbl_users_email_addresses', 'email', [
			'list_id'=>$data->to, 
			'user_id'=>$_SESSION['login'],
			'date_deleted'=>null
		]);
		$DB->update('tbl_users', [
			'company_name' => htmlspecialchars($data->company_name)
		], [
			'id' => $_SESSION['login']
		]);
		if (external_mail(false, $data->send_indiv, $data->from, $data->company_name, $email_list, $data->subject, $template, $data->template_id, $data->to)) {
			http_response_code(200); die(); 
		} else {
			http_response_code(201); die();
		}
	} else {
		http_response_code(202); die();
	}
}

# SETUP SMTP
if ($action == "setup_smtp") {
	$mail = new PHPMailer;
	$mail->Host = $data->host;
	$mail->Port = $data->port;
	$mail->Username = $data->username;
	$mail->Password = $data->password;
	$mail->isSMTP();
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
	$mail->SMTPSecure = ($data->ssl ? 'ssl' : 'tls');
	$mail->SMTPDebug = 3;
	$mail->setFrom($data->username);
	if (!$mail->smtpConnect()) {
		http_response_code(201); die();
	}
	$mail->smtpClose();

	$update = $DB->update('tbl_users', [
		'smtp_host' => $data->host,
		'smtp_username' => encrypt_string($data->username),
		'smtp_password' => encrypt_string($data->password),
		'smtp_port' => $data->port,
		'smtp_ssl' => $data->ssl
	], [
		'login_token' => $_SESSION['token'],
		'id' => $_SESSION['login']
	]);
	if ($update) { http_response_code(200); die(); } else { http_response_code(202); die(); }
}

?>