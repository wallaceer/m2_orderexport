<?php

namespace Wl\OrderExport\Cron;


use \Wl\OrderExport\Helper\Csv;
use \Wl\OrderExport\Helper\Data;


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

        $this->_csv->toCsv($data);

        $this->logger->info('End orderExport cronjob');

    }


}