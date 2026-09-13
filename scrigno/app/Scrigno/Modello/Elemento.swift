import Foundation
import SwiftData

/// Una cosa custodita in libreria: una foto, un video, un PDF, una nota vocale,
/// un archivio. Il file vero sta in `~/Immagini/Scrigno/Originali/...`; qui c'è
/// solo la scheda che lo descrive.
@Model
final class Elemento {
    var nomeFile: String = ""
    /// Percorso dentro `Originali/`, per esempio `2026/09/12/IMG_4821.HEIC`.
    var percorsoRelativo: String = ""
    var tipoGrezzo: String = TipoFile.altro.rawValue

    var dataScatto: Date = Date.distantPast
    var dataImportazione: Date = Date.distantPast
    var peso: Int = 0
    var larghezza: Int = 0
    var altezza: Int = 0
    /// Secondi, per video e audio. Zero per tutto il resto.
    var durata: Double = 0

    /// SHA-256 del file: due file con la stessa impronta sono lo stesso file.
    var improntaFile: String = ""
    /// Impronta percettiva a 64 bit: due foto simili hanno impronte vicine.
    /// Zero quando non è calcolabile.
    var improntaPercettiva: Int = 0
    /// Quanto è nitida la foto: serve a proporre quale scatto tenere in una raffica.
    var nitidezzaMisurata: Double = 0

    var latitudine: Double?
    var longitudine: Double?
    var luogo: String?

    var fotocamera: String?
    var diaframma: Double?
    var tempoEsposizione: Double?
    var iso: Int?
    var focale: Double?

    var preferito: Bool = false
    /// Quando è finito in "Eliminati di recente". Dopo 30 giorni sparisce davvero.
    var eliminatoIl: Date?

    /// Testo letto dentro PDF e scansioni.
    var testoRiconosciuto: String = ""
    /// Parole riconosciute nella scena (tramonto, laguna…).
    var paroleAutomatiche: [String] = []
    /// Data di scadenza pescata dentro un documento, quando è leggibile.
    var scadenza: Date?
    var scadenzaDescrizione: String?

    var datiModifiche: Data?

    var etichette: [Etichetta] = []
    var album: [Album] = []

    init(nomeFile: String, percorsoRelativo: String, tipo: TipoFile) {
        self.nomeFile = nomeFile
        self.percorsoRelativo = percorsoRelativo
        self.tipoGrezzo = tipo.rawValue
        self.dataScatto = Date()
        self.dataImportazione = Date()
    }

    var tipo: TipoFile {
        get { TipoFile(rawValue: tipoGrezzo) ?? .altro }
        set { tipoGrezzo = newValue.rawValue }
    }

    var modifiche: Modifiche {
        get { Modifiche.da(datiModifiche) }
        set { datiModifiche = newValue.codificate }
    }

    var modificato: Bool { datiModifiche != nil }
    var nelCestino: Bool { eliminatoIl != nil }

    var url: URL { Libreria.condivisa.urlOriginale(percorsoRelativo) }

    var giorno: Date {
        Calendar.current.startOfDay(for: dataScatto)
    }

    var haPosizione: Bool { latitudine != nil && longitudine != nil }

    /// Quanti giorni mancano prima che sparisca davvero dal cestino.
    var giorniPrimaDellaCancellazione: Int? {
        guard let eliminatoIl else { return nil }
        let scadenza = eliminatoIl.addingTimeInterval(Libreria.giorniNelCestino * 86_400)
        let mancanti = Calendar.current.dateComponents([.day], from: Date(), to: scadenza).day ?? 0
        return max(0, mancanti)
    }

    /// Il testo su cui lavora la ricerca.
    var testoCercabile: String {
        var pezzi = [nomeFile, luogo ?? "", testoRiconosciuto]
        pezzi.append(contentsOf: paroleAutomatiche)
        pezzi.append(contentsOf: etichette.map(\.nome))
        pezzi.append(contentsOf: album.map(\.nome))
        return pezzi.joined(separator: " ").lowercased()
    }
}
