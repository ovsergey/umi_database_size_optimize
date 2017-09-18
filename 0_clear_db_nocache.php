<?php

/* http://umi-nsk.ru/ */

		header('Content-Type: text/html; charset=utf-8');

		if (!  (isset($_REQUEST['debug']) or isset($_REQUEST['cron']) or isset($_REQUEST['refresh']))    ) {
			exit();
		}

		set_time_limit(0);
		ini_set('max_execution_time', 0);
		ini_set('implicit_flush', 'On');
	    chdir(dirname(__FILE__) . '/');

        include "./standalone.php";
        $objectTypes = umiObjectTypesCollection::getInstance();
		$permissions = permissionsCollection::getInstance();
		$guest_id = $permissions->getGuestId();
     
        $objects = umiObjectsCollection::getInstance();  

		$t1 = time();
		if (isset($_REQUEST['limit'])) $limit = intval($_GET['limit']); else $limit = 30;
		if (!is_numeric($limit) or $limit <= 0 or $limit > 10000) $limit = 30;
		$deleted = 0;
		if (isset($_REQUEST['debug'])) $debug = intval($_GET['debug']); else $debug = 0;

		$expire = 7200;

		$time_start = microtime(1);

		// Поиск заказов в корзине
		$sel_order = new selector('objects');
		$sel_order->types('object-type')->name('emarket', 'order');   
		$sel_order->where('number')->isNull(true);
		//$sel_order->where('order_date')->less(time()-86400*10);
		$sel_order->where('order_date')->isNull(true);
		$sel_order->order('id')->desc();
		//$sel_order->limit(0, $limit); 
		$sel_order->limit($limit * 10, $limit);  // оставлять заказы, в стадии наполнения в корзине
		$result_orders = $sel_order->result();

		$time_end = microtime(1);
			
		if($sel_order->length > 0) {
		//if($sel_order->length > $limit * 10) { // оставлять заказы, в стадии наполнения в корзине
			echo 'Осталось удалить пустых заказов: <b>'.$sel_order->length.'</b> ';

			$time = $time_end - $time_start;
			echo ",  поиск шёл $time секунд\n";
			
			foreach($sel_order->result as $order) {
					$order_id = $order->id;
					echo '<br/>'.$order_id.' - ';
					if($order instanceof umiObject) {
						//$orderItems = $order->getItems();
						//$orderItems = $order->items;
						$orderItems = $order->getValue('order_items');
						echo ('товаров: 0'.count($orderItems).' ');
						//var_dump($orderItems);
						foreach($orderItems as $id_order_item) {
							echo ' '.$id_order_item;
							flush();
							//$sql_del = "DELETE FROM `cms3_object_content` WHERE `obj_id` = {$id_order_item}";
							//l_mysql_query($sql_del);
							if (!$debug) {
								$objects->delObject($id_order_item);
								echo ' <b style="color: #ff0033">(item deleted)</b> ';
							}
						}
						$customer_id = (int)$order->getValue('customer_id');
						echo (' customer_id: '.$customer_id.' ');
						flush();

						/*echo $guest_id = umiObjectsCollection::getInstance()->getObjectIdByGUID('system-guest');
						echo $guest_id;
						$customer = $objects->getObject($customer_id);
						if ($customer instanceof umiObject) {
							//var_dump($customer);
						}*/

						if (!$debug or $debug == 2) {

							//if (!$debug and !$user_need) $objects->delObject($customer_id);
						}

						if (!$debug) {
							$objects->delObject($order_id);
							echo ' <b style="color: #ff0033">order deleted</b> ';
						}else{
							echo ' <b style="color: #cc0099">debug</b> <b style="color: #ff0033">order deleted</b>';
						}

						$deleted++;
					}
			}
			if ($deleted < $limit) {
				echo '<br/>ok, all cleared';
			}else{
				$t2 = time();
				echo '<br/>ok, next '.($t2 - $t1).' sec';	
			}
		}

		// после очистки заказов и товарных позиций, найдём всех незарегистрированных покупателей, не привязанных ни к одному из заказов и удалим их

		$objects = umiObjectsCollection::getInstance();
		$typeId = $objectTypes->getBaseType('emarket', 'order');
		$customerObjectTypeId = $objectTypes->getBaseType('emarket', 'customer');
		$order_itemObjectTypeId = $objectTypes->getBaseType('emarket', 'order_item');
		$type = umiObjectTypesCollection::getInstance()->getType($typeId);
		$prop_id = $type->getFieldId('customer_id');
		$field = umiFieldsCollection::getInstance()->getField($prop_id);
		if($field instanceof umiField) {
			$field_id = $field->getId();

			if (!$debug) {

			$sql = "DELETE  
	FROM
	  cms3_objects 
	WHERE (
		NOT EXISTS 
		(SELECT 
		  t2.obj_id 
		FROM
		  cms3_object_content AS t2 
		WHERE t2.rel_val = cms3_objects.id 
		  AND t2.field_id = {$field_id}) 
		AND cms3_objects.type_id = {$customerObjectTypeId}
	  ) 
	ORDER BY cms3_objects.id
	LIMIT {$limit};";  // незарегистрированные пользователи
			}else{
			$sql = "SELECT  *  
	FROM
	  cms3_objects 
	WHERE (
		NOT EXISTS 
		(SELECT 
		  t2.obj_id 
		FROM
		  cms3_object_content AS t2 
		WHERE t2.rel_val = cms3_objects.id 
		  AND t2.field_id = 60) 
		AND cms3_objects.type_id = 79
	  ) 
	ORDER BY cms3_objects.id
	LIMIT {$limit};";  // незарегистрированные пользователи

			$sql = "SELECT  *  
	FROM
	  cms3_objects 
	WHERE (
		NOT EXISTS 
		(SELECT 
		  t2.obj_id 
		FROM
		  cms3_object_content AS t2 
		WHERE t2.rel_val = cms3_objects.id 
		  AND t2.field_id = {$field_id}) 
		AND cms3_objects.type_id = {$customerObjectTypeId}
	  ) 
	ORDER BY cms3_objects.id
	LIMIT {$limit};";  // незарегистрированные пользователи

		echo '<br/><br/>'.$sql;

			}

			$time_start = microtime(1);

			$res = l_mysql_query($sql);

			$time_end = microtime(1);
				
			$time = $time_end - $time_start;
			echo "<br><br>удаление незарегистрированных покупателей не привязанных к заказам шло $time секунд<br>";

			$deleted_customer = mysql_affected_rows();

			if (!$debug) 
				printf ("Records deleted: %d\n", $deleted_customer);
			else {
				echo "field_id = {$field_id}<br>";
				printf ("Найдено для удаления: %d\n", $deleted_customer);
			}

			$deleted = $deleted + $deleted_customer;

		}
		// конец очистки незарегистрированных покупателей


		// можно ещё найти позиции заказа непривязанные к заказу (потерявшиеся)

		$prop_id_items = $type->getFieldId('order_items');
		$field = umiFieldsCollection::getInstance()->getField($prop_id_items);
		if($field instanceof umiField) {
			$field_id = $field->getId();

			if (!$debug) {

			$sql = "DELETE  
	FROM
	  cms3_objects 
	WHERE (
		NOT EXISTS 
		(SELECT 
		  t2.obj_id 
		FROM
		  cms3_object_content AS t2 
		WHERE t2.rel_val = cms3_objects.id 
		  AND t2.field_id = {$field_id}) 
		AND cms3_objects.type_id = {$order_itemObjectTypeId}
	  ) 
	ORDER BY cms3_objects.id
	LIMIT {$limit};";  // позиции заказа непривязанные к заказу
			}else{

			$sql = "SELECT  *  
	FROM
	  cms3_objects 
	WHERE (
		NOT EXISTS 
		(SELECT 
		  t2.obj_id 
		FROM
		  cms3_object_content AS t2 
		WHERE t2.rel_val = cms3_objects.id 
		  AND t2.field_id = {$field_id}) 
		AND cms3_objects.type_id = {$order_itemObjectTypeId}
	  ) 
	ORDER BY cms3_objects.id
	LIMIT {$limit};";  // позиции заказа непривязанные к заказу

		echo '<br/><br/>'.$sql;

			}

			$time_start = microtime(1);

			$res = l_mysql_query($sql);

			$time_end = microtime(1);
				
			$time = $time_end - $time_start;
			echo "<br><br>удаление позиций заказа непривязанных к заказу шло $time секунд<br>";

			$deleted_items = mysql_affected_rows();

			if (!$debug) 
				printf ("Items deleted: %d\n", $deleted_items);
			else {
				echo "<br>field_id = {$field_id}<br>";
				echo " order_itemObjectTypeId = {$order_itemObjectTypeId}<br>";
				printf ("Найдено для удаления: %d\n", $deleted_items);
			}

			$deleted = $deleted + $deleted_items;

		}
		// конец найти позиции заказа непривязанные к заказу (потерявшиеся)

       
		if($deleted < $limit) {

			if (!  (isset($_REQUEST['debug']) or isset($_REQUEST['cron']))    ) {
				echo '<br><br>Далее идёт оптимизация (сжатие) базы. Наберитесь терпения. Взависимости от размера базы и мощности хостинга операция может продолжаться довольно долго.'; 
				flush();
				//$sql_optimize = "OPTIMIZE TABLE `cms3_object_content`, `cms3_objects`";
				//l_mysql_query($sql_optimize);  
				//echo '<br><br>Оптимизация базы (сжатие) закончена успешно.'; 
			}

			exit(); 
		}
	
		if (isset($_REQUEST['debug']) or isset($_REQUEST['cron'])) exit;


if (isset($_REQUEST['refresh'])) {

?>

<SCRIPT type="text/javascript">
	//location.reload();
	window.location.href = '?refresh=1&limit=<?php echo $limit.'&cs='.$time_end; ?>';
</SCRIPT>

<?php

exit();

}

?>

<SCRIPT type="text/javascript">
	//location.reload();
	window.location.reload(true)
</SCRIPT>

<?php

//############################### ####################################

function set_vars ($file, $data){
	$filename = '/'.$file;
	$filename2 = dirname (__FILE__).$filename;

    $fp = fopen($filename2, 'wb+');
    fwrite($fp, serialize($data) );
    fclose($fp);

	@chmod($filename, 0666);

	return $filename2;
}

function get_vars ($file){
	$filename = '/'.$file;
	$filename2 = dirname (__FILE__).$filename;

    if (!@filesize($filename2)){
    	return false;
    }

	return unserialize(file_get_contents($filename2));

}

?>