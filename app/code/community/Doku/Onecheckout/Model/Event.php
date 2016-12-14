<?php
/**
 * Abstract Model
 *
 * @author KDS
 */

/**
 * DOKU OneCheckout notification processor model
 */
class Doku_Onecheckout_Model_Event
{

	protected $_CHANNEL_DIRECT = array('01', '02', '04', '06', '15', '16', '18', '19');
	protected $_CHANNEL_INDIRECT = array('03', '05', '08', '14', '17', '22');

    /**
     * Store order instance
     *
     * @var Mage_Sales_Model_Order
     */
    protected $_order = null;

	/**
     * Onecheckout stored transactions
     *
     * @var Doku_Onecheckout_Model_Transactions
     */
    protected $_transactions = null;


    /**
     * Event request data
     * @var array
     */
    protected $_eventData = array();

    /**
     * Event request data setter
     * @param array $data
     * @return Doku_Onecheckout_Model_Event
     */
    public function setEventData(array $data)
    {
        $this->_eventData = $data;
        return $this;
    }

    /**
     * Event request data getter
     * @param string $key
     * @return array|string
     */
    public function getEventData($key = null)
    {
        if (null === $key) {
            return $this->_eventData;
        }
		$value = isset($this->_eventData[$key]) ? $this->_eventData[$key] : null;
		$value = is_array($value) ? '' : $value;
        return $value;
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
     * Process notify from DOKU server
     *
     * @return String
     */
    public function notifyEvent()
    {
        try {
            $params = $this->_validateEventData('NOTIFY');
            $msg = '';
			if($params['RESULTMSG'] == 'SUCCESS') {
				$msg = 'Payment success with approval code ' . $params['APPROVALCODE'] . '.';
				$this->_processSale($msg);
			} else if($params['RESULTMSG'] == 'FAILED') {
				//$msg = 'Payment failed with response code ' . $params['RESPONSECODE'] . ' .';
				$msg = 'Transaksi anda gagal';
				$this->_processCancel($msg, 'DECLINED');
			} else if($params['STATUSTYPE'] !== 'P') {
				//$msg = 'Payment failed with status type ' . $params['STATUSTYPE'] . ' .';
				$msg = 'Transaksi anda gagal';
				$this->_processCancel($msg, 'REVERSAL');
			}
            return 'Continue';
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            return 'Stop ' . $e->getMessage();
        } catch(Exception $e) {
            Mage::logException($e);
            return 'Stop ' . $e->getMessage();
        }
    }

    /**
     * Redirect back from DOKU Payment Page
     *
     * @return int
     */
    public function redirectEvent(){
		$params = $this->_validateEventData('REDIRECT');
		$this->_transactions->addData(
		array(
			'state' => '3',
			'redirect_back_time' => Mage::getModel('core/date')->date(),
			'payment_code' => $this->getEventData('PAYMENTCODE')
		));
		$this->_transactions->save();
		$responseCode = $this->_transactions->getResponseCode();
		if($responseCode !== $this->getEventData('STATUSCODE')) {
			//SEND CHECK STATUS
			$this->checkStatusEvent();
			if(($this->_transactions->getStatus() == 'S') && ($params['STATUSCODE'] != '5511')){
				//$msg = 'Payment success with approval code ' . $params['APPROVALCODE'] . '.';
				$msg = 'Transaksi anda gagal';
				$this->_processSale($msg);
			} else if($this->_transactions->getStatus() == 'F') {
				Mage::log('result failed');
				$msg = '';
				if(in_array($this->getEventData('PAYMENTCHANNEL'), $this->_CHANNEL_DIRECT)) {
					//$msg = 'Payment failed after check status with response code ' . $params['RESPONSECODE'] . ' .';
					$msg = 'Transaksi anda gagal';
					$this->_processCancel($msg, 'DECLINED');
				} else {
					$msg = 'Thank you for your purchase. Please pay with payment code ' . $params['PAYMENTCODE'] . ' .';
				}
				Mage::log($msg);
				Mage::throwException($msg);
			}
		} else {
			if($this->_transactions->getStatus() == 'F') {
				//$msg = 'Payment failed with response code ' . $this->_transactions->getResponseCode() . ' .';
				$msg = 'Transaksi anda gagal';
				Mage::throwException($msg);
			}
		}
	        return $this->_order->getQuoteId();
    }

	public function checkStatusEvent() {
		$status = null;
        $mallid = Mage::getStoreConfig('onecheckout/settings/config_mallid');
        $sharedkey = Mage::getStoreConfig('onecheckout/settings/config_sharedkey');
        $chainnumber = Mage::getStoreConfig('onecheckout/settings/config_chainnumber');
		$transidmerchant = $this->_transactions->getInvoiceNo();
		$sessionid = $this->_transactions->getSessionId();
		$currency = $this->_transactions->getCurrency();
		$purchasecurrency = $this->_transactions->getPurchaseCurrency();
		if($currency == '360') {
			$words = sha1($mallid . $sharedkey . $transidmerchant);
		} else {
			$words = sha1($mallid . $sharedkey . $transidmerchant . $currency);
		}
		$data = 'MALLID=' . $mallid . '&CHAINMERCHANT=' . $chainnumber . '&TRANSIDMERCHANT=' . $transidmerchant . '&SESSIONID=' . $sessionid . '&WORDS=' . $words . '&CURRENCY=' . $currency . '&PURCHASECURRENCY=' . $purchasecurrency;
		$curl = new Varien_Http_Adapter_Curl();
		$curl->setConfig(array('timeout' => 15));    //Timeout in no of seconds
		$checkstatus_url = $this->_getStatusUrl();
		$curl->write(Zend_Http_Client::POST, $checkstatus_url, '1.0', array('Content-Type' => 'application/x-www-form-urlencoded'), $data);
		$result = $curl->read();
		if ($result === false) {
			Mage::log('CHECK STATUS FAILED');
		}
		$result = substr($result, strpos($result, '<'), strlen($result) - strpos($result, '<'));
		$curl->close();
		try {
			$xml = new SimpleXMLElement($result);
			$this->setEventData(json_decode(json_encode($xml), TRUE));
			$data = $this->_constructData();
			if($xml->RESULTMSG == 'SUCCESS') $status = 'S';
			else if($xml->RESULTMSG == 'FAILED') $status = 'F';
			else if($xml->RESULTMSG == 'TRANSACTION_NOT_FOUND') $status = 'F';
			else if($xml->RESULTMSG == 'UNPAID') $status = 'S';
			else if($xml->RESULTMSG == 'ERROR') $status = 'F';
			else $status = 'F';
		} catch (Exception $e) {
			if($result == 'FAILED') $status = 'F';
		}
		if($status !== null) {
			$data['status'] = $status;
			$data['state'] = '4';
			$data['check_status_time'] = Mage::getModel('core/date')->date();
			$this->_transactions->addData($data);
			$this->_transactions->save();
		}
	}

    /**
     * Process identify from DOKU server
     *
     * @return String
     */
    public function identifyEvent()
    {
        try {
            $params = $this->_validateEventData('IDENTIFY');
			$this->_transactions->addData(
			array(
				'state' => '5',
				'identify_time' => Mage::getModel('core/date')->date(),
				'payment_channel' => $this->getEventData('PAYMENTCHANNEL')
			));
			$this->_transactions->save();
           return 'Continue';
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            return 'Stop ' . $e->getMessage();
        } catch(Exception $e) {
            Mage::logException($e);
            return 'Stop ' . $e->getMessage();
        }
    }

	/**
     * Return url of DOKU check status URL
     *
     * @return string
     */
    public function _getStatusUrl()
    {
        if(Mage::getStoreConfig('onecheckout/settings/config_environment') == 'PRODUCTION') {
            return 'https://pay.doku.com/Suite/CheckStatus';
        } else if (Mage::getStoreConfig('onecheckout/settings/config_environment') == 'DEVELOPMENT'){
            return 'http://luna2.nsiapay.com/Suite/CheckStatus';
        } else {
            return 'http://staging.doku.com/Suite/CheckStatus';
		}
    }

    /**
     * Processed order cancelation
     * @param string $msg Order history message
     */
    protected function _processCancel($msg, $via) {
        $this->_order->cancel();
        $this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, $msg);
        $this->_order->save();
		$data = $this->_constructData();
		$data['status'] = 'F';
		if($via == 'DECLINED') {
			$data['state'] = '2';
			$data['notify_time'] = Mage::getModel('core/date')->date();
		} else if($via == 'CANCEL') {
			$data['state'] = '6';
			$data['cancel_time'] = Mage::getModel('core/date')->date();
		} else if($via == 'REVERSAL') {
			$data['state'] = '7';
			$data['reversal_time'] = Mage::getModel('core/date')->date();
		}
		$this->_transactions->addData($data);
		$this->_transactions->save();
    }

