<?php
/**
 * Transactions item model
 *
 * @author KDS
 */

class Doku_Onecheckout_Model_Resource_Transactions extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Define resource model
     */
    protected function _construct() {
        $this->_init('onecheckout/transactions', 'transaction_id');
    }
}