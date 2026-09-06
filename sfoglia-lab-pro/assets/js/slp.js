/**
 * slp.js — le chiamate AJAX del catalogo corsi (iscrizione pubblica) e del
 * pannello del gestore (registrazione dei pagamenti).
 */
(function () {
	'use strict';

	function mostraEsito(form, testo, ok) {
		var span = form.querySelector('.slp-esito');
		if (!span) return;
		span.textContent = testo;
		span.style.color = ok ? '#3f7a3f' : '#a93b27';
	}

	function chiamata(azione, dati, nonce) {
		var corpo = new FormData();
		corpo.append('action', 'slp_' + azione);
		corpo.append('nonce', nonce);
		Object.keys(dati).forEach(function (chiave) { corpo.append(chiave, dati[chiave]); });
		return fetch(slpDati.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: corpo })
			.then(function (r) { return r.json(); });
	}

	function datiForm(form) {
		var risultato = {};
		new FormData(form).forEach(function (valore, chiave) { risultato[chiave] = valore; });
		return risultato;
	}

	document.addEventListener('submit', function (evento) {
		var form = evento.target;
		var azione = form.getAttribute('data-sla-azione');
		if (!azione) return;
		evento.preventDefault();

		var dati = datiForm(form);

		if ('iscrivi' === azione) {
			dati.sessione_id = form.getAttribute('data-sessione-id');
			chiamata('iscrivi', dati, slpDati.noncePubblico).then(function (r) {
				if (!r.success) { mostraEsito(form, r.data.message, false); return; }
				form.outerHTML = '<p class="slp-avviso slp-successo">Iscrizione ricevuta. Riceverai le coordinate per il bonifico dell\'acconto via email.</p>';
			});
		}

		if ('crea-sessione' === azione) {
			chiamata('crea_sessione', dati, slpDati.nonceGestore).then(function (r) {
				if (r.success) { location.reload(); }
				else { mostraEsito(form, r.data.message, false); }
			});
		}

		if ('registra-pagamento' === azione) {
			dati.iscrizione_id = form.getAttribute('data-iscrizione-id');
			chiamata('registra_pagamento', dati, slpDati.nonceGestore).then(function (r) {
				if (r.success) { location.reload(); }
				else { alert(r.data.message); }
			});
		}
	});
})();
