# Istruzione: i sedici test che falliscono da prima

**Per Claude Code Ennio — 27/08/2026, scritta su 3.298.2**

**Una premessa che conta più del resto del documento:** questo file **non nasce da
un'ispezione dei test veri**. I `tests/` stanno fuori da questo repository — sul Mac, accanto
a `run.sh` — e nessuna sessione che lavora qui li ha mai aperti. Quello che segue è la
diagnosi **riferita** da una sessione che gira sul Mac e ha effettivamente lanciato
`./run.sh`, letta e riorganizzata in compiti. Chi riceve questo file deve **riverificare la
diagnosi aprendo i file veri**, non fidarsi che sia già corretta: può darsi che una causa sia
descritta in modo impreciso, o che ne manchi una.

Questo è diverso da tutti gli altri `ISTRUZIONE-*.md` di questo repository, che portano codice
esatto perché nascevano da un pacchetto `.zip` ispezionato riga per riga. Qui no: qui c'è una
mappa, non un tesoro già scavato.

## Cosa si sa, e da chi

Riferito il 27/08/2026: sedici file di test falliscono, e **nessuno di loro tocca il Reset**
(verificato indipendentemente per l'unica riga che lo riguardava: `gs_pannello_reset()` è
presente in entrambi i pannelli generali). Sono attribuiti a quattro cause:

1. **Un mock di test che non imposta il ruolo dell'utente finto** — la maggioranza dei sedici.
2. **Un refactor delle mail** di qualche giorno fa, non ancora riflesso in un test.
3. **Una pagina "Newsletter"** che un test si aspetta e non trova.
4. **La scansione dei due pannelli generali**: sette funzioni `gs_pannello_*()` risultano
   presenti in un solo pannello invece che in tutti e due.

## Il lavoro, in quattro pezzi

### 1. Il mock del ruolo

`./run.sh 2>&1 | grep -B2 -A10 FAIL` sui test coinvolti. Se davvero la causa è un helper di
mock che crea un utente finto senza `wp_set_current_user()` o senza un ruolo assegnato, la
correzione va fatta **in un solo posto** — l'helper condiviso, non test per test — o la
prossima volta che qualcuno scrive un test nuovo ricadrà nello stesso buco. Trova quell'helper
prima di toccare i singoli file.

### 2. Il refactor delle mail

Trova cosa è cambiato (`git log --oneline -- includes/*mail*` o simile, se il sorgente vero è
sotto controllo di versione) e allinea il test alla nuova forma. **Non il contrario**: se il
test descriveva un comportamento che qualcuno ha voluto cambiare apposta, il test va
aggiornato; se invece il refactor ha rotto qualcosa che doveva restare com'era, è un bug vero
e va segnalato, non zittito riscrivendo il test per farlo passare.

### 3. La pagina Newsletter

Prima domanda: la pagina manca perché **non è stata creata** (bug: dovrebbe esistere e non
esiste) o perché **è stata rimossa apposta** (il test è vecchio, va tolto o aggiornato)? La
funzione che dovrebbe crearla è probabilmente in `includes/menu-struttura.php` (il changelog di
altri lavori l'ha citata come responsabile della creazione delle pagine del sito). Guarda lì
prima di decidere in che direzione va la correzione.

### 4. I sette pannelli scoperti

Questa è la più delicata: **sette funzioni** `gs_pannello_*()` non sono nello stesso posto in
tutti e due i pannelli generali (Plancia e Pannello di Controllo). Non presumere che il difetto
sia nel test — potrebbe essere il contrario: sette zone del sito che il titolare non vede da
uno dei due pannelli. Elenca le sette, una per una, e per ciascuna guarda **a mano** se manca
davvero in uno dei due o se il test la cerca con un nome sbagliato. Non correggere in blocco:
sette voci diverse possono avere sette cause diverse.

---

## Quello che NON devi decidere da solo

Se il punto 3 o il punto 4 rivelano che manca davvero qualcosa **nel sito**, non solo nel
test — una pagina che dovrebbe esistere e non c'è, una zona del pannello che il titolare non
vede — quello non è un difetto di test: è un difetto del plugin, su cosa il titolare può fare o
vedere. **Scrivilo e fermati**, non deciderlo aggiustando il test per farlo tacere. Le cose che
il titolare vede o non vede nel suo pannello sono una scelta di Ennio, come tutto il resto.

---

## Come si prova

```bash
./run.sh 2>&1 | tee /tmp/prova-prima.txt
# dopo ogni correzione:
./run.sh 2>&1 | tee /tmp/prova-dopo.txt
diff /tmp/prova-prima.txt /tmp/prova-dopo.txt
```

Il numero dei test rossi deve **scendere**, mai risalire. Se una correzione ne fa passare uno
e ne rompe un altro che prima era verde, non è una correzione: è uno scambio, e va capito
perché prima di consegnare.

Alla fine, l'elenco dei sedici con l'esito di ognuno: corretto, non correggibile senza una
decisione di Ennio (con la domanda scritta), o scoperto che non era un difetto ma un test
vecchio da togliere (motivandolo).

## La consegna

Solo se qualcosa nel **sorgente del plugin** è stato toccato (non solo nei test): `php -l` sui
file toccati, versione nei tre punti, voce di changelog che dica **cosa non funzionava e per
chi** — se il punto 4 rivela zone del pannello scoperte, il changelog deve dirlo esplicitamente,
non «corretti alcuni test».

Se invece la correzione è tutta e sola dentro `tests/`, non serve bump di versione del plugin:
i test non fanno parte dello zip installabile (regola fissa di questo progetto, in `CLAUDE.md`).

## Una cosa da non fare

**Non riscrivere un test per farlo passare quando il difetto è nel plugin, non nel test.** Un
test verde che nasconde una zona del pannello che il titolare non vede è peggio di un test
rosso che lo dice.
