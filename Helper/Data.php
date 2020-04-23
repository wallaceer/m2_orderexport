<?php

namespace Ws\OrderExport\Helper;


class Data{


    protected $_orderFactory;
    protected $orderCollectionFactory;
    protected $_order;



    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderFactory,
        \Magento\Sales\Model\Order $orderData
    ){
        $this->_orderFactory = $orderFactory;
        $this->_order = $orderData;
    }

    public function getOrdersCollection($status, $start, $end){
        try {

            $orderCollection = $this->_orderFactory->create()
                ->addFieldToSelect(
                    '*'
                )->addFieldToFilter(
                    'created_at', ['gteq' => $start]
                )->addFieldToFilter(
                    'created_at', ['lteq' => $end]
                );

            if($status !== 'all'){
                $orderCollection->addFieldToFilter(
                    'status',
                    ['in' => $status]
                );
            }

            return $orderCollection->getData();

        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
            return;
        }
    }


    public function getSelectedConfigurableOption($child_id){

        $sql = "select catalog_product_entity.sku as SKU,catalog_product_entity_varchar.value as Description,
                concat('FR-',LPAD(catalog_product_super_attribute_label.value,10,'0')) as FeatureCode,
                eav_attribute_label.value as QuestionDescription,
                tb3.acode as AnswerCode,tb3.adesc as AnswerDescription
                from catalog_product_super_attribute
                inner join catalog_product_super_attribute_label
                    on catalog_product_super_attribute_label.product_super_attribute_id=catalog_product_super_attribute.product_super_attribute_id
                inner join eav_attribute_label on eav_attribute_label.attribute_id=catalog_product_super_attribute.attribute_id
                inner join catalog_product_entity on catalog_product_super_attribute.product_id=catalog_product_entity.entity_id
                inner join (select * from catalog_product_entity_int
                inner join (select eav_attribute_option_value.option_id,lpad(eav_attribute_option_value.value,10,'0') as acode,
                            tb1.value as adesc
                            from eav_attribute_option_value
                            inner join (select eav_attribute_option_value.option_id,eav_attribute_option_value.value
                                        from eav_attribute_option_value where eav_attribute_option_value.store_id=1) as tb1
                                        on tb1.option_id=eav_attribute_option_value.option_id
                                        where eav_attribute_option_value.store_id=0) as tb2
                            on catalog_product_entity_int.value=tb2.option_id
                            where catalog_product_entity_int.entity_id=$child_id) as tb3
                        on tb3.attribute_id=catalog_product_super_attribute.attribute_id
                inner join catalog_product_entity_varchar on catalog_product_entity_varchar.entity_id=catalog_product_super_attribute.product_id and
                            catalog_product_entity_varchar.attribute_id=73
                where catalog_product_super_attribute.product_id=(select parent_id from catalog_product_relation where child_id=$child_id);
                ";

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource  = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        return $result = $connection->fetchAll($sql);

    }


    public function parseInfoBuyRequest($infodata){
        return $selectedConfigurableOption = isset($infodata['info_buyRequest']['selected_configurable_option']) ? $infodata['info_buyRequest']['selected_configurable_option'] : '';
    }

    public function getConfig($scope)
    {
        try {
            switch($scope){
                case 'store':
                    return $this->scopeConfig->getValue('config/to/path', ScopeInterface::SCOPE_STORE);        // From store view
                case 'website':
                    return $this->scopeConfig->getValue('config/to/path', ScopeInterface::SCOPE_WEBSITE);    // From Website
                default:
                    return $this->scopeConfig->getValue('config/to/path', ScopeInterface::SCOPE_STORE);
            }

        } catch (\Exception $e) {
            return false;
        }
    }


    public function getConfiguredCrontab(){
        return $this->getConfig('ws_orderexport/ws_general/ws_crontab_enable');
    }


}