<?php

class Doku_Onecheckout_IndexController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {
        echo 'Hello Index!';
		echo '<dl>';
		foreach($this->getRequest()->getParams() as $key=>$value) {
			echo '<dt><strong>Param: </strong>'.$key.'</dt>';
			echo '<dl><strong>Value: </strong>'.$value.'</dl>';
		}
		echo '</dl>';
    }

	public function goodbyeAction() {
		echo 'Good Bye!';
	}


    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

	/**
	 * Preparing redirect to DOKU Payment Page
	 * STATE = 1
	 *
	 */

	public function paymentAction() {

        try {
            $session = $this->_getCheckout();
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($session->getLastRealOrderId());

            if (!$order->getId()) {
                Mage::throwException('No order for processing found');
            }
            $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                'The customer was redirected to Payment Gateway.');
            $order->save();
            $order->sendNewOrderEmail();

            $session->setOnecheckoutQuoteId($session->getQuoteId());
            $session->setOnecheckoutRealOrderId($session->getLastRealOrder()->getId());
            $session->getQuote()->setIsActive(false)->save();
            //$session->clear();

            $this->loadLayout();
            $this->renderLayout();
        } catch (Exception $e){
            Mage::logException($e);
            parent::_redirect('checkout/cart');
        }
	}

	/**
	 * Receiving Notify from DOKU Server
	 * STATE = 2
	 * STATE = 7 <= Reversal
	 *
	 */
    public function notifyAction() {
    	Mage::log('Start notify');
    	Mage::log($this->getRequest()->getParams());
	$event = Mage::getModel('onecheckout/event')
            ->setEventData($this->getRequest()->getParams());
        $message = $event->notifyEvent();
        $this->getResponse()->setBody($message);
	Mage::log('Result = ' . $message);
    	Mage::log('End notify');
    }

	/**
	 * Receiving Redirect Back from DOKU Payment Page
	 * STATE = 3
	 * STATE = 6 <= Cancel
	 *
	 */
    public function resultAction() {
    	Mage::log('Start redirect back');
    	Mage::log($this->getRequest()->getParams());
        $event = Mage::getModel('onecheckout/event')
                 ->setEventData($this->getRequest()->getParams());
        try {
            $quoteId = $event->redirectEvent();
            $this->_getCheckout()->setLastSuccessQuoteId($quoteId);
            $this->_redirect('checkout/onepage/success');
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getCheckout()->addError($e->getMessage());
        } catch(Exception $e) {
            Mage::logException($e);
        }
        
        // This Additional Code Written By Bayu
        // Code for send data to seller dashboard
        $order_param = $this->getRequest()->getParams();
        $session = $this->_getCheckout();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());
        $orderId = $order->getId();
        //$order   = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        $site_name = "http://vendor.tinkerlust.com";

        $name = $order->getCustomerName();
        $email = $order->getCustomerEmail();
        $order_no = $orderId;
        $total = (int)$order->getGrandTotal();
        $transfer_no = 212;
        $transfer_bank = "DOKU";

        $name = urlencode($name);
        $email = urlencode($email);
        $order_no = urlencode($order_no);
        $total = urlencode($total);
        $transfer_no = urlencode($transfer_no);
        $transfer_bank = urlencode($transfer_bank);

        $url_go = "$site_name/api_paymentconfirm.php?payment_sd=yes&name=$name&email=$email&order_no=$order_no&total=$total&transfer_no=$transfer_no&transfer_bank=$transfer_bank";
        $content = Mage::helper('kredivopayment')->bacaHTML($url_go);
        //echo $content;
        // Code for send data to seller dashboard ends

    	Mage::log('End redirect back');
        $this->_redirect('checkout/cart');
    }

	/**
	 * Triggering to check status for all pending transaction (No Response Message from DOKU) for indirect payment
	 * STATE = 4
	 *
	 */
	public function checkStatusAction() {
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

	/**
	 * Receiving Identify from DOKU server
	 * STATE = 5
	 *
	 */
    public function identifyAction() {
    	Mage::log('Start identify');
    	Mage::log($this->getRequest()->getParams());
		$event = Mage::getModel('onecheckout/event')
            ->setEventData($this->getRequest()->getParams());
        $message = $event->identifyEvent();
        $this->getResponse()->setBody($message);
    	Mage::log('End identify');
    }

	/**
	 * Receiving Review from DOKU server
	 * STATE = 8
	 *
	 */
    public function reviewAction() {
        echo 'This is Review!';
        echo '<dl>';
        foreach($this->getRequest()->getParams() as $key=>$value) {
            echo '<dt><strong>Param: </strong>'.$key.'</dt>';
            echo '<dl><strong>Value: </strong>'.$value.'</dl>';
        }
        echo '</dl>';
    }

	/**
	 * Receiving Inquiry from DOKU server
	 * STATE = 9
	 *
	 */
    public function inquiryAction() {
        echo 'This is Inquiry!';
        echo '<dl>';
        foreach($this->getRequest()->getParams() as $key=>$value) {
            echo '<dt><strong>Param: </strong>'.$key.'</dt>';
            echo '<dl><strong>Value: </strong>'.$value.'</dl>';
        }
        echo '</dl>';
    }
}
