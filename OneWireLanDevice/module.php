<?php                                                                           
    class OneWireLanDevice extends IPSModule
    {
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
            
            $this->RegisterPropertyInteger("DataType", 0);
            $this->RegisterPropertyString("ROMId", "");
            
            // Connect to IO or create it
            $this->ConnectParent("{926EAA81-B983-49EB-900A-673C6A81EE29}");
        }

        /**
         * ApplyChanges
         */
        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();   
            
            switch ($this->ReadPropertyInteger("DataType")) 
            {
                case 0: //DS18B20
                    $this->RegisterVariableInteger("Health", "Health", "", 0);
                    $this->RegisterVariableFloat("Temp", "Temperature", "~Temperature", 1);
                    $this->UnregisterVariable("Vdd");
                    $this->UnregisterVariable("Vad");
                    $this->UnregisterVariable("Vsense");
                break;
                case 2: //DS18S20
                    $this->RegisterVariableInteger("Health", "Health", "", 0);
                    $this->RegisterVariableFloat("Temp", "Temperature", "~Temperature", 1);
                    $this->UnregisterVariable("Vdd");
                    $this->UnregisterVariable("Vad");
                    $this->UnregisterVariable("Vsense");
                break;
                case 1: //DS2438
                    $this->RegisterVariableInteger("Health", "Health", "", 0);
                    $this->RegisterVariableFloat("Temp", "Temperature", "~Temperature", 1);
                    $this->RegisterVariableFloat("Vdd", "Vdd", "~Volt", 2);
                    $this->RegisterVariableFloat("Vad", "Vad", "~Volt", 3);
                    $this->RegisterVariableFloat("Vsense", "Vsense", "mV", 4);
                break;
            }            
        }      
               
        /**
         * ReceiveData
         * @param string $JSONString
         */
        public function ReceiveData($JSONString) 
        {
            // Empfangene Daten
            $Data = json_decode($JSONString);
            //IPS_LogMessage("ReceiveData", utf8_decode($JSONString));  
            if ( $Data->DataID === "{B62FA047-5739-4518-A30B-2B12339713A2}" )
            {
                $Data = json_decode($Data->Buffer);
                $ROMId = $this->ReadPropertyString("ROMId");
                if ( $ROMId != "" ) 
                {
                    switch ($this->ReadPropertyInteger("DataType")) 
                    {
                        case 0:                                       
                            foreach ($Data->owd_DS18B20->$ROMId as $Key => $Value)
                            {   
                                switch ($Key) 
                                {
                                    case "Health":
                                        $this->SetValue($this->GetIDForIdent("Health"), $Value);
                                    break;
                                    case "Temperature":
                                        $this->SetValue($this->GetIDForIdent("Temp"), $Value);
                                    break;
                                }                            
                            }
                        break;
                        case 2:                                       
                            foreach ($Data->owd_DS18S20->$ROMId as $Key => $Value)
                            {   
                                switch ($Key) 
                                {
                                    case "Health":
                                        $this->SetValue($this->GetIDForIdent("Health"), $Value);
                                    break;
                                    case "Temperature":
                                        $this->SetValue($this->GetIDForIdent("Temp"), $Value);
                                    break;
                                }                            
                            }
                        break;
                        case 1:                                                
                            foreach ($Data->owd_DS2438->$ROMId as $Key => $Value)
                            {   
                                switch ($Key) 
                                {
                                    case "Health":
                                        $this->SetValue($this->GetIDForIdent("Health"), $Value);
                                    break;
                                    case "Temperature":
                                        $this->SetValue($this->GetIDForIdent("Temp"), $Value);
                                    break;
                                    case "Vdd":
                                        $this->SetValue($this->GetIDForIdent("Vdd"), $Value);
                                    break;
                                    case "Vad":
                                        $this->SetValue($this->GetIDForIdent("Vad"), $Value);
                                    break;
                                    case "Vsense":
                                        $this->SetValue($this->GetIDForIdent("Vsense"), $Value);
                                    break;
                                }                            
                            }
                        break;                 
                    }
                }
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
         * OneWireLan_GetRomID
         * @return string
         */
        public function GetRomID() 
        {
            if ($_IPS['SENDER'] != "RunScript")
            {
                trigger_error("Do not use this function!", E_USER_WARNING);
                return;
            }
            if ($this->ReadPropertyString("ROMId"))
            {
                trigger_error("ROMId is not empty!", E_USER_WARNING);
                return;
            }    
            $ModuleID_r = IPS_GetInstanceListByModuleID("{21C81179-662C-46E5-BB0E-F3E18EF75637}");
            $resultat = $this->SendDataToParent(json_encode(Array("DataID" => "{C7DF8BCB-7CF7-4AD9-B636-5A3DEFE4E034}", "Buffer" => "NEW")));  
            if ($resultat == "")
            {
                trigger_error("Interface is closed!", E_USER_WARNING);
                return;
            }
            $Data = json_decode($resultat);
            switch ($this->ReadPropertyInteger("DataType"))
            {
                case 0:
                    foreach ($Data->DS18B20 as $Key => $Value)
                    {       
                        $ROMId = $Key;
                        foreach ($ModuleID_r as $ID) 
                        {
                            if (IPS_GetProperty($ID, "ROMId") == $Key)
                            {
                                $ROMId = "";
                            }
                        }
                        if ($ROMId != "")
                        {
                            IPS_SetProperty($this->InstanceID, "ROMId", $ROMId);
                            IPS_ApplyChanges($this->InstanceID);
                            echo "New ROMID: " . $ROMId . " found and saved! PLEASE CLOSE THE WINDOW!"; 
                            return;
                        }
                    }
                    echo "No New ROMId for DS18B20 found!";
                    return; 
                break;
                case 2:
                    foreach ($Data->DS18S20 as $Key => $Value)
                    {       
                        $ROMId = $Key;
                        foreach ($ModuleID_r as $ID) 
                        {
                            if (IPS_GetProperty($ID, "ROMId") == $Key)
                            {
                                $ROMId = "";
                            }
                        }
                        if ($ROMId != "")
                        {
                            IPS_SetProperty($this->InstanceID, "ROMId", $ROMId);
                            IPS_ApplyChanges($this->InstanceID);
                            echo "New ROMID: " . $ROMId . " found and saved! PLEASE CLOSE THE WINDOW!"; 
                            return;
                        }
                    }
                    echo "No New ROMId for DS18S20 found!";
                    return; 
                break;
                case 1:
                    foreach ($Data->DS2438 as $Key => $Value)
                    {       
                        $ROMId = $Key;
                        foreach ($ModuleID_r as $ID) 
                        {
                            if (IPS_GetProperty($ID, "ROMId") == $Key)
                            {
                                $ROMId = "";
                            }
                        }
                        if ($ROMId != "")
                        {
                            IPS_SetProperty($this->InstanceID, "ROMId", $ROMId);
                            IPS_ApplyChanges($this->InstanceID);
                            echo "New ROMID: " . $ROMId . " found and saved! PLEASE CLOSE THE WINDOW!"; 
                            return;
                        }
                    }
                    echo "No New ROMId for DS2438 found!";
                    return;
                break;
            }           
        }
    }