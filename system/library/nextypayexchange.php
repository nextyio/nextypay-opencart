<?php

class Nextypayexchange{
    private static $instance;

  /**
   * @param  object  $registry  Registry Object
   */

    public static function get_instance($registry) {
      if (is_null(static::$instance)) {
        static::$instance = new static($registry);
      }

      return static::$instance;
    }
	//Ether 1027 Nexty 2714
	public $coin_id=1027;
	public function coinmarketcap_exchange($text_to,$amount){
		$id_from="1027";
		$str="https://api.coinmarketcap.com/v2/ticker/".$id_from."/?convert=".$text_to;
		$result=json_decode((file_get_contents($str)),true);
		$upper_text_to=strtoupper($text_to);

		return $amount/$result['data']['quotes'][$upper_text_to]['price'];
	}

}
?>
