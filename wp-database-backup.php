<?php
/*
Plugin Name: WP Database Backup
Plugin URI:walkeprashant.wordpress.com
Description: This plugin helps you to create wordpress database backup
Version: 1.0
Author:Prashant Walke
Author URI:walkeprashant.wordpress.com

This plugin helps you to create Database Backup easily.

License: GPL v3

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
if ( ! defined( 'WPDB_PLUGIN_URL'  ) ) define( 'WPDB_PLUGIN_URL',  WP_CONTENT_URL. '/plugins/wp-database-backup' );
add_action('admin_menu', 'wp_db_backup_add_menu');
function wp_db_backup_add_menu() {
	$page = add_management_page('WP-DB Backup', 'WP-DB Backup ', 'manage_options', 'wp-database-backup', 'wp_db_backup_settings_page');
}

add_action('admin_init', 'wp_db_backup_admin_init');
function wp_db_backup_admin_init() {

	if(isset($_GET['action'])) {
		switch((string)$_GET['action']) {
 
			case 'createdbbackup':
				wp_db_backup_event_process();
				wp_redirect(get_bloginfo('url').'/wp-admin/tools.php?page=wp-database-backup');
				break;
			case 'removebackup':
				$index = (int)$_GET['index'];
				$options = get_option('wp_db_backup_backups');
				$newoptions = array();
				$count = 0;
				foreach($options as $option) {
					if($count != $index) {
						$newoptions[] = $option;
					}
					$count++;
				}
				
				unlink($options[$index]['dir']);
				update_option('wp_db_backup_backups', $newoptions);
				wp_redirect(get_bloginfo('url').'/wp-admin/tools.php?page=wp-database-backup');
				break;
		}
	}
	register_setting('wp_db_backup_options', 'wp_db_backup_options', 'wp_db_backup_validate');
    add_settings_section('wp_db_backup_main', '', 'wp_db_backup_section_text', 'wp-database-backup');
}

function wp_db_backup_settings_page() { ?>
    <div class="wrap">
		<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
		<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
		<script>
		jQuery(document).ready(function() {
			jQuery("#tabs").tabs();
		});
		</script>
		<style type="text/css">
		.widefat td {
			vertical-align: middle;
		}
		</style>
		<div><h2><a href="http://walkeprashant.wordpress.com" target="blank"><img src="<?php echo WPDB_PLUGIN_URL.'/wp-database-backup.png';?>" ></a>Database Backup Settings</h2></div>
		<form method="post" action="options.php" name="wp_auto_commenter_form">
			<?php settings_fields('wp_db_backup_options'); ?>
			<?php do_settings_sections('wp-database-backup'); ?>
		</form>
    </div>
<?php
}

function wp_db_backup_section_text() {
	$options = get_option('wp_db_backup_backups');
	$settings = get_option('wp_db_backup_options');
	echo '<div id="tabs" style="width: 640px;">';
		echo '<ul>';
			echo '<li><a href="#fragment-1"><span>Database Backups</span></a></li>';
			echo '<li><a href="#fragment-2"><span>Scheduler</span></a></li>';
			echo '<li><a href="#fragment-3"><span>Help</span></a></li>';
		echo '</ul>';
		echo '<div id="fragment-1">';
			if($options) {
				echo '<table class="widefat">';
					echo '<thead>';
						echo '<tr>';
							echo '<th class="manage-column" scope="col" width="15%" style="text-align: center;">SL No</th>';
							echo '<th class="manage-column" scope="col" width="30%">Date</th>';
							echo '<th class="manage-column" scope="col" width="20%">Backup File</th>';
							echo '<th class="manage-column" scope="col" width="20%">Size</th>';
							echo '<th class="manage-column" scope="col" width="15%"></th>';
						echo '</tr>';
					echo '</thead>';
					echo '<tfoot>';
						echo '<tr>';
							echo '<th class="manage-column" scope="col" width="15%" style="text-align: center;">SL No</th>';
							echo '<th class="manage-column" scope="col" width="30%">Date</th>';
							echo '<th class="manage-column" scope="col" width="20%">Backup File</th>';
							echo '<th class="manage-column" scope="col" width="20%">Size</th>';
							echo '<th class="manage-column" scope="col" width="15%"></th>';
						echo '</tr>';
					echo '</tfoot>';
					echo '<tbody>';
						$count = 1;
						foreach($options as $option) {
							echo '<tr '.((($count % 2) == 0)?' class="alternate"':'').'>';
								echo '<td style="text-align: center;">'.$count.'</td>';
								echo '<td>'.date('jS, F Y', $option['date']).'<br />'.date('h:i:s A', $option['date']).'</td>';
								echo '<td><a href="'.$option['url'].'" style="color: #21759B;">Download</a></td>';
								echo '<td>'.wp_db_backup_format_bytes($option['size']).'</td>';
								echo '<td><a href="'.get_bloginfo('url').'/wp-admin/tools.php?page=wp-database-backup&action=removebackup&index='.($count - 1).'" class="button-secondary">Remove Database Backup<a/></td>';
							echo '</tr>';
							$count++;
						}
					echo '</tbody>';
				echo '</table>';
			} else {
				echo '<p>No Database Backups Created!</p>';
			}
			echo '<p class="submit">';
				echo '<a href="'.get_bloginfo('url').'/wp-admin/tools.php?page=wp-database-backup&action=createdbbackup" class="button-secondary">Create New Database Backup<a/>';
			echo '</p>';
		echo '</div>';
	
	echo '<div id="fragment-2">';
			echo '<p>Enable Auto Backups&nbsp;';
				echo '<input type="checkbox" name="wp_db_backup_options[enable_autobackups]" value="1" '.checked(1, $settings['enable_autobackups'], false).'/>';
			echo '</p>';
			echo '<p>Auto Database Backup Frequency<br />';
				echo '<select name="wp_db_backup_options[autobackup_frequency]" style="width: 100%; margin: 5px 0 0;">';
					echo '<option value="daily" '.selected('daily', $settings['autobackup_frequency'], false).'>Daily</option>';
					echo '<option value="weekly" '.selected('weekly', $settings['autobackup_frequency'], false).'>Weekly</option>';
					echo '<option value="monthly" '.selected('monthly', $settings['autobackup_frequency'], false).'>Monthly</option>';
				echo '</select>';
			echo '</p>';
			echo '<p class="submit">';
				echo '<input type="submit" name="Submit" class="button-secondary" value="Save Settings" />';
			echo '</p>';
		echo '</div>';
		
	echo '<div id="fragment-3">';
			echo '<p>';
				echo '<b>Follow the steps listed below to Create Database Backup</b><br />';
				echo '<br />';
				echo 'Create Backup:<br /> ';
				echo '&nbsp;&nbsp;1) Click on Create New Database Backup<br />';
				echo '&nbsp;&nbsp;2) Download Database Backup file.<br />';
				echo '<br />';
				echo 'Restore Backup:<br /> ';
				echo '&nbsp;&nbsp;1)Login to phpMyAdmin<br />';
				echo '&nbsp;&nbsp;2)Click Databases and select the database that you will be importing your data into.<br />';
				echo '&nbsp;&nbsp;3)Across the top of the screen will be a row of tabs. Click the Import tab.<br />';
				echo '&nbsp;&nbsp;4)On the next screen will be a location of text file box, and next to that a button named Browse.<br />';
				echo '&nbsp;&nbsp;5)Click Browse. Locate the backup file stored on your computer.<br />';
				echo '&nbsp;&nbsp;6)Click the Go button<br />';
			echo '</p>';
			echo '<p>Wish you could store your backups in a safer place(Dropbox, FTP, Google Drive and more)?</p> ';
			echo 'Drop Mail :walke.prashant28@gmail.com';
		echo '</div>';
	echo '</div>';
	
}		

function wp_db_backup_validate($input) {	
	return $input;
}

function wp_db_backup_format_bytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 

function wp_db_backup_create_mysql_backup() {
	global $wpdb;
	/*BEGIN : Prevent saving backup plugin settings in the database dump*/
	$options_backup = get_option('wp_db_backup_backups');
	$settings_backup = get_option('wp_db_backup_options');
	delete_option('wp_db_backup_backups');
	delete_option('wp_db_backup_options');
	/*END : Prevent saving backup plugin settings in the database dump*/
	
	$tables = $wpdb->get_col('SHOW TABLES');
	$output = '';
	foreach($tables as $table) {
		$result = $wpdb->get_results("SELECT * FROM {$table}", ARRAY_N);
		$row2 = $wpdb->get_row('SHOW CREATE TABLE '.$table, ARRAY_N); 
		$output .= "\n\n".$row2[1].";\n\n";
		for($i = 0; $i < count($result); $i++) {
			$row = $result[$i];
			$output .= 'INSERT INTO '.$table.' VALUES(';
			for($j=0; $j<count($result[0]); $j++) {
				$row[$j] = mysql_real_escape_string($row[$j]);
				$output .= (isset($row[$j])) ? '"'.$row[$j].'"'	: '""'; 
				if ($j < (count($result[0])-1)) {
					$output .= ',';
				}
			}
			$output .= ");\n";
		}
		$output .= "\n";
	}
	$wpdb->flush();
	/*BEGIN : Prevent saving backup plugin settings in the database dump*/
	add_option('wp_db_backup_backups', $options_backup);
	add_option('wp_db_backup_options', $settings_backup);
	/*END : Prevent saving backup plugin settings in the database dump*/
	return $output;
}



