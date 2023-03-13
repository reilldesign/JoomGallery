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

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Object\CMSObject;
use \Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\Filesystem\File as JFile;
use \Joomla\CMS\Filesystem\Path as JPath;

use \Joomla\Component\Media\Administrator\Adapter\AdapterInterface;
use \Joomla\Component\Media\Administrator\Event\FetchMediaItemEvent;
use \Joomla\Component\Media\Administrator\Event\FetchMediaItemsEvent;
use \Joomla\Component\Media\Administrator\Event\FetchMediaItemUrlEvent;
use \Joomla\Component\Media\Administrator\Exception\FileExistsException;
use \Joomla\Component\Media\Administrator\Exception\FileNotFoundException;
use \Joomla\Component\Media\Administrator\Exception\InvalidPathException;
use \Joomla\Component\Media\Administrator\Provider\ProviderManagerHelperTrait;

use \Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem\FilesystemInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;

/**
* Filesystem Base Class
*
* @package JoomGallery
*
* @since  4.0.0
*/
class Filesystem implements AdapterInterface, FilesystemInterface
{
  use ServiceTrait;
  use ProviderManagerHelperTrait;

  /**
   * The adapter name.
   * Scheme: adapter-rootfolder
   *
   * @var   string
   * @since  4.0.0
   */
  protected $filesystem = 'local-images';

  /**
   * The available extensions.
   *
   * @var   string[]
   * @since  4.0.0
   */
  private $allowedExtensions = null;

  /**
   * Root folder of the local filesystem
   *
   * @var string
   */
  protected $local_root = JPATH_ROOT;

