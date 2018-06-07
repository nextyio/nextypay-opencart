<?php
class Nextypayupdatedb extends Model{
  public static $instance;

  public $_url = 'https://rinkeby.infura.io/fNuraoH3vBZU8d4MTqdt';
  public $_functions;
  public $_blockchain;

  public $_connection;

  public $_db_prefix;
  public $_store_currency;
  public $_admin_wallet_address;
  public $_order_id_prefix;
  public $_min_blocks_saved_db;
  public $_max_blocks_saved_db;
  public $_blocks_loaded_each_request;

  /**
    * @param  object  $registry  Registry Object
  */


  public static function get_instance($registry) {
    if (is_null(static::$instance)) {
      static::$instance = new static($registry);
    }
    return static::$instance;
  }

  public function set_connection($connection){
    $this->_connection=$connection;
  }

  public function set_includes($obj_blockchain,$obj_functions){
    $this->_functions=$obj_functions;
    $this->_blockchain=$obj_blockchain;
  }

  public function set_backend_settings($db_prefix,$store_currency,$admin_wallet_address,$order_id_prefix,$min,$max,$blocks_loaded){
    $this->_db_prefix=$db_prefix;
    $this->_store_currency=$store_currency;
    $this->_admin_wallet_address=$admin_wallet_address;
    $this->_order_id_prefix=$order_id_prefix;
    $this->_min_blocks_saved_db=$min;
    $this->_max_blocks_saved_db=$max;
    $this->_blocks_loaded_each_request=$blocks_loaded;
  }

////////////////////sql query DB,depending on framework

  public function query_db($sql){
    return $this->_connection->query($sql);
  }

  public function get_value_query_db($sql){
    $result= $this->_connection->query($sql);
    return $result->row['output'];
  }

  public function get_values_query_db($sql){
    $results= $this->_connection->query($sql);
    return $results->rows;
  }

///////////////////////////////////////////////////

  public function get_order_id_prefix(){
    return $_order_id_prefix;
  }

  public function get_transactions_table_name(){
    return $this->_db_prefix.'transactions';
  }

  public function get_blocks_table_name(){
    return $this->_db_prefix.'blocks';
  }

  public function get_order_in_coin_table_name(){
    return $this->_db_prefix.'order_in_coin';
  }

  ///////////////////////////////////////////////////

  public function get_max_block_number_db(){

    $table_name = $this->get_blocks_table_name();
    $sql="SELECT MAX(number) AS output FROM $table_name";
    $result = $this->get_value_query_db($sql);

    if (!$result) return 0;
    return $result;

  }

  public function get_order_id_from_input($input_hash){

    //{“walletaddress”: “0x841A13DDE9581067115F7d9D838E5BA44B537A42″,”uoid”: “46”,”amount”: “80000”}
    $input=($this->_functions->hexToStr($input_hash));
    $input_arr=(explode(",",$input));

    $key='uoid';

    foreach($input_arr as $str)
    {
      $tmp= explode(":",$str,2);
      $get_key=$this->_functions->key_filter($tmp[0]);
      if ($get_key==$key) {
        $get_value=$this->_functions->key_filter($tmp[1]);
        $order_id=intval(explode("_",$get_value,2) [0]);
        return $order_id;
      }
    }
    return false;

  }

  public function get_order_id_prefix_from_input($input_hash){

    //{“walletaddress”: “0x841A13DDE9581067115F7d9D838E5BA44B537A42″,”uoid”: “46”,”amount”: “80000”}
    $input=($this->_functions->hexToStr($input_hash));
    $input_arr=(explode(",",$input));

    $key='uoid';

    foreach($input_arr as $str){
      $tmp= explode(":",$str,2);
      $get_key=$this->_functions->key_filter($tmp[0]);
      if ($get_key==$key) {
        $get_value=$this->_functions->key_filter($tmp[1]);
        $order_id_prefix=explode("_",$get_value,2) [1];
        return $order_id_prefix;
      }
    }
    return false;

  }

  public function transaction_exist($hash){

    $table_name=$this->get_transactions_table_name();
    $sql= "SELECT hash FROM $table_name
          WHERE hash='$hash'";
    $results = $this->get_values_query_db($sql);
    if (count($results)>0) return true;
    return false;

  }