function wp_db_backup_create_archive() {	
	/*Begin : Setup Upload Directory, Secure it and generate a random file name*/
	
	$source_directory = wp_db_backup_wp_config_path();
	
	$path_info = wp_upload_dir();
	
	wp_mkdir_p($path_info['basedir'].'/db-backup');
	fclose(fopen($path_info['basedir'].'/db-backup/index.php', 'w'));
	/*Begin : Generate SQL DUMP and save to file database.sql*/
	$filename=Date("Y_m_d").'_'.Time("H:M:S").'_database.sql';
	$handle = fopen($path_info['basedir'].'/db-backup/'.$filename,'w+');
	fwrite($handle, wp_db_backup_create_mysql_backup());
	fclose($handle);
	
	/*End : Generate SQL DUMP and save to file database.sql*/
	$upload_path = array(
		'filename' => ($filename),
		'dir' => ($path_info['basedir'].'/db-backup/'.$filename),
		'url' => ($path_info['baseurl'].'/db-backup/'.$filename),
		'size' => 0
	);
	$upload_path['size']=filesize($upload_path['dir']);
	return $upload_path;
	
}


function wp_db_backup_wp_config_path() {
    $base = dirname(__FILE__);
    $path = false;
    if (@file_exists(dirname(dirname($base))."/wp-config.php")) {
        $path = dirname(dirname($base));
    } else {
		if (@file_exists(dirname(dirname(dirname($base)))."/wp-config.php")) {
			$path = dirname(dirname(dirname($base)));
		} else {
			$path = false;
		}
	}
    if ($path != false) {
        $path = str_replace("\\", "/", $path);
    }
    return $path;
}
function wp_db_backup_event_process() {
	$options = get_option('wp_db_backup_backups');
	$details = wp_db_backup_create_archive();

	if(!$options) {
		$options = array();
	}
	$options[] = array(
		'date' => mktime(),
		'filename' => $details['filename'],
		'url' => $details['url'],
		'dir' => $details['dir'],
		'size' => $details['size']
	);
	update_option('wp_db_backup_backups', $options);
	
}
function wp_db_backup_cron_schedules($schedules) {
	$schedules['weekly'] = array(
		'interval' => 604800,
		'display' => 'Once Weekly'
	);
	$schedules['monthly'] = array(
		'interval' => 2635200,
		'display' => 'Once a month'
	);
	return $schedules;
}
add_filter('cron_schedules', 'wp_db_backup_cron_schedules');

add_action('wp_db_backup_event', 'wp_db_backup_event_process');
add_action('wp', 'wp_db_backup_scheduler_activation');
function wp_db_backup_scheduler_activation() {
	$options= get_option('wp_db_backup_options');
	if ((!wp_next_scheduled('wp_db_backup_event')) && ($options['enable_autobackups'])) {
		wp_schedule_event(time(), $options['autobackup_frequency'], 'wp_db_backup_event');
	}
}

?>