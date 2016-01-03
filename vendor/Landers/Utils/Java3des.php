<?php
namespace Landers\Utils;
class java3des {
	private static $type = MCRYPT_3DES; //MCRYPT_3DES 或 MCRYPT_TRIPLEDES， 结果一样
	private static $mode = MCRYPT_MODE_ECB;
	private static $rand = MCRYPT_RAND;

	public static function encrypt($str, $key){
		$size = mcrypt_get_block_size(self::$type, self::$mode);
		$str = self::pkcs5_pad($str, $size);
		$key = str_pad($key, 24,'0');
		$td = mcrypt_module_open(self::$type, '', self::$mode, '');
		$iv = @mcrypt_create_iv (mcrypt_enc_get_iv_size($td), self::$rand);
		@mcrypt_generic_init($td, $key, $iv);
		$data = mcrypt_generic($td, $str);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		$data = base64_encode($data);
		return $data;
	}

	public static function decrypt($str, $key){
		$str = base64_decode($str);
		$key = str_pad($key,24,'0');
		$td = mcrypt_module_open(self::$type,'','ecb','');
		$iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), self::$rand);
		$ks = mcrypt_enc_get_key_size($td);
		@mcrypt_generic_init($td, $key, $iv);
		$str = mdecrypt_generic($td, $str);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		$y=self::pkcs5_unpad($str);
		return $y;
	}

	private static function pkcs5_pad($str, $blocksize){
		$pad = $blocksize - (strlen($str) % $blocksize);
		return $str.str_repeat(chr($pad), $pad);
	}

	private static function pkcs5_unpad($str){
		$pad = ord($str{strlen($str)-1});
		if ($pad > strlen($str)) return false;
		if (strspn($str, chr($pad), strlen($str) - $pad) != $pad) return false;
		return substr($str, 0, -1 * $pad);
	}
}
?>