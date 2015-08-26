<?php

class PushCommand extends CConsoleCommand
{
	/**
	 * @var int
	 */
	public $idlingDelay = 3600;

	/**
	 * @var stdClass
	 */
	protected $connections;

	/**
	 * @var Queue
	 */
	protected $queue;

	/**
	 * @var int
	 */
	protected $idlingTime;

	public function init()
	{
		/** @var Push $push */
		$push = Yii::app()->push;

		$this->queue = new Queue();
		$this->connections = new stdClass();
		$this->connections->apns = new APNSConnection($this->queue, false);

		/** @var APNSConnection $apnsConnection */
		$apnsConnection = $this->connections->apns;
		$apnsConnection->certificate = $push->apnsProductionCertificate;
		$apnsConnection->passphrase = $push->apnsProductionCertificatePassphrase;

		$this->queue->attachEventHandler('onBeforeProcess', function () use (&$apnsConnection) {

			if ($this->queue->count > 0) {
				$this->idlingTime = null;
			} elseif (!isset($this->idlingTime)) {
				$this->idlingTime = time();
			}

			if (!$apnsConnection->isConnected() && $this->queue->count > 0) {
				$apnsConnection->open();
				echo 'APNS Connection opened.' . PHP_EOL;
			}

			echo 'Starting to process a queue of ' . $this->queue->count . ' messages.' . PHP_EOL;

		});

		$this->queue->attachEventHandler('onAfterProcess', function () use (&$apnsConnection) {

			echo 'Queue have been processed.' . PHP_EOL;

			if ($this->isIdling() && $apnsConnection->isConnected()) {
				$apnsConnection->close();
				echo 'APNS Connection closed.' . PHP_EOL;
			}

		});
	}

	protected function isIdling()
	{
		return isset($this->idlingTime) && time() - $this->idlingTime > $this->idlingDelay;
	}

	public function actionIndex()
	{
		echo get_class($this) . ' have been started.' . PHP_EOL;
		while (!$this->queue->isProcessing()) {
			try {
				foreach ($this->queued() as $message) {
					$this->queue->enqueue($message);
				}
				if ($this->queue->count > 0) {
					$this->queue->process();
				}
			} catch (Exception $e) {
				echo get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
			}
		}
		echo get_class($this) . ' have been finished.' . PHP_EOL;
	}

	protected function queued()
	{
		return [];
	}
}