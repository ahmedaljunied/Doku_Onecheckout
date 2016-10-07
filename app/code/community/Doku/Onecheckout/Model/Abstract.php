<?php
/**
 * Abstract Model
 *
 * @author KDS
 */
abstract class Doku_Onecheckout_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{
    /**getMethod
     *
     * unique internal payment method identifier
     */
    protected $_code = 'onecheckout_abstract';
    protected $_formBlockType = 'onecheckout/form';
    protected $_infoBlockType = 'onecheckout/info';

    /**
     * Availability options
     */
    protected $_isGateway              = true;
    protected $_canAuthorize           = true;
    protected $_canCapture             = true;
    protected $_canCapturePartial      = false;
    protected $_canRefund              = false;
    protected $_canVoid                = false;
    protected $_canUseInternal         = false;
    protected $_canUseCheckout         = true;
    protected $_canUseForMultishipping = false;

    protected $_paymentMethod    = 'abstract';
    protected $_defaultLocale    = 'en';

//    protected $_supportedLocales = array('cn', 'cz', 'da', 'en', 'es', 'fi', 'de', 'fr', 'gr', 'it', 'nl', 'ro', 'ru', 'pl', 'sv', 'tr');
//    protected $_hidelogin        = '1';

	protected $_CHANNEL_DIRECT = array('01', '02', '04', '06', '15', '16', '18', '19');
	protected $_CHANNEL_INDIRECT = array('03', '05', '08', '14', '17', '22');

    protected $_order;
	protected $_model;

    /**
     * Get order model
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = $this->getInfoInstance()->getOrder();
        }
        return $this->_order;
    }
	
	/**
	 *
	 *
	 */
	 public function assignData($data){
         if (!($data instanceof Varien_Object)) {
             $data = new Varien_Object($data);
         }
         $info = $this->getInfoInstance();
         $info->setAdditionalInformation('installment', $data->getInstallmentOption());
		return $this;
     }

    public function validate() {
        parent::validate();
        $info = $this->getInfoInstance();
        $installment = $info->getAdditionalInformation('installment');
        return $this;
    }

    /**
     * Return url for redirection after order placed
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('oco/index/payment');
    }

    /**
     * Capture payment through DOKU api
     *
     * @param Varien_Object $payment
     * @param decimal $amount
     * @return Doku_Onecheckout_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $order_id = $this->getOrder()->getRealOrderId();
        $payment->setStatus(self::STATUS_APPROVED)
            ->setTransactionId($order_id)
            ->setIsTransactionClosed(0);
        return $this;
    }

    /**
     * Cancel payment
     *
     * @param Varien_Object $payment
     * @return Doku_Onecheckout_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        $order_id = $this->getOrder()->getRealOrderId();
        $payment->setStatus(self::STATUS_DECLINED)
            ->setTransactionId($order_id)
            ->setIsTransactionClosed(1);
        return $this;
    }

    /**
     * Return url of DOKU redirect URL
     *
     * @return string
     */
/*    public function getUrl()
    {
        if(Mage::getStoreConfig('onecheckout/settings/config_environment') == 'PRODUCTION') {
            return 'https://pay.doku.com/Suite/Receive';
        } else if (Mage::getStoreConfig('onecheckout/settings/config_environment') == 'DEVELOPMENT'){
            return 'http://luna2.nsiapay.com/Suite/Receive';
        } else {
            return 'http://staging.doku.com/Suite/Receive';		
		}
    }
*/	

    /**
     * Return url of payment method
     *
     * @return string
     */
    public function getLocale()
    {
        $locale = explode('_', Mage::app()->getLocale()->getLocaleCode());
        if (is_array($locale) && !empty($locale) && in_array($locale[0], $this->_supportedLocales)) {
            return $locale[0];
        }
        return $this->getDefaultLocale();
    }

