<?php
if($_SERVER["HTTPS"] != "on") {
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}
?>

<html> 
  <head>
		<title>Vue Mailer</title>
		<meta name="viewport" content="initial-scale=1, maximum-scale=1">
		<meta charset="utf-8">

		<!-- STLYES -->
		<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet">
		<link href="https://use.fontawesome.com/releases/v5.0.1/css/all.css" rel="stylesheet">
		<link rel="stylesheet" type="text/css" href="css/stylesheet.css">
		<link rel="stylesheet" type="text/css" href="css/codemirror.css">
		<link rel="stylesheet" type="text/css" href="css/monokai.css">
  </head>
  <body>

    <div id="app" class="o-wrapper">

			<img v-if="login !== 3" src="imgs/logo.png" class="c-logo">

			<!-- FORGOT PASSWORD -->
			<div id="login" class="o-color-box u-max-w460 u-bg-color-grey u-center u-margin100 u-padding-10" v-if="login === -1">
				Please input your email address below and we'll send you a password reset email.
				<form name="forgot_password" v-on:submit.prevent="onForgotPassword" action="app/login.php" method="post">
					<input type="text" v-model="email" placeholder="Email" required><br>
					<button>Submit</button> or <span @click="switch_forms(1)" class="c-text-btn">Go Back</span>
					<p v-if="error.active" class="u-error">{{ error.msg }}<p>
					<p v-if="success.active" class="u-success">{{ success.msg }}</p>
				</form>
			</div>

			<!-- LOGIN -->
			<div id="login" class="o-color-box u-max-w460 u-bg-color-grey u-center u-margin100 u-padding-10" v-if="login === 1">
				<form name="login" v-on:submit.prevent="onLogin" action="app/login.php" method="post">
					<input type="text" v-model="email" placeholder="Email" required><br>
					<input type="password" v-model="password" placeholder="Password" required><br>
					<button>Login</button> or <span @click="switch_forms(0)" class="c-text-btn">Register</span><br>
					<span @click="switch_forms(-1)" class="c-text-btn">Forgot Password?</span>
					<p v-if="error.active" class="u-error">{{ error.msg }}<p>
				</form>
			</div>

			<!-- REGISTER -->
			<div id="register" class="o-color-box u-max-w460 u-bg-color-grey u-center u-margin100 u-padding-10" v-else-if="login === 0">
				<form name="register" v-on:submit.prevent="onRegister" action="app/login.php" method="post">
					<input type="text" v-model="reg_company_name" placeholder="Company/Personal Name"><br>
					<input type="text" v-model="email" placeholder="Email" required><br>
					<input type="text" v-model="con_email" placeholder="Retype your Email" required><br>
					<input type="password" v-model="password" placeholder="Password" required><br>
					<input type="password" v-model="con_password" placeholder="Retype your Password" required><br>
					<button>Register</button> or <span @click="switch_forms(1)" class="c-text-btn">Go Back</span>
					<p v-if="error.active" class="u-error">{{ error.msg }}<p>
					<p v-if="success.active" class="u-success">{{ success.msg }}</p>
				</form>
			</div>

			<!-- MAIN APP -->
			<div id="main" class="o-box u-center u-text-left u-half-width" v-else-if="login === 3">

				<!-- USER MENU BUTTON -->
				<div class="c-menu-btn" v-if="!verify_required && !smtp_setup">
					<i title="Settings" class="fas fa-cog fa-lg" @click="show_user_menu != 1 ? show_user_menu = 1 : show_user_menu = 0"></i>
				</div>
				
				<!-- HISTORY BUTTON -->
				<div class="c-menu-btn" v-if="!verify_required && !smtp_setup">
					<i title="History" class="fas fa-history fa-lg" @click="show_user_menu != 2 ? show_user_menu = 2 : show_user_menu = 0"></i>
				</div>

				<!-- USER MENU  -->
				<div class="o-user-menu u-no-select" v-if="show_user_menu == 1">
					<h3>SETTINGS</h3>
					<div class="o-settings-container">
						<h2>Password</h2>
						<p>Change your password.</p>
						<form v-on:submit.prevent="change_password">
							<input type="password" v-model="password" placeholder="Old Password" required>
							<input type="password" v-model="con_password" placeholder="New Password" required>
							<button >Update</button>
						</form>
						<span v-if="error2.active" class="u-error">{{ error2.msg }}</span>
						<p v-if="success2.active" class="u-success">{{ success2.msg }}</p>

						<h2>SMTP</h2>
						<p>Before you can send any emails you need to setup your SMTP settings.</p>
						<input type="text" v-model="smtp_host" placeholder="SMTP Host"><br>
						<input type="text" v-model="smtp_username" placeholder="SMTP Username"><br>
						<input type="password" v-model="smtp_password" placeholder="SMTP Password"><br>
						<input type="text" v-model="smtp_port" placeholder="SMTP Port"><br>
						<label class="switch">
						<input type="checkbox" v-model="smtp_ssl" value="None" checked />
					  <span class="slider slider-dark"></span>
					</label><span class="c-slider-result">{{ (smtp_ssl != 0 ? 'SSL' : 'TLS' ) }}</span><br>
					<button v-on:click="setup_smtp">Test Connection</button>
					<p v-if="error3.active" class="u-error">{{ error3.msg }}<p>
					<p v-if="success3.active" class="u-success">{{ success3.msg }}</p>
					</div>
				</div>

				<!-- HISTORY MENU -->
				<div class="o-user-menu u-no-select" v-if="show_user_menu == 2">
					<h3>HISTORY</h3>
					<div class="o-history-container">
						<div class="o-hist-item" v-for="hist in history" v-bind:value="hist.id">
							<h4 v-if="hist.sent == 1" class="u-success">{{ hist.list_name ? hist.list_name : "Preview"}}</h4>
							<h4 v-else class="u-error">{{ hist.list_name ? hist.list_name : "Preview"}}</h4>
							<span class="o-hist-desc">Email template '{{ hist.template_name }}' {{ hist.sent == 1 ? "successfuly sent" : "failed to send"}}.</span>
							<p class="o-hist-time">{{ hist.date }}</p>
						</div>
					</div>
				</div>

				<!-- TEMPARARY PASSWORD ALERT -->
				<div v-if="temp_password_alert" class="o-alert-box">
					<p>You've recently reset your password. Please change your password immediately, you're account could be at risk.</p>
				</div>

				<!-- VERIFY EMAIL -->
				<div v-if="verify_required" class="o-alert-box">
					<p>Before you can send any emails you need to verify that your email address is valid. Please input the code sent to you in the verification email we sent to you. Didn't receive a code? <a v-on:click="resend_ver_code" class="c-text-btn-white">Resend</a>.</p>
					<input type="text" v-model="verification_code" placeholder="Code">
					<button v-on:click="verify_code">Verify</button>
					<span v-if="error3.active" class="u-error">{{ error3.msg }}<span>
				</div>

				<!-- SETUP SMTP -->
				<div v-if="!verify_required && smtp_setup" class="o-alert-box">
					<p>Before you can send any emails you need to setup your SMTP settings. Please input your SMTP details below.</p>
					<input type="text" v-model="smtp_host" placeholder="SMTP Host"><br>
					<input type="text" v-model="smtp_username" placeholder="SMTP Username"><br>
					<input type="password" v-model="smtp_password" placeholder="SMTP Password"><br>
					<input type="text" v-model="smtp_port" placeholder="SMTP Port"><br>
					<!-- <input type="checkbox" v-model="smtp_ssl"> SSL<br> -->
					<label class="switch">
						<input type="checkbox" v-model="smtp_ssl" value="None"/>
					  <span class="slider"></span>
					</label><span class="c-slider-result">{{ (smtp_ssl != 0 ? 'SSL' : 'TLS' ) }}</span><br>
					<button v-on:click="setup_smtp">Set</button>
					<p v-if="error3.active" class="u-error">{{ error3.msg }}<p>
				</div>

				<!-- MAIL HEADERS -->
				<div class="o-flex-box">

					<!-- FROM -->
					<div class="o-color-box u-bg-color-blue">
						<h6 class="o-title">From</h6>
						<input v-model="company_name" type="text" class="c-cornered-field u-full-width" placeholder="Company Name / Personal Name">
					</div>

					<!-- TO -->
					<div class="o-color-box u-bg-color-blue">
						<h6 class="o-title">To</h6>

						<select v-if="!editing_email_list" v-on:change="change_email_list" v-model="email_list_selected">
							<option value="blank" selected>-</option>
							<option v-for="your_e_list in your_email_list" v-bind:value="your_e_list.id">{{your_e_list.name}}</option>
						</select>
						<input class="u-white-txt" v-else type="text" v-model="new_email_list_name" placeholder="Email list name">

						<button class="c-icon-btn" v-if="editing_email_list" v-on:click="new_email_list"><i class="fas fa-check"></i></button>
						<button class="c-icon-btn" v-else v-on:click="toggle_edit_email_list(true)"><i class="fas fa-plus"></i></button>
						<button class="c-icon-btn u-delete-btn" v-if="editing_email_list" v-on:click="toggle_edit_email_list(false)"><i class="fas fa-times"></i></button>
						<button v-on:click="delete_email_list" class="c-icon-btn u-delete-btn" v-if="email_list_selected != 'blank' && editing_email_list == false"><i class="fas fa-trash-alt"></i></button>

						<span v-if="error.active" class="u-error">{{ error.msg }}</span>
						<input v-if="email_list_selected != 'blank'" @keydown.enter="addEmail" v-model="cur_email" type="text" class="c-cornered-field u-full-width" placeholder="Add an email addess to the list.">

						<div v-if="email_list_selected != 'blank'" id="emailContainer" class="c-email-container">
							<div v-if="email_list.length == 0" class="c-email-item">No emails added.</div>
							<div v-for="address in email_list" class="c-email-item">
								{{address.email}}
								<span class="c-email-item-delete u-no-select" v-on:click="removeEmail(address.id)">x</span>
							</div>
						</div>
					</div>

					<!-- SUBJECT -->
					<div class="o-color-box u-bg-color-blue">
						<h6 class="o-title">Subject</h6>
						<input v-model="subject" type="text" class="c-cornered-field u-full-width" placeholder="Enter the subject of the Email here">
					</div>
				</div>

				<!-- MAIL TEMPLATE -->
				<div class="o-box">

					<!-- TEMPLATE PICKER / EDITOR -->
					<div class="o-color-box u-bg-color-grey">
						<h6 class="o-title">Email Template</h6>

						<div id="codeConatiner" class="o-flex-box">

							<select v-if="!editing_email_template" v-on:click="template_change_click" v-on:change="change_template" v-model="email_template_selected">
								<option value="blank" selected>-</option>
								<option v-for="template in email_templates" v-bind:value="template.id">{{template.name}}</option>
							</select>

							<input v-else class="u-bg-color-grey2 u-white-txt" type="text" v-model="new_template_name" placeholder="Template name">

							<button class="c-icon-btn" v-if="editing_email_template" v-on:click="new_template"><i class="fas fa-check"></i></button>
							<button class="c-icon-btn" v-else v-on:click="toggle_edit_email_template(true)"><i class="fas fa-plus"></i></button>
							<button class="c-icon-btn u-delete-btn" v-if="editing_email_template" v-on:click="toggle_edit_email_template(false)"><i class="fas fa-times"></i></button>
							<button v-on:click="delete_template" class="c-icon-btn u-delete-btn" v-if="email_template_selected != 'blank' && editing_email_template == false"><i class="fas fa-trash-alt"></i></button>

							<button title="Save Changes" v-if="email_template_changed" v-on:click="save_template" class="c-icon-btn u-float-right"><i class="fas fa-save"></i></button>
							<span v-if="email_template_changed" class="u-error u-float-right u-margin-left-10">*</span>

							<button title="Format Code" v-if="email_code != ''" v-on:click="format_template" class="c-icon-btn u-float-right"><i class="fas fa-file-code"></i></button>

							<span v-if="error2.active" class="u-error">{{ error2.msg }}</span>
							
							<div v-if="email_template_selected != 'blank'" id="code_container">
								<textarea id="code_editor" v-on:keyup="change_template_state" class="c-code-container" placeholder="Email body code. Handtype or select from the template above.">{{email_code}}</textarea>
							</div>
						
						</div>
					</div>

					<!-- TEMPLATE PREVIEW -->
					<div class="o-color-box u-bg-color-grey">
						<h6 class="o-title">Preview</h6>
						<p v-if="email_code == ''">Nothing to display.</p>
						<iframe v-if="email_code != ''" v-bind:srcdoc="email_code" id="previewContainer" class="o-flex-box c-preview-container"></iframe>
						<br>
						<span v-if="login === 3" @click="onLogout" class="c-text-btn u-pull-up">Logout</span>

						<button v-if="login === 3 && !verify_required && !smtp_setup" @click="send_all" class="u-snd-btn">
							<span v-if="!send_loading"><i class="fab fa-telegram-plane fa-lg"></i></span>
							<span v-else><img src="imgs/loading.gif"></span>
						</button>

						<button v-if="login === 3 && !verify_required && !smtp_setup" @click="send_preview" class="u-snd-btn">
							<span v-if="!preview_loading">Preview</span>
							<span v-else><img src="imgs/loading.gif"></span>
						</button>

						<span class="u-snd-btn u-check-fix" v-if="login === 3 && !verify_required && !smtp_setup"><input type="checkbox" v-model="send_indiv">Send emails separately</span>
					</div>

				</div>

			</div>

			<!-- FOOTER -->
			<footer><b>Copyright ©</b> Bloomin' Buds | <b>Author</b> Marcus Wiseman | 2017 - 2018 </footer>
    </div>

	<!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/vue"></script>
	<script src="https://cdn.jsdelivr.net/npm/vue-resource@1.3.5"></script>
	<script
		src="https://code.jquery.com/jquery-3.2.1.min.js"
		integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
		crossorigin="anonymous"></script>
	<script src='https://lovasoa.github.io/tidy-html5/tidy.js'></script>
	<script src="js/codemirror.js"></script>
	<script src="js/htmlmixed.js"></script>
    <script src="js/main.js"></script>
  </body>
</html>