  /**
   * Constructor
   *
   * @param  string  $filesystem  Name of the filesystem to use
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct(string $filesystem = '')
  {  
    // Load component
    $this->getComponent();

    // instantiate config service
    $this->component->createConfig();

    if($filesystem != '')
    {
      $this->filesystem = $filesystem;
    }
  }

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
  public function cleanPath(string $path, string $ds=\DIRECTORY_SEPARATOR): string
  {
    return JPath::clean($path, $ds);
  }

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
  public function cleanFilename(string $file, int $with_ext=2, string $use_ext='jpg', string $replace_chars='')
  {
    // Check if multibyte support installed
    if(\in_array ('mbstring', \get_loaded_extensions()))
    {
      // Get the funcs from mb
      $funcs = \get_extension_funcs('mbstring');
      if(\in_array ('mb_detect_encoding', $funcs) && \in_array ('mb_strtolower', $funcs))
      {
        // Try to check if the name contains UTF-8 characters
        $isUTF = \mb_detect_encoding($file, 'UTF-8', true);
        if($isUTF)
        {
          // Try to lower the UTF-8 characters
          $file = \mb_strtolower($file, 'UTF-8');
        }
        else
        {
          // Try to lower the one byte characters
          $file = \strtolower($file);
        }
      }
      else
      {
        // TODO mbstring loaded but no needed functions
        // --> server misconfiguration
        $file = \strtolower($file);
      }
    }
    else
    {
      // TODO no mbstring loaded, appropriate server for Joomla?
      $file = \strtolower($file);
    }

    // Replace special chars
    $filenamesearch  = array();
    $filenamereplace = array();

    $items = \explode(',', $replace_chars);
    if($items != false)
    {
      // Contains pairs of <specialchar>|<replaced char(s)>
      foreach($items as $item)
      {
        if(!empty($item))
        {
          $workarray = \explode('|', \trim($item));
          if($workarray != false && isset($workarray[0]) && !empty($workarray[0]) && isset($workarray[1]) && !empty($workarray[1]))
          {
            \array_push($filenamesearch, \preg_quote($workarray[0]));
            \array_push($filenamereplace, \preg_quote($workarray[1]));
          }
        }
      }
    }

    // Replace whitespace with underscore
    \array_push($filenamesearch, '\s');
    \array_push($filenamereplace, '_');
    // Replace slash with underscore
    \array_push($filenamesearch, '/');
    \array_push($filenamereplace, '_');
    // Replace backslash with underscore
    \array_push($filenamesearch, '\\\\');
    \array_push($filenamereplace, '_');
    // Replace other stuff
    \array_push($filenamesearch, '[^a-z_0-9-]');
    \array_push($filenamereplace, '');

    // Checks for different array-length
    $lengthsearch  = \count($filenamesearch);
    $lengthreplace = \count($filenamereplace);
    if($lengthsearch > $lengthreplace)
    {
      while($lengthsearch > $lengthreplace)
      {
        \array_push($filenamereplace, '');
        $lengthreplace = $lengthreplace + 1;
      }
    }
    else
    {
      if($lengthreplace > $lengthsearch)
      {
        while($lengthreplace > $lengthsearch)
        {
          \array_push($filenamesearch, '');
          $lengthsearch = $lengthsearch + 1;
        }
      }
    }

    $detect_ext = JFile::getExt($file);

    // Replace extension if present
    if($detect_ext)
    {
      $fileextensionlength  = \strlen($detect_ext);
      $filenamelength       = \strlen($file);
      $filename             = \substr($file, -$filenamelength, -$fileextensionlength - 1);
    }
    else
    {
      // No extension found (Batchupload)
      $filename = $file;
    }

    // Perform the replace
    for($i = 0; $i < $lengthreplace; $i++)
    {
      $searchstring = '!'.$filenamesearch[$i].'+!i';
      $filename     = \preg_replace($searchstring, $filenamereplace[$i], $filename);
    }

    switch($with_ext)
    {
      case 0:
        // strip extension
        break;

      case 1:
        // add extension
        if($detect_ext)
        {
          $filename = $filename.'.'. \strtolower($detect_ext);
        }
        else
        {
          $filename = $filename.'.'. \strtolower($use_ext);
        }
        break;
      
      default:
        // leave it as it is
        if($detect_ext)
        {
          $filename = $filename.'.'. \strtolower($detect_ext);
        }
        break;
    }

    return $filename;
  }

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
  public function checkFilename(string $nameb, string $namea = '', bool $checkspecial = false): bool
  {
    // TODO delete this function and the call of them?
    // return true;

    // Check only for special characters
    if($checkspecial)
    {
      $pattern = '/[^0-9A-Za-z -_]/';
      $check = \preg_match($pattern, $nameb);
      if($check == 0)
      {
        // No special characters found
        return true;
      }
      else
      {
        return false;
      }
    }
    // Remove extension from names
    $nameb = JFile::stripExt($nameb);
    $namea = JFile::stripExt($namea);

    // Check the old filename for containing only underscores
    if(\strlen($nameb) - \substr_count($nameb, '_') == 0)
    {
      $nameb_onlyus = true;
    }
    else
    {
      $nameb_onlyus = false;
    }
    if(empty($namea) || (!$nameb_onlyus && strlen($namea) == substr_count($nameb, '_')))
    {
      return false;
    }
    else
    {
      return true;
    }
  }

  /**
   * Copies an index.html file into a specified folder
   *
   * @param   string   $path    The path where the index.html should be created
   * 
   * @return  string
   * 
   * @since   4.0.0
   */
  public function createIndexHtml(string $path): string
  {
    // Content
    $html = '<html><body bgcolor="#FFFFFF"></body></html>';

    return $this->createFile('index.html', $path, $html);
  }

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
  public function chmod(string $path, string $val, bool $mode=true): bool
  {
    // complete folder path
    $path = $this->completePath($path);

    if($mode)
    {
      return JPath::setPermissions(JPath::clean($path), $val, null);
    }
    else
    {
      return JPath::setPermissions(JPath::clean($path), null, $val);
    }
  }

  /**
   * Returns the requested file or folder information. More information
   * can be found in AdapterInterface::getFile().
   * 
   * @param   string     $path     The path to the file or folder
   * @param   array      $options  The options
   *
   * @return  \stdClass  Object with file information
   *
   * @since   4.0.0
   * @throws  \Exception
   * @see     AdapterInterface::getFile()
   */
  public function getFile(string $path = '/', array $options = []): \stdClass
  {
    $adapter = $this->getFilesystem();

    // Add adapter prefix to the file returned
    $file = $this->getAdapter($adapter)->getFile($path);

    // Check if it is an allowed file
    if($file->type == 'file' && !$this->isAllowedFile($file->path))
    {
        throw new InvalidPathException();
    }

    if(isset($options['url']) && $options['url'] && $file->type == 'file')
    {
        $file->url = $this->getUrl($adapter, $file->path);
    }

    if(isset($options['content']) && $options['content'] && $file->type == 'file')
    {
        $resource = $this->getAdapter($adapter)->getResource($file->path);

        if($resource)
        {
            $file->content = base64_encode(stream_get_contents($resource));
        }
    }

    $file->path    = $adapter . ":" . $file->path;
    $file->adapter = $adapter;

    $event = new FetchMediaItemEvent('onFetchMediaItem', ['item' => $file]);
    Factory::getApplication()->getDispatcher()->dispatch($event->getName(), $event);

    return $event->getArgument('item');
  }

