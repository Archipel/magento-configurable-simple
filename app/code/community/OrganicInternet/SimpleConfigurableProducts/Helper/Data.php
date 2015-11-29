<?php

class OrganicInternet_SimpleConfigurableProducts_Helper_Data
    extends Mage_Core_Helper_Abstract {
	
	public function getParentConfigurableProduct($product){
		
		if(!is_numeric($product) && is_object($product)){
			$product = $product->getId();
		}
		
		$parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($product);
		if(count($parentIds)) {
			return Mage::getModel('catalog/product')->load($parentIds[0]);
		}
		
		return null;
	}
	
}
