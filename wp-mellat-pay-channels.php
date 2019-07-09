<?php
/*
Plugin Name: wp-mellat-pay-channels
Plugin URI:
Description: wordpress mellat payment channel
Version: 1.0.0
Author: Saeed Torabi
Author URI: https://saeedtrb.com
License: MIT
*/

/**
 * @param ../wp-pay-channels/wp-pay-channels.php PayChannelTransaction $transaction
 * @return ../wp-pay-channels/wp-pay-channels.php PayChannelTransaction $transaction
 */
function filter_mellat_pay_channel_pay_request($transaction){
    $terminalId		= "xxxxx";							//-- شناسه ترمینال
    $userName		= "xxxxx"; 							//-- نام کاربری
    $userPassword	= "xxxxx"; 							//-- کلمه عبور
    $transactionData = [];

    $parameters = array(
        'terminalId' 		=> $terminalId,
        'userName' 			=> $userName,
        'userPassword' 		=> $userPassword,
        'orderId' 			=> $transaction->getId(),
        'amount' 			=> $transaction->getAmount(),
        'localDate' 		=> $transaction->getInvoiceDate()->format('Ymd'),
        'localTime' 		=> $transaction->getInvoiceDate()->format('Gis'),
        'additionalData' 	=> "",
        'callBackUrl' 		=> $transaction->getCallbackUrl(),
        'payerId' 			=> 0
    );

    $client 	= new nusoap_client('https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl');
    $namespace 	='http://interfaces.core.sw.bps.com/';
    $result 	= $client->call('bpPayRequest', $parameters, $namespace);

    if (!$client->fault)
    {
        if (!$client->getError())
        {
            $res 		= explode (',',$result);
            $ResCode 	= $res[0];

            if ($ResCode == "0")
            {
                $transactionData['RefId'] = $res[1];
            }
        }
    }
    #TODO: handling mellat pay request error
    #(Store params in transaction for PayForm);
    $transaction->setData($transactionData);

    return $transaction;
}
/**
 * @param ../wp-pay-channels/wp-pay-channels.php PayChannelTransaction $transaction
 * @param ../wp-pay-channels/wp-pay-channels.php PayChannelPayForm $payForm
 * @return ../wp-pay-channels/wp-pay-channels.php PayChannelPayForm $payForm
 */
function filter_mellat_pay_channel_pay_form($payForm, $transaction){

    $payForm->setAction('https://bpm.shaparak.ir/pgwchannel/startpay.mellat');
    $payForm->setMethod('post');
    $payForm->setBody($transaction->getData());

    return $payForm;
}
function filter_mellat_pay_channel_pay_answer($transaction){
    #TODO: validation mellat pay answer
    $terminalId		= "xxxxx"; //-- شناسه ترمینال
    $userName		= "xxxxx"; //-- نام کاربری
    $userPassword	= "xxxxx"; //-- کلمه عبور

    $ResCode 		= (isset($_POST['ResCode']) && $_POST['ResCode'] != "") ? $_POST['ResCode'] : "";

    if ($ResCode == '0')
    {
        $client 				= new nusoap_client('https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl');
        $namespace 				='http://interfaces.core.sw.bps.com/';
        $orderId 				= (isset($_POST['SaleOrderId']) && $_POST['SaleOrderId'] != "") ? $_POST['SaleOrderId'] : "";
        $verifySaleOrderId 		= (isset($_POST['SaleOrderId']) && $_POST['SaleOrderId'] != "") ? $_POST['SaleOrderId'] : "";
        $verifySaleReferenceId 	= (isset($_POST['SaleReferenceId']) && $_POST['SaleReferenceId'] != "") ? $_POST['SaleReferenceId'] : "";

        $parameters = array(
            'terminalId' 		=> $terminalId,
            'userName' 			=> $userName,
            'userPassword' 		=> $userPassword,
            'orderId' 			=> $orderId,
            'saleOrderId' 		=> $verifySaleOrderId,
            'saleReferenceId' 	=> $verifySaleReferenceId
        );

        $result = $client->call('bpVerifyRequest', $parameters, $namespace);

        if($result == 0)
        {
            $result = $client->call('bpSettleRequest', $parameters, $namespace);

            if($result == 0)
            {
                $transaction->setStatus(PayChannelTransaction::STATUS_PAID);
            }
        }
    }
    #TODO: handling mellat pay answer error
    return $transaction;
}

function filter_mellat_pay_channel_pay_refunds($transaction){
    #TODO: impalement mellat refunds transaction
    return $transaction;
}

function filter_pay_channels_mellat_options($channels){
    $channels[] = [
        'name' => 'mellat',
        'label' => 'ملت',
        'image' => 'http://s.ir/mellat.jpg'
    ];
    return $channels;
}



function init_mellat_pay_channel(){

    add_filter('wp_pay_channels_channels_options','filter_pay_channels_mellat_options');
    add_filter('mellat_pay_channel_pay_request','filter_mellat_pay_channel_pay_request');
    add_filter('mellat_pay_channel_pay_form','filter_mellat_pay_channel_pay_form');
    add_filter('mellat_pay_channel_pay_answer','filter_mellat_pay_channel_pay_answer');
    add_filter('mellat_pay_channel_pay_refunds','filter_mellat_pay_channel_pay_refunds');

}
add_action('init','init_mellat_pay_channel');
?>