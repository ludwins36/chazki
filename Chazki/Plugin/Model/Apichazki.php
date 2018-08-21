<?php
namespace Chazki\Plugin\Model;
use Chazki\Plugin\Api\ApichazkiInterface;
 
class Apichazki implements ApichazkiInterface
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
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Returns greeting message to user
     *
     * @api
     * @param string $name Users name.
     * @return string Greeting message with users name.
     */
    public function getestado() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();  
        $request = $objectManager->get('Magento\Framework\App\Request\Http');
        $customer_id = $request->getParam('customer_id');
        $id = $request->getParam('order_id');
        


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

        $msg = '';
        $status = 1;
        $data = array();

        if ($err) {
            $msg = $err;
            $status = 0;
        } else {
            if(isset($response['response']) && $response['response']!=99){
                $data = $response;
            }else{
                $status = 0;
                $msg = "Error response invallido GET";
            }
        }


        $response=array(array(
            'status' => $status,
            'result' => $data,
            'error_message' => $msg
        ));
        return $response;
    }
    
    
}