<?php
class ModelExtensionPaymentNextypay extends Model {
  public function install() {
      $this->db->query("
        CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "nextypay_block` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");

      $this->db->query("
        CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "nextypay_transaction` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
    }

  public function uninstall() {
    $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "nextypay_block`;");
    $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "nextypay_transaction`;");
  }

}
  ?>
