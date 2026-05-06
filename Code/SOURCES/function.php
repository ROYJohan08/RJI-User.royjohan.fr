<?php

enum ErrorLevel: int {
    case ALL = 0;
	case INFO = 1;
    case WARNING = 2;
    case ERROR = 3;
}

class ErrorItem{
    public function __construct(public readonly string $content,public readonly ErrorLevel $level,public readonly \DateTimeImmutable $date) {}
}

class Error{
    private static array $errors = [];

    public static function add(string $content, ErrorLevel $level): bool{
        $content = trim(strip_tags($content));
        if (strlen($content) < 5) {
            return false;
        }
        if (class_exists('Transliterator')) {
            $trans = \Transliterator::create('Any-Latin; Latin-ASCII');
            $content = $trans->transliterate($content);
        }
        $content = htmlspecialchars($Content, ENT_QUOTES, 'UTF-8');
        self::$errors[] = new ErrorItem(Content: $content,Level: $level,Date: new \DateTimeImmutable());
        self::sort();
        return true;
    }
   
   public static function get(?ErrorLevel $level = null): array{
        if ($level === null || $level=== ErrorLevel::ALL) {
            return self::$errors;
        }
        return array_values(array_filter(self::$errors,fn(ErrorItem $e) => $e->level === $level));
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
	
	public static function cid(string $cid): string {
        return self::validateId($cid, 'C', 'Cid');
    }

    public static function uid(string $uid): string {
        return self::validateId($uid, 'U', 'Uid');
    }

    public static function id(string $iid): string {
        return self::validateId($iid, 'I', 'Iid');
    }

    public static function tid(string $tid): string {
        return self::validateId($tid, 'T', 'Tid');
	}
	
	public static function token(string $token): string{
        $token = trim($token);
        if ($token === '') {
            Error::add("Token is empty", ErrorLevel::WARNING);
            return "";
        }
        if (!preg_match('/^[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]*$/', $token)) {
            Error::add("Token has an incorrect format", ErrorLevel::WARNING);
            return "";
        }
        return $token;
    }
	
	public static function username(string $username): string{
        $username = trim($username);
        $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);
        $isAlpha = preg_match('/^[A-Za-zÀ-ÿ\-\'\s]{2,}$/', $username);
        if ($isEmail || $isAlpha) {
            return $username;
        }
        Error::add("Username incorrect (Email ou Nom attendu)", ErrorLevel::WARNING);
        return "";
    }

    public static function password(string $password): string{
        if (preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
            return $password;
        }
        Error::add("Password trop faible (8 car. min, 1 Maj, 1 Min, 1 Chiffre)", ErrorLevel::WARNING);
        return "";
    }

    public static function code(string $code): string{
        $code = trim($code);
        if (preg_match('/^[A-Za-z0-9]{8}$/', $code)) {
            return $code;
        }
        Error::add("Code incorrect (8 caractères alphanumériques)", ErrorLevel::WARNING);
        return "";
    }

	public static function nom(string $nom): string{
        $nom = trim($nom);
        if (preg_match('/^[A-Za-zÀ-ÿ\s\-\']{2,50}$/', $nom)) {
            return $nom;
        }
        Error::add("Nom invalide", ErrorLevel::WARNING);
        return "";
    }

    public static function prenom(string $prenom): string{
        $prenom = trim($prenom);
        if (preg_match('/^[A-Za-zÀ-ÿ\s\-\']{2,50}$/', $prenom)) {
            return $prenom;
        }
        Error::add("Prénom invalide", ErrorLevel::WARNING);
        return "";
    }

    public static function adresse(string $adresse): string{
        $adresse = trim($adresse);
        if (strlen($adresse) > 5) {
            return $adresse;
        }
        Error::add("Adresse trop courte", ErrorLevel::WARNING);
        return "";
    }

    public static function complement(string $complement): string{
        return trim($complement);
    }

    public static function codePostal(string $codePostal): string{
        $codePostal = trim($codePostal);
        if (preg_match('/^[0-9]{5}$/', $codePostal)) {
            return $codePostal;
        }
        Error::add("Code postal invalide (5 chiffres)", ErrorLevel::WARNING);
        return "";
    }
   
