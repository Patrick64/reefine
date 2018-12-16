<?php
/**
 * Generic field class used to gather informaiton about the field in the database.
 * @author Patrick
 *
 */
class Reefine_field {
	/**
	 * Field name as it appears int he reefine tag eg colour, store:price
	 * @var unknown
	 */
	protected $field_name;
	/**
	 * Column name to be used in SQL for value field, used in URL. eg field_id_27
	 * @var unknown
	 */
	protected $column_name;
	/**
	 * Column name in field for title field that is displayed to user
	 * @var unknown
	 */
	protected $title_column_name;
	/**
	 * Additional sql to be added to join
	 * @var unknown
	 */
	protected $join_sql = '';
	/**
	 * Name of field in channel entry eg store:price would be store
	 * @var unknown
	 */
	protected $ee_field_name = '';
	protected $field_label = '';
	protected $ee_field_info;
	public $ee_type = '';
	/**
	 * Reefine object
	 * @var Reefine
	 */
	protected $reefine;
	/**
	 * Database column name (eg field_id_2)
	 * @var unknown
	 */
	protected $db_column;
	
	public $channel_data_alias = '';
	
	public $channel_titles_alias = '';
	
	function __construct($reefine, $field_name,$parent_field_name='',$child_field_name='') {
		
		$this->reefine = $reefine;
		$this->field_name = $field_name;
		$dbprefix = $reefine->dbprefix;
		
		$this->channel_titles_alias = "{$dbprefix}channel_titles";
		$this->assign_field_info($field_name);
		if (isset($this->ee_field_info)) {
			// $this->channel_data_alias = "{$dbprefix}channel_data";
			if (isset($this->ee_field_info['field_id'])) {
				$this->channel_data_alias = "{$dbprefix}channel_data_field_{$this->ee_field_info['field_id']}";
			}
		}
	}
	
	// get bit of SQL for various columns in the filter:
	
	function get_title_column() {
		return $this->get_value_column();
	}
	
	function get_value_column($table='') {
		if ($table!='') // a table name/alias is specified so use that instead
			return $table  . '.' . $this->get_field_by_key($this->field_name,'field_column');
		else if ($this->get_field_by_key($this->field_name,'is_title_field'))
			return $this->channel_titles_alias  . '.' . $this->get_field_by_key($this->field_name,'field_column');
		else {
			return $this->channel_data_alias . '.' . $this->get_field_by_key($this->field_name,'field_column');
		}
			
	}
	
	// normal fields don't an ID or need for extra columns
	
	function get_filter_id_field() {
		return '';
	}
	
	function get_filter_extra_columns() {
		return array();
	}
	
	function get_filter_extra_clause() {
		return '';
	}
	
	function get_filter_order_by() {
		return '';
	}
	
	function get_field() {
		return $this->get_field_by_name($this->ee_field_name);
	}
	
	function get_join_sql() {
		// return '';
		if ($this->get_field_by_key($this->field_name,'is_title_field')) {
			return '';
		} else {
			return " JOIN $this->channel_data_alias ON {$this->reefine->dbprefix}channel_titles.entry_id = $this->channel_data_alias.entry_id  ";
		}
	}
	
	function assign_field_info($ee_field_name) {
		if (isset($this->reefine->_custom_fields[$this->reefine->site][$ee_field_name])) {
			$ee_field = $this->reefine->_custom_fields[$this->reefine->site][$ee_field_name];
			$this->ee_field_name = $ee_field_name;
			$this->field_label = $ee_field['field_label'];
			$this->ee_type= $ee_field['field_type'];
			$this->db_column = $ee_field['field_column'];
			$this->ee_field_info = $ee_field;
		} else {
			throw new Exception("Reefine error: Field $ee_field_name not found");
		}
	}
	function get_field_by_name($field_name) {
		if (isset($this->reefine->_custom_fields[$this->reefine->site][$field_name])) 
			return $this->reefine->_custom_fields[$this->reefine->site][$field_name];
		else
			return null;
	}
	
	// get an attribute of a field (eg is_title_field)
	function get_field_by_key($field_name,$key) {
		$field = $this->get_field_by_name($field_name);
		return $field[$key];
	}
	
	
	
	
	/**
	 * Get where clause to be used
	 * @param unknown $filter_group
	 * @param unknown $in_list
	 * @param string $value
	 * @return string
	 */
	function get_where_clause($filter_group,$in_list=false,$value=false) {
		if (isset($filter_group->delimiter) && $filter_group->delimiter!='') {
			$delimiter = $filter_group->db->escape($filter_group->delimiter);
			if ($filter_group->join=='or' || $filter_group->join=='none') {
				return " instr(concat({$delimiter},{$filter_group->get_field_value_column($this)},{$delimiter}),concat({$delimiter},{$value},{$delimiter}))";
			} else {
				return " instr(concat({$delimiter},{$filter_group->get_field_value_column($this)},{$delimiter}),concat({$delimiter},{$value},{$delimiter}))";
			}
		} else {
			if ($filter_group->join=='or' || $filter_group->join=='none') {
				return " {$filter_group->get_field_value_column($this)} IN (" . implode(',',$in_list) . ")";
			} else {
				return " {$filter_group->get_field_value_column($this)} = {$value}";
			}
		}
		
	}
		
	
	
}
