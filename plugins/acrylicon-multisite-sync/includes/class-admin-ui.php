<?php
namespace Acrylicon_Multisite_Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_UI {

	private $sync_manager;

	/**
	 * Supported post types for syncing
	 */
	private $supported_post_types = [
		'referanser',
		'produkter',
		'bruksomrader',
		'godegrunner',
		'levetidskostnader',
		'baerekreaftig'
	];

	public function __construct() {
		$this->sync_manager = new Sync_Manager();

		// Add metabox to supported post types
		add_action( 'add_meta_boxes', [ $this, 'add_sync_metabox' ] );

		// Handle sync AJAX request
		add_action( 'wp_ajax_acrylicon_sync_post', [ $this, 'handle_sync_ajax' ] );

		// Enqueue admin assets
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// Add admin notice after sync
		add_action( 'admin_notices', [ $this, 'show_sync_notices' ] );
	}

	/**
	 * Add sync metabox to post editor
	 */
	public function add_sync_metabox() {
		foreach ( $this->supported_post_types as $post_type ) {
			add_meta_box(
				'acrylicon_sync_metabox',
				'Multisite Synkronisering',
				[ $this, 'render_sync_metabox' ],
				$post_type,
				'side',
				'high'
			);
		}
	}

	/**
	 * Render metabox content
	 */
	public function render_sync_metabox( $post ) {
		// Get available target sites
		$sites = get_sites( [ 'number' => 100 ] );
		$current_blog_id = get_current_blog_id();

		// Check if already synced
		$synced_sites = [];
		foreach ( $sites as $site ) {
			if ( $site->blog_id == $current_blog_id ) {
				continue;
			}

			if ( $this->sync_manager->is_synced( $post->ID, $site->blog_id ) ) {
				$synced_sites[] = $site;
			}
		}

		// Nonce for security
		wp_nonce_field( 'acrylicon_sync_post', 'acrylicon_sync_nonce' );

		?>
		<div class="acrylicon-sync-metabox">
			<?php if ( ! empty( $synced_sites ) ) : ?>
				<div class="sync-status synced">
					<span class="dashicons dashicons-yes-alt"></span>
					<strong>Synkronisert til:</strong>
					<ul>
						<?php foreach ( $synced_sites as $site ) : ?>
							<?php
							$synced_post_id = get_post_meta( $post->ID, '_synced_to_post_' . $site->blog_id, true );
							$synced_date = get_post_meta( $post->ID, '_synced_date_' . $site->blog_id, true );
							?>
							<li>
								<?php echo esc_html( get_blog_details( $site->blog_id )->blogname ); ?>
								<br>
								<small>
									Post ID: <?php echo esc_html( $synced_post_id ); ?> |
									Dato: <?php echo esc_html( date( 'Y-m-d H:i', strtotime( $synced_date ) ) ); ?>
								</small>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php else : ?>
				<div class="sync-status not-synced">
					<span class="dashicons dashicons-admin-multisite"></span>
					<p>Ikke synkronisert til andre sites ennå.</p>
				</div>
			<?php endif; ?>

			<div class="sync-actions">
				<label for="target_blog_id"><strong>Synkroniser til:</strong></label>
				<select id="target_blog_id" name="target_blog_id">
					<option value="">-- Velg site --</option>
					<?php foreach ( $sites as $site ) : ?>
						<?php if ( $site->blog_id == $current_blog_id ) continue; ?>
						<?php
						$is_synced = $this->sync_manager->is_synced( $post->ID, $site->blog_id );
						?>
						<option
							value="<?php echo esc_attr( $site->blog_id ); ?>"
							<?php disabled( $is_synced ); ?>
						>
							<?php echo esc_html( get_blog_details( $site->blog_id )->blogname ); ?>
							<?php echo $is_synced ? '(Allerede synkronisert)' : ''; ?>
						</option>
					<?php endforeach; ?>
				</select>

				<button
					type="button"
					id="acrylicon-sync-button"
					class="button button-primary"
					data-post-id="<?php echo esc_attr( $post->ID ); ?>"
				>
					<span class="dashicons dashicons-update"></span>
					Synkroniser nå
				</button>

				<div id="sync-spinner" class="spinner" style="display:none;"></div>
				<div id="sync-result" style="margin-top: 10px;"></div>
			</div>

			<div class="sync-info">
				<p><strong>⚠️ Viktig:</strong></p>
				<ul style="margin-left: 20px; font-size: 12px;">
					<li>Post opprettes som <strong>utkast</strong> på target site</li>
					<li>Kan kun synkroniseres <strong>én gang</strong> per site</li>
					<li>ACF relationship fields blir <strong>tomme</strong></li>
					<li>Media kopieres fysisk til target site</li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle AJAX sync request
	 */
	public function handle_sync_ajax() {
		// Security checks
		check_ajax_referer( 'acrylicon_sync_post', 'nonce' );

		if ( ! current_user_can( 'manage_network' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions' ] );
		}

		$post_id = intval( $_POST['post_id'] );
		$target_blog_id = intval( $_POST['target_blog_id'] );

		if ( ! $post_id || ! $target_blog_id ) {
			wp_send_json_error( [ 'message' => 'Invalid parameters' ] );
		}

		// Perform sync
		$result = $this->sync_manager->sync_post( $post_id, $target_blog_id );

		if ( $result['success'] ) {
			wp_send_json_success( [
				'message' => 'Synkronisering fullført!',
				'post_id' => $result['post_id'],
				'errors' => $result['errors']
			] );
		} else {
			wp_send_json_error( [
				'message' => 'Synkronisering feilet: ' . $result['error']
			] );
		}
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! in_array( $screen->post_type, $this->supported_post_types ) ) {
			return;
		}

		wp_enqueue_style(
			'acrylicon-sync-admin',
			ACRYLICON_SYNC_URL . '/assets/css/admin-style.css',
			[],
			ACRYLICON_SYNC_VERSION
		);

		wp_enqueue_script(
			'acrylicon-sync-admin',
			ACRYLICON_SYNC_URL . '/assets/js/admin-script.js',
			[ 'jquery' ],
			ACRYLICON_SYNC_VERSION,
			true
		);

		wp_localize_script( 'acrylicon-sync-admin', 'acrylicon_sync', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'acrylicon_sync_post' )
		] );
	}

	/**
	 * Show admin notices after sync
	 */
	public function show_sync_notices() {
		// Placeholder for future implementation
	}
}
