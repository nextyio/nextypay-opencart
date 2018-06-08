<?php
/**
 * Fredo Software.
 *
 * @category Fredo
 * @package Opencart Module Nexty Payment
 * @author Thang Nguyen
 * @copyright Copyright (c) 2017-2018 Fredo Software Public (https://nexty.io/)
 * @license https://nexty.io/
 */
/**
 * The controller class must extend the parent class i.e. Controller
 * The controller name must be like Controller + directory path (with first character of each folder in capital) + file name (with first character in capital)
 * For version 3.0.0.0 and upper, the name of the controller must be ControllerExtensionModuleFirstModule
 */
 class ControllerExtensionPaymentNextypay extends Controller {

   private $error = array();
   public $nextypay_name='payment_nextypay';

   public function install() {
      $this->load->language('extension/payment/nextypay');
      $this->document->setTitle($this->language->get('heading_title'));
      $this->load->model('extension/payment/nextypay');
      $this->load->model('setting/extension');
      //$nextypay_name='payment_nextypay';

      //$this->load->model('extension/payment');

      if ($this->install_validate()) {
        //create custom tables
        $this->model_extension_payment_nextypay->install();
        //create OC tables
        $this->model_setting_extension->install("payment",$this->nextypay_name);
      }

    }

    public function uninstall() {
    //$nextypay_name='payment_nextypay';
    $this->load->language('extension/payment/nextypay');
    $this->document->setTitle($this->language->get('heading_title'));
    $this->load->model('extension/payment/nextypay');
    $this->load->model('setting/extension');

    //$this->load->model('extension/payment');

    if ($this->install_validate()) {
      //delete custom tables
      $this->model_extension_payment_nextypay->uninstall();
      //delete OC tables
      $this->model_setting_extension->uninstall("payment",$this->nextypay_name);
    }

  }

  private function set_navi(&$data){
    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_extension'),
      'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('extension/payment/nextypay', 'user_token=' . $this->session->data['user_token'], true)
    );
  }

  private function set_backend_template(&$data){

    $data['heading_title'] = $this->language->get('heading_title');

    $data['entry_title'] = $this->language->get('entry_title');
    $data['entry_order_status'] = $this->language->get('entry_order_status');
    $data['entry_description'] = $this->language->get('entry_description');
    $data['entry_instruction'] = $this->language->get('entry_instruction');
    $data['entry_walletAddress'] = $this->language->get('entry_walletAddress');
    $data['entry_exchangeAPI'] = $this->language->get('entry_exchangeAPI');
    $data['entry_endPointAddress'] = $this->language->get('entry_endPointAddress');
    $data['entry_min_blocks_saved_db'] = $this->language->get('entry_min_blocks_saved_db');
    $data['entry_max_blocks_saved_db'] = $this->language->get('entry_max_blocks_saved_db');
    $data['entry_blocks_loaded_each_request'] = $this->language->get('entry_blocks_loaded_each_request');

    $data['button_save'] = $this->language->get('text_button_save');
    $data['button_cancel'] = $this->language->get('text_button_cancel');
    $data['text_enabled'] = $this->language->get('text_enabled');
    $data['text_disabled'] = $this->language->get('text_disabled');
    $data['entry_status'] = $this->language->get('entry_status');

    $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);
    $data['action'] = $this->url->link('extension/payment/nextypay', 'user_token=' . $this->session->data['user_token'], 'SSL');

  }

  private function set_errors(&$data,$error){
    if (isset($error['walletAddress_warning'])) {
        $data['error_walletAddress_warning'] = $error['walletAddress_warning'];
    } else {
        $data['error_walletAddress_warning'] = '';
    }

    if (isset($error['exchangeAPI_warning'])) {
       $data['error_exchangeAPI_warning'] = $error['exchangeAPI_warning'];
    } else {
       $data['error_exchangeAPI_warning'] = '';
    }

    if (isset($error['endPointAddress_warning'])) {
       $data['error_endPointAddress_warning'] = $error['endPointAddress_warning'];
    } else {
       $data['error_endPointAddress_warning'] = '';
    }

    if (isset($error['blocks_info_warning'])) {
       $data['error_blocks_info_warning'] = $error['blocks_info_warning'];
    } else {
       $data['error_blocks_info_warning'] = '';
    }
  }

  private function set_backend_settings_value(&$data){
    $nextypay_prefix=$this->nextypay_name.'_';
    if (isset($this->request->post[$nextypay_prefix.'title'])) {
      $data['title'] = $this->request->post[$nextypay_prefix.'title'];
    } else {
      $data['title'] = $this->config->get( $nextypay_prefix.'title');
    }

    if (isset($this->request->post[$nextypay_prefix.'description'])) {
      $data['description'] = $this->request->post[$nextypay_prefix.'description'];
    } else {
      $data['description'] = $this->config->get( $nextypay_prefix.'description');
    }

    if (isset($this->request->post[$nextypay_prefix.'instruction'])) {
      $data['instruction'] = $this->request->post[$nextypay_prefix.'instruction'];
    } else {
      $data['instruction'] = $this->config->get( $nextypay_prefix.'instruction');
    }

    if (isset($this->request->post[$nextypay_prefix.'walletAddress'])) {
      $data['walletAddress'] = $this->request->post[$nextypay_prefix.'walletAddress'];
    } else {
      $data['walletAddress'] = $this->config->get( $nextypay_prefix.'walletAddress');
    }

    if (isset($this->request->post[$nextypay_prefix.'exchangeAPI'])) {
       $data['exchangeAPI'] = $this->request->post[$nextypay_prefix.'exchangeAPI'];
     } else {
       $data['exchangeAPI'] = $this->config->get( $nextypay_prefix.'exchangeAPI');
     }

    if (isset($this->request->post[$nextypay_prefix.'endPointAddress'])) {
      $data['endPointAddress'] = $this->request->post[$nextypay_prefix.'endPointAddress'];
    } else {
      $data['endPointAddress'] = $this->config->get( $nextypay_prefix.'endPointAddress');
    }

    if (isset($this->request->post[$nextypay_prefix.'min_blocks_saved_db'])) {
      $data['min_blocks_saved_db'] = $this->request->post[$nextypay_prefix.'min_blocks_saved_db'];
    } else {
      $data['min_blocks_saved_db'] = $this->config->get( $nextypay_prefix.'min_blocks_saved_db');
    }

    if (isset($this->request->post[$nextypay_prefix.'max_blocks_saved_db'])) {
      $data['max_blocks_saved_db'] = $this->request->post[$nextypay_prefix.'max_blocks_saved_db'];
    } else {
      $data['max_blocks_saved_db'] = $this->config->get( $nextypay_prefix.'max_blocks_saved_db');
    }

    if (isset($this->request->post[$nextypay_prefix.'blocks_loaded_each_request'])) {
      $data['blocks_loaded_each_request'] = $this->request->post[$nextypay_prefix.'blocks_loaded_each_request'];
    } else {
      $data['blocks_loaded_each_request'] = $this->config->get( $nextypay_prefix.'blocks_loaded_each_request');
    }

    if (isset($this->request->post[$nextypay_prefix.'status'])) {
      $data[$nextypay_prefix.'status'] = $this->request->post[$nextypay_prefix.'status'];
    } else {
      $data[$nextypay_prefix.'status'] = $this->config->get($nextypay_prefix.'status');
    }

    if (isset($this->request->post[$nextypay_prefix.'order_status_id'])) {
      $data['order_status_id'] = $this->request->post[$nextypay_prefix.'order_status_id'];
    } else {
      $data['order_status_id'] = $this->config->get($nextypay_prefix.'order_status_id');
    }
  }

   public function index() {
     $this->language->load('extension/payment/nextypay');
     $this->document->setTitle($this->language->get('heading_title'));
     $this->load->model('setting/setting');
     $nextypay_name=$this->nextypay_name;
     $nextypay_prefix=$nextypay_name.'_';
     $data['nextypay_prefix']=$nextypay_prefix;
     $this->document->addScript('view/javascript/nextypay/nextypay.js');

     if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

       $this->model_setting_setting->editSetting($nextypay_name, $this->request->post);

       $this->session->data['success'] = $this->language->get('text_success');

       $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
     }

     $this->set_navi($data);
     $this->set_backend_template($data);

     $data['user_token'] = $this->session->data['user_token'];

     $this->set_errors($data,$this->error);

     $this->set_backend_settings_value($data);

     $this->load->model('localisation/order_status');
     $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

     $this->children = array(
       'common/header',
       'common/footer'
     );
     $data['header'] = $this->load->controller('common/header');
     $data['column_left'] = $this->load->controller('common/column_left');
     $data['footer'] = $this->load->controller('common/footer');

     $this->response->setOutput($this->load->view('extension/payment/nextypay', $data));
   }

   protected function install_validate() {

     return true;
   }

   protected function validate() {
     $nextypay_name='payment_nextypay';
     $nextypay_prefix=$nextypay_name.'_';

     $exchangeAPI_valid="";
     $endPointAddress_valid="";
     $walletAddress_valid="";
     $min_blocks_saved_db_valid="";
     $max_blocks_saved_db_valid="";
     $blocks_loaded_each_request_valid="";

     if (isset($this->request->post[$nextypay_prefix.'exchangeAPI'])) $exchangeAPI_valid=$this->request->post[$nextypay_prefix.'exchangeAPI'];
     if (isset($this->request->post[$nextypay_prefix.'endPointAddress'])) $endPointAddress_valid=$this->request->post[$nextypay_prefix.'endPointAddress'];
     if (isset($this->request->post[$nextypay_prefix.'walletAddress'])) $walletAddress_valid=$this->request->post[$nextypay_prefix.'walletAddress'];
     if (isset($this->request->post[$nextypay_prefix.'min_blocks_saved_db'])) $min_blocks_saved_db_valid=$this->request->post[$nextypay_prefix.'min_blocks_saved_db'];
     if (isset($this->request->post[$nextypay_prefix.'max_blocks_saved_db'])) $max_blocks_saved_db_valid=$this->request->post[$nextypay_prefix.'max_blocks_saved_db'];
     if (isset($this->request->post[$nextypay_prefix.'blocks_loaded_each_request'])) $blocks_loaded_each_request_valid=$this->request->post[$nextypay_prefix.'blocks_loaded_each_request'];

     //if (!is_numeric($min_blocks_saved_db)||!is_numeric($max_blocks_saved_db)||!is_numeric($blocks_loaded_each_request)) $this->error['blocks_info']=$this->language->get('error_blocks_type');
     if ((!$min_blocks_saved_db_valid)||(!$max_blocks_saved_db_valid)||(!$blocks_loaded_each_request_valid)||($min_blocks_saved_db_valid>$max_blocks_saved_db_valid)) $this->error['blocks_info_warning']=$this->language->get('error_blocks_type');
     if (!filter_var($exchangeAPI_valid, FILTER_VALIDATE_URL)) $this->error['exchangeAPI_warning'] = $this->language->get('error_exchangeAPI');
     if (!filter_var($endPointAddress_valid, FILTER_VALIDATE_URL)) $this->error['endPointAddress_warning'] = $this->language->get('error_endPointAddress');

/////check valid hex string walletAddress
     if (($walletAddress_valid[0]=='0') && (($walletAddress_valid[1]=='x')||($walletAddress_valid[1]=='X'))) {
       $walletAddress_valid=substr($walletAddress_valid, 2);
     } else $walletAddress_valid="invalid";
     if (!ctype_xdigit($walletAddress_valid)) $this->error['walletAddress_warning'] = $this->language->get('error_walletAddress');

     return !$this->error;
   }
 }
