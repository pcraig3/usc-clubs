<?php
/**
 * USC Clubs was a technically challenging plugin that nevertheless works pretty good now.
 * Essentially, it fakes the 'usc_club' data type, providing a fake archive page that's filterable and sortable
 * as well as a template for single USC Clubs.
 *
 * Also includes a hacky search function, which I can't honestly recommend trying in the future.
 * Basically, if a user searches something that could be a club, a club is inserted as a fake post as the
 * first result in the list of search results.  Don't judge me.
 *
 * @package   USC_Clubs
 * @author    Paul Craig <pcraig3@uwo.ca>
 * @license   GPL-2.0+
 * @link      http://testwestern.com
 * @copyright 2014 pcraig3
 */

/**
 * USC Clubs class.
 *
 * @package USC_Clubs
 * @author  Paul Craig <pcraig3@uwo.ca>
 */
class USC_Clubs {

    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @since   2.2.1
     *
     * @var     string
     */
    const VERSION = '2.2.1';

    /**
     * Unique identifier for your plugin.
     *
     * The variable name is used as the text domain when internationalizing strings
     * of text. Its value should match the Text Domain file header in the main
     * plugin file.
     *
     * @since    0.9.0
     *
     * @var      string
     */
    protected $plugin_slug = 'usc-clubs';

    /**
     * Instance of this class.
     *
     * @since    0.9.0
     *
     * @var      object
     */
    protected static $instance = null;

    /**
     * instance of the USC_CLUBS_WP_AJAX class.
     * registers and contains all of the WordPress AJAX methods
     *
     * @since 2.0.0
     *
     * @var object
     */
    public $wp_ajax = null;

    /**
     * Initialize the plugin by setting localization and loading public scripts
     * and styles.
     *
     * @since     2.2.0
     */
    private function __construct() {

        $this->wp_ajax = \USC_Clubs\WP_AJAX::get_instance();

        // Load plugin text domain
        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

        // Activate plugin when new blog is added
        add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

        // Load public-facing style sheet and JavaScript.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );


        //define the rewrite tag and a url pattern that triggers it
        add_action( 'init', array( $this, 'usc_clubs_rewrite_rules' ) );
        //add 'usc_clubs' to our query variables
        add_action( 'init', array( $this, 'add_usc_clubs' ) );
        //check the current request for a usc_clubs value
        add_action( 'template_redirect', array( $this, 'usc_clubs_redirect' ) );

        //add a shortcode to generate a list of clubs
        add_shortcode( 'usc_clubs', array( $this, 'usc_clubs_shortcode_function') );

        //add a sidebar for single clubs
        add_action( 'widgets_init', array( $this, 'usc_clubs_register_sidebars' ) );

        //the idea is to intercept the search query and put a club as the first result if we find a club that matches
        add_action( 'wp', array( $this, 'usc_clubs_inject_clubs_into_search_results') );
    }

