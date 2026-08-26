# Si riparte da zero il 1° ottobre — cosa cade, cosa resta, e come si fa il reset

**Deciso da Ennio il 26/08/2026:** *«tutto quello che stai progettando non deve tenere conto
degli utenti attuali, il gaming è fermo e resettiamo tutto prima di partire il 1° ottobre»*.

Buona notizia: **toglie lavoro.** Ma ci sono due cose da fissare prima, e la seconda è quella
che conta.

---

# PARTE 1 — Cosa cade davvero

Queste cose erano nei documenti e **si possono togliere dalla lista**:

| Cosa | Dov'era |
|---|---|
| Contare i punti gonfiati del Tavolo prima del lancio | Verifica 3.281.0, punto **D1**, e in fondo a `TAVOLO-SENZA-LIMITE.md` |
| L'avviso di scadenza che riparte una volta sola sulle vetrine già avvisate | Verifica 3.281.0, nota su **P3** |
| «Chi ha già il badge da prima di questa correzione non viene toccato» | Commento in `percorsi-lezioni.php`, L2 |
| La prima riapertura di una lezione già vista che scrive il contrassegno | L1, `lezioni-video.php` |

**Nessuna di queste va più fatta.** Le due note nel codice possono restare — sono commenti,
non fanno danno — ma non serve provarle.

---

# PARTE 2 — Cosa NON cade, ed è la maggior parte

Questo va detto chiaramente perché è il punto dove un malinteso costerebbe caro.

**Quasi niente di quello che abbiamo corretto riguardava i dati vecchi.** Riguardava **cosa
succede mentre si gioca** — e il 1° ottobre si comincia a giocare davvero, con più persone
dentro di quante ce ne siano mai state.

Restano tutte, senza sconti:

- **L1, L2, C1, T1, T2, P1** — le corse. Un doppio clic, due schede, due socie che finiscono
  insieme: **succedono in futuro, non nel passato.** Un database vuoto non protegge da
  niente.
- **Il bug del tema** e la sua correzione. Non c'entra con gli utenti.
- **Lo sfarfallio.** Non c'entra con gli utenti.
- **La riparazione del vicolo cieco in L1** — serve a una richiesta che si interrompe *domani*.
- **La data sbagliata di P3** — riguarda avvisi che partiranno da ottobre in poi.
- **A1, A2, B1, B3** dalla verifica della 3.281.0.
- **Tutto quello che la seconda lettura troverà da qui in avanti.**

**Il reset azzera i danni già fatti. Non azzera le cause.**

---

# PARTE 3 — «Tutto» non può essere letterale, e qui serve una decisione

Ci sono tre cose sul sito che **non sono utenti del gaming**, e cancellarle sarebbe un danno
vero e non recuperabile.

## 1. I partner paganti — **non toccare mai**

`gs_artigiano` e `gs_scuola`: pastifici, botteghe e artigiani che hanno pagato **150 €
all'anno con bonifico**. Dentro ci sono:

- `gs_art_scadenza` / `gs_scu_scadenza` — **la data fino a cui hanno pagato**;
- `gs_art_pagamenti` / `gs_scu_pagamenti` — **il registro dei bonifici ricevuti, che è
  l'unica contabilità che esiste**;
- il contenuto della vetrina, che hanno scritto loro.

E i loro **account WordPress** (`gs_status = 'artigiano'` / `'scuola_cucina'`): se il reset
azzera `gs_status`, quelle persone diventano sfogline normali e le loro vetrine si scollegano.

**Vanno esclusi dal reset, uno per uno.** Questa non è un'opinione: sono soldi incassati.

## 2. I contenuti che ha costruito Ennio — quasi certamente da tenere

Lezioni video, Percorsi Guidati, FAQ, direttive, novità, locandine, premi, piatti in via
d'estinzione, sondaggi, letture, corsi del calendario. **Non sono dati degli utenti: sono il
sito.** Rifarli vorrebbe dire rifare mesi di lavoro.

`gs_lezione`, `gs_percorso_lezioni`, `gs_faq`, `gs_direttiva`, `gs_novita`, `gs_locandina`,
`gs_premio`, `gs_piatto`, `gs_cassaforte`, `gs_sondaggio`, `gs_lettura`, `gs_corso_cal`.

**Da confermare con Ennio, ma il difetto va nella direzione di tenerli.**

## 3. La Posta interna — leggerla prima di cancellarla

`gs_msg_interno` contiene, fra le altre cose, **il messaggio della rettifica di luglio con i
nomi delle sei sfogline**. Nel documento di allora avevo scritto che *«quello è anche
l'unico posto dove quei nomi esistono»*.

**Non è un motivo per non cancellarla** — è un motivo per **guardarla prima** e portare via
quello che serve.

## E una domanda che deve fare Ennio, non tu

**Gli abbonamenti delle sfogline** (`gs_abbonamento`, `gs_abbonamento_scadenza`): sono quote
associative **vere, pagate**? Se sì, azzerarle vuol dire che il 1° ottobre nessuna ha più
accesso alle aree private, e vanno tutte reinserite a mano.

**Chiedilo a Ennio prima di scrivere una riga di reset.**

---

# PARTE 4 — Il reset va costruito, non digitato

**Nel plugin non esiste nessuna funzione di reset.** Ho controllato: non c'è.

Quindi, così com'è, il 30 settembre qualcuno aprirà phpMyAdmin e scriverà delle `DELETE` a
mano, sul sito vero, di sera, una volta sola e senza tornare indietro. **È esattamente la
forma degli incidenti che abbiamo già avuto** — la chiusura di luglio è partita perché una
cosa è stata fatta senza chiedersi prima cosa toccava.

