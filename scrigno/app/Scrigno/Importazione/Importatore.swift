import Foundation
import Observation
import SwiftData

/// Porta dentro i file: li guarda, scarta i doppioni, li copia nella libreria,
/// legge i dati di scatto e prepara la miniatura.
///
/// Lavora in due tempi, come la finestra Importa dell'anteprima: prima **guarda**
/// cosa c'è e lo mostra, poi **copia** solo quando l'utente conferma.
@MainActor
@Observable
final class Importatore {

    // MARK: - Cosa sta succedendo

    enum Fase: Equatable {
        case fermo
        case guardo
        case copio
        case fatto
        case annullato
        case fermato(String)
    }

    private(set) var fase: Fase = .fermo
    private(set) var candidati: [Candidato] = []
    private(set) var fatti = 0
    private(set) var saltati = 0
    private(set) var nomeInLavorazione = ""
    private(set) var problemi: [String] = []
    private(set) var nomeSorgente = ""

    private var daFermare = false

    // MARK: - Un file in attesa di entrare

    struct Candidato: Identifiable, Hashable {
        let id = UUID()
        var url: URL
        var tipo: TipoFile
        var peso: Int
        var impronta: String
        var data: Date
        /// Vero se lo stesso identico file è già in libreria, o compare due volte
        /// nella stessa importazione.
        var doppione: Bool
        /// Se questo file è arrivato via Wi-Fi o da un dispositivo, dopo va tolto
        /// dalla cartella di transito.
        var daRipulire: Bool = false
    }

    struct Opzioni {
        var albumPerGiorno = true
        var riconosciTesto = true
        var eliminaDallaSorgente = false
        var etichetta: Etichetta?
        var nomeAlbum: String?
    }

    // MARK: - Conti da mostrare

    var daImportare: [Candidato] { candidati.filter { !$0.doppione } }
    var quantiDoppioni: Int { candidati.filter { $0.doppione }.count }
    var pesoTotale: Int { daImportare.reduce(0) { $0 + $1.peso } }

    func quanti(_ tipo: TipoFile) -> Int {
        daImportare.filter { $0.tipo == tipo }.count
    }

    var avanzamento: Double {
        let totale = daImportare.count
        guard totale > 0 else { return 0 }
        return min(1, Double(fatti) / Double(totale))
    }

    // MARK: - Primo tempo: guardare

    func guarda(_ origini: [URL], nomeSorgente: String = "", daRipulire: Bool = false,
                contesto: ModelContext) async {
        fase = .guardo
        daFermare = false
        candidati = []
        problemi = []
        fatti = 0
        saltati = 0
        self.nomeSorgente = nomeSorgente

        let giaInLibreria = improntePresenti(contesto)
        let file = await Self.elencaFile(origini)

        var vistiOra = Set<String>()
        var trovati: [Candidato] = []

        for url in file {
            if daFermare { fase = .annullato; return }
            nomeInLavorazione = url.lastPathComponent

            guard let scheda = await Self.guardaUnFile(url, daRipulire: daRipulire) else {
                problemi.append("Non sono riuscito a leggere \(url.lastPathComponent).")
                continue
            }

            var candidato = scheda
            candidato.doppione = giaInLibreria.contains(candidato.impronta)
                || vistiOra.contains(candidato.impronta)
            vistiOra.insert(candidato.impronta)
            trovati.append(candidato)
        }

        candidati = trovati
        nomeInLavorazione = ""
        fase = .fermo
    }

    // MARK: - Secondo tempo: copiare

    func copia(opzioni: Opzioni, contesto: ModelContext) async {
        fase = .copio
        daFermare = false
        fatti = 0
        saltati = quantiDoppioni

        let libreria = Libreria.condivisa
        do {
            try libreria.preparaCartelle()
        } catch {
            fase = .fermato("Non riesco a preparare la cartella della libreria: \(error.localizedDescription)")
            return
        }

        // Meglio accorgersene adesso che a metà strada.
        let spazio = libreria.spazio()
        if spazio.liberoSulDisco > 0 && pesoTotale > spazio.liberoSulDisco {
            fase = .fermato("""
                Servono \(Libreria.pesoLeggibile(pesoTotale)) ma sul disco ce ne sono \
                \(Libreria.pesoLeggibile(spazio.liberoSulDisco)). Libera spazio e riprova.
                """)
            return
        }

        var albumPerGiorno: [Date: Album] = [:]
        var albumUnico: Album?
        if let nome = opzioni.nomeAlbum, !nome.isEmpty {
            albumUnico = Album(nome: nome)
            contesto.insert(albumUnico!)
        }

        for candidato in daImportare {
            if daFermare { break }
            nomeInLavorazione = candidato.url.lastPathComponent

            do {
                let elemento = try await porta(candidato, in: libreria, contesto: contesto)

                if let etichetta = opzioni.etichetta {
                    elemento.etichette.append(etichetta)
                }
                if let albumUnico {
                    elemento.album.append(albumUnico)
                }
                if opzioni.albumPerGiorno, candidato.tipo != .documento {
                    let giorno = Calendar.current.startOfDay(for: elemento.dataScatto)
                    let album = albumPerGiorno[giorno] ?? {
                        let nuovo = Album(nome: Self.nomeGiornata(giorno), automatico: true)
                        contesto.insert(nuovo)
                        albumPerGiorno[giorno] = nuovo
                        return nuovo
                    }()
                    elemento.album.append(album)
                }

                fatti += 1

                // Si salva ogni tanto, non a ogni file: così un'interruzione non
                // butta via tutto e il disco non viene martellato.
                if fatti % 25 == 0 { try? contesto.save() }

            } catch {
                problemi.append("\(candidato.url.lastPathComponent): \(error.localizedDescription)")
            }
        }

        try? contesto.save()

        if opzioni.eliminaDallaSorgente {
            ripulisci()
        } else {
            ripulisciSoloTransito()
        }

        nomeInLavorazione = ""
        fase = daFermare ? .annullato : .fatto
    }

