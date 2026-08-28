<?php
/**
 * cpt.php — Registrazione Custom Post Type e metabox.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra i 6 Custom Post Type del plugin.
 */
function gs_register_cpt() {

	$cpts = array(
		'gs_sfida' => array(
			'labels'   => gs_cpt_labels( 'Sfida', 'Sfide' ),
			'public'   => true,
			'menu_icon'=> 'dashicons-awards',
			'supports' => array( 'title', 'editor', 'thumbnail' ),
			'has_archive' => false,
			'show_in_menu' => false, // gestita dal menu del plugin
			'show_in_rest' => true, // editor a blocchi
		),
		'gs_sfoglia' => array(
			'labels'   => gs_cpt_labels( 'Sfoglia', 'Sfoglie' ),
			'public'   => true,
			'menu_icon'=> 'dashicons-format-image',
			'supports' => array( 'title', 'editor', 'thumbnail', 'author' ),
			'has_archive' => false,
			'show_in_menu' => false,
		),
		'gs_diario' => array(
			'labels'   => gs_cpt_labels( 'Voce di Diario', 'Diario dell\'Impasto' ),
			'public'   => false,
			'show_ui'  => true,
			'supports' => array( 'title', 'editor', 'author' ),
			'show_in_menu' => false,
		),
		'gs_consiglio' => array(
			'labels'   => gs_cpt_labels( 'Consiglio', 'Consigli' ),
			'public'   => true,
			'supports' => array( 'title', 'editor', 'author' ),
			'has_archive' => false,
			'show_in_menu' => false,
		),
		'gs_ingrediente' => array(
			'labels'   => gs_cpt_labels( 'Ingrediente Segreto', 'Ingredienti Segreti' ),
			'public'   => true,
			'menu_icon'=> 'dashicons-carrot',
			'supports' => array( 'title', 'editor', 'thumbnail' ),
			'has_archive' => false,
			'show_in_menu' => false,
			'show_in_rest' => true, // editor a blocchi
		),
		'gs_barometro' => array(
			'labels'   => gs_cpt_labels( 'Guida Stagionale', 'Guide Stagionali' ),
			'public'   => false,
			'show_ui'  => true,
			'supports' => array( 'title', 'editor' ),
			'show_in_menu' => false,
		),
	);

	foreach ( $cpts as $slug => $args ) {
		// show_in_rest solo dove serve davvero: gs_diario e gs_barometro
		// non sono pubblici e non devono comparire su /wp-json/.
		$args = wp_parse_args( $args, array(
			'show_in_rest' => false,
		) );
		register_post_type( $slug, $args );
	}
}

/**
 * Genera le labels standard per un CPT.
 */
function gs_cpt_labels( $singolare, $plurale ) {
	return array(
		'name'          => $plurale,
		'singular_name' => $singolare,
		'add_new'       => 'Aggiungi nuovo',
		'add_new_item'  => 'Aggiungi nuovo/a ' . $singolare,
		'edit_item'     => 'Modifica ' . $singolare,
		'new_item'      => 'Nuovo/a ' . $singolare,
		'view_item'     => 'Vedi ' . $singolare,
		'search_items'  => 'Cerca ' . $plurale,
		'not_found'     => 'Nessun risultato',
		'menu_name'     => $plurale,
	);
}

// -----------------------------------------------------------------------------
// Metabox
// -----------------------------------------------------------------------------
add_action( 'add_meta_boxes', 'gs_register_metaboxes' );

function gs_register_metaboxes() {
	add_meta_box( 'gs_sfida_dettagli', 'Dettagli Sfida', 'gs_metabox_sfida', 'gs_sfida', 'side', 'high' );
	add_meta_box( 'gs_barometro_stagione', 'Stagione', 'gs_metabox_barometro', 'gs_barometro', 'side', 'high' );
}

/**
 * Metabox "Dettagli Sfida" — include i due campi nuovi della v2:
 * Modalità di partecipazione e Visibilità.
 */
