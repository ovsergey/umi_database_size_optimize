<?php

/* http://umi-nsk.ru/ */

		header('Content-Type: text/html; charset=utf-8');

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
		if (isset($_REQUEST['optimize'])) $optimize = intval($_GET['optimize']); else $optimize = false;

		$expire = 7200;

		$cache_file = 'sys-temp/runtime-cache/orders_need.txt';
		if (file_exists($cache_file) or isset($_REQUEST['search'])) $search = false; else $search = true;

		$path = CURRENT_WORKING_DIR . '/' . $cache_file;
		if(is_file($path)) {
			$mtime = filemtime(CURRENT_WORKING_DIR . '/' . $cache_file);
			//echo $mtime;

			if(time() > ($mtime + $expire)) {		// кэш просрочен
				$search = true;
			}
		}

		$cache_file2 = 'sys-temp/runtime-cache/customer_need.txt';
		if (file_exists($cache_file2) or isset($_REQUEST['search'])) $search2 = false; else $search2 = true;

		$path = CURRENT_WORKING_DIR . '/' . $cache_file2;
		if(is_file($path)) {
			$mtime = filemtime(CURRENT_WORKING_DIR . '/' . $cache_file2);
			//echo $mtime;

			if(time() > ($mtime + $expire)) {		// кэш просрочен
				$search2 = true;
			}
		}

		$time_start = microtime(1);

		if($debug) echo '<br><b style="color: red">Режим отладки!!!</b><br>';

		$orderTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('emarket-order');
		$expiration = umiObjectsExpiration::getInstance();
		//$orders = $expiration->getExpiredObjectsByTypeId($orderTypeId, $limit);
		$orders = getExpiredObjectsByTypeId($orderTypeId, $limit);
		if (sizeof($orders) > 0) {
			echo '<br>Найдено через getExpired просроченных заказов: '.sizeof($orders);

			if (sizeof($orders) > 0) {
				$objects = umiObjectsCollection::getInstance();
				foreach($orders as $orderId) {
					$order = $objects->getObject($orderId);
					echo '<br><b>id заказа: <a href="/admin/emarket/order_edit/'.$orderId. '/" target="_blank">'.$orderId. '</a></b>';
					if (is_null($order->status_id)) {
						echo ' - статус пустой';
						//$order = order::get($orderId);
						/*$items = $order->getItems();
						foreach($items as $item) {
							$orderItem = orderItem::get($item->getId());
							$orderItem->remove();
						}*/
						$order_obj = $objects->getObject($orderId);
						if($order_obj instanceof umiObject) {
							$orderItems = $order_obj->getValue('order_items');
							echo (', товаров: '.count($orderItems).' ');
							foreach($orderItems as $id_order_item) {
								echo ', order_item: '.$id_order_item;
								flush();
								//$sql_del = "DELETE FROM `cms3_object_content` WHERE `obj_id` = {$id_order_item}";
								//l_mysql_query($sql_del);
								if (!$debug) {$objects->delObject($id_order_item); echo ' - удалён';}
							}

						}

						//$customerId = $order->customer_id;
						$customerId = (int)$order_obj->getValue('customer_id');
						//if (!$expiration->isExpirationExists($customerId)) {
						if (!isExpirationExists($customerId)) {
							echo ' покупатель: ' . $customerId. ' - add to expiration';
							//if (!$debug) $expiration->add($customerId, 1);
							if (!$debug) add($customerId, 1);
						}
						if (!$debug) { $order->delete(); echo ' <b style="color: red">заказ удалён</b>'; }
					}
				}
			}


		}else{
			echo '<br>Не найдены через getExpired просроченные заказы';
		}

		echo '<br>';	

		$customerTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('emarket-customer');
		//$customers = $expiration->getExpiredObjectsByTypeId($customerTypeId, $limit);
		$customers = getExpiredObjectsByTypeId($customerTypeId, $limit);
		if (sizeof($customers) > 0) {
			echo '<br>Найдено через getExpired просроченных покупателей: '.sizeof($customers);

			if (sizeof($customers) > 0) {
				$objects = umiObjectsCollection::getInstance();
				foreach($customers as $customerId) {
					echo '<br><b>id покупателя: <a href="/uobject/'.$customerId. '" target="_blank">'.$customerId. '</a></b>';
					$selector = new selector('objects');
					$selector->types('object-type')->name('emarket', 'order');
					$selector->where('customer_id')->equals(array($customerId));
					//$selector->option('no-length')->value(true);
					//$customer = new customer($objects->getObject($customerId));
					$customer_obj = $objects->getObject($customerId);

					if ($selector->first) {
						//$customer->freeze();
						foreach($selector->result as $order) {
							if (is_null($order->status_id)) {
								echo ', найден пустой заказ: <a href="/admin/emarket/order_edit/'.$order->getId(). '/" target="_blank">'.$order->getId(). '</a>';

								//if (!$expiration->isExpirationExists($order->getId())) {

								$orderId = $order->getId();

								$sql = "SELECT `obj_id`, `entrytime`, `expire` FROM `cms3_objects_expiration` WHERE  obj_id = {$orderId};";
								$res = mysql_query($sql);
								if ($res) {
									if (mysql_num_rows($res) < 1) {
										echo ' - add to expiration';
										//if (!$debug) $expiration->add($order->getId(), 1);
										if (!$debug) add($order->getId(), 1);
									}
								}

							}
						}
					} else {
						//$deliveryAddresses = $customer->delivery_addresses;
						$deliveryAddresses = $customer_obj->getValue('delivery_addresses');
						if (!is_null($deliveryAddresses) && is_array($deliveryAddresses) && sizeof($deliveryAddresses) > 0) {
							foreach($deliveryAddresses as $addressId) {
								echo ', удалён адрес: '.$addressId;
								if (!$debug) $objects->delObject($addressId);
							}
						}
						//$customer->delete();
						echo ', удалён покупатель: '.$customerId;
						if (!$debug) { $objects->delObject($customerId); echo ' <b style="color: red">покупатель удалён</b>'; }
					}
					$deleted++;
				}
			}


		}else{
			echo '<br>Не найдены через getExpired покупатели';
		}

		if ($debug) {
			$now = time();

			echo '<br>';	

			$sql = "SELECT `obj_id`, `entrytime`, `expire`, guid, type_id FROM `cms3_objects_expiration` INNER JOIN `cms3_objects` ON obj_id = id WHERE  type_id = {$orderTypeId} AND (`entrytime`+`expire`) < {$now} LIMIT 0, {$limit};";
			if($debug) echo '<br><br>'. $sql;

			$res = mysql_query($sql);
			if ($res) {
				$objects = umiObjectsCollection::getInstance();
				while ($row = mysql_fetch_assoc($res)) {
					echo '<br>'.$row['obj_id'];
					echo ', дата: ' . date('d.m.Y H:i', $row['entrytime']);
					echo ', время хранения: '.( $row['expire'] / 86400). ' дней';
					$object_id  = $row['obj_id'];
					$object = $objects->getObject($object_id);
					if($object instanceof iUmiObject) {
						$type_id = $object->getTypeId();
						echo ', тип данных: '.$type_id;
					}
				}
			}

			echo '<br>';	

			$sql = "SELECT `obj_id`, `entrytime`, `expire`, guid, type_id FROM `cms3_objects_expiration` INNER JOIN `cms3_objects` ON obj_id = id WHERE  type_id = {$customerTypeId} AND (`entrytime`+`expire`) < {$now} LIMIT 0, {$limit};";
			if($debug) echo '<br><br>'. $sql;

			$res = mysql_query($sql);
			if ($res) {
				$objects = umiObjectsCollection::getInstance();
				while ($row = mysql_fetch_assoc($res)) {
					echo '<br>'.$row['obj_id'];
					echo ', дата: ' . date('d.m.Y H:i', $row['entrytime']);
					echo ', время хранения: '.( $row['expire'] / 86400). ' дней';
					$object_id  = $row['obj_id'];
					$object = $objects->getObject($object_id);
					if($object instanceof iUmiObject) {
						$type_id = $object->getTypeId();
						echo ', тип данных: '.$type_id;
					}
				}
			}

		}
	
		echo '<br>';	

		$time_end = microtime(1);

		$time = $time_end - $time_start;
		echo "поиск шёл $time секунд\n";
			
		if ($deleted < $limit) {
			echo '<br/>ok, all cleared';
		}else{
			$t2 = time();
			echo '<br/>ok, next '.($t2 - $t1).' sec';	
		}

		echo '<br>'.$deleted.' из ',$limit;	

		//die('stop');
		usleep(1000000); // 1 sec
       
		if($deleted < $limit) {

			if (!  (isset($_REQUEST['debug']) or isset($_REQUEST['cron']))    ) {
				echo '<br><br>Далее идёт оптимизация (сжатие) базы. Наберитесь терпения. Взависимости от размера базы и мощности хостинга операция может продолжаться довольно долго.'; 
				flush();
				$sql_optimize = "OPTIMIZE TABLE `cms3_object_content`, `cms3_objects`";
				if ($optimize) l_mysql_query($sql_optimize);  
				echo '<br><br>Оптимизация базы (сжатие) закончена успешно.'; 
			}

			exit(); 
		}
	
		if (isset($_REQUEST['debug']) or isset($_REQUEST['cron'])) exit;


