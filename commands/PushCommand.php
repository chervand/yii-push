<?php

class PushCommand extends CConsoleCommand
{
	public function init()
	{
		Yii::import('vendor.chervand.yii-push.components.*');
	}

	public function actionIndex()
	{
		$queue = new Queue();

		$queue->attachEventHandler('onBeforeProcess', function () use (&$queue) {
			echo 'Starting to process a queue of ' . $queue->count . ' messages.' . PHP_EOL;
		});
		$queue->attachEventHandler('onAfterProcess', function () use (&$queue) {
			echo 'Queue have been processed.' . PHP_EOL;
		});

		$connection = new APNSConnection($queue);
		$connection->certificate = Yii::app()->basePath . '/cert/apns_prod_cert.pem';
		$connection->passphrase = 'Parent123';

		$deviceToken = '86e6b954eac7a4322e3dde0801ff36c8c90d0eda105a50e275e551765a97a8b7';

		$message1 = new APNSMessage($connection, $deviceToken);
		$message1->alert = [
			'title' => 'Apple sucks',
			'body' => 'olololo'
		];

		$message2 = new APNSMessage($connection, $deviceToken);
		$message2->alert = 'Message 2';

		$queue->enqueue($message1);
		$queue->enqueue($message2);

		try {
			$result = $queue->process();
			if ($result) {
				echo get_class($this) . ' have been finished.' . PHP_EOL;
			}
		} catch (Exception $e) {
			echo get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
		}

	}
}