<?php
/**
 * @link https://github.com/FinalLevel/php-utils/
 * @copyright Copyright (c) 2014 Vitalii Khranivskyi
 * @author Vitalii Khranivskyi <misaret@gmail.com>
 * @license LICENSE file
 */

namespace fl\utils\fileSystem;

/**
 * Manage temporary file. Close pointer and unlink file when object destroy
 */
class TemporaryFile
{
	/**
	 * File pointer
	 *
	 * @var resource
	 */
	public $fp;
	/**
	 * @var string
	 */
	public $path;

	/**
	 * @param string $path
	 * @param string $mode
	 */
	public function __construct($path, $mode)
	{
		$this->reset($path, $mode);
	}

	public function __destruct()
	{
		$this->reset();
	}

	public function __toString()
	{
		return (string) $this->path;
	}

	/**
	 * @param string $path
	 * @param string $mode
	 * @return resource
	 */
	public function open($path, $mode)
	{
		return $this->reset($path, $mode);
	}

	public function close()
	{
		if ($this->fp) {
			fclose($this->fp);
			$this->fp = null;
		}
	}

	/**
	 * Close pointer and release file. File will not unlink when object destroy
	 */
	public function release()
	{
		$this->close();

		$this->path = null;
	}

	/**
	 * Link another file. Previous file will unlink
	 *
	 * @param string $path
	 * @param string $mode
	 * @return resourse
	 */
	public function reset($path = null, $mode = null)
	{
		$this->close();

		if ($this->path) {
			if (@unlink($this->path)) {
				trigger_error('unlink(' . $this->path . ');');
			} elseif (file_exists($this->path)) {
				trigger_error('unlink(' . $this->path . '); error', E_USER_WARNING);
			}
		}

		$this->path = $path;
		if ($path && $mode) {
			$this->fp = fopen($path, $mode);
			return $this->fp;
		}

		return true;
	}
}
