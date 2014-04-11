<?php
class WordPressValidatePostImgSrc {

	const DB_SERVER = 'localhost';
	const DB_USER = 'username';
	const DB_PASSWORD = 'password';
	const DB_DATABASE = 'database';

	const PUBLIC_SITE_UPLOADS_URL = 'http://www.siteurl.com/wp-content/uploads/';
	const PATH_TO_UPLOADS = '/docroot/path/to/wp-content/uploads/';
	const LOG_FILE_PATH = './imageerror.log';

	const POST_SOURCE_ROW_FETCH = 50;
	const VALID_IMAGE_EXT_LIST_REGEXP = 'gif|jpg|jpeg|png';


	public function __construct() {

		// connect to database
		$mySQLi = new mysqli(
			self::DB_SERVER,
			self::DB_USER,
			self::DB_PASSWORD,
			self::DB_DATABASE
		);

		// open log file to save results
		$fhLogFile = fopen(self::LOG_FILE_PATH,'w');

		// work over source data
		foreach ($this->postSourceIterator($mySQLi) as $postItem) {
			// extract image URLs contained in the blog post - if none found no work to do
			$imageURLList = $this->extractImageURLList($postItem['post']);
			if (!$imageURLList) continue;

			// now validate each image URL exists in file system - log error if any images not found
			$this->validateImageURLListExists($fhLogFile,$imageURLList,$postItem['GUID']);
		}

		// close log file and database connection
		fclose($fhLogFile);
		$mySQLi->close();

		echo("\nDone!\n\n");
	}

	private function postSourceIterator(mysqli $mySQLi) {

		$lastSeenPostID = 0;
		$rowCount = 0;
		$postType = 'post';

		while ($lastSeenPostID !== false) {
			// prepare statement and execute
			$statement = $mySQLi->stmt_init();
			$statement->prepare(
				'SELECT ID,post_content,guid ' .
				'FROM wp_posts ' .
				'WHERE (ID > ?) AND (post_type = ?) ' .
				'ORDER BY ID LIMIT ' . self::POST_SOURCE_ROW_FETCH
			);

			$statement->bind_param('is',$lastSeenPostID,$postType);
			$statement->execute();

			// fetch result set
			$queryResult = $statement->get_result();
			$lastSeenPostID = false; // if zero result rows will be kept false and iterator ends

			// yield results
			foreach ($queryResult->fetch_all(MYSQLI_ASSOC) as $resultRow) {
				yield [
					'ID' => $resultRow['ID'],
					'post' => trim($resultRow['post_content']),
					'GUID' => trim($resultRow['guid'])
				];

				$lastSeenPostID = $resultRow['ID'];
				$rowCount++;
			}

			// free result and close statement
			$queryResult->free();
			$statement->close();

			echo($rowCount . " posts loaded\n");
		}
	}

	private function extractImageURLList($postText) {

		if (preg_match_all(
			sprintf(
				'/["\'](%s[^"\']+?\.(?:%s))["\']/',
				preg_quote(self::PUBLIC_SITE_UPLOADS_URL,'/'),
				self::VALID_IMAGE_EXT_LIST_REGEXP
			),
			$postText,
			$imageMatchList
		)) {
			// return image URL result list
			return $imageMatchList[1];
		}

		// none found
		return [];
	}

	private function validateImageURLListExists($fh,array $imageURLList,$postGUID) {

		$publicSiteUploadsURLLength = strlen(self::PUBLIC_SITE_UPLOADS_URL);
		$missingImageList = [];

		foreach ($imageURLList as $imageURLItem) {
			// remove public site uploads URL from image path
			$imageFileItem = substr($imageURLItem,$publicSiteUploadsURLLength);

			// check image exists on disk
			if (!is_file(SELF::PATH_TO_UPLOADS . $imageFileItem)) {
				// not found, add to missing list
				$missingImageList[] = $imageURLItem;
			}
		}

		if ($missingImageList) {
			// missing images found - de-dupe list and write to log file
			fwrite($fh,$postGUID . "\n");
			foreach (array_unique($missingImageList) as $missingImageItem) {
				fwrite($fh,"\t" . $missingImageItem . "\n");
			}

			fwrite($fh,"\n");
		}
	}
}


new WordPressValidatePostImgSrc();
