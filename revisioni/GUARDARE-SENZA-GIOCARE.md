# «Guardare senza giocare»: quanto costa, e cosa ha scoperto la domanda

**Per Ennio — 26/08/2026, misurato su 3.290.0**

Ennio chiede: dopo i trenta giorni, si può lasciare che una sfoglina che non ha fatto il bonifico **guardi** senza poter giocare, magari potendo solo commentare?

**La risposta è sì, ed è più semplice del cancello che è appena stato costruito** — perché è quasi tutto già fatto. Ma cercando la risposta ho trovato un buco che va chiuso in ogni caso, e che questa idea renderebbe grande.

---

## Perché è già quasi fatto

Il congelamento è costruito su **due meccanismi distinti**, e questo è stato un colpo di fortuna nella progettazione:

| | cosa fa | quanti punti tocca |
|---|---|---|
| `gs_gate_riservato()` | chiude la **pagina** | 25 |
| `gs_puo_partecipare()` | rifiuta l'**azione** | 27 |
| il cancello in `gs_add_points()` | non dà **punti** | 1, il passaggio obbligato |

«Guardare senza giocare» **è già il secondo e il terzo**. L'unica cosa da togliere è il primo.

In pratica: `gs_gate_riservato()` smette di sbarrare la pagina a una congelata e le mostra invece una fascia in cima — *«stai guardando: per partecipare serve il contributo»* — e tutto il resto resta esattamente com'è. Le azioni continuano a rifiutarla perché `gs_puo_partecipare()` non cambia; i punti continuano a non arrivarle perché il cancello dei punti non cambia.

**Non è aggiungere una funzione: è togliere una restrizione da una sola funzione.**

---

## Ma prima va chiuso un buco, e questa domanda me l'ha fatto trovare

`gs_puo_partecipare()` è collegata a **27 handler**. Ma le azioni che scrivono qualcosa non sono 27: sono una quarantina. **Venti controllano ancora solo `gs_is_approved()`**, cioè «sei una socia approvata?» — e una congelata lo è ancora, perché il congelamento non le toglie l'approvazione.

Di quei venti, nove sono le eccezioni volute (Aiuto, Calendario, Biografia, i due cestini dei partner). **Gli altri undici sono aperti per sbaglio:**

```
voting.php:216   gs_ajax_invia_sfoglia         ← pubblicare una sfoglia
voting.php:297   gs_ajax_vota                  ← votare
voting.php:418   gs_ajax_sfoglia_commento      ← commentare
sondaggi.php:211 gs_ajax_sondaggio_vota
sondaggi.php:282 gs_ajax_sondaggio_proponi
forms.php:15     gs_ajax_aggiungi_diario
forms.php:98     gs_ajax_aggiungi_consiglio
esperti.php:365  gs_ajax_esperto_domanda
esperti.php:431  gs_ajax_esperto_domanda_privata   ← spende un token: sono soldi
conversazioni.php:646 gs_ajax_conv_sfoglina_richiesta ← idem
compleanni.php:150 gs_ajax_augurio_invia
```

**I primi tre sono il cuore del gaming**: pubblicare, votare, commentare. Sono rimasti indietro perché stanno in `voting.php`, che usava già il controllo forte del vecchio mondo — e nel vecchio mondo `gs_is_approved()` *era* il controllo giusto.

**È un errore mio, ed è il terzo della stessa famiglia in due giorni.** Avevo detto: «31 handler hanno il controllo debole, 7 hanno già quello giusto». Era vero quando l'ho scritto. Poi è nato il congelamento e **ha cambiato cosa vuol dire "giusto"** — e io non sono tornato indietro a dirlo. I sette «già a posto» non lo erano più.

### Quanto è grave oggi

**Poco, ma non zero.** Le pagine sono chiuse, quindi per arrivare a quegli undici serve una scheda del browser rimasta aperta da prima della scadenza. Non è un'apertura pubblica.

Ma due di quegli undici **spendono un token**, e i token sono soldi versati con bonifico. Una congelata che manda una domanda privata da una scheda vecchia paga con un credito che non dovrebbe più poter usare.

### Quanto diventa grave con «guardare senza giocare»

