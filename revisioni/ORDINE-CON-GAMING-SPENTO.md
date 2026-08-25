# Il gaming è spento — cosa cambia, e la prova che adesso si può fare davvero

Ennio ha oscurato il gaming (25/08/2026) e ha detto: *«sistemiamo tutto al meglio
possibile, deve essere tutto perfetto»*.

**Non è solo "abbiamo più tempo".** Cambiano tre cose concrete, e una di queste sblocca la
domanda rimasta aperta ieri.

---

## Cosa cambia

**1. Le prove si possono fare sul sito vero.** Finora ogni verifica in produzione era
vincolata dal fatto che c'erano sfogline dentro: per questo la rettifica di luglio è stata
un file usa-e-getta, e per questo ieri ho chiesto a Ennio di guardare con gli occhi invece
di far girare qualcosa. **Con il gaming spento questo vincolo non c'è più.** La domanda sul
tema — quella che decide se il Tavolo di Lavoro regala punti — si chiude oggi, non «con
calma».

**2. Non c'è più niente che debba essere corretto di corsa.** La scadenza del 1° ottobre era
l'unica cosa che spingeva. Da adesso in poi **nessuna correzione va installata prima di
essere stata provata**, e nessuna va accorpata a un'altra per far prima. Un giro per volta,
come si è fatto finora, ma senza la fretta che ha fatto arrivare in produzione il difetto
di luglio.

**3. La seconda lettura viene prima delle correzioni che restano.** Questo lo avevo già
proposto nel piano del 22 agosto e vale ancora di più adesso: prima di riscrivere i Giri
3-6, conviene sapere **tutto** quello che c'è. Se `percorsi-lezioni.php` o `area-pro.php`
nascondono qualcosa di grosso, è meglio scoprirlo prima di aver già corretto attorno.

**Quello che resta in coda non cambia**, cambia solo che non ha più una data addosso.

---

## L'ordine, da qui in avanti

| | Cosa | Chi |
|---|---|---|
| **1** | **La diagnosi sul tema** (qui sotto) — decide molte altre cose | Claude Code |
| **2** | La data di P3 sui tre file — già specificata nella verifica della 3.277.0 | Claude Code |
| **3** | Quello che la diagnosi dice di fare | Claude Code |
| **4** | La seconda lettura fino in fondo: `area-pro`, `percorsi-lezioni`, `lezioni-video`, `sfogline-extra`, e il resto | io |
| **5** | Giri 3-6 e tutto quello che la lettura tira fuori, un giro per volta | Claude Code |
| **6** | Il pannello nuovo | Ennio + Claude Code |
| **7** | La prova generale su Local con dati veri copiati, prima di riaccendere | tutti |

Il punto **4 non aspetta il 3**: sono due lavori diversi e vanno in parallelo, come è stato
finora.

---

# La diagnosi sul tema — fallo per primo

## Perché non basta più il ragionamento

Ieri hai scritto che il tema altera le query con `author`, e l'hai verificato confrontando
l'SQL. Ti credo, ed è la prova giusta. Ma **l'hai visto su guru2**, e la conclusione che
conta riguarda il sito vero:

- se succede anche in produzione, **`gs_tavolo_di_oggi()` non sta fermando niente** e ogni
  sfoglina può prendere +5 punti a ripetizione (`tavolo.php:57` e `:172`);
- se **non** succede in produzione, allora guru2 e il sito vero non si comportano allo
  stesso modo — e questo rende inaffidabili **tutte** le prove fatte su guru2 finora, che è
  un problema più grande del primo.

**Non è una domanda a cui si risponde guardando: è una domanda a cui si risponde
misurando.** E adesso si può misurare, perché non c'è nessuno dentro.

## Il file usa-e-getta

Stesso schema di `rettifica-luglio.php`, che ha già funzionato: si installa, gira una volta
quando Ennio apre il pannello, scrive il risultato in Posta interna, e **si toglie**.

Crea `includes/diagnosi-author.php` e aggiungilo al caricatore:

