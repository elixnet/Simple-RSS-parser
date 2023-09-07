# Simple RSS parser

A simple RSS parser with minimal memory and performance requirements.


## Usage:

```php
	require 'RssParser.php';
	$rss = new RssParser();
	$rss->setUrlFeed( ['https://example1.org/feed', 'https://example2.org/feed'] );
	$rss->setItemsPerFeed( 10 );
	$rss->setCachePath( 'cache/' );
	$rss->setCacheTime( 7200 );
	$rss->setRandomUa( false );
	$feed = $rss->get();

	foreach( $feed as $item ):
		echo $item['title'];        // show post title
		echo $item['link'];         // show post url
		echo $item['description'];  // show post description or content
		echo $item['pubDate'];      // show post date and time
		echo $item['sourceUrl'];    // show source url whitout "https://"
	endforeach;
```

## Support
**Simple RSS Parser** is open source and free. Donate for coffee or just like that:

BTC: `bc1q03v5la7uvcwxr7z4qn03ex6n5edju6zv4n6ppt`

## License
**Simple RSS Parser** is open source software licensed under the [MIT license](https://tldrlegal.com/license/mit-license).
