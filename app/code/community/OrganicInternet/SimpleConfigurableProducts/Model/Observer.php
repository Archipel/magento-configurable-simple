<?php
class OrganicInternet_SimpleConfigurableProducts_Model_Observer {
	
	public function quoteItemChangeSimpleProduct(arien_Event_Observer $observer){
		$params  = Mage::app()->getRequest()->getParams();
		$qi = $observer->getData('quote_item');
		if(!empty($pid = $params['product']) && $pid != $qi->getProduct()->getId()){
			$qi->setProduct(Mage::getModel('catalog/product')->load($pid));
		}
	}
	
	public function addParentCustomOptions($observer){
		$transport = $observer->getTransport();
		$buyRequest = $observer->getBuyRequest();
		$product = $observer->getProduct();
		$parent = Mage::helper('simpleconfigurableproducts')->getParentConfigurableProduct($product);
		if($parent && $transport && $buyRequest){
			foreach($parent->getOptions() as $o){
				$id = $o->getId();
				if(!array_key_exists($id, $transport->options) && array_key_exists($id, $buyRequest->options)){
					$transport->options[$id] = $buyRequest->options[$id];
				} 
			}
		}
	}
	
}