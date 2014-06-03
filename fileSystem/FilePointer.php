<?php
/**
 * @link https://github.com/FinalLevel/php-utils/
 * @copyright Copyright (c) 2014 Vitalii Khranivskyi
 * @author Vitalii Khranivskyi <misaret@gmail.com>
 * @license LICENSE file
 */

namespace fl\utils\fileSystem;

/**
 * Manage file pointer. Close pointer when destroy
 */
class FilePointer
{
	/**
	 * File pointer
	 *
	 * @var resource
	 */
	public $fp;

	/**
	 * @param string $path
	 * @param string $mode
	 */
	function __construct($path, $mode)
	{
		$this->open($path, $mode);
	}

	function __destruct()
	{
		$this->close();
	}

	/**
	 * @param string $path
	 * @param string $mode
	 */
	function open($path, $mode)
	{
		$this->close();

		$this->fp = fopen($path, $mode);

		return $this->fp;
	}

	function close()
	{
		if ($this->fp) {
			fclose($this->fp);
			$this->fp = null;
		}
	}
}
