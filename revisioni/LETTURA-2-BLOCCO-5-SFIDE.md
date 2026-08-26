# Seconda lettura · Blocco 5 — `voting.php` e `giuria-turno.php`

Le sfide della settimana e la Giuria a Turno: **è qui che si assegnano i premi più grossi
del gaming** — 100, 60 e 30 punti al podio di ogni sfida, 30 alla vincitrice di ogni turno di
giuria.

**Sei voci, V1-V3 e G1-G3. Una sola è alta**, ed è la stessa forma della chiusura di luglio.

E **due cose fatte bene** che vale la pena dire prima, perché sono il metro con cui giudico
il resto.

---

## Due cose giuste, e una è la migliore del plugin

**`gs_ajax_vota()` è la funzione meglio protetta che ho letto in tutto il progetto**
(`voting.php:363`):

```php
	if ( ! add_post_meta( $sfoglia_id, 'gs_voto_uid_' . $user_id, 1, true ) ) {
		wp_send_json_error( array( 'message' => 'Hai già votato questa sfoglia.' ) );
	}
```

**«Prenota» il diritto di votare prima di usarlo, e guarda il risultato.** Ogni voto è una
riga a sé, mai in competizione con le altre. Il commento spiega da dove viene: *«un vero
test di carico il 14/08/2026 ha dimostrato che il vecchio schema perdeva fino a metà dei
voti»*.

**È l'unico punto del plugin dove la protezione nasce da una misura invece che da un
ragionamento** — ed è per questo che è la più solida.

**E in `gs_giuria_chiudi()` il marcatore è scritto prima degli effetti** (`giuria-turno.php:225`):

```php
	update_post_meta( $id, 'gs_giuria_chiusa', '1' );   // ← PRIMA
	$badge_key = 'giuria_' . $id;                       // ← poi badge e punti
```

**È la regola della chiusura del mese, applicata giusta**, in un file che nessuno ci aveva
chiesto di correggere.

**Il che rende più stridente la voce che segue**, perché nel file gemello è fatta al
contrario.

---

# V1 · ALTO — I premi delle sfide si assegnano prima di segnare la sfida come chiusa

**File:** `includes/voting.php:612-635` (`gs_close_expired_challenges()`) — VERIFICATO

```php
	foreach ( $sfide as $sfida ) {
		if ( get_post_meta( $sfida->ID, 'gs_chiusa', true ) ) {
			continue;
		}
		…
		gs_award_challenge_prizes( $sfida->ID );          // ← prima i premi
		update_post_meta( $sfida->ID, 'gs_chiusa', 1 );   // ← poi il marcatore
	}
```

**È esattamente la forma della chiusura del mese prima della correzione A1**: si fa il
lavoro, e solo alla fine si scrive che è stato fatto.

## Cosa costa

`gs_award_challenge_prizes()` dà **100 + 60 + 30 = 190 punti**. Se questo giro parte due
volte prima che il marcatore sia scritto, **sono 380**.

E i modi per farlo partire due volte ci sono tutti:

- **il cron giornaliero che si sovrappone** — WP-Cron parte dalle visite, e su un sito con
  traffico due richieste possono innescarlo quasi insieme;
- **un errore a metà del giro** (una `wp_mail` che va in timeout dentro `gs_podio_sfida`, la
  richiesta che muore): i premi delle prime due sono già dati, `gs_chiusa` non è scritto, e
  **il giorno dopo si ricomincia da capo**.

**Il secondo caso non è teorico: è il difetto di luglio, identico.** Lì fu una data, qui è
un errore a metà — ma la causa è la stessa, il marcatore in fondo.

## Correzione

**Spostare il marcatore prima**, come è già stato fatto per la chiusura del mese e come è già
fatto in `gs_giuria_chiudi()` **nel file accanto**:

```php
		// Il marcatore va scritto PRIMA di assegnare i premi, non dopo: sono
		// 190 punti (100+60+30) e un giro interrotto a metà — o due giri del
		// cron sovrapposti — li darebbe due volte. È la stessa lezione della
		// chiusura del mese (luglio 2026), e in giuria-turno.php:225 è già
		// applicata così.
		update_post_meta( $sfida->ID, 'gs_chiusa', 1 );
		gs_award_challenge_prizes( $sfida->ID );
```

**Compromesso da dichiarare:** se l'assegnazione si interrompe a metà, la sfida resta chiusa
e **le ultime classificate non ricevono il premio**, senza che nessuno se ne accorga. È il
verso giusto — meglio un premio mancato e visibile a chi guarda la classifica che 190 punti
regalati e invisibili — **ma va aggiunto un avviso**:

