<?php
class OrganicInternet_SimpleConfigurableProducts_Model_Observer {
	
	public function quoteItemChangeSimpleProduct(arien_Event_Observer $observer){
		$params  = Mage::app()->getRequest()->getParams();
		$qi = $observer->getData('quote_item');
		if(!empty($pid = $params['product']) && $pid != $qi->getProduct()->getId()){
			$qi->setProduct(Mage::getModel('catalog/product')->load($pid));
		}
	}
	
}