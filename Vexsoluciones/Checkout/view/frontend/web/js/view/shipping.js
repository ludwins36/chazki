/*browser:true*/
/*global define*/

define([
    'jquery',
    'uiComponent',
    'ko',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/action/check-email-availability',
    'Magento_Customer/js/action/login',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/full-screen-loader',
    'mage/validation',
    'mage/calendar',
    'Vexsoluciones_Checkout/js/model/ubigeo',
    'Vexsoluciones_Checkout/js/model/shipping-rate-processor/ubigeo'  
], function ($, Component, ko, customer, checkEmailAvailability, loginAction, quote, checkoutData, fullScreenLoader,vali,calen,ubigeoCheckout,ratesUbigeo) {
    'use strict';


    function getRootUrl() {

        return window.location.origin 
                        ? window.location.origin + '/'
                        : window.location.protocol + '/' + window.location.host + '/';
    
    }


    return Component.extend({

        defaults: {
            template: 'Vexsoluciones_Checkout/formularioshipping',
            email: checkoutData.getInputFieldEmailValue(),
            emailFocused: false,
            isLoading: false,
            isPasswordVisible: true,
            listens: {
                email: 'emailHasChanged',
                emailFocused: 'validateEmail'
            }
        },
 
        checkDelay: 2000,
        checkRequest: null,
        isEmailCheckComplete: null,
        isCustomerLoggedIn: customer.isLoggedIn,
        forgotPasswordUrl: window.checkoutConfig.forgotPasswordUrl,
        emailCheckTimeout: 0,
        modeFormAccount : ko.observable(1),
        isCreateAccount : false,

        programado : ko.observable('9-13'),
        movilidad : ko.observable('1'),

        ubigeoPais : ko.observable(),
        currentCountry : ko.observableArray(),
        isCountryLocal : ko.observable(true),
        programadoaux : ko.observable(false),
        departamentos: ko.observableArray(),
        ubigeoDepartamento: ko.observable(),
        provincias: ko.observableArray(),
        ubigeoProvincia: ko.observable(),
        distritos: ko.observableArray(),
        ubigeoDistrito: ko.observable(),

        distanciaMenor : ko.observable(0),
        mapGeoCode : null,
        marcadorDelivery : null,
        marcadorCoordenadas : ubigeoCheckout.deliveryLocation,
        verificartienda : null,
        verificareventoend : null,
        tiendascoordenadas : ko.observable('-11.1,-77.605'),
        
        initialize: function () {
            this._super();

            var self = this;
            var rqUbigeo = null;
            
            console.log('Solicitando departamentos');

            rqUbigeo =  $.ajax({
                url: getRootUrl()+'rest/default/V1/listarDepartamentos',
                data: JSON.stringify({}),
                showLoader: true,
                type: 'GET',
                dataType: 'json',
                context: this, 
                async : false,
                beforeSend: function(request) {
                    request.setRequestHeader('Content-Type', 'application/json');
                },
                success: function(response){
             
                    let departamentosTmp = [];

                    for(let i = 0; i < response.length; i++){
                        departamentosTmp.push({'id' : response[i].id,
                                               'text': response[i].nombre });
                    }
                    
                    self.departamentos(departamentosTmp);
                    self.ubigeoDepartamento(departamentosTmp[0].id);

                },

                complete: function () { 
                     
                }
            });

            $.ajax({
                url: getRootUrl()+'rest/default/V1/listarTienda',
                data: JSON.stringify({}),
                showLoader: true,
                type: 'GET',
                dataType: 'json',
                context: this, 
                async : false,
                beforeSend: function(request) {
                    request.setRequestHeader('Content-Type', 'application/json');
                },
                success: function(response){
                    self.tiendascoordenadas(response[0]);
                },

                complete: function () { 
                     
                }
            });

            $.ajax({
                url: getRootUrl()+'rest/default/V1/listarPaises',
                data: JSON.stringify({}),
                showLoader: true,
                type: 'GET',
                dataType: 'json',
                context: this, 
                async : false,
                beforeSend: function(request) {
                    request.setRequestHeader('Content-Type', 'application/json');
                },
                success: function(response){
             
                    let paisesTmp = [];

                    for(let i = 0; i < response.length; i++){
                        paisesTmp.push({'id' : response[i].id,
                                               'text': response[i].nombre });
                    }
                    let xaux={'id' :'PE','text': 'PERU'} ;
                    self.currentCountry(paisesTmp);
                    self.ubigeoPais(xaux);

                },

                complete: function () { 
                     
                }
            });
            
        

            this.ubigeoPais.subscribe(function(newValue){
                
                console.log('Nuevo pais: ', newValue.id);

                if(newValue.id == 'PE'){
                    self.isCountryLocal(true);
                }else{
                    self.isCountryLocal(false);
                }

                ubigeoCheckout.country_id(newValue.id);
                console.log("________");
                self.calcularDistancias();
            });
            

            this.ubigeoDepartamento.subscribe(function(newValue) {
 
                console.log('Solicitando provincias', newValue);
                let departamento_id = newValue && newValue.id;

                ubigeoCheckout.departamento_id(departamento_id);

                if(rqUbigeo != null){
                    rqUbigeo.abort();
                }

                rqUbigeo = $.ajax({
                    url: getRootUrl()+'rest/default/V1/listarProvincias/' + departamento_id,
                    data: JSON.stringify({}),
                    showLoader: false,
                    type: 'GET',
                    dataType: 'json',
                    context: this, 
                    beforeSend: function(request) {
                        request.setRequestHeader('Content-Type', 'application/json');
                    },
                    success: function(response){

                        let provinciasTmp = [];

                        for(let i = 0; i < response.length; i++){
                              
                            provinciasTmp.push({'id' : response[i].id,
                                                'text': response[i].nombre });

                        }
                            
                        self.provincias(provinciasTmp);
                        self.ubigeoProvincia(provinciasTmp[0]);
                         

                    },
                    complete: function () { 
                         
                    }
                });
            });

            this.ubigeoProvincia.subscribe(function(newValue) {

                console.log('Solicitando distritos: ', newValue);

                let provincia_id = newValue && newValue.id; 

                ubigeoCheckout.provincia_id(provincia_id);

                if(rqUbigeo != null){
                    rqUbigeo.abort();
                }

                rqUbigeo = $.ajax({

                    url: getRootUrl() + 'rest/default/V1/listarDistritos/'+provincia_id,
                    data: JSON.stringify({}),
                    showLoader: false,
                    type: 'GET',
                    dataType: 'json',
                    context: this, 
                    beforeSend: function(request) { 
                        request.setRequestHeader('Content-Type', 'application/json');
                    },
                    success: function(response) {
                         
                       let distritosTmp = [];

                       for(let i = 0; i < response.length; i++){
                             
                           distritosTmp.push({'id' : response[i].id,
                                              'text': response[i].nombre });

                           //self.ubigeoDistrito(distritosTmp[0]);
                       }
                           
                       self.distritos(distritosTmp);
                       self.ubigeoDistrito(distritosTmp[0]);
        
                       let indDistrito = 0;

                    },

                    complete: function () { 
                         
                    }
                });
            });

            this.ubigeoDistrito.subscribe(function(newValue) {

                    let departamento_id = self.ubigeoDepartamento() && self.ubigeoDepartamento().id;
                    let provincia_id = self.ubigeoProvincia() && self.ubigeoProvincia().id;
                    let distrito_id = newValue && newValue.id;  

                    self.generarMapaDelivery();

                    self.calcularDistancias();
                     

                    ubigeoCheckout.distrito_id(distrito_id);   
            });

        },
        click: function(e){
            ubigeoCheckout.programado(this.programado());   
            return true;
        },
        clickmovilidad: function(e){
            ubigeoCheckout.movilidad(this.movilidad());
            this.refrescarCostosPorDistancia();
            return true;
        },

        generarMapaDelivery : function(){

            let self = this;  

            if(this.mapGeoCode==null){
                $("#contenedor-mapa").show();
                this.mapGeoCode = new google.maps.Map(document.getElementById('mapaGeoDecode'), {
                      zoom: 15,
                      center: {lat: -12.1103058, lng: -77.0513356}
                });

                this.geocoder = new google.maps.Geocoder();
            }

            let address =  self.ubigeoDistrito().text+ ',' + self.ubigeoProvincia().text+','+self.ubigeoDepartamento().text;

            this.coberturas = [];
            

            this.geocoder.geocode({'address': address}, function(results, status) {
            
              if (status === 'OK') {

                try{

                    self.mapGeoCode.setCenter(results[0].geometry.location);

                    self.marcadorCoordenadas({ lat: results[0].geometry.location.lat(), 
                                               lng: results[0].geometry.location.lng() });  //results[0].geometry.location;

                    if(self.marcadorDelivery==null){
                        self.marcadorDelivery = new google.maps.Marker({
                              map: self.mapGeoCode,
                              position: results[0].geometry.location,
                              draggable: true
                        });
                    }else{
                        
                        self.marcadorDelivery.setPosition(results[0].geometry.location);
                    }

                    self.marcadorCoordenadas({lat: results[0].geometry.location.lat(), lng: results[0].geometry.location.lng() });
                    self.calcularDistancias();

                }
                catch(err){

                    console.log(err);
                }

                if(self.verificareventoend==null){
                    self.verificareventoend = true;
                    self.marcadorDelivery.addListener('dragend', function(event){
                         self.marcadorCoordenadas({lat: event.latLng.lat(), lng: event.latLng.lng() });
                         self.calcularDistancias();
                         console.log(event.latLng);
                    });
                }
                

             
              } else {
            
                console.log('Geocode was not successful for the following reason: ' + status);
            
              }
            
            });


        },

        calcularDistancias : function(){

                   let self = this;
                   let service = new google.maps.DistanceMatrixService;
                   let destinos = [];

                    if(self.tiendascoordenadas()=="" || self.tiendascoordenadas()==null){
                        self.tiendascoordenadas("0,0");
                    }
                    let tienda = self.tiendascoordenadas().split(',');

                   destinos.push({ lat: parseFloat(tienda[0]), lng: parseFloat(tienda[1]) });
                    let tiendaalmacen = new google.maps.Marker({
                          map: self.mapGeoCode,
                          icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png',
                          position: { lat: parseFloat(tienda[0]), lng: parseFloat(tienda[1]) }
                    });


                   service.getDistanceMatrix({

                         origins: [this.marcadorCoordenadas()],
                         destinations: destinos,
                         travelMode: 'DRIVING',
                         unitSystem: google.maps.UnitSystem.METRIC,
                         avoidHighways: false,
                         avoidTolls: false
                   
                   }, function(response, status) { 

                          
                         if (status !== 'OK') {
                            
                            console.log('Error was: ' + status);
                            self.distanciaMenor( 0 );
                            ubigeoCheckout.distancia(0);
                         }  
                         else
                         {
                            console.log('Distancias calculadas');

                            let distanciaMenor = 0;
                            let indexDistanciaMenor = 0;
                            let aux = 0;

                            $.each( response.rows[0].elements, function (index, data) {
                            
                               let distanciaKm = data.distance.text.replace(",",".").split(' ');
 
                                let distanciaFLoat = parseFloat(distanciaKm[0]); 

                                if(distanciaMenor == 0 || distanciaFLoat < distanciaMenor ){
                                    distanciaMenor = distanciaFLoat;
                                    indexDistanciaMenor = index;
                                }

                                aux = distanciaFLoat;
                            });

                            self.distanciaMenor( aux );
                            ubigeoCheckout.distancia(aux);
                            self.refrescarCostosPorDistancia();
                         }
 
                   }); 

                   console.log('Hola estoy aqui');

        },

        refrescarCostosPorDistancia: function(){
  
                let self = this;
    
                let departamento_id = this.ubigeoDepartamento() && this.ubigeoDepartamento().id;
                let provincia_id = this.ubigeoProvincia() && this.ubigeoProvincia().id;
                let distrito_id = this.ubigeoDistrito() && this.ubigeoDistrito().id;
 
                let distancia = self.distanciaMenor();

                let regionCode = departamento_id + '-' + provincia_id + '-' + distrito_id + '-'+distancia+'-'+self.movilidad()+'-'+self.marcadorCoordenadas().lat+","+self.marcadorCoordenadas().lng;
           
                var type = quote.shippingAddress().getType();                        
                let address = quote.shippingAddress();
                
                address.regionCode = regionCode;
                address.postcode = regionCode;
                address.countryId = self.ubigeoPais().id,
 
                ratesUbigeo.getRates(address).done(function(){

                    console.log(self); 

                    //self.selectShippingMethod(self.getByMethodCode('envio_express'));
                    
                });
                

            }




    });
});
