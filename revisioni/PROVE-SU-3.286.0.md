# Le prove su 3.286.0

**26/08/2026 — PHP 8.4, nessun sito, nessun database.**

Finora avevo letto il codice. Questa volta l'ho **fatto girare**: ho scritto un finto WordPress — quel tanto che basta perché le funzioni del plugin possano essere eseguite davvero — e ho messo sotto prova le correzioni di 3.286.0, confrontandole ogni volta con 3.284.4.

**Le quattro correzioni funzionano. Il difetto che era rimasto fuori si riproduce.**

Tutta la batteria è nel repository, in `revisioni/prove/`, e si rigira su qualunque pacchetto futuro con un comando solo:

```bash
./revisioni/prove/prova.sh /percorso/alla/cartella/scompattata
```

---

## Prova 1 — Sintassi di ogni file

`php -l` su tutti i file del pacchetto.

| | file | errori |
|---|---|---|
| 3.286.0 | 96 | **0** |
| 3.284.4 | 97 | 0 |

Un file in meno: `newsletter.php`. ✅

## Prova 2 — Ogni funzione `gs_*` chiamata esiste davvero?

È **la** prova che serve dopo aver tolto un modulo. Costruisce l'elenco di tutte le funzioni definite e di tutte quelle chiamate, e le confronta.

Usa il tokenizer di PHP, non una ricerca testuale: **la prima versione che avevo scritto dava 70 falsi positivi**, perché scambiava per chiamate i nomi dei meta scritti nei commenti in cima ai file (`gs_custode_tipo (chi è la custode)` e simili). Col tokenizer, commenti e stringhe non vengono più confusi con il codice.

| | definite | chiamate | **mancanti** | definite due volte |
|---|---|---|---|---|
| 3.286.0 | 1358 | 1317 | **0** | 0 |
| 3.284.4 | 1364 | 1323 | 0 | 0 |

**Zero funzioni chiamate e non definite.** Sei funzioni in meno, esattamente le sei della Newsletter. Nessuna doppia definizione. ✅

La prova ha anche trovato da sola, senza che la cercassi, una cosa che avevo segnalato nel blocco 7 leggendo:

```
· gs_get_anno_gioco_points — includes/points.php:324
```

**Non è chiamata da nessuna parte.** È la funzione scritta apposta per l'anno di gioco che chiude il 13 dicembre, mentre le due pagine che dovrebbero usarla usano quella dell'anno solare. È R1, confermato per via meccanica.

*(Delle 41 funzioni «mai chiamate» alcune sono falsi positivi: quelle registrate come `add_meta_box` o richiamate in altri modi che la prova non segue. Il numero identico fra le due versioni è il dato che conta: nessuna è rimasta orfana adesso.)*

## Prova 3 — Le due sponde: JavaScript ↔ PHP

Ogni pulsante del sito chiama un'azione AJAX. Questa prova controlla che **ogni azione chiamata dal JavaScript abbia un gestore in PHP**, e che ogni gestore punti a una funzione che esiste. È il modo per accorgersi che togliendo un modulo si è tolto solo un lato.

| | azioni in PHP | chiamate dal JS | pulsanti morti | gestori rotti |
|---|---|---|---|---|
| 3.286.0 | 342 | 289 | **0** | **0** |
| 3.284.4 | 344 | 291 | 0 | 0 |

**Meno due da una parte e meno due dall'altra**: `gs_newsletter_iscrivi` e `gs_newsletter_elimina`, tolti da tutte e due le sponde. La simmetria è la prova che la rimozione è completa. ✅

---

## Prova 4 — I punti infiniti del piatto: eseguiti davvero

Qui ho preso le funzioni vere dal file, le ho eseguite dentro il finto WordPress, e ho fatto il giro adotta → rinuncia → adotta → rinuncia → adotta.

```
=== 3.286.0 ===
  ✓ adotta/rinuncia/riadotta ×3 → 20 punti in tutto (presi: 20)
  ✓ la seconda che adotta viene respinta
  ✓ chi è stata respinta NON prende punti (0)
  ✓ la custode è la prima arrivata
  ✓ lucchetto riaperto su tutte e 3 le uscite (0/0/0 rimasti aperti)
  ✓ adozione di squadra: custodia alla squadra, 20 punti a chi ha cliccato
  → 6/6 superate

=== 3.284.4 ===
  ✗✗ adotta/rinuncia/riadotta ×3 → 20 punti in tutto (presi: 60)
  → 5/6
```

**60 punti prima, 20 dopo, per gli stessi identici gesti.** Il difetto era reale e la correzione tiene.

La riga sui lucchetti è quella che mi premeva: ho contato i lucchetti rimasti aperti dopo ogni possibile uscita della funzione — successo, «ha già una custode», «non fai parte di una squadra». **Zero in tutti e tre i casi.** Il rilascio a mano, senza `try/finally`, copre davvero tutte le strade.

