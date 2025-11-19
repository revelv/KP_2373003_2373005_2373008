  <?php

  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://api-sandbox.collaborator.komerce.id/order/api/v1/orders/store',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS =>'{
      "order_date": "2025-11-19 16:30:00",                        
      "brand_name": "Komship",                                    
      "shipper_name": "Toko Official Komship",                    
      "shipper_phone": "6281234567689",                           
      "shipper_destination_id": 31597,                            
      "shipper_address": "order address detail",                  
      "shipper_email": "test@gmail.com",                          
      "receiver_name": "Buyer A",                                 
      "receiver_phone": "6281209876543",                          
      "receiver_destination_id": 39947,                           
      "receiver_address": "order destination address detail",     
      "shipping": "SAP",                                          
      "shipping_type": "SAPFlat",                                      
      "payment_method": " BANKTRANSFER",                                    
      "shipping_cost": 18600,                                     
      "shipping_cashback": 0,
      "service_fee": 1921,
      "additional_cost": 0,
      "grand_total": 68600,
      "cod_value": 68600,
      "insurance_value": 0,
      "order_details": [
          {
              "product_name": "Komship package",
              "product_variant_name": "Komship variant product",
              "product_price": 50000,
              "product_width": 1,
              "product_height": 2,
              "product_weight": 2000,
              "product_length": 20,
              "qty": 1,
              "subtotal": 50000
          }
      ]
  }',
    CURLOPT_HTTPHEADER => array(
      'x-api-key: 3I7kuf7B3e00fb2d23c692a69owo8BSW'
    ),
  ));

  $response = curl_exec($curl);

  curl_close($curl);
  echo $response;
