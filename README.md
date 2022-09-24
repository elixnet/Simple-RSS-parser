# Simple RSS parser
---
A simple RSS parser with minimal memory and performance requirements.


### Usage:
 
```php
	require 'RssParser.php';
	$rss = new RssParser();
	$rss->setUrlFeed( array('https://example1.org/feed', 'https://example2.org/feed' ) );
	$rss->setItemsPerFeed( 10 );
	$rss->setCachePath( 'cache/' );
	$rss->setCacheTime( 7200 );
	$rss->setRandomUa( false );
	$feed = $rss->get();

	foreach( $feed as $item ):
		echo $item['title'];
		echo $item['link'];
		echo $item['description'];
		echo $item['pubDate'];
		echo $item['thumb_url'];
	endforeach;
```

### Support
**Simple RSS Parser** is open source and free. Donate one time for the coffee or beer:

BTC: `bc1q03v5la7uvcwxr7z4qn03ex6n5edju6zv4n6ppt`
ETH: `0x661751Ebe19BF3Fe8860e4630796Eb53391E0424`

### License
**Simple RSS Parser** is open source software licensed under the [MIT license](https://tldrlegal.com/license/mit-license).