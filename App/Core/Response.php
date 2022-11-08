<?php

namespace App\Core;

define('RESPOND_WITH_REQUEST', TRUE);

class Response
{
    private $statusCode = 'HTTP/1.1 200 OK';
    private $headers = [];
    private $messages = [];
    private $data = [];

    public function addHeader($name, $value){
        $this->headers[$name][] = $value;
    }

    public function setHeader($name, $value){
        $this->headers[$name] = [
            (string) $value,
        ];
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

    public function addData($name, $data) {
        $this->data[$name] = $data;
    }

    public function addMessage($subject, $message) {
        $this->messages[$subject] = $message;
    }

    public function setStatus(int $code) {
        $this->statusCode = $code;
    }

    public function content() {
        
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