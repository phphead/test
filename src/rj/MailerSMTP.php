<?php namespace Rj;

use Exception;

class MailerSMTP extends Mailer
{
    protected $_hostname = '127.0.0.1';

    public function send($from, $to, $subject, $body, $headers = '')
    {
        if (is_array($from) && count($from) == 2) {
            $encodedFrom = static::_encode($from[0]) . ' <' . $from[1] . '>';
        } else if (is_array($from)) {
            $encodedFrom = $from[1];
        } else {
            $encodedFrom = $from;
        }

        $config = Config::instance();
        if ($conn = fsockopen($config->smtp_hostname, 25, $errno, $errstr, 2)) {

            $hear = function($code) use ( & $conn) {
                $str = fgets($conn, 100);
                if (preg_match('/^(\d+)\ /iD', $str, $pock)) {
                    if ($pock[1] && $pock[1] == $code) {
                        return true;

                    } else {
                        throw new Exception("Unexpected code from SMTP server: " . $pock[1] . " (expected $code): " . $str);
                    }

                } else {
                    throw new Exception("Unexpected response from SMTP server: " . $str);
                }
            };

            $say = function($val, $code = null) use ( & $conn, & $hear) {
                fputs($conn, $val . "\n");
                return $code ? $hear($code) : null;
            };

			$_headers = explode("\n", $headers);
			if ( ! stristr(strtolower($headers), 'content-type')) {
				$_headers[] = 'Content-Type: text/plain; charset=utf-8';
			}

            $hear(220);
            $say('HELO ' . $config->smtp_hostname, 250);
            $say('MAIL FROM: ' . $config->mailer_sender_email, 250);
            $say('RCPT TO: ' . $to, 250);
            $say('DATA', 354);
            $say('Subject: ' . static::_encode($subject));
            $say('From: ' . $encodedFrom);
            $say('To: ' . $to);
			foreach ($_headers as $str)
            	$say($str);
            $say('');
            $say($body);
            $say('.', 250);
            $say('QUIT', 221);

            return true;
        }

        return false;
    }
}
