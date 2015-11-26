<?php


/**
 * Sanitizes a filename replacing whitespace with dashes
 *
 * Removes special characters that are illegal in filenames on certain
 * operating systems and special characters requiring special escaping
 * to manipulate at the command line. Replaces spaces and consecutive
 * dashes with a single dash. Trim period, dash and underscore from beginning
 * and end of filename.
 *
 * @param string $filename The filename to be sanitized
 * @return string The sanitized filename
 */
function sanitizeFileName($filename) {
	// Remove special accented characters - ie. sí.
	$clean_name = strtr($filename, array('Š' => 'S','Ž' => 'Z','š' => 's','ž' => 'z','Ÿ' => 'Y','À' => 'A','Á' => 'A','Â' => 'A','Ã' => 'A','Ä' => 'A','Å' => 'A','Ç' => 'C','È' => 'E','É' => 'E','Ê' => 'E','Ë' => 'E','Ì' => 'I','Í' => 'I','Î' => 'I','Ï' => 'I','Ñ' => 'N','Ò' => 'O','Ó' => 'O','Ô' => 'O','Õ' => 'O','Ö' => 'O','Ø' => 'O','Ù' => 'U','Ú' => 'U','Û' => 'U','Ü' => 'U','Ý' => 'Y','à' => 'a','á' => 'a','â' => 'a','ã' => 'a','ä' => 'a','å' => 'a','ç' => 'c','è' => 'e','é' => 'e','ê' => 'e','ë' => 'e','ì' => 'i','í' => 'i','î' => 'i','ï' => 'i','ñ' => 'n','ò' => 'o','ó' => 'o','ô' => 'o','õ' => 'o','ö' => 'o','ø' => 'o','ù' => 'u','ú' => 'u','û' => 'u','ü' => 'u','ý' => 'y','ÿ' => 'y'));
	$clean_name = strtr($clean_name, array('Þ' => 'TH', 'þ' => 'th', 'Ð' => 'DH', 'ð' => 'dh', 'ß' => 'ss', 'Œ' => 'OE', 'œ' => 'oe', 'Æ' => 'AE', 'æ' => 'ae', 'µ' => 'u'));

	// Enforce ASCII-only & no special characters
	$clean_name = preg_replace(array('/\s+/', '/[^a-zA-Z0-9_\.\-]/'), array('.', ''), $clean_name);
	$clean_name = preg_replace(array('/--+/', '/__+/', '/\.\.+/'), array('-', '_', '.'), $clean_name);
	$clean_name = trim($clean_name, '-_.');

	// Some file systems are case-sensitive (e.g. EXT4), some are not (e.g. NTFS). 
	// We simply assume the latter to prevent confusion later.
	// 
	// Note 1: camelCased file names are converted to dotted all-lowercase: `camel.case`
	// Note 2: we assume all file systems can handle filenames with multiple dots 
	//         (after all only vintage file systems cannot, e.g. VMS/RMS, FAT/MSDOS)
	$clean_name = preg_replace('/([a-z])([A-Z]+)/', '$1.$2', $clean_name);
	$clean_name = strtolower($clean_name);

	// And for operating systems which don't like large paths / filenames, clip the filename to the last 64 characters:
	$clean_name = substr($clean_name, -64);
	$clean_name = ltrim($clean_name, '-_.');
    return $clean_name;
}

if (!function_exists('http_response_code')) {
	function http_response_code($code = NULL) {

		if ($code !== NULL) {

			switch ($code) {
				case 100: $text = 'Continue'; break;
				case 101: $text = 'Switching Protocols'; break;
				case 200: $text = 'OK'; break;
				case 201: $text = 'Created'; break;
				case 202: $text = 'Accepted'; break;
				case 203: $text = 'Non-Authoritative Information'; break;
				case 204: $text = 'No Content'; break;
				case 205: $text = 'Reset Content'; break;
				case 206: $text = 'Partial Content'; break;
				case 300: $text = 'Multiple Choices'; break;
				case 301: $text = 'Moved Permanently'; break;
				case 302: $text = 'Moved Temporarily'; break;
				case 303: $text = 'See Other'; break;
				case 304: $text = 'Not Modified'; break;
				case 305: $text = 'Use Proxy'; break;
				case 400: $text = 'Bad Request'; break;
				case 401: $text = 'Unauthorized'; break;
				case 402: $text = 'Payment Required'; break;
				case 403: $text = 'Forbidden'; break;
				case 404: $text = 'Not Found'; break;
				case 405: $text = 'Method Not Allowed'; break;
				case 406: $text = 'Not Acceptable'; break;
				case 407: $text = 'Proxy Authentication Required'; break;
				case 408: $text = 'Request Time-out'; break;
				case 409: $text = 'Conflict'; break;
				case 410: $text = 'Gone'; break;
				case 411: $text = 'Length Required'; break;
				case 412: $text = 'Precondition Failed'; break;
				case 413: $text = 'Request Entity Too Large'; break;
				case 414: $text = 'Request-URI Too Large'; break;
				case 415: $text = 'Unsupported Media Type'; break;
				case 500: $text = 'Internal Server Error'; break;
				case 501: $text = 'Not Implemented'; break;
				case 502: $text = 'Bad Gateway'; break;
				case 503: $text = 'Service Unavailable'; break;
				case 504: $text = 'Gateway Time-out'; break;
				case 505: $text = 'HTTP Version not supported'; break;
				default:
					exit('Unknown http status code "' . htmlentities($code) . '"');
				break;
			}

			$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

			header($protocol . ' ' . $code . ' ' . $text);

			$GLOBALS['http_response_code'] = $code;

		} else {

			$code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);

		}

		return $code;

	}
}


