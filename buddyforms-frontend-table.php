<?php

/**
 * Plugin Name: BuddyForms Frontend Table
 * Plugin URI: https://themekraft.com/
 * Description: Use BuddyForms with a nice Frontend Table
 * Version: 1.0.0
 * Author: ThemeKraft Team
 * Author URI: https://themekraft.com/
 * License: GPLv2 or later
 * Network: false
 * Text Domain: buddyforms-frontend-table
 *
 *****************************************************************************
 *
 * This script is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 ****************************************************************************
 */


class BuddyFormsFrontendTable {
	/**
	 * @var string
	 */
	public static $include_assets = false;
	public static $version = '1.0.0';
	public static $slug = 'buddyforms-frontend-table';

	/**
	 * Instance of this class
	 *
	 * @var $instance buddyforms_geo_my_wp
	 */
	protected static $instance = null;

	/**
	 * Initiate the class
	 *
	 * @package BuddyFormsFrontendTable
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ), 4, 1 );
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
		$this->load_constants();
	}

	/**
	 * Defines constants needed throughout the plugin.
	 *
	 * These constants can be overridden in bp-custom.php or wp-config.php.
	 *
	 * @package BuddyFormsFrontendTable
	 * @since 1.0
	 */
	public function load_constants() {
		if ( ! defined( 'BUDDYFORMS_FRONTEND_TABLE_PLUGIN_URL' ) ) {
			define( 'BUDDYFORMS_FRONTEND_TABLE_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
		}
		if ( ! defined( 'BUDDYFORMS_FRONTEND_TABLE_INSTALL_PATH' ) ) {
			define( 'BUDDYFORMS_FRONTEND_TABLE_INSTALL_PATH', dirname( __FILE__ ) . '/' );
		}
		if ( ! defined( 'BUDDYFORMS_FRONTEND_TABLE_INCLUDES_PATH' ) ) {
			define( 'BUDDYFORMS_FRONTEND_TABLE_INCLUDES_PATH', BUDDYFORMS_FRONTEND_TABLE_INSTALL_PATH . 'includes/' );
		}
		if ( ! defined( 'BUDDYFORMS_FRONTEND_TABLE_ASSETS' ) ) {
			define( 'BUDDYFORMS_FRONTEND_TABLE_ASSETS', BUDDYFORMS_FRONTEND_TABLE_PLUGIN_URL . 'assets/' );
		}
		if ( ! defined( 'BUDDYFORMS_FRONTEND_TABLE_VIEW' ) ) {
			define( 'BUDDYFORMS_FRONTEND_TABLE_VIEW', BUDDYFORMS_FRONTEND_TABLE_INSTALL_PATH . 'views/' );
		}
	}

	public static function error_log( $message ) {
		if ( ! empty( $message ) ) {
			error_log( self::getSlug() . ' -- ' . $message );
		}
	}

	/**
	 * @return string
	 */
	public static function getNeedAssets() {
		return self::$include_assets;
	}

	/**
	 * @param string $include_assets
	 */
	public static function setNeedAssets( $include_assets ) {
		self::$include_assets = $include_assets;
	}

	/**
	 * Include files needed by BuddyForms
	 *
	 * @package BuddyFormsFrontendTable
	 * @since 1.0
	 */
	public function includes() {
		require_once BUDDYFORMS_FRONTEND_TABLE_INCLUDES_PATH . 'class-form-elements.php';
		new BuddyFormsFrontendTableElements();
		require_once BUDDYFORMS_FRONTEND_TABLE_INCLUDES_PATH . 'class-table-data-output.php';
		new BuddyFormsFrontendTableDataOutput();
	}

	/**
	 * Load the text-domain for the plugin
	 *
	 * @package BuddyFormsFrontendTable
	 * @since 1.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'buddyforms-frontend-table', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Get plugin version
	 *
	 * @return string
	 */
	static function getVersion() {
		return self::$version;
	}

	/**
	 * Get plugins slug
	 *
	 * @return string
	 */
	static function getSlug() {
		return self::$slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

}


if ( ! function_exists( 'buddyforms_frontend_table_fs' ) ) {
	// Create a helper function for easy SDK access.
	function buddyforms_frontend_table_fs() {
		global $buddyforms_frontend_table_fs;

		if ( ! isset( $buddyforms_frontend_table_fs ) ) {
			// Include Freemius SDK.
			if ( file_exists( dirname( dirname( __FILE__ ) ) . '/buddyforms/includes/resources/freemius/start.php' ) ) {
				// Try to load SDK from parent plugin folder.
				require_once dirname( dirname( __FILE__ ) ) . '/buddyforms/includes/resources/freemius/start.php';
			} else if ( file_exists( dirname( dirname( __FILE__ ) ) . '/buddyforms-premium/includes/resources/freemius/start.php' ) ) {
				// Try to load SDK from premium parent plugin folder.
				require_once dirname( dirname( __FILE__ ) ) . '/buddyforms-premium/includes/resources/freemius/start.php';
			}

			try {
				$buddyforms_frontend_table_fs = fs_dynamic_init( array(
					'id'               => '5264',
					'slug'             => 'buddyforms-frontend-table',
					'type'             => 'plugin',
					'public_key'       => 'pk_6cc61f1941b27894cca49b6a1d996',
					'is_premium'       => true,
					'is_premium_only'  => true,
					'has_paid_plans'   => true,
					'is_org_compliant' => false,
					'trial'            => array(
						'days'               => 14,
						'is_require_payment' => true,
					),
					'parent'           => array(
						'id'         => '391',
						'slug'       => 'buddyforms',
						'public_key' => 'pk_dea3d8c1c831caf06cfea10c7114c',
						'name'       => 'BuddyForms',
					),
					'menu'             => array(
						'first-path' => 'plugins.php',
						'support'    => false,
					),
				) );
			} catch ( Freemius_Exception $e ) {
				return false;
			}
		}

		return $buddyforms_frontend_table_fs;
	}
}

function buddyforms_frontend_table_fs_is_parent_active_and_loaded() {
	// Check if the parent's init SDK method exists.
	return function_exists( 'buddyforms_core_fs' );
}

function buddyforms_frontend_table_fs_is_parent_active() {
	$active_plugins = get_option( 'active_plugins', array() );

	if ( is_multisite() ) {
		$network_active_plugins = get_site_option( 'active_sitewide_plugins', array() );
		$active_plugins         = array_merge( $active_plugins, array_keys( $network_active_plugins ) );
	}

	foreach ( $active_plugins as $basename ) {
		if ( 0 === strpos( $basename, 'buddyforms/' ) ||
		     0 === strpos( $basename, 'buddyforms-premium/' )
		) {
			return true;
		}
	}

	return false;
}

function buddyforms_frontend_table_fs_init() {
	if ( buddyforms_frontend_table_fs_is_parent_active_and_loaded() ) {
		// Init Freemius.
		buddyforms_frontend_table_fs();
		// Signal that the add-on's SDK was initiated.
		do_action( 'buddyforms_frontend_table_fs_loaded' );
		$GLOBALS['BuddyFormsFrontendTable'] = BuddyFormsFrontendTable::get_instance();
	}
}

if ( buddyforms_frontend_table_fs_is_parent_active_and_loaded() ) {
	// If parent already included, init add-on.
	buddyforms_frontend_table_fs_init();
} else if ( buddyforms_frontend_table_fs_is_parent_active() ) {
	// Init add-on only after the parent is loaded.
	add_action( 'buddyforms_core_fs_loaded', 'buddyforms_frontend_table_fs_init' );
} else {
	// Even though the parent is not activated, execute add-on for activation / uninstall hooks.
	buddyforms_frontend_table_fs_init();
}

