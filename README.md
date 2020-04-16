# Magento 2 orders export with command and cron

Whit this module you can export the orders registered in Magento 2.

The export create
- one csv file for every order containing order detail
- one csv file containing all order

By default the module saving csv files in `var/` magento's directory.

There are two way to generate the exports
* from CLI 
* from crontab

## How to generate the csv from CLI
In the Magento 2 CLI there is the command 
`bin/magento orders:export ` with this options
* the type of export, `d` detailed or `l` list; 
    - detailed generate one csv file for list of orders and one file for each order with order detail
    - list generate only one file with the list of orders
* the order status used for filtering orders collection
* `start` and `end` date for filtering orders collection; this two dates are relatives to created_at field     

## How to generate the csv from contrab 
By default the module set a cron job to runnig every day at 00:01 hours in detailed mode
The job get all orders created on the current day