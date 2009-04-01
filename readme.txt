=== Error Reporting ===
Contributors: Mittineague
Tags: error reporting, log errors, email errors, ping errors
Requires at least: unknown
Tested up to: 2.7
Stable tag: Trunk

== License ==
Released under the terms of the GNU General Public License.  

== Version History ==
Beta 0.10.0 01-Apr-2009  
- added ping error - dashboard widget code  
- added self-cleanup hooks  
- removed deprecated option descriptions  
- nonce tweaks  
- removed print_ r $context  
- added return false  
- changed admin CSS hook  
- removed fail returns from handler  

Beta 0.9.6 15-Mar-2009  
- fixed uninitialized variables  
- fixed 'all types' 'all folders' bug  
- remove/add 'shutdown' action  
- added label tags  
- friendlier CSS selectors  
- added 'register_ activation_ hook'  

Beta 0.9.5 27-Jan-2009  
- changed mktime() to time()  
- "info" link fix  
- replaced "short tags"  
- changed 'wp'logs to 'er'logs  
- added javascript select/deselect all  

Beta 0.9.4 10-Apr-2007  
- made date_ default_ timezone_ get/set OK for PHP < ver. 5  

Beta 0.9.3 09-Apr-2007  
- removed error_ log from Log block fopen-fwrite fails  
- added natsort to log file display  
- rearranged page sections  
- minor mark-up and info edits  

Beta 0.9.2 30-Mar-2007  
- Security improvements  
- - made wp-log folders / files not world readable  
- - added nonces to form actions  
- minor GUI changes  
- added error_ log to Log block fopen-fwrite fails  

Beta 0.9.1 29-Mar-2007  
- fixed buggy conditional generating code block  
- added info re E_ RECOVERABLE_ ERROR  
- added chmod to fix annoying sporadic permission resets  

Beta 0.9.0 28-Mar-2007  

== Description ==
Logs Errors to files and / or Sends Error Notification emails. Records Ping Errors and displays them in a dashboard widget.
  

== Long Description ==

It is the hope that the Error Reporting plugin will prove to be a valuable tool for the WordPress developer. Highly customizable settings allow for the ability to locate various types of both native WordPress Core errors, and plugin and theme errors. Errors can be handled by logging to files and/or by email notification. The Error Reporting plugin can help identify problems during plugin development, and can help in both locating and keeping aware of errors in a live blog.  
The Ping Errors feature can catch up to 100 ping errors. These are displayed in both a dashboard widget and on the plugin's Settings page. Great for tracking repeated ping failures so you can clean up your ping list.  

Ping Errors  
If you do not want to see the widget on your dashboard, please go to dashboard's "Screen Options" and deselect "Ping Errors"  
* Because a ping fails once, or even a few times, does not necessarily mean that it should be removed from your ping list. However, if one is repeatedly failing over a long period of time, it justifies investigation and possible removal.  
The Ping Error feature is also available as a stand-alone plugin [Ping Watcher](http://www.mittineague.com/dev/pw.php)
The two are not compatible and both can not be activated at the same time.  

Log Error Reporting  
Depending on where an error occurs, it will be logged to a wp-logs folder that's either under the blog's installation folder, or under the wp-admin folder. New files are created for each day with names having the format "ER-dd-Mmm-yyyy.log"  
eg. ER-05-Mar-2009.log  

Email Error Reporting  
Email Error Reporting does not have a "no repeat errors" setting. This means that the blog administrator's email address will get an email for every reported error, every time.  
For example, while testing this plugin using the default settings, 10 failed pings generated 190+ emails. It is strongly suggested that you "fine tune" your options using Log Error Reporting first (with the Repeat Error option set to yes to get an accurate indication of how many emails would have been sent) and get the errors down to a manageable amount before experimenting with the Email Error Reporting settings.  
Be very careful setting these options. You could end up flooding the inbox with hundreds, and MORE LIKELY THOUSANDS, of emails in a relatively short amount of time.  
Note that the default Email Error Reporting settings are not enabled on install.  

Error Types Options  
The Error Reporting plugin can report E_ WARNING, E_ NOTICE and E_ STRICT errors.  
Any E_ RECOVERABLE_ ERROR, and any "trigger" errors, E_ USER_ERROR, E_ USER_ WARNING, and E_ USER_ NOTICE, will be reported if the option settings report "other error types" (see the "Option Settings Logic" section).  
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
This value is initially set to the server's timezone and controls what time is used. This option requires PHP ver 5+  

Self Cleanup  
Deactivating this plugin will remove the "Number of Ping Errors to Save" setting.  
Uninstalling this plugin using the WordPress plugin list page's "delete" will remove the plugin's options from the wp-options table, including any saved ping errors, and all Log files and folders will be deleted.  

== Option Settings Logic ==
Please see screenshot-2.jpg or the plugin's ACP Settings page for visual explanation by example.  

== Log Files ==
Provides links to the log files for viewing / saving, and a way to delete them.  
Note that they must be temporarily toggled to insecure to access them. Because a native WordPress Core error will reset the permissions if the plugin is set to "E_ NOTICE" and "wp-include", the 'shutdown' action hook is also removed.  
Please remember to toggle permissions back to secure to prevent direct HTTP access to the files and re-enable the output buffer flush as not doing so may result in a security risk and possible PHP memory problems.  

== Installation ==
1. Upload 'errorreporting.php' to the '/wp-content/plugins/' directory  
2. Activate the plugin through the 'Plugins' menu in WordPress  
3. Click the 'Options'/'Settings' admin menu link, and select 'ErrorReporting'  
4. Configure the options  

== Frequently Asked Questions ==

= How should I configure the options for "X" error types and "X" folders? =

There are too many possibilities to show them all in the Option Settings Logic section.  
Hopefully the examples will provide enough insight so that you can figure out what you need for what you want.  
And if you're unsure, Please, Please, Please, experiment with the settings for Log Options, NOT the Email Options.  
But, that said, if enough people recommend or inquire about a particular configuration, it may just make it in a future version's "Top 4 settings" row. :)  

= How can I help? =

A rough estimate of all the possible different option configuations, not taking into account for different settings producing the same result, is 4,420.  
Needless to say, I have not verified that all of them work correctly. If you have trouble with any settings not working as expected, please contact me.  

== Screenshots ==

1. Email Options

2. Option Settings Logic

3. Log Files

4. Ping Errors

5. Dashboard Widget

== More Info ==
For more info, please visit the plugin's page  
[Error Reporting](http://www.mittineague.com/dev/er.php)  

For support, please visit the forum (registration required to post)  
[Support](http://www.mittineague.com/forums/viewtopic.php?t=100)  

For comments / suggestions, please visit the blog  
[Comments / Suggestions](http://www.mittineague.com/blog/2007/03/error-reporting-plugin/)  

***********************
** AN IMPORTANT NOTE **
***********************
It can not be stressed enough. Be VERY CAREFUL with the Email Option Settings.  
You could end up flooding the inbox with hundreds, and MORE LIKELY THOUSANDS, of emails in a relatively short amount of time.  

After downloading or viewing a log file, be sure to toggle permissions back to "secure". Failure to do so may allow direct HTTP access to the log files. Also the 'shutdown' action is removed to allow a Core WordPress E_ NOTICE error from resetting permissions when the Options Settings include E_ NOTICE errors in the wp-include folder. Avoid potential memory problems by resetting back to "secure" which re-enables the output buffer flush by re-adding the 'shutdown' action.  