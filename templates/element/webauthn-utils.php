<?php
/**
 * A collection of javascript utilities used in login & registration.
 */
?>
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
 * @param {ArrayBuffer|null} buffer
 * @returns {String}
 */
function arrayBufferToBase64(buffer) {
    if (buffer === null || buffer === undefined) {
        return null;
    } 
    let binary = '';
    let bytes = new Uint8Array(buffer);
    let len = bytes.byteLength;
    for (let i = 0; i < len; i++) {
        binary += String.fromCharCode( bytes[ i ] );
    }
    return window.btoa(binary);
}

async function sendRequest(options) {
    const {url, method, csrfToken, data} = options;
    var response = await window.fetch(url, {
        method: method,
        body: JSON.stringify(data),
        cache: 'no-cache',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-Token': csrfToken,
        },
    });
    return response;
}
</script>
