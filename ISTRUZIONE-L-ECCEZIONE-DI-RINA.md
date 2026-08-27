# Istruzione: togliere l'eccezione di Rina sui piatti

**Per Claude Code Ennio — 27/08/2026, scritta su 3.298.0**
**Sostituisce la versione precedente di questo file: la domanda che poneva ha avuto risposta.**

In 3.298.0 il Reset libera il custode di tutti i piatti in via d'estinzione **tranne** quelli
adottati da Rina Poletti. Quell'eccezione è nata dalla frase di Ennio *«cancella tutto, tranne
i consigli di Rina Poletti»*, interpretata come «i piatti che ha adottato lei».

**Ennio ha chiarito: intendeva i Consigli scritti da lei.**

E quelli **sono già tenuti**, senza fare niente: la decisione 2 tiene il tipo `gs_consiglio`
per intero, e i Consigli si scrivono sia dalle sfogline sia dal titolare in wp-admin (c'è la
voce di menu «Consigli» in `admin.php:46`). Quello che Ennio ha chiesto è già in 3.298.0.

Quindi l'eccezione sui piatti è una regola in più, mai chiesta, su un'operazione che non si
annulla. **Va tolta**, e i piatti tornano tutti liberi come nella proposta approvata.

---

## PARTE 1 — Togliere l'eccezione

Tutto in `includes/reset.php`. `gs_e_rina_poletti()` non è usata da nessun'altra parte del
plugin: l'ho verificato su tutti i file del pacchetto, la definizione e l'unica chiamata sono
tutte e due in questo file.

**1a — Cancella la funzione** `gs_e_rina_poletti()` per intero (nel pacchetto 3.298.0 sta poco
sopra `gs_reset_meta_da_tenere()`), commento compreso.

**1b — Semplifica il blocco `3d`** dentro `gs_reset_esegui()`: il ramo che risparmiava i suoi
piatti sparisce.

```php
	// 3d — i piatti restano (sono catalogo), ma il custode è stato di gioco:
	// senza svuotarlo i piatti ripartono già adottati da sfogline che non
	// hanno più niente, e nessuna nuova sfoglina può più adottarli — lo
	// stesso motivo per cui si svuotano i voti dentro i sondaggi
	// (decisione di Ennio, 27/08/2026).
	//
	// Nessuna eccezione per nessuno: la 3.298.0 ne aveva una per i piatti di
	// Rina Poletti, nata da una frase interpretata male. Ennio ha chiarito il
	// 27/08/2026 che parlava dei Consigli scritti da lei — che si tengono
	// tutti per conto loro, perché gs_consiglio è nell'elenco dei tipi da
	// tenere. Un'eccezione basata sul nome visualizzato di un account, per
	// giunta, sarebbe fallita in silenzio a ogni piccola differenza di
	// maiuscole o di spazi.
	$piatti_liberati = 0;
	if ( post_type_exists( 'gs_piatto' ) ) {
		foreach ( get_posts( array( 'post_type' => 'gs_piatto', 'post_status' => $stati_da_cancellare, 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $pid ) {
			if ( ! get_post_meta( $pid, 'gs_custode_tipo', true ) ) { continue; }
			delete_post_meta( $pid, 'gs_custode_tipo' );
			delete_post_meta( $pid, 'gs_custode_id' );
			delete_post_meta( $pid, 'gs_custode_team' );
			$piatti_liberati++;
		}
	}
```

**1c — Rimetti i commenti in ordine.** Nel pacchetto 3.298.0 il blocco `3d` sta fisicamente
*prima* del `3c` (le tre opzioni segnaposto). Sposta il `3d` dopo il `3c`, o rinumerali: è solo
estetica, ma chi legge questo file fra un anno segue i numeri.

**1d — `piatti_liberati` resta com'è**, nel log, nel messaggio di fine reset e nella cronologia
del pannello. Quella parte va bene ed è utile.

**1e — Controlla che non sia rimasto niente**: `grep -rn "rina_poletti" gaming-sfogline/` non
deve trovare più nulla.

## PARTE 2 — Quello che l'anteprima deve dire dei piatti

Questa parte va fatta lo stesso, anche senza eccezione: il Reset libera dei piatti, ed è una
cosa irreversibile che oggi l'anteprima non nomina affatto. Si guarda prima di premere, non
dopo nel log.

