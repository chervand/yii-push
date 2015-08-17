<?php

class PushCommand extends CConsoleCommand
{
	public function init()
	{
		Yii::import('vendor.chervand.yii-push.components.*');
		Yii::import('vendor.chervand.yii-push.interfaces.*');
	}

	public function actionIndex()
	{
		$queue = new Queue();

		$queue->attachEventHandler('onBeforeProcess', function() use(&$queue) {
			echo 'Starting to process a queue of ' . $queue->count . ' messages.' . PHP_EOL;
		});
		$queue->attachEventHandler('onAfterProcess', function() use(&$queue) {
			echo 'Queue have been processed.' . PHP_EOL;
		});

		$socketConnection = new SocketConnection($queue);
		$httpConnection = new HTTPConnection($queue);

		$messages[] = new APNSMessage($socketConnection);
		$messages[] = new APNSMessage($socketConnection);
		$messages[] = new GCMMessage($httpConnection);
		$messages[] = new GCMMessage($httpConnection);
		$messages[] = new APNSMessage($socketConnection);
		$messages[] = new APNSMessage($socketConnection);
		$messages[] = new GCMMessage($httpConnection);

		foreach ($messages as $message) {
			$queue->enqueue($message);
		}

		try {
			$result = $queue->process();
			if ($result) {
				echo get_class($this) . ' have been finished.' . PHP_EOL;
			}
		} catch (Exception $e) {
			echo get_class($e) . ': '. $e->getMessage() . PHP_EOL;
		}

	}
}