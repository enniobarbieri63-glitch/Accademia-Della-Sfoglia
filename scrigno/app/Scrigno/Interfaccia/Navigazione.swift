import Foundation
import Observation
import SwiftData

/// Dove si trova l'utente in questo momento: quale schermata, quale cassetto,
/// cosa ha selezionato, cosa sta cercando.
@MainActor
@Observable
final class Navigazione {

    enum Schermata: String, CaseIterable, Identifiable {
        case libreria, video, modifica, documenti, persone, doppioni

        var id: String { rawValue }

        var titolo: String {
            switch self {
            case .libreria: "Libreria"
            case .video: "Video"
            case .modifica: "Modifica"
            case .documenti: "Documenti"
            case .persone: "Persone e luoghi"
            case .doppioni: "Doppioni"
            }
        }

        var tastoRapido: Character {
            switch self {
            case .libreria: "1"
            case .video: "2"
            case .modifica: "3"
            case .documenti: "4"
            case .persone: "5"
            case .doppioni: "6"
            }
        }
    }

    /// Il cassetto aperto nella barra laterale.
    enum Cassetto: Hashable {
        case tutto
        case tipo(TipoFile)
        case preferiti
        case senzaEtichetta
        case scontrini
        case cestino
        case etichetta(PersistentIdentifier)
        case album(PersistentIdentifier)
        case persona(PersistentIdentifier)
        case luogo(String)
    }

    enum Raggruppamento: String, CaseIterable, Identifiable {
        case anni = "Anni"
        case mesi = "Mesi"
        case giorni = "Giorni"
        case tutti = "Tutti"

        var id: String { rawValue }
    }

    var schermata: Schermata = .libreria
    var cassetto: Cassetto = .tutto
    var raggruppamento: Raggruppamento = .giorni
    var ricerca = ""
    var selezione: Set<PersistentIdentifier> = []
    /// L'elemento aperto nella schermata Modifica o nel lettore video.
    var elementoAperto: PersistentIdentifier?

    var mostraImporta = false
    var mostraAnteprimaGrande = false
    /// Alzata da ⌘F: la schermata a fuoco porta il cursore nella sua ricerca.
    var chiediFuocoRicerca = false

    /// Il nome del cassetto, per la riga in alto e per i messaggi di "qui non c'è niente".
    func nomeCassetto(etichette: [Etichetta], album: [Album]) -> String {
        switch cassetto {
        case .tutto: "Tutto"
        case .tipo(let tipo): tipo.nomePlurale
        case .preferiti: "Preferiti"
        case .senzaEtichetta: "Senza etichetta"
        case .scontrini: "Scontrini e ricevute"
        case .cestino: "Eliminati di recente"
        case .etichetta(let id): etichette.first { $0.persistentModelID == id }?.nome ?? "Etichetta"
        case .album(let id): album.first { $0.persistentModelID == id }?.nome ?? "Album"
        case .persona: "Persona"
        case .luogo(let nome): nome
        }
    }

    func vai(_ dove: Schermata) {
        schermata = dove
    }

    func apri(_ cassetto: Cassetto) {
        self.cassetto = cassetto
        ricerca = ""
        schermata = .libreria
    }

    func apriInModifica(_ elemento: Elemento) {
        elementoAperto = elemento.persistentModelID
        schermata = .modifica
    }

    func apriNelLettore(_ elemento: Elemento) {
        elementoAperto = elemento.persistentModelID
        schermata = .video
    }
}
