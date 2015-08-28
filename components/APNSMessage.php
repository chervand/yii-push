<?php

/**
 * Class APNSMessage
 *
 * @property-read int $format notification format (command), defaults to 2
 *
 * @property int $expirationDate
 * @property int $payloadBadge
 * @property int $payloadContentAvailable
 * @property int $priority
 * @property mixed $notificationIdentifier
 * @property mixed $payloadAlert
 * @property string $deviceToken
 * @property string $payloadSound
 *
 * @todo implement enhanced notification format build
 *
 * @author chervand <chervand@gmail.com>
 */
class APNSMessage extends Message
{
	/**
	 * Simple notification format.
	 * @see https://developer.apple.com/library/ios/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/Chapters/LegacyFormat.html
	 */
	const FORMAT_SIMPLE = 0;

	/**
	 * Enhanced notification format.
	 * https://developer.apple.com/library/ios/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/Chapters/LegacyFormat.html
	 */
	const FORMAT_ENHANCED = 1;

	/**
	 * Default notification format.
	 * @see https://developer.apple.com/library/ios/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/Chapters/CommunicatingWIthAPS.html
	 */
	const FORMAT_DEFAULT = 2;

	const PAYLOAD_SOUND_DEFAULT = 'default';
	const PAYLOAD_BADGE_DEFAULT = 0;
	const PAYLOAD_CONTENT_AVAILABLE_DEFAULT = 1;

	private $_deviceToken;
	private $_expirationDate;
	private $_format;
	private $_notificationIdentifier;
	private $_payloadAlert;
	private $_payloadBadge;
	private $_payloadContentAvailable;
	private $_payloadSound;
	private $_priority;

	/**
	 * @param ConnectionInterface $connection
	 * @param int $format notification format, defaults to 2
	 * @throws CException
	 */
	public function __construct(ConnectionInterface &$connection, $format = self::FORMAT_DEFAULT)
	{
		parent::__construct($connection);

		if (!in_array($format, [self::FORMAT_SIMPLE, self::FORMAT_ENHANCED, self::FORMAT_DEFAULT])) {
			throw new CException('Unknown message format.');
		}

		$this->_format = $format;
	}

	/**
	 * @inheritdoc
	 */
	public function __toString()
	{
		return $this->_build();
	}

	/**
	 * Validates a APNS token.
	 * @param string $token device token
	 * @return bool whether token is valid or not
	 */
	public static function validateDeviceToken($token)
	{
		return ctype_xdigit($token);
	}

	/**
	 * Returns notification format (command).
	 * @return int notification format (command), defaults to 2
	 */
	public function getFormat()
	{
		return $this->_format;
	}

	/**
	 * @return string
	 */
	public function getDeviceToken()
	{
		return $this->_deviceToken;
	}

	/**
	 * @return mixed
	 */
	public function getPayloadAlert()
	{
		return $this->_payloadAlert;
	}

	/**
	 * @return mixed
	 */
	public function getPayloadBadge()
	{
		return isset($this->_payloadBadge) ? $this->_payloadBadge : self::PAYLOAD_BADGE_DEFAULT;
	}

	/**
	 * @return mixed
	 */
	public function getPayloadSound()
	{
		return isset($this->_payloadSound) ? $this->_payloadSound : self::PAYLOAD_SOUND_DEFAULT;
	}

	/**
	 * @return mixed
	 */
	public function getPayloadContentAvailable()
	{
		return isset($this->_payloadContentAvailable) ? $this->_payloadContentAvailable : self::PAYLOAD_CONTENT_AVAILABLE_DEFAULT;
	}

	public function setDeviceToken($deviceToken)
	{
		if (!self::validateDeviceToken($deviceToken)) {
			throw new CException('Token ' . $deviceToken . ' is invalid.');
		}
		$this->_deviceToken = $deviceToken;
		return $this;
	}

	public function setPayloadAlert($alert)
	{
		$this->_payloadAlert = $alert;
		return $this;
	}

	/**
	 * @param mixed $badge
	 * @return $this
	 */
	public function setPayloadBadge($badge)
	{
		$this->_payloadBadge = $badge;
		return $this;
	}

	/**
	 * @param mixed $sound
	 * @return $this
	 */
	public function setPayloadSound($sound)
	{
		$this->_payloadSound = $sound;
		return $this;
	}

	/**
	 * @param mixed $contentAvailable
	 * @return $this
	 */
	public function setPayloadContentAvailable($contentAvailable)
	{
		$this->_payloadContentAvailable = $contentAvailable;
		return $this;
	}

	/**
	 * Returns JSON-formatted notification payload.
	 * @return string json-formatted notification payload
	 */
	private function _payload()
	{
		return json_encode([
			'aps' => [
				'alert' => $this->payloadAlert,
				'badge' => $this->payloadBadge,
				'sound' => $this->payloadSound,
				'content-available' => $this->payloadContentAvailable
			]
		]);
	}

	/**
	 * Returns notification binary string according to the defined format.
	 * @return string
	 * @see getFormat()
	 */
	private function _build()
	{
		try {
			switch ($this->format) {
				case self::FORMAT_SIMPLE:
					return $this->_buildSimple();
					break;
				case self::FORMAT_ENHANCED:
					return $this->_buildEnhanced();
					break;
				case self::FORMAT_DEFAULT:
				default:
					return $this->_buildDefault();
			}
		} catch (Exception $e) {
			return (string)$e;
		}
	}

	/**
	 * Returns Simple Notification Format binary string.
	 * @return string notification binary string
	 */
	private function _buildSimple()
	{
		$_p = $this->_payload();
		return
			chr(self::FORMAT_SIMPLE)
			. pack('n', 32) . pack('H*', $this->deviceToken)
			. pack('n', strlen($_p)) . $_p;
	}

	/**
	 * Returns Enhanced Notification Format binary string.
	 * @return string notification binary string
	 * @throws CException
	 */
	private function _buildEnhanced()
	{
		$_p = $this->_payload();
		throw new CException('Enhanced Notification Format build is not implemented.');
	}

	/**
	 * Returns Default Notification Format binary string.
	 * @return string notification binary string
	 */
	private function _buildDefault()
	{
		$_p = $this->_payload();
		return CVarDumper::dumpAsString($_p);
		$_n = pack('CnH*', 1, 32, $this->deviceToken)
			. pack('CnA*', 2, strlen($_p), $_p)
//			. pack('CnA*', 3, 4, $this->notificationIdentifier)
//			. pack('CnN', 3, 4, $this->notificationIdentifier)
//			. pack('CnN', 4, 4, $this->expireTime)
			. pack('CnC', 5, 1, 10);
		return pack('CN', self::FORMAT_DEFAULT, strlen($_n)) . $_n;
	}
}