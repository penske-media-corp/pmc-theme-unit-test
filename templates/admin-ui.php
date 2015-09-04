<?php
/*
 * Template to render Admin UI for this plugin
 *
 * @since 2015-07-17 Archana Mandhare
 */

?>
	<div class="wrap">

		<div id="poststuff">

			<div id="postbox-container-1" class="postbox-container">

				<div class="postbox">

					<div class="handlediv" title="Click to toggle"><br></div>

					<h3 class="hndle ui-sortable-handle"><span>Sync data from Production</span></h3>

					<div class="inside">
						<?php if ( $show_cred_form ) { ?>
							<div class="domain-creds-form">
								<p>Please enter the following credentials for the Production domain : .</p>

								<form id="pmc-domain-creds" action="options.php" method="post">
									<?php
									settings_fields( 'pmc_domain_creds' );
									?>
									<div>
										<table id="domain-creds-layout" class="widefat">
											<tr>
												<td><span>Production Domain Name: e.g  http://exampledomain.com/ Just enter <strong>exampledomain.com</strong> </span></td>
												<td><input type="text" id="domain" name="domain" value="<?php echo ! empty( $domain ) ? esc_attr( $domain ) : '';?>"/></td>
											</tr>
											<tr>
												<td><span>REST API Client ID : </span></td>
												<td><input type="text" id="client_id" name="client_id" value="<?php echo ! empty( $client_id ) ? esc_attr( $client_id ) : '';?>"/></td>
											</tr>
											<tr>
												<td><span>REST API Client Secret : </span></td>
												<td><input type="text"
												           name="client_secret"
												           value="<?php echo ! empty( $client_secret ) ? esc_attr( $client_secret ) : '';?>"/></td>
											</tr>
											<tr>
												<td><span>REST API Redirect URI : </span></td>
												<td><input type="text" id="redirect_uri"
												           name="redirect_uri"
												           value="<?php echo ! empty( $redirect_uri ) ? esc_attr( $redirect_uri ) : '';?>"/></td>
											</tr>
											<tr>
												<td><span>XMLRPC Username : </span></td>
												<td><input type="text"
												           name="xmlrpc_username"
												           value="<?php echo ! empty( $xmlrpc_username ) ? esc_attr( $xmlrpc_username ) : '';?>"/></td>
											</tr>
											<tr>
												<td><span>XMLRPC Password : </span></td>
												<td><input type="text"
												           name="xmlrpc_password"
												           value="<?php echo ! empty( $xmlrpc_password ) ? esc_attr( $xmlrpc_password ) : '';?>"/></td>
											</tr>
											<tr>
												<td>
													<div class="domain-code">

														<p>Please authenticate yourself using below link and copy the
															<strong>code</strong>
															query
															string to enter in textbox below.</p>

														<div id="authorize-text"><a id="authorize-url"
														                            href="<?php echo esc_url( $authorize_url ); ?>"
														                            target="_blank">Authorize URL</a>
														</div>

														<span>Code : </span>

														<input type="text" id="wp-auth-code" name="code" value=""/>

													</div>
												</td>
											</tr>
											<tr>
												<td><?php submit_button( 'Save All' ); ?> <a id="cancel-form-button" href="javascript:window.location.reload();"> Cancel </a><td>
												<td><td>
											</tr>
										</table>

									</div>

								</form>
							</div>


						<?php } else if ( $show_data_import ) { ?>
							<div class="import-block">
								<div class="import-row1">
									<div class="sync-button">

										<button id="sync-from-prod" class="button" disabled><b>Import All From
												Production</b></button>
										<div class="spin-loader"></div>

									</div>
									<div class="credentials"><a id="change-credentials" href="#">Change Credentials</a><div class="spin-loader1"></div></div>
								</div>
								<div class="import-row2">
									<div class="log-output"></div>
								</div>
							</div>
						<?php } ?>
					</div>

				</div>

			</div>

		</div>

	</div>
<?php
//EOF
