<?php

class SocketConnection extends CComponent implements ConnectionInterface
{

	/**
	 * @var Queue
	 */
	private $_q;

	public function __construct(QueueInterface &$queue)
	{
		$this->_q = $queue;
		$this->_q->attachEventHandler('onBeforeProcess', [$this, 'beforeProcess']);
		$this->_q->attachEventHandler('onAfterProcess', [$this, 'afterProcess']);
	}

	public function beforeProcess()
	{
		$this->open();
	}

	public function afterProcess()
	{
		$this->close();
	}

	public function open()
	{
		echo 'open' . PHP_EOL;;
	}

	public function close()
	{
		echo 'close' . PHP_EOL;
	}
}