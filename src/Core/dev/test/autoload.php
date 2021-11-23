<?php
use WN\Core\Autoload;

// var_dump(class_exists('VN\Vendor'));

// Autoload::add('vendor', 'VN');

// var_dump(class_exists('VN\Vendor'));

var_dump(class_exists('PHPMailer\PHPMailer\SMTP'));

Autoload::add('src/Core/vendor/PHPMailer', 'PHPMailer\PHPMailer');

var_dump(class_exists('PHPMailer\PHPMailer\SMTP'));
