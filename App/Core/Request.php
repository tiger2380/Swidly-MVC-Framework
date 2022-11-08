<?php

namespace App\Core;

class Request
{
    protected $request = array();
    protected $server = null;
    protected $user = null;
    public $vars = array();

    public function __construct()
    {
        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);

        if(!is_array($decoded)) {
            $decoded = [];
        }

        $this->request = array_merge($_GET, $_POST, $_COOKIE, $_FILES, $decoded, $_SERVER, $_SESSION);
        $this->server = $_SERVER;
        $this->CheckAuthentication();
        $this->getUser();
    }

    public function set($key, $value) {
        $this->request[$key] = $value;
    }

    public function get($key, $defaultValue = null) {
        return isset($this->request[$key]) ? $this->request[$key] : $defaultValue;
    }

    protected function getUser() {
        if($this->is_authenticated) {
            $user = DB::Table('users')->Select()->WhereOnce(['email' => Store::get(App::getConfig('session_name'))]);
            $this->vars['user'] = (object)$user;
        } else {
            $this->vars['user'] = null;
        }
    }

    public function getUri()
    {
        return trim($this->server['REQUEST_URI'], '/');
    }

    public function getType()
    {
        return $this->server['REQUEST_METHOD'];
    }

    public function isPost() {
        return $this->getType() === 'POST';
    }

    public function isGet() {
        return $this->getType() === 'GET';
    }

    public function CheckAuthentication() {
        $this->vars['is_authenticated'] = isset($_SESSION[App::getConfig('session_name')]);
    }

    public function __get($key) {
        if(isset($this->vars[$key])) {
            return $this->vars[$key];
        }
    }

    public function __set($key, $value) {
        $this->vars[$key] = $value;
    }

    public function getBody() {
        $body = [];

        if($this->isPost()) {
            foreach ($_POST as $key => $value) {
                $body[$key] = filter_input(INPUT_POST, $key);
            }
        }

        if($this->isGet()) {
            foreach ($_GET as $key => $value) {
                $body[$key] = filter_input(INPUT_GET, $key);
            }
        }

        return $body;
    }

    public function serverName() {
        return $this->server['SCRIPT_NAME'];
    }
}