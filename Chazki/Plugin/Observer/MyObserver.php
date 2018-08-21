<?php
namespace Chazki\Plugin\Observer;

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
    
 
    public function __construct(
         \Magento\Framework\App\Request\Http $request
        , \Magento\Store\Model\StoreManagerInterface $storeManager
        , \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->_request = $request;
        $this->_storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/chazky.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

    	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    	$_store = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
     	$_imageHelper = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Catalog\Helper\Image');
		$_baseImageUrl = $_store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'catalog/product';
		
     	$order_id = $observer->getData('order_ids');
		$order = $objectManager->create('Magento\Sales\Model\Order')->load($order_id);
		
        $pay_method = $order->getShippingMethod();
       	if ($pay_method != 'chazki_chazki') {
            return false;
        }

        $magento_id = $order->getId();

        $currency = $order->getOrderCurrencyCode();
        $total_amountaux = $order->getData();
        $total_amount = $total_amountaux['grand_total'];

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

            $customer_phone = $order->getShippingAddress()->getData('telephone');//$observer->getEvent()->getBillingAddress()->getTelephone();
            $costo = $order->getShippingAmount();
            $costo = ($costo=="")?0:$costo;

            $items = $order->getAllVisibleItems();
            $productoslistado = '';
            
            foreach($items as $item) {
                $num_quantity = $item->getQtyOrdered();
                $weight = $item->getWeight();//$item->getWeight() * $item->getQty();
                $nameproduct = $item->getName();
                $precio = $item->getPrice()*$num_quantity;
                /*$item->getProductId());*/
                $productoslistado .= "{
                    \"name\": \"".$nameproduct."\",
                    \"currency\": \"".$currency."\",
                    \"price\": ".$precio.",
                    \"weight\": ".$weight.",
                    \"volum_weight\": ".$weight.",
                    \"num_quantity\": ".$num_quantity.",
                    \"quantity\": \"Paquete\",
                    \"size\":\"S\"
                },";
            }
            $productoslistado = ($productoslistado=="")?"":substr($productoslistado, 0, -1);

            $urlconfig = $this->scopeConfig->getValue('carriers/chazki/chazky_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $keyconfig = $this->scopeConfig->getValue('carriers/chazki/chazky_api', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $storeidconfig = $this->scopeConfig->getValue('carriers/chazki/storeid', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $branchidconfig = $this->scopeConfig->getValue('carriers/chazki/branchid', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            $curl = curl_init($urlconfig."chazkiServices/delivery/create/deliveryService");                                                                      
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, "[
                                        {
                                            \"storeId\": \"".$storeidconfig."\",
                                            \"branchId\": \"".$branchidconfig."\",
                                            \"deliveryTrackCode\": \"".$magento_id."\",
                                            \"proofPayment\": \"Boleta\",
                                            \"deliveryCost\": ".$costo.",
                                            \"mode\": \"Regular\",
                                            \"time\" : \"\",
                                            \"paymentMethod\": \"Pagado\",
                                            \"country\": \"".$codeCountry."\",
                                            \"listItemSold\": [
                                                ".$productoslistado."
                                            ],
                                            \"notes\": \"El paquete lo deja en la recepción del edificio\",
                                            \"documentNumber\": \"12345678987\",
                                            \"name_tmp\": \"".$customer_firstname."\",
                                            \"last_tmp\": \"".$customer_lastname."\",
                                            \"company_name\": \"".$company_name."\",
                                            \"email\": \"".$correo."\",
                                            \"phone\": \"".$customer_phone."\",
                                            \"documentType\": \"RUC\",
                                            \"addressClient\": [
                                                {
                                                    \"department\": \"LIMA\",
                                                    \"province\": \"LIMA\",
                                                    \"district\": \"SAN BORJA\",
                                                    \"name\": \"".$street1."\",
                                                    \"phone\":\"".$customer_phone."\",
                                                    \"reference\": \"".$street2."\",
                                                    \"alias\":\"".""."\",
                                                    \"position\": {
                                                        \"latitude\": 0,
                                                        \"longitude\": 0
                                                    }
                                                }
                                            ]
                                        }
                                    ]");

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);                                                                      
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

     $logger->info("[
                                        {
                                            \"storeId\": \"".$storeidconfig."\",
                                            \"branchId\": \"".$branchidconfig."\",
                                            \"deliveryTrackCode\": \"".$magento_id."\",
                                            \"proofPayment\": \"Boleta\",
                                            \"deliveryCost\": ".$costo.",
                                            \"mode\": \"Regular\",
                                            \"time\" : \"\",
                                            \"paymentMethod\": \"Pagado\",
                                            \"country\": \"".$codeCountry."\",
                                            \"listItemSold\": [
                                                ".$productoslistado."
                                            ],
                                            \"notes\": \"El paquete lo deja en la recepción del edificio\",
                                            \"documentNumber\": \"12345678987\",
                                            \"name_tmp\": \"".$customer_firstname."\",
                                            \"last_tmp\": \"".$customer_lastname."\",
                                            \"company_name\": \"".$company_name."\",
                                            \"email\": \"".$correo."\",
                                            \"phone\": \"".$customer_phone."\",
                                            \"documentType\": \"RUC\",
                                            \"addressClient\": [
                                                {
                                                    \"department\": \"LIMA\",
                                                    \"province\": \"LIMA\",
                                                    \"district\": \"SAN BORJA\",
                                                    \"name\": \"".$street1."\",
                                                    \"phone\":\"".$customer_phone."\",
                                                    \"reference\": \"".$street2."\",
                                                    \"alias\":\"".""."\",
                                                    \"position\": {
                                                        \"latitude\": 0,
                                                        \"longitude\": 0
                                                    }
                                                }
                                            ]
                                        }
                                    ]");       
    }

}