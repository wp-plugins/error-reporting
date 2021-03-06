<?php
/*
Plugin Name: Error Reporting
Plugin URI: http://www.mittineague.com/dev/er.php
Description: Logs Errors to file and/or Sends Error Notification emails. Records Ping Errors and displays them in a dashboard widget.
Version: 1.0.0 RC
Author: Mittineague
Author URI: http://www.mittineague.com
*/

/*
* Change Log
* 
* ver. 1.0.0 RC 09-Feb-2010
* - replaced deprecated user_role
* - auto delete old log files feature
* - minor tweaks
* 
* ver. Beta 0.10.1 13-Aug-2009
* - skip SimplePie errors for now
* - capability check
* - changed Version History to Changelog in readme
* 
* ver. Beta 0.10.0 01-Apr-2009
* - added ping error - dashboard widget code
* - added self-cleanup hooks
* - removed deprecated option descriptions
* - nonce tweaks
* - removed print_r $context
* - added return false
* - changed admin CSS hook
* - removed fail returns from handler
* 
* ver. Beta 0.9.6 15-Mar-2009
* - fixed uninitialized variables
* - fixed 'all types' 'all folders' bug
* - remove/add 'shutdown' action
* - added label tags
* - friendlier CSS selectors
* - added 'register_activation_hook'
* 
* ver. Beta 0.9.5 27-Jan-2009
* - changed mktime() to time()
* - "info" link fix
* - replaced "short tags"
* - changed 'wp'logs to 'er'logs
* - added javascript select/deselect all
*
* ver. Beta 0.9.4 10-Apr-2007
* - made date_default_timezone_get/set OK for PHP < ver. 5
*
* ver. Beta 0.9.3 09-Apr-2007
* - removed error_log from Log block fopen-fwrite fails
* - added natsort to log file display
* - rearranged page sections
* - minor mark-up and info edits
*
* ver. Beta 0.9.2 30-Mar-2007
* - Security improvements 
* - - made wp-log folders / files not world readable
* - - added nonces to form actions
* - minor GUI changes
* - added error_log to Log block fopen-fwrite fails
*
* ver. Beta 0.9.1 29-Mar-2007
* - fixed buggy conditional generating code block
* - added info re E_RECOVERABLE_ERROR
* - added chmod to fix annoying sporadic permission resets
*
* ver. Beta 0.9.0 28-Mar-2007
*/

/*
/--------------------------------------------------------------------\
|                                                                    |
| License: GPL                                                       |
|                                                                    |
| Error Reporting Plugin - Logs errors - Sends Email alerts.         |
| Copyright (C) 2007, Mittineague, www.mittineague.com               |
| All rights reserved.                                               |
|                                                                    |
| This program is free software; you can redistribute it and/or      |
| modify it under the terms of the GNU General Public License        |
| as published by the Free Software Foundation; either version 2     |
| of the License, or (at your option) any later version.             |
|                                                                    |
| This program is distributed in the hope that it will be useful,    |
| but WITHOUT ANY WARRANTY; without even the implied warranty of     |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the      |
| GNU General Public License for more details.                       |
|                                                                    |
| You should have received a copy of the GNU General Public License  |
| along with this program; if not, write to the                      |
| Free Software Foundation, Inc.                                     |
| 51 Franklin Street, Fifth Floor                                    |
| Boston, MA  02110-1301, USA                                        |   
|                                                                    |
\--------------------------------------------------------------------/
*/

function mitt_er_activate()
{
/* default Log Error Reporting options set on install */
	$mitt_log = array('log_action' => '1',
			'log_type_mode' => 'exc',
			'log_type_W' => '0',
			'log_type_N' => '1',
			'log_type_S' => '1',
			'log_andor' => 'or',
			'log_fold_mode' => 'exc',
			'log_fold_A' => '0',
			'log_fold_C' => '0',
			'log_fold_P' => '1',
			'log_fold_I' => '0',
			'log_cont' => '0',
			'log_rpt' => '0',
			);

	add_option('mitt_er_log', $mitt_log);

/* default Email Error Reporting options NOT set on install */
	$mitt_email = array('email_action' => '0',
			'email_type_mode' => '',
			'email_type_W' => '0',
			'email_type_N' => '0',
			'email_type_S' => '0',
			'email_andor' => '',
			'email_fold_mode' => '',
			'email_fold_A' => '0',
			'email_fold_C' => '0',
			'email_fold_P' => '0',
			'email_fold_I' => '0',
			'email_cont' => '',
			);

	add_option('mitt_er_email', $mitt_email);

	if ( function_exists('date_default_timezone_get') )
	{
		$serv_tz = date_default_timezone_get();
	}
	else
	{
		$serv_tz = 'This_option_requires_PHP_ver5+';
	}
	add_option('mitt_er_tz', $serv_tz);

	$mitt_er_ping_errs = array();
	add_option('mitt_er_ping_errors', $mitt_er_ping_errs);

	$mitt_er_pe_len = 25;
	add_option('mitt_er_ping_error_length', $mitt_er_pe_len);

	add_option('er_do_cron_del', 'never');
	wp_schedule_event(time(), 'daily', 'er_cron_del_hook');
}

function mitt_er_deactivate()
{
	remove_action('init', 'mitt_err_handler');
	remove_action('admin_init', 'mitt_er_admin_init');
	remove_action('admin_menu', 'mitt_add_er_page');
	remove_action('wp_dashboard_setup', 'add_er_dashboard_widget');
	delete_option('mitt_er_ping_error_length');

	wp_clear_scheduled_hook('er_cron_del_hook');
	remove_action('er_cron_del_hook', 'er_cron_delete_logs');

	restore_error_handler();
}

/* remove ALL error log files when plugin is uninstalled */
function mitt_remove_log_files()
{
	$guardian = '/^ER-[0-3][0-9]-[ADFJMNOS][abceglnoprtuvy]{2}-20[0-9]{2}\.log$/';

	$main_dir = "../er-logs";
	if ( is_dir($main_dir) )
	{
		chmod($main_dir, 0705);
		exec("chmod 604 " . $main_dir . "/*.log");

		$open_dir = opendir($main_dir);
		while (false !== ($file = readdir($open_dir)))
		{
			if ($file != "." && $file != "..")
			{
				if(preg_match($guardian, $file))
				{
 				      unlink($main_dir . "/" . $file);
				}
			}
		}
		closedir($open_dir);
	}
	rmdir($main_dir);

	$admin_dir = "./er-logs";
	if ( is_dir($admin_dir) )
	{
		chmod($admin_dir, 0705);
		exec("chmod 604 " . $admin_dir . "/*.log");

		$open_dir2 = opendir($admin_dir);
		while (false !== ($file2 = readdir($open_dir2)))
		{
			if ($file2 != "." && $file2 != "..")
			{
				if(preg_match($guardian, $file2))
				{
				      unlink($admin_dir . "/" . $file2);
				}
			}
		}
		closedir($open_dir2);
	}
	rmdir($admin_dir);
}

function mitt_er_uninstall()
{
	delete_option('mitt_er_log');
	delete_option('mitt_er_email');
	delete_option('mitt_er_tz');
	delete_option('mitt_er_ping_errors');
	delete_option('er_do_cron_del');
	mitt_remove_log_files();		
}

function mitt_er_admin_init()
{
	register_setting('mitt-er-poptions', 'mitt_er_pe_len', 'absint');
}

function mitt_er_dashboard_widget()
{
	$er_ping_errs = get_option('mitt_er_ping_errors');
	$er_ping_errs = array_unique($er_ping_errs);
	$num_errs = count($er_ping_errs);
	if ($num_errs === 0)
	{
		echo "There are no recorded ping errors";
	}
	else
	{
		echo current($er_ping_errs) . "<br />";

		for ($pe = 1; $pe < $num_errs; $pe++)
		{
			echo next($er_ping_errs) . "<br />";
		}
	}
}

function add_er_dashboard_widget()
{
	if (function_exists('wp_add_dashboard_widget'))
	{
		wp_add_dashboard_widget('mitt_er_ping_error_widget', 'Ping Errors', 'mitt_er_dashboard_widget');
	}
}


/* CRON Auto Delete old error log files */
function er_cron_delete_logs()
{
	$cron_del_limiter = get_option('er_do_cron_del');

	if ( ($cron_del_limiter == 'month') || ($cron_del_limiter == 'week') )
	{
		$curr_time = time();
		$cron_file_age = 31536000; // default 1 year should be way more than enough

		if ($cron_del_limiter == 'month') $cron_file_age = 2678400;
		if ($cron_del_limiter == 'week') $cron_file_age = 604800;

		$guardian = '/^ER-[0-3][0-9]-[ADFJMNOS][abceglnoprtuvy]{2}-20[0-9]{2}\.log$/';

		$main_dir = "./er-logs";
		if ( is_dir($main_dir) )
		{
			chmod($main_dir, 0705);
			exec("chmod 604 " . $main_dir . "/*.log");

			$open_dir = opendir($main_dir);
			while (false !== ($file = readdir($open_dir)))
			{
				if ($file != "." && $file != "..")
				{
					$last_mod = filemtime('./er-logs/' . $file);
					$passed_time = $curr_time - $last_mod;

					if( (preg_match($guardian, $file)) && ($passed_time > $cron_file_age) )
					{
 				      	unlink($main_dir . "/" . $file);
					}
				}
			}
			exec("chmod 600 " . $main_dir . '/*.log');
			chmod($main_dir, 0700);
			closedir($open_dir);
		}

		$admin_dir = "./wp-admin/er-logs";
		if ( is_dir($admin_dir) )
		{
			chmod($admin_dir, 0705);
			exec("chmod 604 " . $admin_dir . "/*.log");

			$open_dir2 = opendir($admin_dir);
			while (false !== ($file2 = readdir($open_dir2)))
			{
				if ($file2 != "." && $file2 != "..")
				{
					$last_mod2 = filemtime('./wp-admin/er-logs/' . $file2);
					$passed_time2 = $curr_time - $last_mod2;

					if( (preg_match($guardian, $file2)) && ($passed_time2 > $cron_file_age) )
					{
 					      unlink($admin_dir . "/" . $file2);
					}
				}
			}
			exec("chmod 600 " . $admin_dir . '/*.log');
			chmod($admin_dir, 0700);
			closedir($open_dir2);
		}
		clearstatcache();
	}
}

function mitt_er_css()
{
?>
<style type="text/css">
.wrap h3 {
  font-style: italic;
 }
.postbox {
  margin-top: 1.5em;
 }
.postbox h4{
  margin: 0;
  padding: 0.5em;
  background-color: #eee;
 }
.postbox .inside {
  margin: 0;
  padding-left: 1em;
  background-color: #fff;
 }
.postbox .inside form p {
  margin-top: 0;
 }
.postbox .inside form .submit {
  padding: 0 0 0.5em 0;
 }
fieldset.er_options {
  border: 1px solid #999;
  margin-bottom: 1em;
 }
#er_email_opts {
  border: 3px dotted #f00;
 }
