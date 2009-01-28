<?php
/*
Plugin Name: Error Reporting
Plugin URI: http://www.mittineague.com/dev/er.php
Description: Logs Errors to file and/or Sends Error Notification emails.
Version: Beta 0.9.5
Author: Mittineague
Author URI: http://www.mittineague.com
*/

/*
* Change Log
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

add_option('mitt_er_log', $mitt_log, 'Error Reporting');

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

add_option('mitt_er_email', $mitt_email, 'Error Reporting');

if ( function_exists('date_default_timezone_get') )
{
	$serv_tz = date_default_timezone_get();
}
else
{
	$serv_tz = 'This_option_requires_PHP_ver5+';
}
add_option('mitt_er_tz', $serv_tz, 'Error Reporting');

function mitt_add_er_page()
{
	if (function_exists('add_options_page'))
	{
		add_options_page('Error Reporting Options', 'ErrorReporting', 8, basename(__FILE__), 'mitt_er_options_page');
	}
	$ernonce = md5('errorreporting');
}

function mitt_er_css()
{
?>
<style type="text/css">
#page_header {
margin-top: 2em;
}
fieldset {
border: 1px solid #999;
margin-bottom: 1em;
}
#email_opts {
border: 3px dotted #f00;
}
.logic {
border: 1px solid #000;
margin: 1.1em;
}
.logic td {
padding: .1em;
border-right: 1px solid #bbb;
border-bottom: 1px solid #bbb;
}
#key {
list-style-type: none;
}
#key li {
margin: 1em 0 -1em 0;
font-family: monospace;
}
#key_title {
font-weight: bold;
font-size: large;
text-decoration: underline;
padding: 0 0 .5em 0;
}
</style>
<?php
}

function mitt_er_options_page()
{
	global $ernonce;

/* Clear Options Section */
	if (isset($_POST['clear_options']))
	{
		check_admin_referer('error-reporting-clear-options_' . $ernonce);
?>
		<div id="message" class="updated fade"><p><strong>
<?php
		$clr_log_opts = array();
		$clr_log_opts['log_action'] = '0';
		$clr_log_opts['log_type_mode'] = '';
		$clr_log_opts['log_type_W'] = '0';
		$clr_log_opts['log_type_N'] = '0';
		$clr_log_opts['log_type_S'] = '0';
		$clr_log_opts['log_andor'] = '';
		$clr_log_opts['log_fold_mode'] = '';
		$clr_log_opts['log_fold_A'] = '0';
		$clr_log_opts['log_fold_C'] = '0';
		$clr_log_opts['log_fold_P'] = '0';
		$clr_log_opts['log_fold_I'] = '0';
		$clr_log_opts['log_cont'] = '';
		$clr_log_opts['log_rpt'] = '';

		$clr_email_opts = array();
		$clr_email_opts['email_action'] = '0';
		$clr_email_opts['email_type_mode'] = '';
		$clr_email_opts['email_type_W'] = '0';
		$clr_email_opts['email_type_N'] = '0';
		$clr_email_opts['email_type_S'] = '0';
		$clr_email_opts['email_andor'] = '';
		$clr_email_opts['email_fold_mode'] = '';
		$clr_email_opts['email_fold_A'] = '0';
		$clr_email_opts['email_fold_C'] = '0';
		$clr_email_opts['email_fold_P'] = '0';
		$clr_email_opts['email_fold_I'] = '0';
		$clr_email_opts['email_cont'] = '';

		update_option('mitt_er_log', $clr_log_opts);
		update_option('mitt_er_email', $clr_email_opts);

		echo "Error Reporting Options have been Cleared<br />Error Reporting is now Disabled.<br />To Resume Error Reporting, select options and Update them.";
?>
		</strong></p></div>
<?php
	}
