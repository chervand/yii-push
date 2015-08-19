<?php

class Queue extends CQueue implements QueueInterface
{
	private $_p = false;

	public function process()
	{
		$event = new CEvent($this);
		$this->onBeforeProcess($event);
		while ($this->count > 0) {
			$item = $this->dequeue();
			$item->send();
		}
		$this->onAfterProcess($event);
		return true;
	}

	public function onBeforeProcess($event)
	{
		$this->_p = true;
		$this->raiseEvent('onBeforeProcess', $event);
	}

	public function onAfterProcess($event)
	{
		$this->raiseEvent('onAfterProcess', $event);
		$this->_p = false;
	}

	public function isProcessing()
	{
		return $this->_p;
	}
}