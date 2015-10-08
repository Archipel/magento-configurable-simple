<?php
class OrganicInternet_SimpleConfigurableProducts_Adminhtml_Block_Catalog_Product_Edit_Tab_Super_Config
extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Super_Config
{
	public function __construct(){
		parent::__construct();
		$this->setCanEditPrice(false);
		$this->setCanReadPrice(false);
	}
}