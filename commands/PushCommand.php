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
	private $_idlingTime;

	/**
	 * Init.
	 * Creates queues and connections instances, attaches events handlers.
	 */
	public function init()
	{
		/** @var Push $push */
		$push = Yii::app()->push;

		$this->queue = new Queue();

		$this->queue->attachEventHandler('onBeforeProcess', [$this, 'beforeQueueProcess']);
		$this->queue->attachEventHandler('onBeforeSend', [$this, 'beforeQueueSend']);
		$this->queue->attachEventHandler('onAfterSend', [$this, 'afterQueueSend']);
		$this->queue->attachEventHandler('onAfterProcess', [$this, 'afterQueueProcess']);

		$this->connections = new stdClass();
		$this->connections->apns = new APNSConnection($this->queue, false);

		/** @var APNSConnection $apnsConnection */
		$apnsConnection = $this->connections->apns;
		$apnsConnection->certificate = $push->apnsProductionCertificate;
		$apnsConnection->passphrase = $push->apnsProductionCertificatePassphrase;

		$apnsConnection->attachEventHandler('onOpen', [$this, 'onAPNSConnectionOpen']);
		$apnsConnection->attachEventHandler('onClose', [$this, 'onAPNSConnectionClose']);
		$apnsConnection->attachEventHandler('onError', [$this, 'onAPNSConnectionError']);
	}

	/**
	 * Index action.
	 * Processes message queue in endless loop.
	 */
	public function actionIndex()
	{
		$this->log(get_class($this) . ' have been started.');
		while ($this->loop()) {
			try {
				$this->queue->process();
			} catch (Exception $e) {
				$this->log(get_class($e) . ': ' . $e->getMessage(), CLogger::LEVEL_ERROR);
				$this->terminate();
			}
		}
		$this->log(get_class($this) . ' have been finished.');
	}

	protected function loop()
	{
		return !$this->queue->isProcessing;
	}

	/**
	 * Returns true if the command is idling (no notifications are being processed for a while) and false otherwise.
	 * @return bool
	 */
	protected function getIsIdling()
	{
		return isset($this->_idlingTime) && time() - $this->_idlingTime > $this->idlingDelay;
	}

	/**
	 * Before queue processed event handler.
	 */
	protected function beforeQueueProcess()
	{
		/** @var APNSConnection $apnsConnection */
		$apnsConnection = $this->connections->apns;

		$apnsConnection->checkErrorResponse();

		if ($this->queue->count > 0) {
			$this->_idlingTime = null;
		} elseif (!isset($this->_idlingTime)) {
			$this->_idlingTime = time();
		}

		if (!$apnsConnection->isConnected && $this->queue->count > 0) {
			$apnsConnection->open();
		}

		if ($this->queue->count > 0) {
			$this->log('Starting to process a queue of ' . $this->queue->count . ' messages.', CLogger::LEVEL_INFO, __FUNCTION__);
		}
	}

	/**
	 * Before message send event handler.
	 */
	protected function beforeQueueSend()
	{
		/** @var APNSConnection $apnsConnection */
		$apnsConnection = $this->connections->apns;

		if (!$apnsConnection->isConnected) {
			$apnsConnection->open();
		}
	}

	/**
	 * After message send event handler.
	 */
	protected function afterQueueSend()
	{
	}

	/**
	 * After queue processed event handler.
	 */
	protected function afterQueueProcess()
	{
		/** @var APNSConnection $apnsConnection */
		$apnsConnection = $this->connections->apns;

		if ($this->queue->count > 0) {
			$this->log('Queue have been processed.' . $this->queue->count . ' messages.', CLogger::LEVEL_INFO, __FUNCTION__);
		}

		if ($this->isIdling && $apnsConnection->isConnected) {
			$apnsConnection->close();
		}
	}

	protected function onAPNSConnectionOpen()
	{
		$this->log('APNS Connection opened.', CLogger::LEVEL_INFO, __FUNCTION__);
	}

	protected function onAPNSConnectionClose()
	{
		$this->log('APNS Connection closed.', CLogger::LEVEL_INFO, __FUNCTION__);
	}

	protected function onAPNSConnectionError()
	{
	}

	protected function log($msg, $level = CLogger::LEVEL_INFO, $category = null)
	{
		$_c = 'chervand.yii-push.daemon.' . (string)getmypid();
		Yii::log($msg, $level, $category ? $_c . '.' . $category : $_c);
	}

	protected function terminate($code = 1)
	{
		$this->log('Terminating ' . get_class($this) . ' with exit code ' . $code . '.');
		exit($code);
	}
}