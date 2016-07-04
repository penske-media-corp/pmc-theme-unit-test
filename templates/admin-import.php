<?php
/*
 * Template to render Admin UI to Import Data for this plugin
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
						<div class="import-block">
							<div class="import-row1">
								<div class="sync-button">

									<button id="sync-from-prod" class="button" disabled><b>Import All From
											Production</b></button>
									<div class="spin-loader"></div>

								</div>
								<div class="credentials"><a id="change-credentials" href="#">Change Credentials</a>

									<div class="spin-loader1"></div>
								</div>
							</div>
							<div class="import-row2">
								<div class="log-output"></div>
							</div>
						</div>
					</div>

				</div>

			</div>

		</div>

	</div>
<?php
//EOF
