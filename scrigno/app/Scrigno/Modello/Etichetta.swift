import Foundation
import SwiftData
import SwiftUI

/// Le etichette colorate che l'utente mette a mano: Famiglia, Lavoro, Viaggi…
@Model
final class Etichetta {
    var nome: String = ""
    var coloreEsadecimale: String = "#e9ae4f"
    var ordine: Int = 0

    @Relationship(inverse: \Elemento.etichette)
    var elementi: [Elemento] = []

    init(nome: String, coloreEsadecimale: String, ordine: Int = 0) {
        self.nome = nome
        self.coloreEsadecimale = coloreEsadecimale
        self.ordine = ordine
    }

    var colore: Color { Color(esadecimale: coloreEsadecimale) }

    var quanti: Int { elementi.filter { !$0.nelCestino }.count }

    /// Le quattro etichette con cui l'app parte la prima volta.
    static let predefinite: [(String, String)] = [
        ("Famiglia", "#e06d5c"),
        ("Lavoro", "#6f8cf0"),
        ("Viaggi", "#58b39c"),
        ("Da stampare", "#d6a23f")
    ]
}
