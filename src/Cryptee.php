<?php
/**
 * Copyright (c) 2008 · Kerem Güneş
 * Apache License 2.0 · https://github.com/k-gun/cryptee
 */
declare(strict_types=1);

namespace Cryptee;

/**
 * @package Cryptee
 * @object  Cryptee\Cryptee
 * @author  Kerem Güneş
 */
class Cryptee
{
    /**
     * Output types.
     * @const int
     */
    const B64 = 1, // Base64
          HEX = 2; // Hex

    /**
     * Key length.
     * @const int
     */
    const KEY_LENGTH = 128;

    /**
     * Key.
     * @var string
     */
    private string $key;

    /**
     * Output type (base64 or hex, for readable strings)
     * @var int
     */
    private int $type = self::B64;

    /**
     * Constructor.
     *
     * @param string $key
     * @param int    $type
     */
    public function __construct(string $key, int $type = null)
    {
        // Check key validity.
        if (strlen($key) < 6 || !(
            preg_match('~[a-z0-9]+~i', $key) &&
            preg_match('~[_=&"\.\+\-\*\?\']+~', $key)
        )) {
            throw new CrypteeException(sprintf(
                "Key length must be at least 6 chars and contain alp-num & printable chars!\n".
                "Pick up random this key generated for once: '%s'\n",
                str_replace("'", "\'", self::generateKey())
            ));
        }

        $this->key = $key;

        if ($type) {
            $this->type = $type;
        }
    }

    /**
     * Crypt.
     *
     * @param  string $input
     * @return string
     */
    public function crypt(string $input): string
    {
        $ret = b'';
        $key = [];
        $cnt = [];

        for ($i = 0, $length = strlen($this->key); $i < 255; $i++) {
            $key[$i] = ord(substr($this->key, ($i % $length) + 1, 1));
            $cnt[$i] = $i;
        }

        for ($i = 0, $x = 0; $i < 255; $i++) {
            $x = ($x + $cnt[$i] + $key[$i]) % 256;
            $s = $cnt[$i];
            $cnt[$i] = isset($cnt[$x]) ? $cnt[$x] : 0;
            $cnt[$x] = $s;
        }

        for ($i = 0, $x = -1, $y = -1, $length = strlen($input); $i < $length; $i++) {
            $x = ($x + 1) % 256;
            $y = ($y + $cnt[$x]) % 256;
            $z = $cnt[$x];
            $cnt[$x] = isset($cnt[$y]) ? $cnt[$y] : 0;
            $cnt[$y] = $z;
            $ord  = ord(substr($input, $i, 1)) ^ $cnt[($cnt[$x] + $cnt[$y]) % 256];
            $ret .= chr($ord);
        }

        return $ret;
    }

    /**
     * Encode.
     *
     * @param  string $input
     * @param  bool   $translate
     * @return string
     */
    public function encode(string $input, bool $translate = false): string
    {
        $input = $this->crypt($input);
        if ($this->type == self::B64) {
            $input = base64_encode($input);
            if ($translate) {
                $input = rtrim(strtr($input, '+/', '-_'), '=');
            }
        } elseif ($this->type == self::HEX) {
            $input = bin2hex($input);
        }

        return $input;
    }

    /**
     * Decode.
     *
     * @param  string $input
     * @param  bool   $translate
     * @return string
     */
    public function decode(string $input, bool $translate = false): string
    {
        if ($this->type == self::B64) {
            if ($translate) {
                $input = strtr($input, '-_', '+/');
            }
            $input = base64_decode($input);
            $input = $this->crypt($input);
        } elseif ($this->type == self::HEX) {
            $input = $this->crypt(hex2bin($input));
        }

        return $input;
    }

    /**
     * Generate key.
     *
     * @param  int $length
     * @return string
     */
    public static function generateKey(int $length = self::KEY_LENGTH): string
    {
        $key = '';

        srand();
        for ($i = 0; $i < $length; $i++) {
            $key .= chr(rand(33, 126));
        }

        return $key;
    }
}
