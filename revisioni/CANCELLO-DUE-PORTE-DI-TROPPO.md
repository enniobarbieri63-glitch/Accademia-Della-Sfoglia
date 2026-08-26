# Il cancello ha chiuso due porte di troppo — ed è colpa della mia istruzione

**Per Claude Code Ennio 2 — urgente, prima del prossimo giro**

Non ho ancora il pacchetto 3.290.0, quindi questa non è una verifica: è un controllo che ho fatto sull'elenco dei punti di `gs_login_notice()` così com'era in 3.288.0, cioè **esattamente l'elenco su cui avete lavorato**.

Fra i punti che ho detto di convertire ce ne sono **due che non dovevano esserlo**, e la colpa è della mia istruzione, non vostra: avevo scritto *«sempre uguale, mai adattata»*, e avete fatto bene a seguirla alla lettera.

---

## I due punti

```
includes/artigiani.php:433      gs_sc_art_pannello()    — [gs_vetrina_artigiano_pannello]
includes/scuole-cucina.php:438  gs_sc_scu_pannello()    — [gs_vetrina_scuola_pannello]
```

Sono i **pannelli di autogestione dei partner**: il posto dove un artigiano della pasta o una scuola di cucina modifica la propria vetrina — logo, testo, foto, video, indirizzo. È quello che si compra con i **490 euro**.

## Perché è un problema

I partner hanno un sistema di scadenza **completamente separato** da quello delle sfogline, e sta su un'altra cosa:

| | dove vive | chi lo legge |
|---|---|---|
| abbonamento del partner | `gs_art_scadenza` / `gs_scu_scadenza` — meta del **post** vetrina | `gs_art_attivo()` / `gs_scu_attivo()` |
| prova/quota della sfoglina | `gs_abbonamento_scadenza` — meta dell'**utente** | `gs_sfoglina_congelata()` |

I due non si parlano, ed è giusto così.

**Il guaio nasce quando la stessa persona è tutte e due le cose.** Un artigiano che si iscrive anche come socio — che è il caso normale, non l'eccezione: sono persone del mondo della sfoglia — riceve `gs_abbonamento_scadenza` al momento dell'approvazione, come tutti, con i trenta giorni di regalo.

Al trentunesimo giorno `gs_sfoglina_congelata()` dice «sì», il cancello si chiude, e **quella persona non può più toccare la vetrina per cui ha pagato 490 euro** — con l'abbonamento da partner perfettamente in corso, e senza che niente glielo spieghi. Il messaggio che si trova davanti le parla di una prova del gaming finita e di 29 euro: parole che con la sua vetrina non c'entrano niente.

Ed è il verso peggiore dell'errore: chi ha pagato di più è chi resta fuori.

## Che è la stessa cosa che avete già deciso bene per la biografia

Nelle tre eccezioni degli handler AJAX avete tenuto fuori **Biografia**, e il motivo è identico: la Vetrina delle sfogline si paga a parte, 49 euro, quindi non deve chiudersi con il gaming. Il ragionamento era giusto. **Va portato anche sulle due porte dei partner**, che sono lo stesso caso con uno zero in più.

## La correzione

Nei due punti, al posto del cancello generico:

```php
// NON gs_gate_riservato() qui: questo pannello è ciò che il partner compra
// con i 490 euro, e la sua scadenza è un'altra (gs_art_scadenza sul post
// della vetrina, non gs_abbonamento_scadenza sull'utente). Un artigiano che
// è ANCHE socio verrebbe chiuso fuori dalla propria vetrina il giorno in cui
// finisce la prova del gaming, con l'abbonamento da partner in corso —
// e il messaggio del congelamento gli parlerebbe di 29 euro e di gaming,
// che con la sua vetrina non c'entrano nulla.
if ( ! is_user_logged_in() ) { return gs_login_notice(); }
```

