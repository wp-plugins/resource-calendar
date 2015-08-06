<?php
class ResourceCalendar_Page {
	const INPUT_BOTTOM_MARGIN = 20;
	const SHORT_WIDTH = '50px';
	const MIDDLE_WIDTH = '120px';
	const LONG_WIDTH = '150px';
	
	const TARGET_DATE_PATERN = 'day';
	
	private $version = '1.0';
	protected $nonce = '';
	protected $user_login = "";
	protected $is_plugin_admin = false;

	protected $config_datas = null;

	public function __construct() {
		$this->nonce = wp_create_nonce(session_id());
		
	}
	
	public function setUserLogin($user_login) {
		$this->user_login = $user_login;
	}

	public function setPluginAdmin($is_plugin_admin) {
		$this->is_plugin_admin = $is_plugin_admin;
	}
	
	public function isPluginAdmin() {
		return $this->is_plugin_admin;
	}
	
	public function set_config_datas($config_datas) {
		if ($config_datas['RCAL_CONFIG_USE_SESSION_ID'] == ResourceCalendar_Config::USE_NO_SESSION){
			$this->nonce = wp_create_nonce(RCAL_PLUGIN_DIR);
		}
		$this->config_datas = $config_datas;
	}
	
	static function getResponseType() {
		if (empty($_POST['func']) )	return ResourceCalendar_Response_Type::JASON_406_RETURN;
		else return Response_Type::JASON;
	}

	static function echoInitData($datas) {
		$data_cnt = count($datas);
		//indexが歯抜けの可能性があるので降りなおす
		$i = 0;
		
		if ($datas ) {
			foreach ($datas as $k1 => $d1) {
				$i++;
				$datas[$k1]['no'] = sprintf("%03d",$i);
				$datas[$k1]['check'] = 0;	//[TODO]いらない？
			}
		}
		$jdata = array();
		$jdata['iTotalRecords'] = $data_cnt;
		$jdata['iTotalDisplayRecords'] = $data_cnt;
		$jdata['sEcho'] = 1;
		if (is_null($datas) )$datas = array();
		$jdata['aaData'] = $datas;
		echo json_encode($jdata);
	}

	static function echoOpenCloseTime($tag,$open,$close,$step,$plusClass="") {
		echo '<select id="'.$tag.'" name="'.$tag.'" class="rcal_sel rcal_time '.$plusClass.'" >';
		$dt = new DateTime(substr($open,0,2).":".substr($open,2,2));
		$last_hour = substr($close,0,2).":".substr($close,2,2);
		$dt_max = new DateTime($last_hour);
		$echo_data =  '';
		while($dt <= $dt_max ) {
			$echo_data .= '<option value="'.$dt->format("H:i").'" >'.$dt->format("H:i").'</option>';
			$dt->modify("+".$step." minutes");
		}
		echo $echo_data;	
		echo '</select>';

	}
	
	static function echoTime25Check() {
		$msg =  __('Time step is wrong ?');
		echo <<<EOT
		function _fnCheckTimeStep(step,targetMin){
			if ( targetMin%step === 0 ) return true;
			alert("{$msg}");
			return false;
		}
EOT;
	}
	
//kokomade


	static function echoClientItem($items) {
		$item_contents = ResourceCalendar_Page::setItemContents();	
		echo 'var check_items = { ';
		$tmp = array();
		if (is_array($items) ){
			foreach ($items as $d1) {
				$add_class = '';
				$tmp[] ='"'.$item_contents[$d1]['id'].'": '.
						'{'.
						' "id" : "'.$item_contents[$d1]['id'].'"'.
						',"class" : "'.implode(" ",$item_contents[$d1]['check'])." ".implode(" ",$item_contents[$d1]['class']).'"'.
						',"label" : "'.$item_contents[$d1]['label'].'"'.
						',"tips" : "'.$item_contents[$d1]['tips'].'"'.
						'}';
			}
		}
		echo join(',',$tmp);
		echo '};';
		self::echoHtmlpecialchars();

	}

	static function echoClientItemMobile($items) {
		$item_contents = ResourceCalendar_Page::setItemContents();	
		echo 'var check_items = { ';
		$tmp = array();
		if (is_array($items) ){
			foreach ($items as $d1) {
				$add_class = '';
				$tmp[] ='"'.$item_contents[$d1]['id'].'": '.
						'{'.
						' "id" : "'.$item_contents[$d1]['id'].'"'.
						',"label" : "'.$item_contents[$d1]['label'].'"'.
						'}';
			}
		}
		echo join(',',$tmp);
		echo '};';
		self::echoHtmlpecialchars();

	}
	

