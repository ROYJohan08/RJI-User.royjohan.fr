<?php

enum ErrorLevel: int {
    case ALL = 0;
	case INFO = 1;
    case WARNING = 2;
    case ERROR = 3;
}
class ErrorItem{
    public function __construct(public readonly string $Content,public readonly ErrorLevel $Level,public readonly \DateTimeImmutable $Date) {}
}
class Error{
    private static array $errors = [];

    public static function Add(string $Content, ErrorLevel $Level): bool{
        $Content = trim(strip_tags($Content));
        if (strlen($Content) < 5) {
            return false;
        }
        if (class_exists('Transliterator')) {
            $trans = \Transliterator::create('Any-Latin; Latin-ASCII');
            $Content = $trans->transliterate($Content);
        }
        $Content = htmlspecialchars($Content, ENT_QUOTES, 'UTF-8');
        self::$errors[] = new ErrorItem(Content: $Content,Level: $Level,Date: new \DateTimeImmutable());
        self::sort();
        return true;
    }
   
   public static function get(?ErrorLevel $Level = null): array{
        if ($Level === null || $Level=== ErrorLevel::ALL) {
            return self::$errors;
        }
        return array_values(array_filter(self::$errors,fn(ErrorItem $e) => $e->Level === $Level));
    }
   
   public static function clear(): void{
		self::$errors = [];
	}

    private static function sort(): void{
        usort(self::$errors, fn(ErrorItem $a, ErrorItem $b) =>
            $a->Level->value <=> $b->Level->value
        );
    }
}
class Check{
    
	
	public static function Cid(string $Cid): string {
        return self::validateId($Cid, 'C', 'Cid');
    }

    public static function Uid(string $Uid): string {
        return self::validateId($Uid, 'U', 'Uid');
    }

    public static function Iid(string $Iid): string {
        return self::validateId($Iid, 'I', 'Iid');
    }

    public static function Tid(string $Tid): string {
        return self::validateId($Tid, 'T', 'Tid');
	}
	
	public static function Token(string $Token): string{
        $Token = trim($Token);
        if ($Token === '') {
            Error::Add("Token is empty", ErrorLevel::WARNING);
            return "";
        }
        if (!preg_match('/^[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]*$/', $Token)) {
            Error::Add("Token has an incorrect format", ErrorLevel::WARNING);
            return "";
        }
        return $Token;
    }
	
	public static function Username(string $Username): string{
        $Username = trim($Username);
        $isEmail = filter_var($Username, FILTER_VALIDATE_EMAIL);
        $isAlpha = preg_match('/^[A-Za-zÀ-ÿ\-\'\s]{2,}$/', $Username);
        if ($isEmail || $isAlpha) {
            return $Username;
        }
        Error::Add("Username incorrect (Email ou Nom attendu)", ErrorLevel::WARNING);
        return "";
    }

