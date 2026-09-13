import AppKit
import Foundation
import Observation

/// Quello che la finestra Importa mostra della ricezione dal telefono:
/// l'indirizzo, il codice QR, i file arrivati finora.
@MainActor
@Observable
final class ArrivoDaTelefono {

    enum Stato: Equatable {
        case spento
        case accendo
        case pronto
        case guasto(String)
    }

    private(set) var stato: Stato = .spento
    private(set) var indirizzo: String?
    private(set) var arrivati: [URL] = []
    private(set) var ultimoNome: String?

    var codice: String { servitore.codice }
    var codiceQR: NSImage? {
        guard let indirizzo else { return nil }
        return ReteLocale.codiceQR(per: indirizzo)
    }

    /// Chiamata ogni volta che il telefono manda qualcosa, con tutti i file
    /// arrivati finora: la finestra Importa li rimette in coda da sola.
    var quandoArrivaRoba: (([URL]) -> Void)?

    private let servitore: ServitoreWiFi

    init() {
        servitore = ServitoreWiFi(cartellaArrivi: Libreria.condivisa.inArrivo)
        servitore.quandoCambia = { [weak self] acceso, motivo in
            Task { @MainActor in self?.aggiorna(acceso: acceso, motivo: motivo) }
        }
        servitore.quandoArrivano = { [weak self] nuovi in
            Task { @MainActor in self?.registra(nuovi) }
        }
    }

    func accendi() {
        guard stato == .spento else { return }
        stato = .accendo
        servitore.avvia()
    }

    func spegni() {
        servitore.ferma()
        stato = .spento
        indirizzo = nil
    }

    func dimentica() {
        arrivati = []
        ultimoNome = nil
    }

    private func aggiorna(acceso: Bool, motivo: String?) {
        if let motivo {
            stato = .guasto(motivo)
            indirizzo = nil
            return
        }
        if acceso {
            indirizzo = servitore.indirizzo
            // Senza rete non c'è nessun indirizzo da mostrare: meglio dirlo che
            // far inquadrare un codice che non porta da nessuna parte.
            stato = indirizzo == nil
                ? .guasto("Il Mac non sembra collegato a una rete Wi-Fi o via cavo.")
                : .pronto
        } else {
            stato = .spento
            indirizzo = nil
        }
    }

    private func registra(_ nuovi: [URL]) {
        arrivati.append(contentsOf: nuovi)
        ultimoNome = nuovi.last?.lastPathComponent
        quandoArrivaRoba?(arrivati)
    }
}
