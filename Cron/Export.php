<?php

namespace Reply\OrderExport\Cron;


use \Reply\OrderExport\Helper\Csv;
use \Reply\OrderExport\Helper\Data;


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
    }


    public function execute(){

        $this->logger->info('Start orderExport cronjob');

        $data = $this->_data->getData();

        //Create order's csv
        $this->_csv->toCsv($data);
        //Create product's configuration file
        foreach($data as $d){
            foreach($d as $value){
                if(isset($value['configurationId']) && $value['configurationId']!=''){
                    $confData = $this->_data->getSelectedConfigurableOption($value['configurationId']);
                    $this->_csv->toCsvConfiguration($confData, $value['order_id'], $value['configurationId']);
                }
            }
        }


        $this->logger->info('End orderExport cronjob');

    }


}