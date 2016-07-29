var custom_post_types_loaded = false;
var import_started = false;

jQuery(document).ready(function () {

    if (null === sessionStorage.getItem('clicked')) {
        sessionStorage.setItem('clicked', false);
    }

    import_started = sessionStorage.getItem('clicked');
    if( '1' === import_started ) {
        var intervalID = setInterval(function() {
            get_import_status();
        }, 3000);
        setTimeout( function(){ clearInterval(intervalID); jQuery('#error_log').show(); }, 18000 );
    }

    jQuery('#main-select-all-1').on("click", function () {
        var checkBoxes = jQuery("input[name='content[]']");
        checkBoxes.prop("checked", jQuery(this).prop("checked"));
    });

    jQuery('#custom-select-all-1').on("click", function () {
        var checkBoxes = jQuery("input[name='custom-post-types[]']");
        checkBoxes.prop("checked", jQuery(this).prop("checked"));
    });

    jQuery("input[name='custom-content[]']").on("click", function () {
        if( "post-types" === this.value ) {
            jQuery("#custom-post-types-container").show();
            if( ! custom_post_types_loaded ) {
                get_custom_post_types();
            }
        } else {
            jQuery("#custom-post-types-container").hide();
        }
    });

    jQuery('input[type="submit"][name="submit"]').click( function() {
        sessionStorage.setItem('clicked', 1);
    });

    function get_custom_post_types() {

        if (typeof pmc_unit_test_ajax === 'undefined') {
            return;
        }

        jQuery('.spin-loader').show();

        jQuery.ajax({
            type: "post",
            url: pmc_unit_test_ajax.admin_url,
            data: {
                action: "get_custom_post_types",
                post_types_nOnce: pmc_unit_test_ajax.post_types_nOnce
            },
            timeout: 6000,
            success: function (data, textStatus, jqXHR) {
                for( var key in data ) {
                    var template = jQuery('.custom-template')[0].outerHTML;
                    var post_type = jQuery(template).appendTo('#the-list-post-types');
                    jQuery(post_type).find('th').text(key);
                    jQuery(post_type).find('input').prop('name', 'custom-post-types[]');
                    jQuery(post_type).find('input').prop('value', key);
                    jQuery(post_type).find('div').prop('id', 'progressbar-'+key);
                    jQuery(post_type).removeClass('custom-template');
                    jQuery(post_type).attr('id', key);
                }
                jQuery('.spin-loader').hide();
                custom_post_types_loaded = true;
            },
            error: function (x, t, m) {
                if (t === "timeout") {
                    alert("Got timeout for loading custom post types");
                } else {
                    alert(t + " : " + m);
                }
            }
        });
    }

    function get_import_status() {
        if( '1' === import_started ) {

            jQuery.ajax({
                type: 'post',
                url: pmc_unit_test_ajax.admin_url,
                data: {
                    action: 'import_report',
                    import_nOnce: pmc_unit_test_ajax.import_nOnce
                },
                timeout: 10000,
                success: function( response, textStatus, jqXHR ) {

                    var csv_files = response.files;
                    var file_names = Object.keys(csv_files);
                    for ( var i = 0; i < file_names.length; i++ ) {
                        var filename = file_names[i];
                        var div_id = filename.substring(filename.lastIndexOf("-") + 1, filename.lastIndexOf("."));
                        if (true === response.success) {
                            var a = document.getElementById(filename);
                            if (null === a) {
                                a = document.createElement('a');
                                a.id = filename;
                            }
                            a.download = filename;
                            a.href = encodeURI("data:text/csv;charset=utf-8," + csv_files[filename]);
                            a.text = filename;
                            jQuery('#' + div_id).append(a);
                        } else {
                            var div = document.createElement('div');
                            div.text = response.message;
                            jQuery('#' + div_id).append(div);
                        }
                    }
                },
                error: function( data, textStatus, jqXHR ) {
                    sessionStorage.removeItem('clicked');
                },
                complete: function() {
                    sessionStorage.removeItem('clicked');
                }
            });
        }
    }

});
