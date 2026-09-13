import AppKit
import CoreImage
import Foundation

/// Le maschere dipinte a mano nella schermata Modifica.
///
/// Non finiscono dentro il catalogo, che si appesantirebbe: stanno in file loro
/// dentro la cartella della libreria, accanto alle foto, e i backup se le portano via.
enum MaschereDipinte {

    /// Lato lungo con cui si salvano. Basta e avanza: la maschera viene poi
    /// sfumata, quindi non serve la risoluzione piena della foto.
    static let lato = 1024

    private static var cartella: URL {
        Libreria.condivisa.radice.appendingPathComponent("Maschere", isDirectory: true)
    }

    private static func url(_ nome: String) -> URL {
        cartella.appendingPathComponent(nome)
    }

    static func nuovoNome() -> String { "\(UUID().uuidString).png" }

    static func salva(_ immagine: NSImage, comeNome nome: String) -> Bool {
        guard let tiff = immagine.tiffRepresentation,
              let rappresentazione = NSBitmapImageRep(data: tiff),
              let png = rappresentazione.representation(using: .png, properties: [:])
        else { return false }

        do {
            try FileManager.default.createDirectory(at: cartella, withIntermediateDirectories: true)
            try png.write(to: url(nome))
            return true
        } catch {
            return false
        }
    }

    static func carica(_ nome: String) -> CIImage? {
        CIImage(contentsOf: url(nome))
    }

    static func caricaNSImage(_ nome: String) -> NSImage? {
        NSImage(contentsOf: url(nome))
    }

    static func elimina(_ nome: String?) {
        guard let nome else { return }
        try? FileManager.default.removeItem(at: url(nome))
    }

    /// Una maschera tutta nera: niente sfocatura finché non si comincia a dipingere.
    static func vuota(larghezza: Int, altezza: Int) -> NSImage {
        let dimensione = NSSize(width: larghezza, height: altezza)
        let immagine = NSImage(size: dimensione)
        immagine.lockFocus()
        NSColor.black.setFill()
        NSRect(origin: .zero, size: dimensione).fill()
        immagine.unlockFocus()
        return immagine
    }

    /// Misura con cui conviene dipingere, mantenendo le proporzioni della foto.
    static func misuraDiLavoro(perLarghezza larghezza: Int, altezza: Int) -> (Int, Int) {
        guard larghezza > 0, altezza > 0 else { return (lato, lato) }
        let maggiore = max(larghezza, altezza)
        guard maggiore > lato else { return (larghezza, altezza) }
        let fattore = Double(lato) / Double(maggiore)
        return (max(1, Int(Double(larghezza) * fattore)), max(1, Int(Double(altezza) * fattore)))
    }
}
