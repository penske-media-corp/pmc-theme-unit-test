window.PMC_Theme_Unit_Test = {

    /**
     * Register settings for JS object
     *
     * @since 2015-07-15
     * @version 2015-07-15 Archana Mandhare - PPT-5077
     */
    options: 0,
    routes: {},

    /**
     * Kick things off
     */
    init: function (settings) {
        this.options = settings;
    },

    /**
     * Get the details from the server to setup the page and what data to import
     *
     * @since 2015-07-15
     * @version 2015-07-15 Archana Mandhare - PPT-5077
     */
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

    /**
     * Setup the admin page HTML
     *
     * @since 2015-07-15
     * @version 2015-07-15 Archana Mandhare - PPT-5077
     */
    setupAdminPage: function (data) {

        var self = this;

        if (data.all_routes !== 'undefined') {
            self.routes.all_routes = data.all_routes;

            jQuery.each(self.routes.all_routes, function (i, end_route) {
                self.setupIndividualImportForAll(end_route);
            });
        }

        if (data.post_routes !== 'undefined') {
            self.routes.post_routes = data.post_routes;

            jQuery.each(self.routes.post_routes, function (i, end_route) {
                self.setupIndividualImportForPosts(end_route);
            });
        }

        if (data.xmlrpc_routes !== 'undefined') {
            self.routes.xmlrpc_routes = data.xmlrpc_routes;
            jQuery.each(self.routes.xmlrpc_routes, function (i, end_route) {
                self.setupIndividualImportForxmlrpc(end_route);
            });
        }

        jQuery('#sync-from-prod').prop('disabled', false);

        jQuery('.spin-loader').hide();


    },

    /**
     * Create HTML for the endpoint to show in Admin UI
     *
     * @since 2015-07-15
     * @version 2015-07-15 Archana Mandhare - PPT-5077
     */
    setupIndividualImport: function (end_route) {

        var routes_span = jQuery('<div>').attr('id', end_route).addClass('label-blue').addClass("route-label");
        routes_span.append(jQuery('<span>').text(end_route));
        routes_span.append(jQuery('<div />').append(jQuery('<button />', {
            text: 'Import',
            class: 'button',
            id: 'import' + end_route
        })));

        jQuery('.log-output').append(routes_span);
        jQuery('.log-output').append(jQuery('<br /><br />'));

    },

    /**
     * Create HTML for the most endpoints to show in Admin UI
     *
     * @since 2015-07-15
     * @version 2015-07-15 Archana Mandhare - PPT-5077
     */
    setupIndividualImportForAll: function (end_route) {

        var self = this;

        self.setupIndividualImport(end_route);

        jQuery('#import' + end_route).on('click', function () {
            self.callRestAllEndpoints(end_route);
        });

    },

    /**
     * Create HTML for the posts endpoints to show in Admin UI
     *
     * @since 2015-07-15
     * @version 2015-07-15 Archana Mandhare - PPT-5077
     */
    setupIndividualImportForPosts: function (end_route) {

        var self = this;

        self.setupIndividualImport(end_route);

        jQuery('#import' + end_route).on('click', function () {
            self.callRestPostEndpoints(end_route);
        });

    },

    /**
     * Create HTML for the xmlrpc endpoints to show in Admin UI
     *
     * @since 2015-07-15
     * @version 2015-07-15 Archana Mandhare - PPT-5077
     */
    setupIndividualImportForxmlrpc: function (end_route) {

        var self = this;

        self.setupIndividualImport(end_route);

        jQuery('#import' + end_route).on('click', function () {
            self.callXmlrpcEndpoints(end_route);
        });

    },

    /**
     * Start the actual data import by making an ajax call for each endpoint
     *
     * @since 2015-07-15
     * @version 2015-07-15 Archana Mandhare - PPT-5077
     */
    importData: function () {

        var self = this;

        if (self.routes !== 'undefined') {

            if (self.routes.all_routes !== 'undefined') {
                jQuery.each(self.routes.all_routes, function (i, end_route) {
                    self.callRestAllEndpoints(end_route);
                });
            }

            if (self.routes.post_routes !== 'undefined') {
                jQuery.each(self.routes.post_routes, function (i, end_route) {
                    self.callRestPostEndpoints(end_route);
                });
            }

            if (self.routes.xmlrpc_routes !== 'undefined') {
                jQuery.each(self.routes.xmlrpc_routes, function (i, end_route) {
                    self.callXmlrpcEndpoints(end_route);
                });
            }
        }

    },


    /**
     * Call the ajax method for each endpoint
     *
     * @since 2015-07-15
     * @version 2015-07-15 Archana Mandhare - PPT-5077
     */
    callRestAllEndpoints: function (end_route) {

        var self = this;

        var ajax_data = {
            action: "import_all_data_from_production",
            import_nOnce: self.options.import_nOnce,
            route: end_route
        };

        self.makeAjaxRequest(ajax_data, end_route);


    },

    /**
     * Call the ajax method for post endpoint
     *
     * @since 2015-07-15
     * @version 2015-07-15 Archana Mandhare - PPT-5077
     */
    callRestPostEndpoints: function (end_route) {

        var self = this;

        var ajax_data = {
            action: "import_posts_data_from_production",
            import_posts_nOnce: self.options.import_posts_nOnce,
            route: end_route
        };

        self.makeAjaxRequest(ajax_data, end_route);


    },

    /**
     * Call the ajax method for xmlrpc endpoint
     *
     * @since 2015-07-15
     * @version 2015-07-15 Archana Mandhare - PPT-5077
     */
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

    /**
     * Ajax method to fetch data from server
     *
     * @since 2015-07-15
     * @version 2015-07-15 Archana Mandhare - PPT-5077
     */
    makeAjaxRequest: function (ajax_data, route_name) {

        var self = this;

        try {

            var routes_span = jQuery('#' + route_name);
            routes_span.empty();
            routes_span.addClass('label-blue');
            routes_span.append( jQuery('<span style="display:table-cell">').text(route_name + ' Import Started : '));
            routes_span.append( jQuery('<div>').addClass('loader'));

            jQuery.ajax({

                timeout: 5000000, /* 5000 secs timeout */
                type: "post",
                url: self.options.admin_url,
                data: ajax_data,
                success: function (data, textStatus, jqXHR) {

                    routes_span.empty();

                    routes_span.removeClass('label-blue').addClass('label-green').append(jQuery('<span style="display:table-cell">').text(route_name + ' Import Done') );

                },
                error: function (x, t, m) {

                    if (t === "timeout") {
                        alert("Got timeout for " + route_name);
                    } else {
                        alert(t + " : " + m);
                    }
                    routes_span.empty();
                    routes_span.removeClass('label-blue').addClass('label-red').append(jQuery('<span style="display:table-cell">').text( route_name + ' Import Failed') );

                }

            });
        } catch (e) {
            console.log(e);
        }

    }

}


jQuery(document).ready(function () {

    if (typeof pmc_unit_test_ajax !== 'undefined') {

        window.PMC_Theme_Unit_Test.init(pmc_unit_test_ajax);

        window.PMC_Theme_Unit_Test.getClientDetails();

        jQuery('#sync-from-prod').on("click", function () {
            window.PMC_Theme_Unit_Test.importData();
        });

        jQuery('#authorize-url').on("click", function (e) {
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
