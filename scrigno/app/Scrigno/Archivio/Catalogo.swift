import Foundation
import SwiftData

/// Il catalogo: nomi, date, etichette, album, modifiche. Sta dentro la cartella
/// della libreria, non nella cache, così Time Machine se lo porta via insieme
/// alle foto.
enum Catalogo {

    static func apri() throws -> ModelContainer {
        try Libreria.condivisa.preparaCartelle()
        let configurazione = ModelConfiguration(url: Libreria.condivisa.catalogo)
        return try ModelContainer(
            for: Elemento.self, Etichetta.self, Album.self, Persona.self, Volto.self,
            configurations: configurazione
        )
    }

    /// Catalogo in memoria: serve quando la cartella della libreria non si trova,
    /// così l'app si apre lo stesso e può spiegare cos'è successo invece di
    /// chiudersi da sola.
    static func inMemoria() -> ModelContainer {
        let configurazione = ModelConfiguration(isStoredInMemoryOnly: true)
        // Se fallisce anche questo il problema è nel modello dei dati, non nel disco:
        // meglio fermarsi subito con un messaggio chiaro che proseguire a metà.
        return try! ModelContainer(
            for: Elemento.self, Etichetta.self, Album.self, Persona.self, Volto.self,
            configurations: configurazione
        )
    }

    /// Alla prima apertura mette le quattro etichette colorate.
    @MainActor
    static func preparaSeVuoto(_ contesto: ModelContext) {
        let quante = (try? contesto.fetchCount(FetchDescriptor<Etichetta>())) ?? 0
        guard quante == 0 else { return }

        for (indice, coppia) in Etichetta.predefinite.enumerated() {
            contesto.insert(Etichetta(nome: coppia.0, coloreEsadecimale: coppia.1, ordine: indice))
        }
        try? contesto.save()
    }

    /// Svuota per davvero quello che è nel cestino da più di 30 giorni.
    /// Gira a ogni avvio: l'utente non deve ricordarsene.
    @MainActor
    static func svuotaCestinoScaduto(_ contesto: ModelContext) {
        let limite = Date().addingTimeInterval(-Libreria.giorniNelCestino * 86_400)
        let richiesta = FetchDescriptor<Elemento>()
        guard let tutti = try? contesto.fetch(richiesta) else { return }

        let scaduti = tutti.filter { elemento in
            guard let eliminatoIl = elemento.eliminatoIl else { return false }
            return eliminatoIl < limite
        }
        guard !scaduti.isEmpty else { return }

        for elemento in scaduti {
            try? FileManager.default.removeItem(at: elemento.url)
            let impronta = elemento.improntaFile
            Task { await Miniature.condivisa.dimentica(impronta) }
            contesto.delete(elemento)
        }
        try? contesto.save()
    }
}
