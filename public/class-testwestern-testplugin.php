<?php
/**
 * Testwestern Testplugin.
 *
 * @package   Testwestern_Testplugin
 * @author    Paul Craig <pcraig3@uwo.ca>
 * @license   GPL-2.0+
 * @link      http://testwestern.com
 * @copyright 2014 University Students' Council
 */

/**
 * Testwestern Testplugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `testwestern-testplugin-admin.php`
 *
 * @TODO: Rename this class to a proper name for your plugin.
 *
 * @package Testwestern_Testplugin
 * @author  Paul Craig <pcraig3@uwo.ca>
 */
class Testwestern_Testplugin {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.1.1
	 *
	 * @var     string
	 */
	const VERSION = '1.1.1';

	/**
	 * @TODO - Rename "testwestern-testplugin" to the name your your plugin
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    0.9.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'testwestern-testplugin';

	/**
	 * Instance of this class.
	 *
	 * @since    0.9.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.1
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/* Define custom functionality.
		 * Refer To http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
	    add_action( 'init', array( $this, 'clubsapi_rewrite_rules' ) );
		//add_filter( 'the_content', array( $this, 'filter_content_string' ) );

        add_shortcode( 'testplugin', array( $this, 'testplugin_func') );

    }


    /**
     * Function meant to target the [testplugin] shortcode.  Grabs the attributes in the shortcode to
     * call a function somewhere down there.
     *
     * @param $atts         create an associative array based on attributes and values in the shortcode
     *
     * @since    1.1.0
     *
     * @return string       a complimentary adjective for students
     */
    public function testplugin_func ( $atts ) {

        //initialize your variables
        $get = $show = $result = false;

        extract(
            shortcode_atts(
                array(
                    'get'   => 'clubs',
                    'show'  => 'count',
                ), $atts ),
            EXTR_OVERWRITE);

        //function returns the clubs on github as a json array.
        //in the future, we'll have this take a parameter
        $returned_array = $this->call_api();

        if( is_array( $returned_array ) ) {

            $testplugin_function = (string) $get . "_" . (string) $show;

            ob_start();

            echo call_user_func( array( $this, $testplugin_function ), $returned_array );

            $result = ob_get_clean();
        }

        if( $result ) {

            return $result;
        }

        return "false";
    }

    /**
     * Return the number of clubs as an integer
     *
     * @param $clubs_array      an array of clubs originating from a csv file on github
     *
     * @since    1.1.1
     *
     * @return int              the number of clubs on github
     */
    private function clubs_count( $clubs_array ) {

        return intval( $clubs_array['total'] );
    }

    /**
     * Return HTML code to list all of the clubs known about on github
     *
     * @param $clubs_array      an array of clubs originating from a csv file on github
     *
     * @since    1.1.1
     *
     * @return string           the names of all of the clubs on github
     */
    private function clubs_list( $clubs_array ) {

        $html_string = '<blockquote>';

        $max = intval( $clubs_array['total'] );

        for($i = 0; $i < $max; $i++) {

            $current_club = $clubs_array['clubs'][$i];

            $email = sanitize_email( $current_club['email'] );
            $fb_url = esc_url( $current_club['facebookUrl'] );
            $tw_url = esc_url( $current_club['twitterUrl'] );

            $html_string .= '<p style="text-align:left;" title="' . esc_attr( $current_club['organizationId'] ) .
                '">' . (intval( $current_club['id'] ) + 1) . '. ' . esc_html( $current_club['name'] );

            if($email)
                $html_string .= ' | <a href="mailto:' . antispambot( $email, 1 ) .
                                '" title="Click to e-mail" >Email</a>';
            if($fb_url)
                $html_string .= ' | <a href="' . $fb_url .
                    '" title="View Facebook page" >Facebook</a>';

            if($tw_url)
                $html_string .= ' | <a href="' . $tw_url .
                    '" title="View Twitter profile" >Twitter</a>';

            $html_string .= '</p>';
        }

        $html_string .= "</blockquote>";

        return $html_string;
    }

    /**
     * Calls some page which calls a github csv file and converts it to json.
     *
     * @since    1.0.1
     *
     * @return array       at this point, return the clubs known about on github as an indexed array
     */
    private function call_api() {

        //the url where to get the github clubs
        $ch = curl_init('http://testwestern.com/github/json.php');

        curl_setopt( $ch, CURLOPT_HEADER, false ); //TRUE to include the header in the output.
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ); //TRUE to return transfer as a string instead of outputting it out directly.
        //curl_setopt($ch, CURLOPT_FRESH_CONNECT, true); //TRUE to force the use of a new connection instead of a cached one.

        $returnedString = curl_exec( $ch );
        curl_close( $ch );

        // Define the errors.
        /* $constants = get_defined_constants(true);

        /*$json_errors = array();
        foreach ($constants["json"] as $name => $value) {
            if (!strncmp($name, "JSON_ERROR_", 11)) {
                $json_errors[$value] = $name;
            }
        }

        echo '<h1>';
        echo 'Last error: ', $json_errors[json_last_error()], PHP_EOL, PHP_EOL;
        echo '</h1>';
        die;
        */

        return json_decode( $returnedString, true );
    }

	/**
	 * Return the plugin slug.
	 *
	 * @since    0.9.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.9.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    0.9.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    0.9.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    0.9.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    0.9.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    0.9.0
	 */
	private static function single_activate() {

        clubsapi_rewrite_rules();

        // flush rewrite rules - only do this on activation as anything more frequent is bad!
        flush_rewrite_rules();
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    0.9.0
	 */
	private static function single_deactivate() {

        // flush rules on deactivate as well so they're not left hanging around uselessly
        flush_rewrite_rules();
    }

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    0.9.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    0.9.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueue public-facing JavaScript files.
	 *
	 * @since    0.9.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}


    public function clubsapi_rewrite_rules() {

        // Custom tag we will be using to recognize page requests
        add_rewrite_tag('%clubsapi%','([^/]+)');

        // Custom rewrite rule to hijack page generation
        add_rewrite_rule('clubs/([^/]+)/?$','index.php?clubsapi=$matches[1]','top');
    }

}
