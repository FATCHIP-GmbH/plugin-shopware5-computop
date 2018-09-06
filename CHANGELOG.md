# Changelog - Shopware Computop Payment Connector

## 1.0.6
Released 2018-09-6
* bugfix: Fixed wrong capture and refund amount calculation for net orders 

## 1.0.5
Released 2018-08-3
* bugfix: Fixed credit card iframe problem occured with Shopware 5.4.3

## 1.0.4
Released 2018-07-24
* bugfix: fixed expected response status for cc payment

## 1.0.3
Released 2018-06-12
* bugfix: orderDesc Parameter now uses the correct subshop name 
* feature: added backend option to anonymize IBAN everywhere

## 1.0.2
Released 2018-06-25
* added fetature to show iban anonymized on comfirm and account page
* added custom prefix and suffix for order numbers
* there are 3 placeholders you can use in prefixes and suffixes:
  - %transid% -> gets replaces with computop transactionid
  - %payid% -> gets replaces with computop payid
  - %xid% -> gets replaces with computop xid

## 1.0.1
Released 2018-05-15
* some wording and consistency changes 

## 1.0.0
Released 2018-05-15
* initial release
