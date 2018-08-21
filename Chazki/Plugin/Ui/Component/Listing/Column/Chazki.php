<?php
namespace Chazki\Plugin\Ui\Component\Listing\Column;

use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Store\Model\StoreManagerInterface;

class Chazki extends Column
{
    
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource["data"]["items"])) {
            foreach ($dataSource["data"]["items"] as & $item) {
                if (isset($item['entity_id'])) {

                    $id = $item["entity_id"];

                    $item[$this->getData('name')] = [
                        'edit' => [
                            "href"=>$this->getContext()->getUrl(
                                "chazki/chazki/index",["id"=>$id]),
                            "label"=>__("Ver pedido")
                        ],
                    ];
                }
            }
        }    
        return $dataSource;
    }
}
