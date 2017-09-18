<?php
header("Content-type: text/html; charset=utf-8");

set_time_limit(0);

define("DISABLE_SEARCH_REINDEX", 1); 
require_once("standalone.php");

ini_set('display_errors',1);
//error_reporting(E_ALL & ~E_Notice);

define("DIR", $dirname = dirname(__FILE__));
//ini_set('memory_limit', '1024M');

define('DEFAULT_IMAGES_FOLDER', '365');  // папка с картинками по-умолчанию

 if (isset ($_GET['getlistfolders'])) {  // вернуть подкаталоги
				header ("Content-Type:text/xml"); 

				$dir = '.'.$_GET['getlistfolders'];
				$dir_full = dirname (__FILE__).'/'.$dir;
				$err = '0'; $total = 0;
				echo '<udata method="getlistfolders" par="'.$dir.'">';
				if (file_exists($dir)) {
					$thisdir = dir($dir);
					while($entry=$thisdir->read()) {
						if(($entry!='.')&&($entry!='..')) { //&&(in_array(strtolower(file_extension($entry)), $validimagetypes)) ) { 
							if (is_dir($dir.$entry)) { 
								$total++;
								echo '<folder link="'.$dir.$entry.'/'.'" name="'.$entry.'">'.$entry.'</folder>';
							}
						}
					}
				}else{
					$err = 'Директория не найдена: '.$dir.'';
				}
				echo '<error>'.$err.'</error><total>'.$total.'</total></udata>';
				exit();
 }
 if (isset ($_GET['step'])) {  // вернуть подкаталоги
	 			switch($_REQUEST['step']) {
					case 'getitemlist': {$v = new import_processor(); echo $v->getitemsajax(); exit(); break;	}
					default: {
						//$this->process_import();
						break;
					}
				}	

 }

 if (isset ($_GET['getdatadb'])) {  // вернуть инфу по базе
		header("Content-type: text/html; charset=utf-8");

		$all_records = 0;
		$cnt_records = array();

		if ($_GET['getdatadb'] == '2') {
			$sql = "SELECT COUNT(*) AS cnt FROM cms3_object_content";
			$res = l_mysql_query($sql);
			while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
				$all_records = $row['cnt'];
				break;
			}

			$sql = "SELECT cms3_objects.type_id, COUNT(*) AS cnt FROM cms3_object_content
		INNER JOIN cms3_objects 
			ON (cms3_object_content.obj_id = cms3_objects.id)
	GROUP BY cms3_objects.type_id
	ORDER BY cnt DESC;";
			$res = l_mysql_query($sql);
			while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
				$cnt_records[$row['type_id']] = $row['cnt'];
			}
		}

			$sql = "SELECT cms3_objects.type_id, cms3_object_types.name, COUNT(cms3_objects.type_id) AS cnt FROM
    cms3_objects 
    INNER JOIN cms3_object_types 
        ON (cms3_objects.type_id = cms3_object_types.id)    
GROUP BY cms3_objects.type_id
ORDER BY cnt DESC;";
			$res = l_mysql_query($sql);
			$i=20;
			while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
				if ($i == 20) {
					echo '<div class="row" style="display: table-row;"><span style="display: table-cell; padding: 0 50px 0 0;">Тип данных</span><span style="display: table-cell; padding: 0 50px 0 0;">Наименование</span><span style="display: table-cell; padding: 0 50px 0 0; text-align: right;">Кол-во объектов</span>';
					if ($_GET['getdatadb'] == '2') {
						echo '<span style="display: table-cell; padding: 0 50px 0 0; text-align: right;">Кол-во записей</span>';
						if ($all_records > 0) echo '<span style="display: table-cell; padding: 0 50px 0 0; text-align: right;">%</span>';
					}
					echo '</div>';
				}
				echo '<div class="row" style="display: table-row;"><span class="type_id" style="display: table-cell; padding: 0 50px 0 0;">'.$row['type_id'].'</span><span style="display: table-cell; padding: 0 50px 0 0;">'.$row['name'].'</span><span class="cnt" rel="'.$row['cnt'].'" name="'.$row['type_id'].'" style="display: table-cell; padding: 0 50px 0 0; text-align: right;">'.number_format($row['cnt'], 0, ',', ' ').'</span>';

				if ($_GET['getdatadb'] == '2') {
					echo '<span class="size" rel="'.$cnt_records[$row['type_id']].'" name="'.$row['type_id'].'" style="display: table-cell; padding: 0 50px 0 0; text-align: right;">'.number_format($cnt_records[$row['type_id']], 0, ',', ' ').'</span>';
					if ($all_records > 0) echo '<span style="display: table-cell; padding: 0 50px 0 0; text-align: right;">'.(ceil ($cnt_records[$row['type_id']] / $all_records * 100)).'%</span>';
				}

				echo '</div>';
				if (--$i < 0) break;
			}


		exit();
 }

  if (isset ($_GET['getindexdb'])) {  // вернуть инфу по базе
		header("Content-type: text/html; charset=utf-8");

		echo 'Есть в индексе, но уже нет в базе (is_active = 0 OR is_deleted = 1) :';
		$sql = "SELECT
    `cms3_filter_index_56_pages_0`.`id`
    , `cms3_filter_index_56_pages_0`.`page_id`
    , `cms3_hierarchy`.`id`, is_active, is_deleted
FROM
    `cms3_filter_index_56_pages_0`
    LEFT JOIN `cms3_hierarchy` 
        ON (`cms3_filter_index_56_pages_0`.`page_id` = `cms3_hierarchy`.`id`)
WHERE ISNULL(`cms3_hierarchy`.`id` ) OR is_active = 0 OR is_deleted = 1;";

//echo $sql;

		$res = l_mysql_query($sql);
		$num_rows = mysql_num_rows($res);

			$i=20;
			while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
				if ($i == 20) {
					echo '<div class="row" style="display: table-row;"><span style="display: table-cell; padding: 0 50px 0 0;">page_id</span><span style="display: table-cell; padding: 0 50px 0 0;">is_active</span><span style="display: table-cell; padding: 0 50px 0 0; text-align: right;">is_deleted</span><span style="display: table-cell; padding: 0 50px 0 0; text-align: right;">reindex</span></div>';
				}
				echo '<div class="row" style="display: table-row;"><a class="page_id" href="/admin/catalog/edit/'.$row['page_id'].'/" target="_blank" style="display: table-cell; padding: 0 50px 0 0;">'.$row['page_id'].'</a><span style="display: table-cell; padding: 0 50px 0 0;">'.$row['is_active'].'</span><span style="display: table-cell; padding: 0 50px 0 0;">'.$row['is_deleted'].'</span><a href="/udata/catalog/init_fast_table//'.$row['page_id'].'?debug=1" target="Frame1" style="display: table-cell; padding: 0 50px 0 0;" onclick="$(this).css({\'text-decoration\': \'line-through\'})">reindex</a></div>';

				if (--$i < 0) break;
			}
			echo '<div class="row" style="display: table-row;">Всего найдено: ' . $num_rows .'</div>';
			if ($num_rows > 0) { echo '<iframe name="Frame1" height="70px" width="357px"></iframe>';}

		echo '<br><br>Есть в базе, но нет в индексе :';
		$sql = "SELECT 
  `cms3_hierarchy`.`id`, `cms3_hierarchy`.`type_id`,
  is_active,
  is_deleted 
