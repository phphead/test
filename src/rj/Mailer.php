<?php namespace Rj;

class Mailer
{
    protected static function _encode($value)
    {
        return '=?utf-8?b?' . base64_encode($value) . '?=';
    }

    public function send($from, $to, $subject, $body)
    {
        $headers = array(
            'Content-Type: text/plain; charset=utf-8',
        );

        if (is_array($from) && count($from) == 2) {
            $headers[] = 'From: ' . static::_encode($from[0]) . ' <' . $from[1] . '>';
        } else if ($from) {
            $headers[] = 'From: ' . $from[1];
        }

        $reporting = error_reporting(0);
        $result = mail($to, static::_encode($subject), $body, implode("\r\n", $headers) . "\r\n");
        error_reporting($reporting);

        return $result;
    }
}
