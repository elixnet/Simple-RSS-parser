<?php
/**
 * Simple RSS parser
 *
 * A simple RSS parser with minimal memory and performance requirements.
 *
 * @package RssParser
 * @version 1.5.0
 * @copyright 2023 Elix
 * @author Elix
 * @link https://github.com/Elixcz/Simple-RSS-parser
 * @license https://tldrlegal.com/license/mit-license MIT License
 * ---------------------------------------------------------------------
 *
 * Usage:
 *
 * 		require 'RssParser.php';
 * 		$rss = new RssParser();
 * 		$rss->setUrlFeed( ['https://example1.org/feed', 'https://example2.org/feed'] );
 * 		$rss->setItemsPerFeed( 10 );
 * 		$rss->setCachePath( './cache/' );
 * 		$rss->setCacheTime( 7200 );
 * 		$rss->setRandomUa( false );
 * 		$feed = $rss->get();
 *
 * 		foreach( $feed as $item ):
 * 			echo $item['title'];
 * 			echo $item['link'];
 * 			echo $item['description'];
 * 			echo $item['pubDate'];
 * 			echo $item['sourceUrl'];
 * 		endforeach;
 *
 * ---------------------------------------------------------------------
 */
class RssParser{

	/**
	 * Number of displayed items from one RSS feed.
	 */
	protected $itemsPerFeed = 5;

	/**
	 * Number of seconds between cache updates
	 */
	protected $cacheTime = 7200;

	/**
	 * Feed URLs
	 */
	protected $feedUrls = NULL;

	/**
	 * The content of the XML file
	 */
	protected $xmlFile = NULL;

	/**
	 * The data from XML file
	 */
	protected $xmlData = NULL;

	/**
	 * The path for storing cache files.
	 */
	protected $cachePath = NULL;

	/**
	 * Random user agent for downloading RSS feed
	 */
	protected $randomUa = TRUE;

	/**
	 * Avalaible user agents
	 */
	protected $userAgents = array(
								'Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10',
								'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36',
								'Mozilla/5.0 (Macintosh; Intel Mac OS X 12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36',
								'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36',
								'Mozilla/5.0 (Linux; Android 10; SM-A205U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.5195.136 Mobile Safari/537.36',
								'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:105.0) Gecko/20100101 Firefox/105.0',
								'Mozilla/5.0 (X11; Linux i686; rv:105.0) Gecko/20100101 Firefox/105.0',
								'Mozilla/5.0 (Macintosh; Intel Mac OS X 12.6; rv:105.0) Gecko/20100101 Firefox/105.0',
								'Mozilla/5.0 (iPhone; CPU iPhone OS 12_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/105.0 Mobile/15E148 Safari/605.1.15',
								'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36 Edg/105.0.1343.50'
								);


	/**
	 * Init class
	 */
	public function __construct()
	{
		if( ! function_exists( 'simplexml_load_string' ) )
		{
			throw new Exception('The PHP function "simplexml_load_string" is required for class RssParser to function properly.');
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
					'pubDate'     => $this->xmlData->channel->item[$y]->pubDate,
					'sourceUrl'   => $this->_showFeedSource( $this->xmlData->channel->item[$y]->link ),
				);
			}
		}
		unset( $this->xmlData );
		krsort( $itemsArray );

		$x = 0;
		$rss = NULL;
		foreach( $itemsArray as $item)
		{
			$d = date('j', strtotime( $item['pubDate'] ));
			$m = date('n', strtotime( $item['pubDate'] ));
			$y = date('Y', strtotime( $item['pubDate'] ));
			$G = date('G', strtotime( $item['pubDate'] ));
			$i = date('i', strtotime( $item['pubDate'] ));

			$rss[$x] = [
				'title'       => $item['title'],
				'link'        => $item['link'],
				'description' => $item['description'],
				'pubDate'     => $item['pubDate'],
				'sourceUrl'   => $item['sourceUrl'],
				'd'           => $d,
				'm'           => $m,
				'y'           => $y,
				'G'           => $G,
				'i'           => $i,
				];
			$x++;
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

	/** Setting whether to use a random user agent for download.
	 *
	 * @param bool Yes or No
	 * @return void
	 */
	public function setRandomUa( $bool )
	{
		$this->randomUa = $bool;
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
			//throw new Exception('Failed to find the cache file creation time!');
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
			//throw new Exception('Failed to find the cache file age!');
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
			//throw new Exception('Failed to delete cache file!');
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
					//throw new Exception('Failed to create cache file!');
					return false;
				}else{
					return true;
				}
			}else{
				//throw new Exception('Failed to create cache file, a file with the same name already exists and cannot be deleted!');
				return false;
			}
		}else{
			$createFile = @file_put_contents( $filepath, $data, LOCK_EX );
			if( $createFile === FALSE )
			{
				//throw new Exception('Failed to create cache file!');
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
		if( $this->randomUa ){
			$ua = rand( 0, count( $this->userAgents ) - 1 );
			$userAgent = $this->userAgents[ $ua ];
		}else{
			$userAgent = $_SERVER['HTTP_USER_AGENT'];
		}
		$options = array(
						'http' => array(
									'method'=>"GET",
									'header'=> "User-Agent: " . $userAgent . "\r\n"
									)
						);
		$context = stream_context_create( $options );
		$fileContent = @file_get_contents( $filepath, false, $context );
		if( $fileContent !== FALSE || ! empty( $fileContent ) )
		{
			$this->xmlFile = trim( $fileContent );
		}else{
			//throw new Exception('Failed to load content of XML file!');
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
			//throw new Exception('XML content is empty or corrupted!');
			return false;
		}

	}

	/** Return domain URL of source
	 *
	 * @param string Feed item URL
	 * @return string Source URL
	 */
	private function _showFeedSource( $feed_item_url = false ){
		if(!$feed_item_url) return 'undefined!';

		$tmp = explode('/', $feed_item_url);
		$source = $tmp[2];
		$source = str_replace('www.', '', $source);
		$source = ucfirst($source);
		return (string) $source;
	}
}
// END of file
