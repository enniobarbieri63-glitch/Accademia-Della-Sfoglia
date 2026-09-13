import Foundation
import SwiftData

/// Una persona riconosciuta nelle foto. Il nome lo mette sempre l'utente:
/// l'app sa solo dire "questi volti si somigliano".
@Model
final class Persona {
    var nome: String = ""
    /// Finché è falso il gruppo sta in "Da riconoscere".
    var confermata: Bool = false
    var creataIl: Date = Date.distantPast

    @Relationship(deleteRule: .cascade, inverse: \Volto.persona)
    var volti: [Volto] = []

    init(nome: String = "", confermata: Bool = false) {
        self.nome = nome
        self.confermata = confermata
        self.creataIl = Date()
    }

    var quanteFoto: Int {
        Set(volti.compactMap { $0.elemento?.persistentModelID }).count
    }

    var voltoDiCopertina: Volto? {
        volti.max { $0.qualita < $1.qualita }
    }
}

/// Un volto trovato dentro una foto.
@Model
final class Volto {
    var elemento: Elemento?
    var persona: Persona?

    /// Riquadro in frazioni del lato della foto (0…1), origine in alto a sinistra.
    var x: Double = 0
    var y: Double = 0
    var larghezza: Double = 0
    var altezza: Double = 0

    /// Quanto è grande e pulito il volto: serve a scegliere la copertina.
    var qualita: Double = 0
    /// Descrittore numerico del volto, usato per raggruppare.
    var improntaDati: Data = Data()

    init(elemento: Elemento?, x: Double, y: Double, larghezza: Double, altezza: Double,
         qualita: Double, impronta: [Float]) {
        self.elemento = elemento
        self.x = x
        self.y = y
        self.larghezza = larghezza
        self.altezza = altezza
        self.qualita = qualita
        self.improntaDati = impronta.withUnsafeBufferPointer { Data(buffer: $0) }
    }

    var impronta: [Float] {
        improntaDati.withUnsafeBytes { grezzi in
            Array(grezzi.bindMemory(to: Float.self))
        }
    }
}
