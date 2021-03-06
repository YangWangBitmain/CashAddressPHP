<?php

namespace CashAddress;

// (c) uMCCCS
// with some minor additions from Har01d @ blockchair.com

// This script uses some of the code and ideas from the following repositories:

// https://github.com/deadalnix/cashaddressed
// https://github.com/cryptocoinjs/base-x/blob/master/index.js - base-x encoding
// Forked from https://github.com/cryptocoinjs/bs58
// Originally written by Mike Hearn for BitcoinJ
// Copyright (c) 2011 Google Inc
// Ported to JavaScript by Stefan Thomas
// Merged Buffer refactorings from base58-native by Stephen Pair
// Copyright (c) 2013 BitPay Inc

// The MIT License (MIT)
// Copyright base-x contributors (c) 2016
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.

// Copyright (c) 2017 Pieter Wuille
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.

// ISC License
//
// Copyright (c) 2013-2016 The btcsuite developers
//
// Permission to use, copy, modify, and distribute this software for any
// purpose with or without fee is hereby granted, provided that the above
// copyright notice and this permission notice appear in all copies.
//
// THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
// WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
// MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
// ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
// WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
// ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
// OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.

// https://github.com/Bit-Wasp/bitcoin-php/blob/master/src/Bech32.php
// This is free and unencumbered software released into the public domain.
//
// Anyone is free to copy, modify, publish, use, compile, sell, or
// distribute this software, either in source code form or as a compiled
// binary, for any purpose, commercial or non-commercial, and by any
// means.
//
// In jurisdictions that recognize copyright laws, the author or authors
// of this software dedicate any and all copyright interest in the
// software to the public domain. We make this dedication for the benefit
// of the public at large and to the detriment of our heirs and
// successors. We intend this dedication to be an overt act of
// relinquishment in perpetuity of all present and future rights to this
// software under copyright law.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
// EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
// IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR
// OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
// ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
// OTHER DEALINGS IN THE SOFTWARE.
//
// For more information, please refer to <http://unlicense.org/>

class CashAddressException extends \Exception {

}

class CashAddress {

	const ALPHABET = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";
	const CHARSET = "qpzry9x8gf2tvdw0s3jn54khce6mua7l";
	const ALPHABET_MAP = [86  => 28, 100 => 36, 118 => 53, 50 => 1, 54 => 5, 57 => 8, 71 => 15, 74 => 17, 66 => 10, 77 => 20, 99 => 35, 75 => 18, 111 => 46,
						  112 => 47, 117 => 52, 52 => 3, 83 => 25, 113 => 48, 67 => 11, 68 => 12, 98 => 34, 104 => 40, 121 => 56, 85 => 27, 122 => 57, 109 => 44,
						  115 => 50, 56 => 7, 72 => 16, 90 => 32, 97 => 33, 102 => 38, 76 => 19, 84 => 26, 107 => 43, 78 => 21, 81 => 23, 88 => 30, 101 => 37,
						  65  => 9, 51 => 2, 103 => 39, 106 => 42, 116 => 51, 49 => 0, 53 => 4, 82 => 24, 105 => 41, 114 => 49, 70 => 14, 55 => 6, 69 => 13,
						  87  => 29, 89 => 31, 120 => 55, 80 => 22, 110 => 45, 119 => 54];
	const BECH_ALPHABET = [119 => 14, 52 => 21, 117 => 28, 121 => 4, 57 => 5, 116 => 11, 115 => 16, 104 => 23, 109 => 27, 55 => 30, 122 => 2, 114 => 3, 48 => 15,
						   99  => 24, 97 => 29, 50 => 10, 106 => 18, 108 => 31, 113 => 0, 56 => 7, 103 => 8, 101 => 25, 102 => 9, 100 => 13, 110 => 19, 107 => 22,
						   51  => 17, 53 => 20, 112 => 1, 120 => 6, 118 => 12, 54 => 26];
	const EXPAND_PREFIX = [2, 9, 20, 3, 15, 9, 14, 3, 1, 19, 8, 0];
	const BASE16 = ["0" => 0, "1" => 1, "2" => 2, "3" => 3, "4" => 4, "5" => 5, "6" => 6, "7" => 7, "8" => 8, "9" => 9, "a" => 10, "b" => 11, "c" => 12, "d" => 13,
					"e" => 14, "f" => 15];
	const t = 96; // (ord('a') & 0xe0)

