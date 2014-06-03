<?php
/**
 * @link https://github.com/FinalLevel/php-utils/
 * @copyright Copyright (c) 2014 Vitalii Khranivskyi
 * @author Vitalii Khranivskyi <misaret@gmail.com>
 * @license LICENSE file
 */

namespace fl\utils\storage;

/**
 * Common interface for storage engines
 */
interface StorageInterface
{
	/**
	 * Return file content
	 *
	 * @param string $name
	 * @return string
	 */
	public function get($name);

	/**
	 * Put file content into $outFile
	 *
	 * @param string $name
	 * @param string $outFile
	 * @return boolean
	 */
	public function getFile($name, $outFile);

	/**
	 * Put content from $content into storage and set modtime
	 *
	 * @param string $name
	 * @param string $content
	 * @param integer $modTime
	 * @return boolean
	 */
	public function put($name, $content, $modTime = null);

	/**
	 * Put content from $inFile into storage and set modtime
	 * If $fileSize not present then $fileSize = file_size($inFile)
	 *
	 * @param string $name
	 * @param string $inFile
	 * @param integer $modTime
	 * @param integer $fileSize
	 * @return boolean
	 */
	public function putFile($name, $inFile, $modTime = null, $fileSize = null);

	/**
	 * Check file exists
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function exists($name);

	/**
	 * Return file size
	 *
	 * @param string $name
	 * @return integer
	 */
	public function getSize($name);

	/**
	 * Return file modtime
	 *
	 * @param string $name
	 * @return integer
	 */
	public function getModTime($name);

	/**
	 * Delete file from storage
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function delete($name);

	/**
	 * Touch file and set modtime
	 *
	 * @param string $name
	 * @param integer $modTime
	 * @return boolean
	 */
	public function touch($name, $modTime = null);
	
	/**
	 * Touch file and set modtime
	 *
	 * @param string $name
	 * @param integer $modTime
	 * @return boolean
	 */
	public function move($name, $newName);

	/**
	 * Get URI for file
	 *
	 * @param string $name
	 * @return string
	 */
	public function getExternalUri($name);
}
