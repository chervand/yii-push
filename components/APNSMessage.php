<?php

/**
 * Class APNSMessage
 *
 * @property string $deviceToken
 * @property string $payload
 * @property string|array $alert
 * @property integer $badge
 * @property string $sound
 * @property integer $contentAvailable
 */
class APNSMessage extends Message
{
	/**
	 * @var
	 */
	private $_deviceToken;

	private $_alert = '';
	private $_badge = 0;
	private $_sound = 'default';
	private $_contentAvailable = 1;

	/**
	 * @param ConnectionInterface $connection
	 * @param string | null $deviceToken
	 * @throws CException
	 */
	public function __construct(ConnectionInterface &$connection, $deviceToken = null)
	{
		parent::__construct($connection);
		if (!static::isTokenValid($deviceToken)) {
			throw new CException('Token ' . $deviceToken . ' is invalid.');
		}
		$this->_deviceToken = $deviceToken;
	}

	public static function isTokenValid($token)
	{
		return ctype_xdigit($token);
	}

	/**
	 * @return mixed
	 */
	public function getAlert()
	{
		return $this->_alert;
	}

	/**
	 * @param mixed $alert
	 */
	public function setAlert($alert)
	{
		$this->_alert = $alert;
	}

	/**
	 * @return mixed
	 */
	public function getBadge()
	{
		return $this->_badge;
	}

	/**
	 * @param mixed $badge
	 */
	public function setBadge($badge)
	{
		$this->_badge = $badge;
	}

	/**
	 * @return mixed
	 */
	public function getSound()
	{
		return $this->_sound;
	}

	/**
	 * @param mixed $sound
	 */
	public function setSound($sound)
	{
		$this->_sound = $sound;
	}

	/**
	 * @return mixed
	 */
	public function getContentAvailable()
	{
		return $this->_contentAvailable;
	}

	/**
	 * @param mixed $contentAvailable
	 */
	public function setContentAvailable($contentAvailable)
	{
		$this->_contentAvailable = $contentAvailable;
	}

	public function getDeviceToken()
	{
		return $this->_deviceToken;
	}

	public function getPayload()
	{
		if ($this->alert) {
			return json_encode([
				'aps' => [
					'alert' => $this->getAlert(),
					'badge' => $this->getBadge(),
					'sound' => $this->getSound(),
					'content-available' => $this->getContentAvailable()
				]
			]);
		}
		return null;
	}
}