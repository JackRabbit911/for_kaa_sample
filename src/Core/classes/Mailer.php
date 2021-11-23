<?php
namespace WN\Core;

use WN\Core\Autoload;
use WN\Core\Pattern\Settings;
use PHPMailer\PHPMailer\{PHPMailer, SMTP, Exception};

class Mailer
{
    use Settings;

    public static $default_charset = 'utf-8';
    public static $is_smtp = true;
    public static $is_imap = true;
    public static $smtp;
    public static $smtp_port;
    public static $pop3;
    public static $pop3_box;
    public static $imap;
    public static $imap_box;
    public static $mailboxes;

    public static $config_path = '/src/App/config/host/';

    protected $is_html = true;

    public static function sendMail($from, $to, $subject, $body, $options = [])
    {
        list($from, $sign) = (is_array($from)) ? $from : [$from, null];
        list($to, $name) = (is_array($to)) ? $to : [$to, null];

        $mail = new static($from, $sign);
        $mail->to($to, $name)->subject($subject)->body($body);

        foreach($options AS $key => $value)
        {
            if(is_array($value)) call_user_func_array([$mail, $key], $value);
            else call_user_func([$mail, $key], $value);
        }

        $mail->send();
    }

    public static function getStatus($mailbox = null, $password = null)
    {
        $config = static::get_config();
        if(isset($config['is_imap']) && $config['is_imap'] === true)
        {
            $imapbox = $config['imap_box'];
            if(!$mailbox) $mailbox = array_key_first($config['mailboxes']);
            if(!$password) $password = $config['mailboxes'][$mailbox];

            $imap = imap_open($imapbox, $mailbox, $password);
            $status = imap_status($imap, $imapbox, SA_ALL);
            imap_close($imap);
        }
        else $status = (object)['flags' => 0, 'messages' => 0, 'recent' => 0, 'unseen' => 0];

        return $status;
    }

    protected static function get_config()
    {
        global $config_cache;
        if(isset($config_cache['mail'])) $config = $config_cache['mail'];
        else
        {
            $config_path = $_SERVER['DOCUMENT_ROOT'].static::$config_path;
            if(!is_file(($config_file = $config_path.$_SERVER['SERVER_NAME'].'.php')))
                $config_file = $config_path.'default.php';

            $config_cache = include $config_file;
            $config = $config_cache['mail'];
        }
        return $config;
    }

    public function __construct($username = null, $sign = null)
    {
        Autoload::add('src/Core/vendor/PHPMailer', 'PHPMailer\PHPMailer');

        static::settings(static::get_config());

        if(!$username) $username = array_key_first(static::$mailboxes);

        $this->mail = new PHPMailer();
        $this->mail->CharSet = static::$default_charset;

        if(static::$is_smtp)
        {
            $this->mail->isSMTP();
            $this->mail->Host = static::$smtp;
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $username;
            $this->mail->Password = static::$mailboxes[$username];
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = static::$smtp_port;
        }

        $this->mail->isHTML($this->is_html);
        $this->mail->setFrom($username, $sign);
    }

    public function isHTML($html = true)
    {
        $this->is_html = $html;
        $this->mail->isHTML($this->is_html);
        return $this;
    }

    public function to($address, $name = null)
    {
        $this->mail->addAddress($address, $name);
        return $this;
    }

    public function replyTo($to)
    {
        $this->mail->addReplyTo($to);
        return $this;
    }

    public function cc($cc)
    {
        $this->mail->addCC($cc);
        return $this;
    }

    public function bcc($bcc)
    {
        $this->mail->addBCC($bcc);
        return $this;
    }

    public function attach($path, $new_name = null)
    {
        $this->mail->addAttachment($path, $new_name);
        return $this;
    }

    public function subject($text)
    {
        $this->mail->Subject = $text;
        return $this;
    }

    public function body($body)
    {
        $this->mail->Body = $body;
        return $this;
    }

    public function altBody($text)
    {
        $this->mail->AltBody = $text;
        return $this;
    }

    public function send()
    {
        $this->mail->send();
    }
}