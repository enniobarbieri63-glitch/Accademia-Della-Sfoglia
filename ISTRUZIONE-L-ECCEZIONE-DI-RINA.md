# Istruzione: rendere visibile l'eccezione di Rina sui piatti

**Per Claude Code Ennio — 27/08/2026, scritta su 3.298.0**

In 3.298.0 il Reset libera il custode di tutti i piatti in via d'estinzione **tranne** quelli
adottati da Rina Poletti, che restano suoi (decisione di Ennio). La regola è giusta e il codice
la applica. Il problema è un altro: **quella regola non si vede da nessuna parte prima di
premere, e può fallire in silenzio.**

`gs_e_rina_poletti()` confronta il nome così:

```php
return $u && 'Rina Poletti' === trim( $u->display_name );
```

Confronto esatto, maiuscole comprese, un solo spazio. Se l'account si chiama `RINA POLETTI`,
`Rina`, `Rina Poletti – Accademia`, o ha due spazi, o uno spazio unificatore arrivato da un
copia-incolla, la funzione risponde **falso** e il Reset libera anche i piatti di Rina. Non dà
errore. Non compare nell'anteprima, che dei piatti non parla affatto. Si scopre dopo.

Tre modifiche, tutte in `includes/reset.php` tranne l'ultima.

---

## 1. Il confronto non deve dipendere da maiuscole e spazi

Sostituisci tutta `gs_e_rina_poletti()` con queste due funzioni:

```php
/**
 * Riconosce l'account di Rina Poletti per nome visualizzato, non per ID: un
 * ID utente non è portabile fra guru2 e il sito vero, un nome sì. Usata solo
 * dall'eccezione sui piatti in via d'estinzione (decisione di Ennio,
 * 27/08/2026).
 *
 * Il confronto è tollerante su maiuscole e spazi di proposito: se fosse
 * esatto, un account scritto "RINA POLETTI" o con due spazi in mezzo
 * risponderebbe NO in silenzio, e il Reset le libererebbe tutti i piatti
 * senza che niente lo dica. Il nome vero sta in gs_rina_nome(), in un
 * punto solo: se un giorno l'account si chiamasse diversamente, si cambia lì.
 */
function gs_rina_nome() {
	return 'Rina Poletti';
}

function gs_e_rina_poletti( $uid ) {
	$uid = (int) $uid;
	if ( ! $uid ) { return false; }
	$u = get_userdata( $uid );
	if ( ! $u ) { return false; }
	$normalizza = function ( $s ) {
		$s = function_exists( 'mb_strtolower' ) ? mb_strtolower( (string) $s, 'UTF-8' ) : strtolower( (string) $s );
		// \s in modalità unicode prende anche lo spazio unificatore (U+00A0),
		// quello che arriva incollato da Word o da una pagina web.
		return trim( preg_replace( '/\s+/u', ' ', $s ) );
	};
	return $normalizza( $u->display_name ) === $normalizza( gs_rina_nome() );
}

/**
 * L'ID dell'account di Rina, se un account con quel nome esiste davvero.
 * Serve all'anteprima per dire subito «quel nome non trova nessuno», che è
 * il modo in cui questa eccezione può fallire senza farsi notare.
 */
function gs_rina_poletti_uid() {
	foreach ( get_users( array( 'fields' => 'ID' ) ) as $uid ) {
		if ( gs_e_rina_poletti( $uid ) ) { return (int) $uid; }
	}
	return 0;
}
```

## 2. L'anteprima deve dire quanti piatti restano suoi

Aggiungi la funzione, accanto alle altre del riepilogo:

```php
/**
 * I piatti in via d'estinzione: quanti tornano liberi e quanti restano a
 * Rina. Una regola che decide qualcosa di irreversibile va guardata PRIMA
 * di premere, non letta dopo nel log — stesso motivo per cui l'anteprima
 * mostra le sfogline nel Cestino.
 */
function gs_reset_riepilogo_piatti() {
	$out = array(
		'liberati'      => 0,
		'restano_rina'  => 0,
		'rina_trovata'  => (bool) gs_rina_poletti_uid(),
		'nome_cercato'  => gs_rina_nome(),
	);
	if ( ! post_type_exists( 'gs_piatto' ) ) { return $out; }
	foreach ( get_posts( array( 'post_type' => 'gs_piatto', 'post_status' => array( 'any', 'trash' ), 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $pid ) {
		$tipo = get_post_meta( $pid, 'gs_custode_tipo', true );
		if ( ! $tipo ) { continue; }
		if ( 'sfoglina' === $tipo && gs_e_rina_poletti( (int) get_post_meta( $pid, 'gs_custode_id', true ) ) ) {
			$out['restano_rina']++;
		} else {
			$out['liberati']++;
		}
	}
	return $out;
}
```