if (isset($_REQUEST['refresh'])) {

?>

<SCRIPT type="text/javascript">
	//location.reload();
	window.location.href = '?refresh=1&limit=<?php echo $limit.'&cs='.time(); ?>';
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

// ------------------------------------- 

		function isExpirationExists($objectId) {
			$sql = <<<SQL
			SELECT
				`obj_id`
			FROM
				`cms3_objects_expiration`
			WHERE
				`obj_id` = {$objectId}
			LIMIT 1
SQL;
			$res = l_mysql_query($sql);
			return mysql_num_rows($res) > 0;
		}

		function getExpiredObjectsByTypeId($typeId, $limit = 50) {
			$time = time();

			$sql = <<<SQL
			SELECT
				`obj_id`
			FROM
				`cms3_objects_expiration`
			WHERE
				`obj_id`  IN (
					SELECT
						`id`
					FROM
						`cms3_objects`
					WHERE
						`type_id`='{$typeId}'
					)
				AND (`entrytime` +  `expire`) <= {$time}
			ORDER BY (`entrytime` +  `expire`)
			LIMIT {$limit}
SQL;

			$result = array();
			$res = l_mysql_query($sql);
			if (mysql_numrows($res) > 0) {
				while($row = mysql_fetch_assoc($res)) {
					$result[] = $row['obj_id'];
				}
			}

			return $result;
		}

		function add($objectId, $expires = false) {
			if($expires == false) {
				$expires = $this->defaultExpires;
			}
			$objectId = (int) $objectId;
			$expires = (int) $expires;
			$time = time();

			$sql = <<<SQL
INSERT INTO `cms3_objects_expiration`
	(`obj_id`, `entrytime`, `expire`)
		VALUES ('{$objectId}', '{$time}', '{$expires}')
SQL;
			l_mysql_query($sql);
		}

?>