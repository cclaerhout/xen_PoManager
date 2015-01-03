<?php

class Sedo_PoManager_ViewAdmin_Language_ExportPo extends XenForo_ViewAdmin_Base
{
	public function renderRaw()
	{
		$poFile = $this->_params['po'];
		$this->_response->setHeader('Content-type', 'application/txt', true); //application/octet-stream
		$this->setDownloadFileName($this->_params['filename'] . '.po', true);				
		return $this->_params['po'];
	}
}