FROM
  `cms3_hierarchy` 
  LEFT JOIN `cms3_filter_index_56_pages_0` 
    ON (
      `cms3_filter_index_56_pages_0`.`page_id` = `cms3_hierarchy`.`id`
    ) 
WHERE `cms3_hierarchy`.`type_id` = 78 AND ISNULL(`cms3_filter_index_56_pages_0`.`page_id`);";

//echo $sql;

		$res = l_mysql_query($sql);
		$num_rows = mysql_num_rows($res);
			$i=20;
			while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
				if ($i == 20) {
					echo '<div class="row" style="display: table-row;"><span style="display: table-cell; padding: 0 50px 0 0;">page_id</span><span style="display: table-cell; padding: 0 50px 0 0;">is_active</span><span style="display: table-cell; padding: 0 50px 0 0; text-align: right;">is_deleted</span><span style="display: table-cell; padding: 0 50px 0 0; text-align: right;">reindex</span></div>';
				}
				echo '<div class="row" style="display: table-row;"><a class="page_id" href="/admin/catalog/edit/'.$row['id'].'/" target="_blank" style="display: table-cell; padding: 0 50px 0 0;">'.$row['id'].'</a><span style="display: table-cell; padding: 0 50px 0 0;">'.$row['is_active'].'</span><span style="display: table-cell; padding: 0 50px 0 0;">'.$row['is_deleted'].'</span><a href="/udata/catalog/init_fast_table//'.$row['id'].'?debug=1" target="_blank"  style="display: table-cell; padding: 0 50px 0 0;">reindex</a></div>';

				if (--$i < 0) break;
			}
			echo '<div class="row" style="display: table-row;">Всего найдено: ' . $num_rows .'</div>';

		exit();

  }


class import_processor{

	public $global_clear_part_cnt = '5000';
	public $global_object_type_id = false;
	public $global_publish_date_fieldname = 'publish_time';
	public $global_update_date_fieldname = 'update_time';

	function import_processor(){
	}
	
	function run(){
		$is_logged = false; 

		//session_start();
		// проверка прав пользователя
		//$permissions = permissionsCollection::getInstance();
		//$isSv = $permissions->isSv();
		//if($permissions->isAdmin())
		$users_inst = cmsController::getInstance()->getModule("users");
		$user_id = 0;
		
		if($users_inst->is_auth()) {  // проверка на авторизацию пользователя..
			$user_id = $users_inst->user_id;
			//if ($isSv) {
				$is_logged = true; // need real check for login
			//}
		}
		
		//echo $is_logged;
		//exit();

		if (file_exists("tree.csv")) {
			if (($handle = fopen("tree.csv", "r")) !== FALSE) {
				if (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
				   $this->global_clear_part_cnt = $data[0];
				   $this->global_object_type_id = $data[1];
				   $this->global_publish_date_fieldname = $data[2];
				   $this->global_update_date_fieldname = $data[3];
				}
				fclose($handle);
			}
		}
		
		//if( $is_logged ){
			if( isset ($_POST['submit']) or isset($_REQUEST['counter_start'])){
				$this->process_import();
			}elseif (isset($_REQUEST['step'])) {
				switch($_REQUEST['step']) {
					case 1: {$this->get_list_old_item(); break;  }
					case 2: {$this->del_old_item(); break;	}
					default: {
						$this->process_import();
						break;
					}
				}
			} elseif (isset ($_GET['child_id'])) {  // найти и сохранить картинку для объекта
				$child_id = $_GET['child_id'];
				$hierarchy = umiHierarchy::getInstance();
				$element = $hierarchy->getElement($child_id);
				if($element instanceof iUmiHierarchyElement) {
					//$element->setValue('price', $_POST['value']);
					//$element->commit();
					if ($file_src = $this->del_element($child_id)) {
						$s = '<span style="color: #6600ff">удалён</span>';
					}
					else $s = 'пропущен';
					echo $s;
					exit;
				} else {
					echo 'false';
					exit;
				}
			} else{
					//if( isset ($_POST['submit_export'])  or isset($_REQUEST['id']))
						//echo '<A href="?id=0">Выберите раздел каталога</A>:'.$this->excel_page();
					//else {
						$this->show_form();
						$tcs = time();
						echo '<a href="/tree.php">Сервисные запросы</a>: <a href="javascript:void(0)" class="get_data_db" rel="1">кол-во данных в системе по типу объектов</a> (<a href="javascript:void(0)" class="get_data_db" rel="2">подробнее</a><IMG class="loading2" src="/images/loading.gif" width="16" height="16" border="0" alt="">) - <a class="0_clear_db" href="/0_clear_db.php?cron=1&refresh=1&limit='.$this->global_clear_part_cnt.'&cs='.$tcs.'" target="_blank" rel="'.$tcs.'">удалить '.$this->global_clear_part_cnt.' пустых заказов</a><div id="getdatadb"></div><div id="getdatadb_old" style="display: none"></div>';
						//echo 'Обслуживание индекса: <a href="javascript:void(0)" class="get_index_db" rel="1">Найти непроиндексированные товары</a><img class="loading4" src="/images/loading.gif" style="display: none;     vertical-align: middle;"/>';
						//echo '<div id="box1" style="margin-top: 5px;"></div><div id="box2" style="">Найти товары по всему сайту, выключенные <input id="dataoldday" type="text" name="oldday" value="365" style="width: 50px"> дней назад. <input type="button" value="Получить кол-во старых товаров" onclick="checkolditem();"><input id="deloldall" type="button" value="Начать удаление" onclick="findolditem();"><input id="stopoldall" type="button" value="Остановить удаление" onclick="stopoldall();" style="display: none">&nbsp;<a href="/_del_old_tovars.html">Лог удаления</a></div><div id="loading3" class="loading3" style="float:left"><img src="/images/loading.gif"/></div><div id="topresult"></div><div id="proc" class=""></div><div id="result" class=""></div>';
						echo '<br/><A href="?id=0&cs='.$tcs.'">Выберите раздел каталога</A>:';
						echo $this->excel_page();
					//}
			}
		//}
		//else 
		//	echo 'Недостаточно прав';

?>
<script type="text/javascript">
	jQuery(document).ready(function() {

		$('.get_data_db').click(function(){

			var par = $(this).attr('rel');
			var t1 = Date.now();
			jQuery('.loading2').show();
		
			$.ajax({
				url: '?getdatadb=' + par + '&t=' + t1,
				dataType: 'html',
				success: function(data, status){

					$('#getdatadb_old').html($('#getdatadb').html());
					$('#getdatadb').html(data);
					jQuery('.loading2').hide();

					if ($('#getdatadb_old').text().length > 0) {
						$('#getdatadb .cnt').each(function(){
							var type_id = $(this).attr('name');
							var cnt = parseInt($(this).attr('rel'));
							var cntold = parseInt($('#getdatadb_old .cnt[name="' + type_id + '"]').attr('rel'));
							if (cntold > 0 && cntold != cnt) {
								if (cntold < cnt) 
									$(this).append('<span class="added small right">&nbsp;(+' + (cnt - cntold ) + ')</span>');
								else
									$(this).append('<span class="blue small right">&nbsp;(' + (cnt - cntold ) + ')</span>');
							}
						});

						$('#getdatadb .size').each(function(){
							var type_id = $(this).attr('name');
							var cnt = parseInt($(this).attr('rel'));
							var cntold = parseInt($('#getdatadb_old .size[name="' + type_id + '"]').attr('rel'));
							//console.log(type_id, cnt, cntold);
							if (cntold > 0 && cntold != cnt) {
								if (cntold < cnt) 
									$(this).append('<span class="added small right">&nbsp;(+' + (cnt - cntold ) + ')</span>');
								else
									$(this).append('<span class="blue small right">&nbsp;(' + (cnt - cntold ) + ')</span>');
							}
						});
					}


				},
				error: function(data, status, e){
					alert(e);
					jQuery('.loading2').hide();
				}
			});
		
		});

		$('.0_clear_db').click(function() {
			var href = $(this).attr('href');
			var tcs = $(this).attr('rel');
			var tcsnew = Date.now();

			var hrefnew = (href.indexOf('cs=') != -1) ? href.substr(0, href.indexOf('cs=')) : false;
			if (!hrefnew) return false;
			$(this).attr('rel', tcsnew);
			$(this).attr('href', hrefnew + 'cs=' + tcsnew);
		
		});

		$('.get_index_db').click(function(){

			var par = $(this).attr('rel');
			var t1 = Date.now();
			jQuery('.loading4').show();
		
			$.ajax({
				url: '?getindexdb=' + par + '&t=' + t1,
				dataType: 'html',
				success: function(data, status){

					//$('#getdatadb_old').html($('#getdatadb').html());
					$('#result').html(data);
					jQuery('.loading4').hide();

					if ($('#getdatadb_old').text().length > 0) {
					
					}


				},
				error: function(data, status, e){
					alert(e);
					jQuery('.loading4').hide();
				}
			});
		
		});

	})
</script>
<?php

	}
	