**Totale.** Nel momento in cui le pagine si aprono, quegli undici diventano il gioco intero: una sfoglina che non ha pagato potrebbe pubblicare le sue sfoglie, votare, fare i sondaggi, scrivere il diario e i consigli. Senza punti, ma facendo tutto.

**Quindi la risposta alla tua domanda è: sì, è facile — ma il primo passo non è aprire le pagine, è finire di collegare il cancello delle azioni.** Undici righe, una per handler, tutte identiche a quelle già scritte.

---

## E qui viene la parte bella

Una volta chiusi quegli undici, **la tua domanda diventa una scelta a mano, non un lavoro**:

Quei trentotto punti sono un elenco. Per ciascuno si decide se una che guarda può farlo o no. **«Guardare e solo commentare» vuol dire lasciare `gs_is_approved()` su uno solo — `voting.php:418`, il commento — e mettere `gs_puo_partecipare()` su tutti gli altri.**

Non c'è niente da progettare: c'è da spuntare una lista. E se un domani cambi idea — «facciamole anche votare», «anche il diario» — è una riga per volta, senza toccare nient'altro.

---

## Le due cose che devi decidere tu, e non sono tecniche

### 1. I pulsanti che non fanno niente

Se le pagine si aprono, ogni pagina mostrerà i suoi pulsanti — «Vota», «Invia», «Partecipa» — che adesso rispondono con un rifiuto. Ci sono due strade:

**Nascondere i pulsanti** su ogni pagina. È il lavoro vero di questa idea: una ventina di pagine da toccare a mano, ognuna diversa. Non è difficile, è lungo.

**Lasciarli, con il messaggio giusto.** *«Per partecipare serve il contributo di 29 euro a sostegno dell'Accademia»* al posto di un errore secco.

Io ti consiglio la seconda, e non per pigrizia. **Una porta chiusa non racconta niente. Una pagina che si vede, con quella frase su ogni pulsante, racconta ogni giorno esattamente cosa si sta perdendo** — e lo racconta nel momento in cui la voglia di fare quella cosa c'è. È un invito, non un ostacolo. Una porta chiusa la si dimentica in una settimana.

Se poi vedi che dà fastidio, nascondere i pulsanti si può sempre fare dopo, una pagina per volta.

### 2. Il commento vale 5 punti — e va bene così

`commento_sfida` dà 5 punti. Ma il cancello dei punti li blocca già a una congelata: **commenta e non prende niente**, senza che nessuno debba scrivere una riga in più. Il messaggio però le dirà «+5 punti», e quello va corretto — è lo stesso caso del diario, dove la promessa scritta non si avvera più.

Vale la pena dire una cosa sul merito, non sul codice: **una che commenta è una che torna.** Chi resta a leggere e scrivere sotto le sfoglie delle altre è molto più probabile che faccia il bonifico di una che ha trovato la porta chiusa e non è più tornata. Se l'idea era questa, è un'idea giusta.

---

## Il conto onesto

| | |
|---|---|
| **Chiudere gli undici handler** (da fare comunque, che tu faccia o no il resto) | mezza giornata |
| **Aprire le pagine a chi guarda** | una funzione, poche righe |
| **La fascia «stai guardando» in cima** | poche righe |
| **Scegliere cosa resta permesso** (il commento, o altro) | una lista da spuntare |
| **I messaggi dei pulsanti** | un pomeriggio, se scegli di lasciarli visibili |
| **Nascondere i pulsanti pagina per pagina** | ~20 pagine, se scegli quella strada |

**Senza l'ultima riga, è un giorno di lavoro.** Con l'ultima, è tre o quattro.

**Ma non farlo adesso.** Gli undici handler sì, quelli vanno chiusi subito perché sono un buco vero. Il resto no: mancano ancora le email di scadenza — che adesso sono l'unico posto dove una congelata legge come rientrare — gli undici testi e la mail di benvenuto. **Quelle hanno la scadenza di settembre; questa idea no.**

E c'è una ragione in più per aspettare: se apri le pagine, la mail di scadenza deve dire una cosa diversa («puoi continuare a guardare e a commentare») invece che «l'accesso cessa». Scriverla due volte è lavoro buttato. **Decidi questa cosa prima che scrivano quelle email, non dopo.**

Se mi dici di sì, scrivo l'istruzione. È corta.
