# 3.282.2 verificata · I prezzi 490 e 49 · «Nessun dato di accesso in rete»

---

# 1 · La 3.282.2 va bene

Diff contro la 3.282.1: tocca solo `gaming.js`, il file principale e il changelog. Sintassi
pulita.

- **260 → 320 ms nel Pannello:** fatto, con il commento che dice perché.
- **Soglia raddoppiata a due schermate:** applicata bene (`window.innerHeight * 2`), e il
  commento registra che è un raddoppio chiesto da Ennio lo stesso giorno — così fra un mese
  non sembra un numero uscito dal nulla.

**Niente da rifare.** Una sola raccomandazione, che vale il doppio adesso: **provala sul
telefono.** Due schermate di risalita col dito sono parecchie, e la finestra su un telefono
è più piccola *e* cambia da sola quando il browser nasconde la barra degli indirizzi. Se a
Ennio sembra troppo, il numero da toccare è uno solo.

---

# 2 · Sull'accesso al sito: risposta chiara, e va bene così

Le tre righe sono esaurienti e la distinzione è quella giusta: **non riga di comando, non
database — il browser di Ennio, con la sua sessione, aperta da lui.** Nessuna password,
niente di permanente.

**E l'ultima frase è quella che conta:**

> *«tecnicamente potrei eseguire un'azione di scrittura tramite quel browser senza che
> nessuno la rilegga prima… è giusto che sia scritto, non sottinteso»*

**È esattamente il punto**, ed è stato colto senza che servisse insistere. Con l'impegno a
seguire comunque la procedura del reset e a non usare quell'accesso per azioni in massa, per
me la questione è chiusa.

**Una sola cosa da tenere presente, non da cambiare:** un clic fatto da lì è indistinguibile,
nei registri del sito, da un clic fatto da Ennio. Se un giorno qualcosa risultasse cambiato
e nessuno ricordasse chi, **quella è l'unica ambiguità che questo accesso introduce.** Basta
saperlo.

---

# 3 · I prezzi: 490 € e 49 €, che sostituiscono gli attuali

**Le due cose costano lavoro molto diverso**, e vale la pena separarle subito.

## 490 € — non è codice, è una casella

Il «150,00 €/anno» che vedi oggi **non è scritto nel codice**: è un'impostazione, e il codice
la usa solo come promemoria quando registri un bonifico. `token.php:281` lo dice:

> *«I due importi qui sotto sono solo un riferimento per te: non si scalano da nessun saldo,
> servono a ricordarti quanto chiedere.»*

**Lo cambi tu, adesso, in due minuti**, nel pannello dei token: due caselle, *«Riferimento
abbonamento annuale — Artigiani della Pasta»* e la gemella per le Scuole. Scrivi `490,00
€/anno` e hai finito. **Nessuno zip, nessuna attesa.**

**Resta una cosa che non hai detto, e la assumo — correggimi se sbaglio:** poiché i 490
*sostituiscono* i 150 **€/anno**, resta **annuale**. Tutto il meccanismo delle scadenze e
degli avvisi a tre fasi che abbiamo appena sistemato continua a valere. **Se invece i 490
fossero una volta sola, quel meccanismo perderebbe senso** e andrebbe ripensato — è una riga
di risposta, non un problema.

## 49 € — questo invece è un cambiamento vero

Oggi la vetrina di una sfoglina **non si sblocca con dei soldi: si sblocca spendendo token**
(`gs_ajax_vetrina_attiva_token()`, 5 token di norma). I 49 € sostituiscono quel meccanismo,
quindi va costruito il pezzo che oggi non c'è:

1. **Un modulo per registrare il contributo di 49 €** su una singola sfoglina — nella sua
   scheda personale, dove già c'è tutto il resto;
2. **che sblocca la vetrina** senza nessun secondo passaggio (il tuo «in automatico»);
3. **con le stesse tre protezioni di P1**, perché sono soldi: identificativo contro il doppio
   clic, importo maggiore di zero, e una riga nel registro dei pagamenti — così l'anno
   prossimo si sa chi ha versato e quando;
4. **e la vecchia attivazione a token va tolta**, non lasciata accanto: due modi di sbloccare
   la stessa cosa sono due modi di sbagliare.

### Una fortuna da sfruttare: farlo subito dopo il reset non costa niente

Se questo cambio si facesse **fra sei mesi**, ci sarebbero sfogline con la vetrina già
attivata coi token, e bisognerebbe decidere cosa farne — riconoscere il pagamento vecchio,
convertirlo, chiederglielo. **Fatto subito dopo il reset, nessuna ha niente**: si passa da un
sistema all'altro senza nessun caso particolare da gestire.

**È il momento buono. Fra un mese non lo è più.**

### E c'è un difetto lì dentro da chiudere comunque

`shortcodes.php:463` — segnalato nel documento precedente: **doppio clic su «Attiva la
vetrina» = token scalati due volte.** Se quella funzione sparisce con i 49 €, sparisce anche
il difetto. **Ma finché c'è, funziona** — e se il cambio slitta, va corretto.

---

# 4 · «Nessun dato personale di accesso deve andare in rete»

**Ho preso questa come una regola e sono andato a cercare tutti i punti, non solo quello che
avevo trovato.** Sono tre. **Solo uno è nel plugin.**

## a) L'indirizzo della vetrina — dentro il plugin

`helpers.php:835`, quello che avevo già segnalato:

```php
	return add_query_arg( 'sfoglina', rawurlencode( $user->user_login ), get_permalink( $page_id ) );
```

