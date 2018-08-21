<?PHP 

namespace Chazki\Plugin\Model\Carrier;
 
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
 
class Chazki extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    
    protected $_code = 'chazki';
 
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }
 
 
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }
 

    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }
    
        $result = $this->_rateResultFactory->create();

        $method = $this->_rateMethodFactory->create();
        $method->setCarrier($this->_code);
        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('name') );

        $amount = $this->getConfigData(15);
        $descripcion = $this->getConfigData('title');
        
        $method->setCarrierTitle($descripcion);
        $method->setPrice($amount);
        $method->setCost($amount);
 
        $result->append($method);
    
        return $result;
    }
}