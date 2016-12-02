<?php

namespace BootPress\Unzip;

use BootPress\Page\Component as Page;

/**
 * This component is based on the code at:
 * https://github.com/philsturgeon/codeigniter-unzip
 *
 * Requires extension ZLib Enabled.
 */
class Component
{
    private $fh;
    private $dir;
    private $chmod;
    private $errors = array();
    private $compressed_list = array();
    private $central_dir_list = array();
    private $end_of_central = array();

    /**
     * Establish the file to be unzipped, and where.
     *
     * @param string $zip_file
     * @param string $target_dir  Defaults to the directory that contains the $zip_file.
     * @param int    $chmod
     */
    public function __construct($zip_file, $target_dir = null, $chmod = 0755)
    {
        if ($this->fh = fopen($zip_file, 'r')) {
            if (!$this->loadFileListByEof()) {
                $this->loadFilesBySignatures();
            }
        }
        $this->dir = $target_dir ? $target_dir : dirname($zip_file);
        $this->dir = rtrim(str_replace('\\', '/', $this->dir), '/').'/';
        $this->chmod = $chmod;
    }

    /**
     * Free the file resource automatically.
     *
     * @param     none
     *
     * @return none
     */
    public function __destroy()
    {
        $this->close();
    }

    /**
     * Retrieve all of the zipped files FYI.  Used internally as needed.
     *
     * @return array
     */
    public function files()
    {
        $files = array_keys($this->compressed_list);
        sort($files);

        return $files;
    }

    /**
     * Unzip allowable files in archive.
     *
     * @param string|array $allow_extensions   Either an arrary, or a pipe-delimited string of acceptable file extensions to extract.
     * @param bool         $remove_common_dir  Whether to remove a common directory (if any), or not.
     *
     * @return array  All of the extracted file locations.
     */
    public function extract($allow_extensions = null, $remove_common_dir = false)
    {
        $locations = array();
        $files = $this->files();
        $start = $remove_common_dir ? mb_strlen(Page::html()->commonDir($files)) : 0;
        foreach ($files as $file) {
            $path = substr($file, $start);
            if (empty($path) || substr($file, -1, 1) == '/') {
                continue;
            }
            if (!$this->allowed($path, $allow_extensions)) {
                continue;
            }
            $locations[$path] = $this->dir.$path;
            $this->mkpath(pathinfo($path, PATHINFO_DIRNAME));
            $this->extractFile($file, $this->dir.$path);
        }

        return $locations;
    }

    /**
     * Unzip all files from specified folders in archive.
     *
     * @param string|array $folders           The folder(s) to extract files from.  Do not include a trailing slash.
     * @param string|array $allow_extensions  Either an arrary, or a pipe-delimited string of acceptable file extensions to extract.
     *
     * @return array  All of the extracted file locations.
     */
    public function extractFolders($folders, $allow_extensions = null)
    {
        $file_locations = array();
        foreach ((array) $folders as $folder) {
            foreach ($this->files() as $file) {
                if (strpos($file, $folder.'/') !== 0) {
                    continue;
                }
                if (!$this->allowed($file, $allow_extensions)) {
                    continue;
                }
                $file_locations[$folder][substr($file, strlen($folder) + 1)] = $file_location = $this->dir.$file;
                $this->mkpath(pathinfo($file, PATHINFO_DIRNAME));
                $this->extractFile($file, $file_location);
            }
        }

        return $file_locations;
    }

    /**
     * Unzip specified files in archive.
     *
     * @param string|array $files  The file(s) to extract.
     *
     * @return array  All of the extracted file locations.
     */
    public function extractFiles($files)
    {
        $file_locations = array();
        foreach ((array) $files as $extract) {
            foreach ($this->files() as $file) {
                if ($extract != $file) {
                    continue;
                }
                $file_locations[$file] = $file_location = $this->dir.$file;
                $this->mkpath(pathinfo($file, PATHINFO_DIRNAME));
                $this->extractFile($file, $file_location);
                break;
            }
        }

        return $file_locations;
    }

    /**
     * Frees the file resource, and returns any errors generated.
     *
     * return array
     */
    public function close()
    {
        if ($this->fh) {
            fclose($this->fh);
        }

        return $this->errors;
    }

    /**
     * Sets an error message in case you are interested.
     *
     * @param string $error
     *
     * @return false
     */
    private function setError($string)
    {
        $this->errors[] = $string;

        return false;
    }

