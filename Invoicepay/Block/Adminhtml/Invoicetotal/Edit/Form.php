<?php
/**
 * Compliance List admin edit form block
 *
 * @author Magento
 */
class Pro_Invoicepay_Block_Adminhtml_Invoicetotal_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('invoicepay/editform.phtml');
    }
    
    protected function _prepareForm()
    {
        $invoice = Mage::registry('current_invoice');
        
        $form = new Varien_Data_Form(
            array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save', array('invoice_id' => $invoice->getId())),
                'method' => 'post',
            )
        );
         
        $form->setUseContainer(true);
        $this->setForm($form);
        
        $helper = Mage::helper('pro_invoicepay');
        
        $fieldset = $form->addFieldset('main', array(
            'no_container' => true
        ));
         
        $fieldset->addField('discount_amount', 'text', array(
            'name' => 'discount_amount',
            'label' => $helper->__('Discount Amount'),
            'required' => false,
            'class' => 'validate-zero-or-greater',
            'value' => Mage::getModel('directory/currency')->format(abs($invoice->getDiscountAmount()), array('display'=>Zend_Currency::NO_SYMBOL), false)
        ));
         
        return parent::_prepareForm();
    }
    
    public function getHeaderText()
    {
        return Mage::helper('pro_invoicepay')->__('Invoice Totals Information');
    }
}