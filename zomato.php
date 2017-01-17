<?php

$curl = curl_init();
//echo date('Y-m-d', strtotime(' -1 day'));
$date = date('Y-m-d', strtotime(' -1 day'));
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.zba.se/v2/merchant/56/orders?filter=pos_date%3D".$date."&expand=customer_detail",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "accept: application/json",
    "authorization: bearer 5q2HtW0b_qxzq0p4E9gCD1U_j0YmtA3M0zKYOrZlR9k._Bangy5qwjsaYLbokMxmXs7epcX1XE8rPuyBEj8FCUo",
    "cache-control: no-cache",
    "postman-token: 7516f728-a560-5c87-aeed-80d0602209c1"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
 $response = json_decode($response);
 $total  = $response->paging->total;
 
 if($total > 0){
  
  foreach ($response->data as $value) {
    # code...
    $invoce_num = $value->invoice_number;
    $amount  = $value->net_bill;
    $delivery_type = $value->delivery_type;
    $bill_settle_time = $value->bill_settled_time;
    $pos_date = $value->pos_date;

    $cus_phone = $value->customer_detail->phone;
    $cus_name = $value->customer_detail->first_name." ".$value->customer_detail->last_name;
    $cus_dob = $value->customer_detail->dob;
    $cus_anniversary = $value->customer_detail->anniversary;
    $cus_email = $value->customer_detail->email;
    $cus_gender = $value->customer_detail->gender;
    $epoch = $bill_settle_time;
    $dt = new DateTime("@$epoch");  // convert UNIX timestamp to PHP DateTime
    $closetime =  $dt->format('Y-m-d H:i:s'); // output = 2017-01-01 00:00:00
    

    //$cus_phone = "7309273874";
    

    if(strlen($cus_phone) == 11) $cus_phone = substr_replace($cus_phone, '', 0, 1);
    // $details = array('name'=>$cus_name,'phone'=>$cus_phone,'amount'=>$amount,'bill'=>$invoce_num,'closetime'=>$closetime);
    //print_r($details);
    $channel = 'Outlet';
    $shortname = 'Checkin';
    
    //$business['xeno_business_key']  = '178740c460a05cbc3568969bf0e9e7c0';
    
    // $post_url = 'https://external.xeno.in/updatebill.get?name='.urlencode($cus_name).'&phone='.$cus_phone.'&amount='.$amount.'&bill='.$invoce_num.'&api_key='.$business['xeno_business_key'].'&closetime='.urlencode($closetime).'&channel='.strtolower($channel).'&shortname='.strtolower($shortname);

    //   echo $post_url;
    //   echo "\n\n\n\n";
    //   $c = curl_init();
    //       curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    //       curl_setopt($c, CURLOPT_URL,$post_url);
    //       $contents = curl_exec($c);
    //       print_r($contents);
    //       curl_close($c);
      
  //break;
  }//foreach

 }

 

}