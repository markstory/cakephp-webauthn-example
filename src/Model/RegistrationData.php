<?php
declare(strict_types=1);

namespace App\Model;

use lbuchs\WebAuthn\Binary\ByteBuffer;
use stdClass;

class RegistrationData
{
    public $registration;
    public $challenge;

    public function __construct(stdClass $registration, ByteBuffer $challenge)
    {
        $this->registration = $registration;
        $this->challenge = $challenge;
    }
}
