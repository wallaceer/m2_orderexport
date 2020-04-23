<?php

namespace Ws\OrderExport\Cron;


use \Ws\OrderExport\Helper\Csv;
use \Ws\OrderExport\Helper\Data;


class Export{

    /**
     * @var \Psr\Log\LoggerInterface;
     */
    protected $logger;
    /**
     * @var Csv
     */
    protected $_csv;

    protected $_data;


    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        Csv $csv,
        Data $data
    )
    {
        $this->logger = $logger;
        $this->_csv = $csv;
        $this->_data = $data;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->order = $objectManager->create('\Magento\Sales\Model\Order');
    }


    public function execute(){

        if($this->_data->getConfiguredCrontab() !== 1){
            throw new \Exception('Crontab disabled from admin!');
        }

        /**
         * Boolean var for empty result
         */
        $res = 0;

        $this->logger->info('Start orderExport cronjob');

        $ordersList = $this->_data->getOrdersCollection('all', date("Y-m-d 00:00:01"), date("Y-m-d 23:59:59"));

        foreach($ordersList as $orderData){

            //Order Items
            $order = $this->order->load($orderData['entity_id']);
            $orderItems = $order->getAllItems();
            $shippingAdress['shipping_data'] = $order->getShippingAddress();
            $billingAddress['billing_data'] = $order->getBillingAddress();

            $orderData = array_merge($orderData, $shippingAdress);
            $orderData = array_merge($orderData, $billingAddress);

            foreach ($orderItems as $item) {
                /**
                 * Data for single csv for every order
                 */
                $finalOrderDataForSingleCsvPerOrder[$item['order_id']][] = [
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
                    'updated_at' => $orderData['updated_at']
                ];

                /**
                 * Data for one file for all orders
                 */
                $finalOrderDataForOneCsv[] = [
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
                    'updated_at' => $orderData['updated_at']
                ];
            }
        }

        /**
         * Single csv for every order
         */
        if(isset($finalOrderDataForSingleCsvPerOrder)){
            $this->_csv->toCsv($finalOrderDataForSingleCsvPerOrder);
            $res = 1;
        }


        /**
         * Single csv for all orders
         */
        if(isset($finalOrderDataForOneCsv)){
            $this->_csv->toCsvSingleFile($finalOrderDataForOneCsv);
            $res = 1;
        }

        //Create product's configuration file
        /*foreach($data as $d){
            foreach($d as $value){
                if(isset($value['configurationId']) && $value['configurationId']!=''){
                    $confData = $this->_data->getSelectedConfigurableOption($value['configurationId']);
                    $this->_csv->toCsvConfiguration($confData, $value['order_id'], $value['configurationId']);
                }
            }
        }*/

        if($res == 0){
            throw new \Exception('No orders to export');
        }

        $this->logger->info('End orderExport cronjob');

    }


}