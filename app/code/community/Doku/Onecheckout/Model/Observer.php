<?php
/**
 * Observer Model
 *
 * @author KDS
 */
class Doku_Onecheckout_Model_Observer
{
	public function checkstatus() {
		$transactions = Mage::getModel('onecheckout/transactions')->getCollection();
		$transactions->addFieldToFilter('check_status_time' , array('null' => true));
		$transactions->addFieldToFilter('response_message', array('null' => true));
		$transactions->addfieldtofilter('payment_timelimit',array('lt' => Mage::getModel('core/date')->date()));
		$transactions->setPageSize(10);
		$transactions->getCurPage(1);
		$transactions->getSelect()->reset(Zend_Db_Select::COLUMNS)->columns('invoice_no');
		$transactions->load();
		foreach ($transactions as $transaction) {
			$transactionArray = $transaction->toArray();
			$event = Mage::getModel('onecheckout/event')
				->setTransaction($transactionArray['invoice_no']);
			$event->checkStatusEvent();
		}
        echo 'Continue';		
	}
}
