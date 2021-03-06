<?php
/**
 * This file implements the UI view for the robot stats.
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

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';


global $blog, $admin_url, $rsc_url, $AdminUI, $agent_type_color, $Settings, $localtimenow;

echo '<h2 class="page-title">'.T_('Hits from indexing robots / spiders / crawlers - Summary').get_manual_link( 'robots-hits-summary' ).'</h2>';

echo '<p class="notes">'.T_('In order to be detected, robots must be listed in /conf/_stats.php.').'</p>';

// Display panel with buttons to control a view of hits summary pages:
display_hits_summary_panel();

// Check if it is a mode to display a live data:
$is_live_mode = ( get_hits_summary_mode() == 'live' );

$SQL = new SQL( 'Get robot hits summary ('.( $is_live_mode ? 'Live data' : 'Aggregate data' ).')' );
if( $is_live_mode )
{	// Get the live data:
	$SQL->SELECT( 'SQL_NO_CACHE COUNT( * ) AS hits,
		EXTRACT( YEAR FROM hit_datetime ) AS year,
		EXTRACT( MONTH FROM hit_datetime ) AS month,
		EXTRACT( DAY FROM hit_datetime ) AS day' );
	$SQL->FROM( 'T_hitlog' );
	$SQL->WHERE( 'hit_agent_type = "robot"' );
	if( $blog > 0 )
	{	// Filter by collection:
		$SQL->WHERE_and( 'hit_coll_ID = '.$DB->quote( $blog ) );
	}

	$hits_start_date = NULL;
	$hits_end_date = date( 'Y-m-d' );
}
else
{	// Get the aggregated data:
	$SQL->SELECT( 'SUM( hagg_count ) AS hits,
		EXTRACT( YEAR FROM hagg_date ) AS year,
		EXTRACT( MONTH FROM hagg_date ) AS month,
		EXTRACT( DAY FROM hagg_date ) AS day' );
	$SQL->FROM( 'T_hits__aggregate' );
	$SQL->WHERE( 'hagg_agent_type = "robot"' );
	if( $blog > 0 )
	{	// Filter by collection:
		$SQL->WHERE_and( 'hagg_coll_ID = '.$DB->quote( $blog ) );
	}
	// Filter by date:
	list( $hits_start_date, $hits_end_date ) = get_filter_aggregated_hits_dates();
	$SQL->WHERE_and( 'hagg_date >= '.$DB->quote( $hits_start_date ) );
	$SQL->WHERE_and( 'hagg_date <= '.$DB->quote( $hits_end_date ) );
}
$SQL->GROUP_BY( 'year, month, day' );
$SQL->ORDER_BY( 'year DESC, month DESC, day DESC' );
$res_hits = $DB->get_results( $SQL->get(), ARRAY_A, $SQL->title );

/*
 * Chart
 */