	public function __construct()
	{
		if (((int)4294967296) == 0) {

			// Requires x64 system and PHP!
			throw new CashAddressException('Run it on a x64 system (+ 64 bit PHP)');
		}
	}

	static private function convertBits(array $data, $fromBits, $toBits, $pad = true)
	{
		$acc    = 0;
		$bits   = 0;
		$ret    = [];
		$maxv   = (1 << $toBits) - 1;
		$maxacc = (1 << ($fromBits + $toBits - 1)) - 1;

		for ($i = 0; $i < sizeof($data); $i++)
		{
			$value = $data[$i];

			if ($value < 0)
			{
				return false;
			}

			if ($value >> $fromBits != 0)
			{
				return false;
			}

			$acc  = (($acc << $fromBits) | $value) & $maxacc;
			$bits += $fromBits;

			while ($bits >= $toBits)
			{
				$bits  -= $toBits;
				$ret[] = (($acc >> $bits) & $maxv);
			}
		}

		if ($pad)
		{
			if ($bits)
			{
				$ret[] = ($acc << $toBits - $bits) & $maxv;
			}
		}
		else if ($bits >= $fromBits || ((($acc << ($toBits - $bits))) & $maxv))
		{
			return false;
		}

		return $ret;
	}

	static private function polyMod($var)
	{
		$c = gmp_init(1);

		for ($i = 0; $i < sizeof($var); $i++)
		{
			// $c0 = uRShift($c, 35);
			$c0 = gmp_div_q($c, "34359738368", GMP_ROUND_MINUSINF);
			// $c = (($c & 0x07ffffffff) << 5) ^ $var[$i];
			$c = gmp_xor(gmp_mul(gmp_and($c, "0x07ffffffff"), "32"), gmp_init($var[$i]));
			if (gmp_strval(gmp_mod($c0, "2")) != "0")
			{
				$c = gmp_xor($c, "0x98f2bc8e61");
			}
			if (gmp_strval(gmp_div_q(gmp_mod($c0, "4"), "2", GMP_ROUND_MINUSINF)) != "0")
			{
				$c = gmp_xor($c, "0x79b76d99e2");
			}
			if (gmp_strval(gmp_div_q(gmp_mod($c0, "8"), "4", GMP_ROUND_MINUSINF)) != "0")
			{
				$c = gmp_xor($c, "0xf33e5fb3c4");
			}
			if (gmp_strval(gmp_div_q(gmp_mod($c0, "16"), "8", GMP_ROUND_MINUSINF)) != "0")
			{
				$c = gmp_xor($c, "0xae2eabe2a8");
			}
			if (gmp_strval(gmp_div_q(gmp_mod($c0, "32"), "16", GMP_ROUND_MINUSINF)) != "0")
			{
				$c = gmp_xor($c, "0x1e4f43e470");
			}
		}

		return intval(gmp_strval(gmp_xor($c, "1")));
	}

	static private function rebuildAddress($bytes)
	{
		$ret = "";
		$i   = 0;

		while ($bytes[$i] != 0)
		{
			$ret .= chr(self::t + $bytes[$i]);
			$i++;
		}

		$ret .= ':';

		for ($i++; $i < sizeof($bytes); $i++)
		{
			$ret .= self::CHARSET[$bytes[$i]];
		}

		return $ret;
	}

	// http://www.sitepoint.com/forums/showthread.php?449434-Unsigned-right-bitwise-shift
	static private function uRShift($n, $s)
	{
		return ($n >= 0) ? ($n >> $s) : (($n & 0x7fffffff) >> $s) | (0x40000000 >> ($s - 1));
	}

