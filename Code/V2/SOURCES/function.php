<?php

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
		private  int    $roles      = 0;

		public function __construct(array $data = []) {
			if (!empty($data)) {
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
						Errors::add("Hydrate: impossible d’hydrater '$key' avec la valeur '" . print_r($value, true) . "'",ErrorLevel::WARNING);
					}
				} else {
					Errors::add("Hydrate: propriété inconnue '$key' ignorée",ErrorLevel::INFO);
				}
			}
		}

		public function getUid(): ?int { return $this->uid; }
		public function getNom(): ?string { return $this->nom; }
		public function getPrenom(): ?string { return $this->prenom; }
		public function getTelephone(): ?string { return $this->telephone; }
		public function getEmail(): ?string { return $this->email; }
		public function getAdresse(): ?string { return $this->adresse; }
		public function getComplement(): ?string { return $this->complement; }
		public function getCodePostal(): ?string { return $this->codePostal; }
		public function getVille(): ?string { return $this->ville; }
		public function getWallet(): ?string { return $this->wallet; }
		public function getRoles(): int { return $this->roles; }

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
	}

	class User {

		public function __construct(private PDO $Database){}

		private function checkDatabase(): void {
			try {
				$stmt = $this->Database->query("SHOW TABLES LIKE 'user_user'");
				$tableExists = $stmt->rowCount() > 0;
				if (!$tableExists) {
					Errors::add("Table user_user inexistante, création…", ErrorLevel::WARNING);
					$this->createDatabase();
					return;
				}
				$stmt = $this->Database->query("DESCRIBE user_user");
				$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
				$required = ["uid","nom","prenom","adresse","complement","telephone","email","codePostal","ville","roles","wallet"];
				foreach ($required as $col) {
					if (!in_array($col, $columns)) {
						Errors::add("Colonne manquante dans user_user : $col", ErrorLevel::ERROR);
						$this->createDatabase();
						return;
					}
				}
				$stmt = $this->Database->query("SHOW FIELDS FROM user_user WHERE Field = 'wallet'");
				$walletInfo = $stmt->fetch(PDO::FETCH_ASSOC);
				if (!str_contains(strtolower($walletInfo['Type']), "longtext")) {
					Errors::add("Colonne wallet incorrecte (doit être LONGTEXT)", ErrorLevel::ERROR);
					$this->createDatabase();
					return;
				}
				Errors::add("Base user_user vérifiée et complète", ErrorLevel::LOG);
			} catch (Exception $e) {
				Errors::add("Erreur checkDatabase : " . $e->getMessage(), ErrorLevel::ERROR);
			}
		}
		
		private function createDatabase(): void {
			try {
				$sql = "CREATE TABLE IF NOT EXISTS user_user ( uid INT AUTO_INCREMENT PRIMARY KEY, nom VARCHAR(50) NOT NULL, prenom VARCHAR(50) NOT NULL, adresse VARCHAR(255) NOT NULL, complement VARCHAR(255) NULL, telephone VARCHAR(20) NOT NULL, email VARCHAR(255) NOT NULL UNIQUE, codePostal VARCHAR(10) NOT NULL, ville VARCHAR(100) NOT NULL, roles INT NOT NULL DEFAULT 0, wallet LONGTEXT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
				$this->Database->exec($sql);
				Errors::add("Table user_user créée avec succès", ErrorLevel::LOG);
			} catch (Exception $e) {
				Errors::add("Erreur createDatabase : " . $e->getMessage(), ErrorLevel::ERROR);
			}
		}
	}

?>
