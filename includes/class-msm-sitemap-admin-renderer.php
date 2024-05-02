<?php

class Metro_Sitemap_Admin_Renderer {

	protected string $partition_name;

	public function __construct( string $name = '' ) {
		$this->partition_name = $name;
	}

	/**
	 * Render admin options page
	 */
	public function render_sitemap_options_page() {

		if ( ! empty( $this->partition_name ) ) {
			do_action( 'msm_sitemap_select_partition', $this->partition_name );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'metro-sitemaps' ) );
		}

		// Array of possible user actions
		$actions = apply_filters( 'msm_sitemap_actions', array() );

		// Start outputting html
		echo '<div class="wrap">';
		screen_icon();
		echo '<h2>' . __( 'Sitemap', 'metro-sitemaps' ) . '</h2>';

		if ( ! Metro_Sitemap::is_blog_public() ) {
			Metro_Sitemap::show_action_message( __( 'Oops! Sitemaps are not supported on private blogs. Please make your blog public and try again.', 'metro-sitemaps' ), 'error' );
			echo '</div>';
			return;
		}

		if ( isset( $_POST['action'] ) ) {
			check_admin_referer( 'msm-sitemap-action' );
			foreach ( $actions as $slug => $action ) {
				if ( $action['text'] !== $_POST['action'] ) continue;

				do_action( 'msm_sitemap_action-' . $slug );
				break;
			}
		}

		// All the settings we need to read to display the page
		$sitemap_create_in_progress = (bool) get_option( 'msm_sitemap_create_in_progress' ) === true;
		$sitemap_update_last_run = get_option( 'msm_sitemap_update_last_run' . Metro_Sitemap::get_partition_suffix() );

		// Determine sitemap status text
		$sitemap_create_status = apply_filters(
			'msm_sitemap_create_status',
			$sitemap_create_in_progress ? __( 'Running', 'metro-sitemaps' ) : __( 'Not Running', 'metro-sitemaps' )
		);

		?>
		<div class="stats-container">
			<div class="stats-box"><strong id="sitemap-count"><?php echo number_format( Metro_Sitemap::count_sitemaps() ); ?></strong><?php esc_html_e( 'Sitemaps', 'metro-sitemaps' ); ?></div>
			<div class="stats-box"><strong id="sitemap-indexed-url-count"><?php echo number_format( Metro_Sitemap::get_total_indexed_url_count() ); ?></strong><?php esc_html_e( 'Indexed URLs', 'metro-sitemaps' ); ?></div>
			<div class="stats-footer"><span><span class="noticon noticon-time"></span><?php esc_html_e( 'Updated', 'metro-sitemaps' ); ?> <strong><?php echo human_time_diff( $sitemap_update_last_run ); ?> <?php esc_html_e( 'ago', 'metro-sitemaps' ) ?></strong></span></div>
		</div>

		<h3><?php esc_html_e( 'Latest Sitemaps', 'metro-sitemaps' ); ?></h3>
		<div class="stats-container stats-placeholder"></div>
		<div id="stats-graph-summary"><?php printf( __( 'Max: %s on %s. Showing the last %s days.', 'metro-sitemaps' ), '<span id="stats-graph-max"></span>', '<span id="stats-graph-max-date"></span>', '<span id="stats-graph-num-days"></span>' ); ?></div>

		<h3><?php esc_html_e( 'Generate', 'metro-sitemaps' ); ?></h3>
		<p><strong><?php esc_html_e( 'Sitemap Create Status:', 'metro-sitemaps' ) ?></strong> <?php echo esc_html( $sitemap_create_status ); ?></p>
		<form action="<?php echo menu_page_url( 'metro-sitemap', false ) ?>" method="post" style="float: left;">
			<?php wp_nonce_field( 'msm-sitemap-action' ); ?>
			<input id="sitemap-partition" type="hidden" name="partition" value="<?php echo esc_attr( $this->partition_name ); ?>">
			<?php foreach ( $actions as $action ):
				if ( ! $action['enabled'] ) continue; ?>
				<input type="submit" name="action" class="button-secondary" value="<?php echo esc_attr( $action['text'] ); ?>">
			<?php endforeach; ?>
		</form>
		</div>
		<div id="tooltip"><strong class="content"></strong> <?php esc_html_e( 'indexed urls', 'metro-sitemaps' ); ?></div>
		<?php
	}
}
