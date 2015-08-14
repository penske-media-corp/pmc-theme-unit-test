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
												<td><input type="text" id="domain" name="domain" value=""/></td>
											</tr>
											<tr>
												<td><span>REST API Client ID : </span></td>
												<td><input type="text" id="client_id" name="client_id" value=""/></td>
											</tr>
											<tr>
												<td><span>REST API Client Secret : </span></td>
												<td><input type="text"
												           name="client_secret"
												           value=""/></td>
											</tr>
											<tr>
												<td><span>REST API Redirect URI : </span></td>
												<td><input type="text" id="redirect_uri"
												           name="redirect_uri"
												           value=""/></td>
											</tr>
											<tr>
												<td><span>XMLRPC Username : </span></td>
												<td><input type="text"
												           name="xmlrpc_username"
												           value=""/></td>
											</tr>
											<tr>
												<td><span>XMLRPC Password : </span></td>
												<td><input type="text"
												           name="xmlrpc_password"
												           value=""/></td>
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
												<td><?php submit_button( 'Save All' ); ?>
												<td>
											</tr>
										</table>

									</div>

								</form>
							</div>


						<?php } else if ( $show_data_import ) { ?>
							<div class="sync-button">

								<button id="sync-from-prod" class="button" disabled><b>Import From Production</b></button><div class="spin-loader"></div>

							</div>

							<div class="log-output"></div>
						<?php } ?>
					</div>

				</div>

			</div>

		</div>

	</div>
<?php
//EOF
