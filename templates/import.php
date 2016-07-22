<?php
/*
 * Template to render Admin UI to Import Data for this plugin
 *
 * @since 2016-07-21
 * @version 2016-07-21 Archana Mandhare PMCVIP-1950
 *
 */

?>
	<div class="wrap">

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2" style="float:left;">

				<div id="postbox-container-1" class="postbox-container">
					<div class="postbox meta-box-sortables ui-sortable">
						<div class="handlediv" title="Click to toggle"><br></div>
						<h3 class="hndle ui-sortable-handle"><span>Credentials</span></h3>
						<div class="inside">
							<div class="credentials"><a id="change-credentials" href="admin.php?page=content-login&change=1">Change Credentials</a>
							</div>
						</div>
					</div>
				</div>
				<div id="postbox-container-2" class="postbox-container">

					<div class="postbox meta-box-sortables ui-sortable">

						<div class="handlediv" title="Click to toggle"><br></div>

						<h3 class="hndle ui-sortable-handle"><span>Import from Production </span></h3>

						<div class="inside">

							<div class="import-block">

								<form action="<?php echo admin_url( 'admin.php?page=pmc_theme_unit_test&amp;types=1' ); ?>" method="post">
								<?php wp_nonce_field( 'import-content' ); ?>
									<fieldset>
										<table class="wp-list-table widefat fixed striped posts">
											<thead>
											<tr>
												<td id="cb" class="manage-column column-cb check-column">
													<input id="main-select-all-1" type="checkbox">
												</td>
												<th scope="col" class="manage-column column-title column-primary">
													<span><strong> Select All</strong></span>
												</th>
											</tr>
											</thead>
											<tbody>
											<tr class="custom-template">
												<td><input type="checkbox" name="content[]" value="users"></td>
												<th> Users</th>
											</tr>
											<tr>
												<td><input type="checkbox" name="content[]" value="menus"></td>
												<th> Menu</th>
											</tr>
											<tr>
												<td><input type="checkbox" name="content[]" value="tags"></td>
												<th> Tags</th>
											</tr>
											<tr>
												<td><input type="checkbox" name="content[]" value="categories"></td>
												<th> Categories</th>
											</tr>
											<tr>
												<td><input type="checkbox" name="content[]" value="post"></td>
												<th> Posts</th>
											</tr>
											<tr>
												<td><input type="checkbox" name="content[]" value="page"></td>
												<th> Pages</th>
											</tr>
											</tbody>
										</table>

									</fieldset>

									<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Import Selected"></p>
								</form>

							</div>

						</div>

					</div>

					<div class="postbox meta-box-sortables ui-sortable">

						<div class="handlediv" title="Click to toggle"><br></div>

						<h3 class="hndle ui-sortable-handle"><span>Import Custom Content from Production </span></h3>

						<div class="inside">

							<div class="import-block">

								<form action="<?php echo admin_url( 'admin.php?page=pmc_theme_unit_test&amp;types=2' ); ?>" method="post">
									<?php wp_nonce_field( 'import-custom-content' ); ?>
									<fieldset>
										<legend class="screen-reader-text">Content to export</legend>
										<input type="hidden" name="download" value="true">
										<table class="wp-list-table widefat fixed striped posts">
											<thead>
											<tr>
												<td id="cb" class="manage-column column-cb check-column">
													<label class="screen-reader-text" for="cb-select-all-1">Select One</label>
												</td>
												<th scope="col" id="title" class="manage-column column-title column-primary sortable desc">
													<span><strong>Title</strong></span>
												</th>
											</tr>
											</thead>
											<tbody id="the-list">
											<tr>
												<td><input type="radio" name="custom-content[]" value="post-types"></td>
												<th> Custom Post Types
													<ul id="custom-post-types-container" class="export-filters" style="display: none;">
														<li>
															<table class="wp-list-table widefat fixed striped posts">
																<thead>
																<tr>
																	<td id="cb" class="manage-column column-cb check-column">
																		<input id="custom-select-all-1" type="checkbox">
																	</td>
																	<th scope="col" id="title" class="manage-column column-title column-primary desc">
																		<span><strong>Select All</strong><div class="spin-loader"></div></span>
																	</th>
																</tr>
																</thead>
																<tbody id="the-list-post-types">
																</tbody>
															</table>
														</li>
													</ul>
												</th>
											</tr>
											<tr>
												<td><input type="radio" name="custom-content[]" value="taxonomies"></td>
												<th> Custom Taxonomies</th>
											</tr>
											<tr>
												<td><input type="radio" name="custom-content[]" value="options"></td>
												<th> Options</th>
											</tr>
											</tbody>
										</table>

									</fieldset>

									<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Import Selected"></p>
								</form>

							</div>

						</div>

					</div>
				</div>

			</div>
		</div>
	</div>
<?php
//EOF
