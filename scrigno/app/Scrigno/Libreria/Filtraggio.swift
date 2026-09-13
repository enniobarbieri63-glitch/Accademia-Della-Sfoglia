import Foundation
import SwiftData

/// Dato l'elenco completo, tira fuori quello che va mostrato adesso: il cassetto
/// aperto più quello che è stato scritto nella ricerca.
enum Filtraggio {

    static func filtra(_ tutti: [Elemento], cassetto: Navigazione.Cassetto,
                       ricerca: String) -> [Elemento] {
        var risultato = tutti.filter { tieni($0, cassetto: cassetto) }

        let cercato = ricerca.trimmingCharacters(in: .whitespacesAndNewlines).lowercased()
        if !cercato.isEmpty {
            let parole = cercato.split(separator: " ").map(String.init)
            risultato = risultato.filter { elemento in
                let testo = elemento.testoCercabile
                return parole.allSatisfy { testo.contains($0) }
            }
        }
        return risultato
    }

    private static func tieni(_ elemento: Elemento, cassetto: Navigazione.Cassetto) -> Bool {
        // Il cestino è l'unico posto dove si vede quello che è stato eliminato.
        if case .cestino = cassetto { return elemento.nelCestino }
        guard !elemento.nelCestino else { return false }

        switch cassetto {
        case .tutto:
            return true
        case .tipo(let tipo):
            return elemento.tipo == tipo
        case .preferiti:
            return elemento.preferito
        case .senzaEtichetta:
            return elemento.etichette.isEmpty
        case .scontrini:
            return TrovaScontrini.sembraUnoScontrino(elemento)
        case .etichetta(let id):
            return elemento.etichette.contains { $0.persistentModelID == id }
        case .album(let id):
            return elemento.album.contains { $0.persistentModelID == id }
        case .luogo(let nome):
            return elemento.luogo == nome
        case .persona:
            // Le persone hanno una schermata loro: qui non filtrano la libreria.
            return true
        case .cestino:
            return false
        }
    }

    // MARK: - Raggruppamento

    struct Gruppo: Identifiable {
        let id: Date
        let titolo: String
        let luogo: String?
        var elementi: [Elemento]
    }

    static func raggruppa(_ elementi: [Elemento],
                          come modo: Navigazione.Raggruppamento) -> [Gruppo] {
        guard modo != .tutti else {
            guard !elementi.isEmpty else { return [] }
            return [Gruppo(id: .distantPast, titolo: "Tutti gli elementi",
                           luogo: nil, elementi: elementi)]
        }

        let calendario = Calendar.current
        var mucchi: [Date: [Elemento]] = [:]

        for elemento in elementi {
            let chiave: Date
            switch modo {
            case .giorni:
                chiave = calendario.startOfDay(for: elemento.dataScatto)
            case .mesi:
                chiave = calendario.date(from: calendario.dateComponents([.year, .month],
                                                                        from: elemento.dataScatto))
                    ?? elemento.dataScatto
            case .anni:
                chiave = calendario.date(from: calendario.dateComponents([.year],
                                                                         from: elemento.dataScatto))
                    ?? elemento.dataScatto
            case .tutti:
                chiave = .distantPast
            }
            mucchi[chiave, default: []].append(elemento)
        }

        return mucchi.keys.sorted(by: >).map { chiave in
            let dentro = mucchi[chiave] ?? []
            return Gruppo(id: chiave,
                          titolo: titolo(per: chiave, modo: modo),
                          luogo: luogoPiuComune(dentro),
                          elementi: dentro.sorted { $0.dataScatto > $1.dataScatto })
        }
    }

    private static func titolo(per data: Date, modo: Navigazione.Raggruppamento) -> String {
        let formato = DateFormatter()
        formato.locale = Locale(identifier: "it_IT")
        switch modo {
        case .giorni: formato.dateFormat = "EEEE d MMMM"
        case .mesi: formato.dateFormat = "MMMM yyyy"
        case .anni: formato.dateFormat = "yyyy"
        case .tutti: return "Tutti gli elementi"
        }
        return formato.string(from: data).capitalized
    }

    private static func luogoPiuComune(_ elementi: [Elemento]) -> String? {
        var conti: [String: Int] = [:]
        for elemento in elementi {
            guard let luogo = elemento.luogo, !luogo.isEmpty else { continue }
            conti[luogo, default: 0] += 1
        }
        return conti.max { $0.value < $1.value }?.key
    }
}

/// Riconosce scontrini, ricevute e garanzie fra i documenti. È una regola grossolana
/// che guarda le parole: sbaglia qualche volta, e l'utente può sempre togliere
/// l'elemento dalla raccolta mettendogli un'etichetta sua.
enum TrovaScontrini {

    private static let indizi = [
        "scontrino", "ricevuta", "fattura", "garanzia", "totale", "iva",
        "p.iva", "partita iva", "importo", "pagamento", "acquisto"
    ]

    static func sembraUnoScontrino(_ elemento: Elemento) -> Bool {
        guard elemento.tipo == .documento || elemento.tipo == .foto else { return false }

        let daCercare = (elemento.nomeFile + " " + elemento.testoRiconosciuto).lowercased()
        guard !daCercare.isEmpty else { return false }

        // Due indizi valgono più di uno: "totale" da solo capita in troppi documenti.
        let trovati = indizi.filter { daCercare.contains($0) }.count
        return trovati >= 2 || daCercare.contains("scontrino") || daCercare.contains("ricevuta")
    }
}
