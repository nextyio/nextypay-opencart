<?php
class ModelExtensionPaymentNextypay extends Model {
  public function getMethod($address, $total) {
    $this->load->language('extension/payment/nextypay');

    $method_data = array(
      'code'     => 'nextypay',
      'title'    => $this->language->get('text_logo').$this->config->get( 'payment_nextypay_title'),
      'sort_order' => $this->config->get('payment_nextypay_sort_order')
    );

    return $method_data;
  }
}
