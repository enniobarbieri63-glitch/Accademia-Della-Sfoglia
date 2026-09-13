import CoreImage
import CoreImage.CIFilterBuiltins
import Foundation

/// La sfocatura con profondità di campo.
///
/// Tutto si regge su una **maschera**: un'immagine in bianco e nero grande quanto
/// la foto, dove il nero vuol dire "resta nitido" e il bianco "sfoca il massimo".
/// I quattro modi sono quattro maniere diverse di costruire quella maschera.
enum Sfocatura {

    static func applica(_ m: Modifiche, a immagine: CIImage, fileOriginale: URL?) -> CIImage {
        guard m.sfocatura > 0 else { return immagine }
        let modo = ModoSfocatura(rawValue: m.sfocaturaModo) ?? .cerchio

        guard let maschera = costruisciMaschera(modo: modo, m: m,
                                                riquadro: immagine.extent,
                                                fileOriginale: fileOriginale)
        else { return immagine }

        let raggio = Float(m.sfocatura / 100 * 28)

        let filtro = CIFilter.maskedVariableBlur()
        filtro.inputImage = immagine
        filtro.mask = maschera
        filtro.radius = raggio

        // La sfocatura sbava oltre i bordi: si ritaglia com'era.
        return (filtro.outputImage ?? immagine).cropped(to: immagine.extent)
    }

    // MARK: - Le quattro maschere

    private static func costruisciMaschera(modo: ModoSfocatura, m: Modifiche,
                                           riquadro: CGRect, fileOriginale: URL?) -> CIImage? {
        switch modo {
        case .cerchio:
            cerchio(m, riquadro: riquadro)
        case .banda:
            banda(m, riquadro: riquadro)
        case .pennello:
            dipinta(m, riquadro: riquadro) ?? cerchio(m, riquadro: riquadro)
        case .profondita:
            // Se la foto non porta con sé le distanze non ci si inventa niente:
            // si ricade sul cerchio, che almeno fa quello che l'utente vede.
            daProfondita(m, riquadro: riquadro, fileOriginale: fileOriginale)
                ?? cerchio(m, riquadro: riquadro)
        }
    }

    /// Zona nitida tonda attorno al punto scelto.
    private static func cerchio(_ m: Modifiche, riquadro: CGRect) -> CIImage? {
        let centro = puntoDiFuoco(m, riquadro: riquadro)
        let latoMaggiore = max(riquadro.width, riquadro.height)

        let interno = max(8, latoMaggiore * 0.5 * (m.profonditaCampo / 100))
        let sfumatura = max(12, latoMaggiore * 0.30 * (1 - m.profonditaCampo / 100))

        let filtro = CIFilter.radialGradient()
        filtro.center = centro
        filtro.radius0 = Float(interno)
        filtro.radius1 = Float(interno + sfumatura)
        filtro.color0 = CIColor.black
        filtro.color1 = CIColor.white

        return filtro.outputImage?.cropped(to: riquadro)
    }

    /// Fascia nitida che attraversa la foto, inclinabile.
    private static func banda(_ m: Modifiche, riquadro: CGRect) -> CIImage? {
        let centro = puntoDiFuoco(m, riquadro: riquadro)
        let latoMaggiore = max(riquadro.width, riquadro.height)

        let mezzaFascia = max(6, latoMaggiore * 0.35 * (m.profonditaCampo / 100))
        let sfumatura = max(10, latoMaggiore * 0.25 * (1 - m.profonditaCampo / 100))

        // Direzione perpendicolare alla fascia.
        let angolo = m.inclinazioneFuoco * .pi / 180
        let nx = -sin(angolo)
        let ny = cos(angolo)

        func gradiente(verso segno: Double) -> CIImage? {
            let filtro = CIFilter.linearGradient()
            filtro.point0 = CGPoint(x: centro.x + nx * mezzaFascia * segno,
                                    y: centro.y + ny * mezzaFascia * segno)
            filtro.point1 = CGPoint(x: centro.x + nx * (mezzaFascia + sfumatura) * segno,
                                    y: centro.y + ny * (mezzaFascia + sfumatura) * segno)
            filtro.color0 = CIColor.black
            filtro.color1 = CIColor.white
            return filtro.outputImage
        }

        guard let sopra = gradiente(verso: 1), let sotto = gradiente(verso: -1) else { return nil }

        // Il massimo dei due: nero solo dove entrambi sono neri, cioè dentro la fascia.
        let unione = CIFilter.maximumCompositing()
        unione.inputImage = sopra
        unione.backgroundImage = sotto
        return unione.outputImage?.cropped(to: riquadro)
    }

