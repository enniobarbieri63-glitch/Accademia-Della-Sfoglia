# Le risposte di Ennio sul reset — e una cosa da non fare così com'è scritta

Ennio ha risposto alle tre domande (26/08/2026). Due si chiudono qui. La terza — *«se ne
trovi altri oltre i sei… cancellali»* — **non va eseguita nella forma in cui è scritta**, e
sotto c'è il perché e come farla invece.

---

## 1. Quando azzerare punti, messaggi e avanzamento → **adesso, quando vi fa comodo**

> *«li puoi azzerare quando vuoi, quando ritieni che non ti servono più questi dati per
> capire»*

**Non mi servono. Potete azzerare quando volete.**

Io leggo il codice, non i dati: nessuna delle voci trovate finora — C1, T1, T2, P1, L1, L2,
il bug del tema, lo sfarfallio — è uscita guardando cosa c'era nel database. L'unica cosa
per cui i dati sono serviti davvero è stata **confermare che il bug del tema faceva davvero
danno in produzione**, e quella conferma c'è già.

**Non aspettate me.** Se conviene farlo prima, fatelo prima.

---

## 2. Gli abbonamenti delle sfogline → **sono veri, non si toccano**

> *«sono veri, tranne alcune eccezioni che vengono fatte per gli amici o per giornalisti»*

`gs_abbonamento` e `gs_abbonamento_scadenza` **restano fuori dal reset**, per tutti — le
quote pagate e le cortesie insieme. Non c'è modo di distinguerle nel database e **non serve
distinguerle**: sono tutte scelte che Ennio ha già fatto una volta, e rifarle a mano
significherebbe rifare anche gli errori.

Vanno aggiunte all'elenco delle eccezioni:

```php
	'gs_abbonamento',
	'gs_abbonamento_scadenza',
	'gs_abbonamento_avviso_per',   // il contrassegno degli avvisi: senza, riparte l'avviso di scadenza
```

---

## 3. I dati delle persone restano

> *«I dati degli utenti si mantengono»*

Restano nome, email, data di nascita, genere, foto, biografia, preferenze di notifica, note
del gestore. **Si azzera il gioco, non le persone.** È quello che c'era già scritto nel
documento precedente.

---

# ⚠️ 4. «Cancellali» — così com'è scritto cancella anche i partner paganti

> *«i sei account rimangono, non si cancellano, se ne trovi altri oltre i sei forse sono 7
> gli utenti, si è aggiunta Rosemma che va mantenuta, cancellali»*

**Non eseguire questa frase alla lettera.** Tre ragioni, in ordine di gravità.

## a) Fra gli «altri» ci sono i partner che hanno pagato

Gli account degli **Artigiani della Pasta** e delle **Scuole di Cucina** sono account
WordPress normali, creati dal pannello. Non sono sfogline, quindi **sono esattamente fra
quelli «oltre i sei»** — e cancellarli vuol dire cancellare le persone che hanno versato
150 € l'anno.

E c'è di peggio: **`wp_delete_user()` in WordPress cancella anche tutti i contenuti di quella
persona**, se non gli si dice espressamente il contrario. Cancellare l'account di un partner
**porta via con sé la sua vetrina**, il testo, le foto e **il registro dei bonifici** — che è
l'unica contabilità che esiste.

Nella stessa categoria ci sono anche i **lettori** (`letture.php`) e, se ci sono, gli
**account con permessi da gestore**: docenti, collaboratori. E l'amministratore del sito.

## b) Il plugin, di proposito, non cancella mai un account

`sfogline-extra.php:690-697`, commento nel codice:

> *«"elimina" (nasconde, mai cancellazione vera)… in entrambi i casi l'account WordPress vero
> non viene mai toccato con `wp_delete_user()`, per restare coerenti con la regola del
> progetto "mai cancellazione definitiva"»*

**Non è una dimenticanza: è una regola scritta.** Eseguire «cancellali» significherebbe
uscire dal plugin e farlo a mano, con SQL o riga di comando — cioè proprio nel modo in cui
non si torna indietro.

E c'è già lo strumento che fa quello che serve: **`gs_status = 'eliminata'`** dalla scheda
della singola persona. L'account sparisce da Le Sfogline, dal nastro, dal contatore, dalle
classifiche, da tutto — **ma se domani si scopre che era una persona vera, torna indietro con
un clic.**

## c) Ennio stesso non è sicuro del numero

*«forse sono 7 gli utenti»*. **Non si cancellano account su un «forse».**

---

# Come si fa invece: elencare, far vedere, poi agire

## Passo 1 — L'elenco, senza cancellare niente

Da guru2 con una copia del sito vero, o in Diagnostica sul sito vero: **stampare tutti gli
utenti**, con quello che serve a riconoscerli:

| Colonna | Perché serve |
|---|---|
| nome e email | per riconoscerli |
| ruolo WordPress | amministratori e editor si vedono subito |
| `gs_status` | distingue sfogline, `artigiano`, `scuola_cucina`, `lettore` |
| ha una vetrina? | `gs_art_owner_post()` / `gs_scu_owner_post()` |
| `gs_abbonamento_scadenza` | chi ha un abbonamento vero |
| punti, ultimo accesso, data di iscrizione | dice chi ha usato il sito e chi no |

**Nessuna cancellazione in questo passo.** Solo l'elenco.

## Passo 2 — Le esclusioni, che valgono comunque

Qualunque cosa decida Ennio, **questi non si toccano mai**, e vanno tolti dall'elenco prima
ancora di mostrarglielo:

