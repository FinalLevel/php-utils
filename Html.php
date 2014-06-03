<?php

namespace fl\utils;

class Html
{
	static public function linkVars($skeep = null, $input = null)
	{
		if ($input === null) {
			$input = $_GET;
		}
		if ($skeep) {
			if (is_array($skeep)) {
				foreach ($skeep as $val) {
					unset($input[$val]);
				}
			} else {
				unset($input[$skeep]);
			}
		}
		if (!$input) {
			return '';
		}

		return implode('&', array_map(function ($key, $val) {
			return urlencode($key) . '=' . urlencode($val);
		}, array_keys($input), array_values($input)));
	}
}
