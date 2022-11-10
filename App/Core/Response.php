<?php

namespace App\Core;

define('RESPOND_WITH_REQUEST', TRUE);

class Response
{
    private $statusCode = 'HTTP/1.1 200 OK';
    private $headers = [];
    private $messages = [];
    private $data = [];
    private $content = '';

    public function addHeader($name, $value){
        $this->headers[$name][] = $value;

        return $this;
    }

    public function setHeader($name, $value){
        $this->headers[$name] = [
            (string) $value,
        ];

        return $this;
    }

    public function redirect($url, $referrer = null){
        if($referrer) {
            $referrer = urlencode($referrer);
            $url = "{$url}?redirect_uri={$referrer}";
        }

        if(count($this->messages) > 0) {
            foreach($this->messages as $subject => $message) {
                Store::save($subject, $message);
            }
        }
        header('LOCATION: '. $url);
        exit();
    }

    public function setContent(string $content) {
        $this->content = $content;

        return $this;
    }

    public function addData($name, $data) {
        $this->data[$name] = $data;

        return $this;
    }

    public function addMessage($subject, $message) {
        $this->messages[$subject] = $message;

        return $this;
    }

    public function setStatus(int $code) {
        $this->statusCode = $code;

        return $this;
    }

    public function content() {
        foreach($this->headers as $header){
			header($header,true);
		};

        echo $this->content;
    }

    public function json() {
        // Clear output buffer
		ob_clean();
		ob_start();
			
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
		
		
		// Set all headers
		/*$this->header('Rest-Token: '.TOKEN);
		$this->header('Rest-Server: '.SERVER_NAME);
		$this->header('Rest-Server-Version: '.SERVICE_VERSION);*/
		foreach($this->headers as $header){
			header($header,true);
		};
		

		echo json_encode($R);
		die();
    }

    public static function setStatusCode(int $code){
        return http_response_code($code);
    }
}