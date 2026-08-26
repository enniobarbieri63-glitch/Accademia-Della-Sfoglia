# Le tre mail dell'approvazione: stesso contenuto, distanziate

**Situazione, verificata nella 3.283.2.** Tre mail sono agganciate allo stesso evento,
`gs_dopo_approvazione_sfoglina`, e partono **tutte insieme, nello stesso istante**:

| Priorità | Mail | Dove |
|---|---|---|
| 5 | «La Mia Sfoglia, spiegata» | `registration.php:209` |
| 10 | «Accesso e Vetrina» | `mail-area-riservata.php` |
| *(+ la presentazione)* | | |

**La priorità mette in ordine l'esecuzione, non il tempo.** Le tre email arrivano nella
casella nel giro di pochi secondi.

**Non c'è niente di sbagliato nei contenuti** — sono tre testi buoni, e il registro dei
modelli con l'invio di prova è fatto bene. **Il problema è solo che arrivano insieme.**
Tre messaggi dallo stesso mittente in trenta secondi si leggono come uno, e quello letto è
il primo. Ennio ha chiesto proprio di non tediare le iscritte (26/08/2026), e questo è il
punto dove succede.

**Nessuna va tolta. Vanno solo spaziate.**

---

## Come

| Quando | Mail | Perché lì |
|---|---|---|
| **Giorno 0**, all'approvazione | la **presentazione** | è il benvenuto: deve arrivare subito |
| **Giorno 2** | **«La Mia Sfoglia, spiegata»** | ha già guardato la sua pagina: adesso la spiegazione ha un aggancio |
| **Giorno 5** | **«Accesso e Vetrina»** | è la prima che parla di soldi: non deve stare accanto al benvenuto |

**Il giorno 0 resta com'è.** Cambiano solo le altre due.

## Con il cron giornaliero, non con `wp_schedule_single_event()`

**Non usare gli eventi singoli programmati.** WP-Cron dipende dalle visite: un evento fissato
fra due giorni può non partire mai se in quel momento il sito è fermo, e **nessuno se ne
accorge.** Su questo progetto lo abbiamo già pagato una volta.

**Usa il cron giornaliero, che gira già e che controlliamo**, con la data di approvazione e
un contrassegno per ogni mail:

**1. Segnare quando è stata approvata.** In `gs_approve_user()`, dove si scatena
`gs_dopo_approvazione_sfoglina`:

```php
	update_user_meta( $user_id, 'gs_data_approvazione', current_time( 'Y-m-d' ) );
```

**2. Togliere i due agganci immediati** — `registration.php:209` e il gemello in
`mail-area-riservata.php`. Restano le funzioni, sparisce solo l'`add_action`.

**3. Un solo aggancio nuovo sul cron giornaliero:**

```php
/**
 * Le mail di benvenuto, distanziate nel tempo (Ennio, 26/08/2026: "non
 * mandiamo troppe mail, non tediamo gli iscritti"). Prima partivano tutte e
 * tre insieme all'approvazione: tre messaggi dallo stesso mittente in
 * trenta secondi si leggono come uno solo.
 *
 * Sul cron GIORNALIERO e non con wp_schedule_single_event(): un evento
 * singolo programmato fra due giorni dipende dalle visite al sito e può non
 * partire mai, in silenzio. Qui, se il cron salta un giorno, il giorno dopo
 * recupera da solo — il confronto è sui giorni passati, non su un momento
 * esatto.
 */
add_action( 'gs_daily_cron', 'gs_mail_benvenuto_differite' );
function gs_mail_benvenuto_differite() {
	$piano = array(
		2 => 'la_mia_sfoglia',
		5 => 'accesso_vetrina',
	);

	foreach ( gs_sez_sfogline() as $u ) {
		$approvata = get_user_meta( $u->ID, 'gs_data_approvazione', true );
		if ( ! $approvata || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $approvata ) ) { continue; }

		// Congelata: niente mail di benvenuto mentre è fuori (stessa regola
		// del digest settimanale, 3.283.0).
		if ( function_exists( 'gs_abbonamento_scaduto' ) && gs_abbonamento_scaduto( $u->ID ) ) { continue; }

		// Due date attraverso la stessa funzione: mai un timestamp contro una
		// mezzanotte (è l'errore di P3, 25/08/2026).
		$giorni = (int) round(
			( strtotime( current_time( 'Y-m-d' ) ) - strtotime( $approvata ) ) / DAY_IN_SECONDS
		);

		foreach ( $piano as $quando => $chiave ) {
			if ( $giorni < $quando ) { continue; }
			$fatto = 'gs_mail_benv_' . $chiave;
			if ( get_user_meta( $u->ID, $fatto, true ) ) { continue; }
			// Contrassegno PRIMA di mandare: un cron che riparte non deve
			// poter mandare due volte la stessa mail.
			update_user_meta( $u->ID, $fatto, current_time( 'mysql' ) );
			gs_invia_mail_template( $chiave, $u->ID );
		}
	}
}
```

---

## Tre cose da controllare mentre lo scrivi

1. **`gs_approve_user()` esiste con questo nome?** L'ho dedotto da `gs_reject_user()`
   (`registration.php:205`). **Verificalo**, e metti la data dove viene davvero scatenato
   `gs_dopo_approvazione_sfoglina`.
2. **La presentazione resta immediata.** Controlla che non sia agganciata anche lei allo
   stesso evento con un'altra priorità: se lo è, **non toccarla** — è quella del giorno 0.
3. **`$giorni >= $quando`, non `==`.** Se il cron salta un giorno, con `==` la mail del
   giorno 2 non parte più. Con `>=` parte il giorno dopo. **Il contrassegno impedisce
   comunque il doppio invio.**

## Come si prova

Su guru2: approva una sfoglina di prova, **verifica che arrivi una sola mail**. Poi metti
`gs_data_approvazione` a cinque giorni fa, fai girare `gs_daily_cron` a mano, e **devono
arrivare le altre due insieme** (è il recupero, ed è giusto così). Rifallo: **non deve
arrivare più niente.**

**Compromesso da dichiarare:** una sfoglina approvata e riattivata dopo un congelamento non
riceve le mail arretrate, perché i contrassegni restano. **È voluto** — sono mail di
benvenuto, non ha senso mandarle a chi è già dentro da mesi.
