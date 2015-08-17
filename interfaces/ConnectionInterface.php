<?php

interface ConnectionInterface
{
	public function __construct(QueueInterface &$queue);
	public function open();
	public function close();
}