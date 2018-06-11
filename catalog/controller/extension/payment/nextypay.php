<?php
class ControllerExtensionPaymentNextypay extends Controller {
	public function index() {
		$this->load->language('extension/payment/nextypay');

	//	$data['bank'] = nl2br($this->config->get('payment_bank_transfer_bank' . $this->config->get('config_language_id')));
    $nextypay_name='payment_nextypay';
    $nextypay_prefix=$nextypay_name.'_';
    $data['instruction']=$this->config->get($nextypay_prefix.'instruction');
		$data['description']=$this->config->get($nextypay_prefix.'description');
		$data['walletAddress']=$this->config->get($nextypay_prefix.'walletAddress');
		$data['exchangeAPI']=$this->config->get($nextypay_prefix.'exchangeAPI');
		$data['order_status_id']=$this->config->get('payment_nextypay_order_status_id');

		$data['order_id']=$this->session->data['order_id'];
		$this->load->model('checkout/order');

		$data['orderDetails'] = $this->model_checkout_order->getOrder($data['order_id']);
		$data['orderDetails_json'] = json_encode($this->model_checkout_order->getOrder($data['order_id']));

		//$data['walletAddress']=$this->config->get($nextypay_prefix.'walletAddress');
		$data['currency_code']=$data['orderDetails']['currency_code'];
		$data['total']=$data['orderDetails']['total'];
		$data['store_name']=$data['orderDetails']['store_name'];
		$data['store_url']=$data['orderDetails']['store_url'];
		$data['uoid']=$data['order_id']."_".$data['orderDetails']['store_url'].$data['orderDetails']['store_name'];


		return $this->load->view('extension/payment/nextypay', $data);
	}

	public function confirm() {
		$json = array();

		if ($this->session->data['payment_method']['code'] == 'nextypay') 
		{
			$this->load->language('extension/payment/nextypay');

			$this->load->model('checkout/order');

			$comment  = $this->language->get('text_instruction') . "\n\n";
			$comment .= $this->language->get('text_payment');

			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_nextypay_order_status_id'), $comment,true);

			$json['redirect'] = $this->url->link('extension/payment/nextypaysuccess');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
