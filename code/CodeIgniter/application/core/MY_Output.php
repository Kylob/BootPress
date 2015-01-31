<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// These methods were included initially in 3.0 then removed.  I thought they were working great, so here they are again.

class My_Output extends CI_Output {

	/**
	 * Minify
	 *
	 * Reduce excessive size of HTML/CSS/JavaScript content.
	 *
	 * @param	string	$output	Output to minify
	 * @param	string	$type	Output content MIME type
	 * @return	string	Minified output
	 */
	public function minify($output, $type = 'text/html')
	{
		switch ($type)
		{
			case 'text/html':

				if (($size_before = strlen($output)) === 0)
				{
					return '';
				}

				// Find all the <pre>,<code>,<textarea>, and <javascript> tags
				// We'll want to return them to this unprocessed state later.
				preg_match_all('{<pre.+</pre>}msU', $output, $pres_clean);
				preg_match_all('{<code.+</code>}msU', $output, $codes_clean);
				preg_match_all('{<textarea.+</textarea>}msU', $output, $textareas_clean);
				preg_match_all('{<script.+</script>}msU', $output, $javascript_clean);

				// Minify the CSS in all the <style> tags.
				preg_match_all('{<style.+</style>}msU', $output, $style_clean);
				foreach ($style_clean[0] as $s)
				{
					$output = str_replace($s, $this->_minify_js_css($s, 'css', TRUE), $output);
				}

				// Minify the javascript in <script> tags.
				foreach ($javascript_clean[0] as $s)
				{
					$javascript_mini[] = $this->_minify_js_css($s, 'js', TRUE);
				}

				// Replace multiple spaces with a single space.
				$output = preg_replace('!\s{2,}!', ' ', $output);

				// Remove comments (non-MSIE conditionals)
				$output = preg_replace('{\s*<!--[^\[<>].*(?<!!)-->\s*}msU', '', $output);

				// Remove spaces around block-level elements.
				$output = preg_replace('/\s*(<\/?(html|head|title|meta|script|link|style|body|table|thead|tbody|tfoot|tr|th|td|h[1-6]|div|p|br)[^>]*>)\s*/is', '$1', $output);

				// Replace mangled <pre> etc. tags with unprocessed ones.

				if ( ! empty($pres_clean))
				{
					preg_match_all('{<pre.+</pre>}msU', $output, $pres_messed);
					$output = str_replace($pres_messed[0], $pres_clean[0], $output);
				}

				if ( ! empty($codes_clean))
				{
					preg_match_all('{<code.+</code>}msU', $output, $codes_messed);
					$output = str_replace($codes_messed[0], $codes_clean[0], $output);
				}

				if ( ! empty($textareas_clean))
				{
					preg_match_all('{<textarea.+</textarea>}msU', $output, $textareas_messed);
					$output = str_replace($textareas_messed[0], $textareas_clean[0], $output);
				}

				if (isset($javascript_mini))
				{
					preg_match_all('{<script.+</script>}msU', $output, $javascript_messed);
					$output = str_replace($javascript_messed[0], $javascript_mini, $output);
				}

				$size_removed = $size_before - strlen($output);
				$savings_percent = round(($size_removed / $size_before * 100));

				log_message('debug', 'Minifier shaved '.($size_removed / 1000).'KB ('.$savings_percent.'%) off final HTML output.');

			break;

			case 'text/css':

				return $this->_minify_js_css($output, 'css');

			case 'text/javascript':
			case 'application/javascript':
			case 'application/x-javascript':

				return $this->_minify_js_css($output, 'js');

			default: break;
		}

		return $output;
	}

	// --------------------------------------------------------------------

	/**
	 * Minify JavaScript and CSS code
	 *
	 * Strips comments and excessive whitespace characters
	 *
	 * @param	string	$output
	 * @param	string	$type	'js' or 'css'
	 * @param	bool	$tags	Whether $output contains the 'script' or 'style' tag
	 * @return	string
	 */
	protected function _minify_js_css($output, $type, $tags = FALSE)
	{
		if ($tags === TRUE)
		{
			$tags = array('close' => strrchr($output, '<'));

			$open_length = strpos($output, '>') + 1;
			$tags['open'] = substr($output, 0, $open_length);

			$output = substr($output, $open_length, -strlen($tags['close']));

			// Strip spaces from the tags
			$tags = preg_replace('#\s{2,}#', ' ', $tags);
		}

		$output = trim($output);

		if ($type === 'js')
		{
			// Catch all string literals and comment blocks
			if (preg_match_all('#((?:((?<!\\\)\'|")|(/\*)|(//)).*(?(2)(?<!\\\)\2|(?(3)\*/|\n)))#msuUS', $output, $match, PREG_OFFSET_CAPTURE))
			{
				$js_literals = $js_code = array();
				for ($match = $match[0], $c = count($match), $i = $pos = $offset = 0; $i < $c; $i++)
				{
					$js_code[$pos++] = trim(substr($output, $offset, $match[$i][1] - $offset));
					$offset = $match[$i][1] + strlen($match[$i][0]);

					// Save only if we haven't matched a comment block
					if ($match[$i][0][0] !== '/')
					{
						$js_literals[$pos++] = array_shift($match[$i]);
					}
				}
				$js_code[$pos] = substr($output, $offset);

				// $match might be quite large, so free it up together with other vars that we no longer need
				unset($match, $offset, $pos);
			}
			else
			{
				$js_code = array($output);
				$js_literals = array();
			}

			$varname = 'js_code';
		}
		else
		{
			$varname = 'output';
		}

		// Standartize new lines
		$$varname = str_replace(array("\r\n", "\r"), "\n", $$varname);

		if ($type === 'js')
		{
			$patterns = array(
				'#\s*([!\#%&()*+,\-./:;<=>?@\[\]^`{|}~])\s*#'	=> '$1',	// Remove spaces following and preceeding JS-wise non-special & non-word characters
				'#\s{2,}#'					=> ' '		// Reduce the remaining multiple whitespace characters to a single space
			);
		}
		else
		{
			$patterns = array(
				'#/\*.*(?=\*/)\*/#s'	=> '',		// Remove /* block comments */
				'#\n?//[^\n]*#'		=> '',		// Remove // line comments
				'#\s*([^\w.\#%])\s*#U'	=> '$1',	// Remove spaces following and preceeding non-word characters, excluding dots, hashes and the percent sign
				'#\s{2,}#'		=> ' '		// Reduce the remaining multiple space characters to a single space
			);
		}

		$$varname = preg_replace(array_keys($patterns), array_values($patterns), $$varname);

		// Glue back JS quoted strings
		if ($type === 'js')
		{
			$js_code += $js_literals;
			ksort($js_code);
			$output = implode($js_code);
			unset($js_code, $js_literals, $varname, $patterns);
		}

		return is_array($tags)
			? $tags['open'].$output.$tags['close']
			: $output;
	}

}

/* End of file MY_Output.php */
/* Location: ./application/core/MY_Output.php */