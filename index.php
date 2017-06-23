<?php
/**
 * Plugin Name: YMR Requirements
 * Plugin URI: http://www.apache.org
 * Description: This plugin checks the plugin dependency of other plugins and allow their activation
 * Version: 1.0.0
 * Author: Yulier Molina Ramirez
 * Author URI: https://www.linkedin.com/in/ymolinar/
 * License: GPL2
 */

// Exit if accessed directly or if the base framework plugin is not active.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'REQUIREMENTS_FILE_NAMES', array( 'requirements', 'require', 'depends_on' ) );
define( 'REQUIREMENTS_REGULAR_EXPRESSION', '/^([a-zA-Z0-9_\.\/\-]+)(:(\d+\.)?(\d+\.)?(\*|\d+)){0,1}$/i' );

/**
 * Class RequirementsCompiler this class is intended to search requirements files in available wordpress plugins, compare
 * their requirements with the other plugins and enable or disable the plugins activate link based in their requirements
 */
class RequirementsCompiler {
	/**
	 * The instance to make this class use the singleton pattern
	 *
	 * @var RequirementsCompiler
	 */
	private static $instance;
	/**
	 * An array with all the available plugins in the wordpress engine
	 *
	 * @var array
	 */
	private $available_plugins;
	/**
	 * An array with the active plugins in the wordpress engine
	 *
	 * @var array
	 */
	private $active_plugins;

	/**
	 * RequirementsCompiler constructor.
	 */
	protected function __construct() {
		$this->available_plugins = array();
		$this->active_plugins    = array();
	}

	/**
	 * Singleton entry point to create the instance of the object
	 *
	 * @return RequirementsCompiler
	 */
	public static function getInstance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Init all the actions and filters hooks that this class use to do his job
	 */
	public function init() {
		add_filter( 'all_plugins', array( $this, 'set_available_plugins' ) );
		add_filter( 'plugin_action_links', array( $this, 'check_plugin_action_links' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Eneuque the css styles in the plugins page only
	 *
	 * @param string $page The page called when the hook is executed
	 *
	 */
	public function enqueue_scripts( $page ) {
		if ( 'plugins.php' === $page ) {
			wp_register_style( 'require_style', plugin_dir_url( __FILE__ ) . 'require-style.css', false, '1.0.0' );
			wp_enqueue_style( 'require_style' );
		}
	}

	/**
	 * @param array $actions The action links of the plugin
	 * @param string $plugin_file The plugin file
	 * @param $plugin_data The data of the plugin, name, version, ..., etc
	 *
	 * @return array Array with to elements, the first is an array with the satisfied requirements and the second is
	 * another array with the unsatisfied requirements
	 */
	public function check_plugin_action_links( $actions, $plugin_file, $plugin_data ) {
		$requirements = $this->get_plugin_requirements( $plugin_file );
		if ( 0 < count( $requirements ) ) {
			$requirements = $this->check_requirements( $requirements );
			if ( isset( $actions['activate'] ) && 0 < count( $requirements[1] ) ) {
				$actions['activate'] = '';
				foreach ( $requirements[0] as $requirement ) {
					$actions['activate'] .= sprintf(
						'<span class="satisfied">%s (%s)</span><br>',
						$requirement['Name'],
						$requirement['required_version']
					);
				}
				foreach ( $requirements[1] as $requirement ) {
					$actions['activate'] .= sprintf(
						'<span class="unsatisfied">%s (%s)</span><br>',
						$requirement['Name'],
						$requirement['required_version']
					);
				}
			}
		}

		return $actions;
	}

	/**
	 * Get the requirements of the plugin
	 *
	 * @param string $plugin_file The plugin file
	 *
	 * @return array The requirements of the plugin in te format plugin:version
	 */
	protected function get_plugin_requirements( $plugin_file ) {
		$plugin_file = $this->get_plugin_requirement_file( $plugin_file );

		if ( false === $plugin_file ) {
			return array();
		}

		return json_decode( file_get_contents( $plugin_file ), true );
	}

	/**
	 * Return the path of the requirements file of the plugin
	 *
	 * @param string $plugin_file The plugin name
	 *
	 * @return bool|string Returns false in case that the plugin don't have a requirements file, the path of the file
	 * instead
	 */
	protected function get_plugin_requirement_file( $plugin_file ) {
		$plugin_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . plugin_dir_path( $plugin_file );
		$file        = false;
		foreach ( array( 'requirements', 'require', 'depends_on' ) as $file_name ) {
			if ( file_exists( $plugin_file . $file_name ) ) {
				$file = $plugin_file . $file_name;
				break;
			}
		}

		return $file;
	}

	/**
	 * Check the plugin requirements to get the satisfied and unsatisfied requirements
	 *
	 * @param array $requirements The plugin requirements
	 *
	 * @return array
	 */
	protected function check_requirements( $requirements ) {
		$satisfied = $unsatisfied = array();
		foreach ( $requirements as $plugin => $version ) {
			$data                     = $this->available_plugins[ $plugin ];
			$data['required_version'] = $version;
			if ( $this->check_requirement( $plugin, $version ) ) {
				$satisfied[ $plugin ] = $data;
			} else {
				$unsatisfied[ $plugin ] = $data;
			}
		}

		return [ $satisfied, $unsatisfied ];
	}

	/**
	 * Check a simple requirement checking that the plugin is active and the version needed is greater or equaal than
	 * the installed version of the plugin
	 *
	 * @param string $plugin The plugin to see if is active
	 * @param string $version The version needed by the plugin
	 *
	 * @return bool
	 */
	protected function check_requirement( $plugin, $version ) {
		if ( ! isset( $this->active_plugins[ $plugin ] ) ) {
			return false;
		}

		$installed_version = '*';
		if ( isset( $this->available_plugins[ $plugin ]['Version'] ) ) {
			$installed_version = $this->available_plugins[ $plugin ]['Version'];
		}

		if ( version_compare( $installed_version, $version, '>=' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param array $plugins The plugins available in the wordpress engine
	 *
	 * @return array
	 */
	public function set_available_plugins( $plugins ) {
		$this->available_plugins = $plugins;
		$this->active_plugins    = get_option( 'active_plugins', array() );
		$this->active_plugins    = array_combine(
			array_values( $this->active_plugins ), array_keys( $this->active_plugins )
		);

		return $plugins;
	}
}

add_action( 'init', 'plugin_init' );
function plugin_init() {
	RequirementsCompiler::getInstance()->init();
}