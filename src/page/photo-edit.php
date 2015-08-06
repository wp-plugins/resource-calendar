<?php

	require_once(RCAL_PLUGIN_SRC_DIR . 'page/resource-calendar-page.php');

	
class Photo_Edit extends ResourceCalendar_Page {

		
	private $photo_id = null;
	private $resize_file_path = null;
	
	

	function __construct() {
		parent::__construct();

	}
	
	public function check_request() {
		//nonceのチェックのみ
		if (ResourceCalendar_Page::serverCheck(array(),$msg) == false) {
			throw new Exception($msg );
		}
		if	( ($_REQUEST['type'] == 'deleted' ) && empty($_POST['photo_id']) ) {
			throw new Exception(ResourceCalendar_Component::getMsg('E901',basename(__FILE__).':'.__LINE__) );
		}
		$msg = null;
		if ($_REQUEST['type'] == 'inserted' ) {
			//ファイル名などのチェック。不正に対するチェックなので$_FILESはみないで直接ファイルをチェックする
			$attr = strtolower(substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.') + 1));
			if ($attr == 'jpg' || $attr == 'png' ||$attr == 'gif'){}
			else {
				throw new Exception(ResourceCalendar_Component::getMsg('E901',__('FILE TYPE ERROR',RCAL_DOMAIN)));
			}
			$size = getimagesize( $_FILES['file']['tmp_name']);
			if ($size[2] == IMAGETYPE_JPEG || $size[2] == IMAGETYPE_PNG || $size[2] != IMAGETYPE_GIF) {}
			else {
				throw new Exception(ResourceCalendar_Component::getMsg('E901',__('FILE TYPE ERROR',RCAL_DOMAIN)));
			}
			if (filesize( $_FILES['file']['tmp_name']) > RCAL_MAX_FILE_SIZE * 1000 * 1000) {	
				throw new Exception(ResourceCalendar_Component::getMsg('E901',__('FILE MAX SIZE ERROR(10M)',RCAL_DOMAIN)));
			}
		}
	}
	
	public function set_photo_id($photo_id) {
		$this->photo_id = $photo_id;
	}
	public function set_resize_file_path($resize_file_path) { 
		$this->resize_file_path = $resize_file_path;

	}
	
	public function show_page() {
		if ($_REQUEST['type'] == 'deleted') 
			echo '{	"status":"Ok" }';
		else 
			echo '{	"status":"Ok","photo_id":"'.$this->photo_id.'","resize_path":"'.$this->resize_file_path.'"}';
	}	//show_page
}		//class

