<?php
/**
* Plugin Name:       KeyPress Active Plugins
* Plugin URI:        https://getkeypress.com/downloads/kp-active-plugins
* Description:       Shows the active plugins on a network
* Version:           0.1
* Author:            KeyPress Media
* Author URI:        https://getkeypress.com
* License:           GPL-2.0+
* License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
* Text Domain:       kpap
* Domain Path:       /languages
*
*
* KeyPress Active Plugins is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 2 of the License, or
* any later version.
*
* KeyPress UI is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with KeyPress Active Plugins. If not, see <http://www.gnu.org/licenses/>.
*
* @package kp-active-plugins
* @category Core
* @link https://getkeypress.com
*
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Plugin Root File.
if ( ! defined( 'KPAP_PLUGIN_FILE' ) ) {
    define( 'KPAP_PLUGIN_FILE', __FILE__ );
}

// Plugin Folder Path.
if ( ! defined( 'KPAP_PLUGIN_DIR' ) ) {
    define( 'KPAP_PLUGIN_DIR', plugin_dir_path( KPAP_PLUGIN_FILE ) );
}

// Plugin Folder URL.
if ( ! defined( 'KPAP_PLUGIN_URL' ) ) {
    define( 'KPAP_PLUGIN_URL', plugin_dir_url( KPAP_PLUGIN_FILE ) );
}

// Plugin Version.
if ( ! defined( 'KPAP_PLUGIN_VERSION' ) ) {
    define( 'KPAP_PLUGIN_VERSION', '0.1' );
}

if ( ! class_exists( 'KP_ACTIVE_PLUGINS' ) ) {
    class KP_ACTIVE_PLUGINS {

        private $active_plugins_sites;

        public function __construct() {
            $this->define_admin_hooks();
            $this->load_active_plugins_sites();
        }

        private function define_admin_hooks() {
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
            add_filter( 'plugin_row_meta', array( $this, 'plugin_active_sites' ), 10, 4 );
        }

        public function enqueue_scripts() {
            wp_enqueue_script( 'jquery-ui-tooltip' );
            wp_enqueue_script( 'kpap_script', KPAP_PLUGIN_URL . 'assets/js/script.js', array( 'jquery-ui-tooltip' ), KPAP_PLUGIN_VERSION, false );
        }

        public function enqueue_styles() {
            wp_enqueue_style( 'kpap_styles', KPAP_PLUGIN_URL . 'assets/css/styles.css', array(), KPAP_PLUGIN_VERSION, 'all' );
        }

        private function load_active_plugins_sites() {

            global $wpdb;

            $plugins = get_plugins();
            $sites = get_sites();

            $sites_active_plugins = array();

            foreach ( $sites as $site ) {
                $blog_id = absint( $site->blog_id );
                $sql = 1 == $blog_id
                    ? "SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = 'active_plugins' LIMIT 1"
                    : "SELECT option_value FROM {$wpdb->prefix}{$blog_id}_options WHERE option_name = 'active_plugins' LIMIT 1";
                $row = $wpdb->get_row( $sql );

                if ( isset( $row->option_value ) ) {
                    $sites_active_plugins[ $blog_id ] = maybe_unserialize( $row->option_value );
                }
            }

            $this->active_plugins_sites = array();

            foreach ( $plugins as $plugin_file => $plugin_data ) {
                $this->active_plugins_sites[ $plugin_file ] = array();

                foreach ( $sites_active_plugins as $site_id => $active_plugins ) {
                    if ( in_array( $plugin_file, $active_plugins) ) {
                        $this->active_plugins_sites[ $plugin_file ][] = $site_id;
                    }
                }
            }
        }

        public function plugin_active_sites( $plugin_meta, $plugin_file, $plugin_data, $status ) {
            $active_sites_count = 0;
            $tooltip_text = __( 'The plugin is active in 0 sites.', 'kpap' );
            $active_sites = array();

            if ( ! empty( $this->active_plugins_sites[ $plugin_file ] ) ) {
                $active_sites_count = count( $this->active_plugins_sites[ $plugin_file ] );

                if ( 0 < $active_sites_count ) {
                    foreach ( $this->active_plugins_sites[ $plugin_file ] as $site_id ) {
                        $active_sites[] = get_site_url( $site_id );
                    }
                }
            }

            if ( 0 < $active_sites_count ) {
                $tooltip_text = implode( '<br />', $active_sites );
            }

            $active_sites_meta = '<span class="kpap-plugin-meta">';
            $active_sites_meta .= sprintf( __( 'Active in %d %s', 'kpap' ), $active_sites_count, 1 == $active_sites_count ? __( 'site', 'kpap' ) : __( 'sites', 'kpap' )  );
            $active_sites_meta .= '<span alt="f223" class="kpap-tooltip kpap-tooltip-icon" title="' . $tooltip_text . '"></span>';
            $active_sites_meta .= '</span>';

            $plugin_meta[] = $active_sites_meta;

            return $plugin_meta;
        }
    }
}

function kpap_run() {

    // Make sure that the plugin only runs on multisite installs
    if ( ! is_multisite() ) {
        add_action( 'admin_notices', 'kpap_admin_notice__error' );
        return;
    } else {

        // Only run on the plugins screen, on the network admin
        if ( is_network_admin() ) {
            $current_screen =  get_current_screen();

            if ( 'plugins-network' == $current_screen->id ) {
                $kpap = new KP_ACTIVE_PLUGINS();
            }
        }
    }
}
add_action( 'current_screen', 'kpap_run' );