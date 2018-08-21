<?php
namespace Chazki\Plugin\Block;


class Chazki extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $scopeConfig;
    /**
     * Construct
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    )
    {
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context, $data);
    }

    /**
     * Get form action URL for POST booking request
     *
     * @return string
     */
    public function getId()
    {
        return $this->getRequest()->getParam('id');
    }
    /**
     * Get form action URL for POST booking request
     *
     * @return string
     */
    public function datos()
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/chazky.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $id = $this->getRequest()->getParam('id');

        $urlconfig = $this->scopeConfig->getValue('carriers/chazki/chazky_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $keyconfig = $this->scopeConfig->getValue('carriers/chazki/chazky_api', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $storeidconfig = $this->scopeConfig->getValue('carriers/chazki/storeid', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $branchidconfig = $this->scopeConfig->getValue('carriers/chazki/branchid', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $curl = curl_init($urlconfig."chazkiServices/track/select/deliveryCode?code=".$id."&store=".$storeidconfig);                                                                      
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
        $data = array();

        if ($err) {
            $logger->info("CURL Error GET #:" . $err);
        } else {
            if(isset($response['status'])){
                $logger->info($response['status'].": ".$id);
                $data = $response;
            }else{
                $logger->info("Error response invallido GET");
            }
        }


        return $data;
    }

    /**
     * Get form action URL for POST booking request
     *
     * @return string
     */
    public function datoshistorial()
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/chazky.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $id = $this->getRequest()->getParam('id');

        $urlconfig = $this->scopeConfig->getValue('carriers/chazki/chazky_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $keyconfig = $this->scopeConfig->getValue('carriers/chazki/chazky_api', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $storeidconfig = $this->scopeConfig->getValue('carriers/chazki/storeid', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $branchidconfig = $this->scopeConfig->getValue('carriers/chazki/branchid', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $curl = curl_init($urlconfig."chazkiServices/delivery/status/record?code=".$id."&store=".$storeidconfig);                                                                      
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
        $data = array();

        if ($err) {
            $logger->info("CURL Error GET #:" . $err);
        } else {
            if(isset($response['response']) && $response['response']!=99){
                $logger->info($response['response']."(".$response['deliveryResponse']."): ".$id);
                $data = $response;
            }else{
                $logger->info("Error response invallido GET");
            }
        }


        return $data;
    }
    
}