    public static function Password(string $Password): string{
        if (preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $Password)) {
            return $Password;
        }
        Error::Add("Password trop faible (8 car. min, 1 Maj, 1 Min, 1 Chiffre)", ErrorLevel::WARNING);
        return "";
    }

    public static function Code(string $Code): string{
        $Code = trim($Code);
        if (preg_match('/^[A-Za-z0-9]{8}$/', $Code)) {
            return $Code;
        }
        Error::Add("Code incorrect (8 caractères alphanumériques)", ErrorLevel::WARNING);
        return "";
    }

	public static function Nom(string $Nom): string{
        $Nom = trim($Nom);
        if (preg_match('/^[A-Za-zÀ-ÿ\s\-\']{2,50}$/', $Nom)) {
            return $Nom;
        }
        Error::Add("Nom invalide", ErrorLevel::WARNING);
        return "";
    }

    public static function Prenom(string $Prenom): string{
        $Prenom = trim($Prenom);
        if (preg_match('/^[A-Za-zÀ-ÿ\s\-\']{2,50}$/', $Prenom)) {
            return $Prenom;
        }
        Error::Add("Prénom invalide", ErrorLevel::WARNING);
        return "";
    }

    public static function Adresse(string $Adresse): string{
        $Adresse = trim($Adresse);
        if (strlen($Adresse) > 5) {
            return $Adresse;
        }
        Error::Add("Adresse trop courte", ErrorLevel::WARNING);
        return "";
    }

    public static function Complement(string $Complement): string{
        return trim($Complement);
    }

    public static function CodePostal(string $CodePostal): string{
        $CodePostal = trim($CodePostal);
        if (preg_match('/^[0-9]{5}$/', $CodePostal)) {
            return $CodePostal;
        }
        Error::Add("Code postal invalide (5 chiffres)", ErrorLevel::WARNING);
        return "";
    }
   
   public static function Ville(string $Ville): string{
        $Ville = trim($Ville);
        if (preg_match('/^[A-Za-zÀ-ÿ\s\-\'"]{2,100}$/', $Ville)) {
            return $Ville;
        }
        Error::Add("Ville invalide", ErrorLevel::WARNING);
        return "";
    }

    public static function Email(string $Email): string{
        $Email = trim($Email);
        if (filter_var($Email, FILTER_VALIDATE_EMAIL)) {
            return $Email;
        }
        Error::Add("Format Email invalide", ErrorLevel::WARNING);
        return "";
    }
    
	public static function Telephone(string $Telephone): string{
        $Telephone = str_replace([' ', '.', '-', '/'], '', trim($Telephone));
        if (preg_match('/^0[1-59][0-9]{8}$/', $Telephone)) {
            return $Telephone;
        }
        Error::Add("Téléphone fixe invalide", ErrorLevel::WARNING);
        return "";
    }

    public static function Portable(string $Portable): string{
        $Portable = str_replace([' ', '.', '-', '/'], '', trim($Portable));
        if (preg_match('/^0[67][0-9]{8}$/', $Portable)) {
            return $Portable;
        }
        Error::Add("Téléphone portable invalide", ErrorLevel::WARNING);
        return "";
    }

    public static function Siren(string $Siren): string{
        $Siren = str_replace(' ', '', trim($Siren));

        if (!preg_match('/^[0-9]{9}$/', $Siren)) {
            Error::Add("SIREN invalide (doit contenir 9 chiffres)", ErrorLevel::WARNING);
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
            return $Siren;
        }

        Error::Add("SIREN incorrect (échec de la clé de contrôle)", ErrorLevel::WARNING);
        return "";
    }
	
	private static function validateId(string $Value, string $Prefix, string $Label): string{
        $Value = trim($Value);
        if ($Value === '') {
            Error::Add("$Label is empty", ErrorLevel::WARNING);
            return "";
        }
        if (!preg_match('/^(' . $Prefix . '\d+|\d+)$/i', $Value)) {
            Error::Add("$Label has an incorrect format", ErrorLevel::WARNING);
            return "";
        }
        if (strtolower($Value[0]) === strtolower($Prefix)) {
            $Value = substr($Value, 1);
        }
        return $Value;
    }
}

class User{
    private ?string $Uid = null;
    private ?string $Username = null;
    private ?string $Nom = null;
    private ?string $Prenom = null;
    private ?string $Adresse = null;
    private ?string $Complement = null;
    private ?string $CodePostal = null;
    private ?string $Ville = null;
    private ?string $Email = null;
    private ?string $Telephone = null;
    private ?string $Portable = null;
    private ?string $Siren = null;
	
    public function hydrate(array $data): void{
        foreach ($data as $key => $value) {
            $method = "set" . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }
	
    public function getUid(): ?string { return $this->Uid; }
    public function getUsername(): ?string { return $this->Username; }
    public function getNom(): ?string { return $this->Nom; }
    public function getPrenom(): ?string { return $this->Prenom; }
    public function getAdresse(): ?string { return $this->Adresse; }
    public function getComplement(): ?string { return $this->Complement; }
    public function getCodePostal(): ?string { return $this->CodePostal; }
    public function getVille(): ?string { return $this->Ville; }
    public function getEmail(): ?string { return $this->Email; }
    public function getTelephone(): ?string { return $this->Telephone; }
    public function getPortable(): ?string { return $this->Portable; }
    public function getSiren(): ?string { return $this->Siren; }

    public function setUid(string $v): void { $this->Uid = Check::Uid($v); }
    public function setUsername(string $v): void { $this->Username = Check::Username($v); }
    public function setNom(string $v): void { $this->Nom = Check::Nom($v); }
    public function setPrenom(string $v): void { $this->Prenom = Check::Prenom($v); }
    public function setAdresse(string $v): void { $this->Adresse = Check::Adresse($v); }
    public function setComplement(string $v): void { $this->Complement = Check::Complement($v); }
    public function setCodePostal(string $v): void { $this->CodePostal = Check::CodePostal($v); }
    public function setVille(string $v): void { $this->Ville = Check::Ville($v); }
    public function setEmail(string $v): void { $this->Email = Check::Email($v); }
    public function setTelephone(string $v): void { $this->Telephone = Check::Telephone($v); }
    public function setPortable(string $v): void { $this->Portable = Check::Portable($v); }
    public function setSiren(string $v): void { $this->Siren = Check::Siren($v); }
}


class UserRepository{
    
