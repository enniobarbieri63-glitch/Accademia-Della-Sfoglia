# Prezzi, il nastro, e cosa vede chi guarda

**Risposte alle tre domande di Ennio — 26/08/2026, misurato su 3.292.0**

---

## 1. I prezzi dal pannello — sono due situazioni diverse

### La Vetrina dei partner: **il prezzo è già libero**, manca solo il listino

Il pannello dei pagamenti esiste già (`artigiani.php:645`, e il gemello in `scuole-cucina.php`). Quando arriva un bonifico, Ennio scrive **l'importo che vuole** — non c'è nessun prezzo fisso nel codice che glielo impedisca:

```
Importo ricevuto (€)  [ es. 120,00 ]
```

e ogni versamento resta nello storico (`gs_art_pagamenti`, mai sovrascritto), con la scadenza che si sposta di conseguenza.

**Quindi metà di quello che chiedi c'è già.** Quello che manca è un **prezzo di listino** in un posto solo, che serve a due cose: comparire nei testi pubblici («la vetrina si attiva con un contributo di X euro») e presentarsi già scritto nel campo quando registri un bonifico, così non lo ridigiti ogni volta.

È un'impostazione, come quella dei 29 euro che c'è già:

```php
'listino' => array(
    'vetrina_partner' => '500,00',   // artigiani e scuole
    'nastro_sponsor'  => '500,00',   // il nastro sotto il menu
    'vetrina_sfoglina'=> '49,00',    // la sfoglina cliccabile
),
```

Mezz'ora di lavoro, e da lì in poi si cambia dal pannello.

### Il nastro sotto il menu: **non c'è nessun pannello** — e questo è il buco vero

Gli sponsor del nastro sono **scritti dentro il codice**, `nastro-vetrine.php:89-101`:

```php
$sponsor = array(
    array( 'nome' => 'Mulino Marino',  'foto' => …, 'url' => 'https://www.mulinomarino.it/it/' ),
    array( 'nome' => 'Molino Caputo',  'foto' => …, 'url' => 'https://www.mulinocaputo.it/' ),
    array( 'nome' => 'Molini Pivetti', 'foto' => …, 'url' => 'https://www.molinipivetti.it/' ),
);
```

**Questo vuol dire che oggi Ennio non può fare niente da solo**: né aggiungere uno sponsor, né toglierlo quando scade, né registrare un bonifico, né sapere quando scade. Ogni volta serve toccare il codice e rifare il pacchetto. E il logo va caricato a mano dentro il plugin.

Non è un prezzo che manca: **è il modulo che manca.** Serve per il nastro quello che artigiani e scuole hanno già:

- nome, logo (caricato dal pannello, non messo nel plugin), indirizzo del sito;
- **registrazione del bonifico con importo e data**, come per i partner;
- **scadenza automatica**: quando è passata, lo sponsor esce dal nastro da solo, senza che nessuno se ne debba ricordare;
- il promemoria prima della scadenza, che il sistema dei partner ha già.

**È la cosa che mi preoccupa di più fra quelle di oggi**, e per un motivo pratico: uno sponsor che ha smesso di pagare e continua a girare sul nastro è un problema con una persona vera, non con il codice. Oggi l'unica cosa che glielo impedisce è che qualcuno se lo ricordi.

Il lavoro non è grande — **c'è già tutto da copiare**: `artigiani.php` fa esattamente queste cose e funziona. Un giorno, forse meno.

**Una nota:** finché gli sponsor stanno nel codice resta aperto anche il difetto del nastro che ti avevo segnalato (le due metà con sponsor diversi che fanno saltare i loghi al riavvolgimento). Facendo il pannello si sistemano insieme, perché è lo stesso pezzo di codice.

---

## 2. «La Mia Sfoglia» per chi guarda — ma prima una cosa che devi sapere

### Hai cambiato una regola, e non è una piccola

Nella tua domanda scrivi: *«può commentare e partecipare alle votazioni»*.

Quando abbiamo deciso «guarda e commenta», il voto era **fra le cose chiuse**. Adesso lo apri. **Va benissimo se è quello che vuoi**, ma non è un dettaglio tecnico e voglio che la scelta sia tua con gli occhi aperti:

**I voti decidono chi vince la sfida, e la sfida ha un premio vero.** Aprire il voto a chi non ha versato il contributo vuol dire che **chi non è socio concorre a decidere a chi va il premio dei soci.**

