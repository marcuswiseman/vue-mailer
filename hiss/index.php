<?php
/*
 # ▒██░ ██  █▓  ██████   ██████
 # ▓██░ ██ ▒██▒▒██    ▒ ▒██    ▒
 # ▒██▀▀██░▒██▒░ ▓██▄   ░ ▓██▄
 # ░▓█ ░██ ░██░  ▒   ██▒  ▒   ██▒
 # ░▓█▒░██▓░██░▒██████▒▒▒██████▒▒
 # ▒ ░░▒░▒░▓  ▒ ▒▓▒ ▒ ░▒ ▒▓▒ ▒ ░
 # ▒ ░▒░ ░ ▒ ░░ ░▒  ░ ░░ ░▒  ░ ░
 # ░  ░░ ░ ▒ ░░  ░  ░  ░  ░  ░
 # ░  ░  ░ ░        ░        ░
 #
 # v0.1b
 #
 # Features:
 #
*/

include_once 'includes/functions.php';
page_setup(false);
?>

<!DOCTYPE html>
<html>
	<head>
		<title>HiSS</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="Content-type" content="text/html;charset=UTF-8">
		<link href="https://fonts.googleapis.com/css?family=Roboto:300,400,700" rel="stylesheet">
		<link rel="stylesheet" type="text/css" href=" css/style.css">
	</head>
	<body>
		<header>
			<pre>
			  ▒██░ ██  █▓  ██████   ██████
			  ▓██░ ██ ▒██▒▒██    ▒ ▒██    ▒
			  ▒██▀▀██░▒██▒░ ▓██▄   ░ ▓██▄
			  ░▓█ ░██ ░██░  ▒   ██▒  ▒   ██▒
			  ░▓█▒░██▓░██░▒██████▒▒▒██████▒▒
			  ▒ ░░▒░▒░▓  ▒ ▒▓▒ ▒ ░▒ ▒▓▒ ▒ ░
			  ▒ ░▒░ ░ ▒ ░░ ░▒  ░ ░░ ░▒  ░ ░
			  ░  ░░ ░ ▒ ░░  ░  ░  ░  ░  ░
			  ░  ░  ░ ░        ░        ░
			</pre>
		</header>
	
		<!-- DASHBOARD -->	
		<?php if (isset($user)) { ?>
			<!-- SELLER -->
			<?php if ($user['access'] == 1) { ?>
				<div class="o-box">
					<h1 class="o-box-title">Seller Dashboard</h1>
					<div>
					</div>
				</div>
			<?php } ?>
			<!-- CUSTOMER/SELLER -->
			<?php if ($user['access'] == 0 || $user['access'] == 1) { ?>
				<div class="o-box">
					<h1 class="o-box-title">Customer Dashboard</h1>
					<div>
					</div>
				</div>
			<?php } ?>
			<div class="o-box">
				<a class="c-btn u-margin-top-50" href="includes/functions.php?logout">Logout</a>
			</div>
		<!-- LOGIN -->
		<?php } else { ?>
			<div class="o-box">
				<form id="login-form" method="POST">
					<input type="text" placeholder="USERNAME" name="username">
					<input type="password" placeholder="PASSWORD" name="password">
					<button>Login</button>
				</form>
				<p id="login-reply" class="o-ajax-reply u-text-red"></p>
			</div>
		<?php } ?>
		
		<footer>
			<p>Copyright (c) 2017 | HiSS 🐍</p>
		</footer>

		<script
		  src="https://code.jquery.com/jquery-3.2.1.min.js"
		  integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
		  crossorigin="anonymous"></script>
		<script>
			$(function() {
				$("#login-form").on('submit', function(e) {
					e.preventDefault();
					$.post('includes/functions.php?login', $('#login-form').serialize(), function(reply) {
						if (reply == "Logged In") {
							location.reload();
						} else {
							$('#login-reply').html(reply);
						}
					});
				});
			});
		</script>

	</body>
</html>
