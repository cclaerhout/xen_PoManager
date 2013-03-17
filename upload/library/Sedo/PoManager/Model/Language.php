<?php

class Sedo_PoManager_Model_Language extends XFCP_Sedo_PoManager_Model_Language
{
	protected $po_wip_phrase = array('key' => '', 'value' => '');
	
	public function convertPoToXml($file)
	{
		$poFile = new SplFileObject($file->getTempFile());

		$document = new DOMDocument('1.0', 'utf-8');
		$document->formatOutput = true;
		$rootNode = $document->createElement('language');
	
		/****
		*	Language information
		***/
		$language_params = array(
				'title' => '',
				'date_format' => '',
				'time_format' => '',
				'decimal_point' => '',
				'thousands_separator' => '',
				'language_code' => '',
				'text_direction' => '',
				'addon_id' => ''
		);

		while (!$poFile->eof() && $poFile->key() < 80) 
		{
			$line = $poFile->fgets();
			
			if(empty($line))
			{
				continue;
			}
			
			if(empty($language_params))
			{
				break;
			}

      			foreach($language_params as $lang_param => $lang_value)
      			{
      				$lang_param_xen = 'xen_' . $lang_param;
      				if(strpos($line,$lang_param_xen) === 1)
      				{
					$line = $this->_PoToXmlFix($line);
					$lang_value = substr($line, strlen($lang_param_xen)+3, -3);
					
					if($lang_param == 'addon_id' && $lang_value == 'AllInOne')
					{
						continue;
	      				}

      					$rootNode->setAttribute($lang_param, $lang_value);
     					
     					unset($language_params[$lang_param]);
      					continue;
      				}
      			}
		}

		unset($language_params['addon_id']);
		if(!empty($language_params))
		{
			//All params should have been retrieved from the po files - if not, let's inform translators
			foreach($language_params as $lang_param => $lang_value)
			{
				$rootNode->setAttribute($lang_param, 'undefined');
			}
		}
		
		$document->appendChild($rootNode);
		

		/****
		*	Phrases
		*	
		***/
		$poFile->rewind();
		$phrase_key = 0;

		while (!$poFile->eof()) 
		{
			$line = $poFile->fgets();
			
			if(empty($line))
			{
				continue;
			}

			if(strpos($line,'#:') === 0)
			{
				$phrases_datas = explode('][', substr($this->_PoToXmlFix($line),4,-1));
				
				$addon_id = (isset($phrases_datas[0])) ? $phrases_datas[0] : 'undefined';
				$title = (isset($phrases_datas[1])) ? $phrases_datas[1] : 'undefined';
				$global_cache = (isset($phrases_datas[2])) ? $phrases_datas[2] : 'null';
				$version_id = (isset($phrases_datas[3])) ? $phrases_datas[3] : '';
				$version_string = (isset($phrases_datas[4])) ? $phrases_datas[4] : '';
								
				//$phrase_key++;
				$phrase_key = $phrases_datas[1];

				$phraseNode = $document->createElement('phrase');
				$phraseNode->setAttribute('title', $title);
				$phraseNode->setAttribute('addon_id', $addon_id);
				if ($global_cache != 'null')
				{
					$phraseNode->setAttribute('global_cache', $global_cache);
				}
				$phraseNode->setAttribute('version_id', $version_id);
				$phraseNode->setAttribute('version_string', $version_string);

				$this->po_wip_phrase['key'] = $phrase_key;
				$this->po_wip_phrase['value'] = '';
				
				//Skip all the lines until to match "msgstr"
				for($line; strpos($line,'msgstr') !== 0; $line = $poFile->current()) 
				{
					$poFile->next();
				}

				//Get the content of "msgstr"
				for(	$line = $this->_PoToXmlFix($poFile->current()), $condition = (strpos($line,'msgstr') === 0 || strpos($line,'"') === 0) ? true : false; 
					$condition == true; 
					$poFile->next(), $line = $this->_PoToXmlFix($poFile->current()), $condition = (strpos($line,'msgstr') === 0 || strpos($line,'"') === 0) ? true : false
				)
				{
					if($line == 'msgstr ""')
					{
						//Important to use the $poFile->next() cmd inside the for loop to avoid an infinite loop
						continue;
					}
					
					$start = strpos($line, '"') + 1;
					$end = (substr($line, -3) == '\n"') ? -3 : -1;
					
					$this->po_wip_phrase['value'] .= substr($line,$start,$end);
					$this->po_wip_phrase['value'] .= (substr($line, -3) == '\n"') ? "\n" : '';
				}

				//Add the content to the xml structure
				$phraseNode->appendChild($document->createCDATASection($this->_PoToXmlFormat($this->po_wip_phrase['value'])));
				$rootNode->appendChild($phraseNode);
				
				//Loop
			}

			continue;
		}

		return $document;	
	}

	protected function _PoToXmlFormat($string)
	{
		$string = str_replace(array('\"', '\t'), array('"', "\t"), $string);
		return $string;
	}

	protected function _PoToXmlFix($string)
	{
		$string = str_replace(array("\r\n", "\n"), array('', ''), $string);
		return $string;
	}	
	
