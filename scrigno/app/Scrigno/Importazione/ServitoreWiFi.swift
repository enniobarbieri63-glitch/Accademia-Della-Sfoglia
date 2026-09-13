import Foundation
import Network

/// Il pezzo che fa arrivare foto e file da un telefono Android (o da qualsiasi
/// altro, iPhone compreso) **senza installare niente sul telefono**.
///
/// Perché così: macOS non sa leggere un telefono Android collegato col cavo, serve
/// un programma di terze parti. Una pagina sulla rete di casa invece funziona
/// sempre, basta un browser.
///
/// Sta in piedi solo finché la finestra Importa è aperta, e vuole un codice di
/// quattro cifre nell'indirizzo: chi è sulla stessa rete non può mandare roba
/// senza averlo letto sullo schermo del Mac.
final class ServitoreWiFi: @unchecked Sendable {

    let codice: String
    private(set) var porta: UInt16 = 0

    private let coda = DispatchQueue(label: "it.scrigno.wifi", qos: .userInitiated)
    private let cartellaArrivi: URL
    private var ascoltatore: NWListener?
    private var quantiArrivati = 0

    /// Chiamata quando il telefono ha finito di mandare: arrivano i file appena scritti.
    var quandoArrivano: (@Sendable ([URL]) -> Void)?
    /// Chiamata quando il servitore parte o si ferma, con l'eventuale motivo.
    var quandoCambia: (@Sendable (Bool, String?) -> Void)?

    init(cartellaArrivi: URL, codice: String = ReteLocale.codiceNuovo()) {
        self.cartellaArrivi = cartellaArrivi
        self.codice = codice
    }

    // MARK: - Accendere e spegnere

    func avvia() {
        guard ascoltatore == nil else { return }

        do {
            try FileManager.default.createDirectory(at: cartellaArrivi, withIntermediateDirectories: true)

            let parametri = NWParameters.tcp
            parametri.allowLocalEndpointReuse = true
            // Niente rete dati: questo serve solo dentro casa.
            parametri.prohibitedInterfaceTypes = [.cellular]

            let nuovo = try NWListener(using: parametri, on: .any)
            nuovo.stateUpdateHandler = { [weak self] stato in
                guard let self else { return }
                switch stato {
                case .ready:
                    self.porta = self.ascoltatore?.port?.rawValue ?? 0
                    self.quandoCambia?(true, nil)
                case .failed(let errore):
                    self.quandoCambia?(false, Self.spiega(errore))
                    self.ferma()
                case .cancelled:
                    self.quandoCambia?(false, nil)
                default:
                    break
                }
            }
            nuovo.newConnectionHandler = { [weak self] connessione in
                self?.accogli(connessione)
            }

            ascoltatore = nuovo
            nuovo.start(queue: coda)

        } catch {
            quandoCambia?(false, Self.spiega(error))
        }
    }

    func ferma() {
        ascoltatore?.cancel()
        ascoltatore = nil
        porta = 0
    }

    var indirizzo: String? {
        guard porta > 0, let ip = ReteLocale.indirizzo() else { return nil }
        return "http://\(ip):\(porta)/c/\(codice)/"
    }

    private static func spiega(_ errore: Error) -> String {
        let testo = errore.localizedDescription
        if testo.contains("Operation not permitted") || testo.contains("denied") {
            return """
                macOS non mi lascia aprire la rete locale. Vai in Impostazioni di Sistema \
                › Privacy e sicurezza › Rete locale e dai il permesso a Scrigno.
                """
        }
        return testo
    }

    // MARK: - Una conversazione col telefono

    /// Lo stato di una singola richiesta. Il corpo non si tiene in memoria: finisce
    /// subito su un file di transito, altrimenti un video da due giga farebbe fuori
    /// la RAM del Mac.
    private final class Conversazione {
        let connessione: NWConnection
        var intestazioniLette = false
        var metodo = ""
        var percorso = ""
        var confine: String?
        var lunghezzaAttesa = 0
        var ricevuti = 0
        var testa = Data()
        let fileDiTransito: URL
        var scrittore: FileHandle?

        init(_ connessione: NWConnection, cartella: URL) {
            self.connessione = connessione
            self.fileDiTransito = cartella.appendingPathComponent("arrivo-\(UUID().uuidString).tmp")
        }

        func chiudi() {
            try? scrittore?.close()
            scrittore = nil
            try? FileManager.default.removeItem(at: fileDiTransito)
            connessione.cancel()
        }
    }

    private func accogli(_ connessione: NWConnection) {
        let conversazione = Conversazione(connessione, cartella: cartellaArrivi)
        connessione.start(queue: coda)
        leggi(conversazione)
    }

