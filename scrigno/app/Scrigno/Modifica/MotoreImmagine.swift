import CoreImage
import CoreImage.CIFilterBuiltins
import Foundation

/// Applica le modifiche a una foto senza mai toccare il file originale.
/// Riceve l'immagine letta dal disco e restituisce quella da mostrare o esportare.
enum MotoreImmagine {

    static let contesto = CIContext(options: [.useSoftwareRenderer: false])

    /// `fileOriginale` serve solo alla sfocatura "dal telefono", che va a ripescare
    /// le distanze registrate dalla fotocamera dentro il file.
    static func applica(_ modifiche: Modifiche, a partenza: CIImage,
                        fileOriginale: URL? = nil) -> CIImage {
        var immagine = partenza

        // L'ordine conta: prima si raddrizza e si ribalta, poi si ritaglia, poi si
        // lavora su luce e colore. Al contrario il ritaglio cadrebbe nel posto sbagliato.
        immagine = applicaGeometria(modifiche, a: immagine)
        immagine = applicaRitaglio(modifiche, a: immagine)
        immagine = applicaStileELuce(modifiche, a: immagine)
        immagine = applicaDettaglio(modifiche, a: immagine)
        immagine = Sfocatura.applica(modifiche, a: immagine, fileOriginale: fileOriginale)
        immagine = applicaVignettatura(modifiche, a: immagine)

        return immagine
    }

    // MARK: - Geometria

    private static func applicaGeometria(_ m: Modifiche, a partenza: CIImage) -> CIImage {
        var immagine = partenza

        if m.ribaltaOrizzontale || m.ribaltaVerticale {
            let riquadro = immagine.extent
            var trasformazione = CGAffineTransform.identity
            trasformazione = trasformazione.translatedBy(x: riquadro.midX, y: riquadro.midY)
            trasformazione = trasformazione.scaledBy(x: m.ribaltaOrizzontale ? -1 : 1,
                                                     y: m.ribaltaVerticale ? -1 : 1)
            trasformazione = trasformazione.translatedBy(x: -riquadro.midX, y: -riquadro.midY)
            immagine = immagine.transformed(by: trasformazione)
        }

        let quarti = ((m.rotazioneQuarti % 4) + 4) % 4
        if quarti != 0 {
            let riquadro = immagine.extent
            let angolo = -Double(quarti) * .pi / 2   // in senso orario, come si aspetta chi guarda
            var trasformazione = CGAffineTransform.identity
            trasformazione = trasformazione.translatedBy(x: riquadro.midX, y: riquadro.midY)
            trasformazione = trasformazione.rotated(by: angolo)
            trasformazione = trasformazione.translatedBy(x: -riquadro.midX, y: -riquadro.midY)
            immagine = immagine.transformed(by: trasformazione)
        }

        if m.prospettivaVerticale != 0 || m.prospettivaOrizzontale != 0 {
            immagine = correggiProspettiva(immagine,
                                           verticale: m.prospettivaVerticale,
                                           orizzontale: m.prospettivaOrizzontale)
        }

        if m.raddrizza != 0 {
            let filtro = CIFilter.straightenFilter()
            filtro.inputImage = immagine
            filtro.angle = Float(-m.raddrizza * .pi / 180)
            if let raddrizzata = filtro.outputImage {
                immagine = raddrizzata
            }
        }

        return immagine.cropped(to: immagine.extent.integral)
    }

    /// Le linee cadenti: un palazzo ripreso dal basso si chiude verso l'alto.
    /// Si rimette in piano allargando il lato che si è ristretto.
    private static func correggiProspettiva(_ immagine: CIImage,
                                            verticale: Double,
                                            orizzontale: Double) -> CIImage {
        let riquadro = immagine.extent
        // Al massimo un quarto della larghezza: oltre l'immagine si sfascia.
        let scostamentoV = riquadro.width * 0.25 * (verticale / 100)
        let scostamentoO = riquadro.height * 0.25 * (orizzontale / 100)

        let filtro = CIFilter.perspectiveTransform()
        filtro.inputImage = immagine
        // In Core Image l'origine è in basso a sinistra.
        filtro.topLeft = CGPoint(x: riquadro.minX - scostamentoV, y: riquadro.maxY + scostamentoO)
        filtro.topRight = CGPoint(x: riquadro.maxX + scostamentoV, y: riquadro.maxY - scostamentoO)
        filtro.bottomLeft = CGPoint(x: riquadro.minX + scostamentoV, y: riquadro.minY - scostamentoO)
        filtro.bottomRight = CGPoint(x: riquadro.maxX - scostamentoV, y: riquadro.minY + scostamentoO)

        return filtro.outputImage ?? immagine
    }

    // MARK: - Ritaglio