E il nome utente **se lo sceglie la sfoglina quando si registra** (`registration.php:28`):
non è un codice generato, è **la metà del suo accesso, scelta da lei.**

## b) Le pagine «autore» del tema — fuori dal plugin, e sono la falla più larga

WordPress crea per ogni utente una pagina `/author/<nome-utente>/`, e chiunque può scoprire
i nomi utente provando `?author=1`, `?author=2` e così via: il sito **risponde con un
rimando all'indirizzo che contiene il nome vero.**

**E su questo sito quelle pagine ci sono di sicuro**, perché è proprio la funzione del tema
che le costruisce — `the_newspaper_post_author_archive()` — quella che ha causato il difetto
delle ricerche per autore. **La conosciamo già: l'abbiamo aggirata due giorni fa.**

## c) `wp-json/wp/v2/users` — fuori dal plugin

L'interfaccia standard di WordPress: chiamandola senza essere collegati **restituisce l'elenco
degli utenti che hanno pubblicato articoli**, con il loro identificativo pubblico. I contenuti
del plugin non ci finiscono (i suoi tipi non sono esposti lì), **ma Ennio, Rina e chiunque
abbia scritto un articolo sì.**

## La correzione che chiude tutti e tre insieme

**Non serve intervenire in tre modi diversi.** Tutte e tre queste vie espongono la stessa
cosa — l'identificativo pubblico che WordPress ricava dal nome utente — e WordPress permette
di **staccarlo dal nome utente**. Si chiama `user_nicename`, ed è un campo a parte che si può
cambiare senza toccare le credenziali.

**La regola:**

```php
// L'identificativo pubblico (user_nicename) è quello che finisce in
// /author/…, in wp-json e nel link della vetrina. Di norma WordPress lo
// ricava dal nome utente — cioè pubblica metà delle credenziali. Qui lo
// staccamo: si costruisce dal nome VISUALIZZATO, che è pubblico per sua
// natura. E se per caso coincidesse col nome utente, si aggiunge una coda
// casuale: l'identificativo pubblico non deve MAI essere uguale a quello
// con cui si entra (Ennio, 26/08/2026: "nessun dato personale di accesso
// deve andare in rete").
$pubblico = sanitize_title( $u->display_name );
if ( ! $pubblico || $pubblico === sanitize_title( $u->user_login ) ) {
	$pubblico = ( $pubblico ? $pubblico : 'sfoglina' ) . '-' . wp_generate_password( 6, false, false );
}
wp_update_user( array( 'ID' => $u->ID, 'user_nicename' => $pubblico ) );
```

Poi **`gs_vetrina_url()` usa `user_nicename` al posto di `user_login`**, e chi legge
`?sfoglina=` cerca per identificativo pubblico.

**Da fare due volte:**
- **una volta su tutti gli account che ci sono adesso** (poche decine — e conviene farlo
  **insieme al reset**, che è già un momento in cui si passa su tutti);
- **e a ogni nuova registrazione**, dentro `gs_ajax_registrazione()`, subito dopo aver creato
  l'account — altrimenti da settembre si ricomincia ad accumulare il problema.

**Compromesso da dichiarare:** cambiare `user_nicename` **cambia l'indirizzo delle pagine
autore** di quelle persone. Se qualcuno aveva salvato o condiviso uno di quei link, smette di
funzionare. Sono pagine che nessuno condivide di proposito, e **oggi il gaming è spento**:
non ci sarà mai un momento più indolore di questo.

## Due cose che restano da decidere, e sono di Ennio

1. **Le pagine `/author/…` servono?** Se non le usa nessuno, la cosa più pulita è
   **spegnerle del tutto** (rimando alla pagina «Le Sfogline», che è la versione buona della
   stessa idea). Chiudere una porta è meglio che rendere illeggibile la targhetta.
2. **`wp-json/wp/v2/users` si può chiudere ai non collegati** con poche righe. **Ma va
   provato prima**: se un domani si aggiunge un'app o un servizio che legge il sito da fuori,
   quella è la porta che userebbe. Oggi non c'è niente del genere — **è una decisione, non un
   automatismo.**

---

# 5 · Resta aperta la domanda con la scadenza

**Il consenso nel modulo di iscrizione.** È l'unica delle mie domande che non ha ancora
risposta, ed è l'unica che **scade**: da settembre le sfogline si iscrivono, e la domanda va
fatta a loro **mentre compilano**, non dopo con un'email a tutte.

E adesso si lega a questa pagina: se i nomi di tutte le iscritte scorrono su un nastro
pubblico, **il consenso è la cosa che decide se il nome di una persona finisce in rete.** È
la stessa regola che hai appena dato, applicata ai nomi invece che alle credenziali.

---

# Riepilogo

| | Chi | Quando |
|---|---|---|
| **490 €** nelle due caselle del pannello | **Ennio** | oggi, due minuti |
| Confermare che i 490 restano **annuali** | Ennio | una riga |
| **Il reset** (elenco già in Diagnostica) | Ennio + Claude Code | **prima degli inviti** |
| `user_nicename` staccato dal nome utente, su tutti | Claude Code | **insieme al reset** |
| Lo stesso a ogni nuova registrazione | Claude Code | prima degli inviti |
| **Il consenso nel modulo di iscrizione** | **Ennio decide** | prima degli inviti |
| I 49 € al posto dei token | Claude Code | subito **dopo** il reset |
| Pagine `/author/` e `wp-json/users` | Ennio decide | quando capita |

**Le tre cose con una scadenza vera sono: il reset, il consenso, e l'identificativo
pubblico.** Tutto il resto può aspettare senza costi.
