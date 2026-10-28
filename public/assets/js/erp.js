/*!
 * ERP DF - helper global: window.erp
 * Butuh <meta name="csrf-header">, <meta name="csrf-token">, <meta name="base-url"> dari layout.
 */
(function () {
  'use strict';

  const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content ?? '';
  const url = (path) => (/^https?:\/\//.test(path) ? path : meta('base-url') + path.replace(/^\/+/, ''));

  const erp = {
    /** POST JSON ke path aplikasi (relatif terhadap base URL). Melempar Error bila gagal. */
    async post(path, body = {}) {
      const res = await fetch(url(path), {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          [meta('csrf-header')]: meta('csrf-token'),
        },
        body: JSON.stringify(body),
      });

      let data = null;
      try { data = await res.json(); } catch (e) { /* bukan JSON */ }

      if (!res.ok || (data && data.ok === false)) {
        throw new Error(data?.message || `Permintaan gagal (HTTP ${res.status})`);
      }
      return data ?? {};
    },

    /**
     * Ubah status schedule (ajukan / ACC / tolak / revisi / mulai / pause / selesai).
     * Mengembalikan true bila berhasil. Alasan penolakan diminta lewat prompt.
     */
    async transition(id, to, moduleCode = document.body.dataset.module) {
      let note = null;
      const ask = { rejected: 'Alasan penolakan:', revision: 'Catatan revisi:', paused: 'Alasan jeda:' };
      if (ask[to]) {
        note = (window.prompt(ask[to]) || '').trim();
        if (!note) return false;
      }
      try {
        await erp.post(`${moduleCode}/schedule/${id}/transition`, { to, note });
        return true;
      } catch (err) {
        window.alert(err.message);
        return false;
      }
    },
  };

  window.erp = erp;

  document.addEventListener('DOMContentLoaded', () => {
    if (window.bootstrap) {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => new bootstrap.Tooltip(el));
    }
  });
})();