```php
	if ( function_exists( 'gs_inbox_crea' ) ) {
		gs_inbox_crea(
			'Sfida chiusa: ' . get_the_title( $sfida_id ),
			'Premi assegnati a ' . $premiate . ' sfogline. Se il numero non torna, controlla la classifica.',
			array()
		);
	}
```

in fondo a `gs_award_challenge_prizes()`, contando quante ne ha premiate.

---

# V2 · MEDIO — A parità di media, il primo e il secondo posto li decide il caso

**File:** `includes/voting.php:593-604` (`gs_challenge_leaderboard()`) e `:639`
(`gs_award_challenge_prizes()`) — VERIFICATO

```php
	usort( $sfoglie, function ( $a, $b ) use ( $medie ) {
		return $medie[ $b->ID ] <=> $medie[ $a->ID ];
	} );
```

Se due sfoglie hanno **la stessa media**, `usort` le mette in un ordine qualsiasi — e chi
capita prima prende **100 punti**, chi capita dopo **60**.

**E le parità sono probabili, non rare.** Il voto è su 4 criteri da 1 a 5, quindi ogni voto
vale un numero intero da 4 a 20, e la media è arrotondata a due decimali. **Con due o tre
votanti a testa, due sfoglie con la stessa media capitano spesso.**

Per una gara fra persone che si conoscono, **«perché lei prima e io seconda se abbiamo lo
stesso punteggio?» è una domanda a cui bisogna poter rispondere.**

## Correzione

**Un secondo criterio dichiarato**, così l'ordine è una regola e non un caso:

```php
	usort( $sfoglie, function ( $a, $b ) use ( $medie ) {
		if ( $medie[ $a->ID ] !== $medie[ $b->ID ] ) {
			return $medie[ $b->ID ] <=> $medie[ $a->ID ];
		}
		// A parità di media vince chi ha ricevuto PIÙ voti: una media di 18
		// su cinque giudizi vale più della stessa media su uno solo. A parità
		// anche di quello, vince chi ha pubblicato prima. Senza questa
		// regola l'ordine fra due pari merito lo decide usort, cioè il caso —
		// e sono 100 punti contro 60 (26/08/2026).
		$va = count( gs_sfoglia_tutti_i_voti( $a->ID ) );
		$vb = count( gs_sfoglia_tutti_i_voti( $b->ID ) );
		if ( $va !== $vb ) { return $vb <=> $va; }
		return strtotime( $a->post_date ) <=> strtotime( $b->post_date );
	} );
```

**E la regola va scritta dove le sfogline la leggono** — nel riquadro di aiuto della pagina
delle sfide. Una regola che esiste solo nel codice non è una regola: è una coincidenza che si
ripete.

**Chiedi a Ennio se il criterio è quello giusto.** «Più voti» premia chi ha partecipato di
più alla vita del sito; si potrebbe preferire «chi ha pubblicato prima», o lasciare il pari
merito con **due primi posti e nessun secondo**. **È una scelta sua, non tecnica.**

---

# V3 · BASSO — La sfida si chiude a un'ora sbagliata

**File:** `includes/voting.php:613, 626` — VERIFICATO

```php
	$now  = current_time( 'timestamp' );
	…
	$fine = strtotime( get_post_meta( $sfida->ID, 'gs_data_fine', true ) );
	if ( ! $fine || $now < $fine ) { continue; }
```

**È la terza volta che incontro questa coppia** (P3 sulle scadenze, L4 sui percorsi
stagionali): `current_time('timestamp')` è UTC **più lo scarto di WordPress**, `strtotime()`
lavora nel fuso del **server**. La sfida si chiude **una o due ore prima o dopo** quello che
dice la data.

**Correzione:** la stessa già scritta due volte — confrontare due date passate entrambe per
`strtotime()`, oppure `strtotime( $data . ' 23:59:59' )` contro
`strtotime( current_time( 'Y-m-d H:i:s' ) )`.

**Quando si corregge questa, si cerchino tutte insieme:** `grep -n "current_time( 'timestamp' )"`
sul plugin intero, e per ognuna si guardi se è confrontata con uno `strtotime()`. **Sono
tutte lo stesso errore e conviene chiuderle in una passata.**

---

## Una cosa che ho verificato e NON è un problema

Il valore `gs_media_voti` salvato su ogni sfoglia (`voting.php:371`) **è un
leggi-calcola-scrivi** e due voti simultanei possono lasciarlo indietro di uno.

**Ma non tocca i premi**: `gs_challenge_leaderboard()` **ricalcola** la media con
`gs_calc_media_voti()` invece di leggere il valore salvato. Quel valore serve solo a mostrare
un numero, e si sistema da solo al voto successivo.

**Lo scrivo perché era il primo posto dove sono andato a guardare**, e sarebbe stato il
difetto più grave del blocco. Non c'è.

