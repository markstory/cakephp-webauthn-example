<?php
declare(strict_types=1);

namespace App\Model;

use lbuchs\WebAuthn\Binary\ByteBuffer;
use stdClass;

class CreateData
{
    public function __construct(stdClass $payload)
    {
        $this->registration = $registration;
        $this->challenge = $challenge;
    }
}