class BigUpload
{

	/**
	 * Max allowed filesize. This is for unsupported browsers and
	 * as an additional security check in case someone bypasses the js filesize check.
	 *
	 * This must match the value specified in main.js
	 */
	private $MAX_SIZE;

	/**
	 * Temporary directory
	 * @var string
	 */
	private $tempDirectory;

	/**
	 * Directory for completed uploads
	 * @var string
	 */
	private $mainDirectory;

	/**
	 * Name of the temporary file. Used as a reference to make sure chunks get written to the right file.
	 * @var string
	 */
	private $tempName;

	/**
	 * Constructor function, sets the temporary directory and main directory
	 */
	public function __construct($TempDir, $DestinationDir, $MAX_SIZE=2147483648) {
		$this->MAX_SIZE = $MAX_SIZE;
		$this->setTempDirectory($TempDir);
		$this->setMainDirectory($DestinationDir);
	}

	/**
	 * Create a random file name for the file to use as it's being uploaded
	 * @param string $value Temporary filename
	 */
	public function setTempName($value = null) {
		if ($value) {
			$this->tempName = sanitizeFileName($value);
		}
		else {
			$this->tempName = mt_rand() . '.tmp';
		}
	}

	/**
	 * Return the name of the temporary file
	 * @return string Temporary filename
	 */
	public function getTempName() {
		return $this->tempName;
	}

	/**
	 * Set the name of the temporary directory
	 * @param string $value Temporary directory
	 */
	public function setTempDirectory($value) {
		$this->tempDirectory = $value;
		return true;
	}

	/**
	 * Return the name of the temporary directory
	 * @return string Temporary directory
	 */
	public function getTempDirectory() {
		return $this->tempDirectory;
	}

	/**
	 * Set the name of the main directory
	 * @param string $value Main directory
	 */
	public function setMainDirectory($value) {
		$this->mainDirectory = $value;
	}

	/**
	 * Return the name of the main directory
	 * @return string Main directory
	 */
	public function getMainDirectory() {
		return $this->mainDirectory;
	}

	/**
	 * Function to upload the individual file chunks
	 * @return string JSON object with result of upload
	 */
	public function uploadFile() {
		// Make sure the total file we're writing to hasn't surpassed the file size limit
		$tmpPath = $this->getTempDirectory() . $this->getTempName();
		if (file_exists($tmpPath)) {
			$fsize = filesize($tmpPath);
			if ($fsize === false) {
				return array(
					'errorStatus' => 553,
					'errorText' => 'File part access error.'
				);
			}
			if ($fsize > $this->MAX_SIZE) {
				$this->abortUpload();
				return array(
					'errorStatus' => 413,
					'errorText' => 'File is too large.'
				);
			}
		}

		// Open the raw POST data from php://input
		$fileData = file_get_contents('php://input');
		if ($fileData === false) {
			return array(
				'errorStatus' => 552,
				'errorText' => 'File part upload error.'
			);
		}

		// Write the actual chunk to the larger file
		$handle = @fopen($tmpPath, 'a');
		if ($handle === false) {
			return array(
				'errorStatus' => 553,
				'errorText' => 'File part access error.'
			);
		}

		$rv = fwrite($handle, $fileData);
		fclose($handle);
		if ($rv === false) {
			return array(
				'errorStatus' => 554,
				'errorText' => 'File part write error.'
			);
		}

		return array(
			'key' => $this->getTempName(),
			'errorStatus' => 0
		);
	}

	/**
	 * Function for cancelling uploads while they're in-progress; deletes the temp file
	 * @return string JSON object with result of deletion
	 */
	public function abortUpload() {
		if (unlink($this->getTempDirectory() . $this->getTempName())) {
			return array(
				'errorStatus' => 0
			);
		}
		else {
			return array(
				'errorStatus' => 405,
				'errorText' => 'Unable to delete temporary file.'
			);
		}
	}

