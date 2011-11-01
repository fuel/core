<?php

/**
 * Part of the Fuel framework.
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

class Image_Gd extends \Image_Driver
{

	private $image_data = null;
	protected $accepted_extensions = array('png', 'gif', 'jpg', 'jpeg', 'bmp');
	protected $gdresizefunc = "imagecopyresampled";

	public function load($filename, $return_data = false)
	{
		extract(parent::load($filename, $return_data));
		$return = false;
		$image_extension == 'jpg' and $image_extension = 'jpeg';

		if ( ! $return_data)
		{
			$this->image_data !== null and imagedestroy($this->image_data);
			$this->image_data = null;
		}

		// Check if the function exists
		if (function_exists(($function_name = 'imagecreatefrom'.$image_extension)) or is_callable(($function_name = array($this, 'imagecreatefrom'.$image_extension))))
		{
			// Create a new transparent image.
			$sizes = $this->sizes($image_fullpath);
			$tmpImage = call_user_func($function_name, $image_fullpath);
			$image = $this->create_transparent_image($sizes->width, $sizes->height, $tmpImage);
			if ( ! $return_data)
			{
				$this->image_data = $image;
				$return = true;
			}
			else
			{
				$return = $image;
			}
			$this->debug('', "<strong>Loaded</strong> <code>".$image_fullpath."</code> with size of ".$sizes->width."x".$sizes->height);
		}
		else
		{
			throw new \RuntimeException("Function imagecreatefrom".$image_extension."() does not exist (Missing GD?)");
		}
		return $return_data ? $return : $this;
	}

	protected function _crop($x1, $y1, $x2, $y2)
	{
		extract(parent::_crop($x1, $y1, $x2, $y2));
		$width = $x2 - $x1;
		$height = $y2 - $y1;
		$this->debug("Cropping image ".$width."x".$height."+$x1+$y1 based on coords ($x1, $y1), ($x2, $y2)");
		$image = $this->create_transparent_image($width, $height);

		imagecopy($image, $this->image_data, 0, 0, $x1, $y1, $width, $height);
		$this->image_data = $image;
	}

	protected function _resize($width, $height = null, $keepar = true, $pad = true)
	{
		extract(parent::_resize($width, $height, $keepar, $pad));
		$sizes = $this->sizes();

		$this->debug("Resizing image to $width, $height with" . ($keepar ? '' : 'out') . " keeping AR and with" . ($pad ? '' : 'out') . " padding.");

		// Add the original image.
		$image = $this->create_transparent_image($cwidth, $cheight);
		call_user_func($this->gdresizefunc, $image, $this->image_data, $x, $y, 0, 0, $width, $height, $sizes->width, $sizes->height);
		$this->image_data = $image;
	}

	protected function _rotate($degrees)
	{
		extract(parent::_rotate($degrees));
		$degrees = 360 - $degrees;
		$bgcolor = $this->config['bgcolor'] !== null ? $this->config['bgcolor'] : '#000';
		$color = $this->create_color($this->image_data, $bgcolor, 100);
		$this->image_data = imagerotate($this->image_data, $degrees, $color, false);
	}

	protected function _watermark($filename, $position, $padding = 5)
	{
		$values = parent::_watermark($filename, $position, $padding);
		if ($values == false)
		{
			throw new \InvalidArgumentException("Watermark image not found or invalid filetype.");
		}
		else
		{
			extract($values);
			$wsizes = $this->sizes($filename);
			$sizes = $this->sizes();

			// Load the watermark preserving transparency
			$watermark = $this->load($filename, true);

			// Below is to prevent glitch in GD with negative  $x coords
			if ($x < 0 || $y < 0)
			{
				$this->debug("Modifying watermark to remove negative coords.");
				// Generate a new width and height for the watermark.
				$newwidth = ($x < 0 ? $wsizes->width + $x : $wsizes->width);
				$newheight = ($y < 0 ? $wsizes->height + $y : $wsizes->height);
				// Create a transparent image the size of the new watermark.
				$tmpwatermark = $this->create_transparent_image($newwidth, $newheight);
				$this->debug("New size is $newwidth x $newheight and coords are $x , $y");
				// Call the resize function based on image format
				imagecopy(
					$tmpwatermark, $watermark, // Copy the new image into the tmp watermark
					0, 0,
					$x < 0 ? abs($x) : 0,
					$y < 0 ? abs($y) : 0,
					$newwidth, $newheight
				);
				// Set the variables for the image_merge
				$watermark = $tmpwatermark;
				$x = $x < 0 ? 0 : $x;
				$y = $y < 0 ? 0 : $y;
			}
			// Used as a workaround for lack of alpha support in imagecopymerge.
			$this->debug("Coords for watermark are $x , $y");
			$this->image_merge($this->image_data, $watermark, $x, $y, $this->config['watermark_alpha']);
		}
	}

	protected function _border($size, $color = null)
	{
		extract(parent::_border($size, $color));
		$sizes = $this->sizes();
		$image = $this->create_transparent_image($sizes->width + ($size * 2), $sizes->height + ($size * 2));
		$color = $this->create_color($image, $color, 100);
		$this->image_merge($image, $this->image_data, $size, $size, 100);
		for ($s = 0; $s < $size; $s++)
		{
			imagerectangle($image, $s, $s, $sizes->width + ($size * 2) - $s - 1, $sizes->height + ($size * 2) - $s - 1, $color);
		}
		$this->image_data = $image;
	}

	protected function _mask($maskimage)
	{
		extract(parent::_mask($maskimage));

		// Get size and width of image
		$sizes = $this->sizes();
		$masksizes = $this->sizes($maskimage);

		// Create new blank image
		$image = $this->create_transparent_image($sizes->width, $sizes->height);
		if (is_resource($maskimage))
		{
			$maskim = $maskimage;
		}
		else
		{
			$maskim = $this->load($maskimage, true);
		}

		$masksizes->width > $sizes->width and $masksizes->width = $sizes->width;
		$masksizes->height > $sizes->width and $masksizes->height = $sizes->height;

		// Loop through all the pixels
		for ($x = 0; $x < $masksizes->width; $x++)
		{
			for ($y = 0; $y < $masksizes->height; $y++)
			{
				$maskcolor = imagecolorat($maskim, $x, $y);
				$maskcolor = imagecolorsforindex($maskim, $maskcolor);
				$maskalpha = 127 - floor(($maskcolor['red'] + $maskcolor['green'] + $maskcolor['blue']) / 6);
				if ($maskalpha == 127)
				{
					continue;
				}

				if ($maskalpha == 0)
				{
					$ourcolor = array(
						'red' => 0,
						'green' => 0,
						'blue' => 0,
						'alpha' => 0
					);
				}
				else
				{
					$ourcolor = imagecolorat($this->image_data, $x, $y);
					$ourcolor = imagecolorsforindex($this->image_data, $ourcolor);
				}

				$ouralpha = 127 - $ourcolor['alpha'];
				if ($ouralpha == 0)
				{
					continue;
				}

				$newalpha = floor($ouralpha - (($maskalpha / 127) * $ouralpha));
				$newcolor = imagecolorallocatealpha($image, $ourcolor['red'], $ourcolor['green'], $ourcolor['blue'], 127 - $newalpha);
				imagesetpixel($image, $x, $y, $newcolor);
			}
		}

		$this->image_data = $image;
	}

	protected function _rounded($radius, $sides, $antialias)
	{
		extract(parent::_rounded($radius, $sides, $antialias));

		$tl and $this->round_corner($this->image_data, $radius, $antialias, true, true);
		$tr and $this->round_corner($this->image_data, $radius, $antialias, true, false);
		$bl and $this->round_corner($this->image_data, $radius, $antialias, false, true);
		$br and $this->round_corner($this->image_data, $radius, $antialias, false, false);
	}

	protected function _grayscale()
	{
		$sizes = $this->sizes();

		// Create the 256 color palette
		$bwpalette = array();
		for ($i = 0; $i < 256; $i++)
		{
			$bwpalette[$i] = imagecolorallocate($this->image_data, $i, $i, $i);
		}

		for ($x = 0; $x < $sizes->width; $x++)
		{
			for ($y = 0; $y < $sizes->height; $y++)
			{
				$color = imagecolorat($this->image_data, $x, $y);
				$red   = ($color >> 16) & 0xFF;
				$green = ($color >> 8) & 0xFF;
				$blue  = $color & 0xFF;

				// If its black or white, theres no use in setting the pixel
				if (($red == 0 && $green == 0 && $blue == 0) || ($red == 255 && $green == 255 && $blue == 255))
				{
					continue;
				}

				// Now set the color
				$shade = (($red*0.299)+($green*0.587)+($blue*0.114));
				imagesetpixel($this->image_data, $x, $y, $bwpalette[$shade]);
			}
		}
	}

	public function sizes($filename = null)
	{
		if (empty($filename) && !empty($this->image_fullpath))
		{
			$filename = $this->image_fullpath;
		}

		if ($filename == $this->image_fullpath && is_resource($this->image_data))
		{
			$width  = imagesx($this->image_data);
			$height = imagesy($this->image_data);
		}
		else if (is_resource($filename))
		{
			$width  = imagesx($filename);
			$height = imagesy($filename);
		}
		else
		{
			list($width, $height) = getimagesize($filename);
		}
		return (object) array('width' => $width, 'height' => $height);
	}

	public function save($filename, $permissions = null)
	{
		extract(parent::save($filename, $permissions));

		$this->run_queue();
		$this->add_background();

		$vars = array(&$this->image_data, $filename);
		$filetype = $this->image_extension;
		if ($filetype == 'jpg' || $filetype == 'jpeg')
		{
			$vars[] = $this->config['quality'];
			$filetype = 'jpeg';
		}
		elseif ($filetype == 'png')
		{
			$vars[] = floor(($this->config['quality'] / 100) * 9);
		}

		is_callable(($function_name = array($this, 'image'.$filetype))) or $function_name = 'image'.$filetype;

		call_user_func_array($function_name, $vars);

		if ($this->config['persistence'] === false)
		{
			$this->reload();
		}

		return $this;
	}

	public function output($filetype = null)
	{
		$this->gdresizefunc = ($filetype == 'gif') ? 'imagecopyresized': $this->gdresizefunc = 'imagecopyresampled';

		extract(parent::output($filetype));

		$this->run_queue();
		$this->add_background();

		$vars = array($this->image_data, null);
		if ($filetype == 'jpg' || $filetype == 'jpeg')
		{
			$vars[] = $this->config['quality'];
			$filetype = 'jpeg';
		}
		elseif ($filetype == 'png')
		{
			$vars[] = floor(($this->config['quality'] / 100) * 9);
		}
		
		is_callable(($function_name = array($this, 'image'.$filetype))) or $function_name = 'image'.$filetype;

		call_user_func_array($function_name, $vars);

		if ($this->config['persistence'] === false)
		{
			$this->reload();
		}

		return $this;
	}

	/**
	 * Creates a new color usable by GD.
	 *
	 * @param   resource  $image  The image to create the color from
	 * @param   string    $hex    The hex code of the color
	 * @param   integer   $alpha  The alpha of the color, 0 (trans) to 100 (opaque)
	 * @return  integer   The color
	 */
	protected function create_color(&$image, $hex, $alpha)
	{
		if ($hex == null)
		{
			$red = 0;
			$green = 0;
			$blue = 0;
			$alpha = 127;
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
			$alpha = 127 - floor($alpha * 1.27);
		}

		// Check if the transparency is allowed
		return imagecolorallocatealpha($image, $red, $green, $blue, $alpha);
	}

	protected function add_background()
	{
		if ($this->config['bgcolor'] != null || ($this->new_extension == 'jpg' || $this->new_extension == 'jpeg'))
		{
			$bgcolor = $this->config['bgcolor'] == null ? '#000' : $this->config['bgcolor'];
			$this->debug("Adding background color $bgcolor");
			$sizes = $this->sizes();
			$bgimg = $this->create_transparent_image($sizes->width, $sizes->height);
			$color = $this->create_color($bgimg, $bgcolor, 100);
			imagefill($bgimg, 0, 0, $color);
			$this->image_merge($bgimg, $this->image_data, 0, 0, 100);
			$this->image_data = $bgimg;
		}
	}

	/**
	 * Creates a new transparent image.
	 *
	 * @param  integer   $width     The width of the image.
	 * @param  integer   $height    The height of the image.
	 * @param  resource  $resource  Optionally add an image to the new transparent image.
	 * @return resource  Returns the image in resource form.
	 */
	private function create_transparent_image($width, $height, $resource = null)
	{
		$image = imagecreatetruecolor($width, $height);
		$color = $this->create_color($image, null, 0);
		imagesavealpha($image, true);
		if ($this->image_extension == 'gif' || $this->image_extension == 'png')
		{
			// Get the current transparent color if possible...
			$transcolor = imagecolortransparent($image);
			if ($transcolor > 0)
			{
				$color = $transcolor;
			}
			imagecolortransparent($image, $color);
		}
		// Set the blending mode to false, add the bgcolor, then switch it back.
		imagealphablending($image, false);
		imagefilledrectangle($image, 0, 0, $width, $height, $color);
		imagealphablending($image, true);

		if (is_resource($resource))
		{
			imagecopy($image, $resource, 0, 0, 0, 0, $width, $height);
		}
		return $image;
	}

	/**
	 * Creates a rounded corner on the image.
	 *
	 * @param  resource  $image
	 * @param  integer   $radius
	 * @param  integer   $antialias
	 * @param  boolean   $top
	 * @param  boolean   $left
	 */
	private function round_corner(&$image, $radius, $antialias, $top, $left)
	{
		$this->debug("Rounding ".($top ? 'top' : 'bottom')." ".($left ? 'left' : 'right')." corner with a radius of ".$radius."px.");
		$sX = $left ? -$radius : 0;
		$sY = $top ? -$radius : 0;
		$eX = $left ? 0 : $radius;
		$eY = $top ? 0 : $radius;

		// Get this images size
		$sizes = $this->sizes();
		$offsetX = ($left ? $radius : $sizes->width - $radius - 1);
		$offsetY = ($top ? $radius : $sizes->height - $radius - 1);

		// Set the images alpha blend to false
		imagealphablending($image, false);

		// Make this color ahead time
		$transparent = $this->create_color($image, null, 0);
		for ($x = $sX; $x <= $eX; $x++)
		{
			for ($y = $sY; $y <= $eY; $y++)
			{
				$dist = sqrt(($x * $x) + ($y * $y));
				if ($dist <= $radius + $antialias)
				{
					// Decide if anything needs to be changed
					// We subtract from antialias so the transparency makes sense.
					$fromCirc = $dist - $radius;
					if ($fromCirc > 0)
					{
						if ($fromCirc == 0)
						{
							imagesetpixel($image, $x + $offsetX, $y + $offsetY, $transparent);
						}
						else
						{
							// Get color information from this spot on the image
							$rgba = imagecolorat($image, $x + $offsetX, $y + $offsetY);
							$tmpColor = imagecolorallocatealpha(
								$image,
								($rgba >> 16) & 0xFF, // Red
								($rgba >> 8) & 0xFF, // Green
								$rgba & 0xFF, // Blue
								(127 - (($rgba >> 24) & 0xFF)) * ($fromCirc / $antialias) // Alpha
							);
							imagesetpixel($image, $x + $offsetX, $y + $offsetY, $tmpColor);
						}
					}
				}
				else
				{
					// Clear this area out...
					imagesetpixel($image, $x + $offsetX, $y + $offsetY, $transparent);
				}
			}
		}
		// Reset alpha blending
		imagealphablending($image, true);
	}

	/**
	 * Merges to images together, using a fix for transparency
	 *
	 * @param  resource  $image      The bottom image
	 * @param  resource  $watermark  The image to be placed on top
	 * @param  integer   $x          The position of the watermark on the X-axis
	 * @param  integer   $y          The position of the watermark on the Y-axis
	 * @param  integer   $alpha      The transparency of the watermark, 0 (trans) to 100 (opaque)
	 */
	private function image_merge(&$image, $watermark, $x, $y, $alpha)
	{
		$wsizes = $this->sizes($watermark);
		$tmpimage = $this->create_transparent_image($wsizes->width, $wsizes->height);
		imagecopy($tmpimage, $image, 0, 0, $x, $y, $wsizes->width, $wsizes->height);
		imagecopy($tmpimage, $watermark, 0, 0, 0, 0, $wsizes->width, $wsizes->height);
		imagealphablending($image, false);
		imagecopymerge($image, $tmpimage, $x, $y, 0, 0, $wsizes->width, $wsizes->height, $alpha);
		imagealphablending($image, true);
	}
	
	/**
	 * Output BMP image to browser or file.
	 * 
	 * @copyright	Original code from Worf Data (2006) released under public domain and adapted for FuelPHP by Kriansa
	 * @link		http://www.phpclasses.org/package/3391-PHP-Generate-a-file-in-BMP-format-from-a-GD-image.html
	 * @param		resource	GD Image
	 * @param		string		Filename to save the image, instead output to the browser
	 */
	public static function imagebmp($img, $filename = null)
	{
		
		if( ! is_resource($img))
		{
			throw new \InvalidArgumentException("Input image is not a valid GD resource.");
		}
		
		if($filename !== null and ! is_file($filename))
		{
			throw new \OutOfBoundsException("File $filename not found!");
		}
		
		if($filename !== null and ! is_writable($filename))
		{
			throw new \OutOfBoundsException("You don't have permissions to write to file $filename.");
		}
		
		// Helper functions to handle with header
		$int_to_dword = function($n){
	      return chr($n & 255).chr(($n >> 8) & 255).chr(($n >> 16) & 255).chr(($n >> 24) & 255);
	    };
		
	    $int_to_word = function($n){
	      return chr($n & 255).chr(($n >> 8) & 255);
	    };
		
		// And here we go
		
		$widthOrig = imagesx($img);
		// width = 16*x
		$widthFloor = ((floor($widthOrig/16))*16);
		$widthCeil = ((ceil($widthOrig/16))*16);
		$height = imagesy($img);
  
		$size = ($widthCeil*$height*3)+54;
  
		// Bitmap File Header
		$result = 'BM';     // header (2b)
		$result .= $int_to_dword($size); // size of file (4b)
		$result .= $int_to_dword(0); // reserved (4b)
		$result .= $int_to_dword(54);  // byte location in the file which is first byte of IMAGE (4b)
		// Bitmap Info Header
		$result .= $int_to_dword(40);  // Size of BITMAPINFOHEADER (4b)
		$result .= $int_to_dword($widthCeil);  // width of bitmap (4b)
		$result .= $int_to_dword($height); // height of bitmap (4b)
		$result .= $int_to_word(1);  // biPlanes = 1 (2b)
		$result .= $int_to_word(24); // biBitCount = {1 (mono) or 4 (16 clr ) or 8 (256 clr) or 24 (16 Mil)} (2b)
		$result .= $int_to_dword(0); // RLE COMPRESSION (4b)
		$result .= $int_to_dword(0); // width x height (4b)
		$result .= $int_to_dword(0); // biXPelsPerMeter (4b)
		$result .= $int_to_dword(0); // biYPelsPerMeter (4b)
		$result .= $int_to_dword(0); // Number of palettes used (4b)
		$result .= $int_to_dword(0); // Number of important colour (4b)
		
		// is faster than chr()
		$arrChr = array();
		for($i=0; $i<256; $i++){
			$arrChr[$i] = chr($i);
		}
  
		// creates image data
		$bgfillcolor = array("red"=>0, "green"=>0, "blue"=>0);
  
		// bottom to top - left to right - attention blue green red !!!
		$y=$height-1;
		for ($y2=0; $y2<$height; $y2++) {
			for ($x=0; $x<$widthFloor;  ) {
				$rgb = imagecolorsforindex($img, imagecolorat($img, $x++, $y));
				$result .= $arrChr[$rgb["blue"]].$arrChr[$rgb["green"]].$arrChr[$rgb["red"]];
				$rgb = imagecolorsforindex($img, imagecolorat($img, $x++, $y));
				$result .= $arrChr[$rgb["blue"]].$arrChr[$rgb["green"]].$arrChr[$rgb["red"]];
				$rgb = imagecolorsforindex($img, imagecolorat($img, $x++, $y));
				$result .= $arrChr[$rgb["blue"]].$arrChr[$rgb["green"]].$arrChr[$rgb["red"]];
				$rgb = imagecolorsforindex($img, imagecolorat($img, $x++, $y));
				$result .= $arrChr[$rgb["blue"]].$arrChr[$rgb["green"]].$arrChr[$rgb["red"]];
				$rgb = imagecolorsforindex($img, imagecolorat($img, $x++, $y));
				$result .= $arrChr[$rgb["blue"]].$arrChr[$rgb["green"]].$arrChr[$rgb["red"]];
				$rgb = imagecolorsforindex($img, imagecolorat($img, $x++, $y));
				$result .= $arrChr[$rgb["blue"]].$arrChr[$rgb["green"]].$arrChr[$rgb["red"]];
				$rgb = imagecolorsforindex($img, imagecolorat($img, $x++, $y));
				$result .= $arrChr[$rgb["blue"]].$arrChr[$rgb["green"]].$arrChr[$rgb["red"]];
				$rgb = imagecolorsforindex($img, imagecolorat($img, $x++, $y));
				$result .= $arrChr[$rgb["blue"]].$arrChr[$rgb["green"]].$arrChr[$rgb["red"]];
			}
			for ($x=$widthFloor; $x<$widthCeil; $x++) {
				$rgb = ($x<$widthOrig) ? imagecolorsforindex($img, imagecolorat($img, $x, $y)) : $bgfillcolor;
				$result .= $arrChr[$rgb["blue"]].$arrChr[$rgb["green"]].$arrChr[$rgb["red"]];
			}
			$y--;
		}

		if($filename === null)
		{
			echo $result;
		}
		else
		{
			file_put_contents($filename, $result);
		}
	}

	/**
	 * Create a new image from file or URL
	 * 
	 * @copyright	Code taken from PHPRO and adapted for FuelPHP by Kriansa
	 * @link		http://www.phpro.org/examples/Convert-BMP-to-JPG.html
	 * @param		string		Filename
	 * @return		resource	GD2 image
	 */
	public static function imagecreatefrombmp($src)
	{
		// Open source file for reading
		if(!($srch = fopen($src, 'rb')))
		{
			throw new \OutOfBoundsException("Image file $src does not exist.");
		}
	
		// Get the headers
		extract(unpack('vtype/Vsize/Vreserved/Voffset/Vsize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vcolor/Vimportant', fread($srch, 54)));
	
		// Check for BMP signature
		if($type != 0x4D42)
		{
			throw new \InvalidArgumentException('Source image is not a BMP.');
		}
		
		// Create the temp file to handle
		$tmpfile = tempnam(APPPATH.'tmp'.DS, uniqid('bmp2gd'));
		$fp = fopen($tmpfile, 'wb');
	
		// Set the pallete
		$palette_size = $offset - 54;
		$ncolor = $palette_size / 4;
	
		// True-color vs. palette
		$gd_header = ($palette_size == 0)?"\xFF\xFE":"\xFF\xFF";
		$gd_header .= pack('n2', $width, $height);
		$gd_header .= ($palette_size == 0)?"\x01":"\x00";
		if ($palette_size) { $gd_header .= pack('n', $ncolor); }
		
		// Do not allow transparency
		$gd_header .= "\xFF\xFF\xFF\xFF";
		
		// Write the destination headers
		fwrite($fp, $gd_header);
		unset($gd_header);
	
		// if we have a palette
		if ($palette_size) {
			// read the palette
			$palette = fread($srch, $palette_size);
			// begin the gd palette
			$gd_palette = '';
			$j = 0;
			// loop of the palette
			while ($j < $palette_size) {
				$b = $palette{$j++};
				$g = $palette{$j++};
				$r = $palette{$j++};
				$a = $palette{$j++};
				// assemble the gd palette
				$gd_palette .= $r.$g.$b.$a;
			}
			// finish the palette
			$gd_palette .= str_repeat("\x00\x00\x00\x00", 256 - $ncolor);
			// write the gd palette
			fwrite($fp, $gd_palette);
			unset($gd_palette);
		}
	
		// scan line size and alignment
		$scan_line_size = (($bits * $width) + 7) >> 3;
		$scan_line_align = ($scan_line_size & 0x03) ? 4 - ($scan_line_size & 0x03):0;
	
		// main loop
		for ($i = 0, $l = $height - 1; $i < $height; $i++, $l--) {
			// create scan lines starting from bottom
			fseek($srch, $offset + (($scan_line_size + $scan_line_align) * $l));
			$scan_line = fread($srch, $scan_line_size);
			$gd_scan_line = '';
			if($bits == 24) {
				$j = 0;
				while($j < $scan_line_size) {
					$b = $scan_line{$j++};
					$g = $scan_line{$j++};
					$r = $scan_line{$j++};
					$gd_scan_line .= "\x00".$r.$g.$b;
				}
			}
			elseif($bits == 8) { $gd_scan_line = $scan_line; }
			elseif($bits == 4) {
				$j = 0;
				while($j < $scan_line_size) {
					$byte = ord($scan_line{$j++});
					$p1 = chr($byte >> 4);
					$p2 = chr($byte & 0x0F);
					$gd_scan_line .= $p1.$p2;
				}
				$gd_scan_line = substr($gd_scan_line, 0, $width);
			}
			elseif($bits == 1) {
			  $j = 0;
			  while($j < $scan_line_size) {
				  $byte = ord($scan_line{$j++});
				  $p1 = chr((int) (($byte & 0x80) != 0));
				  $p2 = chr((int) (($byte & 0x40) != 0));
				  $p3 = chr((int) (($byte & 0x20) != 0));
				  $p4 = chr((int) (($byte & 0x10) != 0));
				  $p5 = chr((int) (($byte & 0x08) != 0));
				  $p6 = chr((int) (($byte & 0x04) != 0));
				  $p7 = chr((int) (($byte & 0x02) != 0));
				  $p8 = chr((int) (($byte & 0x01) != 0));
				  $gd_scan_line .= $p1.$p2.$p3.$p4.$p5.$p6.$p7.$p8;
			  }
				// put the gd scan lines together
				$gd_scan_line = substr($gd_scan_line, 0, $width);
			}
			// write the gd scan lines
			fwrite($fp, $gd_scan_line);
			unset($gd_scan_line);
		}
		// close the source file
		fclose($fp);
		
		$gd = imagecreatefromgd($tmpfile);
		unlink($tmpfile);
		
		return $gd;
	}
}