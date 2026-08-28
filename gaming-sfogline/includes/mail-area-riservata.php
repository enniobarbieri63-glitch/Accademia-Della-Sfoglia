<?php
/**
 * mail-area-riservata.php — Le mail HTML transazionali dell'Accademia
 * ("Accesso e Vetrina", "Come funziona il Percorso"). Ognuna è un modello
 * con segnaposto ({{NOME}}, {{URL_LOGO}}, {{URL_SFOGLINE}}, {{URL_SCRIVICI}}),
 * modificabile dal pannello "Iscrizioni delle sfogline" (Ennio, 20/08/2026:
 * "voglio la possibilità di vedere le varie mail... e di poterle
 * modificare") — il testo che Ennio salva nel pannello sovrascrive il
 * modello di partenza; un pulsante "Ripristina" torna a quello originale.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** URL del logo tondo per l'intestazione delle mail, impostabile dal pannello — vuoto di default (niente immagine rotta se non ancora caricato). */
function gs_mail_area_riservata_logo_url() {
	$s = gs_settings();
	return ! empty( $s['mail_area_riservata_logo'] ) ? $s['mail_area_riservata_logo'] : '';
}

/**
 * Registro delle mail modificabili. 'corpo' è il modello di partenza (HTML
 * a tabelle, stili inline, segnaposto letterali {{...}}) — quello che
 * torna se Ennio preme "Ripristina il testo originale". 'trigger' è solo
 * una nota informativa mostrata nel pannello.
 */
