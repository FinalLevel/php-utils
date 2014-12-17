<?php

namespace fl\utils;

class Utils
{
	/**
	 * Save $content to file
	 *
	 * @param string $file
	 * @param string $content
	 * @return boolean
	 */
	public static function saveFile($file, $content)
	{
		$res = file_put_contents($file, $content);

		if (!$res) {
			trigger_error('Cannot save: ' . $file . (file_exists($file) ? (is_writable($file) ? '': ' not writeable') : ' not exist'), E_USER_ERROR);
			return false;
		}

		trigger_error($file . ' saved; ' . strlen($content) . 'b;');
		return true;
	}

	/**
	 * Save $data file as php.
	 * Then use $data = include($file)
	 *
	 * @param string $file
	 * @param mixed $data
	 * @return boolean
	 */
	public static function saveStatic($file, $data)
	{
		if (is_object($data)) {
			$str = var_export(serialize($data), true);
			$content = "<?php return unserialize($str);";
		} else {
			$str = var_export($data, true);
			$content = "<?php return $str;";
		}

		return static::saveFile($file, $content);
	}

	/**
	 * Save $data file as JSON.
	 *
	 * @param string $file
	 * @param mixed $data
	 * @return boolean
	 */
	public static function saveJson($file, $data)
	{
		$content = json_encode($data, JSON_UNESCAPED_UNICODE);

		return static::saveFile($file, $content);
	}

	/**
	 * Send simple mail with right headers formating
	 *
	 * @param array $from
	 * @param array $to
	 * @param string $subject
	 * @param string $body
	 */
	static function mail($from, $to, $subject, $body, $returnPath = null)
	{
		\Swift_DependencyContainer::getInstance()
			->register('transport.mailinvoker')
			->asSharedInstanceOf('fl\utils\SimpleMailInvoker');

		// Create the Transport
		$transport = \Swift_MailTransport::newInstance();

		// Create the Mailer using your created Transport
		$mailer = \Swift_Mailer::newInstance($transport);

		// Create a message
		$message = \Swift_Message::newInstance()
			->setFrom($from)
			->setTo($to)
			->setSubject($subject)
			->setBody($body);
		if ($returnPath) {
			if (is_array($returnPath) || is_object($returnPath)) {
				$message->setReturnPath(key($returnPath));
			} else {
				$message->setReturnPath($returnPath);
			}
		}

		// Send the message
		$result = $mailer->send($message);

		$toStr = [];
		foreach ((array) $to as $key => $val) {
			if (is_int($key)) {
				$toStr[] = $val;
			} else {
				$toStr[] = "$val <$key>";
			}
		}
		trigger_error('Mail sent to ' . implode(', ', $toStr), ($result ? E_USER_NOTICE : E_USER_WARNING));

		return $result;
	}
}