  /**
   * Returns the folders and files for the given path. More information
   * can be found in AdapterInterface::getFiles().
   *
   * @param   string       $path     The folder
   * @param   array        $options  The options
   *
   * @return  \stdClass[]  List of objects with file information
   *
   * @since   4.0.0
   * @throws  \Exception
   * @see     AdapterInterface::getFiles()
   */
  public function getFiles(string $path = '/', array $options = []): array
  {
    $adapter = $this->getFilesystem();

    // Check whether user searching
    if(isset($options['search']) && $options['search'] != null)
    {
      // Do search
      $files = $this->search($adapter, $options['search'], $path, $options['recursive']);
    }
    else
    {
      // Grab files for the path
      $files = $this->getAdapter($adapter)->getFiles($path);
    }

    // Add adapter prefix to all the files to be returned
    foreach
    ($files as $key => $file)
    {
      // Check if the file is valid
      if($file->type == 'file' && !$this->isAllowedFile($file->path))
      {
        // Remove the file from the data
        unset($files[$key]);
        continue;
      }

      // Check if we need more information
      if(isset($options['url']) && $options['url'] && $file->type == 'file')
      {
        $file->url = $this->getUrl($adapter, $file->path);
      }

      if(isset($options['content']) && $options['content'] && $file->type == 'file')
      {
        $resource = $this->getAdapter($adapter)->getResource($file->path);

        if($resource)
        {
          $file->content = base64_encode(stream_get_contents($resource));
        }
      }

      $file->path    = $adapter . ":" . $file->path;
      $file->adapter = $adapter;
    }

    // Make proper indexes
    $files = array_values($files);

    $event = new FetchMediaItemsEvent('onFetchMediaItems', ['items' => $files]);
    Factory::getApplication()->getDispatcher()->dispatch($event->getName(), $event);

    return $event->getArgument('items');
  }

  /**
   * Creates a folder with the given name in the given path. More information
   * can be found in AdapterInterface::createFolder().
   *
   * @param   string   $name      The name
   * @param   string   $path      The folder
   * @param   boolean  $override  Should the folder being overridden when it exists (default: true)
   *
   * @return  string   The folder name
   *
   * @since   4.0.0
   * @throws  \Exception
   * @see     AdapterInterface::createFolder()
   */
  public function createFolder(string $name, string $path, bool $override = true): string
  {
    $adapter = $this->getFilesystem();

    try
    {
      $file = $this->getFile($path . '/' . $name);
    }
    catch (FileNotFoundException $e)
    {
      // Do nothing
    }

    // Check if the file exists
    if
    (isset($file) && !$override)
    {
      throw new FileExistsException();
    }

    $app               = Factory::getApplication();
    $object            = new CMSObject();
    $object->adapter   = $adapter;
    $object->name      = $name;
    $object->path      = $path;

    PluginHelper::importPlugin('content');

    $result = $app->triggerEvent('onContentBeforeSave', ['com_media.folder', $object, true, $object]);

    if(in_array(false, $result, true))
    {
      throw new \Exception($object->getError());
    }

    $object->name = $this->getAdapter($object->adapter)->createFolder($object->name, $object->path);

    $app->triggerEvent('onContentAfterSave', ['com_media.folder', $object, true, $object]);

    return $object->name;
  }

  /**
   * Creates a file with the given name in the given path with the data. More information
   * can be found in AdapterInterface::createFile().
   *
   * @param   string   $name      The name
   * @param   string   $path      The folder
   * @param   string   $data      The data
   * @param   boolean  $override  Should the file being overridden when it exists (default: true)
   *
   * @return  string   The filename
   *
   * @since   4.0.0
   * @throws  \Exception
   * @see     AdapterInterface::createFile()
   */
  public function createFile(string $name, string $path, $data, bool $override = true): string
  {
    $adapter = $this->getFilesystem();

    try
    {
      $file = $this->getFile($adapter, $path . '/' . $name);
    }
    catch (FileNotFoundException $e)
    {
      // Do nothing
    }

    // Check if the file exists
    if(isset($file) && !$override)
    {
      throw new FileExistsException();
    }

    // Check if it is an allowed file
    if(!$this->isAllowedFile($path . '/' . $name))
    {
      throw new InvalidPathException();
    }

    $app               = Factory::getApplication();
    $object            = new CMSObject();
    $object->adapter   = $adapter;
    $object->name      = $name;
    $object->path      = $path;
    $object->data      = $data;
    $object->extension = strtolower(JFile::getExt($name));

    PluginHelper::importPlugin('content');

    // Also include the filesystem plugins, perhaps they support batch processing too
    PluginHelper::importPlugin('media-action');

    $result = $app->triggerEvent('onContentBeforeSave', ['com_media.file', $object, true, $object]);

    if(in_array(false, $result, true))
    {
      throw new \Exception($object->getError());
    }

    $object->name = $this->getAdapter($object->adapter)->createFile($object->name, $object->path, $object->data);

    $app->triggerEvent('onContentAfterSave', ['com_media.file', $object, true, $object]);

    return $object->name;
  }

