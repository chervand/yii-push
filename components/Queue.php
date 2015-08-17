<?php

class Queue extends CQueue implements QueueInterface
{
	public function process()
	{
		$event = new CEvent($this);
		$this->onBeforeProcess($event);
		foreach ($this as $item) {
			if ($item instanceof MessageInterface) {
				$item->send();
			}
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