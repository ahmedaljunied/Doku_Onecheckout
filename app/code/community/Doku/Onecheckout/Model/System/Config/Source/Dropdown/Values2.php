<?php
/**
 * Environment Multi Select Values model
 *
 * @author KDS
 */

class Doku_Onecheckout_Model_System_Config_Source_Dropdown_Values2
{
    /**
     * Define environment multi select values
     */
    public function toOptionArray() {
        return array(
            array(
                'value' => '15',
                'label' => 'MPG'
            ),
            array(
                'value' => '01',
                'label' => 'IPG'
            )
        );
    }
}