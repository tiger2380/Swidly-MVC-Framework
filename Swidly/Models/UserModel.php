<?php

namespace Swidly\Models;

use Swidly\Core\Model;
use Swidly\Core\Attributes\Table;
use Swidly\Core\Attributes\Column;
use Swidly\Core\Enums\Types;

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
    public string $first_name;

    #[Column(type: Types::STRING)]
    public string $last_name;
}