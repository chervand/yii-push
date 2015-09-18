<?php

class DbLogRoute extends CDbLogRoute
{
	public $autoCreateLogTable = true;
	public $categories = 'chervand.yii-push.*';
	public $connectionID = 'db';
	public $logTableName = 'push_log';

	public function init()
	{
		parent::init();
		Yii::getLogger()->autoFlush = 1;
		Yii::getLogger()->autoDump = true;
	}
}