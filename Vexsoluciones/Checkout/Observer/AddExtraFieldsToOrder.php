<?php 

namespace Vexsoluciones\Checkout\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer; 

use Vexsoluciones\Checkout\Api\QuoteExtraFields;

class AddExtraFieldsToOrder implements ObserverInterface
{
    
    protected $addressRepository;

    public function __construct(\Magento\Customer\Api\AddressRepositoryInterface $addressRepository){

        $this->addressRepository = $addressRepository;

    }
 
    public function execute(Observer $observer)
    {

        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();
  
        // Quote get shipping address
    
        $quoteShippingAddress = $quote->getShippingAddress();
        $orderShippingAddress = $order->getShippingAddress();


        $depa_id = $quoteShippingAddress->getData(QuoteExtraFields::DEPARTAMENTO_ID);
        $prov_id = $quoteShippingAddress->getData(QuoteExtraFields::PROVINCIA_ID);
        $dist_id = $quoteShippingAddress->getData(QuoteExtraFields::DISTRITO_ID);
        $programado = $quoteShippingAddress->getData(QuoteExtraFields::PROGRAMADO);

        //$quoteShippingAddress->getData(QuoteExtraFields::QUIEN_RECOGE_DOCUMENTO) 
            
        /*if( strpos($quoteShippingAddress->getShippingMethod(), 'pickupstore') === false ){ // solo si no es recojo en tienda
 
            if( $quoteShippingAddress->getCustomerAddressId() != '' && is_numeric($quoteShippingAddress->getCustomerAddressId()) ){
     
                $customerAddress = $this->addressRepository->getById($quoteShippingAddress->getCustomerAddressId());
                
                $customerAddress->setCustomAttribute('departamento_id',$depa_id); 
                $customerAddress->setCustomAttribute('provincia_id',$prov_id); 
                $customerAddress->setCustomAttribute('distrito_id',$dist_id); 

                if( trim($quoteShippingAddress->getData(QuoteExtraFields::DELIVERY_LOCATION)) != ''){

                    $customerAddress->setCustomAttribute('address_location', $quoteShippingAddress->getData(QuoteExtraFields::DELIVERY_LOCATION) ); 
                }

                if( trim($quoteShippingAddress->getData(QuoteExtraFields::QUIEN_RECOGE_DOCUMENTO)) != ''){

                    $customerAddress->setCustomAttribute('dni', $quoteShippingAddress->getData(QuoteExtraFields::QUIEN_RECOGE_DOCUMENTO) ); 
                }
      
                $postCode = $depa_id.''.$prov_id.''.$dist_id;

                if($customerAddress->getPostCode($postCode) == ''){

                    $customerAddress->setPostCode($postCode);
                }

                $this->addressRepository->save($customerAddress);

            }

        }*/

        //throw new \Exception("Aqui estamos ".$quoteShippingAddress->getCustomerAddressId(), 1);
        
        $orderShippingAddress->setData(
            QuoteExtraFields::DEPARTAMENTO_ID,
            $depa_id
        ); 

        $orderShippingAddress->setData(
            QuoteExtraFields::PROVINCIA_ID,
            $prov_id
        ); 

        $orderShippingAddress->setData(
            QuoteExtraFields::DISTRITO_ID,
            $dist_id
        );

        $orderShippingAddress->setData(
            QuoteExtraFields::PROGRAMADO,
            $programado
        ); 
 
        $orderShippingAddress->setData(
            QuoteExtraFields::DELIVERY_LOCATION,
            $quoteShippingAddress->getData(QuoteExtraFields::DELIVERY_LOCATION)
        ); 
 
    }
}
