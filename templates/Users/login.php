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
    <h2>You are Logged in!</h2>
    <?php debug($user) ?>
<?php endif; ?>
<?php if ($this->request->getQuery('redirect')): ?>
    <div class="message">You need to login</div>
<?php endif; ?>

<?php
echo $this->Form->create();
echo $this->Form->control('username');
echo $this->Form->submit('Login');
echo $this->Form->end();
?>

<p> Need an account? 
<?= $this->Html->link('Register', ['action' => 'startRegister']); ?>
</p>

<?php if (isset($loginData)): ?>
<?= $this->element('webauthn-utils'); ?>
<script type="text/javascript">
async function completeLogin(loginData, csrfToken) {
    recursiveBase64ToArrayBuffer(loginData);
    const cred = await navigator.credentials.get(loginData);

    const requestData = {
        id: arrayBufferToBase64(cred.rawId),
        clientData: arrayBufferToBase64(cred.response.clientDataJSON),
        authenticator: arrayBufferToBase64(cred.response.authenticatorData),
        signature: arrayBufferToBase64(cred.response.signature),
        userHandle: arrayBufferToBase64(cred.response.userHandle),
        username: document.querySelector('#username').value,
    };
    const response = await sendRequest({
        url: '/users/login',
        method: 'POST',
        data: requestData,
        csrfToken: csrfToken,
    });
    if (response.redirected) {
        window.location = '/users/view';
    } else {
        const messageEl = document.getElementById('login-flash');
        messageEl.innerText = "Login failed",
        messageEl.style.display = 'block';
    }
}

completeLogin(<?= json_encode($loginData->loginData); ?>, '<?= $this->request->getAttribute('csrfToken') ?>');
</script>
<?php endif ?>


