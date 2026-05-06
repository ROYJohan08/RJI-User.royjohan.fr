<?php
require_once("config.php");

declare(strict_types=1);

enum ErrorLevel: int {
    case ALL     = 0;
    case INFO    = 1;
    case WARNING = 2;
    case ERROR   = 3;
}

enum TechLevel: int {
    case COMPTA = 0;
    case TECH   = 1;
    case COMM   = 2;
    case ADMIN  = 3;
}

class ErrorItem {
    public function __construct(
        public readonly string $content,
        public readonly ErrorLevel $level,
        public readonly \DateTimeImmutable $date
    ) {}
}

class Error {

    /** @var ErrorItem[] */
    private static array $errors = [];

    public static function add(string $content, ErrorLevel $level): bool {
        $content = trim(strip_tags($content));
        if (strlen($content) < 5) {
            return false;
        }

        if (class_exists(\Transliterator::class)) {
            $trans = \Transliterator::create('Any-Latin; Latin-ASCII');
            if ($trans !== null) {
                $content = $trans->transliterate($content);
            }
        }

        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        self::$errors[] = new ErrorItem(
            content: $content,
            level: $level,
            date: new \DateTimeImmutable()
        );

        self::sort();
        return true;
    }

    /** @return ErrorItem[] */
    public static function get(?ErrorLevel $level = null): array {
        if ($level === null || $level === ErrorLevel::ALL) {
            return self::$errors;
        }

        return array_values(
            array_filter(
                self::$errors,
                fn (ErrorItem $e) => $e->level === $level
            )
        );
    }

    public static function clear(): void {
        self::$errors = [];
    }

    private static function sort(): void {
        usort(
            self::$errors,
            fn (ErrorItem $a, ErrorItem $b) =>
                $a->level->value <=> $b->level->value
        );
    }
}

class Check{
	
	public static function cid(string $cid): int {
        return self::validateId($cid, 'C', 'Cid');
    }

    public static function uid(string $uid): int {
        return self::validateId($uid, 'U', 'Uid');
    }

    public static function id(string $iid): int {
        return self::validateId($iid, 'I', 'Iid');
    }

    public static function tid(string $tid): int {
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
        if (preg_match('/^[A-Za-zÀ-ÿ\s\-\']{2,100}$/', $ville)) {
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
        if (preg_match('/^0[1-5][0-9]{8}$|^09[0-9]{8}$/', $telephone)) {
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
			Error::add("Tech vide", ErrorLevel::WARNING);
			return false;
		}
		$tid = $tech->getTid();
		if ($tid <= 0) {
			Error::add("Tid incorrect", ErrorLevel::WARNING);
			return false;
		}

		$token = $tech->getToken();
		$token = $tech->getToken();
		if ($token === null || $token === "") {
			Error::add("Token vide", ErrorLevel::WARNING);
			return false;
		}
		$data = Utils::decodeJwt($token, Config::getKeyPath(2)); 
		if ($data === null) {
			Error::add("JWT incorrect", ErrorLevel::WARNING);
			return false;
		}
		if (!isset($data["data"]["tid"])) {
			Error::add("JWT incomplet", ErrorLevel::WARNING);
			return false;
		}
		if ($data["data"]["tid"] !== $tid) {
			Error::add("JWT ne correspond pas", ErrorLevel::WARNING);
			return false;
		}
		return true;
	}
	
	public static function futureDate(mixed $value): ?\DateTimeImmutable{
		if ($value instanceof \DateTimeImmutable) {
			$date = $value;
		} elseif (is_string($value)) {
			try {
				$date = new \DateTimeImmutable($value);
			} catch (\Exception $e) {
				Error::add("Date invalide : format incorrect", ErrorLevel::WARNING);
				return null;
			}
		} else {
			Error::add("Date invalide : type non supporté", ErrorLevel::WARNING);
			return null;
		}

		$now = new \DateTimeImmutable('now');
		if ($date <= $now) {
			Error::add("Date invalide : doit être dans le futur", ErrorLevel::WARNING);
			return null;
		}

		return $date;
	}
	
	private static function validateId(string $value, string $prefix, string $label):int{
        $value = trim($value);
        if ($value === '') {
            Error::add("$label is empty", ErrorLevel::WARNING);
            return 0;
        }
        if (!preg_match('/^(' . $prefix . '\d+|\d+)$/i', $value)) {
            Error::add("$label has an incorrect format", ErrorLevel::WARNING);
            return 0;
        }
        if (strtolower($value[0]) === strtolower($prefix)) {
            $value = substr($value, 1);
        }
		if (!ctype_digit($value)) {
		Error::add("$label is not numeric", ErrorLevel::WARNING);
		return 0;
	}
        return (int)$value;
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
		$rawKey = file_get_contents($privateKeyPath);
		$privateKey = openssl_pkey_get_private($rawKey);
		if ($privateKey === false) {
			Error::add("Clé privée invalide", ErrorLevel::ERROR);
			return "";
		}
		$header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'RS256'])));
		$payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode([
			'iat' => time(),
			'exp' => time() + 3600,
			'data' => $data
		])));
		if (!openssl_sign($header . "." . $payload, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
			Error::add("Échec de la signature JWT", ErrorLevel::ERROR);
			return "";
		}
		$signature64 = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
		return $header . "." . $payload . "." . $signature64;
	}
	public static function  decodeJwt(string $token, string $publicKeyPath): ?array {
		if (!file_exists($publicKeyPath)) {
			Error::add("Clé publique introuvable", ErrorLevel::ERROR);
			return null;
		}
		$publicKey = file_get_contents($publicKeyPath);
		$parts = explode('.', $token);
		if (count($parts) !== 3) {
			Error::add("Format de token invalide", ErrorLevel::WARNING);
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
				Error::add("Le token a expiré", ErrorLevel::WARNING);
				return null;
			}        
			return $payload;
		} 
		Error::add("Signature JWT invalide", ErrorLevel::WARNING);
		return null;
	}
}

