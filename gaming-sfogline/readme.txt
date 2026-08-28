=== Gaming Sfogline ===
Contributors: Accademia della Sfoglia
Tags: gamification, community, sfoglia, badge, classifica
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 3.299.0
License: GPLv2 or later

Sistema di gamification per il portale WordPress dell'Accademia della Sfoglia.
Ex "GuruShot Sfogline", ora "Gaming Sfogline".

== Descrizione ==

Nove sistemi di gioco in un solo plugin, senza dipendenze esterne né database
personalizzato (solo Custom Post Type, meta, shortcode, AJAX e WP-Cron):

1. Livelli — Le Insegne della Sfoglia (6 stadi)
2. Sfida della Settimana (countdown + WP-Cron)
3. Voto della Community (4 criteri a stelle, reciprocità)
4. Streak del Matterello
5. Missioni Giornaliere
6. Badge System
7. Barometro Stagionale (contenuto gated)
8. Ingrediente Segreto del venerdì
9. Squadre Regionali

Più le novità della v2.0:
- Registrazione pubblica con approvazione manuale (quota associativa)
- Anti-spam (honeypot, trappola del tempo, limite per IP)
- Sfide individuali oltre che a squadre + visibilità galleria per sfida
- Cronologia punti in "Correggi Punti"
- Premio di Fine Anno (Corso con Rina Poletti)
- Email automatiche su badge/livello
- Notifiche push (OneSignal) per l'Ingrediente Segreto
- Galleria pubblica filtrabile (stagione/ingrediente/regione)
- Esportazione classifica in PDF (pagina stampabile)
- Vetrina pubblica del profilo (/vetrina/?sfoglina=nomeutente)
- Pannello di controllo front-end per chi gestisce il portale ([gs_pannello])

== Installazione ==

1. Caricare la cartella `gaming-sfogline` in `wp-content/plugins/`
   (o installare il file .zip da Plugin > Aggiungi nuovo > Carica plugin).
2. Attivare il plugin: crea da solo impostazioni di default e le 10 pagine.
3. Visitare una volta Impostazioni > Permalink (salvare senza modifiche) per
   rigenerare i link (necessario per la Vetrina).
4. Aggiungere le pagine create al menu di navigazione (Aspetto > Menu).

Requisiti: WordPress 5.8+, PHP 7.4+, nessuna dipendenza da plugin di terze parti.

== Note tecniche ==

- Il prefisso delle funzioni resta `gs_` per compatibilità con la v1.0.
- File principale: `gaming-sfogline.php` nella cartella `gaming-sfogline`.
- Per aggiornare: sostituire l'intera cartella e risalvare i permalink.
- Tutti i file PHP sono verificati con `php -l` prima della consegna.

== Changelog ==

= 3.299.0 =
* Tolta la "quota associativa" come condizione bloccante dell'iscrizione: Ennio ha chiarito il 28/08/2026 che l'iscrizione è e resta gratuita, con accesso gratuito alle sezioni di base; solo chi vuole continuare a usare le sezioni di gaming oltre il primo mese di prova versa (facoltativamente) un contributo di 29€. Il modulo di registrazione mostrava invece un riquadro "Quota associativa annuale: 29€" con dati per il bonifico e un checkbox obbligatorio ("Devi confermare l'adesione alla quota associativa" bloccava l'invio) — tolti entrambi, restano solo i campi anagrafici e la Privacy Policy.
* Corretti nello stesso senso tutti i testi che facevano dipendere l'attivazione dell'account dalla "verifica della quota associativa" (mail di conferma email, avviso "in attesa di approvazione", messaggio di fine registrazione, pannello "Richieste di Iscrizione", FAQ): ora dicono tutti che l'approvazione della segreteria è un controllo della richiesta, non la verifica di un pagamento.
* L'impostazione "Importo quota associativa" nella Plancia diventa "Importo contributo gaming dopo la prova" (stesso campo, stesso valore di default 29,00€: è quello già usato nell'avviso di fine prova) — tolto il campo "Testo conferma quota", non più mostrato da nessuna parte.

= 3.298.2 =
* Corretta una frase sgrammaticata nella riga nuova di 3.298.1, che dice quanti piatti in via d'estinzione tornerebbero liberi con il Reset: con più di uno, l'anteprima scriveva «3 piatti in via d'estinzione **tornerà** libero» — il nome al plurale, il verbo rimasto al singolare. Non è un difetto di funzionamento, ma è la pagina che si legge prima di premere il pulsante che non si annulla: l'unico punto del plugin dove una frase sgrammaticata costa qualcosa. Corretto con due frasi intere (singolare/plurale), non più pezzi incollati — così non si sgrammatica di nuovo al prossimo ritocco, che è il motivo per cui si era rotta la prima volta.

= 3.298.1 =
* Tolta l'eccezione sui piatti adottati da Rina Poletti, introdotta in 3.298.0: era nata da una frase di Ennio interpretata male («cancella tutto, tranne i consigli di Rina Poletti»), letta come «i piatti che ha adottato lei». Ennio ha chiarito il 27/08/2026 che intendeva i **Consigli scritti** da lei — e quelli erano già tenuti, senza bisogno di nessuna eccezione: la decisione 2 di 3.298.0 tiene il tipo `gs_consiglio` per intero, chiunque lo scriva. L'eccezione sui piatti era quindi una regola in più, mai chiesta, su un'operazione irreversibile — e per giunta basata sul nome visualizzato di un account, che sarebbe fallita in silenzio a ogni differenza di maiuscole o di spazi. Tolta: `gs_e_rina_poletti()` non esiste più, i piatti in via d'estinzione tornano tutti liberi con il Reset, come nella proposta originale approvata da Ennio.
* L'anteprima ora dice **quanti piatti tornerebbero liberi**, prima di premere: prima il Reset li liberava senza che comparisse da nessuna parte. Nuova funzione `gs_reset_conteggio_piatti_da_liberare()`, stesso principio della tabella delle sfogline nel Cestino (3.297.0) — si guarda prima, non si scopre dopo nel log.
* Test dedicato aggiornato: un piatto adottato da un account "Rina Poletti" di prova ora si libera come tutti gli altri (nessuna eccezione), il numero dell'anteprima e quello del log coincidono, e un test nuovo verifica la richiesta vera di Ennio — un Consiglio scritto da Rina e uno scritto da una sfoglina qualunque sopravvivono entrambi al Reset, perché il tipo si tiene per intero, non filtrato per autore.

= 3.298.0 =
* Le quattro decisioni rimaste aperte sul Reset del gioco (ISTRUZIONE-LA-PROVA-E-LE-QUATTRO-DECISIONI.md), decise da Ennio il 27/08/2026:
  1. **Le "Cose da Fare"** (`gs_todos`, `gs_todos_cestino`) — promemoria personali che ogni sfoglina si scrive da sola: si tengono, stesso motivo del Testamento (l'ha scritto lei, non è un punteggio).
  2. **Il Diario dell'Impasto e i Consigli** (`gs_diario`, `gs_consiglio`) — si tengono per intero.
  3. **Gli errori didattici** (`gs_errore_didattico`) — si tengono tutti, promossi e non promossi.
  4. **I piatti in via d'estinzione**: il custode (`gs_custode_tipo`/`gs_custode_id`/`gs_custode_team`) si svuota per tutti come i voti dentro i sondaggi — **tranne** i piatti adottati da Rina Poletti, che restano suoi. Nuova funzione `gs_e_rina_poletti()` (reset.php), che la riconosce dal nome visualizzato dell'account (`display_name`), non da un ID: un ID utente non è portabile fra guru2 e il sito vero, un nome sì — se il suo account avesse un nome visualizzato diverso da "Rina Poletti", va corretto in quell'unico punto.
* Per la 2 e la 3, i tre tipi sono stati **spostati** dall'elenco "si cancella, voluto" (`gs_reset_tipi_da_cancellare_voluti()`) all'elenco da tenere — non solo aggiunti a uno dei due: un tipo lasciato in entrambi non dà errore e non si vede, il conto della Parte 4 smetterebbe di tornare in silenzio.
* `piatti_liberati` (quanti piatti sono stati liberati da un Reset) ora compare nel log, nel messaggio di fine reset e nella cronologia del pannello, con lo stesso trattamento già dato a `scatole_ripulite` in 3.297.1.
* Il controllo di classificazione (Parte 4, introdotto in 3.297.0) resta pulito dopo lo spostamento dei tre tipi: 36 tipi registrati restano 36 = 26 da tenere + 10 da cancellare-voluti (prima: 23 + 13) — verificato con un test dedicato, non solo a occhio.
* Test dedicato ampliato con un caso per ognuna delle quattro decisioni: una sfoglina con promemoria attivi dopo il Reset, i tre tipi ora tenuti, e un piatto adottato da un account "Rina Poletti" di prova che resta suo dopo il Reset mentre un piatto adottato da chiunque altro (sfoglina o squadra) si libera.

= 3.297.1 =
* Trovato dalla verifica di 3.297.0 (VERIFICA-RESET-3.297.0.md, 27/08/2026): nel passo che ripulisce la scatola di chi è nel Cestino, se dopo la pulizia non restava nessuna delle chiavi da tenere (una registrazione rifiutata subito, senza abbonamento né token), la scatola veniva riscritta vuota invece di essere tolta. Due conseguenze: `gs_ripristina_dati_gaming_utente()` usciva subito su un archivio vuoto senza cancellare la chiave, quindi quella persona sarebbe rimasta per sempre nella tabella del Cestino nell'anteprima anche dopo il ripristino; e il controllo che decide se archiviare confronta con `''`, che un array vuoto non è — un futuro nuovo passaggio nel Cestino non sarebbe più stato archiviato. Corretto: la scatola rimasta vuota viene tolta, non lasciata in giro. Aggiunti due casi al test dedicato con una sfoglina di prova in questa identica situazione.
* Lo stesso passo ora dice anche quante scatole ha ripulito, nel messaggio di fine reset e nella cronologia del pannello — prima veniva contato ma non compariva da nessuna parte.

= 3.297.0 =
* Il Reset del gioco (3.296.0) aveva tre punti scoperti, trovati verificando il plugin per intero (non rileggendo la lista) e confrontandolo con quanto registrato davvero: **la scatola di chi è nel Cestino**. `gs_archivia_dati_gaming_utente()` chiude tutti i meta di gioco di una sfoglina sospesa o rifiutata (abbonamento, scadenza, token, Vetrina, sconto compresi) dentro un'unica chiave, `gs_archivio_gaming`, che non era nell'elenco da tenere: il Reset cancellava la scatola intera, e quelle persone — mai comparse nell'anteprima, che scorre solo le sfogline attive — non avrebbero più ritrovato nulla ripristinandole dal Cestino. Corretto in tre parti: la chiave resta (altrimenti si perde tutto), il suo contenuto viene ripulito con la stessa regola di tutti gli altri meta (altrimenti chi viene ripristinata dopo un Reset si ritroverebbe i punti di prima, unica in tutto il sito), e una nuova tabella nell'anteprima — "Sfogline nel Cestino, anche a loro resta tutto" — le mostra per la prima volta.
* **Il calendario dei corsi e le prenotazioni**: il documento originale del Reset elencava `gs_corso` fra i contenuti da tenere, ma il calendario (calendario.php) registra due tipi distinti, `gs_corso_cal` e `gs_prenotazione`, che non erano in nessun elenco — la seconda contiene gli acconti e i saldi già versati con bonifico e i loro riferimenti. Il Reset li cancellava per sempre: la stessa cosa che il documento vieta esplicitamente per i token («cancellare denaro versato»), qui applicata a un corso vero con Rina. Aggiunti entrambi ai tipi da tenere.
* **Il permesso**: il pannello del Reset si mostra solo al titolare (`manage_options`), ma i quattro handler AJAX controllavano `gs_can_manage()`, che comprende anche i collaboratori (`gs_manage_gaming`). Il pulsante nascosto non era una protezione: una singola richiesta bastava a far partire il Reset o a riscrivere gli indirizzi pubblici di tutte. Corretto su tutti e quattro gli handler.
* Quattro correzioni minori allo stesso elenco: `gs_telefono` (dato di contatto, non lo scrive il plugin) e `gs_vetrina_bloccata` (il blocco amministrativo di una Vetrina — cancellarlo da solo la rimetterebbe pubblica) aggiunti ai meta da tenere; `gs_barometro` (Guide Stagionali) e `gs_ingrediente` (Ingredienti Segreti, compresi quelli programmati nel futuro) aggiunti ai tipi da tenere: sono catalogo scritto dal titolare in wp-admin, non contenuto delle sfogline.
* Nuovo controllo permanente: `gs_reset_tipi_non_classificati()` confronta ogni tipo `gs_` effettivamente registrato con un secondo elenco esplicito — «questi si cancellano, ed è voluto» — e segnala in rosso, in cima all'anteprima, qualunque tipo che non sia in nessuno dei due elenchi. Chi aggiunge un nuovo Custom Post Type al plugin non deve più ricordarsi del Reset: se lo trova segnalato da solo. Aggiunto anche un test dedicato (`tests/test-reset.php`) che verifica lo stesso controllo contro tutti i tipi realmente registrati dal plugin, oltre alle tre correzioni sopra con dati di prova veri (una sfoglina sospesa con abbonamento/token/scadenza, un corso e una prenotazione con acconto versato, un collaboratore contro il titolare).
* Restano segnalate a Ennio, non decise qui (vedi la Parte 3 di ISTRUZIONE-LE-CORREZIONI-AL-RESET.md): se tenere "Le Cose da Fare" personali, cosa fare del Diario dell'Impasto e dei Consigli, se tenere anche gli errori didattici non ancora promossi, e se svuotare (come i voti dei sondaggi) il "custode" dei piatti in via d'estinzione — oggi restano adottati da sfogline che il Reset ha già azzerato, e nessun'altra può più adottarli.

= 3.296.0 =
* Nuovo pannello "Il Reset del gioco" (ISTRUZIONE-IL-RESET.md, 26/08/2026), riservato al titolare: due operazioni pensate per il giorno in cui arrivano le prime sfogline vere, dopo mesi di dati dimostrativi. **Username fuori dalla rete**: le Vetrine pubbliche non mostrano più `user_login` (metà delle credenziali di accesso) nell'indirizzo, ma un identificativo pubblico costruito dal nome (`user_nicename`), con anteprima e applicazione separate; il login stesso non cambia. **Il Reset**: cancella tutto quello che comincia per `gs_` (meta di gioco, contenuti di gioco) TRANNE un elenco scritto di cose da tenere — mai il contrario, perché un elenco di cose da cancellare dimenticherebbe le chiavi costruite a runtime. Non tocca mai gli utenti (nessuna cancellazione account: quella resta una decisione di Ennio, a mano), non tocca mai la Libreria Media, non decide da sola sulle sfide ancora aperte con voti di prova (le segnala soltanto). Anteprima non distruttiva sempre disponibile; la cancellazione vera si sblocca solo dopo l'anteprima e richiede di scrivere `RESET` a mano, con una tracciatura permanente di ogni reset eseguito.
* Corretta, rispetto al documento originale, una dimenticanza nell'elenco dei contenuti da tenere: mancava `gs_sfida` (la definizione della sfida, scritta dal titolare), nonostante il testo dello stesso documento dicesse esplicitamente che le sfide vecchie non si toccano — copiato alla lettera avrebbe cancellato la storia di tutte le sfide.
* Trovato provando il Reset con dati reali (non per ispezione): `'post_status' => 'any'` in `WP_Query` esclude il cestino — un comportamento di WordPress poco noto. Poiché il plugin usa sempre `wp_trash_post()` per le cancellazioni (mai quella definitiva), un vero reset avrebbe lasciato in giro, invisibili, tutti i contenuti di gioco finiti nel cestino nel tempo. Corretto includendo esplicitamente lo stato `trash` nella cancellazione.
* Trovato verificando il pannello nel browser vero (non bastava il test dei soli dati): i quattro pulsanti del pannello (Anteprima/Applica per lo username, Anteprima/Cancella per il Reset) non rispondevano — `gs_box_open()` si aspetta la classe CSS come secondo argomento, non come terzo, e lo scambio faceva sì che il contenitore avesse un `id` invece di una `class`, che è quella cercata dal JavaScript. Senza questa verifica visiva, il pannello sarebbe sembrato completamente muto in mano al titolare.

= 3.295.0 =
* Corretta una svista cosmetica nel pannello delle mail, segnalata nella verifica di 3.294.0: l'elenco dei segnaposto sotto ogni testo proteggeva (`esc_html()`) l'intera riga già unita, comprese le etichette `</code>, <code>` usate per separarli — così a schermo comparivano come testo invece che come formattazione. Corretto proteggendo ogni segnaposto singolarmente, prima di unirli.

= 3.294.0 =
* Corretto un difetto vero in `gs_abbonamento_controlla_scadenze()` (abbonamenti.php), segnalato nella verifica di 3.293.0: la scelta di quale delle tre email di scadenza mandare confrontava un momento CON l'ora (`current_time('timestamp')`) contro una mezzanotte della data di scadenza — lo stesso difetto (P3) già trovato tre volte su questo progetto, stavolta nella funzione che decide quale mail parte. Il giorno esatto della scadenza, la mail diceva "la prova è finita" mentre il cancello (`gs_sfoglina_congelata()`, che confronta correttamente due mezzanotte) diceva ancora "sei dentro" — proprio il giorno che conta di più di tutto il modello dei trenta giorni. Corretto confrontando due DATE attraverso la stessa funzione, con `round()` al posto di `floor()` (l'ora legale rende un giorno di 23 o 25 ore, non sempre 24).
* Provato con dati reali sul confine esatto: il giorno della scadenza, mail e cancello dicono entrambi "ancora dentro"; il giorno dopo, dicono entrambi "fuori" — prima si contraddicevano proprio lì.
* Aggiunta, sotto il testo di ogni mail nel pannello, la lista dei segnaposto che quella mail usa davvero (letta dal testo, non un elenco fisso), con l'avviso che toglierne uno fa sparire quella parte senza che la mail smetta di partire — richiesto dalla stessa verifica.

= 3.293.0 =
* Tappa 1 del «pannello delle mail» (documento IL-PANNELLO-DELLE-MAIL.md, 26/08/2026), che è anche il completamento di A6.5 del cancello dei trenta giorni: le otto email intorno all'iscrizione e alla scadenza vivono ora nel registro modificabile dal pannello, invece che scritte fisse nel codice. Il pannello che le mostra (gs_pannello_invio_mail_area_riservata) esisteva già ed è generico: le sei nuove voci sono comparse da sole, senza scrivere nessuna interfaccia nuova — esattamente come suggeriva il documento.
* Le sei nuove voci: **benvenuto** (parte all'approvazione, ora con le due date vere del mese di prova), **conferma dell'indirizzo email**, **richiesta non accolta**, e le **tre fasi della scadenza** — «fra una settimana», «ultimo giorno», «la prova è finita». Quest'ultima ora scrive anche alla sfoglina («non hai perso niente, ecco come si riapre»), non più solo a Ennio in Posta interna: prima, con la scadenza ancora solo un promemoria manuale, avvisare la sfoglina di qualcosa che non si era ancora spento sarebbe stato falso — ora che la scadenza è vera, tacerle che è congelata sarebbe peggio.
* Due nuovi segnaposto nel motore delle mail: {{DATA_INIZIO}} e {{DATA_FINE}}, le due date del mese di prova (lette dai meta della sfoglina; per una mail di prova, date plausibili inventate al volo, mai un segnaposto vuoto); {{LINK_VERIFICA_EMAIL}}, il link di conferma con il token vero.
* Provato con dati reali su tutte e sei le nuove voci: approvazione, riapprovazione (nessun secondo mese gratis), rifiuto, conferma email (il link contiene il token e l'id giusti), e le tre fasi della scadenza simulate con sfogline la cui data cade fra 7 giorni, domani e 2 giorni fa — ciascuna riceve la mail giusta, nessuna riceve quella sbagliata, nessuna la riceve due volte. Verificato anche che un testo modificato dal pannello sostituisce quello scritto nel codice, e che il pannello mostra tutte e nove le voci del registro (le sei nuove più le tre già esistenti).
* Restano da fare, fuori da questa consegna: le ~31 email della tappa 2 (corsi, ricette, biografie, messaggi, testimoni — "con calma"), il tavolo da scrittura per la newsletter con i tre elenchi per MailPoet, e una decisione di Ennio sulle ~48 email che il sito manda solo a lui (la tappa 3, "forse non vale il lavoro").

= 3.292.0 =
* Chiuso un buco vero nel cancello dei trenta giorni, trovato cercando la risposta a un'altra domanda (documento GUARDARESENZAGIOCARE.md, 26/08/2026): undici handler AJAX controllavano ancora solo `gs_is_approved()` invece di `gs_puo_partecipare()`, perché erano rimasti indietro da prima che esistesse il congelamento — una sfoglina congelata era comunque "approvata", quindi passava lo stesso. Con le pagine chiuse serviva una scheda del browser rimasta aperta da prima della scadenza per arrivarci, ma due di questi undici (`gs_ajax_esperto_domanda_privata`, `gs_ajax_conv_sfoglina_richiesta`) spendono un token — soldi veri versati con bonifico, non punti di gioco.
* Gli undici: pubblicare una sfoglia, votare, commentare una sfoglia (voting.php); votare e proporre in un sondaggio (sondaggi.php); il Diario e i Consigli (forms.php); le due domande a «L'Esperto Risponde» (esperti.php); avviare una conversazione privata (conversazioni.php); lasciare gli auguri di compleanno (compleanni.php). Stessa correzione già applicata agli altri 27 handler in 3.290.0.
* Provato con dati reali: chiamati tutti e undici gli handler veri con una sfoglina congelata, tutti respinti; riprovati due con una sfoglina attiva, passano normalmente come prima (nessuna regressione).
* Restano volutamente fuori da questo pacchetto, perché non hanno la scadenza di settembre: l'idea di «guardare senza giocare» dopo i trenta giorni (un interruttore in più nel cancello delle pagine, con un elenco di cosa resta permesso da spuntare a mano) — a differenza di questo, che è la chiusura di un difetto, quella è una scelta di prodotto, ancora da decidere.

= 3.291.0 =
* «Spiegazioni delle Sezioni»: ogni voce ha ora anche due link diretti per portare il testo fuori dal sito, a chiunque, anche a chi non è iscritta — 📧 email (mailto:, apre il programma di posta con oggetto e corpo già pronti) e 💬 WhatsApp (il link pubblico di condivisione, apre l'app o WhatsApp Web lasciando scegliere il contatto). Nessun JavaScript nuovo, nessun endpoint privato: due semplici link, sempre funzionanti a differenza della Web Share API già usata per le locandine (che su molti browser desktop non supporta ancora la condivisione).
* Trovato provando i link davvero, non leggendo il codice: `esc_url()` di WordPress toglie di proposito gli a-capo codificati dai link `https://` per sicurezza, ma li lascia in quelli `mailto:` — con un a-capo come separatore, il link WhatsApp avrebbe attaccato titolo e testo senza spazio, mentre l'email sarebbe rimasta corretta. Corretto usando un trattino («Titolo — testo») invece dell'a-capo, cosi i due canali si comportano allo stesso modo.

= 3.290.0 =
* Completata la voce «L» del cancello dei trenta giorni, lasciata in sospeso in 3.289.0: `gs_puo_partecipare()` è ora collegata a tutti i 24 handler AJAX individuati (l'account leggero "lettore" e le sfogline congelate non possono più scrivere contenuti né guadagnare punti da lì, nemmeno da una scheda del browser rimasta aperta). Tre eccezioni deliberate restano su solo `gs_is_approved()`, senza il congelamento: «Aiuto e Suggerimenti» (è come una sfoglina congelata chiede di rientrare, deve restare aperto), il Calendario Corsi (chi ha pagato un corso deve poterlo gestire anche a gaming chiuso), e il salvataggio della Biografia/Vetrina (si paga a parte, 49€, indipendente dal gaming).
* Provato con dati reali: una sfoglina congelata viene respinta da un handler normale del gaming (con il messaggio corretto), ma passa ancora attraverso tutte e tre le eccezioni — verificato chiamando gli handler veri, non solo leggendo il codice.
* Nuova sezione del Pannello Generale: «📖 Spiegazioni delle Sezioni» (richiesta di Ennio, 26/08/2026). Un raccoglitore con ricerca di tutte le spiegazioni già scritte per ogni sezione del sito — 76 in tutto, 59 rivolte alle sfogline e 17 ai pannelli di gestione — con la possibilità di scegliere una qualsiasi persona e mandarglielo come messaggio interno con un clic, con il testo modificabile prima dell'invio.
* Cerca per nome della sezione o per una qualunque parola dentro il testo (usa la stessa ricerca già presente altrove nel plugin). Non serve nessun nuovo meccanismo di invio: riusa l'handler già esistente della Posta Interna (gs_ajax_fe_invia_messaggio), lo stesso che manda i messaggi dalla segreteria.
* Attenzione tecnica non banale, corretta prima di sincronizzare: il destinatario "vuoto" nel modulo di invio, se lasciato tale per distrazione, verrebbe letto dal sistema come "manda a TUTTE le sfogline" (è così che la Posta Interna interpreta un destinatario 0) — un incidente serio, non un dettaglio. Tolta l'opzione vuota: il modulo parte sempre con una sfoglina vera già selezionata, mai un valore ambiguo.
* I testi sono una copia di quelli già scritti accanto a ogni sezione (le stesse spiegazioni che una sfoglina vede aprendo quella sezione): tenerli come copia in un unico posto evita di dover eseguire ogni pannello solo per raccoglierne il testo, al prezzo che un domani, se il testo originale cambia, questa copia non si aggiorna da sola.
* Provato con dati reali: una sfoglina normale non vede la nuova sezione, un gestore sì; verificato che un invio dal nuovo pannello arriva davvero nella casella «Messaggi» del destinatario scelto, con oggetto e testo corretti, riusando l'handler vero (non un doppione).

= 3.289.0 =
* AVANZAMENTO PARZIALE del cancello dei trenta giorni (documento TRENTA-GIORNI-IL-CANCELLO.md, 26/08/2026) — non è una consegna finita, è un punto intermedio di un lavoro con scadenza a una settimana. Quello che c'è dentro è testato e stabile; quello che manca è elencato sotto, non è ancora attivo e non rischia nulla per il sito com'è oggi.
* Iscrizione gratuita + 30 giorni di prova in regalo: `gs_approve_user()` scrive ora la data di approvazione una volta sola (una riapprovazione non regala un secondo mese) e imposta automaticamente la scadenza a 30 giorni da oggi.
* Nuova funzione `gs_sfoglina_congelata()` (abbonamenti.php): dice se una sfoglina è fuori dall'area riservata, per stato messo a mano o per data di scadenza passata (il giorno stesso della scadenza è ancora dentro). I sette account senza data restano aperti per sempre, i gestori non si congelano mai.
* Il cancello delle pagine: `gs_gate_riservato()` sostituisce, in 24 file (25 punti), il vecchio controllo "sei collegata?" con "sei collegata e non congelata?" — comprese "La Mia Sfoglia" (chiusa anche lei, per scelta esplicita di Ennio) e tutte le sezioni del gaming. Le pagine in chiaro (Galleria, Registro, Classifica, Sfogline, Letture, Vetrina, FAQ, Novità, Compleanni, Traguardi) restano aperte a tutti.
* Il cancello dei punti: `gs_add_points()` non assegna più punti positivi a una sfoglina congelata, nemmeno da una richiesta AJAX fatta a mano o da una scheda rimasta aperta. Le correzioni negative e quelle fatte da un gestore per conto della sfoglina passano comunque.
* Nuova funzione `gs_puo_partecipare()` (helpers.php): la porta gemella lato AJAX, non ancora collegata a nessun handler — è il prossimo passo, sui 31 punti dove oggi si controlla solo "sei collegata?" invece di "sei approvata e non congelata?" (account leggero "lettore" compreso).
* Pannello «Abbonamenti»: testo riscritto per riflettere la scadenza automatica, nuova colonna "giorni rimasti".
* Trovato e corretto PRIMA di sincronizzare: uno script usato per le 25 sostituzioni in blocco aveva colpito anche la definizione stessa di `gs_gate_riservato()`, creandole una chiamata a se stessa — ogni pagina riservata sarebbe andata in errore fatale per esaurimento memoria. Scoperto da un test end-to-end reale (non dal lint, che non vede la ricorsione), corretto, ritestato.
* Provato con dati reali: creazione e (ri)approvazione di una sfoglina di prova, i confini esatti della data di scadenza, il cancello dei punti nelle sue quattro combinazioni (positivo/negativo × congelata/gestore), e il cancello delle pagine end-to-end su una sezione vera e su "La Mia Sfoglia", prima e dopo il congelamento.
* NON ANCORA FATTO, e non è collegato a niente finché non lo è: i 31 handler AJAX della voce «L» (le eccezioni Aiuto/Calendario/Letture restano aperte, Biografia da decidere), i quattordici agganci del cron giornaliero (streak, chiusura del mese, promemoria lezioni — oggi continuano a girare su tutti, congelate comprese), la riscrittura delle email di scadenza (devono portare le istruzioni intere, ora che la dashboard è chiusa), gli undici testi che dicono ancora "prima paghi, poi entri", la mail di benvenuto finale.

= 3.288.0 =
* Corretto l'ultimo punto rimasto sul rimborso dei token (conversazioni.php, token.php), segnalato nella verifica di 3.287.0: il lucchetto messo nel giro precedente protegge da due rimborsi che partono insieme, ma non da uno che si interrompe a metà (errore fatale, tempo massimo di esecuzione, limite orario di posta dell'hosting — la stessa causa già descritta nel commento di buono-sfoglia.php). In tutti e due i percorsi il contrassegno "rimborsato" veniva scritto DOPO aver già restituito il token: se il processo moriva in mezzo, il token era uscito ma il registro restava pulito, e il giro dopo usciva di nuovo.
* Nel cron (token.php) era più serio: la scrittura non era per messaggio ma una sola, alla fine di tutto il ciclo. Una conversazione con più domande scadute poteva restituire diversi token e mandare le relative email prima di scrivere anche un solo contrassegno — se il ciclo si fermava a metà, tutti i rimborsi già fatti sparivano dal registro e sarebbero usciti di nuovo il giorno dopo. Ora il contrassegno si scrive e si salva per ogni singolo messaggio, prima del movimento e prima dell'email — una scrittura in più per rimborso, invece di una per conversazione (i rimborsi sono rari, il costo non si vede).
* Il verso dell'errore, se qualcosa si interrompe adesso, è diventato quello giusto: nel peggiore dei casi un rimborso resta da fare (si vede dal pannello, si rimette a mano) invece di un rimborso duplicato (che non si vede).
* Provato con dati reali: un test simula un ciclo del cron che "muore" subito dopo il secondo di tre rimborsi in una stessa conversazione — verificato che i primi due restano segnati e pagati una volta sola, il terzo riprende correttamente al giro successivo senza duplicare i primi due. Riprovato anche il test a processi paralleli veri (rimborso a mano + cron nello stesso istante): il lucchetto tiene ancora, un solo rimborso.
* Fatta girare anche la batteria di prove generali (sintassi, funzioni chiamate e non definite, azioni AJAX JavaScript↔PHP) fornita da Ennio: 96 file, 0 errori, 0 funzioni mancanti, 0 pulsanti morti, 0 gestori rotti.

= 3.287.0 =
* Corretta la parte mancante segnalata nella verifica di 3.286.0, sui Sondaggi (sondaggi.php): un voto e una proposta inviati nello stesso istante da due sfogline diverse leggevano lo stesso array e l'ultima a scrivere cancellava l'altra, in silenzio — il sito diceva "Voto registrato, grazie!" e il voto non c'era. Aggiunto un lucchetto MySQL per sondaggio, condiviso tra voto e proposta (S1, S2). Verificato con due processi realmente paralleli, passando per i veri handler AJAX: entrambi i voti e entrambe le proposte restano nel database, nessuno sparisce.
* Nel provare S1/S2 è emerso che il lucchetto da solo non bastava: gli handler AJAX leggono già un meta del sondaggio (per controllare se è aperto) prima ancora di prendere il lucchetto, e quella lettura precarica in cache tutti gli altri meta dello stesso sondaggio — la lettura fatta dentro il lucchetto vedeva quindi ancora il valore di prima, non quello appena scritto dall'altro processo. Corretto svuotando la cache di quel post subito dopo aver preso il lucchetto, prima di leggere. Lo stesso identico problema è stato trovato e corretto anche nel rimborso dei token (vedi sotto): non è un difetto isolato, è un limite di come funziona la cache dei meta di WordPress ogni volta che una lettura precedente nella stessa richiesta ha già toccato lo stesso post.
* Corretta la mini-missione Madrina & Allieva (madrina.php, M2): deselezionare e riselezionare la stessa mini-missione pagava 5+5 punti ogni volta, senza limite. Aggiunto un contrassegno permanente per missione che non si toglie mai, anche quando la spunta va avanti e indietro: il premio si paga una volta sola. Provato con due cicli completi di spunta/de-spunta/ri-spunta.
* Corretto il rimborso del token di una domanda privata senza risposta (C6): il rimborso a mano (un clic del gestore) e il rimborso automatico del cron potevano, nello stesso istante, restituire lo stesso token due volte — soldi veri, non punti di gioco. Aggiunto un lucchetto MySQL per conversazione, condiviso tra i due percorsi, con la stessa correzione della cache spiegata sopra. Verificato con due processi realmente paralleli: il token si restituisce una volta sola.
* Rinforzi minori su "Adotta un Piatto in Via di Estinzione" (piatti-estinzione.php), segnalati nella verifica di 3.286.0: aggiunto un try/finally al lucchetto dell'adozione, così si rilascia anche se una chiamata nel mezzo fallisce con un errore fatale; e lo stesso lucchetto è stato aggiunto anche alla rinuncia (gs_piatto_libera_uid), che prima non lo prendeva — evita che un'adozione e una rinuncia nello stesso istante lascino il piatto in uno stato incoerente.
* Provato tutto con dati reali: test a processi paralleli veri per S1, S2 e C6 (le tre race condition), test sequenziale a due cicli per M2, più una riprova completa di PE1 e del tetto ai punti di 3.286.0 per essere sicuri che nessuna di queste correzioni li avesse toccati per sbaglio.

= 3.286.0 =
* Tetto ai punti del Diario e dei Consigli (deciso da Ennio, 26/08/2026, sulla stessa regola già scelta per le foto del Tavolo di Lavoro): i punti si prendono una volta al giorno per fonte, scrivere resta libero. Senza tetto, Diario e Consigli insieme valevano 350 punti l'ora — i 2.500 del Buono Sfoglia del mese in quattordici minuti al giorno, senza mai impastare (misurato il 26/08/2026). Dalla seconda voce/consiglio dello stesso giorno il contenuto si salva comunque, ma non paga più; il messaggio lo dice chiaramente ("li hai già presi oggi — torna domani").
* Corretto un difetto vero in "Adotta un Piatto in Via di Estinzione" (includes/piatti-estinzione.php): adottare, rinunciare e riadottare lo stesso piatto pagava 20 punti ogni volta, senza nessun limite — 125 giri bastavano per il Buono Sfoglia del mese, pochi minuti. Aggiunto un contrassegno permanente per persona+piatto che paga una volta sola, indipendentemente da quante volte il piatto cambia custode nel tempo.
* Corretta insieme, nella stessa funzione, una race condition: due sfogline potevano cliccare "adotta" nello stesso istante ed entrambe diventare custodi dello stesso piatto. Aggiunto un lock MySQL per piatto. Verificato con due processi realmente paralleli (non in sequenza): solo la prima adozione riesce e paga punti, la seconda viene respinta pulitamente e non paga nulla.
* Corretto un piccolo difetto simile nelle proposte dei sondaggi (includes/sondaggi.php): dieci proposte diverse nello stesso sondaggio pagavano 10 punti ciascuna (100 punti totali da una pagina sola). Ora i punti della proposta si prendono una volta per sondaggio; proposte successive, anche diverse dalla prima, si salvano ma non pagano più.
* Provato con dati reali su tutti e quattro i casi, incluso un test a processi paralleli veri per la race condition dei piatti.

= 3.285.0 =
* Tolto il modulo Newsletter dal Gaming (richiesto da Ennio, 26/08/2026): il sito ha già MailPoet, un plugin di newsletter vero, e questo era un doppione — solo raccolta email, senza invio. Zero iscritti presenti, nessun dato perso. Rimossi il modulo (includes/newsletter.php), tutti gli agganci nei pannelli e nei menu, il form pubblico e i due gestori JavaScript. Sul sito vero: spostata nel cestino la pagina "Newsletter" (conteneva ormai solo uno shortcode orfano) e la domanda FAQ "Come mi iscrivo alla Newsletter?" — nessuna cancellazione definitiva, tutto recuperabile. Provato con dati reali: il Pannello di Controllo si genera senza errori, nessuna funzione mancante.
* Trovato per caso durante questo lavoro, non collegato: un avviso PHP pre-esistente in includes/regia-iscritti.php (un WP_Error usato come numero) — segnalato a parte, non corretto in questa versione.

= 3.284.5 =
* Il logo di Molini Pivetti (largo e basso, 130×50) appariva ripetuto più volte dentro il cerchio nel nastro ("è triplo", segnalato da Ennio): mancava `background-repeat: no-repeat` nel CSS, e senza quella regola il browser riempie con copie del logo lo spazio vuoto lasciato sopra e sotto dentro il cerchio. Corretto. Verificato che lo stile calcolato passa da "repeat" a "no-repeat"; non ancora visto con uno screenshot dal vivo (il pannello del browser non si visualizzava in quel momento) — primo controllo da fare dopo l'installazione.

= 3.284.4 =
* La correzione della 3.284.2 (tutti gli sponsor a rotazione anche con poche voci) aveva un caso che rompeva l'animazione: il nastro scorre esattamente di metà della propria larghezza e poi riparte, quindi le due metà devono essere identiche — con un numero di ripetizioni dispari (es. 3 sponsor e una sola voce nel nastro) il salto cadeva in mezzo a una copia e il nastro sobbalzava a ogni giro. Corretto arrotondando sempre a un numero pari. Provato con dati reali, incluso proprio il caso "3 sponsor, 1 voce" che lo causava.
* Anche "La Sfoglia Misurata" aveva lo stesso difetto già corretto nella Giuria a Turno (G1): i punti del podio stavano fuori dal controllo che evita di darli due volte alla stessa vincitrice. Corretto allo stesso modo. Provato con dati reali.

= 3.284.3 =
* I loghi di Molino Caputo e Molini Pivetti nel nastro non erano più indirizzi fissi al sito di produzione: sono stati portati dentro il plugin (assets/img/), come già Mulino Marino — se un domani cambia hosting o si riordinano i caricamenti, i tre sponsor restano tutti visibili allo stesso modo.
* L'indirizzo per l'invio delle mail di prova non è più scritto fisso nel codice (era info@lentium.it): ora usa l'email dell'amministratore del sito, che si aggiorna da sola se cambia. Provato con l'AJAX vero.

= 3.284.2 =
* Trovato controllando dal vivo dopo la 3.284.1: con solo due persone nel nastro (Bruno Cingolani e Rina Poletti, dopo aver tolto Fondatore e Govoni), il terzo sponsor a rotazione (Molini Pivetti) non compariva mai — la rotazione ripartiva da zero a ogni giro invece di continuare, e con poche voci non faceva in tempo a raggiungere il terzo. Corretto: le voci si ripetono abbastanza volte da garantire che tutti gli sponsor passino, anche se dovesse restare una sola persona nel nastro. Provato con dati reali, compreso il caso estremo di una sola voce.
* Bruno Cingolani e Rina Poletti ora hanno il cartellino "Maestro"/"Maestra" invece di "Sfoglina" nel nastro.

= 3.284.1 =
* Aggiunti Molino Caputo e Molini Pivetti come sponsor nel nastro fisso sotto il menu, accanto a Mulino Marino: i tre loghi ora scorrono a rotazione (uno dopo l'altro, non più sempre lo stesso) fra una voce e l'altra. Ognuno è cliccabile e porta al sito del rispettivo sponsor.
* Tolta la voce fissa "Ennio Barbieri, Fondatore" dal nastro.
* Giuseppe Govoni escluso dal nastro (impostazione già salvata sul sito vero dal pannello Caroselli — non richiede l'installazione di questo zip). Bruno Cingolani e Rina Poletti restano.

= 3.284.0 =
* Le tre mail di benvenuto (presentazione, "La Mia Sfoglia", "Accesso e Vetrina") partivano tutte insieme all'approvazione — tre messaggi in trenta secondi si leggono come uno solo. Ora restano distanziate: presentazione subito, "La Mia Sfoglia" al giorno 2, "Accesso e Vetrina" al giorno 5 — sul cron giornaliero (non su un evento programmato singolo, che può non partire mai in silenzio se il sito è fermo), con recupero automatico se il cron salta un giorno. Chi è congelata non riceve le mail di benvenuto mentre è fuori. Provato con dati reali: una sola mail all'approvazione, le altre due arrivano insieme nel recupero a 5 giorni, nessuna ripetizione a un secondo giro.
* I premi delle Sfide della Settimana (100+60+30 punti) venivano assegnati prima di segnare la sfida come chiusa: un giro interrotto a metà, o due giri del cron sovrapposti, potevano darli due volte (380 invece di 190) — stessa forma del difetto di luglio sulla chiusura del mese. Corretto invertendo l'ordine, con un avviso in Posta interna che conta le sfogline premiate. Provato con un doppio giro reale: il secondo non assegna più nulla.
* Stesso tipo di difetto nella Giuria a Turno: i 30 punti della vincitrice stavano fuori dal controllo che evita di dare due volte lo stesso badge. Corretto portandoli dentro. Provato con dati reali.
* Corretto un errore di fuso orario nella chiusura delle Sfide della Settimana (stesso tipo già corretto altrove nel plugin): la sfida poteva chiudersi un'ora o due prima o dopo l'orario scritto nella data di fine.
* La classifica della Giuria a Turno ricalcolava la media di ogni opera a ogni confronto durante l'ordinamento, invece che una volta sola — stessa ottimizzazione già presente nella classifica delle Sfide, ora anche qui.
* Segnalato ma non deciso: a parità di media, oggi l'ordine fra due sfogline pari merito (chi prende 100 punti e chi 60) lo decide il caso. È una regola di gara, non un difetto tecnico — resta da decidere il criterio.

= 3.283.2 =
* Il logo nell'intestazione e nel piede delle tre mail dell'Accademia (Accesso e Vetrina, Come funziona il Percorso, La Mia Sfoglia) aveva un riquadro bianco intorno — il file caricato è un JPG quadrato senza trasparenza. Ritagliato in tondo via CSS (border-radius), stesso file, nessun nuovo caricamento necessario.

= 3.283.1 =
* Nuova mail "La Mia Sfoglia, spiegata": spiega per intero la bacheca personale (carta d'identità, le quattro fasce Oggi/Percorso/Sfide/Strumenti, streak e scudi salva-streak), ricordando che diventa la loro bacheca. Parte da sola come seconda mail dopo l'approvazione — subito dopo quella di presentazione, prima di "Accesso e Vetrina". Testo modificabile dal pannello "Iscrizioni delle sfogline" come le altre due mail dell'Accademia, con invio di prova incluso. Provato con dati reali: nessun segnaposto rimasto scoperto nel testo, e l'ordine di invio (presentazione → La Mia Sfoglia → Accesso e Vetrina) verificato riga per riga.

= 3.283.0 =
* Il digest settimanale ("Le novità della settimana") partiva anche per le sfogline con l'abbonamento scaduto, ogni lunedì e per sempre — un elenco di ricette, lezioni e corsi che non possono aprire. Corretto: chi ha l'abbonamento scaduto non riceve più il digest. Provato con dati reali: una sfoglina attiva e una con l'abbonamento scaduto, entrambe con contenuti nuovi da annunciare — solo la prima ha ricevuto l'email.

= 3.282.3 =
* Allungata ulteriormente, su richiesta di Ennio, la distanza da risalire prima che il menu torni: da due schermate a tre (+50%).

= 3.282.2 =
* Raddoppiata, su richiesta di Ennio, la distanza da risalire prima che il menu torni: da una schermata a due.
* Un ultimo numero rimasto indietro dalla 3.282.1: il pulsante del Pannello di Controllo rimisurava lo spazio dopo 260 millesimi di secondo invece di 320 come l'altro pulsante — sotto i 320 la transizione CSS non è ancora finita e la misura viene letta a metà. Allineato.

= 3.282.1 =
* Un mio errore nella 3.282.0, non nel codice: avevo consegnato due zip diversi con lo stesso identico numero di versione. Il sito (o la cache in mezzo) non aveva modo di accorgersi che il file era cambiato, e continuava a servire il gaming.js più vecchio — quello SENZA la tolleranza di una schermata appena chiesta, e senza la correzione del doppio clic. Non era un difetto della tolleranza: era la tolleranza che non stava affatto girando. Da qui in poi ogni consegna ha davvero un numero diverso, anche per una correzione minima.
* Il pulsante «Fissa il menu in alto» dentro il Pannello di Controllo aveva lo stesso difetto già chiuso questa mattina sull'altro pulsante, ma nel posto dove non avevamo guardato: la scelta fatta lì veniva comunque disfatta scorrendo, perché i due pulsanti vivono in due punti diversi del codice e non si parlavano. Corretto spostando il contrassegno "l'ha scelto una persona" sulla pagina intera, così i due pulsanti lo condividono davvero — è il pulsante usato più spesso, quindi il più importante da avere corretto.
* Aggiunto in Diagnostica un controllo che l'aggancio del tema (quello che gs_get_posts_by_author() aggira) sia ancora al suo posto con lo stesso nome: se un aggiornamento del tema lo rinomina o lo toglie, l'aggiramento smette di servire a qualcosa in silenzio, come è già successo una volta per settimane senza che nessuno se ne accorgesse.

= 3.282.0 =
* Corretto lo sfarfallio segnalato da Ennio (26/08/2026): scorrendo verso il basso, nascondere il nastro e la barra rosa in cima toglieva spazio così in fretta che la pagina risaliva da sola — e quel movimento veniva scambiato per uno scorrimento vero, che rimetteva tutto a posto, che la faceva riscendere di nuovo: un'altalena continua. Corretto insegnando allo scorrimento a ignorare i movimenti causati dalla pagina stessa, non dalla persona. Provato dal vivo sul sito, scorrendo lentamente vicino alla cima: nessuna oscillazione, in nessuna delle due direzioni.
* Tornare su non fa più comparire il menu per un piccolo sali-scendi mentre si legge (es. sbirciare l'inizio di un articolo e poi riscendere per sistemarsi la pagina): ora serve risalire per una distanza pari a una schermata intera prima che il menu torni — tolleranza chiesta da Ennio (26/08/2026), pensata sulla misura di "una pagina". La distanza si azzera appena si torna giù anche di poco. Vicino alla cima della pagina invece il menu torna subito, senza nessuna soglia.
* Il pulsante «Nascondi logo» rinominato in «Fissa il menu in alto» (proposta di Ennio, più chiaro), ovunque compare — sul sito, nel Pannello di Controllo e nella sua scheda. Ora compare solo dove serve davvero: le pagine con un altro sito incorporato (es. "Rina Poletti", "Lentium Notizie"), dove lo scorrimento automatico non può funzionare per una regola di sicurezza del browser — prima restava ovunque, anche dove non serviva. Provato dal vivo su "Lentium Notizie": il pulsante compare solo lì, compatta il menu, resta compatto muovendo la rotella sopra il riquadro, e torna indietro col secondo clic.
* Corretto un difetto trovato in revisione, prima ancora di consegnare: due clic sul pulsante a meno di un terzo di secondo l'uno dall'altro (il gesto più naturale su un interruttore) potevano lasciare la scritta invertita rispetto allo stato vero, fino al ricaricamento della pagina — stesso genere di difetto già corretto sul pulsante del Diploma (L5). La scritta ora si aggiorna solo quando il cambiamento è davvero avvenuto.
* Due difetti silenziosi trovati per strada: un controllo scritto in un modo che avrebbe smesso di funzionare senza nessun avviso se il tema avesse cambiato una sua impostazione interna; e il nome di un lucchetto interno che, con un nome di squadra abbastanza lungo, avrebbe impedito ai Percorsi di Squadra di assegnare badge, senza nessun errore visibile.
* Tolto, su richiesta di Ennio, il limite di una foto al giorno sul Tavolo di Lavoro: si può caricare quante foto si vuole. I punti del gioco restano invece una volta al giorno (altrimenti caricare foto varrebbe più che fare sfoglia) — separata la regola dei punti da quella delle foto, che finora erano la stessa cosa per un effetto collaterale, non per scelta.
* Aggiunto in Diagnostica un elenco di sola lettura degli account che non rientrano fra amministratori, Artigiani/Scuole di Cucina (anche con vetrina cestinata), lettori, abbonamenti veri e sfogline abilitate a mano: è il primo passo del riordino degli utenti chiesto da Ennio — nessuna cancellazione, solo l'elenco da guardare.
* Aggiunto sempre in Diagnostica un conteggio di quanti punti il Tavolo di Lavoro ha già assegnato per il vecchio bug del tema (una foto poteva dare punti più volte al giorno, senza che il limite se ne accorgesse): da guardare prima di attivare "foto libere", per distinguere quello arrivato dal difetto da quello che arriverà dalla scelta di Ennio.
* Nota di verifica: lo sfarfallio e il pulsante (compresa la sua comparsa solo dove serve) sono stati provati dal vivo sul sito vero, con Ennio che seguiva. Il Tavolo senza limite e i due nuovi elenchi di Diagnostica sono stati provati con dati reali su copia locale (account e contenuti di prova creati apposta, risultati controllati uno per uno). La tolleranza di una schermata prima che il menu ricompaia, e la correzione del doppio clic, sono state scritte e controllate leggendo il codice ma non ancora provate dal vivo: sono il primo controllo da fare dopo questa installazione.

= 3.281.0 =
* Trovato e corretto un problema di fondo nel tema del sito (non nel plugin): ogni volta che una funzione cercava i contenuti di UNA sfoglina specifica — la foto del Tavolo di Oggi, i propri lavori, il proprio cestino, le proprie ricette, le proprie voci del Matterello Parlante, i propri errori, le proprie testimonianze, il proprio diario, le richieste di aiuto viste dal gestore — il tema alterava di nascosto la ricerca e la faceva tornare vuota o sbagliata. Effetti concreti già presenti in produzione: il limite di una foto al giorno nel Tavolo di Oggi non veniva rispettato, "I miei lavori" e il cestino personale risultavano sempre vuoti, e il pannello messaggi del gestore mostrava sempre zero richieste di aiuto per ogni sfoglina. Corretto in tutti i 13 punti trovati (10 file), aggirando solo quella specifica interferenza del tema senza modificarlo.
* Verificato con dati reali su copia locale del sito: creata una sfoglina di prova con un contenuto per ciascun tipo elencato sopra, confermato che ora vengono trovati tutti; verificato anche che un secondo caricamento nello stesso giorno nel Tavolo di Oggi viene correttamente rifiutato.

= 3.280.0 =
* Corretto un mio errore nella 3.278.0: il contrassegno anti-doppio che protegge "lezione vista" (135 punti in gioco fra lezione, badge di percorso e Diploma Finale) non era davvero al riparo da due richieste simultanee, e in un caso raro (connessione caduta a metà) poteva bloccare per sempre il completamento di un percorso senza che nessuno se ne accorgesse. Corretto con lo stesso lucchetto di MySQL già in uso per i posti dei corsi, più la riparazione automatica di quel caso raro — la sfoglina perde al massimo i 5 punti di quella singola lezione, mai il percorso.
* Stessa correzione, più urgente, sul Percorso di Squadra: due socie che finiscono l'ultima lezione insieme (senza bisogno di nessun doppio clic) facevano partire due giri completi di premi su tutta la squadra — con sei membri, 360 punti invece di 180. Provato stavolta con una vera prova di corsa (due processi paralleli distinti, non una simulazione in sequenza): risultato corretto su tutti i membri.
* La barra con la data e il menu superiore (Home/Disciplinare/Appello al Governo/L'Esperto Risponde), in cima a ogni pagina, ora sparisce insieme al logo e al nastro scorrendo verso il basso — resta invariata in cima alla pagina.

= 3.279.0 =
* Il nastro fisso sotto il menu ora sparisce insieme al logo quando si scorre verso il basso, invece di restare sempre incollato in cima — più spazio per il contenuto durante la lettura. Stesso meccanismo già in uso per nascondere il nastro sulla pagina "Le Sfogline", solo esteso a tutte le altre.
* Verificato per come è scritto e per analogia con il meccanismo già in produzione — non sono riuscito a provare lo scroll dal vivo su guru2 perché quel sito ha le opzioni del tema non configurate e gli avvisi PHP che ne escono rompono la struttura dell'intestazione (non collegato a questa modifica). Vale la pena controllarlo con i tuoi occhi dopo l'installazione.

= 3.278.0 =
* Il difetto più serio trovato finora: segnare una lezione video come vista non era protetto da niente, e non c'è nemmeno un pulsante da disabilitare — parte da sola quando si apre il lettore. Da lì partono in automatico il badge di percorso (30 punti) e, se era l'ultima lezione dell'ultimo percorso, il Diploma Finale (100 punti): una doppia esecuzione — anche solo riaprendo il lettore in fretta — poteva valere 270 punti invece di 135. Corretto con un contrassegno per lezione scritto prima di ogni effetto, sia in PHP che in JavaScript.
* Stesso difetto nei Percorsi di Squadra, dove bastano due socie che finiscono l'ultima lezione insieme (senza bisogno di nessun doppio clic) per far scattare due volte il giro di premi su tutta la squadra: con sei membri, 360 punti invece di 180. Stessa correzione, in tutti e tre i punti dove un badge di percorso viene assegnato.
* Segnalato (non riscritto: sarebbe sproporzionato adesso) che gli storici dei punti, dei pagamenti e dei token possono perdere una riga se due assegnazioni capitano nello stesso istante — i totali restano sempre corretti, è solo il registro leggibile che può saltare una voce.
* Corretto lo stesso errore di fuso orario già trovato sugli avvisi di scadenza (3.277.0), qui sui Percorsi Stagionali: un percorso a tempo poteva aprirsi o chiudersi con un'ora o due di anticipo/ritardo rispetto alla data impostata.
* Il pulsante del Diploma dell'Area Professionale è un interruttore (ogni clic lo assegna o lo revoca): un doppio clic lo assegnava e lo revocava subito, mostrando comunque "Diploma assegnato". Aggiunto il freno che mancava.

= 3.277.0 =
* Corretto uno spostamento troppo anticipato del blocco anti-doppio-clic sulle domande a token (introdotto nella 3.276.0): un oggetto scritto troppo corto faceva scattare inutilmente l'attesa di 5 secondi anche correggendo e reinviando subito. Ora il blocco è scritto dopo i controlli sul testo (che sono solo letture) e prima del primo effetto vero.
* Artigiani della Pasta / Scuole di Cucina — registrare un bonifico aveva lo stesso difetto già corretto sul calendario e sui token: nessuna protezione dal doppio clic (due bonifici nel registro), nessun controllo sull'importo, e la scadenza si sostituiva invece di allungarsi — il gesto naturale "è arrivato il bonifico, registro" poteva incassare un rinnovo senza rinnovare niente. Corretto con lo stesso schema già in uso: identificativo anti-doppio-clic, importo obbligatorio maggiore di zero, e una scadenza che non allunga l'abbonamento ora chiede conferma invece di passare in silenzio.
* Artigiani della Pasta / Scuole di Cucina — creare un partner sull'email di un account già esistente (una sfoglina, un amministratore) lo trasformava in partner senza avviso, facendolo sparire da Le Sfogline, dal nastro e dal contatore: già successo davvero l'8 agosto 2026. Ora il pannello chiede conferma e dice di chi si tratta, prima di procedere. Impedita anche la creazione di una seconda vetrina sullo stesso account (restava invisibile al partner).
* Artigiani della Pasta / Scuole di Cucina — l'avviso di scadenza dell'abbonamento partiva una sola volta, sette giorni prima, e poi taceva anche nei giorni in cui la vetrina si nascondeva davvero. Ora avvisa in tre momenti: sette giorni prima, l'ultimo giorno, e il giorno in cui la vetrina è stata nascosta — con una copia in Posta interna per gli ultimi due, quelli su cui c'è da agire. Stessa correzione anche per l'avviso di scadenza degli abbonamenti delle sfogline (abbonamenti.php), dove però la fase finale avvisa il gestore invece della sfoglina: lì la scadenza è solo un promemoria, non spegne nulla da sola.
* L'interruttore "Abilitata come sfoglina" sulla scheda personale ora vince davvero su qualunque altro stato dell'account (anche "artigiano"/"scuola di cucina"/"lettore"), come il suo stesso commento nel codice ha sempre detto: un controllo scritto sopra di lui lo scavalcava in silenzio in un caso specifico.
* Trovato e corretto un problema più ampio, indipendente da questo giro di correzioni: il tema del sito altera silenziosamente le query che usano il parametro "author" di WordPress, azzerandone sempre il risultato. Corrette le due funzioni che ne dipendevano qui (il controllo "una vetrina per account"); esistono altri punti nel plugin con lo stesso parametro, ancora da verificare uno per uno — vedi il resoconto.
* Corretto un piccolo bug proprio dei moduli di contatto: scrivere a un Artigiano della Pasta faceva scattare per errore anche il limite anti-spam delle Scuole di Cucina.

= 3.276.0 =
* Calendario Corsi — rete di sicurezza per quando l'identificativo anti-doppio-clic non arriva (browser con JavaScript vecchio in cache, capita nei giorni dopo un aggiornamento): un pagamento identico a meno di 15 secondi dall'ultimo viene comunque rifiutato, senza bisogno di rendere obbligatorio l'identificativo — rifiutare un incasso vero sarebbe peggio.
* Token — stesso difetto dei pagamenti del calendario, corretto allo stesso modo: un doppio clic su "Assegna" non accredita più due volte, una correzione con numero negativo è possibile solo per il titolare, e quando il saldo non basta per un consumo il taglio a zero viene ora scritto nello storico invece di sparire in silenzio.
* Consulenze a token — il blocco anti-doppio-invio (20 secondi fra una domanda e l'altra) veniva scritto alla fine invece che all'inizio: un doppio clic o due schede aperte passavano entrambe prima che il blocco fosse scritto, addebitando due volte la stessa domanda oppure regalandone una in più. Corretto anche sulla domanda pubblica gratuita, stesso difetto. Il blocco ha ora una durata minima di 5 secondi indipendente da come è impostata l'attesa visibile, così azzerarla nel pannello non toglie anche la protezione.
* Consulenze a token — il conteggio delle domande di oggi/settimana/mese non rilegge più tre volte tutta la corrispondenza: un solo giro.

= 3.275.0 =
* Calendario Corsi — un doppio clic su "Registra pagamento" poteva registrare lo stesso versamento due volte, e non c'era modo di correggere un importo sbagliato dal pannello. Ora il pulsante manda un identificativo generato all'apertura della scheda cliente, il server rifiuta un secondo invio con lo stesso identificativo, e ogni pagamento (e ogni correzione) resta in un nuovo registro per prenotazione. Le correzioni con importo negativo sono riservate al titolare.
* Calendario Corsi — una sfoglina poteva disdire una prenotazione già chiusa (rimborsata, assente) o di un corso già svolto, cancellando per esempio un rimborso già registrato dal gestore. Ora una disdetta è possibile solo su una prenotazione ancora attiva e un corso non ancora passato.
* Calendario Corsi — una disdetta ora avvisa chi gestisce in Posta interna, con scritto se c'è un acconto da restituire: prima non partiva nessun avviso.
* Calendario Corsi — la verifica "questo cliente ha già versato un acconto?" non carica più tutte le prenotazioni mai fatte sul sito: chiede al database solo quelle di quel cliente.
* Tolta una funzione del Calendario mai usata da nessuna parte del plugin (`gs_cal_totale_cliente`).

= 3.274.0 =
* Nuovo interruttore "Gara mensile del Buono Sfoglia in corso" nello Stato Generale, SPENTO di default e azionabile anche dai collaboratori (non solo dal titolare). Finché è spento non parte nessun resoconto di fine mese: serviva, perché la gara non è ancora partita e il 1° settembre sarebbe arrivato a tutte un resoconto di agosto con "0 punti, mancavano 2500" — lo stesso messaggio sbagliato già capitato con luglio.
* All'accensione dell'interruttore il segnaposto del mese si allinea subito al mese appena finito, così il primo resoconto è quello del primo mese giocato per intero. Senza questa riga, accendendo il 1° del mese prima del giro quotidiano sarebbe partito il resoconto del mese precedente, in cui la gara non c'era ancora.
* Tolto "rettifica-luglio.php": era usa e getta, ha fatto il suo lavoro.

= 3.273.0 =
* "Gioco" diventa "percorso" in tutti i testi che si leggono: il resoconto mensile che arriva a ogni sfoglina, le FAQ, il pulsante "FAQ sul percorso" nella mail dell'area riservata, l'aiuto dei Premi e la scheda delle sfogline archiviate. Rinominate anche le voci di pannello "Contenuti del percorso" e "Esporta i dati del percorso". Nessuna chiave e nessun nome di funzione è stato toccato: i dati salvati restano identici.
* Stato Generale: la fotografia della comunità in quattro numeri — quante sfogline attive in tutto, quante hanno la Vetrina accesa (sono quelle che compaiono nel Nastro), quante si sono collegate negli ultimi 30 giorni, e quante sono già sopra i 2500 punti di questo mese, cioè l'anteprima di chi vincerà il Buono alla chiusura. A cache calda non costa nessuna query in più.
* Rettifica una tantum alle sfogline raggiunte per errore dalla chiusura di luglio 2026: ricevono un messaggio privato che spiega l'errore, e in Posta interna arriva l'elenco di chi è stato raggiunto. Gira una volta sola e si spegne da sé; il file va cancellato a cosa fatta.

= 3.272.2 =
* Stato Generale: nuova riga diagnostica per il Buono Sfoglia — mostra se e quale mese risulta già chiuso e quante sfogline sono state toccate, senza dover controllare il database a mano.

= 3.272.1 =
* URGENTE — protetta anche la chiusura del mese (non solo la scadenza annuale) dal primo avvio: senza questa, il primo passaggio del cron dopo la 3.272.0 avrebbe chiuso "luglio 2026" per errore, mandando due messaggi a ogni sfoglina su un mese in cui il gioco mensile non esisteva ancora. Nessun Buono a rischio (i punti di luglio erano comunque zero per tutte), ma i messaggi sì.

= 3.272.0 =
* Buono Sfoglia — chiusura del mese: se il ciclo si interrompeva a metà (troppe sfogline per il tempo massimo di esecuzione, o il limite di posta dell'hosting), la ripresa ripartiva da capo e chi era già stato elaborato riceveva due volte l'email e un secondo 2,5% di sconto. Ora ogni sfoglina viene segnata come "fatta" prima di mandarle qualsiasi cosa: una ripresa salta chi è già stato elaborato.
* Buono Sfoglia — la chiusura del mese e la scadenza annuale non dipendevano più solo dal giorno 1/1° febbraio esatto: se il cron di WordPress non partiva in quella finestra (dipende dal traffico del sito), saltavano per sempre. Ora girano ogni giorno finché non risultano fatte per il periodo giusto.
* Buono Sfoglia — corretto anche un calcolo del mese che sbagliava nei giorni finali di alcuni mesi (es. dal 31 marzo).
* Pulsante "Accedi / La Mia Sfoglia": il controllo che evita di inserirlo due volte ora cerca in tutta la pagina, non solo dentro il menu (da quando il pulsante è stato spostato sotto la barra, quel controllo non lo trovava più).

= 3.271.4 =
* Nastro piccolo: il controllo per non montarlo dove c'è già il nastro grande ora guarda il contenuto reale della pagina (has_shortcode), non più l'id di una pagina fissata a mano — funziona su qualunque sito, e non spegne mai il nastro piccolo dove il grande non c'è davvero (prima capitava su alcune installazioni di prova).

= 3.271.3 =
* Tolti dal nastro grande della pagina "Le Sfogline" gli otto nomi dimostrativi temporanei (non erano persone reali): ora compaiono solo le sfogline vere con Vetrina attiva.

= 3.271.2 =
* Il pulsante "LA MIA SFOGLIA" non è più una voce dentro il menu (rischiava di andare a capo insieme alle altre voci su finestre più strette): ora è una riga propria, centrata, subito sotto la barra del menu.

= 3.271.1 =
* Corretto il trattino separatore tra le voci del menu principale (introdotto in 3.271.0): il tema azzerava di default il pseudo-elemento usato, ora resta visibile.
* Il pulsante "LA MIA SFOGLIA" nel menu principale ora compare al centro della sequenza delle voci, non più in fondo dopo l'ultima.

= 3.271.0 =
* Nuovo sistema mensile di gioco: contatore punti parallelo per il mese in corso, Buono Sfoglia (soglia mensile di punti convertita in sconto cumulativo sui corsi, con scadenza a fine gennaio dell'anno successivo) e Classifica animata delle prime 10 sfogline del mese ("il Matterello che stende"), visibile nella pagina Classifica.
* Le email di benvenuto (accesso e approvazione dell'iscrizione) ora includono i link alle tre pagine di FAQ del sito.
* Gestori: ora si può eliminare un singolo messaggio dentro una Conversazione privata o una singola risposta a un Messaggio della segreteria, senza toccare il resto dello scambio — eliminazione reversibile (resta nei dati, sempre ripristinabile dallo stesso pannello) e protetta da tre clic di conferma, come il blackout rapido.
* L'avviso mostrato alle sfogline durante un Blackout è stato riscritto (più chiaro, con la scusa per l'interruzione) e arricchito con dei palloncini decorativi animati.
* Limite di peso per foto/video/audio caricati dalle sfogline: alzato da 1 MB a 20 MB (bloccava caricamenti di foto normali da telefono o computer).
* Rinominate alcune etichette nel Pannello Generale ("Lista degli Iscritti ai Corsi") per maggiore chiarezza.
* Vari piccoli ritocchi di uniformità grafica nel Pannello Generale e nella Plancia.

= 3.270.0 =
* Sicurezza: Newsletter — l'iscrizione pubblica non aveva alcun freno anti-spam e cercava i doppioni scorrendo tutti gli iscritti; ora ha il modulo anti-spam e una query mirata sull'email.
* Sicurezza: reimpostazione password — il modulo di reset non aveva alcun limite ai tentativi (unico punto del sistema di accesso a esserne privo); ora ha lo stesso controllo anti-spam degli altri endpoint pubblici.
* Il Pannello Generale non nasconde più gli avvisi di errore e di sicurezza di WordPress: restano nascosti solo quelli informativi e promozionali.
* Diario dell'Impasto e Barometro Stagionale non sono più esposti su /wp-json/ (show_in_rest ora è false di default sui Custom Post Type, con eccezione esplicita solo per Sfide e Ingredienti Segreti, che usano l'editor a blocchi).
* Corretto un bug nella pulizia dei messaggi: un tag HTML scritto come entità (es. &amp;lt;script&amp;gt;) poteva sopravvivere alla pulizia e tornare codice vero in uscita (restava comunque innocuo per via dell'escape ai punti di stampa, ora chiuso anche alla fonte).
* Il tipo di file allegato ai messaggi (foto/video/audio) è ora dedotto dall'estensione reale, non dal tipo dichiarato dal browser; allineato anche il campo di caricamento per accettare l'audio del Matterello Parlante.
* L'esportazione dati in zip non carica più l'intero archivio in memoria: file grandi non rischiano più di fallire per il limite di memoria PHP.
* La Diagnostica segnala ora chiaramente se il server non supporta la compressione video (ffmpeg assente); le sezioni del Pannello che falliscono nel caricamento indicano quale sezione e registrano l'errore nel log del server.

= 3.269.2 =
* Pagina Supporter: aggiunta la riga sull'iscrizione gratuita (mese di accesso omaggio all'area riservata) subito sopra la quota annuale.
* Pagina Iscrizione: aggiunto il box introduttivo che spiega che l'iscrizione è gratuita e dà diritto a un mese di accesso omaggio all'area riservata.

= 3.269.1 =
* Trovata la vera causa delle schede "uguali a prima" nella pagina CORSI: la sezione "Cinque percorsi, un solo metodo" non usava [gs_percorsi_corsi] (corretto nella 3.269.0, ma su una pagina diversa) — era una quarta copia scritta a mano dentro pagina-supporter.php, con la vecchia grafica e scollegata dagli altri tre punti. Ora legge la stessa fonte dati e usa la stessa veste "gs-cs".

= 3.269.0 =
* [gs_percorsi_corsi] (pagina Corsi): stessa veste "gs-cs" di "In Vetrina" invece della vecchia grafica a bottega — le due vetrine dei corsi ora sono davvero identiche, non solo negli stessi dati.

= 3.268.3 =
* 4 schede per riga in "In Vetrina" (corretto da 5 a 4), ombra sempre visibile sulle schede per staccarle dallo sfondo ora dello stesso colore.
* Corretto lo sfondo di ".gs-cs" e delle schede delle Sfogline: una vecchia regola pensata per svuotare i contenitori di layout del tema li azzerava per errore (erano rispettivamente un &lt;section&gt; e un &lt;article&gt;, non esclusi come gli altri blocchi del plugin) — ora sono esclusi con lo stesso schema già usato altrove.

= 3.268.2 =
* 5 schede per riga in "In Vetrina" (Sfogline, Artigiani, Scuole, Corsi), stesse dimensioni ovunque — prima la larghezza era un valore fisso, ora è sempre 1/5 dello spazio disponibile.
* Scheda delle Sfogline semplificata (via la riga streak e la barra di avanzamento, restano nel dettaglio al clic): stessa struttura delle altre tre, quindi stessa altezza.

= 3.268.1 =
* Tolto il font Google (Bodoni Moda/Archivo) aggiunto nella 3.268.0: veniva caricato su ogni pagina del sito, non solo dove serve, e rallentava il rendering quel tanto che bastava a mandare in tilt il calcolo dell'altezza del menu fisso del tema — intestazione duplicata a metà pagina, sfarfallio, pagina che si blocca (segnalato su Rina Poletti, ma il problema riguardava ogni pagina). Le schede del carosello restano leggibili con la scaletta di riserva (Georgia/Helvetica).
* Schede più piccole nel carosello delle Sfogline, per vederne 4 insieme anche in altezza — e scorrimento automatico, come gli altri caroselli del sito.
* La stessa veste (gs-cs) estesa anche ad Artigiani, Scuole e Corsi in "In Vetrina" — prima era solo sulle Sfogline.

= 3.268.0 =
* [gs_carosello_sfogline] con veste nuova: bordi arrotondati, filtri per livello, avanzamento verso il livello successivo, finestra di dettaglio con i badge veri, link alla Vetrina. Sostituisce le vecchie schede solo qui — Artigiani, Scuole e i 5 livelli dei Corsi restano invariati.
* Sfondo beige (colore del menu) esteso anche alle pagine del plugin (Calendario Corsi, FAQ, Le Sfogline, ecc.): un blocco di CSS precedente lo toglieva apposta solo lì, riportandole al bianco del tema.

= 3.267.1 =
* Cornice dei caroselli: ombra più marcata, ora che è dello stesso colore dello sfondo pagina serve per staccarla visivamente.

= 3.267.0 =
* Cornice dei caroselli (Sfogline/Artigiani/Scuole/Corsi): da bianca a beige, colore della barra del menu del sito, sopra e sotto le schede.
* Sfondo di tutta la pagina del sito: stesso beige, colore della barra del menu.

= 3.266.0 =
* Nuova mail "Accesso e Vetrina" (mese di prova, token, Vetrina nel nastro): parte da sola all'approvazione di una sfoglina, oppure la mandi a mano a una sfoglina scelta dal nuovo riquadro in "Iscrizioni delle sfogline" (presente in tutti i pannelli). Logo dell'intestazione impostabile dallo stesso riquadro.
* [gs_calendario] sdoppiato in due shortcode indipendenti — [gs_calendario_griglia] e [gs_calendario_ruota] — per poter mettere la griglia mensile e la Ruota dell'Anno anche su pagine diverse; [gs_calendario] resta invariato dove già installato.
* Pannello Generale: nuova scorciatoia a "Visibilità sezioni e permessi" dentro "Iscrizioni delle sfogline", per non dover cercarla nel gruppo Impostazioni.
* Palloncini de "L'Accademia della Sfoglia": non erano più visibili da smartphone (nascosti sotto 820px), ora compaiono anche lì.
* Il pulsante "Nascondi/Vedi logo" da telefono si sovrapponeva al pulsante del menu del tema: ora è centrato nella barra invece di stare a destra.

= 3.265.1 =
* Popup "Vedi dettagli e prenota": ora blocca lo scorrimento della pagina sotto mentre è aperto (poteva sembrare che pagina e popup si muovessero insieme in modo confuso, soprattutto su telefono).

= 3.265.0 =
* Calendario Corsi: aggiunta la "Ruota dell'Anno", una seconda vista del piano annuale accanto alla griglia mensile — un anello per livello, un pallino per ogni data reale disposto lungo il suo anello in base al mese, medaglione centrale con l'anno. Stesso popup di dettagli e prenotazione al clic.

= 3.264.0 =
* Calendario Corsi: ogni data ora ha un pulsante "🔍 Vedi dettagli e prenota" che apre un popup dedicato con orario, posti, prezzo, presentazione completa e il pulsante di prenotazione — invece del testo che si allargava dentro la scheda stessa.

= 3.263.0 =
* "La Vetrina dei Corsi" nel carosello ora è statica: sono sempre 5 corsi fissi, quindi niente frecce/scorrimento automatico — le schede si vedono tutte insieme, a capo su più righe.
* Cliccando una scheda di un corso, ora si vede anche la sua descrizione (con il prezzo) nella pagina "Calendario e Prenotazioni", non solo l'elenco delle date — utile soprattutto quando per quel corso non ci sono ancora date pubblicate. Reso cliccabile anche il link "scrivici" nel messaggio "nessuna data in programma".

= 3.262.1 =
* Corretto il vero motivo per cui le schede dei caroselli non si muovevano: lo "scroll-snap" della pista annullava lo scorrimento lento appena partiva, riportandolo di scatto al punto di partenza. Tolto lo snap CSS (non serve più: la velocità e l'allineamento scheda-per-scheda li calcola già il movimento lento).
* Pagina "In Vetrina": aggiunto lo sfondo giallo/crema dietro ai riquadri dei caroselli (lo stesso tono della Vetrina dei Corsi).

= 3.262.0 =
* Nuovo quarto carosello, [gs_carosello_corsi]: "La Vetrina dei Corsi" con la stessa grafica a schede degli altri tre (icona, titolo, testo, cartellino), i 5 livelli reali dei corsi. Registrato anche nel Pannello Caroselli con il suo shortcode pronto da copiare.
* Sfondo dei caroselli rifatto come l'originale: cornice bianca con bordo intorno, e dentro una mensola marrone effetto legno (come la vera Vetrina dei Corsi) invece dello sfondo crema piatto. Lo sfondo crema/giallo della pagina resta sotto, non viene più duplicato dentro il riquadro del carosello.
* Palloncini "quadrati" corretti: il bordo arrotondato era fisso a 30px e non si adattava all'altezza del pulsante quando il testo andava a capo, risultando squadrato — ora è proporzionale, sempre perfettamente tondo/a pillola.
* Scorrimento delle schede reso lento e morbido invece dello scatto rapido del browser, sia con le frecce sia nello scorrimento automatico da sole — e allineato alla stessa velocità (pixel al secondo) del nastro fisso sotto il menu, così il movimento è coerente in tutte le zone del sito.

= 3.261.0 =
* I tre caroselli (Sfogline, Artigiani della Pasta, Scuole di Cucina): schede rifatte con la stessa grafica della Vetrina dei Corsi (icona su fondo colorato, titolo, testo, cartellino), più piccole di prima, sfondo crema invece di bianco.
* Corretto un bug nelle frecce dei caroselli: il passo di scorrimento era più corto di una scheda e lo scorrimento "a scatto" lo annullava tornando indietro — le schede sembravano non muoversi mai.
* Corretto il palloncino fermo sulla pagina "L'Accademia della Sfoglia": mancava l'animazione nel codice della pagina (si era spezzata salvando), ora la prende dal plugin.
* Corretta la foto di Gian Paolo Chiossi sulla pagina "Pasta TUA", che restava in bianco e nero mentre le altre due erano nel tono dorato: uno stile scritto a mano nel contenuto di quella pagina, corretto solo lì senza toccare la regola voluta sulla pagina "L'Accademia della Sfoglia".

= 3.260.0 =
* Atelier esteso ai colori "funzionali" della Plancia Generale e del Pannello Generale: Bacheca di riepilogo (schede avorio invece del verde pieno, quella in allarme ora oscilla oro→rosso), i 5 colori per tipo del Calendario Corsi, il rosso/verde di liste d'attesa/pagamenti/vetrine/abbonamenti (stesso ritocco ovunque compaiono), la legenda a 5 colori della Pianificazione dell'Anno, i riquadri informativi di Conversazioni private ed Esperto Risponde, gli elenchi "risultati" della Posta interna. Lasciati intatti i colori che segnalano ancora qualcosa di preciso (errori, sponsor attivo, approvazioni urgenti) e le sezioni con un tono festoso voluto (Aeroplanino, Palloncini).

= 3.259.0 =
* Badge e Traguardi: veste rifatta come le schede delle sfogline nel Carosello — testa colorata con l'icona sopra, base bianca con nome e descrizione sotto, cornice dorata, corona d'alloro dietro l'icona. Corretto anche un bug per cui il nome del badge restava verde invece di prendere il colore giusto.
* Pannello Generale (il nuovo, a schermo intero): estesa a tutta la cornice — intestazione, rotaia laterale, sfondo e contenuto di ogni sezione — la stessa veste "Atelier" della Regia degli Iscritti ai Corsi (avorio, inchiostro, oro, titoli in Cormorant Garamond). Le sezioni con una veste già propria (Regia, Badge) non sono state toccate.
* Plancia Generale (quella classica, tutte le sezioni in una pagina): stessa cornice Atelier — sfondo avorio, titoli in Cormorant Garamond, cornice delle zone color pergamena. I colori per categoria delle testate di ogni zona restano quelli di sempre, non sono stati toccati.

= 3.258.0 =
* Badge e Traguardi: nuova veste a colori pieni, un colore diverso per ognuno dei 9 badge — stesse identiche sfumature (e stesso gradiente) già usate nelle schede della pagina Contenuti del sito, copiate dal vero invece che inventate. Medaglione con anello, cartellino "Sbloccato"/"Da sbloccare", da sbloccare in grigio spento.

= 3.257.0 =
* Menu principale: le scritte erano bruno scuro (colore scelto il 4/08 per leggibilità sullo sfondo chiaro del menu), ora verdi — pagina attiva in verde più scuro invece che nera.
* Corretto un lag intermittente in cima alle pagine (spazio sotto il menu fisso non sempre riallineato quando i font finivano di caricare in ritardo): ora si ricalcola da solo anche quando i font finiscono di caricare, quando il menu/nastro cambiano altezza, e al primo scroll come rete di sicurezza finale.

= 3.256.0 =
* Regia degli Iscritti ai Corsi: nuova veste grafica "Atelier" (scelta tra due anteprime) — sfondo avorio, titoli in Cormorant Garamond, corpo in Manrope, sezioni della scheda numerate in numeri romani con colore diverso a rotazione, nuova intestazione con iniziali/nome/contatti rapidi e "Il Percorso" (linea del tempo a 6 tappe: Iscrizione, Acconto, Corso, Saldo, Diploma, Soggiorno).

= 3.255.0 =
* Pianificazione dell'Anno: sulla scheda di un corso, nuovo link "🎯 Vedi chi è iscritto a questo corso" che apre la Regia degli Iscritti ai Corsi già filtrata su quel corso specifico.

= 3.254.0 =
* Nuovo: "Regia degli Iscritti ai Corsi" — scheda unica a 360° per ogni persona iscritta a un corso, solo per chi gestisce. Elenco filtrabile (in attesa acconto, confermati, in sospeso, conclusi, diplomati), con dati anagrafici, corso e calendario, pagamenti, token, comunicazioni (messaggi e conversazioni unite, con pulsanti email/telefono/WhatsApp/SMS), diploma, note riservate e proposta di soggiorno (B&B GUGO + strutture vicine). Registrata nella Plancia, nel Pannello Generale, nel menu verde ☰ Sezioni del Pannello di Controllo e in Stato Generale.
* Nastro fisso sotto il menu: il pannello "Caroselli per la Home Page" (che lo contiene) è stato spostato dalla categoria "Partner & Vetrine" a "Comunicazione", per trovarlo più facilmente.

= 3.253.0 =
* Menu marrone (linguette): il nome della categoria "Contenuti & Racconti" usciva dal bottone. Ridotti carattere e spaziature dei gruppi, e il testo ora va a capo invece di uscire — nessun nome di categoria, nemmeno uno più lungo aggiunto in futuro, può più sporgere.

= 3.252.0 =
* Corretto un bug del tema: il nastro fisso sotto il menu non veniva contato nello spazio riservato in cima alla pagina, così restava sopra all'inizio del contenuto e ne copriva il titolo (visto sulla pagina "Lentium Notizie").
* Nastro fisso: nuova scelta nel pannello Caroselli — una fila sola, due file (come prima) oppure una fila sola con pillole più grandi.
* Menu marrone (linguette): corretto un bug del tema Newspaper che allungava ogni gruppo chiuso all'altezza dell'intero pannello, lasciando un vuoto enorme prima del gruppo successivo.
* Titolo onorario: dalla scheda personale di una sfoglina si può ora impostare un titolo che sostituisce il livello mostrato in ogni scheda del sito (es. "Socio Onorario" 🏵️), lasciando intatti punti e avanzamento.
* Categoria "Giochi & Sfide" rinominata in "Percorsi e Sfide" (registro unico delle categorie, si aggiorna ovunque compare).

= 3.251.0 =
* Logo dello sponsor sullo striscione dell'Aeroplanino: dimensione doppia (52px), spostato in coda oltre la punta a bandiera invece che sul filo verso l'aeroplanino, e ora regolabile a piacere dal pannello Sponsor.
* Riscritto il testo d'aiuto dello "Streak del Matterello": spiega prima cosa significa "streak" in generale (con esempi), poi come funziona qui — nominando sempre esplicitamente "lo Streak" invece di un vago "il numero".

= 3.250.0 =
* Nuovo registro unico delle categorie: stessi 9 colori e stessi nomi ovunque (Plancia Generale, Pannello Generale, menu verde delle sezioni, pannello sul sito) — prima ogni superficie aveva colori diversi e spesso a caso.
* Nuova categoria "Partner & Vetrine" (Artigiani della Pasta, Scuole di Cucina, Caroselli, Vetrina pubblica, Aspetto «Le Sfogline»); Iscrizioni e Diplomi/Locandine spostati nella categoria più coerente.
* Aeroplanino, Palloncini e Palloncino Gigante riuniti in un'unica zona "In diretta" a linguette, in tutte e tre le superfici — i link diretti dal menu di WordPress continuano a funzionare.
* Menu marrone di navigazione (per le sfogline): da 35 linguette tutte in fila a gruppi richiudibili per categoria, con pallino colorato, un gruppo aperto alla volta, si ricorda l'ultimo aperto (o si apre da solo quello della pagina corrente).
* Aeroplanino e Palloncini: la programmazione automatica ora è precisa al minuto (prima solo "entro l'ora") quando c'è almeno una sfoglina collegata, con una rete di sicurezza oraria per quando non c'è nessuno; nuovo tipo "a ripetizione" (es. ogni 5 minuti per un'ora) per gli eventi dal vivo — disponibile anche per i Palloncini, che prima non avevano la programmazione.
* Logo dello sponsor (Aeroplanino e Palloncini): ora si può scegliere anche dalla libreria Media di WordPress, oltre a incollare l'indirizzo a mano — disponibile anche nella Plancia classica e nel Pannello Generale.
* "La Mia Sfoglia" riprogettata: nuova testata con foto, livello e tutti i numeri insieme (punti, streak, scudi, token, badge, sconto corsi), navigazione rapida a pillole, e quattro fasce colorate (Oggi / Il mio percorso / Le mie sfide / I miei strumenti) con gli strumenti diventati cassetti richiudibili. Nessuna funzione tolta, solo riorganizzata. La Vetrina pubblica non cambia.

= 3.249.0 =
* Posta interna: corretto il pulsante "Segna come letto" — un onclick residuo sul pulsante impediva al clic di arrivare al gestore vero, quindi non smetteva mai di lampeggiare in rosso.
* Sponsor dell'Aeroplanino: ora un elenco di più loghi, ognuno col proprio periodo di attività (prima uno solo fisso).
* Palloncini: nuovo elenco sponsor separato con la stessa logica dell'Aeroplanino, con la scelta se mettere il logo su un solo palloncino o su tutti quelli della lanciata.
* Palloncini: colori allineati alla vera identità del sito (verde, oro, terracotta) al posto dell'arcobaleno generico di prima.
* Pannelli Aeroplanino e Palloncini riorganizzati in sotto-sezioni richiudibili (Invia adesso / Sponsor / Programmazione / Storico), con il conteggio nel titolo, per essere più leggibili.
* Posta interna: nei messaggi di richiesta approvazione (biografia, ricetta, testimonianza, matterello parlante, artigiani, scuole di cucina) nuovo pulsante "Vai all'approvazione" che porta direttamente al pannello giusto.

= 3.248.0 =
* Pannello di Controllo: nuovo pulsante "Nascondi logo" (stessa funzione già presente su alcune pagine, ora richiamabile anche da qui).
* Spazio tra nastro e titolo ridotto ora su TUTTE le pagine del sito (prima solo su quelle del plugin).
* Pagina "Le Sfogline": righe del nastro grande più strette, per far entrare la terza fila senza scorrere.
* Aeroplanino: nuovo "Sponsor del momento" (logo agganciato al filo dell'aeroplanino, cliccabile) e nuova programmazione automatica degli invii (una volta, ogni anno/mese/settimana/giorno) — precisione entro l'ora scelta, non al minuto esatto.

= 3.247.0 =
* Nastro grande "Le Sfogline" ridotto da 4 a 3 file.
* Pagina "Le Sfogline": tolta la griglia normale delle schede sotto il nastro (era ridondante).
* Pulsante manuale "Nascondi logo" esteso anche alla pagina "Le Sfogline" (prima solo sulle pagine con iframe grande).

= 3.246.0 =
* Nastro grande "Le Sfogline" molto più lento: la velocità ora è calcolata dal vero (stessa velocità in pixel/secondo del nastro piccolo del menu, ~54,5 px/s) invece di un numero di secondi indovinato — prima girava a 26-42s, ora tipicamente intorno ai 130s a giro.
* Corretto anche un difetto introdotto nella versione precedente: le file rendevano 3 giri di contenuto con un'animazione pensata per 2, causando un possibile scatto a ogni ripetizione.
* Nastro piccolo del menu nascosto solo sulla pagina "Le Sfogline" (aveva già il suo nastro grande dedicato), con recupero automatico dello spazio.

= 3.245.0 =
* Nuovo pannello "Chi appare nel nastro" (dentro Caroselli per la Home Page): esclusione singola di sfogline/artigiani/scuole dal nastro fisso, con "Tutti"/"Nessuno" per categoria. Corretto anche un bug esistente: il pulsante Salva di quel pannello non inviava affatto i campi del nastro.
* Nuovo shortcode [gs_nastro_grande_sfogline]: nastro a 4 file, il doppio delle dimensioni, per la pagina "Le Sfogline". ATTENZIONE: contiene temporaneamente nomi dimostrativi non reali, da rimuovere su richiesta esplicita di Ennio (deroga concessa il 17/08/2026).

= 3.244.0 =
* Foto delle schede sfogline (carosello Home e pagina "Le Sfogline"): ora è sempre e solo quella della biografia, non più l'ultimo lavoro caricato.
* Ennio Barbieri aggiunto nel nastro fisso come "Fondatore".

= 3.243.0 =
* Ridotto lo spazio bianco tra il nastro/menu e l'inizio del contenuto sulle pagine "su misura" con HTML incollato a mano (Appello, Diventa Supporter, Disciplinare, Scrivici...): il tema riservava 60px per un titolo che in queste pagine non esiste mai — verificato dal vivo, ridotto da 92px a 48px in stato "logo nascosto".

= 3.242.0 =
* Nastro fisso tornato a tutta larghezza pagina (era stato ristretto alla larghezza della barra del menu in una versione precedente).

= 3.241.0 =
* Ridotto ulteriormente lo spazio bianco tra le due barre rimaste quando il logo è nascosto: due margini da 22px scritti dal tema (44px in tutto) sono stati ridotti a 8px.

= 3.240.0 =
* Tolto il pulsante manuale "Nascondi logo": lo scroll automatico (giù nasconde, su rimostra) basta da solo, senza bisogno di un pulsante fisso sullo schermo.

= 3.239.0 =
* Corretto per davvero "Nascondi logo": mancava un pezzo — il tema scrive un'altezza fissa (168px) direttamente nell'HTML della riga che contiene il logo, indipendente dal contenuto. Il logo spariva ma menu e nastro non salivano. Ora si azzera anche quella riga: verificato dal vivo, libera 168px di spazio.

= 3.238.0 =
* Corretto il nastro fisso: ora è largo esattamente quanto la barra del menu (sfondo compreso), non più a tutta larghezza dello schermo.
* Corretto "Nascondi logo": il collasso del logo era animato con una transizione CSS che sulla barra "aggrappata" del tema non arrivava mai a destinazione, quindi lo spazio non si liberava davvero. Ora è istantaneo e garantito.
* "Nascondi logo" ora si attiva anche da sola scorrendo la pagina verso il basso, e il logo ricompare risalendo verso l'alto — oltre al pulsante manuale, che resta.
* "La Vetrina dei Corsi" (i livelli dei corsi, sullo scaffale) è tornata sulla pagina Calendario Corsi, sopra il calendario reale delle prenotazioni (ora "Calendario e Prenotazioni").
* Scheda di Bruno Cingolani nella pagina Accademia: "Maestro Ospite" è diventato "Maestro della Materia Prima".

= 3.237.0 =
* Palloncini, Palloncino Gigante (pioggia) e Palloncino Gigante (grande) ora condividono la stessa forma, lo stesso riflesso lucido e — sui palloncini della pioggia e del grande, che ne erano privi — lo stesso filo bianco che si muove da solo, come già avveniva sui Palloncini normali.
* "Nascondi menu" è diventato "Nascondi logo": ora sparisce solo il logo (non più tutta l'intestazione), e il nastro fisso sale subito sotto la barra del menu rimasta, senza lasciare vuoto.
* Il nastro fisso ora si ferma alla stessa larghezza della barra del menu, invece di arrivare fino ai bordi dello schermo.
* Sfondo delle pagine del plugin (come la Home) aggiornato a #F3E5C7.
* Ridotto lo spazio vuoto tra il nastro fisso e il titolo della pagina, su tutte le pagine tranne la Home.

= 3.236.0 =
* Logo Mulino Marino nel nastro: sostituito con una versione più nitida, e ora è cliccabile verso mulinomarino.it (si apre in una nuova scheda, per non far perdere il posto sul sito).
* Più spazio verticale tra le righe di schede nella sezione "Programmi" di Corsi, quando vanno a capo — prima erano troppo vicine.

= 3.235.1 =
* Aggiunto il quinto corso mancante ("Corso Privato") alla nuova sezione "Programmi" della pagina Corsi — nella prima versione ne mancava uno.

= 3.235.0 =
* Nastro fisso: ora sono due file sovrapposte che scorrono in direzioni opposte, come nel progetto originale (prima era una sola).
* Nastro fisso: il logo di Mulino Marino scorre intervallato tra un nome e l'altro, su entrambe le file.
* Nuovo: sezione "Programmi" sulla pagina Corsi generata dal plugin (prima 4 blocchi scritti a mano, a rischio di corruzione al salvataggio come già capitato altrove) — scheda breve con riassunto e dati essenziali, "Vedi il programma" apre sotto tutto il testo di sempre (obiettivo, capitoli, elenchi). Grafica nera con bordo oro, "Porta al Registro" ben visibile su Professionale e Rina Online.
* Attivate per davvero (erano rimaste solo sul sito di prova) le Vetrine pubbliche di Bruno Cingolani, Rina Poletti, Valeria e Giuseppe Govoni, e acceso il Nastro fisso nelle impostazioni: era spento.

= 3.234.1 =
* Corretto il colore dello sfondo giallino dietro "I Nostri Percorsi" in Calendario Corsi: ora è esattamente lo stesso della Home Page (#f5ecd7), non più un giallo simile ma diverso.

= 3.234.0 =
* Corretto: le 5 schede "I Nostri Percorsi" in Calendario Corsi erano scritte a mano nel contenuto della pagina e WordPress le spezzava in pezzi ad ogni salvataggio (una scheda vuota compariva come "barra bianca" sotto le altre) — stesso problema già risolto per Sostienici. Ora sono generate dal plugin con un nuovo shortcode, [gs_percorsi_corsi], immune a questo tipo di corruzione.
* Corretto: lo sfondo giallino dietro le schede era diventato per errore un motivo a onde (pensato per un altro punto del sito) invece del colore pieno paglierino originale.

= 3.233.0 =
* Pagina "Diventa Supporter": le 5 schede dei corsi ("I corsi di formazione") ora hanno la stessa grafica a "vetrina di bottega" di Calendario Corsi, con la stessa cornice bianca e gli stessi colori per corso — testi e link invariati, "Porta al Registro" ora nel cartellino verde di Corso Professionale e Rina Online al posto del livello.
* Corretto: la scritta "· La Vetrina dei Corsi ·" (e ogni altra insegna dello stesso tipo) poteva uscire verde invece che color crema, perché una regola del tema (più specifica per via dell'ID) vinceva sul colore del plugin.

= 3.232.0 =
* Calendario Corsi rinominato "La Vetrina dei Corsi", con la stessa grafica a "vetrina di bottega" (ripiani di legno, schede che si sollevano al passaggio del mouse) già approvata per Artigiani/Scuole — stesso shortcode [gs_calendario], stesse pagine collegate, stesse prenotazioni/pulsanti/pagamenti: cambia solo l'aspetto. Ogni corso ha un colore proprio fisso (si ripete identico anche in "Le mie prenotazioni", per riconoscerlo a colpo d'occhio), e una barra di legno separa ogni gruppo di 3 schede.
* Corretto un tetto massimo di 60 secondi sul campo "Velocità" dei Caroselli: prima si poteva salvare per errore un numero enorme (successo davvero, 7001) che di fatto fermava lo scorrimento automatico.

= 3.231.0 =
* Nuovo: Cestino messaggi in "Messaggi alle sfogline" — i messaggi eliminati non spariscono più senza lasciare traccia, restano recuperabili con un clic su "Ripristina" (verificato: l'eliminazione lato server risultava già corretta anche prima, il problema era la mancanza di un modo per ritrovarli).
* Nuovo: lo stesso Cestino, con ripristino, anche per lo storico dei messaggi dell'Aeroplanino — prima non si potevano eliminare affatto.
* Nuovo: pagina "Stato Generale" (in cima alla Plancia classica e al Pannello Generale) — tutti i servizi del plugin, accesi o spenti, in un unico elenco cercabile con un interruttore che agisce subito, senza dover aprire ogni pannello uno per uno né premere un pulsante "Salva" in fondo a una tabella lunga.

= 3.230.0 =
* Nuovo: nella scheda personale di ogni sfoglina, un interruttore "Vetrina pubblica attiva" per il gestore — accende/spegne la sua Vetrina SENZA spendere token (diverso dal pulsante che usa lei stessa nel proprio pannello, che invece consuma 5 token dal suo saldo). Utile per collaboratori/docenti o per un'attivazione-regalo, senza dover prima trovare o assegnare token.
* Nuovo: nel pannello "Caroselli per la Home Page", legenda con interruttori Sfogline/Scuole/Negozi (Artigiani della Pasta) per scegliere quali categorie mescolare nel Nastro fisso sotto il menu.
* Migliorata la chiarezza del pulsante "LETTO" nella Posta interna: ora si chiama "✓ Segna come letto", per non confondere un'azione da compiere con un'etichetta di stato già acquisito.

= 3.229.1 =
* Corretto un bug serio: chi ha l'interruttore "Abilitata come sfoglina" acceso sulla propria scheda (i collaboratori che restano Editor/Amministratore ma devono anche poter fare le sfogline, come Rina Poletti e Bruno Cingolani) poteva comunque sparire dall'elenco principale — e finire persino nel Cestino — se il suo stato risultava Sospesa o Eliminata, anche per errore: l'interruttore, pensato come "il SÌ definitivo", veniva invece ignorato. Corretto in tre punti (gs_e_sfoglina_vera, l'elenco principale, il Cestino): ora l'interruttore vince sempre. Rina Poletti e Bruno Cingolani sono già stati ripristinati a mano sul sito reale; con questa versione il problema non può più ripetersi da solo.

= 3.229.0 =
* Nuovo: Palloncino Gigante, nel pannello "Palloncino Gigante" (Plancia WordPress e Pannello Generale sul sito, gruppo Messaggi). Un palloncino si gonfia fino a coprire tutto lo schermo e scoppia — su ogni sfoglina e gestore collegato in quel momento — con velocità, dimensione, colore e suono regolabili. Allo scoppio, a scelta: pioggia di palloncini piccoli, un messaggio dell'amministrazione, oppure l'Aeroplanino con un testo. La pioggia dei palloncini piccoli può mostrare le foto vere delle sfogline al posto del colore pieno (colore pieno / sempre foto / mix), e il palloncino gigante può essere dedicato a una sfoglina in particolare (es. la festeggiata di un compleanno), con la sua foto. Verificato con dati veri su un ambiente di prova: invio, risoluzione della persona scelta, campione di sfogline con foto e ricezione da parte di ogni schermo collegato.

= 3.228.0 =
* Nuovo: Cestino delle sfogline. Chi ha stato Rifiutata, Sospesa o Eliminata non compare più mescolata alle sfogline vere nell'elenco principale del pannello: ora ha un Cestino a parte (stesso pannello "Cerca sfoglina"), sempre gestibile da lì (riattivabile in qualunque momento). I suoi dati di gioco (punti, streak, badge, squadra, classifica) vengono archiviati quando entra nel Cestino — mai cancellati per sempre, coerente con la regola del progetto — e ripristinati identici se torna Approvata. Verificato con dati veri: chi va nel Cestino sparisce da conteggi e classifica, chi ne esce riprende esattamente gli stessi punti di prima.

= 3.227.0 =
* Nuovo: nastro scorrevole fisso appena sotto il menu, su tutte le pagine del sito — alterna sfogline con Vetrina attiva, Artigiani della Pasta e Scuole di Cucina, senza bisogno di incollare uno shortcode. Si accende dal pannello "Caroselli per la Home Page", dove si sceglie anche quante voci mostrare. Il contenuto della pagina scende automaticamente di quanto occupa il nastro (stessa misura già usata per il menu), così non nasconde mai l'inizio di nessuna pagina.

= 3.226.0 =
* Corretto un problema serio su smartphone: su OGNI pagina del sito il contenuto iniziava nascosto sotto il menu in cima, invece di iniziare subito sotto. Causa: una regola del tema, valida solo su schermi piccoli (per questo da computer sembrava tutto a posto), che vinceva sulla nostra a parità di "importanza" perché scritta in modo più specifico. Corretto puntando esattamente alla stessa regola. Verificato dal vivo su più pagine (La Mia Sfoglia, un articolo del blog): il contenuto ora parte sempre subito sotto il menu, mai più nascosto.

= 3.225.0 =
* Posta interna: tolto un tetto nascosto di 100 messaggi nel conteggio e nell'elenco (contatore dei non letti compreso). Superata quella soglia in tutta la storia della Posta interna, i messaggi più vecchi sarebbero diventati invisibili e non più contati, anche scorrendo le pagine. Ora non c'è più limite.

= 3.224.0 =
* Corretto un punto debole nella protezione del login contro i tentativi ripetuti di indovinare la password: se qualcuno mandava molti tentativi tutti insieme (invece che uno alla volta), il blocco automatico dopo 5 tentativi sbagliati poteva non scattare in tempo, lasciandone passare fino al doppio. Verificato: anche con 100 tentativi sparati insieme, ora ne passano sempre e solo 5, come previsto.

= 3.223.0 =
* Corretto un rischio di overbooking nel Calendario Corsi: se più persone prenotavano lo stesso corso quasi nello stesso momento, potevano finire accettate più prenotazioni dei posti realmente disponibili (dimostrato con un test: 3 posti, 4 prenotazioni accettate). Ora due prenotazioni sullo stesso corso non possono più essere accettate insieme — verificato fino al caso limite di un solo posto disponibile con 50 richieste simultanee, sempre e solo una accettata.

= 3.222.0 =
* Corretto "Ripristina dal cestino" in tutto il plugin (posta interna, aiuto, calendario, ricettario, artigiani, scuole di cucina e altri venti punti): da qualche tempo WordPress stesso, quando si ripristina qualcosa dal cestino, lo rimette come "bozza" invece che come prima — restava salvato ma spariva da ogni elenco, come se il ripristino non avesse fatto nulla. Corretto in un solo punto per tutto il plugin insieme.

= 3.221.0 =
* Corretto un difetto serio nel voto delle sfoglie: quando più sfogline votavano la stessa sfoglia quasi nello stesso momento, alcuni voti potevano sparire silenziosamente (la sfoglina riceveva comunque il messaggio "voto registrato"), portando via con sé anche i punti che ne derivavano. Dimostrato con un test di carico reale: fino a metà dei voti andava persa. Stesso difetto corretto anche nei commenti liberi sulle sfoglie e nel sistema punti generale (usato anche da badge, missioni, streak). I voti e i commenti già salvati continuano a funzionare esattamente come prima — nessun dato esistente viene toccato.

= 3.220.0 =
* Contatore "Click per leggerlo" dell'Aeroplanino: corretto un difetto che, con molte sfogline che cliccavano nello stesso momento, faceva perdere sistematicamente circa metà dei click (verificato con un test di carico simulato: a 50 click contemporanei ne risultavano contati solo 25). Il conteggio ora è a prova di concorrenza, verificato fino a 144 click simultanei senza perderne nessuno.

= 3.219.0 =
* Aeroplanino della redazione (Pannello Generale): lo striscione ora è cliccabile anche quando non porta da nessuna parte — un clic conta come "letto". Nell'elenco dei messaggi già inviati è comparsa una nuova colonna, "Click per leggerlo", con quante sfogline hanno cliccato su ciascun messaggio.

= 3.218.0 =
* Voto delle sfoglie: ora è obbligatorio scrivere il motivo del voto (un campo di testo nello stesso modulo delle stelle) — senza motivo il voto non si invia. Il motivo compare sulla scheda della sfoglia, separato dai commenti liberi già esistenti.

= 3.217.0 =
* Posta interna (pannello classico e Pannello Generale): i messaggi non letti ora lampeggiano in rosso finché non si clicca l'apposito pulsante "LETTO" — aprire/leggere il messaggio da solo non lo segna più come letto in automatico. Cliccando "LETTO" il lampeggio si ferma e compare una conferma verde. Le altre sezioni che condividono lo stesso meccanismo di apertura (Aiuto, Conversazioni, Calendario, Diario, Consigli…) non sono state toccate: continuano a funzionare come prima.

= 3.216.0 =
* Soluzione definitiva per la pagina "Diventa Supporter": non è più una pagina scritta a mano nell'editor di WordPress (che continuava a rompersi in modo diverso a ogni salvataggio) — ora la genera il plugin, come già fa per Calendario Corsi e le altre pagine. Nuovo shortcode `[gs_pagina_supporter]`: nella pagina va incollato solo questo, niente più HTML/CSS a mano. Tolto anche il "cerotto" JavaScript che ricostruiva le schede corsi, non serve più.

= 3.215.0 =
* Trovata e corretta la causa vera per cui le pagine scritte a mano (Diventa Supporter, Scrivici, Disciplinare, Appello) si rompevano a ogni salvataggio: WordPress applicava comunque la sua formattazione automatica (wpautop, quella che aggiunge `<p>` e `<br />` da solo) al codice già completo incollato in quelle pagine, spezzando tag e link. Il plugin ora disattiva questa formattazione automatica SOLO per le pagine riconosciute come "scritte a mano" (quelle con `id="gs-...-page"` all'inizio del contenuto), lasciandola intatta per articoli e pagine normali del sito.

= 3.214.0 =
* Cambiato approccio per le 5 schede corsi della pagina "Diventa Supporter" (Sostieni l'Accademia): invece di dipendere dall'HTML salvato in quella pagina (che il sito continuava a rompere in modo diverso a ogni salvataggio, staccando il pulsante "Vedi il programma" dalla sua scheda), ora il plugin ricostruisce quella sezione via JavaScript a ogni caricamento della pagina. Il risultato è sempre corretto indipendentemente da cosa sia effettivamente salvato in quella pagina. Non serve più incollare codice HTML corretto in quella sezione della pagina.

= 3.213.0 =
* Corretto un problema serio: al tuo account amministratore mancava il permesso "unfiltered_html" (probabilmente tolto da una misura di sicurezza dell'hosting), per cui WordPress eliminava in silenzio i tag `<style>` e `<script>` da qualunque contenuto salvato — è per questo che la pagina "Diventa Supporter" ha perso tutto lo stile dopo l'ultimo salvataggio. Il plugin ora ripristina questo permesso per gli amministratori a ogni caricamento del pannello di WordPress. Riguarda solo gli amministratori, non gli altri ruoli.

= 3.212.0 =
* Calendario Corsi: aggiunto un filtro per livello raggiungibile da link esterni (es. dalla pagina "Diventa Supporter"), tipo `?livello=base` (anche intermedio, professionale, privato, online) — mostra solo le date in programma per quel corso, con un link per tornare a vedere tutti i corsi. Serve a collegare ogni scheda del percorso, sulla pagina esterna dei corsi, direttamente alle sue date reali in calendario.

= 3.211.1 =
* Corretto: nel pannello "L'Esperto Risponde", il menu "Esperto (account che risponde)" — sia per creare un nuovo canale sia per modificarne uno esistente — mostrava letteralmente tutti gli utenti registrati sul sito (anche chi si è solo iscritto al blog o ha lasciato un commento, senza avere niente a che fare col progetto). Ora mostra solo collaboratori, sfogline vere e il titolare.

= 3.211.0 =
* Aggiunto in Posta interna un pulsante "Ripara i vecchi messaggi 'Nuova registrazione'": corregge una tantum i messaggi già salvati con "nn"/"n" al posto degli a capo (il bug corretto in 3.210.1), ricostruendo il testo originale con gli a capo veri. Non tocca nessun altro messaggio.

= 3.210.1 =
* Corretto: il messaggio automatico "Nuova registrazione" in Posta interna mostrava "nn" e "n" al posto degli a capo (es. "persona.nnNome: ..."). Causa: nel codice il testo del messaggio andava a capo con `\n`, ma era scritto tra apici singoli, dove PHP non lo interpreta come a capo — restava testo letterale, poi ripulito da WordPress lasciando solo le "n". Corretto usando gli apici giusti: da ora gli a capo sono veri.

= 3.210.0 =
* Pannello Generale, riquadro "Posta interna — ultimi arrivi": l'anteprima di ogni messaggio mostrava una sola riga tagliata con "...". Ora mostra fino a 3 righe di testo, per leggere molto di più dell'oggetto senza dover aprire il messaggio.

= 3.209.0 =
* Aggiunto: anche la paginazione nativa del tema (i numeri di pagina del blog in home e altrove — link veri, non del plugin) ora resta ferma nello stesso punto della pagina invece di ripartire dall'alto. Il campo "Footer Custom HTML" del tema toglie i tag `<script>` da quello che ci si incolla, quindi non era possibile risolverlo da lì: riusa il meccanismo già attivo su tutto il sito per i ricaricamenti del plugin.

= 3.208.0 =
* Corretto: cliccando i pulsanti della paginazione (‹ pagina precedente / pagina successiva ›) in un elenco lungo, la pagina saltava in cima invece di restare ferma dov'era. Causa: quando un pulsante appena cliccato veniva disabilitato (a inizio o fine elenco), il browser gli toglieva il focus spostandolo all'inizio del documento, trascinandosi dietro anche lo scroll. Corretto togliendo il focus dal pulsante prima di disabilitarlo.

= 3.207.0 =
* Corretto: eliminando un corso dal Calendario, il pannello tornava in cima alla pagina invece di restare fermo dov'era. Ora il corso eliminato sparisce direttamente dalla lista, senza ricaricare la pagina.
* Ultimo giro di controllo su "chi compare come sfoglina": corretti i conteggi "totale sfogline" nelle bacheche (contavano prima letteralmente tutti gli utenti WordPress del sito), la Cassaforte del Sapere, "I Testamenti delle Maestre" e le Squadre/Classifica a squadre — tutti ora passano dalla stessa funzione definitiva gs_e_sfoglina_vera().
* Calendario Corsi: in fondo a ogni corso, pulsante "Scrivici per informazioni" con oggetto email già compilato (nome del corso e quota, se indicata).
* Nuovo pannello dedicato "Ingrediente Segreto" (Nome, Descrizione, Foto, data/ora di rivelazione, "Chi lo comunica": Rina Poletti o Bruno Cingolani) per crearlo senza passare dall'editor generico di WordPress. Collegato nei tre pannelli (Plancia WordPress, Pannello Generale, pannello sul sito); il vecchio link "+ Ingrediente" ora apre questo pannello invece dell'editor grezzo.
* Nuovo shortcode [gs_corsi_pulsanti corso="Nome corso" prezzo="150"] da incollare a mano in fondo a ogni corso nella pagina "Corsi" (pagina scritta a mano, non gestita dal plugin): due pulsanti, "Vai al Calendario e Prenotazioni" e "Scrivici", con oggetto email già compilato.
* Calendario Corsi: leggere ondine gialle decorative sullo sfondo della pagina.

= 3.206.0 =
* Chi compare tra le sfogline (Le Sfogline, caroselli, Classifica) si decide ora SOLO da dati certi, non da supposizioni: ruolo dell'account, stato dell'iscrizione, e le azioni Disattiva/Elimina fatte a mano dalla scheda personale. Era stato provato un filtro automatico che escludeva gli account "sospetti" (senza stato registrato e con 0 punti): rimosso subito perché escludeva anche sfogline vere ancora ferme a 0 punti — un account non è finto solo perché non ha ancora guadagnato punti.
* Promemoria su come gestire gli account che non devono comparire: nel pannello "Cerca sfoglina" → "Vedi tutte le sfogline registrate" → clic sul nome → Disattiva o Elimina. Restano visibili in QUEL pannello (con lo stato "sospesa"/"eliminata" nell'ultima colonna) proprio per poterli riattivare quando serve, ma spariscono da tutte le pagine pubbliche. Per l'operazione opposta — far comparire tra le sfogline un docente o un account con ruolo Editor/Amministratore, come Bruno Cingolani — si usa l'interruttore "Mostra comunque tra le sfogline" nella sua scheda personale.

= 3.205.1 =
* Corretto: "Disattiva account" ed "Elimina" nella scheda personale non avevano alcun effetto su account con permessi da amministratore/collaboratore (proprio il caso degli account di prova come Ennio, Rina, Bruno) — un controllo di sicurezza troppo ampio li bloccava a monte. Ora l'unica protezione è non poter disattivare/eliminare il proprio stesso account collegato (per non restare fuori dal pannello); tutti gli altri account, di qualunque ruolo, si possono disattivare/eliminare normalmente. Corretto anche un problema collegato: la disattivazione ora nasconde davvero l'account dagli elenchi pubblici (Le Sfogline, caroselli, Classifica) anche per gli amministratori, restando comunque sempre visibile e recuperabile dal pannello "Cerca sfoglina".

= 3.205.0 =
* Nuovo, su richiesta di Ennio: un docente o un account di prova (Editor/Amministratore/Collaboratore) può ora comparire comunque tra le sfogline — "Le Sfogline", caroselli, Classifica, ecc. — senza dover cambiare il suo ruolo vero e senza toccare i suoi permessi wp-admin. Si attiva per singolo account dalla sua scheda personale in "Cerca sfoglina e recupera file" (appare solo per gli account che ne avrebbero altrimenti bisogno, non per le sfogline normali).
* Promemoria: nel pannello "Cerca sfoglina", cliccando "Vedi tutte le sfogline registrate" e poi un nome, si apre la scheda personale completa con i pulsanti Attiva/Disattiva/Elimina — funzione già presente dalla 3.203.0, non nuova in questa versione, ma facile da non notare se si sta ancora testando una versione precedente.

= 3.204.0 =
* Corretto un bug reale, segnalato con screenshot: in "Le Sfogline" comparivano Bruno Cingolani come sfoglina e un account doppio/vuoto di Ennio Barbieri. Causa: lo stesso bug già corretto una volta in gs_sez_sfogline() (v3.197.0) era rimasto duplicato — con logica leggermente diversa e quindi incompleta — in molti altri punti scritti separatamente nel tempo (pagina "Le Sfogline", i nuovi caroselli, Classifica generale, Premio di Fine Anno, elenco "Cerca sfoglina", esportazione dati, e una decina di altri elenchi/menu a tendina nei pannelli). Creata UNA sola funzione definitiva (gs_e_sfoglina_vera(), in helpers.php) che decide chi è davvero una sfoglina; tutti quei punti ora usano questa e solo questa, per evitare che lo stesso bug si ripresenti ancora altrove in futuro.
* Caroselli: aggiunto lo scorrimento automatico (si ferma al passaggio del mouse/dito, riparte quando ci si allontana), con velocità impostabile dal pannello "Caroselli per la Home Page". Il carosello delle sfogline ha anche un ordine casuale opzionale per chi non ha ancora attivato la Vetrina — chi l'ha attivata coi token resta sempre in prima fila.

= 3.203.1 =
* Corretto un errore grave segnalato subito da Ennio: il plugin non si attivava più ("deve essere corrotto"). Causa: clonando Gli Artigiani della Pasta per creare Le Scuole di Cucina (v3.203.0), la funzione gs_data_it() era rimasta duplicata in entrambi i file — PHP non permette due funzioni con lo stesso nome, quindi l'intero plugin falliva già al caricamento, prima ancora di poter attivare qualsiasi cosa (per questo TUTTI gli shortcode del sito, non solo i nuovi caroselli, comparivano come testo grezzo tra parentesi quadre). Rimossa la funzione duplicata da scuole-cucina.php; verificato che non ci sono altre funzioni, shortcode o costanti duplicate in tutto il plugin.

= 3.203.0 =
* Nuovo: tre caroselli scorrevoli copiabili con uno shortcode (sfogline, Artigiani della Pasta, Scuole di Cucina) — per la Home Page o qualunque altra pagina, gestibili (quante schede mostrare) da tutti e tre i pannelli generali.
* Aeroplanino: ogni messaggio inviato resta ora elencato in una tabella paginata sotto il modulo di invio (data, testo, chi l'ha mandato, quante sfogline raggiunte) — prima non c'era nessuna traccia dei messaggi passati.
* Missione "vota 3 sfoglie" ora richiede anche un commento: aggiunta la possibilità di commentare (non solo votare) ogni sfoglia in gara, con elenco dei commenti già scritti sotto ogni scheda. La missione si completa solo con entrambe le condizioni fatte, per gli stessi 10 punti.
* Artigiani della Pasta e Scuole di Cucina: aggiunto un avviso automatico quando l'abbonamento sta per scadere (email al partner + avviso nella Posta interna dei gestori) — prima l'abbonamento scaduto nascondeva la vetrina in silenzio, senza avvisare nessuno.
* "Cerca sfoglina e recupera file" molto ampliato: ora c'è anche l'elenco completo delle sfogline con paginazione e ricerca, e cliccando un nome si apre la sua scheda personale — dati anagrafici modificabili, attivazioni (abbonamento, Vetrina, saldo token), il suo percorso fino a questo momento (livello, streak, badge, lezioni viste, ricette approvate), e i pulsanti per attivare/disattivare o "eliminare" l'account (mai una cancellazione vera: resta sempre recuperabile riattivandolo dalla stessa scheda, stessa filosofia del cestino usata in tutto il resto del plugin).

= 3.202.0 =
* Nuovo: "Le Scuole di Cucina", vetrine di partner paganti per le scuole di cucina — stesso identico meccanismo de "Gli Artigiani della Pasta" (account autogestito, approvazione, abbonamento a bonifico), gestibile dalla Plancia classica, dal Pannello Generale nuovo e con la propria scheda nella Bacheca di riepilogo. Nel pannello Token, aggiunto un promemoria di importo abbonamento distinto per Artigiani e Scuole (non collegato ai token, resta un pagamento a bonifico), così i due costi restano visibilmente separati.
* Vetrina pubblica: ora si attiva una volta sola spendendo dei token dal proprio saldo (5 di default, importabile dal pannello Token) — prima bastava l'interruttore generale acceso. Finché non è attivata: nessun link condivisibile, la pagina pubblica risponde "non ancora attivata" anche a chi prova ad aprirla direttamente, e la scheda in "Le Sfogline" non è cliccabile. Una volta attivata: link pubblico pronto, scheda cliccabile, e sale in cima a "Le Sfogline" insieme alle altre vetrine attive, in ordine alfabetico per cognome.

= 3.201.0 =
* "Come funziona il Percorso": aggiunti due schemi colorati sopra il testo — la barra dei sei livelli (Le Insegne della Sfoglia, con soglie vere) e una griglia di alcune fonti di punti principali, entrambi letti dalle impostazioni reali (si aggiornano da soli se cambi soglie o punti dal pannello, non serve riscriverli a mano). Aggiunto anche il supporto al grassetto nei testi modificabili dal pannello (racchiudi una parola tra **due asterischi**), usato anche nel messaggio di benvenuto in cima a «La Mia Sfoglia», riscritto meglio.
* «La Mia Sfoglia» riordinata su richiesta di Ennio: l'avviso messaggi non letti ora è la primissima cosa in alto; "Le Mie Sfide" (i tuoi lavori inviati) è sotto "Missioni di oggi"; lo Streak del Matterello è sotto l'Ingrediente Segreto; "La Mia Biografia" è sotto "Aiuto e Suggerimenti", in fondo alla pagina. Aggiunto anche il blocco "ℹ️ Come funziona questa sezione" a ogni riquadro che ancora non lo aveva (profilo, streak, missioni, ingrediente segreto, le mie sfide, cestino, cose da fare, vetrina pubblica).
* Squadre regionali: "Team Emilia-Romagna" è stato assorbito in "Team Nord" (è comunque una regione del nord Italia, non aveva senso come squadra a parte). Chi aveva già scelto quella squadra passa in automatico a Team Nord alla prossima attivazione del plugin, senza perdere punti; aggiornati anche la mappa a squadre e il testo del percorso.
* Corretto: l'Ingrediente Segreto non aveva una vera sezione nei pannelli (solo un pulsante "+ Ingrediente" senza stato né link di gestione, a differenza di Sfida e Guida Stagionale). Aggiunto lo stato (prossimo in programma / ultimo rivelato) e il link "Gestisci Ingredienti Segreti" nella zona "Contenuti di gioco", sia nel Pannello di Controllo sia nella Plancia Generale classica sia nel Pannello Generale nuovo.

= 3.200.0 =
* Nuova guida "Come funziona il Percorso" in cima a «La Mia Sfoglia»: un blocco richiudibile (titolo cliccabile, come il messaggio di benvenuto) che spiega in un unico posto tutti i sistemi del percorso alle sfogline già registrate — come si guadagnano i punti, i sei livelli (Le Insegne della Sfoglia) con le rispettive soglie, la Sfida della Settimana e il voto della community, lo Streak del Matterello e gli scudi salva-streak, le Missioni Giornaliere, i Badge, le Squadre Regionali, l'Ingrediente Segreto del venerdì, la Guida Stagionale, Madrina & Allieva, la Vetrina pubblica e il Premio di Fine Anno. Non compare sulla Vetrina pubblica (solo per chi è già dentro il sito). Titolo e testo sono modificabili dal pannello ("Come funziona il Percorso", nuova sezione), sia dal Pannello di Controllo front-end sia dalla Plancia Generale in wp-admin.

= 3.199.0 =
* Genere sfoglina/sfoglino — prima parte (fondamenta): richiesto di distinguere il maschile ("sfoglino") dal femminile ("sfoglina") in tutto il sito invece di usare sempre il femminile. Aggiunto il campo "Sei... Sfoglina / Sfoglino" nel modulo di registrazione pubblica, salvato come dato dell'utente. Aggiunte due funzioni di base (`gs_genere`, `gs_decl`) che il resto del plugin userà per scrivere il termine giusto in base a chi legge. Aggiunto in "Cerca sfoglina e recupera file" un piccolo controllo (menu a tendina + Salva) per correggere il genere degli account già registrati, che non hanno questo dato. Questa versione mette solo le fondamenta: il messaggio all'amministrazione per le nuove richieste di iscrizione usa già il termine corretto; il resto dei testi del plugin (centinaia di punti: messaggi automatici, nomi di sezioni come "Le Sfogline", email) verrà aggiornato nelle prossime versioni, un pezzo alla volta, per non introdurre errori su un cambiamento così esteso.

= 3.198.0 =
* Palloncini: portata dentro al plugin la grafica e l'audio v2, messi a punto in un file di prova a parte con più giri di correzioni e infine approvati. Palloncino con luce/riflesso, nodo e nastro disegnato in SVG (parte sempre dal centro esatto, non più da un bordo come col vecchio trucco dei bordi CSS — segnalato due volte). Il nastro si stacca e cade ruotando quando il palloncino scoppia, invece di sparire di colpo. Scoppio su tre registri di altezza (acuto/medio/grave) invece di sembrare sempre uguale nei primi secondi. Urto tra due palloncini: ora un colpo secco e breve, diverso ogni volta, con una pausa minima più ampia tra un urto e l'altro e un controllo più frequente (ogni 50ms) così il suono resta aderente al momento esatto del contatto — non più il sibilo continuo segnalato prima. Ogni palloncino ha anche una dimensione leggermente diversa dagli altri. Integrazione verificata dal vero (clic → richiesta → messaggio di conferma → palloncini creati), nessun errore. Aeroplanino: nessun cambiamento aggiuntivo qui, la velocità rallentata (22s) era già stata portata in una versione precedente.

= 3.197.0 =
* Corretto un bug reale segnalato con screenshot: in "Le Sfogline" comparivano persone che non c'entrano nulla con la community (autori di articoli, Bruno Cingolani, account doppi). Causa: `gs_sez_sfogline()` — la funzione condivisa da 17 punti del plugin (Le Sfogline, Compleanni, Messaggi, Sondaggi, Giuria a Turno, Traguardi, ecc.) — considerava "sfoglina" chiunque non avesse uno stato di approvazione esplicito impostato, cioè qualsiasi utente del sito. Aggiunto un controllo in più: conta solo chi ha davvero il ruolo "Iscritto" assegnato alla registrazione come sfoglina (vedi registration.php). Corregge il problema ovunque questa lista viene usata, non solo nella pagina Le Sfogline.

= 3.196.1 =
* Corretto (segnalato subito da Ennio): i "Palloncini" della 3.196.0 erano stati aggiunti solo al Pannello di Controllo sul sito, non alla Plancia Generale dentro wp-admin — a differenza dell'Aeroplanino, che compare in entrambi i posti. Aggiunta anche lì, stessa zona, subito dopo l'Aeroplanino, con la sua voce nel sottomenu di wp-admin.

= 3.196.0 =
* Nuovo: "Palloncini — festeggiamenti in diretta", nel Pannello di Controllo subito sotto l'Aeroplanino. Tre pulsanti (Compleanno / Diploma / Festeggia): un clic e i palloncini si gonfiano, salgono ondeggiando e scoppiano con audio, sullo schermo di ogni sfoglina collegata in quel momento, ovunque stia navigando sul sito — a differenza dell'Aeroplanino (riservato a chi gestisce), questi li vede tutta la community insieme, com'era nella demo originale. Nato come demo standalone (2026-08-05_demo-effetto-palloncini.html), integrato ora nel plugin vero con lo stesso identico effetto già verificato. Anche visibilità/permessi per collaboratore, come tutte le altre sezioni del pannello.

= 3.195.1 =
* La striscia sottile in cima all'intestazione (data, Disciplinare, Appello al Governo, Scrivici) ora ha lo stesso colore crema del menu principale (#eee3dc), su richiesta di Ennio. Non era mai stata una regola del plugin: il suo grigio chiaro originale (#f4f4f4) veniva dal tema. Verificato dal vivo.

= 3.195.0 =
* Ampliato: "Cerca in tutto il sito" ora copre anche FAQ - Domande, Cassaforte del Sapere (solo voci già sbloccate), La Sfoglia che Insegna Se Stessa, Il Matterello Parlante, Le Letture, Adotta un Piatto in Via di Estinzione, Novità, Sondaggi e Dicono di Noi — non solo Ricettario e Lezioni Video come prima. Restano esclusi di proposito L'Esperto Risponde e tutte le sezioni di messaggistica privata (Messaggi, Conversazioni, Posta interna, Madrina & Allieva) e i dati personali di ogni sfoglina (Diario, Il Tuo Percorso, Il Tuo Anno in Accademia): non sono contenuto pubblico da far trovare a chiunque cerchi.

= 3.194.0 =
* Aggiornato: descrizione del Corso Privato ("formazione esclusiva, a qualsiasi livello, la Maestra Rina è disponibile"), estesa in tutte le pagine collegate (Corsi, Diventa Supporter, Calendario Corsi, FAQ). Il Corso Privato ha ora un programma completo dedicato sulla pagina Corsi, come gli altri quattro percorsi.

= 3.193.0 =
* Aggiornato: le FAQ tengono conto di tutto quello aggiunto di recente — nuove voci su Rina Online, sul Corso Privato e sulla differenza tra "Rina Online" e "Corsi Online". Corrette due risposte diventate sbagliate: "i corsi sono solo in presenza" (ora esiste anche Rina Online) e la descrizione del Registro Ufficiale (ora sono due rami, non solo la Laurea in Sfoglia).
* Corretto: una risposta preesistente diceva che esiste "il pagamento online tramite il portale" — non è mai stato vero per questo progetto (i pagamenti si registrano sempre a mano), tolto il riferimento.

= 3.192.0 =
* Nuovo: "Rina Online" — due incontri in videochiamata con la Maestra Rina Poletti (lezione + prova pratica valutata), con Attestato di Frequenza Intermedia e iscrizione al Registro degli Amatori. Aggiunto come quinto percorso nelle pagine Corsi, Diventa Supporter e Calendario Corsi, con una voce nelle FAQ e un cenno nel Registro Ufficiale (il pathway al Registro degli Amatori ora nomina anche questa via, oltre al Calendario Corsi).

= 3.191.0 =
* Corretto: stessa correzione della 3.190.0, estesa alla sezione delle 4 schede-corsi in cima alla pagina "Calendario Corsi" (`#gscards-corsi`) — anche lì lo sfondo veniva svuotato dalla stessa regola troppo generica.

= 3.190.0 =
* Corretto: la regola CSS che svuota gli sfondi dei contenitori-struttura del tema sulle pagine del plugin (`body.gs-page section` e simili) era troppo generica — su una pagina standalone che contiene anche uno shortcode del plugin (es. `[gs_calendario]` nella pagina "Corsi"), cancellava anche gli sfondi veri delle sezioni della pagina stessa (il riquadro finale bordeaux della pagina Corsi era diventato invisibile). Ora esclude esplicitamente il contenuto di `#gs-corsi-page` e `#gs-supporter-page`.

= 3.189.0 =
* Nuovo: occhietto mostra/nascondi password su tutti i campi password del sito (accesso, recupero e cambio password, registrazione) — un click rivela il testo digitato, un altro lo nasconde di nuovo.
* Nuovo: il modulo di registrazione mostra ora l'importo della quota associativa (29,00 €, configurabile dalla Plancia) e i dati per il bonifico (stesso IBAN già impostato per il Calendario Corsi), con causale «CONTRIBUTO ASSOCIATIVO».
* Rinominato: "Area Professionale" diventa "Corsi Online" (e "Area Corsi Online" nelle intestazioni), in tutte le pagine collegate — tab della sfoglina, pannelli generali (Plancia e Pannello di Controllo), Cruscotto, Pianificazione dell'Anno, FAQ, Registro Ufficiale, email di assegnazione diploma. Aggiornata anche la spiegazione mostrata a chi non ha ancora un corso attivo, per chiarire che è pensata per chi non può frequentare l'aula in presenza.

= 3.188.0 =
* Nuovo: il Calendario Corsi rilascia ora un attestato per tre livelli reali — 🌱 Corso Base, 🌿 Corso Intermedio, 🎖️ Corso Professionale — invece del solo Corso Professionale. Il pulsante "Assegna attestato" e l'attestato stampabile (titolo e testo) si adattano automaticamente al livello del corso.
* Nuovo: il Registro Ufficiale è ora diviso in due rami pubblici, secondo "I Corsi di Formazione" della pagina Diventa Supporter — Registro degli Amatori (Attestato di Corso Base o Corso Intermedio) e Registro dei Professionisti (Attestato di Corso Professionale, oppure Laurea in Sfoglia dal percorso privato di Area Professionale). Non è legato al gaming/punti/badge.
* Nuovo: pulsante "📋 Vedi tutte le sfogline registrate" — elenco completo con i dati anagrafici, senza dover cercare un nome. Aggiunto in "Cerca sfoglina e recupera file", nella Plancia Generale classica e nel Pannello Generale nuovo.
* Corretto: cestinare (o ripristinare) una vetrina Artigiani ora aggiorna coerentemente lo stato "artigiano" dell'account collegato — se era stato reso "artigiano" per errore (es. email di una sfoglina già esistente), tornare al cestino lo rimette una sfoglina normale, con messaggio esplicito invece del solo ricaricamento silenzioso.

= 3.187.2 =
* Corretto (segnalato con screenshot): il "Pannello Generale" doveva coprire tutto lo schermo, ma la colonna nera del menu di WordPress restava visibile a sinistra invece di sparire. Ora quella colonna viene tolta davvero dalla pagina (non solo nascosta sotto), e lo spazio torna al pannello: schermo pieno vero, come richiesto. Resta solo la barra nera in alto (con "Bacheca" e il nome utente), per non perdere l'uscita da questa vista.
* "🚀 Pannello Generale" è ora anche una voce di PRIMO LIVELLO nel menu di WordPress (icona a parte, in cima, subito dopo "Bacheca"), non più raggiungibile solo aprendo prima "Gaming Sfogline". Resta comunque anche lì, come scorciatoia.
* "🚀 Pannello Generale" è ora disponibile anche ai collaboratori a cui è stato dato il permesso di gestione del pannello (prima lo vedevano e potevano aprirlo solo gli Amministratori veri). Corretto anche un blocco collegato: un collaboratore autorizzato che provava ad aprire wp-admin per raggiungerlo veniva comunque rimandato fuori dall'interruttore "blocca l'accesso a wp-admin" (pensato per tenere fuori le sfogline, non i collaboratori nominati) — ora quell'interruttore lo riconosce e lo lascia passare, esattamente come già succede per gli Amministratori veri.

= 3.187.1 =
* Spostate "🚀 Pannello Generale" e "🍝 Artigiani della Pasta" in cima al menu Gaming Sfogline (subito dopo "Plancia Generale"), invece che in fondo all'elenco insieme a tutte le altre voci — richiesta esplicita, sono le due sezioni più usate in questo momento.

= 3.187.0 =
* Nuovo: "Gli Artigiani della Pasta" — vetrine pubbliche per i partner paganti (pastifici, botteghe, piccoli produttori), a pagamento tramite bonifico. Ogni partner riceve un account e autogestisce la propria vetrina dal proprio pannello ("La Mia Vetrina"): logo, racconto, fino a 6 foto, un video YouTube di presentazione, indirizzo e email di contatto. Ogni modifica torna "in attesa di approvazione" prima di tornare pubblica, come già per le Biografie della Vetrina. La grafica pubblica è volutamente diversa dal resto del gaming (fasce ondulate dorate, card panna, bottoni a pillola, nello stile delle pagine "La Nostra Sede" e "Giallo Sfoglia" del sito), con modulo di contatto vero e mappa "Dove trovarci". La vetrina è visibile nella sezione pubblica SOLO se approvata E con abbonamento attivo: alla scadenza si nasconde da sola, senza bisogno di un intervento manuale, e riappare al bonifico successivo. Il titolare gestisce tutto dalla Plancia Generale (creazione account, approvazione, registrazione bonifici con nuova scadenza, cestino). Aggiunta anche la condivisione social diretta (WhatsApp, Facebook, copia link) sia sulla vetrina pubblica sia nel pannello del partner, con anteprima Open Graph (titolo, logo/foto) quando il link viene condiviso.
* Nuovo: "🚀 Pannello Generale" — nuova modalità di navigazione della Plancia, pensata per uno schermo 16:9 o un portatile 16" a tutto schermo, senza scorrimento infinito. Si affianca alla Plancia classica (non la sostituisce): rotaia di 8 gruppi sempre visibile, nastro di scorciatoie, ricerca rapida ⌘K su tutte le 62 sezioni, e una "Torre di controllo" fissa a destra con tutti gli allarmi in ordine di urgenza (dalla stessa fonte dati già usata dal pannello sul sito) e gli ultimi messaggi della Posta interna, con "segna come letta" e "rispondi al volo" senza cambiare pagina. Ogni sezione richiama la stessa identica funzione già usata dalla Plancia classica: nessuna logica duplicata, stessi permessi per i collaboratori già assegnati in "Visibilità sezioni e permessi".
* Aggiunto il conteggio delle vetrine Artigiani in attesa di approvazione al riepilogo generale (`gs_riepilogo_dati()`), usato sia dal pannello sul sito sia dal nuovo Pannello Generale.

= 3.186.0 =
* Corretto: chi ha il ruolo Editore (es. Rina Poletti, Bruno Cingolani, registrati per scrivere articoli) veniva rimandato al pannello del gaming invece di poter entrare nella bacheca di WordPress. Causa: l'interruttore "blocca l'accesso a wp-admin" (pensato per tenere le sfogline fuori dalla bacheca) lasciava passare solo gli Amministratori veri, non gli Editori — un controllo troppo stretto per l'uso reale. Ora lascia passare anche chi ha il ruolo Editore (o superiore); le sfogline (ruolo Iscritto) restano escluse come prima, il comportamento per loro non cambia.

= 3.185.3 =
* Corretto un terzo caso della stessa famiglia di problemi, segnalato con screenshot: scorrendo la pagina, il tema passa l'intestazione in una modalità più compatta "aggrappata" allo scroll (una barra del tema, non un elemento nostro), e in quella modalità il pulsante "Nascondi intestazione" non riusciva più a farla sparire davvero — restava visibile e tornava a coprire il pannello linguette. Causa: in quella modalità il tema tiene sotto controllo la stessa barra durante lo scroll, e il solo spostamento (transform) usato finora non bastava più a batterlo. Aggiunta una seconda sicurezza indipendente dallo scroll (opacità e visibilità), così l'intestazione sparisce davvero in ogni modalità. Verificato dal vivo: cliccando esattamente dove prima c'era il menu, ora si raggiunge il contenuto della pagina sottostante.

= 3.185.2 =
* Corretto un'altra sovrapposizione dello stesso pulsante "Nascondi intestazione", segnalata con screenshot da chi gestisce il portale: aprendo il pannello delle linguette a destra (quello con "Pannello di Controllo", "Messaggi" ecc.), il pulsante ci finiva sopra invece di restarne fuori. Ora, quando quel pannello è aperto, si sposta alla sua sinistra — stessa tecnica già usata per il lanciatore verde "Menu" dello stesso pannello. Verificato dal vivo.

= 3.185.1 =
* Corretto (segnalato con screenshot): il pulsante "Nascondi intestazione" (3.185.0) copriva il link "Scrivici" della barra sottile in cima, su Safari, Edge e altri browser desktop. Causa: stava fisso in un angolo che in alcuni browser coincide con quello del link. Ora sta sempre SUBITO SOTTO l'intestazione (misurata dal vero, la stessa misura già usata per lo spazio del contenuto), non può più sovrapporsi a nessuna riga del menu; risale in cima solo quando il menu è davvero nascosto. Verificato dal vivo su desktop, sia loggato che in navigazione anonima (le due situazioni non cambiano questa parte di pagina, solo la barra nera di WordPress da loggati, già gestita a parte).
* Corretto un problema reale su smartphone, segnalato di nuovo dopo un primo intervento parziale del 2026-08-04: l'intestazione arrivava a 407px di altezza (più di metà schermo su un telefono medio) prima di mostrare qualsiasi contenuto. Il taglio del 2026-08-04 aveva tolto solo due margini da 22px; il grosso dello spazio (il logo stampato largo quanto tutto lo schermo, più tre cuscinetti del tema sopra/sotto di lui) era rimasto intatto. Ridotti tutti: il logo resta perfettamente leggibile a 190px, l'intestazione scende sotto i 270px. Verificato dal vivo su schermo 375×812.

= 3.185.0 =
* Nuovo pulsante "Nascondi intestazione", su tutto il sito (non solo le pagine del gaming): un cerchietto oro sempre visibile in alto a destra che fa scorrere via il menu (logo compreso) per vedere più contenuto della pagina, e lo fa ricomparire con lo stesso click. La scelta si ricorda passando da una pagina all'altra durante la visita. Verificato dal vivo: mostra/nascondi funzionano, lo spazio sopra al contenuto si libera e si ripristina correttamente, il pulsante resta sempre cliccabile.

= 3.184.2 =
* Corretto (screenshot dal committente): il crema di tutto il sito (3.184.1) si vedeva solo in una sottile striscia, il resto della pagina restava bianco. Causa: il contenitore .middle_inner del tema (racchiude quasi tutto il contenuto sotto il menu) ha un suo sfondo bianco pieno che copriva il crema sottostante. Svuotato anche questo. Verificato dal vivo: ora il crema si vede in tutta la pagina.

= 3.184.1 =
* Sfondo crema (#f5ecd7, lo stesso già usato nelle pagine del gaming) esteso a tutto il sito, su richiesta diretta: prima riguardava solo le pagine del plugin, ora vale anche sulla home e sulle pagine del tema. Verificato dal vivo su più pagine: il colore si vede nei margini, il resto (schede, testo, pulsanti) resta come sempre.

= 3.184.0 =
* Corretto un bug reale segnalato con screenshot: nella sezione Sondaggi, "Proponi una tua idea" rispondeva sempre "Invio troppo rapido, riprova.", indipendentemente da quanto tempo si impiegava a scrivere. Causa: i campi nascosti anti-spam venivano stampati fuori dal modulo (mancava un passaggio tecnico presente in ogni altro modulo del plugin), quindi il timestamp non arrivava mai al server, che di conseguenza bloccava sempre l'invio. Corretto allineando Sondaggi allo stesso schema già usato ovunque altrove nel plugin. Verificato che non ci sono altri punti con lo stesso problema.

= 3.183.5 =
* Corretto il nome sbagliato "Giampaolo Chiossi" in "Gianpaolo Chiossi" (nome vero, segnalato dal committente) nella pagina "L'Accademia della Sfoglia": titolo e testo alternativo della foto. Il testo sta nel contenuto della pagina, non nel plugin — corretto dal vero con lo stesso metodo già usato per il link privacy del banner cookie, così non serve modificare la pagina da WordPress. Aggiornato di conseguenza anche il selettore della foto in bianco e nero (3.183.4), che ora funziona con entrambe le grafie.

= 3.183.4 =
* Foto di Giampaolo Chiossi in bianco e nero nella pagina "L'Accademia della Sfoglia" (sezione con Rina Poletti, Giuseppe Govoni e Giampaolo Chiossi). Selezionata tramite il testo alternativo dell'immagine, unico su quella pagina: le altre due foto restano a colori. Verificato dal vivo.

= 3.183.3 =
* Le linguette dentro il pannello laterale destro passano dal verde all'oro (#bd8a13), lo stesso della linguetta sul bordo e del pulsante "LA MIA SFOGLIA": ora tutto il menu laterale è coordinato su un unico colore.

= 3.183.2 =
* Le linguette dentro il pannello laterale destro tornano verdi, come mostrato nell'anteprima. Erano rimaste rosse: nel file c'erano tre regole diverse sullo stesso elemento (terracotta, verde e rossa) e vinceva l'ultima, scritta a luglio quando il pannello era in prova sul rosso. Corretta quella, non le altre.

= 3.183.1 =
* Corretto un limite della 3.183.0: il contenitore dei social del footer NON viene stampato sempre dal tema — scaricando due volte la stessa pagina, in un caso c'era e nell'altro no. La versione precedente si limitava a riempirlo, quindi su quelle pagine i collegamenti sparivano del tutto. Ora, se manca, il plugin lo crea da solo e lo mette sopra la riga del copyright. Verificato simulando la pagina senza il blocco del tema: quattro collegamenti creati e posizionati correttamente.
* Tolti i tre pulsanti rossi di condivisione (TWITTER / PINTEREST / FACEBOOK) che comparivano dentro le pagine. Sono un elemento del tema inserito nel contenuto con il costruttore di pagine: qui vengono nascosti, non tolti dal contenuto (per eliminarli davvero vanno rimossi dal costruttore).

= 3.183.0 =
* Collegamenti social in fondo alle pagine: erano DUE (Facebook e Instagram) e puntavano entrambi a "#", cioè a nulla — non erano mai stati configurati nelle opzioni del tema, quindi cliccarli non portava da nessuna parte. Ora sono quattro, con gli indirizzi veri: pagina Facebook di Rina Poletti, Instagram, gruppo "Casa Poletti" e il secondo gruppo Facebook.
* Gli stessi collegamenti sono ora centrati nella pagina e disegnati come pastiglie oro (#bd8a13, lo stesso del pulsante "LA MIA SFOGLIA" e della linguetta laterale), con le icone disegnate direttamente nel plugin invece del carattere-icona del tema. Verificato dal vivo: centratura esatta, quattro indirizzi corretti.
* La linguetta sul bordo destro (quella che apre il pannello) passa dal rosso mattone allo stesso oro del pulsante di accesso. Le linguette dentro il pannello restano rosse.
* Nota per il futuro: se questi indirizzi verranno impostati nelle opzioni del tema, il plugin continuerà comunque a sovrascriverli — vanno cambiati nel plugin (gaming.js, elenco gsSocialVoci).

= 3.182.0 =
* Pannelli laterali (indice a sinistra, linguette a destra): sfondo dal gradiente quasi bianco al crema chiaro del gaming (#f5ecd7, scelta C dell'anteprima). I due pannelli restano uguali fra loro.
* Le tendine che si aprono dalle voci del menu ora hanno lo stesso colore della barra da cui scendono (#eee3dc, scelta A). Prima erano bianco sporco (#FAF7F2) impostato nel Personalizzatore, fuori dal plugin: la regola è stata portata dentro il plugin con un selettore più forte. Le voci restano verdi con il rosso sull'attiva.
* Pulsante "LA MIA SFOGLIA" / "ACCEDI": da sfumatura oro con testo scuro a oro pieno #bd8a13 con testo bianco (scelta D), più netto ora che la barra del menu è chiara. Al passaggio del mouse l'oro si scurisce invece di far tornare scuro il testo.
* La scheda "La Mia Sfoglia" resta invariata: valutate diverse alternative con il committente, l'attuale è stata confermata come la migliore.

= 3.181.0 =
* I due menu laterali (l'indice a sinistra e le linguette a destra) partono ora da sotto il menu principale, come il contenuto centrale: prima partivano dalla cima dello schermo e la loro parte alta finiva nascosta dietro il menu. L'altezza a cui partire è la stessa misurata dal vero per il contenuto centrale, quindi resta corretta da sola anche se l'altezza del menu cambia (schermo stretto, barra di WordPress da collegati). Tolto anche lo spazio vuoto di 54px che avevano in cima: serviva a scansare la barra di WordPress quando partivano dall'alto, ora è superfluo.
* Colore di menu e footer schiarito su richiesta: stessa tonalità del logo, dieci punti di luminosità in più (#eee3dc, scelta C dell'anteprima).

= 3.180.4 =
* Colore del menu e del footer aggiornato al vero colore del logo attuale (#dec8b9), campionato dal file immagine caricato oggi 2026-08-04. Nel tentativo precedente era stato preso per errore il colore di un logo più vecchio ancora presente sul server (un medaglione bronzo, non più in uso): corretto qui.

= 3.180.3 =
* Stesso beige del menu principale (#dec7af) applicato anche al footer, su richiesta diretta. Testo del footer invariato (verde scuro, contrasto già buono).

= 3.180.2 =
* Corretto: nella 3.180.1 il beige era finito su TUTTA l'intestazione (data, logo, menu), non solo sulla barra del menu principale come si è sempre fatto per i cambi di colore. Ora il beige (#dec7af) resta solo sulla barra del menu; data e logo restano come sempre. Scurito anche il testo delle voci di menu (era bianco, pensato per lo sfondo dorato scuro di prima: sul beige chiaro sarebbe stato illeggibile).

= 3.180.1 =
* Sfondo del menu cambiato da bianco a beige (#dec7af, dal logo Accademia della Sfoglia), su richiesta diretta.

= 3.180.0 =
* RISOLTO alla radice il problema segnalato per giorni ("la pagina scorre sotto il menu", "si vede passare attraverso"), su tutte le pagine. Causa vera, misurata dal vero: il menu non è un blocco pieno, è fatto a strisce con delle ZONE TRASPARENTI — due fasce vuote (tra la barra della data e il logo, e tra il logo e la barra dorata) e le fasce ai lati della barra dorata. Attraverso quei punti si vedeva scorrere il contenuto della pagina. Ecco perché nei controlli tecnici risultava sempre tutto a posto: il menu ERA fermo, era il contenuto a essere visibile attraverso i suoi buchi. Ora il menu ha uno sfondo pieno. Il bianco scelto è esattamente il colore che già si vedeva in quelle zone stando in cima alla pagina, quindi l'aspetto non cambia: cambia solo che scorrendo il contenuto ci passa dietro senza vedersi. Verificato con 100 controlli sulla superficie del menu a quattro diverse posizioni di scorrimento: prima 4 punti lasciavano passare il contenuto, ora nessuno.
* Ricaricando una pagina a mano (F5, Cmd+R, pulsante del browser) si torna sempre in cima, su tutte le pagine: prima era il browser a rimettersi da solo dove si era. Resta invece invariato il comportamento utile dei pannelli del gaming, dove dopo un'azione (elimina, salva, ripristina) si rimane sul punto in cui si stava lavorando — i due casi sono distinti, non si disturbano a vicenda.

= 3.179.5 =
* Ritirato il tentativo della 3.179.4 (causava uno scatto continuo del menu, segnalato subito): il menu torna a "position: fixed" semplice, senza transform ad ogni scroll.
* Nuovo, richiesto solo per il Pannello Generale (shortcode [gs_pannello]): ricaricando la pagina si torna sempre in cima, mai alla posizione precedente. Tutte le altre pagine del gaming continuano a mantenere la posizione di scorrimento dopo un'azione, come prima — la nuova regola vale solo qui.

= 3.179.4 =
* Il menu, con "position: fixed", scorreva via lo stesso sia in Opera sia in Safari — due browser diversi, stesso problema, mai riprodotto nei controlli fatti da remoto (dove "fixed" funzionava correttamente). Causa più probabile: un blocco pubblicità o un'estensione che disattiva di proposito gli elementi "fixed" a schermo intero, trattandoli come barre fastidiose. Cambiato approccio: il menu ora resta in "position: absolute" (non riconosciuta come bersaglio da questi strumenti) e viene tenuto incollato in cima allo schermo dal JavaScript, che lo riposiziona ad ogni scroll — stesso risultato visivo, ottenuto senza mai usare la proprietà che veniva bloccata.

= 3.179.3 =
* Segnalato che il contenuto scorrendo "scavalcava" (copriva) il menu invece di restarci sempre sotto. Portato lo z-index del menu al valore massimo possibile, così vince sempre su qualunque altro elemento della pagina, presente o futuro — non serve più andare a caccia di quale elemento avesse la priorità più alta. Importante: durante il lavoro su questa versione avevo provato anche a spostare il menu altrove nella pagina come ulteriore protezione, ma quel tentativo rompeva la mia stessa regola CSS — scartato subito, non è mai stato spedito.

= 3.179.2 =
* Corretto (screenshot dal committente, da collegato): il contenuto partiva troppo in alto e finiva nascosto sotto il menu. Lo spazio riservato in cima contava solo l'altezza del menu, non anche la barra nera di WordPress sopra di lui — mancavano quei pixel in più. Ora lo script misura entrambe le barre insieme e posiziona il menu esattamente sotto quella di WordPress (mai più nascosto sotto di lei). Verificato sia da collegati sia da visitatore normale.

= 3.179.1 =
* Corretto un errore della 3.179.0: il menu non restava davvero fisso in cima. Avevo tolto la vecchia regola che lo bloccava in cima, pensando che il tema lo facesse fisso di suo — controllato ora nei fogli di stile del tema: non è così, nessun file lo imposta fisso. Nelle versioni precedenti restava fisso solo grazie al "CSS aggiuntivo" del Personalizzatore di WordPress, fuori dal plugin. Ora il plugin lo impone da solo in modo esplicito, senza dipendere da altre impostazioni del sito.

= 3.179.0 =
* Richiesta diretta del committente: il menu torna fisso in cima allo schermo (come prima della 3.178.0), ma con lo spazio riservato sotto misurato dal vero via JavaScript invece che con un numero fisso — così resta sempre corretto, qualunque cosa cambi in futuro l'altezza del menu (con o senza barra di WordPress, mobile o desktop). Verificato desktop, mobile e da collegati.
* Trovata la vera causa dell'accavallamento tra il riquadro "Riconoscimento Professione Sfoglina e Sfoglino" e il titolo "Ultime Notizie dal Comune di Parma", segnalato di nuovo il 2026-08-04 e non riproducibile nei controlli precedenti: quella colonna aveva l'opzione "sticky" del page builder del tema attiva, che la faceva scorrere agganciata allo schermo invece di restare ferma — quando si "staccava" atterrava sopra il titolo sottostante. Il problema si vedeva solo con questo riquadro (il testo più lungo lo rendeva evidente) e solo su Safari nei test, per questo era sfuggito prima. Disattivato lo scorrimento agganciato: la colonna ora resta sempre al suo posto.

= 3.178.0 =
* Richiesta diretta del committente: il menu non resta più fisso in cima allo schermo durante lo scroll, scorre via con il resto della pagina. Risolve alla radice tutte le sovrapposizioni segnalate il 2026-08-04 (dipendevano dal calcolo dello spazio riservato sotto il menu fisso, sbagliato in alcune combinazioni) — senza menu fisso non serve più nessun calcolo. Tolto anche lo spazio da 274px che il tema riservava apposta per il vecchio menu fisso. Verificato desktop e mobile.

= 3.177.0 =
* Trovata la causa vera della sovrapposizione, mai riproducibile prima: da collegati a WordPress (la barra nera in alto, sempre presente per chi gestisce il sito) l'intestazione cresce di altri 32px ma lo spazio riservato sotto non seguiva — 12px di sovrapposizione reale, solo per chi è loggato. Da visitatore normale (come nei controlli fatti finora) non si vedeva mai. Corretto lo spazio riservato anche in quel caso specifico.

= 3.176.3 =
* Trovato l'ultimo pezzo dell'accavallamento: le date ("13 AGOSTO 2025" ecc.) sono un elemento figlio della scheda, ancorato ad essa dal tema — impostando la scheda a "position: static" (versione precedente) quell'ancoraggio si rompeva e la data "volava" fino ai bordi della pagina. Ora la scheda resta un punto di riferimento valido per i suoi elementi interni, senza più partecipare al vecchio posizionamento di Isotope. Verificato su tutte le schede della sezione.

= 3.176.2 =
* Tolto del tutto il margine negativo del tema sopra i titoli "Ultime Notizie..." (era -28px, pensato per un divisore che non c'entra più con la griglia nuova): ora il distacco dalle schede sotto è sempre positivo, 15px, senza eccezioni.

= 3.176.1 =
* Estesa la correzione della 3.176.0 a un'altra sezione con lo stesso problema ("Video Stories", classe diversa non coperta prima). Aggiunto anche lo spegnimento diretto dello script Isotope (non solo l'override CSS del risultato), per eliminare pure lo spreco di calcolo durante lo scroll.

= 3.176.0 =
* Trovata la vera causa dell'accavallamento e del "congelamento" su "Ultime Notizie dal Comune di Parma" e sezioni simili: il tema posiziona quelle schede con un calcolo JavaScript (libreria Isotope.js) invece che con un layout normale, fragile per definizione. Disattivato quel calcolo e sostituito con una griglia CSS a 2 colonne (1 su mobile), sempre corretta perché la calcola il browser, non uno script. Verificato desktop e mobile, nessuna sovrapposizione.

= 3.175.1 =
* Spente tutte le animazioni di ingresso del tema (i blocchi che scivolano/comparivano scorrendo la pagina): potevano restare per un istante in una posizione diversa da quella finale e sovrapporsi a titoli e schede sotto, segnalato sulla home. Ora ogni elemento compare subito nella posizione definitiva.

= 3.175.0 =
* Centratura del riquadro "notizia in evidenza" nella home, soluzione definitiva: invece di inseguire il margine sbilanciato del tema con un numero fisso (impreciso a seconda della larghezza dello schermo, causa dei problemi nelle versioni precedenti), ora il contenitore viene centrato per davvero. Verificato su quattro larghezze diverse (375, 900, 1264, 1280px), sempre corretto.

= 3.174.3 =
* Home su smartphone: il riquadro "notizia in evidenza" sconfinava fuori schermo a destra (lo spostamento di 60px per correggere il bug del tema serviva solo su schermi larghi, dove il bug esiste davvero — su mobile la riga era già centrata da sola). Ora lo spostamento si applica solo da tablet in su.

= 3.174.2 =
* Aggiunto lo spazio mancante tra il menu e il titolo su tutte le pagine normali (non solo la home): il titolo si sovrapponeva alla barra di 25px, ora c'è uno spazio visibile come nel resto del sito.

= 3.174.1 =
* Spaziatura eccessiva in cima a tutte le pagine, non solo la home: 1) il margine di 45px vicino al riquadro "notizia in evidenza" era scritto per errore in modo da applicarsi ovunque, ora limitato alla home dove serve; 2) i due margini da 22px sopra e sotto il logo, sommati, rendevano la testata troppo alta su schermi stretti — ridotti a 8px solo su mobile, restano 22px su desktop dove non davano problemi.

= 3.174.0 =
* Home del sito: corretti insieme centratura e spaziatura del riquadro "notizia in evidenza" (15 occorrenze), con un metodo diverso e più solido dal tentativo ritirato in 3.173.1. Verificato anche su schermo da smartphone.

= 3.173.1 =
* Ritirata la correzione della 3.173.0 (centratura riquadro "notizia in evidenza"): peggiorava la situazione invece di risolverla. Tornati al comportamento della 3.172.1 su questo punto, ci riprovo con un metodo più sicuro.

= 3.173.0 =
* Home del sito: corretto lo spostamento a destra dei riquadri "notizia in evidenza" (bug del tema, margine sbilanciato del loro contenitore) — ora sono centrati come il resto della pagina, su tutte le 15 occorrenze della home.

= 3.172.1 =
* Logo staccato con lo stesso margine anche dalla barra in alto (data/Disciplinare/Appello/Scrivici), non solo dal menu principale sotto. Verificato anche su smartphone.

= 3.172.0 =
* Scelte B e D dall'anteprima "testo e logo" del 4 agosto: scritte del menu bianche (più leggibili sull'oro) e logo staccato dalla barra con un margine.

= 3.171.3 =
* Trovata davvero la riga bianca "vecchia": non era la stessa che avevo creato io, ma un effetto a bisello (grigio-bianco-grigio) che il tema disegna su un elemento diverso (.header_bot_inner_cont), sempre rimasto acceso perché non l'avevo mai toccato. Spenta esplicitamente.

= 3.171.2 =
* Trovata la causa della riga bianca superiore "persa": aveva un'ombreggiatura solo sotto la barra, non sopra, quindi si confondeva col bianco della pagina intorno al logo mentre quella sotto risaltava. Aggiunta la stessa ombreggiatura anche sopra: ora le due righe sono identiche.

= 3.171.1 =
* Riga bianca sopra e sotto la barra del menu, a tutta larghezza, resa esplicita e voluta invece di rincorrerla come un difetto.

= 3.171.0 =
* Sfondo del menu del sito: dal bordeaux all'oro/grano (colore indicato dal committente), scritte scure per leggibilità.

= 3.170.2 =
* Nessuna modifica al CSS: verificato dal vivo che la 3.170.1 è già corretta (nessuna riga bianca). Solo cambio di versione per forzare la cache del server a servire il file fresco, come già successo in un caso simile.

= 3.170.1 =
* Tolte le righe bianche sopra/sotto il menu del sito, aggiunta un'ombreggiatura tutt'intorno alla barra (oltre a quella già presente ai lati).

= 3.170.0 =
* Ripartiti dalla 3.168.0 (annullata la 3.169.0 con il verde salvia su tutto il plugin, su richiesta esplicita). Unica modifica: sfondo della barra del menu del sito in bordeaux invece che marrone, righe bianche sottili sopra e sotto. Nient'altro è stato toccato.

= 3.168.0 =
* Barra del menu del sito: sfondo marrone uguale ai riquadri scuri delle FAQ/Cose da Fare, scritte dei link bianche. Il pulsante dorato "La Mia Sfoglia" resta com'era, per restare distinto dalle altre voci.

= 3.167.1 =
* Corretto il rosso doppio comparso nelle FAQ (segnalato subito dopo la 3.167.0): il sito colora di rosso mattone ogni riquadro `.gs-box` per regola globale, e il nuovo riquadro scuro della FAQ non era tra le eccezioni — si sommavano i due rossi. Aggiunta la stessa eccezione già usata per gallerie/badge/bacheca: ora il riquadro esterno resta trasparente, l'unico colore è quello del riquadro scuro con le domande.

= 3.167.0 =
* FAQ - Domande: le domande, raggruppate per argomento, ora usano lo stesso riquadro scuro bordeaux di "Ultimi Traguardi" (approvato via anteprima) al posto delle schede bianche di prima — righe separate da un bordo sottile, testo chiaro. La ricerca e l'apertura delle risposte funzionano come prima, cambia solo l'aspetto.

= 3.166.0 =
* Nuova pagina pubblica "Ultimi Traguardi": vetrina di tutta l'Accademia con gli ultimi diplomi conseguiti e badge sbloccati, i più recenti in cima (shortcode già pronto da fine luglio, mai collegato a una pagina — recuperato e agganciato). Compare nel menu (gruppo "L'Accademia") e nella linguetta laterale.

= 3.165.6 =
* La struttura del menu cercava due voci con un titolo che sul sito non esiste ("Accademia della Sfoglia" sotto L'Accademia, "FAQ - Domande" sotto Corsi): non erano voci mancanti, solo nomi diversi da quelli veri. "FAQ - Domande" corretto in "FAQ" (il titolo reale già presente); "Accademia della Sfoglia" tolto dall'elenco, perché il gruppo "L'Accademia" punta già lui stesso alla pagina corrispondente — non serviva una voce duplicata.

= 3.165.5 =
* Trovata la causa vera del mancato riconoscimento di "L'Esperto Risponde": il titolo era salvato con l'entità HTML letterale "&#8217;" al posto dell'apostrofo (un incidente precedente, non di oggi) — invisibile guardando il sito, perché WordPress mostra comunque l'apostrofo giusto. Un tentativo di correggerlo a mano nell'editor dei menu l'ha spostata per sbaglio come sotto-voce di un'altra. Nuovo pulsante "🔧 Ripara 'L'Esperto Risponde'" nel pannello: la riporta di primo livello e corregge il titolo in un solo clic, qualunque delle due cose sia ancora sbagliata.

= 3.165.4 =
* Indagine in corso sul "Rimuovi i gruppi" che non trova nulla pur essendo i gruppi presenti (la Diagnostica separata conferma titoli corretti byte per byte, quindi non è un problema di apostrofi o caratteri nascosti): ora, quando "Rimuovi i gruppi" non trova corrispondenze, mostra nello stesso momento — sullo stesso menu, stessa richiesta — l'elenco di cosa ha visto e il nome/ID esatto del menu esaminato. Serve a escludere che si stia controllando un menu con un pulsante e ripulendone un altro con l'altro (possibile se esistono due menu con lo stesso nome).

= 3.165.3 =
* "Rimuovi i gruppi da questo menu" non trova nulla su nessun menu, pur essendo i gruppi visibili sul sito (verificato dal vivo il 03/08/2026): nuovo strumento "🔍 Mostra le voci di primo livello" per vedere esattamente cosa vede il codice in un menu (titolo esatto, tipo, lunghezza in byte) e capire dove sta la mancata corrispondenza, invece di continuare a indovinare.

= 3.165.2 =
* "Eventi" mancava nel gruppo "Contenuti" del menu (c'era già solo in "Community"): ora è in entrambi, come già succede per "Iscrizione". Va aggiunta a mano con "Applica questa struttura" sul menu interessato (l'automatico resta disattivato dalla 3.165.1).

= 3.165.1 =
* Correzione urgente: la versione 3.165.0 aveva riacceso "Applica la struttura completa del menu" in automatico su TUTTI i menu del sito — ma il tema (Newspaper) ne mostra due contemporaneamente (la barra sottile in alto e il menu principale sotto il logo), così i gruppi L'Accademia/Corsi/Community/Contenuti sono comparsi duplicati su entrambe le barre, compresa quella in alto che doveva restare con le sue poche voci originali. Da questa versione la struttura completa NON parte più da sola: resta solo a mano, un menu alla volta (come già indicato a parole, ma non rispettato dal codice). Nuovo strumento "🧹 Rimuovi i gruppi da questo menu" nel pannello "Applica la struttura del menu" per ripulire un menu su cui sono finiti per errore — toglie solo le voci di menu, mai le pagine a cui puntano.

= 3.165.0 =
* Verifica email alla registrazione: appena una sfoglina si iscrive riceve anche un'email con un link di conferma. È solo informativa: non blocca né rallenta l'attivazione dell'account, che resta sempre decisa a mano dalla segreteria in base alla quota associativa — serve solo a intercettare email scritte male o inventate. Il pannello "Richieste di Iscrizione" mostra ora una colonna "Email verificata".
* Nuova sezione pubblica "Novità": annunci brevi di chi gestisce il portale (nuove sezioni, cambiamenti, avvisi), visibili anche a chi non ha ancora effettuato l'accesso. Dal pannello si crea, modifica ed elimina ogni annuncio (con cestino), con una spunta facoltativa per avvisare subito tutte le sfogline via aeroplanino.

= 3.164.0 =
* Nuovo riquadro "Il tuo account" in fondo a "La Mia Sfoglia": ogni sfoglina può ora cambiare da sola la propria password e la propria email (prima serviva sempre passare dalla segreteria).
* Diritto GDPR di accesso: "Scarica i tuoi dati" genera un file di testo con profilo, punti, livello, squadra e stato dell'account.
* Diritto GDPR alla cancellazione: "Richiedi la cancellazione dell'account" invia un avviso alla segreteria, che la gestisce a mano — mai una cancellazione immediata o automatica.

= 3.163.2 =
* "Eventi" aggiunta al gruppo "Community" nella struttura del menu (mancava, sarebbe rimasta isolata dentro "Contenuti").

= 3.163.1 =
* "Applica la struttura del menu" e "Correggi voci trovate" ora partono anche da soli, una volta sola, su tutti i menu del sito — non serve più premere i pulsanti a mano. Il pannello mostra un riepilogo di cosa è stato fatto ("Ultima esecuzione automatica"). I due pulsanti restano comunque disponibili per rilanciare a mano in futuro.

= 3.163.0 =
* "Applica la struttura del menu": nuovo pulsante "Correggi voci trovate" per tre errori individuati con un controllo del menu (2026-08-02): la voce "miao" (in realtà la pagina di Rina Poletti, solo l'etichetta era sbagliata); la voce "FAQ" che puntava a una pagina vuota e abbandonata invece delle vere FAQ; la voce "Dicono di noi" (minuscolo) che puntava alla vecchia pagina su sede/B&B, ora trasformata per puntare a "La Nostra Sede". Si può rilanciare senza rischi: quello già corretto non viene ritoccato.

= 3.162.1 =
* Pulsante "Accedi" (dorato) sempre visibile nel menu del sito, inserito come vera ultima voce: si centra da solo insieme al resto del menu, senza bisogno di calcoli manuali di posizione. Per chi è già collegata diventa "La Mia Sfoglia".

= 3.162.0 =
* Nuova pagina "Accedi" (pannello di login del plugin) al posto del generico wp-login.php di WordPress: username/email + password, con link a "Iscrizione" per chi non ha ancora un account.
* Nuova funzione "Password dimenticata": la sfoglina riceve un'email con un link per scegliere una nuova password, senza bisogno del pannello di WordPress. Il messaggio è sempre lo stesso, esista o no l'account, per non rivelare quali email sono registrate.
* Sicurezza: dopo 5 tentativi con password sbagliata dallo stesso indirizzo, l'accesso resta bloccato per 15 minuti (si sblocca da solo); stesse protezioni antispam di ogni altro modulo pubblico (honeypot + trappola del tempo + limite invii).
* Correzione: la linguetta "Sondaggi" nel menu laterale a destra, aggiunta e testata ma non ancora inclusa nello zip precedente (3.161.1) — ora c'è davvero.
* Ogni punto del sito che rimandava al login generico di WordPress (sezioni riservate, email di approvazione iscrizione, "Iscriviti per commentare") ora rimanda alla nuova pagina "Accedi".

= 3.161.1 =
* Sondaggi: nuova casella facoltativa "Avvisa subito le sfogline" nel modulo di creazione — manda l'aeroplanino a tutte le sfogline approvate, con link diretto alla pagina Sondaggi. Non era collegato prima: senza spuntarla, il sondaggio resta silenzioso come finora.

= 3.161.0 =
* Nuovo modulo Sondaggi: la segreteria crea una domanda con alcune proposte di partenza, le sfogline votano una proposta a testa e, se il sondaggio lo permette, possono proporne di nuove (si aggiungono a quelle esistenti, votabili da tutte).
* Ogni sondaggio può avere risultati pubblici (visibili anche alle sfogline nella pagina del sito) o riservati (visibili solo dal Pannello Generale) — il pannello mostra sempre il conteggio completo, indipendentemente dall'impostazione.
* Sondaggi gestibile da entrambi i pannelli generali (Pannello di Controllo sul sito e Plancia in WordPress), con cestino recuperabile come tutto il resto del progetto.

= 3.160.0 =
* Modulo di Registrazione (Iscrizione): aggiunto il consenso obbligatorio alla Privacy Policy, con link vero alla pagina — prima raccoglieva nome, email, data di nascita e password senza alcun consenso.
* Modulo Newsletter: il testo del consenso ora contiene un link vero alla Privacy Policy, non solo una frase generica.
* Nuovo helper gs_privacy_policy_url(): legge la pagina Privacy Policy impostata in Impostazioni > Privacy di WordPress, così il link resta corretto anche se lo slug della pagina cambia.

= 3.159.0 =
* Corretto automaticamente, via JS caricato su tutto il sito, il link "Privacy Policy" del banner cookie (CookieYes) che puntava ancora a rinapoletti.it invece che alla pagina privacy del sito.

= 3.158.0 =
- **FAQ: unite le vecchie domande sui corsi dal vero con quelle del plugin.**
  46 domande pratiche (pagamenti, disdette, allergie, accessibilità,
  gruppi, bambini, animali, parcheggio...) recuperate dalla vecchia pagina
  statica "FAQ" del sito e integrate nel set di base, in una nuova
  categoria "Corsi in presenza — domande pratiche". La vecchia pagina non
  è stata toccata: resta raggiungibile finché non deciderai cosa farne.
- **Nuove domande per gli aggiornamenti recenti**: una categoria "Le
  Letture dei Grandi Protagonisti della Cucina" (5 domande: chi pubblica,
  chi può commentare, l'account leggero "solo per commentare", la
  moderazione), tre domande su token/rimborsi in "L'Esperto Risponde", e
  una su come togliere dalla lista le prenotazioni concluse in
  "Calendario Corsi". Il set di base sale da 70 a 123 domande.

= 3.157.0 =
- **Nuovo strumento "Applica la struttura del menu"** nel Pannello di
  Controllo e nella Plancia: un pulsante che riorganizza il menu scelto
  del sito secondo la struttura concordata con Ennio (bacheca del
  01/08/2026) — crea le voci-contenitore "L'Accademia", "Corsi",
  "Community" e "Contenuti", e sposta sotto di loro le voci già esistenti
  nel menu. Ogni voce non gestita direttamente dal plugin viene trovata
  cercando il suo titolo tra quelle già presenti in un menu qualsiasi del
  sito — mai creando un collegamento verso un indirizzo indovinato. Se una
  voce necessaria (come "L'Esperto Risponde") vive in un altro menu (es.
  quello in alto), ne crea una copia nel menu scelto senza mai toccare
  l'originale. Si può rilanciare più volte senza creare doppioni.
- Aggiunte alle pagine spuntabili nel menu: **"La Mia Sfoglia"** (mostra
  l'invito ad accedere a chi non è collegata — serve da punto di accesso,
  visto che la barra di WordPress in alto a destra la vede solo chi
  gestisce il sito) e **"Registro Ufficiale degli Allievi"**, prima
  irraggiungibili da qualunque menu.

= 3.156.0 =
- **SEO/GEO delle pagine pubbliche del plugin**: aggiunti titolo/descrizione
  (meta description), Open Graph e dati strutturati Schema.org per le
  pagine davvero raggiungibili senza accesso — FAQ (FAQPage, il formato che
  Google e le IA leggono meglio per citare direttamente una risposta),
  Registro Ufficiale, Badge e Traguardi, Vetrina pubblica (una scheda
  Persona quando si apre il profilo di una sfoglina precisa), Galleria
  delle Sfogline e Calendario Corsi (ogni corso come "Course", con data e
  prezzo quando impostati). Il Ricettario di Famiglia resta escluso:
  richiede sempre il login, quindi nessun motore di ricerca o assistente
  IA potrebbe comunque leggerne il contenuto. Se sul sito è già attivo un
  plugin SEO dedicato (Yoast, RankMath...) non viene aggiunta una seconda
  meta description doppia: restano solo i dati strutturati, che si
  sommano senza creare conflitti.

= 3.155.1 =
- **Le Letture: titolare e collaboratori non riuscivano a rispondere**
  (segnalato da Ennio l'1/08/2026) — il controllo di chi può commentare
  riconosceva solo un vero Amministratore WordPress, non chi ha il
  permesso da collaboratore (`gs_manage_gaming`): corretto, ora chi
  gestisce il portale può sempre commentare, come già poteva pubblicare
  ed eliminare le letture. Controllato lo stesso tipo di errore in tutto
  il resto del plugin: era un caso isolato di questa nuova sezione, non
  un problema diffuso.
- **Le Letture: Invio manda la risposta** (come su WhatsApp), Maiuscolo+Invio
  va a capo — prima funzionava solo il pulsante "Rispondi".
- Corretto il testo del riquadro "Pagine pubbliche nel menu del sito": non
  nominava ancora "Le Letture dei Grandi Protagonisti della Cucina" tra le
  pagine già gestibili (la spunta per metterla nel menu c'era comunque).

= 3.155.0 =
- **Nuovo pannello "Moderazione di tutte le chat"**: un unico elenco, dal
  messaggio più recente, che raccoglie ciò che viene scritto in
  Conversazioni, Messaggi privati, Posta interna, Aiuto e Suggerimenti,
  Calendario Corsi, Auguri di compleanno e i commenti delle Letture —
  filtrabile per sistema e con ricerca per testo o autore. Ogni riga ha un
  pulsante "Elimina questo messaggio" (sempre recuperabile: cestino
  dedicato per i sistemi ad array, cestino di WordPress per gli auguri) e
  un link diretto al pannello di origine per vedere il contesto completo.
  Utilizzabile sia dal titolare sia dai collaboratori con accesso al
  pannello.

= 3.154.0 =
- **Nuova sezione pubblica "Le Letture dei Grandi Protagonisti della
  Cucina"** — giornalisti e collaboratori pubblicano letture/racconti,
  leggibili da chiunque senza account, in chiaro nel menu principale del
  sito (assegnabile dal pannello "Pagine pubbliche nei menu"). Sotto ogni
  lettura, una chat pubblica a bolle stile WhatsApp: per commentare serve
  essere sfoglina, oppure iscriversi con un nuovo account leggero "solo per
  commentare" (senza quota associativa, senza accesso al resto del gaming).
- **Moderazione degli account "solo per commentare"**: dal pannello
  generale si può bloccare a tempo indeterminato o sospendere fino a una
  data un account lettore; sia il titolare sia i collaboratori con accesso
  al pannello possono farlo.
- **Protezione antispam estesa a tutte le chat del progetto** (honeypot +
  trappola del tempo + limite orario): Conversazioni, Messaggi privati,
  Aiuto e Suggerimenti (richiesta e risposte), Calendario ("Messaggi con
  l'Accademia"), i commenti del Matterello Parlante e i nuovi commenti
  delle Letture. Non tocca le composizioni riservate a chi gestisce il
  portale (già protette dal permesso stesso).

= 3.153.1 =
- **Calendario Corsi: la sfoglina può togliere dalla sua lista le
  prenotazioni concluse** — corso effettuato, annullate, assente o
  rimborsate — con un pulsante "✕ Togli dalla mia lista". Non tocca mai le
  prenotazioni ancora attive (in attesa, confermate su un corso futuro, in
  lista d'attesa). Sempre cestino recuperabile: nel pannello gestore del
  Calendario c'è ora un cestino dedicato con "Ripristina".

= 3.153.0 =
- **Nuovo: Il Cruscotto della Verità** (Pannello di Controllo e Plancia,
  gruppo "Strumenti tecnici") — per ogni sezione del gaming che ha un
  proprio archivio (Sfide, Ricettario, Diario, Cassaforte del Sapere,
  Matterello Parlante, Regia del Gaming e altre 20+), mostra quante voci
  ci sono in totale e da quanti giorni non arriva niente di nuovo, con una
  fascia colorata (Viva, Tiepida, Dormiente, Mai usata) — le più
  silenziose in cima. Non copre ancora le meccaniche senza un proprio
  archivio con data (Streak, Mappa dei Territori, Percorso a Staffetta,
  Badge assegnati): il pannello lo dichiara esplicitamente.

= 3.152.2 =
- **Corretto: in "Visibilità delle sezioni e permessi", deselezionare tutte
  le sfogline a cui una sezione era nascosta non le toglieva davvero**.
  Causa: quando svuoti del tutto un elenco a selezione multipla, il
  browser non manda alcun dato per quella sezione — il salvataggio
  interpretava "nessun dato arrivato" come "non toccare quanto già
  salvato" invece di "svuota". Ora il salvataggio guarda sempre l'elenco
  completo delle sezioni, non solo quelle arrivate nella richiesta, quindi
  una selezione svuotata viene svuotata davvero. Stessa correzione anche
  per i collaboratori abilitati a un pannello.

= 3.152.1 =
- **Bolle whatsapp anche nelle ultime due conversazioni che ne erano
  rimaste fuori**: "Posta interna" (risposte tra collaboratori) e
  "Messaggi con l'Accademia" dentro "Le mie prenotazioni" del Calendario
  Corsi. Prima erano un riquadro piatto uguale per tutti; ora, come nelle
  Conversazioni private, i propri messaggi sono a destra in verde, quelli
  dell'altra persona a sinistra. Le altre sezioni (Conversazioni,
  Messaggi privati alle sfogline, Aiuto e Suggerimenti) usavano già questo
  stile.

= 3.152.0 =
- **Nuovo: Regia del Gaming** (Pannello di Controllo e Plancia, gruppo
  "Sfide e giochi") — uno spazio dove chi collabora (es. Rina Poletti,
  Bruno Cingolani) propone idee per il gaming (nuove sfide, percorsi,
  contenuti…) prima ancora che esistano, e il titolare le approva, le
  segna realizzate (con un link facoltativo a cosa ne è nato) o le
  archivia. Diverso dalla Pianificazione dell'Anno (quella organizza le
  date di cose già create) e dall'Area Professionale (corso individuale
  per una sola sfoglina): qui il destinatario è il gaming nel suo insieme.
  Le proposte in attesa lampeggiano in rosso; cestino recuperabile.

= 3.151.6 =
- **Indice laterale (sinistra): selezione al passaggio del mouse più
  marcata** su schede e voci del sottomenu — bordo colorato con lo stesso
  colore del gruppo, ombra più forte, leggero spostamento. Prima il
  cambiamento era troppo leggero per notarlo bene.

= 3.151.5 =
- **"Richieste di Iscrizione" e "Abbonamenti delle sfogline" ora nel gruppo
  "Pagamenti e contributi"** dell'indice (sinistra) e della Plancia,
  insieme a Token — nessun pannello spostato o eliminato, solo
  ricategorizzati nel gruppo giusto.

= 3.151.4 =
- **Corretto: il sottomenu a tendina dell'indice laterale, una volta aperto
  la prima volta, non si chiudeva più** — né cliccando su un link, né
  cliccando altrove. Causa: la misura dell'altezza del sottomenu (aggiunta
  il 2026-07-30 per evitare che l'ultima voce finisse fuori schermo)
  lasciava uno stile "aperto" scritto direttamente sull'elemento, più forte
  della regola che lo doveva richiudere. Risolto, e la correzione ripulisce
  da sola anche chi aveva già il problema dopo l'aggiornamento.

= 3.151.3 =
- **Menu marrone: nuovo tentativo di correzione dello spazio vuoto** tra il
  gruppo Messaggi e il resto delle linguette — sostituito lo spazio interno
  del pannello (che su alcuni browser/tema poteva non bastare) con un
  margine su ogni singola voce, più robusto contro le regole del tema.

= 3.151.2 =
- **Pannello Pagamenti → Token: si possono assegnare token anche senza un
  bonifico** (regalo, premio, correzione…). L'importo del contributo
  associativo resta facoltativo: senza importo, basta scrivere il motivo
  dell'assegnazione, che resta nello storico della sfoglina.

= 3.151.1 =
- **Rimborso automatico dei token: da 14 a 7 giorni** senza risposta
  (resta comunque modificabile dal pannello Pagamenti → Token).
- **Una domanda rimborsata ora scompare dalla conversazione** (sia lato
  sfoglina che lato maestro): se la sfoglina vuole rifare la domanda, ne
  invia una nuova. I dati non si cancellano per sempre — restano nello
  storico token, consultabile dal pannello.

= 3.151.0 =
- **"L'Esperto Risponde" diventa privato e a token.** Le domande a Rina
  Poletti Risponde, Bruno Cingolani Risponde (e ogni altro canale futuro)
  non sono più una bacheca pubblica: ogni domanda apre una conversazione
  privata (solo la sfoglina e il maestro la vedono), con un campo Oggetto
  obbligatorio — se nel testo si scrivono più domande, conta solo quella
  dell'Oggetto.
- **Nuovo pannello "Pagamenti → Token"** (Pannello di Controllo e Plancia):
  saldo e storico token di ogni sfoglina, accredito manuale dopo un
  contributo associativo (bonifico, causale «CONTRIBUTO ASSOCIATIVO»),
  valore di un token in euro e giorni prima del rimborso automatico —
  entrambi modificabili.
- **Costo in token per domanda modificabile per singolo maestro** dal
  pannello «L'Esperto Risponde» (di base uguale per tutti, un token a
  domanda).
- **Rimborso del token se la domanda resta senza risposta**: automatico
  dopo i giorni impostati, oppure subito a mano con il pulsante "Rimborsa
  token" nella conversazione.
- **Spiegazione dei "contributi associativi"** (versamenti volontari oltre
  la quota associativa) anche nel pannello Abbonamenti delle sfogline, con
  la stessa causale da usare per il bonifico.
- Linguaggio: mai "costo"/"prezzo" verso le sfogline, sempre "contributo
  associativo"/"token".

= 3.150.2 =
- **Indice laterale (sinistra): ogni voce del sottomenu è ora una "nuvoletta"
  bianca ombreggiata a sé**, non più un link piatto con il pallino
  dell'elenco (che restava visibile su alcuni browser).
- **Corretto: l'ultima voce di un sottomenu lungo finiva troppo in basso**,
  fuori dalla parte comoda dello schermo, quando aperto da una scheda in
  fondo all'elenco. Ora la posizione tiene conto di quante voci ci sono
  davvero, prima di scegliere dove aprirsi.

= 3.150.1 =
- **Corretto: spazio vuoto tra il gruppo "Messaggi" e il resto del Menu**
  (le linguette sotto restavano molto distanti). Ora si accostano tutte
  in cima, senza spazi.
- **La casella del gruppo "Messaggi" lampeggia in rosso quando c'è un non
  letto** (stesso lampeggio già usato per segreteria/abbonamenti scaduti),
  invece di restare "muta" con solo il pallino del numero.

= 3.150.0 =
- **Menu marrone (destra): gruppo "Messaggi" in cima**, con lo stesso stile
  a bolla della messaggistica delle Conversazioni private (invece delle
  linguette piatte uguali a tutte le altre). Contiene la messaggeria
  generale e i canali de "L'Esperto Risponde" (es. Rina Poletti Risponde,
  Bruno Cingolani Risponde), col numero di non letti in un pallino rosso
  invece che tra parentesi nel testo.
- **Creati i due canali storici "Rina Poletti Risponde" e "Bruno Cingolani
  Risponde"** in "L'Esperto Risponde" (solo se non c'è già nessun canale
  configurato — non tocca eventuali canali esistenti). L'esperto di ciascun
  canale va assegnato a mano dal pannello.

= 3.149.1 =
- **Corretto: testo delle schede dell'indice tagliato a metà parola** (i
  bottoni erano rimasti su una sola riga senza andare a capo, e il tema
  forzava il maiuscolo). Ora il testo va a capo dentro il bottone e resta
  nel case originale.

= 3.149.0 =
- **Indice laterale "☰ Sezioni": il sottomenu di un gruppo ora si apre a
  tendina sulla destra della scheda**, invece che sotto — non sposta più
  le altre voci dell'elenco, il problema segnalato da Ennio ("quando
  clicco su un link sparisce tutto"). Restano le schede bianche con
  pallino colorato e freccetta già approvate, con lo stesso stile del
  testo (leggibile, non corsivo) anche nei link del sottomenu.
- **Le schede in colore avorio (fascia titolo compresa) ora hanno un'ombra
  più marcata**, per staccarsi meglio dallo sfondo della pagina.

= 3.148.2 =
- **Colore delle schede: da giallo/oro antico a crema neutro** (indicato da
  Ennio con uno screenshot di riferimento). Cambiata la variabile alla
  radice (`--gs-avorio`/`--gs-avorio-band`): l'effetto si vede su ogni
  scheda del sito, non solo nel pannello — fascia del titolo, corpo del
  riquadro, e gli altri elementi che riusano lo stesso colore.

= 3.148.1 =
- **Corretto: testo dei sotto-elementi dell'indice laterale poco leggibile**
  (in corsivo, colore troppo chiaro). Ora testo diritto, più scuro, più
  grande.

= 3.148.0 =
- **Lo stile "lamarketer" (schede bianche, ombra morbida) esteso a tutte le
  sezioni del Pannello di Controllo**: ogni titolo di scheda ora ha un
  pallino colorato che indica il suo gruppo (Messaggistica, Corsi, Sfide,
  Contenuti, Iscrizioni/sfogline, Impostazioni, Strumenti — stesso colore
  già usato nell'indice laterale), l'ombra delle schede è più morbida.
  Provato prima in Artifact con contenuti veri e approvato. Colori dei
  titoli e dei pulsanti invariati; la Plancia in WordPress (già in questo
  stile dal v3.127.0, con le proprie intestazioni colorate) non è stata
  toccata.
- **Sfondo dei due pannelli laterali** (indice "☰ Sezioni" e Menu) con la
  stessa sfumatura crema del pannello generale, invece del bianco piatto —
  applicata a entrambi i pannelli gemelli per coerenza.

= 3.147.0 =
- **Indice laterale a due livelli, con colori per gruppo.** Il Pannello di
  Controllo (e la Plancia gemella in WordPress) avevano 52 pannelli tutti
  sullo stesso piano nell'indice "☰ Sezioni". Ora sono raggruppati in 7
  macro-aree — Messaggistica e comunicazione, Organizzazione corsi, Sfide
  e giochi, Contenuti e racconti, Iscrizioni e gestione delle sfogline,
  Impostazioni sito e pagine, Strumenti tecnici — ognuna riconoscibile da
  un pallino colorato; clicca il nome del gruppo per aprirlo e vedere le
  sue voci. Nessun pannello è stato spostato nella pagina: solo l'indice è
  cambiato.
- **Nuovo stile per l'indice**: schede bianche, ombra morbida, come già
  approvato per la Plancia (v3.127.0) — provato in anteprima prima di
  applicarlo.

= 3.146.1 =
- **Corretto: l'indice laterale spostato in basso in 3.145.0 restava
  nascosto.** Il sito ha un pulsantino fisso per riaprire il banner dei
  cookie in basso a sinistra, con priorità (z-index) molto più alta:
  copriva del tutto il pulsante verde "☰ Sezioni". Spostato più in alto
  (170px dal fondo invece di 90px), abbastanza da non toccarlo mai.

= 3.146.0 =
- **Nuovo: Premi in sconto sui corsi.** In "Premi per Traguardo" ora puoi
  collegare a un badge, oltre a un video o un messaggio, una percentuale di
  sconto sui corsi di Rina Poletti. Ogni sfoglina ha un livello corso su cui
  accumula (parte da Base): i badge vinti sommano lo sconto fino a un tetto
  del 100%. Nel pannello Iscrizioni (Calendario Corsi), quando iscrivi al
  corso giusto una sfoglina con sconto accumulato, lo vedi evidenziato con
  la quota già calcolata: un pulsante "Sconto applicato / corso fatto"
  azzera lo sconto e la fa passare al livello successivo (Base → Avanzato →
  Professionale). Lo sconto non speso entro il 24 dicembre si azzera in
  automatico. La sfoglina vede il proprio sconto accumulato e lo storico
  di tutti i premi ricevuti nel suo pannello personale.
- Nuovo campo "Livello per lo sconto badge" nella creazione/modifica di
  ogni data di corso, per collegarla al sistema sconti.

= 3.145.0 =
- **Messaggi dalla segreteria: anche il messaggio ricevuto è ora una bolla**
  colorata come nelle Conversazioni private (prima solo le risposte lo
  erano, il messaggio originale era testo semplice). Vale sia nella
  casella della sfoglina sia nel pannello "Messaggi di ogni sfoglina".
- **Indice laterale del Pannello di Controllo spostato in basso**: il
  pulsante verde "☰ Sezioni" (bordo sinistro) prima era centrato a metà
  altezza dello schermo, ora è in basso.

= 3.144.0 =
- **Richieste di aiuto e suggerimenti: colori a bolla come in tutto il
  sito.** Il messaggio della sfoglina e le risposte ora si distinguono con
  gli stessi colori usati in Conversazioni e Messaggi privati (crema chi
  riceve, verde chi scrive), sia dal lato di chi gestisce sia dal lato
  della sfoglina, che ora vede anche le risposte ricevute nel suo pannello
  (prima non le vedeva affatto).
- **L'aeroplanino porta dritto al messaggio giusto.** Quando rispondi a una
  richiesta di aiuto, l'avviso volante non porta più genericamente a "La
  Mia Sfoglia": apre direttamente quel messaggio, già espanso.
- **La sfoglina può rispondere a sua volta.** Sotto ogni sua richiesta ora
  c'è un modulo per continuare lo scambio: la risposta torna visibile a chi
  gestisce (email + messaggio interno) e la richiesta torna tra quelle da
  rivedere.
- **Lampeggio rosso per le risposte non lette.** In "La Mia Sfoglia", il
  titolo del riquadro "Aiuto e Suggerimenti" e il messaggio interessato
  lampeggiano in rosso quando è arrivata una risposta che la sfoglina non
  ha ancora visto; smette di lampeggiare dopo averlo aperto.

= 3.143.0 =
- **Le tue notifiche (titolare e collaboratori)**: prima chi gestisce il
  portale riceveva SEMPRE e SOLO l'email, senza possibilità di scelta. Ora
  in "Notifiche per sfoglina" c'è una tabella dedicata anche per titolare
  e collaboratori: puoi scegliere email e/o messaggio interno del
  progetto, categoria per categoria, come già per le sfogline.
- **Richieste di aiuto e suggerimenti: le risposte inviate restano
  visibili** sotto ogni richiesta. Prima partivano solo via email/messaggio
  interno e sparivano dalla vista — se un collaboratore rispondeva, gli
  altri (incluso il titolare) non lo sapevano.
- **Corretto: nel Ricettario delle Famiglie, cliccando su una ricetta già
  approvata non si vedeva il suo contenuto** (ingredienti, procedimento,
  storia, foto) — solo nelle richieste "in attesa" funzionava. Ora il
  contenuto si vede in entrambe le sezioni.
- **Ricettario delle Famiglie: nuovo pulsante "📕 Salva per il libro" anche
  sulle ricette già approvate** (prima era disponibile solo per quelle
  in attesa).
- **Ricettario delle Famiglie e Ricette per il Libro: nuovo modulo per
  rispondere direttamente alla sfoglina** che ha condiviso la ricetta,
  sotto ogni voce — il messaggio avvia una conversazione privata con lei,
  già pronta con la persona giusta selezionata.

= 3.142.1 =
- **Corretto: testo invisibile nel riquadro "Domande senza risposta"**
  (introdotto in 3.141.0) — il testo della domanda e il pulsante per
  rispondere erano bianco su bianco, illeggibili.

= 3.142.0 =
- **Bacheca di riepilogo (le caselle in cima al pannello): ora verdi a
  riposo**, lampeggiano in rosso solo quando c'è davvero qualcosa da
  visionare. Prima erano sempre rosso mattone, allarme o no.

= 3.141.2 =
- **"Sfide — regole di partecipazione" spostato subito sotto "Sfida
  attiva"**, in entrambi i pannelli generali.

= 3.141.1 =
- **Corretto: "Correggi punti di una sfoglina"** — prima bisognava scrivere
  a memoria username o email esatti, e se non si ricordavano non si
  trovava nessuna sfoglina. Ora è un elenco a tendina con tutte le
  sfogline: basta scegliere il nome.

= 3.141.0 =
- **"Cerca sfoglina e recupera i suoi file": ora mostra tutto**. Dopo la
  ricerca, oltre ai lavori (che prima erano solo un titolo scritto, non
  cliccabile) trovi anche il link alla sua Vetrina pubblica e la sua
  biografia (anche se non ancora approvata). Ogni lavoro e sfida si apre
  cliccandoci sopra, mostrando testo e foto — non solo il nome.
- **Corretto: "Domande senza risposta" nella Bacheca di riepilogo portava
  a un box che non mostrava nessuna domanda** (solo la gestione dei
  canali). Ora le domande in sospeso di tutti i canali compaiono subito
  lì, con il link diretto per aprire il canale giusto e rispondere.

= 3.140.2 =
- **Menu marrone laterale**: "Pannello di Controllo" ora è sempre la prima
  linguetta, per chi gestisce il portale.

= 3.140.1 =
- **Pianificazione dell'Anno: corretta la scritta illeggibile** sui
  quadratini corti (foto + "Corso del..." non ci stavano affiancati e il
  testo sbordava sul bianco della linea del tempo, bianco su bianco). Ora
  il testo ha un piccolo fondo scuro che lo rende leggibile ovunque finisca.

= 3.140.0 =
- **Sfida attiva**: il titolo della sfida in corso ora lampeggia in verde,
  nella Plancia e nel Pannello di Controllo.
- **Classifica generale (top 10)**: oro/argento/bronzo per il podio, in
  entrambi i pannelli.
- **Pagine pubbliche nel menu del sito**: ora è una matrice pagina × menu.
  Puoi far comparire lo stesso link in più menu insieme, e togliere una
  spunta per farlo sparire solo da un menu senza toccare gli altri.
- **Nuova pagina per maestri e collaboratori: "Sfogline in gara — controllo
  dei giudici"**. Tutte le sfoglie della sfida attiva in un'unica
  videata, con ricerca in cima. Ogni scheda è rossa finché va controllata,
  diventa verde con "✅ Segnala controllata" (si può sempre rimettere da
  controllare). Dentro ogni scheda, la correzione punti è già pronta con
  il nome della sfoglina.
- **Vetrina pubblica del profilo**: riga verde per chi è pubblica, rossa
  per chi è bloccata, nell'elenco per singola sfoglina.
- **"Cerca sfoglina e recupera i suoi file"** spostato in cima al pannello,
  subito sotto l'intestazione, in entrambi i pannelli generali.

= 3.139.0 =
- **Messaggi privati alle sfogline: ora si può rispondere.** Ogni sfoglina
  risponde direttamente sotto il messaggio ricevuto — le risposte compaiono
  a bolle come nelle Conversazioni. Il titolo del messaggio lampeggia in
  rosso 🔴 quando una sfoglina attende ancora una tua risposta.
- **Abbonamenti delle sfogline colorati**: riga verde per chi è attivo,
  rossa lampeggiante per chi è scaduto (si aggiorna subito anche cambiando
  il menu a tendina, prima ancora di salvare).
- **Bacheca di riepilogo**: due nuove schede — "Messaggi in attesa di
  risposta" e "Abbonamenti scaduti" — che lampeggiano quando c'è qualcosa
  da controllare, con lo stesso cartellino delle altre schede sulla
  Plancia. Il lampeggio delle schede in allerta ora cambia colore in modo
  netto (rosso acceso), per farsi notare meglio.

= 3.138.0 =
- **"Richieste di Iscrizione" colorate**: ogni riga in attesa è rossa;
  quando approvi, diventa verde per un attimo prima di sparire dall'elenco
  (non è più in attesa, per definizione).

= 3.137.1 =
- **"Riepilogo pagamenti per cliente" colorato**: rosso chi non ha ancora
  saldato per intero quanto dovuto sulle sue prenotazioni attive, verde
  chi ha saldato tutto. Chi non deve nulla (prenotazioni annullate o solo
  in lista d'attesa) resta senza colore.

= 3.137.0 =
- **Lista d'attesa colorata**: ogni riga è rossa finché la persona resta in
  attesa. Dopo aver inviato la proposta di data, compare il pulsante
  "✅ Ha accettato" — cliccandolo la riga diventa verde e la prenotazione
  passa subito su quella data (diventa confermata; l'eventuale acconto
  resta valido). Se nel frattempo la data si è riempita, l'accettazione
  si blocca con un avviso invece di sovrapporre due persone sullo stesso
  posto.

= 3.136.1 =
- **"Corsi in calendario e partecipanti": il corso normale ora ha un
  colore** — lo stesso arancio usato per "Calendario Corsi" nella
  Pianificazione dell'Anno, così il colpo d'occhio è coerente tra le due
  sezioni. Esame pratico, Confronto dal vero, Corso Professionale e
  Bloccato restano con i loro colori dedicati, invariati.

= 3.136.0 =
- **Il Matterello Parlante: chat testuale sotto ogni registrazione**. Chi
  ascolta può ora commentare a testo — non è possibile allegare un audio
  al commento, solo alla registrazione originale.
- **Moderazione automatica dei commenti**: in Impostazioni > Moderazione
  chat si può impostare un elenco di parole/frasi da segnalare. Un
  commento che ne contiene una resta comunque pubblicato (per evitare
  falsi allarmi) ma appare con ⚠️ e arriva subito un avviso nella posta
  interna della redazione. Da lì (o direttamente sotto il commento) chi
  gestisce può eliminarlo — sempre recuperabile da un cestino dedicato,
  mai una cancellazione definitiva.
- **Registra un ricordo o un consiglio**: ora sia registrando dal
  microfono sia caricando un file si può riascoltare l'audio scelto
  prima di inviarlo, e rimuoverlo con un pulsante per rifare la scelta.

= 3.135.1 =
- **Pianificazione dell'Anno: corretta la foto deformata** nel cerchietto
  del quadratino (appariva schiacciata/ovale invece che tonda). Il tema del
  sito applicava le sue regole sulle immagini sopra le nostre; ora il
  cerchietto è "blindato" a 60×60 sempre, qualunque cosa dica il tema.

= 3.135.0 =
- **Missioni di oggi: ogni riga è ora cliccabile** e porta dritta alla
  pagina dove quella missione si compie davvero — "Vota 3 sfoglie della
  community" e "Commenta la sfida" portano a Le Sfide, "Aggiungi una voce
  al Diario" porta al Diario, "Condividi un consiglio" porta a Consigli.
  Prima erano solo testo, senza modo di capire dove andare.

= 3.134.0 =
- **Vetrina pubblica: foto molto più grande** (160px, prima 64px), per
  attirare l'occhio di chi la scopre — resta piccola solo nell'uso privato
  ("La Mia Sfoglia").
- **La Mia Biografia: anteprima della foto più grande** (120px, prima
  64px) mentre la controlli/cambi.
- **Pianificazione dell'Anno**: il cerchietto della foto sul quadratino è
  ora il doppio (60px, prima 30px). Dentro al quadratino compare solo la
  data (es. "Corso del 26/09"), non più il titolo intero: il colore dice
  già di che tipo si tratta e il titolo vero resta scritto per esteso a
  sinistra, nell'etichetta della riga.

= 3.133.1 =
- Cerchietto della foto sulla Pianificazione dell'Anno più grande (18px →
  30px), si vedeva troppo poco.

= 3.133.0 =
- **Trovata la vera causa della foto mancante sulla Pianificazione
  dell'Anno**: non era un problema di dati, ma della foto stessa — quella
  di Ennio ha lo sfondo nero, e il quadratino è una barra corta e larga.
  Riempiendo tutta la barra con la foto (ritagliata per coprire tutto lo
  spazio), il ritaglio mostrava quasi solo lo sfondo nero della foto, non
  il viso: sembrava un blocco vuoto. Cambiato approccio: ora la foto di
  chi ha creato l'evento è un piccolo cerchietto accanto al titolo, non
  più lo sfondo di tutto il quadratino — niente più ritagli imprevedibili,
  funziona con qualunque foto.

= 3.132.1 =
- Il correttivo della foto sulla Pianificazione dell'Anno ora ripara
  l'autore anche quando punta a un account cancellato nel frattempo (non
  solo quando manca del tutto).

= 3.132.0 =
- **Corretto (segnalato da Ennio): foto mancante sul quadratino di
  Pianificazione dell'Anno.** Alcuni corsi creati prima che esistesse
  questa funzione non avevano nessun autore collegato — restava un
  quadratino grigio, senza nome né foto nel tooltip. Ora basta **aprire la
  scheda del corso e salvarla** (anche senza cambiare nulla): l'autore
  mancante viene assegnato a chi la salva, e da quel momento la foto
  compare. Non tocca mai un autore già presente, quindi non "ruba" la
  paternità di un corso creato da qualcun altro quando lo modifichi tu.

= 3.131.1 =
- **Foto sul quadratino di Pianificazione dell'Anno, più visibile**: il
  velo scuro sopra la foto di chi ha creato l'evento era troppo forte
  (45% di nero) e su blocchi piccoli la foto risultava quasi invisibile,
  sembrava un blocco grigio piatto. Velo più leggero (22%) e contorno del
  testo rinforzato per restare comunque leggibile.

= 3.131.0 =
- **Mappa dell'Italia in "Classifica a Squadre", ricolorata**: prima era
  sempre colorata a tavolozza fissa (uguale per tutte, senza legame con le
  squadre). Ora l'Italia parte bianca: si colorano solo le regioni della
  squadra in testa alla classifica, nel colore di quella squadra — finché
  nessuna squadra ha punti resta tutta bianca. Aggiunta una piccola
  legenda sotto la mappa ("In testa: Team Nord") per capire subito a cosa
  corrisponde il colore.

= 3.130.0 =
- **Corretto (segnalato da Rina Poletti): il link "Rispondi qui" nelle
  email delle Conversazioni private** portava alla pagina "Messaggi" in
  generale, non alla conversazione precisa — bisognava cercarsela. Ora il
  link apre direttamente quella conversazione, già aperta, senza dover
  cercare nulla.
- **Conversazioni private: colore dei messaggi inviati più marcato**, per
  distinguerli meglio a colpo d'occhio da quelli ricevuti (che restano
  nel beige di sempre).

= 3.129.1 =
- **Corretto**: la disposizione a due colonne introdotta in 3.129.0 rompeva
  alcune schede con contenuto largo (es. "Pianificazione dell'Anno", la
  tabella dei mesi) — finivano schiacciate e sovrapposte. Tolta: il
  Pannello di Controllo torna a una colonna sola, come sempre. Restano il
  titolo colorato di ogni scheda (già presente da prima) e l'ombreggiatura
  più marcata sui pulsanti.

= 3.129.0 =
- **Pannello di Controllo sul sito, nuova disposizione**: le schede ora sono
  affiancate a due a due (come nella Plancia di WordPress), invece di una
  sotto l'altra in un'unica colonna lunghissima — quelle con tabelle o
  elenchi lunghi restano a piena larghezza. Il titolo di ogni scheda è
  diventato una striscia colorata e i pulsanti hanno la stessa
  ombreggiatura "sollevata" della Plancia. Nessun colore nuovo: sfondo,
  bianco delle schede e colori dei pulsanti restano quelli di sempre del
  sito.

= 3.128.0 =
- **Nuovo, il cartellino della Plancia**: un piccolo avviso con bordo
  tratteggiato, leggermente ruotato, compare in alto a una scheda SOLO
  quando c'è davvero qualcosa che aspetta un'azione — iscrizioni da
  approvare, domande senza risposta, richieste in attesa, ricette e
  testimonianze da approvare, biografie da approvare, risposte alle
  lezioni da leggere, foto del Tavolo da commentare. Riusa gli stessi
  conteggi già mostrati nella Bacheca di riepilogo in cima alla Plancia:
  niente di nuovo da controllare, solo più visibile scheda per scheda.

= 3.127.0 =
- **Nuovo aspetto della Plancia Generale** (solo il pannello WP, non tocca
  il sito pubblico): sfondo caldo crema, schede con ombra morbida ed
  effetto "sollevato", testo più grande e leggibile ovunque, pulsanti con
  la stessa ombreggiatura elegante vista sul sito di riferimento portato
  dal committente. Le strisce colorate in testa a ogni scheda restano
  quelle di sempre (ogni sezione ha già il suo colore), solo più grandi e
  leggibili. I colori dei pulsanti restano quelli già in uso in ogni
  sezione — non toccati.

= 3.126.0 =
- **Nuovo, Attestato di Corso Professionale**: nel Calendario Corsi c'è ora
  un tipo di appuntamento dedicato "🎖️ Corso Professionale (una
  settimana)". Per ogni cliente iscritto a un corso di questo tipo, il
  gestore può assegnare l'Attestato di Corso Professionale con un clic:
  diventa subito apribile e stampabile, sia dal pannello sia dalla sfoglina
  stessa in "Le mie prenotazioni". Usa il nuovo sigillo dedicato
  (sigillo-alta-qualita.png), diverso dal sigillo standard degli altri
  documenti.
- Chiude il pezzo mancante dei "3 attestati distinti" richiesti: corso in
  presenza professionale (nuovo), corso online personalizzato/diploma
  professionale (già coperti da Area Professionale) e registro delle
  sfogline confermate (già coperto dal Registro Ufficiale).

= 3.125.0 =
- **Badge cliccabili**: nella pagina "La Mia Sfoglia" ogni badge (es. "Voce
  di Famiglia") era un riquadro con la spiegazione nascosta in un tooltip,
  non apribile al tocco su telefono/tablet. Ora è un titolo cliccabile che
  apre la spiegazione, come nel resto del sito.
- **Cestino della biografia**: rimuovere la foto profilo o una foto/video
  dalla "Mia Biografia" non la cancella più per sempre — resta recuperabile
  in un cestino dedicato, dentro lo stesso riquadro, con un pulsante
  "Ripristina".
- **Missioni di oggi**: aggiunta una riga che chiarisce che l'elenco si
  aggiorna da solo mentre usi il sito, non c'è nulla da aprire.
- **Pannelli riordinati**: "Premi per Traguardo" ora è subito dopo
  "Libreria Video delle Lezioni" nei pannelli generali (prima erano
  separati da altre sezioni), perché i video dei premi spesso riusano video
  già caricati lì. Il testo del pannello Premi per Traguardo ora suggerisce
  esplicitamente questo uso: presentare, a chi arriva al livello massimo,
  le opportunità reali (corso in presenza, online personalizzato,
  professionale) con un video.

= 3.124.0 =
- **FAQ - Domande, caricamento automatico**: le 70 domande di base si
  caricano da sole a ogni attivazione del plugin, senza bisogno di premere
  il pulsante dal pannello. Restano comunque modificabili, cancellabili e
  se ne possono aggiungere di nuove in qualsiasi momento dal pannello: una
  domanda eliminata non torna più, anche dopo un aggiornamento del plugin.
- La FAQ resta solo nel menu interno del gaming (voce "FAQ - Domande" nel
  pannello laterale): tolta l'aggiunta automatica al menu principale del
  sito, resta disponibile solo il pulsante manuale dal pannello "Pagine
  pubbliche nel menu del sito" per chi la vuole anche lì.

= 3.123.1 =
- **Nuovo, Pianificazione dell'Anno**: ogni quadratino colorato della linea
  del tempo ora mostra la foto di chi ha creato quel corso/gara/percorso
  (prima quella della Biografia, altrimenti l'avatar dell'account), con un
  velo scuro che tiene il titolo sempre leggibile sopra la foto.

= 3.123.0 =
- **Nuovo, FAQ - Domande**: nuova sezione pubblica con le domande frequenti
  sull'Accademia, a fisarmonica (clicca la domanda per aprire la risposta),
  raggruppate per argomento e con un campo di ricerca. Precaricata con 70
  domande già pronte, che coprono tutte le sezioni del sito — dal pannello
  puoi correggerle, aggiungerne di nuove o eliminarle. La pagina si
  aggiunge al menu del sito con un clic dal pannello "Pagine pubbliche nel
  menu del sito".

= 3.122.0 =
- **Nuovo, Premi per Traguardo**: nuovo pannello nei pannelli generali dove
  preparare in anticipo un premio da consegnare in automatico quando una
  sfoglina raggiunge un livello o sblocca un badge — un video (link YouTube
  o Vimeo) oppure un messaggio di testo, che arriva nella sua casella
  "Messaggi" con il video incorporato se previsto. Sempre annunciato da un
  aeroplanino cliccabile che porta dritta lì. Si possono impostare più
  premi per lo stesso livello o badge.

= 3.121.24 =
- **Corretto**: dentro le schede rosso mattone (Calendario Corsi e ovunque
  si usi lo stesso tipo di scheda), il messaggio dopo un'azione — es. "Hai
  già una prenotazione per questa data" — restava rosso/verde e si
  confondeva con lo sfondo. Ora è bianco come il resto del testo nella
  scheda.

= 3.121.23 =
- **Nuovo, Aeroplanino cliccabile**: quando lo striscione attraversa lo
  schermo (badge, livello, risposta dell'esperto, lezione nuova, prenotazione
  confermata, e tutti gli altri avvisi personali), ora si può cliccare sopra
  per andare dritti al contenuto da visionare, invece di doverlo cercare a
  mano nel sito.
- **Nuovo, Compleanni**: quando una sfoglina compie gli anni, un annuncio
  "🎂 Oggi festeggia [nome]!" vola in automatico a tutte le sfogline lo
  stesso giorno — non serve più aprire "I Compleanni di Oggi" per
  scoprirlo, e cliccandoci sopra si arriva dritti alla vetrina degli auguri.

= 3.121.22 =
- **Corretto**: nella Plancia (wp-admin) il testo dentro i riquadri scuri
  ("Sfide — regole di partecipazione" e ovunque compaia lo stesso riquadro,
  es. Le Cose da Fare, Madrina & Allieva) restava nel colore spento di base
  invece che bianco, illeggibile sul fondo marrone. La correzione per il
  testo bianco valeva solo sul sito (front-end), non dentro wp-admin: ora
  vale in entrambi.

= 3.121.21 =
- **Area Professionale**: le etichette dei campi di ogni compito (Giorno,
  Titolo, Compito, Il tuo riscontro) erano grigie e spente. Ora sono in
  grassetto con lo stesso colore forte assegnato al corso, ben leggibili.

= 3.121.20 =
- **Esteso, colore delle liste**: il colore di base è arrivato anche a
  Posta interna, Messaggi di ogni sfoglina e Messaggi alle sfogline (Ultimi
  messaggi inviati) — deroga esplicita richiesta dal committente alla
  regola dello sfondo crema della messaggistica, solo per queste liste.
- **Nuovo, Area Professionale**: ogni compito era un riquadro bianco col
  solo bordo grigio, tutti i corsi identici tra loro. Ora ogni corso
  professionale ha un colore proprio (calcolato dall'ID del corso, stabile
  nel tempo), così i compiti di una sfoglina si distinguono subito da
  quelli di un'altra.

= 3.121.19 =
- **Corretto, tutti i pannelli generali**: lo stesso colore di base già dato
  al Calendario Corsi ora è su tutti gli elenchi "risultati" dei pannelli —
  contenuti creati o ricevuti (Ricettario, Ricette per il Libro, Dicono di
  Noi, Biografie, Il Matterello Parlante, La Sfoglia che Insegna Se Stessa,
  La Cassaforte del Sapere, Adotta un Piatto in Via di Estinzione, La
  Sfoglia Misurata, La Giuria a Turno, Lezioni Video, Diplomi e Locandine,
  Percorsi Guidati, partecipanti al Calendario). Le liste "in attesa di
  approvazione" restano col loro colore dedicato già esistente.

= 3.121.18 =
- **Corretto, Calendario Corsi**: nel pannello "Corsi in calendario e
  partecipanti" ogni riga era bianca e identica alle altre, poco leggibile
  con più corsi in elenco. Ora ogni corso ha un colore di base, un esame o
  un confronto dal vero hanno un colore proprio (oro/verde), e un corso
  bloccato risalta in rosso più di tutti gli altri.

= 3.121.17 =
- **Nuovo, Calendario Corsi**: oltre ai corsi normali, ora si può mettere in
  calendario anche un "Esame pratico" o un "Confronto dal vero" presso la
  scuola reale — stessa lista, stesse prenotazioni che le sfogline già
  usano, niente canale a parte. Le date si aggiungono una alla volta come
  già si fa per i corsi normali, senza bisogno di pianificarle in anticipo.

= 3.121.16 =
- **Nuovo, La Giuria a Turno**: campo di ricerca per trovare rapidamente una
  giudice nell'elenco da spuntare, sia quando crei un nuovo turno sia quando
  correggi uno già creato.
- **Nuovo, Modifica (audit sui pannelli generali)**: aggiunto il pulsante
  "Salva modifiche" dove mancava — La Giuria a Turno (nome, descrizione, chi
  giudica), La Cassaforte del Sapere, Adotta un Piatto in Via di Estinzione,
  La Sfoglia Misurata (che prima non aveva nemmeno Elimina/cestino: aggiunti
  anche quelli).
- **Corretto**: "Elimina definitivamente i selezionati" nel cestino non
  funzionava per La Cassaforte del Sapere, Il Matterello Parlante, Adotta un
  Piatto in Via di Estinzione e La Sfoglia che Insegna Se Stessa (mancavano
  dall'elenco dei tipi ammessi, aggiunta la Sfoglia Misurata allo stesso
  elenco).
- **Nuovo, progetto delle sfogline**: "Il Testamento della Sfoglina" ora
  resta visibile e modificabile dalla dashboard anche dopo il primo
  salvataggio — prima spariva per sempre appena scritto una volta.

= 3.121.15 =
- **Corretto, tutti i pannelli generali**: il colore che segnalava un
  contenuto "in attesa di approvazione" (ricette, testimonianze "Dicono di
  Noi", biografie della Vetrina, voci del Matterello Parlante, segnalazioni
  della Sfoglia che Insegna Se Stessa, domande all'Esperto senza risposta)
  era troppo simile allo sfondo crema della pagina e non si vedeva. Ora è
  uno sfondo oro acceso con bordo laterale marcato, uguale in tutte queste
  sezioni.

= 3.121.14 =
- **"Dicono di Noi"**: titolo e spiegazione riscritti, sia nella pagina
  pubblica che nel pannello di approvazione — ora è chiaro che sono le
  impressioni di sfogline E sfoglini che hanno fatto un percorso
  all'Accademia della Sfoglia, non solo delle sfogline.
- **Nuovo**: la pagina pubblica "Dicono di Noi" (già creata in automatico dal
  plugin) si può ora aggiungere al menu di navigazione del sito con un clic,
  insieme a Iscrizione e Newsletter, dal pannello "Pagine pubbliche nel menu
  del sito".

= 3.121.13 =
- **Nuovo, Sfide**: nel pannello "Sfide — regole di partecipazione"
  (rinominato da "Sfide blindate"), per QUALSIASI sfida — non solo quelle
  blindate — puoi ora escludere automaticamente le prime posizioni della
  classifica generale (tu decidi il numero, es. le prime 12), per dare
  spazio a chi è più indietro. Chi ammetti a tua scelta partecipa comunque,
  anche se è tra le prime. Il pannello ora elenca tutte le sfide, non solo
  quelle blindate.

= 3.121.12 =
- **Nuovo, Mappa dei Territori**: diventa una vera gara. Pubblicare una
  ricetta approvata per ognuna delle 20 regioni italiane accende quel
  territorio; la prima sfoglina ad accenderle tutte e 20 vince 50 punti.
  In cima alla mappa ora c'è una spiegazione chiara della regola, e dopo
  che qualcuna vince compare un avviso con il suo nome ("Gara conclusa").
  La gara è unica: dopo la prima vincitrice si può continuare a completare
  la propria mappa, ma il premio non si assegna una seconda volta.

= 3.121.11 =
- **Nuovo, Ricettario delle Famiglie**: le richieste in attesa di
  approvazione ora hanno il titolo evidenziato con un colore ben visibile
  (oro/ambra), invece del generico sfondo quasi invisibile di prima.
- **Nuovo**: oltre ad Approvare o Rifiutare, ora c'è un terzo pulsante
  "📕 Salva per il libro" — la ricetta resta approvata ma NON entra nel
  Ricettario pubblico: la vedono solo titolare e collaboratori, in una
  nuova sezione dedicata "Ricette per il Libro" (presente sia nel Pannello
  di Controllo sul sito sia nella Plancia di WordPress), pensata per
  raccogliere il materiale di un eventuale libro cartaceo. Da lì si può
  comunque spostare una ricetta nel Ricettario pubblico in qualsiasi
  momento, o rifiutarla.

= 3.121.10 =
- **Correzione grafica**: nella Bacheca di riepilogo in cima al pannello
  generale (le schede "Iscrizioni da approvare", "Domande senza risposta",
  ecc.), l'etichetta sotto il numero era scritta troppo piccola (12px) per
  essere letta comodamente. Ora è più grande (16px), più marcata e bianca
  piena invece che semi-trasparente.

= 3.121.9 =
- **Nuovo**: sotto ogni campo di testo lungo con il microfono della
  dettatura vocale, ora compare una piccola spiegazione ("Clicca il
  microfono per scrivere dettando a voce invece che a mano; clicca di nuovo
  per fermarti"). Compare automaticamente ovunque c'è il microfono, in
  qualunque modulo del sito — non serve toccare ogni sezione a mano.

= 3.121.8 =
- **Nuovo, nelle 5 sezioni sistemate in v3.121.7**: oltre a poter già
  eliminare e recuperare dal cestino, ora si può anche **modificare** il
  testo principale (non solo un campo secondario come prima): la
  mini-missione in Madrina & Allieva, la risposta in L'Esperto Risponde, la
  domanda del quiz in Indovina la Sfoglia, la domanda di verifica nelle
  Lezioni Video, e giorno/titolo/testo del compito in Area Professionale.
- **Correzione minore**: in Indovina la Sfoglia e Lezioni Video, il
  pulsante che salvava solo la risposta esatta ora si chiama "Salva
  modifiche" e salva anche il testo della domanda, in un unico passaggio.

= 3.121.7 =
- **Correzione, in tutte le sezioni con lo stesso problema**: dopo il caso
  delle "Cose da Fare" (v3.121.6), controllato tutto il resto del plugin
  per lo stesso difetto — liste salvate come array (non come contenuti
  veri), dove eliminare una voce la cancellava per sempre. Corretto in:
  Madrina & Allieva (mini-missioni), L'Esperto Risponde (risposte),
  Indovina la Sfoglia (domande del quiz), Lezioni Video (domande di
  verifica) e Area Professionale (compiti assegnati). Ognuna ora ha un
  piccolo cestino dedicato con "Ripristina", come già per le Cose da Fare.
- **Correzione minore**: nel modulo "L'Esperto Risponde", il pulsante
  "elimina domanda" puntava a una parte di pagina che non esisteva più nel
  markup attuale e non funzionava mai correttamente; ora punta al posto
  giusto.

= 3.121.6 =
- **Correzione**: "Le Cose da Fare" (i promemoria personali in "La Mia
  Sfoglia") non erano post di WordPress, quindi eliminarne uno lo cancellava
  per sempre, senza nessun cestino dove recuperarlo — contrariamente alla
  regola del progetto per cui nulla si perde per sempre. Ora ogni promemoria
  eliminato finisce in un piccolo cestino dedicato ("🗑️ Cose eliminate",
  dentro lo stesso riquadro), da cui si può ripristinare in qualsiasi
  momento.

= 3.121.5 =
- **Correzione**: audit completo dei pannelli generali (a seguito del caso
  Locandine) — altri 5 pannelli gestore mancavano nella Plancia di
  WordPress, esistevano solo nel Pannello di Controllo sul sito: "Messaggi
  di ogni sfoglina", "Notifiche per sfoglina", "Percorsi Guidati", "Dicono
  di Noi" e "Madrina & Allieva". Ora sono presenti in entrambi i pannelli,
  con le rispettive voci di menu. Aggiunto anche un controllo automatico
  permanente che verifica tutti i pannelli del plugin, per evitare che in
  futuro ne manchi un altro senza accorgersene.

= 3.121.4 =
- **Manutenzione interna, nessuna funzione nuova**: riorganizzato
  `gaming.js`, che era diviso in più blocchi di codice con "memoria"
  separata l'uno dall'altro (causa del bug dell'Anteprima aeroplanino
  corretto in v3.121.2). Ora è un blocco unico: le funzioni di una parte del
  file sono visibili da qualunque altra parte, senza bisogno di scorciatoie.
  Riduce il rischio che lo stesso tipo di bug si ripresenti in futuro.
  Nessun comportamento cambiato: verificato con l'intera suite di test.

= 3.121.3 =
- **Correzione**: il pannello "Diplomi e Locandine" non compariva affatto
  nella Plancia di WordPress (esisteva solo nel Pannello di Controllo sul
  sito) — per questo il pulsante "Crea locandina per questo corso" non
  trovava nulla da chi lavora dalla Plancia in wp-admin. Ora la sezione è
  presente in entrambi i pannelli, con la sua voce di menu.

= 3.121.2 =
- **Correzione**: l'Anteprima dell'aeroplanino (e l'anteprima automatica dopo
  un invio vero) non facevano volare lo striscione sullo schermo del
  titolare — la funzione che lo disegna era chiusa in una parte del file
  gaming.js non raggiungibile dal pulsante. Ora l'anteprima funziona.
- **Nuovo**: nel modulo di Calendario Corsi, sia per una nuova data sia per
  un corso già salvato, il pulsante "🖼️ Crea locandina per questo corso"
  porta dritti al modulo "Nuovo documento" di Diplomi e Locandine, già
  precompilato con titolo del corso, data/orario e quota.

= 3.121.1 =
- **Correzione**: il riordino in cima al pannello (Avvisi → Pianificazione
  dell'Anno → Calendario Corsi → Iscrizioni → Messaggi → Aeroplanino) era
  stato applicato solo al Pannello di Controllo sul sito, non alla Plancia
  dentro WordPress. Ora è uguale in entrambi i posti.

= 3.121.0 =
- **Visibilità sezioni e permessi**: nuovo pulsante "✕ Deseleziona tutto"
  accanto a ogni elenco di sfogline da nascondere — prima si poteva togliere
  una scelta solo tenendo premuto Ctrl/Cmd e ricliccandola.
- **Dettatura vocale**: nuovo interruttore in Impostazioni (Plancia WP) per
  attivarla o disattivarla in generale, con un elenco di eccezioni per
  scegliere singole sfogline con il valore capovolto (stesso schema del
  Blackout).

= 3.120.0 =
- **Pianificazione dell'Anno**: cliccando su un blocco (senza trascinarlo) si
  apre la sua scheda completa — titolo, date e i campi principali di quel
  tipo (per i corsi: orari, posti, prezzo, acconto, descrizione; per i
  Percorsi Guidati: descrizione e livello) — tutto modificabile da lì.
  Spostato anche in cima al Pannello di Controllo: subito sotto gli avvisi,
  seguito da Calendario Corsi, Richieste di Iscrizione, Messaggi privati e
  Aeroplanino, poi tutto il resto come prima.
- **Dettatura vocale**: il microfono per scrivere a voce ora compare su
  QUALUNQUE campo di testo del sito (prima solo su alcuni: ingredienti,
  procedimento, racconto, motivazione, descrizione, contenuto e altri non
  avevano mai avuto il microfono).
- **Correzione**: il pulsante "Vedi tutti" degli elenchi lunghi (es.
  Visibilità sezioni e permessi) aveva il testo che usciva dal pulsante:
  ereditava per errore la larghezza fissa delle frecce ‹›. Ora è un
  pulsante proprio, più grande e colorato.

= 3.119.0 =
- **Pianificazione dell'Anno** (nuovo pannello, solo titolare/collaboratori):
  un'unica linea del tempo, mese per mese, con Calendario Corsi, Sfida della
  Settimana, Percorsi Guidati (Stagionali con data reale, normali con una
  data pianificata che non cambia come si sbloccano) e Area Professionale
  (data pianificata). Si trascina un blocco per cambiargli mese, si
  trascinano i bordi per accorciarlo/allungarlo, si trascina la maniglia ☰
  di un Percorso Guidato normale su un altro per scambiare il loro ordine
  reale nella sequenza. Colori diversi per tipo, legenda sempre visibile.
- **Aeroplanino**: nuovo pulsante "🔍 Anteprima" — mostra sul proprio
  schermo come apparirà lo striscione, senza inviarlo a nessuna sfoglina.
- **Aeroplanino**: lo striscione attraversa lo schermo più lentamente
  (da 7 a 11 secondi), più facile da leggere.
- **Correzione**: ripristinare dal cestino una richiesta di Aiuto o un
  messaggio di Posta interna ora la rimette correttamente tra quelle "da
  leggere" (prima restava con lo stato che aveva prima dell'eliminazione,
  anche se già gestita/letta, e poteva sembrare sparita).

= 3.118.1 =
Correzione urgente: la 3.118.0 conteneva una funzione dichiarata due volte
(`gs_ajax_voce_elimina`, presente sia in forms.php sia nel nuovo Matterello
Parlante) — un errore fatale che, sul sito vero, bloccava l'esecuzione degli
shortcode (comparivano scritti letteralmente, es. "[gs_sfogline]", invece del
contenuto). Le funzioni e i pulsanti del Matterello Parlante sono stati
rinominati in modo univoco. Aggiunto anche un nuovo test permanente
(`test-carica-tutti-i-moduli.php`) che carica tutti i file del plugin
insieme, come fa il sito vero, per scoprire in anticipo questo tipo di
collisione — i singoli test non potevano vederla perché ciascuno caricava
solo i file che gli servivano.

= 3.118.0 =
Le 10 idee approvate del terzo giro di brainstorm, tutte realizzate.

- **Genealogia delle Ricette**: ogni ricetta può indicare da quale altra
  ricetta "nasce" (facoltativo). Il Ricettario mostra "Nata da…" e "Ricette
  nate da questa" sotto ogni ricetta collegata.
- **Adotta un Piatto in Via di Estinzione**: il titolare segnala piatti
  tradizionali a rischio; una sfoglina (o una squadra) può adottarli e
  diventarne custode pubblica. Nuova sezione, nuovo pannello.
- **Il Matterello Parlante**: archivio vocale di ricordi e consigli. Si
  registra direttamente dal browser (dove supportato) o si carica un file
  audio; ogni registrazione va approvata prima di comparire nell'archivio
  pubblico. gs_msg_upload() ora accetta anche l'audio, non solo foto/video.
- **Un Anno Fa Oggi**: flashback pubblico automatico delle ricette
  approvate esattamente un anno fa, nello stesso giorno.
- **Percorso a Staffetta**: un percorso individuale (non di squadra) può
  avere un testimone che passa di mano: chi lo completa lo raccoglie (se
  libero) e sceglie personalmente a chi darlo.
- **La Sfida del Silenzio**: il titolare può impostare un periodo in cui la
  Classifica Generale resta congelata a com'era all'inizio — punti e
  missioni continuano a funzionare normalmente, solo la classifica non si
  muove agli occhi delle sfogline.
- **Il Diario a Doppio Senso**: nel Diario dell'Impasto, se un anno fa (nello
  stesso giorno) avevi già scritto qualcosa, te lo ripropone — privato,
  solo tu lo vedi.
- **Il Testamento della Sfoglina**: chi raggiunge il livello più alto
  dell'Accademia viene invitata a lasciare un testo permanente, visibile
  nella propria Vetrina e nel nuovo elenco pubblico "I Testamenti delle
  Maestre".
- **La Cassaforte del Sapere**: contenuto esclusivo che si sblocca solo
  quando un numero di sfogline scelto dal titolare raggiunge, tutte
  insieme, un livello minimo — non conta il livello individuale.
- **La Sfoglia che Insegna Se Stessa**: una sfoglina può raccontare
  onestamente un errore fatto e cosa ha imparato; il titolare può
  "promuoverlo" a materiale didattico ufficiale, con il credito a chi
  l'ha segnalato.

Aggiunta anche una correzione: le pagine automatiche di "La Sfoglia
Misurata" e "La Giuria a Turno" (introdotte in precedenza) non venivano
create all'attivazione — ora lo sono, come tutte le altre.

= 3.117.0 =
Tre novità legate alle gare tra sfogline: due nuovi percorsi e un
interruttore per scegliere chi giudica.

- **La Sfoglia Misurata**: nuova gara a numeri (chi impasta di più, chi
  usa meno farina, chi si avvicina di più a un obiettivo dato dal
  gestore). Ogni sfoglina invia il proprio valore con una foto, la
  classifica si aggiorna da sola; alla chiusura vince chi è primo in
  classifica: badge, punti e notifica. Nuovo pannello dedicato, nuova
  linguetta "📏 La Sfoglia Misurata" nel menu delle sfogline.
- **La Giuria a Turno**: nuova gara a voto pubblico e motivato. Le
  sfogline inviano un'opera (foto + descrizione); un gruppo di giudici
  assegnato dal gestore vota con un voto MAI anonimo (si vede sempre chi
  ha votato cosa) e un motivo obbligatorio per ogni voto. Alla chiusura
  vince chi ha la media voti più alta: badge, punti e notifica. Nuovo
  pannello dedicato, nuova linguetta "⚖️ La Giuria a Turno".
- **Tipo di voto nelle gare**: sia nella Sfida della Settimana (quella
  storica) sia nella nuova Giuria a Turno, il gestore può ora scegliere
  dal pannello chi giudica ogni singola gara: le sfogline tra di loro
  (come sempre) oppure solo il titolare e i collaboratori ("i maestri").
  Le gare già esistenti restano tutte a voto tra sfogline, nessun
  cambiamento per chi non tocca l'impostazione.

= 3.116.0 =
Bruno Cingolani inserito nel programma con le stesse mansioni di Rina
Poletti: Esperto e Docente.

- **Esperto** (L'Esperto Risponde, Conversazioni private): nessuna
  modifica necessaria, il meccanismo era già generico — basta assegnare
  il suo account da "L'Esperto Risponde" nel pannello, come per chiunque.
- **Docente** (Area Professionale): il Diploma di fine percorso, prima
  scritto sempre come "Diploma di Rina Poletti" a prescindere da chi
  avesse davvero insegnato, ora riporta sempre il nome del docente vero
  di quel corso — sia nella vista della sfoglina, sia nel pannello del
  gestore, sia sul certificato stampabile. Un corso con Bruno come
  docente produce correttamente un "Diploma con Bruno Cingolani"; i corsi
  già esistenti con Rina restano invariati (è il valore di default).
- Corretto anche un piccolo testo del piano di studio che dava per
  scontato "la docente" al femminile.

= 3.115.0 =
Le ultime tre idee del blocco "coinvolgimento quotidiano": un gesto
personale con un maestro, un promemoria su misura, e la costanza premiata
senza ansia.

- **Il Tavolo di Lavoro**: nuova sezione dove ogni sfoglina carica la foto
  del suo lavoro di oggi (privata, non nella Galleria pubblica) e riceve
  un commento diretto da un maestro. Una foto al giorno; nel pannello i
  gestori vedono le foto senza commento in cima, per rispondere in fretta.
- **Promemoria giornaliero**: ogni sfoglina può attivare (da sé, scegliendo
  l'ora) un avviso se non si è ancora collegata quel giorno — nessun
  fastidio se si è già collegata. Estende il modulo notifiche esistente
  con una nuova categoria "Promemoria giornaliero", con gli stessi canali
  email/interno delle altre.
- **Scudo salva-streak**: ogni 4 settimane consecutive della Streak del
  Matterello si guadagna uno scudo. Se una settimana viene saltata, uno
  scudo disponibile la copre in automatico e la streak non si azzera —
  visibile nella dashboard "La Mia Sfoglia".

= 3.114.0 =
Tre nuove idee per il coinvolgimento quotidiano: percorsi di squadra,
mappa dei territori e un quiz-lampo giornaliero.

- **Percorso di Squadra**: un Percorso Guidato può diventare "di squadra"
  — l'avanzamento è collettivo (basta che un membro qualsiasi veda una
  lezione perché conti per tutta la squadra), e quando lo finiscono
  insieme badge e punti arrivano a ogni membro attuale. Fuori dalla
  sequenza normale, sempre aperto, non conta per il Diploma Finale.
- **La Mappa dei Territori**: nuova mappa dell'Italia nel Ricettario. Ogni
  ricetta di famiglia approvata, con la sua regione italiana indicata,
  colora quel territorio sulla mappa personale della sfoglina.
- **Indovina la Sfoglia**: quiz-lampo giornaliero, una domanda al giorno
  uguale per tutte, scelta in automatico dalla banca scritta dal
  titolare — cambia da sola a mezzanotte. Risposta corretta con
  correzione automatica (stessa logica delle domande delle Lezioni
  Video) e qualche punto in premio. Nuova sezione nel pannello e
  linguetta laterale dedicata.

= 3.113.0 =
Due nuove idee per dare un motivo di tornare ogni giorno: contenuti a
puntate e percorsi stagionali a tempo limitato.

- **Lezioni a puntate**: nella Libreria Video ogni lezione può avere una
  "Data di uscita" facoltativa — resta nascosta finché non arriva quel
  giorno, poi si apre da sola. Utile per un percorso pubblicato un pezzo
  alla volta invece che tutto insieme. Il link video ora è facoltativo:
  si può pubblicare anche una lezione di solo testo.
- **Percorsi Stagionali**: un Percorso Guidato può avere una data di
  inizio e/o fine (es. "Percorso di Natale"). Fuori dalla sequenza
  normale — non ha propedeuticità, è aperto a tutti da subito ma solo
  dentro quella finestra, poi torna nascosto in automatico. Compare in
  una sezione a parte ("🎄 Percorsi Stagionali"), sia per la sfoglina sia
  nella vetrina pubblica. Non influisce sul Diploma Finale, che resta
  legato solo ai percorsi permanenti.

= 3.112.0 =
Diploma Finale: completando TUTTI i Percorsi Guidati (non solo uno) si
sblocca un traguardo speciale.

- Nuovo badge "🎓 Diploma Finale: Percorso di Formazione Completo",
  assegnato in automatico quando una sfoglina ha completato ogni
  Percorso Guidato esistente (in ordine, come sempre).
- Bonus di punti dedicato, oltre ai punti già dati per i singoli percorsi.
- Notifica Aeroplanino + email di congratulazioni, come per gli altri
  traguardi importanti.
- Nel pannello "Percorsi Guidati" della sfoglina compare un avviso con il
  pulsante per vedere/stampare il Diploma Finale, un certificato
  stampabile a parte (in aggiunta ai certificati dei singoli percorsi,
  non al loro posto).

= 3.111.0 =
Livelli per i Percorsi Guidati (Base/Intermedio/Avanzato di partenza), con
un modulo completo per gestirli.

- Nel pannello "Percorsi Guidati", nuova sezione **"Livelli dei percorsi"**:
  crea nuovi livelli, rinominali, eliminali o cambia il loro ordine con
  ▲▼ — non sono fissi, li gestisci tu liberamente.
- Assegna un livello (facoltativo) a ogni percorso, dal modulo di
  creazione o da quello di modifica.
- Il livello compare come etichetta accanto al titolo del percorso, sia
  nella vista della sfoglina sia nella vetrina pubblica.
- Rinominare un livello aggiorna automaticamente tutti i percorsi che lo
  usano; eliminarlo li lascia semplicemente senza livello (non li tocca
  in altro modo — non influenza mai lo sblocco in sequenza, che resta
  deciso solo dall'ordine dei percorsi).
15 nuovi test.

= 3.110.0 =
Nuovo: **Aeroplanino — messaggio istantaneo**, nel Pannello di Controllo e
nella Plancia Generale di WordPress.

- Scrivi un messaggio breve e invialo: attraversa lo schermo di ogni
  sfoglina collegata entro una quindicina di secondi, come uno striscione
  — anche se sta navigando altrove sul sito. Non lascia traccia nella
  posta né in nessun elenco: pensato per avvisi rapidi del momento (es.
  "il sito sarà chiuso stasera per manutenzione").
- Puoi scrivere, correggere e cancellare il testo liberamente prima di
  inviarlo; un clic chiede conferma e lo manda a tutte le sfogline.
- **Lo vedi anche tu**, su ogni tuo dispositivo collegato in quel momento
  (computer e telefono insieme, non solo il primo che si accorge
  dell'invio): un meccanismo pensato apposta, separato dalla coda normale
  che invece si "consuma" al primo dispositivo che la legge.
- Come tutti i pannelli del Controllo Generale, assegnabile per nome ai
  collaboratori dalla tabella permessi.
11 nuovi test.

= 3.109.0 =
I tre suggerimenti migliorativi proposti dopo la v3.108.0.

**1. Stesso bug di compressione della biografia, trovato altrove.** Il
controllo aggiunto in v3.105.0 (compressione automatica + limite di peso
prima dell'invio) mancava anche in: Conversazioni private, Auguri,
Messaggi del Calendario, Aiuto e Suggerimenti (tutte passano dalla stessa
funzione condivisa `gsSendMsg`), più Diplomi e Locandine (creazione e
aggiunta foto) e Ricettario delle Famiglie. Corretto centralmente per non
doverlo ripetere in futuro.

**2. Nuovo: "Vedi come collaboratore"** in "Visibilità delle sezioni e
permessi" — scegli una persona dal menu e la tabella evidenzia subito cosa
vede e cosa no (bordo verde/rosso, con un riepilogo "X visibili, Y
nascoste"), senza dover controllare riga per riga su quasi 50 voci.

**3. Confronto pannello sito/WordPress**: trovato e corretto un'altra
differenza silenziosa oltre a quella già risolta in v3.107.0 — l'avviso
"aeroplanino" per messaggi/conversazioni non lette mancava nella Plancia
Generale di WordPress.

I punti 1 e 2 sono JavaScript/CSS: verificati con prove reali in browser
(compressione e blocco file pesanti, evidenziazione righe), non con la
suite automatica. 7 nuovi test per il punto 3.

= 3.108.0 =
Due migliorie dalla lista in sospeso.

- **"Messaggi dalla segreteria"** ha ora lo stesso riquadro scuro rosso
  mattone di tutte le altre sezioni de "La Mia Sfoglia" (prima era l'unica
  con lo sfondo crema). Le altre caselle di posta (Posta interna,
  Conversazioni private, i pannelli del titolare) restano invariate.
- **Galleria, Registro Ufficiale, Le Sfogline, Classifica, Le Sfide, Cerca
  in tutto il sito e Badge** sono ora assegnabili per nome ai
  collaboratori, come già i pannelli del Controllo Generale: scegli quali
  collaboratori vedono ciascuna di queste 7 pagine pubbliche. Non essendoci
  nulla da "gestire" su queste pagine, la scelta controlla solo la
  visibilità per il collaboratore — le sfogline normali non ne sono mai
  toccate.
9 nuovi test.

= 3.107.0 =
Corretto lo scorrimento delle caselle cliccabili nella **Plancia Generale
di WordPress**: il titolo della sezione poteva restare coperto in alto.

- La calibrazione manuale ("Indice laterale — allineamento delle
  sezioni", regolabile dal pannello sul sito) ora viene inviata anche
  alla Plancia Generale in WordPress: prima veniva applicata solo sul
  pannello del sito, e in WordPress non aveva alcun effetto.
- La casella "Iscrizioni da approvare" della bacheca di riepilogo, in
  WordPress, non portava da nessuna parte (l'ancora a cui doveva saltare
  non esisteva in quella pagina): ora funziona come le altre.
- Se dopo l'aggiornamento il titolo resta ancora coperto, regola di nuovo
  il cursore in "Indice laterale — allineamento delle sezioni": ora la
  modifica vale sia sul sito sia in WordPress.

= 3.106.0 =
Nel contatore in cima al Pannello di Controllo ora compare anche "Aiuto e
suggerimenti da leggere", accanto a "Richieste in attesa": una nuova
scheda con il numero dei messaggi non ancora gestiti (evidenziata in
rosso quando c'è qualcosa da leggere), che porta dritti alla sezione con
un clic.
5 nuovi test.

= 3.105.0 =
Corretto: caricare la foto nella Biografia della Vetrina poteva fallire con
un errore poco chiaro se la foto (tipicamente uno scatto da telefono) era
pesante.

- La foto della biografia ora passa dalla stessa compressione automatica
  già usata per tutti gli altri caricamenti del sito (ridimensionata e
  convertita prima dell'invio), e viene controllata contro il limite di
  peso **prima** di provare a inviarla: se resta troppo pesante, il
  messaggio lo dice chiaramente invece di fallire in silenzio.
- Messaggio più chiaro anche quando il file supera il limite del server
  (non solo quello del pannello): "Il file è troppo pesante per il
  server…" invece del generico "Caricamento non riuscito, riprova."
5 nuovi test.

= 3.104.0 =
Due correzioni: nome corretto di "Matterello" ovunque nel plugin, e riquadro
"Come funziona questa sezione" aggiunto dove ancora mancava.

- Corretto "Mattarello" → "Matterello" in tutti i testi del plugin
  (Streak, Area Professionale, readme): il nome giusto è quello
  dell'Associazione Missione Matterello.
- Aggiunto il riquadro "ℹ️ Come funziona questa sezione" a "Sfoglie in
  gara" (galleria della sfida), "Classifica a Squadre" e "Conversazioni
  private" — le uniche sezioni rivolte alle sfogline che ne erano ancora
  prive.
3 nuovi test.

= 3.103.0 =
Rimedio rapido per "Visibilità delle sezioni e permessi", diventata grande
(quasi 50 righe) dopo la v3.101.0: era facile nascondere per sbagliato
qualche pannello — Blackout compreso — e poi non ritrovarlo più.

- Nuova casella **"🔍 Cerca una sezione…"** in cima alla tabella: filtra le
  righe per nome mentre scrivi, come già nelle altre liste del pannello.
- Nuovo pulsante **"Rendi visibili tutte le sezioni"** (compare solo se
  qualcosa risulta nascosto): un solo clic riaccende tutto quello che è
  stato disattivato per errore, senza dover cercare le singole righe.
  Non tocca i permessi assegnati ai collaboratori.
3 nuovi test.

= 3.102.0 =
Nel Blackout ("Oscura il Gaming") ora puoi scegliere **eccezioni per singola
sfoglina**: utile per far provare il portale a qualcuna in anteprima, senza
riaprirlo a tutte.

- Nel pannello "Oscura il Gaming", nuova selezione multipla "Vuoi far
  provare il Gaming a qualcuna anche mentre è oscurato?": le sfogline
  scelte vedono tutto (pagine, gallerie, linguette laterali) anche col
  blackout acceso, come già succede per te e per i collaboratori.
- Nessun impatto quando il Blackout è spento, o quando non ci sono
  eccezioni impostate: il comportamento resta quello di sempre.
14 nuovi test.

= 3.101.0 =
Quasi tutti i pannelli del Controllo Generale sono ora **assegnabili per
nome ai collaboratori**, non solo le 9 sezioni con pagina pubblica di prima.

- Estesa "Visibilità delle sezioni e permessi" con **25 nuovi pannelli**:
  Abbonamenti, Biografie, Correggi punti, Premio di Fine Anno, Sfide
  blindate, Bacheca di riepilogo, Blackout, Vetrina on/off, Richieste di
  Iscrizione, Indice laterale, Pagine nel menu, Diagnostica, Esporta dati,
  Diplomi e Locandine, Madrina & Allieva, Foto/video, Backup, Messaggi
  privati, Messaggi delle sfogline, Newsletter, Notifiche, Percorsi Guidati,
  Cerca sfoglina, Aspetto "Le Sfogline", Messaggio di benvenuto.
- Per ognuno scegli, come già per Ricettario e Lezioni Video, se lo vedono
  tutti i collaboratori o solo alcuni per nome.
- Restano riservati solo a te (titolare): **Blocco dashboard WP**,
  **Collaboratori** e questo stesso pannello dei permessi — perché
  controllano il sistema dei permessi.
- Per i pannelli senza una pagina pubblica propria, l'interruttore
  "Visibile" ora spegne l'intero strumento (anche per te, se lo disattivi).
- Corretto un piccolo bug: "Percorsi Guidati" condivideva per errore il
  permesso della "Libreria Video delle Lezioni" invece di averne uno suo.
- Aggiunta la possibilità di aprire Diagnostica ed Esporta dati anche dal
  pannello sul sito, non solo da wp-admin.
9 nuovi test.

= 3.100.0 =
Collaboratori: ora puoi crearli e modificarli **direttamente dal tuo
pannello**, senza aspettare che si iscrivano da soli.
- Sezione "Collaboratori del pannello": nuovo modulo **"Aggiungi un nuovo
  collaboratore"** — scrivi nome ed email, l'account viene creato e reso
  subito collaboratore (nessuna coda di approvazione: sei tu che lo stai già
  approvando). La persona riceve una email per impostare la propria
  password — non è mai visibile a te, per sicurezza.
- Nuovo pulsante **"✏️ Modifica"** su ogni collaboratore già presente, per
  correggere nome o email senza dover passare da wp-admin.
- Solo tu (titolare vero del sito) vedi questi pulsanti, come per il resto
  di questa sezione.
11 nuovi test.

= 3.99.0 =
Diplomi e Locandine: nuovo pulsante **"🔗 Prepara link per Facebook"** (solo
per il titolare), accanto a "Condividi".
- Genera un link pubblico della locandina — nessun login richiesto per chi lo
  apre — con l'anteprima e il pulsante "Per prenotarti clicca qui" **veri e
  funzionanti**: a differenza dell'immagine scaricata (statica, non
  cliccabile), chi apre questo link vede la pagina reale e può prenotarsi.
- Il pulsante prepara anche l'anteprima che Facebook/Instagram mostrano
  quando incolli il link in un post (salvata una volta come immagine vera:
  i social non "vedono" il disegno fatto nel browser).
- Il link si copia da solo negli appunti, pronto da incollare; se il browser
  blocca la copia automatica, compare comunque per copiarlo a mano.
- Solo documenti pubblicati sono raggiungibili da questo link (mai quelli nel
  cestino).
11 nuovi test.

= 3.98.0 =
Diplomi e Locandine: nuovo pulsante **"📤 Condividi"** sulla pagina del
documento, accanto a "Scarica PNG/JPG".
- Apre il menu di condivisione nativo del telefono/computer, con l'immagine
  già pronta e allegata — scegli tu l'app (Instagram, Facebook, WhatsApp,
  Messaggi…), il sito non decide al posto tuo. Nessuna delle due piattaforme
  permette di aprirsi già pubblicata da un sito esterno: questo è il modo più
  diretto possibile.
- Se il browser/dispositivo non supporta questo tipo di condivisione (capita
  su alcuni computer), un messaggio lo segnala e invita a usare "Scarica" e
  condividere il file a mano.
1 nuovo test.

= 3.97.0 =
Diplomi e Locandine: corretto un difetto che si vedeva solo su alcuni
computer — il testo dell'etichetta (es. "Nuovo corso in partenza!") poteva
sporgere fuori dal riquadro dorato nell'immagine scaricata, pur essendo
perfetto sulla pagina del sito. Causa: se il computer di chi scarica non ha
installato il carattere Georgia (né Times New Roman), il browser lo sostituisce
con un altro carattere dalle misure leggermente diverse — il testo, ancorato
al bordo inferiore del riquadro, poteva sporgere sotto invece di restare
centrato. Ora il testo è sempre centrato verticalmente nel riquadro,
indipendentemente dal carattere che il computer usa davvero.

= 3.96.0 =
Dopo ogni azione nel pannello (elimina, salva, ripristina…), la pagina ora
resta nel punto esatto in cui ti trovavi, invece di ripartire sempre
dall'inizio. Vale ovunque nel pannello: locandine, ricettario, lezioni video,
messaggi, sezioni, iscrizioni, tutto.

= 3.95.0 =
Eliminazione definitiva dal cestino — **solo dal tuo Pannello Generale**, mai
per i collaboratori.
- Su ogni cestino del pannello (Diplomi e Locandine, Lezioni Video,
  Ricettario, "Dicono di Noi", Posta interna, Aiuto e suggerimenti) è
  comparsa una casella di spunta per riga e una casella "seleziona tutto" in
  cima alla tabella, più un pulsante "🗑️ Elimina definitivamente i
  selezionati".
- Questa funzione la vedi **solo tu** (chi ha i permessi di amministratore
  veri del sito): i collaboratori che gestiscono il pannello continuano a
  vedere solo "Ripristina", come prima.
- Resta comunque impossibile eliminare per errore qualcosa che non è già
  passato dal cestino: l'eliminazione definitiva controlla sempre che
  l'elemento sia del tipo giusto e sia già nel cestino, prima di procedere.
- Il resto del progetto non cambia: ogni "Elimina" continua a spostare nel
  cestino (mai cancellazione diretta), questa è solo un'azione in più per
  svuotarlo quando vuoi tu.
22 nuovi test.

= 3.94.0 =
Diplomi e Locandine: corretto il disallineamento dei due titoli grandi
nell'immagine scaricata (nella pagina del sito era già corretto).
- Con un titolo di lunghezza normale, "Settembre" (o l'ultima parola)
  scendeva a capo su una riga tutta sua, mentre nella pagina del sito lo
  stesso titolo stava su una riga sola: la dimensione del testo nell'immagine
  ora si restringe automaticamente quanto basta perché ciascun titolo stia
  su una riga sola, invece di avere una dimensione fissa che andava a capo
  in un punto imprevedibile. Se un titolo è davvero troppo lungo per stare
  su una riga sola in nessun modo, va a capo comunque, ma alla dimensione
  più piccola possibile.
Verificato scaricando davvero un'immagine con due titoli della stessa
lunghezza del caso segnalato.

= 3.93.0 =
Diplomi e Locandine: corretto un bug reale nell'immagine scaricata con foto
verticali e/o due titoli grandi insieme.
- L'immagine scaricata aveva un'altezza fissa: con un secondo titolo e una
  foto verticale, il contenuto superava quell'altezza e la foto restava
  tagliata fuori dalla cornice dorata (nella pagina del sito, che invece si
  allunga liberamente, il problema non si vedeva). Ora l'altezza
  dell'immagine si adatta al contenuto — la cornice cresce quanto serve,
  non taglia più nulla.
- I due titoli grandi, quando sono presenti insieme, sono ora più piccoli
  (invece di restare enormi come un titolo singolo): risultato più
  equilibrato, e meno probabilità che il contenuto superi la cornice.
Verificato scaricando davvero un'immagine con due titoli e una foto
verticale, controllando che la cornice racchiuda tutto senza tagli.

= 3.92.0 =
Diplomi e Locandine: corretto un bug reale, presente fin dalla prima
consegna (v3.86.0) — i pulsanti "Scarica PNG"/"Scarica JPG" della pagina
stampabile aprivano semplicemente la pagina nel browser, senza scaricare
nulla e senza chiedere dove salvare il file.
- Causa: la pagina stampabile della locandina "esce" subito dalla richiesta
  (per poter essere una pagina HTML autonoma, stampabile) prima che
  WordPress carichi jQuery e gaming.js come fa normalmente sulle pagine del
  pannello — quindi i pulsanti "Scarica" non avevano nessun gestore di clic
  collegato. Ora la pagina li carica esplicitamente.
- Corretta anche la generazione dell'immagine: da un certo peso in su, alcuni
  browser (Opera/Chrome inclusi) ignorano la richiesta di scaricare
  un'immagine generata "al volo" e la aprono invece direttamente nel
  browser. Ora l'immagine viene preparata in un modo che i browser
  riconoscono sempre come un file da scaricare, non da visualizzare.
- Verificato con un vero clic in un browser: il pulsante ora genera
  davvero un download, con il nome del file corretto.
2 nuovi test.

= 3.91.0 =
Diplomi e Locandine: secondo titolo grande.
- Nuovo campo facoltativo **"Secondo titolo grande"**, nel form di creazione e
  in quello di modifica: se compilato, compare come una seconda riga grande
  subito sotto il titolo principale, sia nella pagina stampabile sia
  nell'immagine scaricata (PNG/JPG).
5 nuovi test.

= 3.90.0 =
Diplomi e Locandine: rifiniture richieste dopo la consegna di v3.89.0.
- Nel modulo "Nuovo documento" ora si può allegare **una foto già in fase di
  creazione** (caricamento diretto o scelta dalla libreria media), non solo
  dopo aver creato il documento.
- Nell'intestazione della locandina, "ACCADEMIA DELLA SFOGLIA" e
  l'intestazione (es. "Maestra Rina Poletti") sono ora **in grande**, alla
  pari del resto del testo, anziché come una piccola etichetta.
5 nuovi test.

= 3.89.0 =
Correzioni e aggiunte a Diplomi e Locandine (v3.86.0), tutte verificate con
screenshot reali:
- Logo (sigillo) ritagliato meglio e perfettamente centrato nella corona
  dorata; ora **personalizzabile dal pannello** ("Logo dei documenti"),
  scelto dalla libreria media, valido per Diplomi e Locandine, il
  certificato di Percorso Guidato e il diploma di Area Professionale
  insieme (ognuno mantiene il proprio logo di sempre finché non se ne
  sceglie uno nuovo).
- Corretto un difetto di centratura nell'immagine scaricata (PNG/JPG):
  l'intestazione "ACCADEMIA DELLA SFOGLIA" e il riquadro dorato sotto il
  titolo non erano perfettamente centrati per uno stato residuo del
  disegno delle lettere spaziate; ora le lettere si disegnano una per una,
  sempre centrate.
- Pulsanti in fondo alla pagina (Stampa, Scarica PNG, Scarica JPG) ora
  della stessa larghezza e centrati come gruppo, non più sbilanciati.
- **Scarica anche come JPG**, oltre al PNG di prima.
- Testo del programma più grande e leggibile; logo doppio nell'immagine
  scaricata; più respiro tra intestazione, riquadro data e titolo.
- Nuovo **pulsante "Modifica"** per ogni documento già creato (prima si
  poteva solo eliminarlo), e lo stesso anche per i **Percorsi Guidati**.
- Nuovo campo **"Link di prenotazione"**: se compilato, sulla pagina
  compare un pulsante "Per prenotarti clicca qui" verso l'indirizzo scelto.
- Le foto delle locandine si possono ora **scegliere dalla libreria media**
  del sito, non solo caricarne di nuove.
23 nuovi test.

= 3.88.0 =
Pulizia del linguaggio: diversi testi (email, messaggi d'aiuto, pannello)
davano per scontato il genere femminile, ma nel progetto ci sono anche
uomini (es. Bruno Cingolani tra i docenti/collaboratori). Corretti, tra gli
altri: il messaggio di benvenuto ("Benvenuta" → "Ciao"), i compiti "seguiti
dalle maestre" → "dai docenti", "Sei arrivata al livello" → "Hai
raggiunto", "sei tra le concorrenti ammesse" → "le persone ammesse", "se
sei approvata/collegata/interessata" riformulati in modo neutro, "chi non
è ancora iscritta" → "chi non si è ancora iscritto". La correzione più
estesa: "Collaboratrice/Collaboratrici", usata nel Pannello di Controllo,
nella Plancia e nei relativi messaggi, era in contraddizione con il resto
del plugin (che già usava "collaboratore/collaboratori" quasi ovunque) —
uniformato a "Collaboratore/Collaboratori" in tutti i pannelli, le
etichette di stato e i messaggi.

= 3.87.0 =
Nuovo: "Blocco dashboard WordPress" nel pannello di controllo (solo per
amministratori veri). Con l'interruttore acceso, chiunque non sia
amministratore — compresi i collaboratori — viene rimandato al Pannello di
Controllo del sito appena prova ad aprire la dashboard di WordPress: continua
a navigare e lavorare nel progetto, ma non vede il resto di WordPress. Utile
per condividere il sito con i collaboratori durante una manutenzione senza
dare loro accesso a wp-admin. Le pagine stampabili del progetto (diplomi,
certificati, locandine, esportazioni), che tecnicamente passano da admin.php,
restano raggiungibili — non sono la dashboard, sono contenuto del progetto.
L'amministratore che accende l'interruttore non resta mai bloccato fuori.
8 nuovi test.

= 3.86.0 =
Nuovo: "Diplomi e Locandine" nel pannello di controllo generale (nuovo modulo
locandine.php) — documenti stampabili liberi con la stessa grafica dei
diplomi dell'Accademia (cornice dorata su pergamena, logo tondo ufficiale).
Per ogni documento si scrive un'intestazione modificabile (precompilata
"MAESTRA RINA POLETTI"), un titolo grande, un'etichetta facoltativa, un
testo libero (es. il programma di un corso) e si possono caricare foto.

Ogni documento si può stampare/salvare in PDF come i diplomi già esistenti,
oppure scaricare come immagine PNG (1080×1350, formato verticale per i
social) pronta da pubblicare su Facebook e Instagram — disegnata su
&lt;canvas&gt; direttamente nel browser, senza librerie esterne.

Aggiunto anche il logo tondo ufficiale dell'Accademia in alta qualità
(sigillo-accademia.png, ritagliato dal sigillo originale), usato per ora
solo dai nuovi Diplomi e Locandine.

= 3.85.0 =
Nuovo: "Notifiche per sfoglina" nel pannello di controllo (nuovo modulo
notifiche-pref.php). Per ogni sfoglina e per ognuna delle 5 categorie di
email del progetto (Livelli/Badge/Punti, Calendario Corsi e Lezioni Video,
Digest settimanale, Messaggi e Conversazioni, Iscrizione e Account), il
titolare può scegliere due canali indipendenti: Email (la vera email) e
Interno (compare come messaggio dalla segreteria nella sua area, senza
toccare la sua posta ufficiale). Si possono tenere entrambi attivi (utile
per le email di servizio come la scadenza dell'iscrizione, che restano
anche via mail), solo uno, o nessuno. Chi non ha mai impostato nulla
continua a ricevere tutto via email come sempre — nessun cambiamento senza
una scelta esplicita del titolare.

Tutte le email che il plugin manda a una sfoglina in particolare (circa
20 punti di invio in una dozzina di moduli: livelli, badge, percorsi
completati, diplomi, premio di fine anno, prenotazioni corsi, promemoria,
messaggi, risposte dell'amministrazione, esiti di ricette/testimonianze/
biografie, scadenza abbonamento, esito iscrizione, digest settimanale) ora
passano da un unico punto, gs_mail_progetto(), che rispetta queste
preferenze. Le email verso gestori, collaboratori ed esperti (non verso le
sfogline) restano email dirette come sempre, senza passare dalle
preferenze.

Corretto anche, in diversi testi di email e messaggi già esistenti, un
linguaggio che dava per scontato il genere femminile (es. "Brava!", "sei
arrivata", "esserti prenotata") — nel progetto ci sono anche uomini.

= 3.84.0 =
Nuovo nel digest settimanale via email: "sei quasi arrivata". Se a una
sfoglina manca una sola lezione per finire il Percorso Guidato che sta
facendo (e quindi sbloccare il successivo), il digest lo evidenzia con il
titolo del percorso e il nome della lezione mancante, invece di lasciare
che se ne accorga da sola aprendo il sito. L'avviso arriva anche nelle
settimane senza altre novità (nuove ricette, lezioni, corsi). Nuovo helper
gs_percorso_quasi_completo_per() in percorsi-lezioni.php. 6 nuovi test.

Con questa consegna si conclude la serie di 4 idee proposte il 22/07/2026
dopo i Percorsi Guidati (bacheca di riepilogo, certificato di percorso,
vetrina pubblica, e ora questo digest).

= 3.83.0 =
Nuovo: vetrina pubblica dei Percorsi Guidati — shortcode [gs_percorsi_pubblici],
pagina pubblica come la Vetrina, il Registro Ufficiale e gli Ultimi Traguardi
(senza login richiesto). Mostra la mappa del percorso di formazione
dell'Accademia — titolo, numero di lezioni e descrizione di ogni tappa, in
ordine — anche a chi non si è ancora iscritto, a scopo promozionale, senza però
rivelare i titoli delle lezioni vere e proprie (riservate a chi ha
l'abbonamento). Se la pagina di Iscrizione è configurata, mostra anche un
pulsante per iscriversi. 10 nuovi test.

= 3.82.0 =
Nuovo: Certificato di Percorso Guidato. Quando una sfoglina completa
tutte le lezioni di un Percorso Guidato, nell'elenco compare un pulsante
"Vedi / stampa il certificato" che apre un attestato stampabile con lo
stesso stile (cornice dorata, pergamena, sigillo) del diploma già
esistente dell'Area Professionale — nome della sfoglina, titolo del
percorso e data del completamento. Per riuscire a riusare esattamente lo
stesso stile, lo stile del diploma è stato estratto in una funzione
condivisa (gs_certificato_css() in helpers.php); il diploma
dell'Area Professionale continua a funzionare come prima, solo con lo
stile preso dalla funzione comune invece che ripetuto nel file. 5 nuovi
test.

= 3.81.0 =
Ampliata la Bacheca di riepilogo (i numeri in cima al Pannello di
Controllo) con tre voci che mancavano: testimonianze in attesa, biografie
della Vetrina in attesa, risposte alle lezioni video ancora senza un tuo
riscontro — cliccabili come le altre, portano dritte alla sezione giusta.
Corretto per strada un bug preesistente e piuttosto serio: le schede
della Bacheca SENZA nulla in sospeso (es. "Sfoglie di oggi", "Corsi
attivi", o qualunque altra a zero) risultavano bianco-su-bianco,
completamente illeggibili — una regola successiva sovrascriveva lo sfondo
scuro proprio della scheda. Le sole schede "in allarme" restavano
leggibili per puro caso, perché la loro animazione lampeggiante
ridefinisce lo sfondo da sola. Bug presente da prima di questa modifica,
non introdotto ora — trovato verificando le nuove schede con dati reali,
corretto per tutte. 8 nuovi test.

= 3.80.0 =
Corretto: il pannello "Messaggi di ogni sfoglina" prometteva di mostrare
anche le conversazioni private di ogni sfoglina, ma non lo faceva
davvero — ora le mostra (in sola lettura). Aggiunto anche un nuovo
pannello "Conversazioni di ogni collaboratore" (Rina Poletti, Bruno
Cingolani...): il titolare può leggere tutte le loro conversazioni
private con le sfogline, da un unico punto. Entrambi visibili solo al
titolare (`gs_can_manage()`): le sfogline continuano a vedere sempre e
solo le proprie conversazioni, come già garantito da `gs_conv_di_utente()`
— verificato con un nuovo test dedicato. 9 nuovi test in
test-conversazioni.php. Trovati e corretti anche due limiti del banco di
prova (non del plugin): mancava `wp_strip_all_tags()`, e `meta_query` con
più condizioni e `relation => 'OR'` veniva letta come una condizione
sola — entrambi rilevanti anche per altre funzioni già esistenti mai
testate a fondo (es. il conteggio dei messaggi non letti nelle
conversazioni).

= 3.79.0 =
Nuovo: correzione automatica delle domande di verifica sulle Lezioni
Video. Quando Rina o Bruno scrivono una domanda dal pannello, ora
scrivono anche la risposta esatta; quando la sfoglina risponde, il
sistema confronta (senza badare a maiuscole, spazi in più o punteggiatura
finale) e se corrisponde le assegna in automatico dei punti (10 di
default, configurabile in Impostazioni → Punti per azione) — la sfoglina
vede subito "✅ esatta" o "❌ non esatta, riprova" sotto ogni domanda. Il
riscontro personale del maestro resta comunque disponibile in più, non è
sostituito dalla correzione automatica. Una domanda senza risposta esatta
impostata non assegna mai punti (e lo dice chiaramente nel pannello). I
punti non si riassegnano se la sfoglina rimanda la stessa risposta
corretta. Regola generale salvata per il resto del programma: ogni volta
che un docente scrive una "domanda" in un modulo, deve poter indicare
anche la risposta esatta con lo stesso meccanismo. 17 nuovi test.

= 3.78.0 =
Nuovo: propedeuticità nei Percorsi Guidati ("una vera scuola... partendo
dal basso"). I percorsi ora hanno un ordine esplicito (frecce ▲▼ nel
pannello per riordinarli): il primo è sempre accessibile, ogni successivo
si sblocca solo quando la sfoglina ha completato quello immediatamente
precedente. Finché un percorso è bloccato, le sue lezioni non si possono
aprire dalla Libreria Video (mostrano "🔒" al posto del video) — controllo
anche lato server, non solo nell'interfaccia, per evitare aggiramenti.
Una lezione che appartiene anche a un percorso già sbloccato resta
comunque accessibile. Corretto anche un problema di contrasto: un testo
".gs-hint" scritto direttamente in una scheda (fuori da un blocco a
scomparsa, come il nuovo avviso "lezione bloccata") restava scuro e poco
leggibile sullo sfondo rosso mattone. 11 nuovi test.

= 3.77.0 =
Nuovo: "Percorsi Guidati" (nuovo file includes/percorsi-lezioni.php, nuovo
CPT gs_percorso_lezioni). Il titolare raggruppa più lezioni video in un
percorso in sequenza (es. "Dalla sfoglia ai tortellini"); la sfoglina vede
i percorsi in cima alla Libreria Video, con il progresso ("2 di 3 lezioni
viste") e l'elenco delle lezioni con segno di spunta. Vederle tutte
sblocca un badge dedicato a quel percorso (chiave dinamica, stesso
meccanismo del badge "Corso con Rina Poletti" già esistente) più 30 punti
(configurabile). Estesa anche la visualizzazione badge in "Il Tuo
Percorso" e "Ultimi Traguardi" per mostrare correttamente questi badge
dinamici, che prima venivano ignorati in quei due punti. 23 nuovi test in
test-percorsi-lezioni.php.

= 3.76.0 =
Nuovo: tabella "📊 Statistiche" nel pannello delle Lezioni Video, sopra
l'elenco delle lezioni pubblicate. Per ogni lezione: quante sfogline
l'hanno vista, quante hanno risposto alle domande di verifica, quante
risposte aspettano ancora un riscontro — le righe più urgenti (più
risposte da leggere) stanno in cima, per capire subito dove serve
attenzione. Nessun dato nuovo salvato: solo aggregazione di quello che il
plugin registra già. 9 nuovi test (62 totali sulle lezioni). Corretto
anche un warning PHP latente
nel banco di prova (non nel plugin): "Array to string conversion" quando
si contava l'esistenza di un meta che contiene un array (es.
gs_lezioni_viste) — il banco di prova provava a convertirlo in stringa
invece di controllare se l'array fosse vuoto.

= 3.75.0 =
Nuovo: lezioni video consigliate. Il titolare può consigliare una lezione a
una sfoglina specifica dal pannello; se non la vede entro qualche giorno
(3 di default, configurabile in Impostazioni → Lezioni Video), le arriva
un promemoria automatico via email e come avviso in-app — stesso
meccanismo già usato per i promemoria dei corsi in Calendario. Sulla scheda
della lezione, la sfoglina vede "📌 Consigliata per te" finché non l'apre.
15 nuovi test in test-lezioni.php (53 totali).

= 3.74.0 =
Nuovo: domande di verifica sotto ogni lezione video. Aprire una lezione dà
ora pochi punti (5 di default, configurabile in Impostazioni → Punti per
azione), proprio perché non c'è modo di verificare che il video sia stato
guardato per intero. La verifica vera sono le domande: il titolare (Rina
Poletti, Bruno Cingolani) le scrive dal pannello per ogni lezione, le
sfogline rispondono con parole loro subito sotto al video, e le risposte
restano lì per un riscontro del maestro (via email e visibile nella
lezione) — nessun punteggio automatico sulle risposte, la valutazione
resta umana. Rinominata anche la sezione "Il Registro Ufficiale" in
"Il Registro Ufficiale dell'Accademia della Sfoglia" in tutti i punti in
cui compare (linguetta, titolo pagina, pannello sezioni). Corretto anche
un problema di contrasto strutturale: dentro il riquadro scuro, `<label>`,
`<strong>` e i pulsanti restavano verdi (illeggibili) ogni volta che il box
che li conteneva aveva anche una galleria di schede (Ricettario, Lezioni
Video…) — corretto alla radice per tutti i riquadri scuri del plugin, non
solo per le lezioni. 17 nuovi test in test-lezioni.php (38 totali).

= 3.73.1 =
Rifinitura/controllo (nessuna funzione nuova). Corretto un problema serio:
sei pulsanti "Elimina" del pannello cancellavano definitivamente il
contenuto (`wp_delete_post` con cancellazione forzata) invece di spostarlo
nel cestino, in violazione della regola del progetto "mai la cancellazione
definitiva" — riguardava conversazioni private, domande e canali
del­l'Esperto, corsi dell'Area Professionale e corsi del Calendario.
Ora tutti passano dal cestino di WordPress (recuperabili); aggiornati anche
i testi di conferma che parlavano ancora di "operazione definitiva".
Aggiunta anche una protezione mancante contro "autore assente" nella
lettura del Diario (stesso tipo di guardia già presente altrove nel
plugin, per coerenza). Nessun'altra area del plugin ha mostrato problemi
nello stesso controllo (nonce/permessi sugli handler AJAX, somma corretta
di acconto/saldo, uso di `get_users( fields => ID )`).

= 3.73.0 =
Nuovo: "Il Tuo Anno in Accademia" (nuova sezione/linguetta, nuovo file
includes/riepilogo-anno.php). Un racconto personale — non una classifica —
di quello che la sfoglina ha fatto nell'anno solare in corso: punti
guadagnati (con confronto sull'anno precedente), badge sbloccati, opere
pubblicate, ricette di famiglia inviate, posizione nella classifica
dell'anno e striscia attuale. Diverso da "Il Tuo Percorso" (che è la
cronologia di tutta la vita natural durante) e dal Premio di Fine Anno (che
è una classifica). Nessun dato nuovo da salvare: legge solo quello che gli
altri moduli scrivono già. 17 nuovi test in test-riepilogo-anno.php.

= 3.72.0 =
Nuovo: "Ricetta del Mese" nel Ricettario delle Famiglie. Il titolare può
scegliere, tra le ricette già approvate, quella da mettere in evidenza per il
mese corrente (pulsante "Rendi Ricetta del Mese" nel Pannello di Controllo).
Compare in cima al Ricettario pubblico con tutti i dettagli aperti (non dietro
un clic, come l'Ingrediente Segreto), e le scelte dei mesi passati restano
consultabili in un archivio a comparsa. Nessun nuovo tipo di contenuto: riusa
il CPT gs_ricetta già esistente con un meta in più (gs_mese_del_anno).
Corretta anche la mappa dell'Italia in "Classifica a Squadre": la Sardegna
aveva una strozzatura visibile a metà (causata dalla scritta stampata
sull'isola nella mappa di riferimento, che aveva bucato il contorno tracciato
automaticamente) ed è stata ricalcolata come forma unica; la Liguria, prima
spezzata in due schegge sottili, è ora un'unica fascia costiera piena.

= 3.71.0 =
Nuovo: mappa dell'Italia in "Classifica a Squadre" (nuovo file
includes/mappa-squadre.php), con tutte le regioni colorate singolarmente.
I contorni sono ricavati da un'immagine di riferimento con un rilevamento
automatico dei confini (dati geografici, non protetti da copyright), poi
ridisegnati in questo stile — non è una copia dell'immagine originale.
Solo illustrativa: i numeri restano nella tabella già presente.

= 3.70.0 =
Nuovo: "Madrina & Allieva" — dal Pannello di Controllo puoi abbinare una
sfoglina esperta (madrina) a una principiante (allieva) per un periodo di
affiancamento. Le due, appena entra in "La Mia Sfoglia", si ritrovano un
riquadro dedicato dove aggiungere insieme piccole missioni condivise e
segnarle fatte — ogni missione completata vale qualche punto per
entrambe. Dal pannello si vede l'elenco degli abbinamenti attivi (con
quante missioni fatte) e si può concludere un abbinamento quando il
periodo di affiancamento finisce. 19 test dedicati.

= 3.69.0 =
Nuovo: "Tabellone del percorso" in "La Mia Sfoglia" — al posto della
semplice barra di avanzamento, un percorso a tappe con tutti i livelli
(non solo quello attuale e il prossimo), col tratto già fatto in oro e un
segnaposto sulla posizione esatta. Stessi dati di sempre (livello, punti,
punti mancanti al prossimo), presentati in modo diverso. Si adatta da solo
al numero di livelli configurati nel pannello, non solo ai 6 attuali.
Anteprima approvata prima di applicarla. 18 test dedicati.

= 3.68.0 =
Rifatta la pagina di stampa del diploma (Area Professionale, bottone
"Stampa / Salva come PDF"): cornice dorata con angoli decorativi, sigillo
più grande con leggero rilievo, titolo "Diploma di Sfoglina Professionista"
dentro una fascia dorata, un piccolo matterello decorativo tra il titolo e
il nome, riga firma/data in fondo (Rina Poletti — Maestra Sfoglina, e la
data del diploma). Anteprima approvata prima di applicarla. Nessun cambio
di dati o di logica, solo l'aspetto della pagina stampata.

= 3.67.2 =
Corretta anche l'etichetta sopra il nome nelle schedine ("SFOGLINA",
"SFOGLINA NOVELLA"...), rimasta verde per la stessa causa della riga di
statistiche corretta in 3.67.1 — un'altra regola generica vinceva su
quella bianca dedicata. Controllate tutte le altre etichette delle
schedine: non ce ne sono altre con lo stesso problema.

= 3.67.1 =
Corretto: la riga di statistiche in fondo alle schedine (🔥 settimane ·
🎖️ badge · punti — in "Le Sfogline" e ovunque compaia questa scheda)
restava verde invece che bianca. Causa: una regola più vecchia con più
specificità vinceva su quella bianca, nonostante fosse scritta prima nel
foglio di stile — stesso tipo di errore corretto più volte in questi
giorni, stavolta su un punto rimasto scoperto.

= 3.67.0 =
Le schedine della Bacheca di Riepilogo (Pannello di Controllo) ora sono
rosso mattone con testo bianco, come tutte le altre schedine del sito
(Le Sfogline, Ricettario, Badge, ecc.) — prima erano bianche. Il bordo
colorato a sinistra resta, per continuare a distinguere a colpo d'occhio
le voci in allarme.

= 3.66.0 =
Corretto: "Le Mie Sfide" e "Il Mio Cestino" potevano mostrare articoli veri
del sito (dello stesso autore) invece dei soli contenuti di gioco (Sfoglia,
Diario, Consiglio) — causa: il tema (o un altro plugin) altera le query
tramite "pre_get_posts", un aggancio di WordPress che non viene bloccato
da "suppress_filters" nemmeno quando è attivo. Stesso identico bug già
capitato mesi fa con il Ricettario, dove era stato aggiunto un controllo
di sicurezza in più (gs_solo_tipo, in helpers.php) — controllo che però
non era mai stato messo anche qui. Aggiunto ora. Effetto pratico: quegli
articoli veri non compariranno più nell'elenco, e "Cestina" (che già li
rifiutava correttamente, da cui il messaggio "non riesco a eliminare")
tornerà a funzionare su tutto quello che resta in elenco, perché sarà
sempre e solo contenuto di gioco vero. 9 nuovi test dedicati.

= 3.65.1 =
"Come funziona questa sezione" in cima a "La Mia Sfoglia" ora menziona
anche "Prossimo passo" (aggiunto in 3.64.0). Da qui in avanti, ogni nuova
aggiunta visibile aggiorna anche il testo di aiuto della sezione toccata,
se serve.

= 3.65.0 =
Nuovo: "Ultimi Traguardi" — nuovo shortcode [gs_ultimi_traguardi], pagina
pubblica (come la Vetrina e il Registro Ufficiale) che mostra un elenco dei
risultati più recenti di tutta l'Accademia: diplomi conseguiti e badge
sbloccati, i più recenti in cima. Diversa dalla Vetrina esistente, che
mostra una sola sfoglina alla volta. Per i badge è stata aggiunta la data
di sblocco (prima non veniva registrata, solo il possesso). Aggiunti 17
test automatici dedicati. Va aggiunta a mano a una pagina dal pannello
WordPress, come già fatto per gli altri shortcode del Registro.

= 3.64.0 =
Nuovo: "Prossimo passo suggerito" in cima a "La Mia Sfoglia" (dentro il
profilo, prima del messaggio di benvenuto). Un piccolo suggerimento su
cosa fare subito dopo, in ordine di priorità: missioni di oggi non
completate, un compito del Corso Professionale da consegnare, quanto manca
al livello successivo (se vicino), un corso in calendario da prenotare (se
non se n'è mai prenotato uno), oppure un incoraggiamento generico. Non usa
dati nuovi: legge solo quello che il plugin traccia già. Aggiunti 9 test
automatici dedicati.

= 3.63.0 =
Corretto lo stesso bug di contrasto anche in "Come funziona questa
sezione" (il riquadro informativo presente in quasi tutte le sezioni del
sito): il testo era forzato bianco su un fondo chiaro proprio, quindi
illeggibile. Essendo un unico componente condiviso, la correzione vale
automaticamente ovunque questo riquadro compare.

= 3.62.0 =
Il messaggio di benvenuto "un percorso di compiti documentato" ora parte
chiuso (come tutti gli altri messaggi del sito) e nel titolo ha un piccolo
"— clicca per leggere" per far capire che si apre al clic. Aggiunta una
freccina (▸/▾) a tutti i titoli cliccabili che ne erano privi in giro per
il plugin, così si vede subito che si possono aprire: le liste "a
fisarmonica" riusate ovunque (posta interna, ricette e testimonianze
inviate, messaggi di aiuto, biografia, diario, consigli, domande
all'esperto…), "Come funziona questa sezione" e "Scrivi in privato".

= 3.61.0 =
Controllo esteso a tutto il plugin dello stesso bug del messaggio di
benvenuto (3.60.0): trovati altri punti dove del testo con fondo chiaro
proprio finiva bianco su bianco perché dentro un riquadro/box ormai rosso.
Corretti: qualsiasi compito nell'Area Professionale (non solo quello "di
oggi" — prima la correzione copriva solo quel caso); tutte le liste "a
fisarmonica" riusate in giro per il plugin, cioè "Le tue ricette inviate",
"Le tue testimonianze inviate", "I tuoi messaggi inviati" (Aiuto e
Suggerimenti), "La tua biografia salvata", "Le tue voci" (Diario),
"Consigli della Community" e i "Messaggi con l'Accademia" dentro "Le mie
prenotazioni"; gli auguri di compleanno. Nessuna di queste sezioni era
stata segnalata come rotta, ma usano tutte lo stesso meccanismo del
messaggio di benvenuto, quindi avevano probabilmente lo stesso problema.

= 3.60.0 =
Corretto un bug di contrasto: il messaggio di benvenuto "un percorso di
compiti documentato" in cima a "La Mia Sfoglia" ha un fondo chiaro proprio
(si apre su sfondo bianco), ma essendo dentro il profilo (ora rosso) il
testo veniva forzato bianco per errore — bianco su bianco, invisibile. La
correzione precedente per lo stesso problema sul compito "di oggi", sul
riscontro della docente e sulla risposta dell'esperto (versione 3.59.0) non
aveva in realtà effetto: usava una regola CSS troppo debole, che perdeva
sempre contro quella che forza il bianco. Tutte e quattro le correzioni ora
usano la stessa "forza" della regola che vogliono correggere.

= 3.59.0 =
Riquadro scuro esteso a molte altre sezioni (Le tue ricette approvate,
Lezioni video guardate, Ricettario, Dicono di Noi, Libreria Video, Tanti
auguri/Gli auguri per il compleanno, Le mie prenotazioni, Galleria delle
Sfogline, Diario, Consigli, Guida Stagionale, Vetrina, Corso Professionale
di Sfoglia, i canali de L'Esperto Risponde). Titolo incassato ora uniforme
anche nelle sezioni con galleria di schede proprie (prima restavano con il
vecchio titolo avorio). Corretti due punti dove il testo bianco sarebbe
finito su uno sfondo chiaro (il compito "di oggi", il riscontro della
docente, la risposta evidenziata dell'esperto): lì il testo resta scuro.
La messaggistica (posta interna, conversazioni, messaggi) resta esclusa,
come da regola di progetto.

= 3.58.0 =
Stesso riquadro scuro anche in "Aiuto e Suggerimenti": il modulo per
scrivere (tipo di messaggio, testo, invia) e l'elenco dei messaggi già
inviati sono ora dentro il riquadro. Il testo introduttivo sopra resta
fuori, come nelle altre sezioni.

= 3.57.0 =
Stesso riquadro scuro anche nel messaggio di benvenuto ("Benvenuta/o! I tuoi
primi passi") che compare ai nuovi iscritti: il titolo, il testo e l'elenco
dei primi passi sono ora dentro il riquadro. La "✕" per chiuderlo resta
dov'era, sopra il riquadro.

= 3.56.0 =
Stesso riquadro scuro esteso ad altre due zone di "La Mia Sfoglia":
1. Il profilo in cima alla pagina (foto, nome — es. "Ennio Barbieri",
   livello, squadra) è ora dentro il riquadro scuro, al posto di un titolo
   che lì non c'era.
2. "La Mia Biografia": stato attuale, foto, biografia salvata e il modulo
   di modifica sono ora dentro lo stesso riquadro.

= 3.55.0 =
"La Mia Sfoglia": "🎯 Missioni di oggi" aveva lo stesso problema già
sistemato in "Le Cose da Fare" — l'elenco delle missioni era appoggiato
direttamente sul rosso del riquadro, senza un suo spazio. Ora è dentro lo
stesso riquadro scuro usato per "Le Cose da Fare", stessa forma.

= 3.54.0 =
Filo sottile dorato (#d4a017) intorno al bordo di tutti i riquadri di
sezione (Classifica Generale, Classifica a Squadre, Le Cose da Fare, Diario,
Consigli, Il Tuo Percorso, Badge, ecc.), confermato sull'esempio mostrato
dal committente. Dentro il riquadro non cambia nulla — titolo, box aiuto e
tabelle restano come nella 3.53.0.

= 3.53.0 =
"Le Cose da Fare": il modulo "Aggiungi un promemoria" e l'elenco delle cose
da fare sono ora dentro un riquadro identico al titolo (stesso marrone
scuro, stessi bordi arrotondati), come già in "Classifica a Squadre".

= 3.52.0 =
Ripartiti dalla base 3.49.0 (le versioni 3.50 e 3.51 sono state scartate su
richiesta del committente). Corretta la fascia titolo dei riquadri rossi
(es. "Le Cose da Fare", "Classifica a Squadre"): prima era incollata al
bordo superiore del riquadro; ora è un riquadro arrotondato più scuro,
staccato dai bordi, confermato sull'esempio mostrato dal committente.

= 3.49.0 =
Il rosso mattone, prima solo sulle schede e su "La Mia Sfoglia", è ora esteso
a tutti i riquadri di tutte le sezioni raggiungibili dal menu delle sfogline
(Classifica, Diario, Consigli, Guida Stagionale, Il Tuo Percorso, Badge,
Compleanni, Newsletter, Iscrizione, Area Professionale, Vetrina, Il Registro
Ufficiale...). Restano come prima, di proposito:
- la posta interna e i messaggi (non si toccano, per regola di progetto);
- il Pannello di Controllo dei gestori (resta uno strumento di lavoro, non
  una sezione "di gioco");
- i riquadri che contengono già una galleria di schede proprie (Le Sfogline,
  Ricettario, Lezioni Video), che restano trasparenti attorno alle schede
  già rosse, per non avere un doppio bordo rosso su rosso.

= 3.48.0 =
Sulle schede rosse restavano scure alcune scritte non coperte dalla prima
passata: il titolo e la descrizione dei badge, la scritta "Da sbloccare",
il testo dentro le parti a scomparsa delle schede (📖 Leggi la ricetta
completa, la presentazione dei corsi, la descrizione delle lezioni video) e
"Accedi per prenotare" nelle schede del calendario. Ora sono bianche anche
loro.

= 3.47.0 =
Nella pagina "La Mia Sfoglia", le tabelle di "Le Mie Sfide" e "Il Mio
Cestino" restavano bianche in mezzo al riquadro rosso. Ora sono dello
stesso rosso scuro già usato per gli altri accenti (testata delle schede,
fascia del titolo), testo bianco, invece del bianco che spiccava al centro.

= 3.46.0 =
Tornato al rosso mattone (#a8574a) al posto del miele dorato provato nella
3.45.0: è la scelta che il committente tiene come base per i prossimi
aggiornamenti. Nessun'altra modifica.

= 3.45.0 =
Il rosso mattone provato finora è sostituito dal miele dorato (#f0dfae,
bordo #c99a2e) su schede, "La Mia Sfoglia", rollover dei bottoni, menu delle
sfogline e paginazione. Essendo uno sfondo chiaro, i testi tornano agli
stessi colori già usati sulle schede avorio (niente più bianco forzato).

= 3.44.0 =
Le tre proposte approvate:

1. Nuova sezione pubblica "Il Registro Ufficiale", con dentro "Gli Allievi
   dell'Accademia": solo chi ha frequentato i corsi con Rina Poletti fino
   alla Laurea in Sfoglia (il diploma di Area Professionale). "Le Sfogline"
   resta com'era, con tutte le iscritte al sito — sono due elenchi distinti,
   con criteri diversi. Pagina pubblica, come la Vetrina, senza bisogno di
   accesso.
2. Quando un docente assegna il diploma, la sfoglina riceve un avviso in
   bacheca e un'email, invece di scoprirlo solo se va a controllare da sola.
3. In "Le Sfogline" un nuovo filtro per livello (accanto alla ricerca per
   nome) per trovare più facilmente le sfogline più esperte.

= 3.43.0 =
Il rosso acceso (#A80000) approvato in prova è sostituito dal rosso mattone
più tenue #a8574a (bordo #7a4238), in sintonia con il terracotta già usato
nel sito. Cambia solo il colore: tutto quello che era rosso (schede,
rollover dei bottoni, menu delle sfogline, paginazione, "La Mia Sfoglia")
resta esattamente dov'era, solo più smorzato.

= 3.42.0 =
"La Mia Sfoglia" (la pagina personale dopo il login): tutti i suoi riquadri
(profilo, streak, missioni, ingrediente segreto, vetrina, biografia, le mie
sfide, cestino) ora hanno sfondo rosso di prova, come nell'anteprima
approvata. Gli avvisi (abbonamento, messaggi non letti, account in attesa)
restano con il loro stile neutro, per non confonderli con un errore.

= 3.41.0 =
Nel piede delle schede rosse restava del testo verde: bottoni ("Vedi la
vetrina", "Prenota il posto", "Vota"...) e le scritte di voto/prenotazione
("Hai già votato", "Posti esauriti"). Ora sono tutti bianchi, leggibili sul
rosso.

= 3.40.0 =
Corretta la testata delle schede di Calendario Corsi (icona + data): restava
color crema perché usa una struttura diversa dalle altre schede. Ora è rossa
come il resto della scheda, icona e data in bianco. La regola del rosso
resta generale su ".gs-card": qualunque nuova scheda futura la eredita
automaticamente, senza bisogno di modifiche ulteriori.

= 3.39.0 =
Il menu laterale delle sfogline (il pulsante "Apri il menu" e le voci di
navigazione), prima marrone come il logo, è ora del rosso di prova
(#A80000, rollover più scuro).

= 3.38.0 =
I pulsanti marroni della paginazione (‹ › e "Vedi tutti", in fondo a ogni
elenco — comprese Le Sfogline) sono ora del rosso di prova (#A80000).

= 3.37.0 =
Il rosso di prova (#A80000) sostituisce anche il rosso usato finora nel
rollover dei bottoni — sul sito (incluso il pannello di controllo, che vive
nella stessa pagina) e nella "X" di chiusura dei popup.

= 3.36.0 =
Il rosso scuro (#A80000, testo bianco) provato su "Le Sfogline" è ora esteso
a tutte le schede del sito (ricettario, lezioni, corsi, badge, ecc.). I
riquadri di sezione restano in bianco avorio, la messaggistica non è toccata.

= 3.35.0 =
Prova: sfondo rosso scuro (#A80000) sulle schede della sezione "Le Sfogline"
(testo in bianco per la leggibilità), su richiesta del committente. Non
tocca nessun'altra sezione né la messaggistica.

= 3.34.4 =
Stessa ombreggiatura laterale anche sulla fascia "ℹ️ Come funziona questa
sezione", subito sotto il titolo di ogni riquadro.

= 3.34.3 =
Tutte le intestazioni dei riquadri del plugin (es. "📖 Il Ricettario di
Famiglia", "✏️ Invia la tua ricetta di famiglia" — la fascia colorata in
cima a ogni sezione) hanno ora la stessa ombreggiatura laterale già usata
su menu e footer del sito. Riguarda ogni sezione del plugin, non solo
alcune.

= 3.34.2 =
Nel pannello del gestore (Area Professionale → dettaglio corso), il titolo
"Piano di studio ufficiale (fino alla Laurea in Sfoglia)" ora è cliccabile:
apre l'elenco completo delle 31 azioni del percorso, raggruppate per grado
(1-6) più l'Esame di Laurea, con le verifiche/esami segnalati con 🎓 — utile
per vedere tutto il contenuto del piano prima di caricarlo in un corso.

= 3.34.1 =
Nel pannello del gestore (Area Professionale → dettaglio corso), l'elenco dei
compiti del Piano di studio ufficiale ora scorre dal Grado 1 al Grado 6 fino
alla Laurea andando verso il basso (prima era ordinato al contrario, dal più
recente al più vecchio).

= 3.34.0 =
In ogni sezione rivolta alle sfogline (23 in tutto) è comparso un riquadro
"ℹ️ Come funziona questa sezione", chiuso di default (titolo cliccabile,
come tutto il resto del plugin): spiega in poche righe come muoversi in
quella sezione. Nessuna nuova pagina: è lo stesso contenuto della guida
scritta, portato dentro il sito.

= 3.33.0 =
Nuova sezione pubblica "Dicono di Noi": le sfogline scrivono una
testimonianza dal proprio pannello, il titolare la approva, poi compare
pubblicamente anche a chi visita il sito senza essere registrato (solo il
modulo per scriverne una nuova richiede l'accesso). Stesso meccanismo di
approvazione già usato per il Ricettario delle Famiglie, con il suo cestino
recuperabile nel Pannello di Controllo.

= 3.32.0 =
Sei funzioni nuove:
- Badge "Voce di Famiglia" (prima ricetta di famiglia approvata) e "Esploratrice
  delle Lezioni" (5 lezioni video guardate).
- Avviso email quando l'abbonamento sta per scadere (data di scadenza
  facoltativa nel pannello Abbonamenti, promemoria 7 giorni prima, un solo
  invio per data).
- Promemoria email il giorno prima di un corso prenotato (Calendario Corsi).
- Nuova sezione "Il Tuo Percorso": cronologia personale della sfoglina
  (livello, badge, punti, ricette approvate, lezioni guardate).
- Nuova sezione "Cerca in tutto il sito": ricerca unica su Ricettario,
  Lezioni Video e domande a "L'Esperto Risponde".
- Digest settimanale via email con le novità (nuove ricette, nuove lezioni,
  corsi in arrivo), inviato solo se c'è davvero qualcosa di nuovo.

= 3.31.1 =
Tolta la scheda "Sfogline approvate" dalla bacheca di riepilogo del Pannello
di Controllo.

= 3.31.0 =
Nuova sezione: Libreria Video delle Lezioni di Rina. Il titolare carica una
lezione (titolo, tecnica, link YouTube o Vimeo, descrizione) direttamente dal
pannello — nessuna approvazione, il video resta ospitato su YouTube/Vimeo
(niente file caricati sul sito, per non pesare sullo spazio hosting). Sezione
di livello "superiore": visibile solo alle sfogline con abbonamento attivo,
come Calendario Corsi. Ogni lezione ha titolo cliccabile con lettore video
incorporato, ricercabile per nome o tecnica.

= 3.30.1 =
Schede della bacheca di riepilogo (Pannello di Controllo) più piccole, per
farle stare tutte sulla stessa riga invece di andare a capo lasciando
"Sfogline approvate" da sola in una riga a parte.

= 3.30.0 =
Rollover verde anche su: Salva (compito, compleanni, canali, backup, aspetto
"Le Sfogline", permessi conversazioni), Salva impostazioni (Calendario),
Invia messaggio, Mostra (Premio di Fine Anno), Aggiungi al menu, Salva
visibilità e permessi.

= 3.29.0 =
Include il fix del rollover verde su Ripristina/Riattiva della consegna
precedente. Nuovo: il pulsante verde "Sezioni" a sinistra ora ha esattamente
la stessa dimensione e struttura (icona + etichetta) del pulsante marrone
"Menu" a destra — prima erano leggermente diversi.

= 3.28.2 =
Il pulsante "Ripristina" (Aiuto, Posta interna, Ricettario, Il Mio Cestino
della sfoglina) ora è verde, per distinguerlo a colpo d'occhio dagli altri
pulsanti e dal rosso della zona Cestino.

= 3.28.1 =
Reso più brillante il giallo del menu "Sezioni" e dell'hover del menu "Menu"
(da #F2C230 a #FFC72C) — la volta scorsa avevo migliorato solo grassetto e
dimensione del testo, non il colore stesso.

= 3.28.0 =
Tutte le sezioni "Cestino" del plugin (Aiuto, Ricettario, Posta interna, Il
Mio Cestino della sfoglina, il cestino nella ricerca sfoglina) ora hanno il
titolo in rosso su una fascia con bordo rosso, per farle riconoscere subito
come zona di eliminazione — stesso trattamento ovunque, non solo in una
sezione.

= 3.27.0 =
"L'Esperto Risponde" ora usa lo stesso metodo del resto del plugin: ogni
domanda è un titolo cliccabile (anteprima della domanda, autrice, data,
numero di risposte) invece di stare sempre tutta aperta — con tante domande
diventava una pagina lunghissima da scorrere. Aggiunta anche la ricerca nelle
domande, richiesta per lo stesso motivo. La funzione di ricerca generica del
plugin ora funziona anche su questo tipo di elenchi (prima funzionava solo su
tabelle e schede), quindi è pronta per essere riusata ovunque serva in futuro.

= 3.26.0 =
Tolta la riga di avviso ridondante nel Ricettario (restava solo quella nuova,
più completa, sui maestri e il libro). Migliorata la leggibilità delle scritte
nel menu verde "Sezioni" (più grosse, in grassetto, con leggera ombra per
staccarsi meglio dallo sfondo). Il Ricettario delle Famiglie ora compare anche
nella bacheca di riepilogo in cima al Pannello di Controllo ("Ricette in
attesa", lampeggia e porta dritti alla sezione se ce ne sono). Controllato che
il titolo cliccabile per ogni elemento inviato sia applicato coerentemente
anche nelle altre sezioni (Calendario, Conversazioni, Biografie, Aiuto,
Messaggi): lo era già ovunque tranne che nella bacheca "L'Esperto Risponde",
che di proposito resta una bacheca pubblica sempre visibile (non un elenco
privato da aprire).

= 3.25.1 =
Trovata una causa probabile (segnalata dal committente) dei contenuti sbagliati
comparsi in passato nel Ricettario: più moduli del plugin usano lo stesso nome
di campo ("titolo", "nome", "oggetto") in form diversi — Diario, Consigli,
Ricette, Messaggi, Corsi… — e i browser possono suggerire in automatico un
valore digitato in un modulo dentro il campo di un altro modulo completamente
diverso, solo perché il nome del campo coincide. Disattivato il completamento
automatico del browser su tutti questi campi (Diario, Consigli, Ricettario,
Messaggi, Newsletter, Corsi, canali Esperto, messaggio di benvenuto): da ora
ogni campo si presenta sempre vuoto, senza suggerimenti incrociati.

= 3.25.0 =
Ricettario: nel menu della sfoglina ora si chiama "Le tue ricette di famiglia",
nella sezione pubblica "Il Ricettario di Famiglia". Il campo "Provenienza" ora
raccoglie regione E nazione (prima erano mescolate nel campo "Chi te l'ha
tramandata", che ora è solo su chi/famiglia). Aggiunto l'avviso richiesto nella
sezione di invio: le ricette vengono vagliate dai maestri, quelle approvate
finiranno in un libro con la fonte citata. Aggiunto un pulsante nel pannello
per svuotare in un colpo solo tutte le ricette in attesa (utile per ripulire
in fretta le voci di prova/sbagliate, invece di scartarle una per una).

= 3.24.0 =
Corretto un bug reale: nel Ricettario ("Le tue ricette inviate") comparivano
contenuti del sito (articoli, pagine) mescolati alle vere ricette — lo stesso
sospetto mai risolto per "Aiuto e Suggerimenti". Aggiunto un controllo di
sicurezza che tiene solo i contenuti davvero del tipo giusto in tutti gli
elenchi coinvolti (Ricettario, Aiuto, Messaggi, "Messaggi di ogni sfoglina").
Nuovo test automatico che riproduce esattamente il caso segnalato (un
articolo e una pagina mescolati a una ricetta vera) per evitare che torni.

= 3.23.1 =
Il pannello "Sezioni" a sinistra ora si chiude cliccando fuori (sulla pagina)
o con Esc, esattamente come già faceva il menu marrone "Menu" a destra: prima
gli mancava del tutto questa gestione, restava aperto finché non si ricliccava
il pulsante. Verificato anche il colore verde/giallo dei suoi link: il codice
è corretto ed è quello nello zip, sia sul mio archivio sia in quello appena
consegnato — se sul sito appare ancora diverso è quasi certamente cache (SG +
tema) da pulire, non una modifica mancante.

= 3.23.0 =
Nuova sezione: Ricettario delle Famiglie. Ogni sfoglina invia dal proprio
pannello una ricetta tramandata (nome, regione, chi l'ha tramandata,
ingredienti, procedimento, la storia dietro alla ricetta, una foto); resta in
attesa finché il titolare non la approva — stesso meccanismo già usato per la
Biografia della Vetrina. Le ricette approvate compongono un archivio pubblico
cercabile per nome, regione o famiglia, con la ricetta completa dietro un
titolo cliccabile (stesso "Leggi tutto" già usato nel Calendario Corsi). Il
titolare gestisce approvazione/rifiuto e un cestino (recupero incluso) sia dal
pannello sul sito sia dalla Plancia di WordPress.

= 3.22.1 =
Inserito il marchio registrato dell'Accademia della Sfoglia — Rina Poletti nel
Diploma (ridotto da 1 MB a 284 KB senza perdita visibile di qualità, per non
appesantire il caricamento delle pagine). Il diploma ora è completo, sigillo
compreso, sia nel riquadro della sfoglina sia nella pagina da stampare.

= 3.22.0 =
Nuovo: Diploma di Rina Poletti (marchio registrato). In Area Professionale, per
ogni corso individuale, il titolare può assegnare (o revocare) manualmente il
diploma dal pannello — nessun automatismo. Quando assegnato, la sfoglina vede
un riquadro dedicato nella sua pagina e può aprire/stampare un diploma in una
pagina dedicata (nome, data, marchio). MANCA ANCORA il file dell'immagine del
marchio (va inserito in gaming-sfogline/assets/img/diploma-rina.png): finché
non c'è, il diploma funziona lo stesso ma senza l'immagine del sigillo.

= 3.21.0 =
Calendario Corsi: nella schedina di ogni corso, la presentazione (prima
tagliata sempre a 40 parole) ora ha un titolo cliccabile "📖 Leggi la
presentazione completa" — la sfoglina clicca e legge il testo per intero,
senza che le informazioni di prenotazione (data, posti, prezzo, pulsante)
spariscano o si spostino.

= 3.20.1 =
Corretta la causa vera per cui la "fisarmonica" non funzionava: l'evento del
browser che segnala apertura/chiusura di un titolo cliccabile non risale il
DOM (non fa "bubbling"), quindi il modo in cui era agganciata la versione
3.20.0 non si attivava mai — provato con una pagina di prova dedicata prima di
correggere. Ora l'ascolto è fatto nel modo giusto (in fase di cattura) e
funziona davvero, ovunque nel plugin si usino i titoli cliccabili. Corretto
anche un problema gemello, più vecchio: il "segna come letto" della posta
interna quando si apre un messaggio aveva lo stesso difetto e probabilmente
non ha mai funzionato.

= 3.20.0 =
"Fisarmonica" sui titoli cliccabili: aprendo un messaggio (in posta interna,
segreteria, aiuto, conversazioni, calendario, diario, consigli — ovunque si
clicca un titolo per leggerlo) quello precedentemente aperto nella stessa
lista si chiude da solo, invece di restare aperto insieme al nuovo.

= 3.19.1 =
Corretto un bug reale in "Visibilità delle sezioni e permessi": la sezione
"Aiuto e Suggerimenti" non ha una sua pagina dedicata, quindi il controllo
automatico che nasconde le altre sezioni non la intercettava mai — nasconderla
(globalmente o a una sfoglina specifica) non aveva alcun effetto, il riquadro
restava sempre visibile in fondo alla bacheca della sfoglina. Ora rispetta
l'impostazione come tutte le altre. Tutto il resto del meccanismo (nascondi
globalmente, nascondi solo ad alcune sfogline, permessi collaboratori) è stato
verificato con dati reali ed era già corretto.

= 3.19.0 =
Indice delle sezioni (bottone verde a sinistra): le voci ora sono "schedine"
staccate e arrotondate, identiche come forma alle linguette del menu di destra
(bordi arrotondati, ombra, spaziatura tra una e l'altra). Tolta la scritta
"Sezioni del pannello" in cima: come nel menu di destra, le schedine iniziano
già dall'alto appena si apre il pannello, senza intestazione che le spinge giù.

= 3.18.2 =
Indice delle sezioni (bottone verde a sinistra): stato normale dei link invertito
su richiesta, ora sfondo verde con scritte gialle (prima era scritte verdi su
sfondo bianco); il passaggio del mouse resta come nella versione precedente
(sfondo giallo, testo scuro).

= 3.18.1 =
Indice delle sezioni (bottone verde a sinistra): i link interni ora diventano
gialli al passaggio del mouse, stesso giallo e stesso testo scuro leggibile dei
bottoni del menu a destra (invece del beige chiaro di prima).

= 3.18.0 =
Anche le due schede della sezione "Statistiche" del Pannello di Controllo,
"Sfogline registrate" e "Richieste in attesa", sono ora cliccabili con lo stesso
meccanismo: la prima porta a "Cerca sfoglina e recupera i suoi file", la seconda
a "Richieste di Iscrizione" (la stessa sezione della scheda gemella nella
bacheca di riepilogo, dato che mostrano lo stesso dato).

= 3.17.0 =
Le tre schede della bacheca di riepilogo del Pannello di Controllo che lampeggiano
(Iscrizioni da approvare, Domande senza risposta, Richieste in attesa) ora sono
cliccabili: un clic salta subito alla sezione corrispondente più sotto nella
pagina, con lo stesso salto "sicuro" già usato dall'indice laterale delle sezioni
(tiene conto della barra di WordPress e dell'intestazione appiccicata del tema).

= 3.16.0 =
L'aeroplanino non e' piu' solo per i messaggi: ora vola (sempre DUE passaggi, come
richiesto) per QUALSIASI avviso importante alla sfoglina, mentre naviga il sito:
• Nuova risposta in una conversazione con l'esperto ("NUOVA RISPOSTA NELLA CONVERSAZIONE").
• Badge sbloccato ("BADGE SBLOCCATO: [icona] [nome badge]").
• Salita di livello ("SEI SALITA DI LIVELLO: [simbolo] [nome livello]").
• Richiesta di aiuto gestita dall'amministrazione ("LA TUA RICHIESTA DI AIUTO E' STATA GESTITA").
• Prenotazione di un corso confermata ("PRENOTAZIONE CONFERMATA: [nome corso]").
• Risposta dell'esperto a una domanda pubblica ("L'ESPERTO HA RISPOSTO ALLA TUA DOMANDA").
Il messaggio dalla segreteria resta come prima. Se più avvisi arrivano insieme,
l'aeroplanino li mostra in fila uno dopo l'altro, mai sovrapposti.

= 3.15.0 =
Menu laterale destro (il pulsante "Menu" con le linguette): colore normale allineato
al marrone esatto del logo (campionato dal file immagine, #9C7236, non piu' oro
generico); al passaggio del mouse diventa giallo con testo scuro per restare leggibile.

= 3.14.0 =
Aeroplanino dei messaggi rifatto su richiesta della segreteria.
• Ora l'aereo e lo striscione sono ROSA e l'aereo e un disegno "ciccione" da cartone
  animato, visto di lato, con lo striscione trainato dietro.
• Il messaggio non compare piu solo all'avvio: il sito controlla ogni 15 secondi se
  sono arrivati messaggi nuovi, cosi l'aeroplanino appare in tempo reale mentre la
  sfoglina naviga, prima ancora che apra la sezione Messaggi.
• A ogni nuovo messaggio l'aeroplanino attraversa lo schermo DUE volte.

= 3.13.0 =
Due tocchi richiesti dalla segreteria.
• I contatori in cima al Pannello di Controllo che segnalano lavoro da fare
  (iscrizioni da approvare, domande senza risposta, richieste in attesa) ora
  lampeggiano finche restano da sistemare, e smettono da soli quando la voce
  torna a zero.
• Quando a una sfoglina arriva un nuovo messaggio, un aeroplanino attraversa lo
  schermo trainando uno striscione con scritto “MESSAGGIO IN ARRIVO”. Compare una
  volta all'apertura di una pagina, solo se ci sono messaggi nuovi rispetto
  all'ultima volta. Entrambe le animazioni rispettano l'impostazione di sistema
  “riduci animazioni”.

= 3.12.0 =
Novità nella Plancia e per le sfogline, più migliorie di velocità.
• Diagnostica e stato di salute: nuova sezione che controlla in automatico cron,
  permalink, pagine del plugin, cartella dei caricamenti ed estensioni PHP
  (ZipArchive, GD, mbstring), e un pulsante "Invia email di prova" per verificare
  subito se il server riesce a spedire (l'invio dipende dall'SMTP del server).
• Onboarding: alla nuova sfoglina approvata compare, una sola volta in cima a "La
  Mia Sfoglia", un riquadro con i primi passi per guadagnare punti. Si chiude con
  la ✕ e non riappare.
• Prestazioni: gli elenchi delle sfogline e delle richieste in attesa vengono messi
  in cache per la durata della pagina (venivano ricalcolati più volte nella Plancia),
  con invalidazione automatica quando cambia lo stato di un'iscrizione o un permesso.

= 3.11.0 =
Nuovo: esportazione dei dati di gioco. Nella Plancia, sezione «Esporta dati di gioco»,
tre pulsanti scaricano in CSV (apribile con Excel) l'elenco delle sfogline con punti,
livello, squadra, stato iscrizione e abbonamento; le prenotazioni con acconto, saldo e
totale versato; i corsi a calendario con posti e prezzi. Un quarto pulsante scarica tutto
in un unico zip. Serve per i conti di fine anno e come copia di sicurezza dei dati (che
vivono nei meta di WordPress, senza tabelle personalizzate). È un'esportazione manuale,
da affiancare al «Backup dei file».

= 3.10.3 =
Solo prestazioni, nessun cambiamento di funzionamento. Le impostazioni del plugin
(gs_settings) vengono ora messe in cache per la durata della richiesta invece di essere
ricostruite a ogni lettura; la classifica della sfida calcola la media dei voti una sola
volta per sfoglia anziché a ogni confronto in ordinamento; la sfida attiva viene calcolata
una sola volta per pagina. Verificato che tutti gli handler AJAX mantengono il controllo
del nonce e che le regole storiche (autore=0, ->ID su stringhe, accumulo dei meta) restano
rispettate.

= 3.10.2 =
Indice laterale: aggiunti 10 px di margine di sicurezza sopra il titolo della sezione, per
evitare che resti a filo del menu del sito.

= 3.10.1 =
Le pagine pubbliche Iscrizione e Newsletter ora compaiono anche nel menu laterale del
plugin. Aggiunta nel pannello la scheda "Pagine pubbliche nel menu del sito": con un
pulsante le inserisce nel menu di navigazione scelto, senza passare da Aspetto > Menu.
Non crea duplicati e, se una pagina fosse stata cancellata, la ricrea.

= 3.10.0 =
RISOLTO il problema dell'allineamento delle sezioni. La diagnostica sul sito ha rivelato la
causa: l'intestazione del tema (logo e menu) non e "fissa" ma "appiccicata" (sticky), e nelle
versioni precedenti gli elementi appiccicati erano stati esclusi dal calcolo. Cosi il codice
riservava spazio solo per la barra di WordPress (32 px) ignorando i circa 185 px di logo e
menu che coprono il titolo, e la regolazione manuale non aveva alcun effetto perche il limite
di sicurezza la annullava. Ora il calcolo tiene conto di tutte le barre che restano in cima e
coprono il contenuto, fisse o appiccicate.

= 3.9.9 =
Aggiunto un pulsante "Diagnostica" nella scheda dell'indice laterale: mostra i numeri reali
misurati dal browser (barre fisse presenti, regolazione attiva, posizione delle sezioni) e
la versione del codice effettivamente caricata, utile per capire se la cache sta servendo
file vecchi.

= 3.9.8 =
Indice laterale: allineamento predefinito calibrato sul tema (la pagina scende 120 px in
piu). Aggiunto un limite di sicurezza: qualunque regolazione si imposti, il titolo della
sezione non puo mai finire nascosto sotto le barre fisse. Cursore di regolazione sempre
disponibile nel pannello.

= 3.9.7 =
Indice laterale: corretto il calcolo dell'ingombro. Veniva conteggiata anche l'intestazione
del sito (logo e menu), che pero scorre via con la pagina: per questo su desktop la pagina
si fermava troppo in alto. Ora si contano solo le barre realmente fisse (barra di WordPress
e intestazione "appiccicata" del tema).

= 3.9.6 =
Indice laterale: allineamento regolabile. Oltre al rilevamento automatico delle barre fisse
(barra di WordPress e intestazione del tema), il pannello ha ora una scheda "Indice
laterale - allineamento delle sezioni" con un cursore per spostare il titolo piu in basso o
piu in alto, con pulsante "Prova" per vedere subito il risultato. Valore predefinito: 40 px
di spazio in piu sopra il titolo.

= 3.9.5 =
Indice laterale: risolto il salto alle sezioni sul computer (su tablet gia funzionava). Lo
scorrimento animato falsava le misure e l'intestazione fissa del tema compare solo dopo
aver iniziato a scorrere: ora il salto e immediato e la posizione viene ricontrollata e
corretta piu volte, anche se immagini o contenuti caricati dopo spostano la sezione. La
sezione raggiunta viene evidenziata brevemente.

= 3.9.4 =
Corretto il salto alle sezioni dall'indice laterale: l'intestazione della sezione finiva
nascosta sotto la barra di WordPress e sotto l'intestazione fissa del tema. Ora lo scroll
calcola l'ingombro reale delle barre fisse e si ferma appena sotto, con il titolo sempre
visibile. Vale anche per i collegamenti diretti dal menu di WordPress.

= 3.9.3 =
Pulsante "Collaboratrice" accorciato e testi che non escono piu dai pulsanti. Rimossa la
sezione "Messaggi di ogni sfoglina". Calendario: i clienti iscritti a un corso ora sono
titoli cliccabili con paginazione (e popup "Vedi tutti" per le liste lunghe); confermato
il pulsante per eliminare il corso. Menu WordPress "Gaming Sfogline": il sottomenu elenca
tutte le sezioni della Plancia con collegamento diretto. Pannello di controllo sul sito:
nuovo indice laterale a SINISTRA per saltare a ogni sezione senza scorrere (il menu delle
pagine resta a destra).

= 3.9.2 =
Corretto il warning PHP "Attempt to read property ID on string" nel pannello di controllo:
il conteggio delle sfogline approvate chiedeva a get_users() il solo campo ID, che
WordPress restituisce come elenco di ID e non come oggetti utente.

= 3.9.1 =
Corretto: la scheda di approvazione delle biografie elencava solo le sfogline non
gestori, percio la biografia dell'amministratore (o di un collaboratore) restava per
sempre "in attesa" e non compariva mai nella Vetrina. Ora la lista considera tutti gli
utenti. Aggiunto l'elenco delle biografie gia approvate con possibilita di sospenderle, e
per chi ha i permessi di gestione un pulsante per approvare subito la propria biografia
dal box "La Mia Biografia".

= 3.9.0 =
Vetrina: foto della sfoglina e biografia approvata. La sezione La Mia Biografia mostra il
testo salvato sotto un titolo cliccabile e resta sempre modificabile. La sezione Tanti
Auguri mostra la vetrina della festeggiata (foto, dati e biografia). Diario dell impasto e
Consigli della Community: ogni voce ha il titolo cliccabile e il testo modificabile (ed
eliminabile nel cestino recuperabile). Messaggi privati: gli ultimi inviati hanno titolo
cliccabile e si possono modificare o eliminare. Nuova scheda nel pannello per controllare
i messaggi di ogni sfoglina. Nel pannello (front-end e plancia) compare in alto la
versione del plugin. Pagina Le Sfogline: paginazione, dimensione schede e numero per riga
configurabili dal pannello. Paginazione aggiunta a tutti gli elenchi (pannello e sfogline),
con pulsante "Vedi tutti" che apre un popup a schermo con l elenco completo, richiudibile.
Corsi: nel pannello sono elencati con titolo cliccabile, modificabili ed eliminabili.

= 3.8.0 =
Posta interna nel pannello generale: gli avvisi e i messaggi del progetto (nuove
prenotazioni, messaggi dei clienti, blocchi corso, registrazioni, richieste di aiuto,
biografie da approvare) arrivano come posta interna condivisa tra amministratore e
collaboratori. La lista mostra solo l'oggetto; cliccando si apre il messaggio e si puo
rispondere, modificare o eliminare. Il cestino contiene SOLO i messaggi eliminati dal
progetto ed e sempre ripristinabile. Biografia della Vetrina: ogni sfoglina puo comporre
una biografia con testo, foto e video dal proprio pannello; diventa visibile nella Vetrina
pubblica solo dopo l'approvazione dal pannello generale (ogni modifica torna in attesa).

= 3.7.0 =
Abbonamento scaduto: la sfoglina accede solo al gaming pubblico di primo livello; le aree
private di livello superiore (Calendario Corsi, Area Professionale, Messaggi, Esperto,
Conversazioni) sono sospese. Stato gestibile dal pannello, con avviso in dashboard.
Messaggio di benvenuto in «La Mia Sfoglia» modificabile, disattivabile e mostrabile solo
alle sfogline scelte. Nuova pagina pubblica di Iscrizione e nuova pagina pubblica di
Iscrizione alla Newsletter, con gestione iscritti dal pannello. Calendario: intestazione
delle schede con icona calendario (senza numeri) e data del corso; messaggio di
prenotazione visibile 3 secondi; pulsanti di stato che restano evidenziati; totale
acconto+saldo ora corretto (i pagamenti si sommano). Dettatura vocale sui campi messaggio.
"Le Mie Sfide" e "Il Mio Cestino" blindati ai soli contenuti del plugin.

= 3.6.2 =
Messaggi: pulizia automatica del testo incollato dall'editor a blocchi di WordPress.
I marcatori dei blocchi (<!-- wp:paragraph -->) e i tag HTML non compaiono piu come
testo: il messaggio viene mostrato pulito e ben formattato. Vale per aiuto/suggerimenti,
conversazioni, messaggi del calendario e auguri, sia sui messaggi nuovi sia su quelli
gia salvati.

= 3.6.1 =
Menu laterale destro: il pulsante Menu e le linguette passano dal verde a un oro deciso
(non chiaro), con hover in oro piu scuro.

= 3.6.0 =
Messaggistica: ora si possono allegare foto e video in tutta la messaggistica del
progetto (conversazioni private, messaggi col calendario, auguri di compleanno, aiuto e
suggerimenti), con peso e compressione regolati dai parametri del pannello generale.
Aiuto e Suggerimenti: la sfoglina vede la lista dei propri messaggi inviati (come nel
pannello del gestore). Dashboard "La Mia Sfoglia": sotto nome e livello compare il titolo
cliccabile "Benvenuto Nell'Accademia Della Sfoglia, un percorso di compiti documentato",
che apre la presentazione del percorso.

= 3.5.0 =
Visibilita sezioni: ora puoi nascondere una sezione anche solo ad alcune sfogline
scelte per nome (oltre al visibile/invisibile globale e ai permessi collaboratori).
Nuovo helper "Aiuto e Suggerimenti" nel pannello di ogni sfoglina: puo chiedere aiuto
diretto all'amministrazione o inviare suggerimenti per migliorare sito e pubblicazioni.
Le richieste arrivano via email agli amministratori e si gestiscono dal pannello
(segna come gestito, rispondi via email).

= 3.4.0 =
Nuova funzione nel pannello: "Visibilità delle sezioni e permessi collaboratori". Per
ogni sezione puoi decidere se e visibile o nascosta sul sito (nasconde la linguetta e
blocca la pagina), e per le sezioni gestibili puoi scegliere per nome quali collaboratori
possono gestirle dal pannello. Il titolare vede sempre tutto. Solo il titolare
(manage_options) puo modificare questi permessi.

= 3.3.1 =
Menu laterale: soluzione definitiva allo scorrimento. Il pannello delle linguette e ora
agganciato allo schermo con proprieta blindate (!important) e spostato come figlio
diretto del body, cosi il tema Newspaper non puo piu annullare il position:fixed. Con
molte voci il pannello scorre al suo interno senza dover scorrere la pagina.

= 3.3.0 =
Nuova funzione: Calendario Corsi dell'Accademia della Sfoglia (Rina Poletti) con
pannello di controllo dedicato. Date con orari, posti totali e rimasti; prezzi e acconto
decisi dal pannello. Alla prenotazione parte un'email con programma e istruzioni per il
bonifico. No-show con perdita acconto; disdetta con rimborso solo entro i giorni di
preavviso impostati (default 14). Lista d'attesa a discrezione, con email automatica di
proposta data (senza nuovo acconto se gia versato). Conteggio pagamenti per cliente.
Blocco corso con motivazione inviata via email a tutti gli iscritti. Messaggi privati
cliente-Accademia. Email agli amministratori a ogni nuova registrazione. Accesso al
pannello anche ai collaboratori autorizzati. Stessa grafica del progetto. Shortcode
[gs_calendario].

= 3.2.2 =
Navigazione laterale ridisegnata: un pulsante «Menu» sul bordo destro apre un pannello
con tutte le linguette, che scorre internamente quando sono tante. Risolve il problema
delle voci che non entravano nello schermo. Si chiude cliccando fuori o con Esc.

= 3.2.1 =
Linguette laterali: con l'aumentare delle voci alcune uscivano dallo schermo. Ora sono
allineate in alto e il menu scorre (con spazio per la barra di WordPress), cosi tutte
le linguette sono sempre raggiungibili.

= 3.2.0 =
Nuova funzione "I Compleanni di Oggi". Aggiunta la data di nascita nel modulo di
iscrizione (e impostabile dalle sfogline gia iscritte). Il giorno del compleanno la
vetrina della sfoglina compare automaticamente nella pagina dedicata e resta visibile
fino al compleanno successivo; sotto la vetrina le sfogline iscritte possono lasciare
messaggi di auguri (+5 punti). Dal pannello il gestore puo oscurare o mostrare la
vetrina del compleanno e correggere le date di nascita. Stessa grafica del progetto.

= 3.1.5 =
Robustezza: aggiunto un polyfill per mbstring (mb_strlen/mb_substr) usato in "L'Esperto
Risponde", cosi il plugin non va in errore neanche su server privi dell'estensione
mbstring. Test di attivazione a freddo e test di tutte le 52 azioni AJAX superati senza
errori fatali.

= 3.1.4 =
Plancia Generale in wp-admin riportata allo stato precedente (rimosso il restyle
avorio/verde dedicato all'admin): il backend resta sobrio, il nuovo stile grafico
resta applicato a tutto il front-end. Verifica completa del progetto superata.

= 3.1.3 =
Il nuovo stile (schede avorio in rilievo, intestazioni avorio con scritte arancioni,
bottoni verdi con rollover rosso, ricerca in ombra, testi verdi) e ora applicato anche
alla Plancia Generale in wp-admin, non solo al front-end.

= 3.1.2 =
Intestazioni delle sezioni e schede in bianco avorio (non piu verdi/panna), con scritte
arancioni sulle intestazioni e ombra tridimensionale mantenuta. Bottoni verdi con
rollover rosso e ricerca in rilievo restano invariati.

= 3.1.1 =
Campo di ricerca con ombra (in rilievo). Intestazioni delle sezioni con fascia verde e
scritte in arancione (arancio prelevato dal logo, #cd8b0c). Bottoni delle schede in
verde con rollover rosso.

= 3.1.0 =
Revisione grafica: rimosso lo sfondo crema (la pagina torna al colore del tema).
Le schede (blocchi informativi, sfogline, sfide, badge) hanno ora interno bianco panna
lucido (leggero gradiente) e un'ombra ai bordi che le rende tridimensionali; le card si
sollevano al passaggio del mouse. Le sezioni con griglie restano aperte.

= 3.0.9 =
Stile scheda "Mercato della Sfoglia" esteso a tutto il progetto: galleria delle sfide
(con voto nel footer), galleria pubblica e badge ora usano la stessa scheda con testata
beige, corpo bianco, angoli arrotondati e footer. Testata sempre presente (foto o emoji).

= 3.0.8 =
Bottoni riportati in oro (outline che si riempie al passaggio). Schede in stile
"Mercato della Sfoglia": angoli arrotondati, testata beige (di colore diverso dal
corpo bianco), etichetta autore in oro maiuscolo, titolo serif e footer con linea e
azione. Testata sempre presente (foto oppure emoji del livello su fondo beige).

= 3.0.7 =
Coordinamento cromatico completo in verde: bottoni outline verdi (si riempiono al
passaggio), etichette maiuscole e intestazioni tabella in verde, titoli in un verde
piu intenso del testo per dare gerarchia.

= 3.0.6 =
Testi (titoli e testo) delle pagine del plugin nel verde scuro del logo, coordinati
con le linguette laterali; note e aiuti in un verde leggermente piu tenue.

= 3.0.5 =
Sfondo crema: agganciati i contenitori reali del layout Newspaper visti in ispezione
(.content_wrap, .content.entry, .sidebar), che portavano il fondo bianco. Ora il crema
copre la colonna dei contenuti del plugin su questo tema.

= 3.0.4 =
Sfondo crema compatibile con il tema Newspaper (tagDiv): svuotati gli sfondi bianchi
dei contenitori con prefisso td- (td-main-content-wrap, td-pb-row, tdc-row, ecc.) e
dei blocchi WPBakery/VC, cosi il crema del plugin copre tutta la pagina anche su
questo tema.

= 3.0.3 =
Linguette laterali in verde scuro, ripreso dal logo dell'Accademia della Sfoglia
(prima erano terracotta).

= 3.0.2 =
Sfondo crema reso affidabile su qualsiasi tema: le pagine del plugin sono riconosciute
per ID (non solo per shortcode), il crema viene forzato su html/body e i contenitori
piu comuni dei temi resi trasparenti, e il CSS del plugin viene caricato dopo quello
del tema. Se un tema con contenitori particolari lasciasse ancora una fascia colorata,
basta segnalare il nome del tema per aggiungere il selettore giusto.

= 3.0.1 =
Fedelta completa allo stile "Mercato della Sfoglia": lo sfondo CREMA ora copre
l'intera pagina del plugin (classe gs-page sul body), le sezioni poggiano direttamente
sul crema senza scatola bianca attorno, e in bianco restano solo card, tabelle
(ora come card con angoli arrotondati), elementi elenco e campi dei moduli.

= 3.0.0 =
Paginazione su tutte le liste dei pannelli (Le Mie Sfide, cestino, Le Sfogline,
richieste, ammissioni, vetrine, collaboratrici, canali, domande, messaggi inviati,
conversazioni): le voci si sfogliano a pagine con i pulsanti e il conteggio, e la
ricerca ricalcola le pagine. Nuovo stile grafico "Il Mercato della Sfoglia" su tutte
le pagine: fondo crema caldo, titoli in carattere serif, etichette in maiuscolo
spaziato, card bianche con bordo dorato e testata beige, bottoni outline in maiuscolo.

= 2.9.4 =
Bacheca di riepilogo in cima al pannello e alla plancia: i numeri del giorno a colpo
d'occhio (iscrizioni da approvare, domande senza risposta, richieste in attesa, sfoglie
di oggi, corsi attivi, sfogline approvate), con evidenza quando c'e qualcosa da fare.

= 2.9.3 =
Le sfogline possono avviare una conversazione privata con l'esperto di un canale.
Dal pannello scegli la modalità: No / Sì subito / Sì con la tua approvazione. In
modalità approvazione le richieste restano in attesa (non visibili all'esperto) finché
non le approvi dal pannello, dove trovi l'elenco delle richieste con Approva/Rifiuta.
Vale sempre la lista di chi ogni esperto può contattare.

= 2.9.2 =
Pallino delle domande senza risposta sulla linguetta di ogni canale (visibile
all'esperto e alla segreteria). Controllo delle conversazioni private dal pannello:
per ogni esperto decidi con quali sfogline iscritte puo parlare (lista vuota = tutte),
puoi avviare tu una conversazione tra un collaboratore e una sfoglina, e vedere/moderare
(aprire ed eliminare) tutte le conversazioni.

= 2.9.1 =
"L'Esperto Risponde": notifica email all'esperto a ogni nuova domanda nel suo canale.
Nuove conversazioni private a due vie tra esperto e sfoglina: l'esperto puo rispondere
in privato a una domanda o scrivere direttamente a una sfoglina; la sfoglina risponde
dalla sua pagina Messaggi. Notifica email a ogni nuovo messaggio privato e conteggio
dei non letti nella linguetta Messaggi. (L'invio email dipende dalla configurazione di
posta del server.)

= 2.9.0 =
"L'Esperto Risponde": canali pubblici di domande e risposte. Le sfogline fanno
domande, l'esperto designato (e la segreteria) risponde, tutto visibile a tutte.
I canali sono dinamici: dal pannello se ne creano quanti si vuole, si assegna
l'esperto, si rinominano, attivano/disattivano ed eliminano. Anti-spam integrato e
limiti configurabili di domande per sfoglina (al giorno, alla settimana, al mese, con
pausa tra una domanda e l'altra). Moderazione: gestore ed esperto possono eliminare
domande e risposte. Ogni canale ha la sua linguetta e la sua pagina.

= 2.8.3 =
Correzione grafica: le tabelle (Le Mie Sfide, cestino, richieste, ammissioni, ecc.)
ora restano sempre dentro il riquadro, anche con testi lunghi; il testo va a capo e
niente sborda più dalla cornice. Valida per tutti i pannelli e per la plancia.

= 2.8.2 =
Linguette laterali: riportate alla dimensione comoda e ancorate in basso, così il
menu è spostato più giù e si vedono tutte le voci; se lo schermo è piccolissimo il
menu resta scorrevole.

= 2.8.1 =
Linguette laterali: ora si adattano all'altezza della finestra (più compatte e,
se sono molte, scorrevoli), così si vedono tutte comodamente anche quando i pannelli
disponibili sono numerosi.

= 2.8.0 =
Messaggi privati: dal pannello il gestore invia messaggi a una singola sfoglina
oppure a tutte in una volta. Ogni sfoglina ha la sua casella "Messaggi" (pagina e
linguetta con conteggio dei non letti), con stato letto/non letto e avviso in
dashboard. Nel pannello, elenco degli ultimi messaggi inviati con quante li hanno letti.

= 2.7.0 =
Piano di studio ufficiale nell'Area Professionale: un curriculum a gradi (dal
principiante alla Laurea in Sfoglia) con 31 azioni pratiche, caricabile in un corso
con un clic e con le date calcolate dalla data di inizio. Ogni azione mostra il
grado di appartenenza. Il piano resta modificabile: si possono aggiungere o togliere
compiti dopo il caricamento.

= 2.6.0 =
Lotto 3 — Area Professionale: corsi individuali e privati con Rina Poletti, uno per
sfoglina, non visibili alle altre. Dal pannello (riservato) il gestore crea i corsi,
assegna compiti giornalieri precisi, legge le note della sfoglina e risponde con un
riscontro, sospende/riattiva il corso e puo OSCURARE in qualsiasi momento i dati
scambiati con la singola sfoglina. Lato sfoglina: pagina "Area Professionale" con i
propri compiti, visibile solo a lei e solo se il corso e attivo e non oscurato.

= 2.5.0 =
Lotto 2 (media e backup): limite di peso sui caricamenti (sempre attivo),
compressione e ridimensionamento automatico delle foto lato browser, rilevamento
di ffmpeg con compressione video opzionale se il server lo permette. Tutto
comandabile dal pannello. Backup automatico giornaliero dei file registrati, con
numero di copie da conservare, backup manuale immediato e download protetto.

= 2.4.0 =
Lotto 1: cestino personale per ogni sfoglina con recupero dei file (e ricerca per
nome dal pannello gestore, con lavori e cestino di ciascuna). Ricerca personale nei
propri contenuti. Sezione rinominata "Le Mie Sfide". Promemoria personali "Le Cose
da Fare". Pagina pubblica "Le Sfogline". Ricerca su ogni funzione del pannello.
Pulsante blackout per oscurare tutto il Gaming. Predisposte le impostazioni media
(compressione/limite) per il prossimo lotto.

= 2.3.0 =
Vetrina controllabile per singola sfoglina: oltre all'interruttore generale, dal
pannello si può bloccare o riattivare la vetrina di ogni sfoglina (pagina, link e
linguetta spariscono solo per lei). Nuovo permesso dedicato "gs_manage_gaming":
gli amministratori possono nominare una o due collaboratrici che usano il Pannello
di Controllo senza diventare amministratrici del sito.

= 2.2.0 =
Nuova Plancia Generale nel backend WordPress: un'unica schermata con tutti gli
strumenti a zone colorate (iscrizioni, contenuti, punti, sfide blindate, premio,
vetrina, classifiche, impostazioni), con azioni inline in AJAX. Diventa la pagina
principale del menu del plugin.

= 2.1.0 =
Pannello di controllo sul front-end ([gs_pannello]) con approvazione iscrizioni,
correzione punti, assegnazione Premio di Fine Anno e statistiche. Sfide blindate
per livello con ammissione/esclusione concorrenti a discrezione. Linguette
laterali di navigazione lungo il bordo destro. Vetrina attivabile/disattivabile
dal pannello e dalle impostazioni. Documento Word di progetto incluso nel pacchetto.

= 2.0.0 =
Ricostruzione completa del progetto a partire dalla documentazione v1.0 e v2.0.
Rinomina pubblica in "Gaming Sfogline". Aggiunti: registrazione con
approvazione, anti-spam, modalità individuale/squadre, visibilità galleria,
cronologia punti, premio di fine anno, email badge/livello, push OneSignal,
galleria pubblica filtrabile, export PDF, vetrina pubblica.

= 1.0.0 =
Versione iniziale "GuruShot Sfogline": nove sistemi di gamification di base.

== Struttura dei file (per lo sviluppo) ==

gaming-sfogline.php        File principale: attivazione, moduli, pagine, asset
includes/helpers.php       Impostazioni di default e utilità condivise
includes/cpt.php           Custom Post Type + metabox (incl. modalità/visibilità)
includes/points.php        Motore punti, log, livelli, totale annuo
includes/antispam.php      Honeypot / trappola tempo / limite IP
includes/registration.php  Registrazione pubblica + approvazione
includes/notifications.php Email badge/livello + push OneSignal
includes/voting.php        Sfide, invio sfoglie, voto, classifiche, premi
includes/streak.php        Streak del Matterello
includes/missions.php      Missioni giornaliere
includes/badges.php        Badge + trigger di sblocco
includes/teams.php         Squadre regionali + classifica a squadre
includes/seasonal.php      Barometro stagionale (gated per trimestre)
includes/secret-ingredient.php  Ingrediente segreto del venerdì
includes/forms.php         Diario dell'Impasto + Consigli
includes/year-prize.php    Premio di Fine Anno (Corso Rina Poletti)
includes/export.php        Esportazione classifica PDF (pagina stampabile)
includes/shortcodes.php    Tutti gli shortcode front-end
includes/control-panel.php Pannello di controllo front-end (organizzatrici)
includes/side-tabs.php     Linguette laterali di navigazione
includes/admin.php         Pannello di amministrazione
assets/css/gaming.css      Stile front-end (grano / uovo / terracotta)
assets/js/gaming.js        AJAX moduli + countdown + opt-in push
