<?php
/**
 * Created by PhpStorm.
 * User: Paul
 * Date: 18/06/14
 * Time: 11:05 AM
 */
namespace USC_Clubs;

class WP_AJAX {

    /**
     * Instance of this class.
     *
     * @since    2.0.0
     *
     * @var      object
     */
    protected static $instance  = null;

    protected $expiration = null;

    private $wp_using_ext_object_cache_status;

    private $default_transient_name;

    private function __construct() {

        add_action("wp_ajax_get_clubs_ajax", array( $this, "get_clubs_ajax" ) );
        add_action("wp_ajax_nopriv_get_clubs_ajax", array( $this, "get_clubs_ajax") );

        add_action("wp_ajax_update_wordpress_clubs_cache", array( $this, "update_wordpress_clubs_cache" ) );
        add_action("wp_ajax_nopriv_update_wordpress_clubs_cache", array( $this, "update_wordpress_clubs_cache") );

        $this->expiration = WEEK_IN_SECONDS;
        $this->default_transient_name = 'usc_clubs_get_clubs';

        /*
         * set an initial value to this variable, in case we call the method to overwrite the global value before we
         * call the method to store it
         */
        global $_wp_using_ext_object_cache;

        $this->wp_using_ext_object_cache_status = $_wp_using_ext_object_cache;
    }

    /**
     * Return an instance of this class.
     *
     * @since    2.0.0
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
     * one of two functions created to get around a bug with the APC backend object-caching plugin
     * Basically, our APC caching backend plugin was setting $_wp_using_ext_object_cache to true, with the
     * unintended side-effect that any time we saved a transient, it wouldn't persist through the next pageload.
     *
     * So this function sets $wp_using_ext_object_cache_status to false so that setting a transient will work
     *
     * More detailed discussion here:
     * @see: https://github.com/michaeluno/admin-page-framework/issues/118
     *
     * @since    2.1.0
     */
    public function turn_off_object_cache_so_our_bloody_plugin_works() {

        global $_wp_using_ext_object_cache;

        $this->wp_using_ext_object_cache_status = $_wp_using_ext_object_cache;

        $_wp_using_ext_object_cache = false;

    }


    /**
     * one of two functions created to get around a bug with the APC backend object-caching plugin
     * Basically, our APC caching backend plugin was setting $_wp_using_ext_object_cache to true, with the
     * unintended side-effect that any time we saved a transient, it wouldn't persist through the next pageload.
     *
     * So this function assumes the 'turn_off_object_cache_so_our_bloody_plugin_works' was called first,
     * sets the $wp_using_ext_object_cache_status back to its original value
     *
     * More detailed discussion here:
     * @see: https://github.com/michaeluno/admin-page-framework/issues/118
     *
     * @since    2.1.0
     */
    public function turn_object_caching_back_on_for_the_next_poor_sod() {

        global $_wp_using_ext_object_cache;

        $_wp_using_ext_object_cache =  $this->wp_using_ext_object_cache_status;
    }

    /**
     * Does (a bit more than) what it says on the box. gets all facebook and db events (and then merges their values)
     * and then returns everything to the javascript function waiting for it.
     *
     * @deprecated: we're never calling the clubs using ajax.
     *
     * @since    2.0.0
     *
    public function get_clubs_ajax() {

        $this->make_sure_the_nonce_checks_out( $_POST['attr_id'], $_POST['nonce'] );

        //set variables if any

        $clubs_array = $this->get_clubs();


        //set result/response
        //$result['if_cached'] = $clubs_stored_in_cache;
        //$result['success'] = ( false !== $response ) ? true : false;
        echo json_encode($clubs_array);
        die();
    }

    /**
     * This method implements our WordPress caching system. Basically, instead of calling (@see) call_clubs_api all the time,
     * we can store its value to the cache (removing the old value first). So that the next time it's called, we might just
     * get it from the WP_database instead of going all the way to Facebook for the data.
     *
     * The assumption here is that we've run (@see) get_clubs first, and then this method will be called next (via AJAX),
     * and the next time get_clubs is called, it quickly returns the cached value.
     *
     * @since    2.1.0
     */
    public function update_wordpress_clubs_cache() {

        $this->make_sure_the_nonce_checks_out( $_POST['attr_id'], $_POST['nonce'] );

        ignore_user_abort(true);

        $transient_name = ( isset($_POST['transient_name'] ) && !empty($_POST['transient_name']) )
            ? $_POST['transient_name'] : 'usc_clubs_get_clubs';

        $json_decoded_events_array = $this->get_clubs( $transient_name );

        $json_decoded_events_array = $json_decoded_events_array['response'];

        $expiration = $this->expiration;

        $this->turn_off_object_cache_so_our_bloody_plugin_works();

        $deleted = delete_site_transient( $transient_name );

        //returns true or false
        $result['success'] = set_site_transient( $transient_name, json_encode($json_decoded_events_array), $expiration );
        $result['transient_name'] = $transient_name;
        $result['deleted'] = $deleted;

        $this->turn_object_caching_back_on_for_the_next_poor_sod();

        echo json_encode( $result );
        die();
    }

