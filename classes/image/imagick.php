<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.1
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

class Image_Imagick extends \Image_Driver
{
	protected $accepted_extensions = array('png', 'gif', 'jpg', 'jpeg');
	protected $imagick = null;

	public function load($filename, $return_data = false, $force_extension = false)
	{
		extract(parent::load($filename, $return_data, $force_extension));

		if ($this->imagick == null)
		{
			$this->imagick = new \Imagick();
		}

		$this->imagick->readImage($filename);

		// deal with exif autorotation
		$orientation = $this->imagick->getImageOrientation();
		switch($orientation)
		{
			case \Imagick::ORIENTATION_BOTTOMRIGHT:
				$this->imagick->rotateimage("#000", 180); // rotate 180 degrees
			break;

			case \Imagick::ORIENTATION_RIGHTTOP:
				$this->imagick->rotateimage("#000", 90); // rotate 90 degrees CW
			break;

			case \Imagick::ORIENTATION_LEFTBOTTOM:
				$this->imagick->rotateimage("#000", -90); // rotate 90 degrees CCW
			break;
		}

		return $this;
	}

	protected function _crop($x1, $y1, $x2, $y2)
	{
		extract(parent::_crop($x1, $y1, $x2, $y2));

		$width = $x2 - $x1;
		$height = $y2 - $y1;

		$this->debug("Cropping image ".$width."x".$height."+$x1+$y1 based on coords ($x1, $y1), ($x2, $y2)");

		$this->imagick->cropImage($width, $height, $x1, $y1);
		$this->imagick->setImagePage(0, 0, 0, 0);
	}

	protected function _resize($width, $height = null, $keepar = true, $pad = true)
	{
		extract(parent::_resize($width, $height, $keepar, $pad));

		$this->imagick->scaleImage($width, $height, $keepar);

		if ($pad)
		{
			$tmpimage = new \Imagick();
			$tmpimage->newImage($cwidth, $cheight, $this->create_color('#000', 0), 'png');
			$tmpimage->compositeImage($this->imagick, \Imagick::COMPOSITE_DEFAULT, ($cwidth-$width) / 2, ($cheight-$height) / 2);
			$this->imagick = $tmpimage;
		}
	}

	protected function _rotate($degrees)
	{
		extract(parent::_rotate($degrees));

		$this->imagick->rotateImage($this->create_color('#000', 0), $degrees);
	}

	protected function _watermark($filename, $position, $padding = array(5,5))
	{
		extract(parent::_watermark($filename, $position, $padding));
		$wmimage = new \Imagick();
		$wmimage->readImage($filename);
		$wmimage->evaluateImage(\Imagick::EVALUATE_MULTIPLY, $this->config['watermark_alpha'] / 100, \Imagick::CHANNEL_ALPHA);
		$this->imagick->compositeImage($wmimage, \Imagick::COMPOSITE_DEFAULT, $x, $y);
	}

	protected function _flip($direction)
	{
		switch ($direction)
		{
			case 'vertical':
			$this->imagick->flipImage();
			break;

			case 'horizontal':
			$this->imagick->flopImage();
			break;

			case 'both':
			$this->imagick->flipImage();
			$this->imagick->flopImage();
			break;

			default: return false;
		}
	}

	protected function _border($size, $color = null)
	{
		extract(parent::_border($size, $color));

		$this->imagick->borderImage($this->create_color($color, 100), $size, $size);
	}

	protected function _mask($maskimage)
	{
		extract(parent::_mask($maskimage));
		$wmimage = new \Imagick();
		$wmimage->readImage($maskimage);
		$wmimage->setImageMatte(false);
		$this->imagick->compositeImage($wmimage, \Imagick::COMPOSITE_COPYOPACITY, 0, 0);
	}

