<?php 

	require_once(RCAL_PLUGIN_SRC_DIR . 'page/resource-calendar-page.php');

	
class Booking_Edit extends ResourceCalendar_Page {
	
	

	protected $table_data = null;
	private $reservation_datas = null;
	private $target_day = '';


	private $msg = '';
	private $checkOk = false;

	private $insert_max_day = '';

	public function __construct() {
		parent::__construct();
	}
	public function get_target_day() {
		return $this->target_day;
	}

	public function set_reservation_datas($reservation_datas) {
		$this->reservation_datas = $reservation_datas;
		
	}
	public function set_config_datas($config_datas) {
		parent::set_config_datas($config_datas);
		$this->insert_max_day = ResourceCalendar_Component::computeDate($config_datas['RCAL_CONFIG_AFTER_DAY']);

	}
	
	public function set_table_data($table_data) {
		$this->table_data = $table_data;
	}
	
	public function get_table_data() {
		return $this->table_data;
	}
	
	public function set_reservation_cd($reservation_cd) {
		$this->reservation_cd = $reservation_cd;
		$this->table_data['reservation_cd'] = $reservation_cd;
	}
	
	public function get_reservation_cd () {
		return $this->table_data['reservation_cd'];
	}


	public function check_request() {
		$this->_parse_data();


		$check_item = array('customer_name','booking_tel','booking_mail','resource_cd','time_from','time_to');

		$this->checkOk = parent::serverCheck($check_item,$this->msg);
		//from toの大小
		$from = strtotime($_POST['rcal_time_from']);
		$to = strtotime($_POST['rcal_time_to']);
		if ($from >= $to) {
		  $this->checkOk = false;
		  $this->msg  .=  (empty($this->msg) ? '' : "\n"). 'EM003 '.__('Check reserved time ',RCAL_DOMAIN);
		}		
		//fromは指定分以降より後
		$limit_time = new DateTime(date_i18n('Y-m-d H:i'));
		$limit_time->add(new DateInterval("PT".$this->config_datas['RCAL_CONFIG_RESERVE_DEADLINE']."M"));
		if ($limit_time->getTimestamp() > $from) {
		  $this->checkOk = false;
		  $this->msg .=  (empty($this->msg) ? '' : "\n"). 'EM004 '.sprintf(__('Your reservation is possible from %s.',RCAL_DOMAIN),$limit_time->format(__('m/d/Y',RCAL_DOMAIN).' H:i'));
		}
		//未来も制限がある
		if (strtotime($this->insert_max_day) < $from) {
		  $this->checkOk = false;
		  $this->msg .=  (empty($this->msg) ? '' : "\n").  'EM002 '.sprintf(__('The future times can not reserved. please less than %s days ',RCAL_DOMAIN),$this->config_datas['RCAL_CONFIG_AFTER_DAY']);
		}
		return $this->checkOk;		
		
	}
	
	private function _parse_data() {
		$_POST['status'] = '';
		//YYYY-MM-DD HH:MM 最後に読み直すために
		$split = explode(' ',$_POST['rcal_time_from']);
		$this->target_day = str_replace('-','',$split[0]); 
	}

	public function show_page() {
		$first_hour = substr($this->config_datas['RCAL_CONFIG_OPEN_TIME'],0,2);
		if ($this->checkOk ) {	
			$res = parent::echoMobileData($this->reservation_datas,$this->target_day ,$first_hour);
			if (empty($res[$this->target_day]) ) {
				 $res[$this->target_day] = '{"e":0}';
			}
			$msg = __('The reservation is compledted',RCAL_DOMAIN);
			if (!$this->isPluginAdmin($this->user_login) ) {
				if 	($this->config_datas['RCAL_CONFIG_CONFIRM_STYLE'] ==  ResourceCalendar_Config::CONFIRM_BY_ADMIN ) {
					$msg = __('The reservation is not completed.After your reservation confirmed by administrator,you will receive E-mail ',RCAL_DOMAIN);
				}
				elseif 	($this->config_datas['RCAL_CONFIG_CONFIRM_STYLE'] ==  ResourceCalendar_Config::CONFIRM_BY_MAIL ) {
					$msg = __('The reservation is not completed.Please confirm your reservation by [confirm form] in E-mail ',RCAL_DOMAIN);
				}
				//確認なしなので完了にする。
				else {
				}
			}
			echo '{	"status":"Ok","message":"'.$msg.'",
			"set_data":'.'{"'.$this->target_day.'":'.$res[$this->target_day].'} }';
		}
		else {
			$msg['status'] = 'Error';
			if (empty($errno) ) $msg['message'] = $this->msg;
			else $msg['message'] = $errstr.$detail_msg.'('.$errno.')';
			echo json_encode($msg);
		}
	}


}