  /**
   * Updates the file with the given name in the given path with the data. More information
   * can be found in AdapterInterface::updateFile().
   *
   * @param   string  $name     The name
   * @param   string  $path     The folder
   * @param   string  $data     The data
   *
   * @return  void
   *
   * @since   4.0.0
   * @throws  \Exception
   * @see     AdapterInterface::updateFile()
   */
  public function updateFile(string $name, string $path, $data)
  {
    $adapter = $this->getFilesystem();

    // Check if it is an allowed file
    if(!$this->isAllowedFile($path . '/' . $name))
    {
      throw new InvalidPathException();
    }

    $app               = Factory::getApplication();
    $object            = new CMSObject();
    $object->adapter   = $adapter;
    $object->name      = $name;
    $object->path      = $path;
    $object->data      = $data;
    $object->extension = strtolower(JFile::getExt($name));

    PluginHelper::importPlugin('content');

    // Also include the filesystem plugins, perhaps they support batch processing too
    PluginHelper::importPlugin('media-action');

    $result = $app->triggerEvent('onContentBeforeSave', ['com_media.file', $object, false, $object]);

    if(in_array(false, $result, true))
    {
      throw new \Exception($object->getError());
    }

    $this->getAdapter($object->adapter)->updateFile($object->name, $object->path, $object->data);

    $app->triggerEvent('onContentAfterSave', ['com_media.file', $object, false, $object]);
  }

  /**
   * Deletes the folder or file of the given path. More information
   * can be found in AdapterInterface::delete().
   *
   * @param   string  $path     The path to the file or folder
   *
   * @return  void
   *
   * @since   4.0.0
   * @throws  \Exception
   * @see     AdapterInterface::delete()
   */
  public function delete(string $path)
  {
    $adapter = $this->getFilesystem();

    $file = $this->getFile($adapter, $path);

    // Check if it is an allowed file
    if($file->type == 'file' && !$this->isAllowedFile($file->path))
    {
      throw new InvalidPathException();
    }

    $type              = $file->type === 'file' ? 'file' : 'folder';
    $app               = Factory::getApplication();
    $object            = new CMSObject();
    $object->adapter   = $adapter;
    $object->path      = $path;

    PluginHelper::importPlugin('content');

    // Also include the filesystem plugins, perhaps they support batch processing too
    PluginHelper::importPlugin('media-action');

    $result = $app->triggerEvent('onContentBeforeDelete', ['com_media.' . $type, $object]);

    if(in_array(false, $result, true))
    {
      throw new \Exception($object->getError());
    }

    $this->getAdapter($object->adapter)->delete($object->path);

    $app->triggerEvent('onContentAfterDelete', ['com_media.' . $type, $object]);
  }

