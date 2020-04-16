<?php

namespace Ws\OrderExport\Helper;

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


    /**
     * Create csv file
     * @param $data
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
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
    public function toCsvSingleFile($data){

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


}