Non ti dico che è sbagliato. Ci sono due modi di vederla:

- *A favore*: più votanti fanno una gara più viva; e una che non gareggia vota più liberamente, perché non ha un interesse suo.
- *Contro*: se il premio è dell'Accademia, decidere chi lo vince è forse la cosa più «da soci» che ci sia. E una sfoglina che ha pagato potrebbe non capire perché il voto di chi non paga vale come il suo.

**Una terza strada, che secondo me è la migliore:** chi guarda **può votare, e il suo voto si vede, ma conta a parte.** Due numeri sotto ogni sfoglia — «voti delle socie» e «voti della community» — e il premio si assegna sul primo. Così nessuno è escluso dalla festa, e nessuno che ha pagato si sente scavalcato.

Costa poco: il voto è già registrato con l'identità di chi lo dà, quindi basta contarli in due gruppi invece che in uno.

**Dimmi tu quale delle tre**, e me ne occupo. Fino ad allora tengo il voto chiuso, come è adesso.

### Cosa resta visibile

Con la modalità «guarda» accesa, **«La Mia Sfoglia» si riapre** — e deve, perché se può commentare e votare le serve una casa. Ma non tutta.

**Resta, e deve restare:**

| | perché |
|---|---|
| **La fascia in cima** — «stai guardando», con i 29 euro e come rientrare | è il posto dove legge come tornare |
| **La sua carta d'identità**: foto, nome, livello raggiunto | è lei |
| **I suoi numeri, fermi**: punti, badge, streak, scudi | è «congelato significa salvato» reso visibile — deve *vederli* per credere che ci sono ancora |
| **🔗 La tua Vetrina pubblica** | l'ha pagata a parte, 49 euro |
| **Le sue sfide** e il link alla sfida in corso | è dove commenta (e vota, se decidi di sì) |
| **Il suo account e i suoi dati** | sono suoi, sempre |
| **Aiuto e Suggerimenti** | è la porta da cui chiede di rientrare |

**Sparisce, e deve sparire:**

| | perché |
|---|---|
| **🎯 Missioni di oggi** | non può completarle: mostrarle è una lista di cose che non può fare |
| **🥄 Ingrediente Segreto** | stessa cosa |
| **«Prossimo passo»** — il suggerimento automatico | suggerirebbe azioni chiuse |
| **📖 Nuova voce di diario**, **💡 Condividi un consiglio** | i moduli di scrittura chiusi |
| **🔥 Streak del Matterello** — il *riquadro attivo* | il numero resta nella carta d'identità, ma il riquadro che invita a mantenerla no: la streak è ferma |
| **I promemoria** | sono promemoria per fare cose che non può fare |

**Il criterio, in una riga:** *resta tutto ciò che le racconta chi è e cosa ha; sparisce tutto ciò che la invita a fare qualcosa che non può fare.*

Un pulsante che non porta da nessuna parte è una promessa rotta. Un numero fermo che dice «i tuoi 1.240 punti sono qui» è una promessa mantenuta.

---

## 3. La Newsletter: **hai fatto bene**, con un'avvertenza

Toglierla è stata la scelta giusta, per una ragione precisa: **quel modulo non mandava niente.** Raccoglieva solo gli indirizzi email in un elenco, e nient'altro — nessun invio, nessun messaggio, mai. Era un doppione monco di MailPoet, che sul sito c'è già ed è un vero programma di newsletter. Gli iscritti erano zero, quindi non si è perso niente di nessuno.

**L'avvertenza è questa, ed è una domanda, non un difetto:** adesso una sfoglina che *vorrebbe* ricevere le tue novità per email non ha più nessun posto dove dirlo dal suo pannello. Prima ne aveva uno che non serviva a niente; adesso non ne ha nessuno.

Se le newsletter le mandi con MailPoet — e le mandi — allora nel menu delle sfogline al posto di quella voce **ci vuole il modulo di iscrizione vero di MailPoet**, non il vuoto. Sono cinque minuti: MailPoet dà uno shortcode da incollare in una pagina.

Se invece non mandi newsletter e non hai intenzione di mandarne, allora è giusto così com'è e non c'è niente da fare.

**Dimmi tu quale delle due**, che è l'unica cosa che non posso sapere dal codice.
