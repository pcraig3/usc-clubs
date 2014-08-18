jQuery(function ($) {
    /* You can safely use $ in this code block to reference jQuery */

    var fJS;


    var AjaxUSCClubs = {

        /** Remove the clubs listings created by my clubs list template for the job listings returned by filterJS
         *
         * @since    2.0.0
         */
        remove_wordpress_clubs_for_filterjs_clubs: function() {

            var $clubs_column = $('.usc_clubs--count').parents('.et_pb_text');
            var $to_detach = $clubs_column.find('.filterjs__list__wrapper');

            //http://bugs.jquery.com/ticket/13400
            //old.replaceWith( new ); //can be changed to:
            //old.before( new ).detach();
            $articles = $clubs_column.find('article');
            $articles.first().before( $to_detach );
            $articles.remove();
        },

        /** Remove the widgets created by Wordpress (if they exist, which they don't)
         *  and sub in the filter checkboxes and searchbar created by filterJS
         *
         * @since    2.0.0
         */
        remove_wordpress_widgets_for_filterjs_imposter_widgets : function() {

            /* TODO: This is bad practice, adding the class like this. */
            var $widgets_column = $('.page-id-285 .et_pb_widget_area').addClass('btn-menu');
            var $filterjs       = $('.filterjs.hidden');

            //old.before( new ).detach();

            $widgets_column.find('.et_pb_widget').each(function( index ) {

                var found = (  $( this ).find( '[class*=categoryNames]' ).length > 0 );

                if( found )
                    $( this ).replaceWith( $filterjs.find( '#nav_menu-categoryNames-1000' ) );

                if( !found )
                    $( this ).remove();

            });

            //now, put in the search bar.
            $widgets_column.prepend( $filterjs.find('#nav_menu-search-1000').detach() );

            //if there are more asides in the filter column, add them.
            if( $filterjs.find('aside').length > 0 ) {

                $filterjs.find('aside').each(function( index ) {

                    $widgets_column.append( $(this) );

                });
            }

            //keyup event listener updates 'x clubs' string
            $widgets_column.find('#search_box').on('keyup', function() {

                AjaxUSCClubs.typewatch(function () {
                    AjaxUSCClubs.update_visible_clubs();
                }, 50);

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
            $widgets_column.find('#categoryNames').delegate('label', 'click', function() {

                if($( this ).find( 'input:checkbox' ).is( ':checked' ))
                    $( this ).addClass('checked');
                else
                    $( this ).removeClass('checked');

                AjaxUSCClubs.update_visible_clubs();
            });
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
         * * Hide the loading gif
         * * Check all checkboxes (otherwise results would be hidden)
         * * filterInit builds the page
         * * change -- not sure what this does.  Maybe nothing.  @TODO: Whoops
         * * Update the 'x clubs'
         *
         * @since    2.0.0
         */
        clubs_gotten: function( clubs ) {

            $('.filterjs__loading').addClass('hidden');

            $('#categoryNames').find('label').addClass('checked').find('input:checkbox').prop('checked', true);

            fJS = filterInit( clubs );

            $('#usc_clubs_list').trigger( "change" );

            //AjaxUSCClubs.update_visible_clubs();

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
         * @since    2.0.0
         */
        update_visible_clubs: function() {

            var $clubs_column = $('.usc_clubs--count').parents('.et_pb_text');

            $clubs_column.find('#counter').text( $clubs_column.find('article:visible').length );
        },

        /**
         * @since    2.0.0
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
         *
         * @since    2.0.0
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

                    /* if(! data['success']) {
                     //console.log('WordPress transient DB has NOT been updated.');
                     }
                     else
                     //console.log('Yay! WordPress transient DB has been updated.');
                     */

                }, "json");

        }
    };

    /**
     * Function sets up all of our filtering.
     * Works now, but seems a bit brittle.
     *
     * @param clubs    a list of clubs. Data is pulled from the WesternLink data saved as a JSON file on github
     *
     * @since    2.0.0
     *
     * @returns {*} A list of searchable clubs in the backend.
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
                html_string +=      '<a title="Find more clubs with a focus on ' + categories[i].categoryName + '!" '
                    + 'href="#">' + categories[i].categoryName + '</a>, ';
            }

            //cut off the last comma and space
            html_string = html_string.slice(0, (html_string.length - 2));
            html_string +=          '</p><!-- end of .post-meta -->';


            html_string += '</div><!-- .end of .bd --></article><!-- end of .usc_club -->';
            return html_string;

        }

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

    $(document).ready(function() {

        var usc_clubs_as_json = JSON.parse(options.clubs);

        console.log(usc_clubs_as_json[0]);
        AjaxUSCClubs.clubs_gotten( usc_clubs_as_json );

        //unlike the jobs plugin, we're updating the cache only if it wasn't cached before.
        //because I don't really expect the list to change ever.
        if( ! options.if_cached )
            AjaxUSCClubs.ajax_update_wordpress_transient_cache( options );


    });

    //call this right away.  don't wait for $(document).ready
    //this one removes my old clubs and puts in my new clubs.
    AjaxUSCClubs.remove_wordpress_clubs_for_filterjs_clubs();
    AjaxUSCClubs.create_category_checkbox_filters( JSON.parse(options.categories) );


    //this one removes the widgets and puts in my widgets
    AjaxUSCClubs.remove_wordpress_widgets_for_filterjs_imposter_widgets();

});