    /**
     * Seemed simple, but turned into a pretty involved bit of code.  Basically, we've done a pretty
     * good job of faking the 'usc_clubs' data type in our WordPress install so far (all of the information is stored
     * offsite and is being beamed in via an API).
     *
     * One of our glaring problems was that clubs were not searchable using the main search bar.
     *
     * This method runs its own search for clubs names (more below) and, if found, will insert one extra result
     * in front of all the real search results.  We only want one because this methodology isn't scalable, so our
     * search algorithm tries to be reasonably sure that we know what people want before disadvantaging all other search
     * results with our 'fake' result.  It's probably not a huge problem, as most queries that find a club don't return
     * (m)any other results.
     *
     * Search algorithm to find a club:
     *  -if there is a transient in WordPress with clubs (ie, if someone has loaded the Clubs List page recently)
     *
     *      -look for an exact name match (punctuation disregarded): 'acapella project'
     *      -look for an exact shortname match: 'caisa'
     *      -look for any two words that match, with all words that mean 'club' included
     *          so, we can find 'western trivia club' or 'trivia club' or 'trivia society' or 'trivia association'
     *          basically, we know that they want a club, and we know that 'trivia' is the operative word
     *
     *  -NOTE: in the last instance, 'uwo' and 'western' terms are ignored because they're too general
     *
     * @since    2.2.1
     */
    function usc_clubs_inject_clubs_into_search_results() {

        global $wp_query;

        if( $wp_query->is_main_query() && ! $wp_query->is_admin && $wp_query->is_search ) {

            //no transient set means the default transient will be returned
            $transient_name = $this->wp_ajax->generate_transient_name( '' );
            $clubs_stored_in_cache = $this->wp_ajax->if_stored_in_wordpress_transient_cache( $transient_name );

            //if there is no transient, return.  Don't want to hold up every search made on our site.
            if( false === $clubs_stored_in_cache )
                return;

            $max = count($clubs_stored_in_cache);
            $found = -1;

            $matched_words_array = array();

            //if the search query is not an empty string
            if( ! empty($wp_query->query_vars['s'] ) ) {

                //first loop looks for exact matches.
                //We don't want the less-general loop returning a less-correct result before trying all the names
                for($index = 0; $index < $max && $found < 0 ;$index++) {

                    $club = $clubs_stored_in_cache[$index];

                    $search_terms_sanitized = $this->trim_strip_punctuation_lowercase( $wp_query->query_vars['s'] );
                    $club_name_sanitized = $this->trim_strip_punctuation_lowercase( $club['name'] );
                    $club_shortName_sanitized = $this->trim_strip_punctuation_lowercase( $club['shortName'] );

                    //exact match name
                    $found = ( $found < 0 && $search_terms_sanitized === $club_name_sanitized ) ? $index : $found;

                    //exact match short name
                    $found = ( $found < 0 && $search_terms_sanitized === $club_shortName_sanitized ) ? $index : $found;
                }

                /*
                 * Logic in here looks for at least two matched words, and also adds in all common words for 'club'
                 * when at least one is detected.  So if no club has been found yet, we try to match two words from the search terms
                 */
                for($index = 0; $index < $max && $found < 0 ;$index++) {

                    $club = $clubs_stored_in_cache[$index];

                    //set up club words
                    $common_words_for_clubs = array(
                        'club', 'association', 'society', 'fellowship', 'organization'
                    );

                    $search_terms = $wp_query->query_vars['search_terms'];

                    $words_in_both_arrays = array_intersect( $wp_query->query_vars['search_terms'], $common_words_for_clubs);

                    if( ! empty( $words_in_both_arrays ) )
                        $search_terms = array_unique( array_merge( $wp_query->query_vars['search_terms'], $common_words_for_clubs) );

                    /*at this point, $search_terms contain either what was searched for OR all of the different names
                    of clubs if one was detected.  Reason is to return "Persian Club" if user types in "Persian Society."                 */
                    $search_terms = array_map( array( $this, "trim_strip_punctuation_lowercase"), $search_terms);
                    //remove 'western' and 'uwo' as this will lead to a lot of bad matches
                    $search_terms = array_diff( $search_terms, array( 'western', 'uwo' ) );
                    //$search_terms = array_diff( $search_terms, array( 'western', 'uwo', 'students' ) );

                    $words_in_club_name = ( !empty( $club['name'] ) ) ? explode( ' ', $club['name']) : '';

                    $temp = array();
                    foreach( $words_in_club_name as $word ) {
                        //if first letter is not uppercase
                        if( ctype_upper( substr( $word, 0, 1 ) ) )
                            //strip punctuation, trim
                            array_push($temp, $word);
                    }

                    //at this point we have arrays of important words (no more 'and', 'of', etc)
                    $words_in_club_name = array_map( array( $this, "trim_strip_punctuation_lowercase"), $temp);

                    array_push($matched_words_array, count( array_intersect( $search_terms, $words_in_club_name) ) );
                }

            }

            $max_matched_words = ( !empty( $matched_words_array ) ) ? max( $matched_words_array ) : 0;

            ///if we have a match of at least two
            if( $max_matched_words > 1 )
                //get the first array key with the max number of matched words
                $found = array_shift( array_keys( $matched_words_array, $max_matched_words ) );

            //if a club has been found
            if($found >= 0) {

                $found_club = $clubs_stored_in_cache[$found];

                $today = date('Y-m-d H:i:s');
                $shortName_brackets = ( !empty( $found_club['shortName'] ) ) ? ' (' . $found_club['shortName'] . ')' : '';

                $summary = 'Click to find out more about the ' . $found_club['name'] . '!';
                if( ! empty( $found_club['summary'] ) )
                    $summary = wp_strip_all_tags($found_club['summary']);

                else if( ! empty(  $found_club['description'] ) )
                    $summary = wp_strip_all_tags($found_club['description']);

                if( strlen($summary) > 270 )
                    $summary = substr($summary, 0, 270) . '...';

                $post_type_name = 'usc_clubs';

                $club_post = new stdClass();
                $club_post->id = $found_club['organizationId']; //clubs
                $club_post->post_author = '1'; //sets it as the user id = 1
                $club_post->post_date = $today; //set it to today's date
                $club_post->post_date_gmt =  $today;
                $club_post->post_content = $summary;
                $club_post->post_title = $found_club['name'] . $shortName_brackets;
                $club_post->post_excerpt = $summary;
                $club_post->post_status = 'publish';
                $club_post->comment_status = 'closed';
                $club_post->ping_status = 'closed';
                $club_post->post_password = '';
                $club_post->post_name = "website_link";
                $club_post->to_ping = '';
                $club_post->pinged = '';
                $club_post->post_modified = $today;
                $club_post->post_modified_gmt = $today;
                $club_post->post_content_filtered = '';
                $club_post->post_parent = '1'; //I don't know what this means
                $club_post->guid = "http://westernusc.org/index.php?usc_clubs=" . $found_club['organizationId'];
                $club_post->menu_order = 0;
                $club_post->post_type = $post_type_name;
                $club_post->comment_count = '0';
                $club_post->filter = 'raw';

                //create a post_type 'usc_clubs' for the meta-data in the search result
                $this->register_club_post_type_temporarily( $post_type_name );

                add_filter( 'post_type_link', function($url, $post) use ( $found_club, $post_type_name ) {

                    if( $post->post_type === $post_type_name ) {
                        return trailingslashit( get_site_url() )
                        . trailingslashit( get_post_type_object( $post->post_type )->rewrite['slug'] )
                        . trailingslashit( $found_club['organizationId'] );
                    }

                }, 10, 2  );

                /* Increase found_posts by one and post_count by one and just insert our one new post
                into the array of post objects and put the found_club as our first post */
                $wp_query->found_posts = $wp_query->found_posts + 1;
                $wp_query->post_count = $wp_query->post_count + 1;

                array_unshift($wp_query->posts, $club_post);
                $wp_query->post = $club_post;
            }
        }
    }

