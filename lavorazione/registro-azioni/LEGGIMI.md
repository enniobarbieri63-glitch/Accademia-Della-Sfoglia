# Registro delle azioni del Pannello — passaggio di consegne

**Stato:** modulo scritto, NON ancora collegato al plugin. Non è nella versione 3.342.0.

**Richiesta (Ennio, 16/10/2026):** sapere chi ha acceso/spento un interruttore del
Pannello di Controllo (es. Blackout) e quando. Oggi non si può sapere: le impostazioni
sono una riga sola nelle opzioni di WordPress, sovrascritta a ogni salvataggio.

**File:** `tracce.php` — da copiare in `gaming-sfogline/includes/` e da aggiungere
all'elenco `$gs_modules` in `gaming-sfogline.php`.

**Cosa fa già:**
- `gs_traccia($azione, $dettaglio, $su_chi)` — scrive una riga (chi, quando, cosa).
- Un'opzione `gs_tracce` con le ultime 400 azioni, `autoload = false` (non pesa sulle pagine).
- Traccia automatica dell'aggiornamento di versione (`init`).
- Le funzioni di attivazione e disattivazione del plugin ci sono (`gs_traccia_attivazione`, `gs_traccia_disattivazione`) ma NON sono ancora agganciate: va aggiunto `register_activation_hook` / `register_deactivation_hook` nel file principale.
- Riquadro «Registro delle Azioni» con ricerca, e pulsante «Svuota» (solo amministratori).
- `gs_traccia_riga_html($pezzo)` — riga «Ultima modifica: … — chi, data alle ora» da mettere sotto un interruttore.

**Cosa resta da fare:**
1. Collegare il modulo: aggiungerlo a `$gs_modules` in `gaming-sfogline.php`.
2. Chiamare `gs_traccia_interruttore(...)` / `gs_traccia(...)` DOPO il successo in:
   Blackout (`control-panel.php`, `gs_ajax_fe_toggle_blackout`), blocco wp-admin,
   vetrina, Buono Sfoglia mensile, bonifici (`gs_abbonamento_salva`, `gs_fe_gestisci_iscrizione`),
   token, permessi/collaboratori (`gs_collab_crea`, `gs_collab_modifica`, `gs_fe_toggle_collab`),
   iscritte cestinate/eliminate, invii mail (singoli e di gruppo), Reset (`gs_reset_esegui`).
3. Mettere `gs_traccia_riga_html('Blackout')` (e simili) sotto gli interruttori.
4. Chiamare `gs_pannello_tracce()` nel Pannello di Controllo, in fondo.
5. Verificare con `php -l`, versione alzata in quattro posti, changelog, zip.

**Regole da rispettare (dal CLAUDE.md del progetto):**
- Nessuna tabella nuova: solo opzioni.
- Non toccare `gs_add_points()` internamente.
- Prefisso `gs_` per ogni funzione.
- Tracciare solo ciò che è riuscito, mai i tentativi falliti.

**Motivo di non tracciare tutto:** il pannello ha circa 385 comandi AJAX; la
maggioranza sono cancellazioni di voci di contenuto che riempirebbero il registro senza dire nulla.

**Nota sul file:** `tracce.php` è stato ricostruito da una copia della chat dopo la pulizia
della cartella di lavoro. Il codice è lo stesso, ma alcuni commenti sono più brevi dell'originale.
Passa `php -l`, non è ancora stato provato su un sito.
