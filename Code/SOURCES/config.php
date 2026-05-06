<?php
	declare(strict_types=1);
	class Config{
		private static string $privateKeyPath1 = __DIR__ . "/key/private1.key";
		private static string $privateKeyPath2 = __DIR__ . "/key/private2.key";
		private static string $publicKeyPath1  = __DIR__ . "/key/public1.key";
		private static string $publicKeyPath2  = __DIR__ . "/key/public2.key"
		
		private static array $bddHost     = ['localhost'];
		private static array $bddUsername = ['root'];
		private static array $bddPassword = [''];
		private static array $bddBase     = ['test'
	
		private static int $bddType=0;
		
		private static string $passwordHash;
		
		public static function init(): void {
			self::$passwordHash = getenv('CONFIG_HASH') ?: '';
		}
		
		public static function getKeyPath(int $type, string $code): string {
			if (!password_verify($code, self::$passwordHash)) {
				Error::add("Code invalide", ErrorLevel::WARNING);
				return '';
			}
			return match ($type) {
				1  => self::$publicKeyPath1,
				2  => self::$publicKeyPath2,
				60 => self::$privateKeyPath2,
				70 => self::$privateKeyPath1,
				default => (Error::add("Type invalide", ErrorLevel::WARNING) ?? ''),
			};
		}

		public static function getBddInfo(int $type, ?string $code): string {
			if ($type > 65 && ($code === null || !password_verify($code, self::$passwordHash))) {
				Error::add("Code invalide", ErrorLevel::WARNING);
				return '';
			}

			return match ($type) {
				1 => self::validateIndex(self::$bddHost)
					? self::$bddHost[self::$bddType]
					: (Error::add("Donnée manquante", ErrorLevel::WARNING) ?? ''),

				2 => self::validateIndex(self::$bddUsername)
					? self::$bddUsername[self::$bddType]
					: (Error::add("Donnée manquante", ErrorLevel::WARNING) ?? ''),

				3 => self::validateIndex(self::$bddBase)
					? self::$bddBase[self::$bddType]
					: (Error::add("Donnée manquante", ErrorLevel::WARNING) ?? ''),

				70 => self::validateIndex(self::$bddPassword)
					? self::$bddPassword[self::$bddType]
					: (Error::add("Donnée manquante", ErrorLevel::WARNING) ?? ''),

				default => (Error::add("Type invalide", ErrorLevel::WARNING) ?? ''),
			};
		}
		
		private static function validateIndex(array $array): bool {
			return array_key_exists(self::$bddType, $array)
				&& $array[self::$bddType] !== null
				&& $array[self::$bddType] !== '';
		}
	}
  Config::init();
?>
