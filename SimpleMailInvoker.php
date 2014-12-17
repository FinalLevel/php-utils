<?php

namespace fl\utils;

class SimpleMailInvoker extends \Swift_Transport_SimpleMailInvoker
{
	 public function mail($to, $subject, $body, $headers = null, $extraParams = null)
	 {
		 $to = str_replace(["\r", "\n"], ['', ''], $to);
		 return parent::mail($to, $subject, $body, $headers, $extraParams);
	 }
}
