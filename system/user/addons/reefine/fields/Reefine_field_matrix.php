<?php

class Reefine_field_matrix extends Reefine_field {

	private $relation_field_id;

	private $child_field_name;
	private $parent_field_name;
	public $table_alias;
	private $grid_field;
	public $table_name;

	function __construct($reefine,$field_name,$parent_field_name,$child_field_name) {
		parent::__construct($reefine, $parent_field_name);

		$this->reefine = $reefine;
		$this->parent_field_name = $parent_field_name;
		$this->child_field_name=$child_field_name;

		$grid_fields = Reefine_util_matrix_fields::get_instance($reefine);

		$this->grid_field = $grid_fields->get_grid_field($this->ee_field_info['field_id'],$child_field_name);

		$this->table_alias = 'matrix_' . $this->grid_field['field_id']; //preg_replace('/[^A-Z0-9]/i','_',$this->relation_field_id);
		$this->table_name = "{$this->reefine->dbprefix}matrix_data";

	}

	function get_value_column($table='') {
		return "{$this->table_alias}.col_id_{$this->grid_field['col_id']}"; // . $this->get_field_by_key($this->child_field_name,'field_column');
	}

	function get_title_column() {
		return $this->get_value_column();
	}

	function get_join_sql() {
		// join the channel_grid_field_... table
		$joins=array("LEFT OUTER JOIN {$this->reefine->dbprefix}matrix_data {$this->table_alias} " .
		"ON {$this->table_alias}.entry_id = {$this->channel_data_alias}.entry_id ");

		return $joins;
	}
}
