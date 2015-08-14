jQuery(document).ready(function () {

    if (typeof pmc_unit_test_ajax !== 'undefined') {

        window.PMC_Theme_Unit_Test.init(pmc_unit_test_ajax);

        window.PMC_Theme_Unit_Test.getClientDetails();

        jQuery('#sync-from-prod').on("click", function () {
            window.PMC_Theme_Unit_Test.importData();
        });

        jQuery('#authorize-url').on( "click", function (e) {
            var href = this.href;
            var client_id = jQuery("#client_id").val();
            if (href.indexOf(client_id) < 0) {
                href = href + '&client_id=' + client_id;
            }
            var redirect_uri = jQuery("#redirect_uri").val();
            if (href.indexOf(redirect_uri) < 0) {
                href = href + '&redirect_uri=' + redirect_uri;
            }
            this.href = href;
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
                jQuery.each(self.routes.all_routes, function (i, end_route) {
                    self.callRestAllEndpoints(end_route, false);
                });
            }

            if (self.routes.post_routes !== 'undefined') {
                jQuery.each(self.routes.post_routes, function (i, end_route) {
                    self.callRestPostEndpoints(end_route, true);
                });
            }

            if (self.routes.xmlrpc_routes !== 'undefined') {
                jQuery.each(self.routes.xmlrpc_routes, function (i, end_route) {
                    self.callXmlrpcEndpoints(end_route);
                });
            }
        }

    },

    getClientDetails: function () {

        var self = this;

        try {

            jQuery('.spin-loader').show();

            jQuery.ajax({
                type: "post",
                url: self.options.admin_url,
                data: {
                    action: "get_client_configuration_details",
                    client_nOnce: self.options.client_nOnce
                },
                success: function (data, textStatus, jqXHR) {
                    if (data !== "undefined") {
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

        if (data.all_routes !== 'undefined') {
            self.routes.all_routes = data.all_routes;
        }

        if (data.post_routes !== 'undefined') {
            self.routes.post_routes = data.post_routes;
        }

        if (data.xmlrpc_routes !== 'undefined') {
            self.routes.xmlrpc_routes = data.xmlrpc_routes;
        }

        jQuery('#sync-from-prod').prop('disabled', false);

        jQuery('.spin-loader').hide();


    },

    callRestAllEndpoints: function (end_route) {

        var self = this;

        var ajax_data = {
            action: "import_all_data_from_production",
            import_nOnce: self.options.import_nOnce,
            route: end_route
        };

        self.makeAjaxRequest(ajax_data, end_route);


    },

    callRestPostEndpoints: function (end_route) {

        var self = this;

        var ajax_data = {
            action: "import_posts_data_from_production",
            import_posts_nOnce: self.options.import_posts_nOnce,
            route: end_route
        };

        self.makeAjaxRequest(ajax_data, end_route);


    },

    callXmlrpcEndpoints: function (end_route) {

        var self = this;

        var ajax_data = {
            action: "import_xmlrpc_data_from_production",
            import_xmlrpc_nOnce: self.options.import_xmlrpc_nOnce,
            route: end_route
        };

        var route_name = end_route;

        self.makeAjaxRequest(ajax_data, route_name);

    },


    makeAjaxRequest: function (ajax_data, route_name) {

        var self = this;

        try {

            var routes_span = jQuery('<span />').attr('id', route_name).addClass('label-blue').addClass("route-label");
            routes_span.append(route_name + ' Import Started : <div class="loader"></div>');

            jQuery('.log-output').append(routes_span);
            jQuery('.log-output').append(jQuery('<br /><br />'));

            jQuery.ajax({

                timeout: 5000000, /* 5000 secs timeout */
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
