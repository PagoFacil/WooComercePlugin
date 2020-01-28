<?php

require_once('../../../wp-load.php');

if( empty( $_POST['customer_order'] ) || empty( $_POST['status'] ) ){
	die("error");
}

if( $_POST['status'] != 4 ){
	die("error");
}

$order = new WC_Order( $_POST['customer_order'] );

if( $order !== null ){

	$order->payment_complete();
	die("ok");
}else{
	die("error");
}
?>
