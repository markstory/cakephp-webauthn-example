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
echo $this->Form->control('displayName');
echo $this->Form->submit('Register');
echo $this->Form->end();
?>
<?php if (isset($registerData)): ?>
<script type="text/javascript">
/**
 * Borrowed from https://github.com/lbuchs/WebAuthn/blob/b31384c90ceb18bf0fad2755eef77db049cc9593/_test/client.html#LL192C1-L205C10
 *
 * convert RFC 1342-like base64 strings to array buffer
 * @param {mixed} obj
 * @returns {undefined}
 */
function recursiveBase64ToArrayBuffer(obj) {
    let prefix = '=?BINARY?B?';
    let suffix = '?=';
    if (typeof obj === 'object') {
        for (let key in obj) {
            if (typeof obj[key] === 'string') {
                let str = obj[key];
                if (str.substring(0, prefix.length) === prefix && str.substring(str.length - suffix.length) === suffix) {
                    str = str.substring(prefix.length, str.length - suffix.length);

                    let binary_string = window.atob(str);
                    let len = binary_string.length;
                    let bytes = new Uint8Array(len);
                    for (let i = 0; i < len; i++)        {
                        bytes[i] = binary_string.charCodeAt(i);
                    }
                    obj[key] = bytes.buffer;
                }
            } else {
                recursiveBase64ToArrayBuffer(obj[key]);
            }
        }
    }
}
/**
 * Convert a ArrayBuffer to Base64
 * @param {ArrayBuffer} buffer
 * @returns {String}
 */
function arrayBufferToBase64(buffer) {
    let binary = '';
    let bytes = new Uint8Array(buffer);
    let len = bytes.byteLength;
    for (let i = 0; i < len; i++) {
        binary += String.fromCharCode( bytes[ i ] );
    }
    return window.btoa(binary);
}

async function completeRegistration() {
    var registerData = <?= json_encode($registerData->registration); ?>;

    recursiveBase64ToArrayBuffer(registerData);
    const cred = await navigator.credentials.create(registerData);
    const attestationResponse = {
        clientData: cred.response.clientDataJSON ? arrayBufferToBase64(cred.response.clientDataJSON) : null,
        attestation: cred.response.attestationObject ? arrayBufferToBase64(cred.response.attestationObject) : null,
    };

    var response = await window.fetch("/users/completeRegister", {
        method: 'POST',
        body: JSON.stringify(attestationResponse),
        cache: 'no-cache',
        headers: {'X-CSRF-Token': '<?= $this->request->getAttribute('csrfToken') ?>'},
    });

    if (response.success) {
        const messageEl = document.getElementById('register-flash');
        messageEl.textContent = "Successfully registered";
        messageEl.style.display = 'block';
    }
}

completeRegistration();
</script>
<?php endif; ?>
