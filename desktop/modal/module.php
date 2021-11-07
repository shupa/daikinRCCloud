<?php
	/* This file is part of Plugin zigbee for jeedom.
	*
	* Plugin zigbee for jeedom is free software: you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation, either version 3 of the License, or
	* (at your option) any later version.
	*
	* Plugin zigbee for jeedom is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with Plugin zigbee for jeedom. If not, see <http://www.gnu.org/licenses/>.
	*/
	if (!isConnect('admin')) {
		throw new Exception('401 Unauthorized');
	}

    $logicalID = init('logicalId');

    $devices_data = daikinRCCloud_deamon::getDevicesByID($logicalID);
	$devices_prejson = daikinRCCloud_generator::generateJson($logicalID);
?>
<div id='div_nodeDeconzAlert' style="display: none;"></div>
<ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="active"><a href="#rawNodeTab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Informations brutes}}</a></li>
    <li role="presentation" class=""><a href="#rawNodeJsonPregen" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Json Pre Gen}}</a></li>
</ul>
<div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="rawNodeTab">
        <pre><?php echo json_encode($devices_data,JSON_PRETTY_PRINT);?></pre>
    </div>
    <div role="tabpanel" class="tab-pane" id="rawNodeJsonPregen">
        <pre><?php echo json_encode($devices_prejson);?></pre>
    </div>
</div>