	static public function old2new($oldAddress)
	{
		$bytes = [0];

		for ($x = 0; $x < strlen($oldAddress); $x++)
		{
			if (!array_key_exists(ord($oldAddress[$x]), self::ALPHABET_MAP))
			{
				throw new CashAddressException('Error');
			}

			$value = self::ALPHABET_MAP[ord($oldAddress[$x])];
			$carry = $value;

			for ($j = 0; $j < sizeof($bytes); $j++)
			{
				$carry     += $bytes[$j] * 58;
				$bytes[$j] = $carry & 0xff;
				$carry     = $carry >> 8;
			}

			while ($carry > 0)
			{
				$bytes = array_merge($bytes, [$carry & 0xff]);
				$carry = $carry >> 8;
			}
		}

		$numZeros = 0;

		for (; $numZeros < strlen($oldAddress); $numZeros++)
		{
			if ($oldAddress[$numZeros] != "1")
			{
				break;
			}
		}

		for ($i = 0; $i < $numZeros; $i++)
		{
			array_push($bytes, 0);
		}

		if (sizeof($bytes) < 5)
		{
			throw new CashAddressException('Error');
		}

		// reverse array
		$answer = [];

		for ($i = sizeof($bytes) - 1; $i >= 0; $i--)
		{
			array_push($answer, $bytes[$i]);
		}

		$version = $answer[0];
		$payload = array_slice($answer, 1, (sizeof($answer) - 5) - (1) + 1);

		// Assume the checksum of the old address is right
		// Here, the Cash Address conversion starts
		// DEBUG
		if ($version == 0x00)
		{
			// P2PKH
			$addressType = 0;
		}
		else if ($version == 0x05)
		{
			// P2SH
			$addressType = 1;
		}
		else
		{
			throw new CashAddressException('Error');
		}

		// packCashAddressData
		$encodedSize = (sizeof($payload) - 20) / 4;

		if ((sizeof($payload) - 20) % 4 != 0)
		{
			// Weird!
			throw new CashAddressException('Error');
		}

		$versionByte = ($addressType << 3) | $encodedSize;
		// Those addresses are not in use yet!
		$data = array_merge([$versionByte], $payload);
		// convertBits
		$payloadConverted = self::convertBits($data, 8, 5, true);
		// Encode
		$arr          = array_merge(self::EXPAND_PREFIX, $payloadConverted, [0, 0, 0, 0, 0, 0, 0, 0]);
		$mod          = self::polymod($arr);
		$checksum     = [0, 0, 0, 0, 0, 0, 0, 0];

		for ($i = 0; $i < 8; $i++)
		{
			// Convert the 5-bit groups in mod to checksum values.
			// $checksum[$i] = ($mod >> 5*(7-$i)) & 0x1f;
			$checksum[$i] = self::uRShift($mod, 5 * (7 - $i)) & 0x1f;
		}

		if (self::polyMod(array_merge(self::EXPAND_PREFIX, $payloadConverted, $checksum)) != 0)
		{
			throw new CashAddressException('Self-conflicting');
		}

		$combined = array_merge($payloadConverted, $checksum);
		$ret      = "bitcoincash:";

		for ($i = 0; $i < sizeof($combined); $i++)
		{
			$ret .= self::CHARSET[$combined[$i]];
		}

		return $ret;
	}

	static public function decodeNewAddr($inputNew, $shouldFixErrors) {
		$inputNew = strtolower($inputNew);
		if (strpos($inputNew, ":") === false) {
			$inputNew = "bitcoincash:" . $inputNew;
		}
		else if (substr($inputNew, 0, 12) !== "bitcoincash:")
		{
			throw new CashAddressException('Error');
		}

		$values        = [];

		for ($i = 12; $i < strlen($inputNew); $i++)
		{
			if (!array_key_exists(ord($inputNew[$i]), self::BECH_ALPHABET))
			{
				throw new CashAddressException('Error');
			}
			array_push($values, self::BECH_ALPHABET[ord($inputNew[$i])]);
		}

		$data     = array_merge(self::EXPAND_PREFIX, $values);
		$checksum = self::polyMod($data);

		if ($checksum != 0)
		{
			// Checksum is wrong!
			// Try to fix up to two errors
			if ($shouldFixErrors) {
				$syndromes = Array();

				for ($p = 0; $p < sizeof($data); $p++)
				{
					for ($e = 1; $e < 32; $e++)
					{
						$data[$p] ^= $e;
						$c        = self::polyMod($data);
						if ($c == 0)
						{
							return self::rebuildAddress($data);
						}
						$syndromes[$c ^ $checksum] = $p * 32 + $e;
						$data[$p]                  ^= $e;
					}
				}

				foreach ($syndromes as $s0 => $pe)
				{
					if (array_key_exists($s0 ^ $checksum, $syndromes))
					{
						$data[intdiv($pe, 32)]                         ^= $pe % 32;
						$data[intdiv($syndromes[$s0 ^ $checksum], 32)] ^= $syndromes[$s0 ^ $checksum] % 32;
						return self::rebuildAddress($data);
					}
				}
				// Can't correct errors!
				throw new CashAddressException('Error');
			}
		}
		return $values;
	}

