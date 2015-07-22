jQuery(document).ready(function () {

    if (typeof pmc_unit_test_ajax !== 'undefined') {

        window.PMC_Theme_Unit_Test.init(pmc_unit_test_ajax);

        jQuery('#sync-from-prod').on("click", function () {
            window.PMC_Theme_Unit_Test.importData();
        });

        jQuery('#domain-names').on("change", function () {
            window.PMC_Theme_Unit_Test.getClientDetails();
        });

    }

});

window.PMC_Theme_Unit_Test = {

    options: 0,
    routes: {},

    init: function (settings) {
        this.options = settings;
    },

    importData: function () {

        var self = this;
        jQuery(".log-output").empty();

        if (self.routes !== 'undefined') {

            if (self.routes.all_routes !== 'undefined') {
                jQuery.each(self.routes.all_routes, function (i, end_routes) {
                    self.callRestEndpoints(end_routes);
                });
            }

            if (self.routes.post_routes !== 'undefined') {
                jQuery.each(self.routes.post_routes, function (i, end_routes) {
                    self.callRestEndpoints(end_routes);
                });
            }

            if (self.routes.xmlrpc_routes !== 'undefined') {
                jQuery.each(self.routes.xmlrpc_routes, function (i, end_routes) {
                    self.callXmlrpcEndpoints(end_routes);
                });
            }
        }

    },

    getClientDetails: function () {

        var self = this;

        try {

            jQuery('#authorize-text').empty();
            jQuery('.domain-code').hide();
            jQuery('.spin-loader').show();

            jQuery.ajax({
                type: "post",
                url: self.options.admin_url,
                data: {
                    action: "get_client_configuration_details",
                    client_nOnce: self.options.client_nOnce,
                    domain: jQuery('#domain-names').val()
                },
                success: function (data, textStatus, jqXHR) {

                    jQuery('.spin-loader').hide();

                    if ( data !== "undefined" ) {
                        self.setupAdminPage(data)
                    }
                },
                error: function (x, t, m) {

                    jQuery('.spin-loader').hide();
                    if (t === "timeout") {
                        alert("Got timeout for the domain details request. Please try again");
                    } else {
                        alert(t + " : " + m);
                    }

                }

            });
        } catch (e) {
            console.log(e);
        }

    },

    setupAdminPage: function (data) {

        var self = this;

        if (data.all_routes !== 'undefined' && data.post_routes !== 'undefined') {

            self.routes = {
                "all_routes": data.all_routes,
                "post_routes": data.post_routes,
                "xmlrpc_routes": data.xmlrpc_routes
            };
        }
        if (data.config_oauth.redirect_uri !== 'undefined' && data.config_oauth.client_id !== 'undefined') {

            var redirect_uri = encodeURI(data.config_oauth.redirect_uri);
            var query_params = {
                client_id: data.config_oauth.client_id,
                redirect_uri: redirect_uri,
                response_type: 'code',
                scope: 'global'
            };

            var params = jQuery.param(query_params);
            var authorize_url = self.options.API + '?' + params;
            var authorize_href = jQuery('<a />');
            authorize_href.attr('href', authorize_url);
            authorize_href.attr('target', '_blank');
            authorize_href.text('Authorize URL');

            jQuery('#authorize-text').append(authorize_href);
            jQuery('.domain-code').show();
        }

        jQuery('.sync-button').show();

    },

    callRestEndpoints: function (end_route) {

        var self = this;

        var ajax_data = {
            action: "import_data_from_production",
            import_nOnce: self.options.import_nOnce,
            domain: jQuery('#domain-names').val(),
            code: jQuery('#wp-auth-code').val(),
            route: end_route
        };

        var route_name = Object.keys(end_route);

        self.makeAjaxRequest(ajax_data, route_name);


    },

    callXmlrpcEndpoints: function (end_route) {

        var self = this;

        var ajax_data = {
            action: "import_xmlrpc_data_from_production",
            import_xmlrpc_nOnce: self.options.import_xmlrpc_nOnce,
            domain: jQuery('#domain-names').val(),
            route: end_route
        };

        var route_name = end_route;

        self.makeAjaxRequest(ajax_data, route_name);

    },


    makeAjaxRequest: function (ajax_data, route_name) {

        var self = this;

        try {

            var route_name = route_name;

            var routes_span = jQuery('<span />').attr('id', route_name).addClass('label-blue').addClass("route-label");
            routes_span.append(route_name + ' Import Started : <div class="loader"></div>');

            jQuery('.log-output').append(routes_span);
            jQuery('.log-output').append( jQuery('<br /><br />') );

            jQuery.ajax({

                timeout: 500000, /* 500 secs timeout */
                type: "post",
                url: self.options.admin_url,
                data: ajax_data,
                success: function (data, textStatus, jqXHR) {

                    jQuery('#' + route_name).empty();
                    jQuery('#' + route_name).append('<span class="route-label label-green">' + route_name + ' Import Done </span>');

                },
                error: function (x, t, m) {

                    if (t === "timeout") {
                        alert("Got timeout for " + route_name);
                    } else {
                        alert(t + " : " + m);
                    }

                    jQuery('#' + route_name).empty();
                    jQuery('#' + route_name).append('<span class="route-label label-red">' + route_name + ' Import Failed</span>');

                }

            });
        } catch (e) {
            console.log(e);
        }

    }

}
