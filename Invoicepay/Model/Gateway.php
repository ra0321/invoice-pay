<?php

class Pro_Invoicepay_Model_Gateway extends ParadoxLabs_AuthorizeNetCim_Model_Gateway
{
	protected function _runTransaction( $request, $params )
	{
		$auth = array(
			'@attributes'				=> array(
				'xmlns'						=> 'AnetApi/xml/v1/schema/AnetApiSchema.xsd',
			),
			'merchantAuthentication'	=> array(
				'name'						=> $this->getParameter('loginId'),
				'transactionKey'			=> $this->getParameter('transactionKey'),
			)
		);
		
		$xml = $this->_arrayToXml( $request, $auth + $params );
		
		$this->_lastRequest = $xml;
		
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $this->_endpoint );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml") );
		curl_setopt( $curl, CURLOPT_HEADER, 0 );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $xml );
		curl_setopt( $curl, CURLOPT_POST, 1 );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $curl, CURLOPT_TIMEOUT, 15 );
		$this->_lastResponse = curl_exec( $curl );
        
		if( $this->_lastResponse && !curl_errno( $curl ) ) {
			$this->_log .= 'REQUEST: ' . $this->_sanitizeLog( $xml ) . "\n";
			$this->_log .= 'RESPONSE: ' . $this->_sanitizeLog( $this->_lastResponse ) . "\n";
			
			$this->_lastResponse = $this->_xmlToArray( $this->_lastResponse );
			
			/**
			 * Check for basic errors.
			 */
			if( $this->_lastResponse['messages']['resultCode'] != 'Ok' ) {
				$errorCode		= $this->_lastResponse['messages']['message']['code'];
				$errorText		= $this->_lastResponse['messages']['message']['text'];
				
				/**
				 * Log and spit out generic error. Skip certain warnings we can handle.
				 */
				$okayErrorCodes	= array( 'E00039', 'E00040' );
				$okayErrorTexts	= array( 'The referenced transaction does not meet the criteria for issuing a credit.', 'The transaction cannot be found.' );
				
				if( !empty($errorCode) && !in_array( $errorCode, $okayErrorCodes ) && !in_array( $errorText, $okayErrorTexts ) ) {
					Mage::helper('tokenbase')->log( $this->_code, sprintf( "API error: %s: %s\n%s", $errorCode, $errorText, $this->_log ) );
					throw Mage::exception( 'Mage_Payment_Model_Info', Mage::helper('tokenbase')->__( sprintf( 'Authorize.Net CIM Gateway: %s (%s)', $errorText, $errorCode ) ) );
				}
			}
			
			curl_close($curl);
		}
		else {
			Mage::helper('tokenbase')->log( $this->_code, sprintf( 'CURL Connection error: ' . curl_error($curl) . ' (' . curl_errno($curl) . ')' . "\n" . 'REQUEST: ' . $this->_sanitizeLog( $xml ) ) );
			Mage::throwException( Mage::helper('tokenbase')->__( sprintf( 'Authorize.Net CIM Gateway Connection error: %s (%s)', curl_error($curl), curl_errno($curl) ) ) );
		}
		
		return $this->_lastResponse;
	}
}