    /**
     * utility function which removes punctuation, trims surrounding whitespace, and lowercases any string parameters
     *
     * @since    2.2.0
     *
     * @param $arr_value    string a string to sanitize
     * @return string       string the sanitized string
     */
    private function trim_strip_punctuation_lowercase($arr_value) {

        return trim( strtolower( preg_replace('/\p{P}/u', '', $arr_value) ) );
    }

    /**
     * Bit of an hacky function.  Basically, in our search results, we will list a club or a job or whatever along with a
     * link to its archive. Unfortunately, the clubs archive (westernusc.org/clubs/list/) isn't registered in WordPress as
     * a Post Type archive
     *
     * So!  We register the 'usc_clubs' type while search.php (the file that lists search results) is running,
     * and then de-register it once we reach wp_footer.  This means that our search results include the club archive,
     * but that clubs are de-registered right afterwards.
     *
     * @since    2.2.0
     *
     * @param string $post_type_name        the name of the post type to register while search results are being created
     */
    private function register_club_post_type_temporarily( $post_type_name = 'usc_clubs' ) {

        /* register the post type
           This is ONLY to get the club post_type to show up in the search results */
        $labels = array(
            'name'               => 'Clubs',
            'singular_name'      => 'Club'
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'clubs/list' ),
            'has_archive'        => true,
        );

        register_post_type( $post_type_name, $args );