    func ferma() { daFermare = true }

    func azzera() {
        fase = .fermo
        candidati = []
        problemi = []
        fatti = 0
        saltati = 0
        nomeInLavorazione = ""
        nomeSorgente = ""
    }

    // MARK: - Il lavoro su un singolo file

    private func porta(_ candidato: Candidato, in libreria: Libreria,
                       contesto: ModelContext) async throws -> Elemento {
        let scheda = await LettoreMetadati.leggi(candidato.url, tipo: candidato.tipo)
        let data = scheda.data ?? candidato.data

        let percorso = libreria.percorsoLibero(perNome: candidato.url.lastPathComponent, data: data)
        try libreria.creaCartella(perPercorsoRelativo: percorso)
        try FileManager.default.copyItem(at: candidato.url, to: libreria.urlOriginale(percorso))

        let elemento = Elemento(nomeFile: candidato.url.lastPathComponent,
                                percorsoRelativo: percorso,
                                tipo: candidato.tipo)
        elemento.dataScatto = data
        elemento.dataImportazione = Date()
        elemento.peso = candidato.peso
        elemento.improntaFile = candidato.impronta
        elemento.larghezza = scheda.larghezza
        elemento.altezza = scheda.altezza
        elemento.durata = scheda.durata
        elemento.latitudine = scheda.latitudine
        elemento.longitudine = scheda.longitudine
        elemento.fotocamera = scheda.fotocamera
        elemento.diaframma = scheda.diaframma
        elemento.tempoEsposizione = scheda.tempoEsposizione
        elemento.iso = scheda.iso
        elemento.focale = scheda.focale

        contesto.insert(elemento)

        // Le impronte percettive servono solo dove ha senso confrontare immagini.
        if candidato.tipo == .foto, let piccola = Impronte.immagineRidotta(di: candidato.url) {
            elemento.improntaPercettiva = Impronte.percettiva(di: piccola)
            elemento.nitidezzaMisurata = Impronte.nitidezza(di: piccola)
        }

        // La miniatura si prepara subito: la griglia deve essere pronta all'apertura.
        _ = await Miniature.condivisa.miniatura(impronta: candidato.impronta,
                                                file: libreria.urlOriginale(percorso),
                                                tipo: candidato.tipo)
        return elemento
    }

    // MARK: - Utilità

    private func improntePresenti(_ contesto: ModelContext) -> Set<String> {
        let richiesta = FetchDescriptor<Elemento>()
        guard let tutti = try? contesto.fetch(richiesta) else { return [] }
        return Set(tutti.map(\.improntaFile))
    }

    /// Toglie i file dalla cartella di transito (arrivi via Wi-Fi o da dispositivo):
    /// lì non devono restare, hanno già la loro copia in libreria.
    private func ripulisciSoloTransito() {
        for candidato in candidati where candidato.daRipulire {
            try? FileManager.default.removeItem(at: candidato.url)
        }
    }

    /// Toglie anche gli originali dalla sorgente. Si fa solo quando l'utente
    /// l'ha chiesto apposta, e solo sui file davvero copiati.
    private func ripulisci() {
        for candidato in daImportare {
            try? FileManager.default.removeItem(at: candidato.url)
        }
    }

    static func nomeGiornata(_ giorno: Date) -> String {
        let formato = DateFormatter()
        formato.locale = Locale(identifier: "it_IT")
        formato.dateFormat = "d MMMM yyyy"
        return formato.string(from: giorno).capitalized
    }

    // MARK: - Lavoro fuori dal filo principale

    /// Apre le cartelle e tira fuori l'elenco dei file, saltando quelli nascosti
    /// e i pacchetti di sistema.
    nonisolated static func elencaFile(_ origini: [URL]) async -> [URL] {
        await Task.detached(priority: .userInitiated) {
            let fm = FileManager.default
            var trovati: [URL] = []

            for origine in origini {
                var eCartella: ObjCBool = false
                guard fm.fileExists(atPath: origine.path, isDirectory: &eCartella) else { continue }

                if eCartella.boolValue {
                    guard let elenco = fm.enumerator(
                        at: origine,
                        includingPropertiesForKeys: [.isRegularFileKey],
                        options: [.skipsHiddenFiles, .skipsPackageDescendants]
                    ) else { continue }

                    for caso in elenco {
                        guard let file = caso as? URL,
                              (try? file.resourceValues(forKeys: [.isRegularFileKey]).isRegularFile) == true
                        else { continue }
                        trovati.append(file)
                    }
                } else {
                    trovati.append(origine)
                }
            }
            return trovati.sorted { $0.path < $1.path }
        }.value
    }

    nonisolated static func guardaUnFile(_ url: URL, daRipulire: Bool) async -> Candidato? {
        await Task.detached(priority: .userInitiated) {
            guard let impronta = try? Impronte.sha256(di: url) else { return nil }

            let valori = try? url.resourceValues(forKeys: [.fileSizeKey, .contentModificationDateKey])
            return Candidato(
                url: url,
                tipo: TipoFile.riconosci(url),
                peso: valori?.fileSize ?? 0,
                impronta: impronta,
                data: valori?.contentModificationDate ?? Date(),
                doppione: false,
                daRipulire: daRipulire
            )
        }.value
    }
}
