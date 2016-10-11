<?php

class MageLine_Sellsy_Block_Info extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
		
		$html = $this->_getHeaderHtml($element);
		
		$html.= $this->_getFieldHtml($element);
        
        $html .= $this->_getFooterHtml($element);

        return $html;
    }

   

	protected function _getFieldHtml($fieldset)
    {
		$content = 'This module is developed by <a href="http://www.mageline.com/">MageLine.com</a> to integrate Sellsy ERP with Magento 1.9.x';
        $content .= '<br/><img src="http://www.mageline.com/wp-content/themes/virtconmedia/img/logo.png" />'; 
		return $content;
    }
}