```php
<?php
/**
 * diagnosi-author.php — FILE TEMPORANEO, da togliere dopo la lettura.
 *
 * Risponde a una domanda sola: sul sito vero, una get_posts() con il parametro
 * "author" restituisce quello che dovrebbe? Su guru2 non lo faceva (verificato
 * il 25/08/2026 confrontando l'SQL generato), e da questa risposta dipende se
 * gs_tavolo_di_oggi() sta davvero impedendo la seconda foto del giorno.
 *
 * Gira una volta sola, in wp-admin, e scrive tutto in Posta interna.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_diagnosi_author_una_volta', 99 );
function gs_diagnosi_author_una_volta() {
	if ( ! is_admin() ) { return; }
	if ( get_option( 'gs_diagnosi_author_fatta' ) ) { return; }

	// Marcatore PRIMA degli effetti: se qualcosa qui sotto va storto, non deve
	// ripartire a ogni caricamento di pagina.
	update_option( 'gs_diagnosi_author_fatta', current_time( 'mysql' ) );

	$righe = array();

	// --- 1) Chi è agganciato a pre_get_posts -------------------------------
	$righe[] = '=== Chi altera le query (pre_get_posts) ===';
	global $wp_filter;
	if ( empty( $wp_filter['pre_get_posts'] ) ) {
		$righe[] = 'Nessuno.';
	} else {
		foreach ( $wp_filter['pre_get_posts']->callbacks as $priorita => $lista ) {
			foreach ( $lista as $cb ) {
				$f = $cb['function'];
				if ( is_array( $f ) ) {
					$etichetta = ( is_object( $f[0] ) ? get_class( $f[0] ) : (string) $f[0] ) . '::' . $f[1];
				} elseif ( is_string( $f ) ) {
					$etichetta = $f;
				} else {
					$etichetta = 'funzione anonima';
				}
				$righe[] = 'priorità ' . $priorita . ' — ' . $etichetta;
			}
		}
	}

	// --- 2) La prova vera, sul Tavolo di Lavoro ----------------------------
	$righe[] = '';
	$righe[] = '=== La prova: gs_tavolo con e senza "author" ===';

	$ultima = get_posts( array(
		'post_type'        => 'gs_tavolo',
		'post_status'      => 'publish',
		'posts_per_page'   => 1,
		'orderby'          => 'date',
		'order'            => 'DESC',
		'suppress_filters' => true,
	) );

	if ( ! $ultima ) {
		$righe[] = 'Nessuna foto sul Tavolo di Lavoro: prova non eseguibile qui.';
	} else {
		$uid  = (int) $ultima[0]->post_author;
		$chi  = get_userdata( $uid );
		$righe[] = 'Sfoglina di prova: ' . ( $chi ? $chi->display_name : '#' . $uid ) . ' (id ' . $uid . ')';

		// Quante ne ha davvero, contate senza passare da "author".
		$vere = 0;
		foreach ( get_posts( array(
			'post_type'        => 'gs_tavolo',
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'suppress_filters' => true,
		) ) as $p ) {
			if ( (int) $p->post_author === $uid ) { $vere++; }
		}
		$righe[] = 'Foto che ha davvero (contate in PHP): ' . $vere;

		// Quante ne trova la query con "author", e con quale SQL.
		$sql     = '';
		$cattura = function ( $s ) use ( &$sql ) { $sql = $s; return $s; };
		add_filter( 'posts_request', $cattura, PHP_INT_MAX );
		$con_author = get_posts( array(
			'post_type'        => 'gs_tavolo',
			'post_status'      => 'publish',
			'author'           => $uid,
			'posts_per_page'   => -1,
			'suppress_filters' => true,
		) );
		remove_filter( 'posts_request', $cattura, PHP_INT_MAX );

		$righe[] = 'Foto che trova la query con "author": ' . count( $con_author );
		$righe[] = '';
		$righe[] = 'SQL generato:';
		$righe[] = $sql;
		$righe[] = '';
		$righe[] = count( $con_author ) === $vere
			? '>>> ESITO: la query con "author" funziona. Il problema di guru2 NON si ripete qui.'
			: '>>> ESITO: la query con "author" NON funziona (' . count( $con_author ) . ' invece di ' . $vere . ').';

		// --- 3) La conseguenza diretta ------------------------------------
		$righe[] = '';
		$righe[] = '=== Conseguenza: gs_tavolo_di_oggi() ===';
		if ( function_exists( 'gs_tavolo_di_oggi' ) ) {
			$oggi_id = gs_tavolo_di_oggi( $uid );
			$oggi_ymd = date( 'Y-m-d', current_time( 'timestamp' ) );
			$ha_caricato_oggi = substr( $ultima[0]->post_date, 0, 10 ) === $oggi_ymd
				&& (int) $ultima[0]->post_author === $uid;
			$righe[] = 'La sua foto più recente è del ' . substr( $ultima[0]->post_date, 0, 10 ) . ' (oggi è ' . $oggi_ymd . ').';
			$righe[] = 'gs_tavolo_di_oggi() risponde: ' . ( $oggi_id ? 'ha già caricato oggi (blocca)' : 'non ha caricato oggi (lascia passare)' );
			if ( $ha_caricato_oggi && ! $oggi_id ) {
				$righe[] = '>>> IL BLOCCO NON FUNZIONA: ha caricato oggi e la funzione la lascia passare.';
			}
		}
	}

	if ( function_exists( 'gs_inbox_crea' ) ) {
		gs_inbox_crea( 'Diagnosi query "author" — leggere e riportare', implode( "\n", $righe ) );
	}
}
```

