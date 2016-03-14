<?php
/**
 * This file implements the UI view for the user picture crop form.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $display_mode, $Settings;

/**
 * @var instance of User class
 */
global $edited_User;
/**
 * @var current action
 */
global $action;
/**
 * @var user permission, if user is only allowed to edit his profile
 */
global $user_profile_only;
/**
 * @var the action destination of the form (NULL for pagenow)
 */
global $form_action;
/**
 * @var instance of User class
 */
global $current_User;
/**
 * @var File that should be cropped
 */
global $cropped_File;

global $aspect_ratio, $content_width, $content_height;
$aspect_ratio = param( 'aspect_ratio', 'double' );
$content_width = param( 'content_width', 'integer' );
$content_height = param( 'content_height', 'integer' );

if( $display_mode != 'js' )
{
	// ------------------- PREV/NEXT USER LINKS -------------------
	user_prevnext_links( array(
			'user_tab' => 'avatar'
		) );
	// ------------- END OF PREV/NEXT USER LINKS -------------------
}

$Form = new Form( $form_action, 'user_checkchanges' );

if( is_admin_page() )
{
	$form_class = 'fform';
	$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";
	$ctrl_param = '?ctrl=user&amp;user_tab=avatar&amp;user_ID='.$edited_User->ID;

	$form_title = '';
	$form_class = 'fform';
	$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";
}
else
{
	global $Blog;
	$form_title = '';
	$form_class = 'bComment';
	$ctrl_param = url_add_param( $Blog->gen_blogurl(), 'disp='.$disp );
}

if( $display_mode != 'js' && is_admin_page() )
{
	if( !$user_profile_only )
	{
		echo_user_actions( $Form, $edited_User, $action );
	}

	$form_text_title = T_( 'Crop profile picture' ); // used for js confirmation message on leave the changed form
	$form_title = get_usertab_header( $edited_User, '', $form_text_title );
}

$cropped_image_tag = $cropped_File->get_tag( '', '', '', '', 'original', '', '', '', '', '', '', '', '', '', 'none' );

// Display this error when JS is not enabled
echo '<noscript>'
		.'<p class="error text-danger">'.T_('Please activate Javascript in your browser in order to use this feature.').'</p>'
		.'<style type="text/css">form#user_checkchanges { display:none }</style>'
	.'</noscript>';

$Form->begin_form( $form_class, $form_title, array( 'title' => ( isset( $form_text_title ) ? $form_text_title : $form_title ) ) );

if( is_admin_page() )
{
	$Form->hidden_ctrl();
}
else
{
	$Form->hidden( 'disp', $disp );
	$Form->hidden( 'action', $action );
}
$Form->add_crumb( 'user' );
$Form->hidden( 'user_tab', param( 'user_tab_from', 'string', 'avatar' ) );
$Form->hidden( 'user_ID', isset( $edited_User ) ? $edited_User->ID : $current_User->ID );
$Form->hidden( 'file_ID', $cropped_File->ID );
$Form->hidden( 'image_crop_data', '' );
if( isset( $Blog ) )
{
	$Form->hidden( 'blog', $Blog->ID );
}

$close_icon = '';
if( $display_mode == 'js' )
{ // Display a close link for popup window
	$close_icon = action_icon( T_('Close this window'), 'close', '', '', 0, 0, array( 'id' => 'close_button', 'class' => 'floatright' ) );
}

$Form->button( array( 'type' => 'submit', 'name'=>'actionArray[crop]', 'value'=> T_('Crop'), 'class' => 'SaveButton btn-primary' ) );

// Start displaying content
echo '<div id="content" style="height: '.$content_height.'px; width: '. $content_width.'px;">';
echo '</div>';
$Form->end_form();
?>
<style>
	#workarea img {
		object-fit: contain;
		width: <?php echo $workarea_width;?>;
		height: <?php echo $workarea_height;?>;
		visibility: hidden;
	}

	div.jcrop-holder {
		margin: 0 auto;
		position: relative;
		top: 50%;
		transform: translateY(-50%);
	}
