<?php

class Pro_Invoicepay_Model_Order_Invoice extends Pro_Customersecondemail_Model_Order_Invoice
{
    const INVOICE_EMAIL_FOR_PAY = 'invoice_email_for_pay';
    const INVOICE_REMINDER_EMAIL_30 = 'invoice_reminder_email_30';
    const INVOICE_REMINDER_EMAIL_60 = 'invoice_reminder_email_60';
    const INVOICE_REMINDER_EMAIL_90 = 'invoice_reminder_email_90';
    const PDF_PATH = '/var/tmp/invoice.pdf';
    
    public function sendEmail($notifyCustomer = true, $comment = '')
    {
        $order = $this->getOrder();
        
        if ( $order->getStatus() == 'complete_po' || $this->getState() == 1 ) {
            $this->sendEmailForPay($notifyCustomer, $comment, 0);
            
            $pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf(array($this));
                    
            file_put_contents(self::PDF_PATH, $pdf->render());
            
            shell_exec('lp ' . self::PDF_PATH);
        }
        else {
            parent::sendEmail($notifyCustomer, $comment);
        }

        return $this;
    }

    public function sendEmailForPay($notifyCustomer = true, $comment = '', $pastDue = 0)
    {
        $order = $this->getOrder();
        $storeId = $order->getStore()->getId();

        if (!Mage::helper('sales')->canSendNewInvoiceEmail($storeId)) {
            return $this;
        }
        // Get the destination email addresses to send copies to
        $copyTo = $this->_getEmails(self::XML_PATH_EMAIL_COPY_TO);
        $copyMethod = Mage::getStoreConfig(self::XML_PATH_EMAIL_COPY_METHOD, $storeId);
        // Check if at least one recepient is found
        if (!$notifyCustomer && !$copyTo) {
            return $this;
        }

        // Start store emulation process
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);

        try {
            // Retrieve specified view block from appropriate design package (depends on emulated store)
            $paymentBlock = Mage::helper('payment')->getInfoBlock($order->getPayment())
                ->setIsSecureMode(true);
            $paymentBlock->getMethod()->setStore($storeId);
            $paymentBlockHtml = $paymentBlock->toHtml();
        } catch (Exception $exception) {
            // Stop store emulation process
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            throw $exception;
        }

        // Stop store emulation process
        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

        // Retrieve corresponding email template id and customer name
        if ($order->getCustomerIsGuest()) {
            $customerName = $order->getBillingAddress()->getName();
        } else {
            $customerName = $order->getCustomerName();
        }

        $mailer = Mage::getModel('core/email_template_mailer');
        $pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf(array($this), $pastDue);
        $mailer->addAttachment($pdf,'invoice.pdf');
        if ($notifyCustomer) {
            $emailInfo = Mage::getModel('core/email_info');
            
            $secondemail = Mage::getModel('customer/customer')->load($order->getCustomerId())->getSecondemail();
            
            if ( is_null($secondemail) ):
                $emailInfo->addTo($order->getCustomerEmail(), $customerName);
            else:
                $emailInfo->addTo($secondemail, $customerName);
                $emailInfo->addTo($order->getCustomerEmail(), $customerName);
            endif;
        
            if ($copyTo && $copyMethod == 'bcc') {
                // Add bcc to customer email
                foreach ($copyTo as $email) {
                    $emailInfo->addBcc($email);
                }
            }
            $mailer->addEmailInfo($emailInfo);
        }

        // Email copies are sent as separated emails if their copy method is 'copy' or a customer should not be notified
        if ($copyTo && ($copyMethod == 'copy' || !$notifyCustomer)) {
            foreach ($copyTo as $email) {
                $emailInfo = Mage::getModel('core/email_info');
                $emailInfo->addTo($email);
                $mailer->addEmailInfo($emailInfo);
            }
        }

        // Set all required params and send emails
        $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId));
        $mailer->setStoreId($storeId);
        
        switch ($pastDue):
            case 120:
                $templateId = Mage::getModel('core/email_template')->loadByCode(self::INVOICE_REMINDER_EMAIL_90)->getId();
                break;
            case 90:
                $templateId = Mage::getModel('core/email_template')->loadByCode(self::INVOICE_REMINDER_EMAIL_60)->getId();
                break;
            case 60:
                $templateId = Mage::getModel('core/email_template')->loadByCode(self::INVOICE_REMINDER_EMAIL_30)->getId();
                break;
            case 0:
                $templateId = Mage::getModel('core/email_template')->loadByCode(self::INVOICE_EMAIL_FOR_PAY)->getId();
        endswitch;
        
        $mailer->setTemplateId($templateId);
        
        $payButton = '<div><a href="' . Mage::getUrl('invoicepay/index/index') . 'order_id/' . $order->getIncrementId() . '">Pay by Invoice</a></div>';
        $clicking_here = '<a href="' . Mage::getUrl('invoicepay/index/index') . 'order_id/' . $order->getIncrementId() . '">clicking here</a>';
        
        $mailer->setTemplateParams(array(
                'order'        => $order,
                'invoice'      => $this,
                'comment'      => $comment,
                'billing'      => $order->getBillingAddress(),
                'payment_html' => $paymentBlockHtml,
                'payButton'    => $payButton,
                'clicking_here'=> $clicking_here 
            )
        );
        $mailer->send();
        $this->setEmailSent(true);
        $this->_getResource()->saveAttribute($this, 'email_sent');

        return $this;
    }
}
