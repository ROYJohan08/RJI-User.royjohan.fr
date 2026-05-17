<?php
	enum ErrorLevel: int {
		case ALL     = 0;
		case LOG     = 10;
		case INFO    = 11;
		case WARNING = 20;
		case ERROR   = 21;
		case SUCCESS = 30;
	}
	class ErrorItem {
		public function __construct(public readonly string $content,public readonly ErrorLevel $level,public readonly \DateTimeImmutable $date) {}
	}
	class Errors {
		private static array $errors = [];
		
		public static function add(string $content, ErrorLevel $level, bool $save = false): void {
			$content = trim(strip_tags($content));
			if (strlen($content) < 4){
				return;
			}
			if (class_exists(\Transliterator::class)){
				$trans = \Transliterator::create('Any-Latin; Latin-ASCII');
				if ($trans !== null){
					$content = $trans->transliterate($content);
				}
			}
			$content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
			self::$errors[] = new ErrorItem(content: $content,level: $level,date: new \DateTimeImmutable());
			self::sort();
			if ($save){
				self::save($content, $level);
			}
			return;
		}
		
		public static function get(?ErrorLevel $level = null): array {
			self::load();
			if ($level === null || $level === ErrorLevel::ALL){
				return self::$errors;
			}
			$return =  array_values(array_filter(self::$errors,fn (ErrorItem $e) => $e->level === $level));
			return $return;
		}
		
		public static function clear(): void {
			self::$errors = [];
			if (isset($_SESSION['errors'])){
				$_SESSION['errors'] = [];
			}
		}
		
		private static function save(string $content, ErrorLevel $level = ErrorLevel::ERROR): void {
			$content = trim(strip_tags($content));
			if (strlen($content) < 4){
				return;
			}
			if (!isset($_SESSION['errors'])){
				$_SESSION['errors'] = [];
			}
			$_SESSION['errors'][] = ["content" => $content,"level"   => $level->value];
			return;
		}

		private static function load(): void {
			if (!isset($_SESSION['errors']) || empty($_SESSION['errors'])){
				return;
			}
			foreach ($_SESSION['errors'] as $er) {
				Errors::add($er['content'],ErrorLevel::from($er['level']));
			}
			$_SESSION['errors'] = [];
		}
		
		private static function sort(): void {
			usort(self::$errors,fn (ErrorItem $a, ErrorItem $b) =>$a->level->value <=> $b->level->value);
		}
		public static function display(){
			$style = "
				#toast-container {position: fixed;bottom: 2vh;left: 2vw;display: flex;flex-direction: column;gap: 1.2vh;z-index: 9999;width: calc(100vw - 4vw);max-width: 380px;}
				.toast {display: flex;align-items: flex-start;gap: 0.6rem;padding: 0.8rem 1rem;border-radius: 0.6rem;background: #202225;border-left: 4px solid #f04747;color: #ffffff;font-size: clamp(0.75rem, 2vw, 0.9rem);width: 100%;max-width: 100%;box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);opacity: 0;transform: translateX(-20px);animation: toast-in 0.25s forwards;}
				@keyframes toast-in {to {opacity: 1;transform: translateX(0);}}
				@media (max-width: 480px) {#toast-container {left: 50%;bottom: 1.5vh;transform: translateX(-50%);max-width: 90vw;}.toast {border-left-width: 3px;padding: 0.7rem 0.9rem;}}
				@media (min-width: 1200px) {#toast-container {bottom: 30px;left: 30px;max-width: 420px;}.toast {font-size: 0.95rem;padding: 12px 14px;}}
			";
			$script = "
				function showToast(message, type = 'error') {
					const container = document.getElementById('toast-container');
					const toast = document.createElement('div');
					toast.className = 'toast';
					let icon = 'ℹ️';
					let border = '#3498db';
					switch (type) {
						case 'error':   icon = '❌'; border = '#ff3b3b'; break;
						case 'success': icon = '✔️'; border = '#2ecc71'; break;
						case 'warning': icon = '⚠️'; border = '#f1c40f'; break;
						case 'info':    icon = 'ℹ️'; border = '#3498db'; break;
						case 'log':     icon = '📄'; border = '#95a5a6'; break;
					}
					toast.style.borderLeft = `4px solid \${border}`;
					toast.innerHTML = `
						<div class='toast-icon'>\${icon}</div>
						<div>\${message}</div>
						<div class='toast-close' onclick='closeToast(this.parentElement)'>✖</div>
					`;
					container.appendChild(toast);
					setTimeout(() => closeToast(toast), 5000);
				}
				function closeToast(toast) {
					toast.style.animation = 'toast-out 0.25s forwards';
					setTimeout(() => toast.remove(), 250);
				}
			";
			$errorMessage = self::get();
			$display  = "<style>".$style."</style>";
			$display .= "<div id='toast-container'></div>";
			$display .= "<script>".$script."</script>";
			if (!empty($errorMessage)) {
				$display .= "<script>";
				foreach ($errorMessage as $err) {
					$type = match ($err->level) {
						ErrorLevel::ERROR   => "error",
						ErrorLevel::SUCCESS => "success",
						ErrorLevel::WARNING => "warning",
						ErrorLevel::INFO    => "info",
						ErrorLevel::LOG     => "log",
						default             => "info"
					};
					$msg = htmlspecialchars($err->content, ENT_QUOTES, 'UTF-8');
					$display .= "showToast(\"{$msg}\", \"{$type}\");";
				}
				$display .= "</script>";
			}
			return $display;
		}
	}
?>