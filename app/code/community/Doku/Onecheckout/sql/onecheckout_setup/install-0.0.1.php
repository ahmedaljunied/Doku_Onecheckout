<?php
/**
 * DOKU Onecheckout installation script
 *
 * @author KDS
 */

/**
 * @var $installer Mage_Core_Model_Resource_Setup
 */
$installer = $this;

/**
 * Creating Table doku_onecheckout_transactions
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('onecheckout_transactions'))
    ->addColumn('transaction_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'identity' => true,
        'nullable' => false,
        'primary' => true
    ), 'Transaction ID')
    ->addColumn('redirect_time', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable' => true,
        'default' => null
    ), 'Redirect to DOKU timestamp')
    ->addColumn('initiate_time', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable' => true,
        'default' => null
    ), 'Initiation to DOKU timestamp')
    ->addColumn('inquiry_time', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable' => true,
        'default' => null
    ), 'Inquiry from DOKU timestamp')
    ->addColumn('identify_time', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable' => true,
        'default' => null
    ), 'Identify from DOKU timestamp')
    ->addColumn('notify_time', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable' => true,
        'default' => null
    ), 'Notify from DOKU timestamp')
    ->addColumn('notify_edu_time', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable' => true,
        'default' => null
    ), 'Notify from DOKU timestamp')
    ->addColumn('payment_datetime', Varien_Db_Ddl_Table::TYPE_VARCHAR, 14, array(
        'nullable' => true,
        'default' => null
    ), 'Payment datetime from DOKU server time')
    ->addColumn('redirect_back_time', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable' => true,
        'default' => null
    ), 'Redirect Back from DOKU timestamp')
    ->addColumn('check_status_time', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable' => true,
        'default' => null
    ), 'Check status to DOKU timestamp')
    ->addColumn('cancel_time', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable' => true,
        'default' => null
    ), 'Customer cancel at DOKU Payment Page timestamp')
    ->addColumn('reversal_time', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable' => true,
        'default' => null
    ), 'Reversal from DOKU timestamp')
    ->addColumn('payment_timelimit', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
        'nullable' => true,
        'default' => null
    ), 'Timelimit for payment, if no payment, will do check status')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_VARCHAR, 1, array(
        'nullable' => false
    ), 'Transaction status')
    ->addColumn('state', Varien_Db_Ddl_Table::TYPE_VARCHAR, 1, array(
        'nullable' => false
    ), 'Transaction last state done')
    ->addColumn('amount', Varien_Db_Ddl_Table::TYPE_BIGINT, null, array(
        'nullable' => false
    ), 'Transaction last state done')
    ->addColumn('invoice_no', Varien_Db_Ddl_Table::TYPE_VARCHAR, 30, array(
        'nullable' => false
    ), 'Invoice number from Magento')
    ->addColumn('response_code', Varien_Db_Ddl_Table::TYPE_VARCHAR, 4, array(
        'nullable' => true,
        'default' => null
    ), 'Response code from DOKU')
    ->addColumn('response_message', Varien_Db_Ddl_Table::TYPE_VARCHAR, 30, array(
        'nullable' => true,
        'default' => null
    ), 'Response message from DOKU')
    ->addColumn('approval_code', Varien_Db_Ddl_Table::TYPE_VARCHAR, 20, array(
        'nullable' => true,
        'default' => null
    ), 'Approval code from Acquirer')
    ->addColumn('payment_channel', Varien_Db_Ddl_Table::TYPE_VARCHAR, 2, array(
        'nullable' => true,
        'default' => null
    ), 'Chosen payment channel by Customer')
    ->addColumn('payment_code', Varien_Db_Ddl_Table::TYPE_VARCHAR, 16, array(
        'nullable' => true,
        'default' => null
    ), 'Payment code from Virtual Account payment')
    ->addColumn('session_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 48, array(
        'nullable' => false
    ), 'Session ID from Magento')
    ->addColumn('bank', Varien_Db_Ddl_Table::TYPE_VARCHAR, 100, array(
        'nullable' => true,
        'default' => null
    ), 'Issuer Bank Name from DOKU')
    ->addColumn('mcn', Varien_Db_Ddl_Table::TYPE_VARCHAR, 16, array(
        'nullable' => true,
        'default' => null
    ), 'Masked Card Number from DOKU')
    ->addColumn('verify_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 30, array(
        'nullable' => true,
        'default' => null
    ), 'Verify ID from DOKU')
    ->addColumn('verify_score', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => true,
        'default' => null
    ), 'Verify Score from DOKU')
    ->addColumn('verify_status', Varien_Db_Ddl_Table::TYPE_VARCHAR, 10, array(
        'nullable' => true,
        'default' => null
    ), 'Verify Status from DOKU')
    ->addColumn('dynamic_redirect_url', Varien_Db_Ddl_Table::TYPE_VARCHAR, 100, array(
        'nullable' => true,
        'default' => null
    ), 'Redirect URL from DOKU')
    ->addColumn('dynamic_redirect_parameter', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable' => true,
        'default' => null
    ), 'Redirect parameter from DOKU')
    ->addColumn('currency', Varien_Db_Ddl_Table::TYPE_VARCHAR, 3, array(
        'nullable' => false
    ), 'Currency used, 360 = IDR')
    ->addColumn('purchase_currency', Varien_Db_Ddl_Table::TYPE_VARCHAR, 3, array(
        'nullable' => false
    ), 'Currency used, 360 = IDR')
    ->addColumn('brand', Varien_Db_Ddl_Table::TYPE_VARCHAR, 10, array(
        'nullable' => true,
        'default' => null
    ), 'VISA / MASTER / JCB')
    ->addColumn('cardholder_name', Varien_Db_Ddl_Table::TYPE_VARCHAR, 50, array(
        'nullable' => true,
        'default' => null
    ), 'Cardholder Name from DOKU')
    ->addColumn('threedsecure_status', Varien_Db_Ddl_Table::TYPE_VARCHAR, 5, array(
        'nullable' => true,
        'default' => null
    ), '3D Secure Status from DOKU')
    ->addColumn('edu_status', Varien_Db_Ddl_Table::TYPE_VARCHAR, 10, array(
        'nullable' => true,
        'default' => null
    ), 'EDU Status from DOKU')
    ->addColumn('installment_acquirer', Varien_Db_Ddl_Table::TYPE_VARCHAR, 3, array(
        'nullable' => true,
        'default' => null
    ), 'Installment Acquirer, if used')
    ->addColumn('tenor', Varien_Db_Ddl_Table::TYPE_VARCHAR, 2, array(
        'nullable' => true,
        'default' => null
    ), 'Installment Tenor, if used')
    ->addColumn('promo_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 3, array(
        'nullable' => true,
        'default' => null
    ), 'Promo ID, if used')
;
$installer->getConnection()->createTable($table);
