<?php
/**
 * Simple RSS parser
 *
 * A simple RSS parser with minimal memory and performance requirements.
 * ---------------------------------------------------------------------
 *
 * Usage:
 *
 * 		require 'RssParser.php';
 * 		$rss = new RssParser();
 * 		$rss->setUrlFeed( array('https://example1.org/feed', 'https://example2.org/feed' ) );
 * 		$rss->setItemsPerFeed( 10 );
 * 		$rss->setCachePath( 'cache/' );
 * 		$rss->setCacheTime( 7200 );
 * 		$feed = $rss->get();
 *
 * 		foreach( $feed as $item ):
 * 			echo $item['title'];
 * 			echo $item['link'];
 * 			echo $item['description'];
 * 			echo $item['pubDate'];
 * 			echo $item['thumb_url'];
 * 		endforeach;
 *
 * ---------------------------------------------------------------------
 */
class RssParser{

	/** Number of displayed items from one RSS feed.
	 */
	protected $itemsPerFeed = 5;

	/** Number of seconds between cache updates
	 */
	protected $cacheTime = 7200;

	/** Feed URLs
	 */
	protected $feedUrls = NULL;

	/** The content of the XML file
	 */
	protected $xmlFile = NULL;

	/** The data from XML file
	 */
	protected $xmlData = NULL;

	/** The path for storing cache files.
	 */
	protected $cachePath = NULL;



	/** Init class
	 */
	public function __construct()
	{
		if( ! function_exists( 'simplexml_load_string' ) )
		{
			throw new Exception('The PHP function "simplexml_load_string" is required for this class to function properly.');
			return false;
		}
	}

	/** Retun feed array
	 *
	 * @return array RSS feed
	 */
	public function get()
	{
		$itemsArray = null;
		foreach( $this->feedUrls as $url )
		{
			if( $this->_isCache( $this->cachePath . $this->_getCacheName( $url ) ) )
			{
				if( $this->_isOldCacheFile( $this->cachePath . $this->_getCacheName( $url ) ) )
				{
					$this->_deleteCacheFile( $this->cachePath . $this->_getCacheName( $url ) );
					$this->_getXmlFile( $url );
					$this->_createCacheFile( $this->cachePath . $this->_getCacheName( $url ), $this->xmlFile );
				}else{
					$this->_getXmlFile( $this->cachePath . $this->_getCacheName( $url ) );
				}
			}else{
				$this->_getXmlFile( $url );
				$this->_createCacheFile( $this->cachePath . $this->_getCacheName( $url ), $this->xmlFile );
			}

			$this->_getXmlData( $this->xmlFile );
			if( empty( $this->xmlData ) || $this->xmlData === false ) continue;

			for ($y = 0; $y < $this->itemsPerFeed; $y++)
			{
				if( !isset( $this->xmlData->channel->item[$y]->pubDate ) || empty( $this->xmlData->channel->item[$y]->pubDate ) )
				{
					continue;
				}
				$itemsArray[ strtotime( $this->xmlData->channel->item[$y]->pubDate ) ] = array(
					'title'       => $this->xmlData->channel->item[$y]->title,
					'link'        => $this->xmlData->channel->item[$y]->link,
					'description' => $this->xmlData->channel->item[$y]->description,
					'content'     => $this->xmlData->channel->item[$y]->children('content', true)->encoded->attributes(),
					'media'       => $this->xmlData->channel->item[$y]->children('media', true)->content->attributes(),
					'pubDate'     => $this->xmlData->channel->item[$y]->pubDate,
				);
			}
		}
		unset( $this->xmlData );

		krsort( $itemsArray );

		$x = 0;
		$rss = NULL;
		foreach( $itemsArray as $item)
		{
			$imgUrl = NULL;
			if( !empty( $item['media'] ) )
			{
				$imgUrl = $item['media'];
			}elseif( !empty( $item['content'] ) ){
				$imgUrl = $this->_getFirstImage( $item['content'] );
			}else{
				$imgUrl = $this->_getFirstImage( $item['description'] );
			}

			$d = date('j', strtotime( $item['pubDate'] ));
			$m = date('n', strtotime( $item['pubDate'] ));
			$y = date('Y', strtotime( $item['pubDate'] ));
			$G = date('G', strtotime( $item['pubDate'] ));
			$i = date('i', strtotime( $item['pubDate'] ));

			$rss[$x] = [
				'title'       => $item['title'],
				'link'        => $item['link'],
				'description' => $item['description'],
				'content'     => $item['content'],
				'thumb_url'   => $imgUrl,
				'pubDate'     => $item['pubDate'],
				'd'           => $d,
				'm'           => $m,
				'y'           => $y,
				'G'           => $G,
				'i'           => $i,
				];
			$x++;
			unset( $imgUrl );
			unset( $item );
		}
		unset( $itemsArray );

		return $rss;
	}

