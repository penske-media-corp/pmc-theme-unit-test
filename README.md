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
  * This would give you `client_id`, `client_secret` and `redirect_uri`. Add these to `$rest_api_auth` in `config.php`
  * All these would be required for OAuth2 authentication.
2. Create an [application-specific password for your WordPress.com account](https://en.support.wordpress.com/security/two-step-authentication/#application-specific-passwords) (because you're using Two-Step Authentication).
  * Add these to `$xmlrpc_auth` in `config.php`
  * You can have all the sites credentials saved as key-value pair and then choose the domain that you want to import data from the admin.
3. In wp-admin look for Management Menu Option :  Tools => Sync from Production
			OR
	Navigate to http://YOURSITEDOMAIN/wp-admin/tools.php?page=data-import
4. Select the domain you wish to import data from.
5. Please Authorize yourself by clicking on the Authorize URL. You will be redirected to redirect_uri of the site. Get the "code" query parameter and enter in the textbox provided.
6. Hit Import Data from Production and wait patiently as the data gets imported in the background.
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

4. Add filter `pmc_xmlrpc_client_credentials` to fetch the credentials for XMLRPC calls



