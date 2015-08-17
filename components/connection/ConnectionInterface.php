<?php

/**
 * Interface ConnectionInterface
 * @author chervand <chervand@gmail.com>
 *
 * @property Queue $queue
 */
interface ConnectionInterface
{
	/**
	 * @param QueueInterface $queue
	 */
	function __construct(QueueInterface &$queue);

	/**
	 * @return Queue
	 */
	function getQueue();
}