class User{
    private ?int $uid = null;
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
	
    public function getUid(): ?int { return $this->uid; }
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

    public function setUid(int|string|null $v): void {if ($v === null) return;$this->uid = Check::uid((string)$v);}
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

    public function getByUid(string|int $uid): ?User {
        $uid = Check::uid($uid);
		if ($uid === 0){return null;}
        try {
            $stmt = $this->database->prepare(
                'SELECT `uid`,`username`,`nom`,`prenom`,`adresse`,`complement`,`codePostal`,`ville`,`email`,`telephone`,`portable`,`siren`
                 FROM `User` WHERE `uid` = ? LIMIT 1'
            );
            $stmt->execute([$uid]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) return null;

            $user = new User($data);
            return $user;

        } catch (Throwable $e) {
            Error::add("Erreur getByUid : " . $e->getMessage(), ErrorLevel::ERROR);
            return null;
        }
    }

    public function getByUsername(string $username): ?User {
        $uid = $this->fetchUid(
            'SELECT `uid` FROM `User` WHERE `username` = ? LIMIT 1',
            $username
        );
		if ($uid === null) return null;
		return $this->getByUid($uid);
    }

    public function getByCid(string $cid): ?User {
        $uid = $this->fetchUid(
            'SELECT `uid` FROM `Connexion` WHERE `cid` = ? LIMIT 1',
            $cid
        );
		if ($uid === null) return null;
		return $this->getByUid($uid);
    }

