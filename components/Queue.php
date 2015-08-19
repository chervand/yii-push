<?php

class Queue extends CQueue implements QueueInterface
{
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
		$this->raiseEvent('onBeforeProcess', $event);
	}

	public function onAfterProcess($event)
	{
		$this->raiseEvent('onAfterProcess', $event);
	}
}