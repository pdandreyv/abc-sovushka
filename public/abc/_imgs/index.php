<?php

//скрипт для ресайза картинок
//добавлен v1.3.14
//для ресайза картинки нужно просто к сущесввующему адресу картинки прибавить:
// - /_imgs/100x100 - будет resize
// - /_imgs/_100x100 - будет cut
//все созданные картинки будут сохранены по указанному адресу с префиксом в папке /_imgs/

/*
 * v1.4.23 - jpegoptim
 */

define('ROOT_DIR', dirname(__FILE__).'/../');

//require_once(ROOT_DIR.'_config.php');	//динамические настройки
require_once(ROOT_DIR.'_config2.php');	//установка настроек

require_once(ROOT_DIR.'functions/common_func.php');
require_once(ROOT_DIR.'functions/image_func.php');	//функции для работы с картинками

//v1.4.23 - jpegoptim
$config['jpegoptim'] = 0;
$config['jpegoptim_url'] = 'https://5legko.com/jpegoptim.php';

$request_url = explode('?',$_SERVER['REQUEST_URI'],2); //dd($request_url);
//создание массива $u - в нем части полного пути к картинке
$u = explode('/',$request_url[0]);

//тип обрезки
$type = 'resize';
if (substr($u[2], 0, 1)=='_') $type = 'cut';

//размер картинки
$size = $u[2];
$sizes = explode('x',$u[2]);
//ширина
$width = preg_replace('~[^0-9]+~u', '', $sizes[0]);
$width = intval($width);
//высота
$height = @$sizes[1] ? intval($sizes[1]) : 0;
//если не указана ширина то делаем ресайз и ширина максимум в 2 раза будет больше высоты
if ($width==0) {
	$width = $height*2;
	$type = 'resize';
}
//если не указана высота то делаем ресайз и высота максимум в 2 раза будет больше ширины
if ($height==0) {
	$height = $width*2;
	$type = 'resize';
}
$size = $width.'x'.$height;

//дебаг
if ($u[3]=='debug') {
	header("Content-type: image/png");
	$font = 10;
	$img = imagecreate($width, $height);
	$background_color = imagecolorallocate($img, 155, 255, 255);
	$text_color = imagecolorallocate($img, 233, 14, 91);
	$str = $u[2];
	$str.= '/'.$u[4];
	//расположение по вертикали
	$y = $height/2-8;
	if ($y<5) $y=0;
	//расположение по горизонтали
	$len = imagefontwidth($font) * strlen($str);
	if ($width<$len) $x = 0;
	else $x = ($width-$len)/2;
	imagestring($img, $font, $x, $y,  $str, $text_color);
	imagepng($img);
	imagedestroy($img);
	die();
}

//заглушка
$content_type = 'image/svg+xml';
$img = ROOT_DIR . '_imgs/no_img.svg';

//если
$key = $u[4];
if (
	$u[2]=='100x100'//по умолчанию для админки
	OR (isset($config['_imgs'][$key]) AND in_array($u[2],$config['_imgs'][$key]))
) {
	//новый файл
	$file = implode('/', $u);
	$file = trim($file, '/');
	$root_file = ROOT_DIR . $file;

	//старый файл - отрезаем первые три части /_resize/100x100/
	array_splice($u, 0, 3);
	$temp = implode('/', $u);
	$temp = trim($temp, '/');
	// Файлы subscription_levels, ideas, topic_materials лежат в public/files/, а не в public/abc/files/
	$temp_fs = ROOT_DIR . $temp;
	if (preg_match('#^files/(subscription_levels|ideas|topic_materials)/#', $temp)) {
		$temp_fs = ROOT_DIR . '../' . $temp;
	}
	$temp = $temp_fs;
	//echo $type.' '.$temp.' '.$size.' '.$root_file; die();

	$exb = substr($temp,-3);
	if ($exb=='svg') {
		header('Content-type: ' . $content_type);
		//include_once ($img);
		echo file_get_contents($temp);
		die();
	}
	else {
		//для старых версий цмс
		$dir = dirname($root_file);
		//echo $dir; die();
		if (file_exists($temp)) {
			if (is_dir($dir) || mkdir($dir, 0755, true)) {
				//создаем новую картинку
				if (img_process($type, $temp, $size, $root_file)) {
					$content_type = mime_content_type($root_file);
					$img = $root_file;
					header('Content-type: ' . $content_type);
					//include_once ($img);

					//v1.4.23 - jpegoptim
					if ($config['jpegoptim']
						AND in_array($exb,array('jpg','peg'))
						AND $config['local']==0 //на локале не работает так как сторонний скрипт запрашивает картинку по урл
					) {
						//log_add('jpegoptim.txt',$config['http_domain'].'/'.$file);
						//log_add('jpegoptim.txt',intval(filesize($root_file)).'b');
						$base64 = base64_encode($config['http_domain'].'/'.$file);
						$content = file_get_contents($config['jpegoptim_url'].'?file='.$base64);
						if ($content AND $content!='error') {
							//echo ROOT_DIR.$file;
							$fp = fopen($root_file, 'w');
							fwrite($fp,$content);
							fclose($fp);
							//log_add('jpegoptim.txt',intval(filesize($root_file)).'b');
						}
						else {
							log_add('jpegoptim.txt',$root_file);
						}
					}

					echo file_get_contents($img);
					die();
				}
			}
		}
	}
}

header("HTTP/1.0 404 Not Found");
header( 'Content-type: '.$content_type);
//include_once ($img);
echo file_get_contents($img);
