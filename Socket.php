<?php
/**
 * @link https://github.com/misaret/php-utils/
 * @copyright Copyright (c) 2014 Vitalii Khranivskyi
 * @author Vitalii Khranivskyi <misaret@gmail.com>
 * @license LICENSE file
 */

namespace misaret\utils;

/**
 * Socket
 */
class Socket
{
	const CHUNK_SIZE = 65536; // 2^16 = 64kb

	/**
	 * @var string
	 */
	public $server;
	/**
	 * @var integer
	 */
	public $port;
	/**
	 * @var integer
	 */
	public $timeout;

	protected $_socket;

	/**
	 * @param string $server
	 * @param integer $port
	 * @param integer $timeout
	 */
	public function __construct($server, $port, $timeout = 5)
	{
		$this->server = $server;
		$this->port = $port;
		$this->timeout = $timeout;
	}

	public function __destruct()
	{
		$this->close();
	}

	/**
	 * Open connection
	 *
	 * @param boolean $reopen force reopen socket
	 * @return boolean
	 */
	public function open($reopen = false)
	{
		if ($this->_socket) {
			if ($reopen) {
				fclose($this->_socket);
			} else {
				return $this->_socket;
			}
		}

		$errno = $errstr = false;
		$this->_socket = @fsockopen($this->server, $this->port, $errno, $errstr, $this->timeout);
		if ($this->_socket) {
			stream_set_timeout($this->_socket, $this->timeout);
		}

		return $this->_socket;
	}

	/**
	 * Close connection
	 */
	public function close()
	{
		if ($this->_socket) {
			fclose($this->_socket);
			$this->_socket = null;
		}
	}

	/**
	 * Send $content to socket
	 *
	 * @param string $content
	 * @return boolean
	 */
	public function send($content)
	{
		if (!$this->open()) {
			return false;
		}

		$size = strlen($content);
		$sent = fwrite($this->_socket, $content, $size);
		if (($size != $sent) && $this->open(true)) {
			trigger_error('Retry. Error write to socket: sent ' . $sent . ' from ' . $size . ' bytes', E_USER_WARNING);
			$sent = fwrite($this->_socket, $content, $size);
			if ($size != $sent) {
				trigger_error('Retry failed', E_USER_ERROR);
			}
		}

		return ($size == $sent);
	}

	/**
	 * Send content from $inFile to socket
	 *
	 * @param string $inFile
	 * @return integer
	 */
	public function sendFile($inFile)
	{
		$timer = new Stopwatch();

		$file = new fileSystem\FilePointer($inFile, 'rb');
		if (!$file->fp) {
			trigger_error('Error open ' . $inFile, E_USER_ERROR);
			return false;
		}

		if (!$this->open()) {
			return false;
		}

		$size = 0;
		while (!feof($file->fp)) {
			$buf = fread($file->fp, static::CHUNK_SIZE);
			if ($buf === false) {
				trigger_error('Error read ' . $inFile . '; ' . $timer->getElapsed(3) . 'sec.', E_USER_ERROR);
				return false;
			}

			$len = strlen($buf);
			$sent = fwrite($this->_socket, $buf, $len);
			if ($len != $sent) {
				trigger_error('Error write to socket: sent ' . $sent . 'b from ' . $inFile . '; '
					. $timer->getElapsed(3) . 'sec.', E_USER_ERROR);
				return false;
			}

			$size += $sent;
		}

		trigger_error('Sent ' . $inFile . '; ' . $size . 'b; ' . $timer->getElapsed(3) . 'sec.');

		return $size;
	}

	/**
	 * Read all data from socket
	 *
	 * @return string
	 */
	public function readAll()
	{
		$timer = new Stopwatch();

		if (!$this->open()) {
			return false;
		}

		$result = '';
		$retryLeft = 1;
		while (!feof($this->_socket)) {
			$res = fread($this->_socket, static::CHUNK_SIZE);
			if ($res === false || strlen($res) < 1 && $retryLeft-- <= 0) {
				trigger_error('Error read from socket; res = ' . ($res === false ? 'false' : "''") . '; '
					. $timer->getElapsed(3) . 'sec.', E_USER_WARNING);
				break;
			}
			$result .= $res;
		}

		return $result;
	}

