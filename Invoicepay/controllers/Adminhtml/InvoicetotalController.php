<?php

class Pro_Invoicepay_Adminhtml_InvoicetotalController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('invoicepay/invoicetotal');
    }
    
    protected function _initInvoice()
    {
        $invoice = false;
        $invoiceId = $this->getRequest()->getParam('invoice_id');
        
        if ($invoiceId) {
            $invoice = Mage::getModel('sales/order_invoice')->load($invoiceId);
            if (!$invoice->getId()) {
                $this->_getSession()->addError($this->__('The invoice no longer exists.'));
                return false;
            }
        }

        Mage::register('current_invoice', $invoice);
        return $invoice;
    }
    
    public function editAction()
    {
        $invoice = $this->_initInvoice();
        
        if ($invoice) {
            $this->loadLayout();
            $this->renderLayout();
        }
        else {
            $this->_forward('noRoute');
        }
    }
    
    public function saveAction()
    {
        $param = $this->getRequest()->getPost();
        $invoice = $this->_initInvoice();
        $taxInfo = $invoice->getOrder()->getFullTaxInfo();
        $taxPercent = 0;
        
        if (count($taxInfo) > 0) {
            $taxPercent = $taxInfo[0]['percent'];
        }
        
        if ($invoice) {
            $origin_data = $invoice->getData();
            
            if ($param['discount_amount'] == '') {
                $param['discount_amount'] = 0;
            }
            
            $param['discount_amount'] = -1 * $param['discount_amount'];
            
            $invoice->setDiscountAmount($param['discount_amount']);
            $invoice->setBaseDiscountAmount($param['discount_amount']);
            
            $new_tax_amount = ($origin_data['subtotal'] + $param['discount_amount']) * $taxPercent / 100;
            
            $invoice->setTaxAmount($new_tax_amount);
            $invoice->setBaseTaxAmount($new_tax_amount);
            $invoice->setSubtotalInclTax($origin_data['subtotal'] + $new_tax_amount);
            $invoice->setBaseSubtotalInclTax($origin_data['base_subtotal'] + $new_tax_amount);
            $invoice->setGrandTotal($origin_data['grand_total'] - $origin_data['discount_amount'] + $param['discount_amount'] - $origin_data['tax_amount'] + $new_tax_amount);
            $invoice->setBaseGrandTotal($origin_data['base_grand_total'] - $origin_data['base_discount_amount'] + $param['discount_amount'] - $origin_data['base_tax_amount'] + $new_tax_amount);
            $invoice->save();
            
            $this->_redirect('*/sales_invoice/view', array('invoice_id' => $invoice->getId()));
        }
        else {
            $this->_forward('noRoute');
        }
    }
}
