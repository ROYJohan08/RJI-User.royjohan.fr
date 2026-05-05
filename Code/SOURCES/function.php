<?php
class Error{
	private static $ErrorList = [];
    public static function Add($Content, $Level = 0) {
        if (!is_numeric($Level)) { return false; }
		$Content = strip_tags($Content);
		$Content = trim($Content);
		if(strlen($Content)<5){return false;}
		if (class_exists('Transliterator')) {
			$transliterador = Transliterator::create('Any-Latin; Latin-ASCII');
			$Content = $transliterador->transliterate($Content);
		}
		$Content = htmlspecialchars($Content);
		self::$ErrorList[] = ["Content" => $Content, "Level" => (int)$Level];
        return true;
    }
    public static function Get($Level = "A") {
        self::sort(); 
        if (strtolower($Level) == "a") {
            return self::$ErrorList;
        } 
        elseif (is_numeric($Level)) {
            return array_filter(self::$ErrorList, function($error) use ($Level) {
                return $error['Level'] == $Level;
            });
        }
        return [];
    }
    public static function Clear() {
        self::$ErrorList = [];
		return true;
    }
    private static function sort() {
        usort(self::$ErrorList, function($a, $b) {
            return $a['Level'] <=> $b['Level'];
        });
    }
}
class Success{
	private static $SuccessList = [];
    public static function Add($Content, $Level = 0) {
        if (!is_numeric($Level)) { return false; }
		$Content = strip_tags($Content);
		$Content = trim($Content);
		if(strlen($Content)<5){return false;}
		if (class_exists('Transliterator')) {
			$transliterador = Transliterator::create('Any-Latin; Latin-ASCII');
			$Content = $transliterador->transliterate($Content);
		}
		$Content = htmlspecialchars($Content);
		self::$SuccessList[] = ["Content" => $Content, "Level" => (int)$Level];
        return true;
    }
    public static function Get($Level = "A") {
        self::Sort(); 
        if (strtolower($Level) == "a") {
            return self::$SuccessList;
        } 
        elseif (is_numeric($Level)) {
            return array_filter(self::$SuccessList, function($success) use ($Level) {
                return $success['Level'] == $Level;
            });
        }
        return [];
    }
    public static function Clear() {
        self::$SuccessList = [];
    }
    private static function Sort() {
        usort(self::$SuccessList, function($a, $b) {
            return $a['Level'] <=> $b['Level'];
        });
    }
}
class Log{
	private static $LogList = [];
    public static function Add($Content, $Level = 0) {
        if (!is_numeric($Level)) { return false; }
		$Content = strip_tags($Content);
		$Content = trim($Content);
		if(strlen($Content)<5){return false;}
		if (class_exists('Transliterator')) {
			$transliterador = Transliterator::create('Any-Latin; Latin-ASCII');
			$Content = $transliterador->transliterate($Content);
		}
		$Content = htmlspecialchars($Content);
		self::$LogList[] = ["Content" => $Content, "Level" => (int)$Level, "Datetime"=>time()];
        return true;
    }
    public static function Get($Level = "A") {
        self::Sort(); 
        if (strtolower($Level) == "a") {
            return self::$LogList;
        } 
        elseif (is_numeric($Level)) {
            return array_filter(self::$LogList, function($log) use ($Level) {
                return $log['Level'] == $Level;
            });
        }
        return [];
    }
    public static function Clear() {
        self::$LogList = [];
    }
    private static function Sort() {
        usort(self::$LogList, function($a, $b) {
            return $a['Datetime'] <=> $b['Datetime'];
        });
    }
}
class Check{
    public static function Cid($Cid){
		return self::validateGenericId($Cid, 'C', 'Cid');
	}
	
    public static function Uid($Uid){
		return self::validateGenericId($Uid, 'U', 'Uid');
	}
	
    public static function Iid($Iid){
		return self::validateGenericId($Iid, 'I', 'Iid');
	}
	
