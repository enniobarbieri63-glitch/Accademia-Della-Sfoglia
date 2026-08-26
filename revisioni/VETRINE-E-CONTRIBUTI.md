# Le vetrine, i contributi, e il reset che adesso ha una scadenza

**Ennio, 26/08/2026.** Quattro cose nuove: le sfogline si iscrivono a settembre e i loro dati
non si toccano; «La Mia Vetrina» si sblocca con **50 €**; Artigiani e Scuole con **500 €**;
il gaming parte il 1° ottobre o il 1° novembre.

**Ho letto il codice prima di scrivere.** Buona parte di quello che chiedi **esiste già**, ma
funziona in un modo diverso da come lo hai descritto — e le differenze non sono di
dettaglio.

---

# 1 · La cosa urgente: il reset ha smesso di avere tempo

> *«a settembre inviteremo sfogline a iscriversi… inseriranno i loro dati, che non vanno
> cancellati, per cui il reset va fatto ora»*

**Il ragionamento è giusto e la conclusione anche.** Fino a ieri il reset era *«prima del 1°
ottobre»*, cioè cinque settimane. Adesso è **prima del primo invito di settembre**, cioè
**pochi giorni**.

**Perché non si può rimandare:** dal momento in cui la prima sfoglina scrive i suoi dati in
«La Mia Sfoglia», il reset non è più un'operazione su dati di prova. Diventa un'operazione
che deve **distinguere** fra chi è arrivato prima e chi dopo — e distinguere è dove si
sbaglia. **Adesso non c'è niente da distinguere, ed è il momento più sicuro che ci sarà mai.**

## Cosa questo cambia, in pratica

**Il Passo 1 è già pronto e installato**: l'elenco degli account è in Diagnostica dalla
3.282.0, con le cinque esclusioni. **Non c'è niente da costruire per cominciare.**

L'ordine diventa:

| | Cosa | Chi |
|---|---|---|
| **1** | Aprire Diagnostica e guardare l'elenco | Ennio |
| **2** | Dire chi resta e chi no | Ennio |
| **3** | Nascondere i restanti (`gs_status = 'eliminata'`), non cancellare | Claude Code |
| **4** | Azzerare punti, badge, avanzamento, messaggi | Claude Code |
| **5** | Le cinque verifiche di `RESET-PRIMA-DEL-1-OTTOBRE.md` | insieme |
| **6** | **Solo allora** partono gli inviti | Ennio |

**Il punto 1 lo puoi fare oggi**, e non richiede nessuna decisione presa in anticipo: è solo
un elenco da guardare.

**E prima di tutto: il backup del database.** Era già scritto, ma adesso che c'è fretta vale
il doppio — la fretta è il motivo per cui i backup si saltano.

---

# 2 · «La Mia Vetrina»: esiste già, ma con i token e con una differenza sui nastri

Questa parte del sito è **già costruita**, ed è stata costruita su una tua richiesta
dell'**11 agosto**. C'è scritto nel codice (`helpers.php:367`):

> *«richiesto da Ennio il 2026-08-11: la Vetrina — e il link condivisibile fuori dal sito —
> si sblocca solo comprandola coi token, non è più gratuita»*

Oggi funziona così: la sfoglina **compra token con un bonifico**, poi **spende i token** per
attivare la vetrina (5 token di norma, `gs_token_costo_vetrina()`).

**Rispetto a quello che chiedi adesso, ci sono tre differenze. La prima è quella che conta.**

## Differenza 1 — Oggi i nomi non pagati **non compaiono affatto** sul nastro

`nastro-vetrine.php:167`:

```php
		if ( ! gs_e_sfoglina_vera( $u ) || ! gs_vetrina_disponibile( $u->ID ) || … ) {
			continue;
		}
```

**Chi non ha la vetrina attiva viene saltato**: non è che il suo nome non sia cliccabile — il
suo nome **non c'è**.

Tu chiedi il contrario:

> *«appariranno i nomi delle sfogline… ma saranno cliccabili solo le sfoglie che avranno
> pagato»*

**È un cambiamento vero, non una regolazione**, e va nella direzione giusta: un nastro con
tutti i nomi dice *«questa è la comunità»* e fa venire voglia di esserci; un nastro con solo
i paganti dice *«questo è l'elenco dei clienti»*.

**Ma tira dietro una conseguenza che va decisa insieme, non dopo** — vedi il punto 3.

## Differenza 2 — Il prezzo: token o 50 euro?

Oggi il costo è in **token**, che si comprano con un bonifico. Tu adesso dici **50 euro**.

**Due letture, e cambiano cosa c'è da scrivere:**

- **(a) I 50 € sostituiscono i token** per la vetrina: si registra il contributo e la vetrina
  si sblocca, i token restano solo per le consulenze;
- **(b) I 50 € sono il modo di comprare i token** che poi sbloccano la vetrina: niente cambia
  nel codice, cambia solo il listino.

**Credo tu intenda la (a)**, perché hai scritto *«a quel punto si sblocca la vetrina, lo fai
tu in automatico»* — cioè senza che la sfoglina debba fare un secondo passaggio. **Ma
confermalo**, perché la (b) non richiede nemmeno una riga.

