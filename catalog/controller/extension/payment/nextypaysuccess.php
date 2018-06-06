<?php


class ControllerExtensionPaymentNextypaysuccess extends Controller {
  public function strToHex($string){

    	$hex = '';
    	for ($i=0; $i<strlen($string); $i++){
    		$ord = ord($string[$i]);
    		$hexCode = dechex($ord);
    		$hex .= substr('0'.$hexCode, -2);
    	}
    	return strToLower($hex);

    }
	public function index() {
		$this->load->language('extension/payment/nextypaysuccess');
    $data['order_id']=$this->session->data['order_id'];
    /////////////////////////////////////////////////////////
    $this->load->model('checkout/order');

    $data['orderDetails'] = $this->model_checkout_order->getOrder($data['order_id']);
    $data['orderDetails_json'] = json_encode($this->model_checkout_order->getOrder($data['order_id']));

    $nextypay_name='payment_nextypay';
    $nextypay_prefix=$nextypay_name.'_';
    //$data['walletAddress']=$this->config->get($nextypay_prefix.'walletAddress');
    $data['walletAddress']=$this->config->get($nextypay_prefix.'walletAddress');
    $data['currency_code']=$data['orderDetails']['currency_code'];
    $data['total']=$data['orderDetails']['total'];
    $data['store_name']=$data['orderDetails']['store_name'];
    $data['store_url']=$data['orderDetails']['store_url'];
    $data['uoid']=$data['order_id']."_".$data['orderDetails']['store_url'].$data['orderDetails']['store_name'];
    $data['test']=$data['currency_code']." ".$data['total']." ".$data['uoid'];

    $QRtext='{"walletaddress":"'.$data['walletAddress'].'","uoid":"'.$data['uoid'].'","amount":"'.$data['total'].'"}';
    $QRtext_hex="0x".$this->strToHex($QRtext);
    $QRtextencode= urlencode ( $QRtext );
    $data['QRtextencode']=$QRtextencode;
    $data['QRtext']=$QRtext;

    /////////////////////////////////////////////////////////
		if (isset($this->session->data['order_id'])) {
			$this->cart->clear();
/*
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['guest']);
			unset($this->session->data['comment']);
			unset($this->session->data['order_id']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);
			unset($this->session->data['totals']);
      */
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_basket'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_checkout'),
			'href' => $this->url->link('checkout/checkout', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_success'),
			'href' => $this->url->link('extension/payment/nextypaysuccess')
		);

		if ($this->customer->isLogged()) {
			$data['text_message'] = sprintf($this->language->get('text_customer'), $this->url->link('account/account', '', true), $this->url->link('account/order', '', true), $this->url->link('account/download', '', true), $this->url->link('information/contact'));
		} else {
			$data['text_message'] = sprintf($this->language->get('text_guest'), $this->url->link('information/contact'));
		}

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('extension/payment/nextypaysuccess', $data));
	}
}