	/**
	 * Function to rename and move the finished file
	 * @param  string $final_name Name to rename the finished upload to
	 * @return string JSON object with result of rename
	 */
	public function finishUpload($finalName) {
		$dstName = sanitizeFileName($finalName);
		$dstPath = $this->getMainDirectory() . $dstName;
		if (rename($this->getTempDirectory() . $this->getTempName(), $dstPath.$dstName)) {
			return array(
				'errorStatus' => 0,
				'fileName' => $dstName
			);
		}
		else {
			return array(
				'errorStatus' => 405,
				'errorText' => 'Unable to move file to "' . $dstPath . '" after uploading.'
			);
		}
	}

	/**
	 * Basic php file upload function, used for unsupported browsers. 
	 * The output on success/failure is very basic, and it would be best to have these errors return the user to index.html
	 * with the errors printed on the form, but that is beyond the scope of this project as it is very application specific.
	 * @return string Success or failure of upload
	 */
	public function postUnsupported($files) {
		if (empty($files)) {
			$files = $_FILES['bigUploadFile'];
		}
		if (empty($files)) {
			return array(
				'errorStatus' => 550,
				'errorText' => 'No BigUpload file uploads were specified.'
			);
		}
		$name = sanitizeFileName($files['name']);
		$size = $files['size'];
		$tempName = $files['tmp_name'];

		$fsize = filesize($tempName);
		if ($fsize === false) {
			return array(
				'errorStatus' => 553,
				'errorText' => 'File part access error.'
			);
		}
		if ($fsize > $this->MAX_SIZE) {
			return array(
				'errorStatus' => 413,
				'errorText' => 'File is too large.'
			);
		}

		$dstPath = $this->getMainDirectory() . $name;
		if (move_uploaded_file($tempName, $dstPath)) {
			return array(
				'errorStatus' => 0,
				'fileName' => $name,
				'errorText' => 'File uploaded.'
			);
		}
		else {
			return array(
				'errorStatus' => 405,
				'errorText' => 'There was an error uploading the file to "' . $dstPath . '".'
			);
		}
	}
	public function CheckIncomingFile() {
		if(isset($_GET['actionold'])){
			if(isset($_GET['action'])){
				if(isset($_POST['key'])){
					return true;
				}elseif(isset($_GET['key'])){
					return true;
				}elseif($_GET['action'] == 'finish'){
					return true;
				}
			}elseif($_GET['actionold'] == 'post-unsupported'){
				$_GET['action'] = $_GET['actionold'];
				return true;
			}
		}
		if(isset($_POST['action'])){
			return true;
		}
		return false;
	}
	public function HandleIncomingFile() {
		$tempName = null;
		if (isset($_GET['key'])) {
			$tempName = $_GET['key'];
		}
		if (isset($_POST['key'])) {
			$tempName = $_POST['key'];
		}

		// extract the required action from the request parameters
		$action = 'help';
		if (isset($_GET['action'])) {
			$action = $_GET['action'];
		}
		if (isset($_POST['action'])) {
			$action = $_POST['action'];
		}

		// and get the desired filename from the user 
		// 
		// Note: only really applicable for action=='finish' but for simplicity's sake 
		//       we always grab it here and let handleRequest() do the rest.
		$realFileName = null;
		if (isset($_GET['name'])) {
			$realFileName = $_GET['name'];
		}
		if (isset($_POST['name'])) {
			$realFileName = $_POST['name'];
		}

		// Vanilla DropZone hack:
		$files = null;
		if (!empty($_FILES['file']) && $action === 'help') {
			$files = $_FILES['file'];
			$action = 'vanilla';
		}
		

		$response = $this->handleRequest($action, $tempName, $realFileName, $files);

		$httpResponseCode = intval($response['errorStatus']);
		
		if($httpResponseCode != 0){
			http_response_code($httpResponseCode);
		}

		print json_encode($response);
		exit();

	}
	private function handleRequest($action, $tempName, $finalFileName, $files) {
		// Instantiate the class

		header('Content-Type: application/json');
		$this->setTempName($tempName);

		switch($action) {
		case 'upload':
			return $this->uploadFile();

		case 'abort':
			return $this->abortUpload();

		case 'finish':
			return $this->finishUpload($finalFileName);

		case 'post-unsupported':
		case 'vanilla':
			return $this->postUnsupported($files);

		case 'help':
			return array(
				'errorStatus' => 552,
				'errorText' => "You've reached the BigUpload gateway. Machines will know what to do."
			);

		default:
			return array(
				'errorStatus' => 550,
				'errorText' => 'Unknown action. Internal failure.'
			);
		}
	}
}

