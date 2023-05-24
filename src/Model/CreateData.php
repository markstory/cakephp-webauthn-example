<?php
declare(strict_types=1);

namespace App\Model;

use stdClass;

class CreateData
{
    private $payload;

    public function __construct(stdClass $payload)
    {
        $this->payload = $payload;
    }

    public function getCredentialId()
    {
        return base64_encode($this->payload->credentialId);
    }

    public function getPayload()
    {
        $data = (array)$this->payload;
        $data['credentialId'] = base64_encode($data['credentialId']);

        return $data;
    }

    // TODO add more methods to increse ability to validate passkey data.
    //
    // Available options are:
    //
    // - credentialPublicKey
    // - certificate
    // - rpId
    // - attestationFormat
    // - certificateIssue
    // - certificateSubject
    // - rootValid
    // - userPresent
    // - userVerified
}
