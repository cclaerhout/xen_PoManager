<?php

class Sedo_PoManager_Listeners_Controllers
{
	public static function extend($class, array &$extend)
	{
		switch ($class) 
		{
			case 'XenForo_ControllerAdmin_Language':
			$extend[] = 'Sedo_PoManager_ControllerAdmin_Language';
			break;
		}
	}
}