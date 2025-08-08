<?php

/**
 * create a seeder using PHP
 */
class Seeder {
    private \Swidly\Core\DB $db;
    
    public function __construct() {
        $this->db = \Swidly\Core\DB::create();
    }

    public function create($name) {
        $this->db->query("CREATE TABLE IF NOT EXISTS $name (id INT AUTO_INCREMENT PRIMARY KEY)");
    }

    public function seed($name, $data) {
        $this->db->query("INSERT INTO $name (id) VALUES (NULL)");
        $id = $this->db->lastInsertId();
        $this->db->query("UPDATE $name SET " . implode(', ', array_map(function($key, $value) {
            return "$key = '$value'";
        }, array_keys($data), $data)) . " WHERE id = $id");
    }

    public function drop($name) {
        $this->db->query("DROP TABLE IF EXISTS $name");
    }

    public function truncate($name) {
        $this->db->query("TRUNCATE TABLE $name");
    }

    public function dropAll() {
        $this->db->query("DROP TABLE IF EXISTS users");
        $this->db->query("DROP TABLE IF EXISTS posts");
        $this->db->query("DROP TABLE IF EXISTS comments");
        $this->db->query("DROP TABLE IF EXISTS likes");
        $this->db->query("DROP TABLE IF EXISTS follows");
        $this->db->query("DROP TABLE IF EXISTS tags");
        $this->db->query("DROP TABLE IF EXISTS posts_tags");
    }

    public function seedAll() {
        $this->create('users');
        $this->create('posts');
        $this->create('comments');
        $this->create('likes');
        $this->create('follows');
        $this->create('tags');
        $this->create('posts_tags');

        $this->seed('users', [
            'username' => 'admin',
            'email' => '',
            'password' => password_hash('admin', PASSWORD_DEFAULT),
            'avatar' => 'https://i.imgur.com/1qkX7mv.png',
            'bio' => 'I am the admin.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->seed('users', [
            'username' => 'user',
            'email' => '',
            'password' => password_hash('user', PASSWORD_DEFAULT),
            'avatar' => 'https://i.imgur.com/1qkX7mv.png',
            'bio' => 'I am the user.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->seed('posts', [
            'user_id' => 1,
            'content' => 'Hello, world!',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->seed('posts', [
            'user_id' => 2,
            'content' => 'Hello, world!',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->seed('comments', [
            'user_id' => 1,
            'post_id' => 1,
            'content' => 'Hello, world!',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}