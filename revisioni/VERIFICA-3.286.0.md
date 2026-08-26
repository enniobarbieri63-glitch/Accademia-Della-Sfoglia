# Verifica di 3.286.0

**Letto il codice, non il rapporto. Confronto riga per riga con 3.284.4.**

Sedici file cambiati, uno tolto. **Quattro cose su cinque sono fatte bene. Una è fatta a metà, e la metà che manca è quella che conta di più.**

---

## ✅ Newsletter tolta — pulizia completa, verificata

Ho controllato in due modi indipendenti, perché togliere un modulo è il posto dove restano i pezzi.

**Primo: le funzioni.** Ho preso i nomi delle sei funzioni definite in `newsletter.php` di 3.284.4 — `gs_ajax_newsletter_elimina`, `gs_ajax_newsletter_iscrivi`, `gs_newsletter_cpt`, `gs_newsletter_iscritti`, `gs_pannello_newsletter`, `gs_sc_newsletter` — e le ho cercate una per una in tutto 3.286.0. **Zero riferimenti rimasti.**

**Secondo: la parola.** Cercata in tutto il pacchetto. Restano solo prosa e storia:

- il `readme.txt` (la cronologia, giusto che resti);
- `faq.php:160`, *«Le novità sono annunciate sul portale e via newsletter»* — **è ancora vero**, il sito ha MailPoet;
- `helpers.php:623` e `sfogline-extra.php:525,649`, dove «iscritti alla newsletter» descrive una categoria di utenti WordPress che non sono sfogline — resta sensato.

**Otto agganci staccati, tutti quelli che c'erano:** il registro delle sezioni (`sezioni.php:98`), il Pannello di Controllo, il sottomenu e la zona in `admin.php`, l'indice di `pannello-nuovo.php`, il menu laterale (`side-tabs.php`), la struttura del menu, il Cruscotto, la voce FAQ predefinita, e i due gestori JavaScript.

Una cosa mi ha convinto più delle altre: in `side-tabs.php` il commento diceva *«…Risponde attivo + Newsletter»*. **È stato riscritto**, non lasciato lì a mentire. È il genere di dettaglio che di solito resta.

## ✅ Il piatto in via di estinzione — tutti e due i difetti

`piatti-estinzione.php:125-181`. Sia il lucchetto (PE1) sia il contrassegno contro i punti infiniti, e la separazione fra i due è scritta chiara nel commento: *«non è un tetto di gioco, sono due difetti»*. Giusto.

Ho tracciato **le quattro uscite** della funzione, perché un lucchetto che non si apre è peggio del difetto che chiude:

| Uscita | Rilascia? |
|---|---|
| tipo di post sbagliato | prima del lucchetto, non serve ✓ |
| il lucchetto non si prende | non l'ha preso ✓ |
| ha già una custode | rilascia ✓ |
| non fai parte di una squadra | rilascia ✓ |
| adozione riuscita | rilascia ✓ |

Tutte coperte. E il contrassegno è scritto **prima** di `gs_add_points()`.

## ✅ Diario e Consigli — il tetto, esattamente com'era da decidere

`forms.php:56-74` e `:139-153`. Contrassegno prima, punti dentro, e **il messaggio corretto**: *«Voce salvata. I punti del Diario li hai già presi oggi — torna domani.»* Era il punto su cui insistevo — una promessa scritta che non si avvera è peggio del tetto stesso — ed è stato preso.

Due cose che ho controllato apposta e che vanno bene:

- **`gs_detect_evo()` e `do_action()` sono rimasti fuori dal blocco `if`.** Giusto: scrivere resta libero, quindi la voce deve contare per il badge dell'olio e per la missione anche quando non paga. Se fossero finiti dentro il blocco, la seconda voce del giorno sarebbe diventata invisibile al resto del gioco. Chi l'ha scritto ci ha pensato.
- **La missione non paga due volte.** `gs_advance_mission( $user_id, 'diario' )` ha obiettivo 1 e il controllo su `done`: la prima voce del giorno vale 15 (azione) + 15 (missione), dalla seconda in poi zero. Il conto torna.

## ✅ Versione coerente

`Version: 3.286.0` nell'intestazione e `GS_VERSION = '3.286.0'`. La lezione dei due pacchetti con lo stesso numero è tenuta.

## ✅ In più, non richiesto: il logo del nastro che si vedeva triplo

`gaming.css`, `.gs-ph-nastro-logo`: aggiunto `background-repeat: no-repeat`, con la spiegazione del perché (un logo largo e basso dentro un cerchio da 30px lascia spazio sopra e sotto, e il browser lo riempie ripetendolo). **È la segnalazione di Ennio «è triplo».** Corretta e spiegata bene.

Attenzione a non confonderla con **N2**, che è un'altra cosa e **resta aperta**: `nastro-vetrine.php` non è stato toccato, e le due metà del nastro contengono ancora sponsor diversi, quindi i loghi saltano al momento del riavvolgimento. Quello è un errore mio di analisi, non vostro, e va ancora chiuso.

---

## ⚠️ I sondaggi sono fatti a metà — e manca la metà seria

Questa è l'unica cosa vera da segnalare in questo pacchetto.

Di `sondaggi.php` è stato applicato **solo il tetto ai punti della proposta**. È giusto ed è scritto bene. Ma nel blocco 6 su quel file c'erano **due difetti**, e sono di natura diversa:

