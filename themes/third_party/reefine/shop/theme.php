<?php
class Reefine_theme_shop extends Reefine_theme 
{
	/**
	 * The Reefine class
	 * @var unknown_type
	 */
	var $module;
	
	function __construct(&$module) {
		parent::__construct($module);
	}
	
	function before_parse_tag_data() {
	$a=1;
		if (!isset($this->module->EE->TMPL->var_pair['filter_groups'])) {
			$theme_url = $this->module->EE->config->item('theme_folder_url') . 'third_party/reefine/shop/';
			$filter_groups = file_get_contents(PATH_THIRD_THEMES . 'reefine/shop/filter_groups.html');
			//$active_filters = file_get_contents(PATH_THIRD_THEMES . 'reefine/basic/active_filters.html');
			$filter_groups = str_replace('{theme_url}',$theme_url,$filter_groups);
			//$active_filters = str_replace('{theme_url}',$theme_url,$active_filters);
			$this->module->tagdata = '<link rel="stylesheet" type="text/css" href="' . $theme_url . 'reefine_basic.css" />' .
					'<div class="reefine" id="reefine">' .
					$filter_groups .
					'<div class="reefine_entries">' .
					$this->module->tagdata .
					'</div></div>';
		}
	}
		
}