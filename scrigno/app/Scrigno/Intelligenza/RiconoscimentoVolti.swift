import CoreGraphics
import Foundation
import SwiftData
import Vision

/// Trova i volti nelle foto e mette insieme quelli che si somigliano.
///
/// Da dire chiaro: macOS sa **trovare** i volti, non sa dire che due foto
/// mostrano la stessa persona. Il confronto qui sotto è fatto in casa e sbaglia
/// più dell'app Foto di Apple. I gruppi vanno corretti a mano, ed è previsto.
@MainActor
enum RiconoscimentoVolti {

    /// Lato del ritaglio con cui si confrontano i volti.
    private static let lato = 24
    /// Sotto questa distanza due volti finiscono nello stesso gruppo.
    private static let soglia: Float = 0.62

    static func analizza(contesto: ModelContext, quantiAlMassimo: Int = 150) async {
        guard let tutti = try? contesto.fetch(FetchDescriptor<Elemento>()) else { return }
        guard let personeEsistenti = try? contesto.fetch(FetchDescriptor<Persona>()) else { return }

        let giaGuardate = Set((try? contesto.fetch(FetchDescriptor<Volto>()))?
            .compactMap { $0.elemento?.persistentModelID } ?? [])

        let daGuardare = tutti
            .filter { !$0.nelCestino && $0.tipo == .foto }
            .filter { !giaGuardate.contains($0.persistentModelID) }
            .prefix(quantiAlMassimo)

        var persone = personeEsistenti

        for elemento in daGuardare {
            let url = elemento.url
            let trovati = await Task.detached(priority: .background) {
                cercaVolti(in: url)
            }.value

            for descrizione in trovati {
                let volto = Volto(elemento: elemento,
                                  x: descrizione.x, y: descrizione.y,
                                  larghezza: descrizione.larghezza, altezza: descrizione.altezza,
                                  qualita: descrizione.qualita,
                                  impronta: descrizione.impronta)
                contesto.insert(volto)

                if let vicina = personaPiuVicina(a: descrizione.impronta, fra: persone) {
                    volto.persona = vicina
                } else {
                    let nuova = Persona()
                    contesto.insert(nuova)
                    volto.persona = nuova
                    persone.append(nuova)
                }
            }
        }
        try? contesto.save()
    }

    private static func personaPiuVicina(a impronta: [Float], fra persone: [Persona]) -> Persona? {
        var miglioreDistanza = Float.greatestFiniteMagnitude
        var migliore: Persona?

        for persona in persone {
            for volto in persona.volti.prefix(8) {
                let distanza = distanza(impronta, volto.impronta)
                if distanza < miglioreDistanza {
                    miglioreDistanza = distanza
                    migliore = persona
                }
            }
        }
        return miglioreDistanza <= soglia ? migliore : nil
    }

    private static func distanza(_ a: [Float], _ b: [Float]) -> Float {
        guard a.count == b.count, !a.isEmpty else { return .greatestFiniteMagnitude }
        var somma: Float = 0
        for indice in a.indices {
            let differenza = a[indice] - b[indice]
            somma += differenza * differenza
        }
        return (somma / Float(a.count)).squareRoot()
    }

    // MARK: - Lavoro sui pixel

    struct Descrizione {
        var x: Double
        var y: Double
        var larghezza: Double
        var altezza: Double
        var qualita: Double
        var impronta: [Float]
    }

    nonisolated static func cercaVolti(in url: URL) -> [Descrizione] {
        guard let immagine = Impronte.immagineRidotta(di: url, lato: 1000) else { return [] }

        let richiesta = VNDetectFaceRectanglesRequest()
        let esecutore = VNImageRequestHandler(cgImage: immagine, options: [:])
        guard (try? esecutore.perform([richiesta])) != nil,
              let trovati = richiesta.results else { return [] }

        return trovati.compactMap { osservazione in
            let riquadro = osservazione.boundingBox   // origine in basso a sinistra, 0…1
            // I volti piccolissimi sono quasi sempre passanti sullo sfondo.
            guard riquadro.width > 0.04, riquadro.height > 0.04 else { return nil }

            guard let impronta = descrittore(di: immagine, riquadro: riquadro) else { return nil }

            return Descrizione(
                x: riquadro.minX,
                y: 1 - riquadro.maxY,
                larghezza: riquadro.width,
                altezza: riquadro.height,
                qualita: Double(riquadro.width * riquadro.height),
                impronta: impronta
            )
        }
    }

    /// Il ritaglio del volto, ridotto a pochissimi pixel grigi e normalizzato
    /// per luce e contrasto: due foto della stessa persona con luci diverse
    /// devono somigliarsi lo stesso.
    private nonisolated static func descrittore(di immagine: CGImage,
                                                riquadro: CGRect) -> [Float]? {
        let larghezza = CGFloat(immagine.width)
        let altezza = CGFloat(immagine.height)
        let ritaglio = CGRect(x: riquadro.minX * larghezza,
                              y: riquadro.minY * altezza,
                              width: riquadro.width * larghezza,
                              height: riquadro.height * altezza).integral

        guard ritaglio.width >= 8, ritaglio.height >= 8,
              let tagliata = immagine.cropping(to: ritaglio),
              let spazio = CGColorSpace(name: CGColorSpace.linearGray),
              let contesto = CGContext(data: nil, width: lato, height: lato,
                                       bitsPerComponent: 8, bytesPerRow: lato,
                                       space: spazio,
                                       bitmapInfo: CGImageAlphaInfo.none.rawValue)
        else { return nil }

        contesto.interpolationQuality = .medium
        contesto.draw(tagliata, in: CGRect(x: 0, y: 0, width: lato, height: lato))

        guard let dati = contesto.data else { return nil }
        let puntatore = dati.bindMemory(to: UInt8.self, capacity: lato * lato)
        var valori = (0..<(lato * lato)).map { Float(puntatore[$0]) / 255 }

        let media = valori.reduce(0, +) / Float(valori.count)
        let varianza = valori.reduce(0) { $0 + ($1 - media) * ($1 - media) } / Float(valori.count)
        let scarto = max(0.02, varianza.squareRoot())
        for indice in valori.indices { valori[indice] = (valori[indice] - media) / scarto }

        return valori
    }

    // MARK: - Correzioni a mano

    static func unisci(_ da: Persona, in verso: Persona, contesto: ModelContext) {
        for volto in da.volti { volto.persona = verso }
        contesto.delete(da)
        try? contesto.save()
    }

    static func staccaVolto(_ volto: Volto, contesto: ModelContext) {
        let nuova = Persona()
        contesto.insert(nuova)
        volto.persona = nuova
        try? contesto.save()
    }
}
