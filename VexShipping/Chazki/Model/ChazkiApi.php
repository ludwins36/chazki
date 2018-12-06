<?php

namespace VexShipping\Chazki\Model;

use VexShipping\Chazki\Api\ChazkiInterface;
use Magento\Framework\View\Asset\Repository;

class ChazkiApi implements ChazkiInterface
{

    protected $addressRepository;
    private $scopeConfig;
    private $assetRepo;

    public function __construct(
        Repository $assetRepo,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->addressRepository = $addressRepository;
        $this->assetRepo = $assetRepo;
    }

    public function gettracking($customerId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();  
        $request = $objectManager->get('Magento\Framework\App\Request\Http');

        $orden = $request->getParam('orden');
        $id = $orden;

        $order= $objectManager->get('\Magento\Sales\Model\Order');
        $orderData=$order->load($orden);
        $orderShippingAddress = $orderData->getShippingAddress();
        $latlng = $orderShippingAddress->getData('delivery_location');

        if( $orderShippingAddress->getCustomerAddressId() != '' && is_numeric($orderShippingAddress->getCustomerAddressId()) ){
            $customerAddress = $this->addressRepository->getById($orderShippingAddress->getCustomerAddressId());
            
            if($customerAddress->getId()){
                $r = $customerAddress->__toArray();
                if(isset($r['custom_attributes']['address_location']['value']) && $latlng==""){
                    $latlng = $r['custom_attributes']['address_location']['value'];
                }
            }
        }



        $latlngarray = explode(',', $latlng);
        $latlngarray[0] = ($latlngarray[0]!='')?$latlngarray[0]:0;
        $latlngarray[1] = (isset($latlngarray[1]))?$latlngarray[1]:0;

        $urlconfig = $this->scopeConfig->getValue('vexsolucioneschazki/general/chazky_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $keyconfig = $this->scopeConfig->getValue('vexsolucioneschazki/general/chazky_api', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $storeidconfig = $this->scopeConfig->getValue('vexsolucioneschazki/general/storeid', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $branchidconfig = $this->scopeConfig->getValue('vexsolucioneschazki/general/branchid', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        
        $curl = curl_init($urlconfig."chazkiServices/track/select/deliveryCode?code=".$id."&store=".$storeidconfig);                                                                      
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);                                                              
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(                                                                          
            "Cache-Control: no-cache",
            "Content-Type: application/json",
            "chazki-api-key: ".$keyconfig
            )                                                                       
        );
        $response = curl_exec($curl);
        $response = json_decode($response,true);

        $params = array('_secure' => $request->isSecure());
        //$response['icono'] = $this->getViewFileUrl('VexShipping_Chazki::image/scooter.png');
        $response['destino']['latitude'] = $latlngarray[0];
        $response['destino']['longitude'] = $latlngarray[1];

        if(isset($response['position']['latitude'])){
            $response['position']['latitude'] = (String)$response['position']['latitude'];
        }
        if(isset($response['position']['longitude'])){
            $response['position']['longitude'] = (String)$response['position']['longitude'];
        }

        $err = curl_error($curl);

        curl_close($curl);
        if ($err) {
            $response = $err;
        }
            
        return array($response);
    }

   
}