## Come si legge il risultato

Il messaggio arriva in **Posta interna** con l'oggetto *«Diagnosi query "author"»*.
Riporta **tutto** a Ennio e a me, l'SQL compreso: è quello che dice cosa sta succedendo
davvero, e senza non si può decidere niente.

**Tre esiti possibili, tre strade diverse:**

- **la query funziona in produzione** → il problema è solo di guru2. Allora la domanda
  diventa *«perché guru2 è diverso?»*, e va risolta prima di fidarsi di qualunque altra
  prova fatta lì. Le due funzioni dei partner restano corrette come sono (il giro su
  `gs_art_elenco()` è comunque valido, solo non era necessario).
- **la query non funziona, ma solo per certi tipi** → l'SQL dirà quale parte è stata
  cambiata, e si corregge mirato.
- **la query non funziona mai** → si applica la correzione unica già proposta nella verifica
  della 3.277.0 (`pre_get_posts` a `PHP_INT_MAX` che rimette il `post_type` richiesto, più
  `gs_tipo_atteso` nelle dieci query), **e** la seconda serratura su `gs_tavolo_di_oggi()`.

## Dopo

**Togli il file e l'opzione:**

```
wp option delete gs_diagnosi_author_fatta
```

o, senza riga di comando, lascia perdere l'opzione — è innocua — ma **il file va tolto dal
caricatore e dal pacchetto**, come è stato fatto con `rettifica-luglio.php`.

**Non installare la 3.277.0 con dentro questa diagnosi in modo permanente.** È un file
usa-e-getta e deve restare tale.

---

## Una nota sul metodo, adesso che c'è tempo

Il difetto di luglio è arrivato in produzione perché una correzione è stata installata
prima di aver chiesto *«e se quello stato non è mai stato scritto?»*. La data sbagliata di
P3 è arrivata nello zip perché i confini sono stati spostati senza rifare il conto.

Tutte e due erano cose che si vedevano **facendo girare il conto con date vere**, non
rileggendo il codice. Con il gaming spento e nessuna scadenza addosso, quella è la cosa che
conviene fare sempre, per ogni correzione che tocca una data, un saldo o un contatore:
**non "ho controllato che è giusto", ma "l'ho fatto girare e ho guardato cosa esce".**

È l'unica differenza fra un difetto trovato in una prova e un difetto trovato da una
sfoglina.
