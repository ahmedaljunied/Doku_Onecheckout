<?php
/**
 * Block Form in payment options
 *
 * @author KDS
 */
class Doku_Onecheckout_Block_Form extends Mage_Payment_Block_Form
{
    /**
     * Constructor. Set template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('onecheckout/form.phtml');
    }
	
    public function getPaymentImageSrc($payment)
    {
        $imageFilename = Mage::getDesign()
            ->getFilename('images' . DS . 'onecheckout' . DS . $payment, array('_type' => 'skin'));

        if (file_exists($imageFilename . '.png')) {
            return $this->getSkinUrl('images/onecheckout/' . $payment . '.png');
        } else if (file_exists($imageFilename . '.gif')) {
            return $this->getSkinUrl('images/onecheckout/' . $payment . '.gif');
        } else if (file_exists($imageFilename . '.jpg')) {
            return $this->getSkinUrl('images/onecheckout/' . $payment . '.jpg');
        } else {
            return $this->getSkinUrl('images/onecheckout/doku.png');
        }
    }
	
	public function getInstallmentOptions($_code)
	{
		$bank = str_replace('onecheckout_installment', '', $_code);
		$options = array();
		$tenorStr = Mage::getStoreConfig('onecheckout/channel_' . $bank . '_installment/tenor');
		$tenorStr = preg_replace('/\s+/', '', $tenorStr);
		$tenors = explode(',', $tenorStr);
		$tenorLength = sizeof($tenors);
		$planStr = Mage::getStoreConfig('onecheckout/channel_' . $bank . '_installment/promoid');
		$planStr = preg_replace('/\s+/', '', $planStr);
		$plans = explode(',' , $planStr);
		$planLength = sizeof($plans);
		$installmentacquirer = Mage::getStoreConfig('onecheckout/channel_' . $bank . '_installment/installment_acquirer');
		if($tenorLength > $planLength) $length = $planLength;
		else $length = $tenorLength;
		for($i = 0; $i < $length; $i++) {
			$options[$tenors[$i]] = $installmentacquirer . ',' . $tenors[$i] . ',' . $plans[$i];
		}
		return $options;
	}	
}
