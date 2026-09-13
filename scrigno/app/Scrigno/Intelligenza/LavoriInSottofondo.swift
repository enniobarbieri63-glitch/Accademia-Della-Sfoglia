import Foundation
import SwiftData
import Vision

/// Le cose che si fanno dopo, con calma, senza far aspettare l'utente: leggere il
/// testo dentro i documenti, dare un nome ai luoghi, riconoscere cosa c'è nelle foto.
@MainActor
enum LavoriInSottofondo {

    /// Legge il testo dentro i documenti e le scansioni che non l'hanno ancora.
    static func leggiTestoMancante(contesto: ModelContext, quantiAlMassimo: Int = 300) async {
        guard let tutti = try? contesto.fetch(FetchDescriptor<Elemento>()) else { return }

        let daFare = tutti
            .filter { !$0.nelCestino && $0.testoRiconosciuto.isEmpty }
            .filter { $0.tipo == .documento || $0.tipo == .foto }
            .prefix(quantiAlMassimo)

        for elemento in daFare {
            let url = elemento.url
            let tipo = elemento.tipo
            let testo = await RiconoscimentoTesto.leggi(url, tipo: tipo)
            guard !testo.isEmpty else { continue }

            elemento.testoRiconosciuto = testo
            if let (data, descrizione) = RiconoscimentoTesto.scadenza(in: testo) {
                elemento.scadenza = data
                elemento.scadenzaDescrizione = descrizione
            }
        }
        try? contesto.save()
    }

    /// Dà un nome ai luoghi partendo dalle coordinate. Serve la rete: senza,
    /// restano le coordinate e basta.
    static func completaLuoghi(contesto: ModelContext, quantiAlMassimo: Int = 80) async {
        guard let tutti = try? contesto.fetch(FetchDescriptor<Elemento>()) else { return }

        let senzaNome = tutti.filter { $0.haPosizione && ($0.luogo ?? "").isEmpty }
        guard !senzaNome.isEmpty else { return }

        // Le coordinate vicine danno lo stesso nome: si chiede una volta sola per
        // zona, invece di tempestare il servizio con migliaia di domande uguali.
        var giaChieste: [String: String] = [:]
        var fatte = 0

        for elemento in senzaNome {
            guard fatte < quantiAlMassimo else { break }
            guard let latitudine = elemento.latitudine, let longitudine = elemento.longitudine
            else { continue }

            let zona = String(format: "%.2f,%.2f", latitudine, longitudine)
            if let giaNota = giaChieste[zona] {
                elemento.luogo = giaNota
                continue
            }

            guard let nome = await LettoreMetadati.nomeLuogo(latitudine: latitudine,
                                                             longitudine: longitudine)
            else { continue }

            giaChieste[zona] = nome
            elemento.luogo = nome
            fatte += 1

            // Il servizio di Apple si arrabbia se lo si interroga troppo in fretta.
            try? await Task.sleep(for: .milliseconds(600))
        }
        try? contesto.save()
    }

    /// Riconosce cosa c'è nelle foto. Le parole sono approssimative: si tengono
    /// solo quelle di cui macOS è abbastanza sicuro, e solo quelle che sappiamo
    /// dire in italiano.
    static func riconosciScene(contesto: ModelContext, quantiAlMassimo: Int = 200) async {
        guard let tutti = try? contesto.fetch(FetchDescriptor<Elemento>()) else { return }

        let daFare = tutti
            .filter { !$0.nelCestino && $0.tipo == .foto && $0.paroleAutomatiche.isEmpty }
            .prefix(quantiAlMassimo)

        for elemento in daFare {
            let url = elemento.url
            let parole = await Task.detached(priority: .background) {
                etichette(per: url)
            }.value
            guard !parole.isEmpty else { continue }
            elemento.paroleAutomatiche = parole
        }
        try? contesto.save()
    }

    private nonisolated static func etichette(per url: URL) -> [String] {
        guard let immagine = Impronte.immagineRidotta(di: url, lato: 640) else { return [] }

        let richiesta = VNClassifyImageRequest()
        let esecutore = VNImageRequestHandler(cgImage: immagine, options: [:])
        guard (try? esecutore.perform([richiesta])) != nil,
              let esiti = richiesta.results else { return [] }

        return esiti
            .filter { $0.confidence > 0.6 }
            .prefix(12)
            .compactMap { Vocabolario.italiano[$0.identifier] }
            .reduce(into: [String]()) { elenco, parola in
                if !elenco.contains(parola) { elenco.append(parola) }
            }
            .prefix(4)
            .map { $0 }
    }
}

/// macOS risponde in inglese. Si tengono solo le parole che sappiamo tradurre:
/// un'app in italiano non può mettere "sunset" sotto una foto.
enum Vocabolario {
    static let italiano: [String: String] = [
        "sunset": "tramonto", "sunrise": "alba", "sky": "cielo", "cloud": "nuvole",
        "beach": "spiaggia", "sea": "mare", "ocean": "mare", "wave": "onde",
        "lagoon": "laguna", "lake": "lago", "river": "fiume", "waterfall": "cascata",
        "mountain": "montagna", "hill": "collina", "valley": "valle", "snow": "neve",
        "forest": "bosco", "tree": "alberi", "flower": "fiori", "garden": "giardino",
        "grass": "prato", "field": "campagna", "farm": "campagna",
        "city": "città", "street": "strada", "building": "edifici", "architecture": "architettura",
        "bridge": "ponte", "church": "chiesa", "castle": "castello", "tower": "torre",
        "square": "piazza", "market": "mercato", "shop": "negozio",
        "food": "cibo", "meal": "pranzo", "pasta": "pasta", "pizza": "pizza",
        "bread": "pane", "cake": "dolce", "dessert": "dolce", "wine": "vino",
        "coffee": "caffè", "drink": "bevande", "fruit": "frutta", "vegetable": "verdura",
        "restaurant": "ristorante", "kitchen": "cucina", "table": "tavola",
        "people": "persone", "person": "persona", "crowd": "folla", "child": "bambini",
        "portrait": "ritratto", "wedding": "matrimonio", "party": "festa",
        "dog": "cane", "cat": "gatto", "bird": "uccelli", "horse": "cavallo",
        "animal": "animali", "fish": "pesci",
        "car": "automobile", "bicycle": "bicicletta", "boat": "barca", "train": "treno",
        "airplane": "aereo", "motorcycle": "moto",
        "document": "documento", "text": "testo", "receipt": "scontrino",
        "book": "libro", "newspaper": "giornale", "screenshot": "schermata",
        "night": "notte", "fireworks": "fuochi d'artificio", "candle": "candele",
        "rain": "pioggia", "fog": "nebbia", "sun": "sole", "moon": "luna",
        "music": "musica", "concert": "concerto", "sport": "sport", "beach_umbrella": "ombrellone"
    ]
}