e in `gs_reset_anteprima()`, accanto alle altre voci:

```php
		'piatti'           => gs_reset_riepilogo_piatti(),
```

## 3. Il riquadro nell'anteprima, con l'avviso

In `assets/js/gaming.js`, dentro `gsRenderResetAnteprima( d )`, subito **dopo** la tabella delle
sfogline nel Cestino:

```js
		if ( d.piatti && ( d.piatti.liberati || d.piatti.restano_rina ) ) {
			out += '<h5>Piatti in via d\'estinzione</h5><ul>';
			out += '<li>' + gsEsc( d.piatti.liberati ) + ' tornano liberi: chiunque potrà adottarli</li>';
			out += '<li>' + gsEsc( d.piatti.restano_rina ) + ' restano a ' + gsEsc( d.piatti.nome_cercato ) + '</li>';
			out += '</ul>';
			if ( ! d.piatti.rina_trovata ) {
				out += '<p style="color:#b03a2e"><strong>Attenzione:</strong> nessun account si chiama "'
					+ gsEsc( d.piatti.nome_cercato ) + '". L\'eccezione non risparmierebbe nessun piatto: '
					+ 'controlla il nome visualizzato dell\'account prima di procedere.</p>';
			}
		}
```

---

## Quello che NON devi decidere tu

**Una sola cosa, e va confermata prima di considerare chiusa la decisione 4.**

Ennio ha scritto: *«cancella tutto, tranne i consigli di Rina Poletti»*. Quella frase è stata
tradotta in codice come **«i piatti adottati da Rina restano suoi»**. Ma «i consigli di Rina
Poletti» può voler dire anche, più alla lettera, **i Consigli** — il tipo di contenuto
`gs_consiglio` — scritti da lei.

Se voleva dire quelli, **è già coperto**: la decisione 2 tiene tutti i Consigli, i suoi
compresi. In quel caso l'eccezione sui piatti è una regola in più, che Ennio potrebbe non aver
chiesto — e una regola in più su un'operazione irreversibile va confermata, non lasciata lì
perché nel frattempo è stata scritta.

**Chiediglielo in una riga**, e riporta la risposta:

> *«Quando hai detto "tranne i consigli di Rina Poletti": intendevi i Consigli scritti da lei
> (che restano comunque, li teniamo tutti), o i piatti in via d'estinzione che ha adottato
> lei?»*

Se la risposta è «i Consigli», togli l'eccezione: `gs_e_rina_poletti()`, `gs_rina_nome()`,
`gs_rina_poletti_uid()` e il ramo `continue` dentro il punto 3d, e i piatti si liberano tutti
come nella proposta originale. Se è «i piatti», resta tutto e questo documento è già la sua
correzione.

---

## Come si prova

Nel test dedicato, con dati veri:

1. Un piatto adottato da un account chiamato **`rina poletti`** (minuscolo) → dopo il Reset
   **resta suo**. Con il confronto di oggi si libererebbe: è la prova che serve.
2. Un piatto adottato da un account chiamato **`Rina  Poletti`** (due spazi) → resta suo.
3. Nessun account chiamato Rina, un piatto adottato da un'altra sfoglina → l'anteprima
   restituisce `rina_trovata = false`, e il piatto si libera.
4. `gs_reset_riepilogo_piatti()` conta giusto: un piatto di Rina, uno di un'altra sfoglina, uno
   di una squadra → `restano_rina = 1`, `liberati = 2`.
5. Nel browser su guru2: l'anteprima mostra il riquadro dei piatti, e se rinomini
   temporaneamente l'account di Rina compare l'avviso rosso.

## La consegna

`php -l` sui file toccati, graffe bilanciate nel JS. Versione **3.298.1** nei tre punti.
Changelog che dica **cosa sarebbe successo**: che un nome scritto con una maiuscola diversa
avrebbe fatto perdere a Rina tutti i suoi piatti, senza un errore e senza una riga
nell'anteprima.

## Una cosa da non fare

**Non eseguire il Reset**, né qui né su guru2. La prova a vuoto la legge Ennio, il backup lo fa
lui, il pulsante lo preme lui.
