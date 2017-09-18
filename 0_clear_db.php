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

		// Список всех пользователей , чтобы не удалить их
		if ($search2) {
			$sel_user = new selector('objects');
			$sel_user->types('object-type')->name('users', 'user');
			//$sel_user->limit(0, 10000);
			$result_users = $sel_user->result();
			$users_arr = array();
			foreach($result_users as $user2) {
				$users_arr[] = $user2->id;
			}
			$users_arr = array_unique($users_arr);

			//set_vars ($cache_file2, $result_users);
			set_vars ($cache_file2, $users_arr);
		}else{
			//$result_users = get_vars ($cache_file2);
			$users_arr = get_vars ($cache_file2);
		}
		if ($debug == 2) echo ' users_cnt:'.count($users_arr).' ';

		// Поиск заказов в админке, чтобы не удалить покупателей, которые их оформили
		if ($search) {
			$sel_order_need = new selector('objects');
			$sel_order_need->types('object-type')->name('emarket', 'order');   
			$sel_order_need->where('number')->isNull(false);
			$result_order_need = $sel_order_need->result();
			$customers_arr = array();
			foreach($result_order_need as $order2) {
				$customers_arr[] = (int)$order2->getValue('customer_id');
			}
			$customers_arr = array_unique($customers_arr);

			//set_vars ($cache_file, $result_order_need);
			set_vars ($cache_file, $customers_arr);
		}else{
			//$result_order_need = get_vars ($cache_file);
			$customers_arr = get_vars ($cache_file);
		}
		if ($debug == 2) echo ' customers in orders:'.count($customers_arr).' ';

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
							if (!$debug) $objects->delObject($id_order_item);
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
							// проверить не нужен ли этот пользователь
							$user_need = false;
							$s='';
							/*foreach($result_users as $user2) {
								if ($customer_id == $user2->id) {$user_need = true; $s='/<b style="color:red">нужен</b> ';}
								if ($debug == 2) echo ' ='.$user2->id.' ';
							}*/
							if (in_array($customer_id, $users_arr)) {
								$user_need = true; $s='/<b style="color:red">нужен</b> ';
							}
							/*foreach($result_order_need as $order2) {
								if ($debug == 2) echo ' order:'.$order2->id.' ';
								$customer2_id = $order2->getValue('customer_id');
								if ($customer_id == $customer2_id) {$user_need = true; $s='/<b style="color:red">нужен</b> ';}
								if ($debug == 2) echo ' ='.$customer2_id.' ';
							}*/
							if (in_array($customer_id, $customers_arr)) {
								$user_need = true; $s='/<b style="color:red">нужен</b> ';
							}
							echo $s;
							if (!$debug and !$user_need) $objects->delObject($customer_id);
						}

						if (!$debug) $objects->delObject($order_id);

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
       
		if($deleted < $limit) {

			unlink($cache_file); 
			if (file_exists($cache_file)) echo '<br><b style="color: red">Обязательно удалите файл '.$cache_file.'</b>';

			unlink($cache_file2); 
			if (file_exists($cache_file2)) echo '<br><b style="color: red">Обязательно удалите файл '.$cache_file2.'</b>';

			if (!  (isset($_REQUEST['debug']) or isset($_REQUEST['cron']))    ) {
				echo '<br><br>Далее идёт оптимизация (сжатие) базы. Наберитесь терпения. Взависимости от размера базы и мощности хостинга операция может продолжаться довольно долго.'; 
				flush();
				$sql_optimize = "OPTIMIZE TABLE `cms3_object_content`, `cms3_objects`";
				//l_mysql_query($sql_optimize);  
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

?>