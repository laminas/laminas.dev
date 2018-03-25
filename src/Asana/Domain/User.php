<?php

declare(strict_types=1);

namespace App\Asana\Domain;

class User
{
    /** @var int|string */
    private $id;

    /** @var string */
    private $name;

    /** @var string */
    private $email;

    /** @param string[] $data */
    public static function fromState(array $data) : User
    {
        $user = new self();

        $user->id    = $data['id'];
        $user->name  = $data['name'];
        $user->email = $data['email'];

        return $user;
    }

    public function id() : string
    {
        return (string) $this->id;
    }

    public function name() : string
    {
        return $this->name;
    }

    public function email() : string
    {
        return $this->email;
    }
}
