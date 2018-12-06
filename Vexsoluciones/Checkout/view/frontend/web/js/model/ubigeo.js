define(
    ['ko',
     'underscore'],
    function (ko, 
              _) {

        'use strict';
     
        return {           
            country_id: ko.observable('PE'),
            departamento_id: ko.observable(''),
            provincia_id: ko.observable(''),
            distrito_id: ko.observable(''),
            ciudad_label: ko.observable(''),
            distancia: ko.observable(''),
            programado: ko.observable(''),
            movilidad: ko.observable('1'),
            deliveryLocation: ko.observable({})
        };

    }
);
