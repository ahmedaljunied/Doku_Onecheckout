<?php
/**
 * Block for Payment before auto redirect to DOKU
 *
 * @author KDS
 */

class Doku_Onecheckout_Block_Payment extends Mage_Core_Block_Template
{
    /**
     * Return checkout session instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Return order instance
     *
     * @return Mage_Sales_Model_Order|null
     */
    protected function _getOrder()
    {
        if ($this->getOrder()) {
            return $this->getOrder();
        } elseif ($orderIncrementId = $this->_getCheckout()->getLastRealOrderId()) {
            return Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        } else {
            return null;
        }
    }

    /**
     * Get Form data by using DOKU Onecheckout payment api
     *
     * @return array
     */
    public function getFormData()
    {
        return $this->_getOrder()->getPayment()->getMethodInstance()->getFormFields();
    }
}
