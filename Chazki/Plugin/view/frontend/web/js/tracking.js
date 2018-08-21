require(
    [
        'jquery',
        'Magento_Ui/js/modal/modal',
        'uiComponent'
    ],
    function(
        $,
        modal,
        Component
    ) {
        var map;
        $(document).ready(function(){

            let key = "AIzaSyC6QpC9PVtugnSQjAznW1GwN9ub44E62b4";
            $.getScript("https://maps.googleapis.com/maps/api/js?v=3&sensor=false&key="+key, function () {
                initialize();
            });

            function initialize(){
                var options = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    size: 'sm',
                    title: 'Mapa',
                    modalClass: 'modalMapa'
                };
                var popup = modal(options, $('#popup-modal-tracking-chaski'));

                jQuery(".trackingclick").click(function(e){
                    e.preventDefault();
                    var orden = jQuery(this).data("id");
                    var cliente = jQuery(this).data("cliente");
                    $.ajax({
                        url: window.urlTrackingChaski+'rest/V1/Getestado?',
                        dataType: 'json',
                        type: 'GET',
                        data: {
                            customer_id: cliente,
                            order_id: orden
                        },
                        success: function(data){
                            if(data.status == 1){

                                jQuery('#popup-modal-tracking-chaski').modal('openModal');
                                var latchaski = parseFloat(data.position.latitude);
                                var lngchaski = parseFloat(data.position.longitude);
                                setTimeout(function(){
                                    let uluru = {lat: latchaski, lng: lngchaski };

                                    map = new google.maps.Map(document.getElementById('popup-modal-tracking-chaski'), {
                                      zoom: 12,
                                      center: uluru
                                    });
                                    
                                    addMarker(uluru);

                                },1000);

                            }else{
                                console.log(data.error_message);
                            }
                            
                        },
                        error: function(data){
                            console.error('Error: '+ data);
                        }
                    });
                    

                });

                var markers = [];
                var getMarkerUniqueId= function(lat, lng) {
                    return lat + '_' + lng;
                }
                function addMarker(location) { // Adds a marker to the map and push to the array.
                    var markerId = getMarkerUniqueId(location.lat, location.lng); // that will be used to cache this marker in markers object.
                    var marker = new google.maps.Marker({
                        position: location,
                        map: map,
                        animation: google.maps.Animation.DROP,
                        id: markerId
                    });
                    markers[markerId] = marker;
                }
                var removeMarker = function(marker, markerId) {
                    marker.setMap(null); // set markers setMap to null to remove it from map
                    delete markers[markerId]; // delete marker instance from markers object
                };


                

            }
            
        });
    }
);