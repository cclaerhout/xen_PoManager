<?php

class Sedo_PoManager_ControllerAdmin_Language extends XFCP_Sedo_PoManager_ControllerAdmin_Language
{
	public function actionPoToXml()
	{
		if ($this->isConfirmedPost())
		{
			$input = $this->_input->filter(array(
				'target' => XenForo_Input::STRING,
				'parent_language_id' => XenForo_Input::UINT,
				'overwrite_language_id' => XenForo_Input::UINT
			));

			$upload = XenForo_Upload::getUploadedFile('upload');
			if (!$upload)
			{
				return $this->responseError(new XenForo_Phrase('sedo_po_error_during_upload'));
			}
			
			$fileName = $upload->getFileName();
			$extension =  XenForo_Helper_File::getFileExtension($fileName);

			if ($extension != 'po')
			{
				return $this->responseError(new XenForo_Phrase('sedo_po_file_extension_must_be_po'));
			}
			
			$this->_routeMatch->setResponseType('xml');

			$viewParams = array(
				'filename' => $fileName,
				'xml' => $this->_getLanguageModel()->convertPoToXml($upload)
			);

			return $this->responseView('Sedo_PoManager_ViewAdmin_Language_ExportXml', '', $viewParams);
		}
		else
		{
			$viewParams = array();

			return $this->responseView('Sedo_PoManager_ViewAdmin_Language_PoToXml', 'language_po_to_xml', $viewParams);
		}
	}

	public function actionExportToPo()
	{
		$languageId = $this->_input->filterSingle('language_id', XenForo_Input::UINT);
		$language = $this->_getLanguageOrError($languageId, true);

		$disablefuzzy = $this->_input->filterSingle('disablefuzzy', XenForo_Input::UINT);
		$fuzzy = (empty($disablefuzzy)) ? true : false;

		if ($this->isConfirmedPost())
		{
			$input = $this->_input->filter(array(
				'addon_id' => XenForo_Input::STRING,
				'untranslated' => XenForo_Input::UINT
			));

			$addOnId = ($input['addon_id']) ? $input['addon_id'] : null;
			$xenAddon = (!empty($input['addon_id'])) ? $input['addon_id'] : 'all-in-one';
			$title = str_replace(' ', '-', utf8_romanize(utf8_deaccent($language['title'])));

			$po = $this->_getLanguageModel()->getLanguagePo($language, $addOnId, $input['untranslated'], $fuzzy);

			$filename = "$xenAddon-$title";
			header('Content-type: application/txt');
			header('Content-disposition: po' . date("Y-m-d") . '.po');
			header('Content-disposition: filename=' . $filename . '.po');
			print $po;
			exit;
		}
		else
		{
			$viewParams = array(
				'language' => $language,
				'addOnOptions' => $this->getModelFromCache('XenForo_Model_AddOn')->getAddOnOptionsList(false, true)
			);

			return $this->responseView('Sedo_PoManager_ViewAdmin_Language_Export_Po', 'language_export_po', $viewParams);
		}
	}
}
//Zend_Debug::dump($content);