| | Cosa | Stato |
|---|---|---|
| tetto | 10 proposte l'ora = 100 punti | ✅ fatto |
| **S1** | **i voti che spariscono** | ❌ **non fatto** |
| **S2** | **le proposte che spariscono** | ❌ **non fatto** |

Il codice di `gs_ajax_sondaggio_vota` (righe 225-240) è **identico** a prima:

```php
$voti = get_post_meta( $id, 'gs_sond_voti', true );   // ← l'array di TUTTE
if ( isset( $voti[ $uid ] ) ) { wp_send_json_error( 'Hai già votato' ); }
$voti[ $uid ] = $proposta_id;
update_post_meta( $id, 'gs_sond_voti', $voti );        // ← riscrive tutto
```

Due sfogline che votano nello stesso momento: leggono l'array vuoto tutte e due, ne riscrivono una versione ciascuna, **e l'ultima cancella la prima**. Ad Anna il sito ha detto *«Voto registrato, grazie!»*, le ha dato i 5 punti, e il suo voto non c'è.

**Il tetto e il lucchetto non risolvono lo stesso problema.** Il tetto è equilibrio di gioco: impedisce di guadagnare troppo. Il lucchetto è correttezza: impedisce di **perdere un dato**. Qui si perde un voto, in silenzio, senza che nessuno se ne accorga — e il sondaggio dà un risultato sbagliato che sembra giusto.

E si vede **solo quando molte votano insieme**, cioè esattamente il giorno in cui Ennio scriverà a tutte «vota il sondaggio». Con due o tre sfogline alla volta non succede mai: è un difetto che non si manifesta in prova e si manifesta in pubblico.

**Da fare**: `GET_LOCK` su `'gs_sond_' . $id` con `try/finally`, rilettura dentro il lucchetto, **la stessa chiave per il voto e per la proposta** così i due handler non si pestano i piedi fra loro. È la forma già usata bene nel piatto, in questo stesso pacchetto — c'è già il modello a due file di distanza.

---

## Due note sul lucchetto del piatto, minori ma da sapere

**1. Manca il `try/finally`.** Le quattro uscite sono coperte a mano, e ho verificato che nessuna dimentica il rilascio. Ma fra il `GET_LOCK` e il `RELEASE_LOCK` girano `gs_get_user_team()`, `update_post_meta()`, `get_the_title()` e `gs_add_points()`: se una di queste muore con un errore fatale, il lucchetto resta chiuso fino a quando MySQL non chiude la connessione.

Con PHP-FPM la connessione si chiude a fine richiesta, quindi non si blocca niente per davvero. **Non è urgente.** Ma il `try/finally` costa due righe e toglie di mezzo la domanda; e quando metterete il lucchetto sui sondaggi vale la pena scriverlo lì nella forma buona, invece di copiare questa.

**2. `gs_piatto_libera_uid()` non prende lo stesso lucchetto.** Cancella i tre meta senza chiedere permesso a nessuno. Quasi sempre è innocuo — liberare un posto non fa danno a chi lo sta occupando. C'è un solo incastro storto, e serve una pagina rimasta aperta da prima: Anna adotta mentre Bruna, che era la custode e ha la pagina vecchia, rinuncia. Risultato: ad Anna è stato detto «sei la custode» e le è stato scritto il contrassegno, ma il piatto è libero e lei non è la custode. Può riadottarlo (e non riprende i punti, giustamente), ma deve accorgersene da sola.

Una riga: lo stesso `GET_LOCK` anche in `gs_piatto_libera_uid()`.

**3. Una conseguenza voluta, che segnalo perché nasce da come l'ho specificato io.** Il contrassegno è per **persona + piatto**. Su un'adozione di squadra la custodia è della squadra, ma il pagamento resta personale: in una squadra da sei, lo stesso piatto può pagare venti punti a testa, una volta ciascuna, se se lo passano. **Non è farming** — servono sei account veri e ognuno prende una volta sola quello che prenderebbe comunque adottando un piatto suo. Lo scrivo perché non sembri una svista fra sei mesi.

---

## Cosa non c'è in questo pacchetto

Del blocco 6 restano fuori **le due che avevo messo per prime**:

- **M2** (`madrina.php:200`) — la mini-missione che paga 5+5 punti a ogni ri-spunta, all'infinito;
- **C6** (`conversazioni.php:762`) — il token rimborsato due volte, che sono soldi veri.

Più **L**, i 31 controlli d'accesso deboli, che però va fatto insieme al cancello dei trenta giorni e quindi è giusto che aspetti.

**Non è una critica: è il promemoria.** Questo pacchetto ha fatto la Newsletter, il piatto, il tetto e il logo del nastro — è una giornata piena. Ma M2 e C6 erano i due che avevo segnato «adesso», e non sono passati.

---

## In sintesi

**Il pacchetto è buono e si può installare.** Non introduce difetti, la pulizia della Newsletter è la più accurata che abbia visto in questa serie, e il tetto ai punti è esattamente quello che serviva.

Manca il pezzo di correttezza dei sondaggi, e **quello va prima del prossimo pacchetto**, perché è un difetto che si presenta in pubblico il giorno in cui il sondaggio serve davvero.

**Ordine per il prossimo giro:**

1. **S1 + S2** — il lucchetto sui sondaggi, la metà che manca di quello che avete già cominciato;
2. **M2** e **C6** — punti infiniti e soldi doppi;
3. il `try/finally` e il lucchetto sulla rinuncia del piatto, mentre siete lì;
4. **N2** — l'errore mio sul nastro, ancora aperto.

E sopra tutto questo resta il documento dei trenta giorni, che ha la scadenza vera.
