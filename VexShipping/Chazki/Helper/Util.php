<?php
namespace VexShipping\Chazki\Helper;

class Util
{   

    const CHAZKI_LICENSE_SECRET_KEY = '587423b988e403.69821411';
    const CHAZKI_LICENSE_SERVER_URL = 'https://www.pasarelasdepagos.com';
    const CHAZKI_ITEM_REFERENCE = 'Metodo de envio Chazki - Magento 2';
    private $scopeConfig;
    private $configWriter;
    private $messageManager;
    
    public function __construct( 
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->messageManager = $messageManager;
    }

    public function verify() {

        $license = $this->scopeConfig->getValue('vexsolucioneschazki/general/license', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if( empty( $license ) ){
            $this->disabled();
            return;
        }

        $this->configWriter->save('vexsolucioneschazki/general/activated', '0', 'default', 0);
        $this->configWriter->save('vexsolucioneschazki/general/last_date', '', 'default', 0);
        $activated = $this->scopeConfig->getValue('vexsolucioneschazki/general/activated', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $last_date = $this->scopeConfig->getValue('vexsolucioneschazki/general/last_date', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        
        $current_date = date( 'd/m/Y' );

        if( empty( $last_date ) && $activated ){
            return;
        }

        if( $last_date != $current_date ){
            $this->_verify( $license );
        }
    }

    private function disabled(){
        $this->configWriter->save('vexsolucioneschazki/general/setting', '', 'default', 0);
        $settings = $this->scopeConfig->getValue('vexsolucioneschazki/general/setting', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        
        if( empty( $settings ) ) return;

        $this->configWriter->save('vexsolucioneschazki/general/setting', $settings, 'default', 0);
        $this->configWriter->save('vexsolucioneschazki/general/activated', '0', 'default', 0);

    }

    private function _verify( $license ){

        $curl = curl_init(self::CHAZKI_LICENSE_SERVER_URL."?license_key=".$license."&slm_action=slm_check&secret_key=".self::CHAZKI_LICENSE_SECRET_KEY);                                                                      
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);                                                              
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json"
            )                                                                       
        );
        $response = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        //$response = json_decode($response,true);

        // Check the response code
        //$response_code    = wp_remote_retrieve_response_code( $response );
        //$response_message = wp_remote_retrieve_response_message( $response );

        if ( 200 != $http_status && ! empty( $response ) ) {
            $this->messageManager->addError( __($http_status.' - '.$response) );
            return;
        } elseif ( 200 != $http_status ) {
            $this->messageManager->addError( __('Unexpected Error! The query returned with an error.') );
            return;
        }

        $license_data = json_decode($response,true);

        if( isset($license_data['result']) && $license_data['result'] == 'success' ){
            $this->_activate_plugin( $license_data['message'] );
            return;
        }

        //update_option( 'woocommerce_' . $this->plugin->id . '_activated', '0', 'no' );
        $this->configWriter->save('vexsolucioneschazki/general/activated', '0', 'default', 0);

        //WC_Admin_Settings::add_error( 'Visanet Perú: ' . $license_data->message );
        $this->disabled();
    }

    public function activate( $license ){


        $curl = curl_init(self::VISANET_PE_LICENSE_SERVER_URL."?license_key=".$license."&slm_action=slm_activate&secret_key=".self::VISANET_PE_LICENSE_SECRET_KEY."&item_reference=".urlencode( self::VISANET_PE_ITEM_REFERENCE )."&registered_domain=".$_SERVER['SERVER_NAME']);                                                                      
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);                                                              
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json"
            )                                                                       
        );
        $response = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);


        if ( 200 != $http_status && ! empty( $response ) ) {
            $this->messageManager->addError( __($http_status . ' - ' . $response) );
            return;
        } elseif ( 200 != $http_status ) {
            $this->messageManager->addError( __('Unexpected Error! The query returned with an error.') );

            return;
        }

        $license_data = json_decode($response,true);

        if( isset($license_data['result']) && $license_data['result'] == 'success' ){
            $this->_activate_plugin( $license_data['message'] );
            return;
        }

        $this->messageManager->addError( __($license_data['message']) );

        $this->disabled();
    }

    private function _activate_plugin( $message ){
        $this->configWriter->save('vexsolucioneschazki/general/activated', '1', 'default', 0);
        $this->configWriter->save('vexsolucioneschazki/general/last_date', date( 'd/m/Y' ), 'default', 0);
    }
    
}