	static function echoHtmlpecialchars() {
		echo <<<EOT
			function htmlspecialchars_decode (data) {
				if (data ) {
					data = data.toString().replace(/&lt;/g, "<").replace(/&gt;/g, ">");
					data = data.replace(/&#0*39;/g, "'"); 
					data = data.replace(/&quot;/g, '"');
					data = data.replace(/&amp;/g, '&');
				}
				return data;
			}
			function htmlspecialchars (data) {
				if (data) {
					data = data.toString();
					data = data.replace(/&/g, "&amp;");
					data = data.replace(/</g, "&lt;").replace(/>/g, "&gt;");
					data = data.replace(/'/g, "&#039;");
					data = data.replace(/\"/g, "&quot;");
				}
				return data;
			}
EOT;
	}
	
	static  function echoSetItemLabel($is_Tables = true) {
		echo <<<EOT
			for(index in check_items) {
				if (check_items[index] ) {
					var id = check_items[index]["id"];
					if (check_items[index]["label"] == "") {
						\$j("#"+id).addClass(check_items[index]["class"]);
					}
					else {
						var ast = "";
						if (check_items[index]["class"].indexOf("chk_required") != -1) {
							ast = "<span class=\"rcal_req\">*</span>";
						}
						\$j("#"+id).addClass(check_items[index]["class"]);
						\$j("#"+id).before("<label id=\""+id+"_lbl\" for=\""+id+"\" >"+check_items[index]["label"]+ast+":<span class=\"small\"></span></label>");
					}
				}
			}
EOT;
		if ($is_Tables ) {
			echo <<<EOT2
			\$j(window).bind('resize', function () {
					target.fnAdjustColumnSizing(true);
			} );
			
EOT2;
		}
	}
	
	static  function echoSetItemLabelMobile() {
		echo <<<EOT
			for(index in check_items) {
				if (check_items[index] ) {
					var id = check_items[index]["id"];
					\$j("#"+id).attr("placeholder",check_items[index]["label"]);
					\$j("#"+id).parent().before("<li class=\"rcal_label\"><label id=\""+id+"_lbl\" for=\""+id+"\" >"+check_items[index]["label"]+":</label></li>");
				}
			}
EOT;
	}
	

	static function echoCommonButton($add_operation = '"inserted"'){
		$show = __('Show Details',RCAL_DOMAIN);
		$hide = __('Hide Details',RCAL_DOMAIN);
		echo <<<EOT
			\$j("#rcal_button_div input").addClass("rcal_button");
			fnDetailInit();	
			\$j("#button_insert").click(function(){
				if (\$j("#data_detail").is(":hidden")) {
					\$j("#data_detail").show();
					return;
				}
				fnClickAddRow({$add_operation});
			});
			\$j("#button_update").click(function(){
				fnClickAddRow("updated");
			});
			\$j("#button_clear").click(function(){
				fnDetailInit(true);	
				\$j(target.fnSettings().aoData).each(function (){
					\$j(this.nTr).removeClass("row_selected");
				});
			});
			\$j("#button_detail").click(function(){
				\$j("#data_detail").toggle();
				if (\$j("#data_detail").is(":visible") ) \$j("#button_detail").val("{$hide}")
				else \$j("#button_detail").val("{$show}");
			});
	
			fnDetailInit();
			\$j("#data_detail").hide();
			\$j("#button_detail").val("{$show}");
	
EOT;
	}


	static function echoDataTableLang() {
		$sLengthMenu = __('Display _MENU_ records per page',RCAL_DOMAIN);
		$sNext = __('Next Page',RCAL_DOMAIN);
		$sPrevious = __('Prev Page',RCAL_DOMAIN);
		$sInfo = __('Showing _START_ to _END_ of _TOTAL_ records',RCAL_DOMAIN);
		$sSearch = __('search',RCAL_DOMAIN);
		$sEmptyTable = __('No data available in table',RCAL_DOMAIN);
		$sLoadingRecords = __('Loading...' ,RCAL_DOMAIN);
		$sInfoEmpty = __('Showing 0 to 0 of 0 entries' ,RCAL_DOMAIN);
		$sZeroRecords = __('No matching records found' ,RCAL_DOMAIN);
		
		echo <<<EOT
			"bAutoWidth": false,
			"bProcessing": true,
			"sScrollX": "100%",
			"bScrollCollapse": true,
			"bLengthChange": false,
			"bPaginate": false,
			//"bServerSide": true,
			iDisplayLength : 100,
			"oLanguage": {
			        "sLengthMenu": "{$sLengthMenu}"
			        ,"oPaginate": {
			            "sNext": "{$sNext}"
			            ,"sPrevious": "{$sPrevious}"
				    }
		        	,"sInfo": "{$sInfo}"
			        ,"sSearch": "{$sSearch}："
					,"sEmptyTable":"{$sEmptyTable}"
					,"sLoadingRecords":"{$sLoadingRecords}"
					,"sInfoEmpty":"{$sInfoEmpty}"
					,"sZeroRecords":"{$sZeroRecords}"
			},	
			fnServerData: function(sSource, aoData, fnCallback, oSettings) {
				\$j.ajax({
					url: sSource,
					type: "POST",
					data: aoData,
					dataType: "json",
					success: function(data) {
						if (data === null || data.status == "Error" ) {
							if (data) alert(data.message);
							fnCallback({"iTotalRecords":0,"iTotalDisplayRecords":0,"sEcho":1,"aaData":[]});
						}
						else {
							fnCallback(data);
						}
					},
					error:  function(XMLHttpRequest, textStatus){
						alert (textStatus);
					}
				})
			},
			
EOT;
	}


	static function echoTableItem($items,$is_only_common_part = false,$operate_width = '120px',$isForceNoSort = false) {	
		$operate_title = __('Operation',RCAL_DOMAIN);
		echo <<<EOT
			"aoColumns": [
				{ "mData":"no","sTitle": "No" ,"sClass":"rcal_select","bSearchable": false,"bSortable": false,"sWidth":"80px"},
				{ "mData":"check","sTitle": "{$operate_title}","bSortable": false,"bSearchable": false,"sWidth":"{$operate_width}"},
EOT;
	
		if ($is_only_common_part ) return;
		$item_contents = ResourceCalendar_Page::setItemContents();	

	
		$tmp = array();
		foreach ($items as $d1) {
			empty ($item_contents[$d1]['table']['sort'])  ? $sort = 'false' : $sort = $item_contents[$d1]['table']['sort'];
			empty ($item_contents[$d1]['table']['search']) ? $search = 'false' : $search = $item_contents[$d1]['table']['search'];
			empty ($item_contents[$d1]['table']['visible']) ? $visible = 'false' : $visible = $item_contents[$d1]['table']['visible'];
			$width = '';
			if (!empty ($item_contents[$d1]['table']['width']) ) $width = ',"sWidth" : "'.$item_contents[$d1]['table']['width'].'"';
			
			$tmp[] =
					'{'.
					' "mData" : "'.$item_contents[$d1]['id'].'"'.
					',"sTitle" : "'.$item_contents[$d1]['label'].'"'.
					',"sClass" : "'.$item_contents[$d1]['table']['class'].'"'.
					$width.
					',"bSortable" : '.$sort.
					',"bSearchable" : '.$search.
					',"bVisible" : '.$visible.
					'}';
		}
		echo join(',',$tmp);
		echo '],';
	
	}
	

	public function echoEditableCommon($target_name,$add_col = "",$add_check_process = "") {
		$target_src = get_bloginfo( 'wpurl' ).'/wp-admin/admin-ajax.php?action=rcal'.$target_name;
		$submit = __('change',RCAL_DOMAIN);
		$cancel = __('cancel',RCAL_DOMAIN);
		$placeholder = __('click edit',RCAL_DOMAIN);
		
		$menu_func = ucwords($target_name);
		$add_char1 = '';
		if ( ! empty($add_col) ) {
			if (is_array($add_col) ) {
				foreach ($add_col  as $d1 ) {
					$add_char1 .= ',"'.$d1.'":setData["aoData"][position[0]]["_aData"]["'.$d1.'"] ';
				}
			}
			else {
				$add_char1 = ',"'.$add_col.'":setData["aoData"][position[0]]["_aData"]["'.$add_col.'"] ';
			}
		}
		//positionは行位置、列位置(表示のみ）(=tdの数)、列位置（全体=aoColumnの数）
		echo <<<EOT
				\$j("#lists tbody .rcal_editable").editable("{$target_src}", {
					
					submitdata: function ( value, settings ) {
						var setData = target. fnSettings();
						var position = target.fnGetPosition( this );
						return {
							"{$target_name}_cd": setData['aoData'][position[0]]['_aData']['{$target_name}_cd']
							,"column": position[2]
							,"nonce":"{$this->nonce}"
							,"menu_func":"{$menu_func}_Col_Edit"
							$add_char1
						};
					},
					callback: function( sValue, y ) {
						var jdata = \$j.evalJSON( sValue );
						var position = target.fnGetPosition( this );
						if (jdata.status ==  "Ok" ) {
							target.fnUpdate(jdata.set_data,position[0],position[2],false);
						}
						alert(jdata.message);
						fnDetailInit();
					},
					
					onsubmit:function(settings,td) {
						{$add_check_process	}
						if ( !checkColumnItem( td ) )return false;
					},
			
					onerror: function (settings, original, xhr) {
						var jdata = \$j.evalJSON( xhr.responseText );
						alert(jdata.message);
					},
					onreset: function (settings, original) {
						original.revert = htmlspecialchars(original.revert);
					},
					type : "text",
					submit : "{$submit}",
					cancel : "{$cancel}",
					placeholder : "{$placeholder}",
					"height": "20px"
				} );
				\$j("#lists  tbody .rcal_select").click(function(event) {
					fnSelectRow(this);
				});
EOT;
	}

	static function echoDataTableSelecter($target_name,$is_append = true,$del_disp='',$del_msg='') {
		
		if (empty($del_disp) ) $del_disp = __('Delete ',RCAL_DOMAIN);
		if (empty($del_msg) ) $del_msg = __('Delete ok?',RCAL_DOMAIN);
		$sel_disp = __('Select',RCAL_DOMAIN);
		//セレクターは１列目
		echo <<<EOT
			var element = \$j("td:eq(1)", nRow);
			element.text("");
	
			var sel_box = \$j("<input>")
					.attr("type","button")
					.attr("id","rcal_select_btn_"+iDataIndex)
					.attr("name","rcal_update_"+iDataIndex)
					.attr("value","{$sel_disp}")
					.attr("class","rcal_button rcal_button_short")
					.click(function(event) {
						fnSelectRow(this.parentNode);
					});
			var del_box = \$j("<input>")
					.attr("type","button")
					.attr("id","rcal_delete_btn_"+iDataIndex)
					.attr("name","rcal_delete_"+iDataIndex)
					.attr("class","rcal_button rcal_button_short")
					.attr("value","{$del_disp}")
					.click(function(event) {
						if (confirm(htmlspecialchars_decode(aData.{$target_name})+"{$del_msg}") ) {
							fnClickDeleteRow(this.parentNode);
						}
					});
EOT;
		if ($is_append) {
			echo <<<EOT2
			element.append(sel_box);
			element.append(del_box);
EOT2;
		}
		
	}


	public function echoDataTableEditColumn($target_name,$add_col = "",$add_callback_process="",$add_check_process = "") {	
		
		$target_src = get_bloginfo( 'wpurl' ).'/wp-admin/admin-ajax.php?action=sl'.$target_name;
		$add_char1 = '';
		if ( ! empty($add_col) ) {	//[TODO]salesとresevationのIDで使用しているが、IDはやめられないか
			$add_char1 = ',"'.$add_col.'":setData["aoData"][position[0]]["_aData"]["'.$add_col.'"] ';
		}
		$menu_func = ucwords($target_name);
		
		$check_error_msg = __('select please',RCAL_DOMAIN);
		
		//セレクトボックスであれば何らかの値がはいるはずなので
		//クライアント側では空データのチェックのみをいれとく
		//不正データはサーバ側でチェックする。
		echo <<<EOT
			function fnUpdateColumn(target_col,column_name,set_value) {
				if (set_value == "" ) {
					alert("{$check_error_msg}");
					return false;
				}
				var position = target.fnGetPosition( target_col );
				var setData = target.fnSettings();
				var target_cd = setData['aoData'][position[0]]['_aData']['{$target_name}_cd']; 
				{$add_check_process	}
				\$j.ajax({
						type: "post",
						url:  "{$target_src}",
						dataType : "json",
						data: 	{
							"{$target_name}_cd":target_cd
							,"column":position[2]
							,"value":set_value
							,"func":"update"
							,"nonce":"{$this->nonce}"
							,"menu_func":"{$menu_func}_Col_Edit"
							{$add_char1}
						}, 
						success: function(data) {
							if (data === null || data.status == "Error" ) {
								if (data) alert(data.message);
							}
							else {
								alert(data.message);
								setData['aoData'][position[0]]['_aData'][column_name] = set_value;
								{$add_callback_process}
								fnDetailInit();
							}
						},
						onerror:  function(XMLHttpRequest, textStatus){
							alert (textStatus);
						}
				 });			
			}
EOT;
		
	}

	public function echoDataTableDeleteRow($target_name,$target_key_name = '',$is_delete_row = true,$add_parm = '',$add_check = '') {
		$target_src = get_bloginfo( 'wpurl' ).'/wp-admin/admin-ajax.php?action=rcal'.$target_name;
		if (empty($target_key_name) ) $target_key_name = $target_name;
		$menu_func = ucwords($target_name);
		if ($is_delete_row) $delete_string = 'var rest = target.fnDeleteRow( position[0] );	fnDetailInit();';
		else $delete_string  = 'target.fnUpdate( data.set_data ,position[0] );	fnDetailInit();';

		echo <<<EOT
			function fnClickDeleteRow(target_col) {
				var position = target.fnGetPosition( target_col );
				var setData = target.fnSettings();
				var target_cd = setData['aoData'][position[0]]['_aData']['{$target_key_name}_cd']; 				
				{$add_check}
				 \$j.ajax({
						type: "post",
						url:  "{$target_src}", 
						dataType : "json",
						data: 	{
							$add_parm
							"{$target_key_name}_cd":target_cd,
							"type":"deleted",
							"nonce":"{$this->nonce}",
							"menu_func":"{$menu_func}_Edit"
						}, 
						success: function(data) {
							if (data === null || data.status == "Error" ) {
								if (data) alert(data.message);
							}
							else {
								{$delete_string}
							}
						},
						error:  function(XMLHttpRequest, textStatus){
							alert (textStatus);
						}
				 });			
			}
EOT;
		
	}


	static function echo_clear_error() {		
	//[TODO]IEだとくずれてしまうのでmargin1加算
		$default_margin = self::INPUT_BOTTOM_MARGIN;
		echo <<<EOT
				var userAgent = window.navigator.userAgent.toLowerCase();
				var appVersion = window.navigator.appVersion.toLowerCase();

				\$j("span").removeClass("error");
				for(index in check_items) {
					var id = check_items[index]["id"];
					\$j("#"+id+"_lbl").children(".small").text(check_items[index]["tips"]);
					var diff = \$j("#"+id+"_lbl").outerHeight(true) - \$j("#"+id).outerHeight(true);
					if (diff > 0 ) {
						diff += {$default_margin}+5;

						var attr = \$j("#"+id).attr("style");
						if (!attr) attr = "";
						\$j("#"+id).attr("style",attr + " margin-bottom: "+diff+"px;");
						\$j("#"+id+"_lbl").children(".samll").attr("style","text-align:left;");
					}


					if (userAgent.indexOf('msie') != -1) {
					//ie9以下は無視
					        var lineHeight = parseFloat(\$j("#"+id+"_lbl .small").css("line-height"))*parseFloat($("body").css("font-size"));
					        var bHeight = Math.round(lineHeight);
					}else{//ie以外
					    var lineHeight = parseFloat(\$j("#"+id+"_lbl .small").css("line-height"));
					    var bHeight = Math.round(lineHeight);
					}
					if (bHeight < \$j("#"+id+"_lbl .small").height() ) {
						\$j("#"+id+"_lbl .small").attr("style","text-align:left;");
					}


				}
EOT;
	
	}




	static function echoTimeStepSelect($id,$is_noEcho = false) {	
	
		$echo_data =  '<select name="'.$id.'" id="'.$id.'">';
		$datas = array(10,15,30,60);
		foreach ($datas as  $d1) {
			$echo_data .=  '<option value="'.$d1.'">'.$d1.'</option>';
		}
		$echo_data .= '</select>';
			
		if ($is_noEcho) return str_replace(array("\r\n","\r","\n"), '', $echo_data);
		else echo $echo_data;
			
	}

	static function echoTimeSelect($id,$open_time,$close_time,$time_step,$is_noEcho = false) {	
	
		$dt = new DateTime($open_time);
		$last_hour = substr($close_time,0,2).":".substr($close_time,2,2);
		$dt_max = new DateTime($last_hour);
		$echo_data =  '<select name="'.$id.'" id="'.$id.'">';
		while($dt <= $dt_max ) {
			$echo_data .= '<option value="'.$dt->format("H:i").'" >'.$dt->format("H:i").'</option>';
			$dt->modify("+".$time_step." minutes");
		}
		$echo_data .= '</select>';
			
		if ($is_noEcho) return str_replace(array("\r\n","\r","\n"), '', $echo_data);
		else echo $echo_data;
	
			
	}

	static function echoSettingPaternSelect($tag,$datas) {	
	
		$echo_data =  '<select name="'.$tag.'" id="'.$tag.'">';
		foreach($datas as $k1 => $d1) {
			$echo_data .= '<option value="'.$k1.'" >'.$d1.'</option>';
		}
		$echo_data .= '</select>';
		echo $echo_data;
	
			
	}

	
	static function echoDisplayErrorLable() {
		echo <<<EOT
			function fnDisplayErrorLabel(target,msg) {
//			var label = \$j("#"+target).find("span");
			var label = \$j("#"+target).children(".small")
			var set_msg = msg;
			if (label.hasClass("error") ) {
				set_msg = label.text()+" "+set_msg;
			}
			label.text(set_msg );
			label.addClass("error small");
		}
EOT;
	}




	static function echoDayFormat() {	
		echo <<<EOT
			function fnDayFormat(date,format) {
				edit = format;
				edit = edit.replace("%Y",date.getFullYear());
				edit = edit.replace("%m",(date.getMonth()+1<10?"0":"")+(date.getMonth()+1));
				edit = edit.replace("%d",(date.getDate()+0<10?"0":'')+date.getDate());
				return edit;
			}
EOT;
	}
	
	public function echoTextDateReplace() {
		echo 
		'function _fnTextDateReplace(in_date) {
			if( in_date.match(/^'.__('(\d{1,2})[\/\.\-](\d{1,2})[\/\.\-](\d{4})',RCAL_DOMAIN).'$/) || in_date.match(/^'.__('(\d{2})(\d{2})(\d{4})',RCAL_DOMAIN).'$/)  ){
				var y = '.__('RegExp.$3',RCAL_DOMAIN).';
				var m = '.__('RegExp.$1',RCAL_DOMAIN).';
				var d = '.__('RegExp.$2',RCAL_DOMAIN).';
				
				var di = new Date(y, m - 1, d);
				if (!(di.getFullYear() == y && di.getMonth() == m - 1 &&  di.getDate() == d) ) {
					alert( "'.__('this day not exist?',RCAL_DOMAIN).'");
					return false;
				}
			}
			return  fnDayFormat(di,"%Y%m%d");
		}';
	}

	static function echosSetDay() {	
		echo <<<EOT
			function fnSetDay(yyyymmdd) {
				yyyymmdd=yyyymmdd+"";
				var yyyy = yyyymmdd.substr(0,4);
				var mm = yyyymmdd.substr(4,2);
				var dd = yyyymmdd.substr(6,2);
				return new Date(yyyy, +mm - 1,dd);
			}
EOT;
	}

	
//	//[2014/08/15]
	public function echoCheckDeadline($minutes) {
		//管理者は過去でも動作可能にする。
		if (is_user_logged_in() ) {
			echo '	function _checkDeadline(checkTime) { return true; }';
			return;
		}
		$msg_part = __('%m/%d/%Y',RCAL_DOMAIN);
		$msg = __('Your reservation is possible from %s.',RCAL_DOMAIN);
		echo <<<EOT
		function _checkDeadline(checkTime) {
			var limit_time = new Date();
			if ("Date" !== Object.prototype.toString.call(checkTime).slice(8, -1) ){
				checkTime = new Date(checkTime);
			}
			limit_time.setMinutes(limit_time.getMinutes()+{$minutes});
			if ( limit_time > checkTime) {
				var display_msg = fnDayFormat(limit_time,"{$msg_part}")+" "+('0'+limit_time.getHours()).slice(-2)+":"+('0'+limit_time.getMinutes()).slice(-2);
				var display_main = "{$msg}";
				display_main = display_main.replace("%s",display_msg);
				alert(display_main);
				return false;
			}
			return true;
			
		}
EOT;
	}


	static function set_datepicker_date ($sp_dates = null){
	//  "20130101":{type:0, title:"元日"},
	// で、cssの定義と連動させて色を変える
		echo 'var holidays = {';
		$holiday = unserialize(get_option("rcal_holiday"));
		$tmp_table = array();
		foreach ($holiday as $k1 => $d1 ) {
			$tmp_table[] = '"'.$d1[0].'":{type:0,title:"'.$d1[1].'"}';
		}
		echo implode(',',$tmp_table);
		echo '};';
		//特殊な営業日・休業日の設定
		$tmp_table2 = array();
		echo "\n";
		echo 'var sp_dates = {';
		if (! empty($sp_dates) ) {
			$target_year = date_i18n("Y");
			for ($yyyy = $target_year ; $yyyy < $target_year + 2 ; $yyyy++ ) {
				if ($sp_dates && isset($sp_dates[$yyyy]) && count($sp_dates[$yyyy]) > 0) {
					foreach ($sp_dates[$yyyy] as $k1 => $d1) {
						$tmp_table2[] = '"'.$k1.'":{type:'.$d1.',title:"'.($d1== ResourceCalendar_Status::OPEN ?  __('Business day',RCAL_DOMAIN) :  __('Holiday',RCAL_DOMAIN)).'"}';
					}
				}
			}
			echo implode(',',$tmp_table2);
		}
		echo '};';
		
	}


	
	static function set_datepickerDefault($is_maxToday = false,$is_all = false){
		$range = 'minDate: new Date()';
		if ($is_maxToday) $range = 'maxDate: new Date()';
		if ($is_all) $range = 'minDate:new Date(2000,0,1),maxDate:new Date(2099,11,31)';
		echo 
			'$j.datepicker.setDefaults({
					closeText: "'.__('close',RCAL_DOMAIN).'",
					'.__('prevText: "&#x3C;"',RCAL_DOMAIN).',
					'.__('nextText: "&#x3E;"',RCAL_DOMAIN).',
					currentText: "'.__('today',RCAL_DOMAIN).'",
					monthNames: ['.__('"Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"',RCAL_DOMAIN).'],
					monthNamesShort: ['.__('"Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"',RCAL_DOMAIN).'],
					dayNames: ['.__('"Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"',RCAL_DOMAIN).'],
					dayNamesShort: ['.__('"Sun","Mon","Tue","Wed","Thu","Fri","Sat"',RCAL_DOMAIN).'],
					dayNamesMin: ['.__('"Sun","Mon","Tue","Wed","Thu","Fri","Sat"',RCAL_DOMAIN).'],
					weekHeader: "'.__('week',RCAL_DOMAIN).'",
					dateFormat: "'.__('mm/dd/yy',RCAL_DOMAIN).'",
					changeMonth: true,
					firstDay: 1,
					isRTL: false,
					showMonthAfterYear: true,
//					showButtonPanel: true,
					'.__('yearSuffix:"" ',RCAL_DOMAIN).',
					'.$range.',
			});';			
		
	}
	public function set_datepicker ($tag_id,$select_ok = false,$closed_data = null,$addcode="",$display_month = 1,$is_coloredReserve=false){
		$tmp_status = ResourceCalendar_Status::OPEN;
		if ($select_ok) $tmp_select = 'true';
		else $tmp_select = 'false';
		
		$tmp_before = 'if (sp_date) {';
		if ($is_coloredReserve) {
		$tmp_before = 
			'if ((rcalSchedule._months[yyyymm]) && (rcalSchedule._months[yyyymm][yyyymmdd] )) { 
				if (rcalSchedule._months[yyyymm][yyyymmdd + "_flg"]) {
					result = [true, "date-reserved-tentative", rcalSchedule._months[yyyymm][yyyymmdd]];  
				}
				else {
					result = [true, "date-reserved", rcalSchedule._months[yyyymm][yyyymmdd]];  
				}
			} 
			else if (sp_date) {';
		}
		
		echo 
				'$j("#'.$tag_id.'").datepicker({
					numberOfMonths: '.$display_month.'
					,beforeShowDay: function(day,inst) {
					  var yyyymmdd = $j.format.date(day, "yyyyMMdd");
					  var yyyymm   = $j.format.date(day, "yyyyMM");
					  var result = [true,"",""];
					  var holiday = holidays[yyyymmdd]
					  var sp_date = sp_dates[yyyymmdd]
					  '.$tmp_before.
						'if (sp_date.type == '.$tmp_status.' ) {
							result =  [true, "date-holiday3", sp_date.title];
						}
						else {
							result =  ['.$tmp_select.', "date-holiday2", sp_date.title];
						}
					  } 
					  else {
						switch (day.getDay()) {';
		$datas = array();

		$holiday_set = 'if(!result[1]) result[1] =  "date-holiday0";';
		$holiday_set .= 'result[2] =  result[2] + holiday.title;';


		if (!empty($closed_data)) {
			//定休日を設定
			$datas = explode(",",$closed_data);
			foreach ($datas as $d1 ) {
				if (!empty($d1)) {
					//曜日;開始時間;終了時間
					$datas_arr = explode(';',$d1);
					//施設の開始時間と終了時間が異なる場合は入力可能にする。
					if (isset($datas_arr[1],$datas_arr[2]) && 
					(($datas_arr[1] != $this->config_datas['RCAL_CONFIG_OPEN_TIME']) || 
					($datas_arr[2] != $this->config_datas['RCAL_CONFIG_CLOSE_TIME']))) {
						echo 'case '.$datas_arr[0].': result = [true, "date-holiday-half","'.__('Half-holiday',RCAL_DOMAIN).'"];  break; ';
					}
					else {
						echo 'case '.$datas_arr[0].': result = ['.$tmp_select.', "date-holiday1","'.__('Holiday',RCAL_DOMAIN).'"];  break; ';
					}
				}
			}
		}
		//定休日が土日ではないときは土日に色をつける
		if (in_array(0,$datas) == false ) echo 'case 0: result = [true,"date-sunday-show",""]; break; ';
		if (in_array(6,$datas) == false ) echo 'case 6: result = [true,"date-saturday-show",""]; break; ';
		echo <<<EOT2
						default:
							result = [true, "",""];
							break;
						}
					  }
					  if (holiday) {
						{$holiday_set}
						
					  } 
					  return result;
					}
					{$addcode}
				});