## Prova 5 — Il lucchetto respinge davvero?

Ho messo il lucchetto in mano a una finta seconda richiesta e ho chiamato la funzione:

```
3.284.4 (prima)  ✗✗ entra lo stesso mentre un'altra sta adottando
                    → 20 punti, e sovrascrive la custode
3.286.0 (dopo)   ✓ si ferma: «Ci sta già pensando qualcun'altra,
                    riprova tra un attimo.» — 0 punti
```

## Prova 6 — Il tetto del Diario

Sei voci scritte nello stesso giorno, eseguendo il codice vero:

```
3.284.4 (prima)  sei voci → 90 punti   ✗✗ paga ogni volta
3.286.0 (dopo)   sei voci → 15 punti   ✓ paga una volta sola
```

Esattamente la decisione presa. ✅

---

## Prova 7 — Il difetto rimasto fuori: i voti che spariscono

Questa non è una previsione. Ho preso le righe vere di `gs_ajax_sondaggio_vota` così come stanno **in 3.286.0** e le ho eseguite intrecciate, come capita quando due richieste arrivano insieme:

```
Anna legge i voti:  []
Bruna legge i voti: []
→ Anna:  «Voto registrato, grazie!» +5 punti
→ Bruna: «Voto registrato, grazie!» +5 punti

VOTI REGISTRATI DAVVERO NEL SONDAGGIO: {"202":2}
punti dati: Anna 5, Bruna 5

✗✗ 1 voto su 2 è SPARITO.
```

**Ad Anna il sito ha detto che il voto era registrato, le ha dato i 5 punti, e il suo voto non c'è.** Il sondaggio darà un risultato sbagliato che sembra giusto.

E si vede **solo quando molte votano insieme** — il giorno in cui Ennio scriverà a tutte «votate il sondaggio». Con due o tre sfogline alla volta non capita mai: è un difetto che in prova non si manifesta e in pubblico sì.

Con `GET_LOCK` su `'gs_sond_12'`, Bruna avrebbe aspettato Anna, riletto l'elenco aggiornato e **aggiunto** il suo voto invece di sostituirlo. Il modello è già in questo stesso pacchetto, due file più in là.

## Prova 8 — Madrina & Allieva, ancora aperta

Il cuore vero di `gs_ajax_madrina_toggle`, così com'è in 3.286.0, dieci clic sulla stessa casella:

```
clic 1 — casella spuntata  → madrina 5,  allieva 5
clic 2 — casella tolta     → madrina 5,  allieva 5
clic 3 — casella spuntata  → madrina 10, allieva 10
clic 4 — casella tolta     → madrina 10, allieva 10
…
dopo 10 clic               → madrina 25, allieva 25
```

**Una sola mini-missione, mai davvero completata due volte, ha pagato 50 punti.** Non serve malafede: basta cambiare idea.

---

## Il risultato

| | Esito |
|---|---|
| Sintassi (96 file) | ✅ |
| Funzioni chiamate e non definite | ✅ 0 |
| Doppie definizioni | ✅ 0 |
| Pulsanti morti (JS → PHP) | ✅ 0 |
| Gestori rotti (PHP → funzione) | ✅ 0 |
| Punti infiniti del piatto | ✅ corretto (60 → 20) |
| Lucchetto del piatto | ✅ respinge, e si riapre sempre |
| Tetto del Diario | ✅ corretto (90 → 15) |
| **Voti dei sondaggi** | ❌ **si perdono ancora** |
| **Madrina & Allieva** | ❌ **punti infiniti ancora** |
| **Token rimborsato due volte** | ❌ non toccato |

**3.286.0 si può installare.** Non rompe niente, e le quattro cose che dichiara di aver corretto sono corrette per davvero — non perché lo dice il rapporto, ma perché il codice eseguito si comporta così.

Restano i tre difetti già segnalati, e adesso due di loro non sono più un'analisi: sono una prova che si può rigirare quando si vuole.

---

## Come rigirarle

Le prove 1, 2 e 3 girano su qualunque pacchetto, senza modifiche:

```bash
./revisioni/prove/prova.sh /percorso/alla/cartella/scompattata
```

Le prove 4-8 sono mirate su singole funzioni e si lanciano una per una (`php test_piatto.php zip20`). Sono scritte per confrontare **due versioni**, così una correzione si vede: prima fallisce, dopo passa.

**Il consiglio:** far girare `prova.sh` prima di ogni consegna. Le tre prove generali costano dieci secondi e prendono l'intera classe di errori che nasce quando si toglie o si rinomina qualcosa — che è esattamente il rischio del reset e del lavoro sui trenta giorni che viene adesso.
