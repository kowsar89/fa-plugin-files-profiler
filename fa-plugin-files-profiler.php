<?php
/**
 * Plugin Name: Plugin Files Profiler
 * Plugin URI: http://kowsarhossain.com
 * Description: This plugin shows information of loaded plugin files in any page
 * Version: 1.0.0
 * Author: Md. Kowsar Hossain
 * Author URI: http://kowsarhossain.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 */
if ( ! defined( 'WPINC' ) ) die;

new Fa_Plugin_Performance_Checker();
class Fa_Plugin_Performance_Checker{
	public $plugin_name = 'Plugin Files Profiler';
	public $plugin_folder_names = array();
	public function __construct(){
		add_action('wp_footer', array( $this, 'init' ), 9999999 );
	}
	public function init(){
		if (!current_user_can('administrator')) {
			return;
		}
		$plugin_folder_names = $this->genrate_active_plugin_list();

		$output = '';
		$plugin_files = array();
		$included_files = get_included_files();
		foreach ($included_files as $included_file) {
			foreach ($plugin_folder_names as $plugin_folder_name=>$plugin_name) {
				$plugin_temp_name = DIRECTORY_SEPARATOR.$plugin_folder_name.DIRECTORY_SEPARATOR;
				$pos = strpos($included_file, $plugin_temp_name);
				if ($pos !== false) {
					$plugin_files[$plugin_name][] = $included_file;
				}
			}
		}
		$plugin_data = array();
		$totalfiles = 0;
		$totalsize = 0;
		$totallines = 0;
		foreach ($plugin_files as $pluginname => $pluginfiles) {
			$plugin_data[$pluginname]['num_of_files'] = sizeof($pluginfiles);
			$plugin_data[$pluginname]['size'] = $this->total_size($pluginfiles);
			$plugin_data[$pluginname]['lines'] = $this->total_line_numbers($pluginfiles);

			$totalfiles += $plugin_data[$pluginname]['num_of_files'];
			$totalsize += $plugin_data[$pluginname]['size'];
			$totallines += $plugin_data[$pluginname]['lines'];
		}

		uasort($plugin_data, array( $this, 'sort' ));
		//ksort($plugin_data);

        $output .= '<div class="fa-perf">';
        $output .= '<h2>'.$this->plugin_name.'</h2>';
        $output .= '<h6>Info of PHP files from plugins loaded in this page:</h6>';
        $output .= '<table cellspacing="0" cellpadding="0" border="0">';
        $output .= '<thead>';
        $output .= '<tr><td><strong>Plugins</strong></td><td><strong>Files</strong></td><td><strong>Lines</strong></td><td><strong>Size (KB)</strong></td></tr>';
        $output .= '</thead>';
        $output .= '<tbody>';
		foreach ($plugin_data as $rowname => $row) {
           $output .= '<tr valign="top"><td>'.$rowname.'</td><td>'.$row['num_of_files'].'</td><td>'.$row['lines'].'</td><td>'.$row['size'].'</td></tr>';
		}
		$output .= '<tr valign="top"><td><b>Total</b></td><td><b>'.$totalfiles.'</b></td><td><b>'.$totallines.'</b></td><td><b>'.$totalsize.'</b></td></tr>';
        $output .= '</tbody>';    
        $output .= '</table><small>Note: Only admin can see this info</small></div>';
        $output .= '
            <style type="text/css">
				.fa-perf {background-color: #c0d0a7;color: #000;padding: 10px 0;text-align: center;padding-bottom:40px;}
				.fa-perf table {margin: 0 auto;width: 626px;}
				.fa-perf table tr td {border: 1px solid #000;padding: 10px;}
            </style>';
		echo $output;
	}

	private function total_size($files){
		$size = 0;
		foreach ($files as $file) {
			$size += filesize($file);
		}
		$size = round($size/1024);
		return $size;
	}
	private function total_line_numbers($files){
		$linecount = 0;
		foreach ($files as $file) {
			$handle = fopen($file, "r");
			while(!feof($handle)){
			  $line = fgets($handle);
			  $linecount++;
			}
			fclose($handle);
		}
		return $linecount;
	}
	function genrate_active_plugin_list(){
		$plugins = get_option('active_plugins');
		$result = array();
		foreach ($plugins as $plugin) {
			$name = explode( '/' , $plugin);
			$foldername = $name[0];
			$filename = $name[1];
			$abspath = WP_PLUGIN_DIR.'/'.$foldername.'/'.$filename;
			$data = get_plugin_data($abspath);
			$result[$foldername] = $data['Name'];
		}
		return $result;
	}
	private function sort($a, $b) {
        return $a["size"] - $b["size"];
	}
}
