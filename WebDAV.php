<?php
/**
 * @link https://github.com/FinalLevel/php-utils/
 * @copyright Copyright (c) 2014 Vitalii Khranivskyi
 * @author Vitalii Khranivskyi <misaret@gmail.com>
 * @license LICENSE file
 */

namespace fl\utils;

/**
 * WebDAV Interface
 */
class WebDAV
{
	/**
	 * Host name: webdav.examples.com
	 *
	 * @var string
	 */
	public $host;
	/**
	 * Server IP-addres: 127.0.0.1
	 * By default $serverIp = $host
	 * If server has many virtual hosts, then specity custom $serverIp
	 *
	 * @var string
	 */
	public $serverIp;
	/**
	 * @var integer
	 */
	public $port;
	/**
	 * Network operations timeout
	 *
	 * @var integer
	 */
	public $timeout;

	protected $_lastResponseHeaders;

	/**
	 * If server has many virtual hosts, then specity custom $serverIp
	 *
	 * @param string $host
	 * @param integer $port
	 * @param integer $timeout
	 * @param string $serverIp
	 */
	public function __construct($host, $port, $timeout = 5, $serverIp = null)
	{
		$this->host = $host;
		$this->serverIp = ($serverIp ?: $host);
		$this->port = $port;
		$this->timeout = $timeout;
	}

	/**
	 * GET command
	 *
	 * @param string $path
	 * @return string
	 */
	public function get($path)
	{
		$headers = "GET $path HTTP/1.1\r\n";

		$timer = new Stopwatch();

		$body = $this->_execute($headers);
		if ($body === false) {
			$this->_log('GET', $path, $timer, E_USER_ERROR);
			return false;
		} else {
			$this->_log('GET', $path, $timer, E_USER_NOTICE, strlen($body));
		}

		return $body;
	}

	/**
	 * GET command. Content write to $outFile
	 *
	 * @param string $path
	 * @param string $outFile
	 * @return integer
	 */
	public function getFile($path, $outFile)
	{
		$headers = "GET $path HTTP/1.1\r\n";

		$timer = new Stopwatch();

		$size = $this->_execute($headers, array('outFile' => $outFile));
		if (!$size) {
			$this->_log('GET', $path, $timer, E_USER_ERROR);
			return false;
		} else {
			$this->_log('GET', $path, $timer, E_USER_NOTICE, $size);
		}

		return $size;
	}

	/**
	 * PUT command
	 *
	 * @param string $path
	 * @param string $content
	 * @param integer $modTime
	 * @return boolean
	 */
	public function put($path, $content, $modTime = null)
	{
		$headers = "PUT $path HTTP/1.1\r\n"
			. "Content-Length: " . strlen($content) . "\r\n";
		if ($modTime) {
			$headers .= "Date: " . gmdate('D, d M Y H:i:s T', $modTime) . "\r\n";
		}

		$timer = new Stopwatch();

		$body = $this->_execute($headers, array('content' => $content));
		if ($body === false) {
			$this->_log('PUT', $path, $timer, E_USER_ERROR, strlen($content));
			return false;
		} else {
			$this->_log('PUT', $path, $timer, E_USER_NOTICE, strlen($content));
		}

		return true;
	}

	/**
	 * PUT command. Content read from $inPath
	 *
	 * @param string $path
	 * @param string $inPath
	 * @param integer $modTime
	 * @param integer $fileSize
	 * @return boolean
	 */
	public function putFile($path, $inPath, $modTime = null, $fileSize = null)
	{
		if (!$fileSize) {
			clearstatcache();
			$fileSize = filesize($inPath);
		}
		if (!$fileSize) {
			trigger_error('Wrong file size ' . $fileSize, E_USER_WARNING);
			return false;
		}

		$headers = "PUT $path HTTP/1.1\r\n"
			. "Content-Length: $fileSize\r\n";
		if ($modTime) {
			$headers .= "Date: " . gmdate('D, d M Y H:i:s T', $modTime) . "\r\n";
		}

		$timer = new Stopwatch();

		$body = $this->_execute($headers, array('inFile' => $inPath));
		if ($body === false) {
			$this->_log('PUT', $path, $timer, E_USER_ERROR, $fileSize);
			return false;
		} else {
			$this->_log('PUT', $path, $timer, E_USER_NOTICE, $fileSize);
		}

		return true;
	}

	/**
	 * DELETE command
	 *
	 * @param string $path
	 * @return boolean
	 */
	public function delete($path)
	{
		$headers = "DELETE $path HTTP/1.1\r\n";

		$timer = new Stopwatch();

		$body = $this->_execute($headers);
		if ($body === false) {
			$this->_log('DELETE', $path, $timer, E_USER_ERROR);
			return false;
		} else {
			$this->_log('DELETE', $path, $timer, E_USER_NOTICE);
		}

		return true;
	}

