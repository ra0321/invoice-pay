<?php

class Pro_Invoicepay_Block_Adminhtml_Invoicetotal_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    protected $invoice;
    
    public function __construct()
    {
        $this->_blockGroup = 'pro_invoicepay';
        $this->_controller = 'adminhtml_invoicetotal';
        
        $this->invoice = Mage::registry('current_invoice');
        
        $this->_headerText = Mage::helper('pro_invoicepay')->__('Edit Invoice #%s Totals', $this->invoice->getIncrementId());
        
        parent::__construct();
        
        $this->_removeButton('reset');
    }
    
    public function getBackUrl()
    {
        return $this->getUrl('*/sales_invoice/view', array('invoice_id' => $this->invoice->getId()));
    }
}