<?php

/**
 * Class Message
 *
 * @property bool $isSending
 */
class Message extends CComponent implements MessageInterface
{
	/**
	 * @var ConnectionInterface
	 */
	private $_c;
	private $_s = false;

	/**
	 * @param ConnectionInterface $connection
	 */
	public function __construct(ConnectionInterface &$connection)
	{
		$this->_c = &$connection;
	}

	/**
	 * @return bool
	 */
	public function send()
	{
		$this->_s = true;
		$sent =  $this->_c->send($this);
		$this->_s = false;
		return $sent;
	}

	public function getIsSending()
	{
		return $this->_s;
	}
}