    private func leggi(_ c: Conversazione) {
        c.connessione.receive(minimumIncompleteLength: 1, maximumLength: 1 << 18) {
            [weak self] pezzo, _, finita, errore in
            guard let self else { return }

            if let errore {
                _ = errore
                c.chiudi()
                return
            }

            if let pezzo, !pezzo.isEmpty {
                self.ingoia(pezzo, in: c)
            }

            if self.richiestaCompleta(c) {
                self.rispondi(a: c)
                return
            }

            if finita {
                // Il telefono ha chiuso prima del previsto: meglio buttare via un
                // mezzo file che farlo entrare in libreria monco.
                c.chiudi()
                return
            }

            self.leggi(c)
        }
    }

    private func ingoia(_ pezzo: Data, in c: Conversazione) {
        guard c.intestazioniLette else {
            c.testa.append(pezzo)

            let fineTesta = Data("\r\n\r\n".utf8)
            guard let taglio = c.testa.range(of: fineTesta) else {
                // Una testa enorme vuol dire richiesta malformata o malintenzionata.
                if c.testa.count > 64 * 1024 { c.chiudi() }
                return
            }

            let testo = String(decoding: c.testa[c.testa.startIndex..<taglio.lowerBound], as: UTF8.self)
            leggiIntestazioni(testo, in: c)
            c.intestazioniLette = true

            let restante = c.testa[taglio.upperBound...]
            c.testa = Data()

            if c.lunghezzaAttesa > 0 {
                apriScrittore(c)
                if !restante.isEmpty {
                    c.scrittore?.write(Data(restante))
                    c.ricevuti += restante.count
                }
            }
            return
        }

        c.scrittore?.write(pezzo)
        c.ricevuti += pezzo.count
    }

    private func apriScrittore(_ c: Conversazione) {
        FileManager.default.createFile(atPath: c.fileDiTransito.path, contents: nil)
        c.scrittore = try? FileHandle(forWritingTo: c.fileDiTransito)
    }

    private func leggiIntestazioni(_ testo: String, in c: Conversazione) {
        let righe = testo.split(separator: "\r\n", omittingEmptySubsequences: false)
        guard let prima = righe.first else { return }

        let pezzi = prima.split(separator: " ")
        if pezzi.count >= 2 {
            c.metodo = String(pezzi[0]).uppercased()
            c.percorso = String(pezzi[1])
        }

        for riga in righe.dropFirst() {
            guard let duePunti = riga.firstIndex(of: ":") else { continue }
            let nome = riga[riga.startIndex..<duePunti].lowercased()
            let valore = riga[riga.index(after: duePunti)...].trimmingCharacters(in: .whitespaces)

            if nome == "content-length" {
                c.lunghezzaAttesa = Int(valore) ?? 0
            } else if nome == "content-type", let inizio = valore.range(of: "boundary=") {
                var confine = String(valore[inizio.upperBound...])
                if confine.hasPrefix("\"") { confine = String(confine.dropFirst().dropLast()) }
                c.confine = confine
            }
        }
    }

    private func richiestaCompleta(_ c: Conversazione) -> Bool {
        c.intestazioniLette && c.ricevuti >= c.lunghezzaAttesa
    }

    // MARK: - Le risposte

    private func rispondi(a c: Conversazione) {
        try? c.scrittore?.close()
        c.scrittore = nil

        let atteso = "/c/\(codice)"
        let percorsoPulito = c.percorso.split(separator: "?").first.map(String.init) ?? c.percorso

        guard percorsoPulito.hasPrefix(atteso) else {
            manda(codice: 403, tipo: "text/html; charset=utf-8",
                  corpo: Data(PaginaTelefono.paginaSbagliata.utf8), a: c)
            return
        }

        if c.metodo == "POST", percorsoPulito.hasSuffix("/invia") {
            let arrivati = salvaFile(da: c)
            try? FileManager.default.removeItem(at: c.fileDiTransito)

            if arrivati.isEmpty {
                manda(codice: 400, tipo: "text/plain; charset=utf-8",
                      corpo: Data("non ho ricevuto nessun file".utf8), a: c)
            } else {
                quantiArrivati += arrivati.count
                let elenco = arrivati
                quandoArrivano?(elenco)
                manda(codice: 200, tipo: "text/plain; charset=utf-8",
                      corpo: Data("ricevuti \(arrivati.count)".utf8), a: c)
            }
            return
        }

        manda(codice: 200, tipo: "text/html; charset=utf-8",
              corpo: Data(PaginaTelefono.html(codice: codice, quantiArrivati: quantiArrivati).utf8), a: c)
    }