    public function getByIid(string $iid): ?User {
        $uid = $this->fetchUid(
            'SELECT `uid` FROM `Intervention` WHERE `iid` = ? LIMIT 1',
            $iid
        );
		if ($uid === null) return null;
		return $this->getByUid($uid);
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

    private function fetchUid(string $sql, string $value): ?int {
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

class Tech{
	private ?int       $tid=null;
	private ?User      $user=null;
	private ?TechLevel $level = null;
	private ?string    $token = null;

    public function __construct(array $data = []) {
        $this->hydrate($data);
    }

    public function hydrate(array $data): void {
        foreach ($data as $key => $value) {
            $method = "set" . ucfirst(Utils::toCamelCase($key));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
	}
    
	public function getTid(): ?int {return $this->tid;}
    public function getUser(): ?User {return $this->user;}
    public function getLevel(): ?TechLevel {return $this->level;}
    public function getToken(): ?string {return $this->token;}
	
	public function setTid(?string $v): void { if ($v === null) {return;}$this->tid = Check::tid($v); }
    public function setUser(User|null $v): void {if ($v instanceof User) {$this->user = $v;}else {$this->user = null;}}
	public function setLevel(TechLevel|null $v): void {if ($v instanceof TechLevel) {$this->level = $v;}else {$this->level = null;}}
    public function setToken(?string $v): void {$this->token = $v !== null ? Check::token($v) : null;}
	
}

class TechController{
	public function __construct(private PDO $database) {}
	
	public function getByTid(string $tid):?Tech{
		$tid = Check::tid($tid);
        if ($tid === 0) return null;

        try {
            $stmt = $this->database->prepare(
                'SELECT `tid`,`uid`,`level`,`token`
                 FROM `Tech` WHERE `tid` = ? LIMIT 1'
            );
            $stmt->execute([$uid]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) return null;

            $tech = new Tech($data);
            return $tech;

        } catch (Throwable $e) {
            Error::add("Erreur getByUid : " . $e->getMessage(), ErrorLevel::ERROR);
            return null;
        }
	}
	public function getByUid(string $uid):?Tech{
		return $this->getByTid($this->fetchTid(
            'SELECT `tid` FROM `Tech` WHERE `uid` = ? LIMIT 1',
            $uid
        ));
	}
	public function getByCid(string $cid):?Tech{
		return $this->getByUid($this->fetchTid(
            'SELECT `uid` FROM `Connexion` WHERE `cid` = ? LIMIT 1',
            $cid
        ));
	}
	public function delete(Tech $techToDelete, Tech $tech): bool {
		if ($techToDelete->getTid() === 0 || $tech->getTid() === 0) {
			Error::add("IDs invalides pour la suppression", ErrorLevel::WARNING);
			return false;
		}
		if (!Check::tech($tech)) {
			Error::add("Technicien effectuant l'action invalide", ErrorLevel::WARNING);
			return false;
		}
		if ($tech->getLevel()->value <= $techToDelete->getLevel()->value) {
			Error::add("Permissions insuffisantes : niveau supérieur requis", ErrorLevel::WARNING);
			return false;
		}
		try {
			$stmt = $this->database->prepare('DELETE FROM `Tech` WHERE `tid` = ?');
			$ok = $stmt->execute([$techToDelete->getTid()]);
			if ($ok && $stmt->rowCount() > 0) {
				return true;
			}
			Error::add("Aucun enregistrement supprimé", ErrorLevel::WARNING);
			return false;
		} catch (Throwable $e) {
			Error::add("Erreur lors de la suppression Tech : " . $e->getMessage(), ErrorLevel::ERROR);
			return false;
		}
	}
	private function fetchTid(string $sql, string $value):?int{
		try {
            $stmt = $this->database->prepare($sql);
            $stmt->execute([$value]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['tid'] : null;
        } catch (Throwable $e) {
            Error::add("Erreur fetchUid : " . $e->getMessage(), ErrorLevel::ERROR);
            return null;
        }
	}
	
}


class Connexion{
	private ?int               $cid=null;
	private ?string            $uid=null;
	private ?string            $token=null;
	private ?DateTimeImmutable $tokenValidity=null;
	
	public function __construct(array $data){
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
	public function getCid():?int{return $this->cid;}
	public function getUid():?int{return $this->uid;}
	public function getToken():?string{return $this->token;}
	public function getTokenValidity():?DateTimeImmutable{return $this->tokenValidity;}
	
	public function setUid(?string $v): void { $this->uid = $v !== null ? Check::uid($v) : null; }
	public function setToken(?string $v): void { $this->token = $v !== null ? Check::Token($v) : null; }
	public function setTokenValidity(?string $v): void { $this->tokenValidity = $v !== null ? Check::futureDate($v) : null; }
	
}

?>
