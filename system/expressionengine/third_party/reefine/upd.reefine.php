<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Reefine_upd {

    var $version        = '1.3.1';

    function Reefine_upd()
    {
        $this->EE =& get_instance();
    }
    
    

	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */	
	function install()
	{
		$this->EE->load->dbforge();

		$data = array(
			'module_name' => 'Reefine' ,
			'module_version' => $this->version,
			'has_cp_backend' => 'n',
			'has_publish_fields' => 'n'
		);

		$this->EE->db->insert('modules', $data);


		return TRUE;
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall()
	{
		$this->EE->load->dbforge();

		$this->EE->db->where('module_name', 'Reefine');
		$this->EE->db->delete('modules');

		return TRUE;
	}

    

}
    