	function convert($str){
		if(!is_numeric($str)) {
			if(function_exists('mb_convert_encoding')) {
				return mb_convert_encoding($str, "UTF-8", "cp1251");
			}
			else
				return iconv("CP1251", "UTF-8", $str);
		}
		return $str;
		
	}

	function convert_to_1251($str){
		if(!is_numeric($str)) {
			if(function_exists('mb_convert_encoding')) {
				return mb_convert_encoding($str, "cp1251", "UTF-8");
			}
			else
				return iconv("UTF-8", "CP1251", $str);
		}
		return $str;
	}
	
	public function getitemsajax( $id=null) {

		//error_reporting(E_ALL);
		error_reporting(E_ALL & ~E_NOTICE);

		if (($handle = fopen("tree.csv", "r")) !== FALSE) {
			if (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
			   $this->global_clear_part_cnt = $data[0];
			   $this->global_object_type_id = $data[1];
			   $this->global_publish_date_fieldname = $data[2];
			   $this->global_update_date_fieldname = $data[3];
			}
			fclose($handle);
		}

		$hierarchy = umiHierarchy::getInstance();
		$users_inst = cmsController::getInstance()->getModule("users");
		$user_id = 0;
		if($users_inst->is_auth()) {  // проверка на авторизацию пользователя..
			$user_id = $users_inst->user_id;
		}
		
		$id = (getRequest('id')) ? intval(getRequest('id')) : 887192;
		$artikul = (getRequest('artikul')) ? getRequest('artikul') : false;
		$type = (getRequest('type')) ? getRequest('type') : false;
		$offset = (getRequest('offset')) ? intval(getRequest('offset')) : 0;
		$limit = (getRequest('limit')) ? intval(getRequest('limit')) : 20;

		$s = '';

		//выборка
		$sel = new selector('pages');
		if($type == 'object') 
			$sel->types('hierarchy-type')->name('catalog', 'object');
		else
			$sel->types('hierarchy-type')->name('catalog', 'category');
		$sel->where('hierarchy')->page($id)->childs(1);
		if ($artikul) {
			$sel->where('code')->equals($artikul);
		}else{
			$sel->where('is_active')->equals(array(0,1));
		}
		//if(getRequest('update_time')) $sel->where('update_time')->eqmore(getRequest('update_time'));
		if(getRequest('update_time')) $sel->where($this->global_update_date_fieldname)->eqmore(getRequest('update_time'));
		$sel->limit($offset, $limit);

		$result=$sel->result;
		$s = '';
		if($sel->length > 0) {
		
			foreach($result as $item) {

				$itemxml = array(
					'attribute:id'		=> $item->getId(),
					'attribute:artikul'	=> $item->getValue('articul_for_customer'),
					'attribute:is-active'		=> $item->getIsActive(),
					'node:name'			=> $item->getName()
				);
				$items[] = $itemxml;
				$cnt1 = $hierarchy->getChildsCount($item->getId(), true, true, 1);

				$cl = ''; $ca = 'disabled'; 
				if ($users_inst->isSv($user_id)) $cl = 'editable';
				if ($item->getIsActive()) $ca = 'active';

				$price=$item->getValue("price");
				$price = empty($price) ? '' : ' (<span class="'.$cl.'" id="'.$item->getId().'">'.$price.'</span> )';

				//$pubTime = $item->getValue("publish_time");
				$this->global_publish_date_fieldname;
				$pubTime = $item->getValue($this->global_publish_date_fieldname);
				//$updTime = $item->getValue("update_time");
				$updTime = $item->getValue($this->global_update_date_fieldname);
				$publish_time = ($pubTime instanceof umiDate) ? $pubTime->getFormattedDate('U') : time() ;
				$update_time = ($updTime instanceof umiDate) ? $updTime->getFormattedDate('U') : 0 ;
				$timelast = ($update_time > $publish_time) ? $update_time : $publish_time;
				$pt = ($timelast > time() - 86400*365) ? '' : 'old';

				$spt = ($timelast > time() - 86400*365) ? ' <span class="publish_time" style="" rel="'.$timelast.'" name="'.time().'">'.date('Y-m-d', $timelast).'</span>' : ' <span class="publish_time old" style="" rel="'.$timelast.'" name="'.time().'">'.date('Y-m-d', $timelast).'</span>';
				$spt = str_replace('"', "'", $spt);

				$photo=$item->getValue("photo");
				$photo1=$item->getValue("photo1");
				$photo2=$item->getValue("header_pic");
				$class = (empty($photo) and empty($photo1) and empty($photo2)) ? 'poz_gray poz_gray_nofoto' : 'poz_gray';
				$class .= ' '.$ca.' '.$pt;

				$objectId = $item->getObject()->getId();
				//$cnt_virtual = $hierarchy->checkIsVirtual(array($child_id=>false));
				$virtuals = $hierarchy->getObjectInstances($objectId, true, true);
				$i = 0;
				$vs = '';
				foreach($virtuals as $virtualElementId) {
					$element = $hierarchy->getElement($virtualElementId);
					if($element instanceof umiHierarchyElement) {
						$ca = ($element->getIsActive()) ? 'active' : 'disabled';
						$vs.= ' <a href="'.$hierarchy->getPathById($virtualElementId).'" target="_blank" class="v'.$ca.'" style="font-size: 10px;">v'.(++$i).'</a>';
					}
				}
				$vs = str_replace('"', "'", $vs);

				$item_s = '<item id="'.($item->getId()).'" update_time="'.$spt.'" cnt="'.$cnt1.'" link="'.$hierarchy->getPathById($item->getId()).'" is-active="'.$item->getIsActive().'" virtuals="'.$vs.'" class="'.$class.'">'.$item->getName().' '.$price.'</item>';
				$s .= $item_s;

				$block_arr['subnodes:items'] = $items;
			}

		}

		$text = ''; 

		if ($offset + count($result) >= $sel->length) {
			$text = 'end';
		}
		$block_arr['total'] = $total = $sel->length;
		$block_arr['cnt'] = $cnt = count($result);
		$block_arr['text'] = $text;

		//return $block_arr;
		return $sResult = '<udata><items>' . $s . '</items><error>0</error><total>'.$total. '</total><cnt>' . $cnt . '</cnt></udata>';
		
	}

