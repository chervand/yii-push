<?php

/**
 * @property integer $id
 * @property integer $user_id
 * @property string $platform
 * @property string $token
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $status
 */
class PushDevice extends CActiveRecord
{
	const PLATFORM_ANDROID = 'android';
	const PLATFORM_IOS = 'ios';

	const STATUS_DELETED = -1;
	const STATUS_DISABLED = 0;
	const STATUS_ACTIVE = 1;

	/**
	 * @inheritdoc
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @inheritdoc
	 */
	public function tableName()
	{
		return 'push_device';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			['user_id, platform, token', 'required'],
			['user_id, created_at, updated_at', 'numerical', 'integerOnly' => true],
			['platform, token', 'length', 'max' => 255],
			['platform', 'in', 'range' => [self::PLATFORM_ANDROID, self::PLATFORM_IOS]],
			['status', 'in', 'range' => [self::STATUS_DELETED, self::STATUS_DISABLED, self::STATUS_ACTIVE]],
			['token', 'vToken'],
			['id, user_id, platform, token, created_at, updated_at, status', 'safe', 'on' => 'search'],
		];
	}

	public function vToken()
	{
		$this->token = preg_replace('/\s+/', '', $this->token);
		if (!$this->isTokenValid()) {
			$this->addError('token', 'Invalid device token.');
		}
	}

	public function isTokenValid()
	{
		switch($this->platform) {
			case self::PLATFORM_IOS:
				return APNSMessage::validateDeviceToken($this->token);
				break;
		}
		return false;
	}

	public function save($runValidation = true, $attributes = null)
	{
		$user = Yii::app()->user;
		if ($user instanceof CWebUser) {
			$model = self::model()->find([
				'scopes' => ['active'],
				'condition' => 'user_id=:userId AND token=:token AND platform=:platform',
				'params' => [
					':userId' => $user->id,
					':token' => $this->token,
					':platform' => $this->platform,
				]
			]);
			if ($model instanceof PushDevice) {
				$this->setIsNewRecord(false);
				$this->setPrimaryKey($model->id);
			}
		}
		return parent::save($runValidation, $attributes);
	}

	/**
	 * @inheritdoc
	 */
	public function relations()
	{
		return [];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'user_id' => 'User',
			'platform' => 'Platform',
			'token' => 'Token',
			'created_at' => 'Created At',
			'updated_at' => 'Updated At',
			'status' => 'Status',
		];
	}

	/**
	 * @inheritdoc
	 */
	public function search()
	{
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('user_id', $this->user_id);
		$criteria->compare('platform', $this->platform, true);
		$criteria->compare('token', $this->token, true);
		$criteria->compare('created_at', $this->created_at);
		$criteria->compare('updated_at', $this->updated_at);
		$criteria->compare('status', $this->status);

		return new CActiveDataProvider($this, [
			'criteria' => $criteria,
		]);
	}

	public function scopes()
	{
		return [
			'deleted' => ['condition' => $this->tableAlias . '.status="' . self::STATUS_DELETED . '"'],
			'disabled' => ['condition' => $this->tableAlias . '.status="' . self::STATUS_DISABLED . '"'],
			'active' => ['condition' => $this->tableAlias . '.status="' . self::STATUS_ACTIVE . '"'],
			'ios' => ['condition' => $this->tableAlias . '.platform="' . self::PLATFORM_IOS . '"'],
			'android' => ['condition' => $this->tableAlias . '.platform="' . self::PLATFORM_ANDROID . '"'],
		];
	}

	/**
	 * @inheritdoc
	 */
	protected function beforeValidate()
	{
		if (parent::beforeValidate()) {
			$user = Yii::app()->user;
			if ($user instanceof CWebUser) {
				$this->user_id = $user->id;
			}
			if ($this->getIsNewRecord()) {
				$this->created_at = time();
			}
			$this->updated_at = time();
			return true;
		} else {
			return false;
		}
	}
}