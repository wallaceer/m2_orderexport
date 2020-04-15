<?php

namespace Ws\OrderExport\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Magento\Sales\Model\Order;
use \Magento\Framework\App\State;

class manualExport extends Command
{
    /**
     * @var \Magento\Framework\DataObject
     */
    protected $postObject;

    /**
     * @var \Psr\Log\LoggerInterface;
     */
    protected $logger;


    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    private $state;

    protected $_dir;

    private $_order;
    protected $orderCollectionFactory;
    protected $customer;
    protected $_customerRepositoryInterface;

    /* CSV */
    protected $fileFactory;
    protected $csvProcessor;
    protected $directoryList;
    protected $resultRawFactory;

    const STATO = 'stato';
    const START = 'start';
    const END = 'end';
    const TIPO = 'tipo';

    /**
     * @param  \Psr\Log\LoggerInterface $logger,
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\State $state,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\File\Csv $csvProcessor,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
    )
    {
        $state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->state = $state;
        $this->_dir = $dir;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->order = $objectManager->create('\Magento\Sales\Model\Order');
        $this->registry = $objectManager->create('\Magento\Framework\Registry');
        $this->orderCollectionFactory = $objectManager->create('\Magento\Sales\Model\ResourceModel\Order\CollectionFactory');

        $this->fileFactory = $fileFactory;
        $this->csvProcessor = $csvProcessor;
        $this->directoryList = $directoryList;
        $this->resultRawFactory = $resultRawFactory;

        parent::__construct();
    }

    protected function configure()
    {

        $this->setName('orderexport:export');
        $this->setDescription('export orders');

        $this->addArgument(
            self::TIPO,
            InputArgument::OPTIONAL,
            __('Tipo di export (d=detailed/l=list) (default l)'),
            'l'

        );
        $this->addArgument(
            self::STATO,
            InputArgument::OPTIONAL,
            __('Stato ordine (%1) (default all)', implode("/", $this->getOrderStatus())),
            'all'

        );

        parent::configure();

    }

    /**
     * Write to system.log
     *
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {

        if ($tipo = $input->getArgument(self::TIPO)) {
            if($tipo === 'l' || $tipo === 'd'){
                $output->writeln('<info>Provided export type is `' . $tipo . '`</info>');
            }
            else{
                $tipo = 'l';
                $output->writeln('<info>Your digit for export type is incorrect, so provided export type is `' . $tipo . '`</info>');
            }
        }

        if ($stato = $input->getArgument(self::STATO)) {
            if(in_array($stato, $this->getOrderStatus())){
                $output->writeln('<info>Provided order status is `' . $stato . '`</info>');
            }
            else{
                $stato = 'all';
                $output->writeln('<info>Your digit for order status is incorrect, so provided order status is `' . $stato . '`</info>');
            }
        }

        $output->writeln('*** START order export ' . date('Y-m-d H:i:s'));

        $this->logger->info('Start order export cronjob');

        try {



            $finalOrderData = $finalOrderData2 = [];

            //Ipotiziamo di leggere soltanto i nuovi ordini
            $orderCollection = $this->orderCollectionFactory->create()
                ->addFieldToSelect(
                '*'
                )->addFieldToFilter(
                    'status',
                    ['in' => $stato]
                ); // obtain all orders

            $ordersList = $orderCollection->getData();

            foreach($ordersList as $orderData){

                //Order Items
                $order = $this->order->load($orderData['entity_id']);
                $orderItems = $order->getAllItems();
                $shippingAdress['shipping_data'] = $order->getShippingAddress();
                $billingAddress['billing_data'] = $order->getBillingAddress();

                $orderData = array_merge($orderData, $shippingAdress);
                $orderData = array_merge($orderData, $billingAddress);

                //Righe ordine
                foreach ($orderItems as $item) {
                    $finalOrderData[] = array_merge($orderData, $item->getData());
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
             * One Csv for every order
             */
            if($tipo === 'd') $this->toCsv($finalOrderDataForSingleCsvPerOrder);

            /**
             * One Csv for all orders
             */
            $this->toCsvSingleFile($finalOrderDataForOneCsv);

            $output->writeln('*** END test Order Export Cron ' . date('Y-m-d H:i:s'));

        } catch (\Exception $e) {
            $output->writeln('*** Error ' . $e->getMessage());
            $this->logger->info($e->getMessage());
            return;
        }
    }


    /**
     * Get Customer by id
     */
    public function getCustomer($customerId)
    {
        return $this->_customerRepositoryInterface->getById($customerId);
    }


    /**
     * Create csv file
     * @param $data
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function toCsv($data){

        //Data's array for csv
        $newData = [];

        $header = [];
        foreach($data as $orderId=>$orderData){
            foreach($orderData as $k=>$v){
                foreach($v as $i=>$vv){
                    $header[] = $i;
                }
                break;
            }
            break;
        }

        $newData[] = $header;

        //one file for each order
        foreach($data as $orderId=>$orderData){
            array_unshift($orderData, $header);
            $fileName = 'exportOrder_'.$orderId.'_'.date("YmdHis").'.csv';
            $this->createCsvFile($fileName, $orderData);
        }
    }

    /**
     * Create csv file
     * @param $data
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function toCsvSingleFile($data){

        //Data's array for csv
        $newData = [];

        //Temporary array
        $aRighe = [];

        foreach($data as $index=>$values){
            //Temporary array
            $nRighe = $aTesta = [];
            foreach($values as $testa=>$righe){
                $aTesta[] = $testa;
                $nRighe[] = $righe;
            }
            $aRighe[] = $nRighe;
        }

        //Set csv header
        $newData = [$aTesta];
        //Set csv rows data
        foreach($aRighe as $riga){
            array_push($newData, $riga);
        }

        //print_r($newData);
        $fileName = 'orders_list.csv';
        $this->createCsvFile($fileName, $newData);


    }

    protected function createCsvFile($filename, $data){

        $filePath = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR)
            . "/" . $filename;

        $this->csvProcessor
            ->setDelimiter(',')
            ->setEnclosure('"')
            ->saveData(
                $filePath,
                $data
            );

        return $this->fileFactory->create(
            $filename,
            [
                'type' => "filename",
                'value' => $filename,
                'rm' => false,
            ],
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,
            'text/csv',
            null
        );
    }

    protected function getOrderStatus(){
        return ['processing','fraud','pending_payment','payment_review','pending','holded','STATE_OPEN','complete','closed','canceled','paypay_canceled_reversal','pending_paypal','paypal_reversed','all'];
    }

}