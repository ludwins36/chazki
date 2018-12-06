<?php 
namespace VexShipping\Chazki\Plugin;


class ConfigPlugin
{
	protected $ciudades;
    protected $extensionFactory;
    protected $request;
    protected $checkoutSession;
    protected $util;
    private $scopeConfig;
 
    public function __construct(
        \VexShipping\Chazki\Helper\Util $util
    ) {
        $this->util = $util;
    }

    public function aroundSave(
        \Magento\Config\Model\Config $subject,
        \Closure $proceed
    ) {
    	$this->util->verify();

    	$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/validador.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("Hola--****************");

        // your custom logic
        return $proceed();
    }
}
