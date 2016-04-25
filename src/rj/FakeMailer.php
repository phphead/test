<?php namespace Rj;

class MailerLog
{
    public static $_logger = 'email';

    public $triggerError = '';

    public function send($from, $to, $subject, $body)
    {
        if (is_array($from) && count($from) == 2) {
            $from = $from[0] . ' <' . $from[1] . '>';
        } else if (is_array($from)) {
            $from = implode('/', $from);
        }

        $reporting = error_reporting(0);

        if ($this->triggerError) {
            trigger_error($this->triggerError);
            $result = false;
        } else {
			\Logger::instance(static::$_logger)->log("Message from '$from' to '$to' with subject '$subject' and content\n$body");
            $result = true;
        }

        error_reporting($reporting);

        return $result;
    }
}
