import Foundation

/// Dove stanno le cose sul disco.
///
/// Scrigno **copia** dentro di sé quello che importi: non lascia gli originali
/// sparsi in giro e non li sposta. Così rinominare una cartella nel Finder non
/// svuota mezza libreria.
final class Libreria: @unchecked Sendable {
    static let condivisa = Libreria()

    static let giorniNelCestino: Double = 30
    private static let chiaveCartella = "cartellaLibreria"

    private let fm = FileManager.default

    /// `~/Immagini/Scrigno`, salvo che l'utente non l'abbia spostata.
    private(set) var radice: URL

    private init() {
        if let salvata = UserDefaults.standard.string(forKey: Self.chiaveCartella) {
            radice = URL(fileURLWithPath: salvata, isDirectory: true)
        } else {
            radice = Self.cartellaPredefinita
        }
    }

    static var cartellaPredefinita: URL {
        let immagini = FileManager.default.urls(for: .picturesDirectory, in: .userDomainMask).first
            ?? FileManager.default.homeDirectoryForCurrentUser.appendingPathComponent("Pictures")
        return immagini.appendingPathComponent("Scrigno", isDirectory: true)
    }

    var originali: URL { radice.appendingPathComponent("Originali", isDirectory: true) }
    var catalogo: URL { radice.appendingPathComponent("Catalogo.store") }
    var inArrivo: URL { radice.appendingPathComponent("InArrivo", isDirectory: true) }

    /// Le miniature stanno nella cache: si possono buttare senza perdere niente.
    var miniature: URL {
        let caches = fm.urls(for: .cachesDirectory, in: .userDomainMask).first
            ?? fm.temporaryDirectory
        return caches.appendingPathComponent("it.scrigno.app/Miniature", isDirectory: true)
    }

    /// Vero quando la cartella c'è davvero. Falso significa "primo avvio" oppure
    /// "l'utente ha spostato la cartella": due casi da distinguere in faccia a lui.
    var esiste: Bool {
        var cartella: ObjCBool = false
        return fm.fileExists(atPath: radice.path, isDirectory: &cartella) && cartella.boolValue
    }

    var eraGiaStataScelta: Bool {
        UserDefaults.standard.string(forKey: Self.chiaveCartella) != nil
    }

    func preparaCartelle() throws {
        for cartella in [radice, originali, inArrivo, miniature] {
            try fm.createDirectory(at: cartella, withIntermediateDirectories: true)
        }
    }

    func spostaLibreria(in nuova: URL) throws {
        radice = nuova
        UserDefaults.standard.set(nuova.path, forKey: Self.chiaveCartella)
        try preparaCartelle()
    }

    func urlOriginale(_ percorsoRelativo: String) -> URL {
        originali.appendingPathComponent(percorsoRelativo)
    }

    /// Costruisce il percorso di destinazione `2026/09/12/IMG_4821.HEIC`, evitando
    /// di sovrascrivere: due file diversi con lo stesso nome entrano entrambi.
    func percorsoLibero(perNome nome: String, data: Date) -> String {
        let calendario = Calendar.current
        let parti = calendario.dateComponents([.year, .month, .day], from: data)
        let cartella = String(format: "%04d/%02d/%02d",
                              parti.year ?? 1970, parti.month ?? 1, parti.day ?? 1)

        let base = (nome as NSString).deletingPathExtension
        let estensione = (nome as NSString).pathExtension
        var candidato = "\(cartella)/\(nome)"
        var contatore = 2

        while fm.fileExists(atPath: urlOriginale(candidato).path) {
            let nuovoNome = estensione.isEmpty ? "\(base) \(contatore)" : "\(base) \(contatore).\(estensione)"
            candidato = "\(cartella)/\(nuovoNome)"
            contatore += 1
        }
        return candidato
    }

    func creaCartella(perPercorsoRelativo percorso: String) throws {
        let destinazione = urlOriginale(percorso).deletingLastPathComponent()
        try fm.createDirectory(at: destinazione, withIntermediateDirectories: true)
    }

    // MARK: - Spazio su disco

    struct Spazio {
        var usatoInLibreria: Int
        var liberoSulDisco: Int
        var totaleDelDisco: Int
    }

    func spazio() -> Spazio {
        let valori = try? radice.resourceValues(forKeys: [
            .volumeAvailableCapacityForImportantUsageKey, .volumeTotalCapacityKey
        ])
        return Spazio(
            usatoInLibreria: pesoCartella(originali),
            liberoSulDisco: Int(valori?.volumeAvailableCapacityForImportantUsage ?? 0),
            totaleDelDisco: valori?.volumeTotalCapacity ?? 0
        )
    }

    private func pesoCartella(_ url: URL) -> Int {
        guard let elenco = fm.enumerator(at: url,
                                         includingPropertiesForKeys: [.totalFileAllocatedSizeKey],
                                         options: [.skipsHiddenFiles]) else { return 0 }
        var totale = 0
        for caso in elenco {
            guard let file = caso as? URL,
                  let peso = try? file.resourceValues(forKeys: [.totalFileAllocatedSizeKey]).totalFileAllocatedSize
            else { continue }
            totale += peso
        }
        return totale
    }

    /// Formato leggibile: "3,2 MB", "1,4 GB".
    static func pesoLeggibile(_ byte: Int) -> String {
        let formato = ByteCountFormatter()
        formato.countStyle = .file
        formato.allowedUnits = [.useKB, .useMB, .useGB, .useTB]
        return formato.string(fromByteCount: Int64(byte))
    }
}
