var custom_post_types_loaded = false;
jQuery(document).ready(function () {

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
            timeout: 3000,
            success: function (data, textStatus, jqXHR) {
                for( var key in data ) {
                    var template = jQuery('.custom-template')[0].outerHTML;
                    var post_type = jQuery(template).appendTo('#the-list-post-types');
                    jQuery(post_type).find('th').text(key);
                    jQuery(post_type).find('input').prop('name', 'custom-post-types[]');
                    jQuery(post_type).find('input').prop('value', key);
                    jQuery(post_type).removeClass('custom-template');
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
});
