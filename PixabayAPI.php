<?php namespace pixabayapi;

/**
 * Pixabay API Client
 * 
 * Provides a wrapper to the Pixabay Image API and requires an API key to use.
 * For API details please see https://pixabay.com/api/docs/.
 * 
 * @author Martyr2
 * @copyright 2021 Martyr2
 * @link https://www.coderslexicon.com
 * 
 */

use pixabayapi\lib\CurlRequests;
use pixabayapi\lib\PixabayException;

class PixabayAPI {
    private string $imageSearchUrl = 'https://pixabay.com/api/';
    private string $videoSearchUrl = 'https://pixabay.com/api/videos/';
    private string $apiKey;
    private array $rateLimitInfo = [];
    private $errorObj = null;

    public function __construct(string $apiKey) 
    {
        if (trim($apiKey) === '') {
            throw new PixabayException('Please specify a Pixabay API key.');
        }

        $this->apiKey = $apiKey;
        $this->imageSearchUrl = $this->imageSearchUrl . "?key={$this->apiKey}";
        $this->videoSearchUrl = $this->videoSearchUrl . "?key={$this->apiKey}";
    }

    /**
     * Executes a search of Pixabay images using the query options specified.
     *
     * @param array $queryOptions - An array of query options supported by the Pixabay API
     * @return array|stdClass Returns an array of PixabayImage objects if successful or a stdClass if a failure/callback option is set.
     * @throws PixabayException If invalid query option value is detected or unable to communicate with the API.
     */
    public function searchImages(array $queryOptions) 
    {
        $this->validateQueryOptions($queryOptions);
        $response = $this->queryResults($this->buildQueryUrl($this->imageSearchUrl, $queryOptions));

        if (($response->status_code !== 200) || (array_key_exists('callback', $queryOptions))) {
            return $response;
        }
        
        return $this->parseResponse($response, 'PixabayImage');
    }

    /**
     * Executes a search of Pixabay videos using the query options specified.
     *
     * @param array $queryOptions - An array of query options supported by the Pixabay API
     * @return array|stdClass Returns an array of PixabayVideo objects if successful or a stdClass if a failure/callback option is set.
     * @throws PixabayException If invalid query option value is detected or unable to communicate with the API.
     */
    public function searchVideos(array $queryOptions) 
    {
        $this->validateQueryOptions($queryOptions, 'video');
        $response = $this->queryResults($this->buildQueryUrl($this->videoSearchUrl, $queryOptions));

        if (($response->status_code !== 200) || (array_key_exists('callback', $queryOptions))) {
            return $response;
        }
        
        return $this->parseResponse($response, 'PixabayVideo');
    }

    /**
     * Returns the error number if an error contacting the API was encountered.
     *
     * @return int|null The error number if an error occurred, otherwise null.
     */
    public function getErrorNumber() 
    {
        return !is_null($this->errorObj) ? $this->errorObj->errorno : null; 
    }

    /**
     * Returns the error message if an error contacting the API was encountered.
     *
     * @return int|null The error message if an error occurred, otherwise null.
     */
    public function getErrorMessage() 
    {
        return !is_null($this->errorObj) ? $this->errorObj->errormsg : null; 
    }

    /**
     * Returns rate limit information from the Pixabay API response headers
     *
     * @return array An array of rate limit information based on last query made.
     */
    public function getRateLimitInfo()
    {
        return $this->rateLimitInfo;
    }


    /**
     * Private helper methods
     */

    /**
     * Parses the response body and builds the result list if hits were detected.
     *
     * @param stdClass $response - API response object that needs to be parsed
     * @param string $resultType - The 'PixabayImage' or 'PixabayVideo' result type to be built
     * @return array An array of instances of the appropriate result type or empty if no hits were seen.
     */
    private function parseResponse($response, string $resultType = 'PixabayImage') 
    {       
        $resultList = [];
        $results = json_decode($response->content);

        if (!is_null($results) && property_exists($results, 'hits')) {
            foreach ($results->hits as $result) {
                $classname = 'pixabayapi\\lib\\' . $resultType;
                $resultList[] = new $classname($result);
            }
        }

        return $resultList;
    }

