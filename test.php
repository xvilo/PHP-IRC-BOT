<?php
		$pms = file_get_contents('stuff/pms');
		$pms = unserialize($pms);
		$pmsCount = count($pms);
		echo "Er zijn op moment $pmsCount berichten. Type Read 0 t/m $pmsCount om je berichten te lezen";