<?php

class WeynWebWorks_ProductSizes_Model_Observer_Exception extends Exception {
	
}

class WeynWebWorks_ProductSizes_Model_Observer {

	/**
	 * @param Varien_Event_Observer $observer
	 */
	public function saveProductSizes($observer){
		
		/* @var $product Mage_Catalog_Model_Product */
		$product = $observer->getEvent()->getProduct();
		$params  = Mage::app()->getRequest()->getParams();
		
		$parent = null;
		if($product->getTypeID() == 'simple'){
			$parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
			if(count($parentIds)){
				$parent = Mage::getModel('catalog/product')->load($parentIds[0]);
			}
		}
		
		if(!$parent){
			if(!array_key_exists('product_sizes_dimensions', $params)){
				return;
			}
			
			$product->setData('product_sizes_multiple', array_key_exists('product_sizes_multiple', $params) && $params['product_sizes_multiple'] == 1);
			$product->setData('base_price_is_min', array_key_exists('base_price_is_min', $params) && $params['base_price_is_min'] == 1);
			$product->setData('product_sizes_lengtevracht', array_key_exists('product_sizes_lengtevracht', $params) && $params['product_sizes_lengtevracht'] == 1);
			$product->setData('product_sizes_total_unit', array_key_exists('product_sizes_total_unit', $params) ? trim($params['product_sizes_total_unit']) : '');
			$product->setData('product_sizes_total_factor', ($product->getProductSizesTotalUnit() != '' && array_key_exists('product_sizes_total_factor', $params) && $params['product_sizes_total_factor'] > 0) ? $params['product_sizes_total_factor'] : 1);
			
			$d = (int)$params['product_sizes_dimensions'];
			$product->setData('product_sizes_dimensions', $d);
			for($i = 1; $i <= 3; $i++){
				$valid = ($i <= $d);
				$product->setData('product_sizes_unit'.$i, ($valid ? $params['product_sizes_unit'.$i] : ''));
				$product->setData('product_sizes_included'.$i, ($valid ? floatval($params['product_sizes_included'.$i]) : 0));
				$product->setData('product_sizes_name'.$i, ($valid ? $params['product_sizes_name'.$i] : ''));
			}
			
			if(array_key_exists('product_sizes_predefined_deleted', $params)){
				foreach($params['product_sizes_predefined_deleted'] as $i){
					Mage::getModel('www_productsizes/product_size')->setId($i)->delete();
				}
			}
			
			if(array_key_exists('product_sizes', $params)){
				foreach($params['product_sizes'] as $psri => $psv){
					/* @var $ps WeynWebWorks_ProductDesign_Model_Product_Sizes */ 
					$ps = Mage::getModel('www_productsizes/product_size');
					if(array_key_exists('id', $psv)){
						$psn = $ps->load($psv['id']);
						if($psn != null){
							$ps = $psn;
						}
					}
					$ps->setProduct($product);
					$ps->setName(trim($psv['name']));
					if(!array_key_exists('price_fixed', $psv)){
						$psv['price_fixed'] = 0;
					}
					if(!array_key_exists('price_circumference', $psv)){
						$psv['price_circumference'] = 0;
					}
					if(!array_key_exists('price_surface', $psv)){
						$psv['price_surface'] = 0;
					}
					$ps->setPriceFixed(doubleval(str_replace(',', '.', strtolower(trim($psv['price_fixed'])))));
					$ps->setPriceCircumference(doubleval(str_replace(',', '.', strtolower(trim($psv['price_circumference'])))));
					$ps->setPriceSurface(doubleval(str_replace(',', '.', strtolower(trim($psv['price_surface'])))));
					if(!array_key_exists('price_fixed_abs', $psv)){
						$psv['price_fixed_abs'] = 1;
					}
					if(!array_key_exists('price_circumference_abs', $psv)){
						$psv['price_circumference_abs'] = 1;
					}
					if(!array_key_exists('price_surface_abs', $psv)){
						$psv['price_surface_abs'] = 1;
					}
					$ps->setPriceFixedAbs($psv['price_fixed_abs']);
					$ps->setPriceCircumferenceAbs($psv['price_circumference_abs']);
					$ps->setPriceSurfaceAbs($psv['price_surface_abs']);
					
					$ps->setWidth($psv['width']);
					$ps->setHeight($psv['height']);
					$ps->setDepth($psv['depth']);
					$ps->setWidthmarge($psv['widthmarge']);
					$ps->setHeightmarge($psv['heightmarge']);
					$ps->setDepthmarge($psv['depthmarge']);
					
					$ps->save();
				}
			}
		}
		else if(array_key_exists('product_sizes', $params)){
			foreach($params['product_sizes'] as $psri => $psv){
				/* @var $ps WeynWebWorks_ProductSizes_Model_Product_Size */ 
				$ps = Mage::getModel('www_productsizes/product_size');
				if(array_key_exists('id', $psv)){
					$ps = $ps->load($psv['id']);
					
					$psp = $ps->getProductPrices($product->getId());
					if(!$psp){
						$psp = Mage::getModel('www_productsizes/product_size_price');
						$psp->setProductId($product->getId());
						$psp->setSizeId($ps->getId());
					}
					foreach(array('fixed', 'circumference', 'surface', 'volume') as $key){
						if(array_key_exists("price_$key", $psv) && strlen(trim($psv["price_$key"])) > 0){
							$psp->setData("price_$key", doubleval(str_replace(',', '.', strtolower(trim($psv["price_$key"])))));
							if(!array_key_exists("price_{$key}_abs", $psv)){
								$psv["price_{$key}_abs"] = 1;
							}
							$psp->setData("price_{$key}_abs", $psv["price_{$key}_abs"]);
						}
						else{
							$psp->setData("price_$key", new Zend_Db_Expr("NULL"));
							$psp->setData("price_{$key}_abs", new Zend_Db_Expr("NULL"));
						}
					}
					$psp->save();

					$psm = $ps->getProductMargins($product->getId());
					if(!$psm){
						$psm = Mage::getModel('www_productsizes/product_size_margin');
						$psm->setProductId($product->getId());
						$psm->setSizeId($ps->getId());
					}
					foreach(array('widthmarge', 'heightmarge', 'depthmarge') as $key){
						if(array_key_exists($key, $psv) && strlen(trim($psv[$key])) > 0){
							$psm->setData($key, doubleval($psv[$key]));
						}
						else{
							$psm->setData($key, new Zend_Db_Expr("NULL"));
						}
					}
					$psm->save();
				}
			}
		}
	}

