jQuery(function() {
    jQuery('body')
        .on('updated_checkout', function() {

            addIostPayOnClick();

            jQuery('input[name="payment_method"]').change(function() {
                console.log("payment method changed");

                if (typeof IWalletJS === 'undefined' && jQuery('form[name="checkout"] input[name="payment_method"]:checked').val() == 'iostpay') {

                    // console.log('undefined');

                    swal({
                        title: "Iwallet",
                        text: "iwallet extension not found!",
                        type: "error"
                    }).then((willDelete) => {

                        jQuery('#place_order').attr("disabled", true);
                    });

                    return false;
                } else if (typeof IWalletJS !== 'undefined' && IWalletJS.account.name == null && jQuery('form[name="checkout"] input[name="payment_method"]:checked').val() == 'iostpay') {

                    swal({
                        title: "Iwallet",
                        text: "Please Unlock iwallet Extension!",
                        type: "error"
                    }).then((willDelete) => {

                        jQuery('#place_order').attr("disabled", true);

                    });

                } else {
                    addIostPayOnClick();
                }


            });
        });
});




// iostpay 

var transfer;
document.addEventListener("DOMContentLoaded", async function() {
    await new Promise(done => setTimeout(() => done(), 500));

    if('IWalletJS' in window){
        console.log("IWallet is installed", window.IWalletJS);
    } else {
        console.log("IWallet is not installed");
    }    
        
        
    if (typeof IWalletJS === 'undefined' && jQuery('form[name="checkout"] input[name="payment_method"]:checked').val() == 'iostpay') {

        // console.log('undefined');

        swal({
            title: "Iwallet",
            text: "iwallet extension not found!",
            type: "error"
        }).then((willDelete) => {

            jQuery('#place_order').attr("disabled", true);
        });



        return false;
    } else {

        console.log('defined');
    }


    if (IWalletJS.account.name == null && jQuery('form[name="checkout"] input[name="payment_method"]:checked').val() == 'iostpay') {

        swal({
            title: "Iwallet",
            text: "Please Unlock iwallet Extension!",
            type: "error"
        }).then((willDelete) => {

            jQuery('#place_order').attr("disabled", true);

        });

        return false;
    }


    IWalletJS.enable().then(function(account) {
        if (!account) return;

        const iost = IWalletJS.newIOST(IOST);

        transfer = function() {

            jQuery('#place_order').removeAttr("onclick");

            hndlChkFormSubmsn(false);

            var checkout_form = jQuery('form.checkout');

            var input = jQuery(this).next();
            input.removeAttr("onclick")

            const tx = iost.callABI("token.iost", "transfer", ["iost", account, iostpay_account_id, cart_total, "dapp test memo"]);
            tx.addApprove("iost", cart_total);

            // console.log(tx.getApproveList());

            iost.signAndSend(tx)
                .on('pending', function(txid) {


                    checkout_form.append('<input type="hidden" id="iostpay_txnid" name="iostpay_txnid" value="' + txid + '">');


                    ChekForPayResp('pending');
                    // hndlChkFormSubmsn( true ) ;

                    setInterval(function() {
                        jQuery('#place_order').trigger('click');
                    }, 200);

                    console.log('pending');
                })
                .on('success', function(result) {


                    ChekForPayResp('success');

                })
                .on('failed', function(failed) {


                    ChekForPayResp('failed');

                })


        }
    })


})

// iostpay 

function ChekForPayResp(status) {

    jQuery('#place_order').attr("disabled", true);

    jQuery('#overlay').fadeIn().delay(16000).fadeOut();

    setInterval(function() {


        var iostpay_txnid = jQuery('#iostpay_txnid').val();

        jQuery.ajax({
            url: iost_txnURL,
            cache: false,
            type: "POST",
            data: {
                txid: iostpay_txnid
            },
            success: function(json) {

                if (json !== '') {

                    const obj = JSON.parse(json);
                    var message = obj.message
                    var status_code = obj.status_code

                    jQuery('.iotpay_statuscode').text(message);

                    var checkout_form = jQuery('form.checkout');

                    checkout_form.append('<input type="hidden" id="iostpay_status" name="iostpay_status" value="' + status_code + '">');

                    // console.log(json);


                    if (status_code == 'failed' || status_code == 'pending') {
                        flag = false
                        window.location.href = get_cart_url ;
                        return false;
                    } else {
                        flag = true
                    }

                    hndlChkFormSubmsn(true);
                }
            }
        });
        return false;


    }, 15000);


}

function hndlChkFormSubmsn(flag) {

    var checkout_form = jQuery('form.checkout');
    checkout_form.on('checkout_place_order', function() {
        return flag;
    });

}


function addIostPayOnClick() {

    if (jQuery('form[name="checkout"] input[name="payment_method"]:checked').val() == 'iostpay') {

        jQuery("#place_order").attr("onclick", "transfer()");

    } else {

        jQuery("#place_order").removeAttr("onclick")
        jQuery("#place_order").removeAttr("disabled")

    }

}