<?php

class Pro_Invoicepay_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        if (!Mage::helper('customer')->isLoggedIn()) {
            Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('customer/account'));
        }
        
        $this->loadLayout()
             ->getLayout()->getBlock('head')->setTitle($this->__('Invoice Pay'));
        $this->renderLayout();
    }
    
    public function payAction()
    {
        try {
            $order_id = $this->getRequest()->getParam('order_id');        
            $data = $this->getRequest()->getParam('payment');
            
            $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
            $orderEntityId = $order->getEntityId();
            
            $invoiceIds = $order->getInvoiceCollection()->getAllIds();
            $invoice = Mage::getModel('sales/order_invoice')->load($invoiceIds[0]);
            
            $quoteId = $order->getQuoteId();
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            
            $payment = $quote->getPayment();
            $payment->importData($data);
            $payment->setOrder($order);
            $methodInstance = $payment->getMethodInstance();
            $methodInstance->setStore($order->getStoreId())->capture($payment, $order->getGrandTotal());
            
            $invoice->setState(2)->save();
            
            $result['success'] = true;
            $result['orderId'] = $orderEntityId;
        } catch (Mage_Core_Exception $e) {
            $result['success'] = false;
            $result['error_messages'] = $e->getMessage();
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}
