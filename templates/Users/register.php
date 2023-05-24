<?php
/**
 * @var \App\Model\RegistrationData|null $registerData;
 * @property \Cake\View\Helper\FormHelper $Form
 */
?>
<div id="register-flash" class="message" style="display:none;">
</div>
<h1>Register a new account</h1>
<?php
echo $this->Form->create();
echo $this->Form->control('username');
echo $this->Form->control('display_name');
echo $this->Form->submit('Register');
echo $this->Form->end();
?>
<?php if (isset($registerData)): ?>
<?= $this->element('webauthn-utils'); ?>
<script type="text/javascript">
async function completeRegistration(registerData, csrfToken) {
    recursiveBase64ToArrayBuffer(registerData);

    const cred = await navigator.credentials.create(registerData);
    const attestationResponse = {
        clientData: arrayBufferToBase64(cred.response.clientDataJSON),
        attestation: arrayBufferToBase64(cred.response.attestationObject),
    };

    const response = await sendRequest({
        url: '/users/completeRegister',
        method: 'POST',
        data: attestationResponse,
        csrfToken: csrfToken,
    });
    const responseData = await response.json();
    if (responseData.success) {
        const messageEl = document.getElementById('register-flash');
        messageEl.innerText = "Successfully registered";
        messageEl.style.display = 'block';
    }
}

completeRegistration(
    <?= json_encode($registerData->registration); ?>,
    '<?= $this->request->getAttribute('csrfToken') ?>',
);
</script>
<?php endif; ?>
