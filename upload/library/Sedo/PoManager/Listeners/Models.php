<?php

class Sedo_PoManager_Listeners_Models
{
	public static function extend($class, array &$extend)
	{
		switch ($class) 
		{
			case 'XenForo_Model_Language':
			$extend[] = 'Sedo_PoManager_Model_Language';
			break;
		}
	}
}