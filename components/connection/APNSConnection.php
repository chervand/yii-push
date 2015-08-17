<?php

class APNSConnection extends Connection
{
	public $certificate;
	public $passphrase;
	private $_stream;

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