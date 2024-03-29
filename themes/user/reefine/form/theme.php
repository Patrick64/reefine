<?php
class Reefine_theme_custom extends Reefine_theme
{
	/**
	 * The Reefine class
	 * @var Reefine
	 */
	var $module;

	function __construct(&$module) {
		parent::__construct($module);
	}

	function before_parse_tag_data() {

		if (true || !isset($this->module->EE->TMPL->var_pair['filter_groups'])) {
			
			// make the <form> tag. POST is more likely to work with people's .htaccess setup but GET avoids XID problems.
			if ($this->module->method=='get' || $this->module->method=='ajax')
				$form_html = '<form id="reefine_form" method="get" action="">';
			else
				$form_html = ee()->functions->form_declaration(array('action' => ee()->functions->fetch_current_uri(),'name' => 'reefine_form','id' => 'reefine_form'));
			$theme_url = $this->module->EE->config->item('theme_folder_url') . 'user/reefine/' . $this->module->theme_name . '/';
			$filter_groups = file_get_contents(PATH_THIRD_THEMES . 'reefine/' . $this->module->theme_name . '/filter_groups.html');
			$filter_groups = str_replace('{theme_url}',$theme_url,$filter_groups);
			$content = $filter_groups .
			'<div class="reefine_entries">' .
			$this->module->tagdata .
			'</div>';

			if ($this->module->is_ajax_request) {
				// send back just the
				$this->module->tagdata = $content;
			} else {
				$this->module->tagdata = '<link rel="stylesheet" type="text/css" href="' . $theme_url . 'styles.css" />' .
						'<div class="reefine" id="reefine">' .
						$form_html .
						$content .
						'</form></div>';
				if ($this->module->method=='ajax')
					$this->module->tagdata .= '<script type="text/javascript" src="' . $theme_url . 'ajax.js"></script>';
			}

		}
	}
}