## Differenza 3 — «In automatico» ha un limite fisico

Il sito **non ha un sistema di pagamento**: non c'è modo che il codice si accorga da solo che
sono arrivati 50 € sul conto. **Qualcuno deve registrare il bonifico**, come già succede per
i token, per i corsi e per gli abbonamenti dei partner.

Quindi «in automatico» può voler dire solo questo, e va bene: **tu (o la segreteria)
registrate i 50 € da un modulo, e da lì in poi non serve nessun altro passaggio** — la
vetrina si sblocca, il nome diventa cliccabile, il link è pronto.

**Un passaggio umano ci sarà sempre.** Quello che si può togliere è il secondo.

---

# 3 · Il consenso — è la parte nuova, ed è quella da progettare bene

> *«ma con la possibilità di modificare il tuo consenso in qualsiasi momento»*

**Oggi questa cosa non esiste.** C'è `gs_vetrina_bloccata`, ma è un blocco che mette
l'amministrazione, non una scelta della sfoglina.

## Perché è più importante di come suona

Mettendo insieme le due richieste — *«tutti i nomi scorrono sui nastri»* e *«consenso
modificabile in qualsiasi momento»* — viene fuori una cosa che va detta chiaramente:

**Il nastro è una pagina pubblica.** Se ci scorrono i nomi di tutte le sfogline iscritte,
allora **il sito sta pubblicando il nome e cognome di persone che non hanno chiesto di essere
pubblicate** — comprese quelle che non hanno pagato niente e magari si sono iscritte solo per
guardare.

**Il consenso non può essere una casella nascosta in fondo a una pagina.** Deve valere così:

- **niente consenso, niente nome sul nastro** — nemmeno non cliccabile;
- **la scelta si cambia quando si vuole**, da «La Mia Sfoglia», e ha effetto subito;
- **e va chiesta al momento giusto**, cioè quando la sfoglina compila i suoi dati a
  settembre, non dopo.

**Questa è la ragione per cui il consenso va deciso adesso e non a ottobre**: le sfogline si
iscrivono fra pochi giorni, e la domanda va fatta a loro **mentre si iscrivono**. Chiederla
dopo significa mandare un'email a tutte per una cosa che si poteva risolvere con una riga nel
modulo.

## Come lo scriverei

Tre stati, non due, perché due non bastano:

| Stato | Nome sul nastro | Cliccabile |
|---|---|---|
| **Non ha dato il consenso** | no | — |
| **Consenso sì, contributo non versato** | **sì** | no |
| **Consenso sì, 50 € versati** | **sì** | **sì** |

Un solo contrassegno nuovo (`gs_consenso_vetrina`), acceso dalla sfoglina, spento dalla
sfoglina, letto dal nastro. **Il pagamento e il consenso restano due cose separate**, ed è
giusto che lo siano: chi ha pagato e poi cambia idea deve poter sparire senza chiedere
indietro i soldi.

**Compromesso da dichiarare:** se una sfoglina toglie il consenso dopo aver pagato, **la
vetrina resta pagata ma invisibile**. Non è un problema tecnico — è una cosa da scrivere nel
testo che legge lei, così nessuno si sorprende.

---

# 4 · Artigiani e Scuole a 500 € — c'è un 150 scritto da qualche parte

**Nel pannello di oggi c'è scritto un altro numero.** `token.php:120`:

```php
	return isset( $s['token']['rif_artigiani'] ) && '' !== $s['token']['rif_artigiani']
		? $s['token']['rif_artigiani']
		: '150,00 €/anno';
```

È il promemoria che vedi quando registri un bonifico di un partner: **«150,00 €/anno»**.
Adesso dici **500 €**.

**Tre domande, una riga ciascuna:**

1. **500 € sostituisce 150 €/anno**, o è un'attivazione una tantum **più** un abbonamento
   annuale?
2. **È annuale o una volta sola?** Il codice oggi ragiona per **scadenze annuali**
   (`gs_art_scadenza`, gli avvisi a tre fasi che abbiamo appena sistemato): se i 500 € sono
   una tantum, **la scadenza non serve più** e tutta quella parte cambia significato.
3. **Vale per tutti e due?** Artigiani e Scuole hanno due importi tenuti apposta distinti.

**Buona notizia:** quel numero **non è scritto nel codice**, è un'impostazione che cambi dal
pannello. Se è solo il prezzo a cambiare, **non serve toccare niente** — lo scrivi tu.

---

# 5 · «Il link visibile all'esterno del sito nel web»

## Per gli Artigiani è già fatto, e fatto bene

`seo.php` riconosce già `?gs_art=slug` e per **ogni singola vetrina** genera titolo,
descrizione e dati strutturati suoi (`gs_seo_artigiano_post()`, riga 58). Quindi una vetrina
di artigiano condivisa su Facebook o trovata su Google **si presenta con il nome di
quell'artigiano**, non con il nome della pagina contenitore.

**Questa parte della tua richiesta è già in produzione.**

## Per le sfogline ci sono due cose da guardare