	private static function _getCustomSizeOptionValue($id, $size, $ndims){
		$request = Mage::app()->getFrontController()->getRequest();
		$dims = array();
		for($i = 0; $i < $ndims; $i++){
			$fn = WeynWebWorks_ProductSizes_Model_Product_Size::$fieldNamePerDimension[$i];
			$dims[$i] = $size->getData($fn);
			if($dims[$i] == 'custom'){
				$dims[$i] = $request->getParam("size_option_{$id}_custom_$fn");
			}
		}
		if(count($dims)){
			return ':'.implode(';', $dims);
		}
	}
	
	/**
	 * @param Varien_Event_Observer $observer
	 */
	public function quoteItemSetSize($observer){
		
		/* @var $product Mage_Catalog_Model_Product */
		$sizesproduct = $product = $observer->getData('product');
		
		$sizes = Mage::getModel('www_productsizes/product_size')->getCollection()->addFieldToFilter('product_id', $product->getId());
		
		if(!count($sizes)){
			$parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
			if(count($parentIds)){
				$sizesproduct = Mage::getModel('catalog/product')->load($parentIds[0]);
				$sizes = Mage::getModel('www_productsizes/product_size')->getCollection()->addFieldToFilter('product_id', $sizesproduct->getId());
			}
			
			// skip if the product has no sizes
			if(!count($sizes)){
				return;
			}
		}

		/* @var $quoteItem Mage_Sales_Model_Quote_Item */
		$quoteItem = $observer->getData('quote_item');
		$request = $observer->getInfo();
		
		if($request && $orderItemId = $request->getOrderItemId()){
			$orderItem = Mage::getModel('sales/order_item')->load($orderItemId);
			$oldOption = Mage::getModel('sales/quote_item_option')->getCollection()
        		->addFilter('code', 'www_productsize')
        		->addFilter('item_id', $orderItem->getQuoteItemId())
        		->getFirstItem();
			$option = array('product_id' => $oldOption->getProductId(), 'code' => 'www_productsize', 'value' => $oldOption->getValue());
			$quoteItem->addOption($option);
			if($sizesproduct->getProductSizesMultiple()){
				$tot = 0;
				$parts = explode('|', $oldOption->getValue());
				foreach($parts as $p){
					$n = explode(',', $p)[1];
					$tot += intval($n);
				}
				
			}
			$quoteItem->setQty($tot);
			while($quoteItem->getParentItem() != null){
			  $quoteItem = $quoteItem->getParentItem();
			  $quoteItem->addOption($option);
			  $quoteItem->setQty($tot);
			}
		}
		else{
			$request = Mage::app()->getRequest();
			if(!$sizesproduct->getProductSizesMultiple()){
				$sizeId = intval($request->getParam('options')['size']);
				if($sizeId && $size = Mage::getModel('www_productsizes/product_size')->load($sizeId)){
					$optionString = $sizeId.$this->_getCustomSizeOptionValue($sizeId, $size, $sizesproduct->getProductSizesDimensions());
					$option = array('product_id' => $product->getId(), 'code' => 'www_productsize', 'value' => $optionString);
					$quoteItem->addOption($option);
					while($quoteItem->getParentItem() != null){
					  $quoteItem = $quoteItem->getParentItem();
					  $quoteItem->addOption($option);
					}
				}
				else{
					throw new WeynWebWorks_ProductSizes_Model_Observer_Exception(Mage::helper('core')->__('Please select or enter a valid size'));
				}
			}
			else{
				$strs = array();
				$tot = 0;
				foreach($request->getParam('options')['size'] as $id => $amount){
					if($amount){
						$tot += $amount;
						$size   = Mage::getModel('www_productsizes/product_size')->load($id);
						$strs[] = "$id,$amount".$this->_getCustomSizeOptionValue($id, $size, $sizesproduct->getProductSizesDimensions());
					}
				}
				$option = array('product_id' => $product->getId(), 'code' => 'www_productsize', 'value' => implode('|', $strs));
				$quoteItem->addOption($option);
				$quoteItem->setQty($tot);
				while($quoteItem->getParentItem() != null){
				  $quoteItem = $quoteItem->getParentItem();
				  $quoteItem->addOption($option);
				  $quoteItem->setQty($tot);
				}
			}
		}
	}

