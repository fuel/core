<?php

return array(
	\Upload::UPLOAD_ERR_OK						=> 'The file uploaded with success',
	\Upload::UPLOAD_ERR_INI_SIZE				=> 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
	\Upload::UPLOAD_ERR_FORM_SIZE				=> 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
	\Upload::UPLOAD_ERR_PARTIAL					=> 'The uploaded file was only partially uploaded',
	\Upload::UPLOAD_ERR_NO_FILE					=> 'No file was uploaded',
	\Upload::UPLOAD_ERR_NO_TMP_DIR				=> 'Configured temporary upload folder is missing',
	\Upload::UPLOAD_ERR_CANT_WRITE				=> 'Failed to write uploaded file to disk',
	\Upload::UPLOAD_ERR_EXTENSION				=> 'Upload blocked by an installed PHP extension',
	\Upload::UPLOAD_ERR_MAX_SIZE				=> 'The uploaded file exceeds the defined maximum size',
	\Upload::UPLOAD_ERR_EXT_BLACKLISTED			=> 'Upload of files with this extension is not allowed',
	\Upload::UPLOAD_ERR_EXT_NOT_WHITELISTED		=> 'Upload of files with this extension is not allowed',
	\Upload::UPLOAD_ERR_TYPE_BLACKLISTED		=> 'Upload of files of this file type is not allowed',
	\Upload::UPLOAD_ERR_TYPE_NOT_WHITELISTED	=> 'Upload of files of this file type is not allowed',
	\Upload::UPLOAD_ERR_MIME_BLACKLISTED		=> 'Upload of files of this mime type is not allowed',
	\Upload::UPLOAD_ERR_MIME_NOT_WHITELISTED	=> 'Upload of files of this mime type is not allowed',
	\Upload::UPLOAD_ERR_MAX_FILENAME_LENGTH		=> 'The uploaded file name exceeds the defined maximum length',
	\Upload::UPLOAD_ERR_MOVE_FAILED				=> 'Unable to move the uploaded file to it\'s final destination',
	\Upload::UPLOAD_ERR_DUPLICATE_FILE 			=> 'A file with the name of the uploaded file already exists',
);
