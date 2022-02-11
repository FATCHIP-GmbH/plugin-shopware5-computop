# Shopware First Cash Solution Payment Connector

Licence GPLv3

## About Shopware First Cash Solution Payment Connector
For details about the plugin please visit [our website](https://www.fatchip.de/Plugins/Shopware/Shopware-First Cash Solution-Payment-Connector.html).
This plugin is also available at [Shopware Community Store](https://store.shopware.com/fatch19156119985f/shopware-computop-payment-connector.html).

In order to use First Cash Solution Payments via this plugin, you'll need a contract with First Cash Solution. Please contact sales via the form on https://www.computop.com/de/. In case you are already a customer of First Cash Solution, we kindly ask you to get in touch with your contact person. Following that, you'll receive your access data with which you'll be able to use the Shopware plugin. You will then be able to to use First Cash Solution payments in your shop.


## Installation and usage
Visit our Wiki pages to read the [plugin documentation](https://wiki.fatchip.de/public/shopware-fatchipctpayment).

## Adding a payment method
### Reference implementations
* KlarnaPayments
* AmazonPay (Express)
* Paypal Express (Express)

### Add payment class
Payment classes do contain specefic params and additional requests for payment method if needed.

Folder: Components/Api/lib/FCSPayment/CTPaymentMethods

### Add payment controller
Does contain gateway action and specific actions if needed.

Folder: Controllers/Frontend
Registration in: Subscribers/ControllerPath.php

### Add Event Subscriber
For extend views or listening to Shopware events

Folder: Subscribers/Frontend
Registration in: Bootstrap.php

## Author
FATCHIP GmbH | https://www.fatchip.de | support@fatchip.de 

## License
Please see the [License File](LICENSE.md) for more information.

## Credits
This plugin makes use of the following projects:

* [jQuery](https://js.foundation)
* [Ext JS 4.1](http://cdn.sencha.com/ext/gpl/4.1.1/) by Sencha


We express our thanks to their developers, designers and other contributers for their great work!
