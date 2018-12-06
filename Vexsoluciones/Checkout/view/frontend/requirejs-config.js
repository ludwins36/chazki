 
var config = {
 	config:{
 		mixins: {
                'Magento_Checkout/js/view/shipping': {
                    'Vexsoluciones_Checkout/js/view/shippingmethod': true
                }
    }},
    map: {
        '*': {
            
            "Magento_Checkout/js/model/shipping-save-processor/default" : "Vexsoluciones_Checkout/js/model/shipping-save-processor/default",
                
        }
    }
};
 