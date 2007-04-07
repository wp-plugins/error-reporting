=== Error Reporting ===
Contributors: Mittineague
Tags: error reporting, log errors, email errors
Requires at least: unknown
Tested up to: 2.1.2
Stable tag: Trunk

== Licence ==
Released under the terms of the GNU General Public License.

== Version History ==
Beta 0.9.2 30-Mar-2007
- Security improvements 
- - made wp-log folders / files not world readable
- - added nonces to form actions
- minor GUI changes
- added error_log to Log block fopen-fwrite fails
Beta 0.9.1 29-Mar-2007
- fixed buggy conditional generating code block
- added info re E_RECOVERABLE_ERROR
- added chmod to fix annoying sporadic permission resets
Beta 0.9.0 28-Mar-2007

== Description ==
Logs Errors to files and / or Sends Error Notification emails.

== Long Description ==
Log Error Reporting
Depending on where an error occurs, it will be logged to a wp-logs folder that's
either under the blog's installation folder, or under the wp-admin folder. New files are created for each day with names having the format "WP-dd-Mmm-yyyy.log"
eg. WP-05-Mar-2007.log

Email Error Reporting
Email Error Reporting does not have a "no repeat errors" setting. This means that the blog administrator's email address will get an email for every reported error, every time.
For example, while testing this plugin using the default settings, 10 failed pings generated 190+ emails. It is strongly suggested that you "fine tune" your options using Log Error Reporting first (with the Repeat Error option set to yes to get an accurate indication of how many emails would have been sent) and get the errors down to a manageable amount before experimenting with the Email Error Reporting settings.
Be very careful setting these options. You could end up flooding the inbox with hundreds, and MORE LIKELY THOUSANDS, of emails in a relatively short amount of time.
Note that the default Email Error Reporting settings are not enabled on install.

Error Types Options
The Error Reporting plugin can report E-WARNING, E_NOTICE and E_STRICT errors.
Any E_RECOVERABLE_ERROR, and any "trigger" errors, E_USER_ERROR, E_USER_WARNING, and E_USER_NOTICE, will be reported if the option settings report "other error types" (see the "Option Settings Logic" section).
If you want to ensure that all error types are reported, check "Yes, All Error Types".
If you are interested in only certain error types "Include" them.
Conversely, if you specifically do not want an error type, "Exclude" it.

AND / OR Option
The AND / OR option setting will only matter if neither "Types" nor "Folders" are set to "All".
But, if both are either "Exclude" - "Include", it will make a big difference (see the "Option Settings Logic" section).

Folder Options
Errors in Files that are under the blog's install folder, and are not in the wp-admin, wp-content, or wp-includes folders, will be reported if the option settings report "other folders" (see the "Option Settings Logic" section).
Note that the plugins folder is inside the wp-content folder. It is presented as a separate option to allow for more precise control.
If the wp-content folder is included / excluded, so too will be the plugin folder with it. Likewise for any folders under the other folders.

Context and Repeat Errors Options
Including the Context of the error may provide some helpful information, but as it adds significantly to the size of the log file, it is by default not included.
Likewise, there may be times when it would be helpful to see that a line of a file is causing the same error "X" amount of times, but because including Repeat errors would add significantly to the size of the log file, it too is by default not included.
*Note* that there is no repeat error option for Email Error Reporting.
Because each error will be sent as an individual email, the Context is not as crucial a setting here as it is for the Log options. So once you're sure you have the number of emails being sent under control, you may want to include it if that information will help you.

Timezone Option
This value is initially set to the server's timezone and controls what time is used.

== Option Settings Logic ==
Please see er-screenshot-2.jpg or the plugin's ACP page for visual explanation by example.

== Log Files ==
Provides links to the log files for viewing / saving, and a way to delete them.

== Installation ==
1. Upload `errorreporting.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Click the "Options" admin menu link, and select "ErrorReporting"
4. Configure the options

== Frequently Asked Questions ==

= How should I configure the options for "X" error types and "X" folders? =

There are too many possibilities to show them all in the Option Settings Logic section.
Hopefully the examples will provide enough insight so that you can figure out what you need for what you want.
And if you're unsure, Please, Please, Please, experiment with the settings for Log Options, NOT the Email Options. 
But, that said, if enough people recommend or inquire about a particular configuration, it may just make it in a future version's "Top 4 settings" row. :)

= How can I help? =

A rough estimate of all the possible different option configuations, not taking
into account for different settings producing the same result, is 4,420.
Needless to say, I have not verified that all of them work correctly. If you
have trouble with any settings not working as expected, please contact me.

== Screenshots ==
Email Options 		- http://www.mittineague.com/dev/img/er-screenshot-1.jpg

Option Settings Logic 	- http://www.mittineague.com/dev/img/er-screenshot-2.jpg

Log Files 		- http://www.mittineague.com/dev/img/er-screenshot-3.jpg

== More Info ==
For more info, please visit
http://www.mittineague.com/dev/er.php

For support, please visit (registration required)
http://www.mittineague.com/forums/viewforum.php?f=30

For comments / suggestions, please visit (registration required)
http://www.mittineague.com/blog/2007/03/error-reporting-plugin/

***********************
** AN IMPORTANT NOTE **
***********************
It can not be stressed enough. Be VERY CAREFUL with the Email Option Settings.
You could end up flooding the inbox with hundreds, and MORE LIKELY THOUSANDS,
of emails in a relatively short amount of time.