function gs_mail_template_registro() {
	$testata = function ( $sottotitolo ) {
		return '<tr>
          <td style="background:#0B3D2E;padding:34px 40px 30px;text-align:center;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr><td style="border:1px solid #B08D3F;padding:26px 24px;text-align:center;">
                {{BLOCCO_LOGO_INTESTAZIONE}}
                <div style="font-family:Georgia,\'Times New Roman\',serif;font-size:17px;letter-spacing:0.14em;color:#F2EDE1;line-height:1.5;">ACCADEMIA<br>DELLA SFOGLIA</div>
                <div style="height:1px;background:#B08D3F;width:56px;margin:16px auto;"></div>
                <div style="font-size:9px;letter-spacing:0.3em;color:#9FB6A6;">' . $sottotitolo . '</div>
              </td></tr>
            </table>
          </td>
        </tr>';
	};
	$piede = '<tr>
          <td style="padding:30px 40px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
              <tr><td style="background:#0B3D2E;padding:13px 30px;">
                <a href="{{URL_SCRIVICI}}" style="font-size:11px;letter-spacing:0.22em;color:#F2EDE1;text-decoration:none;">SCRIVICI PER QUALSIASI DUBBIO</a>
              </td></tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="padding:14px 40px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
              <td style="padding:0 8px 8px 0;"><a href="{{URL_FAQ_INFORMAZIONI}}" style="display:inline-block;font-size:10.5px;letter-spacing:0.08em;color:#0B3D2E;text-decoration:none;border:1px solid #0B3D2E;border-radius:20px;padding:8px 16px;">Informazioni sui corsi</a></td>
              <td style="padding:0 8px 8px 0;"><a href="{{URL_FAQ_GIOCO}}" style="display:inline-block;font-size:10.5px;letter-spacing:0.08em;color:#0B3D2E;text-decoration:none;border:1px solid #0B3D2E;border-radius:20px;padding:8px 16px;">FAQ sul percorso</a></td>
              <td style="padding:0 0 8px 0;"><a href="{{URL_FAQ_PORTALE}}" style="display:inline-block;font-size:10.5px;letter-spacing:0.08em;color:#0B3D2E;text-decoration:none;border:1px solid #0B3D2E;border-radius:20px;padding:8px 16px;">FAQ del portale</a></td>
            </tr></table>
          </td>
        </tr>
        <tr>
          <td style="padding:20px 40px 36px;">
            <div style="height:1px;background:#C6BCA0;margin-bottom:20px;"></div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td valign="middle">
                  <div style="font-size:11px;letter-spacing:0.2em;color:#12241B;padding-bottom:8px;">ACCADEMIA DELLA SFOGLIA</div>
                  <div style="font-size:12px;line-height:1.7;color:#5B5545;">
                    <a href="https://accademiadellasfoglia.it" style="color:#0B3D2E;text-decoration:none;">accademiadellasfoglia.it</a> · <a href="{{URL_SFOGLINE}}" style="color:#0B3D2E;text-decoration:none;">Le Sfogline</a> · <a href="{{URL_SCRIVICI}}" style="color:#0B3D2E;text-decoration:none;">Scrivici</a>
                  </div>
                </td>
                <td valign="middle" width="70" align="right">
                  {{BLOCCO_LOGO_PIEDE}}
                </td>
              </tr>
            </table>
          </td>
        </tr>';
	$apertura = '<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Accademia della Sfoglia</title>
</head>
<body style="margin:0;padding:0;background:#EDEAE0;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#EDEAE0;padding:28px 12px;font-family:Georgia,\'Times New Roman\',serif;">
  <tr>
    <td align="center">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="width:600px;max-width:600px;background:#F5F1E6;">
';
	$chiusura = '
      </table>
      <div style="font-size:9px;letter-spacing:0.24em;color:#8A8474;padding-top:18px;font-family:Georgia,\'Times New Roman\',serif;">I CUSTODI DELLA SFOGLIA FATTA A MANO</div>
    </td>
  </tr>
</table>
</body>
</html>';

	return array(
		'benvenuto' => array(
			'label'    => 'Benvenuto (iscrizione approvata)',
			'oggetto'  => 'Benvenuta nell\'Accademia della Sfoglia 🎉',
			'trigger'  => 'Parte da sola quando la segreteria approva l\'iscrizione. È la prima mail che riceve, il giorno stesso: dice che è gratis, che ha trenta giorni di prova, e le due date esatte.',
			'corpo'    => $apertura . $testata( 'BENVENUTA' ) . '
        <tr>
          <td style="padding:34px 40px 8px;">
            <div style="font-size:20px;line-height:1.35;color:#12241B;letter-spacing:0.02em;">Benvenuta nell\'Accademia<br>della Sfoglia</div>
            <div style="height:2px;background:#0B3D2E;margin:16px 0 4px;"></div>
            <div style="height:1px;background:#C6BCA0;"></div>
          </td>
        </tr>
        <tr>
          <td style="padding:22px 40px 0;font-size:14px;line-height:1.75;color:#3B372C;">
            Ciao {{NOME}},<br><br>
            iscriversi è gratuito, e lo resta: siamo una communitas che difende l\'arte e la professione della sfoglia a mano, non un club a pagamento.
          </td>
        </tr>
        <tr>
          <td style="padding:26px 40px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">I</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">TRENTA GIORNI DI REGALO</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Da oggi hai trenta giorni di accesso completo alla parte riservata del sito: il percorso, le sfide, la classifica, il ricettario, il Tavolo di Lavoro, e la tua pagina «La Mia Sfoglia» dove tenere le tue cose.</div>
                  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:12px;background:#EFEADA;">
                    <tr><td style="padding:12px 14px;border-left:2px solid #B08D3F;font-size:13.5px;line-height:1.7;color:#3B372C;">
                      I tuoi trenta giorni vanno dal <strong style="font-weight:700;">{{DATA_INIZIO}}</strong> al <strong style="font-weight:700;">{{DATA_FINE}}</strong>.
                    </td></tr>
                  </table>
                </td>
              </tr>
            </table>
            <div style="height:1px;background:#DFD7C2;margin:22px 0;"></div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">II</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">NON PERDI NIENTE</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Il {{DATA_FINE}} l\'accesso alla parte riservata si chiude. Punti, badge, ricette, foto e tutto quello che avrai scritto restano salvati esattamente dove li hai lasciati, congelati e pronti. Il resto del sito — la Galleria, il Registro, la Classifica, le Letture, la tua Vetrina — resta aperto come per tutti.</div>
                </td>
              </tr>
            </table>
            <div style="height:1px;background:#DFD7C2;margin:22px 0;"></div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">III</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">PER RIAPRIRE, QUANDO VUOI</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Basta un contributo di 29 € a sostegno dell\'Accademia. Te lo ricorderemo per tempo, con le istruzioni: adesso non devi fare nulla.</div>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="padding:30px 40px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
              <tr><td style="background:#0B3D2E;padding:13px 30px;">
                <a href="{{URL_MIA_SFOGLIA}}" style="font-size:11px;letter-spacing:0.22em;color:#F2EDE1;text-decoration:none;">ACCEDI ALLA TUA SFOGLIA</a>
              </td></tr>
            </table>
          </td>
        </tr>' . $piede . $chiusura,
		),
		'conferma_email' => array(
			'label'    => 'Conferma dell\'indirizzo email',
			'oggetto'  => 'Conferma la tua email — Accademia della Sfoglia',
			'trigger'  => 'Parte da sola subito dopo l\'iscrizione. Il link serve solo a verificare l\'indirizzo email: l\'attivazione dell\'account dipende sempre e solo dal controllo manuale della segreteria, che approva l\'iscrizione a parte.',
			'corpo'    => $apertura . $testata( 'CONFERMA LA TUA EMAIL' ) . '
        <tr>
          <td style="padding:34px 40px 8px;">
            <div style="font-size:20px;line-height:1.35;color:#12241B;letter-spacing:0.02em;">Grazie per esserti iscritta</div>
            <div style="height:2px;background:#0B3D2E;margin:16px 0 4px;"></div>
            <div style="height:1px;background:#C6BCA0;"></div>
          </td>
        </tr>
        <tr>
          <td style="padding:22px 40px 0;font-size:14px;line-height:1.75;color:#3B372C;">
            Ciao {{NOME}},<br><br>
            clicca qui sotto: serve solo a confermare che questa è davvero la tua email, così siamo sicuri di poterti scrivere.<br><br>
            Nel frattempo lo staff dell\'Accademia controlla la tua iscrizione per approvarla: riceverai un\'altra email non appena sarà attiva.
          </td>
        </tr>
        <tr>
          <td style="padding:30px 40px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
              <tr><td style="background:#0B3D2E;padding:13px 30px;">
                <a href="{{LINK_VERIFICA_EMAIL}}" style="font-size:11px;letter-spacing:0.22em;color:#F2EDE1;text-decoration:none;">CONFERMA LA TUA EMAIL</a>
              </td></tr>
            </table>
          </td>
        </tr>' . $piede . $chiusura,
		),
		'richiesta_non_accolta' => array(
			'label'    => 'Richiesta non accolta',
			'oggetto'  => 'Esito della tua richiesta di iscrizione',
			'trigger'  => 'Parte quando la segreteria rifiuta una richiesta di iscrizione, dal pannello «Richieste in attesa».',
			'corpo'    => $apertura . $testata( 'LA TUA RICHIESTA' ) . '
        <tr>
          <td style="padding:34px 40px 8px;">
            <div style="font-size:20px;line-height:1.35;color:#12241B;letter-spacing:0.02em;">Esito della tua richiesta</div>
            <div style="height:2px;background:#0B3D2E;margin:16px 0 4px;"></div>
            <div style="height:1px;background:#C6BCA0;"></div>
          </td>
        </tr>
        <tr>
          <td style="padding:22px 40px 0;font-size:14px;line-height:1.75;color:#3B372C;">
            Ciao {{NOME}},<br><br>
            purtroppo non è stato possibile approvare la tua iscrizione in questo momento. Per informazioni contatta la segreteria dell\'Accademia.<br><br>
            Grazie.
          </td>
        </tr>' . $piede . $chiusura,
		),
		'scadenza_preavviso' => array(
			'label'    => 'Fra una settimana finisce la prova',
			'oggetto'  => 'Fra una settimana finisce il tuo mese di prova — Accademia della Sfoglia',
			'trigger'  => 'Parte da sola 7 giorni prima della scadenza (gs_abbonamento_controlla_scadenze(), sul cron giornaliero).',
			'corpo'    => $apertura . $testata( 'FRA UNA SETTIMANA' ) . '
        <tr>
          <td style="padding:34px 40px 8px;">
            <div style="font-size:20px;line-height:1.35;color:#12241B;letter-spacing:0.02em;">Fra una settimana finisce<br>il tuo mese di prova</div>
            <div style="height:2px;background:#0B3D2E;margin:16px 0 4px;"></div>
            <div style="height:1px;background:#C6BCA0;"></div>
          </td>
        </tr>
        <tr>
          <td style="padding:22px 40px 0;font-size:14px;line-height:1.75;color:#3B372C;">
            Ciao {{NOME}},<br><br>
            i tuoi trenta giorni di prova (dal {{DATA_INIZIO}}) finiscono il <strong style="font-weight:700;">{{DATA_FINE}}</strong> — fra una settimana.
          </td>
        </tr>
        <tr>
          <td style="padding:26px 40px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">I</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">COSA SUCCEDE IL {{DATA_FINE}}</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">L\'accesso alla parte riservata si chiude. Non perdi niente: punti, badge, ricette, foto e tutto quello che avrai scritto restano salvati esattamente dove li hai lasciati, congelati e pronti. Il resto del sito — la Galleria, il Registro, la Classifica, le Letture, la tua Vetrina — resta aperto come per tutti.</div>
                </td>
              </tr>
            </table>
            <div style="height:1px;background:#DFD7C2;margin:22px 0;"></div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">II</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">COME CONTINUARE SENZA INTERRUZIONI</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Basta un contributo di <strong style="font-weight:700;">29 €</strong> a sostegno dell\'Accademia, con bonifico (causale «CONTRIBUTO A SOSTEGNO DELL\'ASSOCIAZIONE ACCADEMIA DELLA SFOGLIA» — per IBAN e intestatario scrivi alla segreteria). Appena arriva, il gestore sposta la tua data e riparti da dove eri.</div>
                </td>
              </tr>
            </table>
          </td>
        </tr>' . $piede . $chiusura,
		),
		'scadenza_ultimo' => array(
			'label'    => 'Ultimo giorno',
			'oggetto'  => 'Ultimo giorno del tuo mese di prova — Accademia della Sfoglia',
			'trigger'  => 'Parte da sola il giorno stesso della scadenza, o il giorno prima (gs_abbonamento_controlla_scadenze(), sul cron giornaliero).',
			'corpo'    => $apertura . $testata( 'ULTIMO GIORNO' ) . '
        <tr>
          <td style="padding:34px 40px 8px;">
            <div style="font-size:20px;line-height:1.35;color:#12241B;letter-spacing:0.02em;">Oggi è l\'ultimo giorno<br>del tuo mese di prova</div>
            <div style="height:2px;background:#0B3D2E;margin:16px 0 4px;"></div>
            <div style="height:1px;background:#C6BCA0;"></div>
          </td>
        </tr>
        <tr>
          <td style="padding:22px 40px 0;font-size:14px;line-height:1.75;color:#3B372C;">
            Ciao {{NOME}},<br><br>
            il tuo mese di prova finisce il <strong style="font-weight:700;">{{DATA_FINE}}</strong>: oggi o domani. Da domani, se non arriva il contributo, l\'accesso alla parte riservata si chiude — congelato, non cancellato: ritrovi tutto al tuo rientro.
          </td>
        </tr>
        <tr>
          <td style="padding:26px 40px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">I</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">PER CONTINUARE SENZA INTERRUZIONI</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Basta un contributo di <strong style="font-weight:700;">29 €</strong> a sostegno dell\'Accademia, con bonifico (causale «CONTRIBUTO A SOSTEGNO DELL\'ASSOCIAZIONE ACCADEMIA DELLA SFOGLIA» — per IBAN e intestatario scrivi alla segreteria). Appena arriva, il gestore sposta la tua data e riparti da dove eri.</div>
                </td>
              </tr>
            </table>
          </td>
        </tr>' . $piede . $chiusura,
		),
		'scadenza_scaduto' => array(
			'label'    => 'La prova è finita',
			'oggetto'  => 'Il tuo mese di prova è finito — Accademia della Sfoglia',
			'trigger'  => 'Parte da sola quando la scadenza è passata da un giorno o due (gs_abbonamento_controlla_scadenze(), sul cron giornaliero). Arriva anche una voce in Posta interna per il gestore, per sapere chi è appena uscita.',
			'corpo'    => $apertura . $testata( 'IL TUO MESE DI PROVA È FINITO' ) . '
        <tr>
          <td style="padding:34px 40px 8px;">
            <div style="font-size:20px;line-height:1.35;color:#12241B;letter-spacing:0.02em;">Il tuo mese di prova<br>è finito</div>
            <div style="height:2px;background:#0B3D2E;margin:16px 0 4px;"></div>
            <div style="height:1px;background:#C6BCA0;"></div>
          </td>
        </tr>
        <tr>
          <td style="padding:22px 40px 0;font-size:14px;line-height:1.75;color:#3B372C;">
            Ciao {{NOME}},<br><br>
            <strong style="font-weight:700;">Non hai perso niente.</strong> I tuoi punti, i tuoi badge, il tuo percorso, le tue ricette, le tue foto e tutto quello che hai scritto sono salvati esattamente come li hai lasciati. Sono congelati, non cancellati: il giorno in cui rientri, ritrovi tutto al suo posto.
          </td>
        </tr>
        <tr>
          <td style="padding:26px 40px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">I</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">COME SI RIAPRE</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Basta un contributo di <strong style="font-weight:700;">29 €</strong> a sostegno dell\'Accademia, con bonifico (causale «CONTRIBUTO A SOSTEGNO DELL\'ASSOCIAZIONE ACCADEMIA DELLA SFOGLIA» — per IBAN e intestatario scrivi alla segreteria). Appena arriva, il gestore riapre il tuo accesso e riparti da dove eri, quando vuoi: anche fra qualche mese.</div>
                </td>
              </tr>
            </table>
            <div style="height:1px;background:#DFD7C2;margin:22px 0;"></div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">II</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">NEL FRATTEMPO</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Il resto del sito resta tuo: la Galleria, il Registro, la Classifica, le Sfogline, le Letture e la tua Vetrina.</div>
                </td>
              </tr>
            </table>
          </td>
        </tr>' . $piede . $chiusura,
		),
		'accesso_vetrina' => array(
			'label'    => 'Accesso e Vetrina',
			'oggetto'  => "Accademia della Sfoglia — accesso all'area riservata e Vetrina",
			'trigger'  => 'Parte da sola 5 giorni dopo l\'approvazione (terza mail, dopo la presentazione e "La Mia Sfoglia"). La puoi anche rimandare a mano, dal riquadro qui sotto.',
			'corpo'    => $apertura . $testata( 'AREA RISERVATA DELLE SFOGLINE' ) . '
        <tr>
          <td style="padding:34px 40px 8px;">
            <div style="font-size:20px;line-height:1.35;color:#12241B;letter-spacing:0.02em;">Accesso e Vetrina:<br>come funzionano</div>
            <div style="height:2px;background:#0B3D2E;margin:16px 0 4px;"></div>
            <div style="height:1px;background:#C6BCA0;"></div>
          </td>
        </tr>
        <tr>
          <td style="padding:22px 40px 0;font-size:14px;line-height:1.75;color:#3B372C;">
            Ciao {{NOME}},<br><br>
            ecco in breve come funzionano l\'accesso all\'area riservata e la Vetrina delle sfogline sul sito.
          </td>
        </tr>
        <tr>
          <td style="padding:26px 40px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">I</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">IL PRIMO MESE È DI PROVA</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Dalla registrazione hai un mese di tempo per effettuare il bonifico. Se non arriva entro quella data, l\'accesso all\'area privata delle sfogline si blocca automaticamente.</div>
                </td>
              </tr>
            </table>
            <div style="height:1px;background:#DFD7C2;margin:22px 0;"></div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">II</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">COME SI RIATTIVA</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Acquisto minimo: <strong style="font-weight:700;">50 € in token</strong> (restano tuoi, li usi quando vuoi — non scadono all\'acquisto).</div>
                  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:12px;background:#EFEADA;">
                    <tr><td style="padding:12px 14px;border-left:2px solid #B08D3F;font-size:13.5px;line-height:1.7;color:#3B372C;">
                      <strong style="font-weight:700;">29 € di token</strong> — l\'accesso all\'area privata si riattiva.
                    </td></tr>
                    <tr><td style="height:1px;background:#DFD7C2;"></td></tr>
                    <tr><td style="padding:12px 14px;border-left:2px solid #B08D3F;font-size:13.5px;line-height:1.7;color:#3B372C;">
                      <strong style="font-weight:700;">50 € di token</strong> — il tuo nome diventa cliccabile nella pagina <em>Le Sfogline</em> e apre la tua Biografia.
                    </td></tr>
                  </table>
                  <div style="font-size:12.5px;line-height:1.7;color:#7A7263;padding-top:10px;">Bonifico con causale <strong style="font-weight:700;color:#3B372C;">«CONTRIBUTO A SOSTEGNO DELL\'ASSOCIAZIONE ACCADEMIA DELLA SFOGLIA»</strong> — per IBAN e intestatario scrivi alla segreteria.</div>
                </td>
              </tr>
            </table>
            <div style="height:1px;background:#DFD7C2;margin:22px 0;"></div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">III</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">VETRINA NEL NASTRO SOTTO IL MENU <span style="color:#7A7263;letter-spacing:0.04em;">(FACOLTATIVO)</span></div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Per Aziende, Scuole di Cucina e Sfogline che vogliono comparire nel nastro scorrevole sotto il menu del sito: <strong style="font-weight:700;">500 €/anno in token</strong>. Se non rinnovi entro l\'anno, il sistema toglie automaticamente il nome dal nastro.</div>
                </td>
              </tr>
            </table>
          </td>
        </tr>' . $piede . $chiusura,
		),
		'come_funziona_percorso' => array(
			'label'    => 'Come funziona il Percorso',
			'oggetto'  => 'Come funziona il Percorso — Accademia della Sfoglia',
			'trigger'  => 'Non ha ancora un invio automatico collegato — per ora la trovi solo qui, pronta e modificabile, per quando deciderai quando/a chi mandarla.',
			'corpo'    => $apertura . $testata( 'IL PERCORSO DELLE SFOGLINE' ) . '
        <tr>
          <td style="padding:34px 40px 8px;">
            <div style="font-size:20px;line-height:1.35;color:#12241B;letter-spacing:0.02em;">Come funziona<br>il Percorso</div>
            <div style="height:2px;background:#0B3D2E;margin:16px 0 4px;"></div>
            <div style="height:1px;background:#C6BCA0;"></div>
          </td>
        </tr>
        <tr>
          <td style="padding:22px 40px 0;font-size:14px;line-height:1.75;color:#3B372C;">
            Ciao {{NOME}},<br><br>
            ogni gesto che fai sul sito — pubblicare una sfoglia, aiutare un\'altra sfoglina con un commento, scrivere nel Diario dell\'Impasto — è un passo avanti nel Percorso dell\'Accademia. Ecco com\'è fatto.
          </td>
        </tr>
        <tr>
          <td style="padding:26px 40px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">I</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">LE TAPPE DEL PERCORSO</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Sei tappe, dalla prima sfoglia alla maestria riconosciuta: Sfoglina Novella, Sfoglina, Sfoglina Provetta, Maestra della Sfoglia, Sfoglina d\'Oro, Custode della Tradizione. Il tuo profilo mostra sempre a che tappa sei arrivata.</div>
                </td>
              </tr>
            </table>
            <div style="height:1px;background:#DFD7C2;margin:22px 0;"></div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">II</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">COME SI AVANZA</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Pubblicando una sfoglia, votando e commentando i lavori delle altre, scrivendo nel Diario dell\'Impasto, condividendo un consiglio, restando costanti settimana dopo settimana.</div>
                </td>
              </tr>
            </table>
            <div style="height:1px;background:#DFD7C2;margin:22px 0;"></div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">III</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">I COMPITI DEL GIORNO</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Ogni giorno trovi qualche piccolo compito proposto apposta per te — un modo semplice per fare un passo avanti anche nei giorni più impegnati.</div>
                </td>
              </tr>
            </table>
            <div style="height:1px;background:#DFD7C2;margin:22px 0;"></div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">IV</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">LA COSTANZA SETTIMANALE</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Ogni settimana in cui partecipi almeno una volta, la tua costanza sale. Mantenerla a lungo vale anche qualcosa in più; ogni 4 settimane di fila guadagni una copertura che ti salva una settimana persa senza interrompere il conteggio.</div>
                </td>
              </tr>
            </table>
            <div style="height:1px;background:#DFD7C2;margin:22px 0;"></div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">V</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">I TRAGUARDI</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Lungo il Percorso ci sono anche traguardi speciali — alcuni rari, alcuni legati alla costanza. Una volta raggiunti, restano per sempre nel tuo profilo.</div>
                </td>
              </tr>
            </table>
          </td>
        </tr>' . $piede . $chiusura,
		),
		'la_mia_sfoglia' => array(
			'label'    => 'La Mia Sfoglia, spiegata',
			'oggetto'  => 'Ciao, "La Mia Sfoglia" la tua bacheca personale spiegata bene! Segui questo percorso e non ti stancare, sarà divertente e molto produttivo!',
			'trigger'  => 'Parte da sola 2 giorni dopo l\'approvazione (seconda mail, dopo la presentazione e prima di "Accesso e Vetrina", che arriva al giorno 5).',
			'corpo'    => $apertura . $testata( 'LA TUA BACHECA PERSONALE' ) . '
        <tr>
          <td style="padding:34px 40px 8px;">
            <div style="font-size:20px;line-height:1.35;color:#12241B;letter-spacing:0.02em;">«La Mia Sfoglia»<br>diventa la tua bacheca</div>
            <div style="height:2px;background:#0B3D2E;margin:16px 0 4px;"></div>
            <div style="height:1px;background:#C6BCA0;"></div>
          </td>
        </tr>
        <tr>
          <td style="padding:22px 40px 0;font-size:14px;line-height:1.75;color:#3B372C;">
            Ciao {{NOME}},<br><br>
            hai già visto «La Mia Sfoglia» nel menu del sito — ma forse non hai ancora scoperto tutto quello che c\'è dentro. È la tua bacheca personale: da qui parte tutto, ed è pensata per dirti sempre, con un\'occhiata, «cosa faccio oggi» e «a che punto sono». Ecco com\'è fatta.
          </td>
        </tr>
        <tr>
          <td style="padding:26px 40px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">I</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">IN CIMA, LA TUA CARTA D\'IDENTITÀ</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Foto, livello e tutti i tuoi numeri insieme: punti, streak, scudi salva-streak, token, badge, lo sconto sui corsi maturato. Accanto, «Prossimo passo» — un piccolo suggerimento che cambia da solo in base a dove sei arrivata.</div>
                </td>
              </tr>
            </table>
            <div style="height:1px;background:#DFD7C2;margin:22px 0;"></div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">II</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">OGGI</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Le missioni del giorno e l\'Ingrediente Segreto: il motivo per aprire la pagina ogni giorno. Le missioni si aggiornano da sole mentre usi il sito — clicchi, vai dritta a fare la cosa, e i punti arrivano da soli.</div>
                </td>
              </tr>
            </table>
            <div style="height:1px;background:#DFD7C2;margin:22px 0;"></div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">III</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">IL TUO PERCORSO</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Livello, badge, tabellone dei premi, il Buono Sfoglia quando la gara è attiva, lo sconto sui corsi, e il riquadro Madrina/Allieva se sei abbinata a qualcuna.</div>
                </td>
              </tr>
            </table>
            <div style="height:1px;background:#DFD7C2;margin:22px 0;"></div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">IV</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">LE TUE SFIDE</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Le sfide a cui partecipi, il cestino di quelle passate, e lo Streak del Matterello: conta le settimane consecutive in cui pubblichi almeno una sfoglia. Se ne salti una lo streak di solito si azzera — ma ogni 4 settimane guadagni uno scudo salva-streak, che copre da solo la settimana saltata senza farti perdere la serie.</div>
                </td>
              </tr>
            </table>
            <div style="height:1px;background:#DFD7C2;margin:22px 0;"></div>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td width="34" valign="top" style="font-size:19px;color:#B08D3F;line-height:1.1;">V</td>
                <td valign="top">
                  <div style="font-size:13px;letter-spacing:0.06em;color:#12241B;padding-bottom:6px;">I TUOI STRUMENTI</div>
                  <div style="font-size:13.5px;line-height:1.72;color:#3B372C;">Le cose da fare, la tua Vetrina pubblica (il profilo condivisibile anche fuori dal sito, se l\'hai attivata), e «Il tuo account»: password, email, esportazione dei tuoi dati, richiesta di cancellazione. Tutto qui, niente da cercare altrove.</div>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="padding:30px 40px 0;font-size:13.5px;line-height:1.72;color:#3B372C;">
            Non c\'è nulla da configurare la prima volta: apri «La Mia Sfoglia» e trovi già tutto pronto, aggiornato in automatico man mano che usi il sito.
          </td>
        </tr>
        <tr>
          <td style="padding:22px 40px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
              <td style="background:#0B3D2E;padding:13px 30px;">
                <a href="{{URL_MIA_SFOGLIA}}" style="font-size:11px;letter-spacing:0.22em;color:#F2EDE1;text-decoration:none;">APRI LA MIA SFOGLIA</a>
              </td>
            </tr></table>
          </td>
        </tr>' . $piede . $chiusura,
		),
	);
}

/** Il modello di partenza (non modificato) di una mail, o null se la chiave non esiste. */
function gs_mail_template_predefinito( $chiave ) {
	$reg = gs_mail_template_registro();
	return isset( $reg[ $chiave ] ) ? $reg[ $chiave ] : null;
}

/** Il testo che Ennio ha salvato per questa mail dal pannello, o il modello di partenza se non l'ha mai toccata. */
function gs_mail_template_corpo_attivo( $chiave ) {
	$def = gs_mail_template_predefinito( $chiave );
	if ( ! $def ) {
		return '';
	}
	$s = gs_settings();
	$salvato = isset( $s['mail_template_corpo'][ $chiave ] ) ? $s['mail_template_corpo'][ $chiave ] : '';
	return $salvato ? $salvato : $def['corpo'];
}

/**
 * Sostituisce i segnaposto {{...}} nel testo attivo di una mail per una
 * sfoglina precisa — stesso meccanismo qualunque sia la mail (attuale o
 * futura, purché registrata in gs_mail_template_registro()).
 */
function gs_mail_template_render( $chiave, $user ) {
	$corpo = gs_mail_template_corpo_attivo( $chiave );
	if ( ! $corpo ) {
		return '';
	}
	$nome     = $user->display_name ? $user->display_name : $user->user_login;
	$url_logo = gs_mail_area_riservata_logo_url();

	// Le due date dei trenta giorni (usate dalla mail di benvenuto): per una
	// sfoglina vera si leggono i meta scritti da gs_approve_user(); per una
	// mail di prova (nessun ->ID, un oggetto finto) si inventano date
	// plausibili — «finte ma realistiche», come chiesto nel documento del
	// pannello delle mail (26/08/2026), così la prova mostra come verrà
	// davvero invece di un segnaposto vuoto.
	if ( isset( $user->ID ) && $user->ID ) {
		$inizio_raw = get_user_meta( $user->ID, 'gs_data_approvazione', true );
		$fine_raw   = get_user_meta( $user->ID, 'gs_abbonamento_scadenza', true );
	} else {
		$inizio_raw = '';
		$fine_raw   = '';
	}
	if ( ! $inizio_raw || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $inizio_raw ) ) {
		$inizio_raw = current_time( 'Y-m-d' );
	}
	if ( ! $fine_raw || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $fine_raw ) ) {
		$fine_raw = date( 'Y-m-d', strtotime( $inizio_raw . ' +30 days' ) );
	}
	$data_inizio = date_i18n( 'j F Y', strtotime( $inizio_raw ) );
	$data_fine   = date_i18n( 'j F Y', strtotime( $fine_raw ) );

	// Link di conferma email: letto dal contrassegno che gs_email_verifica_invia()
	// scrive PRIMA di chiamare questa funzione (stesso schema delle due date
	// qui sopra — un valore vero per una sfoglina reale, un link finto ma
	// dello stesso formato per una mail di prova).
	if ( isset( $user->ID ) && $user->ID && get_user_meta( $user->ID, 'gs_email_verify_token', true ) ) {
		$link_verifica = add_query_arg(
			array( 'gs_verifica_email' => (int) $user->ID, 'gs_verifica_token' => get_user_meta( $user->ID, 'gs_email_verify_token', true ) ),
			function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_login' ) : home_url( '/' )
		);
	} else {
		$link_verifica = add_query_arg(
			array( 'gs_verifica_email' => 0, 'gs_verifica_token' => 'esempio' ),
			function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_login' ) : home_url( '/' )
		);
	}

	// border-radius:50% ritaglia il quadrato bianco del file caricato
	// (logo-mail-accademia-della-sfogliia.jpg, un JPG 300×300 senza
	// trasparenza) sul bordo del sigillo tondo — segnalato da Ennio
	// (26/08/2026: "elimina il riquadro bianco intorno al logo").
	$blocco_logo_intestazione = $url_logo
		? '<img src="' . esc_url( $url_logo ) . '" width="104" alt="Accademia della Sfoglia" style="width:104px;height:104px;border-radius:50%;display:block;margin:0 auto 18px;border:0;">'
		: '';
	$blocco_logo_piede = $url_logo
		? '<img src="' . esc_url( $url_logo ) . '" width="60" alt="" style="width:60px;height:60px;border-radius:50%;display:block;border:0;">'
		: '';

	return strtr( $corpo, array(
		'{{NOME}}'                      => esc_html( $nome ),
		'{{DATA_INIZIO}}'               => esc_html( $data_inizio ),
		'{{DATA_FINE}}'                 => esc_html( $data_fine ),
		'{{LINK_VERIFICA_EMAIL}}'       => esc_url( $link_verifica ),
		'{{URL_LOGO}}'                  => esc_url( $url_logo ),
		'{{URL_SFOGLINE}}'              => esc_url( 'https://accademiadellasfoglia.it/le-sfogline/' ),
		'{{URL_MIA_SFOGLIA}}'           => esc_url( function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_dashboard' ) : home_url( '/' ) ),
		'{{URL_SCRIVICI}}'              => esc_url( 'https://accademiadellasfoglia.it/scrivici-laccademia-della-sfoglia-risponde/' ),
		// Tre pulsanti FAQ (Ennio, 21/08/2026: "tutte" le tre pagine FAQ del
		// sito, un pulsante per ciascuna — vedi il registro di gs_mail_template_registro()
		// per dove compaiono nel testo).
		'{{URL_FAQ_INFORMAZIONI}}'      => esc_url( 'https://accademiadellasfoglia.it/informazioni-faq/' ),
		'{{URL_FAQ_GIOCO}}'             => esc_url( 'https://accademiadellasfoglia.it/faq-gaming/' ),
		'{{URL_FAQ_PORTALE}}'           => esc_url( 'https://accademiadellasfoglia.it/faq-domande/' ),
		'{{BLOCCO_LOGO_INTESTAZIONE}}'  => $blocco_logo_intestazione,
		'{{BLOCCO_LOGO_PIEDE}}'         => $blocco_logo_piede,
	) );
}

/** Compatibilità: la mail "Accesso e Vetrina" com'era prima, ora costruita dal modello registrato. */
function gs_mail_area_riservata_html( $user ) {
	return gs_mail_template_render( 'accesso_vetrina', $user );
}

/**
 * Invia una mail del registro a una sfoglina precisa. Content-Type impostato
 * solo per questo invio (via $headers di wp_mail), senza toccare il filtro
 * globale — le altre email del plugin restano testo semplice.
 */
function gs_invia_mail_template( $chiave, $user_id ) {
	$def = gs_mail_template_predefinito( $chiave );
	$user = get_userdata( (int) $user_id );
	if ( ! $def || ! $user || ! $user->user_email ) {
		return false;
	}
	$corpo   = gs_mail_template_render( $chiave, $user );
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	return wp_mail( $user->user_email, $def['oggetto'], $corpo, $headers );
}

/**
 * Come gs_invia_mail_template(), ma a un indirizzo diretto invece che a uno
 * user_id esistente (punto 4b: pulsante "Invia mail di prova", Ennio
 * 21/08/2026) — serve per testare aspetto/testo senza dover scegliere una
 * sfoglina vera ogni volta. gs_mail_template_render() legge solo
 * $user->display_name, quindi basta un oggetto finto con quel campo, non
 * serve un vero WP_User.
 */
function gs_invia_mail_template_a_indirizzo( $chiave, $email, $nome = 'Prova' ) {
	$def = gs_mail_template_predefinito( $chiave );
	if ( ! $def || ! is_email( $email ) ) {
		return false;
	}
	$finto = (object) array( 'display_name' => $nome, 'user_login' => $nome );
	$corpo   = gs_mail_template_render( $chiave, $finto );
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	return wp_mail( $email, '[PROVA] ' . $def['oggetto'], $corpo, $headers );
}

/** Compatibilità: invia "Accesso e Vetrina" — usata dal riquadro di invio manuale. */
function gs_invia_mail_area_riservata( $user_id ) {
	return gs_invia_mail_template( 'accesso_vetrina', $user_id );
}

// Invio automatico: ogni sfoglina approvata la riceve comunque (Ennio,
// 19/08/2026: "a tutti coloro che si iscrivono automaticamente ricevono
// questa mail") — ma non più nello stesso istante dell'approvazione. Dal
// 26/08/2026 parte 5 giorni dopo, insieme a "La Mia Sfoglia" (2 giorni dopo):
// vedi gs_mail_benvenuto_differite() in registration.php, sul cron
// giornaliero. Qui non resta nessun gancio immediato.

// -----------------------------------------------------------------------------
// Invio manuale a una sfoglina scelta a mano, dal pannello "Iscrizioni delle
// sfogline" — cerca per nome/username/email, invia con un clic.
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_invia_mail_area_riservata_manuale', 'gs_ajax_invia_mail_area_riservata_manuale' );
function gs_ajax_invia_mail_area_riservata_manuale() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Non hai i permessi per inviare questa mail.' ) );
	}
	$uid = isset( $_POST['user'] ) ? (int) $_POST['user'] : 0;
	$chiave = isset( $_POST['modello'] ) ? sanitize_key( wp_unslash( $_POST['modello'] ) ) : 'accesso_vetrina';
	if ( ! $uid || ! get_userdata( $uid ) ) {
		wp_send_json_error( array( 'message' => 'Sfoglina non trovata.' ) );
	}
	if ( ! gs_mail_template_predefinito( $chiave ) ) {
		wp_send_json_error( array( 'message' => 'Mail non riconosciuta.' ) );
	}
	$ok = gs_invia_mail_template( $chiave, $uid );
	if ( $ok ) {
		wp_send_json_success( array( 'message' => 'Mail inviata.' ) );
	}
	wp_send_json_error( array( 'message' => "Non è stato possibile inviare l'email (controlla che l'invio email sia configurato sul server)." ) );
}

