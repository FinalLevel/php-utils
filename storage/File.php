<?php
/**
 * @link https://github.com/FinalLevel/php-utils/
 * @copyright Copyright (c) 2014 Vitalii Khranivskyi
 * @author Vitalii Khranivskyi <misaret@gmail.com>
 * @license LICENSE file
 */

namespace fl\utils\storage;

/**
 * Storage engine: file
 */
class File implements StorageInterface
{
	public $localBasePath;
	public $externalBaseUri;
	/**
	 * How many sublevels.
	 * 12345.jpg -> 5/45/12345.jpg
	 *
	 * @var integer
	 */
	public $levelsCount;

	public function __construct($localBasePath, $externalBaseUri = null, $levelsCount = null)
	{
		$this->localBasePath = $localBasePath;
		$this->externalBaseUri = $externalBaseUri;
		$this->levelsCount = $levelsCount;
	}

	protected function _getLocalPart($name)
	{
		if (!$this->levelsCount) {
			return $name;
		}

		$result = [];
		$id = intval($name);
		for ($i = 0; $i < $this->levelsCount; $i++) {
			$result[] = ($id % pow(10, $i + 1));
		}
		$result[] = $name;

		return implode('/', $result);
	}

	protected function _getLocalPath($name)
	{
		return $this->localBasePath . $this->_getLocalPart($name);
	}

	public function get($name)
	{
		return file_get_contents($this->_getLocalPath($name));
	}

	public function getFile($name, $outFile)
	{
		return copy($this->_getLocalPath($name), $outFile);
	}

	public function put($name, $content, $modTime = null)
	{
		$res = file_put_contents($this->_getLocalPath($name), $content);
		if ($res && $modTime) {
			touch($this->_getLocalPath($name), $modTime);
		}

		return $res;
	}

	public function putFile($name, $inFile, $modTime = null, $fileSize = null)
	{
		$res = copy($inFile, $this->_getLocalPath($name));
		if ($res && $modTime) {
			touch($this->_getLocalPath($name), $modTime);
		}

		return $res;
	}

	public function exists($name)
	{
		return file_exists($this->_getLocalPath($name));
	}

	public function getSize($name)
	{
		return @filesize($this->_getLocalPath($name));
	}

	public function getModTime($name)
	{
		return @filemtime($this->_getLocalPath($name));
	}

	public function delete($name)
	{
		return unlink($this->_getLocalPath($name));
	}

	public function touch($name, $modTime = null)
	{
		return touch($this->_getLocalPath($name), $modTime);
	}

	public function move($name, $newName)
	{
		return rename($this->_getLocalPath($name), $this->_getLocalPath($newName));
	}

	public function getExternalUri($name)
	{
		return $this->externalBaseUri . $this->_getLocalPart($name);
	}
}
