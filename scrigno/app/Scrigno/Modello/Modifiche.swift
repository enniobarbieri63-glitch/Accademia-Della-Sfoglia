import Foundation

/// Le modifiche a una foto o a un video vivono qui, separate dal file originale.
/// L'originale non viene mai riscritto: "Ripristina originale" vuota questa struttura.
struct Modifiche: Codable, Equatable, Sendable {
    var stile: String = Stile.originale.rawValue

    // MARK: Luce, da -100 a +100
    var esposizione: Double = 0
    var contrasto: Double = 0
    var alteLuci: Double = 0
    var ombre: Double = 0

    // MARK: Colore, da -100 a +100
    var temperatura: Double = 0
    var saturazione: Double = 0

    // MARK: Dettaglio e vignettatura, da 0 a 100
    var nitidezza: Double = 0
    var riduzioneRumore: Double = 0
    var vignettatura: Double = 0

    // MARK: Sfocatura
    /// Quanto sfoca, da 0 a 100. A zero il gruppo non fa niente.
    var sfocatura: Double = 0
    var sfocaturaModo: String = ModoSfocatura.cerchio.rawValue
    /// Dove cade il fuoco, in frazioni del lato (0…1), origine in alto a sinistra.
    var fuocoX: Double = 0.5
    var fuocoY: Double = 0.5
    /// Quanto è larga la zona che resta nitida, da 0 a 100.
    var profonditaCampo: Double = 40
    /// Inclinazione della fascia a fuoco, in gradi. Vale solo per il modo "banda".
    var inclinazioneFuoco: Double = 0
    /// Nome del file della maschera dipinta a mano. Vale solo per il modo "pennello".
    var maschera: String?

    // MARK: Geometria
    /// Gradi di rotazione fine per rimettere in piano un orizzonte storto (-15…+15).
    var raddrizza: Double = 0
    /// Rotazioni da un quarto di giro: 0, 1, 2, 3.
    var rotazioneQuarti: Int = 0
    var ribaltaOrizzontale: Bool = false
    var ribaltaVerticale: Bool = false
    /// Linee cadenti: positivo allarga in alto, negativo allarga in basso (-100…100).
    var prospettivaVerticale: Double = 0
    /// Stessa cosa sui lati, per le pareti riprese di sbieco (-100…100).
    var prospettivaOrizzontale: Double = 0

    var ritaglio: Ritaglio?

    // MARK: Video
    var inizio: Double?
    var fine: Double?
    var velocita: Double = 1

    var nessuna: Bool { self == Modifiche() }

    /// Vero quando la geometria è stata toccata: serve a sapere se il ritaglio
    /// automatico deve entrare in gioco.
    var geometriaToccata: Bool {
        raddrizza != 0 || rotazioneQuarti != 0 || ribaltaOrizzontale || ribaltaVerticale
            || prospettivaVerticale != 0 || prospettivaOrizzontale != 0
    }

    static func da(_ dati: Data?) -> Modifiche {
        guard let dati, let lette = try? JSONDecoder().decode(Modifiche.self, from: dati) else {
            return Modifiche()
        }
        return lette
    }

    var codificate: Data? {
        nessuna ? nil : try? JSONEncoder().encode(self)
    }
}

/// Come è fatta la zona che resta a fuoco.
enum ModoSfocatura: String, CaseIterable, Identifiable, Sendable {
    /// Una fascia nitida che attraversa la foto, inclinabile: paesaggi, tavole.
    case banda = "Banda"
    /// Una zona nitida tonda attorno al soggetto: ritratti, oggetti.
    case cerchio = "Cerchio"
    /// Segue le distanze vere, quando la foto se le porta dietro (ritratti da iPhone).
    case profondita = "Dal telefono"
    /// La zona la dipingi tu, a mano.
    case pennello = "Pennello"

    var id: String { rawValue }

    var spiegazione: String {
        switch self {
        case .banda: "La parte nitida è una fascia che puoi inclinare."
        case .cerchio: "Clicca sulla foto: la parte nitida è tonda, attorno a quel punto."
        case .profondita: "Usa le distanze registrate dalla fotocamera del telefono."
        case .pennello: "Dipingi tu la zona da sfocare."
        }
    }

    var simbolo: String {
        switch self {
        case .banda: "rectangle.split.3x1"
        case .cerchio: "circle.dashed"
        case .profondita: "cube.transparent"
        case .pennello: "paintbrush"
        }
    }
}

/// Ritaglio espresso in frazioni del lato, così resta valido a qualsiasi risoluzione.
struct Ritaglio: Codable, Equatable, Sendable {
    var x: Double = 0
    var y: Double = 0
    var larghezza: Double = 1
    var altezza: Double = 1

    var interoFotogramma: Bool {
        x == 0 && y == 0 && larghezza == 1 && altezza == 1
    }
}

/// Le proporzioni pronte del ritaglio.
enum Proporzioni: String, CaseIterable, Identifiable, Sendable {
    case libero = "Libero"
    case originale = "Originale"
    case quadrato = "1:1"
    case quattroTerzi = "4:3"
    case treTerzi = "3:2"
    case sedaciNoni = "16:9"

    var id: String { rawValue }

    /// Larghezza diviso altezza. `nil` quando non c'è un vincolo.
    var rapporto: Double? {
        switch self {
        case .libero, .originale: nil
        case .quadrato: 1
        case .quattroTerzi: 4.0 / 3.0
        case .treTerzi: 3.0 / 2.0
        case .sedaciNoni: 16.0 / 9.0
        }
    }
}

/// Gli stili pronti della schermata Modifica.
enum Stile: String, CaseIterable, Identifiable, Sendable {
    case originale = "Originale"
    case vivido = "Vivido"
    case caldo = "Caldo"
    case freddo = "Freddo"
    case pellicola = "Pellicola"
    case drammatico = "Drammatico"
    case biancoENero = "Bianco e nero"
    case argento = "Argento"

    var id: String { rawValue }

    /// Ogni stile è solo un punto di partenza sui cursori: resta tutto reversibile.
    var regolazioni: (saturazione: Double, contrasto: Double, temperatura: Double,
                      monocromatico: Bool, esposizione: Double) {
        switch self {
        case .originale:   (0, 0, 0, false, 0)
        case .vivido:      (35, 8, 0, false, 0)
        case .caldo:       (20, 0, 30, false, 0)
        case .freddo:      (-10, 0, -25, false, 3)
        case .pellicola:   (-8, -10, 12, false, 5)
        case .drammatico:  (5, 30, 0, false, -10)
        case .biancoENero: (0, 10, 0, true, 0)
        case .argento:     (0, -10, 0, true, 12)
        }
    }
}
