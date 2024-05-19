<?php

add_action('admin_menu', 'wp_manga_watermark_settings_menu');

function wp_manga_watermark_settings_menu()
{
    //add_options_page('WP Manga Watermark - Settings', 'WP Manga Watermark Settings', 'manage_options', 'wp-manga-watermark-settings', 'wp_manga_watermark_settings_page');
	
	add_submenu_page( 'edit.php?post_type=wp-manga', 'WP Manga Image Watermark - Settings', esc_html__( 'Image Watermark Settings', WP_MANGA_WATERMARK_TEXTDOMAIN ), 'manage_options', 'wp-manga-watermark-settings', 'wp_manga_watermark_settings_page' );
}

function wp_manga_watermark_settings_page()
{
	// Save attachment ID
	$err = '';
	if ( isset( $_POST['submit_settings'] ) ) :
		$wp_manga_watermark_options = array();
		
		$wp_manga_watermark_options['attachment_id'] = (isset($_POST['image_attachment_id'])) ? absint($_POST['image_attachment_id']) : '';
		$wp_manga_watermark_options['position'] = (isset($_POST['wp_manga_watermark_position'])) ? $_POST['wp_manga_watermark_position'] : '0,0';
		$wp_manga_watermark_options['position_base'] = (isset($_POST['wp_manga_watermark_position_base'])) ? $_POST['wp_manga_watermark_position_base'] : 'bottom-right';
		
		// validate
		
		list($x, $y) = explode(',',$wp_manga_watermark_options['position']);
		$x = intval(trim($x));$y = intval(trim($y));
		if(is_integer($x) && is_integer($y) && $x >= 0 && $y >= 0){
			$wp_manga_watermark_options['position'] = $x . ',' . $y;
			update_option( 'wp_manga_watermark_options', $wp_manga_watermark_options );
		} else {
			$err = '<p class="warning">' . esc_html__('Invalid position values: must be in format [x,y] in which x, y are integers and not negative',WP_MANGA_WATERMARK_TEXTDOMAIN) . '</p>';
		}
		
		
	endif;
	
	$options = get_option('wp_manga_watermark_options' , array());
	
    echo '<div class="wrap">';
    echo '<h2>'.esc_html__( 'WP Manga Watermark - Settings', WP_MANGA_WATERMARK_TEXTDOMAIN ).'</h2>';
	wp_enqueue_media();
	if($err != ''){
		echo $err;
	}
    ?>
    <form action="" method="post" enctype="multipart/form-data">
        <table class="form-table">
            <tr>
                <th style="width:200px;"><label for="wp_manga_watermark_image"><?php esc_html_e('Watermark Image', WP_MANGA_WATERMARK_TEXTDOMAIN);?></label></th>
                <td>
                    <input type="button" id="wp_manga_watermark_image"
                           name="wp_manga_watermark_image" value="Upload">
					<input type='hidden' name='image_attachment_id' id='image_attachment_id' value='<?php echo (isset($options['attachment_id']) ? $options['attachment_id'] : '');?>'>
					<?php if(isset($options['attachment_id']) && $options['attachment_id'] != 0) {?>
					<img id='image-preview' src='<?php echo wp_get_attachment_url($options['attachment_id']);?>' style='max-height: 100px; '>
					<a href="#" id="remove-image"><?php esc_html_e('Remove', WP_MANGA_WATERMARK_TEXTDOMAIN);?></a>
					<?php } ?>
					<div class="desc"><?php esc_html_e('Watermark image should be smaller than 200x200px',WP_MANGA_WATERMARK_TEXTDOMAIN);?></div>
                </td>
            </tr>
			<tr>
				<th><label for="wp_manga_watermark_position"><?php esc_html_e('Position', 'wp-manga-watermark');?></label></th>
				<td><input type="text" name="wp_manga_watermark_position" id="wp_manga_watermark_position" value="<?php echo isset($options['position']) ? $options['position'] : '0,0';?>"/> 
				<div class="desc"><?php esc_html_e('Set position for the watermark, in [x,y] format. For example: "100,100". Default is "0,0"', WP_MANGA_WATERMARK_TEXTDOMAIN);?></div></td>
			</tr>
			<tr>
				<th><label for="wp_manga_watermark_position_base"><?php esc_html_e('Position Base', 'wp-manga-watermark');?></label></th>
				<td><select name="wp_manga_watermark_position_base" id="wp_manga_watermark_position_base">
					<option value="bottom-right" <?php echo (isset($options['position_base']) && $options['position_base'] == 'bottom-right') ? 'selected="selected"' : '' ;?>>Bottom-Right</option>
					<option value="top-left" <?php echo (isset($options['position_base']) && $options['position_base'] == 'top-left') ? 'selected="selected"' : '' ;?>>Top-Left</option>
					<option value="top-right" <?php echo (isset($options['position_base']) && $options['position_base'] == 'top-right') ? 'selected="selected"' : '' ;?>>Top-Right</option>
					<option value="bottom-left" <?php echo (isset($options['position_base']) && $options['position_base'] == 'bottom-left') ? 'selected="selected"' : '' ;?>>Bottom-Left</option>
				</select>				
				<div class="desc"><?php esc_html_e('From where to calculate position of the watermark. Default is Bottom-Right',WP_MANGA_WATERMARK_TEXTDOMAIN);?></div></td>
			</tr>
        </table>
		<input type="submit" name="submit_settings" value="Save" class="button-primary">
    </form>
    <?php

    echo '</div>';
}