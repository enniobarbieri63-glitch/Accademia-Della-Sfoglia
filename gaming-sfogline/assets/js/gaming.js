/* Gaming Sfogline — front-end: AJAX dei moduli, countdown, push OneSignal */
( function ( $ ) {
	'use strict';

	// Dopo un'azione (elimina, salva, ripristina…) molte parti del pannello
	// ricaricano la pagina per mostrare l'elenco aggiornato — ma un
	// ricaricamento normale riparte sempre dall'inizio della pagina, "perdendo"
	// la sezione su cui si stava lavorando. Questa funzione salva la posizione
	// di scorrimento attuale prima di ricaricare, e viene ripristinata subito
	// dopo (vedi il blocco più sotto). Va usata al posto di
	// window.location.reload() in OGNI punto del file.
	// ECCEZIONE (richiesta 2026-08-04): nel Pannello Generale (shortcode
	// [gs_pannello], riquadro ".gs-pannello") anche i ricaricamenti dopo
	// un'azione devono tornare in cima, non restare sulla posizione di prima.
	function gsSiamoNelPannelloGenerale() {
		return document.querySelector( '.gs-pannello' ) !== null;
	}
	function gsReloadMantenendoPosizione() {
		if ( gsSiamoNelPannelloGenerale() ) {
			window.location.reload();
			return;
		}
		try {
			sessionStorage.setItem( 'gsScrollY', String( window.scrollY ) );
		} catch ( e ) { /* sessionStorage non disponibile: si ricarica comunque, si riparte solo dall'inizio */ }
		window.location.reload();
	}
	// Richiesta 2026-08-04, valida su TUTTE le pagine del sito: ricaricando
	// la pagina a mano (F5, Cmd+R, il pulsante del browser) si deve tornare
	// sempre in cima. Di norma è il BROWSER a decidere da solo di rimettersi
	// dove si era, ed è quello che dava fastidio: qui glielo si impedisce
	// ("scrollRestoration = manual").
	// Resta però valido il caso diverso qui sopra: quando è il plugin stesso
	// a ricaricare dopo un'azione (elimina, salva, ripristina…) la posizione
	// va mantenuta, altrimenti ogni operazione rimanderebbe in cima facendo
	// perdere il punto in cui si stava lavorando. I due casi si distinguono
	// perché solo il secondo lascia scritta una posizione da recuperare.
	if ( 'scrollRestoration' in window.history ) { window.history.scrollRestoration = 'manual'; }
	$( function () {
		var y;
		try { y = sessionStorage.getItem( 'gsScrollY' ); } catch ( e ) { y = null; }
		if ( null === y ) {
			// Nessuna posizione salvata: è un ricaricamento manuale → in cima.
			window.scrollTo( 0, 0 );
			return;
		}
		try { sessionStorage.removeItem( 'gsScrollY' ); } catch ( e ) {}
		// Aspetta che paginazione/immagini abbiano sistemato l'altezza della
		// pagina, altrimenti lo scroll salvato può non esistere ancora.
		setTimeout( function () { window.scrollTo( 0, parseInt( y, 10 ) || 0 ); }, 80 );
	} );
	// Richiesta di Ennio (2026-08-12): anche la paginazione NATIVA del tema
	// (i numeri di pagina "page-numbers" del blog in home e altrove — non è
	// del plugin, è un link vero che ricarica la pagina) deve restare ferma
	// nello stesso punto invece di ripartire dall'alto. Riusa lo stesso
	// meccanismo "gsScrollY" già attivo su tutte le pagine qui sopra: basta
	// salvare la posizione prima che il link faccia la sua normale
	// navigazione, il resto lo fa già il codice sopra.
	$( document ).on( 'click', 'a.page-numbers', function () {
		try { sessionStorage.setItem( 'gsScrollY', String( window.scrollY ) ); } catch ( e ) {}
	} );

	// Invia un messaggio con eventuale allegato foto/video (FormData).
	// fields: oggetto chiave→valore; $file: input file opzionale.
	function gsSendMsg( action, fields, $file, $msg, reload ) {
		var scelto = $file ? gsFileDaInviare( $file ) : null;
		var errore = gsMessaggioSeTroppoGrande( scelto );
		if ( errore ) {
			if ( $msg ) { $msg.removeClass( 'ok' ).addClass( 'err' ).text( errore ); }
			return $.Deferred().reject().promise();
		}
		if ( $msg ) { $msg.removeClass( 'ok err' ).text( 'Invio…' ); }
		var fd = new FormData();
		fd.append( 'action', action );
		fd.append( 'nonce', GS_AJAX.nonce );
		$.each( fields || {}, function ( k, v ) { fd.append( k, v ); } );
		if ( scelto ) {
			fd.append( 'media', scelto.file, scelto.nome );
		}
		return $.ajax( { url: GS_AJAX.url, method: 'POST', data: fd, processData: false, contentType: false } )
			.done( function ( res ) {
				if ( $msg ) { $msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); }
				if ( res && res.success && reload ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} )
			.fail( function () { if ( $msg ) { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } } );
	}

	// Legge i campi antispam (honeypot gs_hp + trappola tempo gs_ts) da un form
	// che li contiene (via gs_antispam_fields() lato PHP), da unire ai fields
	// passati a gsSendMsg per ogni invio di messaggio/chat.
	function gsAntispamFields( $form ) {
		return { gs_hp: $form.find( '[name=gs_hp]' ).val() || '', gs_ts: $form.find( '[name=gs_ts]' ).val() || '' };
	}

	// -------------------------------------------------------------------------
	// Invio moduli via AJAX (tutti i form con data-action)
	// -------------------------------------------------------------------------
	$( document ).on( 'submit', '.gs-form[data-action]', function ( e ) {
		e.preventDefault();

		var $form   = $( this );
		var action  = $form.data( 'action' );
		var $msg    = $form.find( '.gs-form-msg' ).first();
		var $submit = $form.find( 'button[type=submit]' );

		// Usa FormData per supportare gli upload di file.
		var formData = new FormData( this );
		formData.append( 'action', action );
		if ( ! formData.get( 'nonce' ) ) {
			formData.append( 'nonce', GS_AJAX.nonce );
		}

		// Media: applica le foto compresse e controlla il limite di peso.
		var limite = gsLimiteByte();
		var troppoGrande = false, nomeGrande = '';
		$form.find( 'input[type=file]' ).each( function () {
			if ( this._gsBlob ) {
				formData.set( this.name, this._gsBlob, this._gsName );
			}
			var f = this._gsBlob || ( this.files && this.files[0] );
			if ( f && limite > 0 && f.size > limite ) {
				troppoGrande = true;
				nomeGrande = ( this.files && this.files[0] ) ? this.files[0].name : '';
			}
		} );
		if ( troppoGrande ) {
			$msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Il file ' + nomeGrande + ' supera il limite di ' + GS_MEDIA.limite_mb + ' MB. Riduci le dimensioni e riprova.' );
			return;
		}

		$submit.prop( 'disabled', true );
		$msg.removeClass( 'ok err' ).text( 'Invio in corso…' );

		$.ajax( {
			url: GS_AJAX.url,
			method: 'POST',
			data: formData,
			processData: false,
			contentType: false
		} ).done( function ( res ) {
			if ( res && res.success ) {
				$msg.addClass( 'ok' ).text( res.data.message || 'Fatto!' );
				// Reset dei campi (tranne i nascosti).
				$form.find( 'input[type=text], input[type=email], input[type=password], textarea' ).val( '' );
				$form.find( 'input[type=checkbox]' ).prop( 'checked', false );
				// Ricarica dopo un attimo per aggiornare punti/gallerie.
				setTimeout( function () { gsReloadMantenendoPosizione(); }, 1200 );
			} else {
				var m = ( res && res.data && res.data.message ) ? res.data.message : 'Si è verificato un errore.';
				$msg.addClass( 'err' ).text( m );
			}
		} ).fail( function () {
			$msg.addClass( 'err' ).text( 'Errore di connessione, riprova.' );
		} ).always( function () {
			$submit.prop( 'disabled', false );
		} );
	} );

	// -------------------------------------------------------------------------
	// Countdown in tempo reale
	// -------------------------------------------------------------------------
	function updateCountdowns() {
		$( '.gs-countdown[data-deadline]' ).each( function () {
			var $el      = $( this );
			var deadline = parseInt( $el.data( 'deadline' ), 10 ) * 1000;
			var diff     = deadline - Date.now();

			if ( isNaN( deadline ) ) { return; }

			if ( diff <= 0 ) {
				$el.text( '⏳ Tempo scaduto' );
				return;
			}
			var d = Math.floor( diff / 86400000 );
			var h = Math.floor( ( diff % 86400000 ) / 3600000 );
			var m = Math.floor( ( diff % 3600000 ) / 60000 );
			var s = Math.floor( ( diff % 60000 ) / 1000 );

			var txt = '⏳ ';
			if ( d > 0 ) { txt += d + 'g '; }
			txt += ( '0' + h ).slice( -2 ) + ':' + ( '0' + m ).slice( -2 ) + ':' + ( '0' + s ).slice( -2 );
			$el.text( txt );
		} );
	}
	if ( $( '.gs-countdown[data-deadline]' ).length ) {
		updateCountdowns();
		setInterval( updateCountdowns, 1000 );
	}

	// -------------------------------------------------------------------------
	// Opt-in notifiche push (OneSignal, se presente sul sito)
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-push-optin', function ( e ) {
		e.preventDefault();
		if ( window.OneSignal ) {
			window.OneSignal.push( function () {
				window.OneSignal.showNativePrompt();
			} );
		} else {
			alert( 'Le notifiche non sono ancora configurate su questo sito.' );
		}
	} );

	// -------------------------------------------------------------------------
	// Pannello di controllo: approva / rifiuta iscrizioni via AJAX
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-approva, .gs-rifiuta', function ( e ) {
		e.preventDefault();

		var $btn      = $( this );
		var decisione = $btn.hasClass( 'gs-approva' ) ? 'approva' : 'rifiuta';
		var uid       = $btn.data( 'user' );
		var $row      = $btn.closest( 'tr' );

		if ( 'rifiuta' === decisione && ! window.confirm( 'Rifiutare questa richiesta?' ) ) {
			return;
		}

		$row.find( '.gs-btn' ).prop( 'disabled', true );

		$.post( GS_AJAX.url, {
			action: 'gs_fe_gestisci_iscrizione',
			nonce: GS_AJAX.nonce,
			user: uid,
			decisione: decisione
		} ).done( function ( res ) {
			var msg = ( res && res.data && res.data.message ) ? res.data.message : '';
			if ( res && res.success ) {
				$row.find( '.gs-richiesta-azioni' ).html(
					'<span class="gs-richiesta-esito ok">' + msg + '</span>'
				);
				if ( 'approva' === decisione ) { $row.removeClass( 'gs-richiesta-rossa' ).addClass( 'gs-richiesta-verde' ); }
				// Rimuove la riga dopo un attimo.
				setTimeout( function () {
					$row.fadeOut( 300, function () {
						$( this ).remove();
						if ( ! $( '#gs-pannello-richieste tbody tr' ).length ) {
							$( '#gs-pannello-richieste' ).html( '<p>Nessuna richiesta in attesa. 🎉</p>' );
						}
					} );
				}, 1000 );
			} else {
				$row.find( '.gs-richiesta-azioni' ).append(
					'<div class="gs-richiesta-esito err">' + ( msg || 'Errore.' ) + '</div>'
				);
				$row.find( '.gs-btn' ).prop( 'disabled', false );
			}
		} ).fail( function () {
			$row.find( '.gs-btn' ).prop( 'disabled', false );
			alert( 'Errore di connessione, riprova.' );
		} );
	} );

	// -------------------------------------------------------------------------
	// Pannello: invio manuale della mail "Accesso e Vetrina" a una sfoglina
	// scelta, e salvataggio del logo per la sua intestazione (19/08/2026).
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-invia-mail-arriservata-btn', function ( e ) {
		e.preventDefault();
		var $btn  = $( this );
		var $form = $btn.closest( '.gs-form-invia-mail-arriservata' );
		var $msg  = $form.find( '.gs-invia-mail-arriservata-msg' );
		var uid   = $form.find( 'select[name="user"]' ).val();
		// Il modello (quale mail inviare) mancava qui: senza, l'AJAX partiva
		// sempre senza "modello" e il server ripiegava sempre su "Accesso e
		// Vetrina" (default lato PHP), qualunque mail fosse scelta nel primo
		// menu — "Come funziona il Percorso" non poteva mai essere spedita
		// da questo pulsante. Completato il 22/08/2026 (Ennio, punto 3).
		var modello = $form.find( 'select[name="modello"]' ).val();
		if ( ! uid ) { $msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Scegli prima una sfoglina.' ); return; }
		$btn.prop( 'disabled', true );
		$msg.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, { action: 'gs_invia_mail_area_riservata_manuale', nonce: GS_AJAX.nonce, user: uid, modello: modello } )
			.done( function ( res ) {
				var ok = res && res.success;
				$msg.removeClass( 'ok err' ).addClass( ok ? 'ok' : 'err' ).text( ( res && res.data && res.data.message ) || ( ok ? 'Fatto.' : 'Errore.' ) );
			} )
			.fail( function () { $msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Errore di connessione.' ); } )
			.always( function () { $btn.prop( 'disabled', false ); } );
	} );

	// Pannello: "Invia mail di prova" — la mail scelta nel modello, sempre
	// allo stesso indirizzo fisso (punto 4b, Ennio 21/08/2026).
	$( document ).on( 'click', '.gs-invia-mail-prova-btn', function ( e ) {
		e.preventDefault();
		var $btn    = $( this );
		var $form   = $btn.closest( '.gs-form-invia-mail-arriservata' );
		var $msg    = $form.find( '.gs-invia-mail-arriservata-msg' );
		var modello = $form.find( 'select[name="modello"]' ).val();
		$btn.prop( 'disabled', true );
		$msg.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, { action: 'gs_invia_mail_prova', nonce: GS_AJAX.nonce, modello: modello } )
			.done( function ( res ) {
				var ok = res && res.success;
				$msg.removeClass( 'ok err' ).addClass( ok ? 'ok' : 'err' ).text( ( res && res.data && res.data.message ) || ( ok ? 'Fatto.' : 'Errore.' ) );
			} )
			.fail( function () { $msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Errore di connessione.' ); } )
			.always( function () { $btn.prop( 'disabled', false ); } );
	} );

	// Pannello: salva/ripristina il testo di ogni mail (punto 3, completato
	// il 22/08/2026 — il PHP era già pronto dal 20/08, mancava solo questo
	// JS: gs_ajax_salva_mail_template/gs_ajax_ripristina_mail_template in
	// mail-area-riservata.php).
	$( document ).on( 'click', '.gs-salva-mail-template-btn', function ( e ) {
		e.preventDefault();
		var $btn      = $( this );
		var modello   = $btn.data( 'modello' );
		var $dettagli = $btn.closest( 'details' );
		var $msg      = $dettagli.find( '.gs-mail-template-msg' );
		var corpo     = $dettagli.find( '.gs-mail-template-corpo[data-modello="' + modello + '"]' ).val();
		$btn.prop( 'disabled', true );
		$msg.removeClass( 'ok err' ).text( 'Salvo…' );
		$.post( GS_AJAX.url, { action: 'gs_salva_mail_template', nonce: GS_AJAX.nonce, modello: modello, corpo: corpo } )
			.done( function ( res ) {
				var ok = res && res.success;
				$msg.removeClass( 'ok err' ).addClass( ok ? 'ok' : 'err' ).text( ( res && res.data && res.data.message ) || ( ok ? 'Fatto.' : 'Errore.' ) );
			} )
			.fail( function () { $msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Errore di connessione.' ); } )
			.always( function () { $btn.prop( 'disabled', false ); } );
	} );
	$( document ).on( 'click', '.gs-ripristina-mail-template-btn', function ( e ) {
		e.preventDefault();
		var $btn      = $( this );
		var modello   = $btn.data( 'modello' );
		var $dettagli = $btn.closest( 'details' );
		var $msg      = $dettagli.find( '.gs-mail-template-msg' );
		var $textarea = $dettagli.find( '.gs-mail-template-corpo[data-modello="' + modello + '"]' );
		$btn.prop( 'disabled', true );
		$msg.removeClass( 'ok err' ).text( 'Ripristino…' );
		$.post( GS_AJAX.url, { action: 'gs_ripristina_mail_template', nonce: GS_AJAX.nonce, modello: modello } )
			.done( function ( res ) {
				var ok = res && res.success;
				if ( ok && res.data && typeof res.data.corpo === 'string' ) { $textarea.val( res.data.corpo ); }
				$msg.removeClass( 'ok err' ).addClass( ok ? 'ok' : 'err' ).text( ( res && res.data && res.data.message ) || ( ok ? 'Fatto.' : 'Errore.' ) );
			} )
			.fail( function () { $msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Errore di connessione.' ); } )
			.always( function () { $btn.prop( 'disabled', false ); } );
	} );
	$( document ).on( 'click', '.gs-salva-logo-arriservata-btn', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $wrap = $btn.closest( '.gs-box' );
		var $msg = $wrap.find( '.gs-salva-logo-arriservata-msg' );
		var url  = $wrap.find( '.gs-input-logo-arriservata' ).val();
		$btn.prop( 'disabled', true );
		$msg.removeClass( 'ok err' ).text( 'Salvo…' );
		$.post( GS_AJAX.url, { action: 'gs_salva_logo_arriservata', nonce: GS_AJAX.nonce, logo_url: url } )
			.done( function ( res ) {
				var ok = res && res.success;
				$msg.removeClass( 'ok err' ).addClass( ok ? 'ok' : 'err' ).text( ( res && res.data && res.data.message ) || ( ok ? 'Fatto.' : 'Errore.' ) );
			} )
			.fail( function () { $msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Errore di connessione.' ); } )
			.always( function () { $btn.prop( 'disabled', false ); } );
	} );

	// -------------------------------------------------------------------------
	// Pannello: correzione punti
	// -------------------------------------------------------------------------
	$( document ).on( 'submit', '.gs-form-correzione', function ( e ) {
		e.preventDefault();
		var $form = $( this );
		var $msg  = $form.find( '.gs-form-msg' );
		$msg.removeClass( 'ok err' ).text( 'Invio…' );

		$.post( GS_AJAX.url, {
			action: 'gs_fe_correggi_punti',
			nonce:  GS_AJAX.nonce,
			utente: $form.find( '[name=utente]' ).val(),
			punti:  $form.find( '[name=punti]' ).val(),
			motivo: $form.find( '[name=motivo]' ).val()
		} ).done( function ( res ) {
			var m = ( res && res.data && res.data.message ) ? res.data.message : 'Errore.';
			if ( res && res.success ) {
				$msg.addClass( 'ok' ).text( m );
				$form.find( '[name=punti], [name=motivo]' ).val( '' );
			} else {
				$msg.addClass( 'err' ).text( m );
			}
		} ).fail( function () {
			$msg.addClass( 'err' ).text( 'Errore di connessione.' );
		} );
	} );

	// -------------------------------------------------------------------------
	// Sfogline in gara — controllo dei giudici (verde/rossa)
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-giudici-toggle', function ( e ) {
		e.preventDefault();
		var $btn  = $( this );
		var $item = $btn.closest( '.gs-inbox-item' );
		var $m    = $item.find( '.gs-giudici-toggle-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_sfoglia_controllata_toggle', nonce: GS_AJAX.nonce, sfoglia: $btn.data( 'sfoglia' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 600 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// -------------------------------------------------------------------------
	// Pannello: assegnazione Premio di Fine Anno
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-assegna-premio', function ( e ) {
		e.preventDefault();
		if ( ! window.confirm( 'Assegnare il premio e inviare le email alle vincitrici?' ) ) { return; }

		var $btn  = $( this );
		var $wrap = $btn.closest( '.gs-premio-azione' );
		var $msg  = $wrap.find( '.gs-premio-msg' );
		$btn.prop( 'disabled', true );
		$msg.removeClass( 'ok err' ).text( 'Assegnazione in corso…' );

		$.post( GS_AJAX.url, {
			action: 'gs_fe_assegna_premio',
			nonce:  GS_AJAX.nonce,
			anno:   $wrap.data( 'anno' )
		} ).done( function ( res ) {
			var m = ( res && res.data && res.data.message ) ? res.data.message : 'Errore.';
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( m );
		} ).fail( function () {
			$msg.addClass( 'err' ).text( 'Errore di connessione.' );
		} ).always( function () {
			$btn.prop( 'disabled', false );
		} );
	} );

	// -------------------------------------------------------------------------
	// Pannello: ammissione/esclusione concorrenti (sfide blindate)
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-ammetti, .gs-escludi, .gs-reset-amm', function ( e ) {
		e.preventDefault();
		var $btn   = $( this );
		var stato  = $btn.data( 'stato' );
		var $row   = $btn.closest( 'tr' );
		var $table = $btn.closest( '.gs-tabella-ammissioni' );
		var sfida  = $table.data( 'sfida' );
		var uid    = $row.data( 'user' );

		$row.find( '.gs-btn' ).prop( 'disabled', true );

		$.post( GS_AJAX.url, {
			action: 'gs_fe_ammissione',
			nonce:  GS_AJAX.nonce,
			sfida:  sfida,
			user:   uid,
			stato:  stato
		} ).done( function ( res ) {
			if ( res && res.success && res.data.stato_html ) {
				$row.find( '.gs-stato-cell' ).html( res.data.stato_html );
			} else {
				alert( ( res && res.data && res.data.message ) ? res.data.message : 'Errore.' );
			}
		} ).fail( function () {
			alert( 'Errore di connessione.' );
		} ).always( function () {
			$row.find( '.gs-btn' ).prop( 'disabled', false );
		} );
	} );

	// -------------------------------------------------------------------------
	// Pannello: esclusione automatica delle prime N posizioni della classifica
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-top-n-salva', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $wrap = $btn.closest( '.gs-todo-riquadro' );
		var $msg = $wrap.find( '.gs-top-n-msg' );
		var top_n = $wrap.find( '.gs-top-n-input' ).val();
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_fe_top_n_salva', nonce: GS_AJAX.nonce, sfida: $btn.data( 'sfida' ), top_n: top_n } )
			.done( function ( res ) { $msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); } )
			.fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// -------------------------------------------------------------------------
	// Pannello: attiva/disattiva Vetrina
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-toggle-vetrina', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $box = $btn.closest( '.gs-box' );
		var $msg = $box.find( '.gs-vetrina-msg' );
		$btn.prop( 'disabled', true );
		$msg.removeClass( 'ok err' ).text( '…' );

		$.post( GS_AJAX.url, {
			action: 'gs_fe_toggle_vetrina',
			nonce:  GS_AJAX.nonce
		} ).done( function ( res ) {
			if ( res && res.success ) {
				var attiva = res.data.attiva;
				$box.find( '.gs-vetrina-stato' )
					.removeClass( 'on off' ).addClass( attiva ? 'on' : 'off' )
					.text( attiva ? 'ATTIVA' : 'DISATTIVATA' );
				$btn.text( attiva ? 'Disattiva la Vetrina' : 'Attiva la Vetrina' ).data( 'attiva', attiva ? '1' : '0' );
				$msg.addClass( 'ok' ).text( res.data.message );
			} else {
				$msg.addClass( 'err' ).text( ( res && res.data && res.data.message ) ? res.data.message : 'Errore.' );
			}
		} ).fail( function () {
			$msg.addClass( 'err' ).text( 'Errore di connessione.' );
		} ).always( function () {
			$btn.prop( 'disabled', false );
		} );
	} );

	// -------------------------------------------------------------------------
	// Pannello: blocca/riattiva la vetrina di una singola sfoglina
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-toggle-vetrina-utente', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $row = $btn.closest( 'tr' );
		var uid  = $row.data( 'user' );
		$btn.prop( 'disabled', true );

		$.post( GS_AJAX.url, {
			action: 'gs_fe_toggle_vetrina_utente',
			nonce:  GS_AJAX.nonce,
			user:   uid
		} ).done( function ( res ) {
			if ( res && res.success ) {
				var bloccata = 'Riattiva' === res.data.btn_label;
				$row.find( '.gs-vetrina-stato-cell' ).html( res.data.stato_html );
				$btn.text( res.data.btn_label );
				$btn.toggleClass( 'gs-btn-verde', bloccata );
				$row.toggleClass( 'gs-vetrina-rossa', bloccata ).toggleClass( 'gs-vetrina-verde', ! bloccata );
			} else {
				alert( ( res && res.data && res.data.message ) ? res.data.message : 'Errore.' );
			}
		} ).fail( function () {
			alert( 'Errore di connessione.' );
		} ).always( function () {
			$btn.prop( 'disabled', false );
		} );
	} );

	// -------------------------------------------------------------------------
	// Pannello: nomina/revoca collaboratrice
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-toggle-collab', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $row = $btn.closest( 'tr' );
		var uid  = $row.data( 'user' );
		$btn.prop( 'disabled', true );

		$.post( GS_AJAX.url, {
			action: 'gs_fe_toggle_collab',
			nonce:  GS_AJAX.nonce,
			user:   uid
		} ).done( function ( res ) {
			if ( res && res.success ) {
				$row.find( '.gs-collab-stato-cell' ).html( res.data.stato_html );
				$btn.text( res.data.btn_label ).toggleClass( 'gs-btn-ghost', ! res.data.collab );
			} else {
				alert( ( res && res.data && res.data.message ) ? res.data.message : 'Errore.' );
			}
		} ).fail( function () {
			alert( 'Errore di connessione.' );
		} ).always( function () {
			$btn.prop( 'disabled', false );
		} );
	} );

	// Crea un nuovo account e lo rende subito collaboratore, senza passare
	// dalla coda di iscrizione pubblica.
	$( document ).on( 'click', '.gs-collab-crea', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-collab-nuovo' );
		var $m = $f.find( '.gs-collab-crea-msg' );
		$m.removeClass( 'ok err' ).text( 'Creazione…' );
		$.post( GS_AJAX.url, {
			action: 'gs_collab_crea', nonce: GS_AJAX.nonce,
			nome: $f.find( '[name=nome]' ).val(),
			email: $f.find( '[name=email]' ).val()
		} ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// Apre il modulo condiviso "Modifica dati collaboratore", precompilato
	// con i dati della riga cliccata (letti dagli attributi data- della riga,
	// non serve un'altra chiamata al server).
	$( document ).on( 'click', '.gs-collab-modifica-apri', function ( e ) {
		e.preventDefault();
		var $row = $( this ).closest( 'tr' );
		var $f   = $row.closest( 'table' ).nextAll( '.gs-form-collab-modifica' ).first();
		if ( ! $f.length ) { return; }
		$f.find( '.gs-collab-modifica-msg' ).removeClass( 'ok err' ).text( '' );
		$f.find( '[name=user]' ).val( $row.data( 'user' ) );
		$f.find( '[name=nome]' ).val( $row.data( 'nome' ) );
		$f.find( '[name=email]' ).val( $row.data( 'email' ) );
		$f.show();
		$f[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'center' } );
	} );

	$( document ).on( 'click', '.gs-collab-modifica-annulla', function ( e ) {
		e.preventDefault();
		$( this ).closest( '.gs-form-collab-modifica' ).hide();
	} );

	$( document ).on( 'click', '.gs-collab-modifica-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-collab-modifica' );
		var $m = $f.find( '.gs-collab-modifica-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_collab_modifica', nonce: GS_AJAX.nonce,
			user: $f.find( '[name=user]' ).val(),
			nome: $f.find( '[name=nome]' ).val(),
			email: $f.find( '[name=email]' ).val()
		} ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// -------------------------------------------------------------------------
	// Paginazione delle liste lunghe (tabelle e gallerie) + ricerca integrata
	// -------------------------------------------------------------------------
	function gsPagItems( $root ) {
		return $root.is( 'table' ) ? $root.find( 'tbody > tr' ) : $root.children().not( '.gs-pager' );
	}
	function gsRepaginate( $root ) {
		var per = parseInt( $root.data( 'gsPer' ), 10 ) || 10;
		var $items = gsPagItems( $root );
		var $vis = $items.not( '.gs-hide-search' ).not( '.gs-hide-livello' );
		var total = $vis.length;
		var pages = Math.max( 1, Math.ceil( total / per ) );
		var page = Math.min( Math.max( 1, parseInt( $root.data( 'gsPage' ), 10 ) || 1 ), pages );
		$root.data( 'gsPage', page );
		$items.addClass( 'gs-hide-page' );
		$vis.each( function ( i ) {
			if ( i >= ( page - 1 ) * per && i < page * per ) { $( this ).removeClass( 'gs-hide-page' ); }
		} );
		var $pager = $root.data( 'gsPager' );
		if ( $pager ) {
			if ( pages <= 1 ) { $pager.hide(); }
			else {
				$pager.show();
				$pager.find( '.gs-pg-info' ).text( 'Pagina ' + page + ' di ' + pages + ' · ' + total + ' voci' );
				// Togliere il focus prima di disabilitare: altrimenti il browser sposta il
				// focus all'inizio del documento e trascina con sé lo scroll verso l'alto.
				if ( document.activeElement && $pager.find( document.activeElement ).length ) { document.activeElement.blur(); }
				$pager.find( '.gs-pg-prev' ).prop( 'disabled', page <= 1 );
				$pager.find( '.gs-pg-next' ).prop( 'disabled', page >= pages );
			}
		}
	}
	function gsInitPagination( scope ) {
		var $scope = scope ? $( scope ) : $( document );
		$scope.find( '.gs-paginate' ).each( function () {
			if ( this._gsPagerInit ) { return; }
			this._gsPagerInit = true;
			var $root = $( this );
			$root.data( 'gsPer', parseInt( $root.data( 'per-page' ), 10 ) || 10 );
			$root.data( 'gsPage', 1 );
			var $pager = $( '<div class="gs-pager"><button type="button" class="gs-pg-prev">‹</button><span class="gs-pg-info"></span><button type="button" class="gs-pg-next">›</button> <button type="button" class="gs-pg-all">Vedi tutti</button></div>' );
			$root.after( $pager );
			$root.data( 'gsPager', $pager );
			$pager.on( 'click', '.gs-pg-prev', function () { var p = $root.data( 'gsPage' ); if ( p > 1 ) { $root.data( 'gsPage', p - 1 ); gsRepaginate( $root ); } } );
			$pager.on( 'click', '.gs-pg-next', function () { $root.data( 'gsPage', ( parseInt( $root.data( 'gsPage' ), 10 ) || 1 ) + 1 ); gsRepaginate( $root ); } );
			// "Vedi tutti": apre un popup con l'elenco completo, richiudibile.
			$pager.on( 'click', '.gs-pg-all', function () {
				var titolo = $root.closest( '.gs-box' ).find( '.gs-box-title' ).first().text() || 'Elenco completo';
				gsOpenPopup( titolo, $root );
			} );
			gsRepaginate( $root );
		} );
	}

	// Popup: mostra l'elenco completo (tutti gli elementi, senza paginazione).
	function gsOpenPopup( titolo, $root ) {
		var $clone = $root.clone( true, true );
		$clone.removeClass( 'gs-paginate' ).removeAttr( 'data-per-page' );
		$clone.find( '.gs-hide-page' ).removeClass( 'gs-hide-page' );
		if ( $clone.is( 'table' ) ) { $clone.find( 'tbody > tr' ).removeClass( 'gs-hide-page' ); }
		$clone.find( '.gs-pager' ).remove();
		var $ov = $( '<div class="gs-popup-overlay"><div class="gs-popup" role="dialog" aria-modal="true">'
			+ '<div class="gs-popup-head"><strong class="gs-popup-title"></strong>'
			+ '<button type="button" class="gs-popup-close" aria-label="Chiudi">✕ Chiudi</button></div>'
			+ '<div class="gs-popup-body"></div></div></div>' );
		$ov.find( '.gs-popup-title' ).text( titolo );
		$ov.find( '.gs-popup-body' ).append( $clone );
		$( 'body' ).append( $ov ).addClass( 'gs-popup-open' );
	}
	function gsClosePopup() { $( '.gs-popup-overlay' ).remove(); $( 'body' ).removeClass( 'gs-popup-open' ); }
	$( document ).on( 'click', '.gs-popup-close', function ( e ) { e.preventDefault(); gsClosePopup(); } );
	$( document ).on( 'click', '.gs-popup-overlay', function ( e ) { if ( e.target === this ) { gsClosePopup(); } } );
	$( document ).on( 'keydown', function ( e ) { if ( e.key === 'Escape' ) { gsClosePopup(); } } );
	// Espongo l'init per i contenuti caricati via AJAX.
	window.gsInitPagination = gsInitPagination;
	$( function () { gsInitPagination(); } );

	// Stesso popup richiudibile di gsOpenPopup(), ma per un HTML già pronto
	// arrivato via AJAX invece che clonato da un elemento già nella pagina
	// (usato da "Vedi tutte le sfogline registrate" e riusabile altrove).
	function gsApriPopupHtml( titolo, html ) {
		var $ov = $( '<div class="gs-popup-overlay"><div class="gs-popup" role="dialog" aria-modal="true">'
			+ '<div class="gs-popup-head"><strong class="gs-popup-title"></strong>'
			+ '<button type="button" class="gs-popup-close" aria-label="Chiudi">✕ Chiudi</button></div>'
			+ '<div class="gs-popup-body"></div></div></div>' );
		$ov.find( '.gs-popup-title' ).text( titolo );
		$ov.find( '.gs-popup-body' ).html( html );
		$( 'body' ).append( $ov ).addClass( 'gs-popup-open' );
		if ( window.gsInitPagination ) { window.gsInitPagination( $ov ); }
	}
	window.gsApriPopupHtml = gsApriPopupHtml;

	// --- Vedi tutte le sfogline registrate (elenco completo, senza dover cercare) ---
	$( document ).on( 'click', '.gs-vedi-tutte-sfogline', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $m = $btn.closest( 'p' ).find( '.gs-elenco-sfogline-msg' );
		$m.removeClass( 'ok err' ).text( 'Carico l\'elenco…' );
		$.post( GS_AJAX.url, { action: 'gs_elenco_sfogline', nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				if ( res && res.success ) {
					$m.text( '' );
					gsApriPopupHtml( res.data.titolo || 'Tutte le sfogline registrate', res.data.html );
				} else {
					$m.addClass( 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				}
			} )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Cestino sfogline: Rifiutata/Sospesa/Eliminata, stesso popup dell'elenco principale ---
	$( document ).on( 'click', '.gs-vedi-cestino-sfogline', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $m = $btn.closest( 'p' ).find( '.gs-cestino-sfogline-msg' );
		$m.removeClass( 'ok err' ).text( 'Carico il cestino…' );
		$.post( GS_AJAX.url, { action: 'gs_cestino_sfogline', nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				if ( res && res.success ) {
					$m.text( '' );
					gsApriPopupHtml( res.data.titolo || 'Cestino sfogline', res.data.html );
				} else {
					$m.addClass( 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				}
			} )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Elenco sfogline: apre la scheda personale di una, cliccando il nome ---
	$( document ).on( 'click', '.gs-apri-scheda-sfoglina', function ( e ) {
		e.preventDefault();
		var $link = $( this );
		var $dest = $link.closest( 'table' ).nextAll( '.gs-scheda-sfoglina-aperta' ).first();
		if ( ! $dest.length ) { $dest = $link.closest( '.gs-cerca-sfoglina-risultati, .gs-inbox-lista, body' ).find( '.gs-scheda-sfoglina-aperta' ).first(); }
		$dest.html( '<p class="gs-hint">Apro la scheda…</p>' );
		$.post( GS_AJAX.url, { action: 'gs_scheda_sfoglina', nonce: GS_AJAX.nonce, uid: $link.data( 'uid' ) } )
			.done( function ( res ) {
				$dest.html( res && res.success ? res.data.html : ( '<p class="gs-hint">' + ( res && res.data ? res.data.message : 'Errore.' ) + '</p>' ) );
				if ( $dest.length && $dest[ 0 ].scrollIntoView ) { $dest[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'nearest' } ); }
			} )
			.fail( function () { $dest.html( '<p class="gs-hint">Errore di connessione.</p>' ); } );
	} );

	// --- Scheda sfoglina: attiva/disattiva/elimina account ---
	$( document ).on( 'click', '.gs-utente-stato, .gs-utente-elimina', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var uid = $btn.data( 'uid' );
		var esito = $btn.hasClass( 'gs-utente-elimina' ) ? 'eliminata' : $btn.data( 'esito' );
		var conferme = {
			sospesa: 'Disattivare questo account? Resta recuperabile in ogni momento da questa stessa scheda.',
			eliminata: 'Eliminare questo account? Non viene mai cancellato per davvero: resta nascosto ma recuperabile riattivandolo da questa scheda.',
			approvata: null
		};
		if ( conferme[ esito ] && ! window.confirm( conferme[ esito ] ) ) { return; }
		var $blocco = $btn.closest( '.gs-sfoglina-blocco' );
		var $m = $blocco.find( '.gs-utente-stato-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_utente_stato', nonce: GS_AJAX.nonce, uid: uid, esito: esito } )
			.done( function ( res ) {
				if ( res && res.success ) { $blocco.replaceWith( res.data.html ); }
				else { $m.addClass( 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); }
			} )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Scheda sfoglina: salva le modifiche anagrafiche ---
	$( document ).on( 'click', '.gs-utente-modifica-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-utente-modifica' );
		var $m = $f.find( '.gs-utente-modifica-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_utente_modifica', nonce: GS_AJAX.nonce,
			uid: $f.data( 'uid' ),
			nome: $f.find( '[name=nome]' ).val(), email: $f.find( '[name=email]' ).val(),
			squadra: $f.find( '[name=squadra]' ).val(), nascita: $f.find( '[name=nascita]' ).val()
		} )
			.done( function ( res ) {
				if ( res && res.success ) { $f.closest( '.gs-sfoglina-blocco' ).replaceWith( res.data.html ); }
				else { $m.addClass( 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); }
			} )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Scheda sfoglina: eccezione "conta come sfoglina" per Editor/Admin ---
	$( document ).on( 'change', '.gs-conta-come-sfoglina', function () {
		var $chk = $( this );
		var $riga = $chk.closest( '.gs-conta-sfoglina-riga' );
		var $m = $riga.find( '.gs-conta-sfoglina-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_conta_come_sfoglina', nonce: GS_AJAX.nonce, uid: $riga.data( 'uid' ), attiva: $chk.is( ':checked' ) ? 1 : '' } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			} )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Scheda sfoglina: attiva/disattiva la Vetrina pubblica senza token (per il gestore) ---
	$( document ).on( 'change', '.gs-vetrina-admin-attiva', function () {
		var $chk = $( this );
		var $riga = $chk.closest( '.gs-vetrina-admin-riga' );
		var $m = $riga.find( '.gs-vetrina-admin-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_vetrina_admin_attiva', nonce: GS_AJAX.nonce, uid: $riga.data( 'uid' ), attiva: $chk.is( ':checked' ) ? 1 : '' } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			} )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Regia degli Iscritti ai Corsi: filtro per stato (mantiene l'eventuale filtro per corso già attivo) ---
	$( document ).on( 'click', '.gs-regia-filtro', function () {
		var $btn = $( this );
		$btn.siblings( '.gs-regia-filtro' ).removeClass( 'attivo' );
		$btn.addClass( 'attivo' );
		var $elenco = $( '#gs-regia-elenco' );
		var corso = $btn.closest( '.gs-regia-filtri' ).data( 'corso' ) || 0;
		$elenco.css( 'opacity', .5 );
		$.post( GS_AJAX.url, { action: 'gs_regia_filtra', nonce: GS_AJAX.nonce, stato: $btn.data( 'stato' ), corso: corso } )
			.done( function ( res ) {
				if ( res && res.success ) { $elenco.html( res.data.html ); }
			} )
			.always( function () { $elenco.css( 'opacity', 1 ); } );
	} );

	// --- Regia degli Iscritti ai Corsi: cerca per nome (filtro dal vivo, lato client, sulle righe già caricate) ---
	$( document ).on( 'input', '.gs-regia-cerca', function () {
		var q = $( this ).val().trim().toLowerCase();
		$( '#gs-regia-elenco .gs-regia-riga' ).each( function () {
			var nome = $( this ).find( '.gs-regia-nome' ).text().toLowerCase();
			$( this ).toggle( nome.indexOf( q ) !== -1 );
		} );
	} );

	// --- Regia degli Iscritti ai Corsi: apre la scheda completa di una persona ---
	function gsRegiaApriScheda( uid ) {
		var $scheda = $( '#gs-regia-scheda' );
		$scheda.html( '<p class="gs-hint">Apertura scheda…</p>' );
		$scheda.get( 0 ).scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
		$.post( GS_AJAX.url, { action: 'gs_regia_apri_scheda', nonce: GS_AJAX.nonce, user_id: uid } )
			.done( function ( res ) {
				$scheda.html( res && res.success ? res.data.html : '<p class="gs-hint">Errore nell\'apertura della scheda.</p>' );
			} )
			.fail( function () { $scheda.html( '<p class="gs-hint">Errore di connessione.</p>' ); } );
	}
	$( document ).on( 'click keypress', '.gs-regia-riga', function ( e ) {
		if ( e.type === 'keypress' && e.which !== 13 ) { return; }
		gsRegiaApriScheda( $( this ).data( 'uid' ) );
	} );

	// --- Regia degli Iscritti ai Corsi: salva la nota riservata ---
	$( document ).on( 'click', '.gs-regia-nota-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-regia-nota' );
		var $m = $f.find( '.gs-regia-nota-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_regia_nota_salva', nonce: GS_AJAX.nonce, user_id: $f.data( 'uid' ), nota: $f.find( '[name=nota]' ).val() } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			} )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Regia degli Iscritti ai Corsi: salva la scelta di soggiorno ---
	$( document ).on( 'click', '.gs-regia-soggiorno-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-regia-soggiorno' );
		var $m = $f.find( '.gs-regia-soggiorno-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_regia_soggiorno_salva', nonce: GS_AJAX.nonce, user_id: $f.data( 'uid' ), soggiorno: $f.find( '[name=soggiorno]' ).val() } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			} )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Regia degli Iscritti ai Corsi: genera/apre il diploma di una prenotazione ---
	$( document ).on( 'click', '.gs-regia-diploma-toggle', function () {
		var $btn = $( this );
		var $m = $btn.closest( '.gs-inbox-corpo' ).find( '.gs-regia-diploma-msg' );
		$m.removeClass( 'ok err' ).text( 'Generazione…' );
		$.post( GS_AJAX.url, { action: 'gs_regia_diploma_toggle', nonce: GS_AJAX.nonce, pren_id: $btn.data( 'pren' ) } )
			.done( function ( res ) {
				if ( res && res.success ) {
					$m.addClass( 'ok' ).text( res.data.message );
					if ( res.data.attivo && res.data.url ) {
						$btn.replaceWith( $( '<a target="_blank">📜 vedi</a>' ).attr( 'href', res.data.url ) );
					}
				} else {
					$m.addClass( 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				}
			} )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Cestino: eliminazione definitiva (solo per il titolare del sito, vedi
	// gs_is_titolare() in helpers.php) — "seleziona tutto" e pulsante bulk. ---
	$( document ).on( 'change', '.gs-cestino-check-tutti', function () {
		var checked = $( this ).is( ':checked' );
		$( this ).closest( 'table' ).find( '.gs-cestino-check' ).prop( 'checked', checked );
	} );

	$( document ).on( 'click', '.gs-cestino-elimina-def', function ( e ) {
		e.preventDefault();
		var $btn  = $( this );
		var tipo  = $btn.data( 'tipo' );
		var $m    = $btn.closest( '.gs-cestino-azioni' ).find( '.gs-cestino-bulk-msg' );
		var $tabella = $btn.closest( '.gs-sezione-cestino' ).find( 'table' ).first();
		var ids = $tabella.find( '.gs-cestino-check:checked' ).map( function () { return $( this ).val(); } ).get();
		if ( ! ids.length ) {
			$m.removeClass( 'ok' ).addClass( 'err' ).text( 'Seleziona almeno un elemento.' );
			return;
		}
		if ( ! window.confirm( 'Eliminare definitivamente ' + ids.length + ' elemento/i? Non sarà più possibile recuperarli, nemmeno dal cestino.' ) ) {
			return;
		}
		$m.removeClass( 'ok err' ).text( 'Eliminazione…' );
		$.post( GS_AJAX.url, { action: 'gs_cestino_elimina_definitivo', nonce: GS_AJAX.nonce, tipo: tipo, ids: ids } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// Ricerca generica: filtra righe/card e ricalcola la paginazione.
	function gsApplyFilter( $els, q ) {
		$els.each( function () {
			var txt = ( $( this ).data( 'nome' ) || $( this ).text() ).toString().toLowerCase();
			$( this ).toggleClass( 'gs-hide-search', txt.indexOf( q ) === -1 );
		} );
	}
	$( document ).on( 'input', '.gs-cerca-input', function () {
		var q = $( this ).val().toLowerCase().trim();
		var $t = $( $( this ).data( 'target' ) );
		if ( ! $t.length ) { return; }
		gsApplyFilter( $t.is( 'table' ) ? $t.find( 'tbody tr' ) : $t.find( 'tbody tr' ), q );
		gsApplyFilter( $t.find( '.gs-card' ), q );
		gsApplyFilter( $t.find( '> .gs-inbox-item' ), q );
		gsApplyFilter( $t.find( '> label' ), q );
		// Ricalcola le pagine dopo il filtro.
		if ( $t.hasClass( 'gs-paginate' ) ) { $t.data( 'gsPage', 1 ); gsRepaginate( $t ); }
		$t.find( '.gs-paginate' ).each( function () { $( this ).data( 'gsPage', 1 ); gsRepaginate( $( this ) ); } );
	} );

	// Filtro per livello (es. galleria "Le Sfogline"): si combina con la ricerca
	// per nome, non la sostituisce — una scheda resta nascosta se non passa
	// nessuno dei due filtri.
	$( document ).on( 'change', '.gs-filtro-livello', function () {
		var liv = $( this ).val();
		var $t  = $( $( this ).data( 'target' ) );
		if ( ! $t.length ) { return; }
		$t.find( '.gs-card' ).each( function () {
			var match = ( '' === liv ) || ( String( $( this ).data( 'livello' ) ) === liv );
			$( this ).toggleClass( 'gs-hide-livello', ! match );
		} );
		if ( $t.hasClass( 'gs-paginate' ) ) { $t.data( 'gsPage', 1 ); gsRepaginate( $t ); }
	} );

	// Ricerca unica su Ricettario + Lezioni Video + Esperto (AJAX, con un piccolo ritardo
	// per non mandare una richiesta a ogni singola lettera digitata).
	var gsRicercaGlobaleTimer = null;
	$( document ).on( 'input', '.gs-ricerca-globale-input', function () {
		var $input = $( this );
		var $box   = $input.closest( '.gs-box' ).find( '.gs-ricerca-globale-risultati' );
		var q = $input.val().trim();
		clearTimeout( gsRicercaGlobaleTimer );
		if ( q.length < 3 ) {
			$box.html( '<p class="gs-hint">Scrivi almeno 3 lettere.</p>' );
			return;
		}
		gsRicercaGlobaleTimer = setTimeout( function () {
			$box.html( '<p class="gs-hint">Cerco…</p>' );
			$.post( GS_AJAX.url, { action: 'gs_ricerca_globale', nonce: GS_AJAX.nonce, q: q } )
				.done( function ( res ) {
					if ( ! res || ! res.success ) { $box.html( '<p class="gs-hint">' + ( res && res.data ? res.data.message : 'Errore.' ) + '</p>' ); return; }
					var d = res.data;
					if ( ! d.totale ) { $box.html( '<p class="gs-hint">Nessun risultato.</p>' ); return; }
					var html = '';
					var sezioni = [
						{ key: 'ricettario', label: '📖 Ricettario delle Famiglie' },
						{ key: 'lezioni', label: '🎬 Lezioni Video' },
						{ key: 'faq', label: '❓ FAQ - Domande' },
						{ key: 'cassaforte', label: '🔐 La Cassaforte del Sapere' },
						{ key: 'sfoglia_insegna', label: '📚 La Sfoglia che Insegna Se Stessa' },
						{ key: 'matterello', label: '🎙️ Il Matterello Parlante' },
						{ key: 'letture', label: '📰 Le Letture dei Grandi Protagonisti' },
						{ key: 'piatti_estinzione', label: '🍽️ Adotta un Piatto in Via di Estinzione' },
						{ key: 'novita', label: '📣 Novità' },
						{ key: 'sondaggi', label: '🗳️ Sondaggi' },
						{ key: 'dicono_di_noi', label: '💬 Dicono di Noi' },
						{ key: 'esperto', label: "💬 L'Esperto Risponde" }
					];
					sezioni.forEach( function ( s ) {
						var lista = d.risultati[ s.key ] || [];
						if ( ! lista.length ) { return; }
						html += '<h4>' + s.label + ' (' + lista.length + ')</h4><ul class="gs-missions">';
						lista.forEach( function ( t ) { html += '<li>' + $( '<div>' ).text( t ).html() + '</li>'; } );
						html += '</ul>';
						if ( d.link[ s.key ] ) { html += '<p><a class="gs-btn gs-btn-sm" href="' + d.link[ s.key ] + '">Vai alla sezione →</a></p>'; }
					} );
					$box.html( html );
				} )
				.fail( function () { $box.html( '<p class="gs-hint">Errore, riprova.</p>' ); } );
		}, 350 );
	} );

	// -------------------------------------------------------------------------
	// Cestino: cestina / ripristina un contenuto
	// -------------------------------------------------------------------------
	function gsMoveContenuto( action, $btn ) {
		var pid = $btn.data( 'post' );
		var $row = $btn.closest( 'tr, details.gs-inbox-item' );
		var inAdmin = $btn.closest( '.gs-cerca-sfoglina-risultati' ).length > 0;
		$row.find( 'button' ).prop( 'disabled', true );
		$.post( GS_AJAX.url, { action: action, nonce: GS_AJAX.nonce, post: pid } )
			.done( function ( res ) {
				if ( res && res.success ) {
					$row.fadeOut( 250, function () { $( this ).remove(); } );
					if ( ! inAdmin ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 500 ); }
				} else {
					$row.find( 'button' ).prop( 'disabled', false );
					alert( ( res && res.data && res.data.message ) ? res.data.message : 'Errore.' );
				}
			} )
			.fail( function () { $row.find( 'button' ).prop( 'disabled', false ); alert( 'Errore di connessione.' ); } );
	}
	$( document ).on( 'click', '.gs-cestina', function ( e ) { e.preventDefault(); gsMoveContenuto( 'gs_cestina', $( this ) ); } );
	$( document ).on( 'click', '.gs-ripristina', function ( e ) { e.preventDefault(); gsMoveContenuto( 'gs_ripristina', $( this ) ); } );

	// -------------------------------------------------------------------------
	// Le Cose da Fare (promemoria)
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-todo-add', function ( e ) {
		e.preventDefault();
		var $form = $( this ).closest( '.gs-todo-form' );
		var $inp  = $form.find( '.gs-todo-input' );
		var testo = $inp.val().trim();
		if ( ! testo ) { return; }
		$.post( GS_AJAX.url, { action: 'gs_todo_add', nonce: GS_AJAX.nonce, testo: testo } )
			.done( function ( res ) {
				if ( res && res.success ) {
					$form.siblings( '.gs-todo-list' ).append(
						'<li class="gs-todo-item" data-id="' + res.data.id + '">'
						+ '<label><input type="checkbox" class="gs-todo-check"> ' + $( '<div>' ).text( res.data.testo ).html() + '</label>'
						+ '<button class="gs-todo-del" title="Elimina">\u2715</button></li>'
					);
					$inp.val( '' );
				}
			} );
	} );
	$( document ).on( 'change', '.gs-todo-check', function () {
		var $li = $( this ).closest( '.gs-todo-item' );
		$li.toggleClass( 'done', this.checked );
		$.post( GS_AJAX.url, { action: 'gs_todo_toggle', nonce: GS_AJAX.nonce, id: $li.data( 'id' ) } );
	} );
	$( document ).on( 'click', '.gs-todo-del', function ( e ) {
		e.preventDefault();
		var $li = $( this ).closest( '.gs-todo-item' );
		var id = $li.data( 'id' );
		var testo = $.trim( $li.find( 'label' ).text() );
		var $box = $li.closest( '.gs-todo-riquadro' );
		$.post( GS_AJAX.url, { action: 'gs_todo_del', nonce: GS_AJAX.nonce, id: id } );
		$li.fadeOut( 200, function () {
			$( this ).remove();
			// Non è una cancellazione definitiva: torna a comparire nel cestino
			// personale qui sotto ("Cose eliminate"), recuperabile in ogni momento.
			var $det = $box.find( '.gs-todo-cestino' );
			if ( ! $det.length ) { return; }
			var $ul = $det.find( '.gs-todo-list-cestino' );
			if ( ! $ul.length ) {
				$det.find( '.gs-hint' ).remove();
				$ul = $( '<ul class="gs-todo-list gs-todo-list-cestino"></ul>' ).appendTo( $det );
			}
			$ul.prepend(
				'<li class="gs-todo-item" data-id="' + id + '">'
				+ '<span>' + $( '<div>' ).text( testo ).html() + '</span>'
				+ '<button class="gs-todo-ripristina" title="Ripristina">↺ Ripristina</button></li>'
			);
			$det.find( '> summary' ).text( '🗑️ Cose eliminate (' + $ul.children().length + ')' );
		} );
	} );
	$( document ).on( 'click', '.gs-todo-ripristina', function ( e ) {
		e.preventDefault();
		var $li = $( this ).closest( '.gs-todo-item' );
		var id = $li.data( 'id' );
		var testo = $.trim( $li.find( 'span' ).text() );
		var $det = $li.closest( '.gs-todo-cestino' );
		var $box = $det.closest( '.gs-todo-riquadro' );
		$.post( GS_AJAX.url, { action: 'gs_todo_ripristina', nonce: GS_AJAX.nonce, id: id } )
			.done( function ( res ) {
				if ( ! res || ! res.success ) { return; }
				$box.find( '.gs-todo-list-attive' ).append(
					'<li class="gs-todo-item" data-id="' + id + '">'
					+ '<label><input type="checkbox" class="gs-todo-check"> ' + $( '<div>' ).text( testo ).html() + '</label>'
					+ '<button class="gs-todo-del" title="Elimina">✕</button></li>'
				);
				$li.fadeOut( 200, function () {
					$( this ).remove();
					$det.find( '> summary' ).text( '🗑️ Cose eliminate (' + $det.find( '.gs-todo-list-cestino li' ).length + ')' );
				} );
			} );
	} );

	// -------------------------------------------------------------------------
	// Madrina & Allieva: mini-missioni condivise (stessa logica delle Cose da Fare)
	// -------------------------------------------------------------------------
	function gsMadrinaRigaHtml( id, testo ) {
		return '<li class="gs-todo-item gs-madrina-riga" data-id="' + id + '" style="display:block">'
			+ '<div style="display:flex;align-items:center;gap:6px">'
			+ '<input type="checkbox" class="gs-madrina-check">'
			+ '<input type="text" class="gs-madrina-testo" value="' + $( '<div>' ).text( testo ).html() + '" style="flex:1">'
			+ '<button class="gs-btn gs-btn-sm gs-madrina-modifica" title="Salva modifiche">✎ Salva</button>'
			+ '<button class="gs-todo-del gs-madrina-del" title="Elimina">✕</button></div>'
			+ '<span class="gs-madrina-riga-msg gs-richiesta-esito"></span></li>';
	}
	$( document ).on( 'click', '.gs-madrina-add', function ( e ) {
		e.preventDefault();
		var $form = $( this ).closest( '.gs-madrina-form' );
		var $inp  = $form.find( '.gs-madrina-input' );
		var testo = $inp.val().trim();
		if ( ! testo ) { return; }
		var abbinamento = $form.data( 'abbinamento' );
		$.post( GS_AJAX.url, { action: 'gs_madrina_add', nonce: GS_AJAX.nonce, abbinamento: abbinamento, testo: testo } )
			.done( function ( res ) {
				if ( res && res.success ) {
					var $lista = $form.siblings( '.gs-madrina-list' );
					if ( ! $lista.length ) {
						$form.siblings( '.gs-hint' ).remove();
						$lista = $( '<ul class="gs-todo-list gs-madrina-list"></ul>' ).attr( 'data-abbinamento', abbinamento );
						$form.after( $lista );
					}
					$lista.append( gsMadrinaRigaHtml( res.data.id, res.data.testo ) );
					$inp.val( '' );
				}
			} );
	} );
	$( document ).on( 'change', '.gs-madrina-check', function () {
		var $li   = $( this ).closest( '.gs-todo-item' );
		var $lista = $li.closest( '.gs-madrina-list' );
		$li.toggleClass( 'done', this.checked );
		$.post( GS_AJAX.url, { action: 'gs_madrina_toggle', nonce: GS_AJAX.nonce, abbinamento: $lista.data( 'abbinamento' ), id: $li.data( 'id' ) } );
	} );
	$( document ).on( 'click', '.gs-madrina-modifica', function ( e ) {
		e.preventDefault();
		var $li = $( this ).closest( '.gs-todo-item' );
		var $lista = $li.closest( '.gs-madrina-list' );
		var testo = $.trim( $li.find( '.gs-madrina-testo' ).val() );
		var $msg = $li.find( '.gs-madrina-riga-msg' );
		if ( ! testo ) { $msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Il testo non può essere vuoto.' ); return; }
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_madrina_modifica', nonce: GS_AJAX.nonce, abbinamento: $lista.data( 'abbinamento' ), id: $li.data( 'id' ), testo: testo } )
			.done( function ( res ) { $msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); } )
			.fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-madrina-del', function ( e ) {
		e.preventDefault();
		var $li   = $( this ).closest( '.gs-todo-item' );
		var $lista = $li.closest( '.gs-madrina-list' );
		var abbinamento = $lista.data( 'abbinamento' );
		var id = $li.data( 'id' );
		var testo = $.trim( $li.find( '.gs-madrina-testo' ).val() );
		var $box = $li.closest( '.gs-todo-riquadro' );
		$.post( GS_AJAX.url, { action: 'gs_madrina_del', nonce: GS_AJAX.nonce, abbinamento: abbinamento, id: id } );
		$li.fadeOut( 200, function () {
			$( this ).remove();
			var $det = $box.find( '.gs-madrina-cestino' );
			if ( ! $det.length ) { return; }
			var $ul = $det.find( '.gs-todo-list-cestino' );
			if ( ! $ul.length ) {
				$det.find( '.gs-hint' ).remove();
				$ul = $( '<ul class="gs-todo-list gs-todo-list-cestino"></ul>' ).appendTo( $det );
			}
			$ul.prepend(
				'<li class="gs-todo-item" data-id="' + id + '">'
				+ '<span>' + $( '<div>' ).text( testo ).html() + '</span>'
				+ '<button class="gs-todo-ripristina gs-madrina-ripristina" title="Ripristina">↺ Ripristina</button></li>'
			);
			$det.find( '> summary' ).text( '🗑️ Missioni eliminate (' + $ul.children().length + ')' );
		} );
	} );
	$( document ).on( 'click', '.gs-madrina-ripristina', function ( e ) {
		e.preventDefault();
		var $li = $( this ).closest( '.gs-todo-item' );
		var id = $li.data( 'id' );
		var testo = $.trim( $li.find( 'span' ).text() );
		var $det = $li.closest( '.gs-madrina-cestino' );
		var abbinamento = $det.data( 'abbinamento' );
		var $box = $det.closest( '.gs-todo-riquadro' );
		$.post( GS_AJAX.url, { action: 'gs_madrina_ripristina', nonce: GS_AJAX.nonce, abbinamento: abbinamento, id: id } )
			.done( function ( res ) {
				if ( ! res || ! res.success ) { return; }
				var $lista = $box.find( '.gs-madrina-list' );
				if ( ! $lista.length ) {
					$box.find( '.gs-hint' ).first().remove();
					$lista = $( '<ul class="gs-todo-list gs-madrina-list"></ul>' ).attr( 'data-abbinamento', abbinamento );
					$box.find( '.gs-madrina-form' ).after( $lista );
				}
				$lista.append( gsMadrinaRigaHtml( id, testo ) );
				$li.fadeOut( 200, function () {
					$( this ).remove();
					$det.find( '> summary' ).text( '🗑️ Missioni eliminate (' + $det.find( '.gs-todo-list-cestino li' ).length + ')' );
				} );
			} );
	} );

	// -------------------------------------------------------------------------
	// Pannello gestore: Madrina & Allieva — crea/concludi abbinamenti
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-crea-abbinamento', function ( e ) {
		e.preventDefault();
		var $btn  = $( this );
		var $form = $btn.closest( '.gs-form-nuovo-abbinamento' );
		var $msg  = $form.find( '.gs-abb-msg' );
		var madrina = $form.find( '.gs-abb-madrina' ).val();
		var allieva = $form.find( '.gs-abb-allieva' ).val();
		if ( madrina === allieva ) {
			$msg.attr( 'class', 'gs-abb-msg gs-richiesta-esito err' ).text( 'Scegli due sfogline diverse.' );
			return;
		}
		$btn.prop( 'disabled', true );
		$.post( GS_AJAX.url, { action: 'gs_crea_abbinamento', nonce: GS_AJAX.nonce, madrina: madrina, allieva: allieva } )
			.done( function ( res ) {
				if ( res && res.success ) {
					$msg.attr( 'class', 'gs-abb-msg gs-richiesta-esito ok' ).text( res.data.message );
					setTimeout( function () { location.reload(); }, 600 );
				} else {
					$msg.attr( 'class', 'gs-abb-msg gs-richiesta-esito err' ).text( res && res.data ? res.data.message : 'Errore.' );
				}
			} )
			.always( function () { $btn.prop( 'disabled', false ); } );
	} );
	$( document ).on( 'click', '.gs-concludi-abbinamento', function ( e ) {
		e.preventDefault();
		if ( ! window.confirm( 'Concludere questo abbinamento?' ) ) { return; }
		var $btn = $( this );
		var $tr  = $btn.closest( 'tr' );
		$.post( GS_AJAX.url, { action: 'gs_concludi_abbinamento', nonce: GS_AJAX.nonce, id: $btn.data( 'id' ) } )
			.done( function ( res ) {
				if ( res && res.success ) {
					$tr.fadeOut( 200, function () { $( this ).remove(); } );
				}
			} );
	} );

	// -------------------------------------------------------------------------
	// Pannello gestore: cerca una sfoglina e mostra lavori + cestino
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-cerca-sfoglina-btn', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '.gs-box' );
		var q    = $box.find( '.gs-cerca-sfoglina-input' ).val();
		var $out = $box.find( '.gs-cerca-sfoglina-risultati' );
		$out.html( 'Ricerca in corso…' );
		$.post( GS_AJAX.url, { action: 'gs_fe_cerca_sfoglina', nonce: GS_AJAX.nonce, q: q } )
			.done( function ( res ) {
				$out.html( ( res && res.success ) ? res.data.html : ( ( res && res.data && res.data.message ) ? res.data.message : 'Errore.' ) );
				if ( window.gsInitPagination ) { window.gsInitPagination( $out ); }
			} )
			.fail( function () { $out.html( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'keydown', '.gs-cerca-sfoglina-input', function ( e ) {
		if ( e.which === 13 ) { e.preventDefault(); $( this ).closest( '.gs-box' ).find( '.gs-cerca-sfoglina-btn' ).click(); }
	} );

	// Correzione manuale del genere (sfoglina/sfoglino) di una sfoglina trovata.
	$( document ).on( 'click', '.gs-genere-salva', function ( e ) {
		e.preventDefault();
		var $riga = $( this ).closest( '.gs-genere-riga' );
		var $msg  = $riga.find( '.gs-genere-msg' );
		$msg.text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_imposta_genere',
			nonce: GS_AJAX.nonce,
			user_id: $riga.data( 'user' ),
			genere: $riga.find( '.gs-genere-select' ).val()
		} )
			.done( function ( res ) {
				$msg.text( ( res && res.data && res.data.message ) ? res.data.message : ( res && res.success ? 'Salvato.' : 'Errore.' ) );
			} )
			.fail( function () { $msg.text( 'Errore di connessione.' ); } );
	} );

	// -------------------------------------------------------------------------
	// Blackout: oscura / riattiva il Gaming
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-toggle-blackout', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $box = $btn.closest( '.gs-box' );
		var $msg = $box.find( '.gs-blackout-msg' );
		$btn.prop( 'disabled', true );
		$.post( GS_AJAX.url, { action: 'gs_fe_toggle_blackout', nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				if ( res && res.success ) {
					var off = res.data.attivo;
					$box.find( '.gs-blackout-stato' ).removeClass( 'on off' ).addClass( off ? 'off' : 'on' )
						.text( off ? 'GAMING OSCURATO' : 'GAMING ATTIVO' );
					$btn.text( off ? 'Riattiva il Gaming' : 'Oscura tutto il Gaming' );
					$btn.toggleClass( 'gs-btn-verde', off );
					$msg.removeClass( 'err' ).addClass( 'ok' ).text( res.data.message );
				} else {
					$msg.addClass( 'err' ).text( 'Errore.' );
				}
			} )
			.fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } )
			.always( function () { $btn.prop( 'disabled', false ); } );
	} );

	// Interruttore rapido del blackout nella testata del Pannello Generale
	// (Ennio, 22/08/2026: "in prima vista nei pannelli generali") — stesso
	// endpoint del pulsante dentro la sezione "Blackout", solo un accesso
	// più rapido, sempre visibile qualunque sezione sia aperta.
	$( document ).on( 'click', '.gs-toggle-blackout-rapido', function ( e ) {
		e.preventDefault();
		var $btn = $( this );

		// OSCURARE richiede tre clic ravvicinati, RIATTIVARE resta a un
		// clic solo (richiesto da Ennio il 22/08/2026, subito dopo aver
		// reso questo pulsante "in prima vista": sicurezza contro un clic
		// accidentale di un collaboratore, non serve la stessa cautela per
		// annullare in fretta un blackout già attivo).
		if ( ! $btn.hasClass( 'gs-pn-blackout-attivo' ) ) {
			var contatore = ( $btn.data( 'gs-clic' ) || 0 ) + 1;
			clearTimeout( $btn.data( 'gs-clic-timer' ) );
			if ( contatore < 3 ) {
				if ( ! $btn.data( 'gs-testo-originale' ) ) {
					$btn.data( 'gs-testo-originale', $btn.text() );
				}
				$btn.data( 'gs-clic', contatore );
				$btn.text( '🌙 Clicca ancora ' + ( 3 - contatore ) + ( 3 - contatore === 1 ? ' volta' : ' volte' ) + ' per oscurare' );
				var timer = setTimeout( function () {
					$btn.data( 'gs-clic', 0 );
					$btn.text( $btn.data( 'gs-testo-originale' ) || '🌙 Gaming attivo' );
				}, 2500 );
				$btn.data( 'gs-clic-timer', timer );
				return;
			}
			$btn.data( 'gs-clic', 0 );
		}

		$btn.prop( 'disabled', true );
		$.post( GS_AJAX.url, { action: 'gs_fe_toggle_blackout', nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				if ( res && res.success ) {
					var off = res.data.attivo;
					$btn.toggleClass( 'gs-pn-blackout-attivo', off );
					$btn.text( off ? '🌙 OSCURATO — riattiva' : '🌙 Gaming attivo' );
					$btn.removeData( 'gs-testo-originale' );
				}
			} )
			.always( function () { $btn.prop( 'disabled', false ); } );
	} );

	// Blackout: eccezioni (sfogline che vedono tutto anche da oscurato)
	$( document ).on( 'click', '.gs-blackout-esenti-salva', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $f   = $btn.closest( '.gs-form-blackout-esenti' );
		var $msg = $f.find( '.gs-blackout-esenti-msg' );
		var ids  = $f.find( '.gs-blackout-esenti-select' ).val() || [];
		$btn.prop( 'disabled', true );
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_blackout_esenti_salva', nonce: GS_AJAX.nonce, esenti: ids } )
			.done( function ( res ) {
				$msg.removeClass( 'ok err' ).addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			} )
			.fail( function () { $msg.removeClass( 'ok err' ).addClass( 'err' ).text( 'Errore di connessione.' ); } )
			.always( function () { $btn.prop( 'disabled', false ); } );
	} );

	// -------------------------------------------------------------------------
	// Blocco dashboard WP: nega/riapre wp-admin a chi non è amministratore vero
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-toggle-blocco-wpadmin', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $box = $btn.closest( '.gs-box' );
		var $msg = $box.find( '.gs-blocco-wpadmin-msg' );
		$btn.prop( 'disabled', true );
		$.post( GS_AJAX.url, { action: 'gs_fe_toggle_blocco_wpadmin', nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				if ( res && res.success ) {
					var attivo = res.data.attivo;
					$box.find( '.gs-blackout-stato' ).removeClass( 'on off' ).addClass( attivo ? 'off' : 'on' )
						.text( attivo ? 'DASHBOARD BLOCCATA' : 'DASHBOARD LIBERA' );
					$btn.text( attivo ? 'Riapri la dashboard a tutti' : 'Blocca la dashboard agli altri' );
					$btn.toggleClass( 'gs-btn-verde', attivo );
					$msg.removeClass( 'err' ).addClass( 'ok' ).text( res.data.message );
				} else {
					$msg.addClass( 'err' ).text( 'Errore.' );
				}
			} )
			.fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } )
			.always( function () { $btn.prop( 'disabled', false ); } );
	} );

	// "Nascondi logo" dentro il Pannello di Controllo (richiesto da Ennio,
	// 17/08/2026): stesso interruttore puramente lato client già usato da
	// gsHeaderToggleInit() (classe body "gs-logo-hidden"), richiamabile anche
	// da qui. Niente AJAX: non è un'impostazione del sito da salvare per
	// tutti, solo lo stato per questa sessione di navigazione — l'evento
	// "resize" fa ricalcolare lo spazio riservato in cima alla pagina
	// riusando gsAllineaSpazioMenu(), già agganciata a quell'evento.
	// Imposta anche "gs-scelta-manuale": senza, lo scorrimento automatico
	// (in un'altra funzione, con la sua chiusura separata) continuava a
	// disfare questa scelta non appena si scorreva — trovato in revisione
	// il 26/08/2026, corretto insieme al pulsante gemello sul sito.
	$( document ).on( 'click', '.gs-toggle-logo-pannello', function () {
		document.body.classList.add( 'gs-scelta-manuale' );
		var $btn = $( this );
		var $msg = $btn.closest( 'p' ).next( '.gs-logo-pannello-msg' ).length
			? $btn.closest( 'p' ).find( '.gs-logo-pannello-msg' )
			: $btn.siblings( '.gs-logo-pannello-msg' );
		var nascosto = $( 'body' ).toggleClass( 'gs-logo-hidden' ).hasClass( 'gs-logo-hidden' );
		$btn.text( nascosto ? 'Rimetti il logo' : 'Fissa il menu in alto' ).toggleClass( 'gs-btn-verde', nascosto );
		$msg.removeClass( 'err' ).addClass( 'ok' ).text( nascosto ? 'Logo nascosto.' : 'Logo di nuovo visibile.' );
		// 320 come applica(): sotto quel tempo la transizione CSS non è
		// finita e si legge un'altezza a metà.
		setTimeout( function () { $( window ).trigger( 'resize' ); }, 320 );
	} );

	// -------------------------------------------------------------------------
	// Media: compressione foto lato browser + limite
	// -------------------------------------------------------------------------
	function gsLimiteByte() {
		return ( window.GS_MEDIA ? parseFloat( GS_MEDIA.limite_mb ) : 0 ) * 1024 * 1024;
	}
	function gsCompressImage( file, maxLato ) {
		return new Promise( function ( resolve ) {
			if ( ! /^image\//.test( file.type ) || /gif/.test( file.type ) ) { resolve( null ); return; }
			var img = new Image();
			var url = URL.createObjectURL( file );
			img.onload = function () {
				var w = img.width, h = img.height;
				var scale = Math.min( 1, maxLato / Math.max( w, h ) );
				var cw = Math.round( w * scale ), ch = Math.round( h * scale );
				var canvas = document.createElement( 'canvas' );
				canvas.width = cw; canvas.height = ch;
				canvas.getContext( '2d' ).drawImage( img, 0, 0, cw, ch );
				URL.revokeObjectURL( url );
				canvas.toBlob( function ( blob ) { resolve( blob ); }, 'image/jpeg', 0.82 );
			};
			img.onerror = function () { URL.revokeObjectURL( url ); resolve( null ); };
			img.src = url;
		} );
	}
	$( document ).on( 'change', 'input[type=file]', function () {
		var el = this, file = el.files && el.files[0];
		el._gsBlob = null;
		if ( ! file ) { return; }
		if ( window.GS_MEDIA && GS_MEDIA.comprimi && /^image\//.test( file.type ) ) {
			gsCompressImage( file, GS_MEDIA.max_lato ).then( function ( blob ) {
				if ( blob && blob.size < file.size ) {
					el._gsBlob = blob;
					el._gsName = ( file.name.replace( /\.[^.]+$/, '' ) || 'foto' ) + '.jpg';
				}
			} );
		}
	} );

	// Da un input[type=file] (o un $() jQuery che lo racchiude), restituisce
	// {file, nome} da inviare davvero: la versione compressa se disponibile
	// (vedi il listener 'change' qui sopra), altrimenti il file originale.
	// null se non è stato scelto nessun file. Ogni punto del sito che allega
	// una foto/video a un invio deve passare da qui invece di leggere
	// direttamente input.files[0], altrimenti salta sia la compressione sia
	// il controllo del limite di peso qui sotto.
	function gsFileDaInviare( $input ) {
		var el = $input && $input.length ? $input[ 0 ] : $input;
		if ( ! el || ! el.files || ! el.files.length ) { return null; }
		if ( el._gsBlob ) { return { file: el._gsBlob, nome: el._gsName }; }
		return { file: el.files[ 0 ], nome: el.files[ 0 ].name };
	}
	// Messaggio d'errore da mostrare se il file scelto (già passato da
	// gsFileDaInviare) supera il limite di peso configurato, o null se va bene.
	function gsMessaggioSeTroppoGrande( scelto ) {
		var limite = gsLimiteByte();
		if ( scelto && limite > 0 && scelto.file.size > limite ) {
			return 'Il file ' + scelto.nome + ' supera il limite di ' + GS_MEDIA.limite_mb + ' MB. Riduci le dimensioni e riprova.';
		}
		return null;
	}

	// -------------------------------------------------------------------------
	// Pannello: salva impostazioni media
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-salva-media', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-media' );
		var $msg = $f.find( '.gs-media-msg' );
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_fe_salva_media', nonce: GS_AJAX.nonce,
			comprimi_foto: $f.find( '[name=comprimi_foto]' ).is( ':checked' ) ? 1 : 0,
			comprimi_video: $f.find( '[name=comprimi_video]' ).is( ':checked' ) ? 1 : 0,
			foto_max_lato: $f.find( '[name=foto_max_lato]' ).val(),
			limite_mb: $f.find( '[name=limite_mb]' ).val()
		} ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// -------------------------------------------------------------------------
	// Pannello: salva impostazioni backup + esegui backup ora
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-salva-backup', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-backup' );
		var $msg = $f.find( '.gs-backup-msg' );
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_fe_salva_backup', nonce: GS_AJAX.nonce,
			attivo: $f.find( '[name=attivo]' ).is( ':checked' ) ? 1 : 0,
			retention: $f.find( '[name=retention]' ).val()
		} ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-backup-ora', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $box = $btn.closest( '.gs-box' );
		var $msg = $box.find( '.gs-backup-msg' );
		$btn.prop( 'disabled', true );
		$msg.removeClass( 'ok err' ).text( 'Backup in corso…' );
		$.post( GS_AJAX.url, { action: 'gs_fe_backup_ora', nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				if ( res && res.success ) {
					$msg.addClass( 'ok' ).text( res.data.message );
					$box.find( '.gs-backup-lista' ).html( res.data.html );
				} else {
					$msg.addClass( 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				}
			} )
			.fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } )
			.always( function () { $btn.prop( 'disabled', false ); } );
	} );

	// -------------------------------------------------------------------------
	// Onboarding — chiudi il riquadro di benvenuto (non riappare)
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-onboarding-chiudi, .gs-onboarding-ok', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '.gs-onboarding' );
		$box.slideUp( 150, function () { $box.remove(); } );
		$.post( GS_AJAX.url, { action: 'gs_onboarding_chiudi', nonce: GS_AJAX.nonce } );
	} );

	// -------------------------------------------------------------------------
	// Diagnostica — invio email di prova
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-diag-email', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $msg = $btn.closest( '.gs-box' ).find( '.gs-diag-email-msg' );
		$btn.prop( 'disabled', true );
		$msg.removeClass( 'ok err' ).text( 'Invio in corso…' );
		$.post( GS_AJAX.url, { action: 'gs_diag_email', nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				if ( res && res.success ) {
					$msg.addClass( 'ok' ).text( res.data.message );
				} else {
					$msg.addClass( 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				}
			} )
			.fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } )
			.always( function () { $btn.prop( 'disabled', false ); } );
	} );

	// -------------------------------------------------------------------------
	// Area Professionale — lato sfoglina: salva stato/nota di un compito
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-compito-salva', function ( e ) {
		e.preventDefault();
		var $c = $( this ).closest( '.gs-compito' );
		var $msg = $c.find( '.gs-compito-msg' );
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_compito_salva', nonce: GS_AJAX.nonce,
			compito: $c.data( 'id' ),
			fatto: $c.find( '.gs-compito-fatto' ).is( ':checked' ) ? 1 : 0,
			nota: $c.find( '.gs-compito-nota' ).val()
		} ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// -------------------------------------------------------------------------
	// Area Professionale — lato gestore
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-crea-corso', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-nuovo-corso' );
		var $msg = $f.find( '.gs-corso-msg' );
		$msg.removeClass( 'ok err' ).text( 'Creazione…' );
		$.post( GS_AJAX.url, {
			action: 'gs_pro_crea_corso', nonce: GS_AJAX.nonce,
			utente: $f.find( '[name=utente]' ).val(),
			titolo: $f.find( '[name=titolo]' ).val(),
			docente: $f.find( '[name=docente]' ).val()
		} ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	function gsProCorsoId( $el ) { return $el.closest( '.gs-pro-corso' ).data( 'corso' ); }

	$( document ).on( 'click', '.gs-add-compito', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-compito' );
		var corso = gsProCorsoId( $f );
		$.post( GS_AJAX.url, {
			action: 'gs_pro_add_compito', nonce: GS_AJAX.nonce,
			corso: corso,
			data: $f.find( '.gs-compito-data-input' ).val(),
			testo: $f.find( '.gs-compito-testo-input' ).val()
		} ).done( function ( res ) {
			if ( res && res.success ) { gsReloadMantenendoPosizione(); }
			else { alert( res && res.data ? res.data.message : 'Errore.' ); }
		} ).fail( function () { alert( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-pro-compito-modifica', function ( e ) {
		e.preventDefault();
		var $c = $( this ).closest( '.gs-pro-compito' );
		var $msg = $c.find( '.gs-pro-c-msg' );
		var testo = $.trim( $c.find( '.gs-pro-compito-testo' ).val() );
		if ( ! testo ) { $msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Il compito non può essere vuoto.' ); return; }
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_pro_modifica_compito', nonce: GS_AJAX.nonce,
			corso: gsProCorsoId( $c ), compito: $c.data( 'id' ),
			data: $c.find( '.gs-pro-compito-data' ).val(),
			titolo: $c.find( '.gs-pro-compito-titolo' ).val(),
			testo: testo
		} ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-pro-feedback-salva', function ( e ) {
		e.preventDefault();
		var $c = $( this ).closest( '.gs-pro-compito' );
		var $msg = $c.find( '.gs-pro-c-msg' );
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_pro_feedback', nonce: GS_AJAX.nonce,
			corso: gsProCorsoId( $c ), compito: $c.data( 'id' ),
			feedback: $c.find( '.gs-pro-feedback' ).val()
		} ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-pro-del-compito', function ( e ) {
		e.preventDefault();
		if ( ! window.confirm( 'Spostare questo compito nel cestino? Potrai ripristinarlo.' ) ) { return; }
		var $c = $( this ).closest( '.gs-pro-compito' );
		$.post( GS_AJAX.url, { action: 'gs_pro_del_compito', nonce: GS_AJAX.nonce, corso: gsProCorsoId( $c ), compito: $c.data( 'id' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } } );
	} );
	$( document ).on( 'click', '.gs-pro-ripristina-compito', function ( e ) {
		e.preventDefault();
		var $li = $( this ).closest( '.gs-todo-item' );
		var $corso = $( this ).closest( '.gs-pro-corso' );
		$.post( GS_AJAX.url, { action: 'gs_pro_ripristina_compito', nonce: GS_AJAX.nonce, corso: $corso.data( 'corso' ), compito: $li.data( 'id' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } } );
	} );

	$( document ).on( 'click', '.gs-pro-oscura', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $corso = $btn.closest( '.gs-pro-corso' );
		$.post( GS_AJAX.url, { action: 'gs_pro_oscura', nonce: GS_AJAX.nonce, corso: $corso.data( 'corso' ) } )
			.done( function ( res ) {
				if ( res && res.success ) {
					$btn.text( res.data.label ).data( 'osc', res.data.oscurato ? '1' : '0' );
					$corso.find( '.gs-pro-msg' ).removeClass( 'err' ).addClass( 'ok' ).text( res.data.message );
				}
			} );
	} );

	$( document ).on( 'click', '.gs-pro-stato-toggle', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $corso = $btn.closest( '.gs-pro-corso' );
		$.post( GS_AJAX.url, { action: 'gs_pro_stato', nonce: GS_AJAX.nonce, corso: $corso.data( 'corso' ) } )
			.done( function ( res ) {
				if ( res && res.success ) {
					$btn.text( res.data.label );
					$btn.toggleClass( 'gs-btn-verde', 'sospeso' === res.data.stato );
					$corso.find( '.gs-pro-stato' ).text( res.data.testo );
					$corso.find( '.gs-pro-msg' ).removeClass( 'err' ).addClass( 'ok' ).text( res.data.message );
				}
			} );
	} );

	$( document ).on( 'click', '.gs-pro-diploma-toggle', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		// È un interruttore (ogni clic inverte lo stato): senza freno, un
		// doppio clic lo assegna e poi lo revoca subito, ma il messaggio a
		// schermo resta quello della prima risposta ("Diploma assegnato")
		// mentre la ricarica mostra che non c'è (trovato il 25/08/2026).
		if ( $btn.prop( 'disabled' ) ) { return; }
		$btn.prop( 'disabled', true );
		var $corso = $btn.closest( '.gs-pro-corso' );
		var $msg = $corso.find( '.gs-pro-diploma-msg' );
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_pro_diploma', nonce: GS_AJAX.nonce, corso: $corso.data( 'corso' ) } )
			.done( function ( res ) {
				if ( res && res.success ) {
					$msg.removeClass( 'err' ).addClass( 'ok' ).text( res.data.message );
					setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 );
				} else {
					$btn.prop( 'disabled', false );
					$msg.removeClass( 'ok' ).addClass( 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				}
			} )
			.fail( function () { $btn.prop( 'disabled', false ); $msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-pro-del-corso', function ( e ) {
		e.preventDefault();
		if ( ! window.confirm( 'Spostare il corso nel cestino? Potrai ripristinarlo dal cestino di WordPress.' ) ) { return; }
		var $corso = $( this ).closest( '.gs-pro-corso' );
		$.post( GS_AJAX.url, { action: 'gs_pro_del_corso', nonce: GS_AJAX.nonce, corso: $corso.data( 'corso' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } else { alert( res && res.data ? res.data.message : 'Errore.' ); } } );
	} );

	$( document ).on( 'click', '.gs-carica-piano', function ( e ) {
		e.preventDefault();
		if ( ! window.confirm( 'Caricare tutto il piano di studio (dal principiante alla Laurea) in questo corso?' ) ) { return; }
		var $f = $( this ).closest( '.gs-form-piano' );
		var $msg = $f.find( '.gs-piano-msg' );
		$msg.removeClass( 'ok err' ).text( 'Caricamento…' );
		$.post( GS_AJAX.url, {
			action: 'gs_pro_carica_piano', nonce: GS_AJAX.nonce,
			corso: gsProCorsoId( $f ),
			inizio: $f.find( '.gs-piano-inizio' ).val()
		} ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 1000 ); }
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-invia-messaggio', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-messaggio' );
		var $msg = $f.find( '.gs-messaggio-msg' );
		var dest = $f.find( '[name=dest]' ).val();
		if ( dest === 'tutte' && ! window.confirm( 'Inviare questo messaggio a TUTTE le sfogline?' ) ) { return; }
		$msg.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_fe_invia_messaggio', nonce: GS_AJAX.nonce,
			dest: dest,
			oggetto: $f.find( '[name=oggetto]' ).val(),
			testo: $f.find( '[name=testo]' ).val()
		} ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) {
				$f.find( '[name=oggetto], [name=testo]' ).val( '' );
				setTimeout( function () { gsReloadMantenendoPosizione(); }, 1000 );
			}
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Aeroplanino: messaggio istantaneo a tutte le sfogline ---
	// Lo sponsor "attivo ora" è calcolato dal server in base ai periodi
	// configurati (richiesto da Ennio il 17/08/2026: più sponsor, ognuno col
	// suo periodo, non più uno solo fisso) e stampato in window.GS_SPONSOR_ORA
	// al caricamento della pagina — niente chiamata AJAX in più solo per
	// l'anteprima.
	function gsAeroplaninoSponsorCorrente( $f ) {
		if ( ! $f.find( '[name=con_sponsor]' ).is( ':checked' ) ) { return null; }
		return window.GS_SPONSOR_ORA || null;
	}
	$( document ).on( 'click', '.gs-aeroplanino-anteprima', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-aeroplanino' );
		var $msg = $f.find( '.gs-aeroplanino-msg' );
		var testo = $.trim( $f.find( '[name=testo]' ).val() );
		if ( ! testo ) { $msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi un messaggio prima di vedere l\'anteprima.' ); return; }
		// Solo visivo: nessuna chiamata al server, nessun invio reale alle sfogline.
		if ( typeof gsAllarmeVolo === 'function' ) { gsAllarmeVolo( '🛩️ ' + testo, 1, '', '', gsAeroplaninoSponsorCorrente( $f ) ); }
		$msg.removeClass( 'ok err' ).text( 'Anteprima: così lo vedranno le sfogline.' );
	} );
	$( document ).on( 'click', '.gs-aeroplanino-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-aeroplanino' );
		var $msg = $f.find( '.gs-aeroplanino-msg' );
		var testo = $.trim( $f.find( '[name=testo]' ).val() );
		var conSponsor = $f.find( '[name=con_sponsor]' ).is( ':checked' );
		if ( ! testo ) { $msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi un messaggio prima di inviarlo.' ); return; }
		if ( ! window.confirm( 'Inviare questo messaggio a TUTTE le sfogline collegate in questo momento?' ) ) { return; }
		$msg.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, { action: 'gs_aeroplanino_invia', nonce: GS_AJAX.nonce, testo: testo, con_sponsor: conSponsor ? 1 : '' } )
			.done( function ( res ) {
				$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) {
					$f.find( '[name=testo]' ).val( '' );
					// Lo mostra subito su questo dispositivo, senza aspettare il
					// prossimo giro di interrogazione (fino a 15s), e segna il
					// timestamp come già visto perché quel giro non lo rifaccia
					// volare una seconda volta.
					if ( typeof gsAllarmeVolo === 'function' ) { gsAllarmeVolo( '🛩️ ' + testo, 2, '', '', conSponsor ? gsAeroplaninoSponsorCorrente( $f ) : null ); }
					if ( window.gsAeroplaninoSegnaVisto && res.data && res.data.ts ) { window.gsAeroplaninoSegnaVisto( res.data.ts ); }
					// Aggiunge subito la riga nello storico qui sotto, senza ricaricare la pagina.
					var $tbody = $( '#gs-aeroplanino-storico tbody' );
					if ( $tbody.length && res.data ) {
						var $riga = $( '<tr><td></td><td></td><td></td><td></td><td></td></tr>' );
						$riga.find( 'td' ).eq( 0 ).text( res.data.quando || '' );
						$riga.find( 'td' ).eq( 1 ).text( testo );
						$riga.find( 'td' ).eq( 2 ).text( ( conSponsor && window.GS_SPONSOR_ORA ) ? ( window.GS_SPONSOR_ORA.nome || '—' ) : '—' );
						$riga.find( 'td' ).eq( 3 ).text( res.data.autore || '—' );
						$riga.find( 'td' ).eq( 4 ).text( res.data.n || 0 );
						$tbody.prepend( $riga );
						if ( window.gsInitPagination ) { window.gsInitPagination( $tbody.closest( '.gs-paginate' ) ); }
					}
				}
			} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-aeroplanino-cancella', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-aeroplanino' );
		$f.find( '[name=testo]' ).val( '' );
		$f.find( '.gs-aeroplanino-msg' ).removeClass( 'ok err' ).text( '' );
	} );

	// --- Logo sponsor dalla libreria media di WP (Aeroplanino e Palloncini:
	// stesso pulsante, stesso comportamento, il campo "foto" più vicino nel
	// form riceve l'indirizzo scelto — richiesto da Ennio il 18/08/2026). La
	// libreria di WordPress apre da sola sia "Libreria media" che "Carica
	// file": non serve un secondo pulsante per il caricamento dal computer,
	// è la stessa finestra. ---
	$( document ).on( 'click', '.gs-sponsor-foto-media', function ( e ) {
		e.preventDefault();
		var $campo = $( this ).closest( 'p' ).find( '.gs-sponsor-foto-campo' );
		if ( typeof wp === 'undefined' || ! wp.media ) {
			window.alert( 'Libreria media non disponibile su questa pagina.' );
			return;
		}
		var frame = wp.media( { title: 'Scegli il logo dello sponsor', library: { type: 'image' }, multiple: false, button: { text: 'Usa questo logo' } } );
		frame.on( 'select', function () {
			var att = frame.state().get( 'selection' ).first().toJSON();
			$campo.val( att.url );
		} );
		frame.open();
	} );

	// --- Sponsor, ognuno col suo periodo (Aeroplanino) ---
	$( document ).on( 'change', '.gs-aeroplanino-sponsor-tipo', function () {
		$( this ).closest( '.gs-form-aeroplanino-sponsor' ).find( '.gs-aeroplanino-sponsor-campo-periodo' ).toggle( 'periodo' === $( this ).val() );
	} );
	$( document ).on( 'click', '.gs-aeroplanino-sponsor-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-aeroplanino-sponsor' );
		var $msg = $f.find( '.gs-aeroplanino-sponsor-msg' );
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_aeroplanino_sponsor_salva', nonce: GS_AJAX.nonce,
			nome: $f.find( '[name=nome]' ).val(), foto: $f.find( '[name=foto]' ).val(), url: $f.find( '[name=url]' ).val(),
			tipo: $f.find( '[name=tipo]' ).val(),
			data_inizio: $f.find( '[name=data_inizio]' ).val(), data_fine: $f.find( '[name=data_fine]' ).val(),
			ripeti_ogni_anno: $f.find( '[name=ripeti_ogni_anno]' ).is( ':checked' ) ? 1 : ''
		} ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success && res.data && res.data.html ) {
				$( '#gs-aeroplanino-sponsors-lista' ).replaceWith( res.data.html );
				$f.find( '[name=nome], [name=foto], [name=url], [name=data_inizio], [name=data_fine]' ).val( '' );
				$f.find( '[name=ripeti_ogni_anno]' ).prop( 'checked', false );
				// La pagina non si ricarica: window.GS_SPONSOR_ORA resta quello
				// calcolato al caricamento finché non si ricarica la pagina — un
				// nuovo sponsor appena aggiunto compare nell'elenco qui sotto
				// subito, ma serve un ricaricamento perché lo striscione lo usi.
			}
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-aeroplanino-sponsor-toggle', function () {
		var $btn = $( this );
		$.post( GS_AJAX.url, { action: 'gs_aeroplanino_sponsor_toggle', nonce: GS_AJAX.nonce, id: $btn.data( 'id' ) } )
			.done( function ( res ) {
				if ( res && res.success && res.data && res.data.html ) { $( '#gs-aeroplanino-sponsors-lista' ).replaceWith( res.data.html ); }
			} );
	} );
	$( document ).on( 'click', '.gs-aeroplanino-sponsor-elimina', function () {
		if ( ! window.confirm( 'Eliminare questo sponsor?' ) ) { return; }
		var $btn = $( this );
		$.post( GS_AJAX.url, { action: 'gs_aeroplanino_sponsor_elimina', nonce: GS_AJAX.nonce, id: $btn.data( 'id' ) } )
			.done( function ( res ) {
				if ( res && res.success && res.data && res.data.html ) { $( '#gs-aeroplanino-sponsors-lista' ).replaceWith( res.data.html ); }
			} );
	} );

	// --- Dimensione del logo sponsor sullo striscione — regolabile a piacere ---
	$( document ).on( 'input', '.gs-aereo-logo-dim-range', function () {
		$( this ).closest( 'p' ).find( '.gs-aereo-logo-dim-out' ).text( $( this ).val() + 'px' );
	} );
	$( document ).on( 'click', '.gs-aereo-logo-dim-salva', function () {
		var $riga = $( this ).closest( 'p' );
		var $msg = $riga.find( '.gs-aereo-logo-dim-msg' );
		var dim = $riga.find( '.gs-aereo-logo-dim-range' ).val();
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_aereo_logo_dimensione_salva', nonce: GS_AJAX.nonce, dimensione: dim } )
			.done( function ( res ) {
				$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { GS_AJAX.aereo_logo_dimensione = dim; } // vale da subito, senza ricaricare la pagina
			} )
			.fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Sponsor, ognuno col suo periodo (Palloncini) — stessa identica logica
	// dell'Aeroplanino qui sopra, elenco separato (#gs-palloncini-sponsors-lista). ---
	$( document ).on( 'change', '.gs-palloncini-sponsor-tipo', function () {
		$( this ).closest( '.gs-form-palloncini-sponsor' ).find( '.gs-palloncini-sponsor-campo-periodo' ).toggle( 'periodo' === $( this ).val() );
	} );
	$( document ).on( 'click', '.gs-palloncini-sponsor-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-palloncini-sponsor' );
		var $msg = $f.find( '.gs-palloncini-sponsor-msg' );
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_palloncini_sponsor_salva', nonce: GS_AJAX.nonce,
			nome: $f.find( '[name=nome]' ).val(), foto: $f.find( '[name=foto]' ).val(), url: $f.find( '[name=url]' ).val(),
			tipo: $f.find( '[name=tipo]' ).val(),
			data_inizio: $f.find( '[name=data_inizio]' ).val(), data_fine: $f.find( '[name=data_fine]' ).val(),
			ripeti_ogni_anno: $f.find( '[name=ripeti_ogni_anno]' ).is( ':checked' ) ? 1 : ''
		} ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success && res.data && res.data.html ) {
				$( '#gs-palloncini-sponsors-lista' ).replaceWith( res.data.html );
				$f.find( '[name=nome], [name=foto], [name=url], [name=data_inizio], [name=data_fine]' ).val( '' );
				$f.find( '[name=ripeti_ogni_anno]' ).prop( 'checked', false );
				// Come per l'Aeroplanino: la pagina non si ricarica, quindi
				// window.GS_SPONSOR_PALLONCINI_ORA e il checkbox "con sponsor"
				// restano quelli calcolati al caricamento finché non si ricarica.
			}
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-palloncini-sponsor-toggle', function () {
		var $btn = $( this );
		$.post( GS_AJAX.url, { action: 'gs_palloncini_sponsor_toggle', nonce: GS_AJAX.nonce, id: $btn.data( 'id' ) } )
			.done( function ( res ) {
				if ( res && res.success && res.data && res.data.html ) { $( '#gs-palloncini-sponsors-lista' ).replaceWith( res.data.html ); }
			} );
	} );
	$( document ).on( 'click', '.gs-palloncini-sponsor-elimina', function () {
		if ( ! window.confirm( 'Eliminare questo sponsor?' ) ) { return; }
		var $btn = $( this );
		$.post( GS_AJAX.url, { action: 'gs_palloncini_sponsor_elimina', nonce: GS_AJAX.nonce, id: $btn.data( 'id' ) } )
			.done( function ( res ) {
				if ( res && res.success && res.data && res.data.html ) { $( '#gs-palloncini-sponsors-lista' ).replaceWith( res.data.html ); }
			} );
	} );

	// --- Programmazione automatica (Aeroplanino) ---
	function gsAeroplaninoProgrammaCampi( $f ) {
		var tipo = $f.find( '[name=tipo]' ).val();
		$f.find( '.gs-aeroplanino-campo-data' ).toggle( 'una_volta' === tipo );
		$f.find( '.gs-aeroplanino-campo-mese-giorno' ).toggle( 'ogni_anno' === tipo );
		$f.find( '.gs-aeroplanino-campo-giorno-mese' ).toggle( 'ogni_mese' === tipo );
		$f.find( '.gs-aeroplanino-campo-giorno-settimana' ).toggle( 'ogni_settimana' === tipo );
		$f.find( '.gs-aeroplanino-campo-ora-min' ).toggle( 'a_ripetizione' !== tipo );
		$f.find( '.gs-aeroplanino-campo-ripetizione' ).toggle( 'a_ripetizione' === tipo );
	}
	$( document ).on( 'change', '.gs-aeroplanino-programma-tipo', function () {
		gsAeroplaninoProgrammaCampi( $( this ).closest( '.gs-form-aeroplanino-programma' ) );
	} );
	$( document ).on( 'click', '.gs-aeroplanino-programma-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-aeroplanino-programma' );
		var $msg = $f.find( '.gs-aeroplanino-programma-msg' );
		var testo = $.trim( $f.find( '[name=testo]' ).val() );
		if ( ! testo ) { $msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi un messaggio prima di programmarlo.' ); return; }
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		var tipo = $f.find( '[name=tipo]' ).val();
		var giornoMese = $f.find( '[name=giorno_mese]' ).val() || '';
		var dati = {
			action: 'gs_aeroplanino_programma_salva', nonce: GS_AJAX.nonce,
			testo: testo,
			con_sponsor: $f.find( '[name=con_sponsor]' ).is( ':checked' ) ? 1 : '',
			tipo: tipo,
			ora_min: $f.find( '[name=ora_min]' ).val(),
			data: $f.find( '[name=data]' ).val(),
			giorno_mese: giornoMese,
			giorno: $f.find( '[name=giorno]' ).val(),
			giorno_settimana: $f.find( '[name=giorno_settimana]' ).val(),
			ogni_minuti: $f.find( '[name=ogni_minuti]' ).val(),
			durata_minuti: $f.find( '[name=durata_minuti]' ).val()
		};
		$.post( GS_AJAX.url, dati ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success && res.data && res.data.html ) {
				$( '#gs-aeroplanino-programmati-lista' ).replaceWith( res.data.html );
				$f.find( '[name=testo]' ).val( '' );
			}
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-aeroplanino-programma-toggle', function () {
		var $btn = $( this );
		$.post( GS_AJAX.url, { action: 'gs_aeroplanino_programma_toggle', nonce: GS_AJAX.nonce, id: $btn.data( 'id' ) } )
			.done( function ( res ) {
				if ( res && res.success && res.data && res.data.html ) {
					$( '#gs-aeroplanino-programmati-lista' ).replaceWith( res.data.html );
				}
			} );
	} );
	$( document ).on( 'click', '.gs-aeroplanino-programma-elimina', function () {
		if ( ! window.confirm( 'Eliminare questa programmazione?' ) ) { return; }
		var $btn = $( this );
		$.post( GS_AJAX.url, { action: 'gs_aeroplanino_programma_elimina', nonce: GS_AJAX.nonce, id: $btn.data( 'id' ) } )
			.done( function ( res ) {
				if ( res && res.success && res.data && res.data.html ) {
					$( '#gs-aeroplanino-programmati-lista' ).replaceWith( res.data.html );
				}
			} );
	} );

	// --- Programmazione automatica (Palloncini) — stessa identica logica ---
	function gsPalloncimiProgrammaCampi( $f ) {
		var tipo = $f.find( '[name=tipo]' ).val();
		$f.find( '.gs-palloncini-campo-data' ).toggle( 'una_volta' === tipo );
		$f.find( '.gs-palloncini-campo-mese-giorno' ).toggle( 'ogni_anno' === tipo );
		$f.find( '.gs-palloncini-campo-giorno-mese' ).toggle( 'ogni_mese' === tipo );
		$f.find( '.gs-palloncini-campo-giorno-settimana' ).toggle( 'ogni_settimana' === tipo );
		$f.find( '.gs-palloncini-campo-ora-min' ).toggle( 'a_ripetizione' !== tipo );
		$f.find( '.gs-palloncini-campo-ripetizione' ).toggle( 'a_ripetizione' === tipo );
	}
	$( document ).on( 'change', '.gs-palloncini-programma-tipo', function () {
		gsPalloncimiProgrammaCampi( $( this ).closest( '.gs-form-palloncini-programma' ) );
	} );
	$( document ).on( 'click', '.gs-palloncini-programma-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-palloncini-programma' );
		var $msg = $f.find( '.gs-palloncini-programma-msg' );
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		var giornoMese = $f.find( '[name=giorno_mese]' ).val() || '';
		var dati = {
			action: 'gs_palloncini_programma_salva', nonce: GS_AJAX.nonce,
			motivo: $f.find( '[name=motivo]' ).val(),
			con_sponsor: $f.find( '[name=con_sponsor]' ).is( ':checked' ) ? 1 : '',
			distribuzione: $f.find( 'input[name=distribuzione]:checked' ).val() || 'uno',
			tipo: $f.find( '[name=tipo]' ).val(),
			ora_min: $f.find( '[name=ora_min]' ).val(),
			data: $f.find( '[name=data]' ).val(),
			giorno_mese: giornoMese,
			giorno: $f.find( '[name=giorno]' ).val(),
			giorno_settimana: $f.find( '[name=giorno_settimana]' ).val(),
			ogni_minuti: $f.find( '[name=ogni_minuti]' ).val(),
			durata_minuti: $f.find( '[name=durata_minuti]' ).val()
		};
		$.post( GS_AJAX.url, dati ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success && res.data && res.data.html ) {
				$( '#gs-palloncini-programmati-lista' ).replaceWith( res.data.html );
			}
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-palloncini-programma-toggle', function () {
		var $btn = $( this );
		$.post( GS_AJAX.url, { action: 'gs_palloncini_programma_toggle', nonce: GS_AJAX.nonce, id: $btn.data( 'id' ) } )
			.done( function ( res ) {
				if ( res && res.success && res.data && res.data.html ) {
					$( '#gs-palloncini-programmati-lista' ).replaceWith( res.data.html );
				}
			} );
	} );
	$( document ).on( 'click', '.gs-palloncini-programma-elimina', function () {
		if ( ! window.confirm( 'Eliminare questa programmazione?' ) ) { return; }
		var $btn = $( this );
		$.post( GS_AJAX.url, { action: 'gs_palloncini_programma_elimina', nonce: GS_AJAX.nonce, id: $btn.data( 'id' ) } )
			.done( function ( res ) {
				if ( res && res.success && res.data && res.data.html ) {
					$( '#gs-palloncini-programmati-lista' ).replaceWith( res.data.html );
				}
			} );
	} );

	// -------------------------------------------------------------------------
	// L'Esperto Risponde — consulenze private a token (dal 2026-07-30)
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-invia-domanda-privata', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-domanda-privata' );
		var $msg = $f.find( '.gs-domanda-privata-msg' );
		$msg.removeClass( 'ok err' ).text( 'Invio…' );
		var data = $f.find( 'input, textarea' ).serializeArray();
		var payload = { action: 'gs_esperto_domanda_privata', nonce: GS_AJAX.nonce, canale: $f.data( 'canale' ) };
		$.each( data, function ( i, o ) { payload[ o.name ] = o.value; } );
		$.post( GS_AJAX.url, payload ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Pannello: limiti, creazione e gestione canali ---
	$( document ).on( 'click', '.gs-salva-limiti', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-limiti' );
		var $msg = $f.find( '.gs-limiti-msg' );
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_esperto_salva_limiti', nonce: GS_AJAX.nonce,
			giorno: $f.find( '[name=giorno]' ).val(), settimana: $f.find( '[name=settimana]' ).val(),
			mese: $f.find( '[name=mese]' ).val(), cooldown: $f.find( '[name=cooldown]' ).val()
		} ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-crea-canale', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-nuovo-canale' );
		var $msg = $f.find( '.gs-canale-msg' );
		$msg.removeClass( 'ok err' ).text( 'Creazione…' );
		$.post( GS_AJAX.url, { action: 'gs_esperto_crea_canale', nonce: GS_AJAX.nonce, nome: $f.find( '[name=nome]' ).val(), esperto: $f.find( '[name=esperto]' ).val(), costo_token: $f.find( '[name=costo_token]' ).val() } )
			.done( function ( res ) {
				$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-salva-canale', function ( e ) {
		e.preventDefault();
		var $tr = $( this ).closest( 'tr' );
		var $msg = $tr.find( '.gs-canale-riga-msg' );
		$msg.removeClass( 'ok err' ).text( '…' );
		$.post( GS_AJAX.url, {
			action: 'gs_esperto_salva_canale', nonce: GS_AJAX.nonce, slug: $tr.data( 'slug' ),
			nome: $tr.find( '.gs-can-nome' ).val(), esperto: $tr.find( '.gs-can-esperto' ).val(),
			costo_token: $tr.find( '.gs-can-costo' ).val(),
			attivo: $tr.find( '.gs-can-attivo' ).is( ':checked' ) ? 1 : 0
		} ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	$( document ).on( 'click', '.gs-del-canale', function ( e ) {
		e.preventDefault();
		if ( ! window.confirm( 'Eliminare il canale? Le sue domande saranno spostate nel cestino (recuperabili), il canale stesso no.' ) ) { return; }
		var $tr = $( this ).closest( 'tr' );
		$.post( GS_AJAX.url, { action: 'gs_esperto_del_canale', nonce: GS_AJAX.nonce, slug: $tr.data( 'slug' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } else { alert( res && res.data ? res.data.message : 'Errore.' ); } } );
	} );

	// -------------------------------------------------------------------------
	// Pannello Pagamenti — Token per le consulenze private
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-token-imp-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-token-imp' );
		var $msg = $f.find( '.gs-token-imp-msg' );
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_token_salva_impostazioni', nonce: GS_AJAX.nonce,
			valore_euro: $f.find( '[name=valore_euro]' ).val(), giorni_rimborso: $f.find( '[name=giorni_rimborso]' ).val(),
			costo_vetrina: $f.find( '[name=costo_vetrina]' ).val(),
			rif_artigiani: $f.find( '[name=rif_artigiani]' ).val(), rif_scuole: $f.find( '[name=rif_scuole]' ).val()
		} ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			// Aggiorna il valore usato dal calcolo automatico euro -> token, senza ricaricare.
			if ( res && res.success ) {
				$( '.gs-form-token-accredita' ).attr( 'data-valore-token', $f.find( '[name=valore_euro]' ).val() );
			}
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// Calcolo automatico dei token dall'importo, in base al valore di un
	// token impostato sopra — resta comunque modificabile a mano.
	$( document ).on( 'input', '.gs-token-importo', function () {
		var $f = $( this ).closest( '.gs-form-token-accredita' );
		var valore = parseFloat( $f.data( 'valore-token' ) ) || 5;
		var importo = parseFloat( $( this ).val() ) || 0;
		var token = Math.max( 1, Math.floor( importo / valore ) );
		$f.find( '.gs-token-quanti' ).val( token );
	} );

	// Identificativo dell'accredito, generato una volta sola al caricamento
	// della pagina (il form è unico e sempre visibile, non un modulo che si
	// apre/chiude come quello dei pagamenti del calendario) — il server lo
	// usa per bloccare un doppio invio prima di sommare.
	$( '.gs-form-token-accredita' ).each( function () {
		$( this ).data( 'rif', 'r' + Date.now() + Math.random().toString( 36 ).slice( 2 ) );
	} );
	$( document ).on( 'click', '.gs-token-accredita', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		if ( $btn.prop( 'disabled' ) ) { return; }
		var $f = $btn.closest( '.gs-form-token-accredita' );
		var $msg = $f.find( '.gs-token-accredita-msg' );
		if ( ! $f.find( '[name=uid]' ).val() ) { $msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Scegli una sfoglina.' ); return; }
		if ( ! $f.find( '.gs-token-importo' ).val() && ! $.trim( $f.find( '[name=motivo]' ).val() ) ) {
			$msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Senza un importo, scrivi il motivo dell\'assegnazione.' ); return;
		}
		$msg.removeClass( 'ok err' ).text( 'Assegnazione…' );
		$btn.prop( 'disabled', true );
		$.post( GS_AJAX.url, {
			action: 'gs_token_accredita', nonce: GS_AJAX.nonce,
			uid: $f.find( '[name=uid]' ).val(), importo: $f.find( '.gs-token-importo' ).val(),
			token: $f.find( '[name=token]' ).val(), motivo: $f.find( '[name=motivo]' ).val(),
			rif: $f.data( 'rif' ) || ''
		} ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } )
		.always( function () { $btn.prop( 'disabled', false ); } );
	} );

	// -------------------------------------------------------------------------
	// Regia del Gaming — proposte e direttive
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-regia-crea', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-regia-nuova' );
		var $msg = $f.find( '.gs-regia-crea-msg' );
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_regia_crea', nonce: GS_AJAX.nonce, oggetto: $f.find( '[name=oggetto]' ).val(), testo: $f.find( '[name=testo]' ).val() } )
			.done( function ( res ) {
				$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-regia-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-regia-riga' );
		var $msg = $f.find( '.gs-regia-riga-msg' );
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_regia_salva', nonce: GS_AJAX.nonce, id: $f.data( 'id' ),
			stato: $f.find( '[name=stato]' ).val(), note: $f.find( '[name=note]' ).val(), link: $f.find( '[name=link]' ).val()
		} ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-regia-elimina', function ( e ) {
		e.preventDefault();
		if ( ! window.confirm( 'Spostare questa proposta nel cestino? Potrai ripristinarla.' ) ) { return; }
		var $f = $( this ).closest( '.gs-form-regia-riga' );
		$.post( GS_AJAX.url, { action: 'gs_regia_elimina', nonce: GS_AJAX.nonce, id: $f.data( 'id' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } else { alert( res && res.data ? res.data.message : 'Errore.' ); } } );
	} );

	$( document ).on( 'click', '.gs-regia-ripristina', function ( e ) {
		e.preventDefault();
		var $li = $( this ).closest( '.gs-todo-item' );
		$.post( GS_AJAX.url, { action: 'gs_regia_ripristina', nonce: GS_AJAX.nonce, id: $li.data( 'id' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } else { alert( res && res.data ? res.data.message : 'Errore.' ); } } );
	} );

	// -------------------------------------------------------------------------
	// Le Letture dei Grandi Protagonisti della Cucina
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-lettura-crea', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-lettura-nuova' );
		var $msg = $f.find( '.gs-lettura-crea-msg' );
		$msg.removeClass( 'ok err' ).text( 'Pubblicazione…' );
		$.post( GS_AJAX.url, { action: 'gs_lettura_crea', nonce: GS_AJAX.nonce, titolo: $f.find( '[name=titolo]' ).val(), testo: $f.find( '[name=testo]' ).val() } )
			.done( function ( res ) {
				$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-lettura-elimina', function ( e ) {
		e.preventDefault();
		if ( ! window.confirm( 'Spostare questa lettura nel cestino? Potrai ripristinarla.' ) ) { return; }
		$.post( GS_AJAX.url, { action: 'gs_lettura_elimina', nonce: GS_AJAX.nonce, id: $( this ).data( 'id' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } else { alert( res && res.data ? res.data.message : 'Errore.' ); } } );
	} );

	$( document ).on( 'click', '.gs-lettura-ripristina', function ( e ) {
		e.preventDefault();
		var $li = $( this ).closest( '.gs-todo-item' );
		$.post( GS_AJAX.url, { action: 'gs_lettura_ripristina', nonce: GS_AJAX.nonce, id: $li.data( 'id' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } else { alert( res && res.data ? res.data.message : 'Errore.' ); } } );
	} );

	function gsLetturaCommentoInvia( $f ) {
		var $msg = $f.find( '.gs-lettura-commento-msg' );
		var testo = $f.find( '[name=testo]' ).val();
		if ( ! testo || ! testo.trim() ) { $msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi una risposta.' ); return; }
		$msg.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, $.extend( { action: 'gs_lettura_commento', nonce: GS_AJAX.nonce, id: $f.data( 'id' ), testo: testo }, gsAntispamFields( $f ) ) )
			.done( function ( res ) {
				$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	}
	$( document ).on( 'click', '.gs-lettura-commento-invia', function ( e ) {
		e.preventDefault();
		gsLetturaCommentoInvia( $( this ).closest( '.gs-form-lettura-commento' ) );
	} );
	// Invio manda la risposta (come WhatsApp), Maiusc+Invio va a capo.
	$( document ).on( 'keydown', '.gs-form-lettura-commento [name=testo]', function ( e ) {
		if ( 'Enter' === e.key && ! e.shiftKey ) {
			e.preventDefault();
			gsLetturaCommentoInvia( $( this ).closest( '.gs-form-lettura-commento' ) );
		}
	} );

	$( document ).on( 'click', '.gs-lettura-commento-elimina', function ( e ) {
		e.preventDefault();
		if ( ! window.confirm( 'Spostare questa risposta nel cestino?' ) ) { return; }
		var $b = $( this );
		$.post( GS_AJAX.url, { action: 'gs_lettura_commento_elimina', nonce: GS_AJAX.nonce, lettura: $b.data( 'lettura' ), commento: $b.data( 'commento' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } else { alert( res && res.data ? res.data.message : 'Errore.' ); } } );
	} );

	$( document ).on( 'click', '.gs-iscrizione-lettore-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-iscrizione-lettore' );
		var $msg = $f.find( '.gs-iscrizione-lettore-msg' );
		$msg.removeClass( 'ok err' ).text( 'Iscrizione…' );
		$.post( GS_AJAX.url, $.extend( {
			action: 'gs_registrati_lettore', nonce: GS_AJAX.nonce,
			nome: $f.find( '[name=nome]' ).val(), email: $f.find( '[name=email]' ).val(),
			username: $f.find( '[name=username]' ).val(), password: $f.find( '[name=password]' ).val()
		}, gsAntispamFields( $f ) ) )
			.done( function ( res ) {
				$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { $f.find( 'input' ).val( '' ); }
			} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-lettore-blocca', function ( e ) {
		e.preventDefault();
		var $tr = $( this ).closest( 'tr' );
		var $msg = $tr.find( '.gs-lettore-msg' );
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_lettore_modera', nonce: GS_AJAX.nonce, uid: $tr.data( 'uid' ), azione: 'blocca', fino: $tr.find( '.gs-lettore-fino' ).val() } )
			.done( function ( res ) {
				$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-lettore-riattiva', function ( e ) {
		e.preventDefault();
		var $tr = $( this ).closest( 'tr' );
		var $msg = $tr.find( '.gs-lettore-msg' );
		$.post( GS_AJAX.url, { action: 'gs_lettore_modera', nonce: GS_AJAX.nonce, uid: $tr.data( 'uid' ), azione: 'riattiva' } )
			.done( function ( res ) {
				$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// -------------------------------------------------------------------------
	// Moderazione di tutte le chat
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.gs-mod-elimina', function ( e ) {
		e.preventDefault();
		if ( ! window.confirm( 'Spostare questo messaggio nel cestino?' ) ) { return; }
		var $b = $( this );
		var $msg = $b.closest( 'p' ).find( '.gs-mod-msg' );
		$msg.removeClass( 'ok err' ).text( 'Eliminazione…' );
		$.post( GS_AJAX.url, { action: 'gs_mod_elimina', nonce: GS_AJAX.nonce, sistema: $b.data( 'sistema' ), post: $b.data( 'post' ), indice: $b.data( 'indice' ) } )
			.done( function ( res ) {
				$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { $b.closest( '.gs-inbox-item' ).slideUp( 200, function () { $( this ).remove(); } ); }
			} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'change', '.gs-mod-filtro-sistema', function () {
		var v = $( this ).val();
		var $t = $( this ).closest( '.gs-box' ).find( '.gs-mod-lista' );
		if ( ! $t.length ) { return; }
		$t.find( '> .gs-inbox-item' ).each( function () {
			$( this ).toggleClass( 'gs-hide-livello', v !== '' && $( this ).data( 'sistema' ) !== v );
		} );
		if ( $t.hasClass( 'gs-paginate' ) ) { $t.data( 'gsPage', 1 ); gsRepaginate( $t ); }
	} );

	// --- Conversazioni private ---
	$( document ).on( 'click', '.gs-conv-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-conv' );
		var $msg = $f.find( '.gs-conv-msg-out' );
		gsSendMsg( 'gs_conv_invia', $.extend( { conv: $f.data( 'id' ), testo: $f.find( '[name=testo]' ).val() }, gsAntispamFields( $f ) ), $f.find( '.gs-msg-file' ), $msg, true );
	} );

	// Rimborso a mano del token di una domanda privata senza risposta
	// (l'automatico via cron resta come rete di sicurezza dopo N giorni).
	$( document ).on( 'click', '.gs-conv-rimborsa', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $msg = $btn.closest( 'p' ).find( '.gs-conv-rimborsa-msg' );
		$msg.removeClass( 'ok err' ).text( 'Rimborso…' );
		$.post( GS_AJAX.url, { action: 'gs_conv_rimborsa_token', nonce: GS_AJAX.nonce, conv: $btn.data( 'conv' ), msg: $btn.data( 'msg' ) } )
			.done( function ( res ) {
				$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-conv-nuova-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-conv-nuova' );
		var $msg = $f.find( '.gs-conv-nuova-msg' );
		$msg.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, { action: 'gs_conv_nuova', nonce: GS_AJAX.nonce, canale: $f.data( 'canale' ), sfoglina: $f.find( '[name=sfoglina]' ).val(), testo: $f.find( '[name=testo]' ).val() } )
			.done( function ( res ) {
				$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { $f.find( '[name=testo]' ).val( '' ); }
			} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-salva-permessi', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '.gs-conv-perm' );
		var $msg = $box.find( '.gs-perm-msg' );
		var sel = $box.find( '.gs-perm-sfogline' ).val() || [];
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_conv_salva_permessi', nonce: GS_AJAX.nonce, esperto: $box.data( 'esperto' ), sfogline: sel } )
			.done( function ( res ) { $msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); } )
			.fail( function () { $msg.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	$( document ).on( 'click', '.gs-conv-admin-crea', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-conv-admin' );
		var $msg = $f.find( '.gs-conv-admin-msg' );
		$msg.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, { action: 'gs_conv_admin_crea', nonce: GS_AJAX.nonce, esperto: $f.find( '[name=esperto]' ).val(), sfoglina: $f.find( '[name=sfoglina]' ).val(), testo: $f.find( '[name=testo]' ).val() } )
			.done( function ( res ) {
				$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	$( document ).on( 'click', '.gs-conv-admin-del', function ( e ) {
		e.preventDefault();
		if ( ! window.confirm( 'Spostare questa conversazione nel cestino? Potrai ripristinarla dal cestino di WordPress.' ) ) { return; }
		var $tr = $( this ).closest( 'tr' );
		$.post( GS_AJAX.url, { action: 'gs_conv_admin_del', nonce: GS_AJAX.nonce, conv: $tr.data( 'conv' ) } )
			.done( function ( res ) { if ( res && res.success ) { $tr.fadeOut( 200, function () { $( this ).remove(); } ); } else { alert( res && res.data ? res.data.message : 'Errore.' ); } } );
	} );

	// Elimina/ripristina UN messaggio dentro una conversazione (punto 6, Ennio,
	// delega "finisci tutto" del 22/08/2026). ELIMINARE richiede tre clic
	// ravvicinati (stesso schema del blackout rapido, Ennio 22/08/2026:
	// "sempre solito metodo dei tre click"); RIPRISTINARE resta a un clic solo.
	$( document ).on( 'click', '.gs-conv-msg-toggle', function ( e ) {
		e.preventDefault();
		var $btn    = $( this );
		var $esito  = $btn.next( '.gs-conv-msg-toggle-esito' );
		var azione  = $btn.data( 'azione' );

		if ( 'elimina' === azione ) {
			var contatore = ( $btn.data( 'gs-clic' ) || 0 ) + 1;
			clearTimeout( $btn.data( 'gs-clic-timer' ) );
			if ( contatore < 3 ) {
				if ( ! $btn.data( 'gs-testo-originale' ) ) { $btn.data( 'gs-testo-originale', $btn.text() ); }
				$btn.data( 'gs-clic', contatore );
				$btn.text( 'Clicca ancora ' + ( 3 - contatore ) + ( 3 - contatore === 1 ? ' volta' : ' volte' ) );
				var timer = setTimeout( function () {
					$btn.data( 'gs-clic', 0 );
					$btn.text( $btn.data( 'gs-testo-originale' ) || 'Elimina' );
				}, 2500 );
				$btn.data( 'gs-clic-timer', timer );
				return;
			}
			$btn.data( 'gs-clic', 0 );
		}

		$btn.prop( 'disabled', true );
		$esito.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_conv_msg_toggle', nonce: GS_AJAX.nonce, conv: $btn.data( 'conv' ), msg: $btn.data( 'msg' ), azione: azione } )
			.done( function ( res ) {
				var ok = res && res.success;
				$esito.removeClass( 'ok err' ).addClass( ok ? 'ok' : 'err' ).text( ( res && res.data && res.data.message ) || ( ok ? 'Fatto.' : 'Errore.' ) );
				if ( ok ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} )
			.fail( function () { $esito.removeClass( 'ok' ).addClass( 'err' ).text( 'Errore di connessione.' ); } )
			.always( function () { $btn.prop( 'disabled', false ); } );
	} );

	$( document ).on( 'click', '.gs-conv-sfoglina-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-conv-sfoglina' );
		var $msg = $f.find( '.gs-conv-sf-msg' );
		$msg.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, { action: 'gs_conv_sfoglina_richiesta', nonce: GS_AJAX.nonce, canale: $f.data( 'canale' ), esperto: $f.data( 'esperto' ), testo: $f.find( '[name=testo]' ).val() } )
			.done( function ( res ) {
				$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { $f.find( '[name=testo]' ).val( '' ); }
			} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-salva-conv-mode', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-conv-mode' );
		var $msg = $f.find( '.gs-conv-mode-msg' );
		$msg.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_conv_salva_mode', nonce: GS_AJAX.nonce, mode: $f.find( '[name=mode]:checked' ).val() } )
			.done( function ( res ) { $msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); } )
			.fail( function () { $msg.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	$( document ).on( 'click', '.gs-conv-approva, .gs-conv-rifiuta', function ( e ) {
		e.preventDefault();
		var approva = $( this ).hasClass( 'gs-conv-approva' );
		if ( ! approva && ! window.confirm( 'Rifiutare questa richiesta? Sarà spostata nel cestino.' ) ) { return; }
		var $tr = $( this ).closest( 'tr' );
		$.post( GS_AJAX.url, { action: approva ? 'gs_conv_approva' : 'gs_conv_rifiuta', nonce: GS_AJAX.nonce, conv: $tr.data( 'conv' ) } )
			.done( function ( res ) {
				if ( res && res.success ) { $tr.fadeOut( 250, function () { $( this ).remove(); } ); }
				else { alert( res && res.data ? res.data.message : 'Errore.' ); }
			} ).fail( function () { alert( 'Errore di connessione.' ); } );
	} );

	// --- Compleanni: auguri, data di nascita, controllo admin ---
	$( document ).on( 'click', '.gs-augurio-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-augurio' );
		var $m = $f.find( '.gs-augurio-msg' );
		var fields = { target: $f.data( 'target' ), testo: $f.find( '[name=testo]' ).val() };
		$f.find( 'input, textarea' ).each( function () { if ( this.name && this.name.indexOf( 'gs_' ) === 0 ) { fields[ this.name ] = $( this ).val(); } } );
		gsSendMsg( 'gs_augurio_invia', fields, $f.find( '.gs-msg-file' ), $m, true );
	} );

	$( document ).on( 'click', '.gs-bday-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-bday' );
		var $m = $f.find( '.gs-bday-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_bday_salva', nonce: GS_AJAX.nonce, nascita: $f.find( '[name=nascita]' ).val() } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	$( document ).on( 'click', '.gs-bday-toggle', function ( e ) {
		e.preventDefault();
		var $b = $( this );
		$.post( GS_AJAX.url, { action: 'gs_bday_toggle', nonce: GS_AJAX.nonce, uid: $b.data( 'uid' ), hide: $b.data( 'hide' ) } )
			.done( function ( res ) {
				if ( res && res.success ) {
					var hide = $b.data( 'hide' );
					$b.data( 'hide', hide ? 0 : 1 ).text( hide ? 'Mostra vetrina' : 'Oscura vetrina' );
					$b.siblings( '.gs-bday-stato' ).text( hide ? 'oscurata' : 'visibile' );
				} else { alert( res && res.data ? res.data.message : 'Errore.' ); }
			} ).fail( function () { alert( 'Errore.' ); } );
	} );

	$( document ).on( 'click', '.gs-bday-admin-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-bday-admin' );
		var $m = $f.find( '.gs-bday-admin-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_bday_admin_salva', nonce: GS_AJAX.nonce, uid: $f.find( '[name=uid]' ).val(), nascita: $f.find( '[name=nascita]' ).val() } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	// --- Voce "Accedi" (o "La Mia Sfoglia" se già collegata) nel menu del sito ---
	// Aggiunta come vera <li> dentro #navigation invece che con CSS a parte:
	// diventa così una voce del menu a tutti gli effetti e segue automaticamente
	// lo stesso centraggio del tema, senza dover ricalcolare a mano le posizioni
	// (il tema ha l'intestazione "sticky", non "fixed": qualunque calcolo di
	// posizione assoluta andrebbe rifatto a ogni aggiornamento del tema).
	//
	// Lampeggio quando c'è qualcosa di non letto (richiesto da Ennio il
	// 21/08/2026): la linguetta laterale lampeggia già dal 30/07, ma solo
	// dentro al pannello — da qualunque altra pagina del sito non c'era
	// alcun segnale. Il totale è alimentato dagli stessi due polling di
	// Messaggi/Conversazioni più sotto (già in esecuzione ogni 15s), così
	// il lampeggio si accende/spegne da solo senza ricaricare la pagina.
	var gsNonLettiMenu = { msg: 0, conv: 0 };
	function gsAggiornaLampeggioMenuAccedi() {
		var tot = ( gsNonLettiMenu.msg || 0 ) + ( gsNonLettiMenu.conv || 0 );
		$( '.gs-nav-accedi' ).toggleClass( 'gs-lampeggia-rosso', tot > 0 );
	}
	$( function () {
		if ( typeof GS_AJAX === 'undefined' || ! GS_AJAX.login_url ) { return; }
		var $nav = $( '#navigation' ).first();
		// Il controllo cercava dentro #navigation, ma il pulsante viene
		// inserito FUORI (vedi $ancora.after($riga) più sotto): da quando è
		// stato spostato sotto il menu, quel controllo non lo trovava più —
		// innocuo finché gaming.js gira una volta sola, ma diventa un doppio
		// pulsante se un plugin di ottimizzazione (SiteGround Optimizer,
		// attivo su questo sito) combina/rimanda gli script ed esegue questo
		// blocco due volte.
		if ( ! $nav.length || $( '.gs-nav-accedi' ).length ) { return; }
		var loggedIn = !! GS_AJAX.logged_in;
		var href = loggedIn ? ( GS_AJAX.dashboard_url || '#' ) : GS_AJAX.login_url;
		var testo = loggedIn ? 'La Mia Sfoglia' : 'Accedi';
		if ( loggedIn && typeof GS_MSG !== 'undefined' && GS_MSG ) {
			gsNonLettiMenu.msg = parseInt( GS_MSG.non_letti, 10 ) || 0;
			gsNonLettiMenu.conv = parseInt( GS_MSG.conv_non_letti, 10 ) || 0;
		}
		// Sotto la barra del menu, centrato (Ennio, 22/08/2026: prima provata
		// dentro la sequenza dei link, ma un pulsante largo in mezzo a un menu
		// che va a capo da solo rischiava di finire su una riga sbagliata su
		// finestre strette). Riga propria, fuori dal flusso dei link: niente
		// più rischio di andare a capo insieme a loro.
		var $riga = $( '<div class="gs-nav-accedi-riga"><a class="gs-nav-accedi" href="' + href + '">' + testo + '</a></div>' );
		var $ancora = $nav.closest( '.menu-primary-navigation-container' );
		if ( $ancora.length ) { $ancora.after( $riga ); } else { $nav.after( $riga ); }
		gsAggiornaLampeggioMenuAccedi();
	} );

	// --- Pannello linguette laterali: apri/chiudi ---
	// Sposto pannello e pulsante come figli diretti di <body>: così nessun
	// contenitore del tema (con transform/overflow) può rompere il position:fixed.
	$( function () {
		var $b = $( 'body' );
		$( '.gs-side-launcher, .gs-side-tabs' ).each( function () {
			if ( this.parentNode !== document.body ) { $b.append( this ); }
		} );
	} );
	$( document ).on( 'click', '.gs-side-launcher', function ( e ) {
		e.preventDefault();
		var open = $( 'body' ).toggleClass( 'gs-tabs-open' ).hasClass( 'gs-tabs-open' );
		$( this ).attr( 'aria-expanded', open ? 'true' : 'false' );
	} );
	// Chiudi cliccando fuori dal pannello
	$( document ).on( 'click', function ( e ) {
		if ( ! $( 'body' ).hasClass( 'gs-tabs-open' ) ) { return; }
		if ( $( e.target ).closest( '.gs-side-tabs, .gs-side-launcher' ).length === 0 ) {
			$( 'body' ).removeClass( 'gs-tabs-open' );
			$( '.gs-side-launcher' ).attr( 'aria-expanded', 'false' );
		}
	} );
	// Chiudi con Esc
	$( document ).on( 'keydown', function ( e ) {
		if ( e.key === 'Escape' ) { $( 'body' ).removeClass( 'gs-tabs-open' ); $( '.gs-side-launcher' ).attr( 'aria-expanded', 'false' ); }
	} );

	// --- Gruppi del menu marrone: un solo gruppo aperto alla volta, si
	// ricorda l'ultimo aperto da una visita all'altra (richiesto da Ennio
	// il 18/08/2026, quando le 35 linguette sono state raggruppate per
	// categoria). Se un link dentro un gruppo è la pagina corrente, quel
	// gruppo si apre da solo al primo caricamento — più utile che ricordare
	// sempre e comunque l'ultimo, che potrebbe non avere nulla a che fare
	// con dove ci si trova ora.
	( function () {
		var CHIAVE = 'gs_side_gruppo_aperto';
		function apriGruppo( $grp, salva ) {
			$( '.gs-side-grp' ).not( $grp ).removeClass( 'aperto' ).find( '.gs-side-grp-btn' ).attr( 'aria-expanded', 'false' );
			$grp.addClass( 'aperto' ).find( '.gs-side-grp-btn' ).attr( 'aria-expanded', 'true' );
			if ( salva ) {
				try { window.localStorage.setItem( CHIAVE, $grp.data( 'grp' ) ); } catch ( e ) {}
			}
		}
		$( document ).on( 'click', '.gs-side-grp-btn', function () {
			var $grp = $( this ).closest( '.gs-side-grp' );
			var giaAperto = $grp.hasClass( 'aperto' );
			$( '.gs-side-grp' ).removeClass( 'aperto' ).find( '.gs-side-grp-btn' ).attr( 'aria-expanded', 'false' );
			if ( ! giaAperto ) { apriGruppo( $grp, true ); }
			else {
				try { window.localStorage.removeItem( CHIAVE ); } catch ( e ) {}
			}
		} );
		$( function () {
			var qui = window.location.href.replace( /#.*$/, '' );
			var $conCorrente = $( '.gs-side-grp' ).filter( function () {
				return $( this ).find( '.gs-side-tab' ).filter( function () { return this.href.replace( /#.*$/, '' ) === qui; } ).length > 0;
			} ).first();
			if ( $conCorrente.length ) { apriGruppo( $conCorrente, false ); return; }
			var ultimo;
			try { ultimo = window.localStorage.getItem( CHIAVE ); } catch ( e ) {}
			if ( ultimo ) {
				var $prec = $( '.gs-side-grp[data-grp="' + ultimo + '"]' );
				if ( $prec.length ) { apriGruppo( $prec, false ); }
			}
		} );
	} )();

	// --- Calendario Corsi ---
	function gsCalPost( data, $msg, reload, delay ) {
		$msg.removeClass( 'ok err' ).text( 'Attendi…' );
		data.nonce = GS_AJAX.nonce;
		// return: alcuni chiamanti (es. il pagamento) devono sapere quando la
		// richiesta è finita per riattivare un pulsante disabilitato — nessun
		// chiamante esistente usava il valore restituito, quindi aggiungerlo
		// non cambia niente per loro.
		return $.post( GS_AJAX.url, data ).done( function ( res ) {
			$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success && reload ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, delay || 900 ); }
		} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	}
	// Popup "Vedi dettagli e prenota" (Ennio, 19/08/2026): clicca la scheda
	// di una data e si apre una finestra dedicata con orario, posti, prezzo,
	// presentazione e il pulsante di prenotazione — invece del testo che
	// stava già dentro la scheda (che resta nel markup ma nascosto, così i
	// pulsanti dentro sono sempre gli stessi elementi: nessuna logica AJAX
	// duplicata, funzionano identici anche dentro il popup).
	var $gsCalModal = null;
	function gsCalModaleCrea() {
		if ( $gsCalModal ) { return $gsCalModal; }
		$gsCalModal = $(
			'<div class="gs-cal-modal-sfondo" style="display:none">' +
				'<div class="gs-cal-modal" role="dialog" aria-modal="true">' +
					'<button type="button" class="gs-cal-modal-chiudi" aria-label="Chiudi">✕</button>' +
					'<div class="gs-cal-modal-testata"></div>' +
					'<div class="gs-cal-modal-corpo"></div>' +
				'</div>' +
			'</div>'
		).appendTo( 'body' );
		return $gsCalModal;
	}
	function gsCalModaleChiudi() {
		if ( $gsCalModal ) { $gsCalModal.hide(); }
		$( 'html' ).removeClass( 'gs-cal-modal-aperto' );
	}
	// Apre il popup a partire dall'ID del corso, cercando la sua scheda
	// (visibile nell'elenco, o nascosta dentro la griglia mensile — vedi
	// gs_cal_griglia_html in PHP) ovunque sia nella pagina: così sia il
	// pulsante "Vedi dettagli e prenota" sia un blocco della griglia aprono
	// esattamente lo stesso popup, con gli stessi dati.
	function gsCalApriPopup( corsoId ) {
		var $scheda = $( '.gs-vc-oggetto[data-corso="' + corsoId + '"]' ).first();
		if ( ! $scheda.length ) { return; }
		var $modal = gsCalModaleCrea();
		$modal.find( '.gs-cal-modal-testata' ).html(
			$scheda.find( '.gs-vc-ph' ).prop( 'outerHTML' ) +
			'<p class="gs-vc-titolo" style="color:#4a3a28 !important">' + $scheda.find( '.gs-vc-titolo' ).html() + '</p>' +
			'<p class="gs-vc-luogo" style="color:#8a7a5c !important">' + $scheda.find( '.gs-vc-luogo' ).html() + '</p>'
		);
		$modal.find( '.gs-cal-modal-corpo' ).html( $scheda.find( '.gs-vc-dettagli' ).html() );
		$modal.show();
		// Blocca lo scorrimento della pagina sotto al popup mentre è aperto
		// (Ennio, 19/08/2026: "il popup si incasina con la pagina") — senza
		// questo, su telefono soprattutto, si poteva scorrere la pagina di
		// sfondo insieme al popup, dando la sensazione che tutto si muovesse
		// insieme in modo confuso.
		$( 'html' ).addClass( 'gs-cal-modal-aperto' );
	}
	$( document ).on( 'click', '.gs-cal-apri-dettagli, .gs-cal-piano-blocco', function () {
		gsCalApriPopup( $( this ).data( 'corso' ) );
	} );
	$( document ).on( 'click', '.gs-cal-modal-chiudi', gsCalModaleChiudi );
	$( document ).on( 'click', '.gs-cal-modal-sfondo', function ( e ) { if ( e.target === this ) { gsCalModaleChiudi(); } } );
	$( document ).on( 'keydown', function ( e ) { if ( 'Escape' === e.key && $gsCalModal && $gsCalModal.is( ':visible' ) ) { gsCalModaleChiudi(); } } );

	// Griglia mensile "Pianificazione dell'Anno" pubblica: selettore
	// dell'anno, mostra/nasconde il blocco corrispondente (niente AJAX, i
	// dati di tutti gli anni sono già nella pagina).
	$( document ).on( 'change', '.gs-cal-piano-anno-select', function () {
		var anno = $( this ).val();
		var $piano = $( this ).closest( '.gs-cal-piano' );
		$piano.find( '.gs-cal-piano-anno-blocco' ).hide();
		$piano.find( '.gs-cal-piano-anno-blocco[data-anno="' + anno + '"]' ).show();
	} );
	// Stesso principio, per il selettore d'anno della Ruota dell'Anno.
	$( document ).on( 'change', '.gs-cal-ruota-anno-select', function () {
		var anno = $( this ).val();
		var $ruota = $( this ).closest( '.gs-cal-ruota' );
		$ruota.find( '.gs-cal-ruota-anno-blocco' ).hide();
		$ruota.find( '.gs-cal-ruota-anno-blocco[data-anno="' + anno + '"]' ).show();
	} );

	// Prenotazione: il messaggio di avvenuto invio resta visibile 3 secondi.
	$( document ).on( 'click', '.gs-cal-prenota', function ( e ) { e.preventDefault(); gsCalPost( { action: 'gs_cal_prenota', corso: $( this ).data( 'corso' ) }, $( this ).siblings( '.gs-cal-msg' ), true, 3000 ); } );
	$( document ).on( 'click', '.gs-cal-disdici', function ( e ) { e.preventDefault(); if ( ! confirm( 'Confermi la disdetta?' ) ) return; gsCalPost( { action: 'gs_cal_disdici', pren: $( this ).data( 'pren' ) }, $( this ).siblings( '.gs-cal-dmsg' ), true ); } );
	$( document ).on( 'click', '.gs-cal-pren-elimina', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Togliere questa prenotazione dalla tua lista? Resta comunque recuperabile: chiedi alla segreteria se ti serve di nuovo.' ) ) return;
		gsCalPost( { action: 'gs_cal_pren_elimina', pren: $( this ).data( 'pren' ) }, $( this ).siblings( '.gs-cal-pren-elimina-msg' ), true );
	} );
	$( document ).on( 'click', '.gs-cal-pren-ripristina', function ( e ) {
		e.preventDefault();
		var $li = $( this ).closest( '.gs-todo-item' );
		$.post( GS_AJAX.url, { action: 'gs_cal_pren_ripristina', nonce: GS_AJAX.nonce, pren: $li.data( 'pren' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } else { alert( res && res.data ? res.data.message : 'Errore.' ); } } );
	} );
	$( document ).on( 'click', '.gs-cal-msg-invia', function ( e ) {
		e.preventDefault(); var $f = $( this ).closest( '.gs-form-cal-msg' );
		gsSendMsg( 'gs_cal_msg', $.extend( { pren: $f.data( 'pren' ), testo: $f.find( '[name=testo]' ).val() }, gsAntispamFields( $f ) ), $f.find( '.gs-msg-file' ), $f.find( '.gs-cal-mmsg' ), true );
	} );
	$( document ).on( 'click', '.gs-cal-cfg-salva', function ( e ) {
		e.preventDefault(); var $f = $( this ).closest( '.gs-form-cal-cfg' );
		gsCalPost( { action: 'gs_cal_salva_cfg', beneficiario: $f.find( '[name=beneficiario]' ).val(), iban: $f.find( '[name=iban]' ).val(), causale: $f.find( '[name=causale]' ).val(), istruzioni: $f.find( '[name=istruzioni]' ).val(), giorni_disdetta: $f.find( '[name=giorni_disdetta]' ).val() }, $f.find( '.gs-cal-cfg-msg' ), false );
	} );
	$( document ).on( 'click', '.gs-cal-corso-salva', function ( e ) {
		e.preventDefault(); var $f = $( this ).closest( '.gs-form-cal-corso' );
		gsCalPost( { action: 'gs_cal_salva_corso', id: $f.data( 'id' ), titolo: $f.find( '[name=titolo]' ).val(), tipo: $f.find( '[name=tipo]' ).val(), data: $f.find( '[name=data]' ).val(), inizio: $f.find( '[name=inizio]' ).val(), fine: $f.find( '[name=fine]' ).val(), posti: $f.find( '[name=posti]' ).val(), prezzo: $f.find( '[name=prezzo]' ).val(), acconto: $f.find( '[name=acconto]' ).val(), livello_sconto: $f.find( '[name=livello_sconto]' ).val(), descrizione: $f.find( '[name=descrizione]' ).val() }, $f.find( '.gs-cal-corso-msg' ), true );
	} );
	$( document ).on( 'click', '.gs-cal-blocca', function ( e ) {
		e.preventDefault(); var motivo = prompt( 'Motivazione da inviare agli iscritti:' ); if ( motivo === null || ! motivo.trim() ) return;
		gsCalPost( { action: 'gs_cal_blocca_corso', id: $( this ).data( 'id' ), motivo: motivo }, $( this ).closest( 'p' ).find( '.gs-cal-row-msg' ).addBack().filter( '.gs-cal-row-msg' ).first(), true );
	} );
	$( document ).on( 'click', '.gs-cal-riapri', function ( e ) { e.preventDefault(); gsCalPost( { action: 'gs_cal_riapri_corso', id: $( this ).data( 'id' ) }, $( this ).parent(), true ); } );
	// Niente ricarica pagina: si toglie solo la riga del corso eliminato, così
	// la pagina resta esattamente dov'era (richiesto da Ennio, 11/08/2026 —
	// prima un ricaricamento la riportava altrove, specie dentro il Pannello
	// di Controllo dove i ricaricamenti tornano sempre in cima).
	$( document ).on( 'click', '.gs-cal-elimina', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Spostare il corso nel cestino? Potrai ripristinarlo dal cestino di WordPress.' ) ) return;
		var $btn = $( this );
		var $msg = $btn.parent();
		var $riga = $btn.closest( '[data-corso]' );
		$msg.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_cal_elimina_corso', id: $btn.data( 'id' ), nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				if ( res && res.success ) {
					$riga.fadeOut( 250, function () { $( this ).remove(); } );
				} else {
					$msg.addClass( 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				}
			} )
			.fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-cal-stato', function ( e ) {
		e.preventDefault(); var $tr = $( this ).closest( '[data-pren]' );
		$tr.find( '.gs-cal-stato' ).removeClass( 'gs-cal-active' );
		$( this ).addClass( 'gs-cal-active' );
		gsCalPost( { action: 'gs_cal_pren_stato', pren: $( this ).data( 'pren' ), stato: $( this ).data( 'stato' ) }, $tr.find( '.gs-cal-row-msg' ), true );
	} );
	$( document ).on( 'click', '.gs-cal-pay', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		if ( $btn.prop( 'disabled' ) ) { return; } // già in corso, ignora clic ripetuti
		var $tr = $btn.closest( '[data-pren]' );
		$btn.prop( 'disabled', true );
		gsCalPost( { action: 'gs_cal_pagamento', pren: $tr.data( 'pren' ), tipo: $tr.find( '.gs-cal-pay-tipo' ).val(), importo: $tr.find( '.gs-cal-pay-imp' ).val(), rif: $tr.data( 'pay-rif' ) || '' }, $tr.find( '.gs-cal-row-msg' ), true )
			.always( function () { $btn.prop( 'disabled', false ); } );
	} );
	$( document ).on( 'click', '.gs-cal-attestato-toggle', function ( e ) {
		e.preventDefault(); var $tr = $( this ).closest( '[data-pren]' );
		gsCalPost( { action: 'gs_cal_attestato', pren: $( this ).data( 'pren' ) }, $tr.find( '.gs-cal-attestato-msg' ), true );
	} );
	$( document ).on( 'click', '.gs-sconto-applica', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Confermi che ha davvero fatto il corso? Lo sconto si azzera e passa al livello successivo.' ) ) { return; }
		var $tr = $( this ).closest( '[data-pren]' );
		gsCalPost( { action: 'gs_sconto_applica', pren: $( this ).data( 'pren' ) }, $tr.find( '.gs-sconto-applica-msg' ), true );
	} );
	$( document ).on( 'click', '.gs-cal-offri', function ( e ) {
		e.preventDefault(); var $tr = $( this ).closest( 'tr' ); var corso = $tr.find( '.gs-cal-offri-corso' ).val();
		if ( ! corso ) { $tr.find( '.gs-cal-offri-msg' ).addClass( 'err' ).text( 'Scegli una data.' ); return; }
		gsCalPost( { action: 'gs_cal_offri_data', pren: $tr.data( 'pren' ), corso: corso }, $tr.find( '.gs-cal-offri-msg' ), true );
	} );
	$( document ).on( 'click', '.gs-cal-offerta-accettata', function ( e ) {
		e.preventDefault();
		var $tr = $( this ).closest( 'tr' ), $m = $tr.find( '.gs-cal-offri-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_cal_offerta_accettata', nonce: GS_AJAX.nonce, pren: $tr.data( 'pren' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) {
					$tr.removeClass( 'gs-cal-attesa-rossa' ).addClass( 'gs-cal-attesa-verde' );
					setTimeout( function () { gsReloadMantenendoPosizione(); }, 1200 );
				}
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// Dal corso dritti alla creazione della locandina: precompila il modulo
	// "Nuovo documento" di Diplomi e Locandine con titolo, data e quota, poi
	// ci scorre sopra (stesso salto "sicuro" usato dall'indice del pannello).
	function gsDataItaliana( iso, ora_i, ora_f ) {
		if ( ! iso ) { return ''; }
		var p = iso.split( '-' );
		if ( p.length !== 3 ) { return ''; }
		var s = p[ 2 ] + '/' + p[ 1 ] + '/' + p[ 0 ];
		if ( ora_i ) { s += ', ore ' + ora_i + ( ora_f ? '–' + ora_f : '' ); }
		return s;
	}
	$( document ).on( 'click', '.gs-cal-vai-locandina', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-cal-corso' );
		var titolo  = $.trim( $f.find( '[name=titolo]' ).val() );
		var quando  = gsDataItaliana( $f.find( '[name=data]' ).val(), $f.find( '[name=inizio]' ).val(), $f.find( '[name=fine]' ).val() );
		var prezzo  = $.trim( $f.find( '[name=prezzo]' ).val() );
		var $loc = $( '.gs-form-locandina' ).first();
		if ( ! $loc.length ) { window.alert( 'Sezione "Diplomi e Locandine" non trovata in questa pagina.' ); return; }
		if ( titolo ) { $loc.find( '[name=titolo]' ).val( titolo ); }
		$loc.find( '[name=sottotitolo]' ).val( 'Nuovo corso in partenza' );
		var testo = '';
		if ( quando ) { testo += 'Il ' + quando + '.\n'; }
		if ( prezzo ) { testo += 'Quota: € ' + prezzo + '.\n'; }
		if ( testo ) { $loc.find( '[name=testo]' ).val( testo ); }
		var target = $loc.closest( '.gs-box, .gs-zone' )[ 0 ] || $loc[ 0 ];
		if ( typeof window.gsScrollTo === 'function' ) { window.gsScrollTo( target ); }
		else { target.scrollIntoView( { behavior: 'smooth', block: 'center' } ); }
		setTimeout( function () { $loc.find( '[name=titolo]' ).trigger( 'focus' ); }, 300 );
	} );

	// --- Visibilità sezioni e permessi collaboratori ---

	// "Vedi come collaboratore": calcola dai dati già nella tabella (nessuna
	// chiamata al server) cosa vede una persona specifica, con la stessa
	// logica di gs_sez_zona_ok()/gs_sez_collab_ok(): sezione spenta ("visibile"
	// non spuntata) = non la vede nessuno; altrimenti, se non c'è nessun
	// collaboratore selezionato in quella riga la vedono tutti, altrimenti solo
	// chi è spuntato.
	$( document ).on( 'change', '.gs-sez-vedi-come', function () {
		var uid = $( this ).val();
		var $tabella = $( this ).closest( '.gs-box' ).find( '.gs-tabella-sezioni' );
		var $esito = $( this ).closest( '.gs-box' ).find( '.gs-sez-vedi-come-esito' );
		$tabella.find( 'tr[data-key]' ).removeClass( 'gs-sez-vede gs-sez-non-vede' );
		if ( ! uid ) { $esito.text( '' ); return; }
		var visibili = 0, nascoste = 0;
		$tabella.find( 'tr[data-key]' ).each( function () {
			var $tr = $( this );
			var vis = $tr.find( '.gs-sez-vis' ).is( ':checked' );
			var $collabBox = $tr.find( '.gs-sez-collab' );
			var puoVedere = vis;
			if ( vis && $collabBox.length ) {
				var scelti = $collabBox.filter( ':checked' );
				puoVedere = ( 0 === scelti.length ) || ( scelti.filter( '[value="' + uid + '"]' ).length > 0 );
			}
			$tr.addClass( puoVedere ? 'gs-sez-vede' : 'gs-sez-non-vede' );
			if ( puoVedere ) { visibili++; } else { nascoste++; }
		} );
		$esito.text( visibili + ' visibili, ' + nascoste + ' nascoste per questa persona (calcolato sui dati mostrati qui sotto, salva prima se hai appena modificato qualcosa).' );
	} );

	$( document ).on( 'click', '.gs-sez-deseleziona', function ( e ) {
		e.preventDefault();
		$( this ).siblings( '.gs-sez-hideusers' ).find( 'option' ).prop( 'selected', false );
	} );

	$( document ).on( 'click', '.gs-sez-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-sezioni' );
		var $m = $f.find( '.gs-sez-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		var visibili = [], collab = {}, hideusers = {};
		$f.find( 'tr[data-key]' ).each( function () {
			var key = $( this ).data( 'key' );
			if ( $( this ).find( '.gs-sez-vis' ).is( ':checked' ) ) { visibili.push( key ); }
			var ids = [];
			$( this ).find( '.gs-sez-collab:checked' ).each( function () { ids.push( $( this ).val() ); } );
			if ( ids.length ) { collab[ key ] = ids; }
			var hu = $( this ).find( '.gs-sez-hideusers' ).val() || [];
			if ( hu.length ) { hideusers[ key ] = hu; }
		} );
		$.post( GS_AJAX.url, { action: 'gs_sez_salva', nonce: GS_AJAX.nonce, visibili: visibili, collab: collab, hideusers: hideusers } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-sez-reset-visibili', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		if ( ! window.confirm( 'Rendere visibili tutte le sezioni nascoste? I permessi per collaboratore non cambiano.' ) ) { return; }
		$btn.prop( 'disabled', true ).text( 'Attendere…' );
		$.post( GS_AJAX.url, { action: 'gs_sez_reset_visibili', nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				if ( res && res.success ) {
					gsReloadMantenendoPosizione();
				} else {
					window.alert( res && res.data ? res.data.message : 'Errore.' );
					$btn.prop( 'disabled', false ).text( 'Rendi visibili tutte le sezioni' );
				}
			} )
			.fail( function () {
				window.alert( 'Errore di connessione.' );
				$btn.prop( 'disabled', false ).text( 'Rendi visibili tutte le sezioni' );
			} );
	} );

	// --- Notifiche per sfoglina (email / interno per categoria) ---
	$( document ).on( 'click', '.gs-notif-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-notifiche' );
		var $m = $f.find( '.gs-notif-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		var pref = {};
		$f.find( 'tr[data-uid]' ).each( function () {
			var uid = $( this ).data( 'uid' );
			var cats = {};
			$( this ).find( '.gs-notif-email' ).each( function () {
				var cat = $( this ).data( 'cat' );
				cats[ cat ] = { email: $( this ).is( ':checked' ), interno: false };
			} );
			$( this ).find( '.gs-notif-interno' ).each( function () {
				var cat = $( this ).data( 'cat' );
				if ( ! cats[ cat ] ) { cats[ cat ] = { email: false, interno: false }; }
				cats[ cat ].interno = $( this ).is( ':checked' );
			} );
			pref[ uid ] = cats;
		} );
		$.post( GS_AJAX.url, { action: 'gs_notifiche_pref_salva', nonce: GS_AJAX.nonce, pref: pref } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Diplomi e Locandine ---
	$( document ).on( 'click', '.gs-loc-crea', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-locandina' );
		var $m = $f.find( '.gs-loc-msg' );
		var $file = $f.find( '.gs-loc-nuovo-foto-file' );
		var scelto = gsFileDaInviare( $file );
		var errore = gsMessaggioSeTroppoGrande( scelto );
		if ( errore ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( errore ); return; }
		$m.removeClass( 'ok err' ).text( 'Creazione…' );
		var fd = new FormData();
		fd.append( 'action', 'gs_locandina_crea' );
		fd.append( 'nonce', GS_AJAX.nonce );
		fd.append( 'titolo', $f.find( '[name=titolo]' ).val() );
		fd.append( 'titolo2', $f.find( '[name=titolo2]' ).val() );
		fd.append( 'intestazione', $f.find( '[name=intestazione]' ).val() );
		fd.append( 'sottotitolo', $f.find( '[name=sottotitolo]' ).val() );
		fd.append( 'testo', $f.find( '[name=testo]' ).val() );
		fd.append( 'link', $f.find( '[name=link]' ).val() );
		if ( scelto ) {
			fd.append( 'foto', scelto.file, scelto.nome );
		} else {
			fd.append( 'foto_attachment', $f.find( '.gs-loc-nuovo-foto-attachment' ).val() );
		}
		$.ajax( { url: GS_AJAX.url, type: 'POST', data: fd, processData: false, contentType: false } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// Scelta della foto dalla libreria media già nel form "Nuovo documento"
	// (prima ancora di creare il documento): l'id scelto resta in un campo
	// nascosto e viaggia con la creazione stessa.
	$( document ).on( 'click', '.gs-loc-nuovo-foto-libreria', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-locandina' );
		var $scelta = $f.find( '.gs-loc-nuovo-foto-scelta' );
		if ( typeof wp === 'undefined' || ! wp.media ) {
			$scelta.text( 'Libreria non disponibile su questa pagina.' );
			return;
		}
		var frame = wp.media( { title: 'Scegli una foto dalla libreria', library: { type: 'image' }, multiple: false, button: { text: 'Usa questa foto' } } );
		frame.on( 'select', function () {
			var att = frame.state().get( 'selection' ).first().toJSON();
			$f.find( '.gs-loc-nuovo-foto-attachment' ).val( att.id );
			$f.find( '.gs-loc-nuovo-foto-file' ).val( '' );
			$scelta.text( 'Foto scelta dalla libreria: ' + ( att.filename || att.title || att.id ) );
		} );
		frame.open();
	} );

	$( document ).on( 'click', '.gs-loc-modifica-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-locandina-modifica' );
		var loc = $f.data( 'loc' );
		var $m = $f.find( '.gs-loc-modifica-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_locandina_modifica', nonce: GS_AJAX.nonce, loc: loc,
			titolo: $f.find( '[name=titolo]' ).val(),
			titolo2: $f.find( '[name=titolo2]' ).val(),
			intestazione: $f.find( '[name=intestazione]' ).val(),
			sottotitolo: $f.find( '[name=sottotitolo]' ).val(),
			testo: $f.find( '[name=testo]' ).val(),
			link: $f.find( '[name=link]' ).val()
		} ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-loc-elimina', function ( e ) {
		e.preventDefault();
		var loc = $( this ).data( 'loc' );
		var $m = $( this ).closest( 'details' ).find( '.gs-loc-row-msg' );
		if ( ! window.confirm( 'Spostare questo documento nel cestino?' ) ) { return; }
		$m.removeClass( 'ok err' ).text( '…' );
		$.post( GS_AJAX.url, { action: 'gs_locandina_elimina', nonce: GS_AJAX.nonce, loc: loc } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-loc-ripristina', function ( e ) {
		e.preventDefault();
		var loc = $( this ).data( 'loc' );
		var $m = $( this ).closest( 'tr' ).find( '.gs-loc-trow-msg' );
		$m.removeClass( 'ok err' ).text( '…' );
		$.post( GS_AJAX.url, { action: 'gs_locandina_ripristina', nonce: GS_AJAX.nonce, loc: loc } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Sondaggi: elimina/ripristina (crea, modifica, vota, proponi passano
	// tutti dal gestore generico dei form .gs-form[data-action]) ---
	$( document ).on( 'click', '.gs-sond-elimina', function ( e ) {
		e.preventDefault();
		var sondaggio = $( this ).data( 'sondaggio' );
		var $m = $( this ).closest( 'details' ).find( '.gs-sond-row-msg' );
		if ( ! window.confirm( 'Spostare questo sondaggio nel cestino?' ) ) { return; }
		$m.removeClass( 'ok err' ).text( '…' );
		$.post( GS_AJAX.url, { action: 'gs_sondaggio_elimina', nonce: GS_AJAX.nonce, sondaggio: sondaggio } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-sond-ripristina', function ( e ) {
		e.preventDefault();
		var sondaggio = $( this ).data( 'sondaggio' );
		var $m = $( this ).closest( 'tr' ).find( '.gs-sond-trow-msg' );
		$m.removeClass( 'ok err' ).text( '…' );
		$.post( GS_AJAX.url, { action: 'gs_sondaggio_ripristina', nonce: GS_AJAX.nonce, sondaggio: sondaggio } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-loc-foto-aggiungi', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var loc = $btn.data( 'loc' );
		var $file = $btn.closest( 'p' ).find( '.gs-loc-foto-file' );
		var $m = $btn.closest( 'p' ).find( '.gs-loc-foto-msg' );
		var scelto = gsFileDaInviare( $file );
		if ( ! scelto ) {
			$m.addClass( 'err' ).text( 'Scegli prima una foto.' );
			return;
		}
		var errore = gsMessaggioSeTroppoGrande( scelto );
		if ( errore ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( errore ); return; }
		$m.removeClass( 'ok err' ).text( 'Caricamento…' );
		var fd = new FormData();
		fd.append( 'action', 'gs_locandina_foto_aggiungi' );
		fd.append( 'nonce', GS_AJAX.nonce );
		fd.append( 'loc', loc );
		fd.append( 'foto', scelto.file, scelto.nome );
		$.ajax( { url: GS_AJAX.url, type: 'POST', data: fd, processData: false, contentType: false } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// Scegli una foto già presente nella libreria media del sito, invece di
	// caricarne una nuova (richiede wp_enqueue_media(), solo sul Pannello).
	// Logo dei documenti (diploma, certificato, locandine): scelto dalla
	// libreria media, vale per tutti finché non lo si ripristina.
	$( document ).on( 'click', '.gs-logo-certificati-scegli', function ( e ) {
		e.preventDefault();
		var $m = $( this ).closest( '.gs-form-logo-certificati' ).find( '.gs-logo-certificati-msg' );
		if ( typeof wp === 'undefined' || ! wp.media ) {
			$m.removeClass( 'ok' ).addClass( 'err' ).text( 'Libreria non disponibile su questa pagina.' );
			return;
		}
		var frame = wp.media( { title: 'Scegli il logo dei documenti', library: { type: 'image' }, multiple: false, button: { text: 'Usa questo logo' } } );
		frame.on( 'select', function () {
			var att = frame.state().get( 'selection' ).first().toJSON();
			$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
			$.post( GS_AJAX.url, { action: 'gs_certificato_logo_salva', nonce: GS_AJAX.nonce, attachment: att.id } )
				.done( function ( res ) {
					$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
					if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
				} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
		} );
		frame.open();
	} );

	$( document ).on( 'click', '.gs-logo-certificati-reset', function ( e ) {
		e.preventDefault();
		var $m = $( this ).closest( '.gs-form-logo-certificati' ).find( '.gs-logo-certificati-msg' );
		$m.removeClass( 'ok err' ).text( 'Ripristino…' );
		$.post( GS_AJAX.url, { action: 'gs_certificato_logo_reset', nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-loc-foto-libreria', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var loc = $btn.data( 'loc' );
		var $m = $btn.closest( 'p' ).find( '.gs-loc-foto-msg' );
		if ( typeof wp === 'undefined' || ! wp.media ) {
			$m.removeClass( 'ok' ).addClass( 'err' ).text( 'Libreria non disponibile su questa pagina.' );
			return;
		}
		var frame = wp.media( { title: 'Scegli una foto dalla libreria', library: { type: 'image' }, multiple: false, button: { text: 'Usa questa foto' } } );
		frame.on( 'select', function () {
			var att = frame.state().get( 'selection' ).first().toJSON();
			$m.removeClass( 'ok err' ).text( 'Aggiunta…' );
			$.post( GS_AJAX.url, { action: 'gs_locandina_foto_libreria', nonce: GS_AJAX.nonce, loc: loc, attachment: att.id } )
				.done( function ( res ) {
					$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
					if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
				} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
		} );
		frame.open();
	} );

	$( document ).on( 'click', '.gs-loc-foto-rimuovi', function ( e ) {
		e.preventDefault();
		var loc = $( this ).data( 'loc' );
		var url = $( this ).data( 'url' );
		$.post( GS_AJAX.url, { action: 'gs_locandina_foto_rimuovi', nonce: GS_AJAX.nonce, loc: loc, url: url } )
			.done( function ( res ) {
				if ( res && res.success ) { gsReloadMantenendoPosizione(); }
			} );
	} );

	// "Scarica PNG/JPG": dal pannello apre la pagina stampabile con
	// ?gs_scarica=png (o jpg), che disegna il documento su un <canvas> e lo
	// scarica nel formato scelto — nessuna libreria esterna, solo Canvas 2D
	// nativo del browser.
	$( document ).on( 'click', '.gs-loc-scarica-immagine', function ( e ) {
		e.preventDefault();
		var url = $( this ).data( 'url' );
		var formato = $( this ).data( 'formato' ) === 'jpeg' ? 'jpg' : 'png';
		if ( url ) {
			window.open( url + '&gs_scarica=' + formato, '_blank' );
		} else {
			gsScaricaLocandinaImmagine( formato );
		}
	} );

	function gsRoundRectPath( ctx, x, y, w, h, r ) {
		ctx.beginPath();
		ctx.moveTo( x + r, y );
		ctx.arcTo( x + w, y, x + w, y + h, r );
		ctx.arcTo( x + w, y + h, x, y + h, r );
		ctx.arcTo( x, y + h, x, y, r );
		ctx.arcTo( x, y, x + w, y, r );
		ctx.closePath();
	}

	// Disegna un testo con lettere spaziate a mano, centrato in (cx, y). Non usa
	// ctx.letterSpacing di proposito (vedi commento sopra al punto di chiamata).
	function gsFillTextSpaziato( ctx, text, cx, y, spaziatura ) {
		var chars = String( text ).split( '' );
		var larghezze = chars.map( function ( ch ) { return ctx.measureText( ch ).width; } );
		var totale = larghezze.reduce( function ( s, w ) { return s + w; }, 0 ) + spaziatura * ( chars.length - 1 );
		var prevAlign = ctx.textAlign;
		ctx.textAlign = 'left';
		var x = cx - totale / 2;
		chars.forEach( function ( ch, i ) {
			ctx.fillText( ch, x, y );
			x += larghezze[ i ] + spaziatura;
		} );
		ctx.textAlign = prevAlign;
	}

	function gsWrapCanvasText( ctx, text, maxWidth ) {
		var words = String( text ).split( /\s+/ ).filter( Boolean );
		var lines = [];
		var line = '';
		words.forEach( function ( w ) {
			var test = line ? line + ' ' + w : w;
			if ( line && ctx.measureText( test ).width > maxWidth ) {
				lines.push( line );
				line = w;
			} else {
				line = test;
			}
		} );
		if ( line ) { lines.push( line ); }
		return lines.length ? lines : [ '' ];
	}

	function gsCaricaImmagineImg( src ) {
		return new Promise( function ( resolve ) {
			if ( ! src ) { resolve( null ); return; }
			var img = new Image();
			img.crossOrigin = 'anonymous';
			img.onload = function () { resolve( img ); };
			img.onerror = function () { resolve( null ); };
			img.src = src;
		} );
	}

	// Trova la dimensione più grande (tra pxMax e pxMin, a scatti di 2px) con
	// cui "testo" sta su una riga sola larga al massimo "larghezzaMax". Se non
	// ci sta nemmeno a pxMin, ritorna pxMin (andrà a capo, ma il più piccolo
	// possibile). Con testo vuoto ritorna pxMax: un titolo assente non deve
	// mai essere quello che restringe l'altro.
	function gsMisuraDimensioneTitolo( ctx, testo, larghezzaMax, pxMax, pxMin ) {
		if ( ! testo ) { return pxMax; }
		for ( var px = pxMax; px > pxMin; px -= 2 ) {
			ctx.font = '700 ' + px + 'px Georgia, "Times New Roman", serif';
			if ( ctx.measureText( testo ).width <= larghezzaMax ) { return px; }
		}
		return pxMin;
	}

	// Disegna tutto il corpo della locandina (eyebrow, sigillo, intestazione,
	// chip, titoli, foto, testo) a partire da y = cm + 56, e ritorna la
	// posizione y finale (dove finisce il contenuto). Con disegna=false non
	// disegna nulla, calcola solo lo spazio necessario: misureText/wrap
	// dipendono dal font, non dalle dimensioni del canvas, quindi lo stesso
	// calcolo funziona anche su un canvas "usa e getta". Usata due volte
	// (misura, poi disegno vero) così il canvas può avere un'altezza che si
	// adatta al contenuto invece di una altezza fissa in cui foto e testo
	// lunghi finiscono tagliati fuori dalla cornice.
	function gsDisegnaCorpoLocandina( ctx, W, cm, d, disegna ) {
		var cx = W / 2, y = cm + 56;
		var larghezzaTitolo = W - cm * 2 - 100;
		var larghezzaTesto  = W - cm * 2 - 140;

		// Eyebrow — grande quanto il resto del testo, non più una piccola etichetta.
		// Le lettere spaziate si disegnano a mano (una per una), invece di usare
		// ctx.letterSpacing: su alcuni browser quella proprietà lascia uno stato
		// residuo che sfalsa il centraggio dei testi disegnati subito dopo.
		ctx.font = '700 32px -apple-system, "Segoe UI", sans-serif';
		if ( disegna ) {
			ctx.fillStyle = '#8a5a1f';
			gsFillTextSpaziato( ctx, d.eyebrow.toUpperCase(), cx, y, 6 );
		}
		y += 75;

		// Sigillo (logo tondo) — doppio delle dimensioni originali.
		if ( d.sigilloImg ) {
			var r = 180;
			if ( disegna ) {
				ctx.save();
				ctx.beginPath(); ctx.arc( cx, y + r, r, 0, Math.PI * 2 ); ctx.closePath(); ctx.clip();
				ctx.drawImage( d.sigilloImg, cx - r, y, r * 2, r * 2 );
				ctx.restore();
				ctx.strokeStyle = 'rgba(189,138,19,.5)'; ctx.lineWidth = 3;
				ctx.beginPath(); ctx.arc( cx, y + r, r + 6, 0, Math.PI * 2 ); ctx.stroke();
			}
			y += r * 2 + 50;
		}

		// Intestazione.
		if ( d.intestazione ) {
			ctx.font = '700 30px Georgia, "Times New Roman", serif';
			if ( disegna ) {
				ctx.fillStyle = '#3a2a1a';
				ctx.fillText( d.intestazione, cx, y );
			}
			y += 60;
		}

		// Chip (etichetta).
		if ( d.chip ) {
			ctx.font = '700 26px Georgia, "Times New Roman", serif';
			var chipW = ctx.measureText( d.chip ).width + 60, chipH = 54;
			var chipTop = y - chipH + 14, chipCenterY = chipTop + chipH / 2;
			if ( disegna ) {
				var chipGrad = ctx.createLinearGradient( 0, chipTop, 0, chipTop + chipH );
				chipGrad.addColorStop( 0, '#c79a3f' ); chipGrad.addColorStop( 1, '#96691e' );
				gsRoundRectPath( ctx, cx - chipW / 2, chipTop, chipW, chipH, chipH / 2 );
				ctx.fillStyle = chipGrad; ctx.fill();
				ctx.fillStyle = '#fffaf0';
				// Testo centrato verticalmente nel riquadro (textBaseline
				// 'middle'), non più ancorato al bordo inferiore. Se sul
				// dispositivo di chi scarica manca il font Georgia/Times New
				// Roman (capita fuori da Mac/Windows con Office), il browser
				// usa un font sostitutivo con misure diverse: ancorato al
				// bordo, il testo poteva sporgere sotto al riquadro e sembrare
				// "sovrapposto" a quello che veniva dopo — centrato, la
				// differenza si distribuisce sopra e sotto invece di sporgere
				// da un lato solo.
				ctx.textBaseline = 'middle';
				ctx.fillText( d.chip, cx, chipCenterY );
				ctx.textBaseline = 'alphabetic';
			}
			y += 80;
		}

		// Divisore.
		if ( disegna ) {
			ctx.strokeStyle = '#c79a3f'; ctx.lineWidth = 2;
			ctx.beginPath(); ctx.moveTo( cx - 140, y ); ctx.lineTo( cx + 140, y ); ctx.stroke();
		}
		y += 60;

		// I due titoli grandi condividono la stessa dimensione — più piccola se
		// sono presenti entrambi insieme, altrimenti sembrano esagerati e
		// rischiano di spingere il resto (foto comprese) fuori dalla cornice.
		// La dimensione si restringe quanto basta perché ciascun titolo stia
		// su una riga sola: a dimensione fissa, un titolo di lunghezza "normale"
		// andava a capo in un punto qualunque (es. l'ultima parola sola sotto),
		// disallineando tutto quello che veniva dopo. Sotto una certa misura
		// (titoloPxMin) si accetta comunque l'andare a capo, per titoli davvero
		// lunghi che non potrebbero mai stare su una riga sola.
		var dueTitoli   = d.nome && d.nome2;
		var titoloPxMax = dueTitoli ? 42 : 56;
		var titoloPxMin = 26;
		var titoloPx    = Math.min(
			gsMisuraDimensioneTitolo( ctx, d.nome, larghezzaTitolo, titoloPxMax, titoloPxMin ),
			gsMisuraDimensioneTitolo( ctx, d.nome2, larghezzaTitolo, titoloPxMax, titoloPxMin )
		);
		var titoloLh = Math.round( titoloPx * 66 / 56 );

		[ d.nome, d.nome2 ].forEach( function ( testoTitolo ) {
			if ( ! testoTitolo ) { return; }
			ctx.font = '700 ' + titoloPx + 'px Georgia, "Times New Roman", serif';
			var righe = gsWrapCanvasText( ctx, testoTitolo, larghezzaTitolo );
			if ( disegna ) { ctx.fillStyle = '#3a2a1a'; }
			righe.forEach( function ( riga ) {
				if ( disegna ) { ctx.fillText( riga, cx, y ); }
				y += titoloLh;
			} );
			var lastW = ctx.measureText( righe[ righe.length - 1 ] ).width;
			if ( disegna ) {
				ctx.strokeStyle = '#c79a3f'; ctx.lineWidth = 2;
				ctx.beginPath(); ctx.moveTo( cx - lastW / 2 - 10, y - titoloLh + 16 ); ctx.lineTo( cx + lastW / 2 + 10, y - titoloLh + 16 ); ctx.stroke();
			}
			y += 20;
		} );

		// Foto.
		if ( d.fotoImgs.length ) {
			var fotoSize = d.fotoImgs.length > 1 ? 220 : 320, gap = 16;
			if ( disegna ) {
				var totalW = d.fotoImgs.length * fotoSize + ( d.fotoImgs.length - 1 ) * gap;
				var startX = cx - totalW / 2;
				d.fotoImgs.forEach( function ( img, i ) {
					var fx = startX + i * ( fotoSize + gap );
					var scale = Math.max( fotoSize / img.naturalWidth, fotoSize / img.naturalHeight );
					var sw = fotoSize / scale, sh = fotoSize / scale;
					var sx = ( img.naturalWidth - sw ) / 2, sy = ( img.naturalHeight - sh ) / 2;
					ctx.save();
					gsRoundRectPath( ctx, fx, y, fotoSize, fotoSize, 14 ); ctx.clip();
					ctx.drawImage( img, sx, sy, sw, sh, fx, y, fotoSize, fotoSize );
					ctx.restore();
					ctx.strokeStyle = '#c79a3f'; ctx.lineWidth = 2;
					gsRoundRectPath( ctx, fx, y, fotoSize, fotoSize, 14 ); ctx.stroke();
				} );
			}
			y += fotoSize + 36;
		}

		// Testo libero (es. il programma del corso).
		if ( d.testo ) {
			ctx.font = 'italic 30px Georgia, "Times New Roman", serif';
			if ( disegna ) { ctx.fillStyle = '#3a2a1a'; }
			d.testo.split( '\n' ).forEach( function ( paragrafo ) {
				if ( ! paragrafo.trim() ) { y += 20; return; }
				gsWrapCanvasText( ctx, paragrafo, larghezzaTesto ).forEach( function ( riga ) {
					if ( disegna ) { ctx.fillText( riga, cx, y ); }
					y += 40;
				} );
			} );
		}

		return y;
	}

	// Ricostruisce l'immagine della locandina (stesso disegno usato da "Scarica"
	// e da "Condividi", per non tenere due copie della stessa logica) e
	// risolve con { blob, nomeFile }. Non tocca il DOM: chi chiama decide se
	// scaricarla o condividerla.
	function gsCostruisciBlobLocandina( formato ) {
		formato = 'jpg' === formato ? 'jpg' : 'png';
		var $doc = $( '#gs-loc-canvas-source' );
		if ( ! $doc.length ) { return Promise.reject( new Error( 'documento non trovato' ) ); }

		var d = {
			eyebrow:      $doc.find( '.eyebrow-top' ).first().text().trim() || 'Accademia della Sfoglia',
			intestazione: $doc.find( '.intestazione' ).first().text().trim(),
			chip:         $doc.find( '.chip' ).first().text().trim(),
			nome:         $doc.find( '.nome' ).first().text().trim(),
			nome2:        $doc.find( '.gs-loc-titolo2' ).first().text().trim(),
			testo:        $doc.find( '.testo' ).first().text().trim()
		};
		var sigilloSrc = $doc.find( '.sigillo' ).attr( 'src' ) || '';
		var fotoSrcs = [];
		$doc.find( '.doc-foto' ).each( function () { fotoSrcs.push( $( this ).attr( 'src' ) ); } );

		var W = 1080;
		var margin = 36, cm = margin + 14;

		return Promise.all( [ gsCaricaImmagineImg( sigilloSrc ) ].concat( fotoSrcs.map( gsCaricaImmagineImg ) ) ).then( function ( imgs ) {
			d.sigilloImg = imgs[ 0 ];
			d.fotoImgs   = imgs.slice( 1 ).filter( function ( i ) { return !! i; } );

			// Primo passaggio: solo misura, nessun disegno (vedi commento sopra
			// gsDisegnaCorpoLocandina). Serve per sapere quanto deve essere alta
			// la cornice, così un secondo titolo e/o una foto non finiscono più
			// tagliati fuori dal bordo dorato come succedeva con l'altezza fissa.
			var misura = document.createElement( 'canvas' );
			misura.width = W; misura.height = 10;
			var ctxMisura = misura.getContext( '2d' );
			ctxMisura.textAlign = 'center';
			var fineContenuto = gsDisegnaCorpoLocandina( ctxMisura, W, cm, d, false );

			var H = Math.max( 1350, Math.round( fineContenuto + cm + 40 ) );
			var canvas = document.createElement( 'canvas' );
			canvas.width = W; canvas.height = H;
			var ctx = canvas.getContext( '2d' );

			// Sfondo pergamena.
			var bg = ctx.createRadialGradient( W / 2, 60, 40, W / 2, H / 2, W );
			bg.addColorStop( 0, '#fbf4e2' ); bg.addColorStop( 1, '#f3e6c6' );
			ctx.fillStyle = bg; ctx.fillRect( 0, 0, W, H );

			// Cornice dorata.
			var frameGrad = ctx.createLinearGradient( 0, margin, 0, H - margin );
			frameGrad.addColorStop( 0, '#e7c877' ); frameGrad.addColorStop( 1, '#c79a3f' );
			gsRoundRectPath( ctx, margin, margin, W - margin * 2, H - margin * 2, 28 );
			ctx.fillStyle = frameGrad; ctx.fill();

			// Carta interna.
			var cardGrad = ctx.createLinearGradient( 0, cm, W, H - cm );
			cardGrad.addColorStop( 0, '#fffdf7' ); cardGrad.addColorStop( 1, '#f8edd3' );
			gsRoundRectPath( ctx, cm, cm, W - cm * 2, H - cm * 2, 20 );
			ctx.fillStyle = cardGrad; ctx.fill();
			ctx.strokeStyle = '#bd8a13'; ctx.lineWidth = 2;
			gsRoundRectPath( ctx, cm, cm, W - cm * 2, H - cm * 2, 20 ); ctx.stroke();

			ctx.textAlign = 'center';
			gsDisegnaCorpoLocandina( ctx, W, cm, d, true );

			var nomeFile = ( d.nome || 'documento' ).toLowerCase().replace( /[^a-z0-9]+/g, '-' ).replace( /(^-|-$)/g, '' );

			return new Promise( function ( resolve, reject ) {
				canvas.toBlob( function ( blob ) {
					if ( ! blob ) { reject( new Error( 'generazione immagine non riuscita' ) ); return; }
					resolve( {
						blob: blob,
						nomeFile: 'accademia-sfoglia-' + ( nomeFile || 'documento' ) + '.' + ( 'jpg' === formato ? 'jpg' : 'png' )
					} );
				}, 'jpg' === formato ? 'image/jpeg' : 'image/png', 0.92 );
			} );
		} );
	}

	function gsScaricaLocandinaImmagine( formato ) {
		gsCostruisciBlobLocandina( formato ).then( function ( risultato ) {
			// Un'immagine come "data:" URI può superare la soglia oltre la quale
			// alcuni browser (Opera/Chrome inclusi) ignorano l'attributo "download"
			// e aprono semplicemente l'immagine invece di scaricarla. Un Blob con
			// URL.createObjectURL non ha questo limite ed è il modo corretto per
			// scaricare un'immagine generata da canvas.
			var url  = URL.createObjectURL( risultato.blob );
			var link = document.createElement( 'a' );
			link.download = risultato.nomeFile;
			link.href = url;
			document.body.appendChild( link );
			link.click();
			document.body.removeChild( link );
			setTimeout( function () { URL.revokeObjectURL( url ); }, 1000 );
		} );
	}

	// "Condividi": apre il menu di condivisione nativo del dispositivo (Web
	// Share API) con l'immagine già allegata — è il dispositivo/browser a
	// decidere quali app offrire (Instagram, Facebook, WhatsApp, Messaggi…),
	// non il sito: né Facebook né Instagram permettono di aprirsi già con
	// un'immagine pronta da un sito esterno, questo è il modo più vicino
	// possibile. Su desktop molti browser non supportano ancora la
	// condivisione di file: in quel caso si avvisa di usare "Scarica".
	function gsCondividiLocandina( formato, $msg ) {
		if ( $msg ) { $msg.removeClass( 'ok err' ).text( 'Preparo l\'immagine…' ); }
		gsCostruisciBlobLocandina( formato ).then( function ( risultato ) {
			var file = new File( [ risultato.blob ], risultato.nomeFile, { type: risultato.blob.type } );
			if ( ! navigator.share || ! navigator.canShare || ! navigator.canShare( { files: [ file ] } ) ) {
				if ( $msg ) { $msg.addClass( 'err' ).text( 'La condivisione diretta non è supportata qui: usa "Scarica" e condividi il file a mano.' ); }
				return;
			}
			if ( $msg ) { $msg.text( '' ); }
			navigator.share( {
				files: [ file ],
				title: 'Accademia della Sfoglia',
				text: 'Accademia della Sfoglia'
			} ).catch( function ( err ) {
				// L'utente che chiude il menu di condivisione senza scegliere
				// nulla non è un errore da segnalare.
				if ( $msg && err && 'AbortError' !== err.name ) {
					$msg.addClass( 'err' ).text( 'Non è stato possibile condividere. Prova a scaricare l\'immagine e condividerla manualmente.' );
				}
			} );
		} ).catch( function () {
			if ( $msg ) { $msg.addClass( 'err' ).text( 'Errore nella preparazione dell\'immagine.' ); }
		} );
	}

	$( document ).on( 'click', '.gs-loc-condividi', function ( e ) {
		e.preventDefault();
		var formato = $( this ).data( 'formato' ) === 'jpeg' ? 'jpg' : 'png';
		gsCondividiLocandina( formato, $( this ).closest( 'p' ).find( '.gs-loc-condividi-msg' ) );
	} );

	// "Prepara link per Facebook": genera la stessa immagine di "Scarica", la
	// salva come allegato del sito (serve un file vero con un URL stabile,
	// perché Facebook/Instagram leggono i tag Open Graph senza eseguire
	// JavaScript) e copia negli appunti il link pubblico della pagina — quello
	// da incollare in un post, con l'anteprima e il pulsante "Per prenotarti"
	// che funzionano davvero per chi clicca.
	function gsPreparaLinkCondivisibile( locId, $msg ) {
		if ( $msg ) { $msg.removeClass( 'ok err' ).text( 'Preparo l\'anteprima…' ); }
		gsCostruisciBlobLocandina( 'png' ).then( function ( risultato ) {
			var fd = new FormData();
			fd.append( 'action', 'gs_locandina_salva_anteprima' );
			fd.append( 'nonce', GS_AJAX.nonce );
			fd.append( 'loc', locId );
			fd.append( 'anteprima', risultato.blob, risultato.nomeFile );
			return $.ajax( { url: GS_AJAX.url, type: 'POST', data: fd, processData: false, contentType: false } );
		} ).then( function ( res ) {
			if ( ! res || ! res.success || ! res.data || ! res.data.url ) {
				if ( $msg ) { $msg.addClass( 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); }
				return;
			}
			var link = res.data.url;
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( link ).then( function () {
					if ( $msg ) { $msg.addClass( 'ok' ).text( 'Link copiato: ' + link ); }
				}, function () {
					if ( $msg ) { $msg.addClass( 'ok' ).text( 'Link pronto (copialo a mano): ' + link ); }
				} );
			} else if ( $msg ) {
				$msg.addClass( 'ok' ).text( 'Link pronto (copialo a mano): ' + link );
			}
		} ).catch( function () {
			if ( $msg ) { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); }
		} );
	}

	$( document ).on( 'click', '.gs-loc-link-condivisibile', function ( e ) {
		e.preventDefault();
		var loc = $( this ).data( 'loc' );
		gsPreparaLinkCondivisibile( loc, $( this ).closest( 'p' ).find( '.gs-loc-link-msg' ) );
	} );

	// Se la pagina stampabile è stata aperta con ?gs_scarica=png (o jpg, o il
	// vecchio =1) dal pulsante nel pannello, scarica subito l'immagine senza
	// bisogno di un secondo clic.
	$( function () {
		var m = window.location.search.match( /[?&]gs_scarica=([a-z0-9]+)/ );
		if ( m && $( '#gs-loc-canvas-source' ).length ) {
			gsScaricaLocandinaImmagine( 'jpg' === m[ 1 ] ? 'jpg' : 'png' );
		}
	} );

	// --- Aiuto e Suggerimenti (sfoglina) ---
	$( document ).on( 'click', '.gs-aiuto-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-aiuto' );
		var $m = $f.find( '.gs-aiuto-msg' );
		gsSendMsg( 'gs_aiuto_invia', $.extend( { tipo: $f.find( '[name=tipo]' ).val(), testo: $f.find( '[name=testo]' ).val() }, gsAntispamFields( $f ) ), $f.find( '.gs-msg-file' ), $m, true );
	} );
	$( document ).on( 'click', '.gs-aiuto-risposta-sfoglina-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-aiuto-risposta' );
		var $m = $f.find( '.gs-aiuto-risposta-sfoglina-msg' );
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, $.extend( { action: 'gs_aiuto_risposta_sfoglina', nonce: GS_AJAX.nonce, aiuto: $f.find( '[name=aiuto]' ).val(), testo: $f.find( '[name=testo]' ).val() }, gsAntispamFields( $f ) ) )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	// --- Accesso, password dimenticata, reimposta password ---
	$( document ).on( 'submit', '.gs-form-login', function ( e ) {
		e.preventDefault();
		var $f = $( this );
		var $m = $f.find( '.gs-form-msg' );
		var $btn = $f.find( '.gs-login-invia' );
		$btn.prop( 'disabled', true );
		$m.removeClass( 'ok err' ).text( 'Accesso in corso…' );
		$.post( GS_AJAX.url, {
			action: 'gs_login', nonce: GS_AJAX.nonce,
			gs_login_user: $f.find( '[name=gs_login_user]' ).val(),
			gs_login_pwd: $f.find( '[name=gs_login_pwd]' ).val(),
			gs_login_remember: $f.find( '[name=gs_login_remember]' ).is( ':checked' ) ? 1 : '',
			gs_login_redirect: $f.find( '[name=gs_login_redirect]' ).val(),
			gs_hp: $f.find( '[name=gs_hp]' ).val(),
			gs_ts: $f.find( '[name=gs_ts]' ).val()
		} ).done( function ( res ) {
			if ( res && res.success && res.data && res.data.redirect ) {
				$m.addClass( 'ok' ).text( res.data.message || 'Accesso riuscito!' );
				window.location.href = res.data.redirect;
			} else {
				$btn.prop( 'disabled', false );
				$m.addClass( 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			}
		} ).fail( function () {
			$btn.prop( 'disabled', false );
			$m.addClass( 'err' ).text( 'Errore di connessione.' );
		} );
	} );

	$( document ).on( 'submit', '.gs-form-password-dimenticata', function ( e ) {
		e.preventDefault();
		var $f = $( this );
		var $m = $f.find( '.gs-form-msg' );
		var $btn = $f.find( '.gs-pwd-dimenticata-invia' );
		$btn.prop( 'disabled', true );
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_password_dimenticata', nonce: GS_AJAX.nonce,
			gs_pwd_user: $f.find( '[name=gs_pwd_user]' ).val(),
			gs_hp: $f.find( '[name=gs_hp]' ).val(),
			gs_ts: $f.find( '[name=gs_ts]' ).val()
		} ).done( function ( res ) {
			$btn.prop( 'disabled', false );
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { $f.find( 'input[type=text]' ).val( '' ); }
		} ).fail( function () {
			$btn.prop( 'disabled', false );
			$m.addClass( 'err' ).text( 'Errore di connessione.' );
		} );
	} );

	$( document ).on( 'submit', '.gs-form-password-reimposta', function ( e ) {
		e.preventDefault();
		var $f = $( this );
		var $m = $f.find( '.gs-form-msg' );
		var $btn = $f.find( '.gs-pwd-reimposta-invia' );
		$btn.prop( 'disabled', true );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_password_reimposta', nonce: GS_AJAX.nonce,
			gs_reset_key: $f.find( '[name=gs_reset_key]' ).val(),
			gs_reset_login: $f.find( '[name=gs_reset_login]' ).val(),
			gs_reset_pwd: $f.find( '[name=gs_reset_pwd]' ).val(),
			gs_reset_pwd2: $f.find( '[name=gs_reset_pwd2]' ).val(),
			gs_hp: $f.find( '[name=gs_hp]' ).val(),
			gs_ts: $f.find( '[name=gs_ts]' ).val()
		} ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) {
				$f.find( 'input' ).prop( 'disabled', true );
				$btn.prop( 'disabled', true );
			} else {
				$btn.prop( 'disabled', false );
			}
		} ).fail( function () {
			$btn.prop( 'disabled', false );
			$m.addClass( 'err' ).text( 'Errore di connessione.' );
		} );
	} );

	// --- Il tuo account: cambio password, cambio email, esporta dati, richiedi cancellazione ---
	$( document ).on( 'submit', '.gs-form-account-password', function ( e ) {
		e.preventDefault();
		var $f = $( this ), $m = $f.find( '.gs-form-msg' ), $btn = $f.find( '.gs-account-password-invia' );
		$btn.prop( 'disabled', true );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_account_password', nonce: GS_AJAX.nonce,
			gs_acc_vecchia: $f.find( '[name=gs_acc_vecchia]' ).val(),
			gs_acc_nuova: $f.find( '[name=gs_acc_nuova]' ).val(),
			gs_acc_nuova2: $f.find( '[name=gs_acc_nuova2]' ).val()
		} ).done( function ( res ) {
			$btn.prop( 'disabled', false );
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { $f[0].reset(); }
		} ).fail( function () { $btn.prop( 'disabled', false ); $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'submit', '.gs-form-account-email', function ( e ) {
		e.preventDefault();
		var $f = $( this ), $m = $f.find( '.gs-form-msg' ), $btn = $f.find( '.gs-account-email-invia' );
		$btn.prop( 'disabled', true );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_account_email', nonce: GS_AJAX.nonce,
			gs_acc_email: $f.find( '[name=gs_acc_email]' ).val(),
			gs_acc_password: $f.find( '[name=gs_acc_password]' ).val()
		} ).done( function ( res ) {
			$btn.prop( 'disabled', false );
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
		} ).fail( function () { $btn.prop( 'disabled', false ); $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-account-esporta', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $m = $btn.closest( 'details' ).find( '.gs-account-esporta-msg' );
		$m.removeClass( 'ok err' ).text( 'Preparazione…' );
		$.post( GS_AJAX.url, { action: 'gs_account_esporta', nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				if ( ! res || ! res.success ) { $m.addClass( 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); return; }
				var d = res.data.dati || {};
				var righe = [
					'I tuoi dati — Accademia della Sfoglia', '',
					'Nome: ' + ( d.nome || '' ),
					'Username: ' + ( d.username || '' ),
					'Email: ' + ( d.email || '' ),
					'Squadra: ' + ( d.squadra || '—' ),
					'Livello: ' + ( d.livello || '—' ),
					'Punti totali: ' + ( d.punti_totali || 0 ),
					'Data di nascita: ' + ( d.data_nascita || '—' ),
					'Stato account: ' + ( d.stato_account || '—' )
				];
				var blob = new Blob( [ righe.join( '\n' ) ], { type: 'text/plain;charset=utf-8' } );
				var url = URL.createObjectURL( blob );
				var a = document.createElement( 'a' );
				a.href = url; a.download = 'i-miei-dati.txt';
				document.body.appendChild( a ); a.click(); document.body.removeChild( a );
				setTimeout( function () { URL.revokeObjectURL( url ); }, 2000 );
				$m.addClass( 'ok' ).text( 'Scaricato.' );
			} )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-account-cancella-richiedi', function ( e ) {
		e.preventDefault();
		if ( ! window.confirm( 'Chiedere alla segreteria di eliminare il tuo account? La richiesta verrà esaminata a mano, non è immediata.' ) ) { return; }
		var $btn = $( this ), $m = $btn.closest( 'details' ).find( '.gs-account-cancella-msg' );
		$btn.prop( 'disabled', true );
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, { action: 'gs_account_richiedi_cancellazione', nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 1200 ); } else { $btn.prop( 'disabled', false ); }
			} )
			.fail( function () { $btn.prop( 'disabled', false ); $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Aiuto e Suggerimenti (gestore) ---
	$( document ).on( 'click', '.gs-aiuto-gestito', function ( e ) {
		e.preventDefault(); var $tr = $( this ).closest( 'tr' ); var $m = $tr.find( '.gs-aiuto-row-msg' );
		$m.removeClass( 'ok err' ).text( '…' );
		$.post( GS_AJAX.url, { action: 'gs_aiuto_gestito', nonce: GS_AJAX.nonce, aiuto: $( this ).data( 'aiuto' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-aiuto-rispondi', function ( e ) {
		e.preventDefault(); var $tr = $( this ).closest( 'tr' ); var $m = $tr.find( '.gs-aiuto-row-msg' );
		var testo = prompt( 'Scrivi la risposta da inviare via email alla sfoglina:' );
		if ( testo === null || ! testo.trim() ) return;
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, { action: 'gs_aiuto_rispondi', nonce: GS_AJAX.nonce, aiuto: $( this ).data( 'aiuto' ), testo: testo } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	// --- Messaggio di benvenuto ---
	$( document ).on( 'click', '.gs-percorso-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-percorso' );
		var $m = $f.find( '.gs-percorso-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		var dest = $f.find( '[name=destinatari]' ).val() || [];
		var data = { action: 'gs_percorso_salva', nonce: GS_AJAX.nonce, titolo: $f.find( '[name=titolo]' ).val(), testo: $f.find( '[name=testo]' ).val(), attivo: $f.find( '[name=attivo]' ).is( ':checked' ) ? 1 : '' };
		data['destinatari'] = dest;
		$.post( GS_AJAX.url, data ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	// --- Come funziona il Percorso ---
	$( document ).on( 'click', '.gs-come-funziona-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-come-funziona' );
		var $m = $f.find( '.gs-come-funziona-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		var data = { action: 'gs_come_funziona_salva', nonce: GS_AJAX.nonce, titolo: $f.find( '[name=titolo]' ).val(), testo: $f.find( '[name=testo]' ).val(), attivo: $f.find( '[name=attivo]' ).is( ':checked' ) ? 1 : '' };
		$.post( GS_AJAX.url, data ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	// --- Dettatura vocale sui campi messaggio (Web Speech API) ---
	var gsSpeechOK = ( ( 'webkitSpeechRecognition' in window ) || ( 'SpeechRecognition' in window ) )
		&& ! ( window.GS_AJAX && false === GS_AJAX.dettatura_vocale ); // il titolare può disattivarla in generale o per singola sfoglina
	var gsRec = null, gsRecBtn = null;
	function gsAddMic( $ta ) {
		if ( ! gsSpeechOK || $ta.next( '.gs-mic' ).length ) { return; }
		$ta.after(
			'<button type="button" class="gs-mic" title="Dettatura vocale">🎤</button>'
			+ '<span class="gs-mic-hint gs-hint">🎤 Clicca il microfono per scrivere dettando a voce invece che a mano; clicca di nuovo per fermarti.</span>'
		);
	}
	function gsStopRec() {
		if ( gsRec ) { try { gsRec.stop(); } catch ( e ) {} gsRec = null; }
		if ( gsRecBtn ) { gsRecBtn.removeClass( 'gs-mic-on' ); gsRecBtn = null; }
	}
	$( document ).on( 'click', '.gs-mic', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $ta = $btn.prev( 'textarea' );
		if ( gsRec && gsRecBtn && gsRecBtn[ 0 ] === $btn[ 0 ] ) { gsStopRec(); return; }
		gsStopRec();
		var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
		gsRec = new SR(); gsRec.lang = 'it-IT'; gsRec.interimResults = false; gsRec.continuous = true;
		gsRecBtn = $btn; $btn.addClass( 'gs-mic-on' );
		gsRec.onresult = function ( ev ) {
			var t = '';
			for ( var i = ev.resultIndex; i < ev.results.length; i++ ) { if ( ev.results[ i ].isFinal ) { t += ev.results[ i ][ 0 ].transcript; } }
			if ( t ) { var cur = $ta.val(); $ta.val( ( cur ? cur + ' ' : '' ) + t.trim() ); }
		};
		gsRec.onerror = function () { gsStopRec(); };
		gsRec.onend = function () { if ( gsRecBtn && gsRecBtn[ 0 ] === $btn[ 0 ] ) { $btn.removeClass( 'gs-mic-on' ); gsRec = null; gsRecBtn = null; } };
		try { gsRec.start(); } catch ( e2 ) { gsStopRec(); }
	} );
	// Prima era solo textarea[name=testo]: lasciava fuori la maggior parte dei
	// campi di testo lunghi del sito (procedimento, ingredienti, racconto,
	// motivazione, descrizione, contenuto…). Ora il microfono compare su
	// qualunque textarea della pagina, comprese quelle dentro i pannelli
	// (già presenti nella pagina, solo dentro un <details> chiuso: il
	// microfono si aggiunge comunque, si vede aprendo la sezione).
	$( function () { if ( gsSpeechOK ) { $( 'textarea' ).each( function () { gsAddMic( $( this ) ); } ); } } );

	// --- Occhietto mostra/nascondi password, su ogni campo password del sito ---
	function gsAddPwdEye( $pwd ) {
		if ( $pwd.parent().hasClass( 'gs-pwd-wrap' ) ) { return; }
		$pwd.wrap( '<span class="gs-pwd-wrap"></span>' );
		$pwd.after( '<button type="button" class="gs-pwd-eye" title="Mostra la password" aria-label="Mostra la password">👁️</button>' );
	}
	$( document ).on( 'click', '.gs-pwd-eye', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $pwd = $btn.prev( 'input' );
		var mostrata = 'text' === $pwd.attr( 'type' );
		$pwd.attr( 'type', mostrata ? 'password' : 'text' );
		$btn.text( mostrata ? '👁️' : '🙈' )
			.attr( 'title', mostrata ? 'Mostra la password' : 'Nascondi la password' )
			.attr( 'aria-label', mostrata ? 'Mostra la password' : 'Nascondi la password' );
	} );
	$( function () { $( 'input[type=password]' ).each( function () { gsAddPwdEye( $( this ) ); } ); } );

	// --- Abbonamenti ---
	$( document ).on( 'change', '.gs-abb-stato', function () {
		var $tr = $( this ).closest( 'tr' );
		var scaduto = 'scaduto' === $( this ).val();
		$tr.removeClass( 'gs-abb-attivo gs-abb-scaduto gs-lampeggia-rosso' )
			.addClass( scaduto ? 'gs-abb-scaduto gs-lampeggia-rosso' : 'gs-abb-attivo' );
	} );
	$( document ).on( 'click', '.gs-abb-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-abbonamenti' );
		var $m = $f.find( '.gs-abb-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		var stati = {}, scadenze = {};
		$f.find( 'tr[data-uid]' ).each( function () {
			var uid = $( this ).data( 'uid' );
			stati[ uid ] = $( this ).find( '.gs-abb-stato' ).val();
			scadenze[ uid ] = $( this ).find( '.gs-abb-scadenza' ).val();
		} );
		$.post( GS_AJAX.url, { action: 'gs_abbonamento_salva', nonce: GS_AJAX.nonce, stati: stati, scadenze: scadenze } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	// --- Fisarmonica + segna-letto sui titoli cliccabili (<details class="gs-inbox-item">) ---
	// L'evento "toggle" di <details> NON risale il DOM (non fa bubbling): la delega
	// jQuery normale ($(document).on('toggle', selettore, fn)) non si attiva mai.
	// Va ascoltato in fase di CATTURA con addEventListener nativo, unico modo per
	// cui funziona davvero. Vale ovunque si usi ".gs-inbox-item" (posta interna,
	// segreteria, aiuto, conversazioni, calendario, diario, consigli…).
	document.addEventListener( 'toggle', function ( e ) {
		var det = e.target;
		if ( ! det || 'DETAILS' !== det.tagName || ! det.open ) { return; }
		var eInbox = det.classList.contains( 'gs-inbox-item' );
		// ".gs-sezione-aiuto" (es. "Registra un bonifico" di Artigiani/Scuole)
		// passa oltre solo per generare l'identificativo del pagamento più
		// sotto — la fisarmonica e il segna-letto restano SOLO per
		// ".gs-inbox-item", altrimenti aprire un riquadro "Come funziona"
		// qualsiasi (ce ne sono in quasi ogni sezione) chiuderebbe i fratelli
		// e proverebbe a segnarlo come letto per sbaglio.
		if ( ! eInbox && ! det.classList.contains( 'gs-sezione-aiuto' ) ) { return; }

		if ( eInbox ) {
			// Fisarmonica: chiude gli altri messaggi aperti nella stessa lista.
			var fratelli = det.parentElement ? det.parentElement.children : [];
			for ( var i = 0; i < fratelli.length; i++ ) {
				if ( fratelli[ i ] !== det && fratelli[ i ].tagName === 'DETAILS' && fratelli[ i ].classList.contains( 'gs-inbox-item' ) ) {
					fratelli[ i ].open = false;
				}
			}

			// Segna come letto quando si apre — TRANNE in Posta interna (classe
			// "gs-inbox-posta"), dove aprire il messaggio per leggerlo non basta
			// più: deve restare a lampeggiare in rosso finché non si clicca
			// apposta il pulsante "LETTO" (Ennio, 13/08/2026).
			if ( det.classList.contains( 'gs-non-letto' ) && ! det.classList.contains( 'gs-inbox-posta' ) ) {
				var $it = $( det ); var id = $it.data( 'id' );
				$.post( GS_AJAX.url, { action: 'gs_inbox_letto', nonce: GS_AJAX.nonce, id: id } )
					.done( function () { $it.removeClass( 'gs-non-letto' ); $it.find( '.gs-dot' ).remove(); } );
			}
		}

		// Calendario Corsi: genera l'identificativo del pagamento QUI, una
		// volta sola all'apertura della scheda del cliente — non a ogni
		// clic su "Registra". Il pulsante lo manda al server, che rifiuta
		// un secondo invio con lo stesso identificativo: così un doppio
		// clic non registra lo stesso pagamento due volte (trovato il
		// 23/08/2026: prima non c'era nessuna protezione).
		var $payImp = det.querySelector ? det.querySelector( '.gs-cal-pay-imp' ) : null;
		if ( $payImp ) {
			$( det ).data( 'pay-rif', 'r' + Date.now() + Math.random().toString( 36 ).slice( 2 ) );
		}

		// Stesso meccanismo per il bonifico di Artigiani della Pasta e Scuole
		// di Cucina (25/08/2026): l'identificativo si genera all'apertura di
		// "Registra un bonifico", non a ogni clic.
		var $payForm = det.querySelector ? det.querySelector( '.gs-form-art-pagamento, .gs-form-scu-pagamento' ) : null;
		if ( $payForm ) {
			$( $payForm ).data( 'rif', 'r' + Date.now() + Math.random().toString( 36 ).slice( 2 ) );
		}
	}, true );

	// Posta interna: pulsante "LETTO" dedicato — ferma il lampeggio rosso e
	// mostra la conferma verde, senza chiudere/aprire il messaggio.
	$( document ).on( 'click', '.gs-inbox-segna-letto', function ( e ) {
		e.preventDefault();
		e.stopPropagation();
		var $btn = $( this );
		var $item = $btn.closest( '.gs-inbox-posta' );
		var id = $item.data( 'id' );
		$btn.prop( 'disabled', true ).text( '…' );
		$.post( GS_AJAX.url, { action: 'gs_inbox_letto', nonce: GS_AJAX.nonce, id: id } )
			.done( function ( res ) {
				if ( res && res.success ) {
					$item.removeClass( 'gs-non-letto gs-lampeggia-rosso' );
					$item.find( '.gs-dot' ).remove();
					$btn.remove();
					$item.find( '.gs-inbox-letto-ok' ).show();
				} else {
					$btn.prop( 'disabled', false ).text( '✓ Segna come letto' );
				}
			} )
			.fail( function () { $btn.prop( 'disabled', false ).text( 'LETTO' ); } );
	} );

	// Libreria Video: segna la lezione come vista quando la sfoglina apre il lettore
	// (stessa ragione tecnica sopra: "toggle" non fa bubbling, serve addEventListener nativo).
	document.addEventListener( 'toggle', function ( e ) {
		var det = e.target;
		if ( ! det || 'DETAILS' !== det.tagName || ! det.classList.contains( 'gs-lezione-apertura' ) || ! det.open ) { return; }
		var lezione = det.getAttribute( 'data-lezione' );
		if ( ! lezione ) { return; }
		// Una lezione si segna una volta sola per caricamento di pagina:
		// "toggle" scatta a ogni apertura, e se il file venisse eseguito due
		// volte (succede con i JavaScript combinati da SiteGround Optimizer
		// — il difetto già corretto sul pulsante "Accedi") ogni apertura
		// manderebbe due richieste simultanee. Il contrassegno vive
		// sull'elemento, quindi vale anche con due ascoltatori registrati
		// (25/08/2026).
		if ( det.getAttribute( 'data-gs-vista-inviata' ) ) { return; }
		det.setAttribute( 'data-gs-vista-inviata', '1' );
		$.post( GS_AJAX.url, { action: 'gs_lezione_segna_vista', nonce: GS_AJAX.nonce, lezione: lezione } );
	}, true );

	$( document ).on( 'click', '.gs-inbox-rispondi', function ( e ) {
		e.preventDefault(); var $f = $( this ).closest( '.gs-form-inbox-risp' ); var $m = $f.find( '.gs-inbox-msg' );
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, { action: 'gs_inbox_rispondi', nonce: GS_AJAX.nonce, id: $f.data( 'id' ), testo: $f.find( '[name=testo]' ).val() } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-inbox-modifica', function ( e ) {
		e.preventDefault();
		var $item = $( this ).closest( '.gs-inbox-item' );
		var ogg = ( $item.find( '.gs-inbox-oggetto' ).clone().children().remove().end().text() || '' ).trim();
		var corpo = $item.find( '.gs-inbox-testo' ).text();
		var nOgg = prompt( 'Oggetto:', ogg ); if ( nOgg === null ) return;
		var nCorpo = prompt( 'Testo del messaggio:', corpo ); if ( nCorpo === null ) return;
		var $m = $( this ).closest( '.gs-form-inbox-risp' ).find( '.gs-inbox-msg' );
		$.post( GS_AJAX.url, { action: 'gs_inbox_modifica', nonce: GS_AJAX.nonce, id: $item.data( 'id' ), oggetto: nOgg, corpo: nCorpo } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 600 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-inbox-elimina', function ( e ) {
		e.preventDefault(); if ( ! confirm( 'Spostare nel cestino? Potrai ripristinarlo.' ) ) return;
		var $item = $( this ).closest( '.gs-inbox-item' ); var $m = $( this ).closest( '.gs-form-inbox-risp' ).find( '.gs-inbox-msg' );
		$.post( GS_AJAX.url, { action: 'gs_inbox_elimina', nonce: GS_AJAX.nonce, id: $item.data( 'id' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 600 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-inbox-ripristina', function ( e ) {
		e.preventDefault(); var $tr = $( this ).closest( 'tr' ); var $m = $tr.find( '.gs-inbox-tmsg' );
		$.post( GS_AJAX.url, { action: 'gs_inbox_ripristina', nonce: GS_AJAX.nonce, id: $( this ).data( 'id' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 600 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-inbox-ripara-registrazioni', function ( e ) {
		e.preventDefault();
		var $m = $( this ).siblings( '.gs-inbox-ripara-esito' );
		$m.removeClass( 'ok err' ).text( 'Controllo…' );
		$.post( GS_AJAX.url, { action: 'gs_inbox_ripara_registrazioni', nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
			} )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Biografia Vetrina ---
	$( document ).on( 'click', '.gs-bio-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-bio' );
		var $m = $f.find( '.gs-bio-msg' );
		var limite = gsLimiteByte();
		var troppoGrande = false, nomeGrande = '';
		var $files = $f.find( '.gs-bio-foto-file, .gs-msg-file' );
		$files.each( function () {
			var f = this._gsBlob || ( this.files && this.files[ 0 ] );
			if ( f && limite > 0 && f.size > limite ) {
				troppoGrande = true;
				nomeGrande = ( this.files && this.files[ 0 ] ) ? this.files[ 0 ].name : '';
			}
		} );
		if ( troppoGrande ) {
			$m.removeClass( 'ok' ).addClass( 'err' ).text( 'Il file ' + nomeGrande + ' supera il limite di ' + GS_MEDIA.limite_mb + ' MB. Riduci le dimensioni e riprova.' );
			return;
		}
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		var fd = new FormData();
		fd.append( 'action', 'gs_bio_salva' );
		fd.append( 'nonce', GS_AJAX.nonce );
		fd.append( 'testo', $f.find( '[name=testo]' ).val() );
		var $foto = $f.find( '.gs-bio-foto-file' )[ 0 ];
		if ( $foto && $foto.files && $foto.files.length ) {
			fd.append( 'foto', $foto._gsBlob || $foto.files[ 0 ], $foto._gsBlob ? $foto._gsName : $foto.files[ 0 ].name );
		}
		var $med = $f.find( '.gs-msg-file' )[ 0 ];
		if ( $med && $med.files && $med.files.length ) {
			fd.append( 'media', $med._gsBlob || $med.files[ 0 ], $med._gsBlob ? $med._gsName : $med.files[ 0 ].name );
		}
		$.ajax( { url: GS_AJAX.url, method: 'POST', data: fd, processData: false, contentType: false } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-bio-foto-rimuovi', function ( e ) {
		e.preventDefault(); if ( ! confirm( 'Rimuovere la foto profilo?' ) ) return;
		$.post( GS_AJAX.url, { action: 'gs_bio_foto_rimuovi', nonce: GS_AJAX.nonce } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } } );
	} );
	$( document ).on( 'click', '.gs-bio-rimuovi', function ( e ) {
		e.preventDefault(); if ( ! confirm( 'Rimuovere questo elemento dalla biografia?' ) ) return;
		$.post( GS_AJAX.url, { action: 'gs_bio_rimuovi', nonce: GS_AJAX.nonce, i: $( this ).data( 'i' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } } );
	} );
	$( document ).on( 'click', '.gs-bio-ripristina', function ( e ) {
		e.preventDefault();
		$.post( GS_AJAX.url, { action: 'gs_bio_cestino_ripristina', nonce: GS_AJAX.nonce, id: $( this ).data( 'id' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } } );
	} );
	$( document ).on( 'click', '.gs-bio-modera', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '[data-uid]' ); var $m = $box.find( '.gs-bio-mod-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_bio_modera', nonce: GS_AJAX.nonce, uid: $( this ).data( 'uid' ), esito: $( this ).data( 'esito' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	// --- Caroselli scorrevoli: frecce + puntini ---
	// Il passo di scorrimento deve essere almeno quanto una scheda intera
	// (più il suo spazio): con "scroll-snap-type: x mandatory" sulla pista,
	// un passo più corto viene annullato dal browser, che torna da solo al
	// punto di aggancio più vicino — di partenza, se il passo non arriva
	// almeno alla scheda successiva. Su schermi stretti l'80% della
	// larghezza visibile può essere più corto di una scheda: le frecce
	// sembravano non fare nulla (segnalato da Ennio il 19/08/2026, "le
	// schede devono muoversi"). "$pista.scrollBy" qui sotto era anche un
	// controllo morto: scrollBy è un metodo dell'elemento vero, non
	// dell'oggetto jQuery, quindi il ramo nativo non partiva mai — corretto
	// leggendolo su $pista[0].
	function gsCarosselloPasso( $pista ) {
		var $prima = $pista.children().first();
		var passoScheda = $prima.length ? $prima.outerWidth( true ) : 0;
		return Math.max( passoScheda, Math.round( $pista.width() * 0.8 ) ) || 220;
	}
	// Scorrimento lento e morbido invece dello scatto rapido del browser
	// (behavior:'smooth' nativo dura solo qualche centesimo di secondo, non
	// regolabile) — richiesto da Ennio il 19/08/2026 ("le voglio che
	// scorrono lentamente"). Anima da sola, un fotogramma alla volta, alla
	// STESSA velocità in pixel/secondo del nastro fisso sotto il menu
	// (GS_NASTRO_PICCOLO_PX_AL_SECONDO più sotto in questo file), così il
	// movimento è coerente in tutto il sito invece di avere un ritmo a sé
	// — richiesto da Ennio il 19/08/2026 ("segui la velocità del nastro").
	var GS_CAROSELLO_PX_AL_SECONDO = 54.5;
	function gsCarosselloScorriA( pista, destra ) {
		var partenza = pista.scrollLeft;
		var distanza = destra - partenza;
		if ( ! distanza ) { return; }
		var durata = Math.max( 300, Math.abs( distanza ) / GS_CAROSELLO_PX_AL_SECONDO * 1000 );
		var inizio = null;
		function passo( adesso ) {
			if ( inizio === null ) { inizio = adesso; }
			var t = Math.min( 1, ( adesso - inizio ) / durata );
			var t2 = t < .5 ? 2 * t * t : 1 - Math.pow( -2 * t + 2, 2 ) / 2; // ease-in-out
			pista.scrollLeft = partenza + distanza * t2;
			if ( t < 1 ) { requestAnimationFrame( passo ); }
		}
		requestAnimationFrame( passo );
	}
	$( document ).on( 'click', '.gs-carosello-freccia', function ( e ) {
		e.preventDefault();
		var $pista = $( '#' + $( this ).data( 'target' ) );
		var pista = $pista[ 0 ];
		if ( ! pista ) { return; }
		var passo = gsCarosselloPasso( $pista );
		var destra = pista.scrollLeft + ( $( this ).hasClass( 'prev' ) ? -passo : passo );
		gsCarosselloScorriA( pista, destra );
	} );
	$( document ).on( 'scroll', '.gs-carosello-pista', function () {
		var $pista = $( this );
		var $punti = $pista.closest( '.gs-carosello-wrap' ).find( '.gs-carosello-punti span' );
		if ( ! $punti.length ) { return; }
		var max = $pista[ 0 ].scrollWidth - $pista[ 0 ].clientWidth;
		var pct = max > 0 ? $pista.scrollLeft() / max : 0;
		var i = Math.min( $punti.length - 1, Math.round( pct * ( $punti.length - 1 ) ) );
		$punti.removeClass( 'attivo' ).eq( i ).addClass( 'attivo' );
	} );

	// --- Caroselli: scorrimento automatico, uno alla volta, si ferma al
	// passaggio del mouse/dito e riparte quando ci si allontana. ---
	$( '.gs-carosello-wrap[data-autoplay="1"]' ).each( function () {
		var $wrap = $( this );
		var $pista = $wrap.find( '.gs-carosello-pista' );
		var secondi = parseInt( $wrap.data( 'velocita' ), 10 ) || 4;
		var timer = null;
		function avanti() {
			var passo = gsCarosselloPasso( $pista );
			var max = $pista[ 0 ].scrollWidth - $pista[ 0 ].clientWidth;
			var destra = $pista.scrollLeft() >= max - 4 ? 0 : $pista.scrollLeft() + passo;
			gsCarosselloScorriA( $pista[ 0 ], destra );
		}
		function riparti() { fermati(); timer = setInterval( avanti, secondi * 1000 ); }
		function fermati() { if ( timer ) { clearInterval( timer ); timer = null; } }
		riparti();
		$wrap.on( 'mouseenter touchstart', fermati );
		$wrap.on( 'mouseleave touchend', riparti );
	} );

	// --- [gs_carosello_sfogline]: filtri per livello, frecce, finestra di
	// dettaglio con i dati reali (letti dall'attributo data-gs-cs-dati,
	// un JSON scritto da gs_sc_carosello_sfogline() in caroselli.php).
	document.querySelectorAll( '.gs-cs' ).forEach( function ( radice ) {
		var dati = [];
		try { dati = JSON.parse( radice.getAttribute( 'data-gs-cs-dati' ) || '[]' ); } catch ( e ) { dati = []; }
		var carrello = radice.querySelector( '.gs-cs__carrello' );
		if ( ! carrello ) { return; }
		var schede = Array.prototype.slice.call( carrello.querySelectorAll( '.gs-cs__scheda' ) );
		var soloAttive = radice.querySelector( '.gs-cs__solo-attive' );

		function applicaFiltri() {
			var attivo = radice.querySelector( '.gs-cs__filtro.is-active' );
			var filtro = attivo ? attivo.getAttribute( 'data-filtro' ) : 'tutte';
			schede.forEach( function ( s ) {
				var passaLivello = filtro === 'tutte' || s.getAttribute( 'data-livello' ) === filtro;
				var passaAttiva = ! soloAttive || ! soloAttive.checked || s.getAttribute( 'data-attiva' ) === '1';
				s.hidden = ! ( passaLivello && passaAttiva );
			} );
		}
		radice.querySelectorAll( '.gs-cs__filtro' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				radice.querySelectorAll( '.gs-cs__filtro' ).forEach( function ( b ) { b.classList.remove( 'is-active' ); } );
				btn.classList.add( 'is-active' );
				applicaFiltri();
			} );
		} );
		if ( soloAttive ) { soloAttive.addEventListener( 'change', applicaFiltri ); }

		var prec = radice.querySelector( '.gs-cs__prec' );
		var succ = radice.querySelector( '.gs-cs__succ' );
		if ( prec ) { prec.addEventListener( 'click', function () { gsCarosselloScorriA( carrello, carrello.scrollLeft - gsCarosselloPasso( $( carrello ) ) ); } ); }
		if ( succ ) { succ.addEventListener( 'click', function () { gsCarosselloScorriA( carrello, carrello.scrollLeft + gsCarosselloPasso( $( carrello ) ) ); } ); }

		// Scorrimento automatico (richiesto da Ennio il 21/08/2026, "devono
		// scorrere automaticamente") — stessa velocità/pausa al passaggio del
		// mouse/dito degli altri caroselli del sito. Escluso il carrello
		// statico dei Corsi (gs-cs__carrello-statico): quello non scorre per
		// scelta, vedi gs_carosello_gs_cs_statico_html() in caroselli.php.
		if ( schede.length > 1 && ! carrello.classList.contains( 'gs-cs__carrello-statico' ) ) {
			var $carrello = $( carrello );
			var timerCs = null;
			function avantiCs() {
				var passo = gsCarosselloPasso( $carrello );
				var max = carrello.scrollWidth - carrello.clientWidth;
				var destra = carrello.scrollLeft >= max - 4 ? 0 : carrello.scrollLeft + passo;
				gsCarosselloScorriA( carrello, destra );
			}
			function ripartiCs() { fermatiCs(); timerCs = setInterval( avantiCs, 4000 ); }
			function fermatiCs() { if ( timerCs ) { clearInterval( timerCs ); timerCs = null; } }
			ripartiCs();
			$carrello.on( 'mouseenter touchstart', fermatiCs );
			$carrello.on( 'mouseleave touchend', ripartiCs );
		}

		var finestra = radice.querySelector( '.gs-cs__finestra' );
		if ( ! finestra ) { return; }
		radice.querySelectorAll( '[data-apri-scheda]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var d = dati[ parseInt( btn.getAttribute( 'data-apri-scheda' ), 10 ) ];
				if ( ! d ) { return; }
				finestra.querySelector( '.gs-cs__finestra-titolo' ).textContent = d.nome;
				finestra.querySelector( '.gs-cs__finestra-dati' ).innerHTML =
					'<div class="gs-cs__dato"><dt>Livello</dt><dd>' + d.livello + '</dd></div>' +
					'<div class="gs-cs__dato"><dt>Punti</dt><dd>' + d.punti + '</dd></div>' +
					'<div class="gs-cs__dato"><dt>Streak</dt><dd>' + d.streak + ' sett.</dd></div>' +
					'<div class="gs-cs__dato"><dt>Badge</dt><dd>' + d.badge + '</dd></div>';
				var listaBadge = finestra.querySelector( '.gs-cs__badge' );
				listaBadge.innerHTML = ( d.badgeNomi && d.badgeNomi.length )
					? d.badgeNomi.map( function ( b ) { return '<li>' + String( b ).replace( /</g, '&lt;' ) + '</li>'; } ).join( '' )
					: '<li>Nessun badge ancora</li>';
				var cta = finestra.querySelector( '.gs-cs__cta' );
				if ( d.attiva && d.url ) {
					cta.textContent = 'Vai alla Vetrina';
					cta.href = d.url;
					cta.removeAttribute( 'aria-disabled' );
				} else {
					cta.textContent = 'Vetrina non attiva';
					cta.href = '#';
					cta.setAttribute( 'aria-disabled', 'true' );
				}
				finestra.showModal();
			} );
		} );
		var chiudiCs = finestra.querySelector( '.gs-cs__chiudi' );
		if ( chiudiCs ) { chiudiCs.addEventListener( 'click', function () { finestra.close(); } ); }
		finestra.addEventListener( 'click', function ( e ) { if ( e.target === finestra ) { finestra.close(); } } );
	} );

	// --- Pannello "Caroselli per la Home Page": copia shortcode + salva ---
	$( document ).on( 'click', '.gs-copia-shortcode', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var testo = $btn.data( 'sc' );
		var originale = $btn.text();
		function fatto() { $btn.text( '✅ Copiato' ); setTimeout( function () { $btn.text( originale ); }, 1600 ); }
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( testo ).then( fatto );
		} else {
			var $tmp = $( '<textarea readonly></textarea>' ).val( testo ).css( { position: 'fixed', top: '-1000px' } ).appendTo( 'body' );
			$tmp[ 0 ].select();
			try { document.execCommand( 'copy' ); fatto(); } catch ( err ) { /* copia a mano */ }
			$tmp.remove();
		}
	} );
	$( document ).on( 'click', '.gs-caroselli-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-caroselli' );
		var $m = $f.find( '.gs-caroselli-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		// serializeArray() invece di elencare i campi a mano (bug reale
		// scoperto il 17/08/2026: i campi del nastro — nastro_attivo,
		// nastro_max, nastro_mostra_* — non venivano mai inviati, quindi
		// "Salva" non li salvava mai davvero). Così qualunque campo nuovo
		// aggiunto al form in futuro viene inviato automaticamente, senza
		// doverlo ricordare qui.
		var dati = $f.serializeArray();
		dati.push( { name: 'action', value: 'gs_caroselli_salva' }, { name: 'nonce', value: GS_AJAX.nonce } );
		$.post( GS_AJAX.url, $.param( dati ) ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Pulsanti "Tutti"/"Nessuno" per le liste di esclusione dal nastro
	// (richiesto da Ennio, 17/08/2026): spuntano/tolgono lo spunta a tutte le
	// checkbox di quella lista in un colpo solo, invece di doverle cliccare
	// una per una su elenchi anche lunghi.
	$( document ).on( 'click', '.gs-nastro-esc-tutti', function () {
		var spuntare = $( this ).data( 'stato' ) == 1;
		$( $( this ).data( 'target' ) ).prop( 'checked', spuntare );
	} );

	// --- Vetrina pubblica: attivazione a token dentro "La Mia Sfoglia" ---
	$( document ).on( 'click', '.gs-vetrina-attiva-token', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $m = $btn.closest( 'p' ).find( '.gs-vetrina-attiva-msg' );
		$m.removeClass( 'ok err' ).text( 'Attivazione…' );
		$.post( GS_AJAX.url, { action: 'gs_vetrina_attiva_token', nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
			} )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Gli Artigiani della Pasta: pannello di autogestione del partner ---
	$( document ).on( 'click', '.gs-art-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-art' );
		var $m = $f.find( '.gs-art-msg' );
		var $logo = $f.find( '.gs-art-logo-file' )[ 0 ];
		var $foto = $f.find( '.gs-art-foto-file' )[ 0 ];
		var sceltoLogo = gsFileDaInviare( $( $logo ) );
		var sceltoFoto = gsFileDaInviare( $( $foto ) );
		var errore = gsMessaggioSeTroppoGrande( sceltoLogo ) || gsMessaggioSeTroppoGrande( sceltoFoto );
		if ( errore ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( errore ); return; }
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		var fd = new FormData();
		fd.append( 'action', 'gs_art_salva' );
		fd.append( 'nonce', GS_AJAX.nonce );
		fd.append( 'comune', $f.find( '[name=comune]' ).val() );
		fd.append( 'testo', $f.find( '[name=testo]' ).val() );
		fd.append( 'youtube', $f.find( '[name=youtube]' ).val() );
		fd.append( 'indirizzo', $f.find( '[name=indirizzo]' ).val() );
		fd.append( 'email', $f.find( '[name=email]' ).val() );
		if ( sceltoLogo ) { fd.append( 'logo', sceltoLogo.file, sceltoLogo.nome ); }
		if ( sceltoFoto ) { fd.append( 'foto', sceltoFoto.file, sceltoFoto.nome ); }
		$.ajax( { url: GS_AJAX.url, method: 'POST', data: fd, processData: false, contentType: false } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-art-logo-rimuovi', function ( e ) {
		e.preventDefault(); if ( ! confirm( 'Rimuovere il logo?' ) ) return;
		$.post( GS_AJAX.url, { action: 'gs_art_logo_rimuovi', nonce: GS_AJAX.nonce } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } } );
	} );
	$( document ).on( 'click', '.gs-art-media-rimuovi', function ( e ) {
		e.preventDefault(); if ( ! confirm( 'Rimuovere questa foto dalla galleria?' ) ) return;
		$.post( GS_AJAX.url, { action: 'gs_art_media_rimuovi', nonce: GS_AJAX.nonce, i: $( this ).data( 'i' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } } );
	} );

	// --- Gli Artigiani della Pasta: copia link (vetrina pubblica + pannello) ---
	$( document ).on( 'click', '.gs-art-copia-link', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var link = $btn.data( 'link' );
		var testoOriginale = $btn.text();
		function fatto() { $btn.text( '✅ Copiato!' ); setTimeout( function () { $btn.text( testoOriginale ); }, 1800 ); }
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( link ).then( fatto )['catch']( function () { gsArtCopiaFallback( link, fatto ); } );
		} else {
			gsArtCopiaFallback( link, fatto );
		}
	} );
	function gsArtCopiaFallback( testo, poi ) {
		var $tmp = $( '<textarea readonly></textarea>' ).val( testo ).css( { position: 'fixed', top: '-1000px' } ).appendTo( 'body' );
		$tmp[ 0 ].select();
		try { document.execCommand( 'copy' ); poi(); } catch ( err ) { /* niente da fare, l'utente può copiare a mano */ }
		$tmp.remove();
	}

	// --- Gli Artigiani della Pasta: modulo di contatto pubblico (vetrina) ---
	$( document ).on( 'click', '.gs-art-contatta-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-art-form-contatto' );
		var $m = $f.find( '.gs-art-form-msg' );
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_art_contatta', nonce: GS_AJAX.nonce,
			artigiano: $f.data( 'artigiano' ),
			nome: $f.find( '[name=nome]' ).val(),
			email: $f.find( '[name=email]' ).val(),
			messaggio: $f.find( '[name=messaggio]' ).val(),
			gs_hp: $f.find( '[name=gs_hp]' ).val()
		} )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { $f.find( 'input[type=text], input[type=email], textarea' ).val( '' ); }
			} )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Gli Artigiani della Pasta: pannello di amministrazione (Plancia) ---
	// Crea un partner (Artigiani della Pasta / Scuole di Cucina), comune ai
	// due pulsanti gemelli: se l'email è già di un account esistente che non
	// è già un partner (una sfoglina, un amministratore…), il server chiede
	// conferma prima di trasformarlo — un confirm() ripete il motivo e
	// rimanda con conferma:1 (25/08/2026).
	function gsCreaPartnerInvia( $btn, action, formClass, msgClass ) {
		if ( $btn.prop( 'disabled' ) ) { return; }
		var $f = $btn.closest( formClass );
		var $m = $f.find( msgClass );
		var dati = {
			action: action, nonce: GS_AJAX.nonce,
			nome: $f.find( '[name=nome]' ).val(),
			comune: $f.find( '[name=comune]' ).val(),
			email: $f.find( '[name=email]' ).val()
		};

		function manda() {
			$m.removeClass( 'ok err' ).text( 'Creazione…' );
			$btn.prop( 'disabled', true );
			$.post( GS_AJAX.url, dati )
				.done( function ( res ) {
					$btn.prop( 'disabled', false );
					if ( res && res.data && res.data.conferma && ! dati.conferma ) {
						$m.removeClass( 'ok err' ).text( '' );
						if ( confirm( res.data.message + '\n\nConfermi comunque?' ) ) {
							dati.conferma = 1;
							manda();
						}
						return;
					}
					$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
					if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
				} )
				.fail( function () { $btn.prop( 'disabled', false ); $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
		}
		manda();
	}
	$( document ).on( 'click', '.gs-art-crea-invia', function ( e ) {
		e.preventDefault();
		gsCreaPartnerInvia( $( this ), 'gs_art_crea', '.gs-form-art-crea', '.gs-art-crea-msg' );
	} );
	$( document ).on( 'click', '.gs-art-modera', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '[data-art]' ); var $m = $box.find( '.gs-art-mod-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_art_modera', nonce: GS_AJAX.nonce, art: $( this ).data( 'art' ), esito: $( this ).data( 'esito' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	// Bonifico di Artigiani della Pasta / Scuole di Cucina, comune ai due
	// pulsanti gemelli: identificativo anti-doppio-clic (generato all'apertura
	// del modulo, vedi il "toggle" più sopra), pulsante disabilitato durante
	// l'invio, e se il server risponde con "conferma" (scadenza che non
	// allunga niente) un confirm() che ripete il motivo e rimanda la stessa
	// richiesta con conferma:1 (25/08/2026).
	function gsPagamentoPartnerInvia( $btn, action, idKey ) {
		if ( $btn.prop( 'disabled' ) ) { return; }
		var $f = $btn.closest( '.gs-form-art-pagamento, .gs-form-scu-pagamento' );
		var $m = $f.find( '.gs-art-pag-msg, .gs-scu-pag-msg' );
		var dati = {
			action: action, nonce: GS_AJAX.nonce,
			importo: $f.find( '[name=importo]' ).val(),
			scadenza: $f.find( '[name=scadenza]' ).val(),
			note: $f.find( '[name=note]' ).val(),
			rif: $f.data( 'rif' ) || ''
		};
		dati[ idKey ] = $f.data( idKey );

		function manda() {
			$m.removeClass( 'ok err' ).text( 'Registrazione…' );
			$btn.prop( 'disabled', true );
			$.post( GS_AJAX.url, dati )
				.done( function ( res ) {
					$btn.prop( 'disabled', false );
					if ( res && res.data && res.data.conferma && ! dati.conferma ) {
						$m.removeClass( 'ok err' ).text( '' );
						if ( confirm( res.data.message + '\n\nConfermi comunque?' ) ) {
							dati.conferma = 1;
							manda();
						}
						return;
					}
					$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
					if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
				} )
				.fail( function () { $btn.prop( 'disabled', false ); $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
		}
		manda();
	}
	$( document ).on( 'click', '.gs-art-pagamento-invia', function ( e ) {
		e.preventDefault();
		gsPagamentoPartnerInvia( $( this ), 'gs_art_pagamento', 'art' );
	} );
	$( document ).on( 'click', '.gs-art-cestina', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Spostare questo artigiano nel cestino? Se l\'account era stato reso "artigiano" da questa vetrina, torna una sfoglina normale.' ) ) return;
		$.post( GS_AJAX.url, { action: 'gs_art_cestina', nonce: GS_AJAX.nonce, art: $( this ).data( 'art' ) } )
			.done( function ( res ) {
				if ( res && res.success ) { alert( res.data.message ); gsReloadMantenendoPosizione(); }
				else { alert( res && res.data ? res.data.message : 'Errore.' ); }
			} );
	} );
	$( document ).on( 'click', '.gs-art-ripristina', function ( e ) {
		e.preventDefault();
		$.post( GS_AJAX.url, { action: 'gs_art_ripristina', nonce: GS_AJAX.nonce, art: $( this ).data( 'art' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } } );
	} );

	// --- Le Scuole di Cucina: pannello di autogestione del partner ---
	$( document ).on( 'click', '.gs-scu-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-scu' );
		var $m = $f.find( '.gs-scu-msg' );
		var $logo = $f.find( '.gs-scu-logo-file' )[ 0 ];
		var $foto = $f.find( '.gs-scu-foto-file' )[ 0 ];
		var sceltoLogo = gsFileDaInviare( $( $logo ) );
		var sceltoFoto = gsFileDaInviare( $( $foto ) );
		var errore = gsMessaggioSeTroppoGrande( sceltoLogo ) || gsMessaggioSeTroppoGrande( sceltoFoto );
		if ( errore ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( errore ); return; }
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		var fd = new FormData();
		fd.append( 'action', 'gs_scu_salva' );
		fd.append( 'nonce', GS_AJAX.nonce );
		fd.append( 'comune', $f.find( '[name=comune]' ).val() );
		fd.append( 'testo', $f.find( '[name=testo]' ).val() );
		fd.append( 'youtube', $f.find( '[name=youtube]' ).val() );
		fd.append( 'indirizzo', $f.find( '[name=indirizzo]' ).val() );
		fd.append( 'email', $f.find( '[name=email]' ).val() );
		if ( sceltoLogo ) { fd.append( 'logo', sceltoLogo.file, sceltoLogo.nome ); }
		if ( sceltoFoto ) { fd.append( 'foto', sceltoFoto.file, sceltoFoto.nome ); }
		$.ajax( { url: GS_AJAX.url, method: 'POST', data: fd, processData: false, contentType: false } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-scu-logo-rimuovi', function ( e ) {
		e.preventDefault(); if ( ! confirm( 'Rimuovere il logo?' ) ) return;
		$.post( GS_AJAX.url, { action: 'gs_scu_logo_rimuovi', nonce: GS_AJAX.nonce } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } } );
	} );
	$( document ).on( 'click', '.gs-scu-media-rimuovi', function ( e ) {
		e.preventDefault(); if ( ! confirm( 'Rimuovere questa foto dalla galleria?' ) ) return;
		$.post( GS_AJAX.url, { action: 'gs_scu_media_rimuovi', nonce: GS_AJAX.nonce, i: $( this ).data( 'i' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } } );
	} );

	// --- Le Scuole di Cucina: copia link (vetrina pubblica + pannello) ---
	$( document ).on( 'click', '.gs-scu-copia-link', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var link = $btn.data( 'link' );
		var testoOriginale = $btn.text();
		function fatto() { $btn.text( '✅ Copiato!' ); setTimeout( function () { $btn.text( testoOriginale ); }, 1800 ); }
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( link ).then( fatto )['catch']( function () { gsArtCopiaFallback( link, fatto ); } );
		} else {
			gsArtCopiaFallback( link, fatto );
		}
	} );

	// --- Le Scuole di Cucina: modulo di contatto pubblico (vetrina) ---
	$( document ).on( 'click', '.gs-scu-contatta-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-scu-form-contatto' );
		var $m = $f.find( '.gs-scu-form-msg' );
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_scu_contatta', nonce: GS_AJAX.nonce,
			scuola: $f.data( 'scuola' ),
			nome: $f.find( '[name=nome]' ).val(),
			email: $f.find( '[name=email]' ).val(),
			messaggio: $f.find( '[name=messaggio]' ).val(),
			gs_hp: $f.find( '[name=gs_hp]' ).val()
		} )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { $f.find( 'input[type=text], input[type=email], textarea' ).val( '' ); }
			} )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Le Scuole di Cucina: pannello di amministrazione (Plancia) ---
	$( document ).on( 'click', '.gs-scu-crea-invia', function ( e ) {
		e.preventDefault();
		gsCreaPartnerInvia( $( this ), 'gs_scu_crea', '.gs-form-scu-crea', '.gs-scu-crea-msg' );
	} );
	$( document ).on( 'click', '.gs-scu-modera', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '[data-scu]' ); var $m = $box.find( '.gs-scu-mod-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_scu_modera', nonce: GS_AJAX.nonce, scu: $( this ).data( 'scu' ), esito: $( this ).data( 'esito' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-scu-pagamento-invia', function ( e ) {
		e.preventDefault();
		gsPagamentoPartnerInvia( $( this ), 'gs_scu_pagamento', 'scu' );
	} );
	$( document ).on( 'click', '.gs-scu-cestina', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Spostare questa scuola nel cestino? Se l\'account era stato reso "scuola_cucina" da questa vetrina, torna una sfoglina normale.' ) ) return;
		$.post( GS_AJAX.url, { action: 'gs_scu_cestina', nonce: GS_AJAX.nonce, scu: $( this ).data( 'scu' ) } )
			.done( function ( res ) {
				if ( res && res.success ) { alert( res.data.message ); gsReloadMantenendoPosizione(); }
				else { alert( res && res.data ? res.data.message : 'Errore.' ); }
			} );
	} );
	$( document ).on( 'click', '.gs-scu-ripristina', function ( e ) {
		e.preventDefault();
		$.post( GS_AJAX.url, { action: 'gs_scu_ripristina', nonce: GS_AJAX.nonce, scu: $( this ).data( 'scu' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } } );
	} );

	$( document ).on( 'click', '.gs-aiuto-elimina', function ( e ) {
		e.preventDefault(); if ( ! confirm( 'Spostare nel cestino? Potrai ripristinarlo.' ) ) return;
		var $b = $( this ), $m = $b.closest( '.gs-inbox-corpo' ).find( '.gs-aiuto-row-msg' );
		$m.removeClass( 'ok err' ).text( '…' );
		$.post( GS_AJAX.url, { action: 'gs_aiuto_elimina', nonce: GS_AJAX.nonce, aiuto: $b.data( 'aiuto' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 600 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-aiuto-ripristina', function ( e ) {
		e.preventDefault(); var $tr = $( this ).closest( 'tr' ), $m = $tr.find( '.gs-aiuto-trow-msg' );
		$.post( GS_AJAX.url, { action: 'gs_aiuto_ripristina', nonce: GS_AJAX.nonce, aiuto: $( this ).data( 'aiuto' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 600 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	// --- Ricettario delle Famiglie ---
	$( document ).on( 'click', '.gs-ricetta-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-ricetta' );
		var $m = $f.find( '.gs-ricetta-msg' );
		if ( ! $f.find( '[name=titolo]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il nome della ricetta.' ); return; }
		var scelto = gsFileDaInviare( $f.find( '.gs-msg-file' ) );
		var errore = gsMessaggioSeTroppoGrande( scelto );
		if ( errore ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( errore ); return; }
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		var fd = new FormData();
		fd.append( 'action', 'gs_ricetta_invia' );
		fd.append( 'nonce', GS_AJAX.nonce );
		fd.append( 'titolo', $f.find( '[name=titolo]' ).val() );
		fd.append( 'regione', $f.find( '[name=regione]' ).val() );
		fd.append( 'regione_mappa', $f.find( '[name=regione_mappa]' ).val() );
		fd.append( 'famiglia', $f.find( '[name=famiglia]' ).val() );
		fd.append( 'ingredienti', $f.find( '[name=ingredienti]' ).val() );
		fd.append( 'procedimento', $f.find( '[name=procedimento]' ).val() );
		fd.append( 'racconto', $f.find( '[name=racconto]' ).val() );
		if ( scelto ) { fd.append( 'media', scelto.file, scelto.nome ); }
		$.ajax( { url: GS_AJAX.url, method: 'POST', data: fd, processData: false, contentType: false } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-ricetta-svuota-attesa', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Spostare nel cestino TUTTE le ricette in attesa? Potrai ripristinarle una per una dal cestino.' ) ) { return; }
		var $m = $( this ).closest( '.gs-box' ).find( '.gs-ricetta-svuota-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_ricetta_svuota_attesa', nonce: GS_AJAX.nonce } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-ricetta-modera', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '[data-ricetta]' ); var $m = $box.find( '.gs-ricetta-mod-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_ricetta_modera', nonce: GS_AJAX.nonce, ricetta: $( this ).data( 'ricetta' ), esito: $( this ).data( 'esito' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-ricetta-mese-imposta', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '[data-ricetta]' ); var $m = $box.find( '.gs-ricetta-mese-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_ricetta_mese_imposta', nonce: GS_AJAX.nonce, ricetta: $( this ).data( 'ricetta' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-ricetta-mese-rimuovi', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '[data-ricetta]' ); var $m = $box.find( '.gs-ricetta-mese-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_ricetta_mese_rimuovi', nonce: GS_AJAX.nonce, ricetta: $( this ).data( 'ricetta' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-ricetta-elimina', function ( e ) {
		e.preventDefault(); if ( ! confirm( 'Spostare nel cestino? Potrai ripristinarla.' ) ) return;
		var $box = $( this ).closest( '[data-ricetta]' ); var $m = $box.find( '.gs-ricetta-mod-msg' );
		$m.removeClass( 'ok err' ).text( '…' );
		$.post( GS_AJAX.url, { action: 'gs_ricetta_elimina', nonce: GS_AJAX.nonce, ricetta: $( this ).data( 'ricetta' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 600 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-ricetta-ripristina', function ( e ) {
		e.preventDefault(); var $tr = $( this ).closest( 'tr' ), $m = $tr.find( '.gs-ricetta-trow-msg' );
		$.post( GS_AJAX.url, { action: 'gs_ricetta_ripristina', nonce: GS_AJAX.nonce, ricetta: $( this ).data( 'ricetta' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 600 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	// --- Dicono di Noi (testimonianze) ---
	$( document ).on( 'click', '.gs-testim-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-testimonianza' );
		var $m = $f.find( '.gs-testim-msg' );
		if ( ! $f.find( '[name=testo]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi la tua testimonianza.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_testim_invia', nonce: GS_AJAX.nonce,
			testo: $f.find( '[name=testo]' ).val(), credito: $f.find( '[name=credito]' ).val()
		} )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-testim-modera', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '[data-testim]' ); var $m = $box.find( '.gs-testim-mod-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_testim_modera', nonce: GS_AJAX.nonce, testim: $( this ).data( 'testim' ), esito: $( this ).data( 'esito' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-testim-elimina', function ( e ) {
		e.preventDefault(); if ( ! confirm( 'Spostare nel cestino? Potrai ripristinarla.' ) ) return;
		var $box = $( this ).closest( '[data-testim]' ); var $m = $box.find( '.gs-testim-mod-msg' );
		$m.removeClass( 'ok err' ).text( '…' );
		$.post( GS_AJAX.url, { action: 'gs_testim_elimina', nonce: GS_AJAX.nonce, testim: $( this ).data( 'testim' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 600 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-testim-ripristina', function ( e ) {
		e.preventDefault(); var $tr = $( this ).closest( 'tr' ), $m = $tr.find( '.gs-testim-trow-msg' );
		$.post( GS_AJAX.url, { action: 'gs_testim_ripristina', nonce: GS_AJAX.nonce, testim: $( this ).data( 'testim' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 600 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	// --- Libreria Video delle Lezioni ---
	$( document ).on( 'click', '.gs-lezione-crea', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-lezione' );
		var $m = $f.find( '.gs-lezione-msg' );
		if ( ! $f.find( '[name=titolo]' ).val() || ( ! $f.find( '[name=video_url]' ).val() && ! $f.find( '[name=descrizione]' ).val() ) ) {
			$m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il titolo, e un link video o un testo.' ); return;
		}
		$m.removeClass( 'ok err' ).text( 'Pubblicazione…' );
		$.post( GS_AJAX.url, {
			action: 'gs_lezione_crea', nonce: GS_AJAX.nonce,
			titolo: $f.find( '[name=titolo]' ).val(),
			categoria: $f.find( '[name=categoria]' ).val(),
			video_url: $f.find( '[name=video_url]' ).val(),
			descrizione: $f.find( '[name=descrizione]' ).val(),
			data_uscita: $f.find( '[name=data_uscita]' ).val()
		} )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-lezione-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-lezione-modifica' );
		var $m = $f.closest( '.gs-inbox-corpo' ).find( '.gs-lezione-row-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_lezione_salva', nonce: GS_AJAX.nonce, lezione: $f.data( 'lezione' ),
			titolo: $f.find( '[name=titolo]' ).val(),
			categoria: $f.find( '[name=categoria]' ).val(),
			video_url: $f.find( '[name=video_url]' ).val(),
			descrizione: $f.find( '[name=descrizione]' ).val(),
			data_uscita: $f.find( '[name=data_uscita]' ).val()
		} )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-lezione-elimina', function ( e ) {
		e.preventDefault(); if ( ! confirm( 'Spostare nel cestino? Potrai ripristinarla.' ) ) return;
		var $b = $( this ), $m = $b.closest( '.gs-inbox-corpo' ).find( '.gs-lezione-row-msg' );
		$m.removeClass( 'ok err' ).text( '…' );
		$.post( GS_AJAX.url, { action: 'gs_lezione_elimina', nonce: GS_AJAX.nonce, lezione: $b.data( 'lezione' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 600 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-lezione-ripristina', function ( e ) {
		e.preventDefault(); var $tr = $( this ).closest( 'tr' ), $m = $tr.find( '.gs-lezione-trow-msg' );
		$.post( GS_AJAX.url, { action: 'gs_lezione_ripristina', nonce: GS_AJAX.nonce, lezione: $( this ).data( 'lezione' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 600 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	// Domande di verifica: aggiunta/eliminazione dal pannello (gestore).
	$( document ).on( 'click', '.gs-lezione-domanda-add', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), lezione = $btn.data( 'lezione' );
		var $wrap = $btn.closest( 'div' );
		var $inp  = $wrap.find( '.gs-lezione-domanda-input' );
		var $rinp = $wrap.find( '.gs-lezione-domanda-risposta-input' );
		var testo = $inp.val().trim();
		var risposta = $rinp.val().trim();
		var $msg  = $wrap.siblings( '.gs-lezione-domanda-msg' );
		if ( ! testo ) { return; }
		$.post( GS_AJAX.url, { action: 'gs_lezione_domanda_aggiungi', nonce: GS_AJAX.nonce, lezione: lezione, testo: testo, risposta_esatta: risposta } )
			.done( function ( res ) {
				if ( res && res.success ) {
					var $lista = $wrap.siblings( '.gs-lezione-domande-list' );
					$wrap.siblings( '.gs-lezione-domande-vuoto' ).remove();
					var testoEsc = $( '<div>' ).text( testo ).html();
					var rispEsc  = $( '<div>' ).text( risposta ).html();
					var avviso   = risposta ? '' : '<p class="gs-hint" style="margin:4px 0 0">⚠️ Senza risposta esatta, questa domanda non assegna punti in automatico.</p>';
					$lista.append(
						'<li class="gs-todo-item" data-domanda="' + res.data.id + '" style="display:block;padding:8px 0">'
						+ '<div style="display:flex;justify-content:space-between;align-items:center;gap:8px">'
						+ '<input type="text" class="gs-lezione-domanda-testo" value="' + testoEsc + '" style="flex:1">'
						+ '<button class="gs-todo-del gs-lezione-domanda-elimina" data-lezione="' + lezione + '" data-domanda="' + res.data.id + '" title="Elimina">✕</button></div>'
						+ '<div style="display:flex;gap:8px;margin-top:4px">'
						+ '<input type="text" class="gs-lezione-domanda-risposta-esatta" value="' + rispEsc + '" placeholder="Risposta esatta…" style="flex:1">'
						+ '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-lezione-domanda-risposta-salva" data-lezione="' + lezione + '" data-domanda="' + res.data.id + '">✎ Salva modifiche</button>'
						+ '</div><span class="gs-lezione-domanda-riga-msg gs-richiesta-esito"></span>' + avviso + '</li>'
					);
					$inp.val( '' );
					$rinp.val( '' );
				} else {
					$msg.addClass( 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				}
			} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-lezione-domanda-elimina', function ( e ) {
		e.preventDefault();
		$.post( GS_AJAX.url, { action: 'gs_lezione_domanda_elimina', nonce: GS_AJAX.nonce, lezione: $( this ).data( 'lezione' ), domanda: $( this ).data( 'domanda' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } } );
	} );
	$( document ).on( 'click', '.gs-lezione-domanda-ripristina', function ( e ) {
		e.preventDefault();
		$.post( GS_AJAX.url, { action: 'gs_lezione_domanda_ripristina', nonce: GS_AJAX.nonce, lezione: $( this ).data( 'lezione' ), domanda: $( this ).data( 'domanda' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } } );
	} );
	$( document ).on( 'click', '.gs-lezione-domanda-risposta-salva', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $li  = $btn.closest( '.gs-todo-item' );
		var $msg = $li.find( '.gs-lezione-domanda-riga-msg' );
		var risposta = $li.find( '.gs-lezione-domanda-risposta-esatta' ).val().trim();
		var testo = $.trim( $li.find( '.gs-lezione-domanda-testo' ).val() );
		if ( ! testo ) { $msg.removeClass( 'ok' ).addClass( 'err' ).text( 'La domanda non può essere vuota.' ); return; }
		$.post( GS_AJAX.url, { action: 'gs_lezione_domanda_risposta_salva', nonce: GS_AJAX.nonce, lezione: $btn.data( 'lezione' ), domanda: $btn.data( 'domanda' ), risposta_esatta: risposta, testo: testo } )
			.done( function ( res ) {
				$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) {
					$li.find( '.gs-hint' ).remove();
					if ( ! risposta ) { $li.append( '<p class="gs-hint" style="margin:4px 0 0">⚠️ Senza risposta esatta, questa domanda non assegna punti in automatico.</p>' ); }
				}
			} )
			.fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// Risposte della sfoglina alle domande di verifica.
	$( document ).on( 'click', '.gs-lezione-risposte-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-lezione-risposte' );
		var $m = $f.find( '.gs-lezione-risposte-msg' );
		var data = { action: 'gs_lezione_risposte_invia', nonce: GS_AJAX.nonce, lezione: $f.data( 'lezione' ) };
		$f.find( 'textarea' ).each( function () { data[ $( this ).attr( 'name' ) ] = $( this ).val(); } );
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, data )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { $f.find( '.gs-lezione-risposte-invia' ).text( 'Aggiorna le tue risposte' ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// Riscontro del maestro sulle risposte di una sfoglina (pannello gestore).
	$( document ).on( 'click', '.gs-lezione-risposta-feedback-salva', function ( e ) {
		e.preventDefault();
		var $c = $( this ).closest( '[data-sfoglina]' );
		var $m = $c.find( '.gs-lezione-risposta-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_lezione_risposta_feedback', nonce: GS_AJAX.nonce,
			lezione: $c.data( 'lezione' ), sfoglina: $c.data( 'sfoglina' ),
			feedback: $c.find( '.gs-lezione-risposta-feedback' ).val()
		} )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	// Consiglia una lezione a una sfoglina (pannello gestore) + promemoria automatico se non la vede.
	$( document ).on( 'click', '.gs-lezione-assegna-add', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), lezione = $btn.data( 'lezione' );
		var $wrap = $btn.closest( 'div' );
		var $sel  = $wrap.find( '.gs-lezione-assegna-select' );
		var uid   = $sel.val();
		var nome  = $sel.find( 'option:selected' ).text();
		var $msg  = $wrap.siblings( '.gs-lezione-assegna-msg' );
		if ( ! uid ) { return; }
		$.post( GS_AJAX.url, { action: 'gs_lezione_assegna', nonce: GS_AJAX.nonce, lezione: lezione, sfoglina: uid } )
			.done( function ( res ) {
				if ( res && res.success ) {
					var $lista = $wrap.siblings( '.gs-lezione-assegnazioni-list' );
					$wrap.siblings( '.gs-lezione-assegna-vuoto' ).remove();
					$lista.append(
						'<li class="gs-todo-item" data-sfoglina="' + uid + '"><span>' + $( '<div>' ).text( nome ).html() + ' — ⏳ non ancora vista</span>'
						+ '<button class="gs-todo-del gs-lezione-assegna-rimuovi" data-lezione="' + lezione + '" data-sfoglina="' + uid + '" title="Togli il consiglio">✕</button></li>'
					);
					$sel.val( '' );
					$msg.removeClass( 'err' );
				} else {
					$msg.addClass( 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				}
			} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-lezione-assegna-rimuovi', function ( e ) {
		e.preventDefault();
		var $li = $( this ).closest( '.gs-todo-item' );
		$.post( GS_AJAX.url, { action: 'gs_lezione_assegna_rimuovi', nonce: GS_AJAX.nonce, lezione: $( this ).data( 'lezione' ), sfoglina: $( this ).data( 'sfoglina' ) } );
		$li.fadeOut( 200, function () { $( this ).remove(); } );
	} );

	// --- Percorsi Guidati (gruppi ordinati di lezioni) ---
	$( document ).on( 'click', '.gs-percorso-crea', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-percorso' );
		var $m = $f.find( '.gs-percorso-msg' );
		if ( ! $f.find( '[name=titolo]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il nome del percorso.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Creazione…' );
		$.post( GS_AJAX.url, { action: 'gs_percorso_crea', nonce: GS_AJAX.nonce, titolo: $f.find( '[name=titolo]' ).val(), descrizione: $f.find( '[name=descrizione]' ).val(), livello: $f.find( '[name=livello]' ).val(), data_inizio: $f.find( '[name=data_inizio]' ).val(), data_fine: $f.find( '[name=data_fine]' ).val(), squadra: $f.find( '[name=squadra]' ).is( ':checked' ) ? 1 : 0, staffetta: $f.find( '[name=staffetta]' ).is( ':checked' ) ? 1 : 0 } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-percorso-modifica-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-percorso-modifica' );
		var $m = $f.find( '.gs-percorso-modifica-msg' );
		if ( ! $f.find( '[name=titolo]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il nome del percorso.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_percorso_modifica', nonce: GS_AJAX.nonce, percorso: $f.data( 'percorso' ),
			titolo: $f.find( '[name=titolo]' ).val(), descrizione: $f.find( '[name=descrizione]' ).val(), livello: $f.find( '[name=livello]' ).val(),
			data_inizio: $f.find( '[name=data_inizio]' ).val(), data_fine: $f.find( '[name=data_fine]' ).val(),
			squadra: $f.find( '[name=squadra]' ).is( ':checked' ) ? 1 : 0,
			staffetta: $f.find( '[name=staffetta]' ).is( ':checked' ) ? 1 : 0
		} ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-percorso-elimina', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Spostare nel cestino questo percorso? Le lezioni che contiene non vengono toccate.' ) ) { return; }
		var $b = $( this ), $m = $b.closest( '.gs-inbox-corpo' ).find( '.gs-percorso-row-msg' );
		$m.removeClass( 'ok err' ).text( '…' );
		$.post( GS_AJAX.url, { action: 'gs_percorso_elimina', nonce: GS_AJAX.nonce, percorso: $b.data( 'percorso' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 600 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-percorso-lezione-add', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), percorso = $btn.data( 'percorso' );
		var $wrap = $btn.closest( 'div' );
		var $sel  = $wrap.find( '.gs-percorso-lezione-select' );
		var lid   = $sel.val();
		var nome  = $sel.find( 'option:selected' ).text();
		var $msg  = $wrap.siblings( '.gs-percorso-lezione-msg' );
		if ( ! lid ) { return; }
		$.post( GS_AJAX.url, { action: 'gs_percorso_lezione_aggiungi', nonce: GS_AJAX.nonce, percorso: percorso, lezione: lid } )
			.done( function ( res ) {
				if ( res && res.success ) {
					var $lista = $wrap.siblings( '.gs-percorso-lezioni-list' );
					$wrap.siblings( '.gs-percorso-vuoto' ).remove();
					$lista.append(
						'<li class="gs-todo-item" data-lezione="' + lid + '"><span>' + $( '<div>' ).text( nome ).html() + '</span>'
						+ '<button class="gs-todo-del gs-percorso-lezione-rimuovi" data-percorso="' + percorso + '" data-lezione="' + lid + '" title="Togli dal percorso">✕</button></li>'
					);
					$sel.find( 'option:selected' ).remove();
					$sel.val( '' );
					$msg.removeClass( 'err' );
				} else {
					$msg.addClass( 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				}
			} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-percorso-lezione-rimuovi', function ( e ) {
		e.preventDefault();
		var $li = $( this ).closest( '.gs-todo-item' );
		$.post( GS_AJAX.url, { action: 'gs_percorso_lezione_rimuovi', nonce: GS_AJAX.nonce, percorso: $( this ).data( 'percorso' ), lezione: $( this ).data( 'lezione' ) } );
		$li.fadeOut( 200, function () { $( this ).remove(); } );
	} );
	$( document ).on( 'click', '.gs-percorso-sposta', function ( e ) {
		e.preventDefault();
		$.post( GS_AJAX.url, { action: 'gs_percorso_sposta', nonce: GS_AJAX.nonce, percorso: $( this ).data( 'percorso' ), direzione: $( this ).data( 'direzione' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } } );
	} );

	// --- Livelli dei Percorsi Guidati (crea, rinomina, elimina, riordina) ---
	$( document ).on( 'click', '.gs-livello-crea', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-livello-nuovo' );
		var $m = $f.find( '.gs-livello-msg' );
		if ( ! $f.find( '[name=nome]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il nome del livello.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Creazione…' );
		$.post( GS_AJAX.url, { action: 'gs_livello_crea', nonce: GS_AJAX.nonce, nome: $f.find( '[name=nome]' ).val() } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-livello-rinomina-apri', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( 'div' );
		var $f = $box.find( '.gs-form-livello-rinomina' );
		if ( ! $f.length ) { $f = $box.parent().find( '.gs-form-livello-rinomina' ); }
		$f.find( '[name=indice]' ).val( $( this ).data( 'indice' ) );
		$f.find( '[name=nome]' ).val( $( this ).data( 'nome' ) );
		$f.find( '.gs-livello-rinomina-msg' ).removeClass( 'ok err' ).text( '' );
		$f.show();
	} );
	$( document ).on( 'click', '.gs-livello-rinomina-annulla', function ( e ) {
		e.preventDefault();
		$( this ).closest( '.gs-form-livello-rinomina' ).hide();
	} );
	$( document ).on( 'click', '.gs-livello-rinomina-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-livello-rinomina' );
		var $m = $f.find( '.gs-livello-rinomina-msg' );
		if ( ! $f.find( '[name=nome]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il nome del livello.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_livello_rinomina', nonce: GS_AJAX.nonce, indice: $f.find( '[name=indice]' ).val(), nome: $f.find( '[name=nome]' ).val() } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-livello-elimina', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Eliminare questo livello? I percorsi che lo usano restano, senza livello — non vengono cancellati.' ) ) { return; }
		$.post( GS_AJAX.url, { action: 'gs_livello_elimina', nonce: GS_AJAX.nonce, indice: $( this ).data( 'indice' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } else { alert( res && res.data ? res.data.message : 'Errore.' ); } } )
			.fail( function () { alert( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-livello-sposta', function ( e ) {
		e.preventDefault();
		$.post( GS_AJAX.url, { action: 'gs_livello_sposta', nonce: GS_AJAX.nonce, indice: $( this ).data( 'indice' ), direzione: $( this ).data( 'direzione' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } } );
	} );

	// --- Indovina la Sfoglia: quiz-lampo giornaliero ---
	$( document ).on( 'click', '.gs-indovina-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-indovina' );
		var $m = $f.find( '.gs-indovina-msg' );
		var risposta = $f.find( '[name=risposta]' ).val();
		if ( ! risposta || ! risposta.trim() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi una risposta.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, { action: 'gs_indovina_rispondi', nonce: GS_AJAX.nonce, domanda: $f.data( 'domanda' ), risposta: risposta } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-indovina-domanda-add', function ( e ) {
		e.preventDefault();
		var $wrap = $( this ).closest( 'div' );
		var $inp  = $wrap.find( '.gs-indovina-domanda-input' );
		var $rinp = $wrap.find( '.gs-indovina-domanda-risposta-input' );
		var testo = $inp.val().trim();
		var risposta = $rinp.val().trim();
		var $msg  = $wrap.siblings( '.gs-indovina-domanda-msg' );
		if ( ! testo ) { return; }
		$.post( GS_AJAX.url, { action: 'gs_indovina_domanda_aggiungi', nonce: GS_AJAX.nonce, testo: testo, risposta_esatta: risposta } )
			.done( function ( res ) {
				if ( res && res.success ) { gsReloadMantenendoPosizione(); }
				else { $msg.addClass( 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); }
			} ).fail( function () { $msg.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-indovina-domanda-elimina', function ( e ) {
		e.preventDefault();
		$.post( GS_AJAX.url, { action: 'gs_indovina_domanda_elimina', nonce: GS_AJAX.nonce, domanda: $( this ).data( 'domanda' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } } );
	} );
	$( document ).on( 'click', '.gs-indovina-domanda-ripristina', function ( e ) {
		e.preventDefault();
		$.post( GS_AJAX.url, { action: 'gs_indovina_domanda_ripristina', nonce: GS_AJAX.nonce, domanda: $( this ).data( 'domanda' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } } );
	} );
	$( document ).on( 'click', '.gs-indovina-domanda-risposta-salva', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $li  = $btn.closest( '.gs-todo-item' );
		var $msg = $li.find( '.gs-indovina-domanda-riga-msg' );
		var risposta = $li.find( '.gs-indovina-domanda-risposta-esatta' ).val().trim();
		var testo = $.trim( $li.find( '.gs-indovina-domanda-testo' ).val() );
		if ( ! testo ) { $msg.removeClass( 'ok' ).addClass( 'err' ).text( 'La domanda non può essere vuota.' ); return; }
		$.post( GS_AJAX.url, { action: 'gs_indovina_domanda_risposta_salva', nonce: GS_AJAX.nonce, domanda: $btn.data( 'domanda' ), risposta_esatta: risposta, testo: testo } )
			.done( function ( res ) {
				$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) {
					$li.find( '.gs-hint' ).remove();
					if ( ! risposta ) { $li.append( '<p class="gs-hint" style="margin:4px 0 0">⚠️ Senza risposta esatta, questa domanda non assegna punti in automatico.</p>' ); }
				}
			} )
			.fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Il Tavolo di Lavoro: foto del giorno ---
	$( document ).on( 'click', '.gs-tavolo-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-tavolo' );
		var $m = $f.find( '.gs-tavolo-msg' );
		var scelto = gsFileDaInviare( $f.find( '.gs-msg-file' ) );
		if ( ! scelto ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scegli una foto.' ); return; }
		var errore = gsMessaggioSeTroppoGrande( scelto );
		if ( errore ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( errore ); return; }
		$m.removeClass( 'ok err' ).text( 'Caricamento…' );
		var fd = new FormData();
		fd.append( 'action', 'gs_tavolo_invia' );
		fd.append( 'nonce', GS_AJAX.nonce );
		fd.append( 'didascalia', $f.find( '[name=didascalia]' ).val() );
		fd.append( 'media', scelto.file, scelto.nome );
		$.ajax( { url: GS_AJAX.url, method: 'POST', data: fd, processData: false, contentType: false } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-tavolo-commento-salva', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $card = $btn.closest( '.gs-card' );
		var commento = $card.find( '.gs-tavolo-commento-input' ).val();
		var $m = $card.find( '.gs-tavolo-commento-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_tavolo_commento_salva', nonce: GS_AJAX.nonce, tavolo: $btn.data( 'tavolo' ), commento: commento } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	// --- La Sfoglia Misurata ---
	$( document ).on( 'click', '.gs-misura-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-misura' );
		var $m = $f.find( '.gs-misura-msg' );
		var valore = $f.find( '[name=valore]' ).val();
		if ( '' === valore || null === valore ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi un numero.' ); return; }
		var scelto = gsFileDaInviare( $f.find( '.gs-msg-file' ) );
		var errore = gsMessaggioSeTroppoGrande( scelto );
		if ( errore ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( errore ); return; }
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		var fd = new FormData();
		fd.append( 'action', 'gs_misura_invia' );
		fd.append( 'nonce', GS_AJAX.nonce );
		fd.append( 'misura', $f.data( 'misura' ) );
		fd.append( 'valore', valore );
		fd.append( 'nota', $f.find( '[name=nota]' ).val() );
		if ( scelto ) { fd.append( 'media', scelto.file, scelto.nome ); }
		$.ajax( { url: GS_AJAX.url, method: 'POST', data: fd, processData: false, contentType: false } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-misura-crea', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-misura-nuova' );
		var $m = $f.find( '.gs-misura-crea-msg' );
		if ( ! $f.find( '[name=titolo]' ).val() || ! $f.find( '[name=cosa]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi nome e cosa si misura.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Creazione…' );
		$.post( GS_AJAX.url, {
			action: 'gs_misura_crea', nonce: GS_AJAX.nonce,
			titolo: $f.find( '[name=titolo]' ).val(), cosa: $f.find( '[name=cosa]' ).val(), unita: $f.find( '[name=unita]' ).val(),
			modo: $f.find( '[name=modo]' ).val(), target: $f.find( '[name=target]' ).val(), descrizione: $f.find( '[name=descrizione]' ).val()
		} ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-misura-chiudi', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Chiudere questa sfida e assegnare il premio a chi è prima in classifica adesso? Non si può annullare.' ) ) { return; }
		var $btn = $( this ), $m = $btn.siblings( '.gs-misura-chiudi-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_misura_chiudi', nonce: GS_AJAX.nonce, misura: $btn.data( 'misura' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	$( document ).on( 'click', '.gs-misura-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-misura-modifica' );
		var $m = $f.find( '.gs-misura-riga-msg' );
		if ( ! $f.find( '[name=titolo]' ).val() || ! $f.find( '[name=cosa]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi nome e cosa si misura.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_misura_modifica', nonce: GS_AJAX.nonce, misura: $f.data( 'misura' ),
			titolo: $f.find( '[name=titolo]' ).val(), cosa: $f.find( '[name=cosa]' ).val(), unita: $f.find( '[name=unita]' ).val(),
			modo: $f.find( '[name=modo]' ).val(), target: $f.find( '[name=target]' ).val(), descrizione: $f.find( '[name=descrizione]' ).val()
		} ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-misura-elimina', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Spostare questa sfida nel cestino?' ) ) { return; }
		var $btn = $( this ), $m = $btn.siblings( '.gs-misura-riga-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_misura_elimina', nonce: GS_AJAX.nonce, misura: $btn.data( 'misura' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-misura-ripristina', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $m = $btn.siblings( '.gs-misura-trow-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_misura_ripristina', nonce: GS_AJAX.nonce, misura: $btn.data( 'misura' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- La Giuria a Turno ---
	$( document ).on( 'click', '.gs-giuria-opera-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-giuria-opera' );
		var $m = $f.find( '.gs-giuria-opera-msg' );
		if ( ! $f.find( '[name=titolo]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il titolo della tua opera.' ); return; }
		var scelto = gsFileDaInviare( $f.find( '.gs-msg-file' ) );
		var errore = gsMessaggioSeTroppoGrande( scelto );
		if ( errore ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( errore ); return; }
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		var fd = new FormData();
		fd.append( 'action', 'gs_giuria_opera_invia' );
		fd.append( 'nonce', GS_AJAX.nonce );
		fd.append( 'giuria', $f.data( 'giuria' ) );
		fd.append( 'titolo', $f.find( '[name=titolo]' ).val() );
		fd.append( 'testo', $f.find( '[name=testo]' ).val() );
		if ( scelto ) { fd.append( 'media', scelto.file, scelto.nome ); }
		$.ajax( { url: GS_AJAX.url, method: 'POST', data: fd, processData: false, contentType: false } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-giuria-voto-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-giuria-voto' );
		var $m = $f.find( '.gs-giuria-voto-msg' );
		if ( ! $.trim( $f.find( '[name=motivazione]' ).val() ) ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi perché hai votato così.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_giuria_voto_invia', nonce: GS_AJAX.nonce,
			giuria: $f.data( 'giuria' ), opera: $f.data( 'opera' ),
			stelle: $f.find( '[name=stelle]' ).val(), motivazione: $f.find( '[name=motivazione]' ).val()
		} ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'change', '.gs-giuria-tipo-select', function () {
		var $f = $( this ).closest( 'form' );
		$f.find( '.gs-giuria-lista-giudici' ).toggle( 'sfogline' === $( this ).val() );
	} );
	$( document ).on( 'click', '.gs-giuria-crea', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-giuria-nuova' );
		var $m = $f.find( '.gs-giuria-crea-msg' );
		if ( ! $f.find( '[name=titolo]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il nome del turno.' ); return; }
		var tipo = $f.find( '[name=tipo]' ).val();
		var giudici = $f.find( '[name="giudici[]"]:checked' ).map( function () { return $( this ).val(); } ).get();
		if ( 'sfogline' === tipo && ! giudici.length ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scegli almeno una giudice.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Creazione…' );
		$.post( GS_AJAX.url, {
			action: 'gs_giuria_crea', nonce: GS_AJAX.nonce,
			titolo: $f.find( '[name=titolo]' ).val(), descrizione: $f.find( '[name=descrizione]' ).val(), tipo: tipo, giudici: giudici
		} ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-giuria-modifica-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-giuria-modifica' );
		var $m = $f.find( '.gs-giuria-mod-msg' );
		if ( ! $f.find( '[name=titolo]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il nome del turno.' ); return; }
		var tipo = $f.find( '[name=tipo]' ).val();
		var giudici = $f.find( '[name="giudici[]"]:checked' ).map( function () { return $( this ).val(); } ).get();
		if ( 'sfogline' === tipo && ! giudici.length ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scegli almeno una giudice.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_giuria_modifica', nonce: GS_AJAX.nonce, giuria: $f.data( 'giuria' ),
			titolo: $f.find( '[name=titolo]' ).val(), descrizione: $f.find( '[name=descrizione]' ).val(), tipo: tipo, giudici: giudici
		} ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-giuria-chiudi', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Chiudere questo turno e premiare chi è prima in classifica adesso? Non si può annullare.' ) ) { return; }
		var $btn = $( this ), $m = $btn.siblings( '.gs-giuria-chiudi-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_giuria_chiudi', nonce: GS_AJAX.nonce, giuria: $btn.data( 'giuria' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	// --- Premi per Traguardo ---
	$( document ).on( 'change', '.gs-premio-tipo-select', function () {
		var $f = $( this ).closest( 'form' );
		var isLivello = 'livello' === $( this ).val();
		$f.find( '.gs-premio-campo-livello' ).toggle( isLivello );
		$f.find( '.gs-premio-campo-badge' ).toggle( ! isLivello );
	} );
	$( document ).on( 'change', '.gs-premio-azione-select', function () {
		var $f = $( this ).closest( 'form' );
		$f.find( '.gs-premio-campo-video' ).toggle( 'video' === $( this ).val() );
		$f.find( '.gs-premio-campo-sconto' ).toggle( 'sconto' === $( this ).val() );
	} );
	function gsPremioLeggiCampi( $f ) {
		return {
			nome: $f.find( '[name=nome]' ).val(), tipo: $f.find( '[name=tipo]' ).val(),
			livello: $f.find( '[name=livello]' ).val(), badge: $f.find( '[name=badge]' ).val(),
			azione: $f.find( '[name=azione]' ).val(), video_url: $f.find( '[name=video_url]' ).val(),
			sconto_pct: $f.find( '[name=sconto_pct]' ).val(),
			oggetto: $f.find( '[name=oggetto]' ).val(), testo: $f.find( '[name=testo]' ).val()
		};
	}
	$( document ).on( 'click', '.gs-premio-crea', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-premio-crea' );
		var $m = $f.find( '.gs-premio-crea-msg' );
		var dati = gsPremioLeggiCampi( $f );
		if ( ! dati.nome ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi un nome per riconoscere questo premio.' ); return; }
		if ( ! dati.testo ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il testo del messaggio.' ); return; }
		if ( 'video' === dati.azione && ! dati.video_url ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il link del video.' ); return; }
		if ( 'sconto' === dati.azione && ( ! dati.sconto_pct || Number( dati.sconto_pct ) <= 0 ) ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi una percentuale di sconto maggiore di zero.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Creazione…' );
		dati.action = 'gs_premio_crea'; dati.nonce = GS_AJAX.nonce;
		$.post( GS_AJAX.url, dati ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-premio-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-premio-modifica' );
		var $m = $f.find( '.gs-premio-riga-msg' );
		var dati = gsPremioLeggiCampi( $f );
		if ( ! dati.nome ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi un nome per riconoscere questo premio.' ); return; }
		if ( ! dati.testo ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il testo del messaggio.' ); return; }
		if ( 'video' === dati.azione && ! dati.video_url ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il link del video.' ); return; }
		if ( 'sconto' === dati.azione && ( ! dati.sconto_pct || Number( dati.sconto_pct ) <= 0 ) ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi una percentuale di sconto maggiore di zero.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		dati.action = 'gs_premio_modifica'; dati.nonce = GS_AJAX.nonce; dati.premio = $f.data( 'premio' );
		$.post( GS_AJAX.url, dati ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-premio-elimina', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Spostare questo premio nel cestino?' ) ) { return; }
		var $btn = $( this ), $m = $btn.siblings( '.gs-premio-riga-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_premio_elimina', nonce: GS_AJAX.nonce, premio: $btn.data( 'premio' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-premio-ripristina', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $m = $btn.siblings( '.gs-premio-trow-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_premio_ripristina', nonce: GS_AJAX.nonce, premio: $btn.data( 'premio' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- FAQ - Domande ---
	function gsFaqLeggiCampi( $f ) {
		return { categoria: $f.find( '[name=categoria]' ).val(), domanda: $f.find( '[name=domanda]' ).val(), risposta: $f.find( '[name=risposta]' ).val() };
	}
	$( document ).on( 'click', '.gs-faq-crea', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-faq-crea' );
		var $m = $f.find( '.gs-faq-crea-msg' );
		var dati = gsFaqLeggiCampi( $f );
		if ( ! dati.domanda ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi la domanda.' ); return; }
		if ( ! dati.risposta ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi la risposta.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Creazione…' );
		dati.action = 'gs_faq_crea'; dati.nonce = GS_AJAX.nonce;
		$.post( GS_AJAX.url, dati ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-faq-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-faq-modifica' );
		var $m = $f.find( '.gs-faq-riga-msg' );
		var dati = gsFaqLeggiCampi( $f );
		if ( ! dati.domanda ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi la domanda.' ); return; }
		if ( ! dati.risposta ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi la risposta.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		dati.action = 'gs_faq_modifica'; dati.nonce = GS_AJAX.nonce; dati.faq = $f.data( 'faq' );
		$.post( GS_AJAX.url, dati ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-faq-elimina', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Spostare questa domanda nel cestino?' ) ) { return; }
		var $btn = $( this ), $m = $btn.siblings( '.gs-faq-riga-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_faq_elimina', nonce: GS_AJAX.nonce, faq: $btn.data( 'faq' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-faq-ripristina', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $m = $btn.siblings( '.gs-faq-trow-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_faq_ripristina', nonce: GS_AJAX.nonce, faq: $btn.data( 'faq' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-faq-carica-base', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $m = $btn.siblings( '.gs-faq-base-msg' );
		$m.removeClass( 'ok err' ).text( 'Caricamento…' );
		$.post( GS_AJAX.url, { action: 'gs_faq_carica_base', nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Novità ---
	function gsNovitaLeggiCampi( $f ) {
		return { titolo: $f.find( '[name=titolo]' ).val(), testo: $f.find( '[name=testo]' ).val() };
	}
	$( document ).on( 'click', '.gs-novita-crea', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-novita-crea' );
		var $m = $f.find( '.gs-novita-crea-msg' );
		var dati = gsNovitaLeggiCampi( $f );
		if ( ! dati.titolo ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il titolo.' ); return; }
		if ( ! dati.testo ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il testo.' ); return; }
		dati.avvisa = $f.find( '[name=avvisa]' ).is( ':checked' ) ? 1 : 0;
		$m.removeClass( 'ok err' ).text( 'Pubblicazione…' );
		dati.action = 'gs_novita_crea'; dati.nonce = GS_AJAX.nonce;
		$.post( GS_AJAX.url, dati ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-novita-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-novita-modifica' );
		var $m = $f.find( '.gs-novita-riga-msg' );
		var dati = gsNovitaLeggiCampi( $f );
		if ( ! dati.titolo ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il titolo.' ); return; }
		if ( ! dati.testo ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il testo.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		dati.action = 'gs_novita_modifica'; dati.nonce = GS_AJAX.nonce; dati.novita = $f.data( 'novita' );
		$.post( GS_AJAX.url, dati ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-novita-elimina', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Spostare questo annuncio nel cestino?' ) ) { return; }
		var $btn = $( this ), $m = $btn.siblings( '.gs-novita-riga-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_novita_elimina', nonce: GS_AJAX.nonce, novita: $btn.data( 'novita' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-novita-ripristina', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $m = $btn.siblings( '.gs-novita-trow-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_novita_ripristina', nonce: GS_AJAX.nonce, novita: $btn.data( 'novita' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Adotta un Piatto in Via di Estinzione ---
	$( document ).on( 'click', '.gs-piatto-adotta', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $m = $btn.siblings( '.gs-piatto-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_piatto_adotta', nonce: GS_AJAX.nonce, piatto: $btn.data( 'piatto' ), come: $btn.data( 'come' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-piatto-libera', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Rinunciare alla custodia di questo piatto?' ) ) { return; }
		var $btn = $( this ), $m = $btn.siblings( '.gs-piatto-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_piatto_libera', nonce: GS_AJAX.nonce, piatto: $btn.data( 'piatto' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-piatto-crea', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-piatto-crea' );
		var $m = $f.find( '.gs-piatto-crea-msg' );
		if ( ! $f.find( '[name=nome]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il nome del piatto.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_piatto_crea', nonce: GS_AJAX.nonce,
			nome: $f.find( '[name=nome]' ).val(), regione: $f.find( '[name=regione]' ).val(), descrizione: $f.find( '[name=descrizione]' ).val()
		} ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-piatto-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-piatto-modifica' );
		var $m = $f.find( '.gs-piatto-riga-msg' );
		if ( ! $f.find( '[name=nome]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il nome del piatto.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_piatto_modifica', nonce: GS_AJAX.nonce, piatto: $f.data( 'piatto' ),
			nome: $f.find( '[name=nome]' ).val(), regione: $f.find( '[name=regione]' ).val(), descrizione: $f.find( '[name=descrizione]' ).val()
		} ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-piatto-elimina', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Spostare questo piatto nel cestino?' ) ) { return; }
		var $btn = $( this ), $m = $btn.siblings( '.gs-piatto-mod-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_piatto_elimina', nonce: GS_AJAX.nonce, piatto: $btn.data( 'piatto' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-piatto-ripristina', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $m = $btn.siblings( '.gs-piatto-trow-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_piatto_ripristina', nonce: GS_AJAX.nonce, piatto: $btn.data( 'piatto' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	// --- La Sfoglia che Insegna Se Stessa ---
	$( document ).on( 'click', '.gs-errore-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-errore-invia' );
		var $m = $f.find( '.gs-errore-msg' );
		if ( ! $f.find( '[name=titolo]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi un titolo breve.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_errore_invia', nonce: GS_AJAX.nonce,
			titolo: $f.find( '[name=titolo]' ).val(), errore: $f.find( '[name=errore]' ).val(), lezione: $f.find( '[name=lezione]' ).val()
		} ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-errore-modera', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $m = $btn.siblings( '.gs-errore-mod-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_errore_modera', nonce: GS_AJAX.nonce, errore: $btn.data( 'errore' ), esito: $btn.data( 'esito' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-errore-elimina', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Spostare questo racconto nel cestino?' ) ) { return; }
		var $btn = $( this ), $m = $btn.siblings( '.gs-errore-mod-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_errore_elimina', nonce: GS_AJAX.nonce, errore: $btn.data( 'errore' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-errore-ripristina', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $m = $btn.siblings( '.gs-errore-trow-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_errore_ripristina', nonce: GS_AJAX.nonce, errore: $btn.data( 'errore' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	// --- La Cassaforte del Sapere ---
	$( document ).on( 'click', '.gs-cassaforte-crea', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-cassaforte-crea' );
		var $m = $f.find( '.gs-cassaforte-crea-msg' );
		if ( ! $f.find( '[name=titolo]' ).val() || ! $f.find( '[name=soglia]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il nome e quante sfogline servono.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Creazione…' );
		$.post( GS_AJAX.url, {
			action: 'gs_cassaforte_crea', nonce: GS_AJAX.nonce,
			titolo: $f.find( '[name=titolo]' ).val(), contenuto: $f.find( '[name=contenuto]' ).val(),
			soglia: $f.find( '[name=soglia]' ).val(), livello: $f.find( '[name=livello]' ).val()
		} ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-cassaforte-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-cassaforte-modifica' );
		var $m = $f.find( '.gs-cassaforte-mod-msg' );
		if ( ! $f.find( '[name=titolo]' ).val() || ! $f.find( '[name=soglia]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il nome e quante sfogline servono.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, {
			action: 'gs_cassaforte_modifica', nonce: GS_AJAX.nonce, cassaforte: $f.data( 'cassaforte' ),
			titolo: $f.find( '[name=titolo]' ).val(), contenuto: $f.find( '[name=contenuto]' ).val(),
			soglia: $f.find( '[name=soglia]' ).val(), livello: $f.find( '[name=livello]' ).val()
		} ).done( function ( res ) {
			$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
		} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-cassaforte-elimina', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Spostare questa cassaforte nel cestino?' ) ) { return; }
		var $btn = $( this ), $m = $btn.siblings( '.gs-cassaforte-mod-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_cassaforte_elimina', nonce: GS_AJAX.nonce, cassaforte: $btn.data( 'cassaforte' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-cassaforte-ripristina', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $m = $btn.siblings( '.gs-cassaforte-trow-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_cassaforte_ripristina', nonce: GS_AJAX.nonce, cassaforte: $btn.data( 'cassaforte' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	// --- Il Testamento della Sfoglina ---
	$( document ).on( 'click', '.gs-testamento-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-testamento' );
		var $m = $f.find( '.gs-testamento-msg' );
		if ( ! $.trim( $f.find( '[name=testo]' ).val() ) ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi qualcosa prima di salvare.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_testamento_salva', nonce: GS_AJAX.nonce, testo: $f.find( '[name=testo]' ).val() } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Ingrediente Segreto (pannello di creazione) ---
	$( document ).on( 'click', '.gs-ingrediente-crea', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-ingrediente' );
		var $m = $f.find( '.gs-ingrediente-crea-msg' );
		if ( ! $.trim( $f.find( '[name=nome]' ).val() ) ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il nome dell\'ingrediente.' ); return; }
		if ( ! $f.find( '[name=quando]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Indica quando si svela.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		var fd = new FormData();
		fd.append( 'action', 'gs_ingrediente_crea' );
		fd.append( 'nonce', GS_AJAX.nonce );
		fd.append( 'nome', $f.find( '[name=nome]' ).val() );
		fd.append( 'testo', $f.find( '[name=testo]' ).val() );
		fd.append( 'quando', $f.find( '[name=quando]' ).val() );
		fd.append( 'annunciato_da', $f.find( '[name=annunciato_da]' ).val() );
		var $foto = $f.find( '[name=foto]' )[ 0 ];
		if ( $foto && $foto.files && $foto.files.length ) {
			fd.append( 'foto', $foto.files[ 0 ] );
		}
		$.ajax( { url: GS_AJAX.url, method: 'POST', data: fd, processData: false, contentType: false } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) {
					$f[ 0 ].reset();
					setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 );
				}
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- La Sfida del Silenzio ---
	$( document ).on( 'click', '.gs-silenzio-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-silenzio' );
		var $m = $f.find( '.gs-silenzio-salva-msg' );
		if ( ! $f.find( '[name=da]' ).val() || ! $f.find( '[name=a]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scegli entrambe le date.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_silenzio_salva', nonce: GS_AJAX.nonce, da: $f.find( '[name=da]' ).val(), a: $f.find( '[name=a]' ).val() } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-silenzio-disattiva', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Disattivare subito la Sfida del Silenzio? La classifica torna viva immediatamente.' ) ) { return; }
		var $btn = $( this ), $m = $btn.siblings( '.gs-silenzio-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_silenzio_disattiva', nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	// --- Percorso a Staffetta: passa il testimone ---
	$( document ).on( 'click', '.gs-staffetta-passa', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-staffetta-passa' );
		var $m = $f.find( '.gs-staffetta-msg' );
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, { action: 'gs_staffetta_passa', nonce: GS_AJAX.nonce, percorso: $f.data( 'percorso' ), a: $f.find( '[name=a]' ).val() } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Il Matterello Parlante (archivio vocale) ---
	var gsVoceRecorder = null, gsVoceChunks = [];
	function gsVoceMostraAnteprima( $f, url ) {
		$f.find( '.gs-voce-anteprima' ).html(
			'<audio controls src="' + url + '"></audio> '
			+ '<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-voce-audio-rimuovi">🗑️ Rimuovi audio</button>'
		).show();
	}
	$( document ).on( 'change', '.gs-voce-file', function () {
		var $f = $( this ).closest( '.gs-form-voce-nuova' );
		var file = this.files && this.files[ 0 ];
		if ( ! file ) { return; }
		$f.data( 'audioBlob', null ); // un file scelto ora sostituisce un'eventuale registrazione precedente
		gsVoceMostraAnteprima( $f, URL.createObjectURL( file ) );
	} );
	$( document ).on( 'click', '.gs-voce-audio-rimuovi', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-voce-nuova' );
		$f.data( 'audioBlob', null );
		var fileInput = $f.find( '.gs-voce-file' )[ 0 ];
		if ( fileInput ) { fileInput.value = ''; }
		$f.find( '.gs-voce-anteprima' ).empty().hide();
	} );
	$( document ).on( 'click', '.gs-voce-registra', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $f = $btn.closest( '.gs-form-voce-nuova' ), $m = $f.find( '.gs-voce-registra-msg' );
		if ( ! navigator.mediaDevices || ! window.MediaRecorder ) {
			$m.removeClass( 'ok' ).addClass( 'err' ).text( 'Il tuo browser non supporta la registrazione diretta: usa "Carica un file audio" qui sotto.' );
			return;
		}
		if ( $btn.data( 'registrando' ) ) {
			gsVoceRecorder.stop();
			$btn.data( 'registrando', false ).text( '🎙️ Registra dal browser' );
			return;
		}
		navigator.mediaDevices.getUserMedia( { audio: true } ).then( function ( stream ) {
			gsVoceChunks = [];
			gsVoceRecorder = new MediaRecorder( stream );
			gsVoceRecorder.ondataavailable = function ( ev ) { if ( ev.data && ev.data.size ) { gsVoceChunks.push( ev.data ); } };
			gsVoceRecorder.onstop = function () {
				var blob = new Blob( gsVoceChunks, { type: 'audio/webm' } );
				$f.data( 'audioBlob', blob );
				gsVoceMostraAnteprima( $f, URL.createObjectURL( blob ) );
				stream.getTracks().forEach( function ( t ) { t.stop(); } );
			};
			gsVoceRecorder.start();
			$btn.data( 'registrando', true ).text( '⏹️ Ferma registrazione' );
			$m.removeClass( 'err ok' ).text( '' );
		} ).catch( function () {
			$m.removeClass( 'ok' ).addClass( 'err' ).text( 'Non è stato possibile accedere al microfono.' );
		} );
	} );
	$( document ).on( 'click', '.gs-voce-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-voce-nuova' );
		var $m = $f.find( '.gs-voce-msg' );
		if ( ! $f.find( '[name=titolo]' ).val() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi il titolo del ricordo.' ); return; }
		var blob = $f.data( 'audioBlob' );
		var fileInput = $f.find( '.gs-voce-file' )[ 0 ];
		var fileScelto = fileInput && fileInput.files && fileInput.files[ 0 ];
		if ( ! blob && ! fileScelto ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Registra un audio dal browser oppure carica un file.' ); return; }
		var daInviare = blob || fileScelto, nomeFile = blob ? 'registrazione.webm' : fileScelto.name;
		var errore = gsMessaggioSeTroppoGrande( { file: daInviare, nome: nomeFile } );
		if ( errore ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( errore ); return; }
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		var fd = new FormData();
		fd.append( 'action', 'gs_voce_invia' );
		fd.append( 'nonce', GS_AJAX.nonce );
		fd.append( 'titolo', $f.find( '[name=titolo]' ).val() );
		fd.append( 'trascrizione', $f.find( '[name=trascrizione]' ).val() );
		fd.append( 'media', daInviare, nomeFile );
		$.ajax( { url: GS_AJAX.url, method: 'POST', data: fd, processData: false, contentType: false } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-matterello-modera', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $m = $btn.siblings( '.gs-matterello-mod-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_matterello_modera', nonce: GS_AJAX.nonce, voce: $btn.data( 'voce' ), esito: $btn.data( 'esito' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-matterello-elimina', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Spostare questa registrazione nel cestino?' ) ) { return; }
		var $btn = $( this ), $m = $btn.siblings( '.gs-matterello-mod-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_matterello_elimina', nonce: GS_AJAX.nonce, voce: $btn.data( 'voce' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-matterello-ripristina', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $m = $btn.siblings( '.gs-matterello-trow-msg' );
		$m.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_matterello_ripristina', nonce: GS_AJAX.nonce, voce: $btn.data( 'voce' ) } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-voce-commento-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-voce-commento' );
		var $m = $f.find( '.gs-voce-commento-msg' );
		var testo = $f.find( '[name=testo]' ).val();
		if ( ! testo || ! testo.trim() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi qualcosa prima di inviare.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, $.extend( { action: 'gs_voce_commento_invia', nonce: GS_AJAX.nonce, voce: $f.data( 'voce' ), testo: testo }, gsAntispamFields( $f ) ) )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 600 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-voce-commento-elimina', function ( e ) {
		e.preventDefault();
		if ( ! confirm( 'Spostare questo commento nel cestino?' ) ) { return; }
		var $btn = $( this );
		$.post( GS_AJAX.url, { action: 'gs_voce_commento_elimina', nonce: GS_AJAX.nonce, voce: $btn.data( 'voce' ), commento: $btn.data( 'commento' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } else { alert( res && res.data ? res.data.message : 'Errore.' ); } } )
			.fail( function () { alert( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-voce-commento-ripristina', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		$.post( GS_AJAX.url, { action: 'gs_voce_commento_ripristina', nonce: GS_AJAX.nonce, voce: $btn.data( 'voce' ), commento: $btn.data( 'commento' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } else { alert( res && res.data ? res.data.message : 'Errore.' ); } } )
			.fail( function () { alert( 'Errore di connessione.' ); } );
	} );

	// --- Promemoria giornaliero opt-in ---
	$( document ).on( 'click', '.gs-promemoria-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-promemoria' );
		var $m = $f.find( '.gs-promemoria-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_promemoria_salva', nonce: GS_AJAX.nonce, ora: $f.find( '[name=ora]' ).val() } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// --- Modifica/elimina voci (diario, consigli) ---
	$( document ).on( 'click', '.gs-voce-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-voce' ), $m = $f.find( '.gs-voce-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_voce_salva', nonce: GS_AJAX.nonce, id: $f.data( 'id' ), tipo: $f.data( 'tipo' ), titolo: $f.find( '[name=titolo]' ).val(), testo: $f.find( '[name=testo]' ).val() } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-voce-elimina', function ( e ) {
		e.preventDefault(); if ( ! confirm( 'Spostare nel cestino? Potrai recuperarlo.' ) ) return;
		var $f = $( this ).closest( '.gs-form-voce' ), $m = $f.find( '.gs-voce-msg' );
		$.post( GS_AJAX.url, { action: 'gs_voce_elimina', nonce: GS_AJAX.nonce, id: $f.data( 'id' ), tipo: $f.data( 'tipo' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	// --- Modifica/elimina messaggi inviati (gestore) ---
	$( document ).on( 'click', '.gs-msgedit-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-msgedit' ), $m = $f.find( '.gs-msgedit-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_msgedit_salva', nonce: GS_AJAX.nonce, id: $f.data( 'id' ), titolo: $f.find( '[name=titolo]' ).val(), testo: $f.find( '[name=testo]' ).val() } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-msgedit-elimina', function ( e ) {
		e.preventDefault(); if ( ! confirm( 'Spostare nel cestino?' ) ) return;
		var $f = $( this ).closest( '.gs-form-msgedit' ), $m = $f.find( '.gs-msgedit-msg' );
		$.post( GS_AJAX.url, { action: 'gs_msgedit_elimina', nonce: GS_AJAX.nonce, id: $f.data( 'id' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-msgedit-ripristina', function ( e ) {
		e.preventDefault();
		var $btn = $( this ), $m = $btn.closest( 'td' ).find( '.gs-msgedit-tmsg' );
		$.post( GS_AJAX.url, { action: 'gs_msgedit_ripristina', nonce: GS_AJAX.nonce, id: $btn.data( 'id' ) } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); } } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );
	$( document ).on( 'click', '.gs-aeroplanino-elimina', function ( e ) {
		e.preventDefault(); if ( ! confirm( 'Spostare nel cestino?' ) ) return;
		var $btn = $( this );
		$.post( GS_AJAX.url, { action: 'gs_aeroplanino_elimina', nonce: GS_AJAX.nonce, id: $btn.data( 'id' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } else { alert( res && res.data ? res.data.message : 'Errore.' ); } } )
			.fail( function () { alert( 'Errore di connessione.' ); } );
	} );
	// --- Stato Generale: interruttori istantanei ---
	$( document ).on( 'change', '.gs-stato-sez-toggle', function () {
		var $chk = $( this );
		$.post( GS_AJAX.url, { action: 'gs_stato_sez_toggle', nonce: GS_AJAX.nonce, key: $chk.data( 'key' ), attivo: $chk.is( ':checked' ) ? 1 : '' } )
			.fail( function () { $chk.prop( 'checked', ! $chk.is( ':checked' ) ); alert( 'Errore di connessione: nessuna modifica salvata.' ); } );
	} );
	$( document ).on( 'change', '.gs-stato-nastro-toggle', function () {
		var $chk = $( this );
		$.post( GS_AJAX.url, { action: 'gs_stato_nastro_toggle', nonce: GS_AJAX.nonce, attivo: $chk.is( ':checked' ) ? 1 : '' } )
			.fail( function () { $chk.prop( 'checked', ! $chk.is( ':checked' ) ); alert( 'Errore di connessione: nessuna modifica salvata.' ); } );
	} );
	$( document ).on( 'change', '.gs-stato-blackout-toggle', function () {
		var $chk = $( this );
		$.post( GS_AJAX.url, { action: 'gs_fe_toggle_blackout', nonce: GS_AJAX.nonce } )
			.fail( function () { $chk.prop( 'checked', ! $chk.is( ':checked' ) ); alert( 'Errore di connessione: nessuna modifica salvata.' ); } );
	} );
	$( document ).on( 'change', '.gs-stato-percorso-mensile-toggle', function () {
		var $chk = $( this );
		if ( $chk.is( ':checked' ) && ! confirm( 'Accendere la gara mensile del Buono Sfoglia?\n\nDa questo momento, alla fine di ogni mese ogni sfoglina riceve il resoconto dei suoi punti. Accendila solo se la gara è davvero partita.' ) ) {
			$chk.prop( 'checked', false );
			return;
		}
		$.post( GS_AJAX.url, { action: 'gs_stato_percorso_mensile_toggle', nonce: GS_AJAX.nonce, attivo: $chk.is( ':checked' ) ? 1 : '' } )
			.fail( function () { $chk.prop( 'checked', ! $chk.is( ':checked' ) ); alert( 'Errore di connessione: nessuna modifica salvata.' ); } );
	} );
	$( document ).on( 'click', '.gs-buono-pulisci-messaggi-btn', function () {
		var $btn = $( this );
		var $msg = $btn.siblings( '.gs-buono-pulisci-messaggi-msg' );
		if ( ! confirm( 'Sposta nel cestino i messaggi "Il tuo resoconto" di questo mese dalle caselle delle sfogline toccate. Le email già inviate NON vengono richiamate. Procedere?' ) ) { return; }
		$btn.prop( 'disabled', true );
		$msg.attr( 'class', 'gs-buono-pulisci-messaggi-msg gs-richiesta-esito' ).text( 'Un momento…' );
		$.post( GS_AJAX.url, { action: 'gs_buono_sfoglia_pulisci_messaggi', nonce: GS_AJAX.nonce, mese: $btn.data( 'mese' ) } )
			.done( function ( res ) {
				$btn.prop( 'disabled', false );
				$msg.attr( 'class', 'gs-buono-pulisci-messaggi-msg gs-richiesta-esito ' + ( res && res.success ? 'ok' : 'err' ) )
					.text( res && res.data ? res.data.message : 'Errore.' );
			} )
			.fail( function () {
				$btn.prop( 'disabled', false );
				$msg.attr( 'class', 'gs-buono-pulisci-messaggi-msg gs-richiesta-esito err' ).text( 'Errore di connessione.' );
			} );
	} );

	$( document ).on( 'click', '.gs-aeroplanino-ripristina', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		$.post( GS_AJAX.url, { action: 'gs_aeroplanino_ripristina', nonce: GS_AJAX.nonce, id: $btn.data( 'id' ) } )
			.done( function ( res ) { if ( res && res.success ) { gsReloadMantenendoPosizione(); } else { alert( res && res.data ? res.data.message : 'Errore.' ); } } )
			.fail( function () { alert( 'Errore di connessione.' ); } );
	} );
	$( document ).on( 'click', '.gs-msg-risposta-invia', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-msg-risposta' ), $m = $f.find( '.gs-msg-risposta-msg' );
		var testo = $f.find( '[name=testo]' ).val();
		if ( ! testo || ! testo.trim() ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi qualcosa prima di inviare.' ); return; }
		$m.removeClass( 'ok err' ).text( 'Invio…' );
		$.post( GS_AJAX.url, $.extend( { action: 'gs_msg_risposta_invia', nonce: GS_AJAX.nonce, msg: $f.data( 'msg' ), thread: $f.data( 'thread' ), testo: testo }, gsAntispamFields( $f ) ) )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 700 ); }
			} ).fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// Elimina/ripristina UNA risposta nel thread di un messaggio (punto 6,
	// Ennio, delega "finisci tutto" del 22/08/2026). ELIMINARE richiede tre
	// clic ravvicinati (stesso schema del blackout rapido, Ennio 22/08/2026:
	// "sempre solito metodo dei tre click"); RIPRISTINARE resta a un clic solo.
	$( document ).on( 'click', '.gs-msg-risposta-toggle', function ( e ) {
		e.preventDefault();
		var $btn   = $( this );
		var $esito = $btn.next( '.gs-msg-risposta-toggle-esito' );
		var azione = $btn.data( 'azione' );

		if ( 'elimina' === azione ) {
			var contatore = ( $btn.data( 'gs-clic' ) || 0 ) + 1;
			clearTimeout( $btn.data( 'gs-clic-timer' ) );
			if ( contatore < 3 ) {
				if ( ! $btn.data( 'gs-testo-originale' ) ) { $btn.data( 'gs-testo-originale', $btn.text() ); }
				$btn.data( 'gs-clic', contatore );
				$btn.text( 'Clicca ancora ' + ( 3 - contatore ) + ( 3 - contatore === 1 ? ' volta' : ' volte' ) );
				var timer = setTimeout( function () {
					$btn.data( 'gs-clic', 0 );
					$btn.text( $btn.data( 'gs-testo-originale' ) || 'Elimina' );
				}, 2500 );
				$btn.data( 'gs-clic-timer', timer );
				return;
			}
			$btn.data( 'gs-clic', 0 );
		}

		$btn.prop( 'disabled', true );
		$esito.removeClass( 'ok err' ).text( 'Attendi…' );
		$.post( GS_AJAX.url, { action: 'gs_msg_risposta_toggle', nonce: GS_AJAX.nonce, msg: $btn.data( 'msg' ), thread: $btn.data( 'thread' ), i: $btn.data( 'i' ), azione: azione } )
			.done( function ( res ) {
				var ok = res && res.success;
				$esito.removeClass( 'ok err' ).addClass( ok ? 'ok' : 'err' ).text( ( res && res.data && res.data.message ) || ( ok ? 'Fatto.' : 'Errore.' ) );
				if ( ok ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 800 ); }
			} )
			.fail( function () { $esito.removeClass( 'ok' ).addClass( 'err' ).text( 'Errore di connessione.' ); } )
			.always( function () { $btn.prop( 'disabled', false ); } );
	} );

	// --- Aspetto pagina Le Sfogline ---
	$( document ).on( 'click', '.gs-sfview-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-sfview' ), $m = $f.find( '.gs-sfview-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_sfview_salva', nonce: GS_AJAX.nonce, scheda: $f.find( '[name=scheda]' ).val(), per_riga: $f.find( '[name=per_riga]' ).val(), per_pagina: $f.find( '[name=per_pagina]' ).val() } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	/* -------------------------------------------------------------------------
	   Pannello di controllo: indice laterale a SINISTRA (salta alla sezione)
	   Costruito automaticamente dai titoli delle schede presenti nel pannello.
	------------------------------------------------------------------------- */
	$( function () {
		var $panel = $( '.gs-pannello, .gs-dash-grid' ).first();
		if ( ! $panel.length ) { return; }

		// Titoli delle sezioni: schede del pannello front-end o zone della plancia.
		var $titles = $panel.find( '.gs-box-title, .gs-zone-head' );
		if ( $titles.length < 3 ) { return; }

		// Macro-aree (raggruppano i 50+ pannelli): stesse 7 chiavi usate in
		// control-panel.php/admin.php (attributo data-idx-group), richiesto
		// da Ennio il 2026-07-29 per non perdersi in un elenco così lungo.
		// Stessi nomi e stesso ordine del registro unico gs_categorie()
		// (helpers.php) — 'partner' è la categoria nuova del 18/08/2026
		// (Artigiani, Scuole, Caroselli, Vetrina, Aspetto «Le Sfogline»).
		var GS_IDX_GRUPPI = {
			messaggi:     'Comunicazione',
			sfogline:     'Sfogline',
			corsi:        'Corsi & Formazione',
			sfide:        'Giochi & Sfide',
			contenuti:    'Contenuti & Racconti',
			partner:      'Partner & Vetrine',
			pagamenti:    'Pagamenti',
			impostazioni: 'Impostazioni & Sito',
			strumenti:    'Strumenti'
		};
		var GS_IDX_ORDINE = [ 'messaggi', 'sfogline', 'corsi', 'sfide', 'contenuti', 'partner', 'pagamenti', 'impostazioni', 'strumenti' ];

		var items = [];
		$titles.each( function ( i ) {
			var $t = $( this );
			var id = 'gs-sec-' + i;
			var $anchor = $t.closest( '.gs-box, .gs-zone' );
			if ( ! $anchor.length ) { $anchor = $t; }
			if ( ! $anchor.attr( 'id' ) ) { $anchor.attr( 'id', id ); } else { id = $anchor.attr( 'id' ); }
			var $grp = $t.closest( '[data-idx-group]' );
			var grp  = $grp.length ? $grp.attr( 'data-idx-group' ) : 'altro';
			items.push( { id: id, label: $.trim( $t.text() ), grp: grp } );
		} );

		// Quanto spazio occupano le barre fisse in alto (barra di WordPress +
		// intestazione "appiccicata" del tema), così il titolo non resta nascosto.
		// Nota: l'header del tema può comparire solo DOPO che si inizia a scorrere,
		// perciò questa misura va rifatta anche dopo lo scorrimento.
		// Barre che restano davvero in cima e COPRONO il contenuto:
		// sia quelle "fisse" (barra di WordPress) sia quelle "appiccicate"
		// (l'intestazione del tema con logo e menu, che resta visibile scorrendo).
		function gsBarreFisse() {
			var b = 0;
			var el = document.querySelectorAll( 'body *' );
			for ( var i = 0; i < el.length; i++ ) {
				var st = window.getComputedStyle( el[ i ] );
				if ( st.position !== 'fixed' && st.position !== 'sticky' ) { continue; }
				if ( st.display === 'none' || st.visibility === 'hidden' ) { continue; }
				var r = el[ i ].getBoundingClientRect();
				// deve stare in cima, avere altezza sensata e coprire la pagina in larghezza
				if ( r.height > 0 && r.height < 260 && r.top <= 40 && r.bottom > 0 && r.width > window.innerWidth * 0.5 ) {
					b = Math.max( b, r.bottom );
				}
			}
			if ( b > 260 ) { b = 260; }
			return b;
		}

		function gsTopOffset() {
			return gsBarreFisse() + 22; // spazio delle barre + 10px di margine di sicurezza in più
		}

		function gsScrollTo( el ) {
			if ( ! el ) { return; }
			if ( el.tagName === 'DETAILS' ) { el.open = true; }

			// Due insidie, entrambe gestite qui:
			// 1) l'intestazione fissa del tema compare SOLO dopo che si inizia a scorrere;
			// 2) immagini/contenuti che si caricano dopo spostano la sezione.
			// Perciò: salto immediato (niente animazione, che darebbe misure sbagliate)
			// e poi ricontrollo più volte, correggendo finché il titolo non è al posto giusto.
			function porta() {
				// Alcuni temi impostano lo scorrimento animato via CSS: lo disattivo
				// durante il salto, altrimenti le misure risultano sbagliate.
				var htmlEl = document.documentElement;
				var prev = htmlEl.style.scrollBehavior;
				htmlEl.style.scrollBehavior = 'auto';

				// Regolazione manuale dal pannello: negativo = la pagina scende di più.
				var extra = ( window.GS_IDX_EXTRA !== undefined ) ? parseInt( window.GS_IDX_EXTRA, 10 )
					: ( ( typeof GS_AJAX !== 'undefined' && GS_AJAX.idx_extra !== undefined ) ? parseInt( GS_AJAX.idx_extra, 10 ) : 0 );
				if ( isNaN( extra ) ) { extra = 0; }

				var auto = gsTopOffset();
				var off  = auto + extra;

				// Rete di sicurezza: qualunque regolazione, il titolo deve restare
				// almeno un po' SOTTO le barre fisse, mai nascosto sotto di esse.
				var minimo = 44; // sotto la barra di WordPress, mai più in su
				if ( off < minimo ) { off = minimo; }

				var rect = el.getBoundingClientRect();
				var top  = rect.top + ( window.pageYOffset || htmlEl.scrollTop ) - off;
				window.scrollTo( 0, Math.max( 0, top ) ); // immediato, non "smooth"
				htmlEl.style.scrollBehavior = prev;
			}

			porta();
			var ritardi = [ 60, 180, 400, 800, 1400 ];
			ritardi.forEach( function ( ms ) {
				setTimeout( function () {
					var ex = ( window.GS_IDX_EXTRA !== undefined ) ? parseInt( window.GS_IDX_EXTRA, 10 )
						: ( ( typeof GS_AJAX !== 'undefined' && GS_AJAX.idx_extra !== undefined ) ? parseInt( GS_AJAX.idx_extra, 10 ) : 0 );
					if ( isNaN( ex ) ) { ex = 0; }
					var off = gsTopOffset() + ex;
					var min = 44;
					if ( off < min ) { off = min; }
					var scarto = el.getBoundingClientRect().top - off;
					if ( Math.abs( scarto ) > 3 ) { porta(); }
				}, ms );
			} );
			setTimeout( function () { gsEvidenzia( el ); }, 500 );
		}

		// Piccolo lampeggio del bordo, così si vede subito dove siamo arrivati.
		function gsEvidenzia( el ) {
			var $e = $( el );
			$e.addClass( 'gs-sec-evidenzia' );
			setTimeout( function () { $e.removeClass( 'gs-sec-evidenzia' ); }, 1400 );
		}

		// Esposta su window: questa funzione vive dentro il $( function () {...} )
		// di pronti-macchina qui sopra, non al livello condiviso del file — resta
		// isolata anche dopo aver unito i vari blocchi in un solo IIFE. Altro
		// codice (es. Calendario Corsi → Locandine) la riusa così invece di
		// reinventare lo stesso salto "sicuro".
		window.gsScrollTo = gsScrollTo;

		// Raggruppa mantenendo l'ordine di comparsa nella pagina all'interno di ogni gruppo.
		var gsIdxGruppi = {};
		items.forEach( function ( it ) {
			if ( ! gsIdxGruppi[ it.grp ] ) { gsIdxGruppi[ it.grp ] = []; }
			gsIdxGruppi[ it.grp ].push( it );
		} );
		var gsIdxOrdineFinale = GS_IDX_ORDINE.filter( function ( k ) { return gsIdxGruppi[ k ]; } );
		Object.keys( gsIdxGruppi ).forEach( function ( k ) {
			if ( gsIdxOrdineFinale.indexOf( k ) === -1 ) { gsIdxOrdineFinale.push( k ); } // es. 'altro', in coda
		} );

		// Schede con puntino colorato e freccetta, come nella versione che
		// piace a Ennio ("molto leggibile") — ma il sottomenu di un gruppo
		// non si apre più SOTTO (spostava tutto il resto e creava
		// confusione), si apre a tendina sul lato DESTRO della scheda, senza
		// sovrapporsi/spostare l'elenco generale (richiesto il 2026-07-29).
		var html = '<button type="button" class="gs-idx-launcher" aria-label="Indice delle sezioni" aria-expanded="false"><span class="gs-launcher-ico">☰</span><span class="gs-launcher-lbl">Sezioni</span></button>';
		html += '<nav class="gs-idx" aria-label="Indice del pannello">';
		gsIdxOrdineFinale.forEach( function ( k ) {
			var etichetta = GS_IDX_GRUPPI[ k ] || 'Altro';
			html += '<div class="gs-idx-grp gs-idx-grp-' + k + '">';
			html += '<button type="button" class="gs-idx-grp-btn" data-grp="' + k + '" aria-expanded="false"><span class="gs-idx-dot"></span>' + etichetta + '<span class="gs-idx-chev">›</span></button>';
			html += '</div>';
		} );
		html += '</nav>';
		html += '<div class="gs-idx-flyout"><ul></ul></div>';
		$( 'body' ).append( html );

		function gsIdxChiudiFlyout() {
			$( '.gs-idx-grp-btn' ).attr( 'aria-expanded', 'false' );
			// Tolgo anche un eventuale "display" rimasto in linea da versioni
			// precedenti del plugin (bug corretto il 2026-07-30): senza
			// questo, su chi aveva già aperto una volta la tendina restava
			// bloccata aperta per sempre, anche dopo l'aggiornamento.
			$( '.gs-idx-flyout' ).removeClass( 'gs-idx-flyout-open' ).css( 'display', '' );
		}

		$( document ).on( 'click', '.gs-idx-grp-btn', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			var $btn = $( this ), k = $btn.data( 'grp' );
			var giaAperto = $btn.attr( 'aria-expanded' ) === 'true';
			gsIdxChiudiFlyout();
			if ( giaAperto ) { return; }

			var $fly = $( '.gs-idx-flyout' ), $ul = $fly.find( 'ul' ).empty();
			( gsIdxGruppi[ k ] || [] ).forEach( function ( it ) {
				$ul.append( '<li><a href="#' + it.id + '" data-target="' + it.id + '">' + it.label + '</a></li>' );
			} );
			// Classe del gruppo (gs-idx-grp-<k>) così la tendina eredita lo
			// stesso colore del pallino/bordo della scheda cliccata.
			$fly.attr( 'data-grp', k ).attr( 'class', 'gs-idx-flyout gs-idx-grp-' + k );

			var r = this.getBoundingClientRect();
			var larghezza = Math.min( 230, window.innerWidth - 24 );
			var sinistra = r.right + 10;
			if ( sinistra + larghezza > window.innerWidth - 8 ) {
				sinistra = Math.max( 8, window.innerWidth - larghezza - 8 );
			}
			// Misuro l'altezza VERA (dipende da quante voci ha il gruppo) prima
			// di scegliere la posizione, altrimenti un gruppo con tanti link
			// aperto da una scheda in basso finisce con l'ultima voce fuori
			// dallo schermo — segnalato da Ennio il 2026-07-30.
			$fly.css( { top: '0px', left: '-9999px', width: larghezza + 'px', display: 'block' } );
			var altezza = $fly[ 0 ].offsetHeight;
			var alto = Math.min( r.top, window.innerHeight - altezza - 8 );
			if ( alto < 8 ) { alto = 8; }
			// "display" era stato forzato in linea solo per poter misurare
			// l'altezza: va tolto subito, altrimenti resta più forte per
			// sempre della regola CSS che dovrebbe nascondere la tendina (il
			// sottomenu non si chiudeva più dal primo utilizzo in poi,
			// segnalato da Ennio il 2026-07-30).
			$fly.css( { top: alto + 'px', left: sinistra + 'px', width: larghezza + 'px', display: '' } );

			$btn.attr( 'aria-expanded', 'true' );
			$fly.addClass( 'gs-idx-flyout-open' );
		} );

		$( document ).on( 'click', '.gs-idx-launcher', function ( e ) {
			e.preventDefault();
			var open = $( 'body' ).toggleClass( 'gs-idx-open' ).hasClass( 'gs-idx-open' );
			$( this ).attr( 'aria-expanded', open ? 'true' : 'false' );
		} );
		$( document ).on( 'click', '.gs-idx-flyout a', function ( e ) {
			e.preventDefault();
			gsScrollTo( document.getElementById( $( this ).data( 'target' ) ) );
			gsIdxChiudiFlyout();
			if ( $( window ).width() < 900 ) { $( 'body' ).removeClass( 'gs-idx-open' ); $( '.gs-idx-launcher' ).attr( 'aria-expanded', 'false' ); }
		} );
		// Chiudi cliccando fuori dal pannello (o dal sottomenu a tendina), come
		// fa già il menu marrone a destra.
		$( document ).on( 'click', function ( e ) {
			if ( $( e.target ).closest( '.gs-idx-flyout' ).length === 0 ) { gsIdxChiudiFlyout(); }
			if ( ! $( 'body' ).hasClass( 'gs-idx-open' ) ) { return; }
			if ( $( e.target ).closest( '.gs-idx, .gs-idx-launcher, .gs-idx-flyout' ).length === 0 ) {
				$( 'body' ).removeClass( 'gs-idx-open' );
				$( '.gs-idx-launcher' ).attr( 'aria-expanded', 'false' );
			}
		} );
		// Chiudi con Esc.
		$( document ).on( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				$( 'body' ).removeClass( 'gs-idx-open' );
				$( '.gs-idx-launcher' ).attr( 'aria-expanded', 'false' );
				gsIdxChiudiFlyout();
			}
		} );

		// Schede della bacheca di riepilogo e delle statistiche: un clic porta
		// dritti alla sezione corrispondente, con lo stesso salto "sicuro" dell'indice.
		$( document ).on( 'click', '.gs-riep-card[data-target], .gs-stat-card[data-target]', function ( e ) {
			e.preventDefault();
			gsScrollTo( document.getElementById( $( this ).data( 'target' ) ) );
		} );

		// Se si arriva da un link con ancora (es. dal menu di WordPress, o da
		// un'email "rispondi qui" a una conversazione privata), correggi la
		// posizione. Se l'ancora è un <details> (conversazione, messaggio…),
		// aprilo davvero: altrimenti si scorre fino a un riquadro chiuso.
		if ( window.location.hash ) {
			var target = document.getElementById( window.location.hash.replace( '#', '' ) );
			if ( target ) {
				if ( 'DETAILS' === target.tagName ) { target.open = true; }
				// Se l'ancora punta dentro "In diretta" (Aeroplanino/Palloncini/
				// Palloncino Gigante riuniti in una sola zona a linguette,
				// richiesto da Ennio il 18/08/2026): attiva la linguetta giusta
				// prima di scorrere, altrimenti il bersaglio resta nascosto.
				var pannelloDiretta = target.closest ? target.closest( '.gs-diretta-pannello' ) : null;
				if ( pannelloDiretta ) { gsDirettaAttivaScheda( pannelloDiretta ); }
				setTimeout( function () { gsScrollTo( target ); }, 350 );
			}
		}
	} );

	// ---- "In diretta": linguette Aeroplanino / Palloncini / Palloncino Gigante ----
	function gsDirettaAttivaScheda( $pannello ) {
		$pannello = $( $pannello );
		var chiave = $pannello.data( 'pannello' );
		var $wrap = $pannello.closest( '.gs-diretta' );
		$wrap.find( '.gs-diretta-tab' ).removeClass( 'on' );
		$wrap.find( '.gs-diretta-tab[data-tab="' + chiave + '"]' ).addClass( 'on' );
		$wrap.find( '.gs-diretta-pannello' ).hide();
		$pannello.show();
	}
	$( document ).on( 'click', '.gs-diretta-tab', function () {
		var chiave = $( this ).data( 'tab' );
		var $pannello = $( this ).closest( '.gs-diretta' ).find( '.gs-diretta-pannello[data-pannello="' + chiave + '"]' );
		gsDirettaAttivaScheda( $pannello );
	} );

	/* Calibrazione dell'indice laterale (pannello) */
	$( document ).on( 'input', '.gs-idx-extra', function () {
		window.GS_IDX_EXTRA = parseInt( $( this ).val(), 10 ) || 0;
		$( this ).closest( '.gs-form-idx' ).find( '.gs-idx-val' ).text( window.GS_IDX_EXTRA + ' px' );
	} );
	$( document ).on( 'click', '.gs-idx-prova', function ( e ) {
		e.preventDefault();
		// I link vivono nel sottomenu a tendina di un gruppo (si popolano al
		// clic sulla scheda): apro il primo gruppo e poi simulo il clic su
		// un suo link, per testare davvero lo scarto configurato.
		var $g = $( '.gs-idx-grp-btn' );
		if ( ! $g.length ) { return; }
		$g.eq( 0 ).trigger( 'click' );
		setTimeout( function () {
			var $a = $( '.gs-idx-flyout a' );
			$a.eq( Math.min( 2, $a.length - 1 ) ).trigger( 'click' );
		}, 30 );
	} );
	$( document ).on( 'click', '.gs-idx-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-idx' ), $m = $f.find( '.gs-idx-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		$.post( GS_AJAX.url, { action: 'gs_idx_extra_salva', nonce: GS_AJAX.nonce, extra: $f.find( '.gs-idx-extra' ).val() } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore.' ); } );
	} );

	/* Diagnostica dell'indice laterale: mostra i numeri reali del browser */
	$( document ).on( 'click', '.gs-idx-diag', function ( e ) {
		e.preventDefault();
		var $out = $( this ).closest( '.gs-box' ).find( '.gs-idx-diag-out' );
		var righe = [];
		righe.push( 'Versione JS caricata: ' + ( window.GS_IDX_VERSIONE || 'sconosciuta (file vecchio in cache!)' ) );
		righe.push( 'Regolazione attiva (idx_extra): ' + ( ( typeof GS_AJAX !== 'undefined' && GS_AJAX.idx_extra !== undefined ) ? GS_AJAX.idx_extra : 'NON ARRIVA (file vecchio)' ) );
		righe.push( '' );
		righe.push( 'Elementi FISSI in cima allo schermo:' );
		var trovati = 0;
		document.querySelectorAll( 'body *' ).forEach( function ( el ) {
			var st = window.getComputedStyle( el );
			if ( st.position !== 'fixed' ) { return; }
			var r = el.getBoundingClientRect();
			if ( r.top > 4 || r.height < 1 || r.width < window.innerWidth * 0.5 ) { return; }
			trovati++;
			if ( trovati <= 6 ) {
				righe.push( '  • ' + ( el.id ? '#' + el.id : el.className.toString().substring( 0, 40 ) ) + ' → altezza ' + Math.round( r.height ) + 'px, bordo inferiore a ' + Math.round( r.bottom ) + 'px' );
			}
		} );
		if ( ! trovati ) { righe.push( '  (nessuno: nessuna barra fissa in questo momento)' ); }
		righe.push( '' );
		var $sez = $( '.gs-box' ).eq( 2 );
		if ( $sez.length ) {
			righe.push( 'Posizione attuale della 3ª sezione: ' + Math.round( $sez[ 0 ].getBoundingClientRect().top ) + 'px dal bordo alto' );
		}
		righe.push( 'Scorrimento attuale della pagina: ' + Math.round( window.pageYOffset ) + 'px' );
		$out.text( righe.join( '\n' ) ).show();
	} );

	window.GS_IDX_VERSIONE = '3.10.2';

	/* Pagine pubbliche nel menu del sito: matrice pagina × menu, più menu insieme */
	$( document ).on( 'click', '.gs-menu-matrice-salva', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-menu-pagine-matrice' ), $m = $f.find( '.gs-menu-matrice-msg' );
		$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
		var stati = {};
		$f.find( '.gs-menu-check' ).each( function () {
			var pagina = $( this ).data( 'pagina' ), menu = $( this ).data( 'menu' );
			if ( ! stati[ pagina ] ) { stati[ pagina ] = {}; }
			stati[ pagina ][ menu ] = this.checked ? 1 : 0;
		} );
		$.post( GS_AJAX.url, { action: 'gs_menu_matrice_salva', nonce: GS_AJAX.nonce, stati: stati } )
			.done( function ( res ) {
				$m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) { setTimeout( function () { gsReloadMantenendoPosizione(); }, 900 ); }
			} )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	/* Applica la struttura del menu proposta: un pulsante, riorganizza il menu scelto. */
	$( document ).on( 'click', '.gs-menu-struttura-applica', function ( e ) {
		e.preventDefault();
		if ( ! window.confirm( 'Riorganizzare questo menu secondo la struttura proposta? Le voci vengono spostate, mai cancellate.' ) ) { return; }
		var $f = $( this ).closest( '.gs-form-menu-struttura' ), $m = $f.find( '.gs-menu-struttura-msg' );
		$m.removeClass( 'ok err' ).text( 'Applicazione…' );
		$.post( GS_AJAX.url, { action: 'gs_menu_struttura_applica', nonce: GS_AJAX.nonce, menu_id: $f.find( '[name=menu_id]' ).val() } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	/* Correggi voci trovate nel menu ("miao", FAQ morta, Dicono di noi → La Nostra Sede). */
	$( document ).on( 'click', '.gs-menu-correzioni-applica', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-menu-correzioni' ), $m = $f.find( '.gs-menu-correzioni-msg' );
		$m.removeClass( 'ok err' ).text( 'Correzione…' );
		$.post( GS_AJAX.url, { action: 'gs_menu_correzioni_applica', nonce: GS_AJAX.nonce, menu_id: $f.find( '[name=menu_id]' ).val() } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	/* Riparazione: rimuove i gruppi L'Accademia/Corsi/Community/Contenuti da un menu su cui sono finiti per sbaglio. */
	$( document ).on( 'click', '.gs-menu-rimuovi-gruppi', function ( e ) {
		e.preventDefault();
		if ( ! window.confirm( 'Rimuovere i gruppi L\'Accademia/Corsi/Community/Contenuti da questo menu? Vengono tolte solo le voci di menu, mai le pagine a cui puntano.' ) ) { return; }
		var $f = $( this ).closest( '.gs-form-menu-rimuovi-gruppi' ), $m = $f.find( '.gs-menu-rimuovi-gruppi-msg' );
		$m.removeClass( 'ok err' ).text( 'Rimozione…' );
		$.post( GS_AJAX.url, { action: 'gs_menu_struttura_rimuovi_gruppi', nonce: GS_AJAX.nonce, menu_id: $f.find( '[name=menu_id]' ).val() } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	/* Diagnostica: mostra le voci di primo livello di un menu, per capire perché una corrispondenza non scatta. */
	$( document ).on( 'click', '.gs-menu-diagnostica', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-menu-diagnostica' ), $m = $f.find( '.gs-menu-diagnostica-msg' );
		$m.removeClass( 'ok err' ).text( 'Lettura…' );
		$.post( GS_AJAX.url, { action: 'gs_menu_struttura_diagnostica', nonce: GS_AJAX.nonce, menu_id: $f.find( '[name=menu_id]' ).val() } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	/* Riparazione mirata: "L'Esperto Risponde" — la riporta di primo livello e corregge il titolo, in un clic. */
	$( document ).on( 'click', '.gs-menu-ripara-esperto', function ( e ) {
		e.preventDefault();
		var $f = $( this ).closest( '.gs-form-menu-ripara-esperto' ), $m = $f.find( '.gs-menu-ripara-esperto-msg' );
		$m.removeClass( 'ok err' ).text( 'Riparazione…' );
		$.post( GS_AJAX.url, { action: 'gs_menu_struttura_ripara_esperto', nonce: GS_AJAX.nonce, menu_id: $f.find( '[name=menu_id]' ).val() } )
			.done( function ( res ) { $m.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' ); } )
			.fail( function () { $m.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	// -------------------------------------------------------------------------
	// Aeroplanino rosa: striscione che attraversa lo schermo per QUALSIASI
	// avviso alla sfoglina (messaggio, conversazione, badge, livello, aiuto
	// gestito, prenotazione confermata, risposta dell'esperto…). Ogni avviso
	// vola sempre DUE volte di fila, in coda se più avvisi arrivano insieme
	// così non si sovrappongono mai sullo schermo.
	// -------------------------------------------------------------------------
	var GS_AEREO_SVG =
		'<svg class="gs-aereo-svg" viewBox="0 0 160 110" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">' +
		'<path d="M40 52 L16 10 Q12 4 30 14 L60 50 Z" fill="#ff7ab8" stroke="#d84f9a" stroke-width="3" stroke-linejoin="round"/>' +
		'<path d="M22 60 L2 58 Q-1 65 6 68 L26 68 Z" fill="#ff7ab8" stroke="#d84f9a" stroke-width="2.5" stroke-linejoin="round"/>' +
		'<ellipse cx="88" cy="60" rx="62" ry="30" fill="#ff8fc7" stroke="#d84f9a" stroke-width="4"/>' +
		'<path d="M132 38 Q156 60 132 82 Q144 60 132 38 Z" fill="#ffd1e8"/>' +
		'<path d="M95 66 L66 100 Q62 104 76 92 L108 66 Z" fill="#ff6fb0" stroke="#d84f9a" stroke-width="2.5" stroke-linejoin="round"/>' +
		'<circle cx="70" cy="56" r="7" fill="#fff" stroke="#d84f9a" stroke-width="2.5"/>' +
		'<circle cx="94" cy="56" r="7" fill="#fff" stroke="#d84f9a" stroke-width="2.5"/>' +
		'<path d="M118 50 Q132 52 128 64 Q117 63 113 58 Z" fill="#fff" stroke="#d84f9a" stroke-width="2.5"/>' +
		'<path d="M120 72 Q128 78 136 72" fill="none" stroke="#d84f9a" stroke-width="2.5" stroke-linecap="round"/>' +
		'<circle cx="151" cy="60" r="4.5" fill="#d84f9a"/>' +
		'</svg>';

	function gsEscapeHtml( s ) {
		return String( s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	// Se c'è un link, lo striscione diventa cliccabile: un clic porta
	// direttamente al messaggio/contenuto da visionare, senza aspettare che
	// l'animazione finisca da sola. Se invece c'è un aereoId (Aeroplanino
	// della redazione, senza link), lo striscione è comunque cliccabile: il
	// clic conta come "l'ho letto" — vedi gs_ajax_aeroplanino_click() — e fa
	// sparire subito lo striscione, senza aspettare la fine dell'animazione.
	function gsUnVolo( testo, link, aereoId, sponsor, cb ) {
		var el = document.createElement( 'div' );
		var cliccabile = !! ( link || aereoId );
		el.className = 'gs-aeroplano' + ( cliccabile ? ' gs-aeroplano-link' : '' );
		el.setAttribute( 'aria-hidden', 'true' );
		// Logo dello sponsor, se questo invio ne ha uno agganciato (richiesto
		// da Ennio il 17/08/2026, spostato il 18/08/2026 "dopo la punta dello
		// striscione"): non più appeso al filo verso l'aeroplanino, ma in
		// coda allo striscione, oltre la punta a bandiera (.gs-striscione::before)
		// — quindi dentro .gs-striscione stesso, che è già position:relative.
		var filoHtml = '<span class="gs-aereo-filo"></span>';
		var logoHtml = '';
		if ( sponsor && sponsor.foto ) {
			logoHtml = '<img class="gs-aereo-sponsor-logo" src="' + gsEscapeHtml( sponsor.foto ) + '" alt="' + gsEscapeHtml( sponsor.nome || 'Sponsor' ) + '">';
		}
		el.innerHTML = '<span class="gs-striscione">' + gsEscapeHtml( testo ) + logoHtml + '</span>' + filoHtml + GS_AEREO_SVG;
		if ( sponsor && sponsor.foto ) {
			// Dimensione regolabile dal pannello (richiesto da Ennio il
			// 18/08/2026): il CSS resta un ripiego, il valore vero arriva da qui.
			var dimLogo = ( typeof GS_AJAX !== 'undefined' && GS_AJAX.aereo_logo_dimensione ) ? parseInt( GS_AJAX.aereo_logo_dimensione, 10 ) : 52;
			if ( dimLogo ) {
				var elLogo = el.querySelector( '.gs-aereo-sponsor-logo' );
				elLogo.style.width = dimLogo + 'px';
				elLogo.style.height = dimLogo + 'px';
			}
		}
		if ( sponsor && sponsor.url ) {
			el.querySelector( '.gs-aereo-sponsor-logo' ).addEventListener( 'click', function ( ev ) {
				ev.stopPropagation();
				window.open( sponsor.url, '_blank', 'noopener' );
			} );
		}
		if ( link ) {
			el.setAttribute( 'role', 'link' );
			el.setAttribute( 'title', 'Clicca per andare al messaggio' );
			el.addEventListener( 'click', function () { window.location.href = link; } );
		} else if ( aereoId ) {
			el.setAttribute( 'role', 'button' );
			el.setAttribute( 'title', 'Clicca per leggerlo' );
			el.addEventListener( 'click', function () {
				if ( typeof GS_AJAX !== 'undefined' ) {
					$.post( GS_AJAX.url, { action: 'gs_aeroplanino_click', nonce: GS_AJAX.nonce, id: aereoId } );
				}
				fine();
			} );
		}
		document.body.appendChild( el );
		var fine = function () {
			if ( el.parentNode ) { el.parentNode.removeChild( el ); }
			if ( cb ) { var f = cb; cb = null; f(); }
		};
		el.addEventListener( 'animationend', fine );
		setTimeout( fine, 23000 ); // margine di sicurezza sopra i 22s dell'animazione gs-vola (gaming.css)
	}

	// Coda unica: se più avvisi arrivano insieme (es. un messaggio e un badge
	// nello stesso giro di controllo) volano uno dopo l'altro, mai sovrapposti.
	// Ogni voce è { testo, link } — link facoltativo.
	var gsVoloCoda = [];
	var gsVoloInCorso = false;

	function gsVoloProssimo() {
		if ( gsVoloInCorso || ! gsVoloCoda.length ) { return; }
		gsVoloInCorso = true;
		var voce = gsVoloCoda.shift();
		gsUnVolo( voce.testo, voce.link, voce.aereoId, voce.sponsor, function () {
			gsVoloInCorso = false;
			gsVoloProssimo();
		} );
	}

	// Accoda "volte" passaggi dell'aeroplanino con lo stesso messaggio (richiesta: sempre due).
	function gsAllarmeVolo( testo, volte, link, aereoId, sponsor ) {
		volte = volte || 2;
		for ( var i = 0; i < volte; i++ ) { gsVoloCoda.push( { testo: testo, link: link || '', aereoId: aereoId || '', sponsor: sponsor || null } ); }
		gsVoloProssimo();
	}

	// ---- Messaggi dalla segreteria: conteggio in tempo reale ----------------
	( function () {
		if ( typeof GS_MSG === 'undefined' || ! GS_MSG ) { return; }

		var visti = 0;
		try { visti = parseInt( window.localStorage.getItem( 'gs_msg_visti' ) || '0', 10 ) || 0; } catch ( e ) {}

		function valuta( n ) {
			n = parseInt( n, 10 ) || 0;
			if ( n > visti ) { gsAllarmeVolo( 'MESSAGGIO IN ARRIVO', 2, GS_MSG.msg_url || '' ); }
			visti = n;
			try { window.localStorage.setItem( 'gs_msg_visti', String( n ) ); } catch ( e ) {}
			gsNonLettiMenu.msg = n;
			gsAggiornaLampeggioMenuAccedi();
		}

		$( function () { valuta( GS_MSG.non_letti ); } );

		setInterval( function () {
			if ( document.hidden ) { return; }
			$.post( GS_AJAX.url, { action: 'gs_msg_conteggio', nonce: GS_AJAX.nonce } )
				.done( function ( res ) {
					if ( res && res.success && res.data ) { valuta( res.data.non_letti ); }
				} );
		}, 15000 );
	} )();

	// ---- Conversazioni (esperto/sfoglina): conteggio in tempo reale ---------
	( function () {
		if ( typeof GS_MSG === 'undefined' || ! GS_MSG || typeof GS_MSG.conv_non_letti === 'undefined' ) { return; }

		var visti = 0;
		try { visti = parseInt( window.localStorage.getItem( 'gs_conv_visti' ) || '0', 10 ) || 0; } catch ( e ) {}

		function valuta( n ) {
			n = parseInt( n, 10 ) || 0;
			if ( n > visti ) { gsAllarmeVolo( 'NUOVA RISPOSTA NELLA CONVERSAZIONE', 2, GS_MSG.msg_url || '' ); }
			visti = n;
			try { window.localStorage.setItem( 'gs_conv_visti', String( n ) ); } catch ( e ) {}
			gsNonLettiMenu.conv = n;
			gsAggiornaLampeggioMenuAccedi();
		}

		$( function () { valuta( GS_MSG.conv_non_letti ); } );

		setInterval( function () {
			if ( document.hidden ) { return; }
			$.post( GS_AJAX.url, { action: 'gs_conv_conteggio', nonce: GS_AJAX.nonce } )
				.done( function ( res ) {
					if ( res && res.success && res.data ) { valuta( res.data.non_letti ); }
				} );
		}, 15000 );
	} )();

	// ---- Aeroplanino della redazione: lo vede chi gestisce il portale, su -----
	// ---- OGNI suo dispositivo/sessione collegato in quel momento -------------
	// A differenza della coda sopra (che si svuota al primo dispositivo che la
	// legge), qui ogni dispositivo confronta il timestamp dell'ultimo messaggio
	// con quello che ha già mostrato lui (salvato nel proprio localStorage): così
	// computer e telefono, entrambi collegati con lo stesso account, lo mostrano
	// insieme invece che solo il primo che interroga il server.
	( function () {
		if ( typeof GS_MSG === 'undefined' || ! GS_MSG || ! GS_MSG.gestore || typeof GS_AJAX === 'undefined' ) { return; }

		var ultimoVisto = 0;
		try { ultimoVisto = parseInt( window.localStorage.getItem( 'gs_aereo_visto' ) || '0', 10 ) || 0; } catch ( e ) {}

		function segnaVisto( ts ) {
			ultimoVisto = ts;
			try { window.localStorage.setItem( 'gs_aereo_visto', String( ts ) ); } catch ( e ) {}
		}
		// Il primo controllo, appena la pagina si carica, non deve far volare
		// messaggi già visti prima su questo stesso dispositivo — solo registra
		// il punto di partenza; da lì in poi ogni nuovo invio farà volare.
		var primoGiro = true;

		function controlla() {
			if ( document.hidden ) { return; }
			$.post( GS_AJAX.url, { action: 'gs_aeroplanino_ultimo', nonce: GS_AJAX.nonce } )
				.done( function ( res ) {
					if ( ! res || ! res.success || ! res.data || ! res.data.ts ) { return; }
					var ts = parseInt( res.data.ts, 10 ) || 0;
					if ( primoGiro ) { primoGiro = false; if ( ts > ultimoVisto ) { segnaVisto( ts ); } return; }
					if ( ts > ultimoVisto && res.data.testo ) {
						gsAllarmeVolo( '🛩️ ' + res.data.testo, 2, '', '', res.data.sponsor || null );
						segnaVisto( ts );
					}
				} );
		}
		// Espongo segnaVisto: il modulo che invia il messaggio lo richiama subito
		// dopo l'invio riuscito, per mostrarlo sul proprio dispositivo senza
		// aspettare il prossimo giro di interrogazione, e per non farlo volare
		// una seconda volta quando quel giro arriva.
		window.gsAeroplaninoSegnaVisto = segnaVisto;

		setInterval( controlla, 15000 );
	} )();

	// ---- Badge, livello, aiuto gestito, prenotazioni, risposte esperto ------
	// Questi eventi non hanno un "conteggio", arrivano già come testo pronto
	// dal server tramite una coda (vedi includes/volo-notifiche.php).
	( function () {
		if ( typeof GS_AJAX === 'undefined' ) { return; }

		function preleva() {
			if ( document.hidden ) { return; }
			$.post( GS_AJAX.url, { action: 'gs_voli_preleva', nonce: GS_AJAX.nonce } )
				.done( function ( res ) {
					if ( res && res.success && res.data && res.data.voli && res.data.voli.length ) {
						res.data.voli.forEach( function ( v ) {
							if ( v && v.testo ) { gsAllarmeVolo( v.testo, 2, v.link || '', v.aereo_id || '', v.sponsor || null ); }
						} );
					}
				} );
		}

		$( function () { preleva(); } );
		setInterval( preleva, 15000 );
	} )();

	// -------------------------------------------------------------------------
	// Palloncini — festeggiamenti in diretta (compleanno/diploma/festa).
	// Grafica/audio v2 (2026-08-10): messi a punto in un file di prova
	// separato (palloncini-anteprima.html) con più giri di correzioni —
	// forma con luce/nodo/nastro in SVG, nastro che si stacca e cade allo
	// scoppio, scoppio su tre registri di altezza, urto secco (non un
	// sibilo continuo) quando due palloncini si toccano davvero — poi
	// approvati e portati qui. A differenza dell'aeroplanino della redazione
	// (solo i gestori), i palloncini li vede OGNI utente collegato: sono
	// pensati per festeggiare insieme, non per un avviso di servizio — vedi
	// includes/volo-notifiche.php, gs_pannello_palloncini() /
	// gs_ajax_palloncini_invia() / gs_ajax_palloncini_ultimo().
	// -------------------------------------------------------------------------
	( function () {
		// Tavolozza allineata ai colori reali del sito (verde/oro/terracotta
		// dell'Accademia — richiesto da Ennio il 18/08/2026, al posto
		// dell'arcobaleno generico di prima).
		var GS_PALLONE_COLORI = [ '#cd8b0c', '#1f6e37', '#bd8a13', '#b5722a', '#6b8e4e', '#a6790c', '#8a5420', '#d9a441' ];
		var gsPalloneIntervallo = null;
		var gsPalloniAttivi = []; // per controllare quando due palloncini si toccano
		var gsPalloneAudioCtx = null;
		var gsPalloneUltimoSuonoGlobale = 0;

		function gsPalloneColoreCasuale() {
			return GS_PALLONE_COLORI[ Math.floor( Math.random() * GS_PALLONE_COLORI.length ) ];
		}

		function gsPalloneScena() {
			var scena = document.querySelector( '.gs-scena-palloncini' );
			if ( ! scena ) {
				scena = document.createElement( 'div' );
				scena.className = 'gs-scena-palloncini';
				scena.setAttribute( 'aria-hidden', 'true' );
				document.body.appendChild( scena );
			}
			return scena;
		}

		// Segnalato: nei primi secondi (tanti scoppi vicini) il suono sembrava
		// sempre lo stesso. L'altezza di partenza è scelta a caso tra tre
		// registri ben distinti — acuto/medio/grave — non solo un piccolo
		// scarto casuale dentro alla stessa gamma: ogni scoppio suona diverso.
		function gsPalloneSuonoScoppio() {
			try {
				if ( ! gsPalloneAudioCtx ) { gsPalloneAudioCtx = new ( window.AudioContext || window.webkitAudioContext )(); }
				var ctx = gsPalloneAudioCtx;
				var ora = ctx.currentTime;
				var registro = Math.random();
				var fStart, fFine;
				if ( registro < 0.33 ) { fStart = 1300 + Math.random() * 500; fFine = 260 + Math.random() * 90; }        // acuto
				else if ( registro < 0.66 ) { fStart = 750 + Math.random() * 350; fFine = 130 + Math.random() * 60; }    // medio
				else { fStart = 400 + Math.random() * 250; fFine = 55 + Math.random() * 35; }                            // grave

				var osc = ctx.createOscillator();
				var vol = ctx.createGain();
				osc.type = 'sawtooth';
				osc.frequency.setValueAtTime( fStart, ora );
				osc.frequency.exponentialRampToValueAtTime( fFine, ora + 0.055 );
				vol.gain.setValueAtTime( 0.18, ora );
				vol.gain.exponentialRampToValueAtTime( 0.001, ora + 0.08 );
				osc.connect( vol ).connect( ctx.destination );
				osc.start( ora );
				osc.stop( ora + 0.09 );

				var durataRumore = 0.045;
				var buffer = ctx.createBuffer( 1, ctx.sampleRate * durataRumore, ctx.sampleRate );
				var dati = buffer.getChannelData( 0 );
				for ( var i = 0; i < dati.length; i++ ) { dati[ i ] = ( Math.random() * 2 - 1 ) * ( 1 - i / dati.length ); }
				var rumore = ctx.createBufferSource();
				rumore.buffer = buffer;
				var passaAlto = ctx.createBiquadFilter();
				passaAlto.type = 'highpass';
				passaAlto.frequency.value = 2200;
				var volRumore = ctx.createGain();
				volRumore.gain.setValueAtTime( 0.26, ora );
				rumore.connect( passaAlto ).connect( volRumore ).connect( ctx.destination );
				rumore.start( ora );
			} catch ( e ) { /* Web Audio non disponibile: niente audio, l'effetto visivo resta comunque */ }
		}

		// Un urto secco e breve — due palloncini pieni che si toccano un
		// attimo, non un suono che dura. Ogni volta diverso (altezza e
		// timbro a caso), mai lo stesso due volte, finisce subito: nessuna
		// coda (segnalato più volte: prima sembrava un sibilo continuo).
		function gsPalloneSuonoSfregamento() {
			try {
				if ( ! gsPalloneAudioCtx ) { gsPalloneAudioCtx = new ( window.AudioContext || window.webkitAudioContext )(); }
				var ctx = gsPalloneAudioCtx;
				var ora = ctx.currentTime;
				var durata = 0.10 + Math.random() * 0.08;
				var fBase = 180 + Math.random() * 220;

				var osc = ctx.createOscillator();
				var vol = ctx.createGain();
				osc.type = Math.random() < 0.5 ? 'triangle' : 'sine';
				osc.frequency.setValueAtTime( fBase, ora );
				osc.frequency.exponentialRampToValueAtTime( fBase * 0.55, ora + durata );
				vol.gain.setValueAtTime( 0.001, ora );
				vol.gain.linearRampToValueAtTime( 0.20 + Math.random() * 0.08, ora + 0.006 );
				vol.gain.exponentialRampToValueAtTime( 0.001, ora + durata );
				osc.connect( vol ).connect( ctx.destination );
				osc.start( ora );
				osc.stop( ora + durata + 0.02 );

				// un pizzico di rumore secco insieme al tono, la "gomma" del contatto
				var nDur = durata * 0.6;
				var n = Math.max( 1, Math.floor( ctx.sampleRate * nDur ) );
				var buffer = ctx.createBuffer( 1, n, ctx.sampleRate );
				var dati = buffer.getChannelData( 0 );
				for ( var i = 0; i < n; i++ ) { dati[ i ] = ( Math.random() * 2 - 1 ) * ( 1 - i / n ); }
				var rumore = ctx.createBufferSource();
				rumore.buffer = buffer;
				var volRumore = ctx.createGain();
				volRumore.gain.setValueAtTime( 0.10 + Math.random() * 0.05, ora );
				rumore.connect( volRumore ).connect( ctx.destination );
				rumore.start( ora );
			} catch ( e ) { /* Web Audio non disponibile: niente audio, l'effetto visivo resta comunque */ }
		}

		function gsPalloneScoppia( x, y, colore ) {
			var scena = gsPalloneScena();
			var wrap = document.createElement( 'div' );
			wrap.className = 'gs-scoppio';
			wrap.style.left = x + 'px';
			wrap.style.top = y + 'px';

			var lampo = document.createElement( 'div' );
			lampo.className = 'gs-scoppio-lampo';
			wrap.appendChild( lampo );

			for ( var i = 0; i < 14; i++ ) {
				var frammento = document.createElement( 'span' );
				var angolo = ( Math.PI * 2 * i ) / 14 + Math.random() * 0.3;
				var distanza = 48 + Math.random() * 34;
				frammento.style.setProperty( '--gs-dx', Math.cos( angolo ) * distanza + 'px' );
				frammento.style.setProperty( '--gs-dy', Math.sin( angolo ) * distanza + 'px' );
				frammento.style.background = colore;
				wrap.appendChild( frammento );
			}
			scena.appendChild( wrap );
			setTimeout( function () { if ( wrap.parentNode ) { wrap.remove(); } }, 650 );
			gsPalloneSuonoScoppio();
		}

		// Pausa minima tra un urto e l'altro IN GENERALE (non solo per la
		// stessa coppia): con tanti palloncini vicini altrimenti i suoni si
		// accavallano uno sull'altro e sembra un rumore continuo invece di
		// urti distanziati. Controllo ogni 50ms (non 140) perché il suono
		// resti aderente al momento esatto in cui si toccano davvero.
		var gsPalloneControlloContattiAttivo = false;
		function gsPalloneAvviaControlloContatti() {
			if ( gsPalloneControlloContattiAttivo ) { return; }
			gsPalloneControlloContattiAttivo = true;
			gsPalloneControllaContatti();
		}
		function gsPalloneControllaContatti() {
			if ( ! gsPalloniAttivi.length ) { gsPalloneControlloContattiAttivo = false; return; }
			var ora = Date.now();
			if ( ora - gsPalloneUltimoSuonoGlobale >= 650 ) {
				for ( var i = 0; i < gsPalloniAttivi.length; i++ ) {
					var a = gsPalloniAttivi[ i ];
					if ( ! a.isConnected ) { continue; }
					var ra = a.getBoundingClientRect();
					for ( var j = i + 1; j < gsPalloniAttivi.length; j++ ) {
						var b = gsPalloniAttivi[ j ];
						if ( ! b.isConnected ) { continue; }
						var rb = b.getBoundingClientRect();
						var siToccano = ra.left < rb.right && ra.right > rb.left && ra.top < rb.bottom && ra.bottom > rb.top;
						if ( ! siToccano ) { continue; }
						var ultimoA = parseInt( a.dataset.ultimoSfregamento || '0', 10 );
						var ultimoB = parseInt( b.dataset.ultimoSfregamento || '0', 10 );
						if ( ora - ultimoA < 900 && ora - ultimoB < 900 ) { continue; }
						a.dataset.ultimoSfregamento = String( ora );
						b.dataset.ultimoSfregamento = String( ora );
						gsPalloneUltimoSuonoGlobale = ora;
						gsPalloneSuonoSfregamento();
						break;
					}
					if ( ora === gsPalloneUltimoSuonoGlobale ) { break; } // un solo urto per giro, non tutti insieme
				}
			}
			setTimeout( gsPalloneControllaContatti, 50 );
		}

		// Il filo si muove libero: ad ogni giro sceglie un nuovo angolo e un
		// nuovo tempo a caso (non un loop fisso avanti-indietro), così ogni
		// palloncino svolazza in modo diverso dagli altri. Escursioni piccole
		// e transizioni lente e morbide (curva ease-in-out nel CSS) per un
		// movimento leggiadro, non a scatti.
		function gsPalloneSvolazzaFilo( nastro ) {
			if ( ! nastro || ! nastro.isConnected ) { return; }
			var angolo = -10 + Math.random() * 20;
			nastro.style.transform = 'translateX(-50%) rotate(' + angolo + 'deg)';
			var prossimoGiro = 450 + Math.random() * 550;
			nastro.style.transitionDuration = ( 0.45 + Math.random() * 0.4 ) + 's';
			setTimeout( function () { gsPalloneSvolazzaFilo( nastro ); }, prossimoGiro );
		}

		// Quando il palloncino scoppia, il nastro non sparisce di colpo con
		// lui: resta un istante appeso a mezz'aria e cade per conto suo
		// ruotando (stessa forma SVG, ricreata su un elemento indipendente).
		function gsPalloneStaccaNastro( nastro ) {
			if ( ! nastro ) { return; }
			var rect = nastro.getBoundingClientRect();
			var libero = document.createElement( 'div' );
			libero.className = 'gs-nastro-libero';
			libero.style.left = rect.left + 'px';
			libero.style.top = rect.top + 'px';
			libero.innerHTML = nastro.innerHTML;
			document.body.appendChild( libero );
			setTimeout( function () { if ( libero.parentNode ) { libero.remove(); } }, 1200 );
		}

		function gsPalloneCrea( sponsor ) {
			var scena = gsPalloneScena();
			var pallone = document.createElement( 'div' );
			pallone.className = 'gs-palloncino';
			var colore = gsPalloneColoreCasuale();
			var sinistra = 4 + Math.random() * 88;
			var durataSalita = 4 + Math.random() * 2.5;
			var durataOndeggio = 2 + Math.random() * 1.5;
			// Ogni palloncino un po' diverso dagli altri: dimensione a caso
			// dentro a un intervallo contenuto (richiesta: grandi e piccoli
			// mescolati, non tutti dello stesso stampo).
			var scala = Math.max( 0.6, 1 + ( Math.random() * 2 - 1 ) * 0.25 );

			pallone.style.left = sinistra + 'vw';
			pallone.style.color = colore; /* per il nodo, che eredita currentColor */
			pallone.style.animationDuration = durataSalita + 's, ' + durataOndeggio + 's';
			pallone.style.animationDelay = ( Math.random() * 0.6 ) + 's';
			pallone.style.transform = 'scale(' + scala + ')';
			pallone.style.transformOrigin = 'bottom center';
			pallone.innerHTML = '<div class="gs-pallone-corpo" style="background-color:' + colore + '"></div>' +
				'<div class="gs-pallone-luce"></div><div class="gs-pallone-nodo"></div><div class="gs-pallone-nastro"></div>';
			pallone.dataset.ultimoSfregamento = '0';

			// Logo dello sponsor sul pallone, se questa lanciata ne ha uno
			// agganciato (richiesto da Ennio il 18/08/2026, stessa idea
			// dell'Aeroplanino): gsLanciaPalloncini decide se passarlo a un
			// solo pallone della lanciata o a tutti.
			if ( sponsor && sponsor.foto ) {
				pallone.insertAdjacentHTML( 'beforeend', '<img class="gs-pallone-sponsor-logo" src="' + gsEscapeHtml( sponsor.foto ) + '" alt="' + gsEscapeHtml( sponsor.nome || 'Sponsor' ) + '">' );
				if ( sponsor.url ) {
					var logoEl = pallone.querySelector( '.gs-pallone-sponsor-logo' );
					logoEl.addEventListener( 'click', function ( ev ) {
						ev.stopPropagation();
						window.open( sponsor.url, '_blank', 'noopener' );
					} );
				}
			}

			scena.appendChild( pallone );

			// Il nastro parte sempre dal centro esatto (x=12 su un viewBox
			// largo 24), per costruzione, e curva verso un lato a caso —
			// un vero disegno invece del trucco dei bordi CSS: con quello
			// il bordo dritto della scatola restava visibile su un lato e
			// sembrava partire da uno spigolo invece che dal centro.
			var nastroEl = pallone.querySelector( '.gs-pallone-nastro' );
			var versoDx = Math.random() < 0.5;
			var curva = 8 + Math.random() * 8;
			var x2 = versoDx ? ( 12 + curva ) : ( 12 - curva );
			nastroEl.innerHTML = '<svg viewBox="0 0 24 30" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">' +
				'<path d="M12 0 C12 12, ' + x2 + ' 14, ' + x2 + ' 28" stroke="rgba(255,255,255,.55)" stroke-width="2" fill="none" stroke-linecap="round"/>' +
				'</svg>';
			gsPalloneSvolazzaFilo( nastroEl );

			gsPalloniAttivi.push( pallone );
			gsPalloneAvviaControlloContatti();

			var rimosso = false;
			function rimuovi() {
				if ( rimosso ) { return; }
				rimosso = true;
				var idx = gsPalloniAttivi.indexOf( pallone );
				if ( idx !== -1 ) { gsPalloniAttivi.splice( idx, 1 ); }
				if ( pallone.parentNode ) { pallone.remove(); }
			}
			pallone.addEventListener( 'animationend', function ( e ) {
				if ( e.animationName !== 'gs-pallone-sale' || rimosso ) { return; }
				var rect = pallone.getBoundingClientRect();
				gsPalloneStaccaNastro( pallone.querySelector( '.gs-pallone-nastro' ) );
				gsPalloneScoppia( rect.left + rect.width / 2, rect.top + rect.height / 2, colore );
				rimuovi();
			} );
			setTimeout( rimuovi, ( durataSalita + 1.2 ) * 1000 ); // rete di sicurezza
		}

		function gsLanciaPalloncini( motivo, sponsor, distribuzione ) {
			gsFermaPalloncini();
			// Se c'è uno sponsor agganciato: "tutti" lo mette su ogni pallone
			// della lanciata, "uno" (default) lo mette solo sul primo che nasce
			// — deciso qui una volta sola con una chiusura, non a ogni pallone.
			var suTutti = !! sponsor && 'tutti' === distribuzione;
			var unoGiaAssegnato = false;
			function creaPallone() {
				var sp = null;
				if ( sponsor ) {
					if ( suTutti ) { sp = sponsor; }
					else if ( ! unoGiaAssegnato ) { sp = sponsor; unoGiaAssegnato = true; }
				}
				gsPalloneCrea( sp );
			}
			var quantiSubito = motivo === 'diploma' ? 14 : ( motivo === 'compleanno' ? 18 : 10 );
			for ( var i = 0; i < quantiSubito; i++ ) { setTimeout( creaPallone, i * 140 ); }
			var durataOndataSecondi = motivo === 'diploma' ? 5 : ( motivo === 'compleanno' ? 7 : 4 );
			gsPalloneIntervallo = setInterval( creaPallone, 260 );
			setTimeout( function () {
				if ( gsPalloneIntervallo ) { clearInterval( gsPalloneIntervallo ); gsPalloneIntervallo = null; }
			}, durataOndataSecondi * 1000 );
		}
		function gsFermaPalloncini() {
			if ( gsPalloneIntervallo ) { clearInterval( gsPalloneIntervallo ); gsPalloneIntervallo = null; }
		}

		// ---- Pannello di Controllo: pulsanti che fanno partire i palloncini ----
		$( document ).on( 'click', '.gs-palloncini-lancia', function () {
			if ( typeof GS_AJAX === 'undefined' ) { return; }
			var $btn = $( this );
			var motivo = $btn.data( 'motivo' );
			var $sezione = $btn.closest( '.gs-sotto-sezione-corpo' );
			var $msg = $sezione.find( '.gs-palloncini-msg' );
			var conSponsor = $sezione.find( '.gs-palloncini-con-sponsor' ).is( ':checked' );
			var distribuzione = $sezione.find( 'input[name="gs-palloncini-distribuzione"]:checked' ).val() || 'uno';
			$btn.prop( 'disabled', true );
			$.post( GS_AJAX.url, { action: 'gs_palloncini_invia', nonce: GS_AJAX.nonce, motivo: motivo, con_sponsor: conSponsor ? '1' : '', distribuzione: distribuzione } )
				.done( function ( res ) {
					$msg.removeClass( 'ok err' ).addClass( res && res.success ? 'ok' : 'err' )
						.text( ( res && res.data && res.data.message ) ? res.data.message : 'Errore.' );
					if ( res && res.success ) {
						var sponsor = ( res.data && res.data.sponsor ) ? res.data.sponsor : null;
						gsLanciaPalloncini( motivo, sponsor, distribuzione ); // li vede anche chi ha appena cliccato, senza aspettare il giro
						if ( window.gsPalloncimiSegnaVisto ) { window.gsPalloncimiSegnaVisto(); }
					}
				} )
				.fail( function () { $msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Errore di connessione.' ); } )
				.always( function () { $btn.prop( 'disabled', false ); } );
		} );

		// Mostra la scelta "un solo palloncino / tutti" solo quando lo sponsor è agganciato.
		$( document ).on( 'change', '.gs-palloncini-con-sponsor', function () {
			$( this ).closest( '.gs-sotto-sezione-corpo' ).find( '.gs-palloncini-distribuzione-riga' ).toggle( $( this ).is( ':checked' ) );
		} );

		// ---- Controllo periodico: li vede OGNI utente collegato (sfogline -----
		// ---- comprese), a differenza dell'aeroplanino della redazione ---------
		if ( typeof GS_AJAX !== 'undefined' && GS_AJAX.logged_in ) {
			var gsPalloneUltimoVisto = 0;
			try { gsPalloneUltimoVisto = parseInt( window.localStorage.getItem( 'gs_palloncini_visto' ) || '0', 10 ) || 0; } catch ( e ) {}

			function gsPalloneSegnaVisto( ts ) {
				gsPalloneUltimoVisto = ts;
				try { window.localStorage.setItem( 'gs_palloncini_visto', String( ts ) ); } catch ( e ) {}
			}
			window.gsPalloncimiSegnaVisto = function () { gsPalloneSegnaVisto( Math.floor( Date.now() / 1000 ) + 1 ); };

			var gsPallonePrimoGiro = true;
			function gsPalloneControlla() {
				if ( document.hidden ) { return; }
				$.post( GS_AJAX.url, { action: 'gs_palloncini_ultimo', nonce: GS_AJAX.nonce } )
					.done( function ( res ) {
						if ( ! res || ! res.success || ! res.data || ! res.data.ts ) { return; }
						var ts = parseInt( res.data.ts, 10 ) || 0;
						if ( gsPallonePrimoGiro ) {
							gsPallonePrimoGiro = false;
							if ( ts > gsPalloneUltimoVisto ) { gsPalloneSegnaVisto( ts ); }
							return;
						}
						if ( ts > gsPalloneUltimoVisto && res.data.motivo ) {
							gsLanciaPalloncini( res.data.motivo, res.data.sponsor || null, res.data.distribuzione || 'uno' );
							gsPalloneSegnaVisto( ts );
						}
					} );
			}
			setInterval( gsPalloneControlla, 15000 );
		}
	} )();

	// -------------------------------------------------------------------------
	// Palloncino Gigante — si gonfia fino a coprire tutto lo schermo e scoppia,
	// su OGNI utente collegato (sfogline comprese), come Palloncini qui sopra
	// ma con più controlli e tre versioni allo scoppio (pioggia di palloncini
	// / messaggio / aeroplanino) — vedi includes/palloncino-gigante.php.
	// -------------------------------------------------------------------------
	( function () {
		var GS_PG_STELLINE = [ '⭐', '🌟', '✨' ];
		var GS_PG_CUORICINI = [ '❤️', '💛', '💚', '💙', '💜', '🧡' ];
		var gsPgAudioCtx = null;

		function gsPgCtx() {
			if ( ! gsPgAudioCtx ) { gsPgAudioCtx = new ( window.AudioContext || window.webkitAudioContext )(); }
			return gsPgAudioCtx;
		}
		function gsPgSuonoGonfiaggio( durata, attivo ) {
			if ( ! attivo ) { return; }
			try {
				var c = gsPgCtx(), ora = c.currentTime;
				var osc = c.createOscillator(), vol = c.createGain();
				osc.type = 'sawtooth';
				osc.frequency.setValueAtTime( 90, ora );
				osc.frequency.exponentialRampToValueAtTime( 340, ora + durata );
				vol.gain.setValueAtTime( 0.0001, ora );
				vol.gain.linearRampToValueAtTime( 0.05, ora + 0.3 );
				vol.gain.setValueAtTime( 0.05, ora + durata - 0.2 );
				vol.gain.exponentialRampToValueAtTime( 0.0001, ora + durata );
				osc.connect( vol ).connect( c.destination );
				osc.start( ora ); osc.stop( ora + durata + 0.05 );
			} catch ( e ) {}
		}
		function gsPgSuonoEsplosione( attivo ) {
			if ( ! attivo ) { return; }
			try {
				var c = gsPgCtx(), ora = c.currentTime;
				var osc = c.createOscillator(), vol = c.createGain();
				osc.type = 'sawtooth';
				osc.frequency.setValueAtTime( 1400, ora );
				osc.frequency.exponentialRampToValueAtTime( 45, ora + 0.35 );
				vol.gain.setValueAtTime( 0.35, ora );
				vol.gain.exponentialRampToValueAtTime( 0.001, ora + 0.45 );
				osc.connect( vol ).connect( c.destination );
				osc.start( ora ); osc.stop( ora + 0.5 );

				var durataRumore = 0.35;
				var buffer = c.createBuffer( 1, c.sampleRate * durataRumore, c.sampleRate );
				var dati = buffer.getChannelData( 0 );
				for ( var i = 0; i < dati.length; i++ ) { dati[ i ] = ( Math.random() * 2 - 1 ) * Math.pow( 1 - i / dati.length, 1.4 ); }
				var rumore = c.createBufferSource();
				rumore.buffer = buffer;
				var passaBasso = c.createBiquadFilter();
				passaBasso.type = 'lowpass';
				passaBasso.frequency.value = 1800;
				var volRumore = c.createGain();
				volRumore.gain.setValueAtTime( 0.5, ora );
				rumore.connect( passaBasso ).connect( volRumore ).connect( c.destination );
				rumore.start( ora );
			} catch ( e ) {}
		}
		function gsPgSuonoScoppioPiccolo( attivo ) {
			if ( ! attivo ) { return; }
			try {
				var c = gsPgCtx(), ora = c.currentTime;
				var registro = Math.random(), fStart, fFine;
				if ( registro < 0.33 ) { fStart = 1300 + Math.random() * 500; fFine = 260 + Math.random() * 90; }
				else if ( registro < 0.66 ) { fStart = 750 + Math.random() * 350; fFine = 130 + Math.random() * 60; }
				else { fStart = 400 + Math.random() * 250; fFine = 55 + Math.random() * 35; }
				var osc = c.createOscillator(), vol = c.createGain();
				osc.type = 'sawtooth';
				osc.frequency.setValueAtTime( fStart, ora );
				osc.frequency.exponentialRampToValueAtTime( fFine, ora + 0.055 );
				vol.gain.setValueAtTime( 0.16, ora );
				vol.gain.exponentialRampToValueAtTime( 0.001, ora + 0.08 );
				osc.connect( vol ).connect( c.destination );
				osc.start( ora ); osc.stop( ora + 0.09 );
			} catch ( e ) {}
		}

		function gsPgScenaGigante() {
			var scena = document.querySelector( '.gs-pg-scena-gigante' );
			if ( ! scena ) {
				scena = document.createElement( 'div' );
				scena.className = 'gs-pg-scena-gigante';
				scena.setAttribute( 'aria-hidden', 'true' );
				document.body.appendChild( scena );
			}
			return scena;
		}
		function gsPgScenaPalloncini() {
			var scena = document.querySelector( '.gs-pg-scena-palloncini' );
			if ( ! scena ) {
				scena = document.createElement( 'div' );
				scena.className = 'gs-pg-scena-palloncini';
				scena.setAttribute( 'aria-hidden', 'true' );
				document.body.appendChild( scena );
			}
			return scena;
		}
		function gsPgCorpoHtml( persona, colore ) {
			if ( persona && persona.foto ) {
				return '<div class="corpo" style="background-image:url(\'' + String( persona.foto ).replace( /'/g, '%27' ) + '\');background-size:cover;background-position:center 38%"></div>';
			}
			if ( persona ) {
				return '<div class="corpo" style="background-color:' + colore + '"><div class="corpo-emoji">🧑‍🍳</div></div>';
			}
			return '<div class="corpo" style="background-color:' + colore + '"></div>';
		}

		// Stelline/cuoricini che scintillano sulla superficie del pallone
		// mentre si gonfia: elementi a parte che inseguono il pallone frame
		// per frame (se fossero dentro verrebbero ingranditi dallo scale()).
		function gsPgDecora( pallone, scena ) {
			var pezzi = [];
			for ( var i = 0; i < 6; i++ ) {
				var tipo = i % 2 === 0 ? 'stellina' : 'cuoricino';
				var dim = 18 + Math.random() * 14;
				var el = document.createElement( 'div' );
				el.className = tipo === 'stellina' ? 'gs-pg-stellina' : 'gs-pg-cuoricino';
				el.style.animation = 'none';
				el.style.width = dim + 'px';
				el.style.height = dim + 'px';
				el.style.transform = 'translate(-50%,-50%)';
				var guizzo = document.createElement( 'span' );
				guizzo.className = 'guizzo';
				guizzo.style.fontSize = dim + 'px';
				guizzo.style.animationDelay = ( Math.random() * 0.3 ) + 's';
				guizzo.textContent = tipo === 'stellina'
					? GS_PG_STELLINE[ Math.floor( Math.random() * GS_PG_STELLINE.length ) ]
					: GS_PG_CUORICINI[ Math.floor( Math.random() * GS_PG_CUORICINI.length ) ];
				el.appendChild( guizzo );
				scena.appendChild( el );
				pezzi.push( { el: el, angolo: Math.random() * Math.PI * 2, raggio: 0.28 + Math.random() * 0.4 } );
			}
			var attivo = true;
			function segui() {
				if ( ! attivo ) { return; }
				var r = pallone.getBoundingClientRect();
				var cx = r.left + r.width / 2, cy = r.top + r.height / 2;
				pezzi.forEach( function ( p ) {
					p.el.style.left = ( cx + Math.cos( p.angolo ) * ( r.width / 2 ) * p.raggio ) + 'px';
					p.el.style.top = ( cy + Math.sin( p.angolo ) * ( r.height / 2 ) * p.raggio ) + 'px';
				} );
				requestAnimationFrame( segui );
			}
			requestAnimationFrame( segui );
			return function fermaEPulisci() {
				attivo = false;
				pezzi.forEach( function ( p ) { if ( p.el.parentNode ) { p.el.remove(); } } );
			};
		}

		function gsPgEsplosioneFesta( scena, x, y, quantita, forzaBase ) {
			for ( var i = 0; i < quantita; i++ ) {
				var angolo = Math.random() * Math.PI * 2;
				var forzaBurst = forzaBase * ( 0.75 + Math.random() * 0.7 );
				var dx = Math.cos( angolo ) * forzaBurst;
				var dy = Math.sin( angolo ) * forzaBurst;
				var dxf = dx * 1.5 + ( Math.random() * 50 - 25 );
				var dyf = Math.abs( dy ) + forzaBase * ( 0.7 + Math.random() * 0.6 );
				var tipo = Math.random() < 0.5 ? 'stellina' : 'cuoricino';
				var dimEmoji = 20 + Math.random() * 16;

				var pezzo = document.createElement( 'div' );
				pezzo.className = tipo === 'stellina' ? 'gs-pg-stellina' : 'gs-pg-cuoricino';
				pezzo.style.left = x + 'px';
				pezzo.style.top = y + 'px';
				pezzo.style.width = dimEmoji + 'px';
				pezzo.style.height = dimEmoji + 'px';
				pezzo.style.setProperty( '--dx', dx + 'px' );
				pezzo.style.setProperty( '--dy', dy + 'px' );
				pezzo.style.setProperty( '--dxf', dxf + 'px' );
				pezzo.style.setProperty( '--dyf', dyf + 'px' );
				pezzo.style.setProperty( '--rot1', ( Math.random() * 360 - 180 ) + 'deg' );
				pezzo.style.setProperty( '--rot2', ( Math.random() * 900 - 450 ) + 'deg' );
				pezzo.style.animationDelay = ( Math.random() * 0.06 ) + 's';

				var guizzo = document.createElement( 'span' );
				guizzo.className = 'guizzo';
				guizzo.style.fontSize = dimEmoji + 'px';
				guizzo.style.animationDelay = ( Math.random() * 0.2 ) + 's';
				guizzo.textContent = tipo === 'stellina'
					? GS_PG_STELLINE[ Math.floor( Math.random() * GS_PG_STELLINE.length ) ]
					: GS_PG_CUORICINI[ Math.floor( Math.random() * GS_PG_CUORICINI.length ) ];
				pezzo.appendChild( guizzo );

				scena.appendChild( pezzo );
				( function ( el ) { setTimeout( function () { if ( el.parentNode ) { el.remove(); } }, 1600 ); } )( pezzo );
			}
		}

		function gsPgScoppioGigante( colore, suono ) {
			var lampo = document.querySelector( '.gs-pg-lampo' );
			if ( ! lampo ) {
				lampo = document.createElement( 'div' );
				lampo.className = 'gs-pg-lampo';
				lampo.setAttribute( 'aria-hidden', 'true' );
				document.body.appendChild( lampo );
			}
			lampo.classList.remove( 'attivo' );
			void lampo.offsetWidth;
			lampo.classList.add( 'attivo' );

			gsPgSuonoEsplosione( suono );

			var scena = gsPgScenaGigante();
			var cx = window.innerWidth / 2, cy = window.innerHeight / 2;
			gsPgEsplosioneFesta( scena, cx, cy, 34, window.innerHeight * 0.5 );
		}

		function gsPgCreaGigante( dati, cb ) {
			var scena = gsPgScenaGigante();
			var colore = dati.colore || '#e74c3c';
			var velocita = parseFloat( dati.velocita ) || 6;
			var dimBase = parseInt( dati.dimensione, 10 ) || 3;
			var scalaIniziale = 0.15 + ( dimBase - 1 ) * 0.05;
			var persona = dati.persona || null;

			var pallone = document.createElement( 'div' );
			pallone.className = 'gs-pg-gigante';
			pallone.style.color = colore;
			pallone.innerHTML = gsPgCorpoHtml( persona, colore ) + '<div class="luce"></div><div class="nodo"></div><div class="nastro"></div>';
			var nastroEl = pallone.querySelector( '.nastro' );
			nastroEl.innerHTML = '<svg viewBox="0 0 24 30" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 0 C12 12, 18 14, 18 28" stroke="rgba(255,255,255,.55)" stroke-width="2" fill="none" stroke-linecap="round"/></svg>';
			gsPalloneSvolazzaFilo( nastroEl );

			scena.appendChild( pallone );
			var fermaDecorazioni = gsPgDecora( pallone, scena );

			gsPgSuonoGonfiaggio( velocita, dati.suono );

			var diagonale = Math.sqrt( window.innerWidth * window.innerWidth + window.innerHeight * window.innerHeight );
			var scalaFinale = ( diagonale / 160 ) * 1.15;

			pallone.style.transition = 'transform ' + velocita + 's cubic-bezier(.34, .08, .7, 1)';
			pallone.style.transform = 'translate(-50%, -50%) scale(' + scalaIniziale + ')';
			pallone.getBoundingClientRect(); // forza il reflow prima di animare
			requestAnimationFrame( function () {
				pallone.style.transform = 'translate(-50%, -50%) scale(' + scalaFinale + ')';
			} );

			setTimeout( function () {
				fermaDecorazioni();
				pallone.remove();
				gsPgScoppioGigante( colore, dati.suono );
				if ( cb ) { cb(); }
			}, velocita * 1000 );
		}

		function gsPgPalloncinoPiccolo( dati ) {
			var scena = gsPgScenaPalloncini();
			var pallone = document.createElement( 'div' );
			pallone.className = 'gs-pg-palloncino';
			var GS_PG_COLORI = [ '#e74c3c', '#3498db', '#f1c40f', '#9b59b6', '#1abc9c', '#e67e22', '#ec407a', '#2ecc71' ];
			var colore = GS_PG_COLORI[ Math.floor( Math.random() * GS_PG_COLORI.length ) ];
			var sinistra = 4 + Math.random() * 88;
			var durataSalita = 4 + Math.random() * 2.5;
			var durataOndeggio = 2 + Math.random() * 1.5;
			var scala = Math.max( 0.6, 1 + ( Math.random() * 2 - 1 ) * 0.25 );
			pallone.style.left = sinistra + 'vw';
			pallone.style.color = colore;
			pallone.style.animationDuration = durataSalita + 's, ' + durataOndeggio + 's';
			pallone.style.animationDelay = ( Math.random() * 0.4 ) + 's';
			pallone.style.transform = 'scale(' + scala + ')';
			pallone.style.transformOrigin = 'bottom center';

			var campione = dati.campione || [];
			var mostraFoto = dati.modo_foto === 'foto' || ( dati.modo_foto === 'mix' && Math.random() < 0.55 );
			var persona = ( mostraFoto && campione.length ) ? campione[ Math.floor( Math.random() * campione.length ) ] : null;
			pallone.innerHTML = gsPgCorpoHtml( persona, colore ) + '<div class="luce"></div><div class="nodo"></div><div class="nastro"></div>';
			var nastroEl = pallone.querySelector( '.nastro' );
			var x2 = Math.random() < 0.5 ? 20 : 4;
			nastroEl.innerHTML = '<svg viewBox="0 0 24 30" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 0 C12 12, ' + x2 + ' 14, ' + x2 + ' 28" stroke="rgba(255,255,255,.55)" stroke-width="2" fill="none" stroke-linecap="round"/></svg>';
			scena.appendChild( pallone );
			pallone.addEventListener( 'animationend', function ( e ) {
				if ( e.animationName !== 'gs-pg-pallone-sale' ) { return; }
				var rect = pallone.getBoundingClientRect();
				gsPgSuonoScoppioPiccolo( dati.suono );
				gsPgEsplosioneFesta( scena, rect.left + rect.width / 2, rect.top + rect.height / 2, 9, 55 );
				pallone.remove();
			} );
			setTimeout( function () { if ( pallone.parentNode ) { pallone.remove(); } }, ( durataSalita + 1.2 ) * 1000 );
		}
		function gsPgPioggia( dati ) {
			for ( var i = 0; i < 16; i++ ) { setTimeout( function () { gsPgPalloncinoPiccolo( dati ); }, i * 130 ); }
			var intervallo = setInterval( function () { gsPgPalloncinoPiccolo( dati ); }, 250 );
			setTimeout( function () { clearInterval( intervallo ); }, 4500 );
		}

		function gsPgMessaggio( testo ) {
			var overlay = document.querySelector( '.gs-pg-msg-overlay' );
			if ( ! overlay ) {
				overlay = document.createElement( 'div' );
				overlay.className = 'gs-pg-msg-overlay';
				overlay.innerHTML = '<div class="gs-pg-msg-card"><div class="ico">🎉</div><div class="etichetta">Messaggio dall’Accademia</div><div class="testo"></div><button type="button">Ho capito</button></div>';
				document.body.appendChild( overlay );
				overlay.querySelector( 'button' ).addEventListener( 'click', function () { overlay.classList.remove( 'mostra' ); } );
			}
			overlay.querySelector( '.testo' ).textContent = testo || 'Messaggio dall’Accademia.';
			overlay.classList.add( 'mostra' );
		}

		function gsPgAeroplanino( testo, durata ) {
			var el = document.createElement( 'div' );
			el.className = 'gs-pg-aeroplano';
			el.setAttribute( 'aria-hidden', 'true' );
			el.style.animationDuration = ( parseFloat( durata ) || 5 ) + 's';
			el.innerHTML = '<span class="gs-pg-striscione">' + gsEscapeHtml( testo || 'Messaggio dall’Accademia.' ) + '</span><span class="gs-pg-filo"></span>' + GS_AEREO_SVG;
			document.body.appendChild( el );
			el.addEventListener( 'animationend', function ( e ) { if ( e.animationName === 'gs-pg-vola' ) { el.remove(); } } );
		}

		function gsPgLancia( dati ) {
			gsPgCreaGigante( dati, function () {
				if ( dati.versione === '2' ) { gsPgMessaggio( dati.testo_messaggio ); }
				else if ( dati.versione === '3' ) { gsPgAeroplanino( dati.testo_aereo, dati.velocita_aereo ); }
				else { gsPgPioggia( dati ); }
			} );
		}

		// ---- Pannello di Controllo: raccoglie i valori del form e li manda ----
		$( document ).on( 'click', '.gs-palloncino-gigante-lancia', function () {
			if ( typeof GS_AJAX === 'undefined' ) { return; }
			var $btn = $( this );
			var $form = $btn.closest( 'form' );
			var $msg = $btn.closest( 'p' ).find( '.gs-pg-msg' );
			var personaNome = $.trim( $form.find( '[name=persona_gigante]' ).val() );
			var personaId = ( window.GS_PG_PERSONE && personaNome && window.GS_PG_PERSONE[ personaNome ] ) ? window.GS_PG_PERSONE[ personaNome ] : 0;
			var payload = {
				action: 'gs_palloncino_gigante_invia', nonce: GS_AJAX.nonce,
				velocita: $form.find( '[name=velocita]' ).val(),
				dimensione: $form.find( '[name=dimensione]' ).val(),
				colore: $form.find( '[name=colore]' ).val(),
				colore_casuale: $form.find( '[name=colore_casuale]' ).is( ':checked' ) ? 1 : '',
				suono: $form.find( '[name=suono]' ).is( ':checked' ) ? 1 : '',
				modo_foto: $form.find( 'input[name=modo_foto]:checked' ).val(),
				persona_gigante_id: personaId,
				versione: $form.find( 'input[name=versione]:checked' ).val(),
				testo_messaggio: $form.find( '[name=testo_messaggio]' ).val(),
				testo_aereo: $form.find( '[name=testo_aereo]' ).val(),
				velocita_aereo: $form.find( '[name=velocita_aereo]' ).val()
			};
			$btn.prop( 'disabled', true );
			$.post( GS_AJAX.url, payload )
				.done( function ( res ) {
					$msg.removeClass( 'ok err' ).addClass( res && res.success ? 'ok' : 'err' )
						.text( ( res && res.data && res.data.message ) ? res.data.message : 'Errore.' );
					if ( res && res.success && res.data ) {
						gsPgLancia( res.data ); // lo vede anche chi ha appena cliccato, senza aspettare il giro
						if ( window.gsPgSegnaVisto && res.data.ts ) { window.gsPgSegnaVisto( res.data.ts ); }
					}
				} )
				.fail( function () { $msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Errore di connessione.' ); } )
				.always( function () { $btn.prop( 'disabled', false ); } );
		} );

		// Sliders con valore mostrato in tempo reale
		$( document ).on( 'input', '.gs-pg-range', function () {
			var $r = $( this ), out = document.getElementById( $r.data( 'out' ) );
			if ( ! out ) { return; }
			out.textContent = parseFloat( $r.val() ).toFixed( 1 ) + ' s';
		} );
		$( document ).on( 'change', 'input[name=versione][type=radio]', function () {
			$( this ).closest( '.gs-todo-riquadro' ).find( '.gs-pg-campo-testo' ).hide();
			$( this ).closest( 'p' ).next( '.gs-pg-campo-testo' ).show();
		} );

		// ---- Controllo periodico: lo vede OGNI utente collegato, sfogline -----
		// ---- comprese, esattamente come i Palloncini qui sopra ----------------
		if ( typeof GS_AJAX !== 'undefined' && GS_AJAX.logged_in ) {
			var gsPgUltimoVisto = 0;
			try { gsPgUltimoVisto = parseInt( window.localStorage.getItem( 'gs_pg_visto' ) || '0', 10 ) || 0; } catch ( e ) {}

			function gsPgSegnaVisto( ts ) {
				gsPgUltimoVisto = ts;
				try { window.localStorage.setItem( 'gs_pg_visto', String( ts ) ); } catch ( e ) {}
			}
			window.gsPgSegnaVisto = gsPgSegnaVisto;

			var gsPgPrimoGiro = true;
			function gsPgControlla() {
				if ( document.hidden ) { return; }
				$.post( GS_AJAX.url, { action: 'gs_palloncino_gigante_ultimo', nonce: GS_AJAX.nonce } )
					.done( function ( res ) {
						if ( ! res || ! res.success || ! res.data || ! res.data.ts ) { return; }
						var ts = parseInt( res.data.ts, 10 ) || 0;
						if ( gsPgPrimoGiro ) {
							gsPgPrimoGiro = false;
							if ( ts > gsPgUltimoVisto ) { gsPgSegnaVisto( ts ); }
							return;
						}
						if ( ts > gsPgUltimoVisto ) {
							gsPgLancia( res.data );
							gsPgSegnaVisto( ts );
						}
					} );
			}
			setInterval( gsPgControlla, 15000 );
		}
	} )();

	// -------------------------------------------------------------------------
	// Pianificazione dell'Anno: linea del tempo trascinabile
	// -------------------------------------------------------------------------
	( function () {
		var MESI_LABEL = [ 'Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic' ];
		var ETICHETTE_TIPO = {
			corso: 'Calendario Corsi', gara: 'Sfida a tempo',
			percorso_stagionale: 'Percorso Stagionale', percorso_normale: 'Percorso Guidato',
			area_pro: 'Corsi Online'
		};

		function due( n ) { return ( n < 10 ? '0' : '' ) + n; }
		function parseData( s ) {
			var parti = ( s || '' ).split( '-' );
			return { y: parseInt( parti[ 0 ], 10 ) || 0, m: parseInt( parti[ 1 ], 10 ) || 0, d: parseInt( parti[ 2 ], 10 ) || 1 };
		}
		function ultimoGiorno( y, m ) { return new Date( y, m, 0 ).getDate(); }
		function aggiungiMesi( dataStr, deltaMesi ) {
			var p = parseData( dataStr );
			var totMesi = ( p.y * 12 + ( p.m - 1 ) ) + deltaMesi;
			var nuovoAnno = Math.floor( totMesi / 12 );
			var nuovoMese = ( totMesi % 12 ) + 1;
			var giorno = Math.min( p.d, ultimoGiorno( nuovoAnno, nuovoMese ) );
			return nuovoAnno + '-' + due( nuovoMese ) + '-' + due( giorno );
		}

		var eventiCorrenti = [];
		var annoCorrente = null;

		function messaggio( $origineNellaBox, ok, testo ) {
			var $box = $origineNellaBox.closest( '.gs-box-piano' );
			$box.find( '.gs-piano-msg' ).removeClass( 'ok err' ).addClass( ok ? 'ok' : 'err' ).text( testo );
		}

		function salvaData( tipo, id, dataInizio, dataFine, cb ) {
			$.post( GS_AJAX.url, { action: 'gs_piano_sposta', nonce: GS_AJAX.nonce, tipo: tipo, id: id, data_inizio: dataInizio, data_fine: dataFine } )
				.done( function ( res ) { cb( !! ( res && res.success ), res && res.data ? res.data.message : 'Errore.' ); } )
				.fail( function () { cb( false, 'Errore di connessione.' ); } );
		}

		function carica( $timeline, anno ) {
			$timeline.html( '<p class="gs-hint">Caricamento…</p>' );
			$.post( GS_AJAX.url, { action: 'gs_piano_dati', nonce: GS_AJAX.nonce, anno: anno } )
				.done( function ( res ) {
					if ( ! res || ! res.success ) { $timeline.html( '<p class="gs-hint">Errore nel caricamento.</p>' ); return; }
					eventiCorrenti = res.data.eventi || [];
					annoCorrente = anno;
					disegna( $timeline, eventiCorrenti, anno );
				} )
				.fail( function () { $timeline.html( '<p class="gs-hint">Errore di connessione.</p>' ); } );
		}

		function disegna( $timeline, eventi, anno ) {
			var programmati = eventi.filter( function ( e ) { return e.data_inizio && e.data_fine; } );
			var daProgrammare = eventi.filter( function ( e ) { return ! e.data_inizio || ! e.data_fine; } );

			var html = '<div class="gs-piano-mesi"><div class="gs-piano-mese-label"></div>';
			MESI_LABEL.forEach( function ( m ) { html += '<div class="gs-piano-mese">' + m + '</div>'; } );
			html += '</div>';

			if ( ! programmati.length ) {
				html += '<p class="gs-hint">Nessun evento con data in questo anno.</p>';
			} else {
				programmati.forEach( function ( e ) {
					var pi = parseData( e.data_inizio ), pf = parseData( e.data_fine );
					var meseIni = pi.y < anno ? 1 : ( pi.y > anno ? 13 : pi.m );
					var meseFine = pf.y > anno ? 12 : ( pf.y < anno ? 0 : pf.m );
					if ( meseFine < 1 || meseIni > 12 ) { return; }
					meseIni = Math.max( 1, meseIni ); meseFine = Math.min( 12, meseFine );
					var span = Math.max( 1, meseFine - meseIni + 1 );
					// Foto di chi ha creato l'evento: un cerchietto piccolo accanto al
					// titolo, non più sfondo dell'intero quadratino — con "cover" su una
					// barra corta e larga il ritaglio spesso mostrava solo lo sfondo della
					// foto (es. nero) invece del viso, sembrava un blocco vuoto.
					var sfondo = e.colore;
					var titoloAttr = gsEscapeHtml( ETICHETTE_TIPO[ e.tipo ] || e.tipo );
					var avatarHtml = '';
					if ( e.autore && e.autore.foto ) {
						avatarHtml = '<img class="gs-piano-avatar" src="' + gsEscapeHtml( e.autore.foto ) + '" alt="">';
					}
					if ( e.autore && e.autore.nome ) { titoloAttr += ' · ' + gsEscapeHtml( e.autore.nome ); }
					// Dentro al quadratino basta la data: il colore dice già di che tipo
					// si tratta (vedi legenda) e il titolo vero è già scritto a sinistra,
					// nell'etichetta della riga — ripeterlo anche dentro il blocco lo
					// affollava senza motivo.
					var pIni = parseData( e.data_inizio );
					var dataBreve = pIni.d && pIni.m ? due( pIni.d ) + '/' + due( pIni.m ) : '';
					html += '<div class="gs-piano-riga" data-chiave="' + e.chiave + '">';
					html += '<div class="gs-piano-riga-label">';
					if ( 'percorso_normale' === e.tipo ) { html += '<span class="gs-piano-maniglia-ordine" title="Trascina per scambiare ordine con un altro Percorso Guidato">☰</span> '; }
					html += gsEscapeHtml( e.titolo ) + '</div>';
					html += '<div class="gs-piano-riga-grid">';
					html += '<div class="gs-piano-barra" data-chiave="' + e.chiave + '" data-tipo="' + e.tipo + '" data-id="' + e.id +
						'" data-inizio="' + e.data_inizio + '" data-fine="' + e.data_fine +
						'" style="grid-column:' + meseIni + ' / span ' + span + ';background:' + sfondo + '" title="' + titoloAttr + '">' +
						'<span class="gs-piano-maniglia-sx"></span>' + avatarHtml + '<span class="gs-piano-testo">' + gsEscapeHtml( 'Corso del ' + dataBreve ) + '</span><span class="gs-piano-maniglia-dx"></span></div>';
					html += '</div></div>';
				} );
			}
			$timeline.html( html );

			var $box = $timeline.closest( '.gs-box-piano' );
			var $nonProg = $box.find( '.gs-piano-non-programmati' );
			if ( ! $nonProg.length ) { $nonProg = $( '<div class="gs-piano-non-programmati"></div>' ).appendTo( $box ); }
			if ( ! daProgrammare.length ) {
				$nonProg.html( '' );
			} else {
				var h2 = '<p class="gs-hint" style="font-weight:700">Ancora senza data — imposta le prime date per portarli sulla linea del tempo qui sopra:</p>';
				daProgrammare.forEach( function ( e ) {
					h2 += '<div class="gs-piano-non-prog-riga" data-tipo="' + e.tipo + '" data-id="' + e.id + '">';
					h2 += '<span class="gs-piano-tag" style="background:' + e.colore + '">' + gsEscapeHtml( ETICHETTE_TIPO[ e.tipo ] || e.tipo ) + '</span> ';
					h2 += '<strong>' + gsEscapeHtml( e.titolo ) + '</strong> ';
					h2 += 'dal <input type="date" class="gs-piano-non-prog-inizio"> al <input type="date" class="gs-piano-non-prog-fine"> ';
					h2 += '<button class="gs-btn gs-btn-sm gs-piano-non-prog-colloca">Colloca</button> <span class="gs-piano-non-prog-msg gs-richiesta-esito"></span>';
					h2 += '</div>';
				} );
				$nonProg.html( h2 );
			}
		}

		$( document ).on( 'change', '.gs-piano-anno', function () {
			var $box = $( this ).closest( '.gs-box-piano' );
			carica( $box.find( '.gs-piano-timeline' ), parseInt( $( this ).val(), 10 ) );
		} );

		function gsInitPianoTimeline( scope ) {
			var $scope = scope ? $( scope ) : $( document );
			$scope.find( '.gs-piano-timeline' ).each( function () {
				if ( this._gsPianoInit ) { return; }
				this._gsPianoInit = true;
				carica( $( this ), parseInt( $( this ).data( 'anno' ), 10 ) );
			} );
		}
		// Espongo l'init per i contenuti caricati via AJAX (stessa esigenza di
		// gsInitPagination più sopra: al primo caricamento della pagina questo
		// blocco parte da solo, ma un ".gs-piano-timeline" creato più tardi —
		// come nel Pannello Generale, che carica ogni sezione via AJAX — resta
		// fermo su "Caricamento…" per sempre se nessuno lo richiama di nuovo).
		window.gsInitPianoTimeline = gsInitPianoTimeline;
		$( function () { gsInitPianoTimeline(); } );

		// --- Colloca un evento non ancora programmato --------------------------
		$( document ).on( 'click', '.gs-piano-non-prog-colloca', function () {
			var $riga = $( this ).closest( '.gs-piano-non-prog-riga' );
			var $m = $riga.find( '.gs-piano-non-prog-msg' );
			var di = $riga.find( '.gs-piano-non-prog-inizio' ).val();
			var df = $riga.find( '.gs-piano-non-prog-fine' ).val();
			if ( ! di || ! df ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scegli entrambe le date.' ); return; }
			if ( df < di ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'La data di fine non può essere prima di quella di inizio.' ); return; }
			salvaData( $riga.data( 'tipo' ), $riga.data( 'id' ), di, df, function ( ok, msg ) {
				$m.removeClass( 'ok err' ).addClass( ok ? 'ok' : 'err' ).text( msg );
				if ( ok ) { setTimeout( function () { carica( $riga.closest( '.gs-box-piano' ).find( '.gs-piano-timeline' ), annoCorrente ); }, 500 ); }
			} );
		} );

		// --- Trascina una barra: sposta o ridimensiona una data -----------------
		var trasc = null;

		$( document ).on( 'mousedown', '.gs-piano-barra', function ( e ) {
			if ( $( e.target ).is( '.gs-piano-maniglia-sx, .gs-piano-maniglia-dx' ) ) { return; }
			iniziaTrascinamento( e, $( this ), 'sposta' );
		} );
		$( document ).on( 'mousedown', '.gs-piano-maniglia-sx', function ( e ) {
			e.stopPropagation();
			iniziaTrascinamento( e, $( this ).closest( '.gs-piano-barra' ), 'sx' );
		} );
		$( document ).on( 'mousedown', '.gs-piano-maniglia-dx', function ( e ) {
			e.stopPropagation();
			iniziaTrascinamento( e, $( this ).closest( '.gs-piano-barra' ), 'dx' );
		} );

		function iniziaTrascinamento( e, $barra, modo ) {
			e.preventDefault();
			var $griglia = $barra.closest( '.gs-piano-riga-grid' );
			trasc = {
				modo: modo,
				$barra: $barra,
				xIniziale: e.pageX,
				largMese: $griglia.width() / 12,
				dataInizioOrig: $barra.data( 'inizio' ),
				dataFineOrig: $barra.data( 'fine' ),
				deltaMesi: 0
			};
			$barra.addClass( 'gs-piano-trascinando' );
		}

		$( document ).on( 'mousemove', function ( e ) {
			if ( ! trasc || ! trasc.largMese ) { return; }
			var deltaMesi = Math.round( ( e.pageX - trasc.xIniziale ) / trasc.largMese );
			if ( deltaMesi === trasc.deltaMesi ) { return; }
			trasc.deltaMesi = deltaMesi;
			var pi = parseData( trasc.dataInizioOrig ), pf = parseData( trasc.dataFineOrig );
			var meseIni, meseFine;
			if ( 'sposta' === trasc.modo ) {
				meseIni = pi.m + trasc.deltaMesi; meseFine = pf.m + trasc.deltaMesi;
			} else if ( 'sx' === trasc.modo ) {
				meseIni = Math.min( pi.m + trasc.deltaMesi, pf.m ); meseFine = pf.m;
			} else {
				meseIni = pi.m; meseFine = Math.max( pf.m + trasc.deltaMesi, pi.m );
			}
			meseIni = Math.max( 1, Math.min( 12, meseIni ) );
			meseFine = Math.max( meseIni, Math.min( 12, meseFine ) );
			trasc.$barra.css( 'grid-column', meseIni + ' / span ' + Math.max( 1, meseFine - meseIni + 1 ) );
		} );

		$( document ).on( 'mouseup', function () {
			if ( ! trasc ) { return; }
			var t = trasc; trasc = null;
			t.$barra.removeClass( 'gs-piano-trascinando' );
			if ( 0 === t.deltaMesi ) {
				if ( 'sposta' === t.modo ) { apriScheda( t.$barra ); }
				return;
			}

			var nuovaInizio = t.dataInizioOrig, nuovaFine = t.dataFineOrig;
			if ( 'sposta' === t.modo ) {
				nuovaInizio = aggiungiMesi( t.dataInizioOrig, t.deltaMesi );
				nuovaFine = aggiungiMesi( t.dataFineOrig, t.deltaMesi );
			} else if ( 'sx' === t.modo ) {
				nuovaInizio = aggiungiMesi( t.dataInizioOrig, t.deltaMesi );
				if ( nuovaInizio > nuovaFine ) { nuovaInizio = nuovaFine; }
			} else {
				nuovaFine = aggiungiMesi( t.dataFineOrig, t.deltaMesi );
				if ( nuovaFine < nuovaInizio ) { nuovaFine = nuovaInizio; }
			}

			salvaData( t.$barra.data( 'tipo' ), t.$barra.data( 'id' ), nuovaInizio, nuovaFine, function ( ok, msg ) {
				messaggio( t.$barra, ok, msg );
				carica( t.$barra.closest( '.gs-box-piano' ).find( '.gs-piano-timeline' ), annoCorrente );
			} );
		} );

		// --- Scheda: clic su un blocco (senza trascinarlo) apre i dettagli -----
		var ETICHETTE_CAMPO = {
			ora_inizio: 'Ora inizio', ora_fine: 'Ora fine', posti: 'Posti disponibili',
			prezzo: 'Prezzo (€)', acconto: 'Acconto (€)', descrizione: 'Descrizione',
			livello: 'Livello'
		};

		function apriScheda( $barra ) {
			var tipo = $barra.data( 'tipo' ), id = $barra.data( 'id' );
			$.post( GS_AJAX.url, { action: 'gs_piano_scheda', nonce: GS_AJAX.nonce, tipo: tipo, id: id } )
				.done( function ( res ) {
					if ( ! res || ! res.success ) { return; }
					disegnaScheda( res.data );
				} );
		}

		function campoSchedaHtml( chiave, valore ) {
			var etichetta = ETICHETTE_CAMPO[ chiave ] || chiave;
			if ( 'descrizione' === chiave ) {
				return '<p><label>' + etichetta + '<br><textarea name="' + chiave + '" rows="3" style="width:100%">' + gsEscapeHtml( valore || '' ) + '</textarea></label></p>';
			}
			if ( 'ora_inizio' === chiave || 'ora_fine' === chiave ) {
				return '<label style="margin-right:10px">' + etichetta + '<br><input type="time" name="' + chiave + '" value="' + gsEscapeHtml( valore || '' ) + '"></label>';
			}
			if ( 'posti' === chiave ) {
				return '<label style="margin-right:10px">' + etichetta + '<br><input type="number" min="0" name="' + chiave + '" value="' + gsEscapeHtml( valore || 0 ) + '" style="width:90px"></label>';
			}
			if ( 'prezzo' === chiave || 'acconto' === chiave ) {
				return '<label style="margin-right:10px">' + etichetta + '<br><input type="number" min="0" step="0.01" name="' + chiave + '" value="' + gsEscapeHtml( valore || 0 ) + '" style="width:100px"></label>';
			}
			return '<input type="hidden" name="' + chiave + '" value="' + gsEscapeHtml( valore || '' ) + '">';
		}

		function disegnaScheda( d ) {
			var titoloTipo = ETICHETTE_TIPO[ d.tipo ] || d.tipo;
			var html = '<form class="gs-form gs-form-piano-scheda" data-tipo="' + d.tipo + '" data-id="' + d.id + '" onsubmit="return false">';
			html += '<p class="gs-hint">' + gsEscapeHtml( titoloTipo ) + ( d.reale ? ' — le date qui sono quelle vere, visibili sul sito.' : ' — qui la data è solo pianificata, per organizzarti: non cambia come/quando si sblocca davvero.' ) + '</p>';
			html += '<p><label>Titolo<br><input type="text" name="titolo" value="' + gsEscapeHtml( d.titolo ) + '" style="width:100%" required></label></p>';
			if ( 'corso' === d.tipo && GS_AJAX.admin_url ) {
				html += '<p><a class="gs-btn gs-btn-sm gs-btn-ghost" target="_blank" href="' + GS_AJAX.admin_url + 'admin.php?page=gs-generale&gs_regia_corso=' + d.id + '#gs-zona-regia-iscritti">🎯 Vedi chi è iscritto a questo corso</a></p>';
			}
			html += '<p style="display:flex;gap:12px;flex-wrap:wrap"><label>Dal<br><input type="date" name="data_inizio" value="' + gsEscapeHtml( d.data_inizio || '' ) + '"></label><label>Al<br><input type="date" name="data_fine" value="' + gsEscapeHtml( d.data_fine || '' ) + '"></label></p>';
			if ( 'percorso_normale' === d.tipo || 'percorso_stagionale' === d.tipo ) {
				html += '<p><label>Livello<br><input type="text" name="livello" value="' + gsEscapeHtml( ( d.campi && d.campi.livello ) || '' ) + '" placeholder="es. Base, Intermedio, Avanzato"></label></p>';
			}
			if ( d.campi ) {
				if ( 'undefined' !== typeof d.campi.ora_inizio || 'undefined' !== typeof d.campi.ora_fine ) {
					html += '<p>' + campoSchedaHtml( 'ora_inizio', d.campi.ora_inizio ) + campoSchedaHtml( 'ora_fine', d.campi.ora_fine ) + '</p>';
				}
				if ( 'undefined' !== typeof d.campi.posti || 'undefined' !== typeof d.campi.prezzo || 'undefined' !== typeof d.campi.acconto ) {
					html += '<p>';
					if ( 'undefined' !== typeof d.campi.posti ) { html += campoSchedaHtml( 'posti', d.campi.posti ); }
					if ( 'undefined' !== typeof d.campi.prezzo ) { html += campoSchedaHtml( 'prezzo', d.campi.prezzo ); }
					if ( 'undefined' !== typeof d.campi.acconto ) { html += campoSchedaHtml( 'acconto', d.campi.acconto ); }
					html += '</p>';
				}
				if ( 'undefined' !== typeof d.campi.descrizione ) { html += campoSchedaHtml( 'descrizione', d.campi.descrizione ); }
			}
			html += '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-piano-scheda-salva">Salva</button> <span class="gs-piano-scheda-msg gs-richiesta-esito"></span></p>';
			html += '</form>';

			var $ov = $( '<div class="gs-popup-overlay"><div class="gs-popup" role="dialog" aria-modal="true">'
				+ '<div class="gs-popup-head"><strong class="gs-popup-title"></strong>'
				+ '<button type="button" class="gs-popup-close" aria-label="Chiudi">✕ Chiudi</button></div>'
				+ '<div class="gs-popup-body"></div></div></div>' );
			$ov.find( '.gs-popup-title' ).text( d.titolo );
			$ov.find( '.gs-popup-body' ).html( html );
			$( 'body' ).append( $ov ).addClass( 'gs-popup-open' );
		}

		$( document ).on( 'click', '.gs-piano-scheda-salva', function ( e ) {
			e.preventDefault();
			var $f = $( this ).closest( '.gs-form-piano-scheda' );
			var $m = $f.find( '.gs-piano-scheda-msg' );
			if ( ! $.trim( $f.find( '[name=titolo]' ).val() ) ) { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi un titolo.' ); return; }
			$m.removeClass( 'ok err' ).text( 'Salvataggio…' );
			var payload = { action: 'gs_piano_scheda_salva', nonce: GS_AJAX.nonce, tipo: $f.data( 'tipo' ), id: $f.data( 'id' ) };
			$f.find( 'input, textarea' ).each( function () { payload[ $( this ).attr( 'name' ) ] = $( this ).val(); } );
			$.post( GS_AJAX.url, payload )
				.done( function ( res ) {
					$m.removeClass( 'ok err' ).addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
					if ( res && res.success ) {
						setTimeout( function () {
							$( '.gs-popup-overlay' ).remove(); $( 'body' ).removeClass( 'gs-popup-open' );
							carica( $( '.gs-piano-timeline' ), annoCorrente );
						}, 600 );
					}
				} )
				.fail( function () { $m.removeClass( 'ok' ).addClass( 'err' ).text( 'Errore di connessione.' ); } );
		} );

		// --- Trascina la maniglia ☰ di una riga: scambia l'ordine reale --------
		var trascOrdine = null;

		$( document ).on( 'mousedown', '.gs-piano-maniglia-ordine', function ( e ) {
			e.preventDefault();
			trascOrdine = { $riga: $( this ).closest( '.gs-piano-riga' ) };
			trascOrdine.$riga.css( 'opacity', .5 );
		} );
		$( document ).on( 'mouseover', '.gs-piano-riga', function () {
			if ( trascOrdine ) { $( this ).addClass( 'gs-piano-riga-target' ); }
		} ).on( 'mouseout', '.gs-piano-riga', function () {
			$( this ).removeClass( 'gs-piano-riga-target' );
		} );
		$( document ).on( 'mouseup', function ( e ) {
			if ( ! trascOrdine ) { return; }
			var $destinazione = $( e.target ).closest( '.gs-piano-riga' );
			var $origine = trascOrdine.$riga;
			$origine.css( 'opacity', '' );
			$( '.gs-piano-riga' ).removeClass( 'gs-piano-riga-target' );
			trascOrdine = null;
			if ( ! $destinazione.length || $destinazione.is( $origine ) ) { return; }

			var evA = eventiCorrenti.filter( function ( ev ) { return ev.chiave === $origine.data( 'chiave' ); } )[ 0 ];
			var evB = eventiCorrenti.filter( function ( ev ) { return ev.chiave === $destinazione.data( 'chiave' ); } )[ 0 ];
			if ( ! evA || ! evB || 'percorso_normale' !== evA.tipo || 'percorso_normale' !== evB.tipo ) {
				messaggio( $origine, false, 'Puoi scambiare l\'ordine solo tra due Percorsi Guidati normali.' );
				return;
			}
			$.post( GS_AJAX.url, { action: 'gs_piano_scambia', nonce: GS_AJAX.nonce, a: evA.id, b: evB.id } )
				.done( function ( res ) {
					messaggio( $origine, !! ( res && res.success ), res && res.data ? res.data.message : 'Errore.' );
					if ( res && res.success ) { carica( $origine.closest( '.gs-box-piano' ).find( '.gs-piano-timeline' ), annoCorrente ); }
				} )
				.fail( function () { messaggio( $origine, false, 'Errore di connessione.' ); } );
		} );

		// --- Corregge il link "Privacy Policy" del banner cookie (CookieYes), che
		// punta ancora a rinapoletti.it (resto della clonazione del sito) invece
		// che alla pagina privacy vera di questo sito. Il banner è iniettato in
		// modo asincrono dal loro script esterno, quindi si osserva il DOM invece
		// di correggere una sola volta al caricamento. -----------------------
		( function () {
			var GS_PRIVACY_URL = 'https://accademiadellasfoglia.it/privacy-policy/';
			function correggiLinkPrivacyBanner() {
				document.querySelectorAll( 'a[href*="rinapoletti.it/privacy-policy"]' ).forEach( function ( a ) {
					a.href = GS_PRIVACY_URL;
				} );
			}
			correggiLinkPrivacyBanner();
			new MutationObserver( correggiLinkPrivacyBanner ).observe( document.body, { childList: true, subtree: true } );
		} )();

		// --- Corregge un refuso nel contenuto della pagina "L'Accademia della
		// Sfoglia": il nome scritto è "Giampaolo Chiossi", quello vero è
		// "Gianpaolo Chiossi" — segnalato 2026-08-05. Il testo sta nel
		// contenuto della pagina (non nel plugin), quindi la correzione giusta
		// sarebbe editarlo da WordPress; nel frattempo lo si corregge qui,
		// stesso principio del link privacy qui sopra. -----------------------
		( function () {
			function correggiNomeChiossi() {
				document.querySelectorAll( 'h3, img[alt]' ).forEach( function ( el ) {
					if ( el.tagName === 'IMG' ) {
						if ( el.alt === 'Giampaolo Chiossi' ) { el.alt = 'Gianpaolo Chiossi'; }
					} else if ( el.textContent.trim() === 'Giampaolo Chiossi' ) {
						el.textContent = 'Gianpaolo Chiossi';
					}
				} );
			}
			correggiNomeChiossi();
			new MutationObserver( correggiNomeChiossi ).observe( document.body, { childList: true, subtree: true } );
		} )();

		// --- Le sezioni "a mosaico" del tema (Ultime Notizie, Video Stories...)
		// sono posizionate da una libreria JavaScript (Isotope) che calcola le
		// coordinate di ogni scheda: se il calcolo avviene nel momento sbagliato
		// le schede restano sovrapposte finché non lo rifà, e lo rifà spesso
		// (ogni scroll/resize), rallentando la pagina. Il CSS del plugin
		// sostituisce già quel posizionamento con una griglia normale, quindi il
		// calcolo di Isotope non serve più a nulla: qui lo si ferma del tutto,
		// così non spreca tempo a ricalcolare una cosa che non viene più usata.
		// Il tema inizializza Isotope quando le immagini finiscono di caricare
		// (evento "load" della finestra, non "ready" del documento): si aspetta
		// lo stesso momento, con un piccolo margine, altrimenti si rischia di
		// arrivare prima che Isotope sia partito e non trovarlo da fermare.
		// Segnalato 2026-08-04. --------------------------------------------
		$( window ).on( 'load', function () {
			setTimeout( function () {
				var $mosaici = $( '.blog.timeline.isotope, .isotope.masonry' );
				$mosaici.each( function () {
					var $el = $( this );
					try {
						if ( $el.data( 'isotope' ) ) { $el.isotope( 'destroy' ); }
					} catch ( e ) { /* libreria non disponibile: niente da fermare */ }
				} );
			}, 500 );
		} );

		// --- Il menu resta fisso in cima allo schermo (position:fixed): serve
		// uno spazio riservato in cima alla pagina, alto ESATTAMENTE quanto il
		// menu, altrimenti lo copre. Un numero fisso nel CSS smette di bastare
		// ogni volta che l'altezza del menu cambia anche di poco (con o senza
		// la barra nera di WordPress da collegati, mobile o desktop). Lo
		// spazio viene misurato dal vero e tenuto sempre aggiornato. (Il
		// tentativo di sostituire "position:fixed" con un transform mosso ad
		// ogni scroll, provato il 2026-08-04, causava uno scatto continuo —
		// ritirato subito.) -----
		function gsAllineaSpazioMenu() {
			var $header = $( 'header#header' );
			var $middle = $( '#middle' );
			if ( ! $header.length || ! $middle.length ) { return; }
			if ( $header.css( 'position' ) !== 'fixed' ) {
				$middle.css( 'padding-top', '' );
				$header.css( 'top', '' );
				document.documentElement.style.setProperty( '--gs-header-h', '0px' );
				document.documentElement.style.setProperty( '--gs-nastro-h', '0px' );
				return;
			}
			// Da collegati, WordPress mette la sua barra nera fissa in cima
			// (di solito 32px, su mobile diventa 46px o sparisce): il nostro
			// menu deve iniziare SOTTO di lei, non sovrapporsi, e lo spazio
			// riservato al contenuto deve contare ENTRAMBE le barre insieme.
			var $adminBar = $( '#wpadminbar' );
			var adminBarH = ( $adminBar.length && $adminBar.css( 'position' ) === 'fixed' ) ? $adminBar.outerHeight() : 0;
			var spazio = $header.outerHeight() + adminBarH;
			$header.css( 'top', adminBarH + 'px' );
			// La stessa misura serve anche ai due pannelli laterali (l'indice a
			// sinistra e le linguette a destra), che devono partire da sotto il
			// menu come il contenuto centrale invece che dalla cima dello
			// schermo — richiesta 2026-08-04. La passiamo al CSS con una
			// variabile, così basta misurare una volta sola qui.
			document.documentElement.style.setProperty( '--gs-header-h', spazio + 'px' );

			// Nastro fisso sotto il menu (nastro-vetrine.php), quando attivo:
			// occupa altro spazio in cima, quindi il contenuto deve scendere
			// anche di quanto è alto lui — variabile separata da --gs-header-h
			// (che resta la sola altezza del menu, usata dal nastro stesso per
			// sapere dove agganciarsi) per non creare un calcolo circolare. Il
			// padding-top del contenuto invece deve contare ENTRAMBE le barre
			// insieme (menu + nastro), altrimenti il nastro resta sopra
			// all'inizio della pagina e ne copre il titolo — bug segnalato da
			// Ennio il 18/08/2026 sulla pagina "Lentium Notizie".
			var $nastro = $( '#gs-nastro-fisso' );
			var nastroH = ( $nastro.length && $nastro.is( ':visible' ) ) ? $nastro.outerHeight() : 0;
			document.documentElement.style.setProperty( '--gs-nastro-h', nastroH + 'px' );
			$middle.css( 'padding-top', ( spazio + nastroH ) + 'px' );
		}
		gsAllineaSpazioMenu();
		$( window ).on( 'load resize', gsAllineaSpazioMenu );
		// L'altezza del menu può cambiare dopo il caricamento iniziale (es. la
		// barra nera di WordPress che compare/scompare, i font che finiscono di
		// caricare e cambiano leggermente le dimensioni del testo): un secondo
		// controllo poco dopo il caricamento intercetta questi casi senza dover
		// ricalcolare in continuazione.
		setTimeout( gsAllineaSpazioMenu, 1000 );
		// I font "aspetta fino a un secondo" sopra non bastano sempre: su una
		// connessione lenta possono finire di caricare anche dopo, e in quel
		// momento il menu/testata cambia altezza (leggera ma percepibile, "un
		// paio di cm" in cima alla pagina) senza che nessuno dei ricalcoli
		// sopra se ne accorga — la pagina resta sfalsata finché l'utente non
		// scorre col mouse (lo scroll forza il browser a ridisegnare,
		// mascherando il sintomo senza risolverlo). Segnalato da Ennio il
		// 18/08/2026. Qui si osservano DIRETTAMENTE gli elementi che
		// determinano lo spazio riservato (il menu e il nastro): qualunque
		// causa concreta di cambio altezza — font, nastro che compare, barra
		// WP che compare/scompare, larghezza dello schermo — viene
		// intercettata da sola, invece di indovinare ogni singola causa una
		// per una con altri timeout.
		if ( window.ResizeObserver ) {
			var gsRO = new ResizeObserver( function () { gsAllineaSpazioMenu(); } );
			var $headerObs = $( 'header#header' );
			if ( $headerObs.length ) { gsRO.observe( $headerObs[ 0 ] ); }
			var $nastroObs = $( '#gs-nastro-fisso' );
			if ( $nastroObs.length ) { gsRO.observe( $nastroObs[ 0 ] ); }
		}
		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( gsAllineaSpazioMenu );
		}
		// Rete di sicurezza finale: qualunque causa residua non intercettata
		// dalle righe sopra, il primo scroll (anche di un pixel) la corregge
		// da sola — esattamente il gesto che finora andava fatto a mano.
		// "one" e non "on": basta una volta, non serve ricalcolare a ogni
		// scroll successivo.
		$( window ).one( 'scroll', gsAllineaSpazioMenu );

		// --- Nastro grande (pagina "Le Sfogline"): stessa velocità in pixel
		// al secondo del nastro piccolo fisso sotto il menu, non un numero di
		// secondi indovinato — richiesto da Ennio il 17/08/2026 dopo tre giri
		// di "rallenta ancora". Le pillole del nastro grande sono più larghe
		// (il doppio), quindi a parità di secondi coprono più pixel e
		// sembrano sempre più veloci: qui si misura dal vero quanto è veloce
		// il nastro piccolo (pixel/secondo) e si applica la stessa velocità
		// a ciascuna fila del nastro grande, in base alla sua vera larghezza.
		// Velocità fissata come costante (non più misurata dal vero nastro
		// piccolo sulla stessa pagina): misurata una volta sul nastro piccolo
		// vero (4577px di larghezza, 42s di durata) e scritta qui, così
		// funziona anche nelle pagine dove il nastro piccolo viene nascosto
		// (es. "Le Sfogline", richiesto da Ennio il 17/08/2026) e non serve
		// più un elemento di riferimento presente sulla pagina.
		var GS_NASTRO_PICCOLO_PX_AL_SECONDO = 54.5;
		function gsAllineaVelocitaNastroGrande() {
			var $righeGrandi = $( '.gs-nastro-grande-pista' );
			if ( ! $righeGrandi.length ) { return; }
			var pixelAlSecondo = GS_NASTRO_PICCOLO_PX_AL_SECONDO;
			$righeGrandi.each( function () {
				var larghezza = this.scrollWidth;
				if ( ! larghezza ) { return; }
				var durata = ( larghezza / 2 ) / pixelAlSecondo;
				this.style.animationDuration = durata + 's';
			} );
		}
		gsAllineaVelocitaNastroGrande();
		$( window ).on( 'load resize', gsAllineaVelocitaNastroGrande );
		setTimeout( gsAllineaVelocitaNastroGrande, 1000 );

		// --- Scorrimento automatico su TUTTO il sito, non solo le pagine del
		// gaming (richiesto da Ennio il 2026-08-05): il menu scorre fuori
		// dallo schermo per vedere più contenuto e torna risalendo. Non c'è
		// nessuna memoria fra una pagina e l'altra (nessun sessionStorage:
		// cercato nel file, non usato qui): ogni pagina riparte da capo. Il
		// pulsante manuale sotto è un'aggiunta successiva, non un ripiego per
		// questo. ------------------
		function gsHeaderToggleInit() {
			var $header = $( 'header#header' );
			if ( ! $header.length ) { return; }

			// Il pulsante manuale "Nascondi logo" è stato tolto (richiesto da
			// Ennio il 17/08/2026): lo scroll automatico qui sotto copre da solo
			// l'obiettivo (più spazio per leggere), senza bisogno di un pulsante
			// fisso sullo schermo. Resta solo applica(), richiamata dallo scroll.
			// Lo sfarfallio (Ennio, 26/08/2026) nasce da qui: nascondere il
			// nastro e la barra in cima con display:none li toglie dal
			// flusso, il ResizeObserver rimisura, il padding del contenuto
			// si accorcia e la pagina sale DA SOLA. Quel movimento arriva a
			// valutaScroll() indistinguibile da uno scorrimento vero: viene
			// letto come "sta risalendo", il logo ritorna, il contenuto
			// riscende, e il giro si chiude su se stesso.
			// Finché la pagina si sta risistemando, non si valuta niente; a
			// cose ferme si riparte dalla posizione NUOVA, non da quella di
			// prima.
			var inTransizione = false;
			// Restituisce true/false: dice se ha davvero agito, non solo se è
			// stata chiamata. Chi tocca cose visibili in base al risultato (la
			// scritta del pulsante) deve guardare questo valore — due clic più
			// vicini di 320ms altrimenti fanno scrivere "Rimetti il logo" o
			// "Fissa il menu in alto" a caso, indipendentemente da cosa è
			// successo davvero: stesso difetto già corretto in L5 sul
			// pulsante del Diploma (trovato in revisione, 26/08/2026, prima
			// di costruire lo zip).
			function applica( nascondi ) {
				if ( inTransizione ) { return false; }
				inTransizione = true;
				$( 'body' ).toggleClass( 'gs-logo-hidden', nascondi );
				setTimeout( function () {
					gsAllineaSpazioMenu();
					ultimoScrollY = window.pageYOffset || document.documentElement.scrollTop;
					inTransizione = false;
				}, 320 );
				return true;
			}

			// --- Nascondi il logo scorrendo verso il basso, lo rimostra
			// risalendo (richiesto da Ennio il 17/08/2026). Vicino alla cima
			// della pagina resta sempre visibile, anche scorrendo giù di poco.
			var ultimoScrollY = window.pageYOffset || document.documentElement.scrollTop;
			var sogliaCima = 60;
			var scorrimentoMinimo = 8;
			// Tornare su non fa ricomparire il menu per un piccolo sali-scendi
			// (es. sbirciare l'inizio di un articolo e poi riscendere per
			// sistemarsi la pagina): serve risalire per una distanza pari a
			// TRE schermate intere — la "tolleranza di una pagina" chiesta da
			// Ennio il 26/08/2026, poi allargata due volte lo stesso giorno
			// (raddoppiata, poi +50%) — prima che torni. La distanza si
			// accumula scorrendo su e si azzera appena si torna giù anche di
			// poco, così un sali-scendi ripetuto non la fa crescere per
			// sbaglio. Non esiste un equivalente in pixel di "una pagina A4"
			// sullo schermo (dipende da monitor e zoom): si usa l'altezza
			// della finestra (window.innerHeight) come misura pratica di "una
			// schermata". Vicino alla cima della pagina invece il menu torna
			// subito, senza nessuna soglia: lì l'intenzione di risalire non è
			// ambigua.
			var distanzaSuAccumulata = 0;
			var tickPianificato = false;
			// Una scelta fatta col pulsante vale finché non si ripreme il
			// pulsante: lo scorrimento non la disfa. Serve soprattutto sulle
			// pagine con dentro un altro sito, dove la pagina contenitore non
			// scorre quasi (si resta entro i 60 pixel di sogliaCima) e quindi
			// il logo tornerebbe al primo movimento — proprio dove il pulsante
			// è l'unica possibilità. L'intenzione era già nel codice a riga
			// 8015, ma sorvegliava la classe "gs-header-hidden", che dal
			// 17/08/2026 non viene più messa da nessuno (26/08/2026).
			// Il contrassegno sta sul body, non in una variabile qui dentro:
			// il pulsante gemello nel Pannello di Controllo vive in un'altra
			// funzione, con la sua propria chiusura, e senza una bandiera
			// condivisa lo scorrimento continuava a disfare la sua scelta —
			// trovato in revisione (26/08/2026) dopo aver corretto solo
			// questo pulsante e non l'altro.
			function valutaScroll() {
				tickPianificato = false;
				if ( document.body.classList.contains( 'gs-scelta-manuale' ) ) { ultimoScrollY = window.pageYOffset || document.documentElement.scrollTop; return; }
				if ( inTransizione ) { return; }
				var y = window.pageYOffset || document.documentElement.scrollTop;
				var delta = y - ultimoScrollY;
				if ( y <= sogliaCima ) {
					distanzaSuAccumulata = 0;
					if ( $( 'body' ).hasClass( 'gs-logo-hidden' ) ) { applica( false ); }
				} else if ( delta > scorrimentoMinimo ) {
					distanzaSuAccumulata = 0;
					if ( ! $( 'body' ).hasClass( 'gs-logo-hidden' ) ) { applica( true ); }
				} else if ( delta < -scorrimentoMinimo ) {
					distanzaSuAccumulata += -delta;
					if ( distanzaSuAccumulata >= window.innerHeight * 3 && $( 'body' ).hasClass( 'gs-logo-hidden' ) ) {
						distanzaSuAccumulata = 0;
						applica( false );
					}
				}
				ultimoScrollY = y;
			}
			$( window ).on( 'scroll', function () {
				if ( tickPianificato ) { return; }
				tickPianificato = true;
				window.requestAnimationFrame( valutaScroll );
			} );

			// --- Pulsante manuale, su TUTTE le pagine del sito (rimesso su
			// richiesta di Ennio il 18/08/2026 — era stato tolto il
			// 17/08/2026 confidando solo nello scroll automatico, ma serve
			// anche altrove per far tornare su il menu con un clic diretto,
			// senza dover scorrere a mano). Nato per le pagine con un iframe
			// grande (es. "Lentium Notizie", "Rina Poletti": lì lo scroll
			// automatico non può funzionare, perché un iframe di un altro
			// dominio è "opaco" a JavaScript per una regola di sicurezza del
			// browser) e per "Le Sfogline" (ha il nastro grande dedicato,
			// senza il nastro piccolo del menu) — ora è ovunque.
			var $btn = $(
				'<button type="button" class="gs-header-toggle" aria-pressed="false" aria-label="Fissa il menu in alto">' +
					'<span class="gs-ht-ico">▲</span><span class="gs-ht-lbl">Fissa il menu in alto</span>' +
				'</button>'
			);
			// Il pulsante manuale serve SOLO dove lo scorrimento automatico non
			// può funzionare: se la pagina è occupata da un riquadro che
			// contiene un altro sito, la rotella scorre QUEL sito e il browser
			// non lo dice alla pagina che lo contiene — è una regola di
			// sicurezza, non una limitazione nostra. Confermato da Ennio il
			// 26/08/2026: succede su "Rina Poletti" e "Lentium Notizie".
			// Si guarda l'ALTEZZA del riquadro e non il nome della pagina, così
			// una pagina nuova con un altro sito dentro funziona da sola: e si
			// guarda l'altezza e non la semplice presenza, perché la mappa di un
			// artigiano e un video di YouTube sono riquadri anche loro, ma
			// piccoli, e lì intorno c'è tutta la pagina da scorrere.
			function gsServeIlPulsante() {
				var serve = false;
				$( 'iframe' ).each( function () {
					if ( this.getBoundingClientRect().height > window.innerHeight * 0.6 ) {
						serve = true;
						return false;
					}
				} );
				return serve;
			}
			function gsMettiIlPulsanteSeServe() {
				if ( $btn.parent().length ) { return; }        // già messo
				if ( gsServeIlPulsante() ) { $( 'body' ).append( $btn ); }
			}
			gsMettiIlPulsanteSeServe();
			// Un riquadro che contiene un altro sito ha altezza 0 finché non ha
			// finito di caricare: la prima misura da sola non basta.
			$( window ).on( 'load', gsMettiIlPulsanteSeServe );
			setTimeout( gsMettiIlPulsanteSeServe, 1500 );
			$btn.on( 'click', function () {
				document.body.classList.add( 'gs-scelta-manuale' );
				var nuovo = ! $( 'body' ).hasClass( 'gs-logo-hidden' );
				if ( ! applica( nuovo ) ) { return; }   // in transizione: clic ignorato, scritta non toccata
				$btn.attr( 'aria-pressed', nuovo ? 'true' : 'false' )
					.attr( 'aria-label', nuovo ? 'Rimetti il logo' : 'Fissa il menu in alto' )
					.find( '.gs-ht-ico' ).text( nuovo ? '▼' : '▲' )
					.end().find( '.gs-ht-lbl' ).text( nuovo ? 'Rimetti il logo' : 'Fissa il menu in alto' );
			} );
		}
		gsHeaderToggleInit();

		// --- I collegamenti social in fondo alle pagine. Il tema ne stampa
		// due (Facebook e Instagram) e li lascia puntati a "#", cioè a nulla:
		// non erano mai stati configurati nelle opzioni del tema, quindi
		// cliccarli non portava da nessuna parte. Qui il blocco viene
		// ricostruito con i quattro indirizzi veri dati dal committente il
		// 2026-08-04, centrato e con le icone disegnate a mano (SVG) invece
		// del carattere-icona del tema, così l'aspetto non dipende da lui.
		// NOTA: se un domani questi indirizzi si impostano davvero nelle
		// opzioni del tema, questa parte continuerà comunque a sovrascriverli
		// — vanno cambiati qui. -----
		var gsSocialVoci = [
			{ url: 'https://www.facebook.com/rina.poletti1',                titolo: 'Facebook — Rina Poletti',            ico: 'fb' },
			{ url: 'https://www.instagram.com/rinapoletti/',                titolo: 'Instagram — Rina Poletti',           ico: 'ig' },
			{ url: 'https://www.facebook.com/groups/1408976190838501/',     titolo: 'Gruppo Facebook — Casa Poletti',     ico: 'gruppo' },
			{ url: 'https://www.facebook.com/groups/1145798346745403/',     titolo: 'Gruppo Facebook',                    ico: 'gruppo' }
		];
		var gsSocialIcone = {
			fb: '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M15.12 5.32H17V2.14A26.11 26.11 0 0 0 14.26 2C11.54 2 9.68 3.66 9.68 6.7v2.62H6.61v3.56h3.07V22h3.68v-9.12h3.06l.46-3.56h-3.52V7.05c0-1.05.28-1.73 1.76-1.73Z"/></svg>',
			ig: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true" focusable="false"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="3.8"/><circle cx="17.3" cy="6.7" r="1.1" fill="currentColor" stroke="none"/></svg>',
			gruppo: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="9" cy="8" r="3.1"/><circle cx="16.6" cy="9.4" r="2.4"/><path d="M3.2 19.2c0-3.2 2.6-5.2 5.8-5.2s5.8 2 5.8 5.2"/><path d="M16.4 14.2c2.5.2 4.4 1.9 4.4 4.6"/></svg>'
		};
		function gsRifaiSocial() {
			// Il contenitore del tema NON c'è sempre: scaricando due volte la
			// stessa pagina, in un caso il blocco .social_wrap era presente e
			// nell'altro no (verificato il 2026-08-04). Quindi non basta
			// riempirlo: se manca va creato, altrimenti su quelle pagine i
			// collegamenti sparirebbero del tutto.
			var $inner = $( '.footer .social_wrap_inner' );
			if ( ! $inner.length ) {
				var $wrap = $( '.footer .social_wrap' );
				if ( ! $wrap.length ) {
					var $dove = $( '.footer .footer_inner_cont' ).first();
					if ( ! $dove.length ) { $dove = $( '.footer .footer_inner' ).first(); }
					if ( ! $dove.length ) { return; }
					$wrap = $( '<div class="social_wrap gs-social-creato"></div>' );
					// Prima della riga del copyright, se c'è; in fondo altrimenti.
					var $copy = $dove.find( '.footer_copyright' ).first();
					if ( $copy.length ) { $wrap.insertBefore( $copy ); } else { $wrap.appendTo( $dove ); }
				}
				$inner = $( '<div class="social_wrap_inner"></div>' ).appendTo( $wrap );
			}
			if ( $inner.hasClass( 'gs-social-fatto' ) ) { return; }
			var html = '<ul class="gs-social">';
			for ( var i = 0; i < gsSocialVoci.length; i++ ) {
				var v = gsSocialVoci[ i ];
				html += '<li><a href="' + v.url + '" target="_blank" rel="noopener noreferrer"'
					+ ' title="' + v.titolo + '" aria-label="' + v.titolo + '">'
					+ gsSocialIcone[ v.ico ] + '</a></li>';
			}
			html += '</ul>';
			$inner.addClass( 'gs-social-fatto' ).html( html );
		}
		gsRifaiSocial();
		$( window ).on( 'load', gsRifaiSocial );
	} )();

	// -------------------------------------------------------------------------
	// Classifica animata del mese, "Matterello che stende" (Ennio, 19-20/08/2026)
	// -------------------------------------------------------------------------
	( function () {
		var $scena = $( '#gsCmScena' );
		if ( ! $scena.length ) { return; }

		var $sfoglia    = $( '#gsCmSfoglia' );
		var $matterello = $( '#gsCmMatterello' );
		var $btn        = $( '#gsCmStendi' );
		var $schede     = $( '#gsCmSchede' );
		var DURATA      = 2600;
		// Colonne per riga, deve combaciare col CSS (repeat(N, 1fr) di
		// .gs-cm-schede): usata solo per calcolare lo sfalsamento dello
		// svelamento, non per il layout vero e proprio.
		function colonnePerRiga() {
			var w = $scena.width();
			if ( w <= 700 ) { return 2; }
			if ( w <= 980 ) { return 3; }
			return 5;
		}

		function stendi() {
			var $carte = $schede.children( '.gs-cm-scheda' );
			var cols   = colonnePerRiga();
			$sfoglia.css( { transition: 'none', clipPath: 'inset(0 0 0 0)' } );
			$matterello.css( { transition: 'none', left: '-84px' } );
			$carte.removeClass( 'gs-cm-svelata' ).css( 'transition', 'none' );
			// forza il reflow così le transizioni ripartono pulite
			void $scena[ 0 ].offsetWidth;
			$sfoglia.css( 'transition', 'clip-path ' + DURATA + 'ms linear' );
			$matterello.css( 'transition', 'left ' + DURATA + 'ms linear' );
			$carte.each( function ( i ) {
				var colonna = i % cols;
				var ritardo = Math.round( colonna * DURATA / cols );
				$( this ).css( 'transition', 'transform .5s cubic-bezier(.2,.85,.3,1.2) ' + ritardo + 'ms, opacity .4s ease ' + ritardo + 'ms' );
			} );
			requestAnimationFrame( function () {
				$matterello.css( 'left', 'calc(100% + 40px)' );
				$sfoglia.css( 'clip-path', 'inset(0 0 0 100%)' );
				$carte.addClass( 'gs-cm-svelata' );
			} );
		}

		$btn.on( 'click', function () {
			$btn.prop( 'disabled', true );
			stendi();
			setTimeout( function () {
				$btn.prop( 'disabled', false );
				$btn.text( '🥖 Ristendi la sfoglia!' );
			}, DURATA + 600 );
		} );

		// Gioca da sola al primo caricamento — un piccolo ritardo per lasciare
		// respirare l'ingresso nella pagina invece di scattare di colpo.
		setTimeout( function () { $btn.trigger( 'click' ); }, 500 );

		// "In tempo reale" (Ennio): il CONTENUTO delle schede si aggiorna da
		// solo ogni 20s, senza ripetere l'animazione del matterello — quella
		// resta un ingresso, una volta sola per sessione (vedi commento nel
		// PHP, classifica-mensile.php). Se cambia l'ordine o il numero di
		// sfogline sopra soglia, le schede vengono semplicemente riscritte
		// già "svelate", senza nuovo effetto.
		function aggiorna() {
			if ( document.hidden ) { return; }
			$.post( GS_AJAX.url, { action: 'gs_classifica_mensile_dati', nonce: GS_AJAX.nonce } )
				.done( function ( res ) {
					if ( ! res || ! res.success || ! res.data || ! res.data.righe ) { return; }
					res.data.righe.forEach( function ( r, i ) {
						var $c = $schede.children( '.gs-cm-scheda' ).eq( i );
						if ( ! $c.length ) { return; }
						$c.attr( 'href', r.link ).attr( 'data-uid', r.id );
						$c.find( '.gs-cm-avatar' ).text( r.iniziali );
						$c.find( '.gs-cm-nome' ).text( r.nome );
						$c.find( '.gs-cm-punti' ).text( r.punti + ' pt' );
					} );
				} );
		}
		setInterval( aggiorna, 20000 );
	} )();

	// -------------------------------------------------------------------
	// Il Reset del gioco — anteprima, poi cancellazione con conferma scritta
	// a mano. L'unica operazione del plugin che non si annulla: il pulsante
	// di cancellazione parte disabilitato e si sblocca solo dopo aver visto
	// l'anteprima nella stessa pagina (ISTRUZIONE-IL-RESET.md, 26/08/2026).
	// -------------------------------------------------------------------
	function gsEsc( s ) {
		return $( '<div>' ).text( null == s ? '' : String( s ) ).html();
	}

	function gsRenderResetAnteprima( d ) {
		var out = '';
		if ( d.non_classificati && d.non_classificati.length ) {
			out += '<p style="color:#b03a2e"><strong>Da guardare prima di procedere:</strong> ';
			out += 'questi tipi di contenuto verrebbero cancellati, ma nessuno li ha mai classificati — ';
			out += 'sono arrivati nel plugin dopo l\'ultima volta che si è guardato questo elenco: ';
			out += d.non_classificati.map( gsEsc ).join( ', ' ) + '</p>';
		}
		out += '<h5>Righe di dati che verrebbero cancellate</h5><ul>';
		( d.meta || [] ).forEach( function ( r ) {
			out += '<li>' + gsEsc( r.meta_key ) + ': ' + gsEsc( r.n ) + '</li>';
		} );
		out += '</ul>';
		out += '<h5>Contenuti che verrebbero cancellati</h5><ul>';
		Object.keys( d.contenuti || {} ).forEach( function ( t ) {
			out += '<li>' + gsEsc( t ) + ': ' + gsEsc( d.contenuti[ t ] ) + '</li>';
		} );
		out += '</ul>';
		out += '<h5>Contenuti che restano (il catalogo)</h5><ul>';
		Object.keys( d.tenuti || {} ).forEach( function ( t ) {
			out += '<li>' + gsEsc( t ) + ': ' + gsEsc( d.tenuti[ t ] ) + '</li>';
		} );
		out += '</ul>';
		if ( d.sfide_aperte && d.sfide_aperte.length ) {
			out += '<p style="color:#b03a2e"><strong>Attenzione:</strong> sfida/e ancora aperta/e con sfoglie già inviate — controllale a mano prima di procedere, il Reset non decide da solo: ';
			out += d.sfide_aperte.map( function ( s ) { return gsEsc( s.titolo ); } ).join( ', ' ) + '</p>';
		}
		out += '<h5>Per ogni sfoglina, cosa resta dopo il Reset</h5>';
		out += '<table class="gs-table"><thead><tr><th>Sfoglina</th><th>Abbonamento</th><th>Scadenza</th><th>Token</th><th>Vetrina</th></tr></thead><tbody>';
		( d.sfogline || [] ).forEach( function ( s ) {
			out += '<tr><td>' + gsEsc( s.nome ) + '</td><td>' + gsEsc( s.abbonamento ) + '</td><td>' + gsEsc( s.scadenza ) + '</td><td>' + gsEsc( s.token ) + '</td><td>' + gsEsc( s.vetrina ) + '</td></tr>';
		} );
		out += '</tbody></table>';
		if ( d.cestino && d.cestino.length ) {
			out += '<h5>Sfogline nel Cestino — anche a loro resta tutto</h5>';
			out += '<p class="gs-hint">I loro dati stanno dentro l\'archivio del Cestino, non fra i meta: si vedono solo qui.</p>';
			out += '<table class="gs-table"><thead><tr><th>Sfoglina</th><th>Stato</th><th>Abbonamento</th><th>Scadenza</th><th>Token</th><th>Vetrina</th></tr></thead><tbody>';
			d.cestino.forEach( function ( s ) {
				out += '<tr><td>' + gsEsc( s.nome ) + '</td><td>' + gsEsc( s.stato ) + '</td><td>' + gsEsc( s.abbonamento ) + '</td><td>' + gsEsc( s.scadenza ) + '</td><td>' + gsEsc( s.token ) + '</td><td>' + gsEsc( s.vetrina ) + '</td></tr>';
			} );
			out += '</tbody></table>';
		}
		if ( d.piatti_da_liberare ) {
			var unoSolo = ( 1 === d.piatti_da_liberare );
			out += '<p>' + gsEsc( d.piatti_da_liberare )
				+ ( unoSolo ? ' piatto in via d\'estinzione tornerà libero: il piatto resta, la sua custode no, '
				            : ' piatti in via d\'estinzione torneranno liberi: i piatti restano, le custodi di prima no, ' )
				+ 'e chiunque potrà adottarl' + ( unoSolo ? 'o' : 'i' ) + ' di nuovo.</p>';
		}
		return out;
	}

	$( document ).on( 'click', '.gs-reset-anteprima-btn', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '.gs-box-reset' );
		var $msg = $box.find( '.gs-reset-anteprima-msg' );
		var $out = $box.find( '.gs-reset-risultato' );
		$msg.removeClass( 'ok err' ).text( 'Calcolo…' );
		$.post( GS_AJAX.url, { action: 'gs_reset_anteprima', nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				if ( res && res.success ) {
					$msg.addClass( 'ok' ).text( 'Fatto.' );
					$out.show().html( gsRenderResetAnteprima( res.data ) );
					$box.find( '.gs-reset-esegui-btn' ).prop( 'disabled', false );
				} else {
					$msg.addClass( 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				}
			} )
			.fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-reset-esegui-btn', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '.gs-box-reset' );
		var $btn = $( this );
		var conferma = $box.find( '.gs-reset-conferma-input' ).val();
		var $msg = $box.find( '.gs-reset-esegui-msg' );
		if ( 'RESET' !== conferma ) {
			$msg.removeClass( 'ok' ).addClass( 'err' ).text( 'Scrivi esattamente RESET, in maiuscolo, per confermare.' );
			return;
		}
		if ( ! window.confirm( 'Questa operazione cancella i dati di gioco e NON si può annullare. Hai già fatto il backup del database? Premi OK solo se sì.' ) ) { return; }
		$msg.removeClass( 'ok err' ).text( 'Cancellazione in corso…' );
		$btn.prop( 'disabled', true );
		$.post( GS_AJAX.url, { action: 'gs_reset_esegui', nonce: GS_AJAX.nonce, conferma: conferma } )
			.done( function ( res ) {
				$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				if ( res && res.success ) {
					setTimeout( function () { window.location.reload(); }, 2500 );
				} else {
					$btn.prop( 'disabled', false ); // non è riuscito, si può riprovare
				}
			} )
			.fail( function () {
				$msg.addClass( 'err' ).text( 'Errore di connessione.' );
				$btn.prop( 'disabled', false );
			} );
	} );

	// -------------------------------------------------------------------
	// Username fuori dalla rete pubblica — anteprima, poi applica.
	// -------------------------------------------------------------------
	function gsRenderNicenameAnteprima( righe ) {
		var out = '<table class="gs-table"><thead><tr><th>Sfoglina</th><th>Indirizzo attuale</th><th>Indirizzo nuovo</th></tr></thead><tbody>';
		righe.forEach( function ( r ) {
			out += '<tr' + ( r.cambia ? '' : ' style="opacity:.55"' ) + '><td>' + gsEsc( r.nome ) + '</td><td>' + gsEsc( r.attuale ) + '</td><td>' + gsEsc( r.proposto ) + ( r.cambia ? '' : ' (già a posto)' ) + '</td></tr>';
		} );
		out += '</tbody></table>';
		return out;
	}

	$( document ).on( 'click', '.gs-nicename-anteprima-btn', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '.gs-box-reset' );
		var $msg = $box.find( '.gs-nicename-msg' );
		var $out = $box.find( '.gs-nicename-risultato' );
		$msg.removeClass( 'ok err' ).text( 'Calcolo…' );
		$.post( GS_AJAX.url, { action: 'gs_nicename_anteprima', nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				if ( res && res.success ) {
					$msg.addClass( 'ok' ).text( 'Fatto.' );
					$out.show().html( gsRenderNicenameAnteprima( res.data.righe ) );
					$box.find( '.gs-nicename-applica-btn' ).prop( 'disabled', false );
				} else {
					$msg.addClass( 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
				}
			} )
			.fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );

	$( document ).on( 'click', '.gs-nicename-applica-btn', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '.gs-box-reset' );
		var $btn = $( this );
		var $msg = $box.find( '.gs-nicename-msg' );
		$msg.removeClass( 'ok err' ).text( 'Applico…' );
		$btn.prop( 'disabled', true );
		$.post( GS_AJAX.url, { action: 'gs_nicename_applica', nonce: GS_AJAX.nonce } )
			.done( function ( res ) {
				$msg.addClass( res && res.success ? 'ok' : 'err' ).text( res && res.data ? res.data.message : 'Errore.' );
			} )
			.fail( function () { $msg.addClass( 'err' ).text( 'Errore di connessione.' ); } );
	} );
} )( jQuery );