if( count($res_hits) )
{
	// Find the dates without hits and fill them with 0 to display on graph and table:
	$res_hits = fill_empty_hit_days( $res_hits, $hits_start_date, $hits_end_date );

	$last_date = 0;

	$chart[ 'chart_data' ][ 0 ] = array();
	$chart[ 'chart_data' ][ 1 ] = array();

	$chart['dates'] = array();

	// Initialize the data to open an url by click on bar item
	$chart['link_data'] = array();
	$chart['link_data']['url'] = $admin_url.'?ctrl=stats&tab=hits&datestartinput=$date$&datestopinput=$date$&blog='.$blog.'&agent_type=$param1$';
	$chart['link_data']['params'] = array(
			array( 'robot' )
		);

	$count = 0;
	foreach( $res_hits as $row_stats )
	{
		$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );
		if( $last_date != $this_date )
		{ // We just hit a new day, let's display the previous one:
			$last_date = $this_date;	// that'll be the next one
			$count ++;
			array_unshift( $chart[ 'chart_data' ][ 0 ], date( 'D '.locale_datefmt(), $last_date ) );
			array_unshift( $chart[ 'chart_data' ][ 1 ], 0 );

			array_unshift( $chart['dates'], $last_date );
		}
		$chart [ 'chart_data' ][1][0] = $row_stats['hits'];
	}

	array_unshift( $chart[ 'chart_data' ][ 0 ], '' );
	array_unshift( $chart[ 'chart_data' ][ 1 ], T_('Robot hits') );	// Translations need to be UTF-8

	$chart[ 'series_color' ] = array (
			$agent_type_color['robot'],
		);

	$chart[ 'canvas_bg' ] = array( 'width'  => '100%', 'height' => 355 );

	echo '<div class="center">';
	load_funcs('_ext/_canvascharts.php');
	CanvasBarsChart( $chart );
	echo '</div>';


	/*
	 * Table:
	 */
	echo '<table class="grouped table table-striped table-bordered table-hover table-condensed" cellspacing="0">';
	echo '	<tr>';
	echo '		<th class="firstcol shrinkwrap">'.T_('Date').'</th>';
	echo '		<th class="lastcol" style="background-color: #'.$agent_type_color['robot'].'"><a href="'.$admin_url.'?ctrl=stats&amp;tab=hits&amp;agent_type=robot&amp;blog='.$blog.'">'.T_('Robot hits').'</a></th>';
	echo '	</tr>';

	$hits_total = 0;
	foreach( $res_hits as $r => $row_stats )
	{
		$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );

		// Check if current data are live and not aggregated:
		$is_live_data = true;
		if( ! $is_live_mode )
		{	// Check only for "Aggregate data":
			$time_prune_before = mktime( 0, 0, 0 ) - ( $Settings->get( 'auto_prune_stats' ) * 86400 );
			$is_live_data = $this_date >= $time_prune_before;
		}
		?>
		<tr class="<?php echo ( $r % 2 == 1 ) ? 'odd' : 'even'; ?>">
			<td class="firstcol shrinkwrap" style="text-align:right"><?php
				echo date( 'D '.locale_datefmt(), $this_date );
				if( $is_live_mode && $current_User->check_perm( 'stats', 'edit' ) )
				{	// Display a link to prune hits only for live data and if current user has a permission:
					echo action_icon( T_('Prune hits for this date!'), 'delete', $admin_url.'?ctrl=stats&amp;action=prune&amp;date='.$this_date.'&amp;show=summary&amp;blog='.$blog.'&amp;'.url_crumb( 'stats' ) );
				}
			?></td>
			<td class="lastcol right"><?php echo $is_live_data ? '<a href="'.$admin_url.'?ctrl=stats&amp;tab=hits&amp;'
				.'datestartinput='.urlencode( date( locale_datefmt() , $this_date ) ).'&amp;'
				.'datestopinput='.urlencode( date( locale_datefmt(), $this_date ) ).'&amp;blog='.$blog.'&amp;agent_type=robot">'.$row_stats['hits'].'</a>' : $row_stats['hits']; ?></td>
		</tr>
		<?php
		// Increment total hits counter:
		$hits_total += $row_stats['hits'];
	}

	// Total numbers:
	?>
		<tr class="total">
			<td class="firstcol"><?php echo T_('Total') ?></td>
			<td class="lastcol right"><?php echo $is_live_mode ? '<a href="'.$admin_url.'?ctrl=stats&amp;tab=hits&amp;blog='.$blog.'&amp;agent_type=robot">'.$hits_total.'</a>' : $hits_total; ?></td>
		</tr>
	</table>
	<?php
}


// TOP INDEXING ROBOTS
/* put this back when we have a CONCISE table of robots
// Create result set:
$SQL = new SQL();
$SQL->SELECT( 'SQL_NO_CACHE COUNT(*) AS hit_count, agnt_signature' );
$SQL->FROM( 'T_hitlog' );
$SQL->WHERE( 'hit_agent_type = "robot"' );
if( ! empty( $blog ) )
	$SQL->WHERE_and( 'hit_coll_ID = ' . $blog );
$SQL->GROUP_BY( 'agnt_signature' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'SQL_NO_CACHE COUNT( DISTINCT agnt_signature )' );
$count_SQL->FROM( $SQL->get_from( '' ) );
$count_SQL->WHERE( $SQL->get_where( '' ) );

$Results = new Results( $SQL->get(), 'topidx', '-D', 20, $count_SQL->get() );

$count_SQL->SELECT( 'SQL_NO_CACHE COUNT(*)' );
$total_hit_count = $DB->get_var( $count_SQL->get() );

$Results->title = T_('Top Indexing Robots');

/**
 * Helper function to translate agnt_signature to a "human-friendly" version from {@link $user_agents}.
 * @return string
 *
function translate_user_agent( $agnt_signature )
{
	global $user_agents;

	$html_signature = htmlspecialchars( $agnt_signature );
	$format = '<span title="'.$html_signature.'">%s</span>';

	foreach ($user_agents as $curr_user_agent)
	{
		if( strpos($agnt_signature, $curr_user_agent[1]) !== false )
		{
			return sprintf( $format, htmlspecialchars($curr_user_agent[2]) );
		}
	}

	if( ( $browscap = @get_browser( $agnt_signature ) ) && $browscap->browser != 'Default Browser' )
	{
		return sprintf( $format, htmlspecialchars( $browscap->browser ) );
	}

	return $html_signature;
}

// User agent:
$Results->cols[] = array(
		'th' => T_('Robot'),
		'order' => 'agnt_signature',
		'td' => '%translate_user_agent(\'$agnt_signature$\')%',
	);

// Hit count:
$Results->cols[] = array(
		'th' => T_('Hit count'),
		'order' => 'hit_count',
		'td_class' => 'right',
		'td' => '$hit_count$',
	);

// Hit %
$Results->cols[] = array(
		'th' => T_('Hit %'),
		'order' => 'hit_count',
		'td_class' => 'right',
		'td' => '%percentage( #hit_count#, '.$total_hit_count.' )%',
	);

// Display results:
$Results->display();
*/

?>