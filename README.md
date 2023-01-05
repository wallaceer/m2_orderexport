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
`bin/magento order:export ` with this options
* the type of export, `d` detailed or `l` list; 
    - detailed generate one csv file for list of orders and one file for each order with order detail
    - list generate only one file with the list of orders
* the order status used for filtering orders collection
* `start` and `end` date for filtering orders collection; this two dates are relatives to created_at field     

Example.
bin/magento order:export l all 2020-01-01 2020-12-31

## How to generate the csv from contrab 
By default the module set a cron job to runnig every day at 00:01 hours in detailed mode
The job get all orders created on the current day

## Setup

You can install this module via Composer or manual setup.
To install it with composer you can insert this rows in your magento's composer.json
```
"require": {
	"ws/orderexport": "1.0.*"
    },
```
```
"repositories": {
	"m2_orderexport":{
            "type": "git",
            "url": "git@github.com:wallaceer/m2_orderexport.git"
        }
    }
```
  
After edited composer.json 
- launch composer update
- verify the module status with `bin/magento module:status | grep Ws_Orderexport`
- enable the module, if necessary, with `bin/magento module:enable Ws_Orderexport`
- run bin/magento setup:upgrade
    
For a manual installation:
* copy the module in your magento `app/code`
* run `bin/magento setup:upgrade`
* verify the module status with `bin/magento module:status | grep Ws_Orderexport`


In every case remember to launch the command `bin/magento setup:upgrade` for cleaning the cache


## Note
This module was developed with Magento 2.3.4 CE   
