<?php
/**
 * Displays user properties form
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
?>
<div class="panelblock" style="vertical-align:top">
<form class="fform" method="post" action="b2users.php">
	<div style="float:right"><a title="<?php echo T_('Close user profile'); ?>" href="b2users.php">[ X ]</a></div>
		
	<h2><?php
	if( $edited_User->get('ID') == 0 )
	{
		echo T_('Create new user profile');
	}
	else
	{
		echo T_('Profile for:').' '.$edited_User->get('nickname').' ('.$edited_User->get('login').')';
	}	
	?></h2>
	
	<table align="center">
	<tr><td>
	<fieldset>

		<input type="hidden" name="action" value="userupdate" />
		<input type="hidden" name="edited_user_ID" value="<?php $edited_User->disp('ID','formvalue') ?>" />
		<input type="hidden" name="edited_user_oldlogin" value="<?php $edited_User->disp('login','formvalue') ?>" />
		
		<table cellpadding="5" cellspacing="0">
			<?php
			form_text_tr( 'edited_user_login', $edited_User->get('login'), 50, T_('Login'), '', 20 );
			form_text_tr( 'edited_user_firstname', $edited_User->get('firstname'), 50, T_('First name'), '', 50 );
			form_text_tr( 'edited_user_lastname', $edited_User->get('lastname'), 50, T_('Last name'), '', 50 );
			form_text_tr( 'edited_user_nickname', $edited_User->get('nickname'), 50, T_('Nickname'), '', 50 );
			?>
			<tr>
			<td align="right"><strong><?php echo T_('Identity shown') ?>:</strong></td>
			<td>
				<?php $idmode = $edited_User->get( 'idmode' ); ?>
				<select name="edited_user_idmode" class="bComment">
					<option value="nickname"<?php if ( $idmode == 'nickname' ) echo ' selected="selected"'; ?>><?php if( $edited_User->get('nickname') != '' ) $edited_User->disp('nickname', 'htmlhead' ); else echo '['.T_('Nickname').']'; ?></option>
					<option value="login"<?php if ( $idmode == 'login' ) echo ' selected="selected"'; ?>><?php if( $edited_User->get('login') != '' ) $edited_User->disp('login', 'htmlhead' ); else echo '['.T_('Login').']'; ?></option>
					<option value="firstname"<?php if ( $idmode == 'firstname' ) echo ' selected="selected"'; ?>><?php if( $edited_User->get('firstname') != '' ) $edited_User->disp('firstname', 'htmlhead' ); else echo '['.T_('First name').']'; ?></option>
					<option value="lastname"<?php if ( $idmode == 'lastname' ) echo ' selected="selected"'; ?>><?php if( $edited_User->get('lastname') != '' ) $edited_User->disp('lastname', 'htmlhead' ); else echo '['.T_('Last name').']'; ?></option>
					<option value="namefl"<?php if ( $idmode == 'namefl' ) echo ' selected="selected"'; ?>><?php if( $edited_User->get('firstname') != '' ) $edited_User->disp('firstname', 'htmlhead' ); else echo '['.T_('First name').']'; echo ' '; if( $edited_User->get('lastname') != '' ) $edited_User->disp('lastname', 'htmlhead' ); else echo '['.T_('Last name').']'; ?></option>
					<option value="namelf"<?php if ( $idmode == 'namelf' ) echo ' selected="selected"'; ?>><?php if( $edited_User->get('lastname') != '' ) $edited_User->disp('lastname', 'htmlhead' ); else echo '['.T_('Last name').']'; echo ' '; if( $edited_User->get('firstname') != '' ) $edited_User->disp('firstname', 'htmlhead' ); else echo '['.T_('First name').']'; ?></option>
				</select>
			</td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('Locale') ?>:</strong></td>
			<td>
				<select name="edited_user_locale" class="bComment"><?php
					locale_options( $edited_User->get('locale') );?>
				</select>
			</td>
			</tr>
			<?php
			form_text_tr( 'edited_user_email', $edited_User->get('email'), 50, T_('Email'), '<a href="mailto:'.$edited_User->get('email').'"><img src="img/play.png" border="0" height="14" width="14" alt="&gt;" title="'.T_('Write Email').'" /></a>', 100 );
			
			if( $edited_User->get('url') != '' )
			{
				$fieldnote = '<a href="'.$edited_User->get('url').'" target="_blank"><img src="img/play.png" border="0" height="14" width="14" alt="&gt;" title="'.T_('Visit homepage').'" /></a>';
			}
			else $fieldnote = '';
			form_text_tr( 'edited_user_url', $edited_User->get('url'), 50, T_('URL'), $fieldnote, 100 );
			
			if( $edited_User->get('icq') != 0 )
			{
				$fieldnote = '<a href="http://wwp.icq.com/scripts/search.dll?to='.$edited_User->get('icq').'" target="_blank"><img src="img/play.png" border="0" height="14" width="14" alt="&gt;" title="'.T_('Search on ICQ.com').'" /></a>';
			}
			else $fieldnote = '';
			form_text_tr( 'edited_user_icq', $edited_User->get('icq'), 50, T_('ICQ'), $fieldnote, 10 );
			
			if( $edited_User->get('aim') != '' )
			{
				$fieldnote = '<a href="aim:goim?screenname='.$edited_User->get('aim').'&amp;message=Hello"><img src="img/play.png" border="0" height="14" width="14" alt="&gt;" title="'.T_('Instant Message to user').'" /></a>';
			}
			else $fieldnote = '';
			form_text_tr( 'edited_user_aim', $edited_User->get('aim'), 50, T_('AIM'), $fieldnote, 50 );
			
			form_text_tr( 'edited_user_msn', $edited_User->get('msn'), 50, T_('MSN IM'), '', 100 );
			form_text_tr( 'edited_user_yim', $edited_User->get('yim'), 50, T_('YahooIM'), '', 50 );
			?>
			<tr>
				<td align="right"><label for="edited_user_notify"><strong><?php echo T_('Notifications') ?>:</strong></td>
				<td><input type="checkbox" name="edited_user_notify" id="edited_user_notify" value="1"<?php if($edited_User->get('notify')) echo ' checked="checked"'?> />
				<!-- <span class="notes"><?php echo T_('Check this to receive notification whenever one of your posts receives comments, trackbacks, etc.')?></span> -->
				</td>
			</tr>
			<tr>
				<td align="right"><label for="edited_user_pass1"><strong><?php echo T_('New password') ?>:</strong></td>
				<td><input type="password" name="edited_user_pass1" id="edited_user_pass1" value="" size="50" /></td>
			</tr>
			<tr>
				<td align="right"><label for="edited_user_pass2"><strong><?php echo T_('Confirm new password') ?>:</strong></td>
				<td><input type="password" name="edited_user_pass2" id="edited_user_pass2" value="" size="50" /></td>
			</tr>
		</table>
	</fieldset>
	</td>
	<td style="vertical-align:top">
	<fieldset>
		<legend><?php echo T_('User information') ?></legend>
		<table cellpadding="5" cellspacing="0">
			<tr>
			<td align="right"><strong><?php echo T_('ID') ?>:</strong></td>
			<td><?php $edited_User->disp('ID') ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('Posts') ?>:</strong></td>
			<td><?php if( $action != 'newtemplate' ) echo get_usernumposts($edited_User->get('ID')) ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('Created on:') ?></strong></td>
			<td><?php $edited_User->disp('datecreated'); ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('From IP:') ?></strong></td>
			<td><?php $edited_User->disp('ip'); ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('From Domain:') ?></strong></td>
			<td><?php $edited_User->disp('domain'); ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('With Browser:') ?></strong></td>
			<td><?php $edited_User->disp('browser'); ?></td>
			</tr>
		</table>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('User rights') ?></legend>
		
		<?php
		/*<label for="edited_user_level"><?php echo T_('Level')?></label>
		<input type="text" name="edited_user_level" value="<?php $edited_User->disp('level')?>" />
		<span class="notes">[0 - 10]</span>*/
			
		form_text(  'edited_user_level', $edited_User->get('level'), 2, T_('Level'), '[0 - 10]', 2 );
				
		if(  $edited_User->get('ID') != 1 )
		{
			$chosengroup = ( $edited_User->Group === NULL ) ? '' : $edited_User->Group->get('ID');
			form_select_object( 'edited_user_grp_ID', $chosengroup, $GroupCache, T_('User group') );
		}
		else
		{
			echo '<input type="hidden" name="edited_user_grp_ID" value="1" />';
			form_info(  T_('User group'), $edited_User->Group->get('name') );
		}
		?>
	</fieldset>
	</td>
	</tr>
	<?php
	if( $current_User->check_perm( 'users', 'edit' ) )
	{ ?>
		<tr><td colspan="2" style="text-align:center">
		<input type="submit" name="submit" value="<?php if( $edited_User->get('ID') == 0 ) echo T_('Create'); else echo T_('Update') ?>" class="search" />
		<input type="reset" value="<?php echo T_('Reset') ?>" class="search" />
		</td></tr>
	<?php } ?>
	

	</table>
	
	<div class="clear"></div>

</form>

</div>
