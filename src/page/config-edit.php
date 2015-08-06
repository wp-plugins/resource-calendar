<?php 

	require_once(RCAL_PLUGIN_SRC_DIR . 'page/resource-calendar-page.php');

	
class Config_Edit extends ResourceCalendar_Page {
	
	private $table_data = null;
	
	public function __construct() {
		parent::__construct();
	}

	
	public function set_table_data($table_data) {
		$this->table_data = $table_data;
	}
	
	
	public function check_request() {
		$checks = array();
		if ($_POST['type'] == 'updated' ) {
			$checks = array('open_time','close_time','time_step','closed_day_check','name','address','mail','tel','cal_size');
		}
		
		if (ResourceCalendar_Page::serverCheck($checks,$msg) == false) {
			throw new Exception($msg,__LINE__ );
		}
		if ($_POST['type'] == 'updated' ) {
			//時間のチェック
			$edit_holidays = array();
			if (isset($_POST["rcal_closed_day"]) && !empty($_POST["rcal_closed_day"]) ) {
				$holidays_arr = explode(',',$_POST["rcal_closed_day"]);
				$holidays_detail_arr = explode(',',$_POST["rcal_closed_day_detail"]);
				
				foreach ($holidays_arr as $k1 => $d1 ) {
					$tmp_detail = "";
					if (!empty($holidays_detail_arr[$k1]) ) {
						$tmp_detail_arr = array();
						$tmp_detail_arr = explode(';',str_replace(":","",$holidays_detail_arr[$k1]));
						foreach ($tmp_detail_arr as $k2 => $d2 ) {
							$edit_tmp =  substr("0" . $d2,-4);
							if (! ResourceCalendar_Component::checkTime($edit_tmp)  ) {
								throw new Exception(ResourceCalendar_Component::getMsg('E202', basename(__FILE__).':'.__LINE__),1);
							}
							$tmp_detail_arr[$k2] = $edit_tmp;
						}
					}
					$edit_holidays[] = $d1 . ';' . implode(';',$tmp_detail_arr);
				}
				$_POST["rcal_closed_day"] = implode(',',$edit_holidays);
			}
		}
	}

	public function show_page() {

		$this->table_data['no'] = _($_POST['type']);
		$this->table_data['check'] = '';
		if ($_POST['type'] != 'updated' ) {
			$this->table_data['target_date'] = htmlspecialchars($_POST['rcal_target_date'],ENT_QUOTES);
			if  ($_POST['type']	== 'inserted' ) {
				$title = __('Special holiday',RCAL_DOMAIN);
				if ($_POST['rcal_status']==ResourceCalendar_Status::OPEN) $title = __('Business day',RCAL_DOMAIN);
				$this->table_data['status_title'] = $title;
				$this->table_data['status'] = htmlspecialchars($_POST['rcal_status'],ENT_QUOTES);
			}
		}
		
		echo '{	"status":"Ok","message":"'.ResourceCalendar_Component::getMsg('N001').'",
				"set_data":'.json_encode($this->table_data).' }';
	}


}