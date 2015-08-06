<?php
	
	namespace WP_Piwik\Logger;
	
	class File extends \WP_Piwik\Logger {
	
		private $loggerFile = null;
	
		private function encodeFilename($fileName) {
			$fileName = str_replace (' ', '_', $fileName);
			preg_replace('/[^0-9^a-z^_^.]/', '', $fileName);
			return $fileName;
		}
		
		private function setFilename() {
			$this->loggerFile = WP_PIWIK_PATH.'logs'.DIRECTORY_SEPARATOR.
				date('Ymd').'_'.$this->encodeFilename($this->getName()).'.log';
		}
		
		private function getFilename() {
			return $this->loggerFile;
		}
		
		private function openFile() {
			if (!$this->loggerFile)
				$this->setFilename();
			return fopen($this->getFilename(), 'a');			
		}
		
		private function closeFile($fileHandle) {
			fclose($fileHandle);
		}
		
		private function writeFile($fileHandle, $fileContent) {
			fwrite($fileHandle, $fileContent."\n");
		}
		
		private function formatMicrotime($loggerTime) {
			return sprintf('[%6s sec]',number_format($loggerTime,3));
		}
		
		public function loggerOutput($loggerTime, $loggerMessage) {
			if ($fileHandle = $this->openFile()) {
				$this->writeFile($fileHandle, $this->formatMicrotime($loggerTime).' '.$loggerMessage);
				$this->closeFile($fileHandle);
			}
		}
    }