    /**
     * Unzip file in archive.
     *
     * @param string, boolean
     *
     * @return Unziped file
     */
    private function extractFile($compressed_file_name, $target_file_name)
    {
        $fdetails = &$this->compressed_list[$compressed_file_name];

        if (!isset($this->compressed_list[$compressed_file_name])) {
            return $this->setError('The '.$compressed_file_name.' is not compressed in the zip.');
        }

        if (substr($compressed_file_name, -1) == '/') {
            return $this->setError('Trying to unzip a folder: '.$compressed_file_name);
        }

        if (!$fdetails['uncompressed_size']) { // File is empty
            return $target_file_name ? file_put_contents($target_file_name, '') : '';
        }
        
        if (is_file($target_file_name) && $fdetails['lastmod_datetime'] <= filemtime($target_file_name)) {
            return ''; // The file already exists, and we'll stick with the most recent version thank you.
        }

        fseek($this->fh, $fdetails['contents_start_offset']);
        $ret = $this->uncompress(
            fread($this->fh, $fdetails['compressed_size']),
            $fdetails['compression_method'],
            $fdetails['uncompressed_size'],
            $target_file_name
        );

        chmod($target_file_name, $this->chmod);
        
        return $ret;
    }

    /**
     * Uncompress file, and save it to the target file.
     *
     * @param file content, int, int, boolean
     */
    private function uncompress($content, $mode, $uncompressed_size, $target_file_name)
    {
        switch ($mode) {
            case 0:
                return ($target_file_name) ? file_put_contents($target_file_name, $content) : $content;
            case 1:
                return $this->setError('Shrunk mode is not supported... yet?');
            case 2:
            case 3:
            case 4:
            case 5:
                return $this->setError('Compression factor '.($mode - 1).' is not supported... yet?');
            case 6:
                return $this->setError('Implode is not supported... yet?');
            case 7:
                return $this->setError('Tokenizing compression algorithm is not supported... yet?');
            case 8: // Deflate
                return ($target_file_name) ? file_put_contents($target_file_name, gzinflate($content, $uncompressed_size)) : gzinflate($content, $uncompressed_size);
            case 9:
                return $this->setError('Enhanced Deflating is not supported... yet?');
            case 10:
                return $this->setError('PKWARE Date Compression Library Impoloding is not supported... yet?');
            case 12: // Bzip2
                return ($target_file_name) ? file_put_contents($target_file_name, bzdecompress($content)) : bzdecompress($content);
            case 18:
                return $this->setError('IBM TERSE is not supported... yet?');
            default:
                return $this->setError('Unknown uncompress method: '.$mode);
        }
    }