    /**
     * Bare-bones method that rejects non-logged-in users.  Used for all ajax methods.
     *
     * @since    2.0.0
     *
     * @return 		echoes a string telling non-logged in users to log in.
     */
    public function login_please() {
        echo "Log in before you do that, you cheeky monkey.";
        die();
    }

    /** NON AJAX STUFF  **/

    /**
     * Method that abstracts the nonce-checking.  It's not actually that interesting.
     * If any of the values aren't set (or are wrong), execution halts.
     *
     * @since    2.0.0
     *
     * @param string $attr_id   all of my nonce names end with "_nonce" and so we need the prefix
     * @param string $nonce     the nonce itself
     */
    private function make_sure_the_nonce_checks_out( $attr_id = "", $nonce = "") {

        if ( ! wp_verify_nonce( $nonce, $attr_id . "_nonce") )
            exit("No naughty business please");

    }

    /**
     * This function essentially works as a big whitelist, as well as it generates a club's URL.
     *
     * It takes the unformatted json_response and, (if it's not empty), filters out all not active clubs
     * or not ratified clubs.
     * For each club, it generates a sequential id (starts at 1), as well as a url for them.
     * Afterwards, it removes all array keys which aren't in the $fields_to_keep array.
     *
     * returns the modified response if it's not empty (which it shouldn't be)
     *
     * @since    2.1.0
     *
     * @param array $json_response      unfiltered json response returned from API
     * @param array $fields_to_keep     array of keys we want to keep for each club
     * @return array|null               modified array with whitelisted fields, an id num, and a url
     */
    private function filter_js_format_API_response( array $json_response = null,
                                                    array $fields_to_keep = array(
                                                        'organizationId',
                                                        'name',
                                                        'shortName',
                                                        'summary',
                                                        'description',
                                                        'email',
                                                        'externalWebsite',
                                                        'facebookUrl',
                                                        'twitterUrl',
                                                        'flickrFeedUrl',
                                                        'youtubeChannelUrl',
                                                        'googleCalendarUrl',
                                                        'profileImageUrl',
                                                        'profileUrl',
                                                        'primaryContactName',
                                                        'primaryContactCampusEmail',
                                                        'categories'
                                                    )
    ) {

        $json_response_modified = null;
        if ( null == ( $json_response ) ) {

            return new \WP_Error( 'api_error', __( 'Sorry, but we\'ve got nothing back from the API.  See if paul_craig_16@hotmail.com can do anything about it.', 'usc-clubs' ) );

        } elseif ( ! empty( $fields_to_keep ) ) {
            /** Just so we're all on the same page, here's the full list we can get from a club.
             *
            organizationId: 1646
            name: "Acapella Project"
            status: "Active"
            shortName: "TAP"
            summary: "The Acapella Project is an a-cappella choir which aims to bring its unique brand of music to the community!\ \ \ \ Our first rehearsal is this coming Sunday, September 22, from 1 to 3 pm in VAC100. Hope to see you all there!"
            description: "<p><span>The Acapella Project is a USC governed acapella choir which provides an open forum for those interested in singing, arra<\/span><span class=\"x_text_exposed_show\">nging, and beatboxing a-cappella. We accept members regardless of experience, and aim to educate and improve singers&rsquo; musicianship, vocal health, and performing experience.&nbsp;<\/span><\/p>"
            addressStreet1: ""
            addressStreet2: ""
            addressCity: ""
            addressStateProvince: ""
            addressZipPostal: ""
            phoneNumber: ""
            extension: ""
            faxNumber: ""
            email: "theacapellaproject@gmail.com"
            externalWebsite: ""
            facebookUrl: "http:\/\/www.facebook.com\/theacapellaproject"
            twitterUrl: ""
            flickrFeedUrl: ""
            youtubeChannelUrl: ""
            googleCalendarUrl: ""
            profileImageUrl: "westernu.collegiatelink.net\/images\/W170xL170\/0\/noshadow\/Profile\/3afeb90c7fbd43c5b6cf8d4b40b7966d.jpg"
            profileUrl: "westernu.collegiatelink.net\/organization\/acapellaproject"
            directoryVisibility: "Visible"
            membershipType: "Open"
            typeId: 66
            typeName: "Ratified Clubs"
            parentId: 1567
            parentName: "University Students' Council"
            primaryContactId: 48817
            primaryContactName: "Xue Qing Yang"
            primaryContactCampusEmail: "xyang295@uwo.ca"
            categories: [
            {
            categoryId: 165
            categoryName: "Music and Performing Arts"
            }
            ]
            customFields: [ ]
             */

            $clubs = $json_response;

            $temp_club = array();
            foreach( $clubs as $num => $club ) {

                if('Active' === $club['status'] && "Ratified Clubs" === $club['typeName'] ) {

                    $temp_club['id'] = $num + 0; //filter_js needs sequential id numbers
                    $temp_club['url'] = $this->generate_club_url( $club );


                    foreach( $fields_to_keep as &$field ) {
                        $temp_club[$field] = $club[$field];
                    }
                    unset($field);

                    $clubs[$num] = $temp_club;
                }
                else
                    //if not Active + Ratified, remove the club
                    unset($club[$num]);
            }
            //array_values in case any clubs were removed.
            $json_response_modified = array_values($clubs);

        } // end if/else
        return ( is_null($json_response_modified) ) ? $json_response : $json_response_modified;
    }