---

# G1 · MEDIO — I 30 punti della giuria stanno fuori dal controllo del badge

**File:** `includes/giuria-turno.php:226-240` — VERIFICATO

```php
	$badge_key = 'giuria_' . $id;
	$owned     = gs_get_user_badges( $uid );
	if ( ! in_array( $badge_key, $owned, true ) ) {
		…badge, etichetta, storico…
	}

	gs_add_points( $uid, gs_get_points_value( 'giuria_vinta', 30 ), … );   // ← fuori dall'if
```

**Il badge è protetto, i punti no.** Chiunque richiami `gs_giuria_chiudi()` su un turno la
cui vincitrice ha già il badge le dà **altri 30 punti**.

Oggi il marcatore `gs_giuria_chiusa` (riga 225) impedisce il caso normale — **ed è per questo
che è medio e non alto.** Ma è la stessa disposizione di **L2**, e lì bastavano due socie
simultanee per aggirarla.

**Correzione:** portare `gs_add_points()` **dentro** l'`if`, insieme al badge. Chi ha già il
badge di quel turno ha già avuto i punti di quel turno.

---

# G2 · BASSO — La classifica della giuria ricalcola la media dentro l'ordinamento

**File:** `includes/giuria-turno.php:197-203` — VERIFICATO

```php
	usort( $opere, function ( $a, $b ) use ( $id ) {
		return gs_giuria_media_opera( $id, $b['id'] ) <=> gs_giuria_media_opera( $id, $a['id'] );
	} );
```

**È esattamente l'inefficienza già corretta nel file gemello**, e nel gemello c'è pure il
commento che lo racconta (`voting.php:594`):

> *«Calcola la media una sola volta per sfoglia, poi ordina. Prima veniva ricalcolata dentro
> il confronto di usort (O(n log n) letture di meta).»*

**La lezione è stata imparata in un file e non portata nell'altro.** Correzione: le stesse
quattro righe, calcolare le medie una volta in un array e ordinare su quello.

**Priorità bassa** — i turni di giuria hanno poche opere — **ma è gratis, e lascia i due file
coerenti.**

---

# G3 · Il pari merito, anche qui

`gs_giuria_chiudi()` prende `$classifica[0]` e basta. **Stesso problema di V2**, stessa
correzione, stessa domanda a Ennio. **Vanno decisi insieme**: sarebbe strano avere due regole
diverse per due gare dello stesso sito.

---

# Riepilogo del blocco

| # | Cosa | File:riga | Gravità |
|---|---|---|---|
| **V1** | Premi della sfida prima del marcatore: 190 punti raddoppiabili | `voting.php:632` | **Alto** |
| V2 | Pari merito sul podio: 100 o 60 lo decide il caso | `voting.php:593` | Medio |
| V3 | Fuso orario sulla chiusura della sfida | `voting.php:613` | Basso |
| **G1** | I 30 punti della giuria fuori dal controllo del badge | `giuria-turno.php:240` | Medio |
| G2 | Media ricalcolata dentro l'ordinamento | `giuria-turno.php:197` | Basso |
| G3 | Pari merito, come V2 | `giuria-turno.php:222` | Medio |

**V1 e G1 vanno insieme:** sono la stessa domanda — *«il marcatore prima o dopo, e i punti
dentro o fuori il controllo?»* — con due risposte diverse in due file che fanno la stessa
cosa.

**V2 e G3 vanno insieme e passano da Ennio**, perché sono una regola di gara e non un difetto.

---

## Cosa dice questo blocco, in generale

**Il plugin sa fare tutte e tre le cose giuste** — il marcatore prima degli effetti, i punti
dentro il controllo, la prenotazione atomica. **Le fa tutte e tre, in questi due file, in
punti diversi.**

`gs_ajax_vota()` è protetta meglio di qualsiasi altra cosa nel progetto.
`gs_giuria_chiudi()` scrive il marcatore nel posto giusto. **E a venti righe di distanza,
`gs_close_expired_challenges()` lo scrive nel posto sbagliato, e `gs_giuria_chiudi()` lascia
i punti fuori dal controllo.**

**Non è competenza che manca: è che ogni funzione è stata scritta il giorno in cui serviva, e
quello che si era capito il mese prima non è tornato indietro a sistemare le altre.** È la
stessa frase che ho scritto dopo il blocco 2, ed è la ragione per cui questa seconda lettura
serve.

---

## Cosa leggo adesso

`sondaggi.php`, `sfoglia-misurata.php`, `madrina.php` e `indovina.php` — 1.575 righe, tutte
con punti dentro. **Dopo quelle restano undici file dei quindici, e sono i più piccoli.**
