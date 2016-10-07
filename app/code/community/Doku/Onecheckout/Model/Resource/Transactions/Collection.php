<?php
/**
 * Transactions collection model
 *
 * @author KDS
 */

class Doku_Onecheckout_Model_Resource_Transactions_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Define collecion model
     */
    protected function _construct() {
        $this->_init('onecheckout/transactions');
    }
}