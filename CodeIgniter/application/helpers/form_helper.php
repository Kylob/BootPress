<?php

##
# This is to correct undocumented changes from 2.2.0 to 3.0
# Textarea values are being processed twice if used in conjuction with set_value()
# I understand the limitations, but why get rid of the $prepped_fields array() ?
# Why all of a sudden add stripslashes() ?
# What was wrong with htmlspecialchars() ?
# HTML Entities are all screwed up with the new changes so ...
##

if ( ! function_exists('form_prep'))
{
	/**
	 * Form Prep
	 *
	 * Formats text so that it can be safely placed in a form field in the event it has HTML tags.
	 *
	 * @param	string|string[]	$str		Value to escape
	 * @param	bool		$is_textarea	Whether we're escaping for a textarea element
	 * @return	string|string[]	Escaped values
	 */
	function form_prep($str = '', $is_textarea = FALSE)
	{
		static $prepped = array();
		
		if (is_array($str))
		{
			foreach (array_keys($str) as $key)
			{
				$str[$key] = form_prep($str[$key], $is_textarea);
			}

			return $str;
		}
		
		if (isset($prepped[md5($str)])) return $str; // this field has already been prepped
		
		$field = str_replace(array("'", '"'), array("&#39;", "&quot;"), htmlspecialchars($str));
		
		$prepped[md5($field)] = $str;
		
		return $field;
		
		if ($is_textarea === TRUE)
		{
			return str_replace(array('<', '>'), array('&lt;', '&gt;'), stripslashes($str));
		}

		return str_replace(array("'", '"'), array('&#39;', '&quot;'), stripslashes($str));
	}
}

include BASEPATH . 'helpers/form_helper.php';

/* End of file form_helper.php */
/* Location: ./application/helpers/form_helper.php */