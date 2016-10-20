<?php
class Doku_Onecheckout_Helper_Data extends Mage_Core_Helper_Abstract
{
  //Send Data to Anton's Seller Dashboard
  public function bacaHTML($url)
  {
      $data = curl_init();
      curl_setopt($data, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($data, CURLOPT_URL, $url);
      $hasil = curl_exec($data);
      curl_close($data);
      return $hasil;
  }
}
