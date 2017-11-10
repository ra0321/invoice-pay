<?php

class Pro_Invoicepay_Block_Cards extends Mage_Core_Block_Template
{
	protected $_cards		= null;
	
	public function getStoredCards()
	{
		if( is_null( $this->_cards ) ) {
			$customer = Mage::helper('tokenbase')->getCurrentCustomer();
			
			if( Mage::app()->getStore()->isAdmin() || $customer && $customer->getId() > 0 ) {
				$this->_cards = Mage::helper('tokenbase')->getActiveCustomerCardsByMethod('authnetcim');
			}
			else {
				$this->_cards = array();
			}
		}
		
		return $this->_cards;
	}
    
    public function haveStoredCards()
    {
        $cards = $this->getStoredCards();
        
        return ( count( $cards ) > 0 ? true : false );
    }
    
    public function getCcAvailableTypes()
    {
        $config   = Mage::getConfig()->getNode('global/payment/cc/types')->asArray();
        $avail    = explode( ',', Mage::helper('payment')->getMethodInstance( 'authnetcim' )->getConfigData('cctypes') );
        
        $types    = array();
        foreach( $config as $data ) {
            if( in_array( $data['code'], $avail ) !== false ) {
                $types[ $data['code'] ] = $data['name'];
            }
        }
        
        return $types;
    }
    
    public function getCcMonths()
    {
        $months = Mage::app()->getLocale()->getTranslationList('month');
        foreach( $months as $key => $value ) {
            $monthNum        = ($key < 10) ? '0' . $key : $key;
            $months[ $key ]    = $monthNum . ' - ' . $value;
        }
        
        return $months;
    }
    
    public function getCcYears()
    {
        $first    = date("Y");
        $years    = array();
        for( $index=0; $index <= 10; $index++ ) {
            $years[ $first + $index ] = $first + $index;
        }
        
        return $years;
    }
}
