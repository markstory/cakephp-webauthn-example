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
use Cake\Validation\Validation;
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
        'deviceTypes' => self::TYPE_CROSSPLATFORM,
        // Timeout for challenge response
        'promptTimeout' => 20,
        // Require devices with resident key support
        'requireResidentKey' => false,
        // Is user verification (pin/access code) required.
        // One of required|preferred|discouraged.
        // Default value is discouraged to be as simple as possible
        // for end users.
        'requireUserVerification' => 'discouraged',
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
            $this->client = new WebAuthn(
                $this->getConfig('appName'),
                $this->getConfig('rpId'),
                $this->getConfig('formats')
            );
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

    public function getRegistrationData(
        string $userId,
        string $username,
        string $displayName
    ): RegistrationData {
        // Determine if we should support cross platform keys.
        // If the 'int' type is supported, we are crossplatform.
        $types = $this->getConfig('deviceTypes');
        $crossPlatform = !in_array('int', $types) && array_intersect(self::TYPE_CROSSPLATFORM, $types) !== [];

        // TODO Make a similar function for existing users
        // so that 'add a key' works.
        $client = $this->getClient();
        $challengeData = $client->getCreateArgs(
            $userId,
            $username,
            $displayName,
            $this->getConfig('promptTimeout'),
            $this->getConfig('requireResidentKey'),
            $this->getConfig('requireUserVerification') === 'required',
            $crossPlatform,
        );

        return new RegistrationData($challengeData, $client->getChallenge());
    }

    public function validateRegistration(
        ServerRequest $request,
        ByteBuffer $challenge
    ): CreateData {
        $clientData = base64_decode($request->getData('clientData'));
        $attestation = base64_decode($request->getData('attestation'));

        $client = $this->getClient();
        $createData = $client->processCreate(
            $clientData,
            $attestation,
            $challenge,
            $this->getConfig('requireUserVerification') === 'required',
            true,
            false, // TODO make this true.
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
            Log::debug('User found, but no passkeys', 'webauthn');

            return new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND);
        }
        $client = $this->getClient();

        // Check for passkey data
        $hasData = true;
        $data = $request->getData();
        $requiredKeys = ['clientData', 'authenticator', 'signature', 'id'];
        foreach ($requiredKeys as $key) {
            if (empty($data[$key])) {
                $hasData = false;
                break;
            }
        }
        // If we're missing a challenge value in the session or request data,
        // respond with another challenge.
        $challenge = $request->getSession()->read('Webauthn.challenge');
        if (!$hasData || !$challenge) {
            Log::debug('Missing required request data, or Webauthn.challenge data in session.', 'webauthn');

            $ids = collection($user->passkeys)->extract('credential_id')->toList();
            $ids = array_map('base64_decode', $ids);

            $deviceTypes = $this->getconfig('deviceTypes');
            // Get challenge data
            $loginData = $client->getGetArgs(
                $ids,
                $this->getConfig('promptTimeout'),
                in_array(self::TYPE_USB, $deviceTypes),
                in_array(self::TYPE_NFC, $deviceTypes),
                in_array(self::TYPE_BLE, $deviceTypes),
                in_array(self::TYPE_INT, $deviceTypes),
                $this->getConfig('requireUserVerification'),
            );
            $loginChallenge = new LoginChallenge($loginData, $client->getChallenge());

            return new Result($loginChallenge, Result::FAILURE_CREDENTIALS_MISSING);
        }

        // Verify passkey data
        $decoded = [];
        $keys = $requiredKeys + ['id'];
        foreach ($keys as $key) {
            if (!Validation::ascii($data[$key])) {
                Log::debug("Login failed. Value at $key was not a string.", 'webauthn');

                return new Result(null, Result::FAILURE_CREDENTIALS_INVALID);
            }
            // All keys other than id are base64 encoded.
            if ($key != 'id') {
                $decoded[$key] = base64_decode($data[$key]);
            } else {
                $decoded[$key] = $data[$key];
            }
        }

        $passkeys = $user->passkeys;
        $found = null;
        foreach ($passkeys as $passkey) {
            if ($passkey->credential_id == $decoded['id']) {
                $found = $passkey;
                break;
            }
        }
        if (!$found) {
            Log::debug("Login failed. No passkey with id={$decoded['id']} found.", 'webauthn');

            return new Result(null, Result::FAILURE_CREDENTIALS_INVALID);
        }

        // If we require resident keys ensure the userHandle signature matches.
        if ($this->getConfig('requireResidentKey') && $decoded['userHandle'] !== $passkey->getUserHandle()) {
            Log::debug('Login failed. Resident key did not match', 'webauthn');

            return new Result(null, Result::FAILURE_CREDENTIALS_INVALID);
        }

        try {
            $client->processGet(
                $decoded['clientData'],
                $decoded['authenticator'],
                $decoded['signature'],
                $found->getPublicKey(),
                $challenge,
                null,
                $this->getConfig('requireUserVerfication') == 'required',
            );

            return new Result($user, Result::SUCCESS);
        } catch (WebAuthnException $error) {
            Log::debug('Login failed. error=' . $error->getMessage(), 'webauthn');

            return new Result(null, Result::FAILURE_CREDENTIALS_INVALID);
        }
    }
}