    /// La maschera dipinta a mano, riportata alla misura della foto.
    private static func dipinta(_ m: Modifiche, riquadro: CGRect) -> CIImage? {
        guard let nome = m.maschera,
              let dipinta = MaschereDipinte.carica(nome) else { return nil }

        let scalaX = riquadro.width / max(1, dipinta.extent.width)
        let scalaY = riquadro.height / max(1, dipinta.extent.height)
        let adattata = dipinta
            .transformed(by: CGAffineTransform(scaleX: scalaX, y: scalaY))
            .transformed(by: CGAffineTransform(translationX: riquadro.minX, y: riquadro.minY))

        // Un pennello morbido non basta: si sfuma ancora un poco perché il bordo
        // della sfocatura non si veda come un ritaglio di carta.
        let ammorbidita = CIFilter.gaussianBlur()
        ammorbidita.inputImage = adattata
        ammorbidita.radius = Float(max(2, riquadro.width * 0.004))

        return (ammorbidita.outputImage ?? adattata).cropped(to: riquadro)
    }

    /// Le distanze vere, quando la foto se le porta dietro: sfoca ciò che sta
    /// più lontano del piano scelto.
    private static func daProfondita(_ m: Modifiche, riquadro: CGRect,
                                     fileOriginale: URL?) -> CIImage? {
        guard let file = fileOriginale,
              let distanze = CIImage(contentsOf: file, options: [.auxiliaryDisparity: true])
        else { return nil }

        let scalaX = riquadro.width / max(1, distanze.extent.width)
        let scalaY = riquadro.height / max(1, distanze.extent.height)
        let adattata = distanze
            .transformed(by: CGAffineTransform(scaleX: scalaX, y: scalaY))
            .transformed(by: CGAffineTransform(translationX: riquadro.minX, y: riquadro.minY))

        let centro = puntoDiFuoco(m, riquadro: riquadro)
        let distanzaDelSoggetto = valore(di: adattata, nel: centro)

        // Più la profondità di campo è larga, più piano cresce la sfocatura.
        let pendenza = Float(6.0 - 5.0 * (m.profonditaCampo / 100))

        let differenza = CIFilter.colorMatrix()
        differenza.inputImage = adattata
        differenza.rVector = CIVector(x: CGFloat(-pendenza), y: 0, z: 0, w: 0)
        differenza.gVector = CIVector(x: CGFloat(-pendenza), y: 0, z: 0, w: 0)
        differenza.bVector = CIVector(x: CGFloat(-pendenza), y: 0, z: 0, w: 0)
        differenza.aVector = CIVector(x: 0, y: 0, z: 0, w: 0)
        let spostamento = CGFloat(distanzaDelSoggetto * pendenza)
        differenza.biasVector = CIVector(x: spostamento, y: spostamento, z: spostamento, w: 1)

        guard let grezza = differenza.outputImage else { return nil }

        let limitata = CIFilter.colorClamp()
        limitata.inputImage = grezza
        limitata.minComponents = CIVector(x: 0, y: 0, z: 0, w: 0)
        limitata.maxComponents = CIVector(x: 1, y: 1, z: 1, w: 1)

        return limitata.outputImage?.cropped(to: riquadro)
    }

    // MARK: - Utilità

    /// Il punto di fuoco arriva con l'origine in alto a sinistra, Core Image la
    /// vuole in basso.
    private static func puntoDiFuoco(_ m: Modifiche, riquadro: CGRect) -> CGPoint {
        CGPoint(x: riquadro.minX + riquadro.width * m.fuocoX,
                y: riquadro.minY + riquadro.height * (1 - m.fuocoY))
    }

    /// Legge un singolo pixel: serve a sapere quanto è lontano il soggetto.
    private static func valore(di immagine: CIImage, nel punto: CGPoint) -> Float {
        var pixel = [UInt8](repeating: 0, count: 4)
        let riquadro = CGRect(x: punto.x, y: punto.y, width: 1, height: 1)
        MotoreImmagine.contesto.render(immagine,
                                       toBitmap: &pixel,
                                       rowBytes: 4,
                                       bounds: riquadro,
                                       format: .RGBA8,
                                       colorSpace: nil)
        return Float(pixel[0]) / 255
    }
}
