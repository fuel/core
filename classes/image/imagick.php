<?php

/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * Image manipulation class.
 *
 * @package		Fuel
 * @version		1.0
 * @license		MIT License
 * @copyright	2010 - 2011 Fuel Development Team
 * @link		http://fuelphp.com
 */

namespace Fuel\Core;

class Image_Imagick extends \Image_Driver {

	protected $accepted_extensions = array('png', 'gif', 'jpg', 'jpeg');
	private $imagick = null;

	public function load($filename)
	{
		extract(parent::load($filename));
		
		if ($this->imagick == null)
			$this->imagick = new \Imagick();
		
		$this->imagick->readImage($filename);
		
		return $this;
	}

	protected function _crop($x1, $y1, $x2, $y2)
	{
		extract(parent::_crop($x1, $y1, $x2, $y2));
		
		$this->imagick->cropImage(($x2 - $x1), ($y2 - $y1), $y1, $x1);
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

	protected function _watermark($filename, $position, $padding = 5)
	{
		extract(parent::_watermark($filename, $position, $padding));
		$wmimage = new \Imagick();
		$wmimage->readImage($filename);
		$wmimage->setImageOpacity($this->config['watermark_alpha'] / 100);
		$this->imagick->compositeImage($wmimage, \Imagick::COMPOSITE_DEFAULT, $x, $y);
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
	
	protected function _rounded($radius, $sides)
	{
		extract(parent::_rounded($radius, $sides, null));
		
		$sizes = $this->sizes();
		$sizes->width_half = $sizes->width / 2;
		$sizes->height_half = $sizes->height / 2;
		
		if ( ! $tl)
		{
			$tlimage = $this->imagick->clone();
			$tlimage->cropImage($sizes->width_half, $sizes->height_half, 0, 0);
		}
		
		if ( ! $tr)
		{
			$trimage = $this->imagick->clone();
			$trimage->cropImage($sizes->width_half, $sizes->height_half, $sizes->width_half, 0);
		}
		
		if ( ! $bl)
		{
			$blimage = $this->imagick->clone();
			$blimage->cropImage($sizes->width_half, $sizes->height_half, 0, $sizes->height_half);
		}
		
		if ( ! $br)
		{
			$brimage = $this->imagick->clone();
			$brimage->cropImage($sizes->width_half, $sizes->height_half, $sizes->width_half, $sizes->height_half);
		}
		
		$this->imagick->roundCorners($radius, $radius);
		
		if ( ! $tl)
			$this->imagick->compositeImage($tlimage, \Imagick::COMPOSITE_DEFAULT, 0, 0);
		
		if ( ! $tr)
			$this->imagick->compositeImage($trimage, \Imagick::COMPOSITE_DEFAULT, $sizes->width_half, 0);
		
		if ( ! $bl)
			$this->imagick->compositeImage($blimage, \Imagick::COMPOSITE_DEFAULT, 0, $sizes->height_half);
		
		if ( ! $br)
			$this->imagick->compositeImage($brimage, \Imagick::COMPOSITE_DEFAULT, $sizes->width_half, $sizes->height_half);
	}
	
	protected function _grayscale()
	{
		$this->imagick->setImageType(\Imagick::IMGTYPE_GRAYSCALEMATTE);
	}

	public function sizes($filename = null, $usecache = true)
	{
		if ($filename === null)
			return (object) array(
				'width'  => $this->imagick->getImageWidth(),
				'height' => $this->imagick->getImageHeight()
			);
		else
		{
			$tmpimage = new \Imagick();
			$tmpimage->readImage($filename);
			return (object) array(
				'width'  => $tmpimage->getImageWidth(),
				'height' => $tmpimage->getImageHeight()
			);
		}
	}

	public function save($filename, $permissions = null)
	{
		extract(parent::save($filename, $permissions));
		
		$this->run_queue();
		$this->add_background();
		
		if ($this->imagick->getImageFormat() != $filetype)
			$this->imagick->setImageFormat($filetype);
		
		file_put_contents($filename, $this->imagick->getImageBlob());

		if ($this->config['persistent'] === false)
			$this->reload();
		
		return $this;
	}

	public function output($filetype = null)
	{
		extract(parent::output($filetype));
		
		$this->run_queue();
		$this->add_background();
		
		if ($this->imagick->getImageFormat() != $filetype)
			$this->imagick->setImageFormat($filetype);
		
		if ( ! $this->config['debug'])
			echo $this->imagick->getImageBlob();
		
		return $this;
	}

	protected function add_background()
	{
		$tmpimage = new \Imagick();
		$sizes = $this->sizes();
		$tmpimage->newImage($sizes->width, $sizes->height, $this->create_color($this->config['bgcolor'], $this->config['bgcolor'] == null ? 0 : 100), 'png');
		$tmpimage->compositeImage($this->imagick, \Imagick::COMPOSITE_DEFAULT, 0, 0);
		$this->imagick = $tmpimage;
	}

	/**
	 * Creates a new color usable by ImageMagick.
	 *
	 * @param  string   $hex    The hex code of the color
	 * @param  integer  $alpha  The alpha of the color, 0 (trans) to 100 (opaque)
	 * @return string   rgba representation of the hex and alpha values.
	 */
	protected function create_color($hex, $alpha)
	{
		if ($hex == null)
		{
			$red = 0;
			$green = 0;
			$blue = 0;
		}
		else
		{
			// Check if theres a # in front
			if (substr($hex, 0, 1) == '#')
			{
				$hex = substr($hex, 1);
			}

			// Break apart the hex
			if (strlen($hex) == 6)
			{
				$red   = hexdec(substr($hex, 0, 2));
				$green = hexdec(substr($hex, 2, 2));
				$blue  = hexdec(substr($hex, 4, 2));
			}
			else
			{
				$red   = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
				$green = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
				$blue  = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
			}
		}
		return new \ImagickPixel('rgba('.$red.', '.$green.', '.$blue.', '.round($alpha / 100, 2).')');
	}
}

// End of file imagick.php