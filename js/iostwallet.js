var transfer;
document.addEventListener("DOMContentLoaded", async function() {
    await new Promise(done => setTimeout(() => done(), 500));

    if('IWalletJS' in window){

    } else {

    }    
        
        
    if (typeof IWalletJS === 'undefined' ) {


        swal({
            title: "Iwallet",
            text: "iwallet extension not found!",
            type: "error"
        }).then((willDelete) => {
			
            jQuery('#makepayment').attr("disabled", true);
			location.reload(true);
        });



        return false;
    } 

    if ( IWalletJS.account.name == null ) {

        swal({
            title: "Iwallet",
            text: "Please Unlock iwallet Extension!",
            type: "error"
        }).then((willDelete) => {

			
				location.reload(true);

        });

        return false;
    }


    IWalletJS.enable().then(function(account) {
        if (!account) return;

        const iost = IWalletJS.newIOST(IOST);
        var paybyiost_form = jQuery('form#paybyiost');
	   
		    //var cart_total="1";
			const tx = iost.callABI("token.iost", "transfer", ["iost", account, iostpay_account_id, cart_total, "OrderID: "+order_id]);
			
            tx.addApprove("iost", cart_total);


            iost.signAndSend(tx)
                .on('pending', function(txid) {

                paybyiost_form.append('<input type="hidden" id="iostpay_txnid" name="iostpay_txnid" value="' + txid + '">');
		
				})
                .on('success', function(result) {

					var	object 	=	JSON.parse( JSON.stringify(result) ) ;
                 
					ChekForPayResp('success');
				})
                .on('failed', function(failed) {
					
					var	object 	=	JSON.parse( JSON.stringify(failed) ) ;
					var iostpay_txnid = jQuery('#iostpay_txnid').val();	
					
					
							if( iostpay_txnid == null  ){
								
								msg = 'Payment is Failed' ;
								
							}else if( iostpay_txnid != '' ){
								
								msg = 'Balance Is not Enough' ;
								
							}	
							 swal({
									title: "Iwallet",
									text : msg+"!  Try Again!",
									type : "error",
									icon: "warning",
									buttons: true,
									dangerMode: true,
								}).then((willDelete) => {
													
									if (willDelete) {
										location.reload( true ) ;		
									} else {
										window.location = shop_page_url ;
									}
								});
				})
		
		})
	})

function ChekForPayResp( status ) {

	jQuery('#overlay').fadeIn().delay(5000).fadeOut();
	var iostpay_txnid = jQuery('#iostpay_txnid').val();	

	
    setTimeout(function() {
	
		
        jQuery.ajax({
            url: iost_txnURL,
            cache: false,
            type: "POST",
            data: {
             txid   : iostpay_txnid
            },
            success: function( json ) {

				// console.log(json) ;
                if (json !== '') {
					
                    const obj = JSON.parse(json);
                    var message = obj.message
                    var status_code = obj.status_code 
					
					iost_updateOrder( json ) ;
					
                    jQuery('.iotpay_statuscode').text(message);

				}
            }
        });
        return false;


    }, 5000);


}



	function iost_updateOrder( jsonData ) {
	
	   const obj = JSON.parse(jsonData);
	   var message = obj.message
	   var status_code = obj.status_code
	   var iostpay_txnid = obj.iostpay_txnid
					
	jQuery.ajax({
            url: iost_updateOrderURL,
            cache: false,
            type: "POST",
            data: {
             iostpay_txnid   : iostpay_txnid,
			 orderStatus : status_code,
			 message : message
            },
		success: function( resp ) {
			   console.log(resp);
				 const obj 			= JSON.parse( resp );
			     var status_code 	= obj.status_code	;
			     var message 		= obj.message	;
				 var order_key		=obj.order_key;
					swal({
                        title: "Iwallet",
                        text : message,
                        type : "Payment completed"
                    }).then((willDelete) => {
						var final_url=order_received_url+'/?key='+obj.order_key+'&success=1';
						window.location = final_url ;
					});
						
				 
			}			
		})
	}

