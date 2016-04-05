<?php

	namespace WP_Piwik\Template;

	class MetaBoxCustomVars extends \WP_Piwik\Template {
				
		public function addMetabox() {
			add_meta_box(
				'wp-piwik_post_customvars',
				__('Piwik Custom Variables', 'wp-piwik'),
				array(&$this, 'showCustomvars'),
				array('post', 'page', 'custom_post_type'),
				'side',
				'default'
			);
		}

		public function showCustomvars($objPost, $objBox ) {
			wp_nonce_field(basename( __FILE__ ), 'wp-piwik_post_customvars_nonce'); ?>
			<table>
	 			<tr><th></th><th><?php _e('Name', 'wp-piwik'); ?></th><th><?php _e('Value', 'wp-piwik'); ?></th></tr>
	 			<?php for($i = 1; $i <= 5; $i++) { ?>
	 			<tr>
		 			<th><label for="wp-piwik_customvar1"><?php echo $i; ?>: </label></th>
		 			<td><input class="widefat" type="text" name="wp-piwik_custom_cat<?php echo $i; ?>" value="<?php echo esc_attr(get_post_meta($objPost->ID, 'wp-piwik_custom_cat'.$i, true ) ); ?>" size="200" /></td>
		 			<td><input class="widefat" type="text" name="wp-piwik_custom_val<?php echo $i; ?>" value="<?php echo esc_attr(get_post_meta($objPost->ID, 'wp-piwik_custom_val'.$i, true ) ); ?>" size="200" /></td>
		 		</tr>
		 	<?php } ?>
		 	</table>
		 	<p><?php _e('Set custom variables for a page view', 'wp-piwik'); ?>. (<a href="http://piwik.org/docs/custom-variables/"><?php _e('More information', 'wp-piwik'); ?></a>.)</p>
		 	<?php 
		}
		
		public function saveCustomVars($intID, $objPost) {
			// Verify the nonce before proceeding.
			if (!isset( $_POST['wp-piwik_post_customvars_nonce'] ) || !wp_verify_nonce( $_POST['wp-piwik_post_customvars_nonce'], basename( __FILE__ ) ) )
				return $intID;
			// Get post type object
			$objPostType = get_post_type_object($objPost->post_type);
			// Check if the current user has permission to edit the post.
			if (!current_user_can($objPostType->cap->edit_post, $intID))
				return $intID;
			$aryNames = array('cat', 'val');
			for ($i = 1; $i <= 5; $i++)
				for ($j = 0; $j <= 1; $j++) {
					// Get data
					$strMetaVal = (isset($_POST['wp-piwik_custom_'.$aryNames[$j].$i])?htmlentities($_POST['wp-piwik_custom_'.$aryNames[$j].$i]):'');
					// Create key
					$strMetaKey = 'wp-piwik_custom_'.$aryNames[$j].$i;
					// Get the meta value of the custom field key
					$strCurVal = get_post_meta($intID, $strMetaKey, true);
					// Add meta val:
					if ($strMetaVal && '' == $strCurVal)
						add_post_meta($intID, $strMetaKey, $strMetaVal, true);
					// Update meta val:
					elseif ($strMetaVal && $strMetaVal != $strCurVal)
						update_post_meta($intID, $strMetaKey, $strMetaVal);
					// Delete meta val:
					elseif (''==$strMetaVal && $strCurVal)
						delete_post_meta($intID, $strMetaKey, $strCurVal);
				}
		}		
	}