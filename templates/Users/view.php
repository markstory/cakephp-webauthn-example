<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit User'), ['action' => 'edit', $user->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete User'), ['action' => 'delete', $user->id], ['confirm' => __('Are you sure you want to delete # {0}?', $user->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Users'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New User'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="users view content">
            <h3><?= h($user->uuid) ?></h3>
            <table>
                <tr>
                    <th><?= __('Uuid') ?></th>
                    <td><?= h($user->uuid) ?></td>
                </tr>
                <tr>
                    <th><?= __('Username') ?></th>
                    <td><?= h($user->username) ?></td>
                </tr>
                <tr>
                    <th><?= __('Display Name') ?></th>
                    <td><?= h($user->display_name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($user->id) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Passkeys') ?></h4>
                <?= $this->Form->postLink('Add Passkey', ['action' => 'addPasskey']) ?>
                <?php if (!empty($user->passkeys)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('User Id') ?></th>
                            <th><?= __('Credential Id') ?></th>
                            <th><?= __('Display Name') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($user->passkeys as $passkeys) : ?>
                        <tr>
                            <td><?= h($passkeys->id) ?></td>
                            <td><?= h($passkeys->user_id) ?></td>
                            <td><?= h($passkeys->credential_id) ?></td>
                            <td><?= h($passkeys->display_name) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Passkeys', 'action' => 'view', $passkeys->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Passkeys', 'action' => 'edit', $passkeys->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Passkeys', 'action' => 'delete', $passkeys->id], ['confirm' => __('Are you sure you want to delete # {0}?', $passkeys->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php if (isset($registerData)): ?>
<?= $this->element('webauthn-utils'); ?>
<script type="text/javascript">
async function completeAddPasskey(registerData, csrfToken) {
    recursiveBase64ToArrayBuffer(registerData);

    const cred = await navigator.credentials.create(registerData);
    const attestationResponse = {
        clientData: arrayBufferToBase64(cred.response.clientDataJSON),
        attestation: arrayBufferToBase64(cred.response.attestationObject),
    };
    const response = await sendRequest({
        url: '/users/passkeys/complete',
        method: 'POST',
        data: attestationResponse,
        csrfToken: csrfToken,
    });
    if (response.redirected) {
        window.location = '/users/view';
    }
}

completeAddPasskey(
    <?= json_encode($registerData->registration); ?>,
    '<?= $this->request->getAttribute('csrfToken') ?>',
);
</script>
<?php endif ?>