    /**
     * Since we're pretending that the clubs are on our site, they need a url,
     * but since they're not on our site, they don't have one.  So this function creates
     * club urls.
     *
     * URLs generated are formatted: {club id}-{club name, lowercased, no-punctuation, hyphens}-{club shortname}
     * This is so that when I type 'caisa' or 'canadian International' into my browser bar, it will be suggested
     * if I've visited the page before
     *
     * ie, "http://westernusc.org/clubs/list/1737-canadian-asian-international-students-association-caisa/"
     *
     * @since    2.1.0
     *
     * @param array $club           an array representing a club returned from westernlink
     * @param string $url_trunk     the part of the url between the root_url and the beginning of the club link
     * @param string $root_url      the homepage domain
     *
     * @return string               a shiny new url for one of our clubs
     */
    private function generate_club_url($club, $url_trunk = "clubs/list/", $root_url = '') {

        $root_url = ( isset( $root_url ) && ! empty( $root_url ) ) ? esc_url( $root_url )
            : trailingslashit(get_bloginfo('wpurl'));

        $url_leaf = $club['organizationId'];

        //clubs have to have organization IDs for us to make them a URL
        if(empty($url_leaf))
            return "#";

        $name_shortName = array( 'name', 'shortName' );

        foreach($name_shortName as $name)
            $url_leaf .= (  isset( $club[$name] ) && ! empty( $club[$name] ) ) ?  '-' . sanitize_title($club[$name]) : '';

        return $root_url . trailingslashit($url_trunk) . trailingslashit( $url_leaf );
    }

    /**
     * function which returns clubs from our github API.
     * Returns the clubs (whether cached or not), formats them, and then delivers them to whomever asked for them.
     *
     * @since    2.1.0
     *
     * @param string $to_append     a string we'd like to append to our transient_name, to be able individuate save
     *                          different transients under other (predictable) name patterns
     *
     * @return array                filtered array of clubs from github, as well as a transient name and other goodies.
     */
    public function get_clubs( $to_append = '' ) {

        $transient_name = $this->generate_transient_name( '', $to_append );

        //site caching turned off and on in this method
        $clubs_stored_in_cache = $this->if_stored_in_wordpress_transient_cache( $transient_name );

        if( false === $clubs_stored_in_cache ) {

            $clubs_array = $this->call_clubs_api();
            $clubs_array = $this->filter_js_format_API_response($clubs_array['items']);

            $response['response']  = $clubs_array;
            $response['is_cached'] = false;
        } else {

            //wp_die('yes');

            //events stored in cache are already formatted
            $response['response'] = $clubs_stored_in_cache;
            $response['is_cached'] = true;
        }

        $response['transient_name'] = $transient_name;

        return $response;
    }

    /**
     * Returns one club that we want as well as the next and previous clubs to it in an array.
     *
     * Also, save result as a transient so that we can later get the same club more quickly if necessary.
     *
     * @since    2.1.0
     *
     * @param int $desired_club_id  the id of the club we're retrieving
     *
     * @return array                an array containing the club we want, and then the one directly preceding and
     *                          following it (if they both exist)
     */
    public function get_club_along_with_prev_and_next_club( $desired_club_id ) {

        //function returns the clubs on github as a json array.
        $clubs_array_response = $this->get_clubs( $desired_club_id );
        $clubs_array = $clubs_array_response['response'];

        //if you got a cached result, return.
        if($clubs_array_response['is_cached']) {
            $clubs_array['is_cached'] = true;
            return $clubs_array;
        }

        if( ! is_array( $clubs_array ) ) {
            return array();
        }

        $max = intval( count($clubs_array) );

        $current_club = $previous_club = $next_club = null;

        for($i = 0; $i < $max && is_null( $current_club ); $i++) {

            //if it matches
            if( $desired_club_id === $clubs_array[$i]['organizationId']) {

                if( $i > 0 ) {
                    $previous_club = $clubs_array[$i - 1];
                }

                if( $i < ( $max-1 ) ) {
                    $next_club = $clubs_array[$i + 1];
                }

                $current_club = $clubs_array[$i];
            }
        }

        if( is_null( $current_club ) ) {
            return array();
        }

        $clubs_found = array(
            'current_club'  => $current_club,
            'previous_club' => $previous_club,
            'next_club'     => $next_club,
        );

        //if we've gotten here, it means our result hasn't been cached.  so cache it.
        $this->turn_off_object_cache_so_our_bloody_plugin_works();

        set_site_transient($clubs_array_response['transient_name'], json_encode($clubs_found), $this->expiration );

        $clubs_found['is_cached'] = 'false'; //we're just letting us know that it wasn't cached when we got the data

        $this->turn_object_caching_back_on_for_the_next_poor_sod();

        return $clubs_found;
    }

    /**
     * In order to cache a result properly, we need to make sure that only that particular API call can access it.
     * In the case of clubs, this is a moot point.  We can call it whatever we want.
     * We're always calling the same endpoint and getting everything.
     *
     * @since    2.0.0
     *
     * @param   $transient_name     the first part of the transient. if empty, set to default_tranisent_name
     * @param   string $to_append   a string to append to the transient
     * @return  string|WP_Error  a shiny new name for our transient
     */
    public function generate_transient_name( $transient_name, $to_append = '' ) {

        $transient_name = ( ! empty( $transient_name ) ) ? $transient_name : $this->default_transient_name;

        return $transient_name . $to_append;
    }

    /**
     * Function checks for the existence of a specific cached object.
     *
     * @since    2.0.0
     *
     * @param $transient_name   string for a cached object with this name
     * @return bool|mixed       returns 'false' if no object, or a json decoded array if found
     */
    public function if_stored_in_wordpress_transient_cache( $transient_name ) {

        $this->turn_off_object_cache_so_our_bloody_plugin_works();

        //set to false to disable caching
        $clubs_or_false = get_site_transient( $transient_name );

        $this->turn_object_caching_back_on_for_the_next_poor_sod();

        return ( false === $clubs_or_false ) ? false : json_decode( $clubs_or_false, true );
    }

    /**
     * Uses the WordPress HTTP API to call our AmAzE-O clubs api *wink*
     *
     * @since    2.0.0
     *
     * @return array            at this point, return our clubs as a JSON file
     */
    public function call_clubs_api() {

        //simple
        $api_url = 'http://testwestern.com/api/clubs/json.php';

        $returned_string = wp_remote_retrieve_body( wp_remote_get( $this->add_http_if_not_exists($api_url)) );

        if( empty( $returned_string ) ) {

            return new \WP_Error( 'api_error', __( 'Spot of trouble connecting to the clubs API', 'usc-clubs' ) );
        }

        return json_decode( $returned_string, true );
    }

    /**
     * Simple utility function. Add an "http://" to URLs without it.
     * Recognizes ftp://, ftps://, http:// and https:// in a case insensitive way.
     *
     * http://stackoverflow.com/questions/2762061/how-to-add-http-if-its-not-exists-in-the-url
     * @author Alix Axel
     *
     * @since    2.0.0
     *
     * @param $url      a url with or without an http:// prefix
     * @return string   a url with the http:// prefix, or whatever it had originally
     */
    public function add_http_if_not_exists($url) {

        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }
        return $url;
    }


    /**
     * Multisort function sorts two-dimensional arrays on specific keys.
     * Ripped off the PHP reference page from one of the comments.
     * http://www.php.net/manual/en/function.array-multisort.php#114076
     *
     * @author Robert C
     * C probably short for "Champ"
     *
     * @param $data                 the array to be sorted
     * @param $sortCriteria         array of selected keys and how to sort them
     * @param bool $caseInSensitive whether or not to sort stings by case
     *
     * @since    2.0.0
     *
     * @return bool|mixed           returns your array sorted by whatever the eff you asked for
     */
    public function multisort($data, $sortCriteria, $caseInSensitive = true)
    {
        if( !is_array($data) || !is_array($sortCriteria))
            return false;
        $args = array();
        $i = 0;
        foreach($sortCriteria as $sortColumn => $sortAttributes)
        {
            $colList = array();
            foreach ($data as $key => $row)
            {
                $convertToLower = $caseInSensitive && (in_array(SORT_STRING, $sortAttributes) || in_array(SORT_REGULAR, $sortAttributes));
                $rowData = $convertToLower ? strtolower($row[$sortColumn]) : $row[$sortColumn];
                $colLists[$sortColumn][$key] = $rowData;
            }
            $args[] = &$colLists[$sortColumn];

            foreach($sortAttributes as $sortAttribute)
            {
                $tmp[$i] = $sortAttribute;
                $args[] = &$tmp[$i];
                $i++;
            }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return end($args);
    }
}