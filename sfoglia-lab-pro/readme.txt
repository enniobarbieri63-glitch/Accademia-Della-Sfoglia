=== Sfoglia Lab — Pro ===
Contributors: accademiadellasfoglia
Tags: education, courses
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPL-2.0-or-later

Corsi professionali per chef e pasticceri, iscrizione con acconto,
percorso di certificazione a livelli con elenco pubblico.

== Descrizione ==

Prima versione di lavoro (0.1.0), corrisponde ai cantieri 6-9 del
progetto (docs/01-progetto-sfoglia-lab.md). Progetto indipendente sia da
"Gaming Sfogline" sia da "Sfoglia Lab — Aula": installabile insieme a
entrambi sullo stesso sito, senza dipendenze tra i tre.

Cosa c'è in questa versione:

* Catalogo dei corsi (PRO-1, PRO-2, PAS-1, PAS-2, GES-1, CER-1, AZ-1, ESA)
  con i prezzi reali del documento di progetto, non segnaposto.
* Il gestore programma sessioni (date) per un corso del catalogo, con
  posti disponibili calcolati in automatico.
* Iscrizione pubblica: nome, email, telefono; calcolo dell'acconto al 30%
  (non rimborsabile, come da documento di progetto).
* Registrazione dei pagamenti (bonifico) da parte del gestore: storico
  che si aggiunge, non si sovrascrive mai — stesso principio già
  collaudato in Gaming Sfogline per gli sponsor partner.
* L'iscrizione passa automaticamente a "confermata" quando il versato
  raggiunge l'acconto dovuto.
* Email automatiche (da accendere in Impostazioni, dopo aver messo
  l'IBAN): conferma con le coordinate a chi si iscrive, avviso al gestore
  di ogni nuova iscrizione, conferma quando il bonifico è registrato.
* Sessioni che si possono chiudere o annullare: spariscono dal catalogo
  pubblico e non accettano più iscrizioni, ma restano nel pannello con i
  loro iscritti.
* Iscrizioni annullabili quando qualcuno si ritira: il posto torna
  libero, lo storico dei pagamenti resta.
* Elenco dei partecipanti di una sessione in CSV (con BOM e punto e
  virgola, per aprirsi bene in Excel italiano), da stampare il giorno del
  corso: chi ha versato quanto e chi deve ancora saldare.
* Rilascio degli attestati dal pannello, con la spunta che dice se la
  persona autorizza la pubblicazione del proprio nome.
* Percorso di certificazione a quattro livelli (Praticante, Addetto,
  Specialista, Maestro), numero progressivo dell'attestato, validità di
  tre anni, elenco pubblico (il livello 0 non vi compare, come da
  documento di progetto). Un attestato finisce nell'elenco pubblico solo
  se lo si chiede esplicitamente: il nome di una persona non si pubblica
  per impostazione predefinita.

Dati personali e accessi:

* Il modulo di iscrizione chiede un consenso esplicito e lo registra con
  la data; se nel sito è impostata una pagina di informativa privacy, il
  modulo la collega.
* Il plugin si presenta agli strumenti di WordPress in Bacheca →
  Strumenti → Esporta/Cancella dati personali: una richiesta di accesso o
  di cancellazione comprende anche le iscrizioni ai corsi. La
  cancellazione toglie nome, email e telefono; se sull'iscrizione risulta
  un incasso, la riga contabile resta (anonima) per gli obblighi fiscali.
* Il pannello del gestore — dove si leggono i contatti degli iscritti e
  si registrano i bonifici — è riservato agli amministratori. Le versioni
  precedenti lo aprivano a tutto il ruolo Editor: dalla riattivazione del
  plugin quel permesso viene tolto. Per darlo a un collaboratore che non
  è amministratore, assegnare la capacità `slp_gestisci_corsi` a quel
  singolo utente.
* Il modulo pubblico ha un limite di cinque invii all'ora per collegamento
  e un campo esca contro i programmi automatici, per non ritrovarsi il
  database pieno di iscrizioni finte con dati inventati.
* Ogni pagamento registrato porta con sé chi lo ha inserito e quando.

Cosa NON c'è ancora, deliberatamente:

* L'abbonamento annuale alla piattaforma (ABB nel documento di progetto):
  è un modello di dati diverso (ricorrente, non una sessione con posti),
  da costruire a parte.
* Emissione automatica del PDF dell'attestato da stampare (oggi
  l'attestato è un numero progressivo registrato, non un file).
* Promemoria automatici (scadenza dell'attestato, saldo non versato):
  ci sono le email di conferma, non quelle a tempo.
* Pagamento online con carta: si lavora a bonifico, come da documento di
  progetto.
* Collegamento con l'elenco certificati di eventuali altri sistemi
  (per esempio un domani con Sfoglia Lab — Aula, per la certificazione
  condivisa tra i due prodotti).
* Calendario visuale delle sessioni (oggi è un semplice elenco).

== Installazione ==

1. Copia l'intera cartella `sfoglia-lab-pro` dentro `wp-content/plugins/`
   del sito WordPress.
2. Attiva il plugin da Bacheca → Plugin.
3. Crea le pagine con gli shortcode:
   - `[slp_pannello_gestore]` — per chi gestisce i corsi (serve un account
     Amministratore o Editor). Da qui si programmano le sessioni (date) di
     un corso del catalogo, si vedono gli iscritti e si registrano i
     pagamenti.
   - `[slp_catalogo_corsi]` — pagina pubblica di iscrizione
   - `[slp_elenco_certificati]` — pagina pubblica dell'elenco certificati
4. Il pannello del gestore include in fondo un riquadro "Diagnostica"
   (ripiegato di default): mostra tutte le sessioni salvate nel database,
   utile solo per verificare che una sessione sia stata registrata
   correttamente, con un pulsante per eliminare le eventuali sessioni di
   prova senza iscritti.
