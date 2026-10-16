<?php

namespace App\Support;

use Illuminate\Encryption\Encrypter;

class Crypter
{
    private $key;
    private $cipher;
    private $encrypter;

    public function __construct($key)
    {
        $this->cipher = env("CRYPT_CIPHER", "aes-256-gcm");
        $this->setKey($key);
        $this->encrypter = new Encrypter($key, $this->cipher);
    }

    public function encrypt($toEncrypt)
    {
        return $this->encrypter->encrypt($toEncrypt);
    }

    public function decrypt($toDecrypt)
    {
        return $this->encrypter->decrypt($toDecrypt);
    }

    public function getKey()
    {
        return $this->key;
    }

    public function setKey($key): self
    {
        $this->key = $key;

        return $this;
    }

    public static function generateKey()
    {

        $keyLength = env("RANDOM_KEY_LENGTH", 32);
        $theKey = "";
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!%*-/?;";
        $charsLength = strlen($chars);

        while (strlen($theKey) < $keyLength) {
            $pos = random_int(0, $charsLength - 1);
            $theKey .= substr($chars, $pos, 1);
        }

        return $theKey;
    }
}
