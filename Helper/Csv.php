<?php

namespace Wl\OrderExport\Helper;

class Csv{

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;
    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $csvProcessor;
    /**
     * @var
     */
    protected $fileFactory;



    public function __construct(
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\File\Csv $csvProcessor,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    )
    {
        $this->directoryList = $directoryList;
        $this->csvProcessor = $csvProcessor;
        $this->fileFactory = $fileFactory;
    }


    public function toCsv($data){

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

        //one file for each order
        foreach($data as $orderId=>$orderData){
            array_unshift($orderData, $header);
            $fileName = 'exportOrder_'.$orderId.'_'.date("YmdHis").'.csv';
            $this->createCsvFile($fileName, $orderData);
        }

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


}