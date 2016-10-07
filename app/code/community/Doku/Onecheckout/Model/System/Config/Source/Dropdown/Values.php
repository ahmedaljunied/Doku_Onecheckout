<?php
/**
 * Environment Multi Select Values model
 *
 * @author KDS
 */

class Doku_Onecheckout_Model_System_Config_Source_Dropdown_Values
{
    /**
     * Define environment multi select values
     */
    public function toOptionArray() {
        return array(
            array(
                'value' => 'STAGING',
                'label' => 'STAGING - staging.doku.com'
            ),
            array(
                'value' => 'PRODUCTION',
                'label' => 'PRODUCTION - pay.doku.com'
            ), 
			array (
				'value' => 'DEVELOPMENT',
				'label' => 'DEVELOPMENT - luna2.nsiapay.com'
			)
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            0 => 'STAGING',
            1 => 'PRODUCTION',
			2 => 'DEVELOPMENT'
        );
    }

}