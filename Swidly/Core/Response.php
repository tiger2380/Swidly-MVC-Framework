<?php

namespace Swidly\Core;

use JetBrains\PhpStorm\NoReturn;

define('RESPOND_WITH_REQUEST', FALSE);

class Response
{
    private string $statusCode = 'HTTP/1.1 200 OK';
    private array $headers = [];
    private array $messages = [];
    private array $data = [];
    private string $content = '';

    public function addHeader($name, $value): static
    {
        $this->headers[$name][] = $value;

        return $this;
    }

    public function setHeader($name, $value): static
    {
        $this->headers[$name] = [
            (string) $value,
        ];

        return $this;
    }

    #[NoReturn]
    public function redirect($url, $referrer = null){
        if($referrer) {
            $url = $this->addReferrer($url, $referrer);
        }

        if (Swidly::getConfig('app::base_url')) {
            $url = Swidly::getConfig('app::base_url') . $url;
        } else {
            $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $url;
        }

        if(count($this->messages) > 0) {
            $this->saveMessages();
        }
        header('LOCATION: '. $url);
        exit();
    }

    private function addReferrer($url, $referrer) {
        $referrer = urlencode($referrer);
        return "{$url}?redirect_uri={$referrer}";
    }

    private function saveMessages() {
        foreach($this->messages as $subject => $message) {
            Store::save($subject, $message);
        }
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function addData($name, $data): static
    {
        if (is_string($name)) {
            $this->data[$name] = $data;
        } else {
            throw new InvalidArgumentException('Name must be a string');
        }

        return $this;
    }

    public function addMessage($subject, $message): static
    {
        if (!is_string($subject)) {
            throw new InvalidArgumentException('Subject must be a string');
        }

        if (!is_string($message)) {
            throw new InvalidArgumentException('Message must be a string');
        }

        if (isset($this->messages[$subject])) {
            throw new InvalidArgumentException('Subject already exists');
        }

        $this->messages[$subject] = $message;

        return $this;
    }

    public function setStatus(int $code): static
    {
        $this->statusCode = $code;

        return $this;
    }

    #[NoReturn]
    public function content(): void
    {
        foreach($this->headers as $header){
			header($header,true);
		};

        echo $this->content;
    }

    #[NoReturn]
    public function json(): void
    {
        // Clear output buffer
		ob_clean();
		ob_start();
			
		// Create a Response object
		$R = new \stdClass;
		$R->status = $this->statusCode;
		if(!empty($this->message)){
			$R->msg = $this->message;
		};
		if(isset($this->data)){
			$R->data = $this->data;
		};
		
		// If set, include Request in response
		if(defined('RESPOND_WITH_REQUEST') && RESPOND_WITH_REQUEST){	
			 $R->_Request = $GLOBALS['Request'];
		};
		
		foreach($this->headers as $header){
			header($header,true);
		};

		echo json_encode($R, JSON_PRETTY_PRINT);
		die();
    }

    public static function setStatusCode(int $code): bool|int
    {
        return http_response_code($code);
    }

    public static function get($url, $headers = []): string|array|null
    {
        $response = \file_get_contents($url, false, stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => $headers,
            ],
        ]));

        if ($response === false) {
            return null;
        }
        return json_decode($response, true);
    }

    public static function post($url, $data, $headers = []): string|array|null
    {
        $response = \file_get_contents($url, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => $headers,
                'content' => json_encode($data),
            ],
        ]));
        return json_decode($response, true);
    }
}