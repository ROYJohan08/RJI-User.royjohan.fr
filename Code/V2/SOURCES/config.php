<?php
	class Config {

		private ?string $oldConfigFilePath = null;
		private ?string $configFilePath    = null;
		private ?string $version           = null;
		private ?string $author            = null;
		private ?array  $data              = null;

		public function __construct($configFilePath = null){
			if ($configFilePath === null) {
				$configFiles = $this->findConfFiles();
				foreach ($configFiles as $file) {
					echo "Config : ".$file;
					$data = $this->parseConfFile($file);
					if ($data !== [] && isset($data['VERSION'], $data['AUTHOR'])){
						$version = $data['VERSION'];
						if ($this->version === null || (float)$version > (float)$this->version){
							$this->version = $version;
							$this->author  = $data['AUTHOR'];
							$this->data    = $data;
							if ($this->configFilePath!==null){
								$this->oldConfigFilePath = $this->configFilePath;
							}
							$this->configFilePath = $file;
							echo " : Valide<br/>";
						}
						else{
							if ($this->oldConfigFilePath!==null){
								$this->oldConfigFilePath = $this->configFilePath;
							}
							echo " : Plus ancienne<br/>";}
					}
					else{echo " : En erreur<br/>";}
				}
			}
			else{
				$data = $this->parseConfFile($configFilePath);
				if ($data !== [] && isset($data['VERSION'], $data['AUTHOR'])){
					$version = $data['VERSION'];
					if ($this->version === null || (float)$version > (float)$this->version){
						$this->version = $version;
						$this->author  = $data['AUTHOR'];
						$this->data    = $data;
						$this->configFilePath = $configFilePath;
					}
				}
			}
		}
		
		public function getVersion(): string {
			return $this->version ?? "";
		}
		public function getAuthor(): string {
			return $this->author ?? "";
		}
		public function getData(string $name): string {
			if ($this->data === null) {
				return "";
			}
			$key = strtoupper($name);
			return $this->data[$key] ?? "";
		}
		
		public function setData(string $key, string $value, string $version, string $author): bool {
			if ($this->configFilePath === null || $this->data === null) {
				return false;
			}
			if ($this->oldConfigFilePath === null) {
				$path = $this->configFilePath;
				$this->oldConfigFilePath = preg_replace(
					'/\.conf$/i',
					'.old.conf',
					$path
				);
			}
			$content = "##RJIConf##\n";
			foreach ($this->data as $k => $v) {
				$content .= $k . " = " . $v . "\n";
			}
			if (file_put_contents($this->oldConfigFilePath, $content) === false) {
				return false;
			}
			$key = strtoupper($key);
			$this->data[$key] = $value;
			$this->data["VERSION"] = $version;
			$this->data["AUTHOR"]  = $author;
			$content = "##RJIConf##\n";
			foreach ($this->data as $k => $v) {
				$content .= $k . " = " . $v . "\n";
			}
			return file_put_contents($this->configFilePath, $content) !== false;
		}
		
		public function backup(): bool{
            if ($this->configFilePath === null){
                return false;
            }
            if ($this->oldConfigFilePath === null || !is_readable($this->oldConfigFilePath)){
                return false;
            }
            $oldContent = file_get_contents($this->oldConfigFilePath);
            if ($oldContent === false){
                return false;
            }
            $backupDate = date("Y-m-d H:i:s");
            $oldContent .= "\nBACKUP_DATE = ".$backupDate."\n";
            $currentVersion = (float)($this->version ?? 0);
            $newVersion = number_format($currentVersion + 0.1, 1, '.', '');
            $oldContent = preg_replace(
                '/^VERSION\s*=\s*.+$/mi',
                'VERSION = '.$newVersion,
                $oldContent
            );
            if (file_put_contents($this->configFilePath, $oldContent) === false){
                return false;
            }
            $data = $this->parseConfFile($this->configFilePath);
            if ($data === []){
                return false;
            }
            $this->data    = $data;
            $this->version = $data["VERSION"] ?? null;
            $this->author  = $data["AUTHOR"] ?? null;
            return true;
        }



		private function findConfFiles(string $startDir = null): array{
			if ($startDir === null){
				$startDir = __DIR__;
			}
			$confFiles = [];
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($startDir, FilesystemIterator::SKIP_DOTS)
			);
			foreach ($iterator as $file){
				if ($file->isFile() && strtolower($file->getExtension()) === 'conf'){
					$confFiles[] = $file->getPathname();
				}
			}
			return $confFiles;
		}

		private function parseConfFile(string $filePath): array{
			if (!is_readable($filePath)){
				return [];
			}
			$lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			if (!$lines){
				return [];
			}
			if (trim($lines[0]) !== "##RJIConf##"){
				return [];
			}
			$result = [];
			$foundVersion = false;
			$foundAuthor  = false;
			foreach ($lines as $line) {
				if (preg_match('/^\s*[#;]/', $line)){
					continue;
				}
				if (preg_match('/^\s*([^:=\s]+)\s*[:=]\s*(.+)\s*$/', $line, $m)){
					$label = strtoupper(trim($m[1]));
					$value = trim($m[2]);
					$result[$label] = $value;
					if (strcasecmp($label, "VERSION") === 0){
						$foundVersion = true;
					}
					if (strcasecmp($label, "AUTHOR") === 0){
						$foundAuthor = true;
					}
				}
			}
			if (!$foundVersion || !$foundAuthor){
				return [];
			}
			return $result;
		}
	}
?>
