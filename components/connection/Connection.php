<?php

/**
 * Class Connection
 * @author chervand <chervand@gmail.com>
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
		$this->_q = $queue;
		$this->_q->attachEventHandler('onBeforeProcess', [$this, 'beforeQueueProcess']);
		$this->_q->attachEventHandler('onAfterProcess', [$this, 'afterQueueProcess']);
	}

	/**
	 * @return Queue|QueueInterface
	 */
	public function getQueue()
	{
		return $this->_q;
	}

	/**
	 * @return mixed
	 */
	protected function beforeQueueProcess()
	{

	}

	/**
	 * @return mixed
	 */
	protected function afterQueueProcess()
	{

	}
}