	private PDO $Database;
	
	public function __construct(private PDO $Database) {}
	
	public function getByUid(string $Uid): ?User{
        $Uid = Check::Uid($Uid);
        if ($Uid === "") return null;
        try {
            $stmt = $this->Database->prepare('SELECT `Uid`,`Username`,`Nom`,`Prenom`,`Adresse`,`Complement`,`CodePostal`,`Ville`,`Email`,`Telephone`,`Portable`,`Siren` FROM `User` WHERE `Uid` = ? LIMIT 1');
            $stmt->execute([$Uid]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$data) return null;
            $user = new User();
            $user->hydrate($data);
            return $user;
        } catch (Throwable $e) {
            Error::Add("Erreur getByUid : " . $e->getMessage(), ErrorLevel::ERROR);
            return null;
        }
    }
   
    public function save(User $user): bool{
        try {
            if ($user->getUid() === null) {
                $stmt = $this->Database->prepare('INSERT INTO `User` (`Username`,`Nom`,`Prenom`,`Adresse`,`Complement`,`CodePostal`,`Ville`,`Email`,`Telephone`,`Portable`,`Siren`) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                $ok = $stmt->execute([$user->getUsername(),$user->getNom(),$user->getPrenom(),$user->getAdresse(),$user->getComplement(),$user->getCodePostal(),$user->getVille(),$user->getEmail(),$user->getTelephone(),$user->getPortable(),$user->getSiren()]);
                if ($ok) {
                    $user->setUid($this->Database->lastInsertId());
                }
                return $ok;
            }
            $stmt = $this->Database->prepare( 'UPDATE `User` SET `Username`=?,`Nom`=?,`Prenom`=?,`Adresse`=?,`Complement`=?,`CodePostal`=?,`Ville`=?,`Email`=?,`Telephone`=?,`Portable`=?,`Siren`=? WHERE `Uid`=?');
            return $stmt->execute([$user->getUsername(),$user->getNom(),$user->getPrenom(),$user->getAdresse(),$user->getComplement(),$user->getCodePostal(),$user->getVille(),$user->getEmail(),$user->getTelephone(),$user->getPortable(),$user->getSiren(),$user->getUid()]);
        } catch (Throwable $e) {
            Error::Add("Erreur save User : " . $e->getMessage(), ErrorLevel::ERROR);
            return false;
        }
    }
	
    public function delete(User $user): bool{
        if ($user->getUid() === null) {
            Error::Add("Impossible de supprimer un User sans Uid", ErrorLevel::WARNING);
            return false;
        }
        try {
            $stmt = $this->Database->prepare('DELETE FROM `User` WHERE `Uid` = ? LIMIT 1');
            return $stmt->execute([$user->getUid()]);
        } catch (Throwable $e) {
            Error::Add("Erreur delete User : " . $e->getMessage(), ErrorLevel::ERROR);
            return false;
        }
    }
	
    public function usernameToUid(string $Username): ?string{
        return $this->fetchUid('SELECT `Uid` FROM `User` WHERE `Username` = ? LIMIT 1', $Username);
    }

    public function cidToUid(string $Cid): ?string{
        return $this->fetchUid('SELECT `Uid` FROM `Connexion` WHERE `Cid` = ? LIMIT 1', $Cid);
    }

    public function iidToUid(string $Iid): ?string{
        return $this->fetchUid('SELECT `Uid` FROM `Intervention` WHERE `Iid` = ? LIMIT 1', $Iid);
    }

    private function fetchUid(string $sql, string $value): ?string{
        try {
            $stmt = $this->Database->prepare($sql);
            $stmt->execute([$value]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['Uid'] : null;
        } catch (Throwable $e) {
            Error::Add("Erreur fetchUid : " . $e->getMessage(), ErrorLevel::ERROR);
            return null;
        }
    }
}

?>