    public static function Tid($Tid){
		return self::validateGenericId($Tid, 'T', 'Tid');
	}
	
	public static function Token($Token){
		$Token = trim($Token);
		if (strlen($Token) === 0) {Error::Add("Token is empty",1);return "";}
		$pattern = '/^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_]*$/';
		if (!preg_match($pattern, $Token)) {Error::Add("Token has an incorrect format",1);return "";}
		Log::Add("Token correct : ".$Token,0);
		return $Token;
	}
	
	public static function Username($Username) {
        $Username = trim($Username);
        $isEmail = filter_var($Username, FILTER_VALIDATE_EMAIL);
        $isAlpha = preg_match('/^[A-Z]{2,}[A-Z-a-z]{2,}$/', $Username); // Ajustable selon besoin

        if ($isEmail || $isAlpha) {
            Log::Add("Username correct", 0);
            return $Username;
        }
        Error::Add("Username incorrect (Email ou NOMPrenom attendu)", 1);
        return "";
    }

    public static function Password($Password) {
        if (preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $Password)) return $Password;
        Error::Add("Password trop faible (8 car. min, 1 Maj, 1 Min, 1 Chiffre)", 1);
        return "";
    }

    public static function Code($Code) {
        $Code = trim($Code);
        if (preg_match('/^[a-zA-Z0-9]{8}$/', $Code)) return $Code;
        Error::Add("Code incorrect (8 caractères alphanumériques)", 1);
        return "";
    }

	public static function Nom($Nom) {
        $Nom = trim($Nom);
        if (preg_match('/^[a-zA-ZÀ-ÿ\s\-\']{2,50}$/', $Nom)) return $Nom;
        Error::Add("Nom invalide", 1);
        return "";
    }

    public static function Prenom($Prenom) {
        $Prenom = trim($Prenom);
        if (preg_match('/^[a-zA-ZÀ-ÿ\s\-\']{2,50}$/', $Prenom)) return $Prenom;
        Error::Add("Prénom invalide", 1);
        return "";
    }

    public static function Adresse($Adresse) {
        $Adresse = trim($Adresse);
        if (strlen($Adresse) > 5) return $Adresse;
        Error::Add("Adresse trop courte", 1);
        return "";
    }

    public static function Complement($Complement) {
        return trim($Complement); 
    }

    public static function CodePostal($CodePostal) {
        $CodePostal = trim($CodePostal);
        if (preg_match('/^[0-9]{5}$/', $CodePostal)) return $CodePostal;
        Error::Add("Code postal invalide (5 chiffres)", 1);
        return "";
    }

    public static function Ville($Ville) {
        $Ville = trim($Ville);
        if (preg_match('/^[a-zA-ZÀ-ÿ\s\-\']{2,100}$/', $Ville)) return $Ville;
        Error::Add("Ville invalide", 1);
        return "";
    }

    public static function Email($Email) {
        $Email = trim($Email);
        if (filter_var($Email, FILTER_VALIDATE_EMAIL)) return $Email;
        Error::Add("Format Email invalide", 1);
        return "";
    }
    
	public static function Telephone($Telephone) {
        $Telephone = str_replace([' ', '.', '-', '/'], '', trim($Telephone));
        if (preg_match('/^0[1-59][0-9]{8}$/', $Telephone)) return $Telephone;
        Error::Add("Téléphone fixe invalide", 1);
        return "";
    }

    public static function Portable($Portable) {
        $Portable = str_replace([' ', '.', '-', '/'], '', trim($Portable));
        if (preg_match('/^0[67][0-9]{8}$/', $Portable)) return $Portable;
        Error::Add("Téléphone portable invalide", 1);
        return "";
    }

    public static function Siren($Siren) {
		$Siren = str_replace(' ', '', trim($Siren));
		if (!preg_match('/^[0-9]{9}$/', $Siren)) {
			Error::Add("SIREN invalide (doit contenir 9 chiffres)", 1);
			return "";
		}
		$sum = 0;
		for ($i = 0; $i < 9; $i++) {
			$digit = (int)$Siren[$i];
			if ($i % 2 !== 0) {
				$digit *= 2;
				if ($digit > 9) {
					$digit -= 9;
				}
			}
			$sum += $digit;
		}
		if ($sum % 10 === 0) {
			Log::Add("SIREN valide : " . $Siren, 0);
			return $Siren;
		} else {
			Error::Add("SIREN incorrect (Echec de la clé de contrôle)", 1);
			return "";
		}
	}
	
	private static function validateGenericId($Value, $Prefix, $Label) {
        $Value = trim($Value);
        if (strlen($Value) === 0) {
            Error::Add("$Label is empty", 1);
            return "";
        }
        if (!preg_match('/^' . $Prefix . '?\d+$/i', $Value)) {
            Error::Add("$Label has an incorrect format", 1);
            return "";
        }
        if (strtolower(substr($Value, 0, 1)) === strtolower($Prefix)) {
            $Value = substr($Value, 1);
        }
        Log::Add("$Label correct : " . $value, 0);
        return $value;
    }
}

