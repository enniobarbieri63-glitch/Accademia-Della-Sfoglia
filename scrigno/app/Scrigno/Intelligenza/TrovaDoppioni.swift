import Foundation
import SwiftData

/// Mette insieme i gruppi di doppioni e propone quale tenere.
/// Propone: la scelta finale resta sempre dell'utente.
enum TrovaDoppioni {

    enum Genere: String, CaseIterable, Identifiable {
        case identici = "Identici"
        case raffiche = "Raffiche"
        case formati = "Formati diversi"

        var id: String { rawValue }

        var spiegazione: String {
            switch self {
            case .identici: "lo stesso identico file, entrato due volte"
            case .raffiche: "scatti fatti a pochi secondi l'uno dall'altro"
            case .formati: "la stessa foto salvata in due modi"
            }
        }
    }

    struct Gruppo: Identifiable {
        let id = UUID()
        var genere: Genere
        var perche: String
        var elementi: [Elemento]
        /// Quello che l'app propone di tenere.
        var daTenere: Elemento?

        /// Quanto spazio si libera buttando gli altri.
        var spazioLiberato: Int {
            let tenuto = daTenere?.peso ?? 0
            return elementi.reduce(0) { $0 + $1.peso } - tenuto
        }
    }

    /// Oltre questa distanza fra le impronte percettive due foto non si somigliano più.
    private static let sogliaSomiglianza = 8
    /// Due scatti entro questi secondi fanno parte della stessa raffica.
    private static let secondiDiRaffica: TimeInterval = 4

    static func cerca(fra elementi: [Elemento]) -> [Gruppo] {
        let vivi = elementi.filter { !$0.nelCestino }
        var gruppi: [Gruppo] = []
        var giaMessi = Set<PersistentIdentifier>()

        gruppi.append(contentsOf: identici(vivi, giaMessi: &giaMessi))
        gruppi.append(contentsOf: somiglianti(vivi, giaMessi: &giaMessi))

        return gruppi.sorted { $0.spazioLiberato > $1.spazioLiberato }
    }

    // MARK: - Stesso identico file

    private static func identici(_ elementi: [Elemento],
                                 giaMessi: inout Set<PersistentIdentifier>) -> [Gruppo] {
        var perImpronta: [String: [Elemento]] = [:]
        for elemento in elementi where !elemento.improntaFile.isEmpty {
            perImpronta[elemento.improntaFile, default: []].append(elemento)
        }

        var gruppi: [Gruppo] = []
        for (_, insieme) in perImpronta where insieme.count > 1 {
            for elemento in insieme { giaMessi.insert(elemento.persistentModelID) }
            // Fra file identici si tiene il più vecchio: è l'originale.
            let tenuto = insieme.min { $0.dataImportazione < $1.dataImportazione }
            gruppi.append(Gruppo(
                genere: .identici,
                perche: "\(insieme.count) copie dello stesso file",
                elementi: insieme.sorted { $0.dataImportazione < $1.dataImportazione },
                daTenere: tenuto
            ))
        }
        return gruppi
    }

    // MARK: - Foto che si somigliano

    private static func somiglianti(_ elementi: [Elemento],
                                    giaMessi: inout Set<PersistentIdentifier>) -> [Gruppo] {
        let foto = elementi
            .filter { $0.tipo == .foto && $0.improntaPercettiva != 0 }
            .filter { !giaMessi.contains($0.persistentModelID) }
            .sorted { $0.dataScatto < $1.dataScatto }

        var gruppi: [Gruppo] = []
        var insieme: [Elemento] = []

        func chiudiInsieme() {
            defer { insieme = [] }
            guard insieme.count > 1 else { return }
            for elemento in insieme { giaMessi.insert(elemento.persistentModelID) }

            let raffica = distanzaNelTempo(insieme) <= secondiDiRaffica
            let genere: Genere = raffica ? .raffiche : .formati

            gruppi.append(Gruppo(
                genere: genere,
                perche: raffica
                    ? "\(insieme.count) scatti in pochi secondi"
                    : "la stessa immagine in \(insieme.count) versioni",
                elementi: insieme,
                daTenere: scegliIlMigliore(insieme, raffica: raffica)
            ))
        }

        for foto in foto {
            guard let ultima = insieme.last else {
                insieme = [foto]
                continue
            }
            let vicine = Impronte.distanza(ultima.improntaPercettiva, foto.improntaPercettiva)
                <= sogliaSomiglianza
            if vicine {
                insieme.append(foto)
            } else {
                chiudiInsieme()
                insieme = [foto]
            }
        }
        chiudiInsieme()

        return gruppi
    }

    private static func distanzaNelTempo(_ elementi: [Elemento]) -> TimeInterval {
        guard let prima = elementi.first?.dataScatto,
              let ultima = elementi.last?.dataScatto else { return .greatestFiniteMagnitude }
        return ultima.timeIntervalSince(prima)
    }

    /// In una raffica si tiene la più nitida. Fra formati diversi si tiene quella
    /// con più pixel, che è quasi sempre l'originale.
    private static func scegliIlMigliore(_ elementi: [Elemento], raffica: Bool) -> Elemento? {
        if raffica {
            return elementi.max { $0.nitidezzaMisurata < $1.nitidezzaMisurata } ?? elementi.first
        }
        return elementi.max {
            ($0.larghezza * $0.altezza, $0.peso) < ($1.larghezza * $1.altezza, $1.peso)
        } ?? elementi.first
    }

    /// La riga che spiega perché questo scatto e non un altro.
    static func motivo(per elemento: Elemento, in gruppo: Gruppo) -> String {
        guard gruppo.daTenere?.persistentModelID == elemento.persistentModelID else {
            switch gruppo.genere {
            case .identici: return "copia"
            case .raffiche: return elemento.nitidezzaMisurata < (gruppo.daTenere?.nitidezzaMisurata ?? 0)
                ? "meno nitida" : "scarto"
            case .formati: return "risoluzione minore"
            }
        }
        switch gruppo.genere {
        case .identici: return "l'originale"
        case .raffiche: return "la più nitida"
        case .formati: return "la risoluzione più alta"
        }
    }
}
