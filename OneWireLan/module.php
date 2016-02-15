<?php
    class OneWireLan extends IPSModule
    {
        private $ROMIds = array();
        
        /**
         * Log Message
         * @param string $Message
         */
        protected function Log($Message)
        {
            IPS_LogMessage(__CLASS__, $Message);
        }

        /**
         * Create
         */         
        public function Create()
        {
            //Never delete this line!
            parent::Create();  
                   
            $this->RegisterPropertyBoolean("Active", false);
            $this->RegisterPropertyInteger("DeviceType", 0);
            $this->RegisterPropertyString("IPAddress", "192.168.1.1");
            $this->RegisterPropertyInteger("Port", 4304);
            $this->RegisterPropertyInteger("Poller", 0);
            $this->RegisterTimer("Poller", 0, "OWLan_Update(\$_IPS['TARGET']);");   
            $this->CreateFloatProfile("mV", "", "", " mV", 0, 0, 0, 2);
        }

        /**
         * ApplyChanges
         */
        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();     
              
            if ( $this->ReadPropertyBoolean("Active") ) 
            {                     
                if ( @Sys_Ping($this->ReadPropertyString("IPAddress"), 1000) )
                {              
                    $this->SetStatus(102); 
                    $this->SetTimerInterval("Poller", $this->ReadPropertyInteger("Poller") * 1000);                    
                }
                else 
                {
                    $this->SetStatus(201); 
                    $this->SetTimerInterval("Poller", 0);
                    echo "Invalid IP-Address";
                }
            } 
            else 
            { 
                $this->SetStatus(104); 
                $this->SetTimerInterval("Poller", 0);
            }
        }      
        
        /**
         * ForwardData
         * @param sring $JSONString
         * @return boolean
         */
        public function ForwardData($JSONString) 
        {
            // Empfangene Daten von der Device Instanz
            $data = json_decode($JSONString);
            if ( ($data->DataID === "{C7DF8BCB-7CF7-4AD9-B636-5A3DEFE4E034}") and ($this->ReadPropertyBoolean("Active")) )
            {
                if ($data->Buffer === "NEW")
                {
                    $this->Update();                    
                    return json_encode($this->ROMIds);
                }
            }
        }
        
        /**
         * OWLan_Update();
         */
        public function Update()
        {                                                
            if ($this->ReadPropertyInteger("DeviceType") === 0)
            {
                $URL = "http://" . $this->ReadPropertyString("IPAddress") . ":" . $this->ReadPropertyInteger("Port") . "/details.xml";
                if ( !Sys_Ping($this->ReadPropertyString("IPAddress"), 1000) )
                {
                    $this->SetStatus(201);                 
                    trigger_error("Invalid IP-Address", E_USER_ERROR);
                    exit;
                }
                try 
                {
                    $xml = @new SimpleXMLElement($URL, NULL, TRUE);
                } 
                catch (Exception $ex) 
                {
                    $this->SetStatus(200); 
                    trigger_error("OneWireLan: " . $ex->getMessage() . "!", E_USER_ERROR);
                    exit;
                }                
                $this->SetValue($this->RegisterVariableInteger("PollCount", "PollCount", "", -5), (int) $xml->PollCount);
                $this->SetValue($this->RegisterVariableFloat("VoltagePower", "VoltagePower", "~Volt", -4), (float) $xml->VoltagePower);
                $this->SetValue($this->RegisterVariableInteger("DevicesConnectedChannel1", "DevicesConnectedChannel1", "", -3), (int) $xml->DevicesConnectedChannel1);                
                $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent("DevicesConnectedChannel1"), "DataErrorsChannel1", "DataErrorsChannel1", 1), (int) $xml->DataErrorsChannel1);
                $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent("DevicesConnectedChannel1"), "VoltageChannel1", "VoltageChannel1", 2, "~Volt"), (float) $xml->VoltageChannel1);
                $this->SetValue($this->RegisterVariableInteger("DevicesConnectedChannel2", "DevicesConnectedChannel2", "", -2), (int) $xml->DevicesConnectedChannel2);                
                $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent("DevicesConnectedChannel2"), "DataErrorsChannel2", "DataErrorsChannel2", 1), (int) $xml->DataErrorsChannel2);
                $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent("DevicesConnectedChannel2"), "VoltageChannel2", "VoltageChannel2", 2, "~Volt"), (float) $xml->VoltageChannel2);
                $this->SetValue($this->RegisterVariableInteger("DevicesConnectedChannel3", "DevicesConnectedChannel3", "", -1), (int) $xml->DevicesConnectedChannel3);                
                $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent("DevicesConnectedChannel3"), "DataErrorsChannel3", "DataErrorsChannel3", 1), (int) $xml->DataErrorsChannel3);  
                $this->SetValue($this->RegisterVariableByParent($this->GetIDForIdent("DevicesConnectedChannel3"), "VoltageChannel3", "VoltageChannel3", 2, "~Volt"), (float) $xml->VoltageChannel3);                       
                $data = array();
                $this->ROMIds = array();
                foreach ($xml->owd_DS18B20 as $Sensor) 
                {
                    $ROMId = (string)$Sensor->ROMId;
                    $this->ROMIds["DS18B20"][$ROMId] = "";
                    $data["owd_DS18B20"][$ROMId]["Health"] = (int)$Sensor->Health;
                    $data["owd_DS18B20"][$ROMId]["Temperature"] = (float)$Sensor->Temperature;
                }
                foreach ($xml->owd_DS18S20 as $Sensor) 
                {
                    $ROMId = (string)$Sensor->ROMId;
                    $this->ROMIds["DS18S20"][$ROMId] = "";
                    $data["owd_DS18S20"][$ROMId]["Health"] = (int)$Sensor->Health;
                    $data["owd_DS18S20"][$ROMId]["Temperature"] = (float)$Sensor->Temperature;
                }
                foreach ($xml->owd_DS2438 as $Sensor) 
                {
                    $ROMId = (string)$Sensor->ROMId;
                    $this->ROMIds["DS2438"][$ROMId] = "";
                    $data["owd_DS2438"][$ROMId]["Health"] = (int)$Sensor->Health;
                    $data["owd_DS2438"][$ROMId]["Temperature"] = (float)$Sensor->Temperature;        
                    $data["owd_DS2438"][$ROMId]["Vdd"] = (float)$Sensor->Vdd;    
                    $data["owd_DS2438"][$ROMId]["Vad"] = (float)$Sensor->Vad;    
                    $data["owd_DS2438"][$ROMId]["Vsense"] = (float)$Sensor->Vsense;    
                }
                $this->SendDataToChildren(json_encode(Array("DataID" => "{B62FA047-5739-4518-A30B-2B12339713A2}", "Buffer" => json_encode($data)))); 
                $this->SetStatus(102); 
            }
        }
        
        /** *** WORKAROUND ***
         * SendDataToChildren
         * @param string $JSONString
         */
        protected function SendDataToChildren($JSONString) 
        {
            //parent::SendDataToChildren($Data);
            include_once(__DIR__ . "/../OneWireLanDevice/module.php");
            $ModuleID_r = IPS_GetInstanceListByModuleID("{21C81179-662C-46E5-BB0E-F3E18EF75637}");
            foreach ($ModuleID_r as $value) 
            {
                $Device = new OneWireLanDevice($value);
                $Device->ReceiveData($JSONString);                
            }
        } 

        /**
         * SetValue
         * @param integer $ID
         * @param type $Value
         */
        private function SetValue($ID, $Value)
        {
            if ( GetValue($ID) !== $Value ) { SetValue($ID, $Value); }
        }
        
        /**
         * RegisterVariableByParent
         * @param integer $ParentID
         * @param string $Ident
         * @param string $Name
         * @param integer $Type
         * @param string $Profile
         * @param integer $Position
         * @return integer
         */
        private function RegisterVariableByParent($ParentID, $Ident, $Name, $Type, $Profile = "", $Position = 0) 
        {
            if($Profile !== "") 
            {
                //prefer system profiles
		if(IPS_VariableProfileExists("~".$Profile)) 
                {
                    $Profile = "~".$Profile;
		}
		if(!IPS_VariableProfileExists($Profile)) 
                {
                    throw new Exception("Profile with name ".$Profile." does not exist");
		}
            }
            //search for already available variables with proper ident
            $vid = @IPS_GetObjectIDByIdent($Ident, $ParentID);
            //properly update variableID
            if($vid === false) { $vid = 0; }
            //we have a variable with the proper ident. check if it fits
            if($vid > 0) 
            {
                //check if we really have a variable
                if(!IPS_VariableExists($vid)) { throw new Exception("Ident with name ".$Ident." is used for wrong object type"); } //bail out
		//check for type mismatch
		if(IPS_GetVariable($vid)["VariableType"] != $Type) 
                {
                    //mismatch detected. delete this one. we will create a new below
                    IPS_DeleteVariable($vid);
                    //this will ensure, that a new one is created
                    $vid = 0;
		}
            }
            //we need to create one
            if($vid === 0)
            {
                $vid = IPS_CreateVariable($Type);
		//configure it
		IPS_SetParent($vid, $ParentID);
		IPS_SetIdent($vid, $Ident);
		IPS_SetName($vid, $Name);
		IPS_SetPosition($vid, $Position);
		//IPS_SetReadOnly($vid, true);
            }
            //update variable profile. profiles may be changed in module development.
            //this update does not affect any custom profile choices
            IPS_SetVariableCustomProfile($vid, $Profile);
            return $vid;
	}
        
        /**
         * CreateFloatProfile
         * @param string $ProfileName
         * @param string $Icon
         * @param string $Präfix
         * @param string $Suffix
         * @param float $MinValue
         * @param float $MaxValue
         * @param integer $StepSize
         * @param integer $Digits
         */
        private function CreateFloatProfile($ProfileName, $Icon, $Präfix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
        {
            $Profile = IPS_VariableProfileExists($ProfileName);
            if ($Profile === FALSE)
            {
                IPS_CreateVariableProfile($ProfileName, 2);
                IPS_SetVariableProfileIcon($ProfileName,  $Icon);
                IPS_SetVariableProfileText($ProfileName, $Präfix, $Suffix);
                IPS_SetVariableProfileValues($ProfileName, $MinValue, $MaxValue, $StepSize);
                IPS_SetVariableProfileDigits($ProfileName, $Digits);
            }
        }        
    }