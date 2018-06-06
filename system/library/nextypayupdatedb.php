<?php
class Nextypayupdatedb extends Model{
  private static $instance;

  public $db_prefix=DB_PREFIX.'nextypay_';
  public $url = 'https://rinkeby.infura.io/fNuraoH3vBZU8d4MTqdt';
  public $_functions;
  public $_blockchain;
  //DB_PREFIX;

  /**
    * @param  object  $registry  Registry Object
  */


  public static function get_instance($registry) {
    if (is_null(static::$instance)) {
      static::$instance = new static($registry);
    }
    return static::$instance;
  }

  public function set_includes($obj_blockchain,$obj_functions){
    $this->_functions=$obj_functions;
    $this->_blockchain=$obj_blockchain;
  }

  private function get_order_id_prefix(){
    $urlInterface = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\UrlInterface');
    return $urlInterface->getUrl('nexty');
  }


  private function get_transactions_table_name(){
    return $this->db_prefix.'transactions';
  }

  private function get_blocks_table_name(){
    return $this->db_prefix.'blocks';
  }

  private function get_order_in_coin_table_name(){
    return $this->db_prefix.'order_total_in_coin';
  }

  ///////////////////////////////////////////////////

  private function get_max_block_number_db($connection,$blocks_table_name){

    $table_name = $blocks_table_name;
    $sql="SELECT MAX(number) AS max FROM $table_name";
    $result = $connection->fetchAll($sql);
    return $result[0]['max'];

  }

  private function key_filter($key){
    $delete_list=array('"','“','″','”',' ','{','}');
    return str_replace($delete_list, '',$key);
  }

  private function get_order_id_from_input($input_hash){

    //{“walletaddress”: “0x841A13DDE9581067115F7d9D838E5BA44B537A42″,”uoid”: “46”,”amount”: “80000”}
    $input=($this->_functions->hexToStr($input_hash));
    $input_arr=(explode(",",$input));

    $key='uoid';

    foreach($input_arr as $str)
    {
      $tmp= explode(":",$str,2);
      $get_key=$this->key_filter($tmp[0]);
      if ($get_key==$key) {
        $get_value=$this->key_filter($tmp[1]);
        $order_id=intval(explode("_",$get_value,2) [0]);
        return $order_id;
      }
    }
    return false;

  }

  private function get_order_id_prefix_from_input($input_hash){

    //{“walletaddress”: “0x841A13DDE9581067115F7d9D838E5BA44B537A42″,”uoid”: “46”,”amount”: “80000”}
    $input=($this->_functions->hexToStr($input_hash));
    $input_arr=(explode(",",$input));

    $key='uoid';

    foreach($input_arr as $str){
      $tmp= explode(":",$str,2);
      $get_key=$this->key_filter($tmp[0]);
      if ($get_key==$key) {
        $get_value=$this->key_filter($tmp[1]);
        $order_id_prefix=explode("_",$get_value,2) [1];
        return $order_id_prefix;
      }
    }
    return false;

  }

  private function transaction_exist($connection,$transactions_table_name,$hash){

    $sql= "SELECT hash FROM $transactions_table_name
          WHERE hash='$hash'";
    $result = $connection->fetchAll($sql);
    if (count($result)>0) return true;
    return false;
  }

