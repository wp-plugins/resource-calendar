<?php
require_once(RCAL_PLUGIN_SRC_DIR . 'comp/resource-calendar-component.php');


abstract class ResourceCalendar_Control  {


	private $respons_type = ResourceCalendar_Response_Type::JASON;
	private $is_show_detail_msg = false;
	protected $config = null;
	

	public function __construct() {
		set_exception_handler(  array( &$this, '_error_action') );
	}

	public function set_config ($config) {
		$this->is_show_detail_msg = (ResourceCalendar_Config::DETAIL_MSG_OK == $config[ 'RCAL_CONFIG_SHOW_DETAIL_MSG' ]);
		$this->config = $config;
	}
	
	public function set_response_type ( $response_type) {
		$this->respons_type = $response_type;
	}
	
	
	public function exec() {
		try {
			$this->_checkRole();
			$this->do_action();
		} 
		catch (Exception $e) {
			$this->_error_action($e);
		}
	}
	
	public function do_require($class_name,$type,$permits) {
		if (! in_array($class_name,$permits) )  throw new Exception(__('invalid request',RCAL_DOMAIN));

		$path = RCAL_PLUGIN_SRC_DIR.$type.'/'.strtolower(str_replace('_','-',$class_name) ).'.php';
		if ( file_exists($path)) {
			require_once($path);
		}
		else {
		   throw new Exception(__('no class file',RCAL_DOMAIN));
		}
		if (!class_exists($class_name)) {
		   throw new Exception(__('no class ',RCAL_DOMAIN));
		}
		
	}

	abstract  function do_action();
	
	private function _error_action($e) {
		$this->_error_handler($e->getCode(),$e->getMessage(),$e->getFile(),$e->getLine(),$e->getTraceAsString());
	}

	public function _error_handler ( $errno, $errstr, $errfile, $errline, $errcontext ) {
//		if (error_reporting() === 0) return;
		$detail_msg = '';
		if ($this->is_show_detail_msg ) $detail_msg ="\n".$errfile.$errline."\n".$errcontext;
		if ($this->respons_type == ResourceCalendar_Response_Type::JASON || $this->respons_type == ResourceCalendar_Response_Type::JASON_406_RETURN) {
			if ($this->respons_type == ResourceCalendar_Response_Type::JASON_406_RETURN ) {
				header('HTTP/1.1 406 Not Acceptable');
			}
			$msg['status'] = 'Error';
			if (empty($errno) ) $msg['message'] = ResourceCalendar_Component::getMsg('E007',$errstr.$detail_msg);
			else $msg['message'] = $errstr.$detail_msg.'('.$errno.')';
			echo json_encode($msg);
		}
		elseif ($this->respons_type == ResourceCalendar_Response_Type::HTML ) {
			$msg =  nl2br($errstr.$detail_msg);
			echo '<div id="rcal_error_display"><h2>'.$msg.'</h2></div>';
		}
		elseif ($this->respons_type == ResourceCalendar_Response_Type::XML ) {
			if (empty($errno) ) $msg =  $errstr.$detail_msg;
			else $msg =  $errstr.' '.$errfile.$errline.'('.$errno.')';
			$msg = str_replace("'",'"',$msg);
			if (empty($errno) ) $msg = ResourceCalendar_Component::getMsg('E007',$msg);
			header('Content-type: text/xml');
			echo '<?xml version="1.0" encoding="UTF-8" ?>';
			echo "<data><action type='error' sid='".$_POST['id']."' tid='".$_POST['id']."' name='error' message='".$msg."' func='".$_POST['type']."' ></action><userdata name='result'>error</userdata><userdata name='message'>".$msg."</userdata></data>";
		}
		exit();
	}
	
	private function _checkRole() {
		ResourceCalendar_Component::checkRole(get_class($this));
	}


}		//class


