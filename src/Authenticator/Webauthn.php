<?php
declare(strict_types=1);

namespace App\Authenticator;

use Authentication\Authenticator\AbstractAuthenticator;
use Authentication\Authenticator\ResultInterface;
use Cake\Http\ServerRequest;

class WebauthnAuthenticator extends AbstractAuthenticator
{
    protected $_defaultConfig = [
        // Property on the user that has the registered passkeys
        'passkeysProperty' => 'passkeys',
    ];

    public function getRegistrationData(string $userId, string $username, string $displayName): array
    {
        // Create challenge data with the provided data.

    }

    public function validateRegistration(string $clientData, string $attestation): array
    {
        // Validate the registration data and attestation
        // Return an object with the pass key data to be stored.

    }

    public function authenticate(ServerRequest $request): ResultInterface
    {
        // Check for user name.

        // If the request doesn't have the required login information throw
        // an exception with the necessary challenge data.

        // Validate attestation
    }
}
