<?php
/**
 * Plugin Name: JetEngine - Data Stores - clear posts on delete
 * Plugin URI: #
 * Description: Removes post from users data stores on post trashing/deletion. Works only for user meta stores
 * Version:     1.0.0
 * Author:      Crocoblock
 * Author URI:  https://crocoblock.com/
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

add_action( 'delete_post', 'jet_dscp_handler' );
add_action( 'wp_trash_post', 'jet_dscp_handler' );

function jet_dscp_handler( $post_id ) {

	if ( ! function_exists( 'jet_engine' ) ) {
		return;
	}

	if ( ! jet_engine()->modules->is_module_active( 'data-stores' ) ) {
		return;
	}

	$stores = \Jet_Engine\Modules\Data_Stores\Module::instance()->stores->get_stores();

	$meta_keys = array();

	foreach ( $stores as $store ) {

		if ( 'user-meta' !== $store->get_type()->type_id() ) {
			continue;
		}

		if ( $store->is_user_store() ) {
			continue;
		}

		$meta_keys[] = "'" . $store->get_type()->prefix . $store->get_slug() . "'";

	}

	if ( empty( $meta_keys ) ) {
		return;
	}

	global $wpdb;

	$table = $wpdb->usermeta;
	$meta_keys = implode( ', ', $meta_keys );

	$query = "SELECT * FROM $table WHERE `meta_key` IN ( $meta_keys ) AND `meta_value` LIKE '%\"$post_id\"%'";

	$res = $wpdb->get_results( $query );

	if ( ! empty( $res ) ) {
		foreach ( $res as $row ) {

			$posts = maybe_unserialize( $row->meta_value );

			$index = array_search( $post_id, $posts );
			if ( false !== $index ) {
				unset( $posts[ $index ] );
			}

			update_user_meta( $row->user_id, $row->meta_key, $posts );
		}
	}

}