</style>
<script type="text/javascript">
	var jcrop_api;
	var image_url = '<?php echo $cropped_File->get_url(); ?>';
	var gutter = 10;
	var padding = 0;
	var content_height = <?php echo $content_height;?>;
	var content_width = <?php echo $content_width;?>;
	var show_large_preview = true;
	var show_small_preview = true;
	var large_preview_size = 128;
	var small_preview_size = 64;
	var preview_size;
	var preview_margin = 10;
	var aspect_ratio = <?php echo $aspect_ratio;?>;
	var render_mode = 'landscape';

	var workarea_height;
	var workarea_width;
	var workarea_aspect_ratio;

	<?php
	$original_image_size = explode( 'x', $cropped_File->get_image_size() );
	?>
	var original_image_height = <?php echo $original_image_size[1];?>;
	var original_image_width = <?php echo $original_image_size[0];?>;
	var original_image_aspect_ratio = original_image_height / original_image_width;

	var size_ratio = 1;
	var working_image_height = null;
	var working_image_width = null;
	var working_image_aspect_ratio = null;

	var initial_crop_selection = [];

	console.debug( 'Content: ', content_width, content_height );

	// We'll use this function to determine which mode will provide the larger working image
	function get_working_image_dimensions( w_height, w_width, image_aspect_ratio )
	{
		var w_aspect_ratio = w_height / w_width;

		if( w_aspect_ratio > image_aspect_ratio )
		{ // width is limiting dimension
			i_width = w_width;
			i_height = w_width * original_image_aspect_ratio;
		}
		else
		{ // height is limiting dimension
			i_height = w_height;
			i_width = w_height / original_image_aspect_ratio;
		}

		i_area = i_width * i_height;

		return { height: i_height, width: i_width, area: i_area };
	}


	function init_layout()
	{
		var lw_width, lw_height, lw_area, lw_aspect_ratio;
		var pw_width, pw_height, pw_area, pw_aspect_ratio;
		var i_width, i_height, i_area;

		if( ! preview_size )
		{ // assume will use a large preview image
			preview_size = large_preview_size;
		}

		// Try landscape mode first
		lw_width = content_width - preview_size - gutter - ( padding * 2 );
		lw_height = content_height - ( padding * 2 );
		lw_area = lw_width * lw_height;
		lw_aspect_ratio = lw_height / lw_width;

		var l_view = get_working_image_dimensions( lw_height, lw_width, original_image_aspect_ratio );

		// Now let's try portrait mode
		pw_height = content_height - preview_size - gutter - ( padding * 2 );
		pw_width = content_width - ( padding * 2 );
		pw_area = pw_width * pw_height;
		pw_aspect_ratio = pw_height / pw_width;

		var p_view = get_working_image_dimensions( pw_height, pw_width, original_image_aspect_ratio );

		// See what mode provides the largest working image
		if ( l_view.area > p_view.area )
		{
			render_mode = 'landscape';
		}
		else
		{
			render_mode = 'portrait';
		}

		// Determine if we can show all the preview images
		if( render_mode == 'portrait' )
		{
			if( content_width > ( ( ( large_preview_size + preview_margin ) * 2 ) + ( ( small_preview_size + preview_margin ) * 2 ) ) )
			{
				show_large_preview = true;
				show_small_preview = true;
				preview_size = large_preview_size;
			}
			else if( ( content_width > ( ( large_preview_size + preview_margin ) * 2 ) ) && ( ( 0.25 * content_height ) >= large_preview_size ) )
			{
				show_large_preview = true;
				show_small_preview = false;
				preview_size = large_preview_size;
			}
			else if( ( content_width > ( ( small_preview_size + preview_margin ) * 2 ) ) && ( ( 0.25 * ( render_mode == 'landscape' ? content_width : content_height ) ) >= small_preview_size ) )
			{
				show_large_preview = false;
				show_small_preview = true;
				preview_size = small_preview_size;
			}
			else
			{
				show_large_preview = false;
				show_small_preview = false;
				preview_size = 0;
			}
		}
		else
		{
			if( content_height > ( ( ( large_preview_size + preview_margin ) * 2 ) + ( ( small_preview_size + preview_margin ) * 2 ) ) )
			{
				show_large_preview = true;
				show_small_preview = true;
				preview_size = large_preview_size;
			}
			else if( ( content_height > ( ( large_preview_size + preview_margin ) * 2 ) ) && ( ( 0.5 * ( content_width < content_height ? content_width : content_height ) ) >= large_preview_size ) )
			{
				show_large_preview = true;
				show_small_preview = false;
				preview_size = large_preview_size;
			}
			else if( ( content_height > ( ( small_preview_size + preview_margin ) * 2 ) ) && ( ( 0.5 * ( content_width < content_height ? content_width : content_height ) ) >= small_preview_size ) )
			{
				show_large_preview = false;
				show_small_preview = true;
				preview_size = small_preview_size;
			}
			else
			{
				show_large_preview = false;
				show_small_preview = false;
				preview_size = 0;
			}
		}

		console.debug( 'Render mode: ', render_mode );
		console.debug( 'Show large preview: ', show_large_preview );
		console.debug( 'Show small preview: ', show_small_preview );
		console.debug( 'Preview Size: ', preview_size );
	}

	function init_workarea()
	{
		if( render_mode == 'portrait' )
		{
			workarea_height = content_height - preview_size - gutter - ( padding * 2 );
			workarea_width = content_width - ( padding * 2 );
		}
		else
		{
			workarea_height = ( content_height - ( padding * 2 ) );
			workarea_width = content_width - preview_size - gutter - ( padding * 2 );
		}
		workarea_aspect_ratio = ( workarea_height / workarea_width );

		console.debug( 'Workarea: ', workarea_width, workarea_height );
	}

	function init_working_image()
	{
		if( workarea_aspect_ratio == original_image_aspect_ratio )
		{
			working_image_height = workarea_height;
			working_image_width = workarea_width;
			size_ratio = original_image_height / working_image_width;
			console.debug( 'Limiting dimension: ', 'both' );
		}
		else if( workarea_aspect_ratio > original_image_aspect_ratio )
		{
			working_image_width = workarea_width;
			working_image_height = workarea_width * original_image_aspect_ratio;
			size_ratio = original_image_width / working_image_width;
			console.debug( 'Limiting dimension: ', 'width' );
		}
		else
		{
			working_image_height = workarea_height;
			working_image_width = workarea_height / original_image_aspect_ratio;
			size_ratio = original_image_height / working_image_height;
			console.debug( 'Limiting dimension: ', 'height' );
		}
		// Should be always equal to original image aspect ratio
		working_image_aspect_ratio = working_image_height / working_image_width;

		console.debug( 'Image: ', working_image_width, working_image_height );
	}

	function set_initial_crop_selection()
	{
		var crop_size;
		initial_crop_selection = [];

		if( original_image_aspect_ratio > 1 )
		{
			crop_size = original_image_width * 0.90;
		}
		else
		{
			crop_size = original_image_height * 0.90;
		}

		initial_crop_selection.push( ( original_image_width / 2 ) - ( crop_size / 2 ) );
		initial_crop_selection.push( ( original_image_height / 2 ) - ( crop_size / 2 ) );
		initial_crop_selection.push( ( original_image_width / 2 ) + ( crop_size / 2 ) );
		initial_crop_selection.push( ( original_image_height / 2 ) + ( crop_size / 2 ) );
	}

	function render_content()
	{
		var content = jQuery( 'div#content' );

		var working_image = jQuery( '<img />', {
				src: image_url
			});

		var workarea = jQuery( '<div />', {
				id: 'workarea',
				style: {
					'background-color': '#f2f2f2',
					height: workarea_height + 'px',
					width: workarea_width + 'px'
				}
			});

		var previews = jQuery( '<div />', {
				id: 'preview',
				style: {
					'background-color': 'white',
					height: preview_size + 'px',
					'text-align': 'center',
					'margin-top': gutter + 'px'
				}
			});

		var preview_lg_sq = jQuery( '<div />', {
				class: 'preview_cropped_image'
			}).css({
				width: '128px',
				height: '128px',
			}).append( working_image.clone() );

		var preview_sm_sq = jQuery( '<div />', {
				class: 'preview_cropped_image'
			}).css({
					width: '64px',
					height: '64px',
			}).append( working_image.clone() );

		var preview_lg_c = jQuery( '<div />', {
				class: 'preview_cropped_image circle'
			}).css({
					width: '128px',
					height: '128px',
			}).append( working_image.clone() );

		var preview_sm_c = jQuery( '<div />', {
				class: 'preview_cropped_image circle'
			}).css({
					width: '64px',
					height: '64px',
			}).append( working_image.clone() );

		if( render_mode == 'portrait' )
		{ // Portrait
			workarea.css({
				'background-color': '#f2f2f2',
				height: workarea_height + 'px',
				width: workarea_width + 'px'
			});
			previews.css({
				'background-color': '#ffffff',
				height: preview_size + 'px',
				'text-align': 'center',
				'margin-top': gutter + 'px'
			});

			if( show_large_preview )
			{
				previews.append( preview_lg_sq );
			}

			if( show_small_preview )
			{
				previews.append( preview_sm_sq );
			}

			if( show_large_preview )
			{
				previews.append( preview_lg_c );
			}

			if( show_small_preview )
			{
				previews.append( preview_sm_c );
			}

			workarea.prepend( working_image );
			content.append( workarea );
			content.append( previews );

			preview_images = jQuery( 'div.preview_cropped_image' );
			preview_images.css( 'margin-bottom', 0 );
			preview_images.css( 'margin-right', preview_margin + 'px' );
		}
		else
		{ // Landscape
			workarea.css({
				'background-color': '#f2f2f2',
				float: 'left',
				width: workarea_width + 'px',
				height: workarea_height + 'px'
			});
			previews.css({
				'background-color': '#ffffff',
				float: 'left',
				width: preview_size + 'px',
				height: workarea_height + 'px',
				'text-align': 'center',
				'margin-left': gutter + 'px'
			});

			workarea.prepend( working_image );
			var preview_wrapper = jQuery( '<div />' );
			if( show_large_preview )
			{
				preview_wrapper.append( preview_lg_sq );
			}

			if( show_small_preview )
			{
				preview_wrapper.append( preview_sm_sq );
			}

			if( show_large_preview )
			{
				preview_wrapper.append( preview_lg_c );
			}

			if( show_small_preview )
			{
				preview_wrapper.append( preview_sm_c );
			}
			previews.append( preview_wrapper );

			content.append( workarea );
			content.append( previews );
			content.append( jQuery( '<div />', { style: { clear: 'both' } } ) );

			preview_images = jQuery( 'div.preview_cropped_image' );
			preview_images.css( 'margin-bottom', 0 );
			preview_images.css( 'margin-right', preview_margin + 'px' );
		}
	}

	function updatePreview( coords )
	{
		var target_cropped_image_width = original_image_width;
		var target_cropped_image_height = original_image_height;

		var percent_width = Math.ceil( coords.w / target_cropped_image_width * 10000 ) / 100;
		var percent_height = Math.ceil( coords.h / target_cropped_image_height * 10000 ) / 100;
		var percent_top = Math.ceil( coords.x / target_cropped_image_width * 10000 ) / 100;
		var percent_left = Math.ceil( coords.y / target_cropped_image_height * 10000 ) / 100;
		jQuery( 'input[name=image_crop_data]' ).val( percent_top + ':' + percent_left + ':' + percent_width + ':' + percent_height );

		var top = coords.y;
		var left = coords.x;
		if( coords.w > coords.h )
		{ // Center a cropping area of horizontal image
			left += ( coords.w / 2 ) - ( coords.h / 2 );
		}
		else
		{
			var top_shift = ( coords.h - coords.w ) * 0.15;
			if( top + top_shift + coords.w < top + coords.h )
			{ // top - 15%
				top += top_shift;
			}
		}

		jQuery( '.preview_cropped_image img' ).each( function()
		{
			var ratio = jQuery( this ).parent().width() / ( coords.w < coords.h ? coords.w : coords.h );

			jQuery( this ).css({
				width: Math.round( ratio * target_cropped_image_width ) + 'px',
				height: Math.round( ratio * target_cropped_image_height ) + 'px',
				marginLeft: '-' + Math.round( ratio * left ) + 'px',
				marginTop: '-' + Math.round( ratio * top ) + 'px'
			});
		});
	}

	function init_jcrop_tool( image )
	{
		options = {
					boxWidth: working_image_width,
					boxHeight: working_image_height,
					aspectRatio: 1,
					minSize: [ 64, 64 ],
					onChange: updatePreview,
					onSelect: updatePreview
				};

		image.Jcrop( options, function() {
			jcrop_api = this;
			jcrop_api.setSelect( initial_crop_selection );
			image.css({ visibility: 'visible' });
		});
	}

	// Initialize content, workarea and working image
	init_layout();
	init_workarea();
	init_working_image();
	set_initial_crop_selection();

	// Render everything
	render_content();

	// Initialize jcrop tool only after the image is fully loaded
	jQuery( '#workarea img' ).load( function()
		{
			init_jcrop_tool( jQuery( this ) );
		});
</script>