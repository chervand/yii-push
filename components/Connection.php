<?php

/**
 * Class Connection
 * @author chervand <chervand@gmail.com>
 *
 * @property Queue $queue
 */
abstract class Connection extends CComponent implements ConnectionInterface
{
	/**
	 * @var Queue
	 */
	private $_q;

	/**
	 * @param QueueInterface $queue
	 */
	public function __construct(QueueInterface &$queue)
	{
		$this->_q = &$queue;
	}

	abstract public function send(MessageInterface &$message);

	/**
	 * @return Queue|QueueInterface
	 */
	public function getQueue()
	{
		return $this->_q;
	}
}