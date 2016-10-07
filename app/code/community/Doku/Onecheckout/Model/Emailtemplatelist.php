<?php
/**
 *
 * get List of transaction email template
 *
 * @author KDS
 *
 */

class Doku_Onecheckout_Model_Emailtemplatelist {

    public function toOptionArray() {
        $result = array();
        $template_collection =  Mage::getResourceSingleton('core/email_template_collection');
        if(!empty($template_collection)) {
            $cnt = 0;
            foreach ($template_collection as $template) {
                $result[$cnt]['value'] = $template->getTemplateId();
                $result[$cnt]['label'] = $template->getTemplateCode();
                $cnt++;
            }
        }
        return $result;
    }
}