	/** Setting number of displayed items.
	 *
	 * @param int Number of items
	 * @return void
	 */
	public function setItemsPerFeed( $numItems )
	{
		$this->itemsPerFeed = (int) $numItems;
	}

	/** Setting the URL of one or more RSS feeds.
	 *
	 * @param array|string One URL or array
	 * @return void
	 */
	public function setUrlFeed( $feed )
	{
		if( ! is_array( $feed ) )
		{
			$this->feedUrls = array( $feed );
		}else{
			$this->feedUrls = $feed;
		}
	}

	/** Setting number of seconds between cache updates
	 *
	 * @param int Number of seconds
	 * @return void
	 */
	public function setCacheTime ($time )
	{
		$this->cacheTime = (int) $time;
	}

	/** Setting the path for storing cache files.
	 *
	 * @param string Dirname for cache files
	 * @return void
	 */
	public function setCachePath( $path )
	{
		if( is_dir( $path ) && is_writable( $path ) )
		{
			$this->cachePath = $path;
		}else{
			throw new Exception('The path for storing cache files is not writable!');
			return false;
		}
	}

	/** Returns a unique file name
	 *
	 * @param string String for creating unique filename (eg. URL of feed)
	 * @return string Unique filename
	 */
	private function _getCacheName( $string )
	{
		return sha1( $string );
	}

	/** Checking the last modified time of a file.
	 *
	 * @param string Path of the cache file
	 * @return int Time of last modification
	 */
	private function _getFileModTime( $filepath )
	{
		if( $this->_isCache( $filepath ) )
		{
			return (int) filemtime( $filepath );
		}else{
			throw new Exception('Failed to find the cache file creation time!');
			return false;
		}
	}

	/** Checking of cache file age
	 *
	 * @param string Path of the cache file
	 * @return bool If older return true else false
	 */
	private function _isOldCacheFile( $filepath )
	{
		if( $this->_isCache( $filepath ) )
		{
			$timeDiff = time() - $this->_getFileModTime( $filepath );
			if( $timeDiff < $this->cacheTime )
			{
				return false;
			}
			return true;
		}else{
			throw new Exception('Failed to find the cache file age!');
			return false;
		}
	}

	/** Checking if cache file exists.
	 *
	 * @param string Path to the cache file
	 * @return bool Return true|false if file exists or no
	 */
	private function _isCache( $filepath )
	{
		if( is_readable( $filepath ) )
		{
			return true;
		}
		return false;
	}

	/** Deleting chache file
	 *
	 * @param string Path of the cache file for deleting
	 * @return bool If is file successfull deleted
	 */
	private function _deleteCacheFile( $filepath )
	{
		if( is_writable( $filepath ) )
		{
			@unlink( $filepath );
			return true;
		}else{
			throw new Exception('Failed to delete cache file!');
			return false;
		}
	}

	/** Create and save cache file
	 *
	 * @param string Path of the cache file
	 * @param string Data
	 * @return bool
	 */
	private function _createCacheFile( $filepath, $data )
	{
		if( is_writable( $filepath ) )
		{
			if( $this->_deleteCacheFile( $filepath ) )
			{
				$createFile = @file_put_contents( $filepath, $data, LOCK_EX );
				if( $createFile === FALSE )
				{
					throw new Exception('Failed to create cache file!');
					return false;
				}else{
					return true;
				}
			}else{
				throw new Exception('Failed to create cache file, a file with the same name already exists and cannot be deleted!');
				return false;
			}
		}else{
			$createFile = @file_put_contents( $filepath, $data, LOCK_EX );
			if( $createFile === FALSE )
			{
				throw new Exception('Failed to create cache file!');
				return false;
			}else{
				return true;
			}
		}
	}

	/** Loading the contents of an XML file
	 *
	 * @param string Path of the XML file
	 * @return void|false
	 */
	private function _getXmlFile( $filepath )
	{
		$fileContent = @file_get_contents( $filepath );
		if( $fileContent !== FALSE || ! empty( $fileContent ) )
		{
			$this->xmlFile = trim( $fileContent );
		}else{
			throw new Exception('Failed to load content of XML file!');
			return false;
		}
	}

	/** Getting XML data from xml file
	 *
	 * @param string Content of XML file
	 * @return void|false
	 */
	private function _getXmlData( $xmlContent )
	{
		$this->xmlData = @simplexml_load_string( $xmlContent );
		if( !$this->xmlData || empty( $this->xmlData ) || $this->xmlData === false )
		{
			throw new Exception('XML content is empty or corrupted!');
			return false;
		}

	}

	/** Checking for first image in post
	 *
	 * @param string Content of RSS item
	 * @return string Empty string or URL for first image
	 */
	private function _getFirstImage( $html )
	{
		if ( preg_match('/<img.+?src="(.+?)"/', $html, $matches ) )
		{
			return trim( $matches[1] );
		}
		else return '';
	}
}
