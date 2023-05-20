<?php
declare(strict_types=1);

namespace App\Authenticator;

use App\Model\CreateData;
use App\Model\LoginChallenge;
use App\Model\RegistrationData;
use Authentication\Authenticator\AbstractAuthenticator;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\WebAuthnException;
use Psr\Http\Message\ServerRequestInterface;

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
        'deviceTypes' => self::TYPE_ALL,
        // Timeout for challenge response
        'promptTimeout' => 20,
        // Require devices with resident key support
        'requireResidentKey' => false,
        // Is user verification (pin/access code) required.
        'requireUserVerification' => false,
    ];

    public const TYPE_USB = 'usb';
    public const TYPE_NFC = 'nfc';
    public const TYPE_BLE = 'ble';
    public const TYPE_HYBRID = 'hybrid';
    public const TYPE_INT = 'int';
    public const TYPE_ALL = [self::TYPE_USB, self::TYPE_NFC, self::TYPE_BLE, self::TYPE_HYBRID, self::TYPE_INT];
    public const TYPE_CROSSPLATFORM = [self::TYPE_USB, self::TYPE_NFC, self::TYPE_BLE, self::TYPE_HYBRID];

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
        $crossPlatform = !in_array('int', $types) && array_intersect(self::TYPE_CROSSPLATFORM, $types) !== [];

        $client = $this->getClient();
        $challengeData = $client->getCreateArgs(
            $userId,
            $username,
            $displayName,
            $this->getConfig('promptTimeout'),
            $this->getConfig('requireResidentKey'),
            $this->getConfig('requireUserVerification'),
            $crossPlatform,
        );

        return new RegistrationData($challengeData, $client->getChallenge());
    }

    public function validateRegistration(string $clientData, string $attestation, ByteBuffer $challenge): CreateData
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

    public function authenticate(ServerRequestInterface $request): ResultInterface
    {
        assert($request instanceof ServerRequest);
        $fields = $this->getConfig('fields');

        // Check for user
        $identifier = $this->getIdentifier();
        $username = $request->getData($fields['username']);
        $user = $identifier->identify(['username' => $username]);
        if (empty($user)) {
            Log::debug("User with username=$username not found", 'webauthn');

            return new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND);
        }
        if (empty($user->passkeys)) {
            Log::debug("User found, but no passkeys", 'webauthn');

            return new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND);
        }
        $client = $this->getClient();

        // Check for passkey data
        $hasData = true;
        $data = $request->getData();
        $requiredKeys = ['clientData', 'authenticator', 'signature', 'userHandle', 'id'];
        foreach ($requiredKeys as $key) {
            if (empty($data[$key])) {
                $hasData = false;
                break;
            }
        }
        $challenge = $request->getSession()->read('Webauthn.challenge');
        if (!$hasData || !$challenge) {
            Log::debug('Missing required request data, or Webauthn.challenge data in session.', 'webauthn');
            $ids = collection($user->passkeys)->extract('credential_id')->toList();
            $deviceTypes = $this->getconfig('deviceTypes');
            // Prompt for challenge
            $loginData = $client->getGetArgs(
                $ids,
                $this->getConfig('promptTimeout'),
                in_array(self::TYPE_USB, $deviceTypes),
                in_array(self::TYPE_NFC, $deviceTypes),
                in_array(self::TYPE_BLE, $deviceTypes),
                in_array(self::TYPE_HYBRID, $deviceTypes),
                in_array(self::TYPE_INT, $deviceTypes),
                $this->getConfig('requireUserVerification'),
            );
            $loginChallenge = new LoginChallenge($loginData, $client->getChallenge());

            return new Result($loginChallenge, Result::FAILURE_CREDENTIALS_MISSING);
        }

        // Verify passkey data
        $id = base64_decode($request->getData('id'));
        $clientData = base64_decode($request->getData('clientData'));
        $authenticator = base64_decode($request->getData('authenticator'));
        $signature = base64_decode($request->getData('signature'));
        $userHandle = base64_decode($request->getData('userHandle'));

        $passkeys = $user->passkeys;
        $found = null;
        foreach ($passkeys as $passkey) {
            if ($passkey->credential_id == $id) {
                $found = $passkey;
                break;
            }
        }
        if (!$found) {
            Log::debug("Login failed. No passkey with id=$id found.", 'webauthn');

            return new Result(null, Result::FAILURE_CREDENTIALS_INVALID);
        }
        if ($this->getConfig('requireResidentKey') && $userHandle !== $passkey->getUserHandle()) {
            Log::debug('Login failed. Resident key did not match', 'webauthn');

            return new Result(null, Result::FAILURE_CREDENTIALS_INVALID);
        }
        try {
            $client->processGet(
                $clientData,
                $authenticator,
                $signature,
                $found->getPublicKey(),
                $challenge,
                null,
                $this->getConfig('requireUserVerification'),
            );

            return new Result($user, Result::SUCCESS);
        } catch (WebAuthnException $error) {
            Log::debug('Login failed. error=' . $error->getMessage(), 'webauthn');

            return new Result(null, Result::FAILURE_CREDENTIALS_INVALID);
        }
    }
}
