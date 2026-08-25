# 3.276.0 verificata — T1, T2, T3 corrette. Una riga da spostare.

Letto il codice. **Le tre voci sono implementate bene**, `php -l` pulito, e il diff contro
la 3.275.0 tocca solo i file che doveva toccare: `token.php`, `esperti.php`,
`calendario.php` (la rete di sicurezza), più JS e CSS del pulsante.

**Due raffinamenti non erano nel documento e sono giusti**, quindi vanno detti:

- il tetto di **50 identificativi** su `gs_token_rif`: il registro dei pagamenti è per
  prenotazione e finisce con lei, questo è **per sfoglina** e crescerebbe per sempre. È il
  tipo di differenza che si vede solo scrivendo il codice;
- il controllo del blocco in `gs_domande_limiti_ok()` **spostato fuori** dal
  `if ( ! empty( $l['cooldown'] ) )`. Senza, con il cooldown configurato a 0 la rete da 5
  secondi non sarebbe mai stata letta e la correzione T2 sarebbe stata inerte. **Era un
  buco nella mia proposta**, trovato applicandola.

E la funzione gemella — `gs_ajax_esperto_domanda()`, la domanda pubblica gratuita — è
stata trovata e corretta senza che nessuno l'avesse chiesta. Giusto: stessa causa, e
lasciarla rotta avrebbe significato tornarci.

---

## L'unica cosa da correggere: il blocco scatta prima di controllare cosa è stato scritto

**File:** `includes/esperti.php:388` (domanda pubblica) e `includes/esperti.php:459`
(domanda a token) — VERIFICATO

In entrambe le funzioni l'ordine attuale è:

```php
set_transient( 'gs_dom_cd_' . $uid, 1, max( 5, ... ) );   // ← il blocco

$oggetto = ... ;
if ( mb_strlen( $oggetto ) < 5 ) {
    wp_send_json_error( array( 'message' => 'Scrivi la tua domanda nell\'Oggetto…' ) );
}
```

Il blocco viene scritto **prima** del controllo sulla lunghezza del testo. Quindi:

1. una sfoglina scrive un oggetto troppo corto e preme invia;
2. il blocco scatta;
3. le arriva l'errore *«Scrivi la tua domanda nell'Oggetto»*;
4. lei corregge e ripreme subito;
5. **le arriva «Aspetta qualche secondo prima di scrivere ancora».**

Nel documento avevo dichiarato questo compromesso, ma pensando a *«canale rotto, errore nel
creare la conversazione»*: casi rari. **Un testo troppo corto non è un caso raro — è
l'errore più comune che si fa in un modulo**, e adesso costa un'attesa.

### Correzione

Spostare le due `set_transient` **dopo i controlli sulla lunghezza** e **prima** di tutto
ciò che tocca lo stato — cioè, nella funzione a pagamento, prima del controllo del saldo:

```php
	$oggetto  = ...;
	if ( mb_strlen( $oggetto ) < 5 ) { wp_send_json_error( ... ); }
	$dettagli = ...;

	// Il blocco va scritto dopo i controlli sul testo (che non toccano niente)
	// e prima di tutto ciò che modifica qualcosa: così un oggetto troppo corto
	// non costa un'attesa alla sfoglina, ma un doppio clic su un invio valido
	// resta bloccato. (25/08/2026)
	set_transient( 'gs_dom_cd_' . $uid, 1, max( 5, (int) gs_esperti_limiti()['cooldown'] ) );

	$costo = (int) $ch['costo_token'];
	if ( gs_token_saldo( $uid ) < $costo ) { wp_send_json_error( ... ); }
```

**La protezione non si indebolisce:** i controlli sulla lunghezza sono letture pure, non
toccano token né conversazioni. Fra loro e il primo effetto non c'è niente che un doppio
clic possa sdoppiare.

Stessa cosa in `gs_ajax_esperto_domanda()`, dove va dopo
`if ( mb_strlen( $testo ) < 5 )` e prima della `wp_insert_post()`.

**Verifica:** manda una domanda con due caratteri, correggi e ripremi **subito**. Deve
partire, non dire di aspettare. Poi manda una domanda valida e fai doppio clic: il secondo
deve essere respinto.

---

## Riepilogo

| | Stato |
|---|---|
| Rete di sicurezza C1 (pagamenti) | ✅ |
| T1 · accredito token | ✅ con il tetto di 50, che è un'aggiunta giusta |
| T2 · doppio clic sulla domanda | ✅ **da spostare di poche righe**, vedi sopra |
| T2-bis · la funzione gemella pubblica | ✅ trovata e corretta di iniziativa |
| T3 · una passata invece di tre | ✅ |

---

## Ancora in attesa di Ennio

Il campanello della **Posta interna** — dove ora arrivano le disdette con l'acconto da
restituire e, da ottobre, i rendiconti della chiusura del mese. Oggi non c'è nessun avviso:
né email, né aeroplanino, né un numero di non letti, e `gs_inbox_non_letti()` resta scritta
e non collegata a niente.

**Non scrivere codice finché Ennio non sceglie.** Le tre opzioni sono nel documento
precedente; quella consigliata resta la riga nello Stato Generale, più un aeroplanino solo
per le disdette con acconto.

---

## Cosa leggo adesso

`artigiani.php` e `scuole-cucina.php`: gli abbonamenti dei partner, l'altra entrata a
pagamento del sito. Cerco per prima cosa lo schema che si è ripetuto quattro volte —
un'operazione che tocca un valore accumulato senza niente che impedisca di eseguirla due
volte.