  /**
   * Copies file or folder from source path to destination path
   * If forced, existing files/folders would be overwritten
   *
   * @param   string  $sourcePath       Source path of the file or folder (relative)
   * @param   string  $destinationPath  Destination path(relative)
   * @param   bool    $force            Force to overwrite
   *
   * @return  string
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  public function copy(string $sourcePath, string $destinationPath, bool $force = false): string
  {
    $adapter = $this->getFilesystem();

    return $this->getAdapter($adapter)->copy($sourcePath, $destinationPath, $force);
  }

  /**
   * Moves file or folder from source path to destination path
   * If forced, existing files/folders would be overwritten
   *
   * @param   string  $sourcePath       Source path of the file or folder (relative)
   * @param   string  $destinationPath  Destination path(relative)
   * @param   bool    $force            Force to overwrite
   *
   * @return  string
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  public function move(string $sourcePath, string $destinationPath, bool $force = false): string
  {
    $adapter = $this->getFilesystem();

    return $this->getAdapter($adapter)->move($sourcePath, $destinationPath, $force);
  }

  /**
   * Returns a url for serve media files from adapter.
   * Url must provide a valid image type to be displayed on Joomla! site.
   *
   * @param   string  $path     The relative path for the file
   *
   * @return  string  Permalink to the relative file
   *
   * @since   4.0.0
   * @throws  FileNotFoundException
   */
  public function getUrl(string $path): string
  {
    $adapter = $this->getFilesystem();

    // Check if it is an allowed file
    if(!$this->isAllowedFile($path))
    {
      throw new InvalidPathException();
    }

    $url = $this->getAdapter($adapter)->getUrl($path);

    $event = new FetchMediaItemUrlEvent('onFetchMediaFileUrl', ['adapter' => $adapter, 'path' => $path, 'url' => $url]);
    Factory::getApplication()->getDispatcher()->dispatch($event->getName(), $event);

    return $event->getArgument('url');
  }

  /**
   * Search for a pattern in a given path
   *
   * @param   string  $path       The base path for the search
   * @param   string  $needle     The path to file
   * @param   bool    $recursive  Do a recursive search
   *
   * @return \stdClass[]
   *
   * @since   4.0.0
   * @throws \Exception
   */
  public function search(string $path = '/', string $needle, bool $recursive = false): array
  {
    $adapter = $this->getFilesystem();

    return $this->getAdapter($adapter)->search($path, $needle, $recursive);
  }

  /**
   * Returns a resource for the given path.
   *
   * @param   string  $path  The path
   *
   * @return  resource
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  public function getResource(string $path)
  {
    $adapter = $this->getFilesystem();

    return $this->getAdapter($adapter)->getResource($path);
  }

  /**
   * Returns the name of the adapter.
   * It will be shown in the Media Manager
   *
   * @return  string
   *
   * @since   4.0.0
   */
  public function getAdapterName(): string
  {
    $adapter = $this->getFilesystem();

    return $this->getAdapter($adapter)->getAdapterName();
  }

  /**
   * Checks if the given path is an allowed file.
   *
   * @param   string  $path  The path to file
   *
   * @return boolean
   *
   * @since   4.0.0
   */
  private function isAllowedFile(string $path): bool
  {
    // Check if there is an extension available
    if(!strrpos($path, '.'))
    {
      return false;
    }

    // Initialize the allowed extensions
    if($this->allowedExtensions === null)
    {
      // Get options from the input or fallback to images only
      $fileTypes  = ['0', '1', '2', '3'];
      $types      = [];
      $extensions = [];

      array_map(
        function ($fileType) use (&$types)
        {
          switch ($fileType) {
            case '0':
              $types[] = 'images';
              break;
            case '1':
              $types[] = 'audios';
              break;
            case '2':
              $types[] = 'videos';
              break;
            case '3':
              $types[] = 'documents';
              break;
            default:
              break;
          }
        },
        $fileTypes
      );

      $images = array_map(
        'trim',
        array_filter(
          explode(',',$this->component->getConfig()->get('jg_imagetypes',null)),
          fn($value) => !is_null($value) && $value !== ''
        )
      );

      $audios = array_map(
        'trim',
        array_filter(
          explode(',',$this->component->getConfig()->get('jg_audiotypes',null)),
          fn($value) => !is_null($value) && $value !== ''
        )
      );

      $videos = array_map(
        'trim',
        array_filter(
          explode(',',$this->component->getConfig()->get('jg_videotypes',null)),
          fn($value) => !is_null($value) && $value !== ''
        )
      );

      $documents = array_map(
        'trim',
        array_filter(
          explode(',',$this->component->getConfig()->get('jg_documenttypes',null)),
          fn($value) => !is_null($value) && $value !== ''
        )
      );

      foreach($types as $type)
      {
        if(in_array($type, ['images', 'audios', 'videos', 'documents']))
        {
          $extensions = array_merge($extensions, ${$type});
        }
      }

      // Make them an array
      $this->allowedExtensions = $extensions;
    }

    // Extract the extension
    $extension = strtolower(substr($path, strrpos($path, '.') + 1));

    // Check if the extension exists in the allowed extensions
    return in_array($extension, $this->allowedExtensions);
  }

  /**
   * Get the filesystem property.
   *
   * @return string  The filesystem
   *
   * @since   4.0.0
   */
  public function getFilesystem(): string
  {
    return $this->filesystem;
  }
}
