# Changelog - Shopware Computop Payment Connector

## 1.1.10
2022-12-05
* Added fallback for empty house number on address splitting exception

## 1.1.9
2022-11-09
* Added check for all payments to make sure all products in basket are still available

## 1.1.8
2022-10-24
* Added handling of AmazonPay partial refunds
* Fixed Paypal order status not set due to variable re-use 

## 1.1.7
2022-10-11
* Fixed a problem with credit card default mode

# 1.1.6
2022-09-19
* Added payment page as option for credit card mode
* Fixed Refund with Amazon Pay, if capture was done manually
* Fixed payment status for Amazon Pay, if capture mode is set to manual

# 1.1.5
2022-08-24
* Fixed session backward compatibility with SW < 5.7
* Fixed payment status for successful orders using iDeal and Sofort 
* Prevent JS-Errors in console when name splitting fails

# 1.1.4
2022-07-18
* Updated compatibility with AboCommerce (Credit Card, Paypal, Direct Debit)
* Removed unnecessary external call used for hidden afterpay installment
* Replacement of the paydirekt logo
* Fixed generation years in birthday dropdown

# 1.1.3
2022-04-06
* Removed Afterpay Installment
* Added advanced logging
* Fixed address handling of PayPal Express

# 1.1.2
2022-03-24
* Fixed issues with PHP 8.0
* Fixed payment logos during checkout process
* Changed default credit card template to correct template

# 1.1.1
2022-03-15
* Problem solved with PayPal Express when birthday is not transferred (standard)
* Fixed address splitting when no street number is provided
* Fixed EasyCredit

# 1.1.0
2022-02-28
* Added better compatibility with other payment plugins
* Fixed problems with Paypal Express and phone numer/birthday when configured mandatory
* Fixed problems with Amazon Pay and phone numer/birthday when configured mandatory

# 1.0.50
2022-02-07
* Added language parameter for iFrame 

# 1.0.49
2022-02-03
* Added missing translations
* Added Paypal Express fallback button

# 1.0.48
2022-01-17
* Added multi language support
* Fixed removal of leading zeroes in Paypal Express and AmazonPay zip codes and phone numbers
* Fixed credit card payments failing in some cases depending on browser colorDepth
* Fixed backend exception on capture / refund
* Fixed help text for credit card silent mode
* Added support for schemeReferenceID parameter
* Added update of attribute model on plugin update

# 1.0.47
2021-11-18
* display schemeReferenceID in backend API log
* transmit schemeReferenceID in capture and refund requests when available

# 1.0.46
2021-11-08
* Added option for PayPal Standard order status

# 1.0.45
2021-10-18
* Fixed parameter EtiID

# 1.0.44
2021-09-23
* Fixed "call to a member function getAmount() on array" exception

# 1.0.43
2021-09-17
* Added option for capture mode for Amazon Pay

# 1.0.42
2021-07-12
* Added option for credit card verification

# 1.0.41
2021-06-16
* fixed session restore for SW 5.7.0 and higher

# 1.0.40
2021-05-28
* removed No order available within Notify log spam
* fixed string conversion exception with paypal express and php 7.4

# 1.0.39
2020-11-24
* fixed a bug in silentmode preventing error display when session was lost after returning to shop
* fixed CSRF Token error in silent mode when using 3dSecure 2.0
* added support for credentialOnFile Parameter for creditcard payments

# 1.0.38
2020-09-30
Fixed wrong submodule reference in main module

## 1.0.37
2020-09-25
* Fixed transferring of correct IP-Address
* Added workaround for creditcard iframe payments for safari causing lost usersessions

## 1.0.36
2020-09-09
* Fixed subscriber leading to address changes no longer possible in backend

## 1.0.35
2020-08-11
* Fixed capture with klarna was not possible
* Fixed issues with Afterpay
* Removal of payment methods Mobile Pay and ideal via Sofort
* Fixed iFrames for payments with Klarna

## 1.0.34
2020-05-19
* Fixed PayPal Express: Only mark order a fully paid, after capture.
* Fixed PayPal Express: Respect config setting for caption

## 1.0.33
2020-03-03
* Fixed: no interference with external Klarna plugin

## 1.0.32
2020-02-11
* Fixed: RefNrChanges will be only called once
* added: new params browserInfo and CreditCardholder for creditcard payments in silent mode
* added: PPRO provider for iDEAL payments

## 1.0.31
Released 2020-01-16
* Fixed: Removed test code 

## 1.0.30
Released 2020-01-16
* Handle capture response

## 1.0.29
Released 2020-01-06
* Refactoring

## 1.0.28
Pre-Released 2019-12-10
* Rebranding: updated Computop icons and images 
* Refactored: unneccessary code removed
* Fixed: attribute address flush / by @rdss-zmehmedovic
* Added two new Klarna payment methods: Klarna SOFORT, Klarna Lastschrift 
* Fixed: Amazon Pay address selection
* Added: Configuration options for active credit card brands in silent mode

## 1.0.27
Pre-Released 2019-10-21
* Added: three new Klarna payment methods: Pay Later, Pay Now, Slice It

## 1.0.26
Released 2019-09-05
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
