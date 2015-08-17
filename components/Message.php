<?php

/**
 * Class Message
 */
class Message extends CComponent implements MessageInterface
{
	/**
	 * @var ConnectionInterface
	 */
	private $_c;

	/**
	 * @param ConnectionInterface $connection
	 */
	public function __construct(ConnectionInterface &$connection)
	{
		$this->_c = &$connection;
	}

	/**
	 *
	 */
	public function send()
	{
		return $this->_c->send($this);
	}
}