<?php
/**
 * Module 7: Admin Metabox
 *
 * Google SERP preview, SEO title/description override fields,
 * noindex checkbox, and regenerate button.
 */

class Acrylicon_SEO_Admin_Metabox {

	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'register' ] );
		add_action( 'save_post', [ $this, 'save' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_acrylicon_seo_regenerate', [ $this, 'ajax_regenerate' ] );
	}

	public function register() {
		$post_types = get_post_types( [ 'public' => true ], 'names' );

		foreach ( $post_types as $pt ) {
			add_meta_box(
				'acrylicon-seo',
				'AcryliCon SEO',
				[ $this, 'render' ],
				$pt,
				'normal',
				'high'
			);
		}
	}

	public function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		wp_enqueue_style(
			'acrylicon-seo-admin',
			ACRYLICON_SEO_URL . '/assets/css/admin-seo.css',
			[],
			filemtime( ACRYLICON_SEO_DIR . '/assets/css/admin-seo.css' )
		);

		wp_enqueue_script(
			'acrylicon-seo-admin',
			ACRYLICON_SEO_URL . '/assets/js/admin-seo.js',
			[ 'jquery' ],
			filemtime( ACRYLICON_SEO_DIR . '/assets/js/admin-seo.js' ),
			true
		);

		wp_localize_script( 'acrylicon-seo-admin', 'acryliconSeo', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'acrylicon_seo_regenerate' ),
			'postId'  => get_the_ID(),
		] );
	}

	public function render( $post ) {
		$title       = get_post_meta( $post->ID, '_acrylicon_seo_title', true );
		$description = get_post_meta( $post->ID, '_acrylicon_seo_description', true );
		$robots      = get_post_meta( $post->ID, '_acrylicon_seo_robots', true );

		$auto_title = $post->post_title;
		$auto_desc  = $this->get_auto_description( $post );
		$permalink  = get_permalink( $post->ID );

		// Build preview URL breadcrumb (strip /no/ multisite prefix)
		$parsed = wp_parse_url( $permalink );
		$path = $parsed['path'] ?? '';
		$path = preg_replace( '#^/no/#', '/', $path );
		$breadcrumb = ( $parsed['host'] ?? 'acrylicon.no' ) . ' › ' . trim( str_replace( '/', ' › ', $path ), ' › ' );

		wp_nonce_field( 'acrylicon_seo_save', 'acrylicon_seo_nonce' );
		?>
		<div class="acrylicon-seo-metabox">
			<div class="acrylicon-seo-preview">
				<div class="acrylicon-seo-preview-label">Google Preview:</div>
				<div class="acrylicon-seo-serp">
					<div class="acrylicon-seo-serp-title" id="acrylicon-seo-preview-title">
						<?php echo esc_html( $title ?: $auto_title ); ?> | AcryliCon
					</div>
					<div class="acrylicon-seo-serp-url">
						<?php echo esc_html( $breadcrumb ); ?>
					</div>
					<div class="acrylicon-seo-serp-desc" id="acrylicon-seo-preview-desc">
						<?php echo esc_html( $description ?: $auto_desc ); ?>
					</div>
				</div>
			</div>

			<div class="acrylicon-seo-fields">
				<div class="acrylicon-seo-field">
					<label for="acrylicon_seo_title">SEO Title:</label>
					<span class="acrylicon-seo-counter" id="acrylicon-seo-title-counter">
						<?php echo mb_strlen( $title ?: $auto_title ); ?>/60
					</span>
					<input type="text" id="acrylicon_seo_title" name="acrylicon_seo_title"
						value="<?php echo esc_attr( $title ); ?>"
						placeholder="<?php echo esc_attr( $auto_title ); ?>"
						maxlength="70" />
				</div>

				<div class="acrylicon-seo-field">
					<label for="acrylicon_seo_description">Description:</label>
					<span class="acrylicon-seo-counter" id="acrylicon-seo-desc-counter">
						<?php echo mb_strlen( $description ?: $auto_desc ); ?>/155
					</span>
					<textarea id="acrylicon_seo_description" name="acrylicon_seo_description"
						placeholder="<?php echo esc_attr( $auto_desc ); ?>"
						maxlength="200" rows="2"><?php echo esc_textarea( $description ); ?></textarea>
				</div>

				<div class="acrylicon-seo-field">
					<label>
						<input type="checkbox" name="acrylicon_seo_robots" value="noindex"
							<?php checked( $robots, 'noindex' ); ?> />
						Skjul fra søkemotorer (noindex)
					</label>
				</div>
			</div>

			<div class="acrylicon-seo-actions">
				<button type="button" class="button" id="acrylicon-seo-regenerate">
					Regenerer
				</button>
				<button type="button" class="button" id="acrylicon-seo-clear">
					Tøm manuelle overstyringer
				</button>
			</div>

			<?php
			$schema_types = $this->get_schema_types( $post );
			if ( ! empty( $schema_types ) ) : ?>
				<div class="acrylicon-seo-schema-info">
					Schema: <?php echo esc_html( implode( ', ', $schema_types ) ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public function save( $post_id, $post ) {
		if ( ! wp_verify_nonce( $_POST['acrylicon_seo_nonce'] ?? '', 'acrylicon_seo_save' ) ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( wp_is_post_revision( $post_id ) ) return;

		// Title
		$title = sanitize_text_field( $_POST['acrylicon_seo_title'] ?? '' );
		if ( ! empty( $title ) ) {
			update_post_meta( $post_id, '_acrylicon_seo_title', $title );
		} else {
			delete_post_meta( $post_id, '_acrylicon_seo_title' );
		}

		// Description
		$desc = sanitize_text_field( $_POST['acrylicon_seo_description'] ?? '' );
		if ( ! empty( $desc ) ) {
			update_post_meta( $post_id, '_acrylicon_seo_description', $desc );
		} else {
			delete_post_meta( $post_id, '_acrylicon_seo_description' );
		}

		// Robots
		$robots = sanitize_text_field( $_POST['acrylicon_seo_robots'] ?? '' );
		if ( $robots === 'noindex' ) {
			update_post_meta( $post_id, '_acrylicon_seo_robots', 'noindex' );
		} else {
			delete_post_meta( $post_id, '_acrylicon_seo_robots' );
		}
	}

	public function ajax_regenerate() {
		check_ajax_referer( 'acrylicon_seo_regenerate', 'nonce' );

		$post_id = absint( $_POST['post_id'] ?? 0 );
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( 'Invalid post.' );
		}

		// Clear manual overrides
		delete_post_meta( $post_id, '_acrylicon_seo_title' );
		delete_post_meta( $post_id, '_acrylicon_seo_description' );

		$post = get_post( $post_id );

		wp_send_json_success( [
			'title'       => $post->post_title,
			'description' => $this->get_auto_description( $post ),
		] );
	}

	private function get_auto_description( $post ) {
		if ( function_exists( 'get_field' ) ) {
			$excerpt = get_field( 'product_excerpt', $post->ID );
			if ( ! empty( $excerpt ) ) {
				$clean = wp_strip_all_tags( $excerpt, true );
				return trim( preg_replace( '/\s+/', ' ', $clean ) );
			}
		}

		if ( ! empty( $post->post_excerpt ) ) {
			return wp_strip_all_tags( $post->post_excerpt, true );
		}

		if ( ! empty( $post->post_content ) ) {
			return wp_trim_words( wp_strip_all_tags( $post->post_content, true ), 25, '...' );
		}

		return '';
	}

	private function get_schema_types( $post ) {
		$types = [ 'WebPage', 'BreadcrumbList' ];

		switch ( $post->post_type ) {
			case 'produkter':
				$types[] = 'Product';
				break;
			case 'kontor':
				$types[] = 'LocalBusiness';
				break;
			case 'bruksomrader':
			case 'industrier':
				$types[] = 'Service';
				break;
			case 'referanser':
				$types[] = 'Article';
				break;
		}

		return $types;
	}
}