    private static func applicaRitaglio(_ m: Modifiche, a immagine: CIImage) -> CIImage {
        // Quando la geometria è stata toccata restano bordi vuoti agli angoli:
        // si stringe un poco l'inquadratura per non farli vedere.
        var riquadro = immagine.extent
        if m.geometriaToccata && m.ritaglio == nil {
            let stretta = riquadro.insetBy(dx: riquadro.width * 0.04, dy: riquadro.height * 0.04)
            riquadro = stretta
        }

        guard let ritaglio = m.ritaglio, !ritaglio.interoFotogramma else {
            return immagine.cropped(to: riquadro)
        }

        let intero = immagine.extent
        // Il ritaglio arriva con l'origine in alto a sinistra, Core Image la vuole in basso.
        let tagliato = CGRect(
            x: intero.minX + intero.width * ritaglio.x,
            y: intero.minY + intero.height * (1 - ritaglio.y - ritaglio.altezza),
            width: intero.width * ritaglio.larghezza,
            height: intero.height * ritaglio.altezza
        )
        return immagine.cropped(to: tagliato.integral)
    }

    // MARK: - Luce e colore

    private static func applicaStileELuce(_ m: Modifiche, a partenza: CIImage) -> CIImage {
        var immagine = partenza
        let stile = Stile(rawValue: m.stile) ?? .originale
        let base = stile.regolazioni

        let esposizione = (m.esposizione + base.esposizione) / 100
        let contrasto = (m.contrasto + base.contrasto) / 100
        let saturazione = (m.saturazione + base.saturazione) / 100
        let temperatura = (m.temperatura + base.temperatura) / 100

        if esposizione != 0 {
            let filtro = CIFilter.exposureAdjust()
            filtro.inputImage = immagine
            filtro.ev = Float(esposizione * 2)
            immagine = filtro.outputImage ?? immagine
        }

        if temperatura != 0 {
            let filtro = CIFilter.temperatureAndTint()
            filtro.inputImage = immagine
            filtro.neutral = CIVector(x: 6500, y: 0)
            // Verso l'ambra se positivo, verso il blu se negativo.
            filtro.targetNeutral = CIVector(x: 6500 - temperatura * 2500, y: 0)
            immagine = filtro.outputImage ?? immagine
        }

        if contrasto != 0 || saturazione != 0 || base.monocromatico {
            let filtro = CIFilter.colorControls()
            filtro.inputImage = immagine
            filtro.contrast = Float(1 + contrasto * 0.5)
            filtro.saturation = base.monocromatico ? 0 : Float(1 + saturazione * 0.8)
            filtro.brightness = 0
            immagine = filtro.outputImage ?? immagine
        }

        if m.alteLuci != 0 || m.ombre != 0 {
            let filtro = CIFilter.highlightShadowAdjust()
            filtro.inputImage = immagine
            // Core Image vuole 0…1 per le alte luci: 1 vuol dire "non toccarle".
            filtro.highlightAmount = Float(1 + min(0, m.alteLuci / 100))
            filtro.shadowAmount = Float(m.ombre / 100)
            filtro.radius = 8
            immagine = filtro.outputImage ?? immagine
        }

        return immagine
    }

    private static func applicaDettaglio(_ m: Modifiche, a partenza: CIImage) -> CIImage {
        var immagine = partenza

        if m.riduzioneRumore > 0 {
            let filtro = CIFilter.noiseReduction()
            filtro.inputImage = immagine
            filtro.noiseLevel = Float(m.riduzioneRumore / 100 * 0.06)
            filtro.sharpness = 0.4
            immagine = filtro.outputImage ?? immagine
        }

        if m.nitidezza > 0 {
            let filtro = CIFilter.unsharpMask()
            filtro.inputImage = immagine
            filtro.radius = 2.5
            filtro.intensity = Float(m.nitidezza / 100 * 1.2)
            immagine = filtro.outputImage ?? immagine
        }

        return immagine
    }

    private static func applicaVignettatura(_ m: Modifiche, a immagine: CIImage) -> CIImage {
        guard m.vignettatura > 0 else { return immagine }
        let filtro = CIFilter.vignette()
        filtro.inputImage = immagine
        filtro.intensity = Float(m.vignettatura / 100 * 2)
        filtro.radius = 1.5
        return filtro.outputImage ?? immagine
    }

    // MARK: - Auto

    /// I suggerimenti che macOS ricava dalla foto, riportati sui cursori così
    /// restano modificabili a mano invece di essere una scatola chiusa.
    static func regolazioniAutomatiche(per immagine: CIImage) -> Modifiche {
        var proposta = Modifiche()

        let analisi = immagine.autoAdjustmentFilters(options: [.enhance: true, .redEye: false])
        for filtro in analisi {
            switch filtro.name {
            case "CIToneCurve":
                proposta.contrasto = 12
                proposta.ombre = 10
            case "CIVibrance":
                proposta.saturazione = 15
            case "CIHighlightShadowAdjust":
                proposta.alteLuci = -15
                proposta.ombre = 18
            default:
                break
            }
        }

        // Se l'analisi non ha proposto niente, meglio un ritocco leggero che nulla.
        if proposta.nessuna {
            proposta.contrasto = 8
            proposta.saturazione = 10
            proposta.ombre = 8
        }
        return proposta
    }
}