/**
 * "Invia mail di prova" (punto 4b, Ennio 21/08/2026): la mail scelta nel
 * modello, a un indirizzo fisso di test — non serve scegliere una sfoglina
 * vera solo per controllare aspetto e testo.
 */
add_action( 'wp_ajax_gs_invia_mail_prova', 'gs_ajax_invia_mail_prova' );
function gs_ajax_invia_mail_prova() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Non hai i permessi per inviare questa mail.' ) );
	}
	$chiave = isset( $_POST['modello'] ) ? sanitize_key( wp_unslash( $_POST['modello'] ) ) : '';
	if ( ! gs_mail_template_predefinito( $chiave ) ) {
		wp_send_json_error( array( 'message' => 'Mail non riconosciuta.' ) );
	}
	// Prima era un indirizzo scritto fisso (info@lentium.it): se cambia chi
	// gestisce le prove, resterebbe un invio verso una casella dimenticata.
	// L'email dell'amministratore del sito si aggiorna da sola (Ennio,
	// 26/08/2026).
	$indirizzo_prova = get_option( 'admin_email' );
	$ok = gs_invia_mail_template_a_indirizzo( $chiave, $indirizzo_prova, 'Prova' );
	if ( $ok ) {
		wp_send_json_success( array( 'message' => 'Mail di prova inviata a ' . $indirizzo_prova . '.' ) );
	}
	wp_send_json_error( array( 'message' => "Non è stato possibile inviare l'email di prova (controlla che l'invio email sia configurato sul server)." ) );
}

