<?php
declare(strict_types=1);

namespace PhpETL\Tap\MySQL;

class Config
{
    public readonly string $dsn;
    public readonly string $password;
    public readonly string $username;

    static public function fromFile(string $path)
    {
        $json = json_decode(file_get_contents($path), true);

        $instance = new static();
        $instance->dsn = $json['dsn'];
        $instance->password = $json['password'];
        $instance->username = $json['username'];

        return $instance;
    }
}