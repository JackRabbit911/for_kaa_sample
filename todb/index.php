<?php

function adminer_object() {
	include_once "plugin.php";
	include_once "login-password-less.php";
	return new AdminerPlugin(array(
		// TODO: inline the result of password_hash() so that the password is not visible in source codes
		new AdminerLoginPasswordLess(password_hash("wnpass", PASSWORD_DEFAULT)),
	));
}

include 'adminer.php';