	public function get_excel_link($child_id) {
		return ' <A target=_blank href="/admin/catalog/edit/'.$child_id.'/" title="открыть в новом окне" class="linkedit">>></A> <A target=_blank href="'.umiHierarchy::getInstance()->getPathById($child_id).'" title="открыть в новом окне" class="linkview">>></A>';
	}

	function get_list_old_item() {

		if (isset($_REQUEST['update_time'])) $update_time = time() - $_GET['update_time'] * 86400; else $update_time = time() - 365 * 86400;
		$id = (getRequest('id')) ? intval(getRequest('id')) : 0;
		$offset = (getRequest('offset')) ? intval(getRequest('offset')) : 0;
		$limit = (getRequest('limit')) ? intval(getRequest('limit')) : 20;
		$check = (getRequest('check')) ? getRequest('check') : false;

		$s = 'begin: '.date('d.m.Y');

		if ($offset == 0 and !$check) { $this->tolog($s, 0); $time_script_start = time(); }

		//выборка
		$sel = new selector('pages');
		if ($this->global_object_type_id) 
			$sel->types('object-type')->id($this->global_object_type_id);
		else
			$sel->types('hierarchy-type')->name('catalog', 'object');
		$sel->where('hierarchy')->page($id)->childs(100);
		$sel->where('is_active')->equals(0);
		//$sel->where('update_time')->eqless($update_time);
		$sel->where($this->global_update_date_fieldname)->eqless($update_time);
		$sel->limit($offset, $limit);

		$s = '';
		$result=$sel->result;
		if($sel->length > 0) {
		
			$objects_ids = $items = array();

			foreach($result as $item) {
				if(!in_array($item->getObjectId(),$objects_ids)) {
					$objects_ids[] = $item->getObjectId();
					$item = '<item id='.$item->getId().'>'.$item->getName().'</item>';
					$s .= $item;
				} else {
					continue;
				}
			}
		}

		$text = ''; 

		if ($offset + count($result) >= $sel->length) {
			$text = 'end';
		}
		$total = $sel->length;
		$cnt = count($result);
		$this->tolog('Total: '.$total.', cnt: '.$cnt.', offset: '.$offset); 
			
		$sResult = '<udata><items>' . $s . '</items><error>0</error><total>'.$total. '</total><cnt>' . $cnt . '</cnt></udata>';
		echo $sResult;
	}

	public function del_old_item() {
		$id = (getRequest('id')) ? intval(getRequest('id')) : false;

		$hierarchy = umiHierarchy::getInstance();

		$sResult = '<udata><error>1</error></udata>';
		$cnt = '0';
		$text = '';

		if ($id) {
			$element = $hierarchy->getElement($id);
			if($element instanceof umiHierarchyElement) {

				//$pubTime = $element->getValue("publish_time");
				$pubTime = $element->getValue($this->global_publish_date_fieldname);
				$publish_time = ($pubTime instanceof umiDate) ? $pubTime->getFormattedDate('U') : time() ;

				if (!$element->getIsActive()) {
					$name = $element->getName();
					$objectId = $element->getObject()->getId();
					//$cnt_virtual = $hierarchy->checkIsVirtual(array($child_id=>false));
					$virtuals = $hierarchy->getObjectInstances($objectId, true, true);
					$cnt = count($virtuals);
					$text = 'удалено '.$name.' '.$pubTime;
					foreach($virtuals as $virtualElementId) {
						$hierarchy->delElement($virtualElementId);
						$hierarchy->removeDeletedElement($virtualElementId);
					}
					//$objects = umiObjectsCollection::getInstance();  
					//$objects->delObject($objectId);
					$this->tolog('<br/>Удалён: '.$name.', виртуальных копий: '.$cnt.', id: '.$id.', дата публикации: '.$pubTime); 
					$sResult = '<udata><error>0</error><text>'.$text. '</text><cnt>' . $cnt . '</cnt></udata>';
				}

			}
		}
		echo $sResult;

	}

