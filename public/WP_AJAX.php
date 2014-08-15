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
     * @since    0.9.2
     *
     * @var      object
     */
    protected static $instance  = null;

    private function __construct() {


        add_action("wp_ajax_get_clubs_ajax", array( $this, "get_clubs_ajax" ) );
        add_action("wp_ajax_nopriv_get_clubs_ajax", array( $this, "get_clubs_ajax") );

        add_action("wp_ajax_update_wordpress_transient_cache", array( $this, "update_wordpress_transient_cache" ) );
        add_action("wp_ajax_nopriv_update_wordpress_transient_cache", array( $this, "update_wordpress_transient_cache") );

    }

    /**
     * Return an instance of this class.
     *
     * @since     0.9.2
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
     * @since 0.9.8
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
     * @since    0.9.8
     *
     */
    public function update_wordpress_transient_cache() {

        $this->make_sure_the_nonce_checks_out( $_POST['attr_id'], $_POST['nonce'] );

        die();
    }

    /**
     * Bare-bones method that rejects non-logged-in users.  Used for all ajax methods.
     *
     * @since   0.4.0
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
     * @since    0.9.7
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
     * @since 0.6.0
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

    public function get_clubs() {

        $transient_name = $this->generate_transient_name( "usc_clubs_get_clubs" );

        $events_stored_in_cache = $this->if_stored_in_wordpress_transient_cache( $transient_name );

        if( false === $events_stored_in_cache ) {
            $clubs_array = $this->call_clubs_api();
            $clubs_array = $this->filter_js_format_API_response($clubs_array['items']);

            $response['response']  = $clubs_array;
            $response['is_cached'] = false;
        } else {
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
     * @since       0.9.8
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
     * @since    0.9.7
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
     * @since    0.0.0
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
     * @since    0.9.7
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

}