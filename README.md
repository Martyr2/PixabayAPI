# About Pixabay API Wrapper

This is a simple wrapper that wraps the Pixabay API service. If you are unfamiliar with Pixabay, it is a website that offers millions of free stock images and videos that can be used for private and commerical purposes. This API wrapper allows PHP to query and build lists of images and videos from Pixabay.

- [Pixabay](https://www.pixabay.com).
- [Pixabay API - Documentation](https://pixabay.com/api/docs/).

## Requirements

- Requires at least PHP 7.0

## Usage

First create an instance of the PixabayAPI class (using your API key). Next, setup an array of various options as outlined in the Pixabay API docs. Lastly, pass this array to one of two methods: `searchImages()` or `searchVideos()`. In the example below, we will fetch some images that match our query "yellow flowers" and that are at least 800 x 500 in size.

```php
$pixabayAPI = new PixabayAPI(API_KEY);

// Many options available. Refer to the Pixabay Docs
$searchOptions = [
    'q' => 'yellow flowers',
    'min_width' => '800',
    'min_height' => '500'
];

// Get some yellow flowers which are a minimum of 800 x 500
$searchResults = $pixabayAPI->searchImages($searchOptions);

if (is_array($searchResults)) {
    // Loop through images, each $image will be an instance of PixabayImage
    foreach ($searchResults as $image) {
        echo "{$image->largeImageURL}<br/>"; 
    }
} else {
    echo "Oops! There was a problem! Status: {$searchResults->status_code} with content: {$searchResults->content}";
}

// You can also get some brief info about your API key's rate limits
// Note: You would ask for this only after your most recent query.
echo "Your rate limits are now: " . print_r($pixabayAPI->getRateLimitInfo(), true);

```

On success, these methods return an array of `PixabayImage` or `PixabayVideo` objects with numerous properties. However, if you encounter an error (like a 400 bad request), or specify the option `callback`, then you will receive a `stdClass` object. This object will feature a `status_code` and `content` property that you can inspect.

**Note:** In the event that one of the query options fails validation, there is an invalid API key used or a problem happens with connecting to the API to begin with, a `PixabayException` will be thrown.

For more information on usage, see the source code and the related doc strings.

## License

The PixabayAPI wrapper class is software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Questions or to Report An Issue

If you would like to report an issue with this code, please [email us](mailto:github@coderslexicon.com).
