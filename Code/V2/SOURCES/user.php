<?php
	require_once(__DIR__ . "/errors.php");
	require_once(__DIR__ . "/config.php");

	enum Role: int {
		case UTILISATEUR    = 1 << 0;
		case ADMINISTRATEUR = 1 << 1;
		case TECHNICIEN     = 1 << 2;
		case COMPTABLE      = 1 << 3;
		case COMMERCIAL     = 1 << 4;
		case DEVELOPPEUR    = 1 << 5;

		public static function hasRole(int $roles, Role $role): bool {
			return ($roles & $role->value) === $role->value;
		}

		public static function addRole(int $roles, Role $role): int {
			return $roles | $role->value;
		}

		public static function removeRole(int $roles, Role $role): int {
			return $roles & ~$role->value;
		}
	}

	class UserCard {
		private ?int    $uid        = null;
		private ?string $nom        = null;
		private ?string $prenom     = null;
		private ?string $telephone  = null;
		private ?string $email      = null;
		private ?string $adresse    = null;
		private ?string $complement = null;
		private ?string $codePostal = null;
		private ?string $ville      = null;
		private ?string $wallet     = null;
		private ?string $siren      = null;
		private int     $roles      = 0;

		public function __construct(array $data = null) {
			if ($data!=null && !empty($data)) {
				$this->hydrate($data);
			}
		}

		public function hydrate(array $data): void {
			foreach ($data as $key => $value) {
				if ($key === "role" || $key === "roles") {
					$this->setRole($value);
					continue;
				}

				$method = 'set' . str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $key)));

				if (method_exists($this, $method)) {
					$success = $this->$method($value);
					if (!$success) {
						Errors::add("Hydrate: impossible d’hydrater '$key' avec la valeur '" . print_r($value, true) . "'", ErrorLevel::WARNING);
					}
				} else {
					Errors::add("Hydrate: propriété inconnue '$key' ignorée", ErrorLevel::INFO);
				}
			}
		}

		public function getUid(): ?int {
			return $this->uid;
		}
		public function getNom(): ?string {
			return $this->nom;
		}
		public function getPrenom(): ?string {
			return $this->prenom;
		}
		public function getTelephone(): ?string {
			return $this->telephone;
		}
		public function getEmail(): ?string {
			return $this->email;
		}
		public function getAdresse(): ?string {
			return $this->adresse;
		}
		public function getComplement(): ?string { 
			return $this->complement;
		}
		public function getCodePostal(): ?string {
			return $this->codePostal; 
		}
		public function getVille(): ?string { 
			return $this->ville; 
		}
		public function getWallet(): ?string { 
			return $this->wallet; 
		}
		public function getSiren(): ?string { 
			return $this->siren; 
		}
		public function getRoles(): int {
			return $this->roles;
		}

		public function setUid($v): bool {
			if ($v === null) {
				$this->uid = null;
				return true;
			}
			if (!is_numeric($v) || (int)$v <= 0) {
				Errors::add("uid invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->uid = (int)$v;
			return true;
		}
		public function setNom($v): bool {
			if ($v === null) {
				$this->nom = null;
				return true;
			}
			if (!is_string($v) || !preg_match('/^[\p{L}\-\' ]+$/u', $v)) {
				Errors::add("Nom invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->nom = strtoupper(trim($v));
			return true;
		}
		public function setPrenom($v): bool {
			if ($v === null) {
				$this->prenom = null;
				return true;
			}
			if (!is_string($v) || !preg_match('/^[\p{L}\-\' ]+$/u', $v)) {
				Errors::add("Prénom invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->prenom = ucfirst(strtolower(trim($v)));
			return true;
		}
		public function setTelephone($v): bool {
			if ($v === null) {
				$this->telephone = null;
				return true;
			}
			if (!is_string($v)) {
				Errors::add("Téléphone invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}

			$clean = preg_replace('/\D+/', '', $v);

			if (str_starts_with($clean, '0033')) {
				$clean = '0' . substr($clean, 4);
			}
			elseif (str_starts_with($clean, '33')) {
				$clean = '0' . substr($clean, 2);
			}

			if (!preg_match('/^0[1-9]\d{8}$/', $clean)) {
				Errors::add("Numéro de téléphone FR invalide : $v", ErrorLevel::ERROR);
				return false;
			}

			$this->telephone = $clean;
			return true;
		}
		public function setEmail($v): bool {
			if ($v === null) {
				$this->email = null;
				return true;
			}
			if (!is_string($v) || !filter_var($v, FILTER_VALIDATE_EMAIL)) {
				Errors::add("Email invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->email = strtolower(trim($v));
			return true;
		}
		public function setAdresse($v): bool {
			if ($v === null) {
				$this->adresse = null;
				return true;
			}
			if (!is_string($v)) {
				Errors::add("Adresse invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->adresse = trim($v);
			return true;
		}
		public function setComplement($v): bool {
			if ($v === null) {
				$this->complement = null;
				return true;
			}
			if (!is_string($v)) {
				Errors::add("Complément invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->complement = trim($v);
			return true;
		}
		public function setCodePostal($v): bool {
			if ($v === null) {
				$this->codePostal = null;
				return true;
			}
			if (!is_string($v) || !preg_match('/^\d{5}$/', $v)) {
				Errors::add("Code postal invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->codePostal = $v;
			return true;
		}
		public function setVille($v): bool {
			if ($v === null) {
				$this->ville = null;
				return true;
			}
			if (!is_string($v) || !preg_match('/^[\p{L}\-\' ]+$/u', $v)) {
				Errors::add("Ville invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->ville = strtoupper(trim($v));
			return true;
		}
		public function setWallet($v): bool {
			if ($v === null) {
				$this->wallet = null;
				return true;
			}
			if (!is_string($v)) {
				Errors::add("Wallet doit être une chaîne de caractères", ErrorLevel::ERROR);
				return false;
			}

			$v = trim($v);

			if (!filter_var($v, FILTER_VALIDATE_URL)) {
				Errors::add("Lien Google Wallet invalide : $v", ErrorLevel::ERROR);
				return false;
			}

			$host = parse_url($v, PHP_URL_HOST);
			$allowedDomains = ['pay.google.com', 'wallet.google.com', 'wallet.google', 'google.com'];

			$isValidDomain = false;
			foreach ($allowedDomains as $domain) {
				if ($host === $domain || str_ends_with($host, '.' . $domain)) {
					$isValidDomain = true;
					break;
				}
			}

			if (!$isValidDomain) {
				Errors::add("Le lien fourni n'est pas un lien Google Wallet : $v", ErrorLevel::ERROR);
				return false;
			}

			$path = parse_url($v, PHP_URL_PATH);
			if (!preg_match('#^/gp/v/save/.+#', $path)) {
				Errors::add("Le lien Google Wallet ne contient pas de token valide : $v", ErrorLevel::ERROR);
				return false;
			}

			$this->wallet = $v;
			return true;
		}
		public function setSiren($v): bool {
			if ($v === null) {
				$this->siren = null;
				return true;
			}
			if (!is_string($v) && !is_numeric($v)) {
				Errors::add("SIREN invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}

			$clean = preg_replace('/\D+/', '', (string)$v);

			if (strlen($clean) !== 9) {
				Errors::add("SIREN doit contenir exactement 9 chiffres : $clean", ErrorLevel::ERROR);
				return false;
			}

			if (!$this->isValidLuhn($clean)) {
				Errors::add("SIREN invalide (échec Luhn) : $clean", ErrorLevel::ERROR);
				return false;
			}

			$this->siren = $clean;
			return true;
		}
		public function setRoles(int $roles): bool {
			if ($roles < 0) {
				Errors::add("Roles invalide : $roles", ErrorLevel::ERROR);
				return false;
			}

			$rolesValides = 0;
			foreach (Role::cases() as $role) {
				if (($roles & $role->value) === $role->value) {
					$rolesValides |= $role->value;
				}
			}

			$this->roles = $rolesValides;
			return true;
		}
		public function setRole($value): bool {
			if (is_int($value)) {
				return $this->setRoles($value);
			}

			if ($value instanceof Role) {
				return $this->addRole($value);
			}

			if (is_string($value)) {
				$value = strtoupper($value);
				if (defined("Role::$value")) {
					return $this->addRole(Role::from(constant("Role::$value")));
				}
				Errors::add("Rôle inconnu : $value", ErrorLevel::WARNING);
				return false;
			}

			if (is_array($value)) {
				foreach ($value as $item) {
					if (!$this->setRole($item)) {
						return false;
					}
				}
				return true;
			}

			Errors::add("Format de rôle invalide : " . print_r($value, true), ErrorLevel::ERROR);
			return false;
		}
		public function addRole(Role $role): bool {
			$this->roles = Role::addRole($this->roles, $role);
			return true;
		}
		public function removeRole(Role $role): bool {
			$this->roles = Role::removeRole($this->roles, $role);
			return true;
		}
		public function hasRole(Role $role): bool {
			return Role::hasRole($this->roles, $role);
		}

		private function isValidLuhn(string $number): bool {
			$sum = 0;
			$len = strlen($number);

			for ($i = 0; $i < $len; $i++) {
				$digit = (int)$number[$len - 1 - $i];

				if ($i % 2 === 1) {
					$digit *= 2;
					if ($digit > 9) {
						$digit -= 9;
					}
				}

				$sum += $digit;
			}

			return ($sum % 10) === 0;
		}
	}

	class User {

		public function __construct(private PDO $Database){}

		public function save(UserCard $cible, UserCard $modificateur): int {
			if ($modificateur->getUid() === null) {
				Errors::add("Authentification requise", ErrorLevel::ERROR);
				return 0;
			}
			$isAdmin = (
				$modificateur->hasRole(Role::ADMINISTRATEUR) ||
				$modificateur->hasRole(Role::TECHNICIEN) ||
				$modificateur->hasRole(Role::COMMERCIAL)
			);
			$isCreation = ($cible->getUid() === null);
			if ($isCreation && !$isAdmin) {
				Errors::add("Droits insuffisants pour créer un utilisateur", ErrorLevel::ERROR);
				return 0;
			}
			if (!$isCreation && $modificateur->getUid() !== $cible->getUid() && !$isAdmin) {
				Errors::add("Droits insuffisants pour modifier cet utilisateur", ErrorLevel::ERROR);
				return 0;
			}
			if ($cible->getTelephone() === null) {
				Errors::add("Impossible de créer ou modifier un utilisateur sans numéro de téléphone", ErrorLevel::ERROR);
				return 0;
			}
			try {
				if ($isCreation) {
					$stmt = $this->Database->prepare("INSERT INTO user_user (nom, prenom, adresse, complement, codePostal, ville, email, telephone, siren, wallet, roles) VALUES (:nom, :prenom, :adresse, :complement, :codePostal, :ville, :email, :telephone, :siren, :wallet, :roles)");
					$stmt->execute([':nom' => $cible->getNom(), ':prenom' => $cible->getPrenom(), ':adresse' => $cible->getAdresse(), ':complement' => $cible->getComplement(), ':codePostal' => $cible->getCodePostal(), ':ville' => $cible->getVille(), ':email' => $cible->getEmail(), ':telephone' => $cible->getTelephone(), ':siren' => $cible->getSiren(),':wallet' => $cible->getWallet(),':roles' => $cible->getRoles()]);
					$uid = (int)$this->Database->lastInsertId();
					$cible->setUid($uid);
					return $uid;
				}
				$stmt = $this->Database->prepare(" UPDATE user_user SET nom = :nom, prenom = :prenom, adresse = :adresse, complement = :complement, codePostal = :codePostal, ville = :ville, email = :email, telephone = :telephone, wallet = :wallet, siren = :siren, roles = :roles WHERE uid = :uid ");
				$stmt->execute([ ':nom' => $cible->getNom(), ':prenom' => $cible->getPrenom(), ':adresse' => $cible->getAdresse(), ':complement' => $cible->getComplement(), ':codePostal' => $cible->getCodePostal(), ':ville' => $cible->getVille(), ':email' => $cible->getEmail(), ':telephone' => $cible->getTelephone(), ':wallet' => $cible->getWallet(), ':siren' => $cible->getSiren(), ':roles' => $cible->getRoles(),':uid' => $cible->getUid() ]);
				return $cible->getUid();
			}
			catch (PDOException $e) {
				Errors::add("Erreur SQL : " . $e->getMessage(), ErrorLevel::ERROR);
				return 0;
			}
		}

		public function get(int $uid, string $telephone = null): ?UserCard {
			if ($uid === null && ($telephone === null || $telephone === "")) {
				Errors::add("Aucun critère fourni pour récupérer un utilisateur", ErrorLevel::ERROR);
				return null;
			}
			try {
				if ($uid !== null) {
					$stmt = $this->Database->prepare("SELECT * FROM user_user WHERE uid = :uid LIMIT 1");
					$stmt->execute([':uid' => $uid]);
				} else {
					$normalizedTelephone = $this->normalizeTelephone($telephone);
					if ($normalizedTelephone === null) {
						Errors::add("Téléphone recherché invalide", ErrorLevel::ERROR);
						return null;
					}
					$stmt = $this->Database->prepare("SELECT * FROM user_user WHERE telephone = :telephone LIMIT 1");
					$stmt->execute([':telephone' => $normalizedTelephone]);
				}
				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				if (!$row) {
					Errors::add("Aucun utilisateur trouvé", ErrorLevel::ERROR);
					return null;
				}
				$user = new UserCard($row);
				return $user;
			}
			catch (PDOException $e) {
				Errors::add("Erreur SQL : " . $e->getMessage(), ErrorLevel::ERROR);
				return null;
			}
		}

		public function del(UserCard $cible, UserCard $modificateur): bool {
			if ($modificateur->getUid() === null) {
				Errors::add("Authentification requise", ErrorLevel::ERROR);
				return false;
			}
			$isAdmin = (
				$modificateur->hasRole(Role::ADMINISTRATEUR) ||
				$modificateur->hasRole(Role::TECHNICIEN) ||
				$modificateur->hasRole(Role::COMMERCIAL)
			);
			if (!$isAdmin) {
				Errors::add("Droits insuffisants pour supprimer un utilisateur", ErrorLevel::ERROR);
				return false;
			}
			if ($cible->getUid() === null) {
				Errors::add("Impossible de supprimer un utilisateur sans UID", ErrorLevel::ERROR);
				return false;
			}
			try {
				$stmt = $this->Database->prepare("DELETE FROM user_user WHERE uid = :uid");
				$stmt->execute([':uid' => $cible->getUid()]);
				$stmt = $this->Database->prepare("DELETE FROM user_connexion WHERE uid = :uid");
				$stmt->execute([':uid' => $cible->getUid()]);
				return true;
			}
			catch (PDOException $e) {
				Errors::add("Erreur SQL : " . $e->getMessage(), ErrorLevel::ERROR);
				return false;
			}
		}

		public function checkDatabase(): bool {
			$table = "user_user";
			$stmt = $this->Database->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table");
			$stmt->execute(['table' => $table]);
			if ($stmt->rowCount() === 0) {
				Errors::add("La table '$table' est absente", ErrorLevel::WARNING);
				return $this->createDatabase();
			}
			$expectedColumns = [ 'uid', 'nom', 'prenom', 'telephone', 'email', 'adresse', 'complement', 'codePostal', 'ville', 'wallet', 'siren', 'roles'];
			$columns = $this->Database->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
			foreach ($expectedColumns as $col) {
				if (!in_array($col, $columns)) {
					Errors::add("Colonne manquante : $col", ErrorLevel::WARNING);
					return $this->createDatabase();
				}
			}
			$stmt = $this->Database->query("SHOW COLUMNS FROM $table LIKE 'wallet'");
			$walletInfo = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$walletInfo) {
				Errors::add("Colonne wallet introuvable", ErrorLevel::WARNING);
				return $this->createDatabase();
			}
			$type = strtolower($walletInfo['Type']);
			if (!str_contains($type, 'longtext')) {
				Errors::add("La colonne wallet doit être LONGTEXT, trouvé : $type", ErrorLevel::WARNING);
				return $this->createDatabase();
			}
			return true;
		}

		private function normalizeTelephone(string $telephone): ?string {
			$clean = preg_replace('/\D+/', '', $telephone);
			if (str_starts_with($clean, '0033')) {
				$clean = '0' . substr($clean, 4);
			} elseif (str_starts_with($clean, '33')) {
				$clean = '0' . substr($clean, 2);
			}
			if (!preg_match('/^0[1-9]\d{8}$/', $clean)) {
				return null;
			}
			return $clean;
		}

		private function createDatabase(bool $forceDrop = true): bool {
			$table = "user_user";
			if ($forceDrop) {
				$this->Database->exec("DROP TABLE IF EXISTS $table");
			}
			$sql = "CREATE TABLE IF NOT EXISTS $table (uid INT PRIMARY KEY AUTO_INCREMENT, nom VARCHAR(100), prenom VARCHAR(100), telephone VARCHAR(20), email VARCHAR(255), adresse VARCHAR(255), complement VARCHAR(255), codePostal VARCHAR(5), ville VARCHAR(100), wallet LONGTEXT, siren VARCHAR(9), roles INT NOT NULL DEFAULT 0 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; ";
			try {
				$this->Database->exec($sql);
				return true;
			}
			catch (Exception $e) {
				Errors::add("Erreur création base : " . $e->getMessage(), ErrorLevel::ERROR);
				return false;
			}
		}
	}

?>