    public static function ville(string $ville): string{
        $ville = trim($ville);
        if (preg_match('/^[A-Za-zÀ-ÿ\s\-\'"]{2,100}$/', $ville)) {
            return $ville;
        }
        Error::add("Ville invalide", ErrorLevel::WARNING);
        return "";
    }

    public static function email(string $email): string{
        $email = trim($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
        Error::add("Format Email invalide", ErrorLevel::WARNING);
        return "";
    }
    
	public static function telephone(string $telephone): string{
        $telephone = str_replace([' ', '.', '-', '/'], '', trim($telephone));
        if (preg_match('/^0[1-59][0-9]{8}$/', $telephone)) {
            return $telephone;
        }
        Error::add("Téléphone fixe invalide", ErrorLevel::WARNING);
        return "";
    }

    public static function portable(string $portable): string{
        $portable = str_replace([' ', '.', '-', '/'], '', trim($portable));
        if (preg_match('/^0[67][0-9]{8}$/', $portable)) {
            return $portable;
        }
        Error::add("Téléphone portable invalide", ErrorLevel::WARNING);
        return "";
    }

    public static function siren(string $siren): string{
        $siren = str_replace(' ', '', trim($siren));

        if (!preg_match('/^[0-9]{9}$/', $siren)) {
            Error::add("SIREN invalide (doit contenir 9 chiffres)", ErrorLevel::WARNING);
            return "";
        }
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $digit = (int)$siren[$i];
            if ($i % 2 !== 0) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }
        if ($sum % 10 === 0) {
            return $siren;
        }

        Error::add("SIREN incorrect (échec de la clé de contrôle)", ErrorLevel::WARNING);
        return "";
    }
	
	public static function tech(?Tech $tech): bool {
		if ($tech === null) {
			Error::add("Tech vide", ErrorLevel::WARNING);[cite: 1]
			return false;
		}
		$tid = $tech->getTid();
		if (empty($tid) || $tid === 0) {
			Error::add("Tid incorrect", ErrorLevel::WARNING);[cite: 1]
			return false;
		}

		$token = $tech->getToken();
		$data = Utils::decodeJwt($token, "./public.key"); 
		if ($data === null) {
			Error::add("JWT incorrect", ErrorLevel::WARNING);[cite: 1]
			return false;
		}
		if (!isset($data["data"]["tid"])) {
			Error::add("JWT incomplet", ErrorLevel::WARNING);[cite: 1]
			return false;
		}
		if ($data["data"]["tid"] !== $tid) {
			Error::add("JWT ne correspond pas", ErrorLevel::WARNING);[cite: 1]
			return false;
		}
		return true;
	}
		
	private static function validateId(string $value, string $prefix, string $label): string{
        $value = trim($value);
        if ($value === '') {
            Error::add("$label is empty", ErrorLevel::WARNING);
            return "";
        }
        if (!preg_match('/^(' . $prefix . '\d+|\d+)$/i', $value)) {
            Error::add("$label has an incorrect format", ErrorLevel::WARNING);
            return "";
        }
        if (strtolower($value[0]) === strtolower($prefix)) {
            $value = substr($value, 1);
        }
        return $value;
    }
}