// -----------------------------------------------------------------------------
// Salvataggio/ripristino del testo di una mail dal pannello.
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_salva_mail_template', 'gs_ajax_salva_mail_template' );
function gs_ajax_salva_mail_template() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Non hai i permessi.' ) );
	}
	$chiave = isset( $_POST['modello'] ) ? sanitize_key( wp_unslash( $_POST['modello'] ) ) : '';
	if ( ! gs_mail_template_predefinito( $chiave ) ) {
		wp_send_json_error( array( 'message' => 'Mail non riconosciuta.' ) );
	}
	$corpo = isset( $_POST['corpo'] ) ? wp_kses_post( wp_unslash( $_POST['corpo'] ) ) : '';
	$s = gs_settings();
	if ( ! isset( $s['mail_template_corpo'] ) || ! is_array( $s['mail_template_corpo'] ) ) {
		$s['mail_template_corpo'] = array();
	}
	$s['mail_template_corpo'][ $chiave ] = $corpo;
	update_option( GS_OPTION, $s );
	gs_settings( true );
	wp_send_json_success( array( 'message' => 'Testo salvato.' ) );
}

add_action( 'wp_ajax_gs_ripristina_mail_template', 'gs_ajax_ripristina_mail_template' );
function gs_ajax_ripristina_mail_template() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Non hai i permessi.' ) );
	}
	$chiave = isset( $_POST['modello'] ) ? sanitize_key( wp_unslash( $_POST['modello'] ) ) : '';
	$def = gs_mail_template_predefinito( $chiave );
	if ( ! $def ) {
		wp_send_json_error( array( 'message' => 'Mail non riconosciuta.' ) );
	}
	$s = gs_settings();
	if ( isset( $s['mail_template_corpo'][ $chiave ] ) ) {
		unset( $s['mail_template_corpo'][ $chiave ] );
		update_option( GS_OPTION, $s );
		gs_settings( true );
	}
	wp_send_json_success( array( 'message' => 'Ripristinato il testo originale.', 'corpo' => $def['corpo'] ) );
}