  public function order_in_coin_exist($order_id){
    $table_name=$this->get_order_in_coin_table_name();
    $sql= "SELECT order_id FROM $table_name
          WHERE order_id='$order_id'";

    $results = $this->get_values_query_db($sql);
    if (count($result)>0) return true;
    return false;
  }

  //*****
  public function order_status_to_complete($order_id){

    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    $order = $objectManager->create('\Magento\Sales\Model\Order') ->load($order_id);
    $order->setStatus('complete');
    $order->save();
    if (!$order->getId()) {return;}
    $order_total=$order->getGrandTotal(); ///SHOP CURRENCY
    $order_total;
    $connection = $this->get_connection_db();
    $transactions_table_name ="nexty_payment_transactions";
    $paid_sum=$this->get_paid_sum_by_order_id($order_id);
    return $paid_sum.$order_total;
  }

  public function get_paid_sum_by_order_id($order_id){

    $table_name=$this->get_transactions_table_name();
    $sql = "SELECT value FROM $table_name
          WHERE order_id='$order_id'";
    $results=$this->get_values_query_db($sql);

    $sum=0;
    foreach ($results as $result){
      $value_hex=$result['value'];
      $value=(hexdec($value_hex))*1e-18;
      $sum=$sum+$value;
    }
    return $sum;

  }

  public function get_order_in_coin($order_id){

    $table_name=$this->get_order_in_coin_table_name();
    $sql = "SELECT order_total_in_coin FROM $table_name
          WHERE order_id='$order_id'";
    $results=$this->get_values_query_db($sql);
    foreach ($results as $result)
    {
      return $result['order_total_in_coin'];
    }
    return 0;

  }

  public function get_order_in_coin_test($order_id){

    $table_name=$this->get_order_in_coin_table_name();
    $sql = "SELECT order_total_in_coin FROM $table_name
          WHERE order_id='$order_id'";
    $results=$this->get_values_query_db($sql);
    foreach ($results as $result){
      return $result['order_total_in_coin'];
    }
    return 0;

  }

  public function insert_order_in_coin_db($order_id,$store_currency,$order_total,$order_total_in_coin,$placed_time){

    $table_name=$this->get_order_in_coin_table_name();
    if (!$this->order_in_coin_exist($order_id)) {
      $sql = "INSERT INTO " . $table_name . "(order_id, store_currency, order_total, order_total_in_coin, placed_time) VALUES
          ('$order_id', '$store_currency', '$order_total', '$order_total_in_coin', '$placed_time')";
      $this->query_db($sql);
    }

  }

  public function insert_transactions_db($transactions,$block_time){

    $table_name=$this->get_transactions_table_name();

    foreach ($transactions as $transaction)
    if (($transaction['to']) && (strtolower($transaction['to'])==strtolower($this->_admin_wallet_address))){

      $block_hash=$transaction['blockHash'];
      $block_number=$transaction['blockNumber'];
      $from_wallet=$transaction['from'];
      $to_wallet=$transaction['to'];
      $value=$transaction['value'];
      $time=$block_time;
      $hash=strtolower($transaction['hash']);
      $extra_data=$transaction['input'];
      //echo $extra_data;
      $order_id=$this->get_order_id_from_input($extra_data);
      $order_id_prefix_from_input=$this->get_order_id_prefix_from_input($extra_data);
      $order_id_prefix=$this->get_order_id_prefix();
      $block_number_dec= hexdec($block_number);

      if ((strtoupper($order_id_prefix)==strtoupper($order_id_prefix_from_input)) &&
        (!$this->transaction_exist($hash))){
        $sql = "INSERT INTO " . $table_name . "(block_number, block_hash, hash, from_wallet, to_wallet, value, time, order_id) VALUES
            ('$block_number_dec', '$block_hash', '$hash', '$from_wallet', '$to_wallet', '$value', '$time', '$order_id')";
        $this->query_db($sql);
      }
        //$paid_sum=$this->get_paid_sum_by_order_id($connection,$transactions_table_name,$order_id);
        //$table_name=$this->get_order_in_coin_table_name();
        //$order_total_in_coin=$this->get_order_in_coin($connection,$table_name,$order_id);
        //echo $order_id."incoin=".$order_total_in_coin."<br>".$paid_sum."<br>";
        //}
    }

  }

