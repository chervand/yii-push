<?php

/**
 * Class Queue implements a message queue.
 *
 * @property Message $current
 * @property bool isProcessing
 * @property bool isSending
 *
 * @author chervand <chervand@gmail.com>
 */
class Queue extends CQueue implements QueueInterface
{
	/**
	 * processing status
	 * @var bool
	 */
	private $_p = false;

	/**
	 * current item
	 * @var
	 */
	private $_i;

	/**
	 * Removes every message from the queue and send it.
	 * @return bool
	 * @throws CException
	 */
	public function process()
	{
		$event = new CEvent($this);
		$this->onBeforeProcess($event);
		while ($this->count > 0) {
			$this->_i = $this->dequeue();
			if ($this->_i instanceof Message) {
				$this->onBeforeSend($event);
				$this->_i->send();
				$this->onAfterSend($event);
			}
		}
		$this->onAfterProcess($event);
		return true;
	}

	/**
	 * Raises 'onBeforeProcess' event.
	 * @param $event
	 * @throws CException
	 */
	public function onBeforeProcess($event)
	{
		$this->_p = true;
		$this->raiseEvent('onBeforeProcess', $event);
	}

	/**
	 * Raises 'onBeforeSend' event.
	 * @param $event
	 * @throws CException
	 */
	public function onBeforeSend($event)
	{
		$this->raiseEvent('onBeforeSend', $event);
	}

	/**
	 * Raises 'onAfterSend' event.
	 * @param $event
	 * @throws CException
	 */
	public function onAfterSend($event)
	{
		$this->raiseEvent('onAfterSend', $event);
		$this->_i = null;
	}

	/**
	 * Raises 'onAfterProcess' event.
	 * @param $event
	 * @throws CException
	 */
	public function onAfterProcess($event)
	{
		$this->raiseEvent('onAfterProcess', $event);
		$this->_p = false;
	}

	/**
	 * Return true if the queue is processing messages at the moment and false otherwise.
	 * @return bool
	 */
	public function getIsProcessing()
	{
		return $this->_p;
	}

	/**
	 * Returns true if any message is sending at the moment and false otherwise.
	 * @return bool
	 */
	public function getIsSending()
	{
		return $this->_i instanceof Message && $this->_i->isSending;
	}

	/**
	 * Returns currently processing message.
	 * @return Message|null
	 */
	public function getCurrent()
	{
		if ($this->_i instanceof Message) {
			return $this->_i;
		}
		return null;
	}
}