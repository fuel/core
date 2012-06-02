<?php

return array(
	'error_'.\Upload::UPLOAD_ERR_OK						=> 'Dosya başarı ile karşıya yüklendi',
	'error_'.\Upload::UPLOAD_ERR_INI_SIZE				=> 'Karşıya yüklenen dosya boyutu php.ini dosyasındaki upload_max_filesize yönergesini aşıyor',
	'error_'.\Upload::UPLOAD_ERR_FORM_SIZE				=> 'Karşıya yüklenen dosya boyutu HTML formunda belirtilen MAX_FILE_SIZE yönergesini aşıyor',
	'error_'.\Upload::UPLOAD_ERR_PARTIAL				=> 'Karşıya yüklenen dosyanın sadece bir kısmı karşıya yüklendi',
	'error_'.\Upload::UPLOAD_ERR_NO_FILE				=> 'Hiçbir dosya karşıya yüklenmedi',
	'error_'.\Upload::UPLOAD_ERR_NO_TMP_DIR				=> 'Geçici karşıya yükleme klasörü ayarı eksik',
	'error_'.\Upload::UPLOAD_ERR_CANT_WRITE				=> 'Karşıya yüklenen dosya diske yazılırken hata oluştu',
	'error_'.\Upload::UPLOAD_ERR_EXTENSION				=> 'Karşıya yükleme yüklü bir PHP uzantısı tarafından engellendi',
	'error_'.\Upload::UPLOAD_ERR_MAX_SIZE				=> 'Karşıya yüklenen dosya boyutu tanımlanan en üst boyutu aşıyor',
	'error_'.\Upload::UPLOAD_ERR_EXT_BLACKLISTED		=> 'Bu uzantıdaki dosyaların karşıya yüklenmesine izin verilmiyor',
	'error_'.\Upload::UPLOAD_ERR_EXT_NOT_WHITELISTED	=> 'Bu uzantıdaki dosyaların karşıya yüklenmesine izin verilmiyor',
	'error_'.\Upload::UPLOAD_ERR_TYPE_BLACKLISTED		=> 'Bu türdeki dosyaların karşıya yüklenmesine izin verilmiyor',
	'error_'.\Upload::UPLOAD_ERR_TYPE_NOT_WHITELISTED	=> 'Bu türdeki dosyaların karşıya yüklenmesine izin verilmiyor',
	'error_'.\Upload::UPLOAD_ERR_MIME_BLACKLISTED		=> 'Bu mime türündeki dosyaların karşıya yüklenmesine izin verilmiyor',
	'error_'.\Upload::UPLOAD_ERR_MIME_NOT_WHITELISTED	=> 'Bu mime türündeki dosyaların karşıya yüklenmesine izin verilmiyor',
	'error_'.\Upload::UPLOAD_ERR_MAX_FILENAME_LENGTH	=> 'Karşıya yüklenen dosya ismi tanımlanan en üst uzunluğu aşıyor',
	'error_'.\Upload::UPLOAD_ERR_MOVE_FAILED			=> 'Karşıya yüklenen dosya hedef klasörüne taşınırken hata oluştu',
	'error_'.\Upload::UPLOAD_ERR_DUPLICATE_FILE 		=> 'Karşıya yüklenen dosyanın adında başka bir dosya zaten mevcut',
	'error_'.\Upload::UPLOAD_ERR_MKDIR_FAILED			=> 'Dosya hedef klasörü oluşturulurken hata oluştu',
);
