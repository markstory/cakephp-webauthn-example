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

    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value): void
    {
        throw new RuntimeException('Not Implemented');
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($key): void
    {
        throw new RuntimeException('Not Implemented');
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($key): bool
    {
        return isset($this->{$key});
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        return $this->{$key};
    }
}