    /**
     * Processes payment confirmation, creates invoice if necessary, updates order status,
     * sends order confirmation to customer
     *
     * @param string $status
     * @param string $msg Order history message
     */
    protected function _processSale($msg)
    {
		$this->_createInvoice();
		$this->_order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $msg);
		$this->_order->getPayment()->setLastTransId($this->getEventData('TRANSIDMERCHANT'));
		$data = $this->_constructData();
		$data['status'] = 'S';
		$data['state'] = '2';
		$data['notify_time'] = Mage::getModel('core/date')->date();
		$this->_transactions->addData($data);
		$this->_transactions->save();

		// This Additional Code Written By Bayu
		// Code for send data to seller dashboard
		$site_name = "http://vendor.tinkerlust.com";

		$name = $this->_order->getCustomerName();
		$email = $this->_order->getCustomerEmail();
		$order_no = $this->_order->getId();
		$total = (int)$this->_order->getGrandTotal();
		$transfer_no = $this->_transactions->getSessionId();
		$transfer_bank = "DOKU";

		$name = urlencode($name);
		$email = urlencode($email);
		$order_no = urlencode($order_no);
		$total = urlencode($total);
		$transfer_no = urlencode($transfer_no);
		$transfer_bank = urlencode($transfer_bank);

		$url_go = "$site_name/api_paymentconfirm.php?payment_sd=yes&name=$name&email=$email&order_no=$order_no&total=$total&transfer_no=$transfer_no&transfer_bank=$transfer_bank";
		$content = Mage::helper('onecheckout')->bacaHTML($url_go);
		//echo $content;
		// Code for send data to seller dashboard ends