	static public function fixCashAddrErrors($inputNew) {
		try {
			$corrected = self::decodeNewAddr($inputNew, true);
			if (gettype($corrected) === "array") {
				return $inputNew;
			} else {
				return $corrected;
			}
		}
		catch(Exception $e) {
			return "";
		}
	}

	// Code Part 2: New To Old Conversion

	static public function new2old($inputNew, $shouldFixErrors)
	{
		try {
			$corrected = self::decodeNewAddr($inputNew, $shouldFixErrors);
			if (gettype($corrected) === "array") {
				$values = $corrected;
			} else {
				$values = self::decodeNewAddr($corrected, false);
			}
		}
		catch(Exception $e) {
			throw new CashAddressException('Error');
		}

		$values      = self::convertBits(array_slice($values, 0, sizeof($values) - 8), 5, 8, false);
		$addressType = $values[0] >> 3;
		$addressHash = array_slice($values, 1, 21);

		// Encode Address
		$bytes = [$addressType ? 0x05 : 0x00];
		$bytes = array_merge($bytes, $addressHash);

		// Checksum (Double SHA256)
		$stringToBeHashed = "";

		for ($i = 0; $i < sizeof($bytes); $i++)
		{
			$stringToBeHashed .= chr($bytes[$i]);
		}

		$hash = hash("sha256", $stringToBeHashed);

		$hashArray = [];

		for ($i = 0; $i < 32; $i++)
		{
			array_push($hashArray, self::BASE16[$hash[2 * $i]] * 16 + self::BASE16[$hash[2 * $i + 1]]);
		}

		$stringToBeHashed = "";

		for ($i = 0; $i < sizeof($hashArray); $i++)
		{
			$stringToBeHashed .= chr($hashArray[$i]);
		}

		$hashArray = [];
		$hash      = hash("sha256", $stringToBeHashed);

		for ($i = 0; $i < 4; $i++)
		{
			array_push($hashArray, self::BASE16[$hash[2 * $i]] * 16 + self::BASE16[$hash[2 * $i + 1]]);
		}

		$merged = array_merge($bytes, $hashArray);
		// Base 58 encoding
		$digits = [0];

		for ($i = 0; $i < sizeof($merged); $i++)
		{
			$carry = $merged[$i];
			for ($j = 0; $j < sizeof($digits); $j++)
			{
				$carry      += $digits[$j] << 8;
				$digits[$j] = $carry % 58;
				$carry      = intdiv($carry, 58);
			}

			while ($carry > 0)
			{
				array_push($digits, $carry % 58);
				$carry = intdiv($carry, 58);
			}
		}

		// leading zero bytes
		for ($i = 0; $i < sizeof($merged); $i++)
		{
			if ($merged[$i] !== 0)
			{
				break;
			}
			array_push($digits, 0);
		}

		// reverse
		$converted = "";
		for ($i = sizeof($digits) - 1; $i >= 0; $i--)
		{
			//array_push($converted, ALPHABET[$digits[$i]]);
			if ($digits[$i] > strlen(self::ALPHABET))
			{
				throw new CashAddressException('Error');
			}
			$converted .= self::ALPHABET[$digits[$i]];
		}

		return $converted;
	}
}

?>
