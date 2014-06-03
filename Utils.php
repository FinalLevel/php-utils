<?php

namespace fl\utils;

class Utils
{
	static public function saveFile($file, $content)
	{
		$res = file_put_contents($file, $content);

		if (!$res) {
			trigger_error('Cannot save: ' . $file . (file_exists($file) ? (is_writable($file) ? '': ' not writeable') : ' not exist'), E_USER_ERROR);
			return false;
		}

		trigger_error($file . ' saved; ' . strlen($content) . 'b;');
		return $res;
	}

	static public function saveStatic($file, $data)
	{
		$str = var_export($data, true);
		$content = "<?php return $str;";

		return static::saveFile($file, $content);
	}
}