EOT2;
	}
	
	

	static function echoCheckClinet($check_patern) {
		$default_margin = self::INPUT_BOTTOM_MARGIN;
	//reqOther_tel_ZZZZとあったらidがtelとZZZZに入力があるか確認する 
	//[TODO]rcal_checkboxは重いか？
	//[TODO]済 exceptはとりあえず１つだけ→カンマ区切り
		echo <<<EOT
			function checkItem(target,except ) {
				var is_error = false;
				var tmp_excepts = Array();
				if (except) {
					if (except.indexOf(",") > -1) {
						var tmp_excepts = except.split(",");
					}
					else {
						tmp_excepts.push(except);
					}
				}
				\$j("#"+target).find("input[type=text],textarea,select,.rcal_checkbox").each(function(){
					if (\$j(this).hasClass("rcal_nocheck") ) return;
					var id = \$j(this).attr("id");
					if (except) {
						for(var i=0;i<tmp_excepts.length;i++){
							if ( id == tmp_excepts[i] ) return;
						}
						
					}
					var item_errors = Array();
					var cl = \$j(this).attr("class");
					if (cl) {
						var val = \$j(this).val();
EOT;
		//必須チェックがなくて、その他のチェックがある場合を考慮して			
		$check_contents = self::setCheckContents();
		$key = array_search('chk_required',$check_patern);
		if ($key !== false) {
			echo $check_contents['chk_required'];
			unset($check_patern[$key]);
		}
		$key = array_search('reqOther',$check_patern) ;
		if ($key !== false) {
			echo $check_contents['reqOther'];
			unset($check_patern[$key]);
		}
		$key = array_search('reqCheck',$check_patern);
		if ($key !== false) {
			echo $check_contents['reqCheckbox'];
			unset($check_patern[$key]);
		}
		if ( count($check_patern) > 0 ) {
			echo 'if (( item_errors.length == 0 ) && (val != "" ) && (val != null) ){';
			foreach ($check_patern as $d1) {
				echo $check_contents[$d1];
			}
			echo '}';
		}
	//エラーの表示部

		echo <<<EOT2
					}
					
					\$j(this).removeAttr("style");
					var label = \$j(this).prev().children(".small");
					label.removeClass("rcal_coler_not_complete");
					label.removeAttr("style");
					if (  item_errors.length > 0 ) {
						label.text(item_errors.join(" "));
						label.addClass("error small");
						is_error = true;
						var label_tag = \$j(this).prev();
						var diff = label_tag.outerHeight(true) - \$j(this).outerHeight(true);
						if (diff > 0 ) {
							diff += {$default_margin}+5;
							\$j(this).attr("style", " margin-bottom: "+diff+"px;");
							label.attr("style","text-align:left;");
						}
					}
					else {
						label.text(check_items[id]["tips"]);
						label.removeClass("error");
						var label_tag = \$j(this).prev();
						var diff = label_tag.outerHeight(true) - \$j(this).outerHeight(true);
						if (diff > 0 ) {
							diff += {$default_margin}+5;
							\$j(this).attr("style", " margin-bottom: "+diff+"px;");
							label.attr("style","text-align:left;");
						}
					}
				});
				if ( is_error ) return false;
				else return true;
			}
EOT2;
	
	}
	



	static function echoColumnCheck($check_patern) {

		echo <<<EOT
			function checkColumnItem(td) {		
				var input = \$j(td).find("input");
				var val = input.val();
				var cl = \$j(td).attr("class");
				var item_errors = Array();
EOT;

				$check_contents = self::setCheckContents('td');
				$key = array_search('chk_required',$check_patern);
				if ($key !== false) {
					echo $check_contents['chk_required'];
					unset($check_patern[$key]);
				}
				if ( count($check_patern) > 0 ) {
					echo 'if (( item_errors.length == 0 ) && (val != "" ) ){';
					foreach ($check_patern as $d1) {
						echo $check_contents[$d1];
					}
					echo '}';
				}

		echo <<<EOT2
				if ( item_errors.length > 0 ) {
					alert(item_errors.join(" "));
					return false;
				}

				return true;					
EOT2;
		echo '}';
	}

	static function echoClosedCheck($closed_day,$tag_id){
		//closed_day は０→日カンマ区切り
		echo '<div id="'.$tag_id.'_check" class="rcal_checkbox" >	';
		$closed = array(false,false,false,false,false,false,false);
		$week = array(__('sun',RCAL_DOMAIN),__('mon',RCAL_DOMAIN), __('tue',RCAL_DOMAIN), __('wed',RCAL_DOMAIN), __('thr',RCAL_DOMAIN), __('fri',RCAL_DOMAIN), __('sat',RCAL_DOMAIN));
		$datas = explode(',',$closed_day);
		foreach ($datas as $d1 ) {
			$closed[$d1] = true;
		}
		for ( $i = 0 ; $i < 7 ; $i++ ) {		
			echo '<input type="checkbox" id="'.$tag_id.'_'.$i.'" value="'.$i.'" '.($closed[$i] ? 'checked="checked"' :'').'/><label for="'.$tag_id.'_'.$i.'">&nbsp;'.$week[$i].'</label>';
		}
		echo '</div>';
		echo '<div id="rcal_holiday_wrap" >';
		$week_long = explode(',',__('"Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"',RCAL_DOMAIN));
		for ( $i = 0 ; $i < 7 ; $i++ ) {		
			echo '<div id="rcal_holiday_detail_wrap_'.$i.'" class="rcal_holiday_detail_wrap" >';
			echo '<label>'.__(" Detailed time schedule of ",RCAL_DOMAIN).$week_long[$i].'</label><input type="text" id="'.$tag_id.'_'.$i.'_fr" class="rcal_from rcal_nocheck"/><label class="rcal_holiday_in_label">-</label><input type="text" id="'.$tag_id.'_'.$i.'_to" class="rcal_to rcal_nocheck" /><label class="rcal_holiday_in_label">'.__('is on Holiday',RCAL_DOMAIN).'</label>';
			echo '</div>';
		}
		echo '</div>';
	}

	static function echoClosedDetail($closed_day,$tag_id) {
		echo <<<EOT
			\$j("#rcal_closed_day_check input[type=checkbox]").click(function(){
				\$j(".rcal_holiday_detail_wrap").hide();
				var tmp = new Array();  
				var tmp_detail = new Array();
				var tmp_closed_array = save_closed.split(",")
				var tmp_closed_detail_array = save_closed_detail.split(",");
				\$j("#rcal_closed_day_check input[type=checkbox]").each(function (){
					if ( \$j(this).is(":checked") ) {
						var idx  = \$j(this).val();
						tmp.push( idx );
						tmp_detail.push(\$j("#{$tag_id}_"+idx+"_fr").val()+";"+\$j("#{$tag_id}_"+idx+"_to").val());
						\$j("#rcal_holiday_detail_wrap_"+idx).show();
					}
				});
				for( var i = 0; i < tmp.length ; i++ ) {
					var idx = tmp[i];
					var set_index =  tmp_closed_array.indexOf(idx);
					if (set_index == -1 ) {
						\$j("#{$tag_id}_"+idx+"_fr").val(\$j("#rcal_open_time").val());
						\$j("#{$tag_id}_"+idx+"_to").val(\$j("#rcal_close_time").val());
					}
					else {
						var each_array = tmp_closed_detail_array[set_index].split(";");
						if (!each_array[0] ) each_array[0] = \$j("#rcal_open_time").val();
						if (!each_array[1] ) each_array[1] = \$j("#rcal_close_time").val();
						\$j("#{$tag_id}_"+idx+"_fr").val(each_array[0].slice(0,2)+":"+each_array[0].slice(-2));
						\$j("#{$tag_id}_"+idx+"_to").val(each_array[1].slice(0,2)+":"+each_array[1].slice(-2));

					}
				}

				save_closed = tmp.join(",");
				save_closed_detail = tmp_detail.join(",");
			});
			\$j("#rcal_open_time").change(function() {
				for ( var i = 0 ; i < 7 ; i++ ) {
					\$j("#{$tag_id}_"+i+"_fr").val(\$j(this).val());
				}
			});
			\$j("#rcal_close_time").change(function() {
				for ( var i = 0 ; i < 7 ; i++ ) {		
					\$j("#{$tag_id}_"+i+"_to").val(\$j(this).val());
				}
			});
			\$j(".rcal_from,.rcal_to").change(function() {
				var tmp = new Array();  
				\$j("#rcal_closed_day_check input[type=checkbox]").each(function (){
					if ( \$j(this).is(":checked") ) {
						var id=\$j(this).val();
						tmp.push(\$j("#{$tag_id}_"+id+"_fr").val()+";"+\$j("#{$tag_id}_"+id+"_to").val());
					}
					
				});
				save_closed_detail = tmp.join(",");
			});
			
			for ( var i = 0 ; i < 7 ; i++ ) {		
				\$j("#rcal_holiday_detail_wrap_"+i).hide();
			}
			
			
EOT;
		$datas = explode(',',$closed_day);
		foreach ($datas as $d1 ) {
			echo '$j("#rcal_holiday_detail_wrap_'.$d1.'").show();';
		}

	}

	static function echoClosedDetailCheck() {
		$msg1 = __('please HH:MM or HHMM format',RCAL_DOMAIN);
		$msg2 = __('Hour is max 24',RCAL_DOMAIN);
		echo <<<EOT

		function _fnCheckClosedDetail(step) {
			var rtn = true; 
			\$j(".rcal_to,.rcal_from").each(function (){
				var err_msg = "";
				var val = \$j(this).val();
				if (val) {
					if( ! val.match(/^(?:[ ]?\d{1,2}:\d{1,2})$|^(?:\d{4})$/)  ){
						err_msg ="{$msg1}";
					}
					else if (+val.slice(0,2) > 47 ) {
						err_msg ="{$msg2}";
					}
					else if (!_fnCheckTimeStep(step,val.slice(-2) ) ) {
						\$j(this).focus();
						rtn = false;
						return false;
					}
					if (err_msg != "" ) {
						\$j(this).focus();
						alert(err_msg);
						rtn = false;
						return false;
					}
				}
			});
			return rtn;
		}
EOT;
	}

	static function setItemContents() {
		$item_contents = array();


		$item_contents['resource_cd'] =array('id'=>'rcal_resource_cd'
		 ,'class' => array()
		 ,'check' => array( 'chk_required')
		 ,'label' => __('Resource',RCAL_DOMAIN)
		 ,'tips' => __('select please',RCAL_DOMAIN));
		 
		$item_contents['customer_name'] =array('id'=>'rcal_name'
		 ,'class' => array()
		 ,'check' => array( 'chk_required','lenmax30','chkSpace')
		 ,'label' => __('Name',RCAL_DOMAIN)
		 ,'tips' => __('space input between first-name and last-name',RCAL_DOMAIN)
		 ,'table' => array(  'class'=>'rcal_editable'
							,'width'=>self::LONG_WIDTH
							,'sort'=>'true'
							,'search'=>'true'
							,'visible'=>'true' ));
							
		$item_contents['booking_tel'] =array('id'=>'rcal_tel'
		 ,'class' => array()
		 ,'check' => array( 'chk_required','chkTel')
		 ,'label' => __('Tel',RCAL_DOMAIN)
		 ,'tips' => __('Within 15 charctors.',RCAL_DOMAIN));

		$item_contents['booking_mail'] =array('id'=>'rcal_mail'
		 ,'class' => array()
		 ,'check' => array( 'chk_required','chkMail')
		 ,'label' => __('Mail',RCAL_DOMAIN)
		 ,'tips' => __('The allowed formats for "XXX@XXX.XXX"',RCAL_DOMAIN));

		$item_contents['time_from'] =array('id'=>'rcal_time_from'
		 ,'class'	=>array()
		 ,'check' => array('chk_required')
		 ,'label' => __('Start',RCAL_DOMAIN)
		 ,'tips' => '');

		$item_contents['time_to'] =array('id'=>'rcal_time_to'
		 ,'class'	=>array()
		 ,'check' => array('chk_required')
		 ,'label' => __('End',RCAL_DOMAIN)
		 ,'tips' => '');

		$item_contents['search_day'] =array('id'=>'rcal_searchdate'
		 ,'class' => array()
		 ,'check' => array( '')
		 ,'label' => __('Date',RCAL_DOMAIN)
		 ,'tips' => __('MM/DD/YYYY',RCAL_DOMAIN));

		$item_contents['target_day_mobile'] =array('id'=>'rcal_target_day'
		 ,'class' => array()
		 ,'check' => array( 'chk_required')
		 ,'label' => ""
		 ,'tips' => __('please MM/DD/YYYY or MMDDYYYY format',RCAL_DOMAIN));
	
//admin
		$item_contents['display_sequence'] =array('id'=>'rcal_display_sequence'
		 ,'class' => array()
		 ,'check' => array()
		 ,'label' => __('Seq',RCAL_DOMAIN)
		 ,'tips' => ''
		 ,'table' => array(  'class'=>' '
							,'width'=>'10px'
							,'sort'=>'false'
							,'search'=>'false'
							,'visible'=>'true' ));

		$item_contents['setting_patern_cd'] =array('id'=>'rcal_setting_patern_cd'
		 ,'class'	=>array()
		 ,'check' => array('chk_required')
		 ,'label' => __('Setting the reservation time',RCAL_DOMAIN)
		 ,'tips' => __('\"Input time unit\" -> Setting for allowing the user to input a time.\"Input pre-determined time frames\" -> The user is able to select from time frames decided by the administrator. Selecting this item displays the following input selections. ',RCAL_DOMAIN));

		$item_contents['resource_name'] =array('id'=>'rcal_resource_name'
		 ,'class'	=>array()
		 ,'check' => array()
		 ,'label' => __('Management target name',RCAL_DOMAIN)
		 ,'tips' => __('Set the name of the management target, such as \"Equipment,\" \"Resource,\" etc. For example, enter \"Conference rooms\" if managing reservations for multiple conference rooms. ',RCAL_DOMAIN));


		$item_contents['original_name'] =array('id'=>'rcal_original_name'
		 ,'class'	=>array()
		 ,'check' => array()
		 ,'label' => __('Selection item name',RCAL_DOMAIN)
		 ,'tips' => __('The name of the item selected by the user. For example, set \"Morning 1\" and \"Morning 2\" to break the morning into two time frames (10:00 – 11:00 and 11:00 – 12:00)',RCAL_DOMAIN));



		$item_contents['address'] =array('id'=>'rcal_address'
		 ,'class' => array()
		 ,'check' => array('chk_required', 'lenmax300')
		 ,'label' => __('Your address',RCAL_DOMAIN)
		 ,'tips' => __('Within 300 charctors.Embedded when an e-mail is sent. ',RCAL_DOMAIN));


		$item_contents['tel'] = $item_contents['booking_tel'];
		$item_contents['tel']['check'] = array( 'chkTel','chk_required');
		$item_contents['tel']['label'] = __('Contact telephone number',RCAL_DOMAIN);
		$item_contents['tel']['tips'] = __('Within 15 charctors.Embedded when an e-mail is sent. ',RCAL_DOMAIN);

		$item_contents['mail'] = $item_contents['booking_mail'];
		$item_contents['mail']['label'] = __('Contact e-mail',RCAL_DOMAIN);
		$item_contents['mail']['tips'] = __('The allowed formats for \"XXX@XXX.XXX\". Embedded when an e-mail is sent. ',RCAL_DOMAIN);


		$item_contents['open_time'] =array('id'=>'rcal_open_time'
		 ,'class'=>array('rcal_short_width')
		 ,'check'=>array('chk_required','chkTime')
		 ,'label'=> __('Open Time',RCAL_DOMAIN)
		 ,'tips' => __('The allowed formats for \"HH:MM\" or \"HHMM\". Set the reservation handling time for the management target.',RCAL_DOMAIN));
	
		$item_contents['close_time'] =array('id'=>'rcal_close_time'
		 ,'class' => array('rcal_short_width')
		 ,'check' => array( 'chk_required','chkTime')
		 ,'label' => __('Close Time',RCAL_DOMAIN)
		 ,'tips' => __('The allowed formats for \"HH:MM\" or \"HHMM\". Set the reservation handling time for the management target.',RCAL_DOMAIN));
	
		$item_contents['time_step'] =array('id'=>'rcal_time_step'
		 ,'class' => array('rcal_short_width')
		 ,'check' => array( 'chk_required','num','range1_60')
		 ,'label' => __('Input time unit (minutes)',RCAL_DOMAIN)
		 ,'tips' => __('The unit of selection for time when a user makes a reservation. For example, setting this to 15 will result in options for 10:00, 10:15, etc. Setting 30 will result in options for 10:00, 10:30, etc. ',RCAL_DOMAIN));

		$item_contents['sp_date'] =array('id'=>'rcal_sp_date'
		 ,'class' => array()
		 ,'check' => array( 'chk_required','chkDate')
		 ,'label' => __('Irregular business days and holidays',RCAL_DOMAIN)
		 ,'tips' => __('After inputting the date, select \"Business day\" \"Special holiday\" and click the \"Add\" button. ',RCAL_DOMAIN));
	
		$item_contents['closed_day_check'] =array('id'=>'rcal_closed_day_check'
		 ,'class'	=>array()
		 ,'check' => array()
		 ,'label' => __('Off days',RCAL_DOMAIN)
		 ,'tips' => __('Specify the day of the week for off days for each week. Settings for special holidays such as the New Year\'s holiday or off days that will be treated as business days are configured \"Irregular business days and holidays\". In cases where, for example, Saturdays will be treated as half business days, checking the off day will display a detailed time schedule to allow specific settings for that day.',RCAL_DOMAIN));
		$item_contents['config_show_detail_msg'] =array('id'=>'rcal_config_is_show_detail_msg'
		 ,'class'	=>array()
		 ,'check' => array()
		 ,'label' => __('Detailed message output',RCAL_DOMAIN)
		 ,'tips' => __('Check to display detailed error information. Check to acquire information in the event of a diagnostic request.',RCAL_DOMAIN));
		$item_contents['config_name_order_set'] =array('id'=>'rcal_config_name_order_japan'
		 ,'class'	=>array()
		 ,'check' => array()
		 ,'label' => '8.'.__('Sequence of Sur Name and Given Name',RCAL_DOMAIN)
		 ,'tips' => __('please select Sur Name First or Given Name first',RCAL_DOMAIN));

		$item_contents['before_day'] =array('id'=>'rcal_before_day'
		 ,'class' => array('rcal_short_width')
		 ,'check' => array( 'chk_required','num')
		 ,'label' => __('Before X date',RCAL_DOMAIN)
		 ,'tips' => __('Specify the default display range for reservation data. The standard is to use the access date. Set to a range that reflects actual use (for example, reservations up to one month or one year) as setting X to a large value can result in response lag.',RCAL_DOMAIN));

		$item_contents['after_day'] =array('id'=>'rcal_after_day'
		 ,'class' => array('rcal_short_width')
		 ,'check' => array( 'chk_required','num')
		 ,'label' => __('After X date',RCAL_DOMAIN)
		 ,'tips' => __('Specify the default display range for reservation data. The standard is to use the access date. Set to a range that reflects actual use (for example, reservations up to one month or one year) as setting X to a large value can result in response lag.',RCAL_DOMAIN));

		$item_contents['cal_size'] =array('id'=>'rcal_cal_size'
		 ,'class' => array('rcal_short_width')
		 ,'check' => array( 'chk_required','num')
		 ,'label' => __('Calendar size ',RCAL_DOMAIN)
		 ,'tips' => __('Set the size for the displayed calendars. Up to three are displayed. In some cases, only one calendar is displayed depending on the theme due to having a narrow display area.For example, only one can be displayed when themes are at 100%. Setting to 80% will allow two to be displayed. ',RCAL_DOMAIN));

		$item_contents['logged_day'] =array('id'=>'rcal_logged_day'
		 ,'class' => array()
		 ,'check' => array()
		 ,'label' => __('Date',RCAL_DOMAIN)
		 ,'tips' => __('Logged date ',RCAL_DOMAIN)
		 ,'table' => array(  'class'=>''
							,'width'=>self::MIDDLE_WIDTH
							,'sort'=>'true'
							,'search'=>'true'
							,'visible'=>'true' ));

		$item_contents['logged_time'] =array('id'=>'rcal_logged_time'
		 ,'class' => array()
		 ,'check' => array()
		 ,'label' => __('Time',RCAL_DOMAIN)
		 ,'tips' => __('Logged time',RCAL_DOMAIN)
		 ,'table' => array(  'class'=>''
							,'width'=>self::MIDDLE_WIDTH
							,'sort'=>'true'
							,'search'=>'true'
							,'visible'=>'true' ));

		$item_contents['operation'] =array('id'=>'rcal_operation'
		 ,'class' => array()
		 ,'check' => array()
		 ,'label' => __('Operation',RCAL_DOMAIN)
		 ,'tips' => __('the operation to tables',RCAL_DOMAIN)
		 ,'table' => array(  'class'=>''
							,'width'=>self::LONG_WIDTH
							,'sort'=>'true'
							,'search'=>'true'
							,'visible'=>'true' ));

		$item_contents['logged_remark'] =array('id'=>'rcal_remark'
		 ,'class' => array()
		 ,'check' => array()
		 ,'label' => __('Remarks',RCAL_DOMAIN)
		 ,'tips' => __('REMOTE_ADDR,REFERER',RCAL_DOMAIN)
		 ,'table' => array(  'class'=>''
							,'width'=>self::LONG_WIDTH
							,'sort'=>'true'
							,'search'=>'true'
							,'visible'=>'true' ));


		$item_contents['reserve_deadline'] =array('id'=>'rcal_reserve_deadline'
		 ,'class'	=>array("rcal_middle_width_no_margin")
		 ,'check' => array("num")
		 ,'label' => __('Reservation cutoff time',RCAL_DOMAIN)
		 ,'tips' => __('Specify up to when reservations can be made in minutes, hours, or days. Setting 0 means that the user can cancel or change the reservation up to immediately prior to the reservation. Setting to 30 minutes means that, for example, with a 10 o\’clock appointment, cancellation or changes are possible up to 9:30.',RCAL_DOMAIN));

		$item_contents['name'] =array('id' => 'rcal_name'
		 ,'class' => array()
		 ,'check' => array( 'chk_required','lenmax50')
		 ,'label' => __('Name',RCAL_DOMAIN)
		 ,'tips' => __('Within 50 charactors. Set the name of the individual management target. For example, for reservation management for \"Conference rooms\", input the actual conference room name, such as \"Conference Room A\", \"Conference Room B\", etc. ',RCAL_DOMAIN)
		 ,'table' => array(  'class'=>'rcal_editable chk_required lenmax50'
							,'width'=>self::MIDDLE_WIDTH
							,'sort'=>'true'
							,'search'=>'true'
							,'visible'=>'true' ));

		$item_contents['config_name'] =array('id' => 'rcal_name'
		 ,'class' => array()
		 ,'check' => array( 'chk_required','lenmax50')
		 ,'label' => __('Your name',RCAL_DOMAIN)
		 ,'tips' => __('within 50 charactors.Embedded when an e-mail is sent.',RCAL_DOMAIN));



		$item_contents['valid_from'] =array('id'=>'rcal_valid_from'
		 ,'class' => array()
		 ,'check' => array( 'chkDate')
		 ,'label' => __('Valid period (from) ',RCAL_DOMAIN)
		 ,'tips' => __('Input if the management target has a valid period.',RCAL_DOMAIN)
		 ,'table' => array(  'class'=>''
							,'width'=>self::LONG_WIDTH
							,'sort'=>'true'
							,'search'=>'true'
							,'visible'=>'true' ));
		$item_contents['valid_to'] =array('id'=>'rcal_valid_to'
		 ,'class' => array()
		 ,'check' => array( 'chkDate')
		 ,'label' => __('Valid period (to)',RCAL_DOMAIN)
		 ,'tips' => __('Input if the management target has a valid period.',RCAL_DOMAIN)
		 ,'table' => array(  'class'=>''
							,'width'=>self::LONG_WIDTH
							,'sort'=>'true'
							,'search'=>'true'
							,'visible'=>'true' ));

		$item_contents['remark'] =array('id'=>'rcal_remark'
		 ,'class' => array()
		 ,'check' => array( 'lenmax300')
		 ,'label' => __('Remarks',RCAL_DOMAIN)
		 ,'tips' => __('within 300 charctors',RCAL_DOMAIN)
		 ,'table' => array(  'class'=>'rcal_editable lenmax300'
							,'width'=>''
							,'sort'=>'true'
							,'search'=>'true'
							,'visible'=>'true' ));
	
		$item_contents['max_setting'] =array('id'=>'rcal_max_setting'
		 ,'class' => array('rcal_short_width')
		 ,'check' => array( 'chk_required','num')
		 ,'label' => __('Max setting',RCAL_DOMAIN)
		 ,'tips' => __('How many set in the same time',RCAL_DOMAIN));

		$item_contents['confirm_style'] =array('id'=>'rcal_confirm_style'
		 ,'class'	=>array("rcal_long_width_no_margin")
		 ,'check' => array("num")
		 ,'label' => __('Reservation confirmation method',RCAL_DOMAIN)
		 ,'tips' => __('\"Confirmation by an administrator\":At the point that a user makes a reservation, this is treated as a temporary reservation. The reservation is confirmed when a person with WordPress administrator privileges updates the reservation. \"No confirm\":The reservation is confirmed when made by the user. \"Confirmation via user e-mail\":At the point that a user makes a reservation, this is treated as a temporary reservation. A link to the reservation confirmation screen is displayed in the e-mail sent to the user. The reservation is confirmed when the user uses the link to open the reservation confirmation page.',RCAL_DOMAIN));

		$item_contents['enable_reservation'] =array('id'=>'rcal_enable_reservation'
		 ,'class'	=>array("rcal_long_width_no_margin")
		 ,'check' => array("num")
		 ,'label' => __('Privileges allowing reservations',RCAL_DOMAIN)
		 ,'tips' => __('\"Anyone\":Anyone can make a reservation. \"Registered user\":Only the user registered WordPress can make a reservation.',RCAL_DOMAIN));


		$item_contents['send_mail_text_on_mail'] =array('id'=>'rcal_send_mail_text'
		 ,'class' => array()
		 ,'check' => array( 'lenmax300')
		 ,'label' => __('Mail text',RCAL_DOMAIN)
		 ,'tips' => __('Actual values are embedded into the \"{}\" portion of {X-TO_NAME} (referred to as \"embedded text\"). You may delete {X-SHOP_NAME}{X-SHOP_ADDRESS}{X-SHOP_TEL}{X-SHOP_MAIL} but do not delete any other embedded text. ',RCAL_DOMAIN));

		$item_contents['information_mail_text_on_mail'] =array('id'=>'rcal_information_mail_text'
		 ,'class' => array()
		 ,'check' => array( 'lenmax300')
		 ,'label' => __('Mail text to staff member',RCAL_DOMAIN)
		 ,'tips' => __('Actual values are embedded into the \"{}\" portion of {X-TO_NAME} (referred to as \"embedded text\"). You may delete {X-SHOP_NAME}{X-SHOP_ADDRESS}{X-SHOP_TEL}{X-SHOP_MAIL} but do not delete any other embedded text. ',RCAL_DOMAIN));

		$item_contents['send_mail_text_admin_on_mail'] =$item_contents['send_mail_text_on_mail'] ;
		$item_contents['send_mail_text_completed_on_mail'] =$item_contents['send_mail_text_on_mail'] ;
		$item_contents['send_mail_text_accepted_on_mail'] =$item_contents['send_mail_text_on_mail'] ;
		$item_contents['send_mail_text_canceled_on_mail'] =$item_contents['send_mail_text_on_mail'] ;

		$item_contents['send_mail_text_admin_on_mail']['id'] = 'rcal_send_mail_text_admin';
		$item_contents['send_mail_text_completed_on_mail']['id'] = 'rcal_send_mail_text_completed';
		$item_contents['send_mail_text_accepted_on_mail']['id'] = 'rcal_send_mail_text_accepted';
		$item_contents['send_mail_text_canceled_on_mail']['id'] = 'rcal_send_mail_text_canceled';


		$item_contents['send_mail_subject'] =array('id'=>'rcal_send_mail_subject'
		 ,'class' => array()
		 ,'check' => array('lenmax78')
		 ,'label' => __('Mail title',RCAL_DOMAIN)
		 ,'tips' => __('Within 78 charctors.Title of sent message.',RCAL_DOMAIN));
		 
		$item_contents['information_mail_subject'] =array('id'=>'rcal_information_mail_subject'
		 ,'class' => array()
		 ,'check' => array('lenmax78')
		 ,'label' => __('The subject of the mail to staff member',RCAL_DOMAIN)
		 ,'tips' => __('Within 78 charctors.Title of sent message.',RCAL_DOMAIN));


		$item_contents['send_mail_subject_admin'] =$item_contents['send_mail_subject'] ;
		$item_contents['send_mail_subject_completed'] =$item_contents['send_mail_subject'] ;
		$item_contents['send_mail_subject_accepted'] =$item_contents['send_mail_subject'] ;
		$item_contents['send_mail_subject_canceled'] =$item_contents['send_mail_subject'] ;

		$item_contents['send_mail_subject_admin']['id'] = 'rcal_send_mail_subject_admin';
		$item_contents['send_mail_subject_completed']['id'] = 'rcal_send_mail_subject_completed';
		$item_contents['send_mail_subject_accepted']['id'] = 'rcal_send_mail_subject_accepted';
		$item_contents['send_mail_subject_canceled']['id'] = 'rcal_send_mail_subject_canceled';

		$item_contents['mail_from_on_mail'] =array('id'=>'rcal_mail_from'
		 ,'class' => array()
		 ,'check' => array()
		 ,'label' => __('Mail header (From) ',RCAL_DOMAIN)
		 ,'tips' =>  __('Set the mail header FROM section using the format name <XXX@XXX.XXX>. If not set, then the \"WordPress\" default is used.',RCAL_DOMAIN)) ;


		
		$item_contents['mail_returnPath_on_mail'] =array('id'=>'rcal_mail_returnPath'
		 ,'class' => array()
		 ,'check' => array( 'chkMail')
		 ,'label' => __('Mail header (ReturnPath)',RCAL_DOMAIN)
		 ,'tips' => __('Set the mail header ReturnPath section using the mail address format(XXX@XXX.XXX). If not set, then the \"WordPress\" default is used.',RCAL_DOMAIN));


		$item_contents['target_mail_patern'] =array('id'=>'rcal_target_mail_patern'
		 ,'class' => array()
		 ,'check' => array()
		 ,'label' => __('Mail selection',RCAL_DOMAIN)
		 ,'tips' => __('Select the following mail to set the content for each mail. ',RCAL_DOMAIN));

		
		$item_contents['mail_bcc'] =array('id'=>'rcal_mail_bcc'
		 ,'class' => array()
		 ,'check' => array()
		 ,'label' => __('Notification mail recipient',RCAL_DOMAIN)
		 ,'tips' => __('Set the mail address.If the setting plurality of mail addresses, separated by commas',RCAL_DOMAIN));
		
		$item_contents['config_use_session'] =array('id'=>'rcal_config_is_use_session'
		 ,'class'	=>array()
		 ,'check' => array()
		 ,'label' => __('Use session id',RCAL_DOMAIN)
		 ,'tips' => __('If you get the message \"This request is invalid nonce\",uncheck this field',RCAL_DOMAIN));

		$item_contents['config_use_submenu'] =array('id'=>'rcal_config_is_use_submenu'
		 ,'class'	=>array()
		 ,'check' => array()
		 ,'label' => __('Use submenu',RCAL_DOMAIN)
		 ,'tips' => __('If you use the detail of resources,check this field',RCAL_DOMAIN));

		$item_contents['config_require'] =array('id'=>'rcal_config_require_dummy'
		 ,'class'	=>array()
		 ,'check' => array()
		 ,'label' => __('Are fields required ?',RCAL_DOMAIN)
		 ,'tips' => __('If fields are required, check this field',RCAL_DOMAIN));

		$item_contents['category_name'] =array('id'=>'rcal_category_name'
		 ,'class' => array()
		 ,'check' => array( 'chk_required','lenmax50')
		 ,'label' => __('Category name',RCAL_DOMAIN)
		 ,'tips' => __('within 50 charactors',RCAL_DOMAIN)
		 ,'table' => array(  'class'=>''
							,'width'=>self::MIDDLE_WIDTH
							,'sort'=>'true'
							,'search'=>'true'
							,'visible'=>'true' ));
		$item_contents['category_patern'] =array('id'=>'rcal_category_patern'
		 ,'class' => array()
		 ,'check' => array( 'chk_required')
		 ,'label' => __('Category Patern',RCAL_DOMAIN)
		 ,'tips' => __('select please',RCAL_DOMAIN)
		 ,'table' => array(  'class'=>''
							,'width'=>self::MIDDLE_WIDTH
							,'sort'=>'true'
							,'search'=>'true'
							,'visible'=>'true' ));
		$item_contents['category_value'] =array('id'=>'rcal_category_value'
		 ,'class' => array()
		 ,'check' => array('chk_required')
		 ,'label' => __('Category Value',RCAL_DOMAIN)
		 ,'tips' => __('Display values are separated by commas',RCAL_DOMAIN));
		$item_contents['no_edit_remark'] =array('id'=>'rcal_remark'
		 ,'class' => array()
		 ,'check' => array( 'lenmax300')
		 ,'label' => __('Remark',RCAL_DOMAIN)
		 ,'tips' => __('within 300 charctors',RCAL_DOMAIN)
		 ,'table' => array(  'class'=>''
							,'width'=>''
							,'sort'=>'true'
							,'search'=>'true'
							,'visible'=>'true' ));



		return $item_contents;	
	
		
	}
	
	public function serverCheck($items , &$msg) {
		
		$nonce = RCAL_PLUGIN_DIR;
		if ($this->config_datas['RCAL_CONFIG_USE_SESSION_ID'] == ResourceCalendar_Config::USE_SESSION){
			$nonce = session_id();
		}
		if (wp_verify_nonce($_REQUEST['nonce'],$nonce) === false) {
			throw new Exception(ResourceCalendar_Component::getMsg('E021',__function__.':'.__LINE__ ),1 );
		}
		if (count($items) == 0 ) return true;
		$item_contents = self::setItemContents();	
		//必須チェックをはずす場合

		$require_array = unserialize($this->config_datas['RCAL_CONFIG_REQUIRED']);
		if (empty($require_array ) ) $require_array = array();
		if ( in_array('rcal_name',$require_array)  == false)  {
			unset($item_contents['customer_name']['check'][0]);
		}
		if ( in_array('rcal_tel',$require_array) == false ) {
			unset($item_contents['booking_tel']['check'][0]);
		} 
		if (( in_array('rcal_mail',$require_array) == false  ) &&
 		    ($this->config_datas['RCAL_CONFIG_ENABLE_RESERVATION'] != ResourceCalendar_Config::CONFIRM_BY_MAIL )) {
			unset($item_contents['booking_mail']['check'][0]);
		}
		

		$err_msg = array();
		foreach ($items as $d1) {
			$id = $item_contents[$d1]['id'];
			foreach ($item_contents[$d1]['check'] as $d2 ) {
				self::serverEachCheck($_POST[$id],trim($d2),$item_contents[$d1]['label'],$err_msg);


			}
		}
		if (count($err_msg) > 0 ) {
			$msg = implode("\n",$err_msg);
			return false;
		}
		return true;
	}
	
	static function serverColumnCheck($target,$check_item,&$msg) {
		$item_contents = self::setItemContents();	
		$err_msg = array();
		foreach ($item_contents[trim($check_item)]['check'] as $d1 ) {
			self::serverEachCheck($target,$d1,$item_contents[trim($check_item)]['label'],$err_msg);
		}
		if (count($err_msg) > 0 ) {
			$msg = implode("\n",$err_msg);
			return false;
		}
		return true;
		
	}
	
	static function serverEachCheck($target,$check,$label,&$err_msg){
		if (trim($check) == 'chk_required') {
			if (empty($target)&& $target!==0) {
				$err_msg[] = ResourceCalendar_Component::getMsg('E201',$label);
				return false;
			}
		}
		if (trim($check) == 'reqCheckbox' ) {
			if (empty($target) ) {
				$err_msg[] = ResourceCalendar_Component::getMsg('E201',$label);
				return false;
			}
		}
		else {
			if (empty($target) ) return;
			switch (trim($check)) {
				case 'chkTime':
					if (preg_match('/^(?:\d{1,2}:\d{1,2})$|^(?:\d{4})$/', $target, $matches) == 0 ) {
						$err_msg[] = ResourceCalendar_Component::getMsg('E202',$label);
					}
					if ( +substr($target,0,2) > 24 ) {
						$err_msg[] = ResourceCalendar_Component::getMsg('E202',$label);
					}
					break;
				case 'num':
					if (preg_match('/^\d*$/',$target,$matches) == 0 ) {
						$err_msg[] = ResourceCalendar_Component::getMsg('E203',$label);
					}
					break;
				case 'chkZip':
					if (preg_match('/'.__('^\d{5}(?:[-\s]\d{4})?$',RCAL_DOMAIN).'/',$target,$matches) == 0 ) {
						$err_msg[] = ResourceCalendar_Component::getMsg('E205',$label);
					}
					break;
				
				case 'chkTel':
					if (preg_match('/^[\d\-]{1,15}$/',$target,$matches) == 0 ) {
						$err_msg[] = ResourceCalendar_Component::getMsg('E206',$label);
					}
					break;
				case 'chkMail':
					if (preg_match('/^[\w!#$%&\'*+\/=?^_{}\\|~-]+([\w!#$%&\'*+\/=?^_{}\\|~\.-]+)*@([\w][\w-]*\.)+[\w][\w-]*$/',$target,$matches) == 0 ) {
						$err_msg[] = ResourceCalendar_Component::getMsg('E207',$label);
					}
					
					break;
				
				case 'chkDate':
					if ((preg_match('/^'.__('(?<month>\d{1,2})[\/\.\-](?<day>\d{1,2})[\/\.\-](?<year>\d{4})',RCAL_DOMAIN).'$/',$target,$matches) == 0 ) && 
					   (preg_match('/^'.__('(?<month>\d{2})(?<day>\d{2})(?<year>\d{4})',RCAL_DOMAIN).'$/',$target,$matches) == 0 ) ){
						$err_msg[] = ResourceCalendar_Component::getMsg('E208',$label).$target;
					}
					elseif ( checkdate(+$matches['month'],+$matches['day'],+$matches['year']) == false ) {
						$err_msg[] = ResourceCalendar_Component::getMsg('E209',$label);
					}
					break;
				case 'chkSpace':
					$tmp = str_replace("　"," ",$target);
					if (preg_match('/^.+\s+.+$/', $tmp, $matches) == 0 ) {
						$err_msg[] = ResourceCalendar_Component::getMsg('E210',$label);
					}
					break;
				default:
					if (preg_match('/^lenmax(?<length>\d+)$/',trim($check),$matches) === 1 ) {
						$tmp_length = 0;
						if ( function_exists( 'mb_strlen' ) )  {
							$tmp_length = mb_strlen($target);
						}
						else {
							$tmp_length = strlen($target);
						}
						if ( $tmp_length > +$matches['length'] ) {
							$err_msg[] = ResourceCalendar_Component::getMsg('E211',array(+$matches['length'],$label));
						}
					}
			}
		}
	}
	
	static function setCheckContents($target = 'this') {
		//valにはチェックする値を、clには対象のクラスを全部格納しとく
		//当初はdetail部分だけに使用していたが、datatableでも使用するため拡張
		$check_contens = array();
		
	
		$check_contens['chk_required'] = '
						if ($j('.$target.').hasClass("chk_required") ) {
							if(val == "" || val === null){
								item_errors.push( "'.__('please enter',RCAL_DOMAIN).'");
							}
						}';
		$check_contens['num'] = '
							if ($j('.$target.').hasClass("num") ) {
								if( ! val.match(/^\d*$/)  ){
									item_errors.push( "'.__('please enter numeric',RCAL_DOMAIN).'");
								}
							}';
		//全角チェックはいらない
		$check_contens['zenkaku'] = '
							if ($j('.$target.').hasClass("zenkaku") ) {
								if( ! val.match(/^[^ -~｡-ﾟ]*$/)  ){
									item_errors.push( "'.__('please full width enter',RCAL_DOMAIN).'");
								}
							}';
		$check_contens['chkZip'] = '
							if ($j('.$target.').hasClass("chkZip") ) {
								if( ! val.match(/'.__('^\d{5}(?:[-\s]\d{4})?$',RCAL_DOMAIN).'/) ){
									item_errors.push( "'.__('please XXXXX-XXXX format',RCAL_DOMAIN).'");
								}
							}';
		//パターンで例外を考慮すると複雑になるので単純に
		//数字だけだと見えにくいのでハイフンを入れる
		$check_contens['chkTel'] = '
							if ($j('.$target.').hasClass("chkTel") ) {
								if( ! val.match(/^[\d\-]{1,15}$/) ){
									item_errors.push( "'.__('within 15 charctors',RCAL_DOMAIN).'");
								}
							}';
		$check_contens['chkMail'] = '
							if ($j('.$target.').hasClass("chkMail") ) {
								if( ! val.match(/^[\w!#$%&\'*+/=?^_{}\\|~-]+([\w!#$%&\'*+/=?^_{}\\|~\.-]+)*@([\w][\w-]*\.)+[\w][\w-]*$/)  ){
									item_errors.push( "'.__('please XXX@XXX.XXX format',RCAL_DOMAIN).'");
								}
							}';
//								if( ! val.match(/^[^\@]+?@[\w\.\-]+\.[\w\.\-]+$/)  ){
		$check_contens['chkTime'] = '
							if ($j('.$target.').hasClass("chkTime") ) {
								if( ! val.match(/^(?:[ ]?\d{1,2}:\d{1,2})$|^(?:\d{4})$/)  ){
									item_errors.push( "'.__('please HH:MM or HHMM format',RCAL_DOMAIN).'");
								}
								if (+val.slice(0,2) > 24 ) {
									item_errors.push( "'.__('Hour is max 24',RCAL_DOMAIN).'");
								}
								
							}';
	
		$check_contens['chkDate'] = '
							if ($j('.$target.').hasClass("chkDate") ) {
								if( val.match(/^'.__('(\d{1,2})[\/\.\-](\d{1,2})[\/\.\-](\d{4})',RCAL_DOMAIN).'$/) || val.match(/^'.__('(\d{2})(\d{2})(\d{4})',RCAL_DOMAIN).'$/)  ){
									var y = '.__('RegExp.$3',RCAL_DOMAIN).';
									var m = '.__('RegExp.$1',RCAL_DOMAIN).';
									var d = '.__('RegExp.$2',RCAL_DOMAIN).';
									
									var di = new Date(y, m - 1, d);
									if (!(di.getFullYear() == y && di.getMonth() == m - 1 &&  di.getDate() == d) ) {
										item_errors.push( "'.__('this day not exist?',RCAL_DOMAIN).'");
									}  								
								}
								else {
									item_errors.push( "'.__('please MM/DD/YYYY or MMDDYYYY format',RCAL_DOMAIN).'");
								}
							}';
	
		$check_contens['lenmax'] = '
							if ( cl.indexOf("lenmax") != -1 ) {
								var length = cl.match(/lenmax(\d+)/) ? RegExp.$1 : Number.MAX_VALUE;
								if ( val.length > length  ) {
									item_errors.push(
										length.replace(/[A-Za-z0-9]/g, function(s) {
											return String.fromCharCode(s.charCodeAt(0) + 0xFEE0);
										})+"'.__('within charactors',RCAL_DOMAIN).'");
								}
							}';
		$check_contens['range'] = '
							if ( cl.indexOf("range") != -1 ) {
								cl.match(/range(\d+?)_(\d+)/);
								var minval = RegExp.$1;
								var maxval = RegExp.$2;
								if ( ( +val < +minval  ) || ( +val > +maxval ) ){
									item_errors.push(
										minval.replace(/[A-Za-z0-9]/g, function(s) {
											return String.fromCharCode(s.charCodeAt(0) + 0xFEE0);
										})+"'.__('greater than',RCAL_DOMAIN).'"+
										maxval.replace(/[A-Za-z0-9]/g, function(s) {
											return String.fromCharCode(s.charCodeAt(0) + 0xFEE0);
										})+"'.__('within',RCAL_DOMAIN).'");
								}
							}';
		$check_contens['reqOther'] = '
						if (cl.indexOf("reqOther_") != -1 ){
							if(val == ""){
								var target_item = cl.match(/reqOther_(.+)/) ? RegExp.$1 : "";
								var tmp_items = target_item.split("_");
								var is_found = false;
								for(var i = 0 ;i<tmp_items.length;i++) {
									if ($j("#"+tmp_items[i]).val() ) {
										is_found = true;
										break;
									}
								}
								if (! is_found) {
									var msg = Array();
									msg.push(check_items[id]["label"]);
									for(var i = 0 ;i<tmp_items.length;i++) {
										msg.push(check_items[tmp_items[i]]["label"]);
									}
									item_errors.push(msg.join(",")+"'.__('select one or more',RCAL_DOMAIN).'");
								}
							}
						}';
	
		$check_contens['reqCheckbox'] = '
						if (cl.indexOf("reqCheckbox") != -1 ){
							var is_checked = false;
//							$j('.$target.').children().filter("input[type=checkbox]").each(function(){
							$j('.$target.').find("input[type=checkbox]").each(function(){
								if ( $j('.$target.').is(":checked") ) {
									is_checked = true;
								}
								
							});
							if (is_checked == false ) {
								item_errors.push("'.__('please check',RCAL_DOMAIN).'");
							}
						}';

							
		$check_contens['chkSpace'] = '
						if ($j('.$target.').hasClass("chkSpace") ) {
							val = val.replace("　"," ");
							if( ! val.match(/^.+\s+.+$/) ){
								item_errors.push( "'.__('space input between first-name and last-name',RCAL_DOMAIN).'");
							}
						}';
							
		return $check_contens;
	
	}
	static function echoDataTableDisplaySequence($col,$tag = "") {
		$up_name = __('up',RCAL_DOMAIN);
		$down_name = __('down',RCAL_DOMAIN);
		//順番は引数で渡す。ここでは支店の後ろなので４
		//スタッフの場合、seqデータがNULLの場合（WPにのみ登録しているユーザ）の対処
		echo <<<EOT
			var element = \$j("td:eq({$col})", nRow);
			element.text("");
			if (aData.{$tag}display_sequence) {
				var up_box = \$j("<input>")
						.attr("type","button")
						.attr("id","rcal_up_btn_"+iDataIndex)
						.attr("name","rcal_up_"+iDataIndex)
						.attr("value","{$up_name}")
						.attr("class","rcal_button rcal_button_updown")
						.click(function(event) {
							if (iDataIndex == 0 ) return;
							fnSeqUpdate(this.parentNode,iDataIndex,-1);
						});
				var down_box = \$j("<input>")
						.attr("type","button")
						.attr("id","rcal_down_btn_"+iDataIndex)
						.attr("name","rcal_down_"+iDataIndex)
						.attr("value","{$down_name}")
						.attr("class","rcal_button rcal_button_updown")
						.click(function(event) {
							if (iDataIndex == target.fnSettings().aoData.length-1) return;
							fnSeqUpdate(this.parentNode,iDataIndex,1);
						});
				element.append(up_box);
				element.append(down_box);
			}
			
EOT;
		
	}
	

	public function echoDataTableSeqUpdateRow($target_name,$target_key_name ,$tag="") {
		$target_src = get_bloginfo( 'wpurl' ).'/wp-admin/admin-ajax.php?action=rcal'.$target_name;
		if (empty($target_key_name) ) $target_key_name = $target_name;
		$menu_func = ucwords($target_name);
		$check_logic = '';
		echo <<<EOT
			function fnSeqUpdate(target_col,current_row,plus_minus) {
				var position = target.fnGetPosition( target_col );
				var setData = target.fnSettings();
				{$check_logic}
				var addIndex = position[0] + plus_minus;
				while(addIndex >= 0 && addIndex < target.fnSettings().aoData.length) {
					if (setData['aoData'][addIndex]['_aData']['{$tag}display_sequence'] ) break;
					addIndex += plus_minus;
				}
				if (addIndex < 0 || addIndex ==  target.fnSettings().aoData.length) return;
				
				var source_index = setData['aoData'][position[0]]['nTr']['_DT_RowIndex'];
				var source_sequence = setData['aoData'][position[0]]['_aData']['{$tag}display_sequence'];
				var target_index = setData['aoData'][addIndex]['nTr']['_DT_RowIndex'];
				var target_sequence = setData['aoData'][addIndex]['_aData']['{$tag}display_sequence'];
				var source_key_id = setData['aoData'][position[0]]['_aData']['{$target_key_name}'];
				var target_key_id = setData['aoData'][addIndex]['_aData']['{$target_key_name}'];
				

				\$j.ajax({
					type: "post",
					url:  "{$target_src}",
					dataType : "json",
						data: {
							"{$target_key_name}":source_key_id + "," + target_key_id,
							"value":source_sequence + "," + target_sequence,
							"type":"updated",
							"nonce":"{$this->nonce}",
							"menu_func":"{$menu_func}_Seq_Edit"
						},
					success: function(data) {
						if (data === null || data.status == "Error" ) {
							alert(data.message);
						}
						else {
							var save = setData['aoData'][position[0]];
							setData['aoData'][position[0]] = setData['aoData'][addIndex];
							setData['aoData'][position[0]]['nTr']['_DT_RowIndex'] = source_index;
							setData['aoData'][position[0]]['_aData']['{$tag}display_sequence'] = source_sequence;
							setData['aoData'][addIndex] = save;
							setData['aoData'][addIndex]['nTr']['_DT_RowIndex'] = target_index;
							setData['aoData'][addIndex]['_aData']['{$tag}display_sequence'] = target_sequence;
							target.fnDraw();
						}
					},
					error:  function(XMLHttpRequest, textStatus){
						var parse_arrary = JSON.parse( XMLHttpRequest.responseText )
						alert (parse_arrary.message);
					}
					
				 });			
			}
EOT;
		
	}
//
	private function _editDate($yyyymmdd) {
		return substr($yyyymmdd,0,4). substr($yyyymmdd,5,2).  substr($yyyymmdd,8,2);
	}
	private function _editTime($yyyymmdd) {
		return substr($yyyymmdd,11,2). substr($yyyymmdd,14,2);
	}

	public function echoMobileData($reservation_datas ,$target_day ,$first_hour) {
		//全件読むパターン
		$dayResource = array();
		$return_set = array();
		$randam_num = mt_rand(1000000,9999999);
		foreach($reservation_datas as $k1 => $d1 ) {
			$date = $this->_editDate($d1['time_from']);
			$from = $this->_editTime($d1['time_from']);
			$to = $this->_editTime($d1['time_to']);
			//管理者か自分の予約
			if ($this->isPluginAdmin() || ( ! empty($this->user_login) &&  $this->user_login === $d1['user_login'] ) ) {
				$dayResource[$date][$d1['resource_cd']][] = 
					array('s'=>$from
						,'e'=>$to
						,'ev'=>$d1['reservation_cd']
						,'st'=>ResourceCalendar_Edit::OK
						,'name'=>$d1['name']
						,'tel'=>$d1['tel']
						,'mail'=>$d1['mail']
						,'remark'=>$d1['remark']
						,'memo'=>unserialize($d1['memo'])
						,'p2'=>$d1['activate_key']
						,'status'=>$d1['status']
						);
			}
			else {
				$dayResource[$date][$d1['resource_cd']][] = array('s'=>$from,'e'=>$to,'ev'=>$d1['reservation_cd']+$randam_num,'st'=>ResourceCalendar_Edit::NG,'status'=>$d1['status']);
			}
		}
		

		//同一リソースでの重複をチェック->予約済みのDIVの高さを求める分母として使用
		//k1は日付単位。現状、１日だが複数日も可能にしとく
		if(count($dayResource) >  0 ) {
			foreach($dayResource as $k1 => $d1 ) {
				//k2はリソースコード単位
				$set_array = array();
				foreach($d1 as $k2 => $d2) {
					//複数ある場合のみチェック
					$dup_table = array();
					//添字は階層を意味する
					$dup_table[0][] = $d2[0];
					$set_cnt = 1;
					if (count($d2) > 1 ) {
						$max_dup = 0;
						for ($i = 1 ; $i < count($d2) ; $i++  ) {
							$dup_flg = false;
							//階層の中で重複しない
							for($j = 0 ;  $j <= $max_dup ;$j++ ) {
								foreach ($dup_table[$j]  as $k3 => $d3 ){
									//重複したら次の階層へ
									if ($d2[$i]['e'] <= $d3['s'] || $d3['e'] <= $d2[$i]['s'] ) {
									}
									else {
										$dup_flg = true;
										continue 2;
									}
								}
								//ここにきたら重複はない
								$dup_table[$j][] = $d2[$i];
								$dup_flg = false;
								break 1;
							}
							//新しい階層をつくる場合
							if ($dup_flg) {
								$max_dup++;
								$dup_table[$max_dup][] = $d2[$i];
							}
						}
						$set_cnt = $max_dup+1;
					}
					//ここで階層と階層の内容を設定k4は階層
					$set_time = array();						
					foreach ($dup_table as  $k4 => $d4 ) {
						//d5は実際の時間
						foreach ($d4 as $k5 => $d5 ) {
							//5分単位で左と幅を算出$this->branch_datas['time_step']を使う？								
							$left = ResourceCalendar_Component::calcMinute($first_hour.'00',$d5['s'])/5;
							$width = ResourceCalendar_Component::calcMinute($d5['s'], $d5['e'])/5;
							if ($d5['st'] == ResourceCalendar_Edit::OK) {
								$set_time[] = array($k4=>array("b"=>array($left,$width,$d5['ev'],$d5['s'],$d5['e'],$d5['st'],$d5['status']),
																"d"=>array($d5['remark']
																			,$d5['p2']
																			,$d5['name']
																			,$d5['tel']
																			,$d5['mail']
																			,$d5['memo']
																			)
																)
													);						
							}
							else  {
								$set_time[] = array($k4=>array("b"=>array($left,$width,$d5['ev'],$d5['s'],$d5['e'],$d5['st'],$d5['status']),"d"=>array()));
							}
							
						}
					}
					$set_array[] = array($k2=>array("s"=>$set_cnt,
													"d"=>$set_time)
										);
				}
				$return_set[$k1] = json_encode(array("e"=>1,"d"=>$set_array));
			}
		}
		else {
				$return_set[$target_day] = json_encode(array("e"=>0));
		}
		return $return_set;
	}

	public function echoSetHolidayMobile($resource_datas,$target_year) {
		
		if (!empty($this->config_datas['RCAL_CONFIG_CLOSED'])) {
			
			
			//定休日を設定
			$tmp_closed_arr = array();
			$tmp_closed_detail_arr = array();
			$tmp_closed_fullorHalf_arr = array();
			$datas = explode(",",$this->config_datas['RCAL_CONFIG_CLOSED']);
			foreach ($datas as $d1 ) {
				if (!empty($d1)) {
					//曜日;開始時間;終了時間
					$datas_arr = explode(';',$d1);
					//施設の開始時間と終了時間が異なる場合はDatepickerでクリックできる
					$tmp_closed_arr[] = $datas_arr[0];
					$from = str_replace(":","",$datas_arr[1]);
					$to = str_replace(":","",$datas_arr[2]);
					
					$left = ResourceCalendar_Component::calcMinute($this->config_datas['RCAL_CONFIG_OPEN_TIME'],$from)/5;
					$width = ResourceCalendar_Component::calcMinute($from,$to)/5;
					if ($from=="0000"&&$to=="2400") $width=288;
					$tmp_closed_detail_arr[] = array($left,$width,$from,$to);


					if (($datas_arr[1] != $this->config_datas['RCAL_CONFIG_OPEN_TIME']) || 
					($datas_arr[2] != $this->config_datas['RCAL_CONFIG_CLOSE_TIME'])) {
						$tmp_closed_fullorHalf_arr[] = ResourceCalendar_HOLIDAY_PATERN::HALF;
					}
					else {
						$tmp_closed_fullorHalf_arr[] = ResourceCalendar_HOLIDAY_PATERN::FULL;
					}
				}
			}
			echo 'rcalSchedule.config.days = ['.implode(',',$tmp_closed_arr).'];';
			echo 'rcalSchedule.config.days_detail = '.json_encode($tmp_closed_detail_arr).';';
			echo 'rcalSchedule.config.full_half = ['.json_encode($tmp_closed_fullorHalf_arr).'];';
		}
		if (!empty($this->config_datas['RCAL_SP_DATES'])) {
			//特殊な日の設定（定休日だけど営業するor営業日だけど休むなど）
			$sp_dates = $this->config_datas['RCAL_SP_DATES'];
			$on_business_array = array();
			$holiday_array = array();
			$today_check_array = array();
			for ($i=0;$i<2;$i++) {	//指定年と＋１(年末のことを考えて）
				$tmp_year = intval($target_year) + $i;
				if ($sp_dates && !empty($sp_dates[$tmp_year])) {
					foreach ($sp_dates[$tmp_year] as $k1 => $d1) {
						$today_check_array[$k1] = $d1;
						$tmp = 'new Date('.$tmp_year.','.(string)(intval(substr($k1,4,2))-1).','.(string)(intval(substr($k1,6,2))+0).')';
						if ($d1== ResourceCalendar_Status::OPEN ) {
							$on_business_array[] = $tmp;
							
						}
						elseif ($d1== ResourceCalendar_Status::CLOSE ) {
							$holiday_array[] = $tmp;
						}
					}
				}
			}
			echo 'rcalSchedule.config.on_business = [ '.implode(',',$on_business_array).' ];';
			echo 'rcalSchedule.config.holidays = [ '.implode(',',$holiday_array).' ];';
		}
		else {
			echo 'rcalSchedule.config.on_business = [  ];';
			echo 'rcalSchedule.config.holidays = [  ];';
		}

		
	}
	
	static function echoSearchCustomer($url = '') {
		if (empty($url) ) $url = get_bloginfo( 'wpurl' );
		$target_src = $url.'/wp-admin/admin-ajax.php?action=rcalsearch';
		$check_char = __('No',RCAL_DOMAIN);
		echo <<<EOT
			\$j("#button_search").click(function(){
				if (\$j("#rcal_name").val()||\$j("#rcal_tel").val()||\$j("#rcal_mail").val()) {
					\$j.ajax({
						type: "post",
						url:  "{$target_src}", 
						dataType : "json",
						data: {
							"type":"reservation",
							"name":\$j("#rcal_name").val(),
							"mail":\$j("#rcal_mail").val(),
							"menu_func":"Search_Page",
							"tel":\$j("#rcal_tel").val()
						},
			
						success: function(data) {
							if (data.status == "Error" ) {
								alert(data.message);
							}
							else {
								var mW = \$j("#rcal_search").find('.modalBody').innerWidth() / 2;
								var mH = \$j("#rcal_search").find('.modalBody').innerHeight() / 2;
								\$j("#rcal_search").find('.modalBody').css({'margin-left':-mW,'margin-top':-mH});
								\$j("#rcal_search").css({'display':'block'});
								\$j("#rcal_search").animate({'opacity':'1'},'fast');
								\$j("#rcal_search_result").html(data.set_data);
								if (+data.cnt > 0 ) {
									\$j("#rcal_search_result tr").click(function(event) {
										if (this.children[0].innerHTML == "{$check_char}" ) return;
										var name = this.children[1].innerHTML;
										\$j("#rcal_name").val(name);
										var tel = this.children[2].innerHTML;
										if (! tel) tel = this.children[3].innerHTML;
										\$j("#rcal_tel").val(tel);
										\$j("#rcal_mail").val(this.children[4].innerHTML);
//										save_name = name;
//										save_tel = tel;
//										save_mail = this.children[4].innerHTML;
										save_user_login = \$j(this).find("input").val();
										fnRemoveModalResult(this.parentNode.parentNode);
									});
									\$j("#rcal_search_result_data dl").click(function(event) {
										if (this.children[0].innerHTML == "{$check_char}" ) return;
										var name = this.children[1].innerHTML;
										\$j("#rcal_name").val(name);
										var tel = this.children[2].innerHTML;
										if (! tel) tel = this.children[3].innerHTML;
										\$j("#rcal_tel").val(tel);
										\$j("#rcal_mail").val(this.children[4].innerHTML);
//										save_name = name;
//										save_tel = tel;
//										save_mail = this.children[4].innerHTML;
										save_user_login = \$j(this).find("input").val();
										fnRemoveModalResult(this.parentNode);
									});
								}
							}
						},
						error:  function(XMLHttpRequest, textStatus){
							alert (textStatus);
						}
					});			
				}
			});
		\$j('#button_close1,#button_close2').click(function(){
			fnRemoveModalResult(this);
		});
EOT;
	
	}
	static function echoRemoveModal() {	
		echo <<<EOT
			function fnRemoveModalResult(element) {
				var target = \$j(element).parent().parent().parent();
				target.animate(
					{opacity:0,},
					{duration:'fast',complete:
						function() {
							\$j(element).parent().html("");
							target.css({'display':'none'});
						},
				});
			}
EOT;
	}

	static function echoShortcode() {	
		$msg1 = __('(1) Please copy and paste this tag to insert to the page',RCAL_DOMAIN);
		$msg2 = __('(2) After installing, "Reservation Confirm" is added to "Pages".Do not delete this. You may change the title.',RCAL_DOMAIN);
		$msg3 = __('(3) The “Reservation Confirm” is not displayed in the front screen.',RCAL_DOMAIN);
		$msg4 = __('(4) Sample data has been registered. It is a good idea to first use the sample data.',RCAL_DOMAIN);
		$hs = "<h4>";
		$he = "</h4>";
		echo <<<EOT
	<div id="shortcode_wrap">
		{$hs}{$msg1}<input id="display_shortcode" value="[resource-calendar]" />{$he}
		{$hs}{$msg2}{$he}
		{$hs}{$msg3}{$he}
		{$hs}{$msg4}{$he}
	</div>
EOT;
	}


}