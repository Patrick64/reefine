<?php

/**
 * Field class for dealing with entries with the Publisher Module
 * @author Patrick
 *
 */
class Reefine_field_publisher extends Reefine_field {
	
	private $session_language_id;
	private $table_alias;
	private $table_alias_titles;
	private $table_alias_data;
	
	function __construct($reefine,$field_name,$ee_field_name,$child_field_name='') {
		parent::__construct($reefine, $ee_field_name, '');
		
		$this->reefine = $reefine;
		
		$this->field_name = $ee_field_name;
		
		$this->session_language_id = intval($this->reefine->EE->publisher_language->current_language['id']);
		
		// $this->table_alias = 'relation_' . preg_replace('/[^A-Z0-9]/i','_',$ee_field_name);
		$this->table_alias_titles = 'relation_' . preg_replace('/[^A-Z0-9]/i','_',$ee_field_name) . '_titles';
		$this->table_alias_data = 'relation_' . preg_replace('/[^A-Z0-9]/i','_',$ee_field_name) . '_data';
		
		
	}
	
	function get_value_column($table='') {
		if ($this->get_field_by_key($this->field_name,'is_title_field')) // if it's a column that's normally in channel_titles
			return "IFNULL( {$this->table_alias_titles}.{$this->db_column} , {$this->channel_titles_alias}.{$this->db_column}) " ;
		else
			return "IFNULL( {$this->table_alias_data}.{$this->db_column} , {$this->channel_data_alias}.{$this->db_column}) ";
	}
		
	function get_title_column() {
		return $this->get_value_column();
	}
	
	function get_join_sql() {
		$joins = array( "LEFT OUTER JOIN {$this->reefine->dbprefix}publisher_data {$this->table_alias_data} " .
		"ON {$this->table_alias_data}.entry_id = {$this->channel_data_alias}.entry_id " .
		"AND " . $this->reefine->get_status_where_clause($this->reefine->status,"{$this->table_alias_data}.publisher_status") .
		"AND {$this->table_alias_data}.publisher_lang_id = {$this->session_language_id} ");
		if ($this->get_field_by_key($this->field_name,'is_title_field')) // if it's a column that's normally in channel_titles
			$joins[] = "LEFT OUTER JOIN {$this->reefine->dbprefix}publisher_titles {$this->table_alias_titles} " .
			"ON {$this->table_alias_titles}.entry_id = {$this->channel_data_alias}.entry_id " .
			"AND " . $this->reefine->get_status_where_clause($this->reefine->status,"{$this->table_alias_titles}.publisher_status") . 
			"AND {$this->table_alias_titles}.publisher_lang_id = {$this->session_language_id} ";
		return $joins;
	}
	
}
