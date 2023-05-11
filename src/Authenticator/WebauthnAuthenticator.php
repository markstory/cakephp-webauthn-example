<?php
declare(strict_types=1);

namespace App\Authenticator;

use App\Model\CreateData;
use App\Model\RegistrationData;
use Authentication\Authenticator\AbstractAuthenticator;
use Authentication\Authenticator\ResultInterface;
use Cake\Http\ServerRequest;
use lbuchs\WebAuthn\WebAuthn;

class WebauthnAuthenticator extends AbstractAuthenticator
{
    private $client = null;

    protected $_defaultConfig = [
        'appName' => 'CakePHP Webauthn',
        // The 'relaying party'. Should be the application domain name or root domain.
        'rpId' => 'localhost',
        'formats' => ['apple', 'android-key', 'android-safetynet', 'apple', 'fido-u2f', 'tpm'],
        // The certificate roots you want to trust. Default is to accept most providers.
        'certificates' => ['apple', 'yubico', 'hypersecu', 'google', 'microsoft', 'mds'],
        // Property on the user that has the registered passkeys
        'passkeysProperty' => 'passkeys',
        // Device types you accept. Default is all current types.
        'deviceTypes' => ['usb', 'nfc', 'ble', 'hybrid'],
        // Timeout for challenge response
        'promptTimeout' => 20,
        // Is user verification (pin/access code) required.
        'requireUserVerification' => false,
    ];

    protected function getClient(): WebAuthn
    {
        if (!$this->client) {
            $this->client = new WebAuthn($this->getConfig('appName'), $this->getConfig('rpId'), $this->getConfig('formats'));
            foreach ($this->getConfig('certificates') as $certificateName) {
                $this->addRootCertificate($certificateName);
            }
        }
        return $this->client;
    }

    protected function addRootCertificate(string $name): void
    {
        // TODO add file path support?
        $certificatePath = ROOT . "vendor/lbuchs/WebAuthn/_test/rootCertificates/{$name}.pem";
        $this->client->addRootCertificates($certificatePath);
    }

    public function getRegistrationData(string $userId, string $username, string $displayName): RegistrationData
    {
        // Determine if we should support cross platform keys.
        // If the 'int' type is supported, we are crossplatform.
        $types = $this->getConfig('deviceTypes');
        $supportsCrossPlatform = ['usb', 'nfc', 'ble', 'hybrid'];
        $crossPlatform = !in_array('int', $types) && array_intersect($supportsCrossPlatform, $types) !== [];

        $client = $this->getClient();
        $challengeData = $client->getCreateArgs(
            \hex2bin($userId),
            $username,
            $displayName,
            $this->getConfig('promptTimeout'),
            $this->getConfig('requireResidentKey'),
            $this->getConfig('requireUserVerification'),
            $crossPlatform,
        );

        return new RegistrationData($challengeData, $client->getChallenge());
    }

    public function validateRegistration(string $clientData, string $attestation, string $challenge): CreateData
    {
        $client = $this->getClient();
        $createData = $client->processCreate(
            $clientData,
            $attestation,
            $challenge,
            $this->getConfig('requireUserVerification'),
            true,
            false
        );

        return new CreateData($createData);
    }

    public function authenticate(ServerRequest $request): ResultInterface
    {
        // Check for user name.

        // If the request doesn't have the required login information throw
        // an exception with the necessary challenge data.

        // Validate attestation
    }
}