class Utils{
	public static function toCamelCase(string $str): string {
		$str = str_replace(['-', '_'], ' ', strtolower($str));
		$str = ucwords($str);
		$str = str_replace(' ', '', $str);
		return lcfirst($str);
	}
	public static function generateJwt(mixed $data, string $privateKeyPath): string {
		$privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
		$header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'RS256'])));
		$payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode([
			'iat' => time(),
			'exp' => time() + 3600,
			'data' => $data
		])));
		openssl_sign($header . "." . $payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
		$signature64 = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
		return $header . "." . $payload . "." . $signature64;
	}
	public static function  decodeJwt(string $token, string $publicKeyPath): ?array {
		if (!file_exists($publicKeyPath)) {
			Error::add("Clé publique introuvable", ErrorLevel::ERROR);[cite: 1]
			return null;
		}
		$publicKey = file_get_contents($publicKeyPath);
		$parts = explode('.', $token);
		if (count($parts) !== 3) {
			Error::add("Format de token invalide", ErrorLevel::WARNING);[cite: 1]
			return null;
		}
		[$header64, $payload64, $signature64] = $parts;
		$signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $signature64));
		$dataToVerify = $header64 . '.' . $payload64;
		$isValid = openssl_verify(
			$dataToVerify, 
			$signature, 
			$publicKey, 
			OPENSSL_ALGO_SHA256
		);
		if ($isValid === 1) {
			$payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload64)), true);
			if (isset($payload['exp']) && $payload['exp'] < time()) {
				Error::add("Le token a expiré", ErrorLevel::WARNING);[cite: 1]
				return null;
			}        
			return $payload;
		} 
		Error::add("Signature JWT invalide", ErrorLevel::WARNING);[cite: 1]
		return null;
	}
}

class User{
    private ?string $uid = null;
    private ?string $username = null;
    private ?string $nom = null;
    private ?string $prenom = null;
    private ?string $adresse = null;
    private ?string $complement = null;
    private ?string $codePostal = null;
    private ?string $ville = null;
    private ?string $email = null;
    private ?string $telephone = null;
    private ?string $portable = null;
    private ?string $siren = null;
	
	public function __construct(array $data) {
		$this->hydrate($data);
	}
	
    public function hydrate(array $data): void{
        foreach ($data as $key => $value) {
            $method = "set" . ucfirst(Utils::toCamelCase($key));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }
	
    public function getUid(): ?string { return $this->uid; }
    public function getUsername(): ?string { return $this->username; }
    public function getNom(): ?string { return $this->nom; }
    public function getPrenom(): ?string { return $this->prenom; }
    public function getAdresse(): ?string { return $this->adresse; }
    public function getComplement(): ?string { return $this->complement; }
    public function getCodePostal(): ?string { return $this->codePostal; }
    public function getVille(): ?string { return $this->ville; }
    public function getEmail(): ?string { return $this->email; }
    public function getTelephone(): ?string { return $this->telephone; }
    public function getPortable(): ?string { return $this->portable; }
    public function getSiren(): ?string { return $this->siren; }

    public function setUid(?string $v): void { $this->uid = $v !== null ? Check::uid($v) : null; }
    public function setUsername(?string $v): void {$this->username = $v !== null ? Check::username($v) : null;}
    public function setNom(?string $v): void { $this->nom = $v !== null ? Check::nom($v) : null; }
    public function setPrenom(?string $v): void {$this->prenom = $v !== null ? Check::prenom($v) : null; }
    public function setAdresse(?string $v): void { $this->adresse = $v !== null ? Check::adresse($v) : null; }
    public function setComplement(?string $v): void { $this->complement = $v !== null ? Check::complement($v) : null;}
    public function setCodePostal(?string $v): void {$this->codePostal = $v !== null ? Check::codePostal($v) : null;}
    public function setVille(?string $v): void {$this->ville = $v !== null ? Check::ville($v) : null; }
    public function setEmail(?string $v): void { $this->email = $v !== null ? Check::email($v) : null; }
    public function setTelephone(?string $v): void { $this->telephone = $v !== null ? Check::telephone($v) : null; }
    public function setPortable(?string $v): void { $this->portable = $v !== null ? Check::portable($v) : null; }
    public function setSiren(?string $v): void { $this->siren = $v !== null ? Check::siren($v) : null; }
}


class UserController {

    public function __construct(private PDO $database) {}

    public function getByUid(string $uid): ?User {
        $uid = Check::uid($uid);
        if ($uid === "") return null;

        try {
            $stmt = $this->database->prepare(
                'SELECT `uid`,`username`,`nom`,`prenom`,`adresse`,`complement`,`codePostal`,`ville`,`email`,`telephone`,`portable`,`siren`
                 FROM `User` WHERE `uid` = ? LIMIT 1'
            );
            $stmt->execute([$uid]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) return null;

            $user = new User();
            $user->hydrate($data);
            return $user;

        } catch (Throwable $e) {
            Error::add("Erreur getByUid : " . $e->getMessage(), ErrorLevel::ERROR);
            return null;
        }
    }

