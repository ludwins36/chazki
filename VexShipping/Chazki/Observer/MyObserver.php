<?php
namespace VexShipping\Chazki\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Captcha\Observer\CaptchaStringResolver;
use Magento\Store\Model\ScopeInterface;

class MyObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $_request;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    protected $addressRepository;
    
 
    public function __construct(
         \Magento\Framework\App\Request\Http $request
        , \Magento\Store\Model\StoreManagerInterface $storeManager
        , \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
        , \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
    )
    {
        $this->_request = $request;
        $this->_storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->addressRepository = $addressRepository;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/chazky.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $departmentolabel = '';
        $provincialabel = '';
        $distritolabel = '';
        $departmentocode = 0;
        $provinciacode = 0;
        $distritocode = 0;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $_store = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();

        $order = $observer->getEvent()->getOrder();
        $order_id = $order->getId();
        $shipping = $order->getShippingMethod();
        $pos = strpos($shipping, 'chazki');



        $modo = '';
        if(strpos($shipping,'chazkiexpress')!==false){
            $modo = 'Express';
        }else if(strpos($shipping,'chazkiregular')!==false){
            $modo = 'Regular';
        }else if(strpos($shipping,'chazkiprogramado')!==false){
            $modo = 'Programado';
        }

        if ($pos === false) {
            return false;
        }

        $orden_id = $order_id;
        $currency = $order->getOrderCurrencyCode();
        $costo = $order->getShippingAmount();
        $costo = ($costo=="")?0:$costo;

        $dataAddress = $order->getBillingAddress()->toArray();
        $region = $dataAddress['region'];
        $street = $dataAddress['street'];
        $codeCountry = $dataAddress['country_id'];
        $city = $dataAddress['city'];
        $streetarray = explode("\n", $street);
        $street1 = trim($streetarray[0]);
        $street2 = (isset($streetarray[1]))?$streetarray[1]:"";

        $customer_id = $order->getCustomerId();
        $customer_firstname = $order->getBillingAddress()->getFirstname();
        $customer_lastname = $order->getBillingAddress()->getLastname();
        $customer_address = $order->getBillingAddress()->getData('street');
        $correo = $order->getBillingAddress()->getEmail();
        $company_name = $order->getBillingAddress()->getCompany();

        if (!$correo) {
            $correo = $order->getCustomerEmail();
        }

        $customer_phone = $order->getShippingAddress()->getData('telephone');
        $dnivalor = $order->getShippingAddress()->getData('dni');

        $orderShippingAddress = $order->getShippingAddress();
        $latlng = $orderShippingAddress->getData('delivery_location');
        $quote = $observer->getEvent()->getOrder();
        $quoteShippingAddress = $quote->getShippingAddress();

        $departmentocode = $quoteShippingAddress->getData('departamento_id');
        $provinciacode = $quoteShippingAddress->getData('provincia_id');
        $distritocode = $quoteShippingAddress->getData('distrito_id');

        $programado = '';
        if($modo == 'Programado'){
            $programado = $quoteShippingAddress->getData('programado');
        }


        if( $quoteShippingAddress->getCustomerAddressId() != '' && is_numeric($quoteShippingAddress->getCustomerAddressId()) ){
            $customerAddress = $this->addressRepository->getById($quoteShippingAddress->getCustomerAddressId());
            
            if($customerAddress->getId()){
                $r = $customerAddress->__toArray();
                if(isset($r['custom_attributes']['address_location']['value'])){
                    $latlng = $r['custom_attributes']['address_location']['value'];
                }
                if(isset($r['custom_attributes']['dni']['value']) && $dnivalor==""){
                    $dnivalor = $r['custom_attributes']['dni']['value'];
                }

                if(isset($r['custom_attributes']['departamento_id']['value']) && $departmentocode==0){
                    $departmentocode = $r['custom_attributes']['departamento_id']['value'];
                }
                if(isset($r['custom_attributes']['provincia_id']['value']) && $provinciacode==0){
                    $provinciacode = $r['custom_attributes']['provincia_id']['value'];
                }
                if(isset($r['custom_attributes']['distrito_id']['value']) && $distritocode==0){
                    $distritocode = $r['custom_attributes']['distrito_id']['value'];
                }

            }
        }

        $dnivalor = ($dnivalor=="")?"Sin DNI":$dnivalor;
        $latlngarray = explode(',', $latlng);
        $latlngarray[0] = ($latlngarray[0]!='')?$latlngarray[0]:0;
        $latlngarray[1] = (isset($latlngarray[1]))?$latlngarray[1]:0;

        $items = $order->getAllVisibleItems();
        $productoslistado = '';

        foreach($items as $item) {
            $num_quantity = $item->getQtyOrdered();
            $weight = (float)$item->getWeight();
            $nameproduct = $item->getName();
            $precio = $item->getPrice();

            $productoslistado .= "{\"name\": \"".$nameproduct."\",\"currency\": \"".$currency."\",\"price\": ".$precio.",\"weight\": ".$weight.",\"volum_weight\": 0,\"num_quantity\": ".$num_quantity.",\"quantity\": \"Paquete\",\"size\":\"M\"},";
        }
        $productoslistado = ($productoslistado=="")?"":substr($productoslistado, 0, -1);

        
        $urlconfig = $this->scopeConfig->getValue('vexsolucioneschazki/general/chazky_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $keyconfig = $this->scopeConfig->getValue('vexsolucioneschazki/general/chazky_api', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $storeidconfig = $this->scopeConfig->getValue('vexsolucioneschazki/general/storeid', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $branchidconfig = $this->scopeConfig->getValue('vexsolucioneschazki/general/branchid', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $curl = curl_init($urlconfig."chazkiServices/delivery/create/deliveryService");                                                                      
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, "[
                            {
                                \"storeId\": \"".$storeidconfig."\",
                                \"branchId\": \"".$branchidconfig."\",
                                \"deliveryTrackCode\": \"".$order_id."\",
                                \"proofPayment\": \"Boleta\",
                                \"deliveryCost\": 0,
                                \"mode\": \"".$modo."\",
                                \"time\" : \"".$programado."\",
                                \"paymentMethod\": \"Pagado\",
                                \"country\": \"".$codeCountry."\",
                                \"listItemSold\": [
                                    ".$productoslistado."
                                ],
                                \"notes\": \"\",
                                \"documentNumber\": \"".$dnivalor."\",
                                \"name_tmp\": \"".$customer_firstname."\",
                                \"last_tmp\": \"".$customer_lastname."\",
                                \"company_name\": \"".$company_name."\",
                                \"email\": \"".$correo."\",
                                \"phone\": \"".$customer_phone."\",
                                \"documentType\": \"DNI\",
                                \"addressClient\": [
                                    {
                                        \"department\": \""."Lima"."\",
                                        \"province\": \""."Lima"."\",
                                        \"district\": \""."Lima"."\",
                                        \"name\": \"".$street1."\",
                                        \"phone\":\"".$customer_phone."\",
                                        \"reference\": \"".$street2."\",
                                        \"alias\":\"".""."\",
                                        \"position\": {
                                            \"latitude\": \"".$latlngarray[0]."\",
                                            \"longitude\": \"".$latlngarray[1]."\"
                                        }
                                    }
                                ]
                            }
                        ]");

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
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                $logger->info("CURL Error #:" . $err);
            } else {
                if(isset($response['codeDelivery'])){
                    $logger->info($response['response']." - ".$response['codeDelivery']." - ".$response['descriptionResponse']);
                }else{
                    $logger->info("Error response invallido");
                }
            }

       
    
    }

}