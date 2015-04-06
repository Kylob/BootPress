<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * UnZip Class
 *
 * This class is based on a library I found at PHPClasses:
 * http://phpclasses.org/package/2495-PHP-Pack-and-unpack-files-packed-in-ZIP-archives.html
 *
 * The original library is a little rough around the edges so I
 * refactored it and added several additional methods -- Phil Sturgeon
 *
 * Then I carried the torch a little bit further -- Kyle Gadd
 *
 * This class requires extension ZLib Enabled.
 *
 * @package		CodeIgniter
 * @subpackage		Libraries
 * @category		Encryption
 * @author		Alexandre Tedeschi
 * @author		Phil Sturgeon
 * @author		Don Myers
 * @link		http://bitbucket.org/philsturgeon/codeigniter-unzip
 * @license     	
 * @version     	1.0.0
 */
 
class Unzip {

	private $fh;
	private $dir;
	private $chmod;
	private $info = array();
	private $error = array();
	private $compressed_list = array();
	private $central_dir_list = array();
	private $end_of_central = array();
	
	// --------------------------------------------------------------------

	/**
	 * Establish the file to be unzipped, and where.
	 *
	 * @access    public
	 * @param     string, string, int
	 * @return    array
	 */
	public function files ($zip_file, $target_dir = NULL, $chmod = 0777)
	{
		foreach (array('info', 'error', 'compressed_list', 'central_dir_list', 'end_of_central') as $var) $this->$var = array();
		if ($this->fh = fopen($zip_file, 'r'))
		{
			if ( ! $this->load_file_list_by_eof())
			{
				$this->load_files_by_signatures();
			}
		}
		$this->dir = $target_dir ? $target_dir : dirname($zip_file);
		$this->dir = rtrim(str_replace('\\', '/', $this->dir), '/') . '/';
		$this->chmod = $chmod;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Retrieve all of the zipped files FYI.  Used internally as needed.
	 *
	 * @access    public
	 * @param     none
	 * @return    array
	 */
	public function zipped ()
	{
		$files = array_keys($this->compressed_list);
		sort($files);
		return $files;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Retrieve the common directory (if any) to remove for $this->extract().
	 * http://stackoverflow.com/questions/1336207/finding-common-prefix-of-array-of-strings
	 *
	 * @access    public
	 * @param     none
	 * @return    string
	 */
	public function common_dir ()
	{
		$files = $this->zipped();
		$dir = array_shift($files);
		$length = strlen($dir);
		foreach ($files as $common)
		{
			while ($length && substr($common, 0, $length) !== $dir)
			{
				$dir = substr($dir, 0, -1);
				$length--;
			}
			if (!$length) break;
		}
		return ($slash = strrpos($dir, '/')) ? substr($dir, 0, $slash + 1) : '';
        }
        
	// --------------------------------------------------------------------

	/**
	 * Unzip all files in archive.
	 *
	 * @access    public
	 * @param     mixed, string
	 * @return    array
	 */
	public function extract ($allow_extensions = NULL, $remove_from_path = '')
	{
		$file_locations = array();
		foreach ($this->zipped() as $file)
		{
			$path = str_replace($remove_from_path, '', $file);
			if (empty($path) || substr($file, -1, 1) == '/') continue;
			if ( ! $this->allowed($path, $allow_extensions)) continue;
			$file_locations[$path] = $file_location = $this->dir . $path;
			$this->mkpath(pathinfo($path, PATHINFO_DIRNAME));
			$this->extract_file($file, $file_location);
		}
		return $file_locations;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Unzip all files from specified folders in archive.
	 *
	 * @access    public
	 * @param     array, mixed
	 * @return    array
	 */
        public function extract_folders (array $folders, $allow_extensions = NULL)
        {
        	$file_locations = array();
        	foreach ($folders as $folder)
        	{
        		foreach ($this->zipped() as $file)
        		{
        			if (strpos($file, $folder . '/') !== 0) continue;
        			if ( ! $this->allowed($file, $allow_extensions)) continue;
        			$file_locations[$folder][substr($file, strlen($folder) + 1)] = $file_location = $this->dir . $file;
        			$this->mkpath(pathinfo($file, PATHINFO_DIRNAME));
        			$this->extract_file($file, $file_location);
        		}
        	}
        	return $file_locations;
        }

	// --------------------------------------------------------------------

	/**
	 * Unzip specified files in archive.
	 *
	 * @access    public
	 * @param     array
	 * @return    array
	 */
        public function extract_files (array $files)
        {
        	$file_locations = array();
        	foreach ($files as $extract)
        	{
        		foreach ($this->zipped() as $file)
        		{
        			if ($extract != $file) continue;
        			$file_locations[$file] = $file_location = $this->dir . $file;
        			$this->mkpath(pathinfo($file, PATHINFO_DIRNAME));
        			$this->extract_file($file, $file_location);
        			break;
        		}
        	}
        	return $file_locations;
        }
        
	// --------------------------------------------------------------------

	/**
	 * Free the file resource.
	 *
	 * @access    public
	 * @param     none
	 * @return    none
	 */
	public function close ()
	{
		if ($this->fh) fclose($this->fh);
	}

	// --------------------------------------------------------------------

	/**
	 * Free the file resource automatically.
	 *
	 * @access    public
	 * @param     none
	 * @return    none
	 */
	public function __destroy ()
	{
		$this->close();
	}

	// --------------------------------------------------------------------

	/**
	 * Show error messages.
	 *
	 * @access    public
	 * @param     string, string
	 * @return    string
	 */
	public function error_string ($open = '<p>', $close = '</p>')
	{
		return $open . implode($close . $open, $this->error) . $close;
	}

	// --------------------------------------------------------------------

	/**
	 * Show debug messages.
	 *
	 * @access    public
	 * @param     string, string
	 * @return    string
	 */
	public function debug_string ($open = '<p>', $close = '</p>')
	{
		return $open . implode($close . $open, $this->info) . $close;
	}

	// --------------------------------------------------------------------

	/**
	 * Save errors.
	 *
	 * @access    private
	 * @param     string
	 * @return    none
	 */
	private function set_error ($string)
	{
		$this->error[] = $string;
	}

	// --------------------------------------------------------------------

	/**
	 * Save debug data.
	 *
	 * @access    private
	 * @param     string
	 * @return    none
	 */
	private function set_debug ($string)
	{
		$this->info[] = $string;
	}

	// --------------------------------------------------------------------

	/**
	 * Unzip file in archive.
	 *
	 * @access    public
	 * @param     string, boolean
	 * @return    Unziped file
	 */
	private function extract_file ($compressed_file_name, $target_file_name)
	{
		$fdetails = &$this->compressed_list[$compressed_file_name];

		if ( ! isset($this->compressed_list[$compressed_file_name]))
		{
			$this->set_error('File "<strong>$compressed_file_name</strong>" is not compressed in the zip.');
			return FALSE;
		}

		if (substr($compressed_file_name, -1) == '/')
		{
			$this->set_error('Trying to unzip a folder name "<strong>$compressed_file_name</strong>".');
			return FALSE;
		}

		if ( ! $fdetails['uncompressed_size'])
		{
			$this->set_debug('File "<strong>$compressed_file_name</strong>" is empty.');
			return $target_file_name ? file_put_contents($target_file_name, '') : '';
		}

		fseek($this->fh, $fdetails['contents_start_offset']);
		$ret = $this->uncompress(
			fread($this->fh, $fdetails['compressed_size']),
			$fdetails['compression_method'],
			$fdetails['uncompressed_size'],
			$target_file_name
		);

		chmod($target_file_name, FILE_READ_MODE);

		return $ret;
	}

	// --------------------------------------------------------------------

	/**
	 * Uncompress file, and save it to the target file.
	 *
	 * @access    private
	 * @param     file content, int, int, boolean
	 * @return    none
	 */
	private function uncompress ($content, $mode, $uncompressed_size, $target_file_name)
	{
		switch ($mode)
		{
			case 0:
				return $target_file_name ? file_put_contents($target_file_name, $content) : $content;
			case 1:
				$this->set_error('Shrunk mode is not supported... yet?');
				return FALSE;
			case 2:
			case 3:
			case 4:
			case 5:
				$this->set_error('Compression factor ' . ($mode - 1) . ' is not supported... yet?');
				return FALSE;
			case 6:
				$this->set_error('Implode is not supported... yet?');
				return FALSE;
			case 7:
				$this->set_error('Tokenizing compression algorithm is not supported... yet?');
				return FALSE;
			case 8:
				// Deflate
				return $target_file_name ?
						file_put_contents($target_file_name, gzinflate($content, $uncompressed_size)) :
						gzinflate($content, $uncompressed_size);
			case 9:
				$this->set_error('Enhanced Deflating is not supported... yet?');
				return FALSE;
			case 10:
				$this->set_error('PKWARE Date Compression Library Impoloding is not supported... yet?');
				return FALSE;
			case 12:
				// Bzip2
				return $target_file_name ?
						file_put_contents($target_file_name, bzdecompress($content)) :
						bzdecompress($content);
			case 18:
				$this->set_error('IBM TERSE is not supported... yet?');
				return FALSE;
			default:
				$this->set_error('Unknown uncompress method: $mode');
				return FALSE;
		}
	}

	private function load_file_list_by_eof ()
	{
		// Check if there's a valid Central Dir signature.
		// Let's consider a file comment smaller than 1024 characters...
		// Actually, it length can be 65536.. But we're not going to support it.

		for ($x = 0; $x < 1024; $x++)
		{
			fseek($this->fh, -22 - $x, SEEK_END);

			$signature = fread($this->fh, 4);

			if ($signature == "\x50\x4b\x05\x06")
			{
				// If found EOF Central Dir
				$eodir['disk_number_this'] = unpack("v", fread($this->fh, 2)); // number of this disk
				$eodir['disk_number'] = unpack("v", fread($this->fh, 2)); // number of the disk with the start of the central directory
				$eodir['total_entries_this'] = unpack("v", fread($this->fh, 2)); // total number of entries in the central dir on this disk
				$eodir['total_entries'] = unpack("v", fread($this->fh, 2)); // total number of entries in
				$eodir['size_of_cd'] = unpack("V", fread($this->fh, 4)); // size of the central directory
				$eodir['offset_start_cd'] = unpack("V", fread($this->fh, 4)); // offset of start of central directory with respect to the starting disk number
				$zip_comment_lenght = unpack("v", fread($this->fh, 2)); // zipfile comment length
				$eodir['zipfile_comment'] = $zip_comment_lenght[1] ? fread($this->fh, $zip_comment_lenght[1]) : ''; // zipfile comment

				$this->end_of_central = array(
					'disk_number_this' => $eodir['disk_number_this'][1],
					'disk_number' => $eodir['disk_number'][1],
					'total_entries_this' => $eodir['total_entries_this'][1],
					'total_entries' => $eodir['total_entries'][1],
					'size_of_cd' => $eodir['size_of_cd'][1],
					'offset_start_cd' => $eodir['offset_start_cd'][1],
					'zipfile_comment' => $eodir['zipfile_comment'],
				);

				// Then, load file list
				fseek($this->fh, $this->end_of_central['offset_start_cd']);
				$signature = fread($this->fh, 4);

				while ($signature == "\x50\x4b\x01\x02")
				{
					$dir['version_madeby'] = unpack("v", fread($this->fh, 2)); // version made by
					$dir['version_needed'] = unpack("v", fread($this->fh, 2)); // version needed to extract
					$dir['general_bit_flag'] = unpack("v", fread($this->fh, 2)); // general purpose bit flag
					$dir['compression_method'] = unpack("v", fread($this->fh, 2)); // compression method
					$dir['lastmod_time'] = unpack("v", fread($this->fh, 2)); // last mod file time
					$dir['lastmod_date'] = unpack("v", fread($this->fh, 2)); // last mod file date
					$dir['crc-32'] = fread($this->fh, 4);			  // crc-32
					$dir['compressed_size'] = unpack("V", fread($this->fh, 4)); // compressed size
					$dir['uncompressed_size'] = unpack("V", fread($this->fh, 4)); // uncompressed size
					$zip_file_length = unpack("v", fread($this->fh, 2)); // filename length
					$extra_field_length = unpack("v", fread($this->fh, 2)); // extra field length
					$fileCommentLength = unpack("v", fread($this->fh, 2)); // file comment length
					$dir['disk_number_start'] = unpack("v", fread($this->fh, 2)); // disk number start
					$dir['internal_attributes'] = unpack("v", fread($this->fh, 2)); // internal file attributes-byte1
					$dir['external_attributes1'] = unpack("v", fread($this->fh, 2)); // external file attributes-byte2
					$dir['external_attributes2'] = unpack("v", fread($this->fh, 2)); // external file attributes
					$dir['relative_offset'] = unpack("V", fread($this->fh, 4)); // relative offset of local header
					$dir['file_name'] = fread($this->fh, $zip_file_length[1]);							 // filename
					$dir['extra_field'] = $extra_field_length[1] ? fread($this->fh, $extra_field_length[1]) : ''; // extra field
					$dir['file_comment'] = $fileCommentLength[1] ? fread($this->fh, $fileCommentLength[1]) : ''; // file comment

					// Convert the date and time, from MS-DOS format to UNIX Timestamp
					$binary_mod_date = str_pad(decbin($dir['lastmod_date'][1]), 16, '0', STR_PAD_LEFT);
					$binary_mod_time = str_pad(decbin($dir['lastmod_time'][1]), 16, '0', STR_PAD_LEFT);
					$last_mod_year = bindec(substr($binary_mod_date, 0, 7)) + 1980;
					$last_mod_month = bindec(substr($binary_mod_date, 7, 4));
					$last_mod_day = bindec(substr($binary_mod_date, 11, 5));
					$last_mod_hour = bindec(substr($binary_mod_time, 0, 5));
					$last_mod_minute = bindec(substr($binary_mod_time, 5, 6));
					$last_mod_second = bindec(substr($binary_mod_time, 11, 5));

					$this->central_dir_list[$dir['file_name']] = array(
						'version_madeby' => $dir['version_madeby'][1],
						'version_needed' => $dir['version_needed'][1],
						'general_bit_flag' => str_pad(decbin($dir['general_bit_flag'][1]), 8, '0', STR_PAD_LEFT),
						'compression_method' => $dir['compression_method'][1],
						'lastmod_datetime' => mktime($last_mod_hour, $last_mod_minute, $last_mod_second, $last_mod_month, $last_mod_day, $last_mod_year),
						'crc-32' => str_pad(dechex(ord($dir['crc-32'][3])), 2, '0', STR_PAD_LEFT) .
						str_pad(dechex(ord($dir['crc-32'][2])), 2, '0', STR_PAD_LEFT) .
						str_pad(dechex(ord($dir['crc-32'][1])), 2, '0', STR_PAD_LEFT) .
						str_pad(dechex(ord($dir['crc-32'][0])), 2, '0', STR_PAD_LEFT),
						'compressed_size' => $dir['compressed_size'][1],
						'uncompressed_size' => $dir['uncompressed_size'][1],
						'disk_number_start' => $dir['disk_number_start'][1],
						'internal_attributes' => $dir['internal_attributes'][1],
						'external_attributes1' => $dir['external_attributes1'][1],
						'external_attributes2' => $dir['external_attributes2'][1],
						'relative_offset' => $dir['relative_offset'][1],
						'file_name' => $dir['file_name'],
						'extra_field' => $dir['extra_field'],
						'file_comment' => $dir['file_comment'],
					);

					$signature = fread($this->fh, 4);
				}

				// If loaded centralDirs, then try to identify the offsetPosition of the compressed data.
				if ($this->central_dir_list)
				{
					foreach ($this->central_dir_list as $filename => $details)
					{
						$i = $this->get_file_header($details['relative_offset']);
						
						$this->compressed_list[$filename]['file_name'] = $filename;
						$this->compressed_list[$filename]['compression_method'] = $details['compression_method'];
						$this->compressed_list[$filename]['version_needed'] = $details['version_needed'];
						$this->compressed_list[$filename]['lastmod_datetime'] = $details['lastmod_datetime'];
						$this->compressed_list[$filename]['crc-32'] = $details['crc-32'];
						$this->compressed_list[$filename]['compressed_size'] = $details['compressed_size'];
						$this->compressed_list[$filename]['uncompressed_size'] = $details['uncompressed_size'];
						$this->compressed_list[$filename]['lastmod_datetime'] = $details['lastmod_datetime'];
						$this->compressed_list[$filename]['extra_field'] = $i['extra_field'];
						$this->compressed_list[$filename]['contents_start_offset'] = $i['contents_start_offset'];
					}
				}

				return TRUE;
			}
		}
		return FALSE;
	}

	private function load_files_by_signatures ()
	{
		fseek($this->fh, 0);

		$return = FALSE;
		for (;;)
		{
			$details = $this->get_file_header();

			if ( ! $details)
			{
				$this->set_debug('Invalid signature. Trying to verify if is old style Data Descriptor...');
				fseek($this->fh, 12 - 4, SEEK_CUR); // 12: Data descriptor - 4: Signature (that will be read again)
				$details = $this->get_file_header();
			}

			if ( ! $details)
			{
				$this->set_debug('Still invalid signature. Probably reached the end of the file.');
				break;
			}

			$filename = $details['file_name'];
			$this->compressed_list[$filename] = $details;
			$return = true;
		}

		return $return;
	}

	private function get_file_header ($start_offset = FALSE)
	{
		if ($start_offset !== FALSE)
		{
			fseek($this->fh, $start_offset);
		}

		$signature = fread($this->fh, 4);

		if ($signature == "\x50\x4b\x03\x04")
		{
			// Get information about the zipped file
			$file['version_needed'] = unpack("v", fread($this->fh, 2)); // version needed to extract
			$file['general_bit_flag'] = unpack("v", fread($this->fh, 2)); // general purpose bit flag
			$file['compression_method'] = unpack("v", fread($this->fh, 2)); // compression method
			$file['lastmod_time'] = unpack("v", fread($this->fh, 2)); // last mod file time
			$file['lastmod_date'] = unpack("v", fread($this->fh, 2)); // last mod file date
			$file['crc-32'] = fread($this->fh, 4);			  // crc-32
			$file['compressed_size'] = unpack("V", fread($this->fh, 4)); // compressed size
			$file['uncompressed_size'] = unpack("V", fread($this->fh, 4)); // uncompressed size
			$zip_file_length = unpack("v", fread($this->fh, 2)); // filename length
			$extra_field_length = unpack("v", fread($this->fh, 2)); // extra field length
			$file['file_name'] = fread($this->fh, $zip_file_length[1]); // filename
			$file['extra_field'] = $extra_field_length[1] ? fread($this->fh, $extra_field_length[1]) : ''; // extra field
			$file['contents_start_offset'] = ftell($this->fh);

			// Bypass the whole compressed contents, and look for the next file
			fseek($this->fh, $file['compressed_size'][1], SEEK_CUR);

			// Convert the date and time, from MS-DOS format to UNIX Timestamp
			$binary_mod_date = str_pad(decbin($file['lastmod_date'][1]), 16, '0', STR_PAD_LEFT);
			$binary_mod_time = str_pad(decbin($file['lastmod_time'][1]), 16, '0', STR_PAD_LEFT);

			$last_mod_year = bindec(substr($binary_mod_date, 0, 7)) + 1980;
			$last_mod_month = bindec(substr($binary_mod_date, 7, 4));
			$last_mod_day = bindec(substr($binary_mod_date, 11, 5));
			$last_mod_hour = bindec(substr($binary_mod_time, 0, 5));
			$last_mod_minute = bindec(substr($binary_mod_time, 5, 6));
			$last_mod_second = bindec(substr($binary_mod_time, 11, 5));

			// Mount file table
			$i = array(
				'file_name' => $file['file_name'],
				'compression_method' => $file['compression_method'][1],
				'version_needed' => $file['version_needed'][1],
				'lastmod_datetime' => mktime($last_mod_hour, $last_mod_minute, $last_mod_second, $last_mod_month, $last_mod_day, $last_mod_year),
				'crc-32' => str_pad(dechex(ord($file['crc-32'][3])), 2, '0', STR_PAD_LEFT) .
				str_pad(dechex(ord($file['crc-32'][2])), 2, '0', STR_PAD_LEFT) .
				str_pad(dechex(ord($file['crc-32'][1])), 2, '0', STR_PAD_LEFT) .
				str_pad(dechex(ord($file['crc-32'][0])), 2, '0', STR_PAD_LEFT),
				'compressed_size' => $file['compressed_size'][1],
				'uncompressed_size' => $file['uncompressed_size'][1],
				'extra_field' => $file['extra_field'],
				'general_bit_flag' => str_pad(decbin($file['general_bit_flag'][1]), 8, '0', STR_PAD_LEFT),
				'contents_start_offset' => $file['contents_start_offset']
			);

			return $i;
		}

		return FALSE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Determine whether the file extension is allowed or not.
	 *
	 * @access    private
	 * @param     string, mixed
	 * @return    bool
	 */
	private function allowed ($file, $extensions)
	{
		if (empty($extensions)) return true;
		$check = pathinfo($file, PATHINFO_EXTENSION);
		return (is_array($extensions)) ? in_array($check, $extensions) : strpos("||{$extensions}||", "|{$check}|");
	}
	
	// --------------------------------------------------------------------

	/**
	 * Create the directory structure.
	 *
	 * @access    private
	 * @param     string
	 * @return    none
	 */
	private function mkpath ($dir)
	{
		$path = $this->dir . $dir;
		if ( ! is_dir($path))
		{
			$str = '';
			foreach (explode('/', $dir) as $folder)
			{
				$str = $str ? $str . '/' . $folder : $folder;
				if ( ! is_dir($this->dir . $str))
				{
					$this->set_debug('Creating folder: ' . $this->dir . $str);
					if ( ! @mkdir($this->dir . $str, $this->chmod))
					{
						$this->set_error('Destination path is not writable.');
						return FALSE;
					}
				}
			}
		}
	}
	
}

/* End of file Unzip.php */
/* Location: ./application/libraries/Unzip.php */