	/**
	 * @param Varien_Event_Observer $observer
	 */
	public function productUpdatePrice($observer){
		/* @var $product Mage_Catalog_Model_Product */
		/* @var $size WeynWebWorks_ProductSizes_Model_Product_Size */
		$product = $observer->getProduct();
		$product->load($product->getId());
		$size = $product->getCustomOption('www_productsize');
		
		if($size){
			$sizeindependentprice = 0;
			
		    if(!$product->getProductSizesMultiple()){
			    $size = Mage::getModel('www_productsizes/product_size')->getFromOptionValue($size->getValue());	
			    if($size){
			    	$finalPrice = $observer->getCaller()->getCurrentPriceWithOptions() - $observer->getCaller()->getCurrentPriceFixonceOptions() - $sizeindependentprice;
			    	$basePrice = $observer->getBasePrice() - $sizeindependentprice;
			      	$sizePrice = $size->getPrice($basePrice, $finalPrice, $product);
					$observer->getCaller()->addToFinalPrice($sizePrice);
			    }
		    }
		    else{
		    	$sizes = explode('|', $size->getValue());
		    	$tot = 0;
		    	$n = 0;
		    	foreach($sizes as $size){
			    	$size = Mage::getModel('www_productsizes/product_size')->getFromOptionValue($size);
			    	$finalPrice = $observer->getCaller()->getCurrentPriceWithOptions() - $observer->getCaller()->getCurrentPriceFixonceOptions() - $sizeindependentprice;
			    	$basePrice = $observer->getBasePrice() - $sizeindependentprice;
			       	$tot += $size->getAmount()*$size->getPrice($basePrice, $finalPrice, $product);
			       	$n += $size->getAmount();
		    	}
		    	if($n){
					$observer->getCaller()->addToFinalPrice($tot/$n);
		    	}
		    }
		}
	}