	public function getLanguagePo(array $language, $limitAddOnId = null, $getUntranslated = false, $fuzzy = true)
	{
		$visitor = XenForo_Visitor::getInstance();
		$xenAddon = ($limitAddOnId !== null) ? $limitAddOnId : 'AllInOne';
		$cr = "\r\n";
		$po  = '';
		
		//Po File Basic Information
		$po .= 'msgid ""' . $cr;
		$po .= 'msgstr ""' . $cr;
		$po .= '"Project-Id-Version: PACKAGE VERSION\n"' . $cr;
		$po .= '"Report-Msgid-Bugs-To: \n"' .$cr;
		$po .= '"POT-Creation-Date: ' . date("Y-m-d H:i O") . '\n"' . $cr;
		$po .= '"PO-Revision-Date: ' . date("Y-m-d H:i O") . '\n"' . $cr;
		$po .= '"Last-Translator: ' . $visitor->username . '<' . $visitor->email . '>\n"' . $cr;
		$po .= '"Language-Team: XenForo <' . $visitor->email . '>\n"' . $cr;
		$po .= '"MIME-Version: 1.0\n"' . $cr;
		$po .= '"Content-Type: text/plain; charset=UTF-8\n"' . $cr;
		$po .= '"Content-Transfer-Encoding: 8bit\n"' . $cr;
		$po .= '"X-Generator: Translate Toolkit 1.8.0\n"' . $cr;						

		//XenForo Language Information

		foreach($language as $key => $value)
		{
			if(!in_array($key, array('title', 'date_format', 'time_format', 'decimal_point', 'thousands_separator', 'language_code', 'text_direction')))
			{
				continue;
			}
			$po .= '"xen_' . $key . ': ' . $language[$key] . '\n"' . $cr;			
		}
		$po .= '"xen_addon_id: ' . $xenAddon . '\n"' . $cr;
		$po .= $cr;
		
		//Database Fetch Information
		$db = $this->_getDb();

		if ($getUntranslated)
		{
			$phrases = $db->fetchAll('
				SELECT phrase.*,
					IF(master.phrase_id, master.addon_id, phrase.addon_id) AS addon_id
				FROM xf_phrase_map AS map
				INNER JOIN xf_phrase AS phrase ON (map.phrase_id = phrase.phrase_id)
				LEFT JOIN xf_phrase AS master ON (master.title = phrase.title AND master.language_id = 0)
				WHERE map.language_id = ?
					AND ' . ($limitAddOnId === null ? '1=1' : 'master.addon_id = ' . $db->quote($limitAddOnId)) . '
				ORDER BY map.title
			', $language['language_id']);
			
			$source = $db->fetchAll('
				SELECT phrase.*,
					IF(master.phrase_id, master.addon_id, phrase.addon_id) AS addon_id
				FROM xf_phrase_map AS map
				INNER JOIN xf_phrase AS phrase ON (map.phrase_id = phrase.phrase_id)
				LEFT JOIN xf_phrase AS master ON (master.title = phrase.title AND master.language_id = 0)
				WHERE map.language_id = ?
					AND ' . ($limitAddOnId === null ? '1=1' : 'master.addon_id = ' . $db->quote($limitAddOnId)) . '
				ORDER BY map.title
			', 0);
		}
		else
		{
			$phrases = $db->fetchAll('
				SELECT phrase.*,
					IF(master.phrase_id, master.addon_id, phrase.addon_id) AS addon_id
				FROM xf_phrase AS phrase
				LEFT JOIN xf_phrase AS master ON (master.title = phrase.title AND master.language_id = 0)
				WHERE phrase.language_id = ?
					AND ' . ($limitAddOnId === null ? '1=1' : 'master.addon_id = ' . $db->quote($limitAddOnId)) . '
				ORDER BY phrase.title
			', $language['language_id']);

			$source = $db->fetchAll('
				SELECT phrase.*,
					IF(master.phrase_id, master.addon_id, phrase.addon_id) AS addon_id
				FROM xf_phrase AS phrase
				LEFT JOIN xf_phrase AS master ON (master.title = phrase.title AND master.language_id = 0)
				WHERE phrase.language_id = ?
					AND ' . ($limitAddOnId === null ? '1=1' : 'master.addon_id = ' . $db->quote($limitAddOnId)) . '
				ORDER BY phrase.title
			', 0);			
		}

		//Source phrases
		$source = (is_array($source)) ? $source : array(); // should not be needed in theory
		
		foreach ($source as $key => $phrase)
		{
			$source[$phrase['title']] = $phrase['phrase_text'];
			unset($source[$key]);
		}
		
		//Po Create Phrases
		$phrases = (is_array($phrases)) ? $phrases : array(); // should not be needed in theory

		foreach ($phrases AS $phrase)
		{
			if(!isset($phrase['title'])
			|| !isset($phrase['addon_id'])
			|| !isset($phrase['version_id'])
			|| !isset($phrase['version_string'])
			|| !isset($phrase['phrase_text']))
			{
				continue;
			}
			
			$title = $phrase['title'];
			$addon_id = $phrase['addon_id'];
			$global_cache = ($phrase['global_cache']) ? $phrase['global_cache'] : 'null';
			$version_id = $phrase['version_id'];
			$version_string = $phrase['version_string'];
			$phrase_text = $this->_formatToPo($phrase['phrase_text']);
			$source_text = (isset($source[$title])) ? $this->_formatToPo($source[$title]) : $phrase_text; //Should always work in theory

			$po .= "#: [$addon_id][$title][$global_cache][$version_id][$version_string]\r\n";
			$po .= ($fuzzy !== false) ? "#, fuzzy\r\n" : "";
			$po .= "msgid \"$source_text [$title][$version_id]\"\r\n";
			$po .= "msgstr \"$phrase_text\"\r\n\r\n";
		}

		return $po;
	}
	
	protected function _formatToPo($string)
	{
		//Escape double quotes + change tabs
		$string = str_replace(array('"', "\t"), array('\"', '\t'), $string);
		
		//Carriage returns must be marked and use double quotes
		$string = preg_replace('#\n#ui', '\n"$0"', $string);
		
		return $string;
	}
}