Cioè: **rimettete la riga di prima, con il commento che spiega perché è rimasta diversa** — altrimenti fra sei mesi qualcuno la «uniforma» di nuovo in buona fede.

Il controllo che serve davvero lì c'è già ed è quello giusto: due righe sotto, `gs_art_owner_post()` verifica che chi guarda sia il proprietario di una vetrina. Chi non lo è non entra comunque.

---

## Gli altri li ho ricontrollati uno per uno

Perché non dobbiate rifare il giro. Ho guardato i tre punti di `shortcodes.php` che dal nome non si capivano:

| | | |
|---|---|---|
| `shortcodes.php:769` | `[gs_diario]` | gaming ✅ chiuso bene |
| `shortcodes.php:942` | `[gs_barometro]` — guida stagionale | gaming ✅ |
| `shortcodes.php:1135` | `[gs_profilo]` | gaming ✅ |
| `control-panel.php:33` | pannello dei gestori | ✅ innocuo, un gestore non si congela mai |
| tutti gli altri (21) | sfida, ricettario, tavolo, indovina, sondaggi, giuria, lezioni, area pro, esperti, messaggi, cassaforte, matterello, testamento, misurata, promemoria, cronologia, riepilogo anno, anno fa oggi, ricerca globale, piatti, sfoglia insegna | gaming ✅ |

**`aiuto.php` non compare in questo elenco**, e questa è una buona notizia che vale la pena dire: la pagina «Aiuto e Suggerimenti» non passa da `gs_login_notice()`, quindi non è stata toccata dalle 25 sostituzioni. La porta da cui una congelata chiede di rientrare è rimasta aperta senza che nessuno dovesse ricordarsene.

Adesso che «La Mia Sfoglia» si chiude per decisione di Ennio, quella porta conta il doppio: **verificate che sia davvero raggiungibile da congelata**, perché è l'unico posto dentro il sito dove può ancora scrivervi. Se anche quella si è chiusa per un'altra strada, il congelamento diventa una stanza senza maniglia.

---

## Sull'errore mio, perché non si ripeta

È la seconda volta in due giorni che sbaglio nello stesso modo: **trovo una regola giusta, la scrivo come universale, e non porto con lei l'eccezione che avevo già riconosciuto altrove.**

- La prima volta con la cache: l'avevo scritta per i meta utente e non l'ho portata sui meta dei post. L'avete trovata voi.
- Questa volta con le eccezioni: le ho elencate per i 31 handler AJAX e non le ho portate sui 29 cancelli delle pagine, pur avendo scritto «mai adattata» proprio lì.

La regola che mi do, e che vi conviene applicarmi: **quando vi do un elenco di punti da cambiare tutti uguali, chiedetemi l'elenco delle eccezioni prima di cominciare, anche se non ve l'ho dato.** Se rispondo «nessuna», è una risposta; se non ci ho pensato, è il momento in cui me ne accorgo.

---

## Le altre due cose, brevemente

**Il quasi-incidente della ricorsione.** Uno script di sostituzione in blocco che riscrive anche la funzione che sta chiamando è il tipo di errore che il controllo di sintassi non vede — PHP non ha niente da ridire su una funzione che chiama se stessa. L'avete preso perché stavate provando end-to-end, che è l'unica ragione per cui si prende.

**Le mie prove non l'avrebbero visto, ed è un buco della batteria, non un suo limite naturale.** È un controllo facile da aggiungere: una funzione il cui corpo contiene una chiamata al proprio nome. Lo scrivo e ve lo mando con il prossimo giro — dopo una sostituzione in blocco è esattamente la rete che serve.

**La condivisione via email e WhatsApp.** Il difetto che avete trovato — `esc_url()` che toglie gli a-capo dagli URL `https:` ma non da quelli `mailto:` — è vero e la scelta di usare un trattino al posto dell'a-capo è quella giusta: un separatore che si comporta uguale sui due canali vale più di due canali che si comportano diverso. Buona presa.