		// send new order email
		$this->_order->sendOrderUpdateEmail();
		$this->_order->setEmailSent(true);
	        $this->_order->save();
    }

	protected function _constructData() {
		$data = array (
			'response_code' => $this->getEventData('RESPONSECODE'),
			'approval_code' => $this->getEventData('APPROVALCODE'),
			'response_message' => $this->getEventData('RESULTMSG'),
			'payment_channel' => $this->getEventData('PAYMENTCHANNEL'),
			'payment_code' => $this->getEventData('PAYMENTCODE'),
			'session_id' => $this->getEventData('SESSIONID'),
			'bank' => $this->getEventData('BANK'),
			'mcn' => $this->getEventData('MCN'),
			'payment_datetime' => Mage::getModel('core/date')->date('YmdHis', strtotime($this->getEventData('PAYMENTDATETIME'))),
			'verify_id' => $this->getEventData('VERIFYID'),
			'verify_score' => $this->getEventData('VERIFYSCORE'),
			'verify_status' => $this->getEventData('VERIFYSTATUS'),
			'brand' => $this->getEventData('BRAND'),
			'cardholder_name' => $this->getEventData('CHNAME'),
			'threedsecure_status' => $this->getEventData('THREEDSECURESTATUS'),
			'liability' => $this->getEventData('LIABILITY'),
			'edu_status' => $this->getEventData('EDUSTATUS')
		);
		return $data;
	}

    /**
     * Builds invoice for order
     */
    protected function _createInvoice()
    {
        if (!$this->_order->canInvoice()) {
            return;
        }
        $invoice = $this->_order->prepareInvoice();
        $invoice->register()->capture();
        //$invoice->sendEmail();
        $this->_order->addRelatedObject($invoice);
    }

    /**
     * Checking posted parameters
     * Throws Mage_Core_Exception if error
     * @param string $process
     *
     * @return array $params request params
     */
    protected function _validateEventData($process)
    {
        // get request variables
        $params = $this->_eventData;
        if (empty($params)) {
            Mage::throwException('Request does not contain any elements.');
        }

		//check words
        $mallid = Mage::getStoreConfig('onecheckout/settings/config_mallid');
        $sharedkey = Mage::getStoreConfig('onecheckout/settings/config_sharedkey');
		if($process == 'NOTIFY' || $process == 'REVIEW') {
			$calculatedwords = sha1($params['AMOUNT'] . $mallid . $sharedkey . $params['TRANSIDMERCHANT'] . $params['RESULTMSG'] . $params['VERIFYSTATUS']);
			if($calculatedwords != $params['WORDS']) {
				Mage::throwException('Invalid Words.');
			}
		} else if($process == 'REDIRECT') {
			$calculatedwords = sha1($params['AMOUNT'] . $sharedkey . $params['TRANSIDMERCHANT'] . $params['STATUSCODE']);
			if($calculatedwords != $params['WORDS']) {
				Mage::throwException('Invalid Words.');
			}
		}

        // check order ID
        if (empty($params['TRANSIDMERCHANT'])) {
            Mage::throwException('Missing order ID.');
        }

        // load order for further validation
		$this->setOrder($params['TRANSIDMERCHANT']);
        if (!$this->_order->getId()) {
            Mage::throwException('Order not found.');
        }

		// load transaction for further use
		$this->setTransaction($params['TRANSIDMERCHANT']);
		if (!$this->_transactions->getId()) {
			Mage::throwException('Invoice not found.');
		}

		// check transaction amount if currency matches
		if (number_format(round($this->_order->getGrandTotal(), 2), 2, '.', '') != $params['AMOUNT']) {
			Mage::throwException('Transaction amount does not match.');
		}
        return $params;
    }

	public function setTransaction($invoice_no) {
		$this->_transactions = Mage::getModel('onecheckout/transactions')->load($invoice_no, 'invoice_no');
		return $this;
	}

	public function setOrder($order_no) {
		$this->_order = Mage::getModel('sales/order')->loadByIncrementId($order_no);
		return $this;
	}
}
