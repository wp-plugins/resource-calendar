<?php 

	require_once(RCAL_PLUGIN_SRC_DIR . 'page/resource-calendar-page.php');

	
class Category_Edit extends ResourceCalendar_Page {
	
	private $table_data = null;

	
	function __construct() {
		parent::__construct();
	}

	
	public function set_table_data($table_data) {
		$this->table_data = $table_data;
	}
	
	public function set_category_cd($category_cd) {
		 $this->table_data['category_cd'] = $category_cd;
	}

	public function get_category_cd() {
		return $this->table_data['category_cd'];
	}

	
	public function check_request() {
		if	( ($_POST['type'] != 'inserted' ) && empty($_POST['category_cd']) ) {
			throw new Exception(ResourceCalendar_Component::getMsg('E901',basename(__FILE__).':'.__LINE__) );
		}
		$msg = null;
		if ($_POST['type'] != 'deleted' ) {
			if (ResourceCalendar_Page::serverCheck(array('category_name','category_patern','category_value','target_table'),$msg) == false) {
				throw new Exception($msg ,__LINE__);
			}
		}
	}

	public function show_page() {

		$this->table_data['no'] = __($_POST['type'],RCAL_DOMAIN);
		$this->table_data['check'] = '';

		
		if ( $_POST['type'] != 'deleted' ) {
			
			$this->table_data['rcal_category_name'] = htmlspecialchars($this->table_data['category_name']);
			$this->table_data['category_values'] = htmlspecialchars($this->table_data['category_values']);
			$this->table_data['remark'] = '';
		}
		
		
		echo '{	"status":"Ok","message":"'.ResourceCalendar_Component::getMsg('N001').'",
				"set_data":'.json_encode($this->table_data).' }';
	}


}