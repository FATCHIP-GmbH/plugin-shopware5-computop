# Changelog - Shopware Computop Payment Connector

## 1.0.26
Pre-Released 2019-08-27
* Added: SW 5.6 compatibility
* Added: Amazon SCA confirmationFlow
* Added: CreditCard Testmode

## 1.0.24
Pre-Released 2019-08-08
* Fixed: Completing invalid AmazonPay Orders using browser reload function
* Added: AmazonPay Decline-Handling
* Added: 3D-Secure v2

## 1.0.23
* fixed errors in attribute getters when users have no attributes 

## 1.0.22
Released 2019-06-13
* Remove deprecated constants Shopware::VERSION since SW 5.5
* Import 2nd address line when using AmazonPay
* fixed rounding bug with PHP => 7.1 leading to wrong amount in APILog

## 1.0.21
Released 2019-03-28
* Remove scrutinizer
* Config fields prefixOrdernumber and suffixOrdernumber are not required

## 1.0.20
Released 2019-03-21
* Fix: manual capture if prefix or suffix is set

## 1.0.19
Released 2019-03-20
* Fix: Removed warnings regarding not existing Readme.html / thx to bragento

## 1.0.18
Released 2019-02-15
* Fix: AmazonPay failed if birthday field is setup as required for registration
* Removed duplicate readme file 

## 1.0.17
Released 2019-02-06
* Shop version nummer not always transmitted was fixed
* Refactored auto-capture-process
* Additional parameter for Amazon payment

## 1.0.16
Released 2019-01-03
* Add composer compatibility

## 1.0.15
Released 2018-12-14
* Fix minimumBasketValue

## 1.0.12
Released 2018-10-29
* Fix submodule ref

## 1.0.11
Released 2018-10-29
* Deactivate afterpay config form 

## 1.0.10
Released 2018-10-29
* Use only numeric transID

## 1.0.9
Released 2018-10-29
* Use only 12 digits transID

## 1.0.7
Released 2018-09-12
* Added version switch for billing address

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
