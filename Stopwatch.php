<?php
/**
 * @link https://github.com/FinalLevel/php-utils/
 * @copyright Copyright (c) 2014 Vitalii Khranivskyi
 * @author Vitalii Khranivskyi <misaret@gmail.com>
 * @license LICENSE file
 */

namespace fl\utils;

/**
 * Stopwatch
 */
class Stopwatch {
	private $_startTime = 0;
	private $_elapsedTime = 0;

	/**
	 * @param boolean $startNow
	 */
	public function __construct($startNow = true)
	{
		if ($startNow)
			$this->start();
	}

	public function start()
	{
		$this->_startTime = microtime(true);
	}

	public function reset()
	{
		$this->_startTime = 0;
		$this->_elapsedTime = 0;
	}

	public function stop()
	{
		if ($this->_startTime) {
			$this->_elapsedTime = microtime(true) - $this->_startTime;
			$this->_startTime = 0;
		}
	}

	/**
	 * @param int $decimals number of decimal points
	 * @return float|string
	 */
	public function getElapsed($decimals = null)
	{
		$this->stop();

		return ($decimals ? number_format($this->_elapsedTime, $decimals) : $this->_elapsedTime);
	}
}
