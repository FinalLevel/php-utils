<?php
/**
 * @link https://github.com/misaret/php-utils/
 * @copyright Copyright (c) 2014 Vitalii Khranivskyi
 * @author Vitalii Khranivskyi <misaret@gmail.com>
 * @license LICENSE file
 */

namespace misaret\utils;

/**
 * Simple template processor
 */
class Template
{
	/**
	 * ```
	 * echo Template::compile('<span>[text]</span>', ['text' => 'bla-bla-bla'], ['prefix' => '[', 'postfix' => ']']);
	 * ```
	 * @param string $template
	 * @param array $placeholders
	 * @param array $options
	 * @return string
	 */
	public static function compile($template, $placeholders, array $options = null)
	{
		if (!$template || !$placeholders) {
			return $template;
		}

		$prefix = '';
		$postfix = '';
		if (!empty($options['prefix'])) {
			$prefix = $options['prefix'];
		}
		if (!empty($options['postfix'])) {
			$postfix = $options['postfix'];
		}

		$search = array_keys($placeholders);
		if ($postfix || $prefix) {
			$search = array_map(function ($val) use ($prefix, $postfix) {
				return $prefix . $val . $postfix;
			}, $search);
		}
		$replace = array_values($placeholders);

		return str_replace($search, $replace, $template);
	}
}
