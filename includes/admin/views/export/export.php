<?php 
/**
 * Export page.
 *
 * @version		1.0.0
 * @author lafif <hello@lafif.me>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// echo "<pre>";
// print_r($exports);
// echo "</pre>";

?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php _e('WP Custom Export CSV', 'wcec'); ?></h1>
	<hr class="wp-header-end">

	<div id="bulk_process_container" class="wcec-container">
		<div class="wcec-row">
		  	<div class="wcec-left">
		     	<div class="wcec-holder">
			        <h1>
			        	<span class="dashicons dashicons-forms"></span> 
			        	<?php _e('Available', 'wcec'); ?>
			        </h1>
			        <div class="wcec-clear"></div>
			        <hr class="wcec-hr">
			        <noscript>
			        	<p class="alert error"><?php echo sprintf(__('<strong>%s</strong> Javascript have to be enabled to run the bulk process', 'wcec'), 'Error'); ?></p>
				    </noscript>
			        <div class="wcec-content">
						<form>
							<?php  
							if( !empty( $exports ) ):
								foreach ($exports as $export_id => $object ):
								?>
								<p>
								<label class="option">
						          	<input type="radio" id="export_<?php echo $export_id; ?>" name="export_id" value="<?php echo $export_id; ?>" checked="checked" class="focus">
						          	<span class="radio"></span>
				        		</label>
				        		<label class="option-label" for="process_<?php echo $export_id; ?>"><?php echo $object->name; ?></label>
								</p>
								<?php
								endforeach;

							else:
								_e('No exports found.', 'wcec');
							endif;
							?>
							<hr class="wcec-hr" style="margin-bottom: 0px;">
							<div id="progress" style="margin-bottom: 10px; margin-top: -2px;"></div>
							<a href="Javascript:;" id="start-process" class="btn btn-green"><?php _e('Process', 'wcec'); ?></a>
							<!-- <a href="#" class="stop-process btn btn-default hide" style="float:right;"><?php _e('Stop', 'wcec'); ?></a> -->
						</form>
			        </div>
		     	</div>
		  	</div>

		  	<div class="wcec-right">
				<div class="wcec-holder">
			        <h1><span class="dashicons dashicons-backup"></span> <?php _e('Log', 'wcec'); ?></h1>
					<div class="wcec-clear"></div>
			        <hr class="wcec-hr">
			        <div class="wcec-content">
						<div>
							<div id="window">>_ Log<span id="persen"></span></div>
							<div id="cmd">
								<div id="cmd_text"></div>
							</div>
						</div>
			        </div>
			    </div>
		  	</div>
		</div>
	</div>
</div>