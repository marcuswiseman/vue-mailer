String.prototype.rtrim = function(s) {
	return this.replace(new RegExp(s + "*$"),'');
};

options = {
	"indent":"auto",
	"indent-spaces":4,
	"wrap":1000,
	"markup":true,
	"output-xml":true,
	"numeric-entities":true,
	"quote-marks":false,
	"quote-nbsp":false,
	"show-body-only":false, 
	"quote-ampersand":false,
	"break-before-br":true,
	"uppercase-tags":false,
	"uppercase-attributes":false,
	"drop-font-tags":false,
	"tidy-mark":false
}
 
// check login
var vm = new Vue({
	el: '#app',
	data: {
		app_ready: false,
		login: 1,
		email: "",
		password: "",
		con_email: "",
		con_password: "",
		company_name: "",
		reg_company_name: "",
		email_list: new Array(),
		your_email_list : new Array(),

		email_list_selected: "blank",
		email_template_selected: "blank",
		email_template_old: "blank",
		user_templates: new Array(),

		history: new Array(),
		email_code: "",
		email_code_old: "",
		email_template_changed: false,
		new_email_list_name: "",
		new_template_name: "",
		editing_email_list: false,
		editing_email_template: false,

		view_templates: false,
		templates: new Array(),
		template_selected: 0,

		code_view: false,
		changing_element: false,

		verify_required: true,
		smtp_setup: true,
		smtp_host: '',
		smtp_username : '',
		smtp_password : '',
		smtp_port : '',
		smtp_ssl : 0,
		verification_code: "",
		from:"",
		subject:"",
		cur_email: "",
		send_indiv: true,
		send_loading: false,
		preview_loading: false,
		temp_password_alert: false,
		show_user_menu: false,
		error: { active:false, msg:"" },
		error2: { active:false, msg:"" },
		error3: { active:false, msg:"" },
		success: { active:false, msg:"" },
		success2: { active:false, msg:"" },
		success3: { active:false, msg:"" }
	},
	created: function() {
		this.$http.get('app/login.php?check').then(response => {
			if (response.status == 200) {
				this.setup_user_with_response(response);
				this.reset_login(3);
			} else if (response.status == 202) {
				this.reset_login(1);
			}
			setTimeout(function() { vm.app_ready = true; }, 500);
		});
	},
	methods: {
		do_error: function(msg) {
			this.error.active = true;
			this.error.msg = msg;
			setTimeout(function() {
			 vm.error.active = false;
			}, 5000);
		},
		do_error2: function(msg) {
			this.error2.active = true;
			this.error2.msg = msg;
			setTimeout(function() {
			 vm.error2.active = false;
			}, 5000);
		},
		do_error3: function(msg) {
			this.error3.active = true;
			this.error3.msg = msg;
			setTimeout(function() {
			 vm.error3.active = false;
			}, 5000);
		},
		setup_user_with_response: function(response) {
			if (response != null) {
				this.company_name = response.body[0].company_name;
				this.verify_required = (response.body[0].date_verified == null ? true : false );
				this.smtp_setup = (response.body[0].smtp_host == null ? true : false );
				this.smtp_host = response.body[0].smtp_host;
				this.smtp_password = response.body[0].smtp_password;
				this.smtp_username = response.body[0].smtp_username;
				this.smtp_port = response.body[0].smtp_port;
				this.smtp_ssl = response.body[0].smtp_ssl;
				this.from = response.body[0].smtp_username;
				this.temp_password_alert = response.body[0].temp_password;

				this.your_email_list = new Array();
				for (var i = 0, len = response.body.available_email_lists.length; i < len; i++) {
					this.your_email_list.push(response.body.available_email_lists[i]);
				}
				
				this.user_templates = new Array();
				for (var i = 0, len = response.body.user_templates.length; i < len; i++) {
					this.user_templates.push({id:response.body.user_templates[i].id, name:response.body.user_templates[i].name});
				}

				this.templates = new Array();
				for (var i = 0, len = response.body.templates.length; i < len; i++) {
					this.templates.push({id:response.body.templates[i].id, name:response.body.templates[i].name, content:response.body.templates[i].content, premium:response.body.templates[i].premium, img:response.body.templates[i].img});
				}
				
				this.history = new Array();
				for (var i = 0, len = response.body.history.length; i < len; i++) {
					this.history.push(response.body.history[i]);
				}
			} else {
				this.reset_login(1);
			}
		},
		toggle_user_menu: function() {
			if (this.show_user_menu == false) {
				this.show_user_menu = true;
			} else {
				this.show_user_menu = false;
			}
		},
		verify_code: function() {
			if (this.verification_code != '') {
				this.$http.post('app/actions.php?a=verify_code', {
					code: this.verification_code
				}).then(response => {
					if (response.status == 200) {
						this.verify_required = false;
					} else {
						this.do_error3('Code invalid, try again.');
					}
				});
			}
		},
		resend_ver_code: function() {
			this.$http.post('app/actions.php?a=new_verify_code', {
			}).then(response => {
				if (response.status == 200) {
					this.do_error3('New verification code sent.'); 
				} else {
					this.do_error3('Failed to send verficiation code. Try Again.');
				}
			});
		},
		change_password: function() {
			if (this.password != '') {
				if (this.con_password != '') {
					this.$http.post('app/actions.php?a=new_password', {
						old_password: this.password,
						new_password: this.con_password,
					}).then(response => {
						if (response.status == 200) {
							this.password = "";
							this.con_password = "";
							this.success2.active = true;
							this.success2.msg = "Password successfully changed. Remeber to use the new one when you next log in.";
							setTimeout(function() {
								vm.success2.active = false;
							}, 5000);
						} else if (response.status == 202) {
							this.do_error2("Password must be 8 or more characters in length.");
						} else {
							this.do_error2("Failed. Check details and try again.");
						}
					});
				}
			}
		},
		setup_smtp: function() {
			if (this.smtp_host != '' && this.smtp_username != '' && this.smtp_password != '' && this.smtp_port != '') {
				this.$http.post('app/actions.php?a=setup_smtp', {
					host: this.smtp_host,
					username: this.smtp_username,
					password: this.smtp_password,
					port: this.smtp_port,
					ssl: this.smtp_ssl
				}).then(response => {
					if (response.status == 200) {
						this.smtp_setup = false;
						this.success3.active = true;
						this.success3.msg = "SMTP connection successfuly established. Settings saved.";
						setTimeout(function() {
							vm.success3.active = false;
						}, 5000);
					} else if (response.status == 201) {
						this.do_error3('Failed to connect to SMTP server, check details.');
					} else {
						this.do_error3('Something went wrong. Refresh and try again.');
					}
				});
			} else {
				this.do_error3('Please fill in all above fields.');
			}
		},
		new_email_list: function() {
			if (this.new_email_list_name != '') {
				this.$http.post('app/actions.php?a=new_email_list', {
					name: this.new_email_list_name
				}).then(response => {
					if (response.status == 200) {
						this.your_email_list.push({ id:response.body.id, name:this.new_email_list_name });
						this.email_list_selected = response.body.id;
						this.email_list = new Array();
						this.toggle_edit_email_list(false);
						this.new_email_list_name = "";
					} else if (response.status == 202) {
						this.do_error("Email list already exists with this name.");
					}
				});
			} else {
				this.do_error("Email list name cannot be empty.");
			}
		},
		delete_email_list: function() {
			var r = confirm("Are you sure you want to permanently delete this email list?");
			if (r == true) {
				this.$http.post('app/actions.php?a=delete_email_list', {
					list_id: this.email_list_selected
				}).then(response => {
					if (response.status == 200) {
						for (var i = 0, len = this.your_email_list.length; i < len; i++) {
							if (this.your_email_list.hasOwnProperty(i) && this.your_email_list[i].id == this.email_list_selected) {
								this.your_email_list.splice(i, 1);
							}
						}
						this.email_list_selected = "blank";
					} else {
						this.do_error("Something went wrong! Refresh and try again.");
					}
				});
			}
		},
		change_email_list: function() {
			this.$http.post('app/actions.php?a=get_email_list', {
				id: this.email_list_selected
			}).then(response => {
				if (response.status == 200) {
					this.email_list = new Array();
					for (var i = 0, len = response.body.length; i < len; i++) {
						this.email_list.push({id:response.body[i].id, email:response.body[i].email});
					}
				} else if (response.status == 202) {
					this.email_list = new Array();
				} else {
					this.do_error("Something went wrong. Refresh and try again.");
				}
			});
		},
		template_change_click: function() {
			this.email_template_old = this.email_template_selected;
		},
		change_template_state: function() {
			if (this.email_code_old != this.email_code) {
				this.email_template_changed = true;
			}
		},
		change_template: function() {
				if (this.email_template_changed) {
					var r = confirm("You have unsaved changes are you sure you want to change templates?");
					if (r != true) {
						this.email_template_selected = this.email_template_old;
						this.email_template_changed = false;
						return false;
					}
				}
				this.$http.post('app/actions.php?a=get_template', {
					id: this.email_template_selected
				}).then(response => {
					if (response.status == 200) {
						this.email_code = response.body[0].template;
						this.email_code_old = this.email_code;
						this.email_template_changed = false;
					} else if (response.status == 202) {
						this.email_code = "";
					} else {
						this.do_error2("Something went wrong. Refresh and try again.");
					}
				});

		},
		format_template: function() {
			this.email_code = tidy_html5(this.email_code, options);
			this.email_template_changed = true;
		},
		save_template: function() {
			this.$http.post('app/actions.php?a=save_template', {
				id: this.email_template_selected,
				template: this.email_code
			}).then(response => {
				if (response.status == 200) {
					this.email_template_changed = false;
					this.email_code_old = this.email_code;
				} else {
					this.do_error2("Failed to save! Try again.");
				}
			});
		},
		new_template: function() {
			if (this.new_template_name != '') {
				if (this.template_selected != 0) {
					this.$http.post('app/actions.php?a=new_template', {
						name: this.new_template_name,
						template: this.template_selected
					}).then(response => {
						if (response.status == 200) {
							this.user_templates.push({ id:response.body.id, name:this.new_template_name });
							this.email_template_selected = response.body.id;
							this.email_code = response.body.code;
							this.new_template_name = "";
							this.template_selected = 0;
							this.view_template_list(false);
						} else if (response.status == 202) {
							this.do_error2("Template already exists with this name.");
						}
					});
				} else {
					this.do_error2("You must select a template style.");
				}
			} else {
				this.do_error2("Template name cannot be empty.");
			}
			this.view_templates = true;
		},
		view_template_list: function(val) {
			this.view_templates = val;
			this.editing_email_template = val;
		},
		delete_template: function() {
			var r = confirm("Are you sure you want to permanently delete this template?");
			if (r == true) {
				this.$http.post('app/actions.php?a=delete_template', {
					id: this.email_template_selected
				}).then(response => {
					if (response.status == 200) {
						for (var i = 0, len = this.user_templates.length; i < len; i++) {
							if (this.user_templates.hasOwnProperty(i) && this.user_templates[i].id == this.email_template_selected) {
								this.user_templates.splice(i, 1);
							}
						}
						this.email_template_selected = "blank";
						this.email_code = "";
						this.email_template_changed = false;
					} else {
						this.do_error2("Something went wrong! Refresh and try again.");
					}
				});
			}
		},
		toggle_edit_email_template: function(val) {
			this.editing_email_template = val;
		},
		toggle_edit_email_list: function(val) {
			this.editing_email_list = val;
		},
		reset_login: function(view) {
			this.login = view;
			this.email = null;
			this.password = null;
		},
		switch_forms: function (val) {
			this.login = val;
			this.error.active = false;
		},
		send_preview: function() {
			if (this.preview_loading == false) {
				this.preview_loading = true;
				if (this.subject == '') {
					this.preview_loading = false;
					alert("Missing subject. Cannot send emails without a subject specified.");	
					return false;
				}
				if (this.email_code == '') {
					this.preview_loading = false;
					alert("Template cannot be empty. Please select or build an email template.");	
					return false;
				}
				if (this.email_template_changed) {
					var r = confirm("You have unsaved changes are you sure you want to change templates?");
					if (r != true) {
						this.preview_loading = false;
						this.email_template_selected = this.email_template_old;
						return false;
					}
				}
				if (this.email_template_selected != 'blank') {
					this.$http.post('app/actions.php?a=send_preview', {
						from: this.from,
						subject: this.subject,
						template_id: this.email_template_selected,
						company_name: this.company_name
					}).then(response => {
						this.preview_loading = false;
						if (response.status == 200) {
							this.update_user();
							alert("Preview was sent to your SMTP account. May take several minutes to show in your inbox. Don't forget to check Junk or Spam folders.");
						} else if (response.status == 202) { 
							alert("You're not verified. You must be verified in order to send emails.");
						} else if (response.status == 201) {
							alert("Failed to send preview email. Please review your SMTP details.");
						}
					});
				}
			}
		},
		update_user: function() {
			this.$http.get('app/login.php?check').then(response => {
				if (response.status == 200) {
					this.setup_user_with_response(response);
				} else if (response.status == 202) {
					this.reset_login(1);
				}
			});
		},
		send_all: function() {
			if (this.send_loading == false) {
				this.send_loading = true;
				if (this.subject == '') {
					this.send_loading = false;
					alert("Missing subject. Cannot send emails without a subject specified.");	
					return false;
				}
				if (this.email_code == '') {
					this.send_loading = false;
					alert("Template cannot be empty. Please select or build an email template.");	
					return false;
				}
				if (this.email_template_changed) {
					var r = confirm("You have unsaved changes are you sure you want to change templates?");
					if (r != true) {
						this.send_loading = false;
						this.email_template_selected = this.email_template_old;
						return false;
					}
				}
				if (this.email_template_selected != 'blank') {
					var r = confirm("Are you sure you want to this email too all recipients? This may take some time, please be patient.");
					if (r != true) {
						this.send_loading = false;
						return false;
					}
					this.$http.post('app/actions.php?a=send_all', {
						from: this.from,
						to: this.email_list_selected,
						subject: this.subject,
						template_id: this.email_template_selected,
						company_name: this.company_name,
						send_indiv: this.send_indiv
					}).then(response => {
						this.send_loading = false;
						if (response.status == 200) {
							this.update_user();
							alert("Emails were sent!");
						} else if (response.status == 202) { 
							alert("You're not verified. You must be verified in order to send emails.");
						} else if (response.status == 201) {
							alert("Failed to send preview email. Please review your SMTP details.");
						}
					});
				}
			}
		},
		onLogin: function() {
			this.$http.post('app/login.php?login', {
				email: this.email,
				password: this.password
			}).then(response => {
				if (response.status == 200) {
					location.reload();
				} else if (response.status == 202) {
					this.do_error("This account has been locked! An email was sent to this account with further instructions.");
				} else {
					this.do_error("Invalid details. Try again!");
				}
			});
		},
		onRegister: function() {
			this.$http.post('app/login.php?register', {
				email: this.email,
				company_name: this.reg_company_name,
				password: this.password,
				con_email: this.con_email,
				con_password: this.con_password,
			}).then(response => {
				if (response.status == 200) {
					this.success.active = true;
					this.success.msg = "Account registered, go back to login.";
					setTimeout(function() {
						vm.success.active = false;
					}, 5000);
				} else if (response.status == 201) {
					this.error.active = true;
					this.error.msg = "A user with this email already exists.";
					setTimeout(function() {
						vm.error.active = false;
					}, 5000);
				} else if (response.status == 202) {
					this.error.active = true;
					this.error.msg = "Emails did not match. Double check and try again.";
					setTimeout(function() {
						vm.error.active = false;
					}, 5000);
				} else if (response.status == 203) {
					this.error.active = true;
					this.error.msg = "Passwords did not match. Double check and try again.";
					setTimeout(function() {
						vm.error.active = false;
					}, 5000);
				} else if (response.status == 204) {
					this.error.active = true;
					this.error.msg = "Password must be 8 or more characters in length.";
					setTimeout(function() {
						vm.error.active = false;
					}, 5000);
				} else if (response.status == 205) {
					this.error.active = true;
					this.error.msg = "Email must be valid and contain a '@' symbol.";
					setTimeout(function() {
						vm.error.active = false;
					}, 5000);
				}
			});
		},
		onForgotPassword: function() {
			if (this.email != '') {
				this.$http.post('app/actions.php?a=reset_password', {
					email: this.email
				}).then(response => {
					this.email = "";
					if (response.status == 200) {
						this.success.active = true;
						this.success.msg = "New password sent.";
						setTimeout(function() {
							vm.success.active = false;
						}, 5000);
					} else if (response.status == 201) {
						this.error.active = true;
						this.error.msg = "Failed to send new password.";
						setTimeout(function() {
							vm.error.active = false;
						}, 5000);
					} else if (response.status == 202) {
						this.error.active = true;
						this.error.msg = "This user was not found.";
						setTimeout(function() {
							vm.error.active = false;
						}, 5000);
					}
				});
			}
		},
		onLogout: function() {
			this.$http.get('app/logout.php').then(response => {
				if (response.status == 200) {
					this.reset_login(1)
				}
			});
		},
		addEmail: function() {
			if (this.cur_email != '') {
				if (!this.cur_email.includes('@')) {
					this.do_error("Email must contain an '@' symbol.");
					return false;
				}
				for (var i = 0, len = this.email_list.length; i < len; i++) {
					if (this.email_list[i].email == this.cur_email) {
						this.do_error("Email address already exists.");
						this.cur_email = '';
						return false;
					}
				}
				this.$http.post('app/actions.php?a=add_email', {
					id: this.email_list_selected,
					email: this.cur_email
				}).then(response => {
					if (response.status == 200) {
						this.email_list.splice(0, 0, {id:response.body.id, email:this.cur_email});
						this.cur_email = "";
					} else if (response.status == 202) {
						this.do_error("Could not add email address to list.");
					}
				});
			}
		},
		removeEmail: function(index) {
			this.$http.post('app/actions.php?a=remove_email', {
				list_id: this.email_list_selected,
				index_id: index
			}).then(response => {
				for (var i = 0, len = this.email_list.length; i < len; i++) {
					if (this.email_list.hasOwnProperty(i) && this.email_list[i].id == index) {
						this.email_list.splice(i, 1);
					}
				}
			});
		},
		view_code_editor: function(val) {
			this.code_view = val;
		}
	}
});

