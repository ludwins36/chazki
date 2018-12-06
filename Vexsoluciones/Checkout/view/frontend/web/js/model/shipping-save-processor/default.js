/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define,alert*/
define(
    [
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/resource-url-manager',
        'mage/storage',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/payment/method-converter',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/select-billing-address',
        'Vexsoluciones_Checkout/js/model/ubigeo',
        'Magento_Customer/js/model/customer',
        'Magento_Customer/js/customer-data'
    ],
    function (
        ko,
        quote,
        resourceUrlManager,
        storage,
        paymentService,
        methodConverter,
        errorProcessor,
        fullScreenLoader,
        selectBillingAddressAction,
        ubigeoCheckout,
        recojoentiendaDatos,
        customer,
        customerData
    ) {
        'use strict';

        return {
            saveShippingInformation: function () {
                let payload;

                if (!quote.billingAddress()) {
                    selectBillingAddressAction(quote.shippingAddress());
                }

                let shippingAddress = quote.shippingAddress();

                let billingInfo = quote.billingAddress();
                
                let ciudad = '';


                shippingAddress.countryId = ubigeoCheckout.country_id();
                //shippingAddress.city = ciudad;
                billingInfo.countryId = ubigeoCheckout.country_id(); 

                
                let departamento_id = ubigeoCheckout.departamento_id();
                let provincia_id    = ubigeoCheckout.provincia_id();
                let distrito_id     = ubigeoCheckout.distrito_id();
                let devdis     = ubigeoCheckout.distancia();
                let dev     = ubigeoCheckout.deliveryLocation();
                let regionCode = departamento_id + '-' + provincia_id + '-' + distrito_id+ '-' + devdis+'-'+ubigeoCheckout.movilidad()+'-'+ubigeoCheckout.deliveryLocation().lat + ',' + ubigeoCheckout.deliveryLocation().lng; 

                let ciudad_label = ubigeoCheckout.ciudad_label();
                shippingAddress.countryId = ubigeoCheckout.country_id();
                //shippingAddress.city = ciudad_label;
                shippingAddress.postcode = regionCode;
                shippingAddress.regionCode = regionCode;

                billingInfo.countryId = ubigeoCheckout.country_id();
                //billingInfo.city = ciudad_label;
                billingInfo.postcode = regionCode;
                billingInfo.regionCode = regionCode;


                let deliveryLocation = '';

                if( 'lat' in ubigeoCheckout.deliveryLocation() ){

                    deliveryLocation = ubigeoCheckout.deliveryLocation().lat + ',' + ubigeoCheckout.deliveryLocation().lng; 
                }
 
                payload = {
                    addressInformation: {
                        shipping_address: shippingAddress,
                        billing_address: billingInfo,
                        shipping_method_code: quote.shippingMethod().method_code,
                        shipping_carrier_code: quote.shippingMethod().carrier_code,
                        extension_attributes: {
                            departamento_id : ubigeoCheckout.departamento_id(),
                            provincia_id : ubigeoCheckout.provincia_id(),
                            distrito_id :  ubigeoCheckout.distrito_id(),
                            delivery_location : deliveryLocation,
                            programado : ubigeoCheckout.programado()
                        }
                    }
                };

                fullScreenLoader.startLoader();

                return storage.post(
                    resourceUrlManager.getUrlForSetShippingInformation(quote),
                    JSON.stringify(payload)
                ).done(
                    function (response) {
                        quote.setTotals(response.totals);
                        paymentService.setPaymentMethods(methodConverter(response.payment_methods));
                        fullScreenLoader.stopLoader();
                    }
                ).fail(
                    function (response) {
                        errorProcessor.process(response);
                        fullScreenLoader.stopLoader();
                    }
                );
            }
        };
    }
);
