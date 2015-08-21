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
			['user_id, created_at, updated_at, status', 'numerical', 'integerOnly' => true],
			['platform, token', 'length', 'max' => 255],
			['platform', 'in', 'range' => [self::PLATFORM_ANDROID, self::PLATFORM_IOS]],
			['status', 'in', 'range' => [self::STATUS_DELETED, self::STATUS_DISABLED, self::STATUS_ACTIVE]],
			['id, user_id, platform, token, created_at, updated_at, status', 'safe', 'on' => 'search'],
		];
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

	/**
	 * @inheritdoc
	 */
	public function beforeSave()
	{
		if (parent::beforeSave()) {
			if ($this->getIsNewRecord()) {
				$this->created_at = time();
			}
			$this->updated_at = time();
			return true;
		}
		return false;
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

	protected function beforeValidate()
	{
		if (parent::beforeValidate()) {
			$user = Yii::app()->user;
			if ($user instanceof CWebUser) {
				$this->user_id = $user->id;
			}
			return true;
		} else {
			return false;
		}
	}
}