        add_action( 'wp_footer', function() use ( $post_type_name ) {

                global $wp_post_types;
                if ( isset( $wp_post_types[ $post_type_name ] ) ) {
                    unset( $wp_post_types[ $post_type_name ] );
                    return true;
                }
                return false;

            }, 11
        );

    }

    /**
     * function hijacks the main WordPress Query, forcing it to return nothing.
     * Normally, this is a really stupid thing to do, but in this case it's justified because we're creating a listing
     * of results that aren't in the database.
     * If we didn't use this function, a bunch of useless posts would be retrieved from the database.
     *
     * Was helped greatly by an answer on wordpress stackexchange
     * @see: http://wordpress.stackexchange.com/questions/97811/how-to-prevent-execution-of-default-query-while-preserving-ability-to-use-wp-qu
     *
     * @since    2.0.0
     */
    public function suppress_main_query( $request, $query ){

        if( $query->is_main_query() && ! $query->is_admin  )
            return false;
        else
            return $request;
    }

    /**
     * Function checks if the current URL is requesting a single club.
     * If so, it registers a function that generates the web page for a specific club
     *
     * @since    2.0.0
     */
    public function usc_clubs_redirect() {

        // Allow access to WordPress query variables
        global $wp_query;

        // Retrieve WordPress query variables
        $query_vars = $wp_query->query_vars;

        // Check if requesting a club page
        if(!empty($query_vars['usc_clubs'])) {

            // CLUB PAGE REQUESTED!

            //tank the main query so that we're not grabbing a bunch of posts unnecessarily
            add_filter('posts_request', array( $this, 'suppress_main_query' ), 10, 2);

            add_filter( 'template_include', array( $this, 'call_template_club_single' ) );
        }
    }

    /**
     * Function calls our clubs template rather than whatever else WP falls back on.
     *
     * @param $original_template    hands the keys back to WordPress if the if switch was meaningful
     *
     * @since    2.2.0
     *
     * @return string               returns the web page generated by single-club.php
     */
    public function call_template_club_single( $original_template ) {

        //query_var looks like "1717-abolition-project-against-human-trafficking-tapaht"
        $club_id = intval( array_shift( explode("-", get_query_var( 'usc_clubs' ) ) ) );

        //getting the club we want, as well as the 'next' and 'prev' ones
        $one_club_you_want_two_you_dont = $this->wp_ajax->get_club_along_with_prev_and_next_club( $club_id );

        if ( !empty( $one_club_you_want_two_you_dont ) ) {

            $current_club = $previous_club = $next_club = null; //these should be set by the array
            extract($one_club_you_want_two_you_dont, EXTR_OVERWRITE);

            /* THE REASON TO USE INCLUDE IS SO THAT THE TEMPLATE FILES HAVE ACCESS TO OUR WORDPRESS VARIABLES
                This is why we're doing the extracting in the lines above this one.
                @see: http://keithdevon.com/passing-variables-to-get_template_part-in-wordpress/
             */
            //try to load template from the THEME first.  Note the different filename
            $club_template_theme = locate_template('single-usc_clubs.php', false);
            if( !empty ( $club_template_theme ) )
                include($club_template_theme);

            //load template in PLUGIN after trying the theme.
            $club_template_plugin = locate_template('../../plugins/usc-clubs/templates/single-usc_clubs_westernusc.php', false);
            if( !empty ( $club_template_plugin ) )
                include($club_template_plugin);
        }
        else
            return $original_template;
    }

    /**
     * Function adds the 'usc_clubs' parameter to the query variables, as WordPress calls them.
     * If the function below this one describes the pattern in which 'usc_clubs' should be used,
     * this is the function that registers the name of the variable with WordPress
     *
     * more information here:
     * @see: http://wordpress.stackexchange.com/questions/71305/when-should-add-rewrite-tag-be-used
     *
     * @since    2.0.0
     */
    public function add_usc_clubs() {

        global $wp;

        $wp->add_query_var('usc_clubs');
    }

    /**
     * Static function sets the rewrite rules for individual club pages.  The idea is that we should be able
     * to generate a club-specific template if a certain API pattern is matched.
     * Function is static so that it can be called on plugin activation.
     *
     * @since    2.0.0
     */
    public static function usc_clubs_rewrite_rules() {

        // Custom tag we will be using to recognize page requests
        add_rewrite_tag('%usc_clubs%','([^/]+)');

        // Custom rewrite rule to hijack page generation
        add_rewrite_rule('clubs/list/([^/]+)/?$','index.php?usc_clubs=$matches[1]','top');
    }

    /**
     * Guess what this one does.
     *
     * @since    2.1.0
     */
    public function usc_clubs_register_sidebars() {

        /* Register the usc clubs single sidebar. */
        register_sidebar(
            array(
                'id' => 'usc_clubs_single_sidebar',
                'name' => __( 'USC Club Single Sidebar', 'usc-clubs' ),
                'description' => __( 'Widgets meant only for individual USC Club Pages.', 'usc-clubs' ),
                'before_widget' => '<aside id="%1$s" class="et_pb_widget %2$s">',
                'after_widget' => '</aside>',
                'before_title' => '<h4 class="widgettitle">',
                'after_title' => '</h4>'
            )
        );
    }


    /**
     * Function meant to target the [usc_clubs] shortcode.  Grabs the attributes in the shortcode to
     * call a function somewhere down there.
     *
     * @param $atts         create an associative array based on attributes and values in the shortcode
     *
     * @since    2.1.0
     *
     * @return string       a complimentary adjective for students
     */
    public function usc_clubs_shortcode_function ( $atts ) {

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
        $returned_array = $this->wp_ajax->get_clubs();

        $parameters = array(

            $returned_array['response'],
            $returned_array['is_cached'],
            $returned_array['transient_name'],
        );

        if( is_array( $returned_array ) ) {

            $usc_clubs_shortcode_function = (string) $get . "_" . (string) $show;

            ob_start();

            //calls a function based on the 'get' and 'show' parameters
            echo call_user_func_array( array( $this, $usc_clubs_shortcode_function ), $parameters );

            $result = ob_get_clean();
        }

        if( $result ) {

            return $result;
        }

        return "false";
    }

    /**
     * Return the number of clubs *that have categories* as an integer
     *
     * @param array $clubs_array      an array of clubs originating from a csv file on github
     *
     * @since    1.1.2
     *
     * @return int              the number of clubs assigned to categories on github
     */
    private function clubs_count( array $clubs_array ) {

        return intval( count( $this->return_categorized_clubs( $clubs_array ) ) );
    }

    /**
     * function accepts a clubs array and removes any without a category set
     * Reason being is that our Filter JS hides any clubs without categories, so we only need to know the number of
     * clubs that have categories.
     *
     * @since    1.1.2
     *
     * @param array $clubs_array    an array of clubs
     * @return array                an array of clubs stripped of those without categories
     */
    private function return_categorized_clubs( array $clubs_array ) {

        foreach($clubs_array as $index => $club)
            if( empty( $club['categories'] ) )
                unset( $clubs_array[$index] );

        return array_values( $clubs_array );
    }

    /**
     * Return HTML code to list all of the clubs known about on github
     *
     * @since    2.1.0
     *
     * @param array $clubs_array        An array of clubs originating from a csv file on github
     * @param bool $is_cached           Pass this to JS.  If true, trigger an ajax method that updates the cache.
     * @param string $transient_name   Transient name sets the name of the transient if we asynchronously update it.
     * @return string                   the names of all of the clubs on github
     */
    private function clubs_list( $clubs_array, $is_cached, $transient_name ) {

        wp_enqueue_script( 'tinysort', plugins_url( '/bower_components/tinysort/dist/jquery.tinysort.min.js', __DIR__ ), array( 'jquery' ), self::VERSION );

        //<soops h4ck> disable jQuery.noConflict for the length of the externally-hosted filter.js
        wp_enqueue_script( 'jquery_no_conflict_disable', plugins_url( '/assets/js/jquery-no-conflict-disable.js', __FILE__ ), array( 'jquery', 'tinysort' ), self::VERSION );
        wp_enqueue_script( 'filterjs', plugins_url( '/assets/js/filter.js', __FILE__ ), array( 'jquery', 'tinysort', 'jquery-ui-core', 'jquery_no_conflict_disable' ), self::VERSION );

        wp_enqueue_script( 'public_filterjs', plugins_url( '/assets/js/public-filter.js', __FILE__ ), array( 'jquery', 'tinysort', 'jquery-ui-core', 'filterjs' ), self::VERSION );

        // declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
        wp_localize_script( 'public_filterjs', "options", array(
            'clubs'             => json_encode($clubs_array),
            'categories'        => json_encode($this->return_categories_array($clubs_array)),
            'is_cached'         => $is_cached,
            'ajax_url'          => admin_url( 'admin-ajax.php' ),
            'transient_name'    => $transient_name,
        ) );

        return require_once('views/usc_clubs-list.php');
    }

    /**
     * There is no place in the API where we can get all of the categories, so the workaround is just to go through
     * all of the clubs and find categories they belong to.
     *
     * This means that we might miss a category if one exists in WesternLink but has no clubs assigned to it,
     * but in that case we don't need it as a filter.
     * So our stopgap solution is fine.
     *
     * @since   2.0.0
     *
     * @param array $clubs_array    the array of clubs from westernlink
     * @return array                the array of unique categories assigned to the clubs on westernlink
     */
    private function return_categories_array($clubs_array) {

        $categories_array = array();
        $category_ids = array();

        foreach($clubs_array as $club) {

            foreach($club['categories'] as $category) {

                //hasn't been stored in our array yet.
                if( !in_array( $category['categoryId'], $category_ids) ) {

                    array_push($category_ids, $category['categoryId']);
                    array_push($categories_array, $category);
                }
            }

        }
        unset($category_ids);

        $sort_criteria =
            array('categoryName' => array(SORT_ASC, SORT_STRING),
            );

        return $this->wp_ajax->multisort($categories_array, $sort_criteria, true);
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

        self::usc_clubs_rewrite_rules();

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

        wp_enqueue_style( 'prefix-font-awesome', '//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css', array(), '4.1.0' );
    }

    /**
     * Register and enqueue public-facing JavaScript files.
     *
     * @since    0.9.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
    }
}
