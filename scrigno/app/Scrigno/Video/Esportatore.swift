import AVFoundation
import AppKit
import Observation

/// Taglio, velocità ed esportazione dei video. L'originale non viene mai riscritto:
/// l'esportazione crea un file nuovo dove decide l'utente.
@MainActor
@Observable
final class Esportatore {

    private(set) var inCorso = false
    private(set) var avanzamento: Double = 0
    private(set) var problema: String?
    private(set) var finito: URL?

    private var sessione: AVAssetExportSession?

    func esporta(_ origine: URL, con modifiche: Modifiche, in destinazione: URL) async {
        inCorso = true
        avanzamento = 0
        problema = nil
        finito = nil
        defer { inCorso = false; sessione = nil }

        do {
            let montaggio = try await prepara(origine, con: modifiche)
            guard let sessione = AVAssetExportSession(asset: montaggio,
                                                      presetName: AVAssetExportPreset1920x1080)
            else {
                problema = "Questo video non si può esportare a 1080p."
                return
            }
            self.sessione = sessione

            // Il file di destinazione potrebbe già esserci da un tentativo andato male.
            try? FileManager.default.removeItem(at: destinazione)

            // Un compito a parte guarda l'avanzamento mentre l'altro esporta.
            let osservatore = Task { @MainActor in
                for await stato in sessione.states(updateInterval: 0.25) {
                    if case .exporting(let quanto) = stato {
                        avanzamento = quanto.fractionCompleted
                    }
                }
            }
            defer { osservatore.cancel() }

            try await sessione.export(to: destinazione, as: .mp4)
            avanzamento = 1
            finito = destinazione

        } catch {
            problema = error.localizedDescription
        }
    }

    func ferma() {
        sessione?.cancelExport()
        inCorso = false
    }

    /// Costruisce il montaggio: la parte scelta, alla velocità scelta.
    private func prepara(_ origine: URL, con modifiche: Modifiche) async throws -> AVAsset {
        let filmato = AVURLAsset(url: origine)
        let lunghezza = try await filmato.load(.duration)

        let inizio = CMTime(seconds: modifiche.inizio ?? 0, preferredTimescale: 600)
        let fine = modifiche.fine.map { CMTime(seconds: $0, preferredTimescale: 600) } ?? lunghezza
        let tratto = CMTimeRange(start: inizio, end: fine)

        // Senza taglio e a velocità normale non c'è niente da montare.
        guard modifiche.inizio != nil || modifiche.fine != nil || modifiche.velocita != 1 else {
            return filmato
        }

        let montaggio = AVMutableComposition()

        for tipo in [AVMediaType.video, .audio] {
            let tracce = try await filmato.loadTracks(withMediaType: tipo)
            guard let traccia = tracce.first,
                  let destinazione = montaggio.addMutableTrack(withMediaType: tipo,
                                                               preferredTrackID: kCMPersistentTrackID_Invalid)
            else { continue }

            try destinazione.insertTimeRange(tratto, of: traccia, at: .zero)
            if tipo == .video {
                destinazione.preferredTransform = try await traccia.load(.preferredTransform)
            }
        }

        if modifiche.velocita != 1 {
            let nuovaDurata = CMTimeMultiplyByFloat64(montaggio.duration,
                                                      multiplier: 1 / modifiche.velocita)
            montaggio.scaleTimeRange(CMTimeRange(start: .zero, duration: montaggio.duration),
                                     toDuration: nuovaDurata)
        }

        return montaggio
    }

    // MARK: - Fotogrammi

    /// La striscia di fotogrammi sotto il lettore.
    static func striscia(di filmato: AVAsset, quanti: Int) async -> [NSImage] {
        guard let lunghezza = try? await filmato.load(.duration) else { return [] }
        let secondi = CMTimeGetSeconds(lunghezza)
        guard secondi.isFinite, secondi > 0 else { return [] }

        let generatore = AVAssetImageGenerator(asset: filmato)
        generatore.appliesPreferredTrackTransform = true
        generatore.maximumSize = CGSize(width: 240, height: 240)
        generatore.requestedTimeToleranceBefore = CMTime(seconds: 0.6, preferredTimescale: 600)
        generatore.requestedTimeToleranceAfter = CMTime(seconds: 0.6, preferredTimescale: 600)

        var immagini: [NSImage] = []
        for indice in 0..<quanti {
            let istante = secondi * Double(indice) / Double(quanti)
            guard let cg = try? await generatore.image(
                at: CMTime(seconds: istante, preferredTimescale: 600)
            ).image else { continue }
            immagini.append(NSImage(cgImage: cg, size: NSSize(width: cg.width, height: cg.height)))
        }
        return immagini
    }

    /// Il singolo fotogramma da salvare come foto.
    static func fotogramma(di filmato: AVAsset, a secondi: Double) async -> NSImage? {
        let generatore = AVAssetImageGenerator(asset: filmato)
        generatore.appliesPreferredTrackTransform = true
        generatore.requestedTimeToleranceBefore = .zero
        generatore.requestedTimeToleranceAfter = .zero

        guard let cg = try? await generatore.image(
            at: CMTime(seconds: secondi, preferredTimescale: 600)
        ).image else { return nil }

        return NSImage(cgImage: cg, size: NSSize(width: cg.width, height: cg.height))
    }
}
