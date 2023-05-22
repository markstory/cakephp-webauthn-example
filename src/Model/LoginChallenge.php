<?php
declare(strict_types=1);

namespace App\Model;

use ArrayAccess;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use RuntimeException;
use stdClass;

class LoginChallenge implements ArrayAccess
{
    public $loginData;
    public $challenge;

    public function __construct(stdClass $loginData, ByteBuffer $challenge)
    {
        $this->loginData = $loginData;
        $this->challenge = $challenge;
    }

    public function offsetSet($key, $value): void
    {
        throw new RuntimeException('Not Implemented');
    }

    public function offsetUnset($key): void
    {
        throw new RuntimeException('Not Implemented');
    }

    public function offsetExists($key): bool
    {
        return isset($this->{$key});
    }

    public function offsetGet($key)
    {
        return $this->{$key};
    }
}
