<?php

interface MessageInterface
{
	public function __construct(ConnectionInterface &$connection);
	public function send();
}