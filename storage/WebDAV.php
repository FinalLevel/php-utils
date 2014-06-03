<?php
/**
 * @link https://github.com/FinalLevel/php-utils/
 * @copyright Copyright (c) 2014 Vitalii Khranivskyi
 * @author Vitalii Khranivskyi <misaret@gmail.com>
 * @license LICENSE file
 */

namespace fl\utils\storage;

/**
 * Storage engine: WebDAV
 */
class WebDAV implements \fl\utils\StorageInterface
{
	/**
	 * WebDAV instance
	 * 
	 * @var \fl\utils\WebDAV
	 */
	public $webDAV;
	public $localBasePath;
	public $externalBaseUri;
	/**
	 * How many sublevels.
	 * 12345.jpg -> 5/45/12345.jpg
	 *
	 * @var integer
	 */
	public $levelsCount;

	function __construct($server, $externalBaseUri = null, $timeout = 5, $levelsCount = null)
	{
		$info = parse_url($server);
		$this->webDAV = new \fl\utils\WebDAV($info['host'], (empty($info['port']) ? 80 : $info['port']), $timeout);
		$this->localBasePath = (empty($info['path']) ? '/' : $info['path']);
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
		return $this->webDAV->get($this->_getLocalPath($name));
	}

	public function getFile($path, $outFile)
	{
		return $this->webDAV->getFile($this->_getLocalPath($path), $outFile);
	}

	public function put($name, $content, $modTime = null)
	{
		return $this->webDAV->put($this->_getLocalPath($name), $content, $modTime);
	}

	public function putFile($name, $inFile, $modTime = null, $fileSize = null)
	{
		return $this->webDAV->putFile($this->_getLocalPath($name), $inFile, $modTime, $fileSize);
	}

	public function exists($name)
	{
		return $this->webDAV->head($this->_getLocalPath($name));
	}

	public function getSize($name)
	{
		$res = $this->webDAV->head($this->_getLocalPath($name));
		if ($res) {
			return $this->webDAV->getLastSize();
		}

		return false;
	}

	public function getModTime($name)
	{
		$res = $this->webDAV->head($this->_getLocalPath($name));
		if ($res) {
			return $this->webDAV->getLastModified();
		}

		return false;
	}

	public function delete($name)
	{
		return $this->webDAV->delete($this->_getLocalPath($name));
	}

	public function touch($name, $modTime = null)
	{
		trigger_error('WARNING! touch() = get() && put()', E_USER_WARNING);
		$content = $this->get($name);
		return $this->put($name, $content, $modTime);
	}

	public function move($name, $newName)
	{
		return $this->webDAV->move($this->_getLocalPath($name), $this->_getLocalPath($newName));
	}

	public function getExternalUri($name)
	{
		if ($this->externalBaseUri) {
			return $this->externalBaseUri . $this->_getLocalPart($name);
		} else {
			return 'http://' . $this->webDAV->server . ($this->webDAV->port != 80 ? ':' . $this->webDAV->port : '')
				. $this->_getLocalPath($name);
		}
	}

}
