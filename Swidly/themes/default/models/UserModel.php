<?php

<<<<<<< HEAD
namespace Swidly\themes\default\models;
=======
<<<<<<< HEAD:Swidly/themes/single_page/models/UserModel.php
namespace Swidly\themes\single_page\models;
=======
namespace Swidly\themes\default\models;
>>>>>>> 264e7cc21600ddd025ea82dfa9ff19115d813106:Swidly/themes/default/models/UserModel.php
>>>>>>> 264e7cc21600ddd025ea82dfa9ff19115d813106

use Swidly\Core\Attributes\Column;
use Swidly\Core\Attributes\Table;
use Swidly\Core\Enums\Types;
use Swidly\Core\Model;

#[Table(name: 'users')]
class UserModel extends Model {
    #[Column(type: Types::INTEGER, isPrimary: true)]
    public int $id;

    #[Column(type: Types::STRING, length: 50)]
    public string $username;

    #[Column(type: Types::STRING)]
    public string $password;

    #[Column(type: Types::STRING)]
    public string $email;

    #[Column(type: Types::STRING)]
<<<<<<< HEAD
    public string $firstName;

    #[Column(type: Types::STRING)]
    public string $lastName;

    #[Column(type: Types::STRING)]
    public ?string $userName = null;

    public function getId(): int {
        return $this->id ?? 0;
    }

    public function getUsername(): ?string
    {
        return $this->userName;
    }

    public function setUsername(?string $username): void
    {
        $this->userName = $username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $first_name): void
    {
        $this->firstName = $first_name;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $last_name): void
    {
        $this->lastName = $last_name;
    }
=======
    public string $first_name;

    #[Column(type: Types::STRING)]
    public string $last_name;
>>>>>>> 264e7cc21600ddd025ea82dfa9ff19115d813106
}