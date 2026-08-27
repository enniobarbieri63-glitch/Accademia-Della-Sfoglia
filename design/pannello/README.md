# Proposta di riprogettazione del Pannello di Controllo

Mockup statici, non cliccabili — Ennio ha chiesto di *vedere* una proposta.

Canvas pubblicato: https://claude.ai/code/artifact/7bd9b363-938e-463e-8c9e-c778c13fa088

## I file

| | |
|---|---|
| `Main.dc.html` | la proposta, chiaro |
| `Scuro.dc.html` | la stessa, scuro |
| `DirezioneB.dc.html` | «La Plancia» — tutto visibile insieme, come adesso ma leggibile |
| `DirezioneC.dc.html` | «Una cosa alla volta» — il pannello propone la prossima cosa da fare |
| `canvas.json` | disposizione sulla tela e note |
| `pannello-accademia.html` | il file pubblicato (generato: si rifà dai `.dc.html`) |

## Il principio

Il pannello di oggi è ordinato **per argomento** (sfide, messaggi, contenuti,
impostazioni): 73 riquadri in 10 gruppi, `pannello-nuovo.php`.

La proposta lo ordina **per quando serve**:

1. **Cosa ti aspetta oggi** — le code già contate dal codice (iscrizioni,
   ricette, biografie, testimonianze, conversazioni, vetrine partner, messaggi
   senza risposta), in una lista sola, in ordine di attesa, con da quanti
   giorni una cosa aspetta.
2. **Il polso** — sfogline, prove in scadenza, sponsor scaduti, sfida in corso.
3. **Tutto il resto** — per frequenza d'uso: ogni giorno / ogni settimana /
   ogni tanto / si imposta una volta e basta (questi ultimi nove, chiusi).

## Le scelte grafiche, e perché

- **Colori presi dal plugin vero** (`#CD8B0C`, `#1F6E37`, `#8A5A2F`, `#C23B3B`,
  `#8C4A7A`, `#2B7A9E`), non una tavolozza inventata.
- **Niente emoji**: cambiano faccia su ogni dispositivo e a schermo grande
  sembrano appiccicate. Disegni SVG, tutti nello stesso stile.
- **Fondo color farina** (`#F4EFE4`) invece del bianco pieno e testo marrone
  scuro invece del nero: è la richiesta «non deve stancare gli occhi».
- **Testo grande davvero**: 19px per le righe delle code, non 13.

## Per rifare o modificare

Si modificano i `.dc.html`, si riseminA il pacchetto e si ripubblica allo
stesso indirizzo. Il file `pannello-accademia.html` è generato: non va
modificato a mano.
