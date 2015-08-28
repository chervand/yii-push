<?php

/**
 * Class PushCommand implements a default push daemon.
 * Should be overridden with a class implementing required {@link queued()} method
 * and optional {@link beforeProcess()}, {@link beforeSend()}, {@link afterSend()} and {@link afterProcess()} methods.
 *
 * @property bool $isIdling Returns true if the command is idling (no notifications are being processed for a while) and false otherwise.
 */
class PushCommand extends CConsoleCommand
{
	/**
	 * time in seconds since last notification being processed
	 * after which the command will be assumed as idling
	 * @var int
	 */
	public $idlingDelay = 600;

	/**
	 * internal connections storage
	 * @var stdClass
	 */
	protected $connections;

	/**
	 * internal queue storage
	 * @var Queue
	 */
	protected $queue;

	/**
	 * internal idling time storage
	 * @var int
	 */
	protected $idlingTime;

	/**
	 * Init.
	 * Creates queues and connections instances, attaches events handlers.
	 */
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

		$this->queue->attachEventHandler('onBeforeProcess', [$this, 'beforeProcess']);
		$this->queue->attachEventHandler('onBeforeSend', [$this, 'beforeSend']);
		$this->queue->attachEventHandler('onAfterSend', [$this, 'afterSend']);
		$this->queue->attachEventHandler('onAfterProcess', [$this, 'afterProcess']);
	}

	/**
	 * Index action.
	 * Processes message queue in endless loop.
	 */
	public function actionIndex()
	{
		echo get_class($this) . ' have been started.' . PHP_EOL;
		while (!$this->queue->isProcessing) {
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

	/**
	 * Returns true if the command is idling (no notifications are being processed for a while) and false otherwise.
	 * @return bool
	 */
	protected function getIsIdling()
	{
		return isset($this->idlingTime) && time() - $this->idlingTime > $this->idlingDelay;
	}

	/**
	 * Should return an array of messages.
	 * @return Message[]
	 */
	protected function queued()
	{
		return [];
	}

	/**
	 * Before queue processed event handler.
	 */
	protected function beforeProcess()
	{
		/** @var APNSConnection $apnsConnection */
		$apnsConnection = $this->connections->apns;

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
	}

	/**
	 * Before message send event handler.
	 */
	protected function beforeSend()
	{
		/** @var APNSConnection $apnsConnection */
		$apnsConnection = $this->connections->apns;

		if (!$apnsConnection->isConnected()) {
			$apnsConnection->open();
			echo 'APNS Connection opened.' . PHP_EOL;
		}
	}

	/**
	 * After message send event handler.
	 */
	protected function afterSend()
	{
		/** @var APNSConnection $apnsConnection */
		$apnsConnection = $this->connections->apns;

		usleep(300000); // 300 ms

		if ($apnsConnection->checkErrorResponse()) {

			echo $apnsConnection->error->identifier . ': ' . $apnsConnection->error->status_code . ' - ' . $apnsConnection->error->description . PHP_EOL;

			if ($apnsConnection->error->status_code == 8) { //replace with events 'onInvalidToken' ?

				$item = $this->queue->item;
				if ($item instanceof APNSMessage) {
					$count = PushDevice::model()->updateAll(
						['status' => PushDevice::STATUS_DISABLED],
						[
							'scopes' => ['active', 'ios'],
							'condition' => 'token=:token',
							'params' => [':token' => $item->deviceToken]
						]
					);
					echo 'Token: ' . $item->deviceToken . ', ' . $count . ' devices disabled.' . PHP_EOL;
				}
			}

			$apnsConnection->close();
			echo 'APNS Connection closed.' . PHP_EOL;

		}
	}

	/**
	 * After queue processed event handler.
	 */
	protected function afterProcess()
	{
		/** @var APNSConnection $apnsConnection */
		$apnsConnection = $this->connections->apns;

		echo 'Queue have been processed.' . PHP_EOL;

		if ($this->isIdling && $apnsConnection->isConnected()) {
			$apnsConnection->close();
			echo 'APNS Connection closed.' . PHP_EOL;
		}
	}
}