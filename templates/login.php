<?php
/*
 * Template to render Admin UI to setup and save the credentials for this plugin
 *
 * @since 2016-07-21
 * @version 2016-07-21 Archana Mandhare PMCVIP-1950
 */

?>
	<div class="wrap">

		<div id="poststuff">

			<div id="postbox-container-1" class="postbox-container">

				<div class="postbox">

					<div class="handlediv" title="Click to toggle"><br></div>

					<h3 class="hndle ui-sortable-handle"><span>Login Credentials for Content Import</span></h3>

					<div class="inside">

						<div class="domain-creds-form">
							<p>Please enter the following credentials for the Production domain : .</p>

							<form id="pmc-domain-creds" action="options.php" method="post">
								<?php
								settings_fields( 'pmc_domain_creds' );
								?>
								<div>
									<table id="domain-creds-layout" class="widefat">
										<tr>
											<td><span>Production Domain Name: e.g  http://exampledomain.com/ Just enter <strong>exampledomain.com</strong> </span>
											</td>
											<td><input type="text" id="domain" name="domain"
											           value="<?php echo ! empty( $domain ) ? esc_attr( $domain ) : ''; ?>"/>
											</td>
										</tr>
										<tr>
											<td><span>REST API Client ID : </span></td>
											<td><input type="text" id="client_id" name="client_id"
											           value="<?php echo ! empty( $client_id ) ? esc_attr( $client_id ) : ''; ?>"/>
											</td>
										</tr>
										<tr>
											<td><span>REST API Client Secret : </span></td>
											<td><input type="text"
											           name="client_secret"
											           value="<?php echo ! empty( $client_secret ) ? esc_attr( $client_secret ) : ''; ?>"/>
											</td>
										</tr>
										<tr>
											<td><span>REST API Redirect URI : </span></td>
											<td><input type="text" id="redirect_uri"
											           name="redirect_uri"
											           value="<?php echo ! empty( $redirect_uri ) ? esc_attr( $redirect_uri ) : ''; ?>"/>
											</td>
										</tr>
										<tr>
											<td><span>XMLRPC Username : </span></td>
											<td><input type="text"
											           name="xmlrpc_username"
											           value="<?php echo ! empty( $xmlrpc_username ) ? esc_attr( $xmlrpc_username ) : ''; ?>"/>
											</td>
										</tr>
										<tr>
											<td><span>XMLRPC Password : </span></td>
											<td><input type="text"
											           name="xmlrpc_password"
											           value="<?php echo ! empty( $xmlrpc_password ) ? esc_attr( $xmlrpc_password ) : ''; ?>"/>
											</td>
										</tr>
										<tr>
											<td><?php submit_button( 'Save All' ); ?></td>
										</tr>
									</table>

								</div>

							</form>

						</div>

					</div>

				</div>

			</div>

		</div>

	</div>
<?php
//EOF
