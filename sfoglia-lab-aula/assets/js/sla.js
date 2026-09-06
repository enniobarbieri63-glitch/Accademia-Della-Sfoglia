/**
 * sla.js — le chiamate AJAX dei form del pannello docente e della pagina
 * di ingresso. Nessuna libreria: fetch() e FormData bastano per questo
 * volume di interazioni.
 */
(function () {
	'use strict';

	function mostraEsito(form, testo, ok) {
		var span = form.querySelector('.sla-esito');
		if (!span) return;
		span.textContent = testo;
		span.style.color = ok ? '#3f7a3f' : '#a93b27';
	}

	function chiamata(azione, dati, nonce) {
		var corpo = new FormData();
		corpo.append('action', 'sla_' + azione);
		corpo.append('nonce', nonce);
		Object.keys(dati).forEach(function (chiave) {
			corpo.append(chiave, dati[chiave]);
		});
		return fetch(slaDati.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: corpo })
			.then(function (r) { return r.json(); });
	}

	function datiForm(form) {
		var risultato = {};
		new FormData(form).forEach(function (valore, chiave) { risultato[chiave] = valore; });
		return risultato;
	}

	// ---- Pannello docente ---------------------------------------------------

	document.addEventListener('submit', function (evento) {
		var form = evento.target;
		var azione = form.getAttribute('data-sla-azione');
		if (!azione) return;
		evento.preventDefault();

		var dati = datiForm(form);

		switch (azione) {
			case 'crea-classe':
				chiamata('crea_classe', dati, slaDati.nonceDocente).then(function (r) {
					if (r.success) { location.reload(); }
					else { mostraEsito(form, r.data.message, false); }
				});
				break;

			case 'aggiungi-studenti':
				var classeAgg = form.closest('[data-classe-id]');
				dati.classe_id = classeAgg.getAttribute('data-classe-id');
				chiamata('aggiungi_studenti', dati, slaDati.nonceDocente).then(function (r) {
					if (r.success) { location.reload(); }
					else { mostraEsito(form, r.data.message, false); }
				});
				break;

			case 'assegna-esercizio':
				var classeAss = form.closest('[data-classe-id]');
				dati.classe_id = classeAss.getAttribute('data-classe-id');
				chiamata('assegna_esercizio', dati, slaDati.nonceDocente).then(function (r) {
					if (r.success) { location.reload(); }
					else { mostraEsito(form, r.data.message, false); }
				});
				break;

			case 'cerca-classe':
				chiamata('elenco_nickname', dati, slaDati.noncePubblico).then(function (r) {
					if (!r.success) { mostraEsito(form, r.data.message, false); return; }
					var formEntra = form.parentElement.querySelector('[data-sla-azione="entra"]');
					var select = formEntra.querySelector('select[name="studente_id"]');
					select.innerHTML = '';
					r.data.nickname.forEach(function (s) {
						var opzione = document.createElement('option');
						opzione.value = s.id;
						opzione.textContent = s.nickname;
						select.appendChild(opzione);
					});
					formEntra.querySelector('input[name="codice"]').value = dati.codice;
					form.hidden = true;
					formEntra.hidden = false;
				});
				break;

			case 'entra':
				chiamata('entra', dati, slaDati.noncePubblico).then(function (r) {
					if (r.success) { location.reload(); }
					else { mostraEsito(form, r.data.message, false); }
				});
				break;

			case 'consegna':
				var contenitore = form.closest('.sla-esercizio');
				dati.assegnazione_id = contenitore.getAttribute('data-assegnazione-id');
				chiamata('consegna', dati, slaDati.noncePubblico).then(function (r) {
					var box = contenitore.querySelector('.sla-esito-esercizio');
					if (!r.success) { mostraEsito(form, r.data.message, false); return; }
					box.hidden = false;
					box.className = 'sla-esito-esercizio sla-fascia-' + r.data.fascia;
					box.innerHTML = '<strong>Punteggio: ' + r.data.punteggio + '/100</strong><p>' + r.data.messaggio + '</p>';
					form.remove();
				});
				break;
		}
	});

	// ---- Azioni singole (pulsanti, non form) --------------------------------

	document.addEventListener('click', function (evento) {
		var bottone = evento.target.closest('[data-sla-azione]');
		if (!bottone || 'BUTTON' !== bottone.tagName || bottone.closest('form')) return;

		var azione = bottone.getAttribute('data-sla-azione');

		if ('rigenera-codice' === azione) {
			var classeRig = bottone.closest('[data-classe-id]');
			chiamata('rigenera_codice', { classe_id: classeRig.getAttribute('data-classe-id') }, slaDati.nonceDocente)
				.then(function (r) {
					if (r.success) {
						classeRig.querySelector('.sla-codice-valore').textContent = r.data.codice;
					}
				});
		}

		if ('libera-posto' === azione) {
			chiamata('libera_posto', { studente_id: bottone.getAttribute('data-studente-id') }, slaDati.nonceDocente)
				.then(function (r) { if (r.success) { location.reload(); } });
		}
	});
})();
