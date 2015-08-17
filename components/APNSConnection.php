<?php

class APNSConnection extends Connection
{
	public $certificate;
	public $passphrase;
	private $_stream;

	public function send(MessageInterface &$message)
	{
		if (!$message instanceof APNSMessage) {
			throw new CException('Message is not instance of APNSMessage class.');
		}

		$deviceToken = $message->deviceToken;
		$payload = $message->payload;
		$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
		$result = fwrite($this->_stream, $msg, strlen($msg));

		return is_int($result);
	}

	protected function beforeQueueProcess()
	{
		$this->open();
	}

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

		$this->_stream = stream_socket_client('ssl://gateway.push.apple.com:2195',
			$err, $errstr, 60,
			STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT,
			$context);

		if (!$this->_stream) {
			throw new CException('Failed to connect: ' . $errstr);
		}
	}

	protected function afterQueueProcess()
	{
		$this->close();
	}

	public function close()
	{
		if (is_resource($this->_stream)) {
			fclose($this->_stream);
		}
	}
}