	protected function _rounded($radius, $sides, $antialias = 0)
	{
		extract(parent::_rounded($radius, $sides, null));

		$sizes = $this->sizes();
		$sizes->width_half = $sizes->width / 2;
		$sizes->height_half = $sizes->height / 2;

		$list = array();
		if (!$tl) {
			$list = array('x' => 0, 'y' => 0);
		}
		if (!$tr) {
			$list = array('x' => $sizes->width_half, 'y' => 0);
		}
		if (!$bl) {
			$list = array('x' => 0, 'y' => $sizes->height_half);
		}
		if (!$br) {
			$list = array('x' => $sizes->width_half, 'y' => $sizes->height_half);
		}

		foreach($list as $index => $element) {
			$image = $this->imagick->clone();
			$image->cropImage($sizes->width_half, $sizes->height_half, $element['x'], $element['y']);
			$list[$index]['image'] = $image;
		}

		$this->imagick->roundCorners($radius, $radius);

		foreach($list as $element) {
			$this->imagick->compositeImage($element['image'], \Imagick::COMPOSITE_DEFAULT, $element['x'], $element['y']);
		}
	}

	protected function _grayscale()
	{
		$this->imagick->setImageType(\Imagick::IMGTYPE_GRAYSCALEMATTE);
	}

	public function sizes($filename = null, $usecache = true)
	{
		if ($filename === null)
		{
			return (object) array(
				'width'  => $this->imagick->getImageWidth(),
				'height' => $this->imagick->getImageHeight(),
			);
		}

		$tmpimage = new \Imagick();
		$tmpimage->readImage($filename);
		return (object) array(
			'width'  => $tmpimage->getImageWidth(),
			'height' => $tmpimage->getImageHeight(),
		);
	}

	public function save($filename = null, $permissions = null)
	{
		extract(parent::save($filename, $permissions));

		$this->run_queue();
		$this->add_background();

		$filetype = $this->image_extension;

		if ($filetype == 'jpg' or $filetype == 'jpeg')
		{
			$filetype = 'jpeg';
		}

		if ($this->imagick->getImageFormat() != $filetype)
		{
			$this->imagick->setImageFormat($filetype);
		}

		if($this->imagick->getImageFormat() == 'jpeg' and $this->config['quality'] != 100)
		{
			$this->imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
			$this->imagick->setImageCompressionQuality($this->config['quality']);
			$this->imagick->stripImage();
		}

		file_put_contents($filename, $this->imagick->getImageBlob());

		if ($this->config['persistence'] === false)
		{
			$this->reload();
		}

		return $this;
	}

	public function output($filetype = null)
	{
		extract(parent::output($filetype));

		$this->run_queue();
		$this->add_background();

		if ($filetype == 'jpg' or $filetype == 'jpeg')
		{
			$filetype = 'jpeg';
		}

		if ($this->imagick->getImageFormat() != $filetype)
		{
			$this->imagick->setImageFormat($filetype);
		}

		if($this->imagick->getImageFormat() == 'jpeg' and $this->config['quality'] != 100)
		{
			$this->imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
			$this->imagick->setImageCompressionQuality($this->config['quality']);
			$this->imagick->stripImage();
		}

		if ( ! $this->config['debug'])
		{
			echo $this->imagick->getImageBlob();
		}

		return $this;
	}

	protected function add_background()
	{
		if($this->config['bgcolor'] != null)
		{
			$tmpimage = new \Imagick();
			$sizes = $this->sizes();
			$tmpimage->newImage($sizes->width, $sizes->height, $this->create_color($this->config['bgcolor'], $this->config['bgcolor'] == null ? 0 : 100), 'png');
			$tmpimage->compositeImage($this->imagick, \Imagick::COMPOSITE_DEFAULT, 0, 0);
			$this->imagick = $tmpimage;
		}
	}

	/**
	 * Creates a new color usable by Imagick.
	 *
	 * @param  string   $hex    The hex code of the color
	 * @param  integer  $newalpha  The alpha of the color, 0 (trans) to 100 (opaque)
	 * @return string   rgba representation of the hex and alpha values.
	 */
	protected function create_color($hex, $newalpha = null)
	{
		// Convert hex to rgba
		extract($this->create_hex_color($hex));

		// If a custom alpha was passed, use that
		isset($newalpha) and $alpha = $newalpha;

		return new \ImagickPixel('rgba('.$red.', '.$green.', '.$blue.', '.round($alpha / 100, 2).')');
	}
}