    /**
     * Queries the Pixabay API for results based on the query URL passed in.
     *
     * @param string $queryUrl - URL to call on the API to get results
     * @return stdClass A standard class instance with a status code and content property.
     * @throws PixabayException An exception is thrown if there was a problem contacting the API.
     */
    private function queryResults(string $queryUrl) 
    {
        $this->errorObj = null;

        $searchResponse = CurlRequests::get($queryUrl);
        $this->populateRateHeaders(CurlRequests::$responseHeaders);

        if (property_exists($searchResponse, 'errorno')) {
            $this->errorObj = $searchResponse;
            throw new PixabayException('Error establishing a connection to the Pixabay API.');
        }
        
        return $searchResponse;
    }

    /**
     * Builds an API query URL based on the query options
     *
     * @param string $url - Base URL (for images or videos... key already included)
     * @param array $queryOptions - An array of query options to build the string with
     * @return string Returns a URL string
     */
    private function buildQueryUrl(string $url, array $queryOptions) {
        $query = !empty($queryOptions) ? '&' . http_build_query($queryOptions) : '';
        return $url . $query;
    }

    /**
     * Populates the rateLimitInfo property
     *
     * @param array $responseHeaders - Headers that came from the Pixabay API response
     * @return void
     */
    private function populateRateHeaders(array $responseHeaders) 
    {
        $this->rateLimitInfo['x-ratelimit-limit'] = isset($responseHeaders['x-ratelimit-limit'][0]) ? $responseHeaders['x-ratelimit-limit'][0] : 0;
        $this->rateLimitInfo['x-ratelimit-remaining'] = isset($responseHeaders['x-ratelimit-remaining'][0]) ? $responseHeaders['x-ratelimit-remaining'][0] : 0;
        $this->rateLimitInfo['x-ratelimit-reset'] = isset($responseHeaders['x-ratelimit-reset'][0]) ? $responseHeaders['x-ratelimit-reset'][0] : 0;
    }

    /**
     * Validate query options. This will throw a PixabayException if any option validation fails.
     *
     * @param array $queryOptions - Array of query options to validate
     * @param string $queryType - Value of 'image' or 'video' to determine which options are possible to validate.
     * @return void
     * @throws PixabayException Throws an exception if any query options fail validation.
     */
    private function validateQueryOptions(array $queryOptions, string $queryType = 'image') 
    {
        if (array_key_exists('pretty', $queryOptions)) {
            throw new PixabayException('Pretty print is not supported.');
        }

        if (array_key_exists('q', $queryOptions) && strlen($queryOptions['q']) > 100) {
            throw new PixabayException('Query (q) cannot exceed 100 characters.');
        }

        $verifyOptions = [
            'lang' => 'validateLanguage', 
            'category' => 'validateCategory', 
            'min_width' => 'validateMinWidth', 
            'min_height' => 'validateMinHeight', 
            'colors' => 'validateColors', 
            'editors_choice' => 'validateEditorsChoice',
            'safesearch' => 'validateSafeSearch',
            'order' => 'validateOrder', 
            'page' => 'validatePage', 
            'per_page' => 'validatePerPage'
        ];

        if ($queryType === 'image') {
            $verifyOptions['image_type'] = 'validateImageType';
            $verifyOptions['orientation'] = 'validateOrientation';
        } else {
            $verifyOptions['video_type'] = 'validateVideoType';
        }

        // Throw an exception if any specified option doesn't meet requirements.
        foreach ($verifyOptions as $name => $functionName) {
            $this->validateOption($queryOptions, $name, [$this, $functionName]);
        }
    }

    /**
     * Validates a single query option and throws an exception if it fails.
     *
     * @param array $queryOptions - Array of query options being specified
     * @param string $optionName - Name of the option to verify
     * @param callable $func - Callable function to call to do the validation
     * @return void
     * @throws PixabayException Throws a PixabayException if any option fails its validation
     */
    private function validateOption(array $queryOptions, string $optionName, callable $func) 
    {
        if (array_key_exists($optionName, $queryOptions) && !$func($queryOptions[$optionName])) {
            throw new PixabayException("Invalid value for option '{$optionName}' specified.");
        }
    }

    /**
     * Validates the given language is in the range of acceptable languages
     *
     * @param string $lang - Language code to check
     * @return bool True if it is valid, false otherwise.
     */
    private function validateLanguage(string $lang) 
    {
        return in_array($lang, [
            'cs', 'da', 'de', 'en', 'es', 'fr', 'id', 'it', 'hu', 'nl', 'no', 'pl', 
            'pt', 'ro', 'sk', 'fi', 'sv', 'tr', 'vi', 'th', 'bg', 'ru', 'el', 'ja', 
            'ko', 'zh'
        ]);
    }

