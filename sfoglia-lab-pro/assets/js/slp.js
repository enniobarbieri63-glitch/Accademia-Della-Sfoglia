/**
 * slp.js — le chiamate AJAX del catalogo corsi (iscrizione pubblica) e del
 * pannello del gestore (programmazione sessioni, registrazione pagamenti).
 */
(function () {
	'use strict';

	function mostraEsito(form, testo, ok) {
		var span = form.querySelector('.slp-esito');
		if (span) {
			span.textContent = testo;
			span.style.color = ok ? '#3f7a3f' : '#a93b27';
			return;
		}
		// Alcuni moduli (es. "Registra bonifico") non hanno uno spazio
		// dedicato all'esito: senza questo, un errore lì sparirebbe senza
		// che nessuno lo veda.
		alert(testo);
	}

	/**
	 * Fa la chiamata e ne verifica la risposta con cura: se il server
	 * risponde con un errore HTTP, o con qualcosa che non è la forma attesa
	 * {success, data}, solleva un errore leggibile invece di lasciare che
	 * il resto del codice fallisca in silenzio (è già successo: un nonce
	 * scaduto fa rispondere a WordPress con un semplice "-1", che
	 * sembrerebbe un JSON valido ma non ha né .success né .data).
	 */
	function chiamata(azione, dati, nonce) {
		var corpo = new FormData();
		corpo.append('action', 'slp_' + azione);
		corpo.append('nonce', nonce);
		Object.keys(dati).forEach(function (chiave) {
			var valore = dati[chiave];
			if (Array.isArray(valore)) {
				valore.forEach(function (v) { corpo.append(chiave, v); });
			} else {
				corpo.append(chiave, valore);
			}
		});

		return fetch(slpDati.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: corpo })
			.then(function (risposta) {
				if (!risposta.ok) {
					throw new Error('Il server ha risposto con un errore (' + risposta.status + ').');
				}
				return risposta.text();
			})
			.then(function (testo) {
				var r;
				try {
					r = JSON.parse(testo);
				} catch (e) {
					throw new Error('Risposta del server non comprensibile. Prova a ricaricare la pagina e a rientrare (la sessione potrebbe essere scaduta).');
				}
				if (!r || typeof r !== 'object' || typeof r.success === 'undefined') {
					throw new Error('Risposta del server incompleta. Prova a ricaricare la pagina e a rientrare.');
				}
				return r;
			});
	}

	function datiForm(form) {
		var risultato = {};
		new FormData(form).forEach(function (valore, chiave) {
			if (Object.prototype.hasOwnProperty.call(risultato, chiave)) {
				if (Array.isArray(risultato[chiave])) {
					risultato[chiave].push(valore);
				} else {
					risultato[chiave] = [risultato[chiave], valore];
				}
			} else {
				risultato[chiave] = valore;
			}
		});
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
			chiamata('iscrivi', dati, slpDati.noncePubblico)
				.then(function (r) {
					if (!r.success) { mostraEsito(form, r.data.message, false); return; }
					form.outerHTML = '<p class="slp-avviso slp-successo">Iscrizione ricevuta. Riceverai le coordinate per il bonifico dell\'acconto via email.</p>';
				})
				.catch(function (errore) { mostraEsito(form, errore.message, false); });
		}

		if ('crea-sessione' === azione) {
			chiamata('crea_sessione', dati, slpDati.nonceGestore)
				.then(function (r) {
					if (r.success) { location.reload(); }
					else { mostraEsito(form, r.data.message, false); }
				})
				.catch(function (errore) { mostraEsito(form, errore.message, false); });
		}

		if ('registra-pagamento' === azione) {
			dati.iscrizione_id = form.getAttribute('data-iscrizione-id');
			chiamata('registra_pagamento', dati, slpDati.nonceGestore)
				.then(function (r) {
					if (r.success) { location.reload(); }
					else { mostraEsito(form, r.data.message, false); }
				})
				.catch(function (errore) { mostraEsito(form, errore.message, false); });
		}
	});

	// ---- Diagnostica: elimina una sessione di prova ------------------------

	document.addEventListener('click', function (evento) {
		var bottone = evento.target.closest('[data-sla-azione="elimina-sessione-diagnostica"]');
		if (!bottone) return;

		if (!window.confirm('Eliminare questa sessione? L\'azione non si può annullare (funziona solo se non ha iscritti).')) {
			return;
		}

		chiamata('elimina_sessione_diagnostica', { sessione_id: bottone.getAttribute('data-sessione-id') }, slpDati.nonceGestore)
			.then(function (r) {
				if (r.success) { location.reload(); }
				else { alert(r.data.message); }
			})
			.catch(function (errore) { alert(errore.message); });
	});
})();
