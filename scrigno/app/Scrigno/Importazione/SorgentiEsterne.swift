import Foundation

/// Le schede SD e le fotocamere collegate: macOS le monta come dischi, e dentro
/// hanno quasi sempre una cartella `DCIM`.
///
/// Quello che qui **non** si trova sono i telefoni collegati col cavo: né Android
/// né iPhone si montano come dischi. Per quelli c'è la pagina Wi-Fi.
enum SorgentiEsterne {

    struct Disco: Identifiable, Hashable {
        var id: String { url.path }
        var nome: String
        var url: URL
        /// La cartella delle foto, quando c'è.
        var cartellaFoto: URL?
        var quantiFile: Int
    }

    static func dischiCollegati() -> [Disco] {
        let fm = FileManager.default
        let chiavi: [URLResourceKey] = [.volumeNameKey, .volumeIsRemovableKey,
                                        .volumeIsEjectableKey, .volumeIsInternalKey]

        guard let volumi = fm.mountedVolumeURLs(includingResourceValuesForKeys: chiavi,
                                                options: [.skipHiddenVolumes]) else { return [] }

        return volumi.compactMap { volume in
            guard let valori = try? volume.resourceValues(forKeys: Set(chiavi)) else { return nil }
            let staccabile = (valori.volumeIsRemovable ?? false) || (valori.volumeIsEjectable ?? false)
            guard staccabile, valori.volumeIsInternal != true else { return nil }

            let dcim = volume.appendingPathComponent("DCIM", isDirectory: true)
            let haFoto = fm.fileExists(atPath: dcim.path)

            return Disco(
                nome: valori.volumeName ?? volume.lastPathComponent,
                url: volume,
                cartellaFoto: haFoto ? dcim : nil,
                quantiFile: conta(haFoto ? dcim : volume)
            )
        }
    }

    /// Conta senza esagerare: oltre una certa soglia il numero esatto non serve a
    /// nessuno e scorrere tutto il disco costa tempo.
    private static func conta(_ cartella: URL, fermatiA limite: Int = 5000) -> Int {
        guard let elenco = FileManager.default.enumerator(
            at: cartella,
            includingPropertiesForKeys: [.isRegularFileKey],
            options: [.skipsHiddenFiles, .skipsPackageDescendants]
        ) else { return 0 }

        var quanti = 0
        for caso in elenco {
            guard let file = caso as? URL,
                  (try? file.resourceValues(forKeys: [.isRegularFileKey]).isRegularFile) == true
            else { continue }
            quanti += 1
            if quanti >= limite { break }
        }
        return quanti
    }

    /// Le cartelle di casa da cui si importa più spesso.
    static func cartelleComuni() -> [Disco] {
        let fm = FileManager.default
        let candidate: [(String, FileManager.SearchPathDirectory)] = [
            ("Scaricati", .downloadsDirectory),
            ("Scrivania", .desktopDirectory),
            ("Immagini", .picturesDirectory),
            ("Filmati", .moviesDirectory)
        ]

        return candidate.compactMap { nome, cartella in
            guard let url = fm.urls(for: cartella, in: .userDomainMask).first,
                  fm.fileExists(atPath: url.path) else { return nil }
            // La libreria di Scrigno sta dentro Immagini: non ha senso reimportarla.
            guard url != Libreria.condivisa.radice else { return nil }
            return Disco(nome: nome, url: url, cartellaFoto: nil, quantiFile: conta(url, fermatiA: 2000))
        }
    }
}
