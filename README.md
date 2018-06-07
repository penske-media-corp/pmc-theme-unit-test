PMC Theme Unit Test v1.0 Alpha
---------------------------------

[![Build Status](https://travis-ci.org/Penske-Media-Corp/pmc-theme-unit-test.svg?branch=master)](https://travis-ci.org/Penske-Media-Corp/pmc-theme-unit-test)

WordPress plugin that provides a Theme Menu Option to Unit Test data by importing just enough data from production server and creating a local or test environment.

The plugin is basically a data import tool that makes use of [WordPress Public REST API](https://developer.wordpress.com/docs/api/) and [WordPress XML-RPC API](https://codex.wordpress.org/XML-RPC_WordPress_API/Taxonomies) to make an authenticated call to the server to fetch just the required amount of data for the theme to replicate the Production Environment.

## Minimum Requirements

- WordPress 4.0 or above
- PHP 5.4 or above
- Your Theme PHP code for which data import is expected through this plugin.

## Instructions

1. Create an application for the site you want to pull data from using the [WordPress Public REST API](https://developer.wordpress.com/apps/)
  * *The `Redirect URL` you use needs an actual domain.* `.local` or `.dev` domains won't work with OAuth2. When you get redirected back to this domain, you'll need to copy the `code` querystring parameter value.
  * `Type` should be `Native`.
  * This would give you `client_id`, `client_secret` and `redirect_uri`. Add these on the form provided in your plugin admin page.
  * All these would be required for OAuth2 authentication.
2. Create an [application-specific password for your WordPress.com account](https://en.support.wordpress.com/security/two-step-authentication/#application-specific-passwords) (because you're using Two-Step Authentication).
3. In wp-admin look for Management Menu Option :  Tools => Sync from Production
			OR
	Navigate to http://YOURSITEDOMAIN.com/wp-admin/tools.php?page=data-import
4. You will see a form which you have to input domain name , credentials for the first time and will be saved in the database. The `redirect_uri` should be the plugin url `http://YOURSITEDOMAIN.com/wp-admin/tools.php?page=data-import` or `http://YOURSITEDOMAIN.com/redirectme endpoint` (if this endpoint can send the code querystring back to the plugin url)
5. Hit Save All and you will get Import Button replcing the form.
6. Hit `Import Data from Production` and wait patiently as the data gets imported in the background.
7. Voila! Your theme is setup and you can start to unit test you theme.

## Filters required in the Production site

1. Please add a filter `rest_api_allowed_post_types` to whitelist the Custom Post Types that you want to import
	The REST API does not allow custom post types by default. Only built-in post types are allowed.
```php

	/**
	 * Post types besides post and page need to be whitelisted using the
	 * rest_api_allowed_post_types filter in order to access them via the
	 * public REST API
	 *
	 * @see: https://developer.wordpress.com/docs/api/
	 *
	 * @param array $allowed_post_types Array containing the allowed post_types
	 *
	 * @return array $allowed_post_types Array containing the allowed post_types
	 */
	add_filter( 'rest_api_allowed_post_types', function( $allowed_post_types ) {

		$whitelist_post_types_in_rest_api = array(
			'in_preview',
			'late_night_video',
		);

		foreach ( $whitelist_post_types_in_rest_api as $whitelist_post_type ) {
			if ( post_type_exists( $whitelist_post_type ) ) {
				$allowed_post_types[] = $whitelist_post_type;
			}
		}

		$allowed_post_types = array_unique( $allowed_post_types );

		return $allowed_post_types;

	} );

```

2. Add a filter `options_import_blacklist` to blacklist the wp_options that you would not like to be imported to another site.

3. Add a filter `options_import_whitelist` to whitelist the wp_options that you would like to be imported to another site.

4. Add filter `pmc_xmlrpc_client_credentials` to fetch the credentials for XMLRPC calls.

5. Add the below filter for automatic oAuth redirect
```php
                        /*
                         * This is the common endpoint for oauth redirect for theme unit test plugin
                         * https://github.com/Penske-Media-Corp/pmc-theme-unit-test
                         * @since 2015-10-12 Archana Mandhare PMCVIP-62
                         * For local - http://vip.local/redirectme/
                         * and if we have a local site http://abcdef.vip.local/wp-admin
                         */
							add_action( 'init', function () {
							
								if ( false !== stripos( $_SERVER['REQUEST_URI'], '/redirectme' ) && ! empty( $_COOKIE['oauth_redirect'] ) ) {
							
									if ( ! empty( $_GET['code'] ) ) {
							
										$code           = sanitize_text_field( $_GET[ 'code' ] );
										$oauth_redirect = sanitize_text_field( $_COOKIE['oauth_redirect'] );
										$redirect_url   = $oauth_redirect . '&code=' . $code;
										wp_safe_redirect( $redirect_url );
										exit;
							
									}
								}
							
							} );```