    public function getByUsername(string $username): ?User {
        return $this->getByUid($this->fetchUid(
            'SELECT `uid` FROM `User` WHERE `username` = ? LIMIT 1',
            $username
        ));
    }

    public function getByCid(string $cid): ?User {
        return $this->getByUid($this->fetchUid(
            'SELECT `uid` FROM `Connexion` WHERE `cid` = ? LIMIT 1',
            $cid
        ));
    }

    public function getByIid(string $iid): ?User {
        return $this->getByUid($this->fetchUid(
            'SELECT `uid` FROM `Intervention` WHERE `iid` = ? LIMIT 1',
            $iid
        ));
    }

    public function save(User $user, Tech $tech): bool {
        try {
            if ($user->getUid() === null) {

                if ($tech === null || !Check::tech($tech)) {
                    Error::add("Ajout d'un user refusé, Tech invalide", ErrorLevel::WARNING);
                    return false;
                }

                $stmt = $this->database->prepare(
                    'INSERT INTO `User` (`username`,`nom`,`prenom`,`adresse`,`complement`,`codePostal`,`ville`,`email`,`telephone`,`portable`,`siren`)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)'
                );

                $ok = $stmt->execute([
                    $user->getUsername(), $user->getNom(), $user->getPrenom(),
                    $user->getAdresse(), $user->getComplement(), $user->getCodePostal(),
                    $user->getVille(), $user->getEmail(), $user->getTelephone(),
                    $user->getPortable(), $user->getSiren()
                ]);

                if ($ok) {
                    $user->setUid($this->database->lastInsertId());
                }

                return $ok;
            }

            // UPDATE
            if (($tech === null || !Check::tech($tech)) &&
                (!isset($_SESSION['Username']) || $user->getUsername() !== $_SESSION['Username'])) {

                Error::add("Modification d'un user refusée, Tech invalide ou User non autorisé", ErrorLevel::WARNING);
                return false;
            }

            $stmt = $this->database->prepare(
                'UPDATE `User` SET `username`=?,`nom`=?,`prenom`=?,`adresse`=?,`complement`=?,`codePostal`=?,`ville`=?,`email`=?,`telephone`=?,`portable`=?,`siren`=? WHERE `uid`=?'
            );

            return $stmt->execute([
                $user->getUsername(), $user->getNom(), $user->getPrenom(),
                $user->getAdresse(), $user->getComplement(), $user->getCodePostal(),
                $user->getVille(), $user->getEmail(), $user->getTelephone(),
                $user->getPortable(), $user->getSiren(), $user->getUid()
            ]);

        } catch (Throwable $e) {
            Error::add("Erreur save User : " . $e->getMessage(), ErrorLevel::ERROR);
            return false;
        }
    }

    public function delete(User $user, Tech $tech): bool {
        if ($user->getUid() === null) {
            Error::add("Impossible de supprimer un User sans Uid", ErrorLevel::WARNING);
            return false;
        }

        if ($tech === null || !Check::tech($tech)) {
            Error::add("Suppression d'un user refusée, Tech invalide", ErrorLevel::WARNING);
            return false;
        }

        try {
            $stmt = $this->database->prepare('DELETE FROM `User` WHERE `uid` = ?');
            return $stmt->execute([$user->getUid()]);
        } catch (Throwable $e) {
            Error::add("Erreur delete User : " . $e->getMessage(), ErrorLevel::ERROR);
            return false;
        }
    }

    private function fetchUid(string $sql, string $value): ?string {
        try {
            $stmt = $this->database->prepare($sql);
            $stmt->execute([$value]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['uid'] : null;
        } catch (Throwable $e) {
            Error::add("Erreur fetchUid : " . $e->getMessage(), ErrorLevel::ERROR);
            return null;
        }
    }
}


?>