/* the Default Error Reporting option settings */
	if (isset($_POST['restore_options']))
	{
		check_admin_referer('error-reporting-restore-options_' . $ernonce);
?>
		<div id="message" class="updated fade"><p><strong>
<?php
		$rstr_log_opts = array();
		$rstr_log_opts['log_action'] = '1';
		$rstr_log_opts['log_type_mode'] = 'exc';
		$rstr_log_opts['log_type_W'] = '0';
		$rstr_log_opts['log_type_N'] = '1';
		$rstr_log_opts['log_type_S'] = '1';
		$rstr_log_opts['log_andor'] = 'or';
		$rstr_log_opts['log_fold_mode'] = 'exc';
		$rstr_log_opts['log_fold_A'] = '0';
		$rstr_log_opts['log_fold_C'] = '0';
		$rstr_log_opts['log_fold_P'] = '1';
		$rstr_log_opts['log_fold_I'] = '0';
		$rstr_log_opts['log_cont'] = '0';
		$rstr_log_opts['log_rpt'] = '0';

		$rstr_email_opts = array();
		$rstr_email_opts['email_action'] = '1';
		$rstr_email_opts['email_type_mode'] = 'exc';
		$rstr_email_opts['email_type_W'] = '0';
		$rstr_email_opts['email_type_N'] = '1';
		$rstr_email_opts['email_type_S'] = '1';
		$rstr_email_opts['email_andor'] = 'and';
		$rstr_email_opts['email_fold_mode'] = 'exc';
		$rstr_email_opts['email_fold_A'] = '0';
		$rstr_email_opts['email_fold_C'] = '0';
		$rstr_email_opts['email_fold_P'] = '1';
		$rstr_email_opts['email_fold_I'] = '0';
		$rstr_email_opts['email_cont'] = '0';

		update_option('mitt_er_log', $rstr_log_opts);
		update_option('mitt_er_email', $rstr_email_opts);

		echo "The Error Reporting Options have been set to the Default values";
?>
		</strong></p></div>
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
		check_admin_referer('error-reporting-update-options_' . $ernonce);
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
		$upd_log_opts['log_cont'] = ($upd_log_opts['log_action'] == '1') ? $_POST['mitt_log_cont'] : '';
		$upd_log_opts['log_rpt'] = ($upd_log_opts['log_action'] == '1') ? $_POST['mitt_log_rpt'] : '';

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
		$upd_email_opts['email_cont'] = ($upd_email_opts['email_action'] == '1') ? $_POST['mitt_email_cont'] : '';

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
		else if  ( ( ( ($upd_email_opts['email_type_mode'] == 'inc' ) || ($upd_email_opts['email_type_mode'] == 'exc' ) ) &&  ( ($upd_email_opts['email_fold_mode'] == 'inc' ) || ($upd_email_opts['email_fold_mode'] == 'exc' ) ) ) && ($upd_email_opts['email_andor'] == '') )
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
		check_admin_referer('error-reporting-delete-log-files_' . $ernonce);
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
/* Toggle Permissions Section */
	if (isset($_POST['toggle_permissions']))
	{
		check_admin_referer('error-reporting-toggle-permissions_' . $ernonce);
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
			$mitt_perms = 'NOTsecure';
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
?>
	<div id="page_header">
	<h2>Error Reporting</h2>
	</div>

<!-- /* LOG FILES SECTION */ -->
	<div class="wrap">
	<h2>Log Files</h2>
	<p>To view a file's contents, click on it's link. To save the file either right-click save-as or open the file and save using your browser's file menu. To Delete a file check it's checkbox and Submit.</p>
	<p>To access a file, the folder and file permissions must be correct. Be sure to reset them after you're done to prevent outside access.<br />
<?php
	$mitt_perms = (!empty($mitt_perms)) ? $mitt_perms : '';
	if ( ($mitt_perms == 'secure') || ($mitt_perms == 'NOTsecure') )
	{
		echo "The current Permission levels are <strong>" . $mitt_perms .  "</strong>";
	}
?>
	</p>
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	<input type="hidden" name="mitt_perms" value="<?php echo $mitt_perms; ?>" />
<?php
	if ( function_exists('wp_nonce_field') )
		wp_nonce_field('error-reporting-toggle-permissions_' . $ernonce);
?>
	<div class="submit">
		<input type="submit" name="toggle_permissions" value="Toggle Permissions" />
	</div>
	</form>

	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<?php
	if ( function_exists('wp_nonce_field') )
		wp_nonce_field('error-reporting-delete-log-files_' . $ernonce);

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
		echo "<a href='../er-logs/" . $logfile . "'>" . $logfile . "</a> (" . number_format(filesize('../er-logs/' . $logfile)) . " bytes)<br />";
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
		echo "<a href='./er-logs/" . $logfile2 . "'>" . $logfile2 . "</a> (" . number_format(filesize('./er-logs/' . $logfile2)) . " bytes)<br />";
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
	</div>

<!-- /* CONFIGURATION SECTION */ -->
	<div class="wrap">
	<h2>Configuration</h2>

	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<?php
	if ( function_exists('wp_nonce_field') )
		wp_nonce_field('error-reporting-update-options_' . $ernonce);
?>
<!-- /* Log Options Fieldset */ -->
	<fieldset class="options"> 
	<legend>Log Options</legend>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">Log Errors?</th><td valign="top">
      	<input name="mitt_log_action" type="radio" value='1' <?php if ($mitt_log_action == '1') {echo 'checked="checked"';} ?> /> Yes, Log Errors
	</td><td valign="top">
      	<input name="mitt_log_action" type="radio" value='0' <?php if ($mitt_log_action == '0') {echo 'checked="checked"';} ?> /> No, Don't Log Errors
	</td></tr>
	</table>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">Include All Error Types?</th><td valign="top">
      	<input name="mitt_log_type_mode" type="radio" value='all' <?php if ($mitt_log_type_mode == 'all') {echo 'checked="checked"';} ?> /> Yes, All Error Types
	</td><td valign="top">
      	<input name="mitt_log_type_mode" type="radio" value='inc' <?php if ($mitt_log_type_mode == 'inc') {echo 'checked="checked"';} ?> /> No, Only Include the Error Types indicated below
	</td><td valign="top">
      	<input name="mitt_log_type_mode" type="radio" value='exc' <?php if ($mitt_log_type_mode == 'exc') {echo 'checked="checked"';} ?> /> No, Exclude the Error Types indicated below
	</td></tr>

	<tr><th width="45%" valign="top" align="right" scope="row">Error Types:</th><td valign="top">
      	<input name="mitt_log_type_W" type="checkbox" value='1' <?php if ($mitt_log_type_W == '1') {echo 'checked="checked"';} ?> /> E_WARNING</td><td valign="top">
      	<input name="mitt_log_type_N" type="checkbox" value='1' <?php if ($mitt_log_type_N == '1') {echo 'checked="checked"';} ?> /> E_NOTICE</td><td valign="top">
      	<input name="mitt_log_type_S" type="checkbox" value='1' <?php if ($mitt_log_type_S == '1') {echo 'checked="checked"';} ?> /> E_STRICT
	</td></tr>
	</table>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">And or Or?</th><td valign="top">
      	<input name="mitt_log_andor" type="radio" value='and' <?php if ($mitt_log_andor == 'and') {echo 'checked="checked"';} ?> /> AND &#38;&#38;
	</td><td valign="top">
      	<input name="mitt_log_andor" type="radio" value='or' <?php if ($mitt_log_andor == 'or') {echo 'checked="checked"';} ?> /> OR ||
	</td></tr>
	</table>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">Include All Folders?</th><td valign="top">
      	<input name="mitt_log_fold_mode" type="radio" value='all' <?php if ($mitt_log_fold_mode == 'all') {echo 'checked="checked"';} ?> /> Yes, All Folders
	</td><td valign="top">
      	<input name="mitt_log_fold_mode" type="radio" value='inc' <?php if ($mitt_log_fold_mode == 'inc') {echo 'checked="checked"';} ?> /> No, Only Include the Folders indicated below
	</td><td valign="top">
      	<input name="mitt_log_fold_mode" type="radio" value='exc' <?php if ($mitt_log_fold_mode == 'exc') {echo 'checked="checked"';} ?> /> No, Exclude the Folders indicated below
	</td><td>
	</td></tr>

	<tr><th width="45%" valign="top" align="right" scope="row">Folders:</th><td valign="top">
      	<input name="mitt_log_fold_A" type="checkbox" value='1' <?php if ($mitt_log_fold_A == '1') {echo 'checked="checked"';} ?> /> wp-admin</td><td valign="top">
      	<input name="mitt_log_fold_C" type="checkbox" value='1' <?php if ($mitt_log_fold_C == '1') {echo 'checked="checked"';} ?> /> wp-content</td><td valign="top">
      	<input name="mitt_log_fold_P" type="checkbox" value='1' <?php if ($mitt_log_fold_P == '1') {echo 'checked="checked"';} ?> /> plugins</td><td valign="top">
      	<input name="mitt_log_fold_I" type="checkbox" value='1' <?php if ($mitt_log_fold_I == '1') {echo 'checked="checked"';} ?> /> wp-includes
	</td></tr>
	</table>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">Include Context?</th><td valign="top" colspan="2">
      	<input name="mitt_log_cont" type="radio" value='1' <?php if ($mitt_log_cont == '1') {echo 'checked="checked"';} ?> /> Yes, include context
	</td><td valign="top" colspan="2">
      	<input name="mitt_log_cont" type="radio" value='0' <?php if ($mitt_log_cont == '0') {echo 'checked="checked"';} ?> /> No, Don't include context
	</td></tr>

	<tr><th width="45%" valign="top" align="right" scope="row">Log Repeat Errors?</th><td valign="top" colspan="2">
      	<input name="mitt_log_rpt" type="radio" value='1' <?php if ($mitt_log_rpt == '1') {echo 'checked="checked"';} ?> /> Yes, Log repeat errors
	</td><td valign="top" colspan="2">
      	<input name="mitt_log_rpt" type="radio" value='0' <?php if ($mitt_log_rpt == '0') {echo 'checked="checked"';} ?> /> No, Don't Log repeat errors
	</td></tr>

	</table>
	</fieldset>	

<!-- /* Timezone Option Fieldset */ -->
	<fieldset class="options"> 
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
	<fieldset class="options" id="email_opts"> 
	<legend>Email Options</legend>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">Email Errors?</th><td valign="top">
      	<input name="mitt_email_action" type="radio" value='1' <?php if ($mitt_email_action == '1') {echo 'checked="checked"';} ?> /> Yes, Email Errors
	</td><td valign="top">
      	<input name="mitt_email_action" type="radio" value='0' <?php if ($mitt_email_action == '0') {echo 'checked="checked"';} ?> /> No, Don't Email Errors
	</td></tr>
	</table>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">Include All Error Types?</th><td valign="top">
      	<input name="mitt_email_type_mode" type="radio" value='all' <?php if ($mitt_email_type_mode == 'all') {echo 'checked="checked"';} ?> /> Yes, All Error Types
	</td><td valign="top">
      	<input name="mitt_email_type_mode" type="radio" value='inc' <?php if ($mitt_email_type_mode == 'inc') {echo 'checked="checked"';} ?> /> No, Only Include the Error Types indicated below
	</td><td valign="top">
      	<input name="mitt_email_type_mode" type="radio" value='exc' <?php if ($mitt_email_type_mode == 'exc') {echo 'checked="checked"';} ?> /> No, Exclude the Error Types indicated below
	</td></tr>

	<tr><th width="45%" valign="top" align="right" scope="row">Error Types:</th><td valign="top">
      	<input name="mitt_email_type_W" type="checkbox" value='1' <?php if ($mitt_email_type_W == '1') {echo 'checked="checked"';} ?> /> E_WARNING</td><td valign="top">
      	<input name="mitt_email_type_N" type="checkbox" value='1' <?php if ($mitt_email_type_N == '1') {echo 'checked="checked"';} ?> /> E_NOTICE</td><td valign="top">
      	<input name="mitt_email_type_S" type="checkbox" value='1' <?php if ($mitt_email_type_S == '1') {echo 'checked="checked"';} ?> /> E_STRICT
	</td></tr>
	</table>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">And or Or?</th><td valign="top">
      	<input name="mitt_email_andor" type="radio" value='and' <?php if ($mitt_email_andor == 'and') {echo 'checked="checked"';} ?> /> AND &#38;&#38;
	</td><td valign="top">
      	<input name="mitt_email_andor" type="radio" value='or' <?php if ($mitt_email_andor == 'or') {echo 'checked="checked"';} ?> /> OR ||
	</td></tr>
	</table>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">Include All Folders?</th><td valign="top">
      	<input name="mitt_email_fold_mode" type="radio" value='all' <?php if ($mitt_email_fold_mode == 'all') {echo 'checked="checked"';} ?> /> Yes, All Folders
	</td><td valign="top">
      	<input name="mitt_email_fold_mode" type="radio" value='inc' <?php if ($mitt_email_fold_mode == 'inc') {echo 'checked="checked"';} ?> /> No, Only Include the Folders indicated below
	</td><td valign="top">
      	<input name="mitt_email_fold_mode" type="radio" value='exc' <?php if ($mitt_email_fold_mode == 'exc') {echo 'checked="checked"';} ?> /> No, Exclude the Folders indicated below
	</td><td>
	</td></tr>

	<tr><th width="45%" valign="top" align="right" scope="row">Folders:</th><td valign="top">
      	<input name="mitt_email_fold_A" type="checkbox" value='1' <?php if ($mitt_email_fold_A == '1') {echo 'checked="checked"';} ?> /> wp-admin</td><td valign="top">
      	<input name="mitt_email_fold_C" type="checkbox" value='1' <?php if ($mitt_email_fold_C == '1') {echo 'checked="checked"';} ?> /> wp-content</td><td valign="top">
      	<input name="mitt_email_fold_P" type="checkbox" value='1' <?php if ($mitt_email_fold_P == '1') {echo 'checked="checked"';} ?> /> plugins</td><td valign="top">
      	<input name="mitt_email_fold_I" type="checkbox" value='1' <?php if ($mitt_email_fold_I == '1') {echo 'checked="checked"';} ?> /> wp-includes
	</td></tr>
	</table>

	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr><th width="45%" valign="top" align="right" scope="row">Include Context?</th><td valign="top" colspan="2">
      	<input name="mitt_email_cont" type="radio" value='1' <?php if ($mitt_email_cont == '1') {echo 'checked="checked"';} ?> /> Yes, include context
	</td><td valign="top" colspan="2">
      	<input name="mitt_email_cont" type="radio" value='0' <?php if ($mitt_email_cont == '0') {echo 'checked="checked"';} ?> /> No, Don't include context
	</td></tr>

	</table>
	</fieldset>

	<div class="submit">
		<input type="submit" name="update_options" value="<?php _e('Update options'); ?> &raquo;" />
	</div>
	</form>
	<br />
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<?php
	if ( function_exists('wp_nonce_field') )
		wp_nonce_field('error-reporting-clear-options_' . $ernonce);
?>
	<div class="submit">
		<input type="submit" name="clear_options" value="Clear Options" />
	</div>
	</form>
	<br />
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<?php
	if ( function_exists('wp_nonce_field') )
		wp_nonce_field('error-reporting-restore-options_' . $ernonce);
?>
	<div class="submit">
		<input type="submit" name="restore_options" value="Restore Option Defaults" />
	</div>
	</form>
	</div>

<!-- /* OPTIONS SETTINGS LOGIC SECTION - EXAMPLE TABLES */ -->
	<div class="wrap">
	<h2>Option Settings Logic</h2>
	<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr>
	<td valign="top">

<table><tr><td>

<table class="logic">
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

<table class="logic">
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

<table class="logic">
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

<table class="logic">
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

<table class="logic">
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

<table class="logic">
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

<table class="logic">
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

<table class="logic">
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

<table class="logic">
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

<table class="logic">
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

<table class="logic">
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

<table class="logic">
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

<table class="logic">
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

<table class="logic">
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

<ul id="key">
<li id="key_title">Key:</li>
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
	</div>

<!-- /* PLUGIN INFORMATION SECTION */ -->
	<div class="wrap">
	<h2>Plugin Information</h2>
	<ul>
	<li>Log Error Reporting<br />
Depending on where an error occurs, it will be logged to a er-logs folder that's either under the blog's installation folder, or under the wp-admin folder. New files are created for each day with names having the format "ER-dd-Mmm-yyyy.log"<br />
eg. ER-05-Mar-2007.log</li>

	<li>Email Error Reporting<br />
Email Error Reporting does not have a "no repeat errors" setting. This means that the blog administrator's email address will get an email for every reported error, every time.<br />
For example, while testing this plugin using the default settings, 10 failed pings generated 190+ emails. It is strongly suggested that you "fine tune" your options using Log Error Reporting first (with the Repeat Error option set to yes to get an accurate indication of how many emails would have been sent) and get the errors down to a manageable amount before experimenting with the Email Error Reporting settings.<br />
Be very careful setting these options. You could end up flooding the inbox with hundreds, and <strong>more likely thousands</strong>, of emails in a relatively short amount of time.<br />
Note that the default Email Error Reporting settings are not enabled on install.</li>

	<li>Error Types Options<br />
The Error Reporting plugin can report E-WARNING, E_NOTICE and E_STRICT errors.<br />
Any E_RECOVERABLE_ERROR, and any "trigger" errors, E_USER_ERROR, E_USER_WARNING, and E_USER_NOTICE, will be reported if the option settings report "other error types" (see the "Option Settings Logic" section).<br />
If you want to ensure that all error types are reported, check "Yes, All Error Types".<br />
If you are interested in only certain error types "Include" them.<br />
Conversely, if you specifically do not want an error type, "Exclude" it.</li>

	<li>AND / OR Option<br />
The AND / OR option setting will only matter if neither "Types" nor "Folders" are set to "All".<br />
But, if both are either "Exclude" - "Include", it will make a big difference (see the "Option Settings Logic" section).</li>

	<li>Folder Options<br />
Errors in Files that are under the blog's install folder, and are not in the wp-admin, wp-content, or wp-includes folders, will be reported if the option settings report "other folders" (see the "Option Settings Logic" section).<br />
Note that the plugins folder is inside the wp-content folder. It is presented as a separate option to allow for more precise control.<br />
If the wp-content folder is included / excluded, so too will be the plugin folder with it. Likewise for any folders under the other folders.</li>

	<li>Context and Repeat Errors Options<br />
Including the Context of the error may provide some helpful information, but as it adds significantly to the size of the log file, it is by default not included.<br />
Likewise, there may be times when it would be helpful to see that a line of a file is causing the same error "X" amount of times, but because including Repeat errors would add significantly to the size of the log file, it too is by default not included.<br />
Note that there is no repeat error option for Email Error Reporting.<br />
Because each error will be sent as an individual email, the Context is not as crucial a setting here as it is for the Log options. So once you're sure you have the number of emails being sent under control, you may want to include it if that information will help you.</li>

	<li>Timezone Option<br />
This value is initially set to the server's timezone and controls what time is used.<br />*Note* Requires PHP version 5+</li>
	</ul>
	</div>

<!-- /* FURTHER INFORMATION SECTION */ -->
	<div class="wrap">
	<h2>Further Information</h2>
	<p>WANTED - Top 4 Settings<br />
With all the possible option setting configurations, it's impossible to show examples of them all, but if you find one you really like, let me know and it may get included in a future version's "Top 4 settings" row.</p>
	<p>WANTED - Bug Reports<br />
This plugin has been tested to ensure that representative settings work as expected, but with approximately 4,420 different configurations, who knows? If you find a problem with any please let me know.</p>
	<p>For more information, the latest version, etc. please visit <a href='http://www.mittineague.com/dev/er.php'>http://www.mittineague.com/dev/er.php</a></p>
	<p>Questions? For support, please visit <a href='http://www.mittineague.com/forums/viewtopic.php?t=100'>http://www.mittineague.com/forums/viewtopic.php?t=100</a> (registration required to post)</p>
	<p>For comments / suggestions, please visit <a href='http://www.mittineague.com/blog/2007/03/error-reporting-plugin/'>http://www.mittineague.com/blog/2007/03/error-reporting-plugin/</a></p>
	</div>
<?php
}
/* ERROR HANDLER - REPORTING SECTION */
function mitt_err_options($code, $msg, $file, $line, $context)
{
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
			$info .=  print_r($log_context, TRUE);
			$info .= "\r\n";

			if ( (file_exists($mitt_path_file)) && (!is_readable($mitt_path_file)) )
				 chmod($mitt_path_file, 0606);
	
			if ( !$handle = fopen($mitt_path_file, 'a+') )
			{
						return; // silently fail
			}
			else
			{
				$fs = filesize($mitt_path_file);
				$file_data = fread($handle, $fs);
				if( ($mitt_log_rpt == '1') || ( strpos($file_data, $msg) === FALSE ) )
				{
					if (!is_writable($mitt_path_file))
						chmod($mitt_path_file, 0606);
					if ( fwrite($handle, $info) === FALSE )
						return; // silently fail
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
			$body .=  print_r($email_context, TRUE);

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
}

function mitt_err_handler()
{
	set_error_handler('mitt_err_options');
}

if (function_exists('add_action'))
{
	add_action('init', 'mitt_err_handler');
	add_action('admin_head', 'mitt_er_css');
	add_action('admin_menu', 'mitt_add_er_page');
}
?>