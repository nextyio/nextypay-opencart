
 * Fredo Tech Software.
 * @category Fredo Tech
 * @package Opencart 3 Module Nexty Payment
 * @author Thang Nguyen https://github.com/bestboyvn87
 * @copyright Copyright (c) 2017-2018 Fredo Software Public (https://nexty.io/)
 * @license https://nexty.io/
 * Opencart v3 supported only
 
# nextypay-opencart

Nexty Cryptocurrency : NTY 
https://coinmarketcap.com/currencies/nexty/

Nextypay-opencart 3 Plugin is one of our Nexty Payment Plugins for E-Commerce.

Supported list: 
* WordPress Woocommerce
* Magento
* Opencart 
* Shopify (coming soon)
* Prestashop (coming soon)

Nexty payment plugin functionality:

* With the nexty payment plugin, customers have more payment options. In detail, the order is changed to NTY and saved in customs tables.
* The currency exchanged time is determined by place order.In the thank you page, ajax is called for 600 seconds and pings every 5 seconds. 
* We get blockchain data using endpoint address in backend settings. 
* In the test phase, rinkeby testnetz will be used and ETH will be used as replacement for NTY. 
* Every transaction with a to-wallet equal to a "walletAddress" in backend settings is then saved. 
* If the payment is enough, Ajax call will be terminated and the order status changed to Complete. 
* In addition, the Thankyou will be reloaded.

Contact: bestboyvn1987@gmail.com