.er_logic {
  border: 1px solid #000;
  margin: 1.1em;
 }
.er_logic td {
  padding: .1em;
  border-right: 1px solid #bbb;
  border-bottom: 1px solid #bbb;
 }
#er_key {
  list-style-type: none;
 }
#er_key li {
  margin: 1em 0 -1em 0;
  font-family: monospace;
 }
#er_key_title {
  font-weight: bold;
  font-size: large;
  text-decoration: underline;
  padding: 0 0 .5em 0;
 }
ul li span.er_pi_head {
  font-family: monospace;
  font-size: 1.3em;
 }
</style>
<?php
}

function mitt_add_er_page()
{
	if (function_exists('add_options_page'))
	{
		$mitt_er_op = add_options_page('Error Reporting Options', 'ErrorReporting', 'manage_options', basename(__FILE__), 'mitt_er_options_page');
		add_action("admin_head-$mitt_er_op", 'mitt_er_css');
	}
	$ernonce = md5('errorreporting');
}

function mitt_er_options_page()
{
	global $ernonce;

	$max_ping_saves = 100;	// arbitrary number

	if ( function_exists('current_user_can') && !current_user_can('manage_options') ) exit;

	register_setting('mitt-er-poptions', 'mitt_er_pe_len', 'absint');

	if ( isset($_POST['mitt_update_erp']) )
	{
		check_admin_referer('er-pe-update_' . $ernonce, '_mitt_er_peu');

		if ( ( is_numeric($_POST['mitt_er_pe_len']) ) && ($_POST['mitt_er_pe_len'] >= 0) )
		{
			$mitt_er_pe_len = absint($_POST['mitt_er_pe_len']);
			$mitt_er_pe_len = ($mitt_er_pe_len > $max_ping_saves) ? $max_ping_saves : $mitt_er_pe_len;
			update_option('mitt_er_ping_error_length', $mitt_er_pe_len);
			
			$mitt_er_p_errs = get_option('mitt_er_ping_errors');
			$num_p_errs = count($mitt_er_p_errs);

			if ( $num_p_errs > $mitt_er_pe_len )
			{
				$mitt_er_p_errs = array_slice($mitt_er_p_errs, 0, $mitt_er_pe_len);
				update_option('mitt_er_ping_errors', $mitt_er_p_errs);
			}
?>
		<div id="message" class="updated fade">
			<p>The number of Ping Errors to save has been updated</p>
		</div>
<?php
		}
	}

	if ( isset($_POST['mitt_er_rem_pe']) )
	{
		check_admin_referer('er-pe-remove-errors_' . $ernonce, '_mitt_er_pere');

		$mitt_er_p_errs = array();
		update_option('mitt_er_ping_errors', $mitt_er_p_errs);
?>
		<div id="message" class="updated fade">
			<p>All saved Ping Errors have been removed</p>
		</div>
<?php
	}

/* Clear Options Section */
	if (isset($_POST['clear_options']))
	{
		check_admin_referer('error-reporting-clear-options_' . $ernonce, '_mitt_er_co');

		$clr_log_opts = array('log_action' => '0',
					'log_type_mode' => '',
					'log_type_W' => '0',
					'log_type_N' => '0',
					'log_type_S' => '0',
					'log_andor' => '',
					'log_fold_mode' => '',
					'log_fold_A' => '0',
					'log_fold_C' => '0',
					'log_fold_P' => '0',
					'log_fold_I' => '0',
					'log_cont' => '',
					'log_rpt' => '',
					);

		$clr_email_opts = array('email_action' => '0',
					'email_type_mode' => '',
					'email_type_W' => '0',
					'email_type_N' => '0',
					'email_type_S' => '0',
					'email_andor' => '',
					'email_fold_mode' => '',
					'email_fold_A' => '0',
					'email_fold_C' => '0',
					'email_fold_P' => '0',
					'email_fold_I' => '0',
					'email_cont' => '',
					);

		update_option('mitt_er_log', $clr_log_opts);
		update_option('mitt_er_email', $clr_email_opts);
?>
		<div id="message" class="updated fade">
			<p><strong>Error Reporting Options have been Cleared<br />Error Reporting is now Disabled.<br />To Resume Error Reporting, select options and Update them.</strong></p>
		</div>
<?php
	}
/* the Default Error Reporting option settings */
	if (isset($_POST['restore_options']))
	{
		check_admin_referer('error-reporting-restore-options_' . $ernonce, '_mitt_er_ro');

		$rstr_log_opts = array('log_action' => '1',
					'log_type_mode' => 'exc',
					'log_type_W' => '0',
					'log_type_N' => '1',
					'log_type_S' => '1',
					'log_andor' => 'or',
					'log_fold_mode' => 'exc',
					'log_fold_A' => '0',
					'log_fold_C' => '0',
					'log_fold_P' => '1',
					'log_fold_I' => '0',
					'log_cont' => '0',
					'log_rpt' => '0',
					);

		$rstr_email_opts = array('email_action' => '1',
					'email_type_mode' => 'exc',
					'email_type_W' => '0',
					'email_type_N' => '1',
					'email_type_S' => '1',
					'email_andor' => 'and',
					'email_fold_mode' => 'exc',
					'email_fold_A' => '0',
					'email_fold_C' => '0',
					'email_fold_P' => '1',
					'email_fold_I' => '0',
					'email_cont' => '0',
					);

		update_option('mitt_er_log', $rstr_log_opts);
		update_option('mitt_er_email', $rstr_email_opts);
?>
		<div id="message" class="updated fade">
			<p><strong>The Error Reporting Options have been set to the Default values</strong></p>
		</div>
<?php
	}
/* Update Options Section
* if action isn't set to "yes" clears mode options
* if type mode is not "inc" or "exc" clears type sub-options
* if folder mode is not "inc" or "exc" clears folder sub-options
* if either types or folders is not "inc" or "exc" clears andor
* if type is "inc" or "exc" and no type sub-options selected clears assoc. options
* if folder is "inc" or "exc" and no folder sub-options selected clears assoc. options
* if andor is needed but missing shows message otherwise updates values
*/ 
	if (isset($_POST['update_options']))
	{
		check_admin_referer('error-reporting-update-options_' . $ernonce, '_mitt_er_uo');
?>
		<div id="message" class="updated fade"><p><strong>
<?php
		$upd_log_opts = array();
		$upd_log_opts['log_action'] = $_POST['mitt_log_action'];
		$upd_log_opts['log_type_mode'] = ($upd_log_opts['log_action'] == '1') ? $_POST['mitt_log_type_mode'] : '';
		$upd_log_opts['log_type_W'] = ( ( ($upd_log_opts['log_type_mode'] == 'inc') || ($upd_log_opts['log_type_mode'] == 'exc') ) && (isset($_POST['mitt_log_type_W'])) ) ? $_POST['mitt_log_type_W'] : '0';
		$upd_log_opts['log_type_N'] = ( ( ($upd_log_opts['log_type_mode'] == 'inc') || ($upd_log_opts['log_type_mode'] == 'exc') ) && (isset($_POST['mitt_log_type_N'])) ) ? $_POST['mitt_log_type_N'] : '0';
		$upd_log_opts['log_type_S'] = ( ( ($upd_log_opts['log_type_mode'] == 'inc') || ($upd_log_opts['log_type_mode'] == 'exc') ) && (isset($_POST['mitt_log_type_S'])) ) ? $_POST['mitt_log_type_S'] : '0';
		$upd_log_opts['log_fold_mode'] = ($upd_log_opts['log_action'] == '1') ? $_POST['mitt_log_fold_mode'] : '';
		$upd_log_opts['log_fold_A'] = ( ( ($upd_log_opts['log_fold_mode'] == 'inc') || ($upd_log_opts['log_fold_mode'] == 'exc') ) && (isset($_POST['mitt_log_fold_A'])) ) ? $_POST['mitt_log_fold_A'] : '0';
		$upd_log_opts['log_fold_C'] = ( ( ($upd_log_opts['log_fold_mode'] == 'inc') || ($upd_log_opts['log_fold_mode'] == 'exc') ) && (isset($_POST['mitt_log_fold_C'])) ) ? $_POST['mitt_log_fold_C'] : '0';
		$upd_log_opts['log_fold_P'] = ( ( ($upd_log_opts['log_fold_mode'] == 'inc') || ($upd_log_opts['log_fold_mode'] == 'exc') ) && (isset($_POST['mitt_log_fold_P'])) ) ? $_POST['mitt_log_fold_P'] : '0';
		$upd_log_opts['log_fold_I'] = ( ( ($upd_log_opts['log_fold_mode'] == 'inc') || ($upd_log_opts['log_fold_mode'] == 'exc') ) && (isset($_POST['mitt_log_fold_I'])) ) ? $_POST['mitt_log_fold_I'] : '0';
		$upd_log_opts['log_andor'] = ( ( ($upd_log_opts['log_type_mode'] == 'inc') || ($upd_log_opts['log_type_mode'] == 'exc') ) && ( ($upd_log_opts['log_fold_mode'] == 'inc') || ($upd_log_opts['log_fold_mode'] == 'exc') ) ) ? $_POST['mitt_log_andor'] : '';
		if ( ($upd_log_opts['log_type_W'] != '1') && ($upd_log_opts['log_type_N'] != '1') && ($upd_log_opts['log_type_S'] != '1') && ($upd_log_opts['log_type_mode'] != 'all') )
		{
			$upd_log_opts['log_type_mode'] = '';
			$upd_log_opts['log_type_W'] = '0';
			$upd_log_opts['log_type_N'] = '0';
			$upd_log_opts['log_type_S'] = '0';
			$upd_log_opts['log_andor'] = '';
		}
		if ( ($upd_log_opts['log_fold_A'] != '1') && ($upd_log_opts['log_fold_C'] != '1') && ($upd_log_opts['log_fold_P'] != '1') && ($upd_log_opts['log_fold_I'] != '1') && ($upd_log_opts['log_fold_mode'] != 'all') )
		{
			$upd_log_opts['log_fold_mode'] = '';
			$upd_log_opts['log_fold_A'] = '0';
			$upd_log_opts['log_fold_C'] = '0';
			$upd_log_opts['log_fold_P'] = '0';
			$upd_log_opts['log_fold_I'] = '0';
			$upd_log_opts['log_andor'] = '';
		}
		$upd_log_opts['log_cont'] = ( ($upd_log_opts['log_action'] == '1') && isset($_POST['mitt_log_cont']) ) ? $_POST['mitt_log_cont'] : '0';
		$upd_log_opts['log_rpt'] = ( ($upd_log_opts['log_action'] == '1') && isset($_POST['mitt_log_rpt']) ) ? $_POST['mitt_log_rpt'] : '0';

		$upd_email_opts = array();
		$upd_email_opts['email_action'] = $_POST['mitt_email_action'];
		$upd_email_opts['email_type_mode'] = ($upd_email_opts['email_action'] == '1') ? $_POST['mitt_email_type_mode'] : '';
		$upd_email_opts['email_type_W'] = ( ( ($upd_email_opts['email_type_mode'] == 'inc') || ($upd_email_opts['email_type_mode'] == 'exc') ) && (isset($_POST['mitt_email_type_W'])) ) ? $_POST['mitt_email_type_W'] : '0';
		$upd_email_opts['email_type_N'] = ( ( ($upd_email_opts['email_type_mode'] == 'inc') || ($upd_email_opts['email_type_mode'] == 'exc') ) && (isset($_POST['mitt_email_type_N'])) ) ? $_POST['mitt_email_type_N'] : '0';
		$upd_email_opts['email_type_S'] = ( ( ($upd_email_opts['email_type_mode'] == 'inc') || ($upd_email_opts['email_type_mode'] == 'exc') ) && (isset($_POST['mitt_email_type_S'])) ) ? $_POST['mitt_email_type_S'] : '0';
		$upd_email_opts['email_fold_mode'] = ($upd_email_opts['email_action'] == '1') ? $_POST['mitt_email_fold_mode'] : '';
		$upd_email_opts['email_fold_A'] = ( ( ($upd_email_opts['email_fold_mode'] == 'inc') || ($upd_email_opts['email_fold_mode'] == 'exc') ) && (isset($_POST['mitt_email_fold_A'])) ) ? $_POST['mitt_email_fold_A'] : '0';
		$upd_email_opts['email_fold_C'] = ( ( ($upd_email_opts['email_fold_mode'] == 'inc') || ($upd_email_opts['email_fold_mode'] == 'exc') ) && (isset($_POST['mitt_email_fold_C'])) ) ? $_POST['mitt_email_fold_C'] : '0';
		$upd_email_opts['email_fold_P'] = ( ( ($upd_email_opts['email_fold_mode'] == 'inc') || ($upd_email_opts['email_fold_mode'] == 'exc') ) && (isset($_POST['mitt_email_fold_P'])) ) ? $_POST['mitt_email_fold_P'] : '0';
		$upd_email_opts['email_fold_I'] = ( ( ($upd_email_opts['email_fold_mode'] == 'inc') || ($upd_email_opts['email_fold_mode'] == 'exc') ) && (isset($_POST['mitt_email_fold_I'])) ) ? $_POST['mitt_email_fold_I'] : '0';
		$upd_email_opts['email_andor'] = ( ( ($upd_email_opts['email_type_mode'] == 'inc') || ($upd_email_opts['email_type_mode'] == 'exc') ) && ( ($upd_email_opts['email_fold_mode'] == 'inc') || ($upd_email_opts['email_fold_mode'] == 'exc') ) ) ? $_POST['mitt_email_andor'] : '';
		if ( ($upd_email_opts['email_type_W'] != '1') && ($upd_email_opts['email_type_N'] != '1') && ($upd_email_opts['email_type_S'] != '1') && ($upd_email_opts['email_type_mode'] != 'all') )
		{
			$upd_email_opts['email_type_mode'] = '';
			$upd_email_opts['email_type_W'] = '0';
			$upd_email_opts['email_type_N'] = '0';
			$upd_email_opts['email_type_S'] = '0';
			$upd_email_opts['email_andor'] = '';
		}
		if ( ($upd_email_opts['email_fold_A'] != '1') && ($upd_email_opts['email_fold_C'] != '1') && ($upd_email_opts['email_fold_P'] != '1') && ($upd_email_opts['email_fold_I'] != '1') && ($upd_email_opts['email_fold_mode'] != 'all') )
		{
			$upd_email_opts['email_fold_mode'] = '';
			$upd_email_opts['email_fold_A'] = '0';
			$upd_email_opts['email_fold_C'] = '0';
			$upd_email_opts['email_fold_P'] = '0';
			$upd_email_opts['email_fold_I'] = '0';
			$upd_email_opts['email_andor'] = '';
		}
		$upd_email_opts['email_cont'] = ( ($upd_email_opts['email_action'] == '1') && isset($_POST['mitt_email_cont']) ) ? $_POST['mitt_email_cont'] : '0';


		$upd_tz_opt = trim($_POST['mitt_er_tz']);

		if ( function_exists('date_default_timezone_get') )
		{
/* range of 2 to 40 should handle all valid timezone identifiers */
			$tz_regex = '/^[\w-\/\+]{2,40}$/';
			
			if ( preg_match($tz_regex, $upd_tz_opt) )
			{
				$upd_tz_opt = $upd_tz_opt;
			}
			else
			{
				$upd_tz_opt = date_default_timezone_get();
			}
		}
		else
		{
			$upd_tz_opt = 'This_option_requires_PHP_ver5+';
		}

		if ( ( ( ($upd_log_opts['log_type_mode'] == 'inc' ) || ($upd_log_opts['log_type_mode'] == 'exc' ) ) &&  ( ($upd_log_opts['log_fold_mode'] == 'inc' ) || ($upd_log_opts['log_fold_mode'] == 'exc' ) ) ) && ($upd_log_opts['log_andor'] == '') )
		{
			echo "You forgot to select either AND or OR for the Log Options.<br />New option settings have NOT been updated.";
		}
		if  ( ( ( ($upd_email_opts['email_type_mode'] == 'inc' ) || ($upd_email_opts['email_type_mode'] == 'exc' ) ) &&  ( ($upd_email_opts['email_fold_mode'] == 'inc' ) || ($upd_email_opts['email_fold_mode'] == 'exc' ) ) ) && ($upd_email_opts['email_andor'] == '') )
		{
			echo "You forgot to select either AND or OR for the Email Options.<br />New option settings have NOT been updated.";
		}
		else
		{
			update_option('mitt_er_log', $upd_log_opts);
			update_option('mitt_er_email', $upd_email_opts);
			update_option('mitt_er_tz', $upd_tz_opt);

			echo "Error Reporting Options have been Updated<br />Please double check the settings before leaving.";
		}
?>
		</strong></p></div>
<?php
	}
/* Delete Log Files Section */
	if (isset($_POST['delete_log_files']))
	{
		check_admin_referer('error-reporting-delete-log-files_' . $ernonce, '_mitt_er_dlf');
?>
		<div id="message" class="updated fade"><p><strong>
<?php
		if(is_array($_POST['prune_logs']))
		{
			$prune_arr = $_POST['prune_logs'];
/*
* this somewhat over-restrictive regex ensures that no malicious POST
* requests will be able to delete files other than log files.
*/
			$guardian = '/^[\.]{1,2}\/er-logs\/ER-[0-3][0-9]-[ADFJMNOS][abceglnoprtuvy]{2}-20[0-9]{2}\.log$/';
			foreach($prune_arr as $prunefile)
			{
				if(preg_match($guardian, $prunefile))
				{
					unlink($prunefile);
					echo $prunefile . "<br />";
				}
			}
		}

		echo ". . . . . . . . . . . . . . . . . . . . . . . .&nbsp;&nbsp;&nbsp;Deleted";
?>
		</strong></p></div>
<?php
	}

/* Update Cron Delete Section */
	if (isset($_POST['mitt_sched_cron']))
	{
		check_admin_referer('error-reporting-cron-schedule_' . $ernonce, '_mitt_er_cs');

		if ( isset($_POST['er_do_cron']) && ( ($_POST['er_do_cron'] == 'never') || ($_POST['er_do_cron'] == 'month') || ($_POST['er_do_cron'] == 'week') ) ) {
			update_option('er_do_cron_del', $_POST['er_do_cron']);
?>
		<div id="message" class="updated fade"><p><strong>The Auto Delete Settings have been changed.</strong></p></div>
<?php
		}
	}

/* Toggle Permissions Section */
	if (isset($_POST['toggle_permissions']))
	{
?>
		<div id="message" class="updated fade">
			<p><strong>The Folder and File Permissions have changed.</strong></p>
		</div>
<?php
		check_admin_referer('error-reporting-toggle-permissions_' . $ernonce, '_mitt_er_tp');
		if( ($_POST['mitt_perms'] == 'secure') || ($_POST['mitt_perms'] == '') )
		{
			$main_dir = "../er-logs";
			if (is_dir($main_dir))
			{
				chmod($main_dir, 0705);
				exec("chmod 604 " . $main_dir . '/*.log');
			}

			$admin_dir = "./er-logs";
			if (is_dir($admin_dir))
			{
				chmod($admin_dir, 0705);
				exec("chmod 604 " . $admin_dir . '/*.log');
			}
			clearstatcache();
			$mitt_perms = 'NOT secure';
			remove_action( 'shutdown', 'wp_ob_end_flush_all', 1);
		}
		else
		{
			$main_dir = "../er-logs";
			if (is_dir($main_dir))
			{
				exec("chmod 600 " . $main_dir . '/*.log');
				chmod($main_dir, 0700);
			}			

			$admin_dir = "./er-logs";
			if (is_dir($admin_dir))
			{
				exec("chmod 600 " . $admin_dir . '/*.log');
				chmod($admin_dir, 0700);
			}
			clearstatcache();
			$mitt_perms = 'secure';
			add_action( 'shutdown', 'wp_ob_end_flush_all', 1);
		}
	}
/* get current option values to display in page forms */
	$log_options = get_option('mitt_er_log');
	$mitt_log_action = $log_options['log_action'];
	$mitt_log_type_mode = $log_options['log_type_mode'];
	$mitt_log_type_W = $log_options['log_type_W'];
	$mitt_log_type_N = $log_options['log_type_N'];
	$mitt_log_type_S = $log_options['log_type_S'];
	$mitt_log_andor = $log_options['log_andor'];
	$mitt_log_fold_mode = $log_options['log_fold_mode'];
	$mitt_log_fold_A = $log_options['log_fold_A'];
	$mitt_log_fold_C = $log_options['log_fold_C'];
	$mitt_log_fold_P = $log_options['log_fold_P'];
	$mitt_log_fold_I = $log_options['log_fold_I'];
	$mitt_log_cont = $log_options['log_cont'];
	$mitt_log_rpt = $log_options['log_rpt'];

	$email_options = get_option('mitt_er_email');
	$mitt_email_action = $email_options['email_action'];
	$mitt_email_type_mode = $email_options['email_type_mode'];
	$mitt_email_type_W = $email_options['email_type_W'];
	$mitt_email_type_N = $email_options['email_type_N'];
	$mitt_email_type_S = $email_options['email_type_S'];
	$mitt_email_andor = $email_options['email_andor'];
	$mitt_email_fold_mode = $email_options['email_fold_mode'];
	$mitt_email_fold_A = $email_options['email_fold_A'];
	$mitt_email_fold_C = $email_options['email_fold_C'];
	$mitt_email_fold_P = $email_options['email_fold_P'];
	$mitt_email_fold_I = $email_options['email_fold_I'];
	$mitt_email_cont = $email_options['email_cont'];

	$mitt_er_tz = get_option('mitt_er_tz');

	$mitt_er_do_cron_del = get_option('er_do_cron_del');

?>
	<div class="wrap">
	<h2>Error Reporting</h2>

<!-- /* SHOW EXISTING PING ERRORS SECTION */ -->
	<h3>Ping Errors</h3>
		<div class="postbox">
			<h4>Saved Ping Errors</h4>
			<div class="inside">
<?php
	$er_ping_errs = get_option('mitt_er_ping_errors');
	$num_p_errs = count($er_ping_errs);

	function mitt_er_pe_prep($error_line)
	{
		$error_line = rtrim($error_line, '<br />');
		$error_line = str_replace('<br />', ' ~ ', $error_line);
		return $error_line;
	}

	if ($num_p_errs === 0)
	{
		echo '<p>There are no recorded ping errors</p>';
	}
	else
	{
		$er_ping_errs = array_map('mitt_er_pe_prep', $er_ping_errs);
		echo '<ul>';
		echo '<li>' . current($er_ping_errs) . '</li>';

		for ($pe = 1; $pe < $num_p_errs; $pe++)
		{
			echo '<li>' . next($er_ping_errs) . '</li>';
		}
		echo '</ul>';
	}
?>
			</div>
		</div>

		<div class="postbox">
			<h4>Ping Error Setting</h4>
			<div class="inside">
				<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>"><?php
	if ( function_exists('wp_nonce_field') )
		wp_nonce_field('er-pe-update_' . $ernonce, '_mitt_er_peu');
	settings_fields('mitt-er-poptions');
?>
		<label for="mitt_er_pe_len">Number of Ping Errors to Save: </label>
			<input id="mitt_er_pe_len" name="mitt_er_pe_len" type="text" style="width: 2em; text-align: right;" value="<?php echo get_option('mitt_er_ping_error_length'); ?>" />
		<p>Setting a value lower than the existing number of saved ping errors will truncate the saved ping errors to that amount.<br />
		Maximum number is <?php echo $max_ping_saves; ?>.</p>
		<p class="submit">
			<input type="submit" name="mitt_update_erp" value="<?php _e('Save Changes') ?>" class="button" />
		</p>
				</form>
			</div>
		</div>

<!-- /* REMOVE ALL PING ERRORS SECTION */ -->
		<div class="postbox">
			<h4>Remove all Saved Ping Errors</h4>
			<div class="inside">
				<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<?php
	if ( function_exists('wp_nonce_field') )
		wp_nonce_field('er-pe-remove-errors_' . $ernonce, '_mitt_er_pere');
?>
		<p>This will remove all of the saved Ping Errors</p>
		<p class="submit">
			<input type="submit" id="mitt_er_rem_pe" name="mitt_er_rem_pe" value="<?php _e('Remove Saved Ping Errors') ?>" class="button" />
		</p>
				</form>
			</div>
		</div>

<!-- /* LOG FILES SECTION */ -->
	<h3>Log Files</h3>
	<p>To view a file's contents, click on it's link. To save the file either right-click save-as or open the file and save using your browser's file menu. To Delete a file check it's checkbox and Submit.</p>
	<p>To access a file, the folder and file permissions must be correct. Be sure to reset them after you're done to prevent outside access to the log files and re-enable the 'shutdown' - (when PHP finishes executing script) - output buffer flush.<br />
<?php
	$mitt_perms = (!empty($mitt_perms)) ? $mitt_perms : '';
	if ( ($mitt_perms == 'secure') || ($mitt_perms == 'NOT secure') )
	{
		$obf = '';
		if ($mitt_perms == 'secure')
		{
			$obf = 'enabled';
		}
		else if ($mitt_perms == 'NOT secure')
		{
			$obf = 'disabled';
		}
		echo "The current Permission levels are <strong>" . $mitt_perms .  "</strong> and the 'shutdown' output buffer flush is <strong>" . $obf . "</strong>";
	}
?>
	</p>
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	<input type="hidden" name="mitt_perms" value="<?php echo $mitt_perms; ?>" />
<?php
	if ( function_exists('wp_nonce_field') )
		wp_nonce_field('error-reporting-toggle-permissions_' . $ernonce, '_mitt_er_tp');
?>
	<div class="submit">
		<input type="submit" name="toggle_permissions" value="Toggle Permissions" />
	</div>
	</form>

	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<?php
	if ( function_exists('wp_nonce_field') )
		wp_nonce_field('error-reporting-delete-log-files_' . $ernonce, '_mitt_er_dlf');

	echo "Log Files in /er-logs folder:<br />";
	$main_dir = "../er-logs";
	$logfiles_arr = array();
	if (is_dir($main_dir))
	{
		$open_dir = opendir($main_dir);
		$incrementer = 0;
		while (false !== ($file = readdir($open_dir)))
		{
			if ($file != "." && $file != "..")
			{
  			      $logfiles_arr[$incrementer] = $file;
				$incrementer += 1;
			}
		}
		closedir($open_dir);
	}
	natsort($logfiles_arr);

	foreach($logfiles_arr as $logfile)
	{
		echo "<input name='prune_logs[]' type='checkbox' value='../er-logs/" . $logfile . "' />&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "<a href='../er-logs/" . $logfile . "'>" . $logfile . "</a> (" . number_format(filesize('../er-logs/' . $logfile)) . " bytes) <br />";
	}
	if(empty($logfiles_arr))
	{
		echo "No Log files in folder<br />";
	}

	echo "<br />Log Files in /wp-admin/er-logs folder:<br />";
	$admin_dir = "./er-logs";
	$logfiles_arr2 = array();
	if (is_dir($admin_dir))
	{
		$open_dir2 = opendir($admin_dir);
		$incrementer2 = 0;
		while (false !== ($file2 = readdir($open_dir2)))
		{
			if ($file2 != "." && $file2 != "..")
			{
  			      $logfiles_arr2[$incrementer2] = $file2;
				$incrementer2 += 1;
			}
		}
		closedir($open_dir2);
	}
	natsort($logfiles_arr2);

	foreach($logfiles_arr2 as $logfile2)
	{
		echo "<input name='prune_logs[]' type='checkbox' value='./er-logs/" . $logfile2 . "' />&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "<a href='./er-logs/" . $logfile2 . "'>" . $logfile2 . "</a> (" . number_format(filesize('./er-logs/' . $logfile2)) . " bytes) <br />";
	}
	if(empty($logfiles_arr2))
	{
		echo "No Log files in folder<br />";
	}
	clearstatcache();
?>
<!-- javascript select all modified from wp-db-backup 2.1.5 plugin by Austin Matzko http://www.ilfilosofo.com/blog/ -->
			<script type="text/javascript">
			//<![CDATA[
				var selectAllLogs = function() {};
				(function(b){
					var n = function(c) {
						var p = document.getElementsByTagName("input");
						for(var i=0; i<p.length; i++)
							if( 'prune_logs[]' == p[i].getAttribute('name') ) p[i].checked = c;
					}
					b.a = function() { n(true) }
					b.n = function() { n(false) }
					document.write('<p><a href="javascript:void(0)" onclick="selectAllLogs.a()">Select all</a> / <a href="javascript:void(0)" onclick="selectAllLogs.n()">Deselect all</a></p>');
				})(selectAllLogs)
			//]]>
			</script>
	<div class="submit">
		<input type="submit" name="delete_log_files" value="Delete selected Log files" />
	</div>
	</form>

<!-- /* CRON LOG FILE DELETE SECTION */ -->
	<div class="postbox">
		<h4>Auto Delete Old Log Files</h4>
		<div class="inside">
			<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<?php
	if ( function_exists('wp_nonce_field') )
		wp_nonce_field('error-reporting-cron-schedule_' . $ernonce, '_mitt_er_cs');
?>

		<p>Using this option will routinely remove older log files without requiring your attention.<br />
		Note: A "Month" is 31 days.</p>

<input id="er_do_cron_no" name="er_do_cron" type="radio" value='never' <?php if ($mitt_er_do_cron_del == 'never') {echo 'checked="checked"';} ?> /> <label for="er_do_cron_no">No, Don't Auto Delete any log files</label><br />

<input id="er_do_cron_month" name="er_do_cron" type="radio" value='month' <?php if ($mitt_er_do_cron_del == 'month') {echo 'checked="checked"';} ?> /> <label for="er_do_cron_month">Yes, Delete log files Older than a Month</label><br />

<input id="er_do_cron_week" name="er_do_cron" type="radio" value='week' <?php if ($mitt_er_do_cron_del == 'week') {echo 'checked="checked"';} ?> /> <label for="er_do_cron_week">Yes, Delete log files Older than a Week</label><br /><br />

		<p class="submit">
			<input type="submit" name="mitt_sched_cron" value="<?php _e('Schedule Auto Delete') ?>" class="button" />
		</p>
			</form>
		</div>
	</div>

<!-- /* CONFIGURATION SECTION */ -->
	<h3>Configuration</h3>
	<p>Note* Because the SimplePie file throws too many errors, i.e. calling non-static methods statically and passing new by reference, which can cause Internal Server errors, E_STRICT errors from the class-simplepie.php file are not included in any reports.</p>
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<?php
	if ( function_exists('wp_nonce_field') )
		wp_nonce_field('error-reporting-update-options_' . $ernonce, '_mitt_er_uo');
?>
<!-- /* Log Options Fieldset */ -->
	<fieldset class="er_options"> 
	<legend>Log Options</legend>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">Log Errors?</th><td valign="top">
      	<input id="er_la_yes" name="mitt_log_action" type="radio" value='1' <?php if ($mitt_log_action == '1') {echo 'checked="checked"';} ?> /> <label for="er_la_yes">Yes, Log Errors</label>
	</td><td valign="top">
      	<input id="er_la_no" name="mitt_log_action" type="radio" value='0' <?php if ($mitt_log_action == '0') {echo 'checked="checked"';} ?> /> <label for="er_la_no">No, Don't Log Errors</label>
	</td></tr>
	</table>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">Include All Error Types?</th><td valign="top">
      	<input id="er_ltm_all" name="mitt_log_type_mode" type="radio" value='all' <?php if ($mitt_log_type_mode == 'all') {echo 'checked="checked"';} ?> /> <label for="er_ltm_all">Yes, All Error Types</label>
	</td><td valign="top">
      	<input id="er_ltm_inc" name="mitt_log_type_mode" type="radio" value='inc' <?php if ($mitt_log_type_mode == 'inc') {echo 'checked="checked"';} ?> /> <label for="er_ltm_inc">No, Only Include the Error Types indicated below</label>
	</td><td valign="top">
      	<input id="er_ltm_exc" name="mitt_log_type_mode" type="radio" value='exc' <?php if ($mitt_log_type_mode == 'exc') {echo 'checked="checked"';} ?> /> <label for="er_ltm_exc">No, Exclude the Error Types indicated below</label>
	</td></tr>

	<tr><th width="45%" valign="top" align="right" scope="row">Error Types:</th><td valign="top">
      	<input id="er_lt_w" name="mitt_log_type_W" type="checkbox" value='1' <?php if ($mitt_log_type_W == '1') {echo 'checked="checked"';} ?> /><label for="er_lt_w">E_WARNING</label></td><td valign="top">
      	<input id="er_lt_n" name="mitt_log_type_N" type="checkbox" value='1' <?php if ($mitt_log_type_N == '1') {echo 'checked="checked"';} ?> /><label for="er_lt_n">E_NOTICE</label></td><td valign="top">
      	<input id="er_lt_s" name="mitt_log_type_S" type="checkbox" value='1' <?php if ($mitt_log_type_S == '1') {echo 'checked="checked"';} ?> /><label for="er_lt_s">E_STRICT</label>
	</td></tr>
	</table>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">And or Or?</th><td valign="top">
      	<input id="er_l_a" name="mitt_log_andor" type="radio" value='and' <?php if ($mitt_log_andor == 'and') {echo 'checked="checked"';} ?> /><label for="er_l_a">AND &#38;&#38;</label>
	</td><td valign="top">
      	<input id="er_l_o" name="mitt_log_andor" type="radio" value='or' <?php if ($mitt_log_andor == 'or') {echo 'checked="checked"';} ?> /><label for="er_l_o">OR ||</label>
	</td></tr>
	</table>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">Include All Folders?</th><td valign="top">
      	<input id="er_lfm_all" name="mitt_log_fold_mode" type="radio" value='all' <?php if ($mitt_log_fold_mode == 'all') {echo 'checked="checked"';} ?> /><label for="er_lfm_all">Yes, All Folders</label>
	</td><td valign="top">
      	<input id="er_lfm_inc" name="mitt_log_fold_mode" type="radio" value='inc' <?php if ($mitt_log_fold_mode == 'inc') {echo 'checked="checked"';} ?> /><label for="er_lfm_inc">No, Only Include the Folders indicated below</label>
	</td><td valign="top">
      	<input id="er_lfm_exc" name="mitt_log_fold_mode" type="radio" value='exc' <?php if ($mitt_log_fold_mode == 'exc') {echo 'checked="checked"';} ?> /><label for="er_lfm_exc">No, Exclude the Folders indicated below</label>
	</td><td>
	</td></tr>

	<tr><th width="45%" valign="top" align="right" scope="row">Folders:</th><td valign="top">
      	<input id="er_lf_a" name="mitt_log_fold_A" type="checkbox" value='1' <?php if ($mitt_log_fold_A == '1') {echo 'checked="checked"';} ?> /><label for="er_lf_a">wp-admin</label></td><td valign="top">
      	<input id="er_lf_c" name="mitt_log_fold_C" type="checkbox" value='1' <?php if ($mitt_log_fold_C == '1') {echo 'checked="checked"';} ?> /><label for="er_lf_c">wp-content</label></td><td valign="top">
      	<input id="er_lf_p" name="mitt_log_fold_P" type="checkbox" value='1' <?php if ($mitt_log_fold_P == '1') {echo 'checked="checked"';} ?> /><label for="er_lf_p">plugins</label></td><td valign="top">
      	<input id="er_lf_i" name="mitt_log_fold_I" type="checkbox" value='1' <?php if ($mitt_log_fold_I == '1') {echo 'checked="checked"';} ?> /><label for="er_lf_i">wp-includes</label>
	</td></tr>
	</table>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">Include Context?</th><td valign="top" colspan="2">
      	<input id="er_lc_yes" name="mitt_log_cont" type="radio" value='1' <?php if ($mitt_log_cont == '1') {echo 'checked="checked"';} ?> /> <label for="er_lc_yes">Yes, include context</label>
	</td><td valign="top" colspan="2">
      	<input id="er_lc_no" name="mitt_log_cont" type="radio" value='0' <?php if ($mitt_log_cont == '0') {echo 'checked="checked"';} ?> /> <label for="er_lc_no">No, Don't include context</label>
	</td></tr>

	<tr><th width="45%" valign="top" align="right" scope="row">Log Repeat Errors?</th><td valign="top" colspan="2">
      	<input id="er_lr_yes" name="mitt_log_rpt" type="radio" value='1' <?php if ($mitt_log_rpt == '1') {echo 'checked="checked"';} ?> /> <label for="er_lr_yes">Yes, Log repeat errors</label>
	</td><td valign="top" colspan="2">
      	<input id="er_lr_no" name="mitt_log_rpt" type="radio" value='0' <?php if ($mitt_log_rpt == '0') {echo 'checked="checked"';} ?> /> <label for="er_lr_no">No, Don't Log repeat errors</label>
	</td></tr>

	</table>
	</fieldset>	

<!-- /* Timezone Option Fieldset */ -->
	<fieldset class="er_options"> 
	<legend>Timezone Option</legend>

	<p>Please see the PHP documentation - Appendix I - at <a href='http://www.php.net/manual/en/timezones.php'>http://www.php.net/manual/en/timezones.php</a> for other timezone identifiers.<br />*NOTE* This option requires PHP version 5+</p>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">Timezone identifier</th>
	<td valign="top">
      	<input name="mitt_er_tz" type="text" value="<?php echo $mitt_er_tz; ?>" size="45" />
	</td>
	</tr>
	</table>

	</fieldset>

<!-- /* Email Options Fieldset */ -->
	<fieldset class="er_options" id="er_email_opts"> 
	<legend>Email Options</legend>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">Email Errors?</th><td valign="top">
      	<input id="er_ea_yes" name="mitt_email_action" type="radio" value='1' <?php if ($mitt_email_action == '1') {echo 'checked="checked"';} ?> /> <label for="er_ea_yes">Yes, Email Errors</label>
	</td><td valign="top">
      	<input id="er_ea_no" name="mitt_email_action" type="radio" value='0' <?php if ($mitt_email_action == '0') {echo 'checked="checked"';} ?> /> <label for="er_ea_no">No, Don't Email Errors</label>
	</td></tr>
	</table>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">Include All Error Types?</th><td valign="top">
      	<input id="er_etm_all" name="mitt_email_type_mode" type="radio" value='all' <?php if ($mitt_email_type_mode == 'all') {echo 'checked="checked"';} ?> /> <label for="er_etm_all">Yes, All Error Types</label>
	</td><td valign="top">
      	<input id="er_etm_inc" name="mitt_email_type_mode" type="radio" value='inc' <?php if ($mitt_email_type_mode == 'inc') {echo 'checked="checked"';} ?> /> <label for="er_etm_inc">No, Only Include the Error Types indicated below</label>
	</td><td valign="top">
      	<input id="er_etm_exc" name="mitt_email_type_mode" type="radio" value='exc' <?php if ($mitt_email_type_mode == 'exc') {echo 'checked="checked"';} ?> /> <label for="er_etm_exc">No, Exclude the Error Types indicated below</label>
	</td></tr>

	<tr><th width="45%" valign="top" align="right" scope="row">Error Types:</th><td valign="top">
      	<input id="er_et_w" name="mitt_email_type_W" type="checkbox" value='1' <?php if ($mitt_email_type_W == '1') {echo 'checked="checked"';} ?> /><label for="er_et_w">E_WARNING</label></td><td valign="top">
      	<input id="er_et_n" name="mitt_email_type_N" type="checkbox" value='1' <?php if ($mitt_email_type_N == '1') {echo 'checked="checked"';} ?> /><label for="er_et_n">E_NOTICE</label></td><td valign="top">
      	<input id="er_et_s" name="mitt_email_type_S" type="checkbox" value='1' <?php if ($mitt_email_type_S == '1') {echo 'checked="checked"';} ?> /><label for="er_et_s">E_STRICT</label>
	</td></tr>
	</table>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">And or Or?</th><td valign="top">
      	<input id="er_e_a" name="mitt_email_andor" type="radio" value='and' <?php if ($mitt_email_andor == 'and') {echo 'checked="checked"';} ?> /> <label for="er_e_a">AND &#38;&#38;</label>
	</td><td valign="top">
      	<input id="er_e_o" name="mitt_email_andor" type="radio" value='or' <?php if ($mitt_email_andor == 'or') {echo 'checked="checked"';} ?> /> <label for="er_e_o">OR ||</label>
	</td></tr>
	</table>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">Include All Folders?</th><td valign="top">
      	<input id="er_efm_all" name="mitt_email_fold_mode" type="radio" value='all' <?php if ($mitt_email_fold_mode == 'all') {echo 'checked="checked"';} ?> /> <label for="er_efm_all">Yes, All Folders</label>
	</td><td valign="top">
      	<input id="er_efm_inc" name="mitt_email_fold_mode" type="radio" value='inc' <?php if ($mitt_email_fold_mode == 'inc') {echo 'checked="checked"';} ?> /> <label for="er_efm_inc">No, Only Include the Folders indicated below</label>
	</td><td valign="top">
      	<input id="er_efm_exc" name="mitt_email_fold_mode" type="radio" value='exc' <?php if ($mitt_email_fold_mode == 'exc') {echo 'checked="checked"';} ?> /> <label for="er_efm_exc">No, Exclude the Folders indicated below</label>
	</td><td>
	</td></tr>

	<tr><th width="45%" valign="top" align="right" scope="row">Folders:</th><td valign="top">
      	<input id="er_ef_a" name="mitt_email_fold_A" type="checkbox" value='1' <?php if ($mitt_email_fold_A == '1') {echo 'checked="checked"';} ?> /><label for="er_ef_a">wp-admin</label></td><td valign="top">
      	<input id="er_ef_c" name="mitt_email_fold_C" type="checkbox" value='1' <?php if ($mitt_email_fold_C == '1') {echo 'checked="checked"';} ?> /><label for="er_ef_c">wp-content</label></td><td valign="top">
      	<input id="er_ef_p" name="mitt_email_fold_P" type="checkbox" value='1' <?php if ($mitt_email_fold_P == '1') {echo 'checked="checked"';} ?> /><label for="er_ef_p">plugins</label></td><td valign="top">
      	<input id="er_ef_i" name="mitt_email_fold_I" type="checkbox" value='1' <?php if ($mitt_email_fold_I == '1') {echo 'checked="checked"';} ?> /><label for="er_ef_i">wp-includes</label>
	</td></tr>
	</table>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">Include Context?</th><td valign="top" colspan="2">
      	<input id="er_ec_yes" name="mitt_email_cont" type="radio" value='1' <?php if ($mitt_email_cont == '1') {echo 'checked="checked"';} ?> /> <label for="er_ec_yes">Yes, include context</label>
	</td><td valign="top" colspan="2">
      	<input id="er_ec_no" name="mitt_email_cont" type="radio" value='0' <?php if ($mitt_email_cont == '0') {echo 'checked="checked"';} ?> /> <label for="er_ec_no">No, Don't include context</label>
	</td></tr>

	</table>
	</fieldset>

	<div class="submit">
		<input type="submit" name="update_options" value="<?php _e('Update options'); ?>" />
	</div>
	</form>
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<?php
	if ( function_exists('wp_nonce_field') )
		wp_nonce_field('error-reporting-clear-options_' . $ernonce, '_mitt_er_co');
?>
	<div class="submit">
		<input type="submit" name="clear_options" value="Clear Options" />
	</div>
	</form>
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<?php
	if ( function_exists('wp_nonce_field') )
		wp_nonce_field('error-reporting-restore-options_' . $ernonce, '_mitt_er_ro');
?>
	<div class="submit">
		<input type="submit" name="restore_options" value="Restore Option Defaults" />
		<p>Note: The Email Options default is to send notification of non-plugin folder E_WARNING errors.</p>
	</div>
	</form>

<!-- /* OPTIONS SETTINGS LOGIC SECTION - EXAMPLE TABLES */ -->
	<h3>Option Settings Logic</h3>
	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr>
	<td valign="top">

<table><tr><td>

<table class="er_logic">
<caption>All Types - Inc Admin</caption>
<tr>
<td></td>	<td>Of</td>	<td>A</td>	<td>C</td>	<td>P</td>	<td>I</td>
</tr>
<tr>
<td>Oe</td>	<td></td>	<td>+</td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>W</td>	<td></td>	<td>+</td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>N</td>	<td></td>	<td>+</td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>S</td>	<td></td>	<td>+</td>	<td></td>	<td></td>	<td></td>
</tr>
</table>

</td><td>

<table class="er_logic">
<caption>All Types - Exc Admin</caption>
<tr>
<td></td>	<td>Of</td>	<td>A</td>	<td>C</td>	<td>P</td>	<td>I</td>
</tr>
<tr>
<td>Oe</td>	<td>+</td>	<td></td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>W</td>	<td>+</td>	<td></td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>N</td>	<td>+</td>	<td></td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>S</td>	<td>+</td>	<td></td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
</table>

</td><td>

<table class="er_logic">
<caption>Inc Warning - All folders</caption>
<tr>
<td></td>	<td>Of</td>	<td>A</td>	<td>C</td>	<td>P</td>	<td>I</td>
</tr>
<tr>
<td>Oe</td>	<td></td>	<td></td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>W</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>N</td>	<td></td>	<td></td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>S</td>	<td></td>	<td></td>	<td></td>	<td></td>	<td></td>
</tr>
</table>

</td><td>

<table class="er_logic">
<caption>Exc Warning - All Folders</caption>
<tr>
<td></td>	<td>Of</td>	<td>A</td>	<td>C</td>	<td>P</td>	<td>I</td>
</tr>
<tr>
<td>Oe</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>W</td>	<td></td>	<td></td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>N</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>S</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
</table>

</td></tr><tr><td>

<table class="er_logic">
<caption>Inc Warning AND Inc Admin</caption>
<tr>
<td></td>	<td>Of</td>	<td>A</td>	<td>C</td>	<td>P</td>	<td>I</td>
</tr>
<tr>
<td>Oe</td>	<td></td>	<td></td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>W</td>	<td></td>	<td>+</td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>N</td>	<td></td>	<td></td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>S</td>	<td></td>	<td></td>	<td></td>	<td></td>	<td></td>
</tr>
</table>

</td><td>

<table class="er_logic">
<caption>Inc Warning OR Inc Admin</caption>
<tr>
<td></td>	<td>Of</td>	<td>A</td>	<td>C</td>	<td>P</td>	<td>I</td>
</tr>
<tr>
<td>Oe</td>	<td></td>	<td>+</td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>W</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>N</td>	<td></td>	<td>+</td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>S</td>	<td></td>	<td>+</td>	<td></td>	<td></td>	<td></td>
</tr>
</table>

</td><td>

<table class="er_logic">
<caption>Exc Warning AND Exc Admin</caption>
<tr>
<td></td>	<td>Of</td>	<td>A</td>	<td>C</td>	<td>P</td>	<td>I</td>
</tr>
<tr>
<td>Oe</td>	<td>+</td>	<td></td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>W</td>	<td></td>	<td></td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>N</td>	<td>+</td>	<td></td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>S</td>	<td>+</td>	<td></td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
</table>

</td><td>

<table class="er_logic">
<caption>Exc Warning OR Exc Admin</caption>
<tr>
<td></td>	<td>Of</td>	<td>A</td>	<td>C</td>	<td>P</td>	<td>I</td>
</tr>
<tr>
<td>Oe</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>W</td>	<td>+</td>	<td></td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>N</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>S</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
</table>

</td></tr><tr><td>

<table class="er_logic">
<caption>Inc Warning AND Exc Admin</caption>
<tr>
<td></td>	<td>Of</td>	<td>A</td>	<td>C</td>	<td>P</td>	<td>I</td>
</tr>
<tr>
<td>Oe</td>	<td></td>	<td></td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>W</td>	<td>+</td>	<td></td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>N</td>	<td></td>	<td></td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>S</td>	<td></td>	<td></td>	<td></td>	<td></td>	<td></td>
</tr>
</table>

</td><td>

<table class="er_logic">
<caption>Inc Warning OR Exc Admin</caption>
<tr>
<td></td>	<td>Of</td>	<td>A</td>	<td>C</td>	<td>P</td>	<td>I</td>
</tr>
<tr>
<td>Oe</td>	<td>+</td>	<td></td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>W</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>N</td>	<td>+</td>	<td></td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>S</td>	<td>+</td>	<td></td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
</table>

</td><td>

<table class="er_logic">
<caption>Exc Warning AND Inc Admin</caption>
<tr>
<td></td>	<td>Of</td>	<td>A</td>	<td>C</td>	<td>P</td>	<td>I</td>
</tr>
<tr>
<td>Oe</td>	<td></td>	<td>+</td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>W</td>	<td></td>	<td></td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>N</td>	<td></td>	<td>+</td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>S</td>	<td></td>	<td>+</td>	<td></td>	<td></td>	<td></td>
</tr>
</table>

</td><td>

<table class="er_logic">
<caption>Exc Warning OR Inc Admin</caption>
<tr>
<td></td>	<td>Of</td>	<td>A</td>	<td>C</td>	<td>P</td>	<td>I</td>
</tr>
<tr>
<td>Oe</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>W</td>	<td></td>	<td>+</td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>N</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>S</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
</table>

</td></tr><tr><td>

<table class="er_logic">
<caption>Default Log Options - Exc N,S OR Exc Pl</caption>
<tr>
<td></td>	<td>Of</td>	<td>A</td>	<td>C</td>	<td>P</td>	<td>I</td>
</tr>
<tr>
<td>Oe</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>W</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td>+</td>
</tr>
<tr>
<td>N</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td></td>	<td>+</td>
</tr>
<tr>
<td>S</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td></td>	<td>+</td>
</tr>
</table>

</td><td>

<table class="er_logic">
<caption>Default Email Options - Exc N,S AND Exc Pl</caption>
<tr>
<td></td>	<td>Of</td>	<td>A</td>	<td>C</td>	<td>P</td>	<td>I</td>
</tr>
<tr>
<td>Oe</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td></td>	<td>+</td>
</tr>
<tr>
<td>W</td>	<td>+</td>	<td>+</td>	<td>+</td>	<td></td>	<td>+</td>
</tr>
<tr>
<td>N</td>	<td></td>	<td></td>	<td></td>	<td></td>	<td></td>
</tr>
<tr>
<td>S</td>	<td></td>	<td></td>	<td></td>	<td></td>	<td></td>
</tr>
</table>

</td><td colspan="2">

<ul id="er_key">
<li id="er_key_title">Key:</li>
<li>Of:&nbsp;&nbsp;Other folders</li>
<li>A:&nbsp;&nbsp;&nbsp;wp-admin folder</li>
<li>C:&nbsp;&nbsp;&nbsp;wp-content folder</li>
<li>P:&nbsp;&nbsp;&nbsp;plugin folder</li>
<li>I:&nbsp;&nbsp;&nbsp;wp-include folder</li>
<li>Oe:&nbsp;&nbsp;Other errors (eg. "USER" errors)</li>
<li>W:&nbsp;&nbsp;&nbsp;E_WARNING</li>
<li>N:&nbsp;&nbsp;&nbsp;E_NOTICE</li>
<li>S:&nbsp;&nbsp;&nbsp;E_STRICT</li>
</ul>

</td></tr></table>
	</td>
	</tr>
<!-- add a "top 4 settings" row here when known -->
	</table>

<!-- /* PLUGIN INFORMATION SECTION */ -->
	<h3>Plugin Information</h3>
	<ul>
	<li><span class="er_pi_head">Ping Errors</span><br />
If you do not want to see the widget on your dashboard, please go to dashboard's "Screen Options" and deselect "Ping Errors"<br />
* Because a ping fails once, or even a few times, does not necessarily mean that it should be removed from your ping list. However, if one is repeatedly failing over a long period of time, it justifies investigation and possible removal.<br />
The Ping Error feature is also available as a stand-alone plugin <a href='http://www.mittineague.com/dev/pw.php'>Ping Watcher</a><br />
The two are not compatible and both can not be activated at the same time.</li>

	<li><span class="er_pi_head">Log Error Reporting</span><br />
Depending on where an error occurs, it will be logged to a er-logs folder that's either under the blog's installation folder, or under the wp-admin folder. New files are created for each day with names having the format "ER-dd-Mmm-yyyy.log"<br />
eg. ER-05-Mar-2007.log</li>

	<li><span class="er_pi_head">Auto Delete Old Log Files</span><br />
The auto delete uses core WordPress CRON and as such is not real CRON but occurs when your blog is visited. Once per day the auto delete can remove files older than one week - 7 days, or one month - 31 days. So depending on your blog's activity, there may be some slight imprecision, but in general using the auto delete will prevent the error log folders from becoming bloated.</li>

	<li><span class="er_pi_head">Email Error Reporting</span><br />
Email Error Reporting does not have a "no repeat errors" setting. This means that the blog administrator's email address will get an email for every reported error, every time.<br />
For example, while testing this plugin using the default settings, 10 failed pings generated 190+ emails. It is strongly suggested that you "fine tune" your options using Log Error Reporting first (with the Repeat Error option set to yes to get an accurate indication of how many emails would have been sent) and get the errors down to a manageable amount before experimenting with the Email Error Reporting settings.<br />
Be very careful setting these options. You could end up flooding the inbox with hundreds, and <strong>more likely thousands</strong>, of emails in a relatively short amount of time.<br />
Note that the default Email Error Reporting settings are not enabled on install.</li>

	<li><span class="er_pi_head">Error Types Options</span><br />
The Error Reporting plugin can report E-WARNING, E_NOTICE and E_STRICT errors.<br />
Any E_RECOVERABLE_ERROR, and any "trigger" errors, E_USER_ERROR, E_USER_WARNING, and E_USER_NOTICE, will be reported if the option settings report "other error types" (see the "Option Settings Logic" section).<br />
If you want to ensure that all error types are reported, check "Yes, All Error Types".<br />
If you are interested in only certain error types "Include" them.<br />
Conversely, if you specifically do not want an error type, "Exclude" it.</li>

	<li><span class="er_pi_head">AND / OR Option</span><br />
The AND / OR option setting will only matter if neither "Types" nor "Folders" are set to "All".<br />
But, if both are either "Exclude" - "Include", it will make a big difference (see the "Option Settings Logic" section).</li>

	<li><span class="er_pi_head">Folder Options</span><br />
Errors in Files that are under the blog's install folder, and are not in the wp-admin, wp-content, or wp-includes folders, will be reported if the option settings report "other folders" (see the "Option Settings Logic" section).<br />
Note that the plugins folder is inside the wp-content folder. It is presented as a separate option to allow for more precise control.<br />
If the wp-content folder is included / excluded, so too will be the plugin folder with it. Likewise for any folders under the other folders.</li>

	<li><span class="er_pi_head">Context and Repeat Errors Options</span><br />
Including the Context of the error may provide some helpful information, but as it adds significantly to the size of the log file, it is by default not included.<br />
Likewise, there may be times when it would be helpful to see that a line of a file is causing the same error "X" amount of times, but because including Repeat errors would add significantly to the size of the log file, it too is by default not included.<br />
Note that there is no repeat error option for Email Error Reporting.<br />
Because each error will be sent as an individual email, the Context is not as crucial a setting here as it is for the Log options. So once you're sure you have the number of emails being sent under control, you may want to include it if that information will help you.</li>

	<li><span class="er_pi_head">Timezone Option</span><br />
This value is initially set to the server's timezone and controls what time is used.<br />*Note* Requires PHP version 5+</li>

	<li><span class="er_pi_head">Self Cleanup</span><br />
Deactivating this plugin will remove the "Number of Ping Errors to Save" setting.<br />
Uninstalling this plugin using the WordPress plugin list page's "delete" will remove the plugin's options from the wp-options table, including any saved ping errors, and all Log files and folders will be deleted.</li>
	</ul>

<!-- /* FURTHER INFORMATION SECTION */ -->
	<h3>Further Information</h3>
	<p>WANTED - Top 4 Settings<br />
With all the possible option setting configurations, it's impossible to show examples of them all, but if you find one you really like, let me know and it may get included in a future version's "Top 4 settings" row.</p>
	<p>WANTED - Bug Reports<br />
This plugin has been tested to ensure that representative settings work as expected, but with approximately 4,420 different configurations, who knows? If you find a problem with any please let me know.</p>
	<p>For more information, the latest version, etc. please visit <a href='http://www.mittineague.com/dev/er.php'>http://www.mittineague.com/dev/er.php</a></p>
	<p>Questions? For support, please visit <a href='http://www.mittineague.com/forums/viewtopic.php?t=100'>http://www.mittineague.com/forums/viewtopic.php?t=100</a> (registration required to post)</p>
	<p>For comments / suggestions, please visit <a href='http://www.mittineague.com/blog/2010/02/error-reporting-plugin-release-candidate/'>http://www.mittineague.com/blog/2010/02/error-reporting-plugin-release-candidate/</a></p>
	</div>
<?php
}
/* ERROR HANDLER - REPORTING SECTION */
function mitt_err_options($code, $msg, $file, $line, $context)
{
/* The SimplePie file throws too many E_STRICT errors and causes Internal Server errors */
	if ( ($code == '2048') && ( strpos($file, 'class-simplepie.php') !== FALSE ) )
	{
		return false;
	}

/* dashboard widget */
if ( ($code == '2') && ( strpos($msg, 'fsockopen') !== FALSE ) && ( strpos($msg, 'connect') !== FALSE ) && ( strpos($file, 'class-IXR.php') !== FALSE ) )
{

	$blog_date_format = get_option('date_format');
	$blog_time_format = get_option('time_format');
	$both_format = $blog_date_format . ' ' . $blog_time_format;

	$dash_info = str_replace("fsockopen() [<a href='function.fsockopen'>function.fsockopen</a>]: ", '', $msg);
	$dash_info = str_replace('(', '<br />(', $dash_info);

	$dash_info .= " ~ " . date($both_format, time()) . '<br />';

	$ping_err_arr = get_option('mitt_er_ping_errors');
	if ( !in_array($dash_info, $ping_err_arr) )
	{
		array_unshift($ping_err_arr, $dash_info);
		$er_pe_arr_len = count($ping_err_arr);
		$er_pe_len_setting = get_option('mitt_er_ping_error_length');
		if ( $er_pe_arr_len > $er_pe_len_setting )
		{
			$ping_err_arr = array_slice($ping_err_arr, 0, $er_pe_len_setting);
		}
		update_option('mitt_er_ping_errors', $ping_err_arr);
	}
}



/* get current option values for use */
	$log_options = get_option('mitt_er_log');
	$mitt_log_action = $log_options['log_action'];
	$mitt_log_type_mode = $log_options['log_type_mode'];
	$mitt_log_type_W = $log_options['log_type_W'];
	$mitt_log_type_N = $log_options['log_type_N'];
	$mitt_log_type_S = $log_options['log_type_S'];
	$mitt_log_andor = $log_options['log_andor'];
	$mitt_log_fold_mode = $log_options['log_fold_mode'];
	$mitt_log_fold_A = $log_options['log_fold_A'];
	$mitt_log_fold_C = $log_options['log_fold_C'];
	$mitt_log_fold_P = $log_options['log_fold_P'];
	$mitt_log_fold_I = $log_options['log_fold_I'];
	$mitt_log_cont = $log_options['log_cont'];
	$mitt_log_rpt = $log_options['log_rpt'];

	$email_options = get_option('mitt_er_email');
	$mitt_email_action = $email_options['email_action'];
	$mitt_email_type_mode = $email_options['email_type_mode'];
	$mitt_email_type_W = $email_options['email_type_W'];
	$mitt_email_type_N = $email_options['email_type_N'];
	$mitt_email_type_S = $email_options['email_type_S'];
	$mitt_email_andor = $email_options['email_andor'];
	$mitt_email_fold_mode = $email_options['email_fold_mode'];
	$mitt_email_fold_A = $email_options['email_fold_A'];
	$mitt_email_fold_C = $email_options['email_fold_C'];
	$mitt_email_fold_P = $email_options['email_fold_P'];
	$mitt_email_fold_I = $email_options['email_fold_I'];
	$mitt_email_cont = $email_options['email_cont'];

	if ( function_exists('date_default_timezone_get') )
	{
		$serv_tz = date_default_timezone_get();
	}
	else
	{
		$serv_tz = 'This_option_requires_PHP_ver5+';
	}
	$mitt_er_tz =  get_option('mitt_er_tz');
	if ( (!empty($mitt_er_tz)) && ($mitt_er_tz != 'This_option_requires_PHP_ver5+') && (function_exists('date_default_timezone_set')) ) date_default_timezone_set($mitt_er_tz);

	$hr_error = '';
	switch($code)
	{
		case '2':
		$hr_error = 'E_WARNING';
		break;

		case '8':
		$hr_error = 'E_NOTICE';
		break;

		case '2048':
		$hr_error = 'E_STRICT';
		break;

		case '256':
		$hr_error = 'E_USER_ERROR';
		break;

		case '512':
		$hr_error = 'E_USER_WARNING';
		break;

		case '1024':
		$hr_error = 'E_USER_NOTICE';
		break;

		case '4096':
		$hr_error = 'E_RECOVERABLE_ERROR';
		break;

		default:
		$hr_error = $code;
	}

/* Log block */
	if ($mitt_log_action == '1')
	{
		$log_context = ($mitt_log_cont == '1') ? $context : '';

		$log_left_mode = $mitt_log_type_mode;
		$log_left_cond = '';
		$log_left_join = '';
		$log_left_exp = '';
		$log_lead_paren = '';
		$log_andor = $mitt_log_andor;
		$log_end_paren = '';
		$log_right_mode = $mitt_log_fold_mode;
		$log_right_cond = '';
		$log_right_join = '';
		$log_right_exp = '';

		switch($log_left_mode)
		{
			case 'all':
			$log_left_cond = '';
			$log_left_join = '';
			break;

			case 'inc':
			$log_left_cond = '==';
			$log_left_join = '||';
			break;

			case 'exc':
			$log_left_cond = '!=';
			$log_left_join = '&&';
			break;

			case '':
			$log_left_cond = '';
			$log_left_join = '';
			break;

			default:
			$log_left_cond = '';
			$log_left_join = '';
		}

		switch($log_right_mode)
		{
			case 'all':
			$log_right_cond = '';
			$log_right_join = '';
			break;

			case 'inc':
			$log_right_cond = '!==';
			$log_right_join = '||';
			break;

			case 'exc':
			$log_right_cond = '===';
			$log_right_join = '&&';
			break;

			case '':
			$log_right_cond = '';
			$log_right_join = '';
			break;

			default:
			$log_right_cond = '';
			$log_right_join = '';
		}

		switch($log_andor)
		{
			case 'and':
			$log_andor = '&&';
			break;

			case 'or':
			$log_andor = '||';
			break;

			case '':
			$log_andor = '';
			break;

			default:
			$log_andor = '';
		}

		$log_temp_typ_arr = array();
		if ($mitt_log_type_W == '1') $log_temp_typ_arr[] = '2';
		if ($mitt_log_type_N == '1') $log_temp_typ_arr[] = '8';
		if ($mitt_log_type_S == '1') $log_temp_typ_arr[] = '2048';
		$log_left_side = count($log_temp_typ_arr);

		if( ($log_left_cond != '') && ($log_left_join != '') && ($log_left_side > 0) )
		{
			if ($log_left_side == '1') $log_left_exp = '$code' . $log_left_cond . $log_temp_typ_arr[0];
			if ($log_left_side == '2') $log_left_exp = '(' . '$code' . $log_left_cond . $log_temp_typ_arr[0] . ')' . $log_left_join . '(' . '$code' . $log_left_cond . $log_temp_typ_arr[1] . ')';
			if ($log_left_side == '3') $log_left_exp = '(' . '$code' . $log_left_cond . $log_temp_typ_arr[0] . ')' . $log_left_join . '(' . '$code' . $log_left_cond . $log_temp_typ_arr[1] . ')' . $log_left_join . '(' . '$code' . $log_left_cond . $log_temp_typ_arr[2] . ')';
		}

		$log_temp_fold_arr = array();
		if ($mitt_log_fold_A == '1') $log_temp_fold_arr[] = "'wp-admin'";
		if ($mitt_log_fold_C == '1') $log_temp_fold_arr[] = "'wp-content'";
		if ($mitt_log_fold_P == '1') $log_temp_fold_arr[] = "'plugins'";
		if ($mitt_log_fold_I == '1') $log_temp_fold_arr[] = "'wp-includes'";
		$log_right_side = count($log_temp_fold_arr);

		if( ($log_right_cond != '') && ($log_right_join != '') && ($log_right_side > 0) )
		{
			if ($log_right_side == '1') $log_right_exp = 'strpos($file,' . $log_temp_fold_arr[0] . ')' . $log_right_cond . 'FALSE';
			if ($log_right_side == '2') $log_right_exp = '(' . 'strpos($file,' . $log_temp_fold_arr[0] . ')' . $log_right_cond . 'FALSE' . ')' . $log_right_join . '(' . 'strpos($file,' . $log_temp_fold_arr[1] . ')' . $log_right_cond . 'FALSE' . ')';
			if ($log_right_side == '3') $log_right_exp = '(' . 'strpos($file,' . $log_temp_fold_arr[0] . ')' . $log_right_cond . 'FALSE' . ')' . $log_right_join . '(' . 'strpos($file,' . $log_temp_fold_arr[1] . ')' . $log_right_cond . 'FALSE' . ')' . $log_right_join . '(' . 'strpos($file,' . $log_temp_fold_arr[2] . ')' . $log_right_cond . 'FALSE' . ')';
			if ($log_right_side == '4') $log_right_exp = '(' . 'strpos($file,' . $log_temp_fold_arr[0] . ')' . $log_right_cond . 'FALSE' . ')' . $log_right_join . '(' . 'strpos($file,' . $log_temp_fold_arr[1] . ')' . $log_right_cond . 'FALSE' . ')' . $log_right_join . '(' . 'strpos($file,' . $log_temp_fold_arr[2] . ')' . $log_right_cond . 'FALSE' . ')' . $log_right_join . '(' . 'strpos($file,' . $log_temp_fold_arr[3] . ')' . $log_right_cond . 'FALSE' . ')';
		}

		if ( ($log_left_side > 0) && ($log_right_side > 0) && (!empty($log_andor)) )
		{
			$log_lead_paren = "(";
			$log_andor = ")" . $log_andor . "(";
			$log_end_paren = ")";
		}
		else
		{
			$log_andor = '';
		}

		$log_cond_test = $log_lead_paren . $log_left_exp . $log_andor . $log_right_exp . $log_end_paren;

		if ( $log_cond_test == '' ) $log_cond_test = "(1 == 1)";

		if(eval("return $log_cond_test;"))
		{
/*
* If the folder and/or filenames are changed, you will not be
* able to delete them from the options page unless you also
* modify the $guardian regex. It is strongly recommended that you
* use an appropriate regex to prevent malicious deletion of files.
*/
			$mitt_wp_logfoldername = "er-logs";
			$mitt_wp_logfilename = strftime("ER-%d-%b-%Y.log");
			$mitt_path_file = $mitt_wp_logfoldername . '/' . $mitt_wp_logfilename;

			if ( !is_dir($mitt_wp_logfoldername) )
				mkdir($mitt_wp_logfoldername, 0705);
			
			$info = $hr_error;
			$info .= "\r\n";
			$info .= "$msg at $file ($line)";
			$info .= "\r\n";
			$info .= "timed at " . date ('d-M-Y H:i:s', time());
			$info .= "\r\n";
			$info .= $log_context;
			$info .= "\r\n";

			if ( (file_exists($mitt_path_file)) && (!is_readable($mitt_path_file)) )
				 chmod($mitt_path_file, 0606);
	
			if ( !$handle = fopen($mitt_path_file, 'a+') )
			{
						return true; // silently fail, pass error along
			}
			else
			{
				$fs = filesize($mitt_path_file);
				if ( $fs <= 0 ) $fs = 1;
				$file_data = fread($handle, $fs);
				if( ($mitt_log_rpt == '1') || ( strpos($file_data, $msg) === FALSE ) )
				{
					if (!is_writable($mitt_path_file))
						chmod($mitt_path_file, 0606);
					if ( fwrite($handle, $info) === FALSE )
						return true; // silently fail, pass error along
				}
			chmod($mitt_wp_logfoldername, 0700);
			chmod($mitt_path_file, 0600);
			fclose($handle);
			clearstatcache();
			}
		}
	}

/* Email block */
	if ($mitt_email_action == '1')
	{
		$email_context = ($mitt_email_cont == '1') ? $context : '';

		$email_left_mode = $mitt_email_type_mode;
		$email_left_cond = '';
		$email_left_join = '';
		$email_left_exp = '';
		$email_lead_paren = '';
		$email_andor = $mitt_email_andor;
		$email_end_paren = '';
		$email_right_mode = $mitt_email_fold_mode;
		$email_right_cond = '';
		$email_right_join = '';
		$email_right_exp = '';

		switch($email_left_mode)
		{
			case 'all':
			$email_left_cond = '';
			$email_left_join = '';
			break;

			case 'inc':
			$email_left_cond = '==';
			$email_left_join = '||';
			break;

			case 'exc':
			$email_left_cond = '!=';
			$email_left_join = '&&';
			break;

			case '':
			$email_left_cond = '';
			$email_left_join = '';
			break;

			default:
			$email_left_cond = '';
			$email_left_join = '';
		}

		switch($email_right_mode)
		{
			case 'all':
			$email_right_cond = '';
			$email_right_join = '';
			break;

			case 'inc':
			$email_right_cond = '!==';
			$email_right_join = '||';
			break;

			case 'exc':
			$email_right_cond = '===';
			$email_right_join = '&&';
			break;

			case '':
			$email_right_cond = '';
			$email_right_join = '';
			break;

			default:
			$email_right_cond = '';
			$email_right_join = '';
		}

		switch($email_andor)
		{
			case 'and':
			$email_andor = '&&';
			break;

			case 'or':
			$email_andor = '||';
			break;

			case '':
			$email_andor = '';
			break;

			default:
			$email_andor = '';
		}

		$email_temp_typ_arr = array();
		if ($mitt_email_type_W == '1') $email_temp_typ_arr[] = '2';
		if ($mitt_email_type_N == '1') $email_temp_typ_arr[] = '8';
		if ($mitt_email_type_S == '1') $email_temp_typ_arr[] = '2048';
		$email_left_side = count($email_temp_typ_arr);

		if( ($email_left_cond != '') && ($email_left_join != '') && ($email_left_side > 0) )
		{
			if ($email_left_side == '1') $email_left_exp = '$code' . $email_left_cond . $email_temp_typ_arr[0];
			if ($email_left_side == '2') $email_left_exp = '(' . '$code' . $email_left_cond . $email_temp_typ_arr[0] . ')' . $email_left_join . '(' . '$code' . $email_left_cond . $email_temp_typ_arr[1] . ')';
			if ($email_left_side == '3') $email_left_exp = '(' . '$code' . $email_left_cond . $email_temp_typ_arr[0] . ')' . $email_left_join . '(' . '$code' . $email_left_cond . $email_temp_typ_arr[1] . ')' . $email_left_join . '(' . '$code' . $email_left_cond . $email_temp_typ_arr[2] . ')';
		}

		$email_temp_fold_arr = array();
		if ($mitt_email_fold_A == '1') $email_temp_fold_arr[] = "'wp-admin'";
		if ($mitt_email_fold_C == '1') $email_temp_fold_arr[] = "'wp-content'";
		if ($mitt_email_fold_P == '1') $email_temp_fold_arr[] = "'plugins'";
		if ($mitt_email_fold_I == '1') $email_temp_fold_arr[] = "'wp-includes'";
		$email_right_side = count($email_temp_fold_arr);

		if( ($email_right_cond != '') && ($email_right_join != '') && ($email_right_side > 0) )
		{
			if ($email_right_side == '1') $email_right_exp = 'strpos($file,' . $email_temp_fold_arr[0] . ')' . $email_right_cond . 'FALSE';
			if ($email_right_side == '2') $email_right_exp = '(' . 'strpos($file,' . $email_temp_fold_arr[0] . ')' . $email_right_cond . 'FALSE' . ')' . $email_right_join . '(' . 'strpos($file,' . $email_temp_fold_arr[1] . ')' . $email_right_cond . 'FALSE' . ')';
			if ($email_right_side == '3') $email_right_exp = '(' . 'strpos($file,' . $email_temp_fold_arr[0] . ')' . $email_right_cond . 'FALSE' . ')' . $email_right_join . '(' . 'strpos($file,' . $email_temp_fold_arr[1] . ')' . $email_right_cond . 'FALSE' . ')' . $email_right_join . '(' . 'strpos($file,' . $email_temp_fold_arr[2] . ')' . $email_right_cond . 'FALSE' . ')';
			if ($email_right_side == '4') $email_right_exp = '(' . 'strpos($file,' . $email_temp_fold_arr[0] . ')' . $email_right_cond . 'FALSE' . ')' . $email_right_join . '(' . 'strpos($file,' . $email_temp_fold_arr[1] . ')' . $email_right_cond . 'FALSE' . ')' . $email_right_join . '(' . 'strpos($file,' . $email_temp_fold_arr[2] . ')' . $email_right_cond . 'FALSE' . ')' . $email_right_join . '(' . 'strpos($file,' . $email_temp_fold_arr[3] . ')' . $email_right_cond . 'FALSE' . ')';
		}

		if ( ($email_left_side > 0) && ($email_right_side > 0) && (!empty($email_andor)) )
		{
			$email_lead_paren = "(";
			$email_andor = ")" . $email_andor . "(";
			$email_end_paren = ")";
		}
		else
		{
			$email_andor = '';
		}

		$email_cond_test = $email_lead_paren . $email_left_exp . $email_andor . $email_right_exp . $email_end_paren;

		if ( $mail_cond_test == '' ) $mail_cond_test = "1 == 1";

		if(eval("return $email_cond_test;"))
		{
			$admin_email = get_bloginfo('admin_email');
			$to = $admin_email;
			$subject = "Blog error";
			$body = $hr_error;
			$body .= "\r\n";
			$body .= "$msg at $file ($line)";
			$body .= "\r\n";
			$body .= "timed at " . date ('d-M-Y H:i:s', time());
			$body .= "\r\n";
			$body .= $email_context;

			$doc_root = $_SERVER['DOCUMENT_ROOT'];
			$sl_pos = strrpos($doc_root, '/');
			$site_dom = substr($doc_root, $sl_pos + 1);
			$headers  = "MIME-Version: 1.0 \r\n" ;
			$headers .= "Content-Type: text/plain \r\n";
			$headers .= "From: <Webmaster@" . $site_dom . ">\r\n\r\n";

			wp_mail($to, $subject, $body, $headers);
		}
	}
	if ( (function_exists('date_default_timezone_set')) && ($serv_tz != 'This_option_requires_PHP_ver5+') ) date_default_timezone_set($serv_tz);
	return true; // pass error along
}

function mitt_err_handler()
{
	error_reporting(0);
	ini_set('display_errors', FALSE);
	set_error_handler('mitt_err_options');
}

if (function_exists('add_action'))
{
	add_action('init', 'mitt_err_handler');
	add_action('admin_init', 'mitt_er_admin_init');
	add_action('admin_menu', 'mitt_add_er_page');
	add_action('wp_dashboard_setup', 'add_er_dashboard_widget');
	add_action('er_cron_del_hook', 'er_cron_delete_logs');
}

if (function_exists('register_activation_hook'))
{
	register_activation_hook( __FILE__, 'mitt_er_activate' );
}

if (function_exists('register_deactivation_hook'))
{
	register_deactivation_hook( __FILE__, 'mitt_er_deactivate');
}

if (function_exists('register_uninstall_hook'))
{
	register_uninstall_hook( __FILE__, 'mitt_er_uninstall');
}
?>