  private function order_in_coin_exist($connection,$order_in_coin_table_name,$order_id){

    $sql= "SELECT order_id FROM $order_in_coin_table_name
          WHERE order_id='$order_id'";

    $result = $connection->fetchAll($sql);
    if (count($result)>0) return true;
    return false;
  }
  ////////////////////////change to PRIVATE after TESTING
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
    $paid_sum=$this->get_paid_sum_by_order_id($connection,$transactions_table_name,$order_id);
    return $paid_sum.$order_total;
  }

  private function get_paid_sum_by_order_id($connection,$transactions_table_name,$order_id){
    $sql = "SELECT value FROM $transactions_table_name
          WHERE order_id='$order_id'";
    $results=$connection->fetchAll($sql);
    //$json=json_encode($results);
    $sum=0;
    foreach ($results as $result){
      $value_hex=$result['value'];
      $value=(hexdec($value_hex))*1e-18; //COIN
      $sum=$sum+$value;
    }
    //echo $sql."<br>";
    return $sum;
  }

  public function get_order_in_coin($connection,$table_name,$order_id){
    $sql = "SELECT order_total_in_coin FROM $table_name
          WHERE order_id='$order_id'";
    $results=$connection->fetchAll($sql);
    //$json=json_encode($results);
    foreach ($results as $result)
    {
      return $result['order_total_in_coin'];
    }
    //echo $sql."<br>";
    return 0;
  }

  public function get_order_in_coin_test($order_id){
    $connection=$this->get_connection_db();
    $table_name=$this->get_order_in_coin_table_name();
    $sql = "SELECT order_total_in_coin FROM $table_name
          WHERE order_id='$order_id'";
    $results=$connection->fetchAll($sql);
    //$json=json_encode($results);
    foreach ($results as $result){
      return $result['order_total_in_coin'];
    }
    //echo $sql."<br>";
    return 0;
  }

  public function insert_order_in_coin_db($order_id,$store_currency,$order_total,$order_total_in_coin,$placed_time){
    $connection=$this->get_connection_db();
    $table_name=$this->get_order_in_coin_table_name();
    if (!$this->order_in_coin_exist($connection,$table_name,$order_id)) {
      $sql = "INSERT INTO " . $table_name . "(order_id, store_currency, order_total, order_total_in_coin, placed_time) VALUES
          ('$order_id', '$store_currency', '$order_total', '$order_total_in_coin', '$placed_time')";
      $connection->query($sql);
    }
  }

  private function insert_transactions_db($connection,$transactions,$transactions_table_name,$admin_wallet_address,$block_time){

    foreach ($transactions as $transaction)
    if (($transaction['to']) && (strtolower($transaction['to'])==strtolower($admin_wallet_address))){

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
        (!$this->transaction_exist($connection,$transactions_table_name,$hash))){
        $sql = "INSERT INTO " . $transactions_table_name . "(block_number, block_hash, hash, from_wallet, to_wallet, value, time, order_id) VALUES
            ('$block_number_dec', '$block_hash', '$hash', '$from_wallet', '$to_wallet', '$value', '$time', '$order_id')";
        $connection->query($sql);
      }
        //$paid_sum=$this->get_paid_sum_by_order_id($connection,$transactions_table_name,$order_id);
        //$table_name=$this->get_order_in_coin_table_name();
        //$order_total_in_coin=$this->get_order_in_coin($connection,$table_name,$order_id);
        //echo $order_id."incoin=".$order_total_in_coin."<br>".$paid_sum."<br>";
        //}
    }

  }

  public function is_paid_sum_enough($order_id){
    $connection = $this->get_connection_db();
    $transactions_table_name=$this->get_transactions_table_name();
    //$paid_sum="";
    $paid_sum=$this->get_paid_sum_by_order_id($connection,$transactions_table_name,$order_id);

    $order_in_coin_table_name=$this->get_order_in_coin_table_name();
    //$order_total_in_coin=0;
    $order_total_in_coin=$this->get_order_in_coin($connection,$order_in_coin_table_name,$order_id);
    //return $paid_sum."sdgsd".$order_total_in_coin;

    //Setup epsilon
    $epsilon=1e-5;

    $paid_enough= ($paid_sum+$epsilon>$order_total_in_coin);
    if ($paid_enough) $this->order_status_to_complete($order_id);
    return $paid_enough;
  }

  public function insert_block_db($connection,$block_content,$blocks_table_name,$transactions_table_name,$admin_wallet_address){
    //if block still unavaiable
    if (!$block_content) return;
    $block_number=hexdec($block_content['number']);
    $block_hash=$block_content['hash'];
    $block_header="";	/////////////////////////////////
    $block_prev_header=$block_content['parentHash'];
    $block_time=hexdec($block_content['timestamp']);
    $block_time= date("Y-m-d H:i:s", $block_time);
    $transactions=$block_content['transactions'];

    $sql = "INSERT INTO " . $blocks_table_name . "(number, hash, header, prev_header, time) VALUES
      ('$block_number', '$block_hash', '$block_header', '$block_prev_header','$block_time')";
    $connection->query($sql);

    $this->insert_transactions_db($connection,$transactions,$transactions_table_name,$admin_wallet_address,$block_time);

  }

  private function count_total_blocks_db($connection,$blocks_table_name){

    $table_name = $blocks_table_name;
    $sql="SELECT COUNT('id') AS count FROM $table_name";
    $result = $connection->fetchAll($sql);
    return $result[0]['count'];

  }

  private function delete_old_blocks_db($connection,$blocks_table_name,$bottom_limit,$top_limit){

    $total_blocks=$this->count_total_blocks_db($connection,$blocks_table_name);
    $total_blocks_to_delete=$total_blocks-$bottom_limit;
    //echo $total_blocks;
    if ($top_limit>$total_blocks) return;
    $sql="DELETE FROM $blocks_table_name LIMIT $total_blocks_to_delete";
    $connection->query($sql);

  }

  public function is_order_in_coin_placed($order_id){
    $connection = $this->get_connection_db();
    $table_name = $this->get_order_in_coin_table_name();
    $sql="SELECT order_id FROM $table_name WHERE order_id='$order_id'";
    $results=$connection->fetchAll($sql);
    //echo $sql.count($results);
    if (count($results)>0) return true;
    return false;
  }

  public function update_nexty_db(){
    $connection = $this->get_connection_db();
    //echo "DB update";
    $admin_wallet_address=$this->getConfig('payment/sample_gateway/walletAddress');
    $min_blocks_saved_db=$this->getConfig('payment/sample_gateway/min_blocks_saved_db');
    $max_blocks_saved_db=$this->getConfig('payment/sample_gateway/max_blocks_saved_db');
    $blocks_loaded_each_request=$this->getConfig('payment/sample_gateway/blocks_loaded_each_request');
    //echo $admin_wallet_address;

    $db_prefix=$this->db_prefix;
    $blocks_table_name 		= $this->get_blocks_table_name();
    $transactions_table_name= $this->get_transactions_table_name();
    //	$exchange_table_name	= $this->get_exchange_table_name();

    //API to get Informations of Blocks, Transactions
    $url = $this->url;

    //scan from this block number

    $start_block_number=$this->get_max_block_number_db($connection,$blocks_table_name)+1 ;
    //$start_block_number=2360750; //testing transaction at 2360751

    for ($scan_block_number=$start_block_number;
    		//$scan_block_number<=$start_block_number+$blocks_loaded_each_request;
    		$scan_block_number<=$start_block_number+$blocks_loaded_each_request; //test
    		$scan_block_number++){

    	$hex_scan_block_number="0x".strval(dechex($scan_block_number)); //convert to hex
    	$block=$this->_blockchain->get_block_by_number($url,$hex_scan_block_number);	//get Block by number with API
    	$block_content=$block['result'];

    	//put Block to Database, table $blocks_table_name
    	$this->insert_block_db($connection,$block_content,$blocks_table_name,$transactions_table_name,$admin_wallet_address);
    }

    // keep $min_blocks_saved_db Blocks, and delete the oldest blocks, in Admin Setting
    $this->delete_old_blocks_db($connection,$blocks_table_name,$min_blocks_saved_db,$max_blocks_saved_db);

  }

  private function init_blocks_table_db(){
    //get DB connection
    $connection = $this->get_connection_db();

    $db_prefix=$this->db_prefix;
    //if (!is_table_empty_db($wpdb,$blocks_table_name)) return ;
    $blocks_table_name  = $this->get_blocks_table_name();
    $transactions_table_name  = $this->get_transactions_table_name();
    $admin_wallet_address=$this->getConfig('payment/sample_gateway/walletAddress');
    if ($this->count_total_blocks_db($connection,$blocks_table_name)>0) return;

    $url = $this->url;
    $max_block_number = $this->_blockchain->get_max_block_number($url);
    //$max_block_number = 2258373;
    $hex_max_block_number="0x".strval(dechex($max_block_number));
    $block=$this->_blockchain->get_block_by_number($url,$hex_max_block_number);
    $block_content=$block['result'];
    $this->insert_block_db($connection,$block_content,$blocks_table_name,$transactions_table_name,$admin_wallet_address);
  }
/*
    public function call_db_test(){
      return "DB connected";
    }
*/
}
?>
