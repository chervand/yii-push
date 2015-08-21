<?php

class m150817_162754_push_device extends CDbMigration
{
	public function safeUp()
	{
		$this->createTable('push_device', [
			'id' => 'pk',
			'user_id' => 'integer NOT NULL',
			'platform' => 'string NOT NULL',
			'token' => 'string NOT NULL',
			'created_at' => 'integer',
			'updated_at' => 'integer',
			'status' => 'TINYINT NOT NULL DEFAULT 1',
			'KEY (user_id)',
			'KEY (platform)',
			'KEY (status)'
		], 'ENGINE=InnoDB CHARSET=utf8 COLLATE=utf8_unicode_ci');
	}

	public function safeDown()
	{
		$this->dropTable('push_device');
	}
}