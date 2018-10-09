<?php

/* http://umi-nsk.ru/ */

			ini_set('max_execution_time', 0);
			ini_set('implicit_flush', 'On');
	      chdir(dirname(__FILE__) . '/');

        include "./standalone.php";
        $objectTypes = umiObjectTypesCollection::getInstance();
        $ObjectTypeId = $objectTypes->getBaseType("emarket", "order");
        $ObjectTypeIdNonUser = $objectTypes->getBaseType("emarket", "customer");
        $ObjectType = $objectTypes->getType($ObjectTypeId);
        $num_order = $ObjectType->getFieldId("number");
        $order_items = $ObjectType->getFieldId("order_items");
        
        $objects = umiObjectsCollection::getInstance();  

		$t1 = time();

		$cache_file = 'sys-temp/runtime-cache/customer_need.txt';
		if (file_exists($cache_file) or isset($_REQUEST['search'])) $search = false; else $search = true;

		if ($search) {
		
			// Поиск заказов в админке
			$sel_order = new umiSelection;
			$sel_order->addObjectType($ObjectTypeId);
			$sel_order->addPropertyFilterIsNotNull($num_order);
			$result_order = umiSelectionsParser::runSelection($sel_order); //Массив id объектов
			$arr_non_regusers = array();
			
			foreach($result_order as $Id) {
			  $object = $objects->getObject($Id);
			  $non_reg = $object->getValue("customer_id");
			  $arr_non_regusers[] = $non_reg;
			}
			
			// Список незарегистрированных покупателей, которых надо оставить в системе.
			$arr_non_regusers = array_unique($arr_non_regusers);
			set_vars ($cache_file, $arr_non_regusers);
			$res_str = implode(", ", $arr_non_regusers);
		} else {
			//$res_str = '27660,522293,525221';
			//$arr_non_regusers = explode(",", $res_str);

			$arr_non_regusers = get_vars ($cache_file);
			$res_str = implode(", ", $arr_non_regusers);
		}


		echo '<BR>Customers in orders: '.$res_str.'<BR><BR>';
		$customers_need_cnt = count($arr_non_regusers);
              
		$cnt = $customers_need_cnt + 300;
			   
        $sel_guest = new selector('objects');
        $sel_guest->types('object-type')->name('emarket', 'customer');        
        $sel_guest->limit(0, $cnt); 
		$sel_guest->order('id')->desc();
        $result_guest = $sel_guest->result();

		$t2 = time();

		echo "Customers, to remove the remaining amount: " . $sel_guest->length().', '.date('i:s', $t2-$t1).'<BR><BR>';
                
		$i = 1;
		$deleted=0;
        foreach($result_guest as $item){
           $id_customer = $item->id;
			echo ' '.($i++).' '.$id_customer;
			flush();
           if(!in_array($id_customer, $arr_non_regusers)){
				echo ' <i style="color: red">del:</i> '.$id_customer.', ';
				flush();
				$objects->delObject($id_customer);
				//$sql_del = "DELETE FROM `cms3_object_content` WHERE `obj_id` = {$id_customer}";
				//l_mysql_query($sql_del);    
				$deleted++;
           }else {
				echo ' <B style="color: #00cc00">need</B>, ';
		   }
        }
         
		if($deleted <100) {
			unlink($cache_file); 
			echo '<br><br>Шаг 1: Удаление неиспользованных покупателей закончена.'; 
			if (file_exists($cache_file)) echo '<br><b style="color: red">Обязательно удалите файл '.$cache_file.'</b>';
			echo '<br><br>Шаг 2: <a href="2_clear_order_null_cron.php">Удалить неиспользованные заказы.</a>'; 
			exit(); 
		}
	
//exit;
?>

<SCRIPT type="text/javascript">
<!--
	location.reload();
//-->
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