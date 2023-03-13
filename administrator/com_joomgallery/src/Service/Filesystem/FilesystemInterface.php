<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem;

\defined('_JEXEC') or die;

/**
* Filesystem service interface
*
* @since  4.0.0
*/
interface FilesystemInterface
{
  /**
   * Function to strip additional / or \ in a path name.
   *
   * @param   string  $path   The path to clean
   * @param   string  $ds     Directory separator (optional)
   *
   * @return  string  The cleaned path
   *
   * @since   4.0.0
  */
  public function cleanPath(string $path, string $ds=\DIRECTORY_SEPARATOR): string;

  /**
   * Cleaning of file/category name
   * optionally replace extension if present
   * replace special chars defined in the configuration
   *
   * @param   string    $file            The file name
   * @param   integer   $with_ext        0: strip extension, 1: force extension, 2: leave it as it is (default: 2)
   * @param   string    $use_ext         Extension to use if $file given without extension
   * @param   string    $replace_chars   Characters to be replaced
   *
   * @return  mixed     cleaned name on success, false otherwise
   *
   * @since   4.0.0
  */
  public function cleanFilename(string $file, int $with_ext=2, string $use_ext='jpg', string $replace_chars='');

  /**
   * Check filename if it's valid for the filesystem
   *
   * @param   string    $nameb          filename before any processing
   * @param   string    $namea          filename after processing in e.g. fixFilename
   * @param   bool      $checkspecial   True if the filename shall be checked for special characters only
   *
   * @return  bool      True if the filename is valid, false otherwise
   *
   * @since   4.0.0
  */
  public function checkFilename(string $nameb, string $namea = '', bool $checkspecial = false): bool;

  /**
   * Sets the permission of a given file or folder recursively.
   *
   * @param   string  $path      The path to the file/folder
   * @param   string  $val       The octal representation of the value to change file/folder mode
   * @param   bool    $mode      True to use file mode. False to use folder mode. (default: true)
   *
   * @return  bool    True if successful [one fail means the whole operation failed].
   *
   * @since   4.0.0
   */
  public function chmod(string $path, string $val, bool $mode=true): bool;

  /**
   * Copies an index.html file into a specified folder
   *
   * @param   string   $path    The path where the index.html should be created
   * 
   * @return  string
   * 
   * @since   4.0.0
   * @throws  \Exception
   */
  public function createIndexHtml(string $path): string;
}
