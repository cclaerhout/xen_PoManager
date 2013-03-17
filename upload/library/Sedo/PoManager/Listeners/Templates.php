<?php

class Sedo_PoManager_Listeners_Templates
{
	public static function postRender($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template)
	{
		switch ($templateName) 
		{
			case 'PAGE_CONTAINER':
				if(!$template instanceof XenForo_Template_Admin)
				{
					break;
				}
				
				if($template->getParam('viewName') != 'XenForo_ViewAdmin_Language_List')
				{
					break;
				}
				
				//PO TO XML
				$poToXml = new XenForo_Phrase('sedo_po_po_to_xml');
				
				$search  = '#<a.*?"admin\.php\?languages/import".*?class="button".*?</a>#ui';
				if(!preg_match($search, $content))
				{
					//Shouldn't be needed
					$search  = '#<a.*?class="button".*?"admin\.php\?languages/import".*?</a>#ui';
				}
				$replace = '$0' . "\r\n\t" . '<a class="button" href="admin.php?languages/po-to-xml">' . $poToXml . '</a>';

				$content = preg_replace($search, $replace, $content);
			break;
			case 'language_list':
				if(!$template instanceof XenForo_Template_Admin)
				{
					break;
				}

				//Export to PO
				$exportToPo = new XenForo_Phrase('sedo_po_export_to_po');
				$search  = '#<a\s*?href="admin\.php\?languages/(\S+?)/export".+?</a>#i';
				$replace = '<a href="admin.php?languages/$1/export-to-po" class="secondaryContent OverlayTrigger">' . $exportToPo . '</a>$0';
				
				$content = preg_replace($search, $replace, $content);
			break;
		}
	}
}
//Zend_Debug::dump($content);