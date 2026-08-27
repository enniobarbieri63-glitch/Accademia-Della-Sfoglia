# Come si lavora su questo progetto

## La regola fissa: ogni lavoro finisce in un file di istruzioni

**Quando trovo qualcosa da fare, non lo racconto soltanto: scrivo il file di istruzioni da
consegnare alla sessione che lo farà.** Sempre, senza che Ennio debba chiederlo.

Il motivo è il modo in cui si lavora qui: una sessione verifica, un'altra esegue, e fra le due
non c'è memoria condivisa — c'è solo il file. Un elenco di problemi scritto in chat va perso
quando la chat si chiude; un file resta nel repository, si apre e si copia, e la sessione che
lo riceve insieme allo zip sa cosa fare senza doversi ricordare niente.

### Il nome

`ISTRUZIONE-<COSA>.md`, in maiuscolo, nella radice del repository. Accanto ai due che ci sono
già: `ISTRUZIONE-IL-RESET.md`, `ISTRUZIONE-LE-CORREZIONI-AL-RESET.md`.

Le verifiche di un pacchetto consegnato si chiamano invece `VERIFICA-<versione>.md`: dicono
cosa ho controllato, non cosa fare.

### Cosa ci va dentro, sempre

1. **A chi è scritto e su quale versione**, in cima. Chi lo legge fra sei mesi deve sapere
   contro cosa era vero.
2. **Il codice esatto da incollare**, non la descrizione della modifica. Con il commento che
   dice **perché**, destinato a chi lo leggerà fra un anno.
3. **Il punto preciso** dove va: file, funzione, prima o dopo cosa.
4. **Quello che la sessione NON deve decidere da sola**, in una sezione a parte, con la mia
   proposta accanto e l'istruzione di aspettare la risposta di Ennio. Le decisioni sono di
   Ennio: le persone, i soldi, e cosa si tiene di quello che le sfogline hanno scritto.
5. **Come si prova**, in prove concrete e numerate, con dati veri — non «verificare che
   funzioni».
6. **La consegna**: lint, versione nei tre punti, voce di changelog che dice *cosa sarebbe
   successo* e non «corretti alcuni bug».
7. **Una cosa da non fare**, in fondo, quando l'operazione è irreversibile.

### Le tre cose che non cambiano mai

- **Nessun utente si cancella.** Mai, per nessun motivo, in nessuno script. Le persone le
  decide Ennio, una alla volta, guardando i nomi.
- **Niente cancellazioni definitive** se non nel Reset, che è irreversibile per progetto (e per
  questo chiede il backup e la parola digitata a mano).
- **Si verifica censendo, non rileggendo.** Rileggere una lista conferma quello che c'è; il
  censimento trova quello che manca — ed è quello che manca a non dare nessun errore.

## Il progetto

Plugin WordPress `gaming-sfogline` per l'Accademia della Sfoglia (Rina Poletti). Il codice
vero vive fuori da questo repository e arriva qui come pacchetto `.zip` da verificare; questo
repository tiene i documenti — le istruzioni, le verifiche — e il canvas di design.

I `tests/` stanno fuori dalla cartella del plugin, accanto a `run.sh`: nello zip installabile
non devono entrare.
