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

    /////////////////////////////////////////////////////////
    $this->load->model('checkout/order');

    if (!isset($this->session->data['order_id'])) exit;
    $data['order_id']=$this->session->data['order_id'];

    $data['orderDetails'] = $this->model_checkout_order->getOrder($data['order_id']);
    $data['orderDetails_json'] = json_encode($this->model_checkout_order->getOrder($data['order_id']));

    $nextypay_name='payment_nextypay';
    $nextypay_prefix=$nextypay_name.'_';
    //$data['walletAddress']=$this->config->get($nextypay_prefix.'walletAddress');
    $data['walletAddress']=$this->config->get($nextypay_prefix.'walletAddress');
    $data['min_blocks_saved_db']=$this->config->get($nextypay_prefix.'min_blocks_saved_db');
    $data['max_blocks_saved_db']=$this->config->get($nextypay_prefix.'max_blocks_saved_db');
    $data['blocks_loaded_each_request']=$this->config->get($nextypay_prefix.'blocks_loaded_each_request');

    $data['currency_code']=$data['orderDetails']['currency_code'];
    $data['total']=$data['orderDetails']['total'];
    $data['store_name']=$data['orderDetails']['store_name'];
    $data['store_url']=$data['orderDetails']['store_url'];
    $data['order_id_prefix']=$data['orderDetails']['store_url'].$data['orderDetails']['store_name'];
    $data['uoid']=$data['order_id']."_".$data['order_id_prefix'];
    $data['test']=$data['currency_code']." ".$data['total']." ".$data['uoid'];

    $QRtext='{"walletaddress":"'.$data['walletAddress'].'","uoid":"'.$data['uoid'].'","amount":"'.$data['total'].'"}';

    //Get help-functions
    $this->load->library('nextypayblockchain');
    $obj_blockchain = Nextypayblockchain::get_instance($this->registry);

    $this->load->library('nextypayexchange');
    $obj_exchange = Nextypayexchange::get_instance($this->registry);

    $this->load->library('nextypayfunctions');
    $obj_functions = Nextypayfunctions::get_instance($this->registry);

    $this->load->library('nextypayupdatedb');
    $obj_updatedb = Nextypayupdatedb::get_instance($this->registry,$obj_blockchain,$obj_functions);

    $obj_updatedb->set_connection($this->db);
    $obj_updatedb->set_includes($obj_blockchain,$obj_functions);
    $obj_updatedb->set_backend_settings(DB_PREFIX."nextypay_",$data['currency_code'],$data['walletAddress'],$data['order_id_prefix'],
    $data['min_blocks_saved_db'],$data['max_blocks_saved_db'],  $data['blocks_loaded_each_request']);

  //$obj_updatedb->updatedb();

    //////////////////////////////////////////////////////////////////
    $QRtext_hex="0x".$obj_functions->strToHex($QRtext);
    $QRtextencode= urlencode ( $QRtext );
    $data['QRtextencode']=$QRtextencode;
    $data['QRtext']=$QRtext;
    $data['QRtext_hex']=$QRtext_hex;
    $data['test']=$obj_updatedb->updatedb();
    //$data['test']=json_encode($data['test']);

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
