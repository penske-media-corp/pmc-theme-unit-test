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

					<div class="domain-select">

						<span>Select Domain</span>

						<select id="domain-names">

							<option disabled selected>Please Select</option>

							<?php foreach ( $domains as $domain ) { ?>

								<option value="<?php echo esc_attr( $domain ); ?>"><?php echo esc_html( $domain ) ?></option>

							<?php } ?>

						</select>

						<div class="spin-loader"></div>

					</div>

					<div class="domain-code">

						<p>Please authenticate yourself using below link and copy the <strong>code</strong> query string to enter in textbox below.</p>

						<div id="authorize-text"></div>

						<span>Code : </span>

						<input type="text" id="wp-auth-code" value=""/>

					</div>

					<div class="sync-button">

						<button id="sync-from-prod" class="button"><b>Import From Production</b></button>

					</div>

					<div class="log-output"></div>

				</div>

			</div>

		</div>

	</div>

</div>
