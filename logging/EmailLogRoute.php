<?php

class EmailLogRoute extends CEmailLogRoute
{
	public $categories = 'chervand.yii-push.*';
	public $levels = 'error, warning';

	public function init()
	{
		parent::init();
		Yii::getLogger()->autoFlush = 1;
		Yii::getLogger()->autoDump = true;
	}
}