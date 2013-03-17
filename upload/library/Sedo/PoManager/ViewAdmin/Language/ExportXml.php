<?php

class Sedo_PoManager_ViewAdmin_Language_ExportXml extends XenForo_ViewAdmin_Base
{
	public function renderXml()
	{
		$title = str_replace(' ', '-', utf8_romanize(utf8_deaccent($this->_params['filename'])));
		$title = str_replace ('.po', '', $title);

		$this->setDownloadFileName($title . '-converted.xml');
		return $this->_params['xml']->saveXml();
	}
}