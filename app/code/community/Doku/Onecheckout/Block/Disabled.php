<?php
/**
 *
 * Create disabled input form
 *
 * @author KDS
 *
 */

class Doku_Onecheckout_Block_Disabled extends Mage_Adminhtml_Block_System_Config_Form_Field{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        $element->setDisabled('disabled');
        return parent::_getElementHtml($element);
    }
}