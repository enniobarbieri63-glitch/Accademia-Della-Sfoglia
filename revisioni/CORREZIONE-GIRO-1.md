# Giro 1: 1a va bene, 1b va corretta prima di installare

Il lavoro è fatto bene e le verifiche sono quelle giuste. Ma **1b è agganciata alla cosa
sbagliata**, e la prova che hai fatto su guru2 lo mostra già — solo che è stata letta come
un successo invece che come un sintomo.

**Non installare niente finché non hai applicato la correzione qui sotto.**

## Il problema

Hai scritto, ed è la frase che conta:

> su «Le Sfogline» (guru2): il nastro piccolo non c'è più, come previsto
>
> quella pagina di prova ha solo `[gs_sfogline]`, non lo shortcode del nastro grande

Messe insieme, quelle due frasi dicono che **su guru2 quella pagina adesso non ha nessun
nastro: né il piccolo, che hai tolto, né il grande, che lì non c'è.** Non è "come previsto":
è una pagina rimasta senza niente.

La causa è che il controllo si fida di `get_option( 'gs_page_sfogline' )`, che è
**la pagina creata dal plugin** (`gaming-sfogline.php:329`, titolo «Le Sfogline», contenuto
`[gs_sfogline]`). Ma il nastro grande non sta lì per costruzione: **ce l'ha incollato a mano
Ennio**, e non è detto che l'abbia incollato in quella pagina.

Le conseguenze in produzione, se le due pagine non coincidono:

- sulla pagina dove sta davvero il nastro grande, **il doppio calcolo resta**: 1b non ha
  corretto niente, e il difetto che dovevamo eliminare è ancora lì;
- sulla pagina del plugin, **sparisce il nastro piccolo senza che compaia nulla al suo
  posto** — esattamente quello che si vede su guru2.

Il mio documento ti ha fatto agganciare la correzione all'id della pagina. **Era la scelta
sbagliata: l'ho scritta io, è un mio errore, e va corretta.**

## La correzione

Non chiedere *"sono sulla pagina numero N?"*, ma **"su questa pagina c'è già il nastro
grande?"**. È la domanda vera, e si risponde da sola per sempre — anche se Ennio domani
sposta lo shortcode su un'altra pagina, o lo toglie.

In `gs_render_nastro_vetrine()`, **sostituisci** il blocco che avevi aggiunto:

```php
	$pagina_sfogline = (int) get_option( 'gs_page_sfogline' );
	if ( $pagina_sfogline && is_page( $pagina_sfogline ) ) {
		return;
	}
```

**con questo:**

```php
	// Dove c'è già il nastro grande, non montare anche il piccolo: si
	// sovrapporrebbero. Prima questo veniva evitato nascondendo il piccolo
	// col CSS (gaming.css:2174, body.page-id-64342), che però lo faceva
	// comunque calcolare — una scansione completa della tabella utenti
	// buttata via a ogni visita di quella pagina.
	// Il controllo guarda il CONTENUTO e non l'id della pagina: lo shortcode
	// del nastro grande è incollato a mano, quindi può stare su qualunque
	// pagina, e l'id cambia da un sito all'altro.
	if ( is_singular() ) {
		$post_corrente = get_post();
		if ( $post_corrente && has_shortcode( (string) $post_corrente->post_content, 'gs_nastro_grande_sfogline' ) ) {
			return;
		}
	}
```

`has_shortcode()` è una funzione di WordPress, non serve aggiungere niente.

### Perché questa versione è migliore

- **Funziona su qualsiasi sito** senza sapere gli id: guru2, Local, produzione.
- **Non spegne mai un nastro senza sostituirlo**: salta il piccolo solo dove il grande c'è
  davvero. Su guru2, dopo questa correzione, la pagina di prova tornerà ad avere il nastro
  piccolo — ed è giusto così, visto che lì il grande non c'è.
- **Non va aggiornata** se Ennio sposta lo shortcode.

## Come verificarla — la prova che prima mancava

1. **Su guru2**, apri la pagina «Le Sfogline»: il nastro piccolo **deve essere tornato**
   (lì il grande non c'è). Se resta assente, il controllo non sta funzionando.
2. **Crea su guru2 una pagina di prova** con dentro `[gs_nastro_grande_sfogline]` e aprila:
   lì il nastro piccolo **non deve esserci**, e il grande sì. Questa è la prova che su
   guru2 non era mai stata fatta, ed è quella che conta.
3. Con Query Monitor su quella pagina di prova: la scansione utenti deve comparire
   **una volta sola**.
4. Home e un'altra pagina qualsiasi: nastro piccolo presente, come già verificato.

## Due note minori sul resto del report

**Il conteggio delle query.** «14 al primo giro, 0 al secondo» è la direzione giusta, ma
lo zero non è esatto: rileggere un `transient` costa comunque una o due letture
(`_transient_` e `_transient_timeout_`, che non sono autocaricate). Probabilmente hai
contato le query del solo nastro, che è la misura sensata — ma **riporta il totale della
pagina**, non il delta di una funzione: è quello che dice se il sito è più leggero davvero.
Non cambia nulla nel codice.

**Le 128 pillole sulla home di guru2.** Quel numero corrisponde a due piste
(18 voci × sponzor intercalato × 2 giri × 2 file). Vuol dire che **guru2 è in modalità
"doppio"**, mentre il sito vero è a corsia singola. La verifica resta valida — il punto era
"il nastro c'è ancora", e c'è — ma tienilo presente: **guru2 non riproduce la
configurazione di produzione**, quindi non usarlo per giudicare come *appare* il nastro.

## Poi

Riporta l'esito delle quattro verifiche e **fermati**, come stai facendo. Il via libera per
installare arriva dopo.