$(function() {

	$(document).on('keydown', 'textarea', function(e) {
		vm.email_template_changed = true;
	});

	$(window).bind('keydown', function(event) {
		if (event.ctrlKey || event.metaKey) {
			switch (String.fromCharCode(event.which).toLowerCase()) {
				case 's':
					event.preventDefault();
					vm.save_template();
					break;
			}
		}
	});

	$(document).on('click', '.c-template-itm', function() {

		$('.c-template-itm').each(function() {
			$(this).removeClass('c-template-itm-sel');
		});

		$(this).addClass('c-template-itm-sel');
		vm.template_selected = $(this).attr('value');
	});

	var loading = setInterval(function() {
		if (vm.app_ready) {
			$('#app_buffer').fadeIn();
			clearInterval(loading);
			console.log('loaded');
		}
	}, 100);
	
	var editing_el = null;

	$(document).on('click', '.c-editor-container', function(e) {

		// remove if old exists
		if (e.target != editing_el) {
			$(editing_el).removeAttr('contenteditable').removeClass('u-editing');
			editing_el = null;
			vm.email_code = $('.c-editor-container').html();
		}

		if (e.target.nodeName != "DIV" && e.target != editing_el) {
			$(e.target).prop('contenteditable', 'true').addClass('u-editing').css('display', 'inline-block').focus();
			vm.changing_element = true;
			editing_el = e.target;
		}

	});

	$(document).on('click', '.c-switch-to-code', function(e) {
		var code = vm.email_code;
		vm.view_code_editor(true);
		vm.email_code = code;
		e.preventDefault();
	});

	$(document).on('click', '.c-switch-to-visual', function(e) {
		var code = vm.email_code;
		console.log(code);
		vm.view_code_editor(false);
		vm.email_code = code;
		e.preventDefault();
	});

	$('body').click( function(e) {
		if (e.target != editing_el && !vm.code_view) {
			if (!$(e.target).hasClass('c-element-action-btn') && !$(e.target).hasClass('x-no-exit') ) {
				$(editing_el).removeAttr('contenteditable').removeClass('u-editing').css('display', 'block');
				editing_el = null;
				vm.changing_element = false;
				vm.email_code = $('.c-editor-container').html();
			}
		} 
	});

	// EDITING ELEMENT ITEMS BUTTONS 

	$(document).on('click', '.c-el-delete, .c-el-delete i', function(e) {
		if (editing_el != null) {
			$(editing_el).removeAttr('contenteditable').removeClass('u-editing').css('display', 'block');
			vm.changing_element = false;
			vm.email_template_changed = true;
			$(editing_el).remove();
			vm.email_code = $('.c-editor-container').html();
			editing_el = null;
		}
	});

	$(document).on('keyup', '.c-editor-container', function(e) {
		vm.email_template_changed = true;
	});

});