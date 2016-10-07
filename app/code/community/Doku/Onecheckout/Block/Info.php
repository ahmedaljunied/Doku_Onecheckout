<?php
/**
 * Abstract Model
 *
 * @author KDS
 */
class Doku_Onecheckout_Block_Info  extends Mage_Payment_Block_Info
{
    /**
     * Constructor. Set template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('onecheckout/info.phtml');
    }

    /**
     * Returns code of payment method
     *
     * @return string
     */
    public function getMethodCode()
    {
        return $this->getInfo()->getMethodInstance()->getCode();
    }
	
	protected function _prepareSpecificInformation($transport = null) {
		if (null !== $this->_paymentSpecificInformation) {
			return $this->_paymentSpecificInformation;
		}
		$data = array();
		if ($this->getInfo()->getInstallment()) {
			$tenor = $this->getInfo()->getInstallment();
		}
    
		$transport = parent::_prepareSpecificInformation($transport);
		return $transport->setData(array_merge($data, $transport->getData()));
  }	
}