1. **chiunque abbia i permessi di amministratore** (`manage_options`);
2. **chiunque abbia `gs_status` = `artigiano`, `scuola_cucina` o `lettore`**;
3. **chiunque possieda una vetrina**, anche cestinata;
4. **chiunque abbia `gs_abbonamento_scadenza` valorizzata** — sono le quote vere e le
   cortesie di Ennio;
5. **chiunque abbia `gs_conta_come_sfoglina` acceso** — è l'interruttore manuale di Ennio su
   Rina Poletti e Bruno Cingolani.

## Passo 3 — Ennio guarda e segna

L'elenco che resta va messo davanti a Ennio **con i nomi**, non con i numeri. Lui segna chi
resta e chi no.

**Come punto di partenza**, i sei nomi che risultano dalla rettifica di luglio sono:
**Giuseppe Govoni, Bruno Cingolani, Eddy Ferrari, Patrizia Lai, Rina Poletti, Valeria** —
più **Rosemma**, che Ennio ha appena detto di tenere. **Ma questo elenco viene dai miei
appunti, non dal database: va confrontato con l'elenco vero, non usato al posto suo.**

## Passo 4 — Nascondere, non cancellare

Per chi Ennio segna: **`gs_status = 'eliminata'`**, dalla scheda personale o in blocco.

Sparisce da tutto, e si torna indietro. **Se fra un mese l'elenco è ancora giusto, allora si
può parlare di cancellare davvero** — con l'elenco in mano e un backup accanto, non stasera.

**Se Ennio insiste per la cancellazione vera adesso**, allora almeno:
`wp_delete_user( $id, $id_di_ennio )` — il secondo argomento riassegna i contenuti invece di
distruggerli. **Mai `wp_delete_user( $id )` da solo.**

---

# 5. «Azzera i percorsi» — chiarito da Ennio, e vuol dire più di una chiave

Avevo chiesto quale delle due letture fosse quella giusta. **Ennio ha risposto il
26/08/2026:**

> *«i percorsi fatti dagli utenti nel gaming, non i percorsi studiati per realizzare il
> gaming»*

**Quindi:**

| | |
|---|---|
| **Si azzera** | l'avanzamento delle sfogline: quali lezioni hanno visto, quali badge di percorso hanno preso, il Diploma Finale |
| **Non si tocca** | i **Percorsi Guidati** costruiti da Ennio — «Dalla sfoglia ai tortellini» e gli altri — con dentro le lezioni video, l'ordine, i livelli, le finestre stagionali |

**`gs_percorso_lezioni` e `gs_lezione` restano dove sono.** Sono contenuto, non dati degli
utenti: rifarli vorrebbe dire rifare mesi di lavoro. **La domanda è chiusa, non riaprirla.**

## Cosa vuol dire «azzerare l'avanzamento», in pratica

Non è una chiave sola, ed è il punto in cui un elenco scritto a mano sbaglia. L'avanzamento
di una sfoglina in un percorso è sparso in **sei posti diversi**, e due di questi **sono
appena stati creati** dalle correzioni L1 e L2:

| Chiave | Cosa tiene |
|---|---|
| `gs_lezioni_viste` | l'elenco delle lezioni guardate — **è questo che decide se un percorso è completo** |
| `gs_lezione_vista_<ID>` | **nuova (L1)**, una chiave per ogni lezione guardata |
| `gs_badges` | contiene le voci `percorso_<ID>` e `percorsi_tutti_completi` |
| `gs_badge_label_percorso_<ID>` | la scritta del badge, una per percorso |
| `gs_badge_dato_<chiave>` | **nuova (L2)**, una per ogni badge assegnato |
| `gs_badges_log` | quando ogni badge è stato preso |

**Quattro di queste sei hanno il nome costruito al momento**, e cambiano a ogni percorso e a
ogni lezione. **È esattamente la trappola già descritta**: un elenco di chiavi da cancellare,
compilato leggendo il codice, ne mancherebbe la metà — e una sfoglina si ritroverebbe i
badge di percorso addosso pur avendo l'avanzamento azzerato, oppure il contrario.

**Vale la regola già fissata: cancellare per prefisso `gs_`, tenendo l'elenco corto delle
eccezioni.** Fatto così, l'avanzamento sparisce tutto insieme senza doverlo elencare.

## Una conseguenza da sapere

I **certificati di percorso** e il **Diploma Finale** non sono file salvati: si generano al
momento leggendo il badge (`gs_certificato_percorso_html()`,
`gs_certificato_diploma_finale_html()`). **Azzerato il badge, il certificato non si apre
più** — anche se qualcuno ne aveva salvato il collegamento.

È corretto che sia così — un diploma di un percorso mai fatto non deve restare stampabile —
ma se qualcuno l'ha già stampato e chiede perché il collegamento non funziona più, la
risposta è questa.

---

## Riepilogo per Claude Code

| | |
|---|---|
| Punti, messaggi, avanzamento | ✅ azzerabili quando volete, non aspettate me |
| Abbonamenti delle sfogline | 🚫 **non toccare**, sono veri (quote e cortesie insieme) |
| Dati personali delle persone | 🚫 non toccare |
| Account «oltre i sei» | ⚠️ **elencare → escludere → far vedere a Ennio → nascondere**, non cancellare |
| Partner, lettori, amministratori | 🚫 **mai**, in nessuna forma |
| Avanzamento nei percorsi (lezioni viste, badge, diploma) | ✅ si azzera — **sei chiavi, quattro col nome costruito: vedi il punto 5** |
| Percorsi Guidati e Lezioni Video | 🚫 **non toccare**, sono contenuto di Ennio (confermato 26/08) |

**La prima cosa da fare è il Passo 1: l'elenco.** Non richiede nessuna decisione e sblocca
tutto il resto.
