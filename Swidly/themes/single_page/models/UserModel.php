<?php

<<<<<<< HEAD
<<<<<<< HEAD:Swidly/themes/single_page/models/UserModel.php
namespace Swidly\themes\single_page\models;
=======
namespace Swidly\themes\default\models;
>>>>>>> 264e7cc21600ddd025ea82dfa9ff19115d813106:Swidly/themes/default/models/UserModel.php
=======
namespace Swidly\themes\single_page\models;
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
    public string $first_name;

    #[Column(type: Types::STRING)]
    public string $last_name;
}