```php
/**
 * Quanti piatti in via d'estinzione tornerebbero liberi. Il Reset tiene i
 * piatti (sono catalogo) ma svuota il custode: chi legge l'anteprima deve
 * vederlo prima, non scoprirlo dopo — stesso motivo per cui l'anteprima
 * mostra le sfogline nel Cestino.
 */
function gs_reset_conteggio_piatti_da_liberare() {
	if ( ! post_type_exists( 'gs_piatto' ) ) { return 0; }
	$n = 0;
	foreach ( get_posts( array( 'post_type' => 'gs_piatto', 'post_status' => array( 'any', 'trash' ), 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $pid ) {
		if ( get_post_meta( $pid, 'gs_custode_tipo', true ) ) { $n++; }
	}
	return $n;
}
```

In `gs_reset_anteprima()`: `'piatti_da_liberare' => gs_reset_conteggio_piatti_da_liberare(),`

In `assets/js/gaming.js`, dentro `gsRenderResetAnteprima( d )`, dopo la tabella delle sfogline
nel Cestino:

```js
		if ( d.piatti_da_liberare ) {
			out += '<p>' + gsEsc( d.piatti_da_liberare ) + ' piatt' + ( 1 === d.piatti_da_liberare ? 'o' : 'i' )
				+ ' in via d\'estinzione tornerà libero: il piatto resta, la custode di prima no, '
				+ 'e chiunque potrà adottarlo di nuovo.</p>';
		}
```

## PARTE 3 — Una cosa da controllare, non da decidere

Ennio parlava dei Consigli, e quelli sono salvi. Ma vale la pena guardare, una volta sola, se
c'è **altro** scritto da lei che il Reset porterebbe via — per esempio nel Matterello Parlante
(`gs_voce`), che nella sua stessa intestazione si descrive come «archivio vocale di ricordi e
**consigli** registrati a voce», ed è fra i tipi che si cancellano.

Da fare su guru2, con la shell del sito, e **solo per guardare**:

```bash
# 1) trovare il suo account
wp eval 'foreach ( get_users( array( "fields" => array( "ID", "display_name" ) ) ) as $u ) { if ( false !== stripos( $u->display_name, "rina" ) ) { echo $u->ID . "  " . $u->display_name . "\n"; } }'

# 2) cosa perderebbe, per tipo (metti l'ID trovato sopra al posto di <ID>)
wp eval 'foreach ( gs_reset_tipi_da_cancellare() as $t ) { $ids = get_posts( array( "post_type" => $t, "post_status" => array( "any", "trash" ), "author" => <ID>, "posts_per_page" => -1, "fields" => "ids" ) ); if ( $ids ) { echo $t . ": " . count( $ids ) . "\n"; } }'
```

Se il secondo comando non stampa niente, è finita lì: non c'è altro di suo da salvare.
Se stampa qualcosa, **scrivi cosa hai trovato e fermati**: che si tenga o no è una decisione di
Ennio, non tua.

---

## Come si prova

Nel test dedicato, con dati veri:

1. Un piatto adottato da una sfoglina, uno adottato da una squadra e uno adottato da un account
   chiamato «Rina Poletti» → dopo il Reset **tutti e tre** risultano liberi, e
   `piatti_liberati` vale 3.
2. Un piatto senza custode → non viene contato.
3. `gs_reset_conteggio_piatti_da_liberare()` conta gli stessi piatti che il Reset poi libera:
   il numero dell'anteprima e quello del log devono coincidere.
4. Un Consiglio scritto dal titolare e uno scritto da una sfoglina → dopo il Reset ci sono
   **tutti e due**: è la verifica della richiesta di Ennio, e va scritta come test, non
   verificata a mente.
5. Nel browser su guru2: l'anteprima mostra la riga dei piatti, e il numero è quello vero.

## La consegna

`php -l` sui file toccati, graffe bilanciate nel JS. Versione **3.298.1** nei tre punti.
Changelog che dica **perché** l'eccezione è stata tolta: era nata da una frase interpretata
male, Ennio ha chiarito che parlava dei Consigli — che erano già tenuti tutti — e una regola
mai chiesta su un'operazione irreversibile non resta lì solo perché nel frattempo è stata
scritta.

## Una cosa da non fare

**Non eseguire il Reset**, né qui né su guru2. La prova a vuoto la legge Ennio, il backup lo fa
lui, il pulsante lo preme lui.
