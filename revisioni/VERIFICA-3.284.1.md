# 3.284.1 verificata — tutto applicato. Una cosa non c'è, e una da tenere a mente per dopo.

Diff contro la 3.283.2. `php -l` pulito su tutti e cinque i file, **e nessun file di prova
nel pacchetto** (controllato: in `includes/` non c'è nessun `test-*.php`, nonostante ne
abbia creati tre durante il lavoro).

**V1, V3, G1, G2 e le mail distanziate: tutte applicate, e applicate bene.**

---

## Due cose fatte meglio di come le avevo chieste

**1. Su V3 ha verificato una cosa che io non avevo controllato.** Avevo detto «confronta due
date passate entrambe per `strtotime()`». Il codice lo fa — **e aggiunge questo avviso**:

> *«`gs_data_fine` è già salvata con l'orario (`Y-m-d H:i`, vedi `pianificazione-anno.php`):
> non aggiungere altro qui, altrimenti la stringa diventa "…23:59 23:59:59" e `strtotime()`
> la legge male.»*

**È esattamente l'errore che avrebbe fatto una correzione frettolosa** — la mia proposta
generica su P3 suggeriva proprio di aggiungere `' 23:59:59'`. **Andato a guardare dove quel
dato viene scritto, invece di fidarsi del nome del campo.**

**2. Le descrizioni nel pannello sono state aggiornate.** Dicevano *«Parte da sola quando
approvi una sfoglina»*, e adesso dicono *«Parte da sola 5 giorni dopo l'approvazione (terza
mail…)»*. **Non l'avevo chiesto**, e sarebbero rimaste lì a mentire a chi apre quel pannello
fra sei mesi.

---

# 1 · M1 non è entrata

`sfoglia-misurata.php` **non è fra i file toccati.** Era previsto — la mia segnalazione è
arrivata mentre le correzioni erano già scritte.

**Non è urgente**, ma è **una riga sola**: portare `gs_add_points()` dentro l'`if` a
`sfoglia-misurata.php:158`, esattamente come è appena stato fatto a `giuria-turno.php:246`.

**Mettila nel prossimo giro, qualunque esso sia.** È l'unica cosa che separa quel file
dall'essere identico al suo gemello già corretto, e le voci che restano sole si perdono.

---

# 2 · Una conseguenza della mia istruzione, da tenere a mente quando si costruisce il mese di prova

**Non è un difetto della 3.284.1**, ed è meglio dirlo adesso che scoprirlo dopo.

Il vecchio commento di `mail-area-riservata.php` diceva:

> *«appena una sfoglina viene approvata, parte il mese di prova — quindi la mail parte nello
> stesso momento (Ennio, 19/08/2026)»*

**Quella mail adesso parte al giorno 5.** Ho controllato: **oggi non è un problema**, perché
nel testo di quella mail il mese di prova non è ancora nominato — l'ho cercato, non c'è.

**Ma lo diventa appena si costruiscono i trenta giorni.** Se le date della prova finissero
in quella mail, la sfoglina saprebbe di avere trenta giorni **quando cinque sono già
passati.**

**La regola, da scrivere adesso perché serve dopo:**

> **Le date del mese di prova vanno nella mail del giorno 0** — quella di presentazione,
> che oggi dice solo *«la tua iscrizione è stata approvata»*. **Le altre due possono
> ricordarle, non annunciarle.**

**È una conseguenza di una mia istruzione**: ho fatto spostare a giorno 5 proprio la mail che
porta quell'informazione, senza accorgermene. **Meglio scritto qui che scoperto a ottobre.**

---

# 3 · Sul nastro, che non era nel piano

`nastro-vetrine.php` è cambiato per una richiesta tua diretta — via il fondatore, e tre
sponsor a rotazione invece di uno. **Il codice è pulito**: `gs_nastro_intervalla()` adesso
ruota (`$i % $n`), con la protezione per l'elenco vuoto.

**Una cosa piccola, non urgente.** I tre loghi non sono presi allo stesso modo:

```php
	'foto' => GS_URL . 'assets/img/mulino-marino-logo.png',                        // dentro il plugin
	'foto' => 'https://accademiadellasfoglia.it/wp-content/uploads/2026/08/…png',  // dal sito
	'foto' => 'https://accademiadellasfoglia.it/wp-content/uploads/2026/08/…svg',  // dal sito
```

Il primo viaggia con il plugin, gli altri due sono indirizzi fissi al sito vero. **Funziona**,
ma vuol dire due cose: su guru2 quei due loghi si caricano dal sito di produzione, e **se un
domani si cambia hosting o si riordinano i caricamenti, due sponsor su tre spariscono dal
nastro senza che nessuno lo colleghi a quella modifica.**

**Se è facile, metti anche quei due in `assets/img/` come il primo.** Se no, va bene così —
basta saperlo.

---

# 4 · Una domanda, per curiosità

`mail-area-riservata.php:475`:

```php
	$indirizzo_prova = 'info@lentium.it';
```

L'indirizzo per l'invio di prova è **scritto fisso nel codice.** Se è il tuo va benissimo —
**ma allora vale la pena che sia l'email dell'amministratore del sito** (`get_option(
'admin_email' )`), così se un domani cambia non resta una prova che parte verso un indirizzo
di cui nessuno si ricorda.

**Domanda, non segnalazione.**

---

# Riepilogo

| | Stato |
|---|---|
| **V1** · marcatore prima dei premi + avviso in Posta interna | ✅ e provato con doppio giro reale |
| **V3** · fuso orario sulla chiusura | ✅ **con una verifica in più su `gs_data_fine`** |
| **G1** · punti dentro il controllo del badge | ✅ |
| **G2** · media precalcolata | ✅ |
| **Mail distanziate** (giorno 0 / 2 / 5) | ✅ sul cron giornaliero, con contrassegno prima dell'invio |
| Descrizioni nel pannello aggiornate | ✅ non richiesto |
| Nessun file di prova nel pacchetto | ✅ |
| **M1** · il gemello in `sfoglia-misurata.php` | ⏸ **prossimo giro, una riga** |
| Le date della prova nella mail del giorno 0 | 📌 **regola da ricordare quando si costruiscono i 30 giorni** |
| Due loghi sponsor con indirizzo fisso | ⚠ quando capita |
| `info@lentium.it` fisso nel codice | ❓ domanda |

**Niente della 3.284.1 va rifatto. Si può installare.**