    private func manda(codice stato: Int, tipo: String, corpo: Data, a c: Conversazione) {
        let intestazioni = """
            HTTP/1.1 \(stato) \(stato == 200 ? "OK" : "ERR")\r
            Content-Type: \(tipo)\r
            Content-Length: \(corpo.count)\r
            Cache-Control: no-store\r
            Connection: close\r
            \r\n
            """
        var risposta = Data(intestazioni.utf8)
        risposta.append(corpo)

        c.connessione.send(content: risposta, completion: .contentProcessed { _ in
            c.chiudi()
        })
    }

    // MARK: - Tirare fuori i file dalla richiesta

    private func salvaFile(da c: Conversazione) -> [URL] {
        guard let confine = c.confine,
              let corpo = try? Data(contentsOf: c.fileDiTransito, options: .mappedIfSafe)
        else { return [] }

        let separatore = Data("--\(confine)".utf8)
        var posizioni: [Int] = []
        var cursore = corpo.startIndex
        while let trovato = corpo.range(of: separatore, in: cursore..<corpo.endIndex) {
            posizioni.append(trovato.lowerBound)
            cursore = trovato.upperBound
        }
        guard posizioni.count >= 2 else { return [] }

        var salvati: [URL] = []
        let fineTesta = Data("\r\n\r\n".utf8)

        for indice in 0..<(posizioni.count - 1) {
            let inizioParte = posizioni[indice] + separatore.count
            let fineParte = posizioni[indice + 1]
            guard fineParte > inizioParte else { continue }

            // La testa della parte è corta: leggerne un pezzo basta e avanza.
            let finestra = min(inizioParte + 4096, fineParte)
            guard let taglio = corpo.range(of: fineTesta, in: inizioParte..<finestra) else { continue }

            let testa = String(decoding: corpo[inizioParte..<taglio.lowerBound], as: UTF8.self)
            guard let nome = nomeFile(da: testa) else { continue }

            // Il contenuto finisce due byte prima del separatore successivo (\r\n).
            let inizioContenuto = taglio.upperBound
            let fineContenuto = max(inizioContenuto, fineParte - 2)
            guard fineContenuto > inizioContenuto else { continue }

            if let scritto = scrivi(corpo, da: inizioContenuto, a: fineContenuto, nome: nome) {
                salvati.append(scritto)
            }
        }
        return salvati
    }

    /// Prende il nome dal `Content-Disposition` e lo ripulisce: niente barre,
    /// niente `..`, niente nomi vuoti. Quello che arriva dalla rete non si beve mai.
    private func nomeFile(da testa: String) -> String? {
        guard let inizio = testa.range(of: "filename=\"") else { return nil }
        let resto = testa[inizio.upperBound...]
        guard let fine = resto.firstIndex(of: "\"") else { return nil }

        let grezzo = String(resto[resto.startIndex..<fine])
        let solaFoglia = (grezzo as NSString).lastPathComponent
        let pulito = solaFoglia
            .replacingOccurrences(of: "/", with: "-")
            .replacingOccurrences(of: ":", with: "-")
            .trimmingCharacters(in: .whitespacesAndNewlines)

        guard !pulito.isEmpty, pulito != ".", pulito != ".." else { return nil }
        return String(pulito.prefix(180))
    }

    /// Copia l'intervallo su un file vero, a pezzi da un mega: così anche un video
    /// lungo non passa mai tutto per la memoria.
    private func scrivi(_ corpo: Data, da inizio: Int, a fine: Int, nome: String) -> URL? {
        var destinazione = cartellaArrivi.appendingPathComponent(nome)
        var contatore = 2
        while FileManager.default.fileExists(atPath: destinazione.path) {
            let base = (nome as NSString).deletingPathExtension
            let estensione = (nome as NSString).pathExtension
            let nuovo = estensione.isEmpty ? "\(base) \(contatore)" : "\(base) \(contatore).\(estensione)"
            destinazione = cartellaArrivi.appendingPathComponent(nuovo)
            contatore += 1
        }

        FileManager.default.createFile(atPath: destinazione.path, contents: nil)
        guard let scrittore = try? FileHandle(forWritingTo: destinazione) else { return nil }
        defer { try? scrittore.close() }

        var cursore = inizio
        let pezzo = 1 << 20
        while cursore < fine {
            let prossimo = min(cursore + pezzo, fine)
            scrittore.write(corpo.subdata(in: cursore..<prossimo))
            cursore = prossimo
        }
        return destinazione
    }
}
