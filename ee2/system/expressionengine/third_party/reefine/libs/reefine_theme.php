<?php
class Reefine_theme
{
	/**
	 * The Reefine class
	 * @var unknown_type
	 */
	var $module;

	function __construct(&$module) {
		$this->module =& $module;
	}

	function before_parse_tag_data() {

	}

}