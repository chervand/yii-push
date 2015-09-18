<?php

class CliLogRoute extends CLogRoute
{
	public $categories = 'chervand.yii-push.*';

	public function init()
	{
		parent::init();
		Yii::getLogger()->autoFlush = 1;
		Yii::getLogger()->autoDump = true;
	}

	protected function processLogs($logs)
	{
		foreach($logs as $log)
		{
			echo implode(', ', [gmdate('c', $log[3]), $log[1], $log[2]]);
			echo ': ' . $log[0];
			echo PHP_EOL;
		}
	}
}