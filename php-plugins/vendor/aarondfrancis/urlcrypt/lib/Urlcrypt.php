<?php

/**
 * URLcrypt
 *
 * PHP library to securely encode and decode short pieces of arbitrary binary data in URLs.
 *
 * (c) Aaron Francis
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code.
 */

namespace Urlcrypt;

class Urlcrypt
{
    public static $table = "1bcd2fgh3jklmn4pqrstAvwxyz567890";
    public static $key = "";
    protected static $cipher = MCRYPT_RIJNDAEL_128;
    protected static $mode = MCRYPT_MODE_CBC;

    public static function encode($str)
    {
        $n = strlen($str) * 8 / 5;
        $arr = str_split($str, 1);

        $m = "";
        foreach ($arr as $c) {
            $m .= str_pad(decbin(ord($c)), 8, "0", STR_PAD_LEFT);
        }

        $p = ceil(strlen($m) / 5) * 5;

        $m = str_pad($m, $p, "0", STR_PAD_RIGHT);

        $newstr = "";
        for ($i = 0; $i < $n; $i++) {
            $newstr .= self::$table[bindec(substr($m, $i * 5, 5))];
        }

        return $newstr;
    }

    public static function decode($str)
    {
        $n = strlen($str) * 5 / 8;
        $arr = str_split($str, 1);

        $m = "";
        foreach ($arr as $c) {
            $m .= str_pad(decbin(array_search($c, self::$table)), 5, "0", STR_PAD_LEFT);
        }

        $oldstr = "";
        for ($i = 0; $i < floor($n); $i++) {
            $oldstr .= chr(bindec(substr($m, $i * 8, 8)));
        }

        return $oldstr;
    }

    public static function encrypt($str)
    {
        if (self::$key === "") {
            throw new \Exception('No key provided.');
        }

        $key = pack('H*', self::$key);

        $iv_size = mcrypt_get_iv_size(self::$cipher, self::$mode);

        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $str = utf8_encode($str);

        $ciphertext = mcrypt_encrypt(self::$cipher, $key, $str, self::$mode, $iv);

        $ciphertext = $iv . $ciphertext;

        return self::encode($ciphertext);
    }

    public static function decrypt($str)
    {
        if (self::$key === "") {
            throw new \Exception('No key provided.');
        }

        $key = pack('H*', self::$key);

        $str = self::decode($str);

        $iv_size = mcrypt_get_iv_size(self::$cipher, self::$mode);
        $iv_dec = substr($str, 0, $iv_size);

        $str = substr($str, $iv_size);

        $str = mcrypt_decrypt(self::$cipher, $key, $str, self::$mode, $iv_dec);

        // http://jonathonhill.net/2013-04-05/write-tests-you-might-learn-somethin/
        return rtrim($str, "\0");
    }
}

Urlcrypt::$table = str_split(Urlcrypt::$table, 1);