**La prima, e la sistemerei comunque.** `helpers.php:835`:

```php
	return add_query_arg( 'sfoglina', rawurlencode( $user->user_login ), get_permalink( $page_id ) );
```

L'indirizzo pubblico contiene **il nome utente con cui la sfoglina accede al sito**. Finché
quel link gira solo fra amiche è poco; **da settembre diventa un indirizzo pubblico,
indicizzato**, e a quel punto metà delle credenziali di ogni sfoglina è scritta in chiaro su
Google.

**Correzione:** usare uno slug separato (`gs_vetrina_slug`, generato dal nome visualizzato),
tenendo il vecchio indirizzo funzionante per non rompere i link già condivisi. **Non è
urgente come il reset, ma va fatto prima degli inviti**, perché dopo i link sono già in giro.

**La seconda: da verificare.** `seo.php` legge `?sfoglina=` (riga 91), quindi qualcosa fa —
**ma va guardato se genera titolo e descrizione per ogni singola sfoglina come per gli
artigiani, o se si ferma alla pagina generica.** Se si ferma lì, tutte le vetrine delle
sfogline si presentano uguali sul web, e il «link visibile all'esterno» vale la metà.

**Da controllare e riportare**, non da dare per scontato in nessuna delle due direzioni.

---

# 6 · Un difetto trovato leggendo questa parte

**File:** `includes/shortcodes.php:463-477` (`gs_ajax_vetrina_attiva_token()`) — VERIFICATO

```php
	if ( gs_vetrina_token_attivata( $uid ) ) { wp_send_json_error( … ); }
	$costo = gs_token_costo_vetrina();
	$saldo = gs_token_saldo( $uid );
	if ( $saldo < $costo ) { wp_send_json_error( … ); }
	gs_token_movimento( $uid, -$costo, 'consumo', 'Attivazione Vetrina pubblica' );
	update_user_meta( $uid, 'gs_vetrina_token_attiva', '1' );
```

**È di nuovo il leggi-controlla-scrivi**, sull'ottavo punto in cui il plugin muove qualcosa
che è stato comprato. Due richieste simultanee — un doppio clic su «Attiva la vetrina» —
leggono tutte e due «non attiva» e tutte e due lo stesso saldo: **la sfoglina paga due volte
per una vetrina sola.**

**Correzione:** la stessa già scritta tre volte, il lucchetto di MySQL come in L1/L2:

```php
	global $wpdb;
	$lock = 'gs_vetrina_' . (int) $uid;
	if ( '1' !== $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ) ) {
		wp_send_json_error( array( 'message' => 'Attivazione già in corso, attendi un istante.' ) );
	}
	wp_cache_delete( $uid, 'user_meta' );   // senza, si legge la fotografia di inizio richiesta
```

con il `RELEASE_LOCK` su **tutte** le uscite.

**Se si passa ai 50 € questa funzione cambia comunque**: fatela allora, ma **fatela** — non
lasciatela così pensando che tanto sparisce, perché finché c'è, funziona.

---

# 7 · La data: 1 ottobre o 1 novembre

**Da qui cambia poco**, ed è una buona notizia: l'interruttore del percorso mensile è nello
Stato Generale e lo accendi tu quando vuoi. **Il codice non ha una data scritta dentro.**

Le due sole cose legate alla data:

- **il primo resoconto vero arriva il mese dopo la partenza** — 1° novembre se parti a
  ottobre, 1° dicembre se parti a novembre;
- **il rendiconto della chiusura del mese in Posta interna** (`gs_inbox_crea()` alla fine di
  `gs_buono_sfoglia_chiudi_mese()`) **non è ancora stato scritto**, ed è dovuto **prima della
  prima chiusura vera**. Con il 1° novembre c'è tempo; con il 1° dicembre ce n'è di più.

**Gli inviti di settembre e le vetrine non dipendono dalla data del gaming.** Sono due cose
separate, e va bene che lo siano.

---

# Cosa serve da te, in ordine

**Oggi, e non richiede pensarci:**

1. **Apri Diagnostica e guarda l'elenco degli account.** È il Passo 1 del reset, è già
   installato, e non tocca niente.

**Prima degli inviti:**

2. **Il consenso: lo mettiamo nel modulo di iscrizione?** Io direi di sì, ed è la ragione
   per cui te lo chiedo adesso: dopo si rimedia solo con un'email a tutte.
3. **I 50 € sostituiscono i token per la vetrina, o li comprano?**
4. **I 500 €: sostituiscono i 150 €/anno? una tantum o annuale? per tutte e due le
   categorie?**

**Quando capita:**

5. Il nastro con tutti i nomi e solo alcuni cliccabili — **confermi che è così che lo
   vuoi?** Perché oggi fa una cosa diversa, e cambiarlo è una decisione, non una correzione.

---

## Cosa faccio io

Il difetto del punto 6 lo metto in coda con gli altri. E torno a `voting.php` e
`giuria-turno.php` — la seconda lettura non si ferma, ma **il reset viene prima di tutto il
resto**, compreso il mio lavoro: se le sfogline entrano prima che sia fatto, non si torna
indietro.
