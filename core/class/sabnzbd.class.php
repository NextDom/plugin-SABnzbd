<?php

/* This file is part of Jeedom.
 *
 * JEEDOM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once __DIR__ . '/../../3rdparty/sabnzbd_api.class.php';

class sabnzbd extends eqLogic {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */

    public function postInsert()
    {
        $this->postUpdate();
        $this->scan();
    }

    private function getListDefaultCmd()
    {
        return array(   "speed" => array('Speed', 'info', 'numeric', "KB/S", 0, "GENERIC_INFO", 'jauge', 'jauge', 'A',1,12000),
                "size" => array('Size', 'info', 'numeric', "GB", 0, "GENERIC_INFO", 'jauge', 'jauge', 'B',1,12000),
                "sizeleft" => array('Size Left', 'info', 'numeric', "GB", 0, "GENERIC_INFO", 'jauge', 'jauge', 'C',1,12000),
		"status" => array('Status', 'info', 'string', "", 0, "GENERIC_INFO", 'badge','badge', 'D',0,0),
		"nzbfile" => array('Upload', 'action', 'file', "", 0, "UPLOAD", 'badge','badge', 'D',0,0),
		"action" => array('Commande', 'action', 'select', "", 0, "GENERIC_ACTION", '', '', 'pause|'.__('Pause Sabnzbd',__FILE__).';resume|'.__('Resume',__FILE__).';shutdown|'.__('Shutdown',__FILE__))
        );
    }

    public function postUpdate()
    {
        foreach( $this->getListDefaultCmd() as $id => $data)
	{
	    list($name, $type, $subtype, $unit, $invertBinary, $generic_type, $template_dashboard, $template_mobile, $listValue, $multiplier,$maxValue) = $data;
            $cmd = $this->getCmd(null, $id);
            if ( ! is_object($cmd) ) {
                $cmd = new sabnzbdCmd();
                $cmd->setName($name);
                $cmd->setEqLogic_id($this->getId());
		$cmd->setType($type);
		$cmd->setUnite($unit);
		$cmd->setIsHistorized(1);
                $cmd->setSubType($subtype);
                $cmd->setLogicalId($id);
                       // $cmd->setCollectDate('');
                if ( $listValue != "" )
                {
                    $cmd->setConfiguration('listValue', $listValue);
                }
                $cmd->setDisplay('invertBinary',$invertBinary);
                $cmd->setConfiguration('maxValue',$maxValue);
		$cmd->setDisplay('generic_type', $generic_type);
                $cmd->setTemplate('dashboard', $template_dashboard);
                $cmd->setTemplate('mobile', $template_mobile);
                $cmd->save();
            }
            else
            {
                if ( $cmd->getType() == "" )
                {
                    $cmd->setType($type);
                }
                if ( $cmd->getSubType() == "" )
                {
                    $cmd->setSubType($subtype);
                }
                if ( $cmd->getDisplay('invertBinary') == "" )
                {
                    $cmd->setDisplay('invertBinary',$invertBinary);
                }
                if ( $cmd->getDisplay('generic_type') == "" )
                {
                    $cmd->setDisplay('generic_type', $generic_type);
                }
                if ( $cmd->getDisplay('dashboard') == "" )
                {
                    $cmd->setTemplate('dashboard', $template_dashboard);
                }
                if ( $cmd->getDisplay('mobile') == "" )
                {
                    $cmd->setTemplate('mobile', $template_mobile);
                }
                if ( $listValue != "" )
                {
                    $cmd->setConfiguration('listValue', $listValue);
                }
                $cmd->save();
            }
        }
    }

    public function preRemove() {
    }

    public static function get_factorGB($unit,$factorGB) {
        if (array_key_exists($unit,$factorGB)){
	    return $factorGB[$unit];
		} else {
		 return 1;
		}
    }
    public static function pull() {
            foreach (self::byType('sabnzbd') as $eqLogic) {
                $eqLogic->scan_twice();
            }
    }

    public function scan_twice() {
	    $this->scan();
	    sleep(30);
	    $this->scan();
    }
    public function scan() {
	$factor=array("M"=>1000, "K"=>1);
	$factorGB=array("GB"=>1, "MB"=>1000, " B"=>1000000);

	    	
        $session_sabnzbd = new sabnzbd_api();
        if ( $this->getIsEnable() ) {
	    log::add('sabnzbd','info',"  set API =  ".$this->getConfiguration('API'));
	    $DataSabnzbd = $session_sabnzbd->login($this->getConfiguration('host'),$this->getConfiguration('port'),$this->getConfiguration('API'),"queue");

	    $QueuesInfo = json_decode($DataSabnzbd, true);
             
                foreach( $this->getListDefaultCmd() as $id => $data)
                {
			list($name, $type, $subtype, $unit, $invertBinary, $generic_type, $template_dashboard, $template_mobile, $listValue, $multiplier,$maxValue) = $data;
			##$this->checkAndUpdateCmd($id,$queue_info["queue"][$id]);
			$cmd = $this->getCmd(null, $id);
			$val = $QueuesInfo["queue"][$id];
			if ($id == "speed") {
			   log::add('sabnzbd','info'," inti val =  ".$val);
			    if ($val == "") { 
				$val = "0.0 M";
			    }
                           $unit = substr($val, -1);
                           $kb = substr($val, 0,-2) * $factor[$unit];
			   $this->checkAndUpdateCmd($id,$kb);
                           $this->save();
			}
			if (($id == "size") or ($id == "sizeleft")) {
			    if ($val == "") { 
				$val = "0.0 M";
			    }
		$unit = substr($val, -3);


                           $gb = substr($val, 0,-2) / $this->get_factorGB($unit,$factorGB);
                           $max = substr($QueuesInfo["queue"]["size"], 0,-3) / $this->get_factorGB($factorGB,substr($QueuesInfo["queue"]["size"], -2));
                           $max = $max + substr($QueuesInfo["queue"]["sizeleft"], 0,-3) / $this->get_factorGB($factorGB,substr($QueuesInfo["queue"]["sizeleft"], -2));
		log::add('sabnzbd','info',"Commande ".id." val=".$gb." max=".$max);
			   $cmd->setConfiguration('maxValue',$max);
			   $this->checkAndUpdateCmd($id,$gb);
			   $this->save();
			}
			if (($id == "status") || ($id == "nzbfile")){
				$this->checkAndUpdateCmd($id,$QueuesInfo["queue"][$id]);
                                $this->save();
			}
		}
	}
    }
}

class sabnzbdCmd extends cmd 
{
    /*     * *************************Attributs****************************** */
	public function execute($_options = null) {

        if ( $this->getLogicalId() == 'action' && $_options['select'] != "" )
        {
		log::add('sabnzbd','info',"Commande execute ".$this->getLogicalId()." ".$_options['select']);
		$eqLogic = $this->getEqLogic();
		$session_cmd = new sabnzbd_api();
	        $session_cmd->login($eqLogic->getConfiguration('host'),$eqLogic->getConfiguration('port'),$eqLogic->getConfiguration('API'),$_options['select']);
                $eqLogic->scan_twice();
        }
        if ( $this->getLogicalId() == 'nzbfile' )
        {
		log::add('sabnzbd','info',"Commande execute ".$this->getLogicalId()." ".$_options);
        }
    }


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*     * **********************Getteur Setteur*************************** */
}
 
