import AppKit
import Foundation
import QuickLookThumbnailing

/// Le miniature della griglia. Vengono create una volta sola all'importazione e
/// salvate su disco: senza questa cache la griglia non regge 15.000 elementi.
actor Miniature {
    static let condivisa = Miniature()

    /// Lato lungo della miniatura salvata. Regge bene anche i riquadri grandi
    /// della griglia senza pesare.
    private let lato: CGFloat = 512
    private let fm = FileManager.default
    private var inMemoria = NSCache<NSString, NSImage>()

    private init() {
        inMemoria.countLimit = 400
    }

    private func percorso(per impronta: String) -> URL {
        // Due livelli di sottocartelle: una cartella con 15.000 file è lenta da aprire.
        let a = String(impronta.prefix(2))
        let b = String(impronta.dropFirst(2).prefix(2))
        return Libreria.condivisa.miniature
            .appendingPathComponent(a, isDirectory: true)
            .appendingPathComponent(b, isDirectory: true)
            .appendingPathComponent("\(impronta).jpg")
    }

    func miniatura(per elemento: Elemento) async -> NSImage? {
        await miniatura(impronta: elemento.improntaFile, file: elemento.url, tipo: elemento.tipo)
    }

    func miniatura(impronta: String, file: URL, tipo: TipoFile) async -> NSImage? {
        guard !impronta.isEmpty else { return nil }

        if let inCache = inMemoria.object(forKey: impronta as NSString) { return inCache }

        let suDisco = percorso(per: impronta)
        if let dati = try? Data(contentsOf: suDisco), let immagine = NSImage(data: dati) {
            inMemoria.setObject(immagine, forKey: impronta as NSString)
            return immagine
        }

        guard let creata = await crea(per: file, tipo: tipo) else { return nil }
        salva(creata, in: suDisco)
        inMemoria.setObject(creata, forKey: impronta as NSString)
        return creata
    }

    /// QuickLook sa già fare la prima pagina di un PDF, il primo fotogramma di un
    /// video e l'anteprima di una foto: un'unica strada invece di tre.
    private func crea(per file: URL, tipo: TipoFile) async -> NSImage? {
        guard fm.fileExists(atPath: file.path) else { return iconaDiSistema(per: file) }

        let richiesta = QLThumbnailGenerator.Request(
            fileAt: file,
            size: CGSize(width: lato, height: lato),
            scale: 1,
            representationTypes: .thumbnail
        )
        richiesta.iconMode = (tipo == .altro)

        do {
            let risultato = try await QLThumbnailGenerator.shared.generateBestRepresentation(for: richiesta)
            return NSImage(cgImage: risultato.cgImage, size: risultato.nsImage.size)
        } catch {
            return iconaDiSistema(per: file)
        }
    }

    /// Quando il file è rovinato o di un tipo che nessuno sa aprire, invece di un
    /// riquadro vuoto si mostra l'icona del tipo.
    private func iconaDiSistema(per file: URL) -> NSImage {
        let icona = NSWorkspace.shared.icon(forFile: file.path)
        icona.size = NSSize(width: 128, height: 128)
        return icona
    }

    private func salva(_ immagine: NSImage, in url: URL) {
        guard let tiff = immagine.tiffRepresentation,
              let rappresentazione = NSBitmapImageRep(data: tiff),
              let jpeg = rappresentazione.representation(using: .jpeg,
                                                        properties: [.compressionFactor: 0.82])
        else { return }
        try? fm.createDirectory(at: url.deletingLastPathComponent(), withIntermediateDirectories: true)
        try? jpeg.write(to: url)
    }

    func dimentica(_ impronta: String) {
        inMemoria.removeObject(forKey: impronta as NSString)
        try? fm.removeItem(at: percorso(per: impronta))
    }

    func svuota() {
        inMemoria.removeAllObjects()
        try? fm.removeItem(at: Libreria.condivisa.miniature)
        try? fm.createDirectory(at: Libreria.condivisa.miniature, withIntermediateDirectories: true)
    }
}