	/**
	 * HEAD command
	 *
	 * @param string $path
	 * @return boolean
	 */
	public function head($path)
	{
		$headers = "HEAD $path HTTP/1.1\r\n";

		$timer = new Stopwatch();

		$body = $this->_execute($headers);
		if ($body === false) {
			$this->_log('HEAD', $path, $timer, E_USER_WARNING);
			return false;
		} else {
			$this->_log('HEAD', $path, $timer, E_USER_NOTICE);
		}

		return true;
	}

	public function move($path, $detination)
	{
		$headers = "MOVE $path HTTP/1.1\r\n"
			. "Destination: $detination\r\n";

		$timer = new Stopwatch();

		$body = $this->_execute($headers);
		if ($body === false) {
			$this->_log('MOVE', $path, $timer, E_USER_WARNING);
			return false;
		} else {
			$this->_log('MOVE', $path, $timer, E_USER_NOTICE);
		}

		return true;
	}

	/**
	 * Return value 'Content-Length' header from last command
	 *
	 * @return integer
	 */
	public function getLastSize()
	{
		$result = $this->_getHeaderValue('Content-Length');
		if ($result === false) {
			return false;
		}

		return intval($result);
	}

	/**
	 * Return timestamp 'Last-Modified' header from last command
	 *
	 * @return integer
	 */
	public function getLastModified()
	{
		$result = $this->_getHeaderValue('Last-Modified');
		if ($result === false) {
			return false;
		}

		return strtotime($result);
	}

	// ====================
	// === PRIVATE PART ===
	// ====================

	/**
	 * Show debug messages
	 *
	 * @param string $type
	 * @param string $path
	 * @param \fl\utils\Stopwatch $timer
	 * @param integer $errorType
	 * @param integer $fileSize
	 */
	protected function _log($type, $path, Stopwatch $timer, $errorType = E_USER_NOTICE, $fileSize = null)
	{
		trigger_error('[' . $this->host . ':' . $this->port . '] ' . $type . ' ' . $path 
			. ($fileSize ? '; ' . $fileSize . 'b' : '') . '; ' . $timer->getElapsed(3) . 'sec.', $errorType);
	}

	/**
	 * Real send request and read response
	 *
	 * @param string $headers
	 * @param array $params
	 * @return string
	 */
	protected function _execute($headers, array $params = array())
	{
		$this->_lastResponseHeaders = null;

		$socket = new Socket($this->serverIp, $this->port, $this->timeout);
		if (!$socket->open()) {
			return false;
		}

		$res = $socket->send(
			$headers
			. "Host: $this->host\r\n"
			. "Connection: close\r\n"
			. "\r\n"
		);
		if (!$res) {
			trigger_error('Error sending header', E_USER_WARNING);
			return false;
		}

		if (!empty($params['content'])) {
			$res = $socket->send($params['content']);
		} elseif (!empty($params['inFile'])) {
			$res = $socket->sendFile($params['inFile']);
		}
		if ($res === false) {
			trigger_error('Error sending body', E_USER_WARNING);
			return false;
		}

		$headers = null;
		if (!empty($params['outFile'])) {
			$res = $socket->readHttpBodyFile($params['outFile'], $headers);
		} else {
			$res = $socket->readHttpBody($headers);
		}
		if ($res === false) {
			trigger_error('Empty response', E_USER_WARNING);
			return false;
		}

		$endLinePos = strpos($headers, "\r\n");
		if (!$endLinePos) {
			trigger_error('Cannot found response code line' . $headers, E_USER_WARNING);
			return false;
		}

		$firstLine = substr($headers, 0, $endLinePos);
		$tmp = explode(' ', $firstLine, 3);
		if (empty($tmp[1]) || $tmp[1] < 200 || $tmp[1] >= 300) {
			trigger_error('Not OK response: ' . $firstLine, E_USER_WARNING);
			return false;
		}

		$this->_lastResponseHeaders = $headers;

		return $res;
	}

	/**
	 * Return value of header from last command
	 *
	 * @param string $name
	 * @return string
	 */
	protected function _getHeaderValue($name)
	{
		if (!$this->_lastResponseHeaders) {
			trigger_error('Last headers are empty', E_USER_WARNING);
			return false;
		}
		if (!is_array($this->_lastResponseHeaders)) {
			$this->_lastResponseHeaders = explode("\r\n", $this->_lastResponseHeaders);
		}

		$name .= ':';
		$len = strlen($name);
		foreach ($this->_lastResponseHeaders as $header) {
			if (strncasecmp($header, $name, $len) == 0) {
				return ltrim(substr($header, $len));
			}
		}

		trigger_error("Header '$name' not found", E_USER_WARNING);
		return false;
	}

}
