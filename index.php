<?php
if($_SERVER["HTTPS"] != "on") {
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}
?>

<html> 
  <head>
		<title>Wisemailer.com</title>

		<meta name="Description" content="Wisemailer.com provides a free to use web-based tool that offers small & medium sized businesses the ability to seemingly send emails to their customers. Fully responsive for both mobile/tablet and desktop usage. Join now for FREE at no cost, premium features available.">
		<meta name="Keywords" content="wisemailer, wise, mailer, smtp, freware, online tool, mailer, email, email templates, templates">

		<meta name="viewport" content="initial-scale=1, maximum-scale=1">
		<meta charset="utf-8">

		<!-- STLYES -->
		<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet">
		<link href="https://use.fontawesome.com/releases/v5.0.1/css/all.css" rel="stylesheet">
		<link rel="stylesheet" type="text/css" href="css/stylesheet.css">

		<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
		<link rel="icon" href="/favicon.ico" type="image/x-icon">
  </head>
  <body>

    <div id="app" class="o-wrapper" >
			
			<div v-if="!app_ready">
				<img src="imgs/loading2.gif" style="margin-top:200px; height:120px; width:120px;">
			</div>
			<div id="app_buffer" v-if="app_ready">

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
							<p>Before you can send any emails you need to setup your SMTP settings.</p><br>
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
							<h6 class="o-title">Email Body</h6>

							<div id="codeConatiner" class="o-flex-box">

								<select v-if="!editing_email_template" v-on:click="template_change_click" v-on:change="change_template" v-model="email_template_selected">
									<option value="blank" selected>-</option>
									<option v-for="template in user_templates" v-bind:value="template.id">{{template.name}}</option>
								</select>

								<input v-else class="u-bg-color-grey2 u-white-txt" type="text" v-model="new_template_name" placeholder="Template name">
								
								
								<button class="c-icon-btn" v-if="editing_email_template" v-on:click="new_template"><i class="fas fa-check"></i></button>
								<button class="c-icon-btn u-delete-btn" v-if="editing_email_template" v-on:click="view_template_list(false)"><i class="fas fa-times"></i></button>
								<button class="c-icon-btn" v-else v-on:click="view_template_list(true)"><i class="fas fa-plus"></i></button>
								<button v-on:click="delete_template" class="c-icon-btn u-delete-btn" v-if="email_template_selected != 'blank' && editing_email_template == false"><i class="fas fa-trash-alt"></i></button>
								
								
								<button title="Save Changes" v-if="email_template_changed && !editing_email_template" v-on:click="save_template" class="c-icon-btn u-float-right"><i class="fas fa-save"></i></button>
								<span v-if="email_template_changed  && !editing_email_template" class="u-error u-save-star u-float-right u-margin-left-10">*</span>
								
								<!-- <button title="Format Code" v-if="email_code != '' && !editing_email_template && code_view" v-on:click="format_template" class="c-icon-btn u-float-right c-element-action-btn"><i class="fas fa-file-code"></i></button> -->
								
								<!-- EDITING ELEMENT ITEMS -->
								<button title="Delete" class="c-el-delete c-element-action-btn" v-if="changing_element"><i class="x-no-exit fas fa-trash"></i></button>
								<button title="Font" class="c-element-action-btn" v-if="changing_element"><i class="x-no-exit fas fa-font"></i></button>
								<button title="Colors" class="c-element-action-btn" v-if="changing_element"><i class="x-no-exit fas fa-paint-brush"></i></button>
								<button title="Padding" class="c-element-action-btn" v-if="changing_element"><i class="x-no-exit fas fa-expand-arrows-alt"></i></button>

								<!-- EDITING ELEMENT ITEMS -->

								<button title="Switch to Code Editor" class="c-switch-to-code c-icon-btn" v-if="email_code != '' && !editing_email_template && !changing_element && !code_view && email_template_selected != 0 && email_template_selected != 'blank'"><i class="fas fa-code"></i></button>
								<button title="Switch to Visual Editor" class="c-switch-to-visual c-icon-btn" v-if="email_code != '' && !editing_email_template && !changing_element && code_view && email_template_selected != 0 && email_template_selected != 'blank'"><i class="fas fa-th-large"></i></button>

								<span v-if="error2.active" class="u-error">{{ error2.msg }}</span>
								
								<div class="c-templates-view-container" v-if="view_templates">
									<div v-for="template_item in templates" v-bind:value="template_item.id" class="c-template-itm"><span>{{ template_item.name }}<img v-bind:src="template_item.img"></span></div>
								</div>

								<div v-if="email_template_selected != 'blank' && !editing_email_template" id="code_container">
									<textarea v-if="code_view" id="code_editor" v-model='email_code' v-on:keyup="change_template_state" class="c-code-container" placeholder="Email body code. Handtype or select from the template above.">{{email_code}}</textarea>
									<div v-else id="template_editor" v-html="email_code" class="c-editor-container"></div>
								</div>
							
							</div>
						</div>

						<!-- TEMPLATE PREVIEW -->
						<div v-if="email_template_selected != 0 && email_template_selected != 'blank' " class="o-color-box u-bg-color-grey">
							<h6 class="o-title">Preview</h6>
							<p v-if="email_code == ''">Nothing to display.</p>
							<iframe v-if="email_code != ''" v-bind:srcdoc="email_code" id="previewContainer" class="o-flex-box c-preview-container"></iframe>
							<br>
							<div class="o-action-btns">
								<button v-if="login === 3 && !verify_required && !smtp_setup" @click="send_all" class="u-snd-btn">
									<span v-if="!send_loading"><i class="fab fa-telegram-plane fa-lg"></i> Send</span>
									<span v-else><img src="imgs/loading.gif"></span>
								</button>

								<button v-if="login === 3 && !verify_required && !smtp_setup" @click="send_preview" class="u-snd-btn u-float-left">
									<span v-if="!preview_loading"><i class="fas fa-eye"></i> Preview</span>
									<span v-else><img src="imgs/loading.gif"></span>
								</button>

								<!-- <button v-if="login === 3 && !verify_required && !smtp_setup" @click="send_all" class="u-snd-btn">
									<span v-if="!send_loading"><i style="margin-right:4px;" class="far fa-calendar-alt fa-lg"></i> Schedule</span>
								</button> -->

								<span class="u-snd-btn u-check-fix" v-if="login === 3 && !verify_required && !smtp_setup"><input type="checkbox" v-model="send_indiv">Send separately</span></br>
							</div>

						</div>

					</div>
					<!-- FOOTER -->
					<footer><b>Copyright © <b>Author</b> Wise Web Solutions | 2017 - 2018 <a class="c-footer-btn" href="../index.html">Home</a><span v-if="login === 3" @click="onLogout" class="c-footer-btn">Logout</span></footer>
				</div>

			</div>

    </div>

	<!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/vue"></script>
	<script src="https://cdn.jsdelivr.net/npm/vue-resource@1.3.5"></script>
	<script
		src="https://code.jquery.com/jquery-3.2.1.min.js"
		integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
		crossorigin="anonymous"></script>
	<script src='https://lovasoa.github.io/tidy-html5/tidy.js'></script>
    <script src="js/main.js"></script>
  </body>
</html>