class User{
	private $Uid;
	private $Username;
	private $Nom;
	private $Prenom;
	private $Adresse;
	private $Complement;
	private $CodePostal;
	private $Ville;
	private $Email;
	private $Telephone;
	private $Portable;
	private $Siren;
	private $Database;
	
	
	public function getFromUid($Uid){}
	public function getFromUsername($Username){
		return self::getFromUid(self::usernameToUid(Check::Username($Username)));
	}
	
	public function getFromCid($Cid){
		return self::getFromUid(self::cidToUid(Check::Cid($Cid)));
	}
	
	public function getFromIid($Iid){
		return self::getFromUid(self::iidToUid(Check::Iid($Iid)));
	}
	
	public function Add($Tid){}
	public function Modify($Uid,$Cid,$Token, $Tid=0){}
	public function Delete($Uid,$Cid,$Token, $Tid=0){}
	
	public function getUid(){
		return $this->Uid; 
	}
	
	public function getUsername(){
		return $this->Username; 
	}
	
	public function getNom(){
		return $this->Nom; 
	}
	
	public function getPrenom(){
		return $this->Prenom;
	}
	
	public function getAdresse() {
		return $this->Adresse;
	}
	
	public function getComplement() {
		return $this->Complement;
	}
	
	public function getCodePostal() {
		return $this->CodePostal;
	}
	
	public function getVille() {
		return $this->Ville;
	}
	
	public function getEmail() {
		return $this->Email;
	}
	
	public function getTelephone() {
		return $this->Telephone;
	}
	
	public function getPortable() {
		return $this->Portable;
	}
	
	public function getSiren() {
		return $this->Siren;
	}
	
    public function setUsername($value) {
        $this->Username = Check::Username($value);
    }
   
    public function setNom($value) {
        $this->Nom = Check::Nom($value);
    }
    
	public function setPrenom($value) {
        $this->Prenom = Check::Prenom($value);
    }
    
	public function setAdresse($value) {
        $this->Adresse = Check::Adresse($value);
    }

    public function setComplement($value) {
        $this->Complement = Check::Complement($value);
    }

    public function setCodePostal($value) {
        $this->CodePostal = Check::CodePostal($value);
    }

    public function setVille($value) {
        $this->Ville = Check::Ville($value);
    }

    public function setEmail($value) {
        $this->Email = Check::Email($value);
    }
    
	public function setTelephone($value) {
        $this->Telephone = Check::Telephone($value);
    }
    
	public function setPortable($value) {
        $this->Portable = Check::Portable($value);
    }
   
    public function setSiren($value) {
        $this->Siren = Check::Siren($value);
    }

	private function usernameToUid($Username){}
	private function cidToUid($Cid){}
	private function iidToUid($Iid){}
	private function initDatabase($Database){}
}

?>