  public function is_paid_sum_enough($order_id){

    $paid_sum=$this->get_paid_sum_by_order_id($order_id);
    $order_total_in_coin=$this->get_order_in_coin($order_id);

    //Setup epsilon
    $epsilon=1e-5;

    //check if payment success
    $paid_enough= ($paid_sum+$epsilon>$order_total_in_coin);
    if ($paid_enough) $this->order_status_to_complete($order_id);
    return $paid_enough;
  }

  public function insert_block_db($block_content){
    //if block still unavaiable
    if (!$block_content) return;

    $table_name=$this->get_blocks_table_name();

    $block_number=hexdec($block_content['number']);
    $block_hash=$block_content['hash'];
    $block_header="";	/////////////////////////////////*****
    $block_prev_header=$block_content['parentHash'];
    $block_time=hexdec($block_content['timestamp']);
    $block_time= date("Y-m-d H:i:s", $block_time);
    $transactions=$block_content['transactions'];

    $sql = "INSERT INTO " . $table_name . "(number, hash, header, prev_header, time) VALUES
      ('$block_number', '$block_hash', '$block_header', '$block_prev_header','$block_time')";
    $this->query_db($sql);

    $this->insert_transactions_db($transactions,$block_time);

  }

  public function count_total_blocks_db(){

    $table_name = $this->get_blocks_table_name();
    $sql="SELECT COUNT('id') AS output FROM $table_name";
    $result = $this->get_value_query_db($sql);
    return $result;

  }

  public function delete_old_blocks_db(){

    $table_name=$this->get_blocks_table_name();

    $bottom_limit=$this->_min_blocks_saved_db;
    $top_limit=$this->_max_blocks_saved_db;

    $total_blocks=$this->count_total_blocks_db();
    $total_blocks_to_delete=$total_blocks-$bottom_limit;

    if ($top_limit>$total_blocks) return;
    $sql="DELETE FROM $table_name LIMIT $total_blocks_to_delete";
    $this->query_db($sql); //*****

  }

  public function is_order_in_coin_placed($order_id){

    $table_name = $this->get_order_in_coin_table_name();
    $sql="SELECT order_id FROM $table_name WHERE order_id='$order_id'";
    $results=$this->get_values_query_db($sql);
    if (count($results)>0) return true;
    return false;

  }

  public function init_blocks_table_db(){

    if ($this->count_total_blocks_db()>0) return;

    $max_block_number = $this->_blockchain->get_max_block_number($this->_url);
    $hex_max_block_number="0x".strval(dechex($max_block_number));
    $block=$this->_blockchain->get_block_by_number($this->_url,$hex_max_block_number);
    $block_content=$block['result'];
    $this->insert_block_db($block_content);

  }

  public function updatedb(){

    $this->init_blocks_table_db();

    $to_scan_number=$this->_blocks_loaded_each_request;
    //scan from this block number
    $start_number=$this->get_max_block_number_db()+1 ;
    //$start_number=2360750; //testing transaction at xxxxxxxx

    for ($scanning_number=$start_number;
        $scanning_number<$start_number+$to_scan_number; //test
        $scanning_number++){

      $hex_scanning_number="0x".strval(dechex($scanning_number)); //convert to hex
      $block=$this->_blockchain->get_block_by_number($this->_url,$hex_scanning_number);	//get Block by number with API
      $block_content=$block['result'];
      //return $block_content;
      $this->insert_block_db($block_content);

    }

    $this->delete_old_blocks_db();

  }

  public function test_func(){
    $test=$this->_db_prefix."<br>".
    $this->_store_currency."<br>".
    $this->_admin_wallet_address."<br>".
    $this->_order_id_prefix."<br>".
    $this->_min_blocks_saved_db."<br>".
    $this->_max_blocks_saved_db."<br>".
    $this->_blocks_loaded_each_request;
    return $test;
  }

}
?>
