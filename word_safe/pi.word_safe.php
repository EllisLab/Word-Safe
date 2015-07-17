<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
Copyright (C) 2004 - 2015 EllisLab, Inc.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
ELLISLAB, INC. BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

Except as contained in this notice, the name of EllisLab, Inc. shall not be
used in advertising or otherwise to promote the sale, use or other dealings
in this Software without prior written authorization from EllisLab, Inc.
*/

/**
 * Word_safe Class
 *
 * @package			ExpressionEngine
 * @category		Plugin
 * @author			EllisLab
 * @copyright		Copyright (c) 2004 - 2015, EllisLab, Inc.
 * @link			https://github.com/EllisLab/Word-Safe
 */
class Word_safe {

    public $return_data;
    public $length;
    public $option;
    public $js = array();

	/**
	 * Word Safe
	 *
	 * @access	public
	 * @return	void	// sets $return_data
	 */
	function __construct($str = '')
    {
		$this->length	= ( ! ee()->TMPL->fetch_param('length'))	? 25		: ee()->TMPL->fetch_param('length');
		$this->option	= ( ! ee()->TMPL->fetch_param('option'))	? 'shorten'	: ee()->TMPL->fetch_param('option');

		$safe_pre	= ( ! ee()->TMPL->fetch_param('safe_pre'))	? 'y'		: ee()->TMPL->fetch_param('safe_pre');
		$safe_urls	= ( ! ee()->TMPL->fetch_param('safe_urls'))	? 'y'		: ee()->TMPL->fetch_param('safe_urls');

		if ($str == '')
		{
			$str = ee()->TMPL->tagdata;
		}

		// Length Limit must be greater than 5
		// if safe urls is enabled

		if ($this->length <= 5 && $safe_urls == 'y')
		{
			$this->return_data = $str;
			return $this->return_data;
		}

		if (preg_match_all("|\<script(.*?)\<\/script\>|is", $str, $matches))
		{
			$js_unique = $this->functions->random('alpha', 3);

			for($i=0; $i < count($matches['0']); $i++)
			{
				$this->js[$i] = $matches['0'][$i];

				$str = str_replace($matches['0'][$i], "{$js_unique}_{$i}", $str);
			}
		}

		//----------------------------------------
        // Removes line breaks and spaces
		//----------------------------------------

		$str = preg_replace("/(\r\n)|(\r)|(\n)/", ' ', $str);

        $str = preg_replace("/\s+/", ' ', $str);

        //----------------------------------------
        // Protect Links
		//----------------------------------------

		if ($safe_urls == 'y')
		{
			$links = array();

			if (preg_match_all("/<img (.+?)>/i", $str, $matches))
			{
				for ($i = 0; $i < count($matches['0']); $i++)
				{
					$rand = $this->functions->random('alpha', 5);
					$links[$rand] = $matches['0'][$i];
					$str = str_replace($matches['0'][$i], $rand, $str);
				}
			}

			if (preg_match_all("/<a (.+?)>(.+?)\<\/a>/i", $str, $matches))
			{
				for ($i = 0; $i < count($matches['0']); $i++)
				{
					$rand = $this->functions->random('alpha', 5);

					$matches['0'][$i] = str_replace($matches['2'][$i], $this->check_string($matches['2'][$i]), $matches['0'][$i]);

					$links[$rand] = $matches['0'][$i];
					$str = str_replace($matches['0'][$i], $rand, $str);
				}
			}

			if (preg_match_all("/<a (.+?)>/i", $str, $matches))
			{
				for ($i = 0; $i < count($matches['0']); $i++)
				{
					$rand = $this->functions->random('alpha', 5);
					$links[$rand] = $matches['0'][$i];
					$str = str_replace($matches['0'][$i], $rand, $str);
				}
			}
        }

        //----------------------------------------
        // Protect Pre
		//----------------------------------------

		if ($safe_pre == 'y')
		{
			$pre = array();

			if (preg_match_all("/\<pre\>.+?\<\/pre\>/si", $str, $matches))
        	{
				for ($i = 0; $i < count($matches['0']); $i++)
				{
					$rand = $this->functions->random('alpha', 5);
					$pre[$rand] = $matches['0'][$i];
					$str = str_replace($matches['0'][$i], $rand, $str);
				}
        	}
		}


		//----------------------------------------
        // Do the Dew
		//----------------------------------------

        $str = $this->check_string($str);

    	//----------------------------------------
        // Revert Markers back to Images, Links, and Pre
		//----------------------------------------

    	if ($safe_urls == 'y' && count($links) > 0)
    	{
    		$links = array_reverse($links);
    		$str = str_replace(array_keys($links), array_values($links), $str);
    	}

    	if ($safe_pre == 'y' and count($pre) > 0)
    	{
    		$pre = array_reverse($pre);
    		$str = str_replace(array_keys($pre), array_values($pre), $str);
    	}

    	// ---------------------------------------
    	//  JavaScript Transport Complete, Cap'n!
    	// ---------------------------------------

    	if (count($this->js) > 0)
    	{
    		for($i=0; $i < count($this->js); $i++)
			{
				$str = str_replace("{$js_unique}_{$i}", $this->js[$i], $str);
			}
    	}

    	//----------------------------------------
        // Adios! We're done!
		//----------------------------------------

 		$this->return_data = trim($str);
    }

    // --------------------------------------------------------------------

	/**
	* Check string
	*
	* Checks word length
	*
	* @access   public
	* @param    string
	* @return   string
	*/
    function check_string($str)
    {
    	$words = explode(' ', $str);

        $str = '';

        foreach($words as $word)
        {
        	if (strlen($word) > $this->length)
    		{
				switch($this->option)
				{
					case 'breakup' :  // Break up
						while (strlen($word) > $this->length)
						{
							$length = $this->length;

							// The Wonderful Code that Protects Entities!
							// Oh, the joy I experienced while writing this code!

							if (preg_match_all("/&#(\d+);|&(\w+);/", $word, $matches))
							{
								for($i=0; $i < count($matches['0']); $i++)
								{
									$p1 = strpos($word, $matches['0'][$i]);
									$p2 = strlen($matches['0'][$i]);

									if (($this->length >= $p1) && ($this->length < $p1 + $p2))
									{
										$length = $p1 + $p2;
										break;
									}
								}
							}

							$str .= substr($word,0,$length).' ';
							$word = substr($word,$length);
						}

						$str .= $word.' ';
					break;
					case 'remove' :  // Remove
						// Nothing
					break;
					default:  // Shorten

						$length = $this->length;

						// The Wonderful Code that Protects Entities!
						// Oh, the joy I experienced while writing this code!

						if (preg_match_all("/&#(\d+);|&(\w+);/", $word, $matches))
						{
							for($i=0; $i < count($matches['0']); $i++)
							{
								$p1 = strpos($word, $matches['0'][$i]);
								$p2 = strlen($matches['0'][$i]);

								if (($this->length >= $p1) && ($this->length < $p1 + $p2))
								{
									$length = $p1 + $p2;
									break;
								}
							}
						}

						$str .= substr($word,0,$length).' ';
					break;
				}
			}
			else
			{
				$str .= $word.' ';
			}
		}

		return trim($str);
    }

	// --------------------------------------------------------------------
}
