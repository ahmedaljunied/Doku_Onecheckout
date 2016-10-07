<?php
/**
 * Environment Multi Select Values model
 *
 * @author KDS
 */

class Doku_Onecheckout_Model_System_Config_Source_Dropdown_Values3
{
    /**
     * Define environment multi select values
     */
    public function toOptionArray() {
        return array(
            array(
                'value' => '04',
                'label' => 'DOKU Wallet'
            ),
            array(
                'value' => '15',
                'label' => 'Credit Card (MPG)'
            ),
            array(
                'value' => '01',
                'label' => 'Credit Card (IPG)'
            ),
            array(
                'value' => '15,1',
                'label' => 'Credit Card BNI Installment'
            ),
            array(
                'value' => '15,2',
                'label' => 'Credit Card Mandiri Installment'
            ),
            array(
                'value' => '02',
                'label' => 'Mandiri Clickpay'
            ),
            array(
                'value' => '24',
                'label' => 'BCA KlikPay using source of fund Bank Account'
            ),
            array(
                'value' => '18',
                'label' => 'BCA KlikPay source of fund BCA Card'
            ),
            array(
                'value' => '06',
                'label' => 'BRI e-Pay'
            ),
            array(
                'value' => '19',
                'label' => 'CIMB Clicks (Not yet implemented)'
            ),
            array(
                'value' => '05',
                'label' => 'Permata VA via DOKU Aggregator'
            ),
            array(
                'value' => '22',
                'label' => 'Sinarmas VA via DOKU Aggregator'
            ),
            array(
                'value' => '08',
                'label' => 'Mandiri Bill Payment via DOKU Aggregator'
            ),
            array(
                'value' => '14',
                'label' => 'Alfa Group (Alfamart, Alfamidi, Lawson, Dan+Dan)'
            ),
            array(
                'value' => '16',
                'label' => 'Tokenization (Not yet implemented)'
            ),
            array(
                'value' => '17',
                'label' => 'Recurring - customer initiate (Not yet implemented)'
            )
        );
    }
}