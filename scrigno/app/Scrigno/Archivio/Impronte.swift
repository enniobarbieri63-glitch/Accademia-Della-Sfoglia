import Foundation
import CoreGraphics
import CryptoKit
import ImageIO

/// Le due impronte che servono a riconoscere i doppioni, più la misura di nitidezza
/// che serve a proporre quale scatto tenere in una raffica.
enum Impronte {

    /// Impronta esatta del file. Due file con la stessa impronta sono lo stesso file,
    /// anche se hanno nome diverso. Letta a pezzi, così un video da 4 GB non finisce
    /// tutto in memoria.
    static func sha256(di url: URL) throws -> String {
        let file = try FileHandle(forReadingFrom: url)
        defer { try? file.close() }

        var digest = SHA256()
        while true {
            let pezzo = try file.read(upToCount: 1 << 20) ?? Data()
            if pezzo.isEmpty { break }
            digest.update(data: pezzo)
        }
        return digest.finalize().map { String(format: "%02x", $0) }.joined()
    }

    /// Impronta percettiva a 64 bit (media delle frequenze basse). Due foto che
    /// si somigliano hanno impronte vicine: serve per le raffiche e per la stessa
    /// foto salvata in due formati.
    static func percettiva(di immagine: CGImage) -> Int {
        let lato = 32
        guard let grigi = scalaDiGrigio(immagine, lato: lato) else { return 0 }

        // Coseno discreto sulle due dimensioni, tenendo l'angolo 8×8 in alto a sinistra.
        var dct = [Double](repeating: 0, count: 8 * 8)
        for u in 0..<8 {
            for v in 0..<8 {
                var somma = 0.0
                for x in 0..<lato {
                    for y in 0..<lato {
                        somma += Double(grigi[y * lato + x])
                            * cos(Double(2 * x + 1) * Double(u) * .pi / Double(2 * lato))
                            * cos(Double(2 * y + 1) * Double(v) * .pi / Double(2 * lato))
                    }
                }
                dct[u * 8 + v] = somma
            }
        }

        // La media si calcola saltando la componente continua, che sposterebbe tutto.
        let senzaContinua = Array(dct.dropFirst())
        let mediana = senzaContinua.sorted()[senzaContinua.count / 2]

        var bit = 0
        for (indice, valore) in dct.enumerated() where valore > mediana {
            bit |= (1 << indice)
        }
        return bit
    }

    /// Quanti bit differiscono fra due impronte percettive. Sotto 10 le due foto
    /// si somigliano parecchio.
    static func distanza(_ a: Int, _ b: Int) -> Int {
        (a ^ b).nonzeroBitCount
    }

    /// Varianza del laplaciano: più è alta, più la foto è a fuoco.
    static func nitidezza(di immagine: CGImage) -> Double {
        let lato = 64
        guard let grigi = scalaDiGrigio(immagine, lato: lato) else { return 0 }

        var valori: [Double] = []
        valori.reserveCapacity((lato - 2) * (lato - 2))
        for y in 1..<(lato - 1) {
            for x in 1..<(lato - 1) {
                let centro = Double(grigi[y * lato + x]) * 4
                let intorno = Double(grigi[(y - 1) * lato + x])
                    + Double(grigi[(y + 1) * lato + x])
                    + Double(grigi[y * lato + x - 1])
                    + Double(grigi[y * lato + x + 1])
                valori.append(centro - intorno)
            }
        }
        guard !valori.isEmpty else { return 0 }
        let media = valori.reduce(0, +) / Double(valori.count)
        let varianza = valori.reduce(0) { $0 + ($1 - media) * ($1 - media) } / Double(valori.count)
        return varianza
    }

    /// Disegna l'immagine piccola e in scala di grigio, unica base per i due calcoli.
    private static func scalaDiGrigio(_ immagine: CGImage, lato: Int) -> [UInt8]? {
        guard let spazio = CGColorSpace(name: CGColorSpace.linearGray),
              let contesto = CGContext(data: nil, width: lato, height: lato,
                                       bitsPerComponent: 8, bytesPerRow: lato,
                                       space: spazio,
                                       bitmapInfo: CGImageAlphaInfo.none.rawValue)
        else { return nil }

        contesto.interpolationQuality = .medium
        contesto.draw(immagine, in: CGRect(x: 0, y: 0, width: lato, height: lato))

        guard let dati = contesto.data else { return nil }
        let puntatore = dati.bindMemory(to: UInt8.self, capacity: lato * lato)
        return Array(UnsafeBufferPointer(start: puntatore, count: lato * lato))
    }

    /// Carica una versione piccola del file, senza tirare in memoria un RAW da 60 MP.
    static func immagineRidotta(di url: URL, lato: Int = 256) -> CGImage? {
        guard let sorgente = CGImageSourceCreateWithURL(url as CFURL, nil) else { return nil }
        let opzioni: [CFString: Any] = [
            kCGImageSourceCreateThumbnailFromImageAlways: true,
            kCGImageSourceCreateThumbnailWithTransform: true,
            kCGImageSourceThumbnailMaxPixelSize: lato
        ]
        return CGImageSourceCreateThumbnailAtIndex(sorgente, 0, opzioni as CFDictionary)
    }
}
