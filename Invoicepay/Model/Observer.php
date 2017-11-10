<?php

class Pro_Invoicepay_Model_Observer
{
    protected $froms = array(60, 90, 120);
    
    protected $tos = array(59, 89, 119);
    
    protected $pdf_path = '/var/tmp/invoice.pdf';
    
    public function scan()
    {
        foreach ( $this->froms as $key => $from ) {
            $to = $this->tos[$key];
            $invoices = $this->getInvoices($from, $to);
            
            foreach ($invoices as $invoice) {
                $order = $invoice->getOrder();
                $status = $order->getStatus();
                
                if ($status == 'complete_po') {
                    $invoice->sendEmailForPay(true, '', $from);
                    
                    $pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf(array($invoice), $from);
                    
                    file_put_contents($this->pdf_path, $pdf->render());
                    
                    shell_exec('lp ' . $this->pdf_path);
                }
            }
        }
    }
    
	public function getInvoices($from, $to)
	{
		$invoices = Mage::getModel('sales/order_invoice')->getCollection();
        $invoices->addAttributeToFilter('state', array('eq' => 1));
        
        $from = date('Y-m-d', strtotime('-' . $from . ' days'));
        $to = date('Y-m-d', strtotime('-' . $to . ' days'));
        
        $invoices->addAttributeToFilter('created_at', array('from' => $from, 'to' => $to));
        
        return $invoices;
	}
    
    public function changeForm(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        $type = $block->getType();
        
        if ($type == 'adminhtml/sales_order_invoice_view_form') {
            $block->setTemplate('invoicepay/changeForm.phtml');
        }
    }
}
