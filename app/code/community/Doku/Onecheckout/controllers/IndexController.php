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

    // This Additional Code Written By Bayu
    // Code for send data to seller dashboard
    $session = $this->_getCheckout();
    $order = Mage::getModel('sales/order');

    $items = $order->getAllItems();
    $item_details = array();
    foreach ($items as $each) {
        $item = array(
            'product_name'  => $each->getName(),
            'product_sku'   => $each->getSku(),
            'product_price' => $this->is_string($each->getPrice()),
            'quantity'      => $this->is_string($each->getQtyToInvoice()),
        );

        if ($item['quantity'] == 0) {
            continue;
        }

        $item_details[] = $item;
    }
    unset($each);

    if ($discount_amount != 0) {
        $couponItem = array(
            'product_name'  => 'DISCOUNT',
            'product_sku'   => 'DISCOUNT',
            'product_price' => $discount_amount,
            'quantity'      => 1,
        );
        $item_details[] = $couponItem;
    }

    if ($shipping_amount > 0) {
        $shipping_item = array(
            'product_name'  => 'Shipping Cost',
            'product_sku'   => 'SHIPPING',
            'product_price' => $shipping_amount,
            'quantity'      => 1,
        );
        $item_details[] = $shipping_item;
    }

    if ($shipping_tax_amount > 0) {
        $shipping_tax_item = array(
            'product_name'  => 'Shipping Tax',
            'product_sku'   => 'SHIPPING_TAX',
            'product_price' => $shipping_tax_amount,
            'quantity'      => 1,
        );
        $item_details[] = $shipping_tax_item;
    }

    if ($tax_amount > 0) {
        $tax_item = array(
            'product_name'  => 'Tax',
            'product_sku'   => 'TAX',
            'product_price' => $tax_amount,
            'quantity'      => 1,
        );
        $item_details[] = $tax_item;
    }

    $current_currency = Mage::app()->getStore()->getCurrentCurrencyCode();
    if ($current_currency != 'IDR') {
        $conversion_func = function ($non_idr_price) {
            return $non_idr_price * Mage::helper('kredivopayment')->_getConversionRate();
        };
        foreach ($item_details as &$item) {
            $item['product_price'] = intval(round(call_user_func($conversion_func, $item['product_price'])));
        }
        unset($item);
    } else {
        foreach ($item_details as &$each) {
            $each['product_price'] = (int) $each['product_price'];
        }
        unset($each);
    }
    $totalPrice = 0;
    foreach ($item_details as $item) {
        $totalPrice += $item['product_price'] * $item['quantity'];
    }


    $site_name = "http://vendor.tinkerlust.com";

    $name = $order->getBillingAddress()->getName();
    $email = $order_billing_address->getEmail();
    $order_no = $order->loadByIncrementId($session->getLastRealOrderId());
    $total = $this->is_string($totalPrice);
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
