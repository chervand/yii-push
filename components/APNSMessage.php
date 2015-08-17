<?php

class APNSMessage extends CComponent implements MessageInterface
{
	public function __construct(ConnectionInterface &$connection)
	{
		// TODO: Implement __construct() method.
	}

	public function send()
	{
		echo get_class($this) . ' message sent' . PHP_EOL;
	}
}