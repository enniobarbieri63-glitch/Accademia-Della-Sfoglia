import Foundation
import PDFKit
import Vision

/// Legge il testo dentro i documenti: dai PDF lo prende direttamente, dalle
/// scansioni e dalle foto lo fa riconoscere a macOS.
///
/// Quello che **non** fa: aprire i file di Word, Excel e Keynote. Sono archivi
/// compressi e leggerli richiede un lavoro a parte.
enum RiconoscimentoTesto {

    static func leggi(_ url: URL, tipo: TipoFile) async -> String {
        switch tipo {
        case .documento:
            if url.pathExtension.lowercased() == "pdf" {
                return await daPDF(url)
            }
            return ""
        case .foto:
            return await daImmagine(url)
        default:
            return ""
        }
    }

    /// Dai PDF nati al computer il testo c'è già. Da quelli che sono solo una
    /// fotografia di un foglio non c'è niente, e allora si guardano le pagine.
    private static func daPDF(_ url: URL) async -> String {
        guard let documento = PDFDocument(url: url) else { return "" }

        var pezzi: [String] = []
        for indice in 0..<min(documento.pageCount, 30) {
            guard let pagina = documento.page(at: indice) else { continue }
            if let testo = pagina.string, testo.trimmingCharacters(in: .whitespacesAndNewlines).count > 20 {
                pezzi.append(testo)
            } else if let immagine = immaginePagina(pagina) {
                pezzi.append(await riconosci(immagine))
            }
        }
        return pulisci(pezzi.joined(separator: "\n"))
    }

    private static func daImmagine(_ url: URL) async -> String {
        guard let immagine = Impronte.immagineRidotta(di: url, lato: 2000) else { return "" }
        return pulisci(await riconosci(immagine))
    }

    private static func immaginePagina(_ pagina: PDFPage) -> CGImage? {
        let riquadro = pagina.bounds(for: .mediaBox)
        guard riquadro.width > 0, riquadro.height > 0 else { return nil }

        let scala = min(2.0, 2000 / max(riquadro.width, riquadro.height))
        let misura = NSSize(width: riquadro.width * scala, height: riquadro.height * scala)
        let resa = pagina.thumbnail(of: misura, for: .mediaBox)

        var rettangolo = CGRect(origin: .zero, size: misura)
        return resa.cgImage(forProposedRect: &rettangolo, context: nil, hints: nil)
    }

    private static func riconosci(_ immagine: CGImage) async -> String {
        await withCheckedContinuation { risposta in
            let richiesta = VNRecognizeTextRequest { esito, _ in
                let righe = (esito.results as? [VNRecognizedTextObservation] ?? [])
                    .compactMap { $0.topCandidates(1).first?.string }
                risposta.resume(returning: righe.joined(separator: "\n"))
            }
            richiesta.recognitionLevel = .accurate
            richiesta.usesLanguageCorrection = true
            richiesta.recognitionLanguages = ["it-IT", "en-US"]

            let esecutore = VNImageRequestHandler(cgImage: immagine, options: [:])
            do {
                try esecutore.perform([richiesta])
            } catch {
                risposta.resume(returning: "")
            }
        }
    }

    private static func pulisci(_ testo: String) -> String {
        testo
            .replacingOccurrences(of: "\r", with: "\n")
            .split(separator: "\n")
            .map { $0.trimmingCharacters(in: .whitespaces) }
            .filter { !$0.isEmpty }
            .joined(separator: "\n")
    }

    // MARK: - Scadenze

    /// Pesca una data di scadenza dentro il testo. Se il documento è scritto male
    /// non esce niente, e va bene così: meglio nessun promemoria che uno inventato.
    static func scadenza(in testo: String) -> (Date, String)? {
        let minuscolo = testo.lowercased()
        let paroleChiave = ["scade", "scadenza", "valida fino", "validità", "entro il", "termine"]
        guard paroleChiave.contains(where: { minuscolo.contains($0) }) else { return nil }

        guard let rilevatore = try? NSDataDetector(types: NSTextCheckingResult.CheckingType.date.rawValue)
        else { return nil }

        let intervallo = NSRange(testo.startIndex..<testo.endIndex, in: testo)
        let trovate = rilevatore.matches(in: testo, range: intervallo)
            .compactMap(\.date)
            .filter { $0 > Date() }

        guard let prima = trovate.min() else { return nil }

        let descrizione = paroleChiave.first { minuscolo.contains($0) }?.capitalized ?? "Scadenza"
        return (prima, descrizione)
    }
}
