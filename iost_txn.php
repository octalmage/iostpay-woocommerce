<?php 
	
	if( isset( $_POST['txid'] ) ){
		
         $data = $_POST	;
		 $transactionId = 		$data['txid'] ; 
			
		$ch2 = curl_init();
            $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
         );
		
			curl_setopt($ch2, CURLOPT_URL, 'http://api.iost.io/getTxByHash/'.$transactionId);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch2, CURLOPT_HEADER, 0);
            $body = '{}';
			curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET"); 
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        
            $response = curl_exec($ch2);
			$resp    =    json_decode( $response, true ) ;
	
			if( isset( $resp['transaction']['tx_receipt']['status_code'] ) ){ 
                $status_code  =  $resp['transaction']['tx_receipt']['status_code'] ;
                $message      =  $resp['transaction']['tx_receipt']['message'] ;
            }
					
			if( $status_code == 'RUNTIME_ERROR'){
                $message = 'Balance not enough.' ;
                $status_code = 'failed' ;
			}
            elseif( $status_code == 'SUCCESS' ){
				$status_code   = 'processing' ;
				$message   	   = 'Payment completed' ;
			}
			elseif( $status_code == 'failed' ){
				$status_code   = 'failed' ;
			}else{
				$status_code   = 'pending' ;
			}
		
				$array_resp = 
						array(
							'message' => isset( $message ) ? $message : '' ,
							'status_code' => isset( $status_code ) ? $status_code : '' ,
							'iostpay_txnid' => isset( $transactionId ) ? $transactionId : '' ,
						   )        ;
						   
				echo json_encode( $array_resp ) ;
				
			}else{
				
				 $array_resp = 
						array(
								'message' => 'Payment is failed' ,
								'status_code' => 'failed' ,
						  )        ;
							   
				echo json_encode( $array_resp ) ;
			}
	