/**
 * public-filter.js contains the AjaxUSCClubs module which defines the HTML structure for a club template
 * (as a plain string, which isn't really ideal but it's easy) in the club archive, as well as does a whole bunch of other stuff.
 *
 * Basically, if you don't have Javascript, you'll get a static club listing, but if you have it (which almost everyone does)
 * then the original clubs and widgets are stripped from the page and new, better, dynamic ones are implemented.
 *
 * There's a bit of a loading delay, but it can't be helped.
 *
 * **THIS FILE RELIES VERY HEAVILY ON A TEMPLATE FILE THAT HAS ALL OF THE FILTERJS INITIAL MARKUP**
 * PLEASE TAKE A LOOK AT /public/views/usc_clubs-list.php TO UNDERSTAND WHERE THE FILTERJS FILTERS AND EVENTS ARE ORIGINALLY
 * LOCATED
 */

jQuery(function ($) {
    /* You can safely use $ in this code block to reference jQuery */

    //fJS variable used by filterJS to create the searchable/filterable clubs listing.
    var fJS;

    var AjaxUSCClubs = {

        //variables we can use within our module so that we're not re-doing jquery selectors every time
        $clubs_column:      $('.usc_clubs--count').parents('.et_pb_text'),
        /* Note: This is bad practice, adding the class like this, but I don't know a cleaner way */
        $widgets_column:    $('.page-id-285 .et_pb_widget_area').addClass('btn-menu'),
        $filterjs:          $('.filterjs.hidden'),

        /* This function was meant to hide everything on the screen if the user has javascript in order to
         * avoid that really obvious reloading thing that happens on the clubs page (especially on your phone)
         *
         * Didn't work at all, because it takes effect way too late.
         */
        hide_everything_thats_not_a_horse: function() {

            //find everything and set visibility hidden on them.
            var $articles = AjaxUSCClubs.$clubs_column.find('article');
            var $widgets =  AjaxUSCClubs.$widgets_column.find('.et_pb_widget');

            $articles.add($widgets).addClass('invisible');

            //find container
            var $container_row = $articles.parents('.et_pb_row');
            $container_row.prepend( AjaxUSCClubs.$filterjs.find('.filterjs__loading').detach() );
        },

        /**
         * Remove the clubs listings originally on the page before the JS is loaded and replace them instead with the
         * filterJS events that we can manipulate.
         *
         * @since    2.0.0
         */
        remove_wordpress_clubs_for_filterjs_clubs: function() {

            /*var $clubs_column = $('.usc_clubs--count').parents('.et_pb_text');*/
            var $to_detach = AjaxUSCClubs.$clubs_column.find('.filterjs__list__wrapper');

            //http://bugs.jquery.com/ticket/13400
            //old.replaceWith( new ); //can be changed to:
            //old.before( new ).detach();
            var $articles = AjaxUSCClubs.$clubs_column.find('article');
            $articles.first().before( $to_detach );
            $articles.remove();
        },

        /** Remove the widgets created by Wordpress and sub in the filter checkboxes and searchbar created by filterJS
         *  1. Initially looks for widgets similar to the search filters we have so that it changes the order of them
         *  if they're changed (not too important).
         *  2. Search bar goes on top.
         *  3. Add event listener to search bar to update clubs counter slightly after 'keyup' event
         *  4. Add collapseomatic class to event filter widgets.  Hacky solution for mobile filters.  Works though.
         *  5. Add window.resize event that makes sure filters are always revealed on a large screen and hidden on a small one.
         *  6. Sets up event listener for 'All' category checkbox.
         *  7. Sets up event listener for single category checkboxes.
         *  8. If window is on a larger screen, show filters (they are hidden by default)
         *
         * @since    2.1.2
         */
        remove_wordpress_widgets_for_filterjs_imposter_widgets : function() {

            AjaxUSCClubs.$widgets_column.find('.et_pb_widget').each(function( index ) {

                var found = (  $( this ).find( '[class*=categoryNames]' ).length > 0 );

                if( found )
                    $( this ).replaceWith( AjaxUSCClubs.$filterjs.find( '#nav_menu-categoryNames-1000' ) );

                if( !found )
                    $( this ).remove();

            });

            //now, put in the search bar.
            AjaxUSCClubs.$widgets_column.prepend( AjaxUSCClubs.$filterjs.find('#nav_menu-search-1000').detach() );

            //if there are more asides in the filter column, add them.
            if( AjaxUSCClubs.$filterjs.find('aside').length > 0 ) {

                AjaxUSCClubs.$filterjs.find('aside').each(function( index ) {

                    AjaxUSCClubs.$widgets_column.append( $(this) );

                });
            }

            //keyup event listener updates 'x clubs' string
            AjaxUSCClubs.$widgets_column.find('#search_box').on('keyup', function() {

                AjaxUSCClubs.typewatch(function () {
                    AjaxUSCClubs.update_visible_clubs();
                }, 50);

            });

            AjaxUSCClubs.$widgets_column.before('<h3 id="id1234" class="collapseomatic">Filter Club List</h3>');
            AjaxUSCClubs.$widgets_column.prop('id', "target-id1234" ).addClass('collapseomatic_content');

            $(window).resize(function () {
                var $collapseomatic_button = $('.collapseomatic');
                var $collapseomatic_content = $collapseomatic_button.next();

                if( $(window).width() > 980 && ! $collapseomatic_content.is(':visible') )
                    $collapseomatic_content.show();
            });

            //click event listener on '#all' checkbox turns on and off the entire row.
            $('#categoryNames_all').on('click',function(){
                $(this).closest('ul').children().find(':checkbox').prop('checked', $(this).is(':checked'));

                if($(this).is(':checked'))
                    $(this).closest('ul').children().find('label').addClass('checked');
                else
                    $(this).closest('ul').children().find('label').removeClass('checked');
            });

            //click event listener on normal checkbox items adds/removes the 'clicked' class (for CSS)
            AjaxUSCClubs.$widgets_column.find('#categoryNames').delegate('label', 'click', function() {

                if($( this ).find( 'input:checkbox' ).is( ':checked' ))
                    $( this ).addClass('checked');
                else
                    $( this ).removeClass('checked');

                AjaxUSCClubs.update_visible_clubs();
            });

            AjaxUSCClubs.typewatch(function () {
                if( $(window).width() > 980 && ! AjaxUSCClubs.$widgets_column.is(':visible') )
                    AjaxUSCClubs.$widgets_column.show();
            }, 50);
        },

        /** Wait a bit after a jquery event to do your action, basically.
         *  Found this on stackoverflow, written by this CMS guy
         *
         *  @see http://stackoverflow.com/questions/2219924/idiomatic-jquery-delayed-event-only-after-a-short-pause-in-typing-e-g-timew
         *  @author CMS
         *
         * @since    2.0.0
         */
        typewatch: (function(){
            var timer = 0;
            return function(callback, ms){
                clearTimeout (timer);
                timer = setTimeout(callback, ms);
            }
        })(),

        /**
         * Run through a bunch of setup stuff once the clubs (as a JSON string) has been received from our PHP API call
         * 1. Hide the loading gif
         * 2. Check all checkboxes (otherwise results would be hidden)
         * 3. filterInit builds the page
         * 4. Update the 'x clubs' counter
         *
         * @since    2.0.0
         */
        clubs_gotten: function( clubs ) {

            $('.filterjs__loading').addClass('hidden');

            $('#categoryNames').find('label').addClass('checked').find('input:checkbox').prop('checked', true);

            fJS = filterInit( clubs );

            AjaxUSCClubs.update_visible_clubs();

        },

        /**
         *  Yay, date formatting with JS.  -.-
         *
         *  Basically, use the string (a standardized one we can hardcode) to create a date, and then format it so that
         *  it looks like WordPress' default dates and not something dumb like "2014-08-10"
         *
         * @since    2.0.0
         */
        date_format: function( date_string ) {

            var days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];

            //returned date strings look like this
            // "2014-08-22 23:59"
            //  0123456789012345

            //making a date new Date(year, month, day, hours, minutes, seconds, milliseconds);
            var d = new Date(date_string.slice(0,4), (date_string.slice(5,7) - 1), date_string.slice(8,10), date_string.slice(11,13), 0, 0);

            //Friday, August 22
            //return  days[ d.getDay() ] + ', ' + months[ d.getMonth() ] + " " + d.getDate();

            //August 22, 2014
            return  months[ d.getMonth() ] + " " + d.getDate() + ", " + d.getFullYear();
        },

        /**
         *  Simple.  Find how many clubs are visible and change the number in the 'X Clubs' string.
         *
         *  Updated to say '1 Club' but '2 Clubs'  *mad applause*
         *
         * @since    2.2.1
         */
        update_visible_clubs: function() {

            var $clubs_column = $('.usc_clubs--count').parents('.et_pb_text');

            var num_clubs = $clubs_column.find('article:visible').length;

            num_clubs = ( num_clubs === 0 ) ? 'No Clubs' : ( num_clubs === 1 ) ? '1 Club' : num_clubs + ' Clubs' ;

            $clubs_column.find('#counter').text( num_clubs );
        },

        /**
         * Pretty self-explanatory method name, I hope.
         *
         * @since    2.0.0
         *
         * @param categories    array with club categories. go figure.
         */
        create_category_checkbox_filters: function( categories ) {

            var html_string = '<li><label><input id="categoryNames_all" value="all" type="checkbox">All</label></li>';

            var total = categories.length;
            for (var i = 0; i < total; i++) {
                html_string += '<li><label><input id="' + categories[i].categoryId + '" value="' + categories[i].categoryId
                    + '" type="checkbox">' + categories[i].categoryName + '</label></li>';
            }

            $('#categoryNames').append(html_string);

        },

        /**
         * This method, called after non-cached clubs have been returned, calls a php method which updates the cache.
         * Basically, clubs saved as WordPress transients are returned more quickly and so we want to cache them.
         * Not much fancy footwork going on here.  Pretty standard stuff.
         *
         * @since    2.0.0
         *
         * @param options   an array of options we need to make the ajax call
         */
        ajax_update_wordpress_transient_cache: function( options ) {

            var jqxhr = jQuery.post(
                options.ajax_url,
                {
                    action:         "update_wordpress_clubs_cache",
                    attr_id:        "usc_clubs_list",
                    nonce:          jQuery("#usc_clubs_list").data("nonce"),
                    transient_name: options.transient_name
                },
                function( data ) {

                    /*
                     if(! data['success']) {
                     console.log('WordPress transient DB has NOT been updated.');
                     }
                     else
                     console.log('Yay! WordPress transient DB has been updated.');
                     */

                }, "json");
        }
    };


    /**
     * function pretty much a straightforward copy of the samples given in the filter.js github page
     * 'view' defines the HTML club list item template, and 'settings' defines the filtering options, which for the
     * clubs widget includes a search bar and checkboxes representing categories.
     *
     * @see: https://github.com/jiren/filter.js/tree/master
     *
     * @since    2.0.0
     *
     * @param clubs an array of clubs
     * @returns {*} a list of clubs
     */
    function filterInit( clubs ) {

        var view = function( club ) {

            var html_string = '';

            //at this point we have ONE CLUB.  This sets up the loop.

            var img_url = "";

            if( club.profileImageUrl )
                img_url = 'http://' + club.profileImageUrl;


            html_string +=  '<article class="usc_clubs type-usc_clubs et_pb_post media">';

            if( img_url ) {
                html_string += '<a href="' + club.url + '" class="img">';

                html_string +=     '<img src="' + img_url + '" alt="Logo for ' + club.name + '" />';

                html_string += '</a>';
            }

            html_string +=      '<div class="bd">'
                + '<a href="' + club.url + '" title="' + club.name + '"><h2>' + club.name;

            if('' != club.shortName )
                html_string += ' (' + club.shortName + ')';

            html_string +=          '</h2></a>'
            html_string +=          '<p class="post-meta">';

            var categories = club.categories;
            var total = categories.length;
            for (var i = 0; i < total; i++) {
                html_string +=      '<a>' + categories[i].categoryName + '</a>, ';
            }

            //cut off the last comma and space
            html_string = html_string.slice(0, (html_string.length - 2));
            html_string +=          '</p><!-- end of .post-meta -->';


            html_string += '</div><!-- .end of .bd --></article><!-- end of .usc_club -->';
            return html_string;

        };

        var settings = {
            filter_criteria: {
                categories: ['#categoryNames input:checkbox', 'categories.ARRAY.categoryId']
            },
            search: {input: '#search_box' },
            and_filter_on: true,
            id_field: 'id' //Default is id. This is only for usecase
        };

        return FilterJS(clubs, "#usc_clubs_list", view, settings);
    }

    /**
     * WAITS FOR PAGE TO BE MOSTLY LOADED BEFORE FIRING
     *
     * Parse the clubs returned from the PHP file as a JSON string, call the method that
     * updates the page with filterjs clubs, and if the clubs weren't cached then cache them.
     */
    $(document).ready(function() {

        var usc_clubs_as_json = JSON.parse(options.clubs);
        
        //console.log(usc_clubs_as_json[0]);
        AjaxUSCClubs.clubs_gotten( usc_clubs_as_json );

        //unlike the jobs plugin, we're updating the cache only if it wasn't cached before.
        //because I don't really expect the list to change ever.
        if( ! options.if_cached )
            AjaxUSCClubs.ajax_update_wordpress_transient_cache( options );

    });

    //hide elements we want gone and put that loading horse in instead
    //this method turned out useless
    //AjaxUSCClubs.hide_everything_thats_not_a_horse();

    //call this right away.  don't wait for $(document).ready
    //this one removes my old clubs and puts in my new clubs.
    AjaxUSCClubs.remove_wordpress_clubs_for_filterjs_clubs();
    AjaxUSCClubs.create_category_checkbox_filters( JSON.parse(options.categories) );


    //this one removes the widgets and puts in my widgets
    AjaxUSCClubs.remove_wordpress_widgets_for_filterjs_imposter_widgets();

});
