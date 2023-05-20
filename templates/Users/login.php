<?php
declare(strict_types=1);
/**
 * @var \App\Model\LoginChallenge $loginData
 * @var \App\Model\Entity\User|null $user
 */
?>
<div id="login-flash" class="message" style="display:none;">
</div>
<h1>Login</h1>
<?php if (isset($user)): ?>
    <h2>Logged in!</h2>
    <?php debug($user) ?>
<?php endif; ?>
<?php
echo $this->Form->create();
echo $this->Form->control('username');
echo $this->Form->submit('Login');
echo $this->Form->end();

if (isset($loginData)): ?>
<?= $this->element('webauthn-utils'); ?>
<script type="text/javascript">
async function completeLogin(loginData) {
    recursiveBase64ToArrayBuffer(loginData);
    console.log(loginData);
    console.log('start cred');
    let cred
    navigator.credentials.get(loginData).then(function (result) {
        cred = result;
    }).catch(function (err) {
        console.error('Credential read failed', err);
    })
    if (!cred) {
        alert('No credential key found');
        return;
    }
    console.log('creds', cred);

    const attestationResponse = {
        id: arrayBufferToBase64(cred.rawId),
        clientData: arrayBufferToBase64(cred.response.clientDataJSON),
        authenticator: arrayBufferToBase64(cred.response.authenticatorData),
        signature: arrayBufferToBase64(cred.response.signature),
        userHandle: arrayBufferToBase64(cred.response.userHandle),
    };
console.log(attestationResponse);
    var response = await window.fetch("/users/login", {
        method: 'POST',
        body: JSON.stringify(attestationResponse),
        cache: 'no-cache',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-Token': csrfToken,
        }
    });
    var responseData = await response.json();
console.log(responseData);
    if (responseData.success) {
        const messageel = document.getElementById('login-flash');
        messageEl.innerText = "Login complete",
        messageEl.style.display = 'block';
    }
}

completeLogin(<?= json_encode($loginData->loginData); ?>);
</script>
<?php endif ?>