	/**
	 * Read first $len byte
	 *
	 * @param int $size
	 * @return string
	 */
	public function readSize($size)
	{
		$timer = new Stopwatch();

		if (!$this->open()) {
			return false;
		}

		$result = '';
		$retryLeft = 1;
		$sizeLeft = $size;
		while ($sizeLeft > 0 && !feof($this->_socket)) {
			$res = fread($this->_socket, $sizeLeft);
			if ($res === false || strlen($res) < 1 && $retryLeft-- <= 0) {
				trigger_error('Error read from socket; res = ' . ($res === false ? 'false' : "''") . '; '
					. $timer->getElapsed(3) . 'sec.', E_USER_WARNING);
				break;
			}
			$sizeLeft -= strlen($res);
			$result .= $res;
		}

		return ($sizeLeft > 0 ? false : $result);
	}

	/**
	 * Read HTTP response body.
	 * Headers return to $headers.
	 *
	 * @param string $headers
	 * @return string
	 */
	public function readHttpBody(&$headers = null)
	{
		$timer = new Stopwatch();

		if (!$this->open()) {
			return false;
		}

		$headers = null;
		$result = '';
		$retryLeft = 1;
		while (!feof($this->_socket)) {
			$res = fread($this->_socket, static::CHUNK_SIZE);
			if ($res === false || strlen($res) < 1 && $retryLeft-- <= 0) {
				trigger_error('Error read from socket; res = ' . ($res === false ? 'false' : "''") . '; '
					. $timer->getElapsed(3) . 'sec.', E_USER_WARNING);
				break;
			}
			$result .= $res;

			if (!isset($headers) && ($pos = strpos($result, "\r\n\r\n")) !== false) {
				$headers = substr($result, 0, $pos);
				$result = (string) substr($result, $pos + 4);
			}
		}

		return (isset($headers) ? $result : false);
	}

	/**
	 * Read HTTP response body to file.
	 * Headers return to $headers.
	 *
	 * @param string $outFile
	 * @param string $headers
	 * @return integer
	 */
	public function readHttpBodyFile($outFile, &$header = null)
	{
		$timer = new Stopwatch();

		$file = new fileSystem\TemporaryFile($outFile, 'ab');
		if (!$file->fp) {
			trigger_error('Cannot open file for append ' . $outFile, E_USER_ERROR);
			return false;
		}

		if (!$this->open()) {
			return false;
		}

		$header = null;
		$result = '';
		$retryLeft = 1;
		$size = false;
		while (!feof($this->_socket)) {
			$res = fread($this->_socket, static::CHUNK_SIZE);
			if ($res === false || strlen($res) < 1 && $retryLeft-- <= 0) {
				trigger_error('Error read from socket; res = ' . ($res === false ? 'false' : "''") . '; '
					. $timer->getElapsed(3) . 'sec.', E_USER_WARNING);
				break;
			}

			if (!isset($header)) {
				$result .= $res;
				if (($pos = strpos($result, "\r\n\r\n")) !== false) {
					$header = substr($result, 0, $pos);
					$res = substr($result, $pos + 4);
				}
			}

			if (isset($header)) {
				$tmpSize = strlen($res);
				$size += $tmpSize;
				$sent = fwrite($file->fp, $res, $tmpSize);
				if ($sent != $tmpSize) {
					trigger_error('Error write to ' . $outFile . ': sent ' . $sent . 'b from ' . $tmpSize . '; '
						. $timer->getElapsed(3) . 'sec.', E_USER_ERROR);
					return false;
				}
			}
		}

		if ($size > 0) {
			$file->release();
			trigger_error('Write to ' . $outFile . '; ' . $size . 'b; ' . $timer->getElapsed(3) . 'sec.');
		}

		return $size;
	}

	public function flush()
	{
		return fflush($this->_socket);
	}
}