	/**
	 * @param Varien_Event_Observer $observer
	 */
	public function cartItemRenderOptions($observer){
        $item = $observer->getQuoteItem();
        if($item){
        	$size = $item->getOptionByCode('www_productsize');
        }
        else{
        	$itemId = $observer->getQuoteItemId();
        	if($itemId){
        		$size = Mage::getModel('sales/quote_item_option')->getCollection()
        		->addFilter('code', 'www_productsize')
        		->addFilter('item_id', $itemId)
        		->getFirstItem();
        	}
        }
        
		if($size && $size->getValue()){
	    	$product = Mage::getModel('catalog/product')->load($size->getProductId());
			$size = $size->getValue();
		    $option = array();

		    $format = 'html';
        	$c = (new ReflectionClass($observer->getRenderer()));
		    if($c->hasConstant('DISPLAY_FORMAT') && $c->getConstant('DISPLAY_FORMAT')){
		    	$format = $c->getConstant('DISPLAY_FORMAT');
		    }
		    if($format == 'html'){
		    	$linesep = '<br/>';
		    }
		    else{
		    	$linesep = ', ';
		    }
		    
		    if(!$product->getProductSizesMultiple()){
		        $option['label'] = Mage::helper('core')->__('Size');
		        
			    $size = Mage::getModel('www_productsizes/product_size')->getFromOptionValue($size);
		        if(!$size){
		        	return;
		        }
       			$option['print_value'] = $option['value'] = $size->getRenderString($format);
		    }
		    else{
		        $option['label'] = Mage::helper('core')->__('Sizes');
		        $sizes = explode('|', $size);
		        $strs = array();
		        foreach($sizes as $size){
				    $size = Mage::getModel('www_productsizes/product_size')->getFromOptionValue($size);
			        if(!$size || !$size->getAmount()){
			        	continue;
			        }
			        $strs[] = $size->getRenderString($format);
		        }
       			$option['print_value'] = $option['value'] = Mage::helper('core')->__(implode($linesep, $strs));
		    }
			$observer->getRenderer()->addOptionToRender($option);
		}
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function salesOrderItemRendererOptions($observer){
    	$quoteItemId = $observer->getOrderItem()->getQuoteItemId();
    	if($quoteItemId){
    		$observer->setData('quote_item_id', $quoteItemId);
    		return $this->cartItemRenderOptions($observer);
    	}
    }

	/**
	 * Remove hidden tabs from product edit
	 * event: core_block_abstract_prepare_layout_after
	 *
	 * @param Varien_Event_Observer $event
	 */
	public function removeTabs(Varien_Event_Observer $event){
		$block = $event->getBlock();
		if (!$block instanceof Mage_Adminhtml_Block_Catalog_Product_Edit_Tabs) {
			return;
		}
	
		foreach($block->getTabsIds() as $id){
			if($block->getTabLabel($id) == 'Afmetingen'){
				$block->removeTab($id);
			}
		}
		
		//$block->removeTab('group_35');
		//$block->removeTab('group_170');
		
	}
	
}