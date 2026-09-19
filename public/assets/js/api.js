(function () {
    'use strict';

    async function fetchJson(url, options = {}) {
        const res = await fetch(url, options);
        const json = await res.json().catch(() => null);
        return { ok: res.ok, status: res.status, json };
    }

    async function postJson(url, body) {
        return fetchJson(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
            credentials: 'same-origin'
        });
    }

    window.tccApi = {
        fetchJson,
        postJson,
    };
})();