    /**
     * prepare params array to send it to gateway page via POST
     *
     * @return array
     */
    public function getFormFields()
    {
    	Mage::log('Start redirect');
        $order_id = $this->getOrder()->getRealOrderId();
        $billing  = $this->getOrder()->getBillingAddress();
        if ($billing->getEmail()) {
            $email = $billing->getEmail();
        } else {
            $email = $this->getOrder()->getCustomerEmail();
        }
        if ($billing->getName()) {
            $name = $billing->getName();
        } else {
            $name = $this->getOrder()->getCustomerName();
        }
        $mallid = Mage::getStoreConfig('onecheckout/settings/config_mallid');
        $sharedkey = Mage::getStoreConfig('onecheckout/settings/config_sharedkey');
        $chainnumber = Mage::getStoreConfig('onecheckout/settings/config_chainnumber');
        $amount = number_format(round($this->getOrder()->getGrandTotal(), 2), 2, '.', '');
        $basket = '';
        $items = $this->getOrder()->getAllItems();
		$discountTotal = 0;
        foreach($items as $item) {
            $price = $item->getPrice();
            $quantity = $item->getQtyOrdered();
            $total = $price * $quantity;
            $basket .= $item->getName() . ',' . number_format($price, 2, '.', '') . ',' . number_format($quantity, 0, '.', '') . ',' . number_format($total, 2, '.', '') . ';';
			$discountTotal += $item->getDiscountAmount();
        }
		if($this->getOrder()->getBaseShippingInclTax() != 0) {
			$basket .= "Shipping incl Tax in base currency" . ',' . number_format($this->getOrder()->getBaseShippingInclTax(), 2, '.', '') . ',' . 1 . ',' . number_format($this->getOrder()->getBaseShippingInclTax(), 2, '.', '') . ';';
		}
		if($discountTotal != 0) {
			$basket .= "Discount" . ',' . number_format($discountTotal, 2, '.', '') . ',' . 1 . ',' . number_format($discountTotal, 2, '.', '') . ';';		
		}

        $params = array(
            'MALLID'                => $mallid,
            'CHAINMERCHANT'         => $chainnumber,
            'AMOUNT'                => $amount,
            'PURCHASEAMOUNT'        => $amount,
            'TRANSIDMERCHANT'       => $order_id,
            'WORDS'                 => sha1($amount . $mallid . $sharedkey . $order_id),
            'REQUESTDATETIME'       => Mage::getModel('core/date')->date('Ymdhis'),
            'CURRENCY'              => '360',
            'PURCHASECURRENCY'      => '360',
            'SESSIONID'             => $order_id,
            'NAME'                  => $name,
            'EMAIL'                 => $email,
            'BASKET'                => $basket,
            'PAYMENTCHANNEL'        => $this->_paymentMethod,
            'CC_NAME'               => $name,
            'ADDRESS'               => $billing->getStreet(-1),
            'CITY'                  => $billing->getCity(),
            'STATE'                 => $billing->getRegion(),
            'COUNTRY'               => $billing->getCountryModel()->getIso2Code(),
            'ZIPCODE'               => $billing->getPostcode(),
            'HOMEPHONE'             => $billing->getTelephone(),
            'MOBILEPHONE'           => $billing->getTelephone()
        );

        if(in_array($this->_paymentMethod, $this->_CHANNEL_DIRECT)) {
            $timelimit = Mage::getModel('core/date')->timestamp(time() + 60 * Mage::getStoreConfig('onecheckout/settings/config_timelimit_paynow'));
        } else {
            $timelimit = Mage::getModel('core/date')->timestamp(time() + 60 * Mage::getStoreConfig('onecheckout/settings/config_timelimit_paylater'));
        }

        $data = array(
            'redirect_time' => Mage::getModel('core/date')->date('Y-m-d H:i:s'),
            'payment_timelimit' => date('Y-m-d H:i:s', $timelimit),
            'status' => 'F',
            'state' => '1',
            'amount' => $amount,
            'invoice_no' => $order_id,
            'session_id' => $order_id,
            'currency' => '360',
            'purchase_currency' => '360',
            'payment_channel' => $this->_paymentMethod
        );

        if($this->_code == 'onecheckout_installmentbni' || $this->_code == 'onecheckout_installmentmandiri') {
            $installment = $this->getInfoInstance()->getAdditionalInformation('installment');
            $instalment_params = explode(",",$installment);
            $installmentacquirer = $instalment_params[0];
            $tenor = substr(('00' . $instalment_params[1]), -2);
            $promoid = $instalment_params[2];
            $params['INSTALLMENT_ACQUIRER'] = $installmentacquirer;
            $params['TENOR'] = $tenor;
            $params['PROMOID'] = $promoid;
            $data['installment_acquirer'] = $installmentacquirer;
            $data['tenor'] = $tenor;
            $data['promo_id'] = $promoid;
        }

		if($this->_code == 'onecheckout_bcaklikpay') {
			$curl = new Varien_Http_Adapter_Curl();
			$curl->setConfig(array('timeout' => 15));    //Timeout in no of seconds
            if(Mage::getStoreConfig('onecheckout/settings/config_environment') == 'PRODUCTION') {
                $initiate_url = 'https://pay.doku.com/Suite/ReceiveMIP';
            } else if (Mage::getStoreConfig('onecheckout/settings/config_environment') == 'DEVELOPMENT'){
                $initiate_url = 'http://luna2.nsiapay.com/Suite/ReceiveMIP';
            } else {
                $initiate_url = 'http://staging.doku.com/Suite/ReceiveMIP';
            }

            $data['initiate_time'] = Mage::getModel('core/date')->date('Y-m-d h:i:s');
			$curl->write(Zend_Http_Client::POST, $initiate_url, '1.0', array('Content-Type' => 'application/x-www-form-urlencoded'), $params);
			$result = $curl->read();
			if ($result === false) {
				Mage::log('INITIATE FAILED');
			}
			$result = substr($result, strpos($result, '<') - 1, strlen($result) - strpos($result, '<'));
			$curl->close();
			try {
				$xml = new SimpleXMLElement($result);				
				unset($params);
				$params = array();
				$redirecturl = $xml->REDIRECTURL;
				$parameters = $xml->REDIRECTPARAMETER;
				$parameters = substr($parameters, strpos($parameters, ';;'), strlen($parameters) - strpos($parameters, ';;'));
				$param_arrays = explode(";;",$parameters);
				$paramurl =  '';
				foreach ($param_arrays as $param_array) {
					$param = explode("||",$param_array);
					$params[$param[0]] = $param[1];
                    $paramurl .= $param[0] . '=' . $param[1] . '&';
				}
				$params['URL'] = $redirecturl . '?' . $paramurl;
                $data['dynamic_redirect_url'] = $redirecturl;
				$data['dynamic_redirect_parameter'] = $parameters;

			} catch (Exception $e) {
				echo $e->getMessage();
			}
		}

        if(Mage::getStoreConfig('onecheckout/settings/config_environment') == 'PRODUCTION') {
            $params['URL'] = 'https://pay.doku.com/Suite/Receive';
        } else if (Mage::getStoreConfig('onecheckout/settings/config_environment') == 'DEVELOPMENT'){
            $params['URL'] = 'http://luna2.nsiapay.com/Suite/Receive';
        } else {
            $params['URL'] = 'http://staging.doku.com/Suite/Receive';		
		}
		
        $this->_model = Mage::getModel('onecheckout/transactions');
        $this->_model->setData($data);
		$this->_model->save();

	Mage::log($params);
	Mage::log('End redirect');
        return $params;
    }
    /**
     * Get initialized flag status
     * @return true
     */
    public function isInitializeNeeded()
    {
        return true;
    }

    /**
     * Instantiate state and set it to state object
     * //@param
     * //@param
     */
    public function initialize($paymentAction, $stateObject)
    {
    	Mage::log('INITIALIZE');
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        $stateObject->setIsNotified(false);
    }

    /**
     * Get config action to process initialization
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        $paymentAction = $this->getConfigData('payment_action');
        return empty($paymentAction) ? true : $paymentAction;
    }
}