**Va costruito come una funzione del pannello**, con tre requisiti:

1. **Prima una prova a vuoto.** Un pulsante «Mostra cosa verrebbe cancellato» che stampa i
   numeri — quanti utenti, quanti post per tipo, quante righe di meta — **senza toccare
   niente.** Ennio legge i numeri e solo dopo, se tornano, procede.
2. **Provato su guru2 con una copia vera del sito**, non con dati finti. Si guarda cosa resta
   dopo: i partner ci sono ancora? Le lezioni? I percorsi?
3. **Un backup completo del database prima**, tenuto da parte. Non per rimettere tutto
   indietro — per poter guardare, se a novembre qualcosa non torna, com'era prima.

---

## La trappola tecnica che farebbe sbagliare un elenco scritto a mano

Molte chiavi del plugin **hanno il nome costruito al momento**, e un elenco compilato
leggendo il codice le manca tutte:

```
gs_points_2026              gs_points_mese_2026-09        gs_points_anno_2026
gs_badge_label_percorso_12  gs_buono_mese_2026-09         gs_lezione_vista_45
gs_badge_dato_percorso_12   gs_tavolo_punti_giorno        gs_year_prize_assigned_2026
```

Sono **decine di chiavi diverse per ogni sfoglina**, e il numero cambia a ogni mese e a ogni
percorso.

**Quindi la regola è al contrario di come viene istintivo:** non elencare cosa cancellare —
**cancellare tutto ciò che comincia per `gs_`, tranne un elenco corto di eccezioni.**

```php
// Si cancella per prefisso e si TENGONO le eccezioni, non il contrario:
// molte chiavi hanno il nome costruito al momento (gs_points_mese_2026-09,
// gs_badge_label_percorso_12, gs_lezione_vista_45…), sono decine per
// sfoglina e cambiano ogni mese. Un elenco di cose da cancellare, scritto
// leggendo il codice, le mancherebbe tutte e lascerebbe punti e badge
// vecchi attaccati addosso a chi riparte da zero.
$da_tenere = array(
	'gs_status',                 // toglierlo trasformerebbe i partner in sfogline
	'gs_conta_come_sfoglina',    // l'interruttore manuale di Rina e Bruno
	'gs_email_verificata',
	'gs_birthdate',
	'gs_genere',
	'gs_notifiche_pref',
	'gs_note_gestore',
	// gs_abbonamento e gs_abbonamento_scadenza: decide Ennio (vedi sopra)
);
```

**Questo elenco va rivisto voce per voce con Ennio prima di eseguire**, non deciso qui:
ognuna di queste righe è una scelta su cosa una persona si ritrova addosso il 1° ottobre.

---

# PARTE 5 — Com'è lo stato giusto il 1° ottobre

Dopo il reset, **prima di riaprire**, questi cinque punti vanno verificati uno per uno:

| | Cosa deve essere vero | Dove si guarda |
|---|---|---|
| 1 | **Il percorso mensile è ancora SPENTO** finché Ennio non lo accende | l'interruttore nello Stato Generale (3.275.0) |
| 2 | `gs_buono_sfoglia_mese_chiuso` **o è vuota, o vale il mese scorso** | la protezione «prima accensione» in `buono-sfoglia.php` la semina da sola al primo cron — **verifica che quella protezione ci sia ancora** |
| 3 | **Nessuna sfoglina ha punti, badge, streak, token o sconti residui** | tre nomi a campione, non uno |
| 4 | **I partner ci sono tutti, con le loro scadenze** | la zona Artigiani e Scuole del pannello |
| 5 | **Lezioni e Percorsi Guidati ci sono tutti** | la Libreria Video e i Percorsi |

**Il punto 2 è quello che ha già morso una volta.** A luglio la chiusura è partita perché
un'opzione non era mai stata scritta. Dopo un reset **è di nuovo non scritta**: la
protezione esiste dalla 3.272.1, ma va **riconfermata sul codice**, non ricordata.

**E il primo resoconto vero sarà il 1° novembre**, non il 1° ottobre: il 1° ottobre si
comincia a giocare, e si chiude il mese di ottobre un mese dopo. Se il 1° novembre arriva un
resoconto a qualcuno che ha giocato una settimana, va bene; se ne arriva uno a chi non ha
giocato affatto, è luglio che si ripete.

---

# Cosa serve da te, Ennio

Tre risposte, e non hanno fretta — ma servono **prima** che qualcuno scriva il reset:

1. **I contenuti (lezioni, percorsi, FAQ, novità, locandine, corsi): si tengono?** Io direi
   di sì — è il sito, non i dati degli utenti.
2. **Gli abbonamenti delle sfogline sono quote pagate davvero?** Se sì, azzerarli significa
   reinserirli tutti a mano.
3. **Gli account restano o si cancellano anche quelli?** Sono due cose molto diverse:
   *azzerare i punti* lascia le persone dentro con la loro password; *cancellare gli account*
   le costringe a registrarsi di nuovo — e a ottobre significa ricominciare da chi si ricorda
   di farlo.

**Sulla 3 in particolare, non tirare a indovinare:** è la differenza fra ripartire da zero
con le stesse persone e ripartire da zero e basta.

---

## Nel frattempo

**Niente di questo blocca il lavoro in corso.** Le correzioni della Parte 2 vanno fatte
comunque, il reset è indipendente, e la seconda lettura continua.

L'unica cosa che cambia da subito è che **`TAVOLO-SENZA-LIMITE.md` non ha più bisogno del
conteggio finale** — quel paragrafo si può ignorare.
