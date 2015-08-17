<?php

interface QueueInterface
{
	public function process();
	public function onBeforeProcess($event);
	public function onAfterProcess($event);
}