function gs_metabox_sfida( $post ) {
	wp_nonce_field( 'gs_save_sfida', 'gs_sfida_nonce' );

	$inizio     = get_post_meta( $post->ID, 'gs_data_inizio', true );
	$fine       = get_post_meta( $post->ID, 'gs_data_fine', true );
	$tipo       = get_post_meta( $post->ID, 'gs_tipo', true );
	$modalita   = get_post_meta( $post->ID, 'gs_modalita', true );
	$visibilita = get_post_meta( $post->ID, 'gs_visibilita', true );
	$blindata   = get_post_meta( $post->ID, 'gs_blindata', true );
	$livello_min= (int) get_post_meta( $post->ID, 'gs_livello_min', true );
	$tipo_voto  = get_post_meta( $post->ID, 'gs_tipo_voto', true );

	$modalita   = $modalita ? $modalita : 'entrambe';
	$tipo_voto  = $tipo_voto ? $tipo_voto : 'sfogline';
	$visibilita = $visibilita ? $visibilita : 'pubblica';
	$livelli    = gs_settings()['levels'];
	?>
	<p>
		<label><strong>Data e ora inizio</strong></label><br>
		<input type="datetime-local" name="gs_data_inizio" value="<?php echo esc_attr( $inizio ); ?>" style="width:100%">
	</p>
	<p>
		<label><strong>Data e ora scadenza</strong></label><br>
		<input type="datetime-local" name="gs_data_fine" value="<?php echo esc_attr( $fine ); ?>" style="width:100%">
	</p>
	<p>
		<label><strong>Tipo</strong></label><br>
		<select name="gs_tipo" style="width:100%">
			<?php
			$tipi = array( 'settimanale' => 'Settimanale', 'mensile' => 'Mensile', 'speciale' => 'Speciale', 'stagionale' => 'Stagionale' );
			foreach ( $tipi as $val => $lab ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( $val ), selected( $tipo, $val, false ), esc_html( $lab ) );
			}
			?>
		</select>
	</p>
	<hr>
	<p>
		<label><strong>Modalità di partecipazione</strong></label><br>
		<select name="gs_modalita" style="width:100%">
			<option value="individuale" <?php selected( $modalita, 'individuale' ); ?>>Solo individuale</option>
			<option value="squadre" <?php selected( $modalita, 'squadre' ); ?>>Solo a squadre</option>
			<option value="entrambe" <?php selected( $modalita, 'entrambe' ); ?>>Entrambe</option>
		</select>
	</p>
	<p>
		<label><strong>Visibilità galleria</strong></label><br>
		<select name="gs_visibilita" style="width:100%">
			<option value="pubblica" <?php selected( $visibilita, 'pubblica' ); ?>>Pubblica (chiunque)</option>
			<option value="riservata" <?php selected( $visibilita, 'riservata' ); ?>>Riservata alle iscritte approvate</option>
		</select>
	</p>
	<hr>
	<p>
		<label><strong>Chi vota questa sfida</strong></label><br>
		<select name="gs_tipo_voto" style="width:100%">
			<option value="sfogline" <?php selected( $tipo_voto, 'sfogline' ); ?>>Le sfogline, tra di loro</option>
			<option value="maestri" <?php selected( $tipo_voto, 'maestri' ); ?>>I maestri (titolare e collaboratori)</option>
		</select><br>
		<small>"Le sfogline" è il voto di sempre: chiunque tranne l'autrice. "I maestri" riserva il voto solo a chi gestisce il portale — le sfogline vedono comunque le sfoglie in gara, solo non votano.</small>
	</p>
	<hr>
	<p>
		<label><strong>
			<input type="checkbox" name="gs_blindata" value="1" <?php checked( $blindata, '1' ); ?>>
			Sfida blindata
		</strong></label><br>
		<small>Se attiva, partecipano solo le sfogline dal livello minimo in su, più quelle che ammetti a tua discrezione dal Pannello di Controllo.</small>
	</p>
	<p>
		<label><strong>Livello minimo richiesto</strong></label><br>
		<select name="gs_livello_min" style="width:100%">
			<option value="0" <?php selected( $livello_min, 0 ); ?>>Nessuno (tutti i livelli)</option>
			<?php foreach ( $livelli as $i => $lv ) :
				$num = $i + 1; ?>
				<option value="<?php echo $num; ?>" <?php selected( $livello_min, $num ); ?>>
					Livello <?php echo $num; ?> — <?php echo esc_html( $lv['simbolo'] . ' ' . $lv['titolo'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>
	<?php
}

/**
 * Metabox "Stagione" per le Guide Stagionali.
 */
function gs_metabox_barometro( $post ) {
	wp_nonce_field( 'gs_save_barometro', 'gs_barometro_nonce' );
	$trimestre = get_post_meta( $post->ID, 'gs_trimestre', true );
	$trimestre = $trimestre ? $trimestre : gs_current_quarter();
	?>
	<p>
		<label><strong>Trimestre di competenza</strong></label><br>
		<input type="text" name="gs_trimestre" value="<?php echo esc_attr( $trimestre ); ?>" placeholder="es. 2026-Q3" style="width:100%">
		<small>Formato: ANNO-Qn (es. 2026-Q3). Visibile solo a chi ha partecipato a una sfida in quel trimestre.</small>
	</p>
	<?php
}

// -----------------------------------------------------------------------------
// Salvataggio metabox
// -----------------------------------------------------------------------------
add_action( 'save_post_gs_sfida', 'gs_save_sfida_meta' );
function gs_save_sfida_meta( $post_id ) {
	if ( ! isset( $_POST['gs_sfida_nonce'] ) || ! wp_verify_nonce( $_POST['gs_sfida_nonce'], 'gs_save_sfida' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = array(
		'gs_data_inizio' => 'sanitize_text_field',
		'gs_data_fine'   => 'sanitize_text_field',
		'gs_tipo'        => 'sanitize_text_field',
		'gs_modalita'    => 'sanitize_text_field',
		'gs_visibilita'  => 'sanitize_text_field',
		'gs_tipo_voto'   => 'sanitize_text_field',
	);
	foreach ( $fields as $key => $cb ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_post_meta( $post_id, $key, call_user_func( $cb, wp_unslash( $_POST[ $key ] ) ) );
		}
	}

	// Sfida blindata (checkbox) e livello minimo.
	update_post_meta( $post_id, 'gs_blindata', isset( $_POST['gs_blindata'] ) ? '1' : '' );
	update_post_meta( $post_id, 'gs_livello_min', isset( $_POST['gs_livello_min'] ) ? (int) $_POST['gs_livello_min'] : 0 );
}

add_action( 'save_post_gs_barometro', 'gs_save_barometro_meta' );
function gs_save_barometro_meta( $post_id ) {
	if ( ! isset( $_POST['gs_barometro_nonce'] ) || ! wp_verify_nonce( $_POST['gs_barometro_nonce'], 'gs_save_barometro' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['gs_trimestre'] ) ) {
		update_post_meta( $post_id, 'gs_trimestre', sanitize_text_field( wp_unslash( $_POST['gs_trimestre'] ) ) );
	}
}
