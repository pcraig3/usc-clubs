jQuery(function ($) {
    /* You can safely use $ in this code block to reference jQuery */

    var fJS;


    var AjaxUSCJobs = {

        /** Remove the job listings created by wordpress for the job listings returned by filterJS
         *
         * @since  8.0.0
         */
        remove_wordpress_jobs_for_filterjs_jobs: function() {

            var $jobs_column = $('.post-type-archive-usc_jobs .et_pb_text, .tax-departments .et_pb_text');
            var $to_detach = $jobs_column.find('.filterjs__list__wrapper');

            //http://bugs.jquery.com/ticket/13400
            //old.replaceWith( new ); //can be changed to:
            //old.before( new ).detach();
            $articles = $jobs_column.find('article');

            $articles.first().before( $to_detach );
            $articles.remove();
        },

        /** Remove the widgets created by Wordpress and sub in the filter checkboxes and searchbar created by filterJS
         *
         * @since  8.0.2
         */
        remove_wordpress_widgets_for_filterjs_imposter_widgets : function() {

            var $widgets_column = $('.post-type-archive-usc_jobs .et_pb_widget_area, .tax-departments .et_pb_widget_area');
            var $filterjs       = $('.filterjs.hidden');

            //old.before( new ).detach();

            $widgets_column.find('aside').each(function( index ) {

                var found = (  $( this ).find( '[class*=remuneration]' ).length > 0 );

                if( found )
                    $( this ).replaceWith( $filterjs.find( '#nav_menu-remuneration-1000' ) );

                else {

                    found = (  $( this ).find( '[class*=departments]' ).length > 0 );

                 if( found )
                     $( this ).replaceWith( $filterjs.find( '#nav_menu-departments-1000' ) );
                 }

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

            //keyup event listener updates 'x jobs available' string
            $widgets_column.find('#search_box').on('keyup', function() {

                AjaxUSCJobs.typewatch(function () {
                    AjaxUSCJobs.update_visible_jobs()
                }, 50);

            });

            //click event listener on '#all' checkbox turns on and off the entire row.
            $('#departments_all').on('click',function(){
                $(this).closest('ul').children().find(':checkbox').prop('checked', $(this).is(':checked'));

                if($(this).is(':checked'))
                    $(this).closest('ul').children().find('label').addClass('checked');
                else
                    $(this).closest('ul').children().find('label').removeClass('checked');
            });

            //click event listener on normal checkbox items adds/removes the 'clicked' class (for CSS)
            $widgets_column.find('#taxonomy_departments, #remuneration').delegate('label', 'click', function() {

                if($( this ).find( 'input:checkbox' ).is( ':checked' ))
                    $( this ).addClass('checked');
                else
                    $( this ).removeClass('checked');

                AjaxUSCJobs.update_visible_jobs();
            });
        },

        /** Wait a bit after a jquery event to do your action, basically.
         *  Found this on stackoverflow, written by this CMS guy
         *
         *  @see http://stackoverflow.com/questions/2219924/idiomatic-jquery-delayed-event-only-after-a-short-pause-in-typing-e-g-timew
         *  @author CMS
         *
         * @since  8.0.0
         */
        typewatch: (function(){
            var timer = 0;
            return function(callback, ms){
                clearTimeout (timer);
                timer = setTimeout(callback, ms);
            }
        })(),

        /**
         * Run through a bunch of setup stuff once the jobs (as a JSON string) has been received from our PHP API call
         * * Hide the loading gif
         * * Check all checkboxes (otherwise results would be hidden)
         * * filterInit builds the page
         * * change -- not sure what this does.  Maybe nothing.  @TODO: Whoops
         * * Update the 'x Jobs Available string
         *
         * @since  8.0.1
         */
        jobs_gotten: function( jobs ) {

            $('.filterjs__loading').addClass('hidden');

            $('#remuneration label.checked, #taxonomy_departments label.checked').find('input:checkbox').prop('checked', true);

            fJS = filterInit( jobs );

            $('#usc_jobs_list').trigger( "change" );

            AjaxUSCJobs.update_visible_jobs();

        },

        /**
         *  Yay, date formatting with JS.  -.-
         *
         *  Basically, use the string (a standardized one we can hardcode) to create a date, and then format it so that
         *  it looks like WordPress' default dates and not something dumb like "2014-08-10"
         *
         * @since  8.0.0
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
         *  Simple.  Find how many jobs are visible and change the number in the 'X Jobs Available string.
         *
         * @since  8.0.0
         */
        update_visible_jobs: function() {

            var $jobs_column = $('.et_pb_text');

            $jobs_column.find('#counter').text( $jobs_column.find('article:visible').length );
        }

    };

    /**
     * Function sets up all of our filtering.
     * Works now, but seems a bit brittle.
     *
     * @param jobs    a list of jobs. Data is pulled from the USC Jobs Custom Post Type in our database.
     *
     * @since   0.6.0
     *
     * @returns {*} A list of searchable jobs in the backend.
     */
    function filterInit( jobs ) {

        var view = function( job ) {

            var html_string = '';

            //at this point we have ONE JOB.  This sets up the loop.

            html_string +=  '<article id="post-' + job.wp_id + '" class="post-' + job.wp_id + ' ' + job.type + ' type-' + job.type + ' status-publish hentry et_pb_post">';

            html_string +=          '<a href="' + job.url + '" title="' + job.title + '"><h2>' + job.title + '</h2></a>';

            html_string +=          '<p class="post-meta">';

            var departments = job.taxonomy_departments;
            var total = departments.length;
            for (var i = 0; i < total; i++) {

                html_string +=       '<a title="Find more ' + departments[i].title + ' jobs!" '
                    + 'href="http://westernusc.org/departments/' + departments[i].slug + '/">' + departments[i].title + '</a>, ';
            }

            //cut off the last comma and space
            html_string =           html_string.slice(0, (html_string.length - 2));

            html_string +=          '</p>';

            html_string +=          '<p>';
            html_string +=              '<span class="subheading">Remuneration</span> ' +  job.custom_fields.remuneration.charAt(0).toUpperCase() + job.custom_fields.remuneration.slice(1);
            html_string +=          '</p>';
            html_string +=          '<p>';
            html_string +=              '<span class="subheading">Apply By Date</span> ' +  AjaxUSCJobs.date_format(job.custom_fields.apply_by_date);
            html_string +=          '</p>';

            html_string +=  '</article>';

            return html_string;
        }

        var settings = {
            filter_criteria: {
                remuneration: ['#remuneration input:checkbox', 'custom_fields.remuneration'],
                taxonomy_departments: ['#taxonomy_departments input:checkbox', 'taxonomy_departments.ARRAY.slug']
            },
            search: {input: '#search_box' },
            and_filter_on: true,
            id_field: 'id' //Default is id. This is only for usecase
        };

        return FilterJS(jobs, "#usc_jobs_list", view, settings);
    }

    $(document).ready(function() {

        var usc_jobs_as_json = JSON.parse(options.jobs);

        //console.log( usc_jobs_as_json[0] );

        AjaxUSCJobs.jobs_gotten( usc_jobs_as_json );

    });

    //call this right away.  don't wait for $(document).ready
    //this one removes the jobs and puts in my new jobs.
    AjaxUSCJobs.remove_wordpress_jobs_for_filterjs_jobs();

    //this one removes the widgets and puts in my widgets
    AjaxUSCJobs.remove_wordpress_widgets_for_filterjs_imposter_widgets();


});
