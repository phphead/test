<?php namespace Rj;

class Random {

    /**
     * Generate random string
     *
     * @param int $length
     * @return string
     */
    public static function generateKey($length = 40) {
        $chars = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k',
            'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x',
            'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K',
            'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
            'Y', 'Z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        $ret = '';
        for ($i = 0; $i < $length; $i++) {
            $ret .= $chars[mt_rand(0, count($chars) - 1)];
        }
        return $ret;
    }

}
