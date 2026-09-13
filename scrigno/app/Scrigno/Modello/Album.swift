import Foundation
import SwiftData

/// Una raccolta messa insieme dall'utente, o creata dall'importazione quando
/// riconosce i giorni di un viaggio.
@Model
final class Album {
    var nome: String = ""
    var creatoIl: Date = Date.distantPast
    /// Vero se l'ha creato l'app durante un'importazione, non l'utente.
    var automatico: Bool = false

    @Relationship(inverse: \Elemento.album)
    var elementi: [Elemento] = []

    init(nome: String, automatico: Bool = false) {
        self.nome = nome
        self.creatoIl = Date()
        self.automatico = automatico
    }

    var quanti: Int { elementi.filter { !$0.nelCestino }.count }

    var copertina: Elemento? {
        elementi.filter { !$0.nelCestino && $0.tipo == .foto }
            .min { $0.dataScatto < $1.dataScatto }
    }
}
