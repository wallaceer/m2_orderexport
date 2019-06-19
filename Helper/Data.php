<?php

namespace Reply\OrderExport\Helper;


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

    public function getData(){
        try {

            $finalOrderData = $finalOrderData2 = [];

            //Ipotiziamo di leggere soltanto i nuovi ordini
            $orderCollection = $this->_orderFactory->create()
                ->addFieldToSelect(
                    '*'
                )->addFieldToFilter(
                    'status',
                    ['in' => 'payment_review']
                )->addFieldToFilter(
                    'created_at', ['gteq' => date("Y-m-d 00:00:00")]
                )->addFieldToFilter(
                    'created_at', ['lteq' => date("Y-m-d 23:59:59")]
                ); // obtain all orders

            $ordersList = $orderCollection->getData();

            foreach($ordersList as $orderData){

                //Order Items
                $order = $this->_order->load($orderData['entity_id']);
                $orderItems = $order->getAllItems();
                $billingAddress['billing_data'] = $order->getBillingAddress() ? $order->getBillingAddress()->getData() : '';
                $shippingAdress['shipping_data'] = $order->getShippingAddress() ? $order->getShippingAddress()->getData() : $order->getBillingAddress()->getData();

                $orderData = array_merge($orderData, $shippingAdress);
                $orderData = array_merge($orderData, $billingAddress);

                //Righe ordine
                foreach ($orderItems as $item) {
                    $finalOrderData[] = array_merge($orderData, $item->getData());
                    $finalOrderData2[$item['order_id']][] = [
                        'item_id' =>$item['item_id'],
                        'order_id' =>$item['order_id'],
                        'state' => $orderData['state'],
                        'state' => $orderData['status'],
                        'status' => $orderData['status'],
                        'store_id' => $item['store_id'],
                        'customer_id' => $orderData['customer_id'],
                        'customer_group_id' => $orderData['customer_group_id'],
                        'customer_email' => $orderData['customer_email'],
                        'customer_firstname' => $orderData['customer_firstname'],
                        'customer_lastname' => $orderData['customer_lastname'],
                        'billing_firstname' => $orderData['billing_data']['firstname'],
                        'billing_lastname' => $orderData['billing_data']['lastname'],
                        'billing_street' => $orderData['billing_data']['street'],
                        'billing_postcode' => $orderData['billing_data']['postcode'],
                        'billing_city' => $orderData['billing_data']['city'],
                        'billing_region' => $orderData['billing_data']['region'],
                        'billing_telephone' => $orderData['billing_data']['telephone'],
                        'billing_country_id' => $orderData['billing_data']['country_id'],
                        'billing_email' => $orderData['billing_data']['email'],
                        'billing_address_type' => $orderData['billing_data']['address_type'],
                        'billing_company' => $orderData['billing_data']['company'],
                        'billing_vat_id' => $orderData['billing_data']['vat_id'],
                        'shipping_firstname' => $orderData['shipping_data']['firstname'],
                        'shipping_lastname' => $orderData['shipping_data']['lastname'],
                        'shipping_street' => $orderData['shipping_data']['street'],
                        'shipping_postcode' => $orderData['shipping_data']['postcode'],
                        'shipping_city' => $orderData['shipping_data']['city'],
                        'shipping_region' => $orderData['shipping_data']['region'],
                        'shipping_telephone' => $orderData['shipping_data']['telephone'],
                        'shipping_country_id' => $orderData['shipping_data']['country_id'],
                        'shipping_email' => $orderData['shipping_data']['email'],
                        'shipping_address_type' => $orderData['shipping_data']['address_type'],
                        'shipping_company' => $orderData['shipping_data']['company'],
                        'shipping_vat_id' => $orderData['shipping_data']['vat_id'],
                        'coupon_code' => $orderData['coupon_code'],
                        'discount_amount' => $orderData['discount_amount'],
                        'discount_description' => $orderData['discount_description'],
                        'grand_total' => $orderData['grand_total'],
                        'shipping_method' => $orderData['shipping_method'],
                        'shipping_description' => $orderData['shipping_description'],
                        'shipping_amount' => $orderData['shipping_amount'],
                        'shipping_tax_amount' => $orderData['shipping_tax_amount'],
                        'subtotal' => $orderData['subtotal'],
                        'tax_amount' => $orderData['tax_amount'],
                        'total_qty_ordered' => $orderData['total_qty_ordered'],
                        'total_due' => $orderData['total_due'],
                        'order_currency_code' => $orderData['order_currency_code'],
                        'customer_note' => $orderData['customer_note'],
                        'shipping_incl_tax' => $orderData['shipping_incl_tax'],
                        //Dati prodotti
                        'sku' =>$item['sku'],
                        'name' =>$item['name'],
                        'id_variante' =>$item['id_variante'],
                        'qty_ordered' =>$item['qty_ordered'],
                        'price' =>$item['price'],
                        'row_total' =>$item['row_total'],
                        'created_at' => $orderData['created_at'],
                        'updated_at' => $orderData['updated_at'],
                        'configurationId' => $this->parseInfoBuyRequest($item['product_options'])
                    ];

                }
            }

            return $finalOrderData2;

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

}