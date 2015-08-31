<?php

/**
 * Class APNSConnection implements Apple Push Notification Service connection.
 *
 * @property bool $isConnected whether connection is open or not
 */
class APNSConnection extends Connection
{
	public $certificate;
	public $passphrase;
	public $error;
	private $_stream;

	/**
	 * Constructor.
	 * @param QueueInterface $queue
	 * @param bool|true $autoconnect
	 */
	public function __construct(QueueInterface &$queue, $autoconnect = true)
	{
		parent::__construct($queue);
		if ($autoconnect === true) {
			$this->queue->attachEventHandler('onBeforeProcess', [$this, 'open']);
			$this->queue->attachEventHandler('onAfterProcess', [$this, 'close']);
		}
	}

	public function onOpen($event)
	{
		$this->raiseEvent('onOpen', $event);
	}

	public function onClose($event)
	{
		$this->raiseEvent('onClose', $event);
	}

	public function onError($event)
	{
		$this->raiseEvent('onError', $event);
	}

	/**
	 * Opens a connection.
	 * @throws CException
	 */
	public function open()
	{
		if (!isset($this->certificate) || !file_exists($this->certificate)) {
			throw new CException('Certificate not defined or not exists.');
		}

		$context = stream_context_create();

		stream_context_set_option($context, 'ssl', 'local_cert', $this->certificate);

		if (isset($this->passphrase)) {
			stream_context_set_option($context, 'ssl', 'passphrase', $this->passphrase);
		}

		$this->_stream = @stream_socket_client('ssl://gateway.push.apple.com:2195',
			$errno, $errstr, 10,
			STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT,
			$context);

		if (!$this->_stream) {
			throw new CException('Failed to connect: ' . $errno . ' - ' . $errstr);
		}

		$this->onOpen(new CEvent());
	}

	/**
	 * Closes a connection.
	 */
	public function close()
	{
		if (is_resource($this->_stream)) {
			if (fclose($this->_stream)) {
				$this->onClose(new CEvent());
			}
		}
	}

	/**
	 * Sends a message.
	 * @param MessageInterface $message
	 * @return bool
	 * @throws CException
	 */
	public function send(MessageInterface &$message)
	{
		if (!$this->isConnected) {
			throw new CException('APNSMessage send failed: disconnected.');
		}

		if (!$message instanceof APNSMessage) {
			throw new CException('APNSMessage send failed: not an instance of APNSMessage class.');
		}

		$result = @fwrite($this->_stream, $message, strlen($message));

		return is_int($result);
	}

	/**
	 * Return connection status.
	 * @return bool whether connection is open
	 */
	public function getIsConnected()
	{
		return is_resource($this->_stream) && $this->_stream !== false;
	}

	public function checkErrorResponse()
	{
		if ($this->isConnected) {

			stream_set_blocking($this->_stream, 0);

			$this->error = null;

			$packedErrorResponse = fread($this->_stream, 6);
			if ($packedErrorResponse) {
				$unpackedErrorResponse = unpack('Ccommand/Cstatus_code/Nidentifier', $packedErrorResponse);
				if ($unpackedErrorResponse) {
					$this->error = (object)$unpackedErrorResponse;
				}
			}

			if ($this->error instanceof stdClass) {
				if (isset($this->error->status_code)) {
					switch ($this->error->status_code) {
						case 0:
							$this->error->description = 'No errors encountered';
							break;
						case 1:
							$this->error->description = 'Processing error';
							break;
						case 2:
							$this->error->description = 'Missing device token';
							break;
						case 3:
							$this->error->description = 'Missing topic';
							break;
						case 4:
							$this->error->description = 'Missing payload';
							break;
						case 5:
							$this->error->description = 'Invalid token size';
							break;
						case 6:
							$this->error->description = 'Invalid topic size';
							break;
						case 7:
							$this->error->description = 'Invalid payload size';
							break;
						case 8:
							$this->error->description = 'Invalid token';
							break;
						case 10:
							$this->error->description = 'Shutdown';
							break;
						case 255:
							$this->error->description = 'None (unknown)';
							break;
						default:
							$this->error->description = 'Not listed';
					}

					if ($this->error->status_code > 0) {
						$this->onError(new CEvent());
						return true;
					}

				}
			}

		}

		return false;
	}
}