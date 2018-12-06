<?PHP 

namespace VexShipping\Chazki\Model\Carrier;
 
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Magento\Quote\Api\Data\ShippingMethodExtensionFactory;
 
use Magento\Checkout\Model\Session as CheckoutSession;

class ChazkiExpress extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    
    protected $_code = 'chazkiexpress';
    protected $ciudades;
    protected $extensionFactory;
    protected $request;
    protected $checkoutSession;
    protected $util;
    private $scopeConfig;
 
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \VexShipping\Chazki\Helper\Data $ciudades,
        \VexShipping\Chazki\Helper\Util $util,
        ShippingMethodExtensionFactory $extensionFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Framework\Webapi\Rest\Request $request,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->util = $util;
        $this->extensionFactory = $extensionFactory;
        $this->ciudades = $ciudades;
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }
 
 
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('nameexpress')];
    }
 

    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }
        
        $activated = $this->scopeConfig->getValue('vexsolucioneschazki/general/activated', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if($activated!="1"){
            return false;
        }

        $codigopais='';
        if($codigopais == ''){
            $params = json_decode($this->request->getContent(),true);
            if(isset($params['address']['country_id'])){
                $regionCode = $params['address']['country_id'];
                $codigopais = $regionCode;
            }
        }
        if($codigopais == ''){
            $params = json_decode($this->request->getContent(),true);
            if(isset($params['addressInformation']['shipping_address']['countryId'])){
                $regionCode = trim($params['addressInformation']['shipping_address']['countryId']);
                $codigopais = $regionCode;
            }
        }
        if($codigopais == ''){
            $params = json_decode($this->request->getContent(),true);
            if(isset($params['billingAddress']['countryId'])){
                $regionCode = trim($params['billingAddress']['countryId']);
                $codigopais = $regionCode;
            }
        }
        if(trim($request->getDestCountryId()) != '' and $codigopais==''){
            $codigopais = trim($request->getDestCountryId());
        }
        if($codigopais!="PE"){
            return false;
        }
        
        

        $result = $this->_rateResultFactory->create();
        $code = '';

        if($code == ''){
            $params = json_decode($this->request->getContent(),true);
            if(isset($params['address']['regionCode'])){
                $regionCode = $params['address']['regionCode'];
                $code = $regionCode;
            }
        }

        if($code == ''){
            $params = json_decode($this->request->getContent(),true);
            if(isset($params['addressInformation']['shipping_address']['regionCode'])){
                $regionCode = trim($params['addressInformation']['shipping_address']['regionCode']);
                $code = $regionCode;
            }
        }

        if($code == ''){
            $params = json_decode($this->request->getContent(),true);
            if(isset($params['billingAddress']['regionCode'])){
                $regionCode = trim($params['billingAddress']['regionCode']);
                $code = $regionCode;
            }
        }

        if($code == '')  
        {
            $quote = $this->checkoutSession->getQuote();   
            if($quote){
                $quoteShippingAddress = $quote->getShippingAddress(); 
                $depa_id = trim($quoteShippingAddress->getData(\Vexsoluciones\Checkout\Api\QuoteExtraFields::DEPARTAMENTO_ID));
                $prov_id = trim($quoteShippingAddress->getData(\Vexsoluciones\Checkout\Api\QuoteExtraFields::PROVINCIA_ID));
                $dist_id = trim($quoteShippingAddress->getData(\Vexsoluciones\Checkout\Api\QuoteExtraFields::DISTRITO_ID));
                $distancia = trim($quoteShippingAddress->getData(\Vexsoluciones\Checkout\Api\QuoteExtraFields::DELIVERY_LOCATION));
                $code = $depa_id.'-'.$prov_id.'-'.$dist_id."-1";
            }
        }

        if( trim($request->getDestRegionCode()) != '' and $code==''){
             $code = trim($request->getDestRegionCode()); 
        }
    

        
        if( $code != '' ){

            //Verificar parametros
            $auxiliarparametro = explode('-', $code );
            if(!isset($auxiliarparametro[3])){
                return false;
            }

            list($departamento_id, $provincia_id, $distrito_id, $distancia) = $auxiliarparametro;

            if(!$this->ciudades->getCiudades($departamento_id, $provincia_id, $distrito_id)){
                return false;
            }

            $method = $this->_rateMethodFactory->create();
            $method->setCarrier($this->_code);
            $method->setMethod($this->_code);
            $method->setMethodTitle('Chazki Express');

            $descripcion = 'Asegura envíos en un máximo de 3 horas. De 9:00am a 6:00pm.';
            
            $method->setCarrierTitle($descripcion);


            $amount = 0;

            if($distancia<=4.5){
                $amount = 7.2;
            }else{
                $distanciaaux = 1.13*ceil($distancia-4.5);
                $amount = 7.2+$distanciaaux;
            }

            $method->setPrice($amount);
            $method->setCost($amount);
     
            $result->append($method);

        }
    
        return $result;
    }
}