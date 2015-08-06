<?php

	require_once(RCAL_PLUGIN_SRC_DIR . 'control/resource-calendar-control.php');
	require_once(RCAL_PLUGIN_SRC_DIR . 'data/booking-data.php');
	require_once(RCAL_PLUGIN_SRC_DIR . 'comp/booking-component.php');

class Booking_Control extends ResourceCalendar_Control  {

	private $pages = null;
	private $datas = null;
	private $comp = null;
	
	private $action_class = '';
	
	private $permits = null;
	
	

	function __construct() {
		parent::__construct();
		if (empty($_REQUEST['menu_func']) ) {
			$this->action_class = 'BookingFront_Page';
//			$this->set_response_type(ResourceCalendar_Response_Type::HTML);
		}
		else {
			$this->action_class = $_REQUEST['menu_func'];
		}
		$this->datas = new Booking_Data();
		$this->set_config($this->datas->getConfigData());
		$this->comp = new Booking_Component($this->datas);
		$this->permits = array('BookingFront_Page','Booking_Get_Reservation','Booking_Get_Month','Booking_Edit');
	}
	
	
	
	public function do_action() {
		$this->do_require($this->action_class ,'page',$this->permits);
		$this->pages = new $this->action_class();
		$this->pages->set_config_datas($this->config);

		$user_login = $this->datas->getUserLogin();
		$this->pages->setUserLogin($user_login);
		$this->pages->setPluginAdmin($this->comp->isPluginAdmin());
		
		if ($this->action_class == 'BookingFront_Page' ) {
			
			if (!empty($user_login) )$this->pages->set_user_inf($this->datas->getUserInfDataByUserlogin($user_login));

			$this->pages->set_resource_datas($this->comp->getTargetResourceData());
			$this->pages->set_month_datas($this->comp->getMonthData());
			$this->pages->set_reservation_datas($this->datas->getAllEventData());
			if ($this->config['RCAL_CONFIG_USE_SUBMENU'] == ResourceCalendar_Config::USE_SUBMENU) {
				$this->pages->set_category_datas($this->datas->getCategoryDatas());
			}

		}
		elseif ($this->action_class == 'Booking_Get_Reservation' ) {
			if ($this->pages->check_request() ) {
				$this->pages->set_reservation_datas($this->datas->getAllEventData($this->pages->get_target_day(),true));
			}
			
		}
		elseif ($this->action_class == 'Booking_Get_Month' ) {
			if ($this->pages->check_request() ) {
				$this->pages->set_month_datas($this->comp->getMonthData($this->pages->get_target_day_from(),$this->pages->get_target_day_to()));
			}
			
		}
		elseif ($this->action_class == 'Booking_Edit') { 
			if ($this->pages->check_request() ) {
				$result = $this->comp->editTableData($user_login);
				$this->comp->serverCheck($result);
				$this->pages->set_table_data($result);
				if ($_POST['type'] == 'inserted' ) {
					$this->pages->set_reservation_cd($this->datas->insertTable($result));
				}
				elseif ($_POST['type'] == 'updated' ) {
					$this->datas->updateTable($result);
				}
				elseif ($_POST['type'] == 'deleted' ) {
					$this->datas->cancelTable($result);
					
				}
//				$this->comp->sendMail($this->pages->get_table_data());
				$reread = $this->datas->getTargetReservationData($this->pages->get_reservation_cd());
				$this->comp->sendMail($reread[0]);
				$this->pages->set_reservation_datas($this->datas->getAllEventData($this->pages->get_target_day()));
			}
		}

		$this->pages->show_page();
		if ($this->action_class != 'BookingFront_Page') die();

	}
}		//class

$staffs = new Booking_Control();
$staffs->exec();