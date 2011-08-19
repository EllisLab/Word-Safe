<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
						'pi_name'			=> 'Word Safe',
						'pi_version'		=> '1.3',
						'pi_author'			=> 'Paul Burdick',
						'pi_author_url'		=> 'http://www.expressionengine.com/',
						'pi_description'	=> 'Keeps words from being over a certain length',
						'pi_usage'			=> Word_safe::usage()
					);

/**
 * Word_safe Class
 *
 * @package			ExpressionEngine
 * @category		Plugin
 * @author			ExpressionEngine Dev Team
 * @copyright		Copyright (c) 2004 - 2009, EllisLab, Inc.
 * @link			http://expressionengine.com/downloads/details/word_safe/
 */

class Word_safe {

    var $return_data;
    var $length;
    var $option;
    var $js = array();
    
	/**
	 * Word Safe
	 *
	 * @access	public
	 * @return	void	// sets $return_data
	 */
    function Word_safe($str = '')
    {
		$this->EE =& get_instance();
		                        
		$this->length	= ( ! $this->EE->TMPL->fetch_param('length'))	? 25		: $this->EE->TMPL->fetch_param('length');
		$this->option	= ( ! $this->EE->TMPL->fetch_param('option'))	? 'shorten'	: $this->EE->TMPL->fetch_param('option');
		
		$safe_pre	= ( ! $this->EE->TMPL->fetch_param('safe_pre'))	? 'y'		: $this->EE->TMPL->fetch_param('safe_pre');
		$safe_urls	= ( ! $this->EE->TMPL->fetch_param('safe_urls'))	? 'y'		: $this->EE->TMPL->fetch_param('safe_urls');
		
		if ($str == '')
		{
			$str = $this->EE->TMPL->tagdata;
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
	
	/**
	 * Usage
	 *
	 * Plugin Usage
	 *
	 * @access	public
	 * @return	string
	 */
	function usage()
	{
		ob_start(); 
		?>
		Prevents words from being longer than a certain length

		{exp:word_safe length="25" option="breakup"}

		{comment}

		{/exp:word_safe}


		PARAMETERS:

		length - Maximum character length for a word (default is 25).

		option - What to do with words that exceed the maximum length.
			* option="breakup" - Breaks up word with spaces.  No chunk will ever exceed the maximum length.
			* option="remove" - Removes the word
			* option="shorten" (default) - Removes any characters after the maximum length has been reached 

		safe_pre (y/n) - If set to 'y' (default), any content in a <pre> tag are exempt from the maximum word length rule.  If set to 'n', then the contents of a <pre> tag are treated like any other content.

		safe_urls (y/n) - If set to 'y' (default), the content in <a> and <img> tags are ignored and thus the URLs are safe from the maximum word length.  If set to 'n', then the contents of the <a> and <img> tags will be treated likes words.


		******************
		VERSION 1.1
		******************
		 - Fixed bug where the breakup setting the letter at the break was duplicated.

		******************
		VERSION 1.2
		******************
		 - Fixed bug where entities were being broken up, just like a boy band.

		******************
		VERSION 1.3
		******************
		 - Updated plugin to be 2.0 compatible

		<?php
		$buffer = ob_get_contents();
	
		ob_end_clean(); 

		return $buffer;
	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file pi.word_safe.php */
/* Location: ./system/expressionengine/third_party/word_safe/pi.word_safe.php */