    private function loadFileListByEof()
    {
        // Check if there's a valid Central Dir signature.
        // Let's consider a file comment smaller than 1024 characters...
        // Actually, it length can be 65536.. But we're not going to support it.

        for ($x = 0; $x < 1024; ++$x) {
            fseek($this->fh, -22 - $x, SEEK_END);

            $signature = fread($this->fh, 4);

            if ($signature == "\x50\x4b\x05\x06") {
                // If found EOF Central Dir
                $eodir['disk_number_this'] = unpack('v', fread($this->fh, 2)); // number of this disk
                $eodir['disk_number'] = unpack('v', fread($this->fh, 2)); // number of the disk with the start of the central directory
                $eodir['total_entries_this'] = unpack('v', fread($this->fh, 2)); // total number of entries in the central dir on this disk
                $eodir['total_entries'] = unpack('v', fread($this->fh, 2)); // total number of entries in
                $eodir['size_of_cd'] = unpack('V', fread($this->fh, 4)); // size of the central directory
                $eodir['offset_start_cd'] = unpack('V', fread($this->fh, 4)); // offset of start of central directory with respect to the starting disk number
                $zip_comment_lenght = unpack('v', fread($this->fh, 2)); // zipfile comment length
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

                while ($signature == "\x50\x4b\x01\x02") {
                    $dir['version_madeby'] = unpack('v', fread($this->fh, 2)); // version made by
                    $dir['version_needed'] = unpack('v', fread($this->fh, 2)); // version needed to extract
                    $dir['general_bit_flag'] = unpack('v', fread($this->fh, 2)); // general purpose bit flag
                    $dir['compression_method'] = unpack('v', fread($this->fh, 2)); // compression method
                    $dir['lastmod_time'] = unpack('v', fread($this->fh, 2)); // last mod file time
                    $dir['lastmod_date'] = unpack('v', fread($this->fh, 2)); // last mod file date
                    $dir['crc-32'] = fread($this->fh, 4);             // crc-32
                    $dir['compressed_size'] = unpack('V', fread($this->fh, 4)); // compressed size
                    $dir['uncompressed_size'] = unpack('V', fread($this->fh, 4)); // uncompressed size
                    $zip_file_length = unpack('v', fread($this->fh, 2)); // filename length
                    $extra_field_length = unpack('v', fread($this->fh, 2)); // extra field length
                    $fileCommentLength = unpack('v', fread($this->fh, 2)); // file comment length
                    $dir['disk_number_start'] = unpack('v', fread($this->fh, 2)); // disk number start
                    $dir['internal_attributes'] = unpack('v', fread($this->fh, 2)); // internal file attributes-byte1
                    $dir['external_attributes1'] = unpack('v', fread($this->fh, 2)); // external file attributes-byte2
                    $dir['external_attributes2'] = unpack('v', fread($this->fh, 2)); // external file attributes
                    $dir['relative_offset'] = unpack('V', fread($this->fh, 4)); // relative offset of local header
                    $dir['file_name'] = fread($this->fh, $zip_file_length[1]);                           // filename
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
                        'crc-32' => str_pad(dechex(ord($dir['crc-32'][3])), 2, '0', STR_PAD_LEFT).
                        str_pad(dechex(ord($dir['crc-32'][2])), 2, '0', STR_PAD_LEFT).
                        str_pad(dechex(ord($dir['crc-32'][1])), 2, '0', STR_PAD_LEFT).
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
                if ($this->central_dir_list) {
                    foreach ($this->central_dir_list as $filename => $details) {
                        $i = $this->getFileHeader($details['relative_offset']);

                        $this->compressed_list[$filename]['file_name'] = $filename;
                        $this->compressed_list[$filename]['lastmod_datetime'] = $details['lastmod_datetime'];
                        $this->compressed_list[$filename]['uncompressed_size'] = $details['uncompressed_size'];
                        $this->compressed_list[$filename]['compressed_size'] = $details['compressed_size'];
                        $this->compressed_list[$filename]['compression_method'] = $details['compression_method'];
                        $this->compressed_list[$filename]['contents_start_offset'] = $i['contents_start_offset'];
                        $this->compressed_list[$filename]['version_needed'] = $details['version_needed'];
                        $this->compressed_list[$filename]['crc-32'] = $details['crc-32'];
                        $this->compressed_list[$filename]['extra_field'] = $i['extra_field'];
                    }
                }

                return true;
            }
        }

        return false;
    }

    private function loadFilesBySignatures()
    {
        $return = false;
        fseek($this->fh, 0);
        for (;;) {
            $details = $this->getFileHeader();

            if (!$details) { // Invalid signature. Trying to verify if is old style Data Descriptor...
                fseek($this->fh, 12 - 4, SEEK_CUR); // 12: Data descriptor - 4: Signature (that will be read again)
                $details = $this->getFileHeader();
            }

            if (!$details) { // Still invalid signature. Probably reached the end of the file.
                break;
            }

            $filename = $details['file_name'];
            $this->compressed_list[$filename] = $details;
            $return = true;
        }

        return $return;
    }

    private function getFileHeader($start_offset = false)
    {
        if ($start_offset !== false) {
            fseek($this->fh, $start_offset);
        }

        $signature = fread($this->fh, 4);

        if ($signature == "\x50\x4b\x03\x04") {
            // Get information about the zipped file
            $file['version_needed'] = unpack('v', fread($this->fh, 2)); // version needed to extract
            $file['general_bit_flag'] = unpack('v', fread($this->fh, 2)); // general purpose bit flag
            $file['compression_method'] = unpack('v', fread($this->fh, 2)); // compression method
            $file['lastmod_time'] = unpack('v', fread($this->fh, 2)); // last mod file time
            $file['lastmod_date'] = unpack('v', fread($this->fh, 2)); // last mod file date
            $file['crc-32'] = fread($this->fh, 4);            // crc-32
            $file['compressed_size'] = unpack('V', fread($this->fh, 4)); // compressed size
            $file['uncompressed_size'] = unpack('V', fread($this->fh, 4)); // uncompressed size
            $zip_file_length = unpack('v', fread($this->fh, 2)); // filename length
            $extra_field_length = unpack('v', fread($this->fh, 2)); // extra field length
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
                'crc-32' => str_pad(dechex(ord($file['crc-32'][3])), 2, '0', STR_PAD_LEFT).
                str_pad(dechex(ord($file['crc-32'][2])), 2, '0', STR_PAD_LEFT).
                str_pad(dechex(ord($file['crc-32'][1])), 2, '0', STR_PAD_LEFT).
                str_pad(dechex(ord($file['crc-32'][0])), 2, '0', STR_PAD_LEFT),
                'compressed_size' => $file['compressed_size'][1],
                'uncompressed_size' => $file['uncompressed_size'][1],
                'extra_field' => $file['extra_field'],
                'general_bit_flag' => str_pad(decbin($file['general_bit_flag'][1]), 8, '0', STR_PAD_LEFT),
                'contents_start_offset' => $file['contents_start_offset'],
            );

            return $i;
        }

        return false;
    }

    /**
     * Determine whether the file extension is allowed or not.
     *
     * @param string, string|array
     *
     * @return bool
     */
    private function allowed($file, $extensions)
    {
        if (empty($extensions)) {
            return true;
        }
        $check = pathinfo($file, PATHINFO_EXTENSION);

        return (is_array($extensions)) ? in_array($check, $extensions) : strpos("||{$extensions}||", "|{$check}|");
    }

    /**
     * Create the directory structure.
     *
     * @param string
     */
    private function mkpath($dir)
    {
        $path = $this->dir.$dir;
        if (!is_dir($path)) {
            $str = '';
            foreach (explode('/', $dir) as $folder) {
                $str = $str ? $str.'/'.$folder : $folder;
                if (!is_dir($this->dir.$str)) {
                    if (!@mkdir($this->dir.$str, $this->chmod)) {
                        return $this->setError('Destination path is not writable.');
                    }
                }
            }
        }
    }
}