add_action( 'wp_ajax_gs_salva_logo_arriservata', 'gs_ajax_salva_logo_arriservata' );
function gs_ajax_salva_logo_arriservata() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Non hai i permessi.' ) );
	}
	$s = gs_settings();
	$s['mail_area_riservata_logo'] = isset( $_POST['logo_url'] ) ? esc_url_raw( wp_unslash( $_POST['logo_url'] ) ) : '';
	update_option( GS_OPTION, $s );
	gs_settings( true );
	wp_send_json_success( array( 'message' => 'Logo salvato.' ) );
}

/**
 * Riquadro nel pannello "Iscrizioni delle sfogline": elenco delle mail
 * modificabili (testo apribile/modificabile una per una), invio a una
 * sfoglina scelta, e il campo del logo comune a tutte.
 */
function gs_pannello_invio_mail_area_riservata() {
	if ( ! gs_can_manage() ) {
		return;
	}
	// Solo le sfogline davvero approvate — prima mostrava tutti gli utenti
	// del sito, comprese le richieste mai approvate/rifiutate (Ennio,
	// 20/08/2026: "trovo ancora tutta la lista delle persone che non si
	// sono iscritte").
	$sfogline = get_users( array(
		'orderby'    => 'display_name',
		'order'      => 'ASC',
		'fields'     => array( 'ID', 'display_name', 'user_login' ),
		'meta_key'   => 'gs_status',
		'meta_value' => 'approvata',
	) );

	echo gs_box_open( 'Mail dell\'Accademia — testo e invio' );
	echo '<p class="gs-hint">Ogni mail qui sotto ha già un testo pronto: apri "Modifica testo" per leggerlo e cambiarlo, "Salva" tiene la tua versione, "Ripristina il testo originale" torna a quello di partenza in qualsiasi momento. {{NOME}} si scrive da solo con il nome della sfoglina; {{URL_LOGO}}, {{URL_SFOGLINE}}, {{URL_SCRIVICI}}, {{URL_MIA_SFOGLIA}}, {{URL_FAQ_INFORMAZIONI}}, {{URL_FAQ_GIOCO}}, {{URL_FAQ_PORTALE}} si compilano da soli allo stesso modo; {{DATA_INIZIO}} e {{DATA_FINE}} sono le due date del mese di prova (nelle mail di benvenuto e di scadenza); {{LINK_VERIFICA_EMAIL}} è il link di conferma (solo nella mail "Conferma dell\'indirizzo email") — non toccarli se non sai cosa fanno. <strong>Togliere un segnaposto non lo lascia vuoto: la cosa che doveva dire semplicemente non compare</strong> (es. senza {{DATA_FINE}} nella mail di benvenuto, la sfoglina non saprebbe più quando scade la prova).</p>';

	foreach ( gs_mail_template_registro() as $chiave => $def ) {
		$corpo_attivo = gs_mail_template_corpo_attivo( $chiave );
		?>
		<details class="gs-sezione-aiuto" style="margin-bottom:14px">
			<summary>✉️ <?php echo esc_html( $def['label'] ); ?></summary>
			<p class="gs-hint" style="margin-top:6px"><?php echo esc_html( $def['trigger'] ); ?></p>
			<p><label>Oggetto<br><input type="text" value="<?php echo esc_attr( $def['oggetto'] ); ?>" readonly style="width:100%;background:var(--gs-crema,#faf6ec)"></label></p>
			<p><label>Testo (HTML)<br>
				<textarea class="gs-mail-template-corpo" data-modello="<?php echo esc_attr( $chiave ); ?>" rows="14" style="width:100%;font-family:ui-monospace,Menlo,monospace;font-size:11.5px;white-space:pre"><?php echo esc_textarea( $corpo_attivo ); ?></textarea>
			</label></p>
			<?php
			// Segnaposto usati DAVVERO da questa mail (letti dal testo di
			// partenza, non da un elenco fisso): se Ennio ne cancella uno per
			// sbaglio, quella cosa smette di comparire nella mail senza che
			// nessuno se ne accorga, perché la mail parte comunque e sembra
			// a posto — richiesto dalla verifica di 3.293.0, 27/08/2026.
			preg_match_all( '/\{\{[A-Z_]+\}\}/', $def['corpo'], $segnaposto_trovati );
			$segnaposto_usati = array_unique( $segnaposto_trovati[0] );
			if ( $segnaposto_usati ) :
			?>
			<p class="gs-hint">Segnaposto usati in questa mail: <?php
				// esc_html su OGNI voce, non sulla stringa già unita: prima
				// proteggeva anche i separatori </code>, <code> usati per
				// mostrarli, e Ennio se li ritrovava scritti in mezzo ai
				// segnaposto invece che come formattazione (verifica di
				// 3.294.0, 27/08/2026).
				echo '<code>' . implode( '</code>, <code>', array_map( 'esc_html', $segnaposto_usati ) ) . '</code>';
			?>. Se ne togli uno, quella cosa semplicemente non comparirà — la mail parte comunque, senza avviso.</p>
			<?php endif; ?>
			<p>
				<button type="button" class="gs-btn gs-btn-sm gs-salva-mail-template-btn" data-modello="<?php echo esc_attr( $chiave ); ?>">Salva</button>
				<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-ripristina-mail-template-btn" data-modello="<?php echo esc_attr( $chiave ); ?>">Ripristina il testo originale</button>
				<span class="gs-mail-template-msg gs-richiesta-esito"></span>
			</p>
		</details>
		<?php
	}

	echo '<p style="margin-top:16px;padding-top:14px;border-top:1px dashed var(--gs-bordo,#e3d5b8)"><strong>Invia a una sfoglina</strong></p>';
	?>
	<form class="gs-form gs-form-invia-mail-arriservata" onsubmit="return false">
		<p>
			<label>Mail<br>
				<select name="modello" style="min-width:220px">
					<?php foreach ( gs_mail_template_registro() as $chiave => $def ) : ?>
						<option value="<?php echo esc_attr( $chiave ); ?>"><?php echo esc_html( $def['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label style="margin-left:10px">Sfoglina<br>
				<select name="user" style="min-width:260px">
					<option value="">— scegli —</option>
					<?php foreach ( $sfogline as $s ) : ?>
						<option value="<?php echo (int) $s->ID; ?>"><?php echo esc_html( $s->display_name . ' (' . $s->user_login . ')' ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</p>
		<p>
			<button class="gs-btn gs-btn-sm gs-invia-mail-arriservata-btn">Invia mail</button>
			<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-invia-mail-prova-btn" title="Manda la mail scelta sopra all'email dell'amministratore del sito, senza bisogno di scegliere una sfoglina">Invia mail di prova</button>
			<span class="gs-invia-mail-arriservata-msg gs-richiesta-esito"></span>
		</p>
	</form>
	<p style="margin-top:14px;padding-top:14px;border-top:1px dashed var(--gs-bordo,#e3d5b8)">
		<label>URL del logo nell'intestazione delle mail (facoltativo, lascialo vuoto per non mostrarne nessuno)<br>
			<input type="text" class="gs-input-logo-arriservata" value="<?php echo esc_attr( gs_mail_area_riservata_logo_url() ); ?>" placeholder="https://accademiadellasfoglia.it/wp-content/uploads/…" style="width:100%">
		</label>
	</p>
	<p><button class="gs-btn gs-btn-sm gs-btn-ghost gs-salva-logo-arriservata-btn">Salva logo</button> <span class="gs-salva-logo-arriservata-msg gs-richiesta-esito"></span></p>
	<?php
	echo gs_box_close();
}
