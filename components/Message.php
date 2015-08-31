<?php

/**
 * Class Message implements an abstract push message.

 * @property bool $isSending true if message is sending at the moment and false otherwise
 *
 * @author chervand <chervand@gmail.com>
 */
abstract class Message extends CComponent implements MessageInterface
{
	/**
	 * @var Connection assigned connection reference
	 */
	private $_c;

	/**
	 * @var bool internal sending status
	 */
	private $_s = false;

	/**
	 * Constructor.
	 * Assigns connection reference to the message object.
	 * @param ConnectionInterface $connection connection reference
	 */
	public function __construct(ConnectionInterface &$connection)
	{
		$this->_c = &$connection;
	}

	/**
	 * Returns a message string.
	 * @return string message string
	 */
	abstract public function __toString();

	/**
	 * Sends the message using its connection.
	 * @return bool true if message was sent successfully and false otherwise
	 * @see _c
	 */
	public function send()
	{
		$this->_s = true;
		$sent = $this->_c->send($this);
		$this->_s = false;
		return $sent;
	}

	/**
	 * Returns message sending status.
	 * @return bool true if message is sending at the moment and false otherwise
	 */
	public function getIsSending()
	{
		return $this->_s;
	}
}