<?php

namespace MediaWiki\Extension\PDFCreator\Utility;

use MediaWiki\Extension\PDFCreator\IParamDesc;
use MediaWiki\Message\Message;

class ParamDesc implements IParamDesc {

	/** @var string */
	private $key;

	/** @var Message */
	private $message;

	/**
	 * @param string $key
	 * @param Message $message
	 */
	public function __construct( string $key, Message $message ) {
		$this->key = $key;
		$this->message = $message;
	}

	/**
	 * @return string
	 */
	public function getKey(): string {
		return $this->key;
	}

	/**
	 * @return Message
	 */
	public function getMessage(): Message {
		return $this->message;
	}
}
