<?php
/**
 *
 * Create disabled input form
 *
 * @author KDS
 *
 */

class Doku_Onecheckout_Block_Notify extends Mage_Adminhtml_Block_System_Config_Form_Field{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        $element->setReadonly(true);
		$existing = Mage::getConfig()->getNode('default/onecheckout/info')->notifyurl;
		$baseurl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		if(strpos($existing, $baseurl) == false) {
			$element->setData('value', Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'oco/index/notify');
		}
        return parent::_getElementHtml($element);
    }
}