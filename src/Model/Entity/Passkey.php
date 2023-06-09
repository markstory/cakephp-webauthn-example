<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Passkey Entity
 *
 * @property int $id
 * @property int $user_id
 * @property string $credential_id
 * @property string $payload
 *
 * @property \App\Model\Entity\User $user
 */
class Passkey extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected $_accessible = [
        'display_name' => true,
    ];

    private $decoded;

    protected function getPayload()
    {
        if ($this->decoded == null) {
            $this->decoded = json_decode($this->payload);
        }

        return $this->decoded;
    }

    public function getUserHandle(): ?string
    {
        return $this->getPayload()->userId;
    }

    public function getPublicKey(): string
    {
        return $this->getPayload()->credentialPublicKey;
    }

    public function getCertificateIssuer(): string
    {
        return $this->getPayload()->certificateIssuer;
    }
}
