<?php
/*
Plugin Name: BB delete cache
Plugin URI: https://www.wearewp.pro
Description: Add delete beaver builder cache button in admin bar
Contributors: wearewp, thierrypigot, pross
Author: WeAre[WP]
Author URI: https://www.wearewp.pro
Text Domain: bb-delete-cache
Domain Path: /languages/
Version: 1.0.4
Stable tag: 1.0.4
*/

class BB_Delete_Cache_Admin_Bar {

	public function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		add_action( 'admin_bar_menu', array( $this, 'add_item' ) );
		add_action( 'admin_post_purge_cache', array( $this, '_clear_cache' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'bb-delete-cache', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	* Add's new global menu, if $href is false menu is added but registred as submenuable
	*
	* $id String
	* $title String
	* $href String
	* parent_menu String
	* $meta Array
	*
	* @return void
	* @author Janez Troha
	* @author Aaron Ware
	* @author Thierry Pigot
	**/

	private function add_menu( $id, $title, $href = false, $meta = false, $parent_menu = false ) {
		global $wp_admin_bar;
		if ( ! is_super_admin() || ! is_admin_bar_showing() ) {
			return;
		}

		$wp_admin_bar->add_menu(
			array(
				'id'     => $id,
				'parent' => $parent_menu,
				'title'  => $title,
				'href'   => $href,
				'meta'   => $meta,
			)
		);
	}


	/**
	* Add's new submenu where additinal $meta specifies class, id, target or onclick parameters
	*
	* $id String
	* parent_menu String
	* $title String
	* $href String
	* $meta Array
	*
	* @return void
	* @author Janez Troha
	* @author Thierry Pigot
	**/
	private function add_sub_menu( $id, $parent_menu, $title, $href, $meta = false ) {
		global $wp_admin_bar;

		if ( ! is_super_admin() || ! is_admin_bar_showing() || ! class_exists( 'FLBuilderModel' ) ) {
			return;
		}

		$wp_admin_bar->add_menu(
			array(
				'id'     => $id,
				'parent' => $parent_menu,
				'title'  => $title,
				'href'   => $href,
				'meta'   => $meta,
			)
		);
	}

	public function add_item() {

		global $post;

		$referer = '&_wp_http_referer=' . urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$action  = 'purge_cache';
		if ( ! is_admin() ) {
			// Purge a URL (frontend)
			$this->add_sub_menu(
				'bb-delete-url-cache',
				'fl-builder-frontend-edit-link',
				__( 'Clear this post', 'bb-delete-cache' ),
				wp_nonce_url( admin_url( 'admin-post.php?action=' . $action . '&type=post-' . $post->ID . $referer ), $action . '_post-' . $post->ID )
			);

			// Purge All
			$this->add_sub_menu(
				'bb-delete-all-cache',
				'fl-builder-frontend-edit-link',
				__( 'Clear cache', 'bb-delete-cache' ),
				wp_nonce_url( admin_url( 'admin-post.php?action=' . $action . '&type=all' . $referer ), $action . '_all' )
			);
		}
	}


	public function _clear_cache() {
		if ( isset( $_GET['type'], $_GET['_wpnonce'] ) ) {

			$_type = explode( '-', $_GET['type'] );
			$_type = reset( $_type );
			$_id   = explode( '-', $_GET['type'] );
			$_id   = end( $_id );

			if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'purge_cache_' . $_GET['type'] ) ) {
				wp_nonce_ays( '' );
			}

			switch ( $_type ) {

				// Clear all cache
				case 'all':
					FLBuilderModel::delete_asset_cache_for_all_posts();
					do_action( 'fl_builder_cache_cleared' );
					break;

				// Clear a current post
				case 'post':
					FLBuilderModel::delete_all_asset_cache( $_id );
					break;

				default:
					wp_nonce_ays( '' );
					break;
			}

			wp_redirect( wp_get_referer() );
			die();
		}
	}
}

new BB_Delete_Cache_Admin_Bar();