    /**
     * Validates the given image type is in the range of acceptable image typos
     *
     * @param string $imageType - Type of image to search for
     * @return bool True if it is valid, false otherwise.
     */
    private function validateImageType(string $imageType) 
    {
        return in_array($imageType, ['all', 'photo', 'illustration', 'vector']);
    }

    /**
     * Validates the given video type is in the range of acceptable video typos
     *
     * @param string $videoType - Type of video to search for
     * @return bool True if it is valid, false otherwise.
     */    
    private function validateVideoType(string $videoType) 
    {
        return in_array($videoType, ['all', 'film', 'animation', 'vector']);
    }

    /**
     * Validates an image orientation is in the range of valid orientations
     *
     * @param string $orientation - Orientation "all", "horizontal" or "vertical"
     * @return bool True if it is valid, false otherwise.
     */
    private function validateOrientation(string $orientation) 
    {
        return in_array($orientation, ['all', 'horizontal', 'vertical']);
    }

    /**
     * Validate a catagory is in the range of valid catagories
     *
     * @param string $category - Name of the catagory to search
     * @return bool True if it is valid, false otherwise.
     */
    private function validateCategory(string $category) 
    {
        return in_array($category, [
            'backgrounds', 'fashion', 'nature', 'science', 'education', 'feelings',
            'health', 'people', 'religion', 'places', 'animals', 'industry', 'computer',
            'food', 'sports', 'transportation', 'travel', 'buildings', 'business', 'music'
        ]);
    }

    /**
     * Validate minimum width
     *
     * @param string|int $minWidth - The minimum width of the image or video that should be returned
     * @return bool True if it is valid, false otherwise.
     */
    private function validateMinWidth($minWidth) 
    {
        $validMinWidth = filter_var($minWidth, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        return ($validMinWidth !== false);
    }

    /**
     * Validate minimum height
     *
     * @param string|int $minheight - The minimum height of the image or video that should be returned
     * @return bool True if it is valid, false otherwise.
     */
    private function validateMinHeight($minHeight) 
    {
        $validMinHeight = filter_var($minHeight, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        return ($validMinHeight !== false);
    }

    /**
     * Validates that at least one color is specified in the range of all valid colors
     *
     * @param string $colors - Comma separated string of colors the images should contain (invalid colors are ignored by the API)
     * @return bool True if it is valid, false otherwise.
     */
    private function validateColors(string $colors) 
    {
        $acceptedColors = ['grayscale', 'transparent', 'red', 'orange', 'yellow', 'green', 'turquoise', 'blue', 'lilac', 'pink', 'white', 'gray', 'black', 'brown'];
        $specifiedColors = array_map('trim', explode(',', $colors));
        return count(array_intersect($specifiedColors, $acceptedColors)) > 0;
    }

    /**
     * Validate the editors choice option (must be "true" or "false")
     *
     * @param string $choice - String representing true or false if editor's choice is enabled
     * @return bool True if it is valid, false otherwise.
     */
    private function validateEditorsChoice(string $choice) 
    {
        return in_array($choice, ["true", "false"]);
    }

    /**
     * Validate the safe search option (must be "true" or "false")
     *
     * @param string $safeSearch - String representing true or false if safe search is enabled
     * @return bool True if it is valid, false otherwise.
     */
    private function validateSafeSearch(string $safeSearch) 
    {
        return in_array($safeSearch, ["true", "false"]);
    }

    /**
     * Validates the order category search option
     *
     * @param string $order - Order being 'popular' or 'latest'
     * @return bool True if it is valid, false otherwise.
     */
    private function validateOrder(string $order) 
    {
        return in_array($order, ['popular', 'latest']);
    }

    /**
     * Validate page option
     *
     * @param string|int $pageNumber - The page number in paged results (1 minimum)
     * @return bool True if it is valid, false otherwise.
     */
    private function validatePage($pageNumber) 
    {
        $validPageNumber = filter_var($pageNumber, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return ($validPageNumber !== false);
    }

    /**
     * Validate per_page option
     *
     * @param string|int $perPage - The number of results per page in the range of 3 - 200
     * @return bool True if it is valid, false otherwise.
     */
    private function validatePerPage($perPage) 
    {
        $validPerPage = filter_var($perPage, FILTER_VALIDATE_INT, ['options' => ['min_range' => 3, 'max_range' => 200]]);
        return ($validPerPage !== false);
    }
}
