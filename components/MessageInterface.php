<?php

interface MessageInterface
{
	public function __construct(ConnectionInterface &$connection);

	public function __toString();

	public function send();

	public function getIsSending();
}