	public function del_element($child_id) {
		$hierarchy = umiHierarchy::getInstance();

		$images_folder = getRequest('images_folder');

		if ($images_folder and is_numeric($images_folder) and $images_folder > 30 and $images_folder < 400) {
			$element = $hierarchy->getElement($child_id);
			if($element instanceof umiHierarchyElement) {

				//$pubTime = $element->getValue("publish_time");
				$pubTime = $element->getValue($this->global_publish_date_fieldname);
				$publish_time = ($pubTime instanceof umiDate) ? $pubTime->getFormattedDate('U') : time() ;
				if ($publish_time < time() - 86400*$images_folder) {
					$objectId = $element->getObject()->getId();
					//$cnt_virtual = $hierarchy->checkIsVirtual(array($child_id=>false));
					$virtuals = $hierarchy->getObjectInstances($objectId, true, true);
					foreach($virtuals as $virtualElementId) {
						$hierarchy->delElement($virtualElementId);
						$hierarchy->removeDeletedElement($virtualElementId);
					}
					//$objects = umiObjectsCollection::getInstance();  
					//$objects->delObject($objectId);
					return '1';
				}

			}


		}
	}

	function excel_page($id=null, $parent=array(), $level=0){
		$hierarchy = umiHierarchy::getInstance();

		//$template = 'excel';
		//list($template_block, $template_item, $template_form) = def_module::loadTemplates("./tpls/catalog/{$template}.tpl", 'importer', 'order_item', 'form');

		if ($id) {
			$parentElementId = $id;
		}
		else {
			$parent[] = $actElementId = intval(getRequest('id'));
			while ($actElementId) {
				// найти всех предков и записать в массив
				$element = $hierarchy->getElement($actElementId);
				if($element instanceof iUmiHierarchyElement) 
					$parent[] = $actElementId = $element->getParentId();
				else
					$actElementId = null;
			}
			$parentElementId = 0;
		}

		(isset($_SERVER['SERVER_NAME']))?$domain_host=$_SERVER['SERVER_NAME']:$domain_host='localhost';
		$cmsController = cmsController::getInstance();
		$domain_id = domainsCollection::getInstance()->getDomainId($domain_host);
		if(!$domain_id) 
			$domain_id = $cmsController->getCurrentDomain()->getId();

		// проверка прав пользователя
		$users_inst = cmsController::getInstance()->getModule("users");
		$user_id = 0;
		if($users_inst->is_auth()) {  // проверка на авторизацию пользователя..
			$user_id = $users_inst->user_id;
		}

		// получаем экземпляр коллекции
		if ($id) 
			$s = '';
		else
			//$s = $template_block; // добавил сюда блок с загрузкой скриптов для редактирования цены на месте, editable
		$s = <<<END
			<script language="javascript" type="text/javascript" src="/js/jquery/jquery.js"></script>

			<STYLE type="text/css">
			.price_v_valyuta {
				font-weight: bold;
			}	
			.price_edit_workplace span {padding: 0 5px;}
			.editable input {width: 50px!important; margin: 0 5px!important}
			.price_org {display:none}
			.poz_gray {color:#d3d3d3}
			.poz_gray.poz_gray_nofoto {color: #ffcaca !important}
			.poz_gray.poz_gray_nofoto.active {color: red !important}
			.poz_gray.active {color:gray}
			.publish_time.old {color: red; font-size: 10px;}
			.publish_time {color: #339900; font-size: 10px;}
			.vdisabled {    color: #ffcaca;}
			.row span {color: gray; display: table-cell; font-size: 14px;}
			.linkedit {
    background: url("images/cms/admin/mac/tree/ico_edit.png") repeat scroll 0 0 rgba(0, 0, 0, 0);
    color: rgba(0, 0, 0, 0);
    display: inline-block;
    height: 16px;
    opacity: 0.3;
    text-decoration: none;
    width: 16px;
}
			.linkview {opacity: 0.3;}
			.linkedit:hover, .linkview:hover {opacity: 1;} 	
			input, select {margin: 3px;}
			.blue {color: deeppink !important; }
			.added {color: #cc0033 !important; }
			.right {float: right;}
			.small {font-size: 12px !important;}
			.loading, .loading2, .loading3 {display:none}

div#autodel.fixed {
    position: fixed;
    top: 0px;
    left: 0;
    width: 100%;
    background-color: #fff;
    border: 1px solid;
    z-index: 1;
    padding: 7px 0 0 7px;
}
div#result {
    color: orchid;
    font-size: 13px;
}
.vis { opacity: 0.4; }
			</STYLE>

			<SCRIPT type="text/javascript">
				var need_next = true; 

				$(document).ready(function(){
					category_link_init();

					$(window).load(function(){
						var obj = $('#autodel');
						var offset = obj.offset();
						var topOffset = offset.top;
						var gr = 5;

						var docheight = $(document).height();

						$(window).scroll(function() {
							var scrollTop = $(window).scrollTop();
							//console.log(scrollTop);
							gr = $('#getdatadb').height() + 20;
							console.log(gr);

							if (scrollTop >= gr) {
								//obj.show('fast');
								obj.addClass('fixed').css({opacity: 1});//.animate({opacity: 1}, 180);
							 }else{
								 obj.removeClass('fixed');
							 }

						});
						$(window).scroll();
					});

				});

				function category_link_init() {
					$('a.category').unbind();
					$('a.category').click(function() {
						var obj = $(this);
						var id = obj.prev().attr('name');
						obj.toggleClass('open');
						//$('#loading2').remove(); $('#proc2').remove(); $('#log2').remove();
						if(obj.hasClass('open')) {
							obj.prev().prev().prev().css({'background-position':'48px 16px'});
							if (!obj.hasClass('loaded')) {
								$('<span id="loading' + id + '"><IMG SRC="/images/loading.gif" BORDER="0"></span><span id="proc' + id + '"></span><span id="log' + id + '"></span><div id="cat' + id + '" class="box" style="margin: -17px 0 -15px ' + obj.prev().prev().offset().left +  'px;"></div>').insertAfter(obj.next().next());
								getnewitemlist(id, 'category', 0, 1000);
							}else{
								$('#cat' + id).slideDown();
							}
						}else{
							obj.prev().prev().prev().css({'background-position':'32px 32px'});
							$('#cat' + id).slideUp();
						}
						return false;
					});

				}

			function getnewitemlist(id, type, offset, limit) {
					$('#loading2').show();

					var form = $('body form').eq(0);
					//if(!form.find('input[name="counter_start"]').is('input')) { $('#result').html(''); $('#topresult').html('Импорт запущен');}

					$.ajax({
						url: '?step=getitemlist&id=' + id + '&type=' + type + '&offset=' + offset + '&limit=' + limit,
						data: form.serialize(),
						dataType: 'html',
						success: function(data, status){
							//console.log('ajax');
							var error = $(data).find('error').text();
							//console.log('2', status);
							//console.log(error);
							if (error != '0') {
								$('#log' + id).html('<b style="color: red">' + error + '</b>');
							}else{
								 var total = parseInt($(data).find('total').text());
								 var cnt = parseInt($(data).find('cnt').text());
								 var items = $(data).find('item');
								 var text = $(data).find('text').text();

								 items.each(function() {
									 if (type == 'category') {
										$('<br/><div style="width: 16px; height: 16px; background-image: url(/images/icons.png); background-position:32px 32px; display:inline-block"></div><div style="width: 16px; height: 16px; background-image: url(/images/icons.png); display:inline-block" class="vis' + $(this).attr('is-active') + '"></div><a name="' + $(this).attr('id') + '"></a><a href="?id=' + $(this).attr('id') + '&amp;cs=1449650515#' + $(this).attr('id') + '" class="category" style="text-decoration:none;"> <font color="#6699FF" class="vis' + $(this).attr('is-active') + '">' + $(this).text() + ' (' + $(this).attr('cnt') + ')</font></a> <a target="_blank" href="/admin/catalog/edit/' + $(this).attr('id') + '/" title="открыть в новом окне" class="linkedit">&gt;&gt;</a> <a target="_blank" href="' + $(this).attr('link') + '" title="открыть в новом окне" class="linkview">&gt;&gt;</a>').appendTo($('#cat' + id));
									 }else{
										var str = $(this).attr('virtuals');
										str = replaceAll(str, "'", '"');
										var strt = $(this).attr('update_time');
										strt = replaceAll(strt, "'", '"');
										$('<br/><div style="width: 16px; height: 16px; background-image: url(/images/icons.png); background-position:32px 32px; display:inline-block"></div><div style="width: 16px; height: 16px; background-image: url(/images/cms/admin/mac/tree/ico_catalog_object.png); display:inline-block" class="vis' + $(this).attr('is-active') + '"></div> <font class="' + $(this).attr('class') + '" id="' + $(this).attr('id') + '" ondblclick="test(' + $(this).attr('id') + ');" rel="1152525">' + $(this).text() + ' </font>' + strt + ' ' + str + '<span id="' + $(this).attr('id') + '_ajax" style="padding:0 5px" class="ajax_data"></span> <a target="_blank" href="/admin/catalog/edit/' + $(this).attr('id') + '/" title="открыть в новом окне" class="linkedit">&gt;&gt;</a> <a target="_blank" href="' + $(this).attr('link') + '" title="открыть в новом окне" class="linkview">&gt;&gt;</a>').appendTo($('#cat' + id));
									 }
								});

								if (offset + cnt < total) {
									var proc = Math.round((offset + cnt) / total * 100);
									$('#proc' + id + '').html(' ' + proc + '% ...');
									//startsync(offset + cnt, limit);
									//itemupdate(items, 0, offset, cnt, limit);
									if(type == 'object') {
										getnewitemlist(id, type, offset + cnt, limit);
									}
								}else {
									var t = (type == 'category') ? 'категорий' : 'товаров';
									$('#proc' + id + '').html('');
									$('#loading' + id + '').hide();
									$('#log' + id + '').append('<IMG SRC="/images/ok.gif" BORDER="0">  ' + t + ': ' + total + '  ' + text + '');
									if(type == 'category') {
										category_link_init();
										getnewitemlist(id, 'object', 0, 10);
									}else{
										$('a[name="' + id + '"]').next().addClass('loaded');
										if(total > 0) {
											$('#autodel').appendTo($('#box1'));
											$('#autodel').slideDown();
											$('#box2').slideUp();
										}
									}
								}
							}						
						},
						error: function(data, status, e){
							//alert(e);

						}
					});
			}

				function replaceAll(str, find, replace) {
				  return str.replace(new RegExp(find, 'g'), replace);
				}

				function test(child_id) {
					child_id = child_id || jQuery('.disabled.old:first').attr('id');
					if (child_id  == jQuery('.disabled.old:first').attr('id')) {
						jQuery('.loading_count').text('0');
						jQuery('.ajax_data').text('');
					}
					jQuery('.loading').show();

					var images_folder = jQuery('input[name="images_folder"]').val();

					jQuery.ajax({
						url: '/tree.php?child_id='+child_id + '&images_folder='+images_folder,  
						cache: false,
						success: function (data, textStatus) {
							$("#"+child_id + '_ajax').html(data);
							//alert('succes');
							jQuery('.loading_count').text(parseInt(jQuery('.loading_count').text())+1);

							// искать следующий
							var id = jQuery("#"+child_id).nextAll('.disabled.old').attr('id');
							if(id && need_next) test(id)
							else {
								jQuery('img.loading').hide();
								jQuery('input.loading').hide();
								need_next = true; 
							}
						} 
					});
				}

				// удаление старых товаров по всему сайту.

				var stop2 = false;

				function stopoldall() {
					stop2 = true;
					$('#stopoldall').attr('disabled', true); 
					$('#topresult').html('Удаление остановлено');
					$('#loading3').hide();
					$('#deloldall').attr('disabled', false).val('Продолжить удаление'); 
				}

				function checkolditem() {
					startdelolditem(0, 20, 1);
				}

				function findolditem() {
					stop2 = false;
					$('#deloldall').attr('disabled', true); 
					$('#stopoldall').show().attr('disabled', false);
					$('#topresult').html('Удаление запущено');
					startdelolditem(0, 20, 0);
				}

				function startdelolditem(offset, limit, check) {
						$('#loading3').show();

						var form = $('body form').eq(0);
						var date = $('#dataoldday').val();

						$.ajax({
							url: '?step=1&update_time=' + date + '&offset=' + offset + '&limit=' + limit,
							data: form.serialize(),
							dataType: 'html',
							success: function(data, status){
								//console.log('ajax');
								var error = $(data).find('error').text();
								//console.log('2', status);
								//console.log(error);
								if (error != '0') {
									$('#log').html('<b style="color: red">' + error + '</b>');
								}else{
									 var total = parseInt($(data).find('total').text());
									 var cnt = parseInt($(data).find('cnt').text());
									 var items = $(data).find('item');
									 var text = $(data).find('text').text();

									if (offset + cnt < total) {
										var proc = Math.round((offset + cnt) / total * 100);
										if (check) {
											$('#topresult').html('Старых выключенных товаров найдено: ' + total + ' товаров');	
											$('#loading3').hide();
											$('#result').html('');
										}else{
											$('#proc').html('удалено: ' + proc + '% ...');
											$('#result').html(''); 
											items_part_delete(items, 0, offset, cnt, limit);
										}
									}
									else {
										if (check) {
											$('#topresult').html('Старых выключенных товаров найдено: ' + total + ' товаров');	
											$('#loading3').hide();
											$('#result').html('');
										}else{
											$('#proc').html('<IMG SRC="/images/ok.gif" BORDER="0">  Удалено товаров: ' + total + '  ' + text + '');
											$('#result').html(''); 
											items_part_delete(items, 0, offset, cnt, limit);
										}
										$('#loading3').hide();
									}
								}						
							},
							error: function(data, status, e){
								//alert(e);

							}
						});
				}

				function items_part_delete(items, i, offset, cnt, limit) {
					//console.log(i, items.length);
					if (stop2) 	{
						return;
					}
					if (i >= items.length)	{
						startdelolditem(offset + cnt, limit, 0);
					}else{
						//console.log(i, items[i]);
						$('#result').append('<br/>' + (offset + i) + ' ' + $(items[i]).attr('id') + ' ');

						$.ajax({
							url: '?step=2&id=' + $(items[i]).attr('id'),
							dataType: 'html',
							success: function(data, status){
								//console.log('ajax');
								var error = $(data).find('error').text();
								//console.log('2', status);
								console.log(error);
								if (error != '0') {
									$('#result').html('<b style="color: red">' + error + '</b>');
								}else{
									var text = $(data).find('text').html();
									var virtcnt = parseInt($(data).find('cnt').text());
									//$('#proc').append(', ' + (offset + i) + ' ');
									$('#result').append(' (' + virtcnt + ') ' + text);
									i++;
									items_part_delete(items, i, offset, cnt, limit);
								}						
							},
							error: function(data, status, e){
								//alert(e);

							}
						});

					}
				}




			</SCRIPT>
END;
		if (($parentElementId == 0) or $hierarchy->isExists($parentElementId)) {
			$page = $hierarchy->getElement($parentElementId);
			$level++;
			$childs = $hierarchy->getChilds($parentElementId, true	, true, 1, false, $domain_id);  // 5 - только категории
			//$s.=' '.$parentElementId.' _ ';
			foreach($childs as $child_id => $nl) {  //;
				//echo $child_id."<br>";
				$child = umiHierarchy::getInstance()->getElement($child_id);
				//echo $child_id."<br>";
				$objectTypeId = $child->getObject()->getTypeId();
				$objectType = umiObjectTypesCollection::getInstance()->getType($objectTypeId);
				$hierarchyTypeId = $objectType->getHierarchyTypeId();
				if($hierarchyTypeId) {
					$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
					$subMethod = $hierarchyType->getExt();
				}
				if($subMethod != 'object' and $subMethod != 'category') continue;

				$s.='<BR>';

				for ($i=1; $i<=$level; $i++) $s.='&nbsp;&nbsp;&nbsp;&nbsp;';
				/*if ($level>1) // уголок
					$s.='<div style="width: 16px; height: 16px; background-image: url(/images/icons.png); background-position:16px 48px;; display:inline-block"></div>';*/
				if (in_array($child_id, $parent)) 
					$s.='<div style="width: 16px; height: 16px; background-image: url(/images/icons.png); background-position:48px 16px; display:inline-block"></div>';
				else
					$s.='<div style="width: 16px; height: 16px; background-image: url(/images/icons.png); background-position:32px 32px; display:inline-block"></div>';
				if ($subMethod == 'object') {
					$s.='<div style="width: 16px; height: 16px; background-image: url(/images/cms/admin/mac/tree/ico_catalog_object.png); display:inline-block"></div>';
				}else{
					$s.='<div style="width: 16px; height: 16px; background-image: url(/images/icons.png); display:inline-block"></div>';
				}
				//$s.=$child_id;

				$cnt1 = $hierarchy->getChildsCount($child_id, true, true, 1);
				if ($cnt1 or $subMethod == 'category') {
					$s.='<A name="'.$child_id.'"></A><A HREF="?id='.$child_id.'&cs='.time().'#'.$child_id.'" class="'.$subMethod.'" style="text-decoration:none;">';
					$s.=' <FONT COLOR="#6699FF">'.$child->getName();
					// для админов
					//if ($users_inst->isSv($user_id)) $s.= ' ('.$cnt1.'::'.$cnt2.')';
					$s.= ' ('.$cnt1.')';
					$s.='</FONT>';
					$s.='</A>';
					
				}
				else {
					$cl = ''; $ca = 'disabled'; 
					if ($users_inst->isSv($user_id)) $cl = 'editable';
					if ($child->getIsActive()) $ca = 'active';

					//$price=$child->getValue("price");
					$price = empty($price) ? '' : ' (<span class="'.$cl.'" id="'.$child_id.'">'.$price.'</span> )';

					//$pubTime = $child->getValue("publish_time");
					$pubTime = $child->getValue($this->global_publish_date_fieldname);
					$publish_time = ($pubTime instanceof umiDate) ? $pubTime->getFormattedDate('U') : time() ;
					$pt = ($publish_time > time() - 86400*365) ? '' : 'old';

					$photo=$child->getValue("photo");
					$photo1=$child->getValue("photo1");
					$photo2=$child->getValue("header_pic");
					$class = (empty($photo) and empty($photo1) and empty($photo2)) ? 'poz_gray poz_gray_nofoto' : 'poz_gray';

					$s.=' <FONT class="'.$class.' '.$ca.' '.$pt.'" id="'.$child_id.'" ondblClick="test('.$child_id.');" rel="'.$child->getObject()->getId().'">'.$child->getName().$price.'</FONT>';
					$s.= ($publish_time > time() - 86400*365) ? ' <span class="publish_time" style="" rel="'.$publish_time.'" name="'.time().'">'.date('Y-m-d', $publish_time).'</span>' : ' <span class="publish_time old" style="" rel="'.$publish_time.'" name="'.time().'">'.date('Y-m-d', $publish_time).'</span>';

					$objectId = $child->getObject()->getId();
					//$cnt_virtual = $hierarchy->checkIsVirtual(array($child_id=>false));
					$virtuals = $hierarchy->getObjectInstances($objectId, true, true);
					$i = 0;
					foreach($virtuals as $virtualElementId) {
						$element = $hierarchy->getElement($virtualElementId);
						if($element instanceof umiHierarchyElement) {
							$ca = ($element->getIsActive()) ? 'active' : 'disabled';
							$s.= ' <a href="'.$hierarchy->getPathById($virtualElementId).'" target="_blank" class="v'.$ca.'" style="font-size: 10px;">v'.(++$i).'</a>';
						}
					}
					
					//$file_src = $this->del_element($child_id);


					$s.= '<span id="'.$child_id.'_ajax" style="padding:0 5px" class="ajax_data"></span>';
					//$s.= ' <A target=_blank href="'.$photo.'">'.$photo.'</A> ';
				}
				$s.= $this->get_excel_link($child_id);


				if (in_array($child_id, $parent)) {
					$s.= $this->excel_page($child_id, $parent, $level);
				}
			}

		}

		// проверка прав пользователя
		if ($users_inst->isSv($user_id)) {
			// для админов
			//$s.= $template_form;
		}

		return  $s;
	}
	


	function show_form(){
		$actElementId =  (getRequest('id')) ?  intval(getRequest('id')) : 0;
		$actField_name = getRequest('field_name');
		$pref = getRequest('pref');
		$name = getRequest('name');
		$suf = getRequest('suf');
		$images_folder = getRequest('images_folder');
		if (empty($images_folder)) $images_folder = DEFAULT_IMAGES_FOLDER;

		$hierarchy = umiHierarchy::getInstance();

		// нужно достать хоть один текущий объект каталога, чтобы получить у него список его полей типа "Картинка" или "Файл"
		(isset($_SERVER['SERVER_NAME']))?$domain_host=$_SERVER['SERVER_NAME']:$domain_host='localhost';
		$cmsController = cmsController::getInstance();
		$domain_id = domainsCollection::getInstance()->getDomainId($domain_host);
		if(!$domain_id) 
			$domain_id = $cmsController->getCurrentDomain()->getId();

		$childs = $hierarchy->getChilds($actElementId, true	, true, 1, false, $domain_id);  // 5 - только категории
		//$s.=' '.$parentElementId.' _ ';
		if(count($childs) < 1) return;
		foreach($childs as $child_id => $nl) {  //;
			$child = umiHierarchy::getInstance()->getElement($child_id);

			$objectTypeId = $child->getObject()->getTypeId();
			$objectType = umiObjectTypesCollection::getInstance()->getType($objectTypeId);
			$hierarchyTypeId = $objectType->getHierarchyTypeId();
			if($hierarchyTypeId) {
				$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
				$subMethod = $hierarchyType->getExt();
			}
			if($subMethod != 'object') continue;
			break;
		}
		//if($subMethod != 'object') return;

		?>
		<div id="autodel" style="display: none">
			<B>Выберите:</B><BR> 
			<FORM METHOD=POST ACTION="" style="margin-bottom: 32px;">
				<span style="position: relative">
				Количество дней для старых товаров: <INPUT type="text" name="images_folder" value="<?php echo $images_folder; ?>" style="width:354px">
					<span id="subfolders" style="font-size: 12px; left: 268px; position: absolute; top: 22px; color: graytext; white-space: nowrap;"><a href="365" onclick="set_folder(this); return false;">365</a><a href="180" onclick="set_folder(this); return false;">180</a><a href="90" onclick="set_folder(this); return false;">90</a></span>
				</span>
				<INPUT TYPE="submit" value="Удалить" onclick="test(); return false;">
				<IMG class="loading" src="/images/loading.gif" width="16" height="16" border="0" alt=""><SPAN class="loading loading_count">0</SPAN>
				<INPUT class="loading" type="button" value="Stop" onclick="need_next = false;">
			</FORM>
			
		</div>

<script type="text/javascript" src="/js/jquery/jquery.js"></script>
<SCRIPT type="text/javascript">

	$('input[name="images_folder"]').keyup(function (){
		//get_subfolder();
	});

	//get_subfolder();
	jQuery(document).ready(function() {
		set_timeexpire();
	})

	function get_subfolder() {
		var f = $('input[name="images_folder"]').val();
		ajaxGetListFolders(f);
	}

	function set_timeexpire() {
		var f = $('input[name="images_folder"]').val();
		$('span.publish_time').each(function(){
			var tz = parseInt(f);
			var tp = parseInt($(this).attr('rel'));
			var tm = parseInt($(this).attr('name'));
			//console.log(tz,tp, tm);
			if (tp < tm - tz*86400) {
				$(this).addClass('old');
				$(this).prev().addClass('old');
			}else{
				$(this).removeClass('old');
				$(this).prev().removeClass('old');
			}
		
		});
	}


function ajaxGetListFolders(folder) {

			$.ajax({
				url: '?getlistfolders=' + folder,
				dataType: 'xml',
				success: function(data, status){
					var error = $(data).find('error').text();
					var total = parseInt(jQuery('total', data).text());
				
					if (error == '0') {
						//console.log(parseInt(jQuery('total', data).text()));
						if (total > 0) 
							$('#subfolders').html('Подпапки: ');

						// папки
						jQuery(data).find('folder').each(function(i) {
							var link = jQuery(this).attr('link');
							var name = jQuery(this).attr('name');

							$('#subfolders').append('<A href="'+link+'" onclick="set_folder(this); return false;">'+name+'</A>&nbsp;');

						});

					}
					else {
						$('#subfolders').html(error);
					}
				},
				error: function(data, status, e){
					alert(e);
				}
			});


	
	return false;
}

function set_folder(obj) {
	$('input[name="images_folder"]').val($(obj).attr('href'));
	//get_subfolder();
	set_timeexpire();
}

</SCRIPT>
<STYLE type="text/css">
	#subfolders a {
		border-bottom: 1px dotted;
		color: graytext;
		margin-right: 23px;
		text-decoration: none;	
	}
</STYLE>
		<?php	
	}

	function tolog($s, $continue=true, $filename = '_del_old_tovars.html') {
		if (!$continue) {
			$oldfile = $filename.'.bak';
			@copy($filename, $oldfile);
		}
		if ($continue)
			$fp = fopen($filename, 'a');
		else
			$fp = fopen($filename, 'w+');
		if ($continue and $filename == '_sync.html')
			fwrite($fp, "<BR>".date('d.m.Y h:i:s').' '.$s);
		else
			fwrite($fp, $s);
		fclose($fp);
	}

}

$v = new import_processor();
$v->run();

?>