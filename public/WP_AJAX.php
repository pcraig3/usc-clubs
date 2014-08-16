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

    private function __construct() {


        add_action("wp_ajax_get_clubs_ajax", array( $this, "get_clubs_ajax" ) );
        add_action("wp_ajax_nopriv_get_clubs_ajax", array( $this, "get_clubs_ajax") );

        add_action("wp_ajax_update_wordpress_clubs_cache", array( $this, "update_wordpress_clubs_cache" ) );
        add_action("wp_ajax_nopriv_update_wordpress_clubs_cache", array( $this, "update_wordpress_clubs_cache") );

        $this->expiration = DAY_IN_SECONDS;
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
     * Does (a bit more than) what it says on the box. gets all facebook and db events (and then merges their values)
     * and then returns everything to the javascript function waiting for it.
     *
     * @since    2.0.0
     */
    public function get_clubs_ajax() {

        $this->make_sure_the_nonce_checks_out( $_POST['attr_id'], $_POST['nonce'] );

        //set variables if any

        $clubs_array = $this->get_clubs();


        //set result/response
        //$result['if_cached'] = $events_stored_in_cache;
        //$result['success'] = ( false !== $response ) ? true : false;
        echo json_encode($clubs_array);
        die();
    }

    /**
     * This method implements our WordPress caching system.  Basically, instead of calling (@see)  all the time,
     *
     * @since    2.0.0
     *
     */
    public function update_wordpress_clubs_cache() {

        $this->make_sure_the_nonce_checks_out( $_POST['attr_id'], $_POST['nonce'] );

        $transient_name = ( isset($_POST['transient_name'] ) && !empty($_POST['transient_name']) )
            ? $_POST['transient_name'] : 'usc_clubs_get_clubs';

        $json_decoded_events_array = $this->call_clubs_api();
        $json_decoded_events_array = $this->filter_js_format_API_response($json_decoded_events_array['items']);

        $expiration = $this->expiration;
        $expiration = 300;

        $deleted = delete_site_transient( $transient_name );

        //returns true or false
        $result['success'] = set_site_transient( $transient_name, json_encode($json_decoded_events_array), $expiration );
        $result['deleted'] = $deleted;

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
     *
     * @since    2.0.0
     *
     * @param null $json_response
     * @param array $fields_to_keep
     * @return array|null
     */
    private function filter_js_format_API_response( $json_response = null,
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
                                                        'primaryContactName',
                                                        'primaryContactCampusEmail',
                                                        'categories'
                                                    )
    ) {

        $json_response_modified = null;
        if ( null == ( $json_response ) ) {

            return new WP_Error( 'api_error', __( 'Sorry, but we\'ve got nothing back from the API.  See if paul_craig_16@hotmail.com can do anything about it.', 'usc-clubs' ) );

        } elseif ( ! empty( $fields_to_keep ) ) {
            /** Just so we're all on the same page, here's the full list we can get from a club.
             * @TODO: This might mean problems paging in the future. I guess we'll see.
             * maybe check if json_response['count'] == json_response['count_total']
             *
             * Anyway, here's the simplified version.
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
                    $temp_club['url'] = trailingslashit(get_bloginfo('wpurl'))
                                . trailingslashit("clubs/list/" . $club['organizationId']);

                    foreach( $fields_to_keep as &$field ) {
                        $temp_club[$field] = $club[$field];
                    }
                    unset($field);

                    $temp_club['alphabet'] = strtolower(substr(trim($temp_club['name']), 0, 1));


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
     * @since    2.0.0
     */
    public function get_clubs() {

        $transient_name = $this->generate_transient_name( 'usc_clubs_get_clubs' );

        $events_stored_in_cache = $this->if_stored_in_wordpress_transient_cache( $transient_name );

        if( false === $events_stored_in_cache ) {
            $clubs_array = $this->call_clubs_api();
            $clubs_array = $this->filter_js_format_API_response($clubs_array['items']);

            $response['response']  = $clubs_array;
            $response['is_cached'] = false;
        } else {
            //events stored in cache are already formatted
            $response['response'] = $events_stored_in_cache;
            $response['is_cached'] = true;
        }

        return $response;
    }

    /**
     * In order to cache a result properly, we need to make sure that only that particular API call can access it.
     * In the case of clubs, this is a moot point.  We can call it whatever we want.
     * We're always calling the same endpoint and getting everything.
     *
     * @since    2.0.0
     *
     * @param   $transient_name  return the unmodified string
     * @return  string|WP_Error  a shiny new name for our transient
     */
    public function generate_transient_name( $transient_name ) {

        return $transient_name;
    }

    /**
     * Function checks for the existence of a specific cached object.
     *
     * @since    2.0.0
     *
     * @param $transient_name   looks for a cached object with this name
     * @return bool|mixed       returns 'false' if no object, or a json decoded array if found
     */
    public function if_stored_in_wordpress_transient_cache( $transient_name ) {

        $clubs_or_false = get_site_transient( $transient_name );

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
        $api_url = 'http://testwestern.com/github/json.php';

        $returned_string = wp_remote_retrieve_body( wp_remote_get( $this->add_http_if_not_exists($api_url)) );

        if( empty( $returned_string ) ) {

            return new WP_Error( 'api_error', __( 'Spot of trouble connecting to the clubs API', 'usc-clubs' ) );
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
    private function add_http_if_not_exists($url) {

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