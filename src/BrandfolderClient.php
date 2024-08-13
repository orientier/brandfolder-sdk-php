<?php

namespace Brandfolder;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Utils as GuzzleUtils;
use stdClass;

/**
 * Client for interacting with the Brandfolder API.
 *
 * @package Brandfolder
 */
class BrandfolderClient
{
    /**
     * The status code of the most recent operation, if applicable.
     *
     * @var int $status
     */
    public int $status = 0;

    /**
     * A useful message pertaining to the most recent operation, if applicable.
     *
     * @var string $message
     */
    public string $message = '';

    /**
     * HTTP client.
     *
     * @var null|ClientInterface $http_client
     */
    protected null|ClientInterface $http_client;

    /**
     * The REST API endpoint.
     *
     * @var string $endpoint
     */
    protected string $endpoint = 'https://brandfolder.com/api/v4';

    /**
     * The Brandfolder API key with which to authenticate
     * (used as a bearer token).
     *
     * @var string $api_key
     */
    protected string $api_key;

    /**
     * The Brandfolder to use for Brandfolder-specific requests, when no other
     * Brandfolder is specified.
     *
     * @var string|null $default_brandfolder_id
     *
     * @todo setBrandfolder() method.
     */
    public ?string $default_brandfolder_id;

    /**
     * The Collection to use for Collection-specific requests, when no other
     * Collection is specified.
     *
     * @var string|null $default_collection_id
     *
     * @todo setCollection() method.
     */
    public ?string $default_collection_id = null;

    /**
     * The default number of items to fetch per GET request. Corresponds to the
     * "per" query param.
     *
     * @var int $default_items_per_page
     */
    public int $default_items_per_page = 100;

    /**
     * Flag for enabling verbose logging/recording.
     *
     * @var bool $verbose_logging_mode
     */
    protected bool $verbose_logging_mode = false;

    /**
     * Internal storage for logging/recording data.
     *
     * @var array $log_data
     */
    protected array $log_data = [];

    /**
     * BrandfolderClient constructor.
     *
     * @param string $api_key
     * @param string|null $brandfolder_id
     * @param \GuzzleHttp\ClientInterface|NULL $http_client
     */
    public function __construct(string $api_key, string $brandfolder_id = null, ClientInterface $http_client = null)
    {
        $this->api_key = $api_key;
        $this->default_brandfolder_id = $brandfolder_id;

        if (is_null($http_client)) {
            $http_client = new HttpClient();
        }
        $this->http_client = $http_client;
    }


    /***************************************************************************
     * Organizations *
     ***************************************************************************/

    /**
     * Gets Brandfolder Organizations to which the current user belongs.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $organizations = $bf->listOrganizations(['include' => 'brandfolders']);
     * if ($organizations) {
     *   foreach ($organizations->data as $org) {
     *     echo "{$org->attributes->name} ({$org->attributes->id}) <br/>";
     *     if (!empty($org->brandfolders)) {
     *       foreach ($org->brandfolders as $brandfolder_id => $brandfolder) {
     *         echo "&nbsp; {$brandfolder->name} ($brandfolder_id) <br/>";
     *       }
     *     }
     *   }
     * }
     * ```
     *
     * @param array $query_params
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/?http#list-organizations
     */
    public function listOrganizations(array $query_params = []): object|false
    {

        return $this->getAll('/organizations', $query_params);
    }

    /**
     * Alternate name for backward compatibility.
     *
     * @deprecated Use listOrganizations() instead.
     *
     * @see BrandfolderClient::listOrganizations()
     */
    public function getOrganizations(array $query_params = []): object|false
    {

        return $this->listOrganizations($query_params);
    }

    /**
     * Fetch a single Organization by ID.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     * $org = $bf->fetchOrganization(['include' => 'brandfolders']);
     *
     * if ($org) {
     *   echo "{$org->attributes->name} ({$org->attributes->id}) <br/>";
     *   if (!empty($org->brandfolders)) {
     *     foreach ($org->brandfolders as $brandfolder_id => $brandfolder) {
     *       echo "&nbsp; {$brandfolder->name} ($brandfolder_id) <br/>";
     *     }
     *   }
     * }
     *
     * ```
     *
     * @param string $organization_id
     * @param array $query_params
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#fetch-an-organization
     *
     */
    public function fetchOrganization(string $organization_id, array $query_params = []): object|false
    {
        $result = $this->request('GET', "/organizations/$organization_id", $query_params);

        $this->processResultData($result);

        return $result;
    }


    /***************************************************************************
     * Brandfolders *
     ***************************************************************************/

    /**
     * Gets Brandfolders to which the current user has access.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     * $brandfolders = $bf->listBrandfolders(['include' => 'collections']);
     *
     * if ($brandfolders) {
     *   foreach ($brandfolders->data as $brandfolder) {
     *     echo "{$brandfolder->attributes->name} ({$brandfolder->id}) <br/>";
     *     if (!empty($brandfolder->collections)) {
     *       foreach ($brandfolder->collections as $collection_id => $collection) {
     *         echo "&nbsp; {$collection->name} ($collection_id) <br/>";
     *       }
     *     }
     *   }
     * }
     *
     * ```
     *
     * @param array $query_params
     * @param bool $simple_format If true, return a flat array whose keys are
     *  Brandfolder IDs and whose values are Brandfolder names. If false, return
     *  the full result object with all core Brandfolder data and any included
     *  data.
     *
     * @return array|object|false
     *
     * @see https://developers.brandfolder.com/docs/#list-brandfolders
     */
    public function listBrandfolders(array $query_params = []): object|bool
    {
        $result = $this->request('GET', '/brandfolders', $query_params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * Get an array containing the names of all Brandfolders (to which the
     * current user has access), keyed by Brandfolder ID.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     * $brandfolders = $bf->listAllBrandfolderNames();
     *
     * if ($brandfolders) {
     *   foreach ($brandfolders as $brandfolder_id => $brandfolder_name) {
     *     echo "{$brandfolder_name} ({$brandfolder_id}) <br/>";
     *   }
     * }
     *
     * ```
     *
     * @return array|bool
     *
     * @see https://developers.brandfolder.com/docs/#list-brandfolders
     */
    public function listAllBrandfolderNames(): array|bool
    {
        $bf_data = $this->getAll('/brandfolders');
        if ($bf_data) {
            $brandfolders = [];
            foreach ($bf_data->data as $brandfolder) {
                $brandfolders[$brandfolder->id] = $brandfolder->attributes->name;
            }

            return $brandfolders;
        }

        return false;
    }

    /**
     * Get all Brandfolders, in one of two formats.
     *
     * @deprecated Use listBrandfolders() or listAllBrandfolderNames() instead.
     *
     * @see listBrandfolders()
     */
    public function getBrandfolders(array $query_params = [], bool $simple_format = true): object|bool|array
    {
        $bf_data = $this->getAll('/brandfolders', $query_params);
        if ($bf_data) {
            if ($simple_format) {
                $brandfolders = [];
                foreach ($bf_data->data as $brandfolder) {
                    $brandfolders[$brandfolder->id] = $brandfolder->attributes->name;
                }

                return $brandfolders;
            } else {
                return $bf_data;
            }
        }

        return false;
    }


    /**
     * Fetch an individual Brandfolder by ID.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $result = $bf->fetchBrandfolder($brandfolder_id, ['include' => 'sections']);
     * if ($result) {
     *   $brandfolder = $result->data;
     *   echo "{$brandfolder->attributes->name} ({$brandfolder->id}) <br/>";
     *   if (!empty($brandfolder->sections)) {
     *     foreach ($brandfolder->sections as $section_id => $section) {
     *       echo "&nbsp; {$section->name} ($section_id) <br/>";
     *     }
     *   }
     * }
     *
     * ```
     *
     * @param string $brandfolder_id
     * @param array|null $query_params
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#fetch-a-brandfolder
     */
    public function fetchBrandfolder(string $brandfolder_id, ?array $query_params = []): object|false
    {
        $result = $this->request('GET', "/brandfolders/$brandfolder_id", $query_params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * Create a new Brandfolder in an Organization.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $result = $bf->createBrandfolderInOrganization($organization_id, 'My Brandfolder',
     *  'You expected this - Brandfolder's Brandfolder!', 'public');
     * if ($result) {
     *   $new_brandfolder = $result->data;
     *   echo "New Brandfolder created: {$new_brandfolder->attributes->name} ({$new_brandfolder->id}) <br/>";
     * }
     *
     * ```
     *
     * @param string $organization_id
     * @param string $name
     * @param string|null $tagline
     *  A descriptive subtitle/short description of the Brandfolder.
     * @param string $privacy
     *  The privacy setting for the new Brandfolder. Valid options are "private"
     *  (the default) and "public."
     * @param string|null $slug
     *  String of letters, numbers, hyphens, and underscores.
     *  Note: we recommend *not* to invent your own slug. If it is not valid, the
     *  request will fail with a 422 error. Default is to automatically
     *  assign a slug based on $name (a name of "My Brandfolder" would make a
     *  slug of "my-brandfolder").
     *  If the provided slug is valid but not unique, it will have a number
     *  appended to make it unique.
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#create-a-brandfolder-in-an-organization
     */
    public function createBrandfolderInOrganization(
        string $organization_id,
        string $name,
        string $tagline = null,
        string $privacy = 'private',
        string $slug = null
    ): object|false {
        $attributes = [
            'name' => $name,
        ];
        if (!is_null($tagline)) {
            $attributes['tagline'] = $tagline;
        }
        if (!is_null($slug)) {
            $attributes['slug'] = $slug;
        }
        if (!is_null($privacy)) {
            $valid_privacy_values = ['private', 'public'];
            if (!in_array($privacy, $valid_privacy_values)) {
                $this->status = 0;
                $this->message = 'Invalid privacy value. Valid options are "private" and "public".';

                return false;
            }
            $attributes['privacy'] = $privacy;
        }
        $body = [
            "data" => [
                "attributes" => $attributes,
            ],
        ];

        return $this->request('POST', "/organizations/$organization_id/brandfolders", [], $body);
    }


    /***************************************************************************
     * Collections *
     ***************************************************************************/

    /**
     * Lists Collections to which the current user has access.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $result = $bf->listCollectionsForUser(['include' => 'brandfolder']);
     * if ($result) {
     *   $collections = $result->data;
     *   foreach ($collections as $collection) {
     *     echo "{$collection->attributes->name} ({$collection->id}) <br/>";
     *     if (!empty($collection->brandfolders)) {
     *       $brandfolder = reset($collection->brandfolders);
     *       echo "&nbsp; Brandfolder: {$brandfolder->name} ({$brandfolder->id}) <br/>";
     *     }
     *   }
     * }
     *
     * ```
     *
     * @param array|null $query_params
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/?http#list-collections
     */
    public function listCollectionsForUser(array $query_params = []): object|false
    {

        return $this->getAll('/collections', $query_params);
    }

    /**
     * Alternate name for backward compatibility.
     *
     * @deprecated Use listCollectionsForUser() instead.
     *
     * @see listCollectionsForUser()
     */
    public function getCollectionsForUser(array $query_params = []): object|false
    {

        return $this->listCollectionsForUser($query_params);
    }

    /**
     * List Collections belonging to a given Brandfolder.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $result = $bf->listCollectionsInBrandfolder($brandfolder_id, ['fields' => 'asset_count']);
     * if ($result) {
     *   $collections = $result->data;
     *   foreach ($collections as $collection) {
     *     echo "{$collection->attributes->name} ({$collection->id}): "
     *      . "{$collection->attributes->asset_count} assets <br/>";
     *   }
     * }
     *
     * ```
     *
     * @param string|null $brandfolder_id
     * @param array|null $query_params
     *
     * @return array|object|false
     *
     * @see https://developers.brandfolder.com/?http#list-collections
     */
    public function listCollectionsInBrandfolder(string $brandfolder_id = null, array $query_params = []): object|false
    {
        if (is_null($brandfolder_id)) {
            if (is_null($this->default_brandfolder_id)) {
                $this->status = 0;
                $this->message = 'A Brandfolder ID must be provided or a default Brandfolder must be set.';

                return false;
            }
            $brandfolder_id = $this->default_brandfolder_id;
        }

        $result = $this->request('GET', "/brandfolders/$brandfolder_id/collections", $query_params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * Get a simple array of all Collection names in a Brandfolder, keyed by ID.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $collections = $bf->listAllCollectionNamesInBrandfolder($brandfolder_id);
     * echo "Collections in Brandfolder: <br/>";
     * if ($collections) {
     *   foreach ($collections as $collection_id => $collection_name) {
     *     echo "{$collection_name} ({$collection_id}) <br/>";
     *   }
     * }
     *
     * ```
     *
     * @param string|null $brandfolder_id
     *  The ID of the Brandfolder from which to list Collections. If not provided,
     *  we will attempt to use the default Brandfolder, if defined.
     *
     * @return array|false
     *
     * @see https://developers.brandfolder.com/?http#list-collections
     */
    public function listAllCollectionNamesInBrandfolder(string $brandfolder_id = null): array|false
    {
        if (is_null($brandfolder_id)) {
            if (is_null($this->default_brandfolder_id)) {
                $this->status = 0;
                $this->message = 'A Brandfolder ID must be provided or a default Brandfolder must be set.';

                return false;
            }
            $brandfolder_id = $this->default_brandfolder_id;
        }

        $result = $this->getAll("/brandfolders/$brandfolder_id/collections");

        if ($result) {
            $collections = [];
            foreach ($result->data as $collection) {
                $collections[$collection->id] = $collection->attributes->name;
            }

            return $collections;
        }

        return false;
    }

    /**
     * Alternate name for backward compatibility.
     *
     * @deprecated Use listCollectionsInBrandfolder() or
     *  listAllCollectionNamesInBrandfolder() instead.
     *
     * @see BrandfolderClient::listCollectionsInBrandfolder()
     * @see BrandfolderClient::listAllCollectionNamesInBrandfolder()
     */
    public function getCollectionsInBrandfolder(
        string $brandfolder_id = null,
        array $query_params = [],
        bool $simple_format = true
    ): array|object|false {
        if ($simple_format) {
            return $this->listAllCollectionNamesInBrandfolder($brandfolder_id);
        }

        return $this->listCollectionsInBrandfolder($brandfolder_id, $query_params);
    }

    /**
     * Create a new Collection in a Brandfolder.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $result = $bf->createCollectionInBrandfolder('My Collection', $brandfolder_id,
     *  'The best collection ever!', 'my-coll');
     * if ($result) {
     *  $new_collection = $result->data;
     *  echo "New Collection created: {$new_collection->attributes->name} ({$new_collection->id}) <br/>";
     * }
     *
     * ```
     *
     * @param string $name
     * @param string|null $brandfolder_id
     *  The ID of the Brandfolder in which to create the Collection. If not
     *  provided, we will attempt to use the default Brandfolder, if defined.
     * @param string|null $tagline
     *  A descriptive subtitle/short description of the Collection.
     * @param string|null $slug
     *  String of letters, numbers, hyphens, and underscores.
     *  Note: we recommend *not* to invent your own slug. If it is not valid, the
     *  request will fail with a `422` error. Default is to automatically
     *  assign a slug based on `$name` (a name of `My Collection` would make a
     *  slug of `my-collection`).
     *  If the provided slug is valid but not unique, it will have a number
     *  appended to make it unique.
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#create-a-collection-in-a-brandfolder
     */
    public function createCollectionInBrandfolder(
        string $name,
        ?string $brandfolder_id = null,
        string $tagline = null,
        string $slug = null
    ): object|false {
        if (is_null($brandfolder_id)) {
            if (is_null($this->default_brandfolder_id)) {
                $this->status = 0;
                $this->message = 'A Brandfolder ID must be provided or a default Brandfolder must be set.';

                return false;
            }
            $brandfolder_id = $this->default_brandfolder_id;
        }

        $attributes = [
            'name' => $name,
        ];
        if (!is_null($tagline)) {
            $attributes['tagline'] = $tagline;
        }
        if (!is_null($slug)) {
            $attributes['slug'] = $slug;
        }
        $body = [
            "data" => [
                "attributes" => $attributes,
            ],
        ];

        return $this->request('POST', "/brandfolders/$brandfolder_id/collections", [], $body);
    }

    /**
     * Fetch an individual Collection by ID.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $result = $bf->fetchCollection($collection_id, ['include' => 'brandfolder']);
     * if ($result) {
     *   $collection = $result->data;
     *   echo "{$collection->attributes->name} ({$collection->id}) <br/>";
     *   if (!empty($collection->brandfolders)) {
     *     $brandfolder = reset($collection->brandfolders);
     *     echo "&nbsp; Brandfolder: {$brandfolder->name} ({$brandfolder->id}) <br/>";
     *   }
     * }
     *
     * ```
     *
     * @param string $collection_id
     * @param array|null $query_params
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#fetch-a-collection
     */
    public function fetchCollection(string $collection_id, ?array $query_params = []): object|false
    {
        $result = $this->request('GET', "/collections/$collection_id", $query_params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * Update an existing Collection.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     * $result = $bf->updateCollection($collection_id, ['tagline' => 'This is our most popular collection']);
     * if ($result) {
     *   echo "Collection $collection_id updated successfully. New tagline: {$result->data->attributes->tagline} <br/>";
     * }
     *
     * ```
     *
     * @param string $collection_id
     * @param array $attributes
     *  An associative array of attributes to update. Valid keys are `name`,
     *  `tagline`, and `slug`.
     *  Note: we recommend *not* to invent your own slug. If it is not valid
     *  (must be a string of letters, numbers, hyphens, and underscores), the
     *  request will fail with a `422` error. Default is to automatically
     *  assign a slug based on the name (a name of `My Collection` would make a
     *  slug of `my-collection`).
     *  If the provided slug is valid but not unique, it will have a number
     *  appended to make it unique.
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#update-a-collection
     */
    public function updateCollection(string $collection_id, array $attributes): object|false
    {
        // Remove any invalid attributes, and ensure that we have at least one
        // valid attribute to update.
        $valid_attributes = ['name', 'tagline', 'slug'];
        $attributes = array_intersect_key($attributes, array_flip($valid_attributes));
        if (empty($attributes)) {
            $this->status = 0;
            $this->message = 'No valid attributes provided for updating the Collection.';

            return false;
        }

        $body = [
            "data" => [
                "attributes" => $attributes,
            ],
        ];

        return $this->request('PUT', "/collections/$collection_id", [], $body);
    }

    /**
     * Delete a Collection.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     * $result = $bf->deleteCollection($collection_id);
     * if ($result) {
     *   echo "Collection $collection_id deleted successfully. <br/>";
     * }
     *
     * ```
     *
     * @param string $collection_id
     *
     * @return bool
     *
     * @see https://developers.brandfolder.com/docs/#delete-a-collection
     */
    public function deleteCollection(string $collection_id): object|false
    {
        $result = $this->request('DELETE', "/collections/$collection_id");
        $is_success = $result !== false;

        return $is_success;
    }


    /***************************************************************************
     * Sections *
     ***************************************************************************/

    /**
     * List Sections in a Brandfolder.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $result = $bf->listSectionsInBrandfolder($brandfolder_id, ['include' => 'brandfolder']);
     * if ($result) {
     *   $sections = $result->data;
     *   foreach ($sections as $section) {
     *     echo "{$section->attributes->name} ({$section->id}) <br/>";
     *     if (!empty($section->brandfolders)) {
     *       $brandfolder = reset($section->brandfolders);
     *       echo "&nbsp; Brandfolder: {$brandfolder->name} ({$brandfolder->id}) <br/>";
     *     }
     *   }
     * }
     *
     * ```
     *
     * @param string|null $brandfolder_id
     * @param array $query_params
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/?http#sections
     */
    public function listSectionsInBrandfolder(string $brandfolder_id = null, array $query_params = []): object|false
    {
        if (is_null($brandfolder_id)) {
            if (is_null($this->default_brandfolder_id)) {
                $this->status = 0;
                $this->message = 'A Brandfolder ID must be provided or a default Brandfolder must be set.';

                return false;
            }
            $brandfolder_id = $this->default_brandfolder_id;
        }

        $result = $this->request('GET', "/brandfolders/$brandfolder_id/sections", $query_params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * List all Sections in a Brandfolder (combining all pages of results).
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $sections = $bf->listAllSectionsInBrandfolder($brandfolder_id);
     * if ($sections) {
     *   foreach ($sections->data as $section) {
     *     echo "{$section->attributes->name} ({$section->id}). Default asset type: "
     *      . "{$section->attributes->default_asset_type} <br/>";
     *   }
     * }
     *
     * ```
     *
     * @param string|null $brandfolder_id
     *  The ID of the Brandfolder from which to list Sections. If not provided,
     *  we will attempt to use the default Brandfolder, if defined.
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#list-sections
     */
    public function listAllSectionsInBrandfolder(string $brandfolder_id = null): object|false
    {
        if (is_null($brandfolder_id)) {
            if (is_null($this->default_brandfolder_id)) {
                $this->status = 0;
                $this->message = 'A Brandfolder ID must be provided or a default Brandfolder must be set.';

                return false;
            }
            $brandfolder_id = $this->default_brandfolder_id;
        }

        return $this->getAll("/brandfolders/$brandfolder_id/sections");
    }

    /**
     * Get an array containing the names of all Sections in a Brandfolder,
     * keyed by Section ID.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * echo "Sections in Brandfolder: <br/>";
     * $sections = $bf->listAllSectionNamesInBrandfolder($brandfolder_id);
     * if ($sections) {
     *   foreach ($sections as $section_id => $section_name) {
     *     echo "{$section_name} ({$section_id}) <br/>";
     *   }
     * }
     *
     * ```
     *
     * @param string|null $brandfolder_id
     *  The ID of the Brandfolder from which to list Sections. If not provided,
     *  we will attempt to use the default Brandfolder, if defined.
     *
     * @return array|false
     *
     * @see https://developers.brandfolder.com/?http#sections
     */
    public function listAllSectionNamesInBrandfolder(string $brandfolder_id = null): array|false
    {
        if (is_null($brandfolder_id)) {
            if (is_null($this->default_brandfolder_id)) {
                $this->status = 0;
                $this->message = 'A Brandfolder ID must be provided or a default Brandfolder must be set.';

                return false;
            }
            $brandfolder_id = $this->default_brandfolder_id;
        }

        $sections_result = $this->getAll("/brandfolders/$brandfolder_id/sections");
        if ($sections_result) {
            $sections = [];
            foreach ($sections_result->data as $section) {
                $sections[$section->id] = $section->attributes->name;
            }

            return $sections;
        }

        return false;
    }

    /**
     * Fetch an individual Section by ID.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $result = $bf->fetchSection($section_id, ['include' => 'brandfolder']);
     * if ($result) {
     *  $section = $result->data;
     *  echo "{$section->attributes->name} ({$section->id}) <br/>";
     *  if (!empty($section->brandfolders)) {
     *    $brandfolder = reset($section->brandfolders);
     *    echo "&nbsp; Brandfolder: {$brandfolder->name} ({$brandfolder->id}). Slug: {$brandfolder->slug}. <br/>";
     *  }
     * }
     *
     * ```
     *
     * @param string $section_id
     * @param array|null $query_params
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#fetch-a-section
     *
     * @todo: The API appears to have a bug whereby no assets are returned when specifying `?include=assets`.
     */
    public function fetchSection(string $section_id, ?array $query_params = []): object|false
    {
        $result = $this->request('GET', "/sections/$section_id", $query_params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * Create a new Section in a Brandfolder.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $result = $bf->createSectionInBrandfolder('My New Section', 'GenericFile', $brandfolder_id, 2);
     * if ($result) {
     *   $new_section = $result->data;
     *   echo "New Section created: {$new_section->attributes->name} ({$new_section->id}) <br/>";
     * }
     *
     * ```
     *
     * @param string $name
     *  The name of the Section.
     * @param string|null $default_asset_type
     *  Options are `GenericFile`, `Color`, `Font`, `ExternalMedium`, `Person`,
     *  `Press`, and `Text`.
     *  Defaults to `GenericFile`.
     *  Note: we strongly recommend using `GenericFile` when Assets will be added
     *  to the Section via the API.
     * @param string|null $brandfolder_id
     *  The ID of the Brandfolder in which to create the Section. If not provided,
     *  we will attempt to use the default Brandfolder if one is defined.
     * @param integer|null $position
     *  This is where the new Section is displayed relative to other Sections.
     *  Defaults to the last position. 0 means it will be first.
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#create-a-section
     */
    public function createSectionInBrandfolder(
        string $name,
        ?string $default_asset_type = 'GenericFile',
        ?string $brandfolder_id = null,
        int $position = null
    ): object|false {
        if (is_null($brandfolder_id)) {
            if (is_null($this->default_brandfolder_id)) {
                $this->status = 0;
                $this->message = 'A Brandfolder ID must be provided or a default Brandfolder must be set.';

                return false;
            }
            $brandfolder_id = $this->default_brandfolder_id;
        }

        if (is_null($default_asset_type)) {
            $default_asset_type = 'GenericFile';
        }
        $valid_asset_types = ['GenericFile', 'Color', 'Font', 'ExternalMedium', 'Person', 'Press', 'Text'];
        if (!in_array($default_asset_type, $valid_asset_types)) {
            $this->status = 0;
            $this->message = 'Invalid default asset type. Valid options are "GenericFile," "Color," "Font," '
                . '"ExternalMedium," "Person," "Press," and "Text."';

            return false;
        }

        $attributes = [
            'name' => $name,
            'default_asset_type' => $default_asset_type,
        ];
        if (is_integer($position)) {
            $attributes['position'] = $position;
        }
        $body = [
            "data" => [
                "attributes" => $attributes,
            ],
        ];

        return $this->request('POST', "/brandfolders/$brandfolder_id/sections", [], $body);
    }


    /***************************************************************************
     * Assets *
     ***************************************************************************/

    /**
     * List multiple Assets.
     *
     * @param array|null $query_params
     * @param string|null $collection
     *  An ID of a Collection within which to search for Assets, or `all` to look
     *  throughout the entire Brandfolder. If this param is `null`, the operation
     *  will use the previously defined default Collection, if applicable.
     * @param bool $should_get_all if `true`, retrieve all applicable assets -
     *  i.e. aggregate results from all pages of results. If `false`, the page
     *  size and number will be dictated by the params provided in `$query_params`
     *  (`page`, and `per`). If those are not provided, the default behavior will
     *  be to return the first page of results.
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/?http#list-assets
     */
    public function listAssets(
        ?array $query_params = [],
        string $collection = null,
        bool $should_get_all = false
    ): object|bool {
        if (is_null($collection)) {
            $collection = $this->default_collection_id;
        }
        if (($collection == 'all' || is_null($collection)) && isset($this->default_brandfolder_id)) {
            $endpoint = "/brandfolders/$this->default_brandfolder_id/assets";
        } elseif (!is_null($collection)) {
            $endpoint = "/collections/$collection/assets";
        }
        if (!isset($endpoint)) {
            $this->status = 0;
            $this->message = 'Could not determine endpoint for listing assets. '
                . 'Please set a default Brandfolder or provide a Collection ID.';

            return false;
        }

        // Use getAll() if specifically requested. Do not do this by default, for
        // backwards compatibility and to reduce performance impact for
        // unsuspecting users.
        if ($should_get_all) {
            $result = $this->getAll($endpoint, $query_params);
        } else {
            $result = $this->request('GET', $endpoint, $query_params);

            // Note, we only need to do this here, because getAll() will invoke the
            // same processing (per page) before delivering aggregated result data.
            if ($result) {
                $this->processResultData($result);
            }
        }

        return $result;
    }

    /**
     * List Assets in a specific Organization.
     *
     * Warning: this method will have longer response times than other asset
     * fetch methods. If possible, use `listAssetsInBrandfolder()` or
     * `listAssetsInCollection()`.
     *
     * @param string $organization_id
     * @param array|null $query_params
     *
     * @return object|bool
     *
     * @deprecated
     *  If you need to fetch all assets within an Organization you should instead
     *  do the following: list all Brandfolders (along with the linked
     *  Organization for each); iteratively list Assets for each of those
     *  Brandfolders that belong to the target Organization (see code example
     *  below).
     *
     * ```php
     *
     *  $bf = new BrandfolderClient($api_key);
     *
     *  $all_org_assets = [];
     *  $all_brandfolders = $bf->listBrandfolders(['include' => 'organization']);
     *  foreach ($all_brandfolders->data as $brandfolder) {
     *    if (!empty($brandfolder->organizations)) {
     *      $organization = reset($brandfolder->organizations);
     *      if ($organization->id == $my_org_id) {
     *        $assets = $bf->listAssetsInBrandfolder($brandfolder->id);
     *        if ($assets) {
     *          $all_org_assets = array_merge($all_org_assets, $assets->data);
     *        }
     *      }
     *    }
     *  }
     *
     * ```
     *
     * @see https://developers.brandfolder.com/docs/#list-assets-in-an-organization
     * @see BrandfolderClient::listAssetsInBrandfolder()
     * @see BrandfolderClient::listAssetsInCollection()
     */
    public function listAssetsInOrganization(string $organization_id, ?array $query_params = []): object|bool
    {
        $endpoint = "/organizations/$organization_id/assets";
        $result = $this->request('GET', $endpoint, $query_params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * List Assets in a Brandfolder.
     *
     * ```php
     *
     *  $bf = new BrandfolderClient($api_key);
     *
     *  $assets_result = $bf->listAssetsInBrandfolder($brandfolder_id, ['per' => 150]);
     *  if ($assets_result) {
     *    foreach ($assets_result->data as $asset) {
     *      echo "Asset: {$asset->attributes->name} ({$asset->id}) <br/>";
     *    }
     *  }
     *
     * ```
     *
     * @param string $brandfolder_id
     * @param array|null $query_params
     *
     * @return object|bool
     *
     * @see https://developers.brandfolder.com/docs/#list-assets
     */
    public function listAssetsInBrandfolder(string $brandfolder_id, ?array $query_params = []): object|bool
    {
        $result = $this->request('GET', "/brandfolders/$brandfolder_id/assets", $query_params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * List Assets in a Collection.
     *
     * ```php
     *
     *  $bf = new BrandfolderClient($api_key);
     *
     *  $assets_result = $bf->listAssetsInCollection($collection_id);
     *  if ($assets_result) {
     *    foreach ($assets_result->data as $asset) {
     *      echo "Asset: {$asset->attributes->name} ({$asset->id}) <br/>";
     *    }
     *  }
     *
     * ```
     *
     * @param string $collection_id
     * @param array|null $query_params
     *
     * @return object|bool
     *
     * @see https://developers.brandfolder.com/docs/#list-assets
     */
    public function listAssetsInCollection(string $collection_id, ?array $query_params = []): object|bool
    {
        $result = $this->request('GET', "/collections/$collection_id/assets", $query_params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * List Assets in a Label.
     *
     * ```php
     *
     *  $bf = new BrandfolderClient($api_key);
     *
     *  $assets_result = $bf->listAssetsInLabel($label_id);
     *  if ($assets_result) {
     *    foreach ($assets_result->data as $asset) {
     *      echo "Asset: {$asset->attributes->name} ({$asset->id}) <br/>";
     *    }
     *  }
     *
     * ```
     *
     * @param string $label_id
     * @param array|null $query_params
     *
     * @return object|bool
     *
     * @see https://developers.brandfolder.com/docs/#list-assets
     */
    public function listAssetsInLabel(string $label_id, ?array $query_params = []): object|bool
    {
        $result = $this->request('GET', "/labels/$label_id/assets", $query_params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * Fetch an individual Asset.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * // Example A. Get standard asset data.
     * $result = $bf->fetchAsset($asset_id);
     * if ($result) {
     *   $asset = $result->data;
     *   echo "Asset: {$asset->attributes->name} ({$asset->id}) <br />";
     *   echo "&nbsp; Description: {$asset->attributes->description} <br />";
     *   echo "&nbsp; Thumbnail: <img src=\"{$asset->attributes->thumbnail_url}\" /> <br />";
     * }
     *
     * // Example B. Get asset data with CDN URL and attachments.
     * $result = $bf->fetchAsset($asset_id, ['fields' => 'cdn_url', 'include' => 'attachments']);
     * if ($result) {
     *   $asset = $result->data;
     *   echo "Asset: {$asset->attributes->name} ({$asset->id}) <br />";
     *   echo "&nbsp; Default CDN Image: <img src=\"{$asset->attributes->cdn_url}\" /> <br />";
     *   if (!empty($asset->attachments)) {
     *     foreach ($asset->attachments as $attachment) {
     *       echo "&nbsp; Attachment: {$attachment->filename} ({$attachment->id}) <br />";
     *     }
     *   }
     * }
     *
     * ```
     *
     * @param string $asset_id
     * @param array|null $query_params
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/?python#fetch-an-asset
     */
    public function fetchAsset(string $asset_id, ?array $query_params = []): object|bool
    {
        $result = $this->request('GET', "/assets/$asset_id", $query_params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * Create a new Asset.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $attachments = [
     *   [
     *     'url' => 'https://example.com/image-01.jpg',
     *     'filename' => 'image-01.jpg',
     *   ],
     *   [
     *     'url' => 'https://example.com/image-02.jpg',
     *     'filename' => 'image-02.jpg',
     *   ],
     * ];
     * $result = $bf->createAsset('My New Asset', $attachments, '$existing_section_key', 'A description of the asset.');
     * if ($result) {
     *   $new_asset = $result->data[0];
     *   echo "New Asset created: {$new_asset->attributes->name} ({$new_asset->id}) <br/>";
     * }
     *
     * ```
     *
     * @param string $name
     * @param array $attachments
     * @param string $section
     * @param string|null $description
     * @param string|null $brandfolder
     * @param string|null $collection
     * @param string|int|\DateTime|null $availability_start
     *  This can be a date/time string, a timestamp, or a `DateTime` object.
     * @param string|int|\DateTime|null $availability_end
     *  This can be a date/time string, a timestamp, or a `DateTime` object.
     *
     * @return object|false
     *
     * @see https://developer.brandfolder.com/#create-assets
     */
    public function createAsset(
        string $name,
        array $attachments,
        string $section,
        string $description = null,
        string $brandfolder = null,
        string $collection = null,
        string|int|\DateTime $availability_start = null,
        string|int|\DateTime $availability_end = null
    ): object|false {
        $asset = [
            'name' => $name,
            'attachments' => $attachments,
        ];
        if (!is_null($description)) {
            $asset['description'] = $description;
        }
        if (!is_null($availability_start)) {
            $asset['availability_start'] = $availability_start;
        }
        if (!is_null($availability_end)) {
            $asset['availability_end'] = $availability_end;
        }
        $assets = [$asset];

        return $this->createAssets($assets, $section, $brandfolder, $collection);
    }

    /**
     * Create multiple new Assets in one operation.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $assets = [
     *   [
     *     'name' => 'My New Asset 1',
     *     'description' => 'The first asset.',
     *     'availability_start' => 'tomorrow',
     *     'attachments' => [
     *       [
     *         'url' => 'https://example.com/image-01.jpg',
     *         'filename' => 'image-01.jpg',
     *       ]
     *     ],
     *   ],
     *   [
     *     'name' => 'My New Asset 2',
     *     'description' => 'The second asset.',
     *     'availability_end' => '2025-12-31',
     *     'attachments' => [
     *       [
     *         'url' => 'https://example.com/image-02.jpg',
     *         'filename' => 'image-02.jpg',
     *       ]
     *     ],
     *   ],
     * ];
     * $result = $bf->createAssets($assets, $existing_section_key);
     * if ($result) {
     *   echo "New assets created: <br/>";
     *   foreach ($result as $new_asset) {
     *     echo "&nbsp; {$new_asset->attributes->name} ({$new_asset->id}) <br/>";
     *   }
     * }
     *
     * ```
     *
     * @param array $assets
     *  Array of arrays with asset data. Each asset data array should consist of:
     *    `name` (`string`),
     *    `description` (optional `string`),
     *    `availability_start` (optional `string`|`int`|`DateTime`),
     *    `availability_end` (optional `string`|`int`|`DateTime`),
     *    `attachments` (`array`), consisting of:
     *      `url` (`string`),
     *      `filename` (`string`)
     * @param string $section
     * @param string|null $brandfolder
     * @param string|null $collection
     *
     * @return array|false
     *
     * @see https://developer.brandfolder.com/#create-assets
     */
    public function createAssets(
        array $assets,
        string $section,
        string $brandfolder = null,
        string $collection = null
    ): array|false {
        if (is_null($brandfolder) && is_null($collection)) {
            if (!is_null($this->default_brandfolder_id)) {
                $brandfolder = $this->default_brandfolder_id;
            } elseif (!is_null($this->default_collection_id)) {
                $collection = $this->default_collection_id;
            }
        }
        if (!is_null($brandfolder)) {
            $endpoint = "/brandfolders/$brandfolder/assets";
        } elseif (!is_null($collection)) {
            $endpoint = "/collections/$collection/assets";
        }
        if (!isset($endpoint)) {
            $this->status = 0;
            $this->message = 'A Brandfolder or a Collection must be specified when creating an asset.';

            return false;
        }

        // Format & loosely valadate any date values.
        array_walk($assets, function (&$asset) {
            foreach (['availability_start', 'availability_end'] as $date_field) {
                if (!empty($asset[$date_field])) {
                    $formatted_datetime = $this->formatDateTime($asset[$date_field]);
                    if ($formatted_datetime) {
                        $asset[$date_field] = $formatted_datetime;
                    } else {
                        unset($asset[$date_field]);
                    }
                }
            }
        });

        $body = [
            "data" => [
                "attributes" => $assets,
            ],
            "section_key" => $section,
        ];

        $result = $this->request('POST', $endpoint, [], $body);

        if ($result && !empty($result->data)) {
            return $result->data;
        } else {
            return false;
        }
    }

    /**
     * Update an existing asset.
     *
     * ```php
     *
     *  $bf = new BrandfolderClient($api_key);
     *
     *  // Example A. Update an asset's name.
     *  $asset = $bf->updateAsset($asset_id, 'My Updated Asset Name');
     *  if ($asset) {
     *    echo "Asset $asset_id updated successfully. New name: {$asset->attributes->name} <br/>";
     *  }
     *
     *  // Example B. Add a new attachment to an asset.
     *  $new_attachments = [
     *    [
     *      'url' => 'https://www.example.com/image-02.jpg',
     *    ],
     *  ];
     *  $asset = $bf->updateAsset($asset_id, NULL, NULL, $new_attachments);
     *  if ($asset) {
     *    echo "Attachment added to asset $asset_id. <br/>";
     *  }
     *
     *  // Example C. Update an asset's expiration/unpublish date.
     *  $expiration_date = '2025-01-31';
     *  $asset = $bf->updateAsset($asset_id, NULL, NULL, NULL, NULL, $expiration_date);
     *  if ($asset) {
     *    echo "Asset $asset_id will now expire on $expiration_date. <br/>";
     *  }
     *
     * ```
     *
     * @param string $asset_id
     * @param string|null $name
     * @param string|null $description
     * @param array|null $attachments
     *  An array of Attachments to add to the asset. Each Attachment should be
     *  an associative array with keys `url` (required) and `filename` (optional).
     *  If the `filename` key is omitted, the filename will be extracted from the
     *  URL.
     * @param string|int|\DateTime|null $availability_start
     *  To set/update the date at which this asset will be published, provide a
     *  date/time string, a timestamp, or a `DateTime` object. Alternatively,
     *  to remove/clear an existing start date, provide the string `none`.
     * @param string|int|\DateTime|null $availability_end
     *  To set/update the date at which this asset will be unpublished, provide a
     *  date/time string, a timestamp, or a `DateTime` object. Alternatively,
     *  to remove/clear an existing end date, provide the string `none`.
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/#update-an-asset
     */
    public function updateAsset(
        string $asset_id,
        ?string $name = null,
        ?string $description = null,
        ?array $attachments = null,
        string|int|\DateTime $availability_start = null,
        string|int|\DateTime $availability_end = null
    ): object|false {
        $attributes = [];
        if (!is_null($name)) {
            $attributes['name'] = $name;
        }
        if (!is_null($description)) {
            $attributes['description'] = $description;
        }
        if (!is_null($attachments)) {
            $attributes['attachments'] = $attachments;
        }
        if (!is_null($availability_start)) {
            if ($availability_start === 'none') {
                $attributes['availability_start'] = null;
            } else {
                $formatted_datetime = $this->formatDateTime($availability_start);
                if ($formatted_datetime) {
                    $attributes['availability_start'] = $formatted_datetime;
                }
            }
        }
        if (!is_null($availability_end)) {
            if ($availability_end === 'none') {
                $attributes['availability_end'] = null;
            } else {
                $formatted_datetime = $this->formatDateTime($availability_end);
                if ($formatted_datetime) {
                    $attributes['availability_end'] = $formatted_datetime;
                }
            }
        }

        $body = [
            "data" => [
                "attributes" => $attributes,
            ],
        ];

        $result = $this->request('PUT', "/assets/$asset_id", [], $body);

        if ($result && !empty($result->data)) {
            return $result->data;
        } else {
            return false;
        }
    }

    /**
     * Delete an Asset.
     *
     * ```php
     *
     *  $bf = new BrandfolderClient($api_key);
     *
     *  $result = $bf->deleteAsset($asset_id);
     *  if ($result) {
     *    echo "Asset $asset_id was successfully deleted. <br/>";
     *  }
     *
     * ```
     *
     * @param string $asset_id
     *
     * @return bool true if successfully deleted, false otherwise.
     *
     * @see https://developers.brandfolder.com/#delete-an-asset
     */
    public function deleteAsset(string $asset_id): bool
    {
        $result = $this->request('DELETE', "/assets/$asset_id");
        $is_success = $result !== false;

        return $is_success;
    }


    /***************************************************************************
     * Attachments *
     ***************************************************************************/

    /**
     * List Attachments in a given context/scope (Organization, Brandfolder,
     * Collection, or Asset).
     *
     * ```php
     *
     *  $bf = new BrandfolderClient($api_key);
     *  $query_params = [
     *    'include' => 'asset',
     *    'fields' => 'cdn_url',
     *  ];
     *  $attachments = $bf->listAttachments('brandfolder', $brandfolder_id, $query_params);
     *
     * ```
     *
     * Sample Data for `$attachments`:
     * ```json
     *
     *   {
     *     "data": [
     *       0 => {
     *         "id": "attachment-id-abc-123",
     *         "type": "attachments",
     *         "attributes": {
     *           "filename": "example.jpg",
     *           "url": "https://assets2.brandfolder.io/bf-bou-prod/attachment-id-abc-123/v/12345/original/example.jpg",
     *           "cdn_url": "https://cdn.bfldr.com/DEFG123/at/attachment-id-abc-123/example.jpg?auto=webp&format=png",
     *           "thumbnail_url": "https://thumbs.bfldr.com/at/attachment-id-abc-123?expiry=1720620621&fit=bounds&height=162&sig=LONGSIGNATURE%3D%3D&width=262",
     *           "size": 123456,
     *           ...
     *         },
     *         "relationships": {
     *           "asset": {
     *             "data": {
     *               "id": "asset-id-abc-123",
     *               "type": "generic_files"
     *             }
     *           }
     *         },
     *         "generic_files": [
     *           "asset-id-abc-123": {
     *             "id": "asset-id-abc-123",
     *             "name": "Example JPG Asset",
     *             "approved": true,
     *             "thumbnail_url": "https://thumbs.bfldr.com/as/asset-id-abc-123?expiry=1720620621&fit=bounds&height=162&sig=LONGSIGNATURE%3D%3D&width=262",
     *             ...
     *           }
     *         ]
     *       },
     *       ...
     *     ],
     *     "included": [
     *       "generic_files": [
     *         "asset-id-abc-123": {
     *           "id": "asset-id-abc-123",
     *           "name": "Example JPG Asset",
     *           "approved": true,
     *           "thumbnail_url": "https://thumbs.bfldr.com/as/asset-id-abc-123?expiry=1720620621&fit=bounds&height=162&sig=LONGSIGNATURE%3D%3D&width=262",
     *           ...
     *         },
     *         "asset-id-def-456": {
     *           "id": "asset-id-def-456",
     *           "name": "Another Asset Linked to A Different Attachment",
     *           "approved": true,
     *           "thumbnail_url": "https://thumbs.bfldr.com/as/asset-id-def-456?expiry=1720620621&fit=bounds&height=162&sig=LONGSIGNATURE%3D%3D&width=262",
     *           ...
     *         },
     *         ...
     *       ]
     *     ]
     *   }
     *
     * ```
     *
     * @param string $context The type of entity for which to list attachments.
     *  Valid values are: `organization`, `brandfolder`, `collection`, `asset`.
     *  Note: `organization` is being deprecated. If you need to fetch attachments
     *  across an entire Org, you can: (1) list all Brandfolders
     *  (`include=organization`), then (2) list all Sections within each
     *  of those Brandfolders that belongs to the relevant Org, then (3) list all
     *  Attachments within each of those Sections.
     * @param string $context_id
     * @param array|null $query_params
     *
     * @return object|bool
     *
     * @see https://developers.brandfolder.com/docs/#list-attachments
     */
    public function listAttachments(string $context, string $context_id, ?array $query_params = []): object|bool
    {
        $context_endpoint_string = null;
        switch ($context) {
            case 'organization':
                $context_endpoint_string = 'organizations';
                break;
            case 'brandfolder':
                $context_endpoint_string = 'brandfolders';
                break;
            case 'collection':
                $context_endpoint_string = 'collections';
                break;
            case 'section':
                $context_endpoint_string = 'sections';
                break;
            case 'asset':
                $context_endpoint_string = 'assets';
                break;
            default:
                $this->status = 0;
                $this->message = 'Invalid context for listing attachments.';

                return false;
        }
        $endpoint = "/$context_endpoint_string/$context_id/attachments";
        $result = $this->request('GET', $endpoint, $query_params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * List Attachments for a given Organization.
     *
     * @param string $organization_id
     * @param array|null $query_params
     *
     * @return object|bool
     *
     * @deprecated If you need to fetch Attachments across an entire Org, you
     *  can: (1) list all Brandfolders (`include=organization`), then (2) list
     *  all Sections within each of those Brandfolders that belongs to the
     *  relevant Org, then (3) list all Attachments within each of those Sections.
     *
     * @see BrandfolderClient::listBrandfolders()
     * @see BrandfolderClient::listAllSectionsInBrandfolder()
     * @see BrandfolderClient::listAttachmentsInSection()
     *
     * @see BrandfolderClient::listAssetsInOrganization()
     */
    public function listAttachmentsForOrganization(string $organization_id, ?array $query_params = []): object|bool
    {
        return $this->listAttachments('organization', $organization_id, $query_params);
    }

    /**
     * List Attachments for a given Brandfolder.
     *
     * ```php
     *
     *  $bf = new BrandfolderClient($api_key);
     *
     *  $attachments_result = $bf->listAttachmentsForBrandfolder($brandfolder_id);
     *  if ($attachments_result) {
     *    foreach ($attachments_result->data as $attachment) {
     *      echo "Attachment: {$attachment->attributes->filename} ({$attachment->id}) <br/>";
     *    }
     *  }
     *
     * ```
     *
     * @param string $brandfolder_id
     * @param array|null $query_params
     *
     * @return object|bool
     *
     * @see BrandfolderClient::listAttachments()
     */
    public function listAttachmentsForBrandfolder(string $brandfolder_id, ?array $query_params = []): object|bool
    {
        return $this->listAttachments('brandfolder', $brandfolder_id, $query_params);
    }

    /**
     * List Attachments for a given Collection.
     *
     * ```php
     *
     *  $bf = new BrandfolderClient($api_key);
     *
     *  $attachments_result = $bf->listAttachmentsForCollection($collection_id);
     *  if ($attachments_result) {
     *    foreach ($attachments_result->data as $attachment) {
     *      echo "Attachment: {$attachment->attributes->filename} ({$attachment->id}) <br/>";
     *    }
     *  }
     *
     * ```
     *
     * @param string $collection_id
     * @param array|null $query_params
     *
     * @return object|bool
     *
     * @see BrandfolderClient::listAttachments()
     */
    public function listAttachmentsForCollection(string $collection_id, ?array $query_params = []): object|bool
    {
        return $this->listAttachments('collection', $collection_id, $query_params);
    }

    /**
     * List Attachments for a given Section.
     *
     * ```php
     *
     *   $bf = new BrandfolderClient($api_key);
     *
     *   $attachments_result = $bf->listAttachmentsForSection($section_id);
     *   if ($attachments_result) {
     *     foreach ($attachments_result->data as $attachment) {
     *       echo "Attachment: {$attachment->attributes->filename} ({$attachment->id}) <br/>";
     *     }
     *   }
     *
     *  ```
     *
     * @param string $section_id
     * @param array|null $query_params
     *
     * @return object|bool
     *
     * @see BrandfolderClient::listAttachments()
     */
    public function listAttachmentsForSection(string $section_id, ?array $query_params = []): object|bool
    {
        return $this->listAttachments('section', $section_id, $query_params);
    }

    /**
     * List Attachments for a given Asset.
     *
     * ```php
     *
     *  $bf = new BrandfolderClient($api_key);
     *
     *  $attachments_result = $bf->listAttachmentsForAsset($asset_id);
     *  if ($attachments_result) {
     *    foreach ($attachments_result->data as $attachment) {
     *      echo "Attachment: {$attachment->attributes->filename} ({$attachment->id}) <br/>";
     *    }
     *  }
     *
     * ```
     *
     * @param string $asset_id
     * @param array|null $query_params
     *
     * @return object|bool
     *
     * @see listAttachments()
     */
    public function listAttachmentsForAsset(string $asset_id, ?array $query_params = []): object|bool
    {
        return $this->listAttachments('asset', $asset_id, $query_params);
    }

    /**
     * Fetch an individual Attachment.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     * $result = $bf->fetchAttachment($attachment_id, ['fields' => 'cdn_url']);
     * if ($result) {
     *   $attachment = $result->data;
     *   echo "Attachment: {$attachment->attributes->filename} ({$attachment->id}) <br />";
     *   echo "&nbsp; CDN URL: {$attachment->attributes->cdn_url} <br />";
     *   echo "&nbsp; File size (in bytes): " . $attachment->attributes->size . " <br />";
     * }
     *
     * ```
     *
     * @param string $attachment_id
     * @param array|null $params
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#fetch-an-attachment
     */
    public function fetchAttachment(string $attachment_id, ?array $params = []): object|false
    {
        $result = $this->request('GET', "/attachments/$attachment_id", $params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * Update an existing Attachment.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     * $result = $bf->updateAttachment($attachment_id, 'https://example.com/updated-image.jpg', 'updated-image.jpg');
     * if ($result) {
     *   echo "Attachment $attachment_id updated successfully. <br/>";
     * }
     *
     * ```
     *
     * @param string $attachment_id
     * @param string|null $url
     * @param string|null $filename
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/#update-an-attachment
     */
    public function updateAttachment(string $attachment_id, string $url = null, string $filename = null): object|false
    {
        $attributes = [];
        if (!is_null($url)) {
            $attributes['url'] = $url;
        }
        if (!is_null($filename)) {
            $attributes['filename'] = $filename;
        }
        $body = [
            "data" => [
                "attributes" => $attributes,
            ],
        ];

        return $this->request('PUT', "/attachments/$attachment_id", [], $body);
    }

    /**
     * Delete an existing attachment.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     * $result = $bf->deleteAttachment($attachment_id);
     * if ($result) {
     *   echo "Attachment $attachment_id was successfully deleted.";
     * }
     *
     * ```
     *
     * @param string $attachment_id
     *
     * @return bool true on successful deletion. false on failure.
     *
     * @see https://developers.brandfolder.com/docs/#delete-an-attachment
     */
    public function deleteAttachment(string $attachment_id): bool
    {
        $result = $this->request('DELETE', "/attachments/$attachment_id");
        $is_success = $result !== false;

        return $is_success;
    }


    /***************************************************************************
     * Custom Fields *
     ***************************************************************************/

    /**
     * Gets custom field data for a Brandfolder.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * // Example A.
     * $result = $bf->listCustomFields($brandfolder_id);
     * if ($result) {
     *   foreach ($result->data as $custom_field) {
     *     echo "Custom field key name: {$custom_field->attributes->name} (ID: {$custom_field->id}) <br/>";
     *     echo "&nbsp; Allowed values: ";
     *     if ($custom_field->attributes->restricted) {
     *       echo implode(', ', $custom_field->attributes->allowed_values) . "<br/>";
     *     }
     *     else {
     *       echo "Any. <br/>";
     *     }
     *   }
     * }
     *
     * // Example B.
     * $result = $bf->listCustomFields($brandfolder_id, TRUE);
     * if ($result) {
     *   foreach ($result->data as $custom_field) {
     *     $key_name = $custom_field->attributes->name;
     *     echo "Custom field key name: {$key_name} <br/>";
     *     $values_list = $custom_field->attributes->values;
     *     echo "&nbsp; All values currently in use: "
     *      . !empty($values_list) ? implode(', ', $values_list) : 'None' . "<br/>";
     *   }
     * }
     *
     * // Example C.
     * $result = $bf->listCustomFields($brandfolder_id, TRUE, TRUE);
     * if ($result) {
     *   foreach ($result as $custom_field_name => $values) {
     *     echo "$custom_field_name: <br/>";
     *     foreach ($values as $value) {
     *       echo "&nbsp; $value <br/>";
     *     }
     *   }
     * }
     *
     * ```
     *
     * @param string|null $brandfolder_id
     * @param bool $include_values
     * @param bool $simple_format
     *  If true, return a simple array. The format of this array depends on the
     *  $include_values param. If `true`, return an array whose keys are custom
     *  field names and whose values are arrays of all the values that currently
     *  exist in Brandfolder for a given field. If `false`, return an array whose
     *  keys are custom field ids and whose values are custom field names.
     *
     * @return array|object|false
     *
     * @see https://developer.brandfolder.com/docs/#list-custom-field-keys-for-a-brandfolder
     */
    public function listCustomFields(
        string $brandfolder_id = null,
        bool $include_values = false,
        bool $simple_format = false
    ): array|object|false {
        if (is_null($brandfolder_id)) {
            if (is_null($this->default_brandfolder_id)) {
                $this->status = 0;
                $this->message = 'A Brandfolder ID must be provided or a default Brandfolder must be set.';

                return false;
            }
            $brandfolder_id = $this->default_brandfolder_id;
        }

        $query_params = [];
        if ($include_values) {
            $query_params['fields'] = 'values';
            // @todo: Determine whether we really need both of these.
            $query_params['include'] = 'custom_field_values';
        }

        $custom_field_result = $this->getAll("/brandfolders/$brandfolder_id/custom_field_keys", $query_params);
        if ($custom_field_result) {
            if ($simple_format) {
                $custom_fields = [];
                // Array whose keys are custom field names and whose values are arrays
                // of all the values that currently exist in Brandfolder for a given
                // field.
                if ($include_values) {
                    foreach ($custom_field_result->data as $custom_field_data) {
                        $custom_fields[$custom_field_data->attributes->name] = $custom_field_data->attributes->values;
                    }
                } else {
                    // Array whose keys are custom field ids and whose values are custom
                    // field names.
                    foreach ($custom_field_result->data as $custom_field_data) {
                        $custom_fields[$custom_field_data->id] = $custom_field_data->attributes->name;
                    }
                }

                return $custom_fields;
            } else {
                return $custom_field_result;
            }
        }

        return false;
    }

    /**
     * Create custom field keys for a Brandfolder.
     *
     * This is only needed for setting up controlled Custom Fields. If this
     * feature is enabled for your Brandfolder, you can set the allowed keys and
     * optionally restrict their allowed values for Custom Fields using this
     * method.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $custom_fields = [
     *   // Controlled custom field (allowed values are restricted).
     *   [
     *     'name' => 'favorite_coffee',
     *     'allowed_values' => [
     *       'Peruvian',
     *       'Colombian',
     *       'Ethiopian',
     *       'Sumatran',
     *       'Global Blend',
     *     ]
     *   ],
     *   // Uncontrolled custom field (any string value is allowed).
     *   [
     *     'name' => 'vortex_strength'
     *   ]
     * ];
     * $result = $bf->createCustomFieldKeysForBrandfolder($custom_fields, $brandfolder_id);
     *
     * ```
     *
     * @param array $custom_field_data
     *  An array of associative arrays - one for each new custom field. Each
     *  associative array should have the following structure:
     *    - `name`: Required. The name of the custom field [key].
     *    - `allowed_values`: Optional. The value that can be used with this key
     *      when creating or updating any Custom Field on an Asset must be one of
     *      these strings. If not included or empty array `[]`, the values are not
     *      restricted.
     * @param string|null $brandfolder_id
     *  The ID of the Brandfolder in which to create the custom field keys. If
     *  not provided, we will attempt to use the default Brandfolder if one is
     *  defined.
     *
     * @return object|false
     *
     * @see https://developer.brandfolder.com/docs/#create-custom-field-keys-for-a-brandfolder
     */
    public function createCustomFieldKeysForBrandfolder(
        array $custom_field_data,
        ?string $brandfolder_id = null
    ): object|false {
        if (is_null($brandfolder_id)) {
            if (is_null($this->default_brandfolder_id)) {
                $this->status = 0;
                $this->message = 'A Brandfolder ID must be provided or a default Brandfolder must be set.';

                return false;
            }
            $brandfolder_id = $this->default_brandfolder_id;
        }

        // Pre-validate the custom field data.
        foreach ($custom_field_data as $field) {
            if (!isset($field['name'])) {
                $this->status = 0;
                $this->message = 'Custom field key name is required.';

                return false;
            }
            // Ensure only valid keys exist.
            $valid_keys = ['name', 'allowed_values'];
            $field = array_intersect_key($field, array_flip($valid_keys));
            // Ensure allowed_values is at least an array.
            if (isset($field['allowed_values']) && !is_array($field['allowed_values'])) {
                $this->status = 0;
                $this->message = 'Allowed values must be an array of strings.';

                return false;
            }
        }

        $body = [
            'data' => [
                'attributes' => $custom_field_data
            ],
        ];

        return $this->request('POST', "/brandfolders/$brandfolder_id/custom_field_keys", [], $body);
    }

    /**
     * Update an individual custom field key by ID.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $new_coffee_options = [
     *   'Costa Rican',
     *   'Peruvian',
     *   'Colombian',
     *   'Ethiopian',
     *   'Sumatran',
     *   'Global Blend'
     * ];
     *
     * $result = $bf->updateCustomFieldKey($custom_field_id, ['allowed_values' => $new_coffee_options]);
     *
     * ```
     *
     * @param string $custom_field_id
     * @param array $attributes
     *  An associative array of attributes to update. Valid keys are `name` and
     *  `allowed_values`.
     *
     * @return object|false
     *
     * @see https://developer.brandfolder.com/docs/#update-a-custom-field-key
     */
    public function updateCustomFieldKey(string $custom_field_id, array $attributes): object|false
    {
        // Remove any invalid attributes, and ensure that we have at least one
        // valid attribute to update.
        // @todo: Test and consider allowing other, undocumented attributes.
        $valid_attributes = ['name', 'allowed_values'];
        $attributes = array_intersect_key($attributes, array_flip($valid_attributes));
        if (empty($attributes)) {
            $this->status = 0;
            $this->message = 'No valid attributes provided for updating the Custom Field Key.';

            return false;
        }

        $body = [
            "data" => [
                "attributes" => $attributes,
            ],
        ];

        return $this->request('PUT', "/custom_field_keys/$custom_field_id", [], $body);
    }

    /**
     * Delete a custom field key.
     *
     * Caution: be very careful when using this method, as it also deletes all
     * associated values for the given key.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     * $result = $bf->deleteCustomFieldKey($custom_field_key_id);
     * if ($result) {
     *   echo "Custom field key $custom_field_key_id was successfully deleted. <br/>";
     * }
     *
     * ```
     *
     * @param string $custom_field_key_id
     *
     * @return bool `true` if the custom field key was successfully deleted.
     *
     * @see https://developers.brandfolder.com/docs/#delete-a-custom-field-key
     */
    public function deleteCustomFieldKey(string $custom_field_key_id): bool
    {
        $result = $this->request('DELETE', "/custom_field_keys/$custom_field_key_id");
        $is_success = $result !== false;

        return $is_success;
    }

    /**
     * Add custom field values to an asset.
     *
     *  ```php
     *
     *  $bf = new BrandfolderClient($api_key, $default_brandfolder_id);
     *
     *  // Example A. Add custom field values to an asset by custom field name.
     *  $custom_field_values = [
     *    'eye-color' => 'brown',
     *    'hair-color' => 'black',
     *  ];
     *  $result = $bf->addCustomFieldsToAsset($asset_id, $custom_field_values);
     *
     *  // Example B. Add custom field values to an asset by custom field ID.
     *  $custom_field_values = [
     *    $eye_color_custom_field_id => 'brown',
     *    $hair_color_custom_field_id => 'black',
     *  ];
     *  $result = $bf->addCustomFieldsToAsset($asset_id, $custom_field_values, 'id');
     *
     * ```
     *
     * Note: The API endpoint previously used by this method
     *  (`/custom_field_keys/{custom_field_key_id}/custom_field_values`) was
     *  seemingly removed on `2023-05-17`. This method was then updated to work
     *  with the new endpoint/pattern while maintaining backward compatibility.
     *
     * @param string $asset_id
     * @param array $custom_field_values
     *  Associative array with key-value pairs. The key should identify the custom
     *  field (by name or ID, depending on the `$field_key_type` param), and the value
     *  should be the value you wish to store on the given asset for that field.
     * @param string|null $field_key_type If `name` (default), the
     *  `$custom_field_values` array should be keyed by custom field names
     *  (e.g. `eye-color`). If `id`, the `$custom_field_values` array should be
     *  keyed by custom field IDs (e.g. `h3689xgmx2jx558xxnt16xp`).
     *  Note: `name` mode is more convenient, but
     *  (a) requires an extra API call to look up the custom field IDs, and
     *  (b) can cause functionality to break if you are invoking this method with
     *  a hard-coded name and the custom field's name is subsequently changed.
     * @param string|null $brandfolder_id
     *  The ID of the Brandfolder in which the asset resides. If not provided, we
     *  will attempt to use the default Brandfolder if one is defined.
     *  This is only necessary if using the `name` option for `$field_key_type`.
     *
     * @return bool `true` if all requests on success, `false` otherwise.
     *
     * @see https://developer.brandfolder.com/#create-custom-fields-for-an-asset
     */
    public function addCustomFieldsToAsset(
        string $asset_id,
        array $custom_field_values,
        ?string $field_key_type = 'name',
        ?string $brandfolder_id = null
    ): bool {
        $is_total_success = true;
        $messages = [];

        if ($field_key_type == 'name') {
            // First we need to look up custom field keys/IDs, so we can map the
            // friendly string names to IDs (which the Brandfolder API requires for
            // the subsequent POST request).
            if (is_null($brandfolder_id)) {
                if (is_null($this->default_brandfolder_id)) {
                    $this->status = 0;
                    $this->message = 'A Brandfolder ID must be provided or a default Brandfolder must be set.';

                    return false;
                }
                $brandfolder_id = $this->default_brandfolder_id;
            }
            $custom_field_ids_and_names = $this->listCustomFields($brandfolder_id, false, true);
            if (!$custom_field_ids_and_names) {
                $this->status = 0;
                $this->message = 'Could not retrieve custom fields from Brandfolder.';

                return false;
            }
            $custom_field_names_and_ids = array_flip($custom_field_ids_and_names);
            $relevant_field_ids_and_values = [];
            foreach ($custom_field_values as $custom_field_key_name => $new_value) {
                if (isset($custom_field_names_and_ids[$custom_field_key_name])) {
                    $custom_field_key_id = $custom_field_names_and_ids[$custom_field_key_name];
                    $relevant_field_ids_and_values[$custom_field_key_id] = $new_value;
                } else {
                    $is_total_success = false;
                    $messages[] = "Custom field '$custom_field_key_name' appears to no longer exist in Brandfolder, "
                        . "so we cannot add a value for it.";
                }
            }
            $custom_field_values = $relevant_field_ids_and_values;
        }

        foreach ($custom_field_values as $custom_field_key_id => $value) {
            $body = [
                'data' => [
                    [
                        'attributes' => [
                            'value' => $value,
                        ],
                        'relationships' => [
                            'asset' => [
                                'data' => [
                                    'type' => 'assets',
                                    'id' => $asset_id,
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $result = $this->request('POST', "/custom_field_keys/$custom_field_key_id/custom_field_values", [], $body);

            if ($result) {
                $success_phrase = 'Successfully added';
            } else {
                $success_phrase = 'Failed to add';
                $is_total_success = false;
            }
            $messages[] = "$success_phrase custom field value for custom field key ID $custom_field_key_id "
                . "to asset $asset_id.";
        }

        $this->message = implode(' ', $messages);

        return $is_total_success;
    }

    /**
     * Set custom field values for a given custom field, on one or more
     * assets.
     *
     * @param string $custom_field_key_id
     *  The ID of the custom field key for which to set values.
     * @param array $custom_field_values_per_asset
     *  An associative array where keys are asset IDs and values are the new
     *  values for the custom field on the asset.
     *
     * @return bool|object
     *
     * @see https://developers.brandfolder.com/docs/#create-custom-fields-for-an-asset
     */
    public function setCustomFieldValuesForAssets(
        string $custom_field_key_id,
        array $custom_field_values_per_asset
    ): bool|object {
        $body = [
            'data' => []
        ];

        foreach ($custom_field_values_per_asset as $asset_id => $field_value) {
            $body['data'][] = [
                'attributes' => [
                    'value' => $field_value,
                ],
                'relationships' => [
                    'asset' => [
                        'data' => [
                            'type' => 'assets',
                            'id' => $asset_id,
                        ],
                    ],
                ],
            ];
        }

        return $this->request('POST', "/custom_field_keys/$custom_field_key_id/custom_field_values", [], $body);
    }

    /**
     * List custom field values for a given asset.
     *
     * @param string $asset_id
     * @param array|null $query_params
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#list-custom-field-values-for-an-asset
     */
    public function listCustomFieldValuesForAsset(string $asset_id, array $query_params = []): object|false
    {
        $result = $this->request('GET', "/assets/$asset_id/custom_field_values", $query_params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * Update an existing custom field value by value ID
     *
     * Note: the custom field value ID is different than the custom field key
     * ID. Every single value instance has its own ID.
     *
     * @param string $custom_field_value_id
     * @param string $value
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#update-a-custom-field-value
     */
    public function updateCustomFieldValue(string $custom_field_value_id, string $value): object|false
    {
        $body = [
            "data" => [
                "attributes" => [
                    "value" => $value,
                ],
            ],
        ];

        return $this->request('PUT', "/custom_field_values/$custom_field_value_id", [], $body);
    }

    /**
     * Delete a custom field value.
     *
     * @param string $custom_field_value_id
     *
     * @return bool true on successful deletion, false otherwise.
     *
     * @see https://developers.brandfolder.com/docs/#delete-a-custom-field-value
     */
    public function deleteCustomFieldValue(string $custom_field_value_id): bool
    {
        $result = $this->request('DELETE', "/custom_field_values/$custom_field_value_id");
        $is_success = $result !== false;

        return $is_success;
    }


    /***************************************************************************
     * Labels *
     ***************************************************************************/

    /**
     * List Labels defined in a given Brandfolder, structured as a nested
     * associative array.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * // Example A. Get a multidimensional array representing the full set of
     * // Labels, with hierarchy preserved.
     * $labels = $bf->listLabelsInBrandfolder($brandfolder_id);
     * if ($labels) {
     *   // Iterate through the first two tiers of Labels.
     *   foreach ($labels as $label_id => $label_data) {
     *     $label = $label_data->label;
     *     echo "Label: {$label->attributes->name} ($label_id) <br />";
     *     if (!empty($label_data->children)) {
     *       foreach ($label_data->children as $child_id => $child_data) {
     *         $child = $child_data->label;
     *         echo "&nbsp; Nested Label: {$child->attributes->name} ($child_id) <br />";
     *         // There could be any number of tiers in the Label hierarchy.
     *         // To fully process the tree, you would need to use a recursive
     *         // function or comparable algorithm.
     *       }
     *     }
     *   }
     * }
     *
     * // Example B. Get a flat array of Label names keyed by Label IDs.
     * $labels = $bf->listLabelsInBrandfolder($brandfolder_id, TRUE);
     * if ($labels) {
     *   foreach ($labels as $label_id => $label_name) {
     *     echo "Label: $label_name ($label_id) <br/>";
     *   }
     * }
     *
     * ```
     *
     * @param string|null $brandfolder_id
     * @param bool $simple_format
     *  If `true`, return a flat array keyed by Label IDs and containing Label
     *  names. Otherwise, return a multidimensional array representing the full
     *  hierarchy of Labels, with all attributes for each Label item.
     *
     * @return array|false
     *
     * @see https://developers.brandfolder.com/?http#list-labels
     */
    public function listLabelsInBrandfolder(string $brandfolder_id = null, bool $simple_format = false): array|false
    {
        if (is_null($brandfolder_id)) {
            if (is_null($this->default_brandfolder_id)) {
                $this->status = 0;
                $this->message = 'A Brandfolder ID must be provided or a default Brandfolder must be set.';

                return false;
            }
            $brandfolder_id = $this->default_brandfolder_id;
        }

        $labels_result = $this->getAll("/brandfolders/$brandfolder_id/labels");

        if (!$labels_result) {
            return false;
        }


        if ($simple_format) {
            $structured_labels = [];
            foreach ($labels_result->data as $label) {
                $structured_labels[$label->id] = $label->attributes->name;
            }
        } else {
            $structured_labels = new stdClass();
            // First, group Labels by tier/depth, so we can then process with
            // confidence that any given Label's ancestors have already been
            // placed in the structured array.
            $labels_by_tier = [];
            foreach ($labels_result->data as $label) {
                $unique_and_sortable_key = $label->attributes->position . '_' . $label->id;
                $labels_by_tier[$label->attributes->depth][$unique_and_sortable_key] = $label;
            }
            foreach ($labels_by_tier as $tier => $labels) {
                // Sort Labels by position (it's OK if Labels from various parents
                // are interspersed at this point).
                ksort($labels, SORT_NATURAL);
                foreach ($labels as $label) {
                    $lineage = $label->attributes->path;
                    // Remove the Label itself from the end of the path array.
                    array_pop($lineage);
                    // Walk through the path/lineage and place the Label in the
                    // appropriate spot.
                    $ancestor =& $structured_labels;
                    while (count($lineage) > 0) {
                        $younger_ancestor = array_shift($lineage);
                        if (isset($ancestor->children[$younger_ancestor])) {
                            $ancestor =& $ancestor->children[$younger_ancestor];
                        } else {
                            break;
                        }
                    }

                    $label_item = new stdClass();
                    $label_item->label = $label;

                    $ancestor->children[$label->id] = $label_item;
                }
            }
            // The top-level of the result structure should simply be an array of
            // Label items.
            if (isset($structured_labels->children) && is_array($structured_labels->children)) {
                $structured_labels = $structured_labels->children;
            }
        }

        return $structured_labels;
    }

    /**
     * Fetch an individual Label by ID.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $result = $bf->fetchLabel($label_id);
     * if ($result) {
     *   $label = $result->data;
     *   echo "Label: {$label->attributes->name} ({$label->id}) <br/>";
     * }
     *
     * ```
     *
     * @param string $label_id
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#fetch-a-label
     */
    public function fetchLabel(string $label_id): object|false
    {

        return $this->request('GET', "/labels/$label_id");
    }

    /**
     * Create a new Label in a Brandfolder.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     * $result = $bf->createLabelInBrandfolder('My New Label', $brandfolder_id, $parent_id);
     * if ($result) {
     *   $new_label = $result->data;
     *   echo "New Label created: {$new_label->attributes->name} ({$new_label->id}) <br/>";
     * }
     *
     * ```
     *
     * @param string $name
     *  The name of the Label.
     * @param string|null $brandfolder_id
     *  The ID of the Brandfolder in which to create the Label. If not provided,
     *  we will attempt to use the default Brandfolder if one is defined.
     * @param string|null $parent_id
     *  The ID/key of the parent Label, if any. If provided, the new Label will
     *  be nested beneath the parent Label. Otherwise, it will be placed at the
     *  top level.
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#create-a-label
     */
    public function createLabelInBrandfolder(
        string $name,
        ?string $brandfolder_id = null,
        ?string $parent_id = null
    ): object|false {
        if (is_null($brandfolder_id)) {
            if (is_null($this->default_brandfolder_id)) {
                $this->status = 0;
                $this->message = 'A Brandfolder ID must be provided or a default Brandfolder must be set.';

                return false;
            }
            $brandfolder_id = $this->default_brandfolder_id;
        }

        $attributes = [
            'name' => $name,
        ];
        if (!is_null($parent_id)) {
            $attributes['parent_key'] = $parent_id;
        }
        $body = [
            "data" => [
                "attributes" => $attributes,
            ],
        ];

        return $this->request('POST', "/brandfolders/$brandfolder_id/labels", [], $body);
    }

    /**
     * Update an existing Label.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     * $result = $bf->updateLabel($label_id, 'My New Label Name');
     * if ($result) {
     *   echo "Label renamed successfully. <br/>";
     * }
     *
     * ```
     *
     * @param string $label_id
     * @param array $new_name
     *  A new name for the Label.
     *  Note: `parent_id`/`parent_key` is not an option here. If you wish to
     *  relocate the Label, use the `moveLabel()` method.
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#update-a-label
     * @see BrandfolderClient::moveLabel()
     */
    public function updateLabel(string $label_id, string $new_name): object|false
    {
        $body = [
            "data" => [
                "attributes" => [
                    'name' => $new_name,
                ],
            ],
        ];

        return $this->request('PUT', "/labels/$label_id", [], $body);
    }

    /**
     * Move a Label to a new parent Label.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     * $result = $bf->moveLabel($label_id, $new_parent_id);
     *
     * if ($result) {
     *   echo "Label $label_id moved successfully. <br/>";
     * }
     *
     * ```
     *
     * @param string $label_id
     * @param string $new_parent_id
     *  The ID/key of the Label beneath which you want this Label to live.
     *  If you wish to move the Label to the top level, specify "root" here.
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#move-a-label
     */
    public function moveLabel(string $label_id, string $new_parent_id): object|false
    {
        if ($new_parent_id === 'root') {
            $new_parent_id = null;
        }
        $body = [
            "data" => [
                "attributes" => [
                    'parent_key' => $new_parent_id,
                ],
            ],
        ];

        return $this->request('PUT', "/labels/$label_id/move", [], $body);
    }

    /**
     * Delete a Label.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     * $result = $bf->deleteLabel($label_id);
     * if ($result) {
     *   echo "Label $label_id was successfully deleted. <br/>";
     * }
     *
     * ```
     *
     * @param string $label_id
     *
     * @return bool
     *
     * @see https://developers.brandfolder.com/docs/#delete-a-label
     */
    public function deleteLabel(string $label_id): bool
    {
        $result = $this->request('DELETE', "/labels/$label_id");
        $is_success = $result !== false;

        return $is_success;
    }

    /**
     * Add one or more assets to a Label.
     *
     * ```php
     *
     *  $bf = new BrandfolderClient($api_key);
     *
     *  $beach_assets_result = $bf->listAssets(['search' => 'beach OR (sand AND surf)']);
     *  if ($beach_assets_result) {
     *    $beach_asset_ids = array_map(function($asset) {
     *      return $asset->id;
     *    }, $beach_assets_result->data);
     *
     *    $labeling_result = $bf->addAssetsToLabel($beach_asset_ids, $beach_label_id);
     *    if ($labeling_result) {
     *      $num_assets = count($beach_asset_ids);
     *      echo "$num_assets assets successfully added to Label $beach_label_id. <br/>";
     *    }
     *  }
     *
     * ```
     *
     * @param array $asset_ids
     * @param string $label
     *  The ID/key of the Label to which the given assets should be added.
     *
     * @return object|false
     *
     * @todo: Allow users to provide the human-readable Label name if desired.
     * @todo: Add to online documentation? This is an undocumented but very useful endpoint.
     *   Tested and verified functional as of 2023-05-17.
     */
    public function addAssetsToLabel(array $asset_ids, string $label): object|false
    {
        $body = [
            "data" => [
                "asset_keys" => $asset_ids,
                "label_key" => $label,
            ],
        ];
        $result = $this->request('POST', "/bulk_actions/assets/add_to_label", [], $body);

        return $result;
    }


    /***************************************************************************
     * Tags *
     ***************************************************************************/

    /**
     * List Tags in a Brandfolder.
     *
     * ```php
     *
     *  $bf = new BrandfolderClient($api_key);
     *
     *  $tags_result = $bf->listTagsInBrandfolder($brandfolder_id);
     *  if ($tags_result) {
     *    foreach ($tags_result->data as $tag) {
     *      echo "Tag: {$tag->attributes->name} ({$tag->id}) <br/>";
     *    }
     *  }
     *
     * ```
     *
     * @param string|null $brandfolder_id
     *  The ID of the Brandfolder for which to list Tags. If not provided, the
     *  default Brandfolder ID will be used if set.
     * @param array|null $query_params
     *
     * @return object|bool
     *
     * @see https://developers.brandfolder.com/docs/#list-tags
     */
    public function listTagsInBrandfolder(?string $brandfolder_id = null, ?array $query_params = []): object|bool
    {
        if (is_null($brandfolder_id)) {
            if (is_null($this->default_brandfolder_id)) {
                $this->status = 0;
                $this->message = 'A Brandfolder ID must be provided or a default Brandfolder must be set.';

                return false;
            }
            $brandfolder_id = $this->default_brandfolder_id;
        }

        // Note: there is never any "included" data for these responses.

        return $this->request('GET', "/brandfolders/$brandfolder_id/tags", $query_params);
    }

    /**
     * List tags in a Collection.
     *
     * ```php
     *
     *  $bf = new BrandfolderClient($api_key);
     *
     *  $tags_result = $bf->listTagsInCollection($collection_id);
     *  if ($tags_result) {
     *    foreach ($tags_result->data as $tag) {
     *      echo "Tag: {$tag->attributes->name} ({$tag->id}) <br/>";
     *    }
     *  }
     *
     * ```
     *
     * @param string|null $collection_id
     *  The ID of the Collection for which to list Tags. If not provided, the
     *  default Collection ID will be used if set.
     * @param array|null $query_params
     *
     * @return object|bool
     *
     * @see https://developers.brandfolder.com/docs/#list-tags
     */
    public function listTagsInCollection(?string $collection_id = null, ?array $query_params = []): object|bool
    {
        if (is_null($collection_id)) {
            if (is_null($this->default_collection_id)) {
                $this->status = 0;
                $this->message = 'A Collection ID must be provided or a default Collection must be set.';

                return false;
            }
            $collection_id = $this->default_collection_id;
        }

        // Note: there is never any "included" data for these responses.

        return $this->request('GET', "/collections/$collection_id/tags", $query_params);
    }

    /**
     * Retrieves Tags used in a Brandfolder or Collection.
     *
     * @param array|null $query_params
     * @param string|null $collection ID of a Collection. If provided, only fetch
     *  Tags associated with that Collection.
     * @param bool $should_return_data_only If `true`, return only the `data`
     *  array nested within the API response. If false, return the entire response
     *  object. Defaults to `true` (data array only) for backward compatibility.
     *
     * @return array|object|false On success, returns an array of Tag items
     *  (if `$should_return_data_only` is `true`), or an object containing such an
     *  array plus a `meta` array with pagination data. `false` on failure.
     *
     * @deprecated This is provided mainly for backward compatibility. Consider
     *  using other Tag listing methods.
     *
     * @see https://developers.brandfolder.com/docs/#list-tags
     *
     * @see BrandfolderClient::listTagsInBrandfolder()
     * @see BrandfolderClient::listTagsInCollection()
     */
    public function getTags(
        ?array $query_params = [],
        string $collection = null,
        bool $should_return_data_only = true
    ): array|object|false {
        if (!is_null($collection)) {
            $endpoint = "/collections/$collection/tags";
        } elseif (isset($this->default_brandfolder_id)) {
            $endpoint = "/brandfolders/$this->default_brandfolder_id/tags";
        }
        if (!isset($endpoint)) {
            $this->status = 0;
            $this->message = 'Could not determine endpoint for listing tags. '
                . 'Please set a default Brandfolder or provide a Collection ID.';

            return false;
        }

        if (isset($query_params['page']) || isset($query_params['per'])) {
            $result = $this->request('GET', $endpoint, $query_params);
        } else {
            $result = $this->getAll($endpoint, $query_params);
        }

        // For backwards compatibility, return only the data array unless otherwise
        // requested. Note that these tags endpoints do not support any "included"
        // data, so the only difference here is the wrapping object that might also
        // contain a "meta" array.
        if ($should_return_data_only) {
            return $result->data;
        } else {
            return $result;
        }
    }

    /**
     * List tags for a given asset.
     *
     * ```php
     *
     *  $bf = new BrandfolderClient($api_key);
     *
     *  $tags_result = $bf->listTagsForAsset($asset_id);
     *  if ($tags_result) {
     *    foreach ($tags_result->data as $tag) {
     *      echo "Tag: {$tag->attributes->name} ({$tag->id}) <br/>";
     *    }
     *  }
     *
     * ```
     *
     * @param string $asset_id
     * @param array|null $query_params
     *
     * @return object|bool
     *
     * @see https://developers.brandfolder.com/docs/#list-tags-for-an-asset
     */
    public function listTagsForAsset(string $asset_id, ?array $query_params = []): object|bool
    {
        $result = $this->request('GET', "/assets/$asset_id/tags", $query_params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * Create Tags for an Asset (i.e. apply Tags to an Asset).
     *
     * ```php
     *
     *  $bf = new BrandfolderClient($api_key);
     *
     *  $tag_names = ['Inferno', 'Relativity', 'Cake'];
     *  $result = $bf->createTagsForAsset($asset_id, $tag_names);
     *  if ($result) {
     *    echo "Asset $asset_id successfully tagged with three tags.";
     *  }
     *
     * ```
     *
     * @param string $asset_id
     * @param array $tag_names
     *  An array of strings, where each one is the textual name of a Tag to
     *  apply to the Asset. These do not need to have been defined anywhere else
     *  previously.
     *
     * @return object|bool
     *
     * @see https://developers.brandfolder.com/docs/#create-tags-for-an-asset
     */
    public function createTagsForAsset(string $asset_id, array $tag_names): object|bool
    {
        $body = [
            'data' => [
                'attributes' => []
            ],
        ];

        foreach ($tag_names as $tag_name) {
            $body['data']['attributes'][] = [
                'name' => $tag_name,
            ];
        }

        return $this->request('POST', "/assets/$asset_id/tags", [], $body);
    }

    /**
     * Update an existing tag. Note: this only updates one particular instance of
     * a tag name. Whenever you create a new tag for an asset, a new ID is created
     * for that instance, even if the tag name is already used elsewhere.
     * Therefore, a given tag ID is only ever used in one place.
     *
     * ```php
     *
     *  $bf = new BrandfolderClient($api_key);
     *
     *  $tag_id = 'tag-id-abc-123';
     *  $tag_name = 'Renamed Tag';
     *  $result = $bf->updateTag($tag_id, $tag_name);
     *  if ($result) {
     *    echo "Tag $tag_id successfully renamed to $tag_name.";
     *  }
     *
     * ```
     *
     * @param string $tag_id
     * @param string $tag_name
     *
     * @return object|bool
     *
     * @see https://developers.brandfolder.com/docs/#update-a-tag
     */
    public function updateTag(string $tag_id, string $tag_name): object|bool
    {
        $body = [
            'data' => [
                'attributes' => [
                    'name' => $tag_name,
                ],
            ],
        ];

        return $this->request('PUT', "/tags/$tag_id", [], $body);
    }

    /**
     * Delete one or more tags associated with a given asset.
     *
     * ```php
     *
     *  $bf = new BrandfolderClient($api_key);
     *
     *  $tag_names = ['Inferno', 'Relativity'];
     *  $result = $bf->deleteTagsOnAsset($asset_id, $tag_names);
     *  if ($result) {
     *    echo "Tags successfully removed from asset $asset_id.";
     *  }
     *
     * ```
     *
     * @param string $asset_id
     * @param array $tag_names
     *
     * @return bool true on successful deletion, false otherwise.
     *
     * @see https://developers.brandfolder.com/docs/#delete-a-tag
     */
    public function deleteTagsOnAsset(string $asset_id, array $tag_names): bool
    {
        $result = $this->request('DELETE', "/async/tags/assets/$asset_id", [], [
            'tags' => $tag_names,
            //      'locale' => 'en',
        ]);

        // @todo: Look into locale-specific deletion.
        // @todo: Add more support for async endpoints if/when applicable.

        $is_success = $result !== false;

        return $is_success;
    }


    /***************************************************************************
     * Invitations *
     ***************************************************************************/

    /**
     * Lists Invitations to an Organization, Brandfolder, Collection, Portal,
     *  or Brandguide.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $invitations_result = $bf->listInvitations('brandfolder', 'brandfolder-id-abc-123');
     * if ($invitations_result) {
     *   foreach ($invitations_result->data as $invite) {
     *     echo "Invitation {$invite->id}: {$invite->attributes->email} "
     *      . "({$invite->attributes->permission_level}) <br/>";
     *   }
     * }
     *
     * ```
     *
     * @param string $entity_type
     *  The type of entity whose invites you are listing. Valid values are:
     *  `organization`, `brandfolder`, `collection`, `portal`, and `brandguide`.
     * @param string $entity_id
     *  The ID of the entity for which to list invitations.
     * @param array|null $query_params
     *
     * @return object|bool
     *
     * @see https://developers.brandfolder.com/docs/#list-invitations
     */
    public function listInvitations(string $entity_type, string $entity_id, ?array $query_params = []): object|bool
    {
        $entity_endpoint_string = '';
        switch ($entity_type) {
            case 'organization':
                $entity_endpoint_string = 'organizations';
                break;
            case 'brandfolder':
                $entity_endpoint_string = 'brandfolders';
                break;
            case 'collection':
                $entity_endpoint_string = 'collections';
                break;
            case 'portal':
                $entity_endpoint_string = 'portals';
                break;
            case 'brandguide':
                $entity_endpoint_string = 'brandguides';
                break;
            default:
                $this->status = 0;
                $this->message = 'Could not determine endpoint for listing invitations. '
                    . 'Please specify an Organization, Brandfolder, Collection, Portal, or Brandguide.';

                return false;
        }
        $endpoint = "/$entity_endpoint_string/$entity_id/invitations";
        if (isset($query_params['page']) || isset($query_params['per'])) {
            $result = $this->request('GET', $endpoint, $query_params);
            if ($result) {
                $this->processResultData($result);
            }
        } else {
            $result = $this->getAll($endpoint, $query_params);
        }

        return $result;
    }

    /**
     * Fetch an individual invitation by ID.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $invitation_result = $bf->fetchInvitation($invitation_id);
     * if ($invitation_result) {
     *  $invite = $invitation_result->data;
     *   echo "Invitation {$invite->id}: {$invite->attributes->email} ({$invite->attributes->permission_level}) <br/>";
     * }
     *
     * ```
     *
     * @param string $invitation_id
     * @param array|null $query_params
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#fetch-an-invitation
     */
    public function fetchInvitation(string $invitation_id, ?array $query_params = []): object|false
    {
        $result = $this->request('GET', "/invitations/$invitation_id", $query_params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * Create a new invitation to an Organization, Brandfolder, Collection,
     * Portal, or Brandguide.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $invitee_email = 'bob@example.com';
     * $result = $bf->createInvitation('brandfolder', 'brandfolder-id-abc-123', $invitee_email,
     *  'collaborator', 'Join us!');
     *
     * if ($result) {
     *  echo "Invitation sent to $invitee_email.";
     * }
     *
     * ```
     *
     * @param string $entity_type The type of entity to which you are inviting the
     *  user. Valid values are: `organization`, `brandfolder`, `collection`,
     *  `portal`, and `brandguide`.
     * @param string $entity_id The ID of the entity to which you are inviting the
     *  user.
     * @param string $email The email address to which the invitation should be
     *  sent.
     * @param string $permission_level The permission level to be granted to the
     *  invitee. Valid values are: `guest`, `collaborator`, `admin`, and `owner.`
     *  Note: `owner` is only valid when inviting someone to an Organization.
     * @param string $personal_message An optional message to include in the
     *  invitation email.
     * @param bool $prevent_email If true, the invitation email will not be sent.
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#create-an-invitation
     */
    public function createInvitation(
        string $entity_type,
        string $entity_id,
        string $email,
        string $permission_level,
        string $personal_message = '',
        bool $prevent_email = false
    ): object|false {
        $entity_endpoint_string = '';
        switch ($entity_type) {
            case 'organization':
                $entity_endpoint_string = 'organizations';
                break;
            case 'brandfolder':
                $entity_endpoint_string = 'brandfolders';
                break;
            case 'collection':
                $entity_endpoint_string = 'collections';
                break;
            case 'portal':
                $entity_endpoint_string = 'portals';
                break;
            case 'brandguide':
                $entity_endpoint_string = 'brandguides';
                break;
            default:
                $this->status = 0;
                $this->message = 'Cannot create an invitation to this type of entity.';

                return false;
        }
        $endpoint = "/$entity_endpoint_string/$entity_id/invitations";

        // Ensure that the permission level is valid.
        $valid_permission_levels = ['guest', 'collaborator', 'admin', 'owner'];
        if (!in_array($permission_level, $valid_permission_levels)) {
            $this->status = 0;
            $this->message = 'Invalid permission level.';

            return false;
        }
        if ($entity_type != 'organization' && $permission_level == 'owner') {
            $this->status = 0;
            $this->message = 'The "owner" permission level is only valid when inviting someone to an Organization.';

            return false;
        }

        $result = $this->request('POST', $endpoint, [], [
            'data' => [
                'attributes' => [
                    'email' => $email,
                    'permission_level' => $permission_level,
                    'personal_message' => $personal_message,
                    'prevent_email' => $prevent_email,
                ],
            ],
        ]);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * Delete an invitation.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $result = $bf->deleteInvitation($invitation_id);
     * if ($result) {
     *  echo "Invitation $invitation_id successfully deleted.";
     * }
     *
     * ```
     *
     * @param string $invitation_id
     *
     * @return bool true if the invitation was deleted successfully, false otherwise.
     *
     * @see https://developers.brandfolder.com/docs/#delete-an-invitation
     */
    public function deleteInvitation(string $invitation_id): bool
    {
        $result = $this->request('DELETE', "/invitations/$invitation_id");
        $is_success = $result !== false;

        return $is_success;
    }


    /***************************************************************************
     * User Permissions *
     ***************************************************************************/

    /**
     * List user permissions for an Organization.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $permissions_result = $bf->listUserPermissionsForOrganization($organization_id);
     * if ($permissions_result) {
     *  foreach ($permissions_result->data as $permission) {
     *    $user = reset($permission->users);
     *    echo "Permission {$permission->id}: {$user->first_name} {$user->last_name} ({$user->email}) "
     *      . "has permission level {$permission->attributes->permission_level} <br/>";
     *  }
     * }
     *
     * ```
     *
     * @param string $organization_id
     * @param array|null $query_params
     *
     * @return object|bool
     *
     * @see https://developers.brandfolder.com/docs/#list-user-permissions
     */
    public function listUserPermissionsForOrganization(string $organization_id, ?array $query_params = []): object|bool
    {
        $result = $this->request('GET', "/organizations/$organization_id/user_permissions", $query_params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * List user permissions for a Brandfolder.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $permissions_result = $bf->listUserPermissionsForBrandfolder($brandfolder_id);
     * if ($permissions_result) {
     *   foreach ($permissions_result->data as $permission) {
     *     $user = reset($permission->users);
     *     echo "Permission {$permission->id}: {$user->first_name} {$user->last_name} ({$user->email}) "
     *      . "has permission level {$permission->attributes->permission_level} <br/>";
     *   }
     * }
     *
     * ```
     *
     * @param string $brandfolder_id
     * @param array|null $query_params
     *
     * @return object|bool
     *
     * @see https://developers.brandfolder.com/docs/#list-user-permissions
     */
    public function listUserPermissionsForBrandfolder(string $brandfolder_id, ?array $query_params = []): object|bool
    {
        $result = $this->request('GET', "/brandfolders/$brandfolder_id/user_permissions", $query_params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * List user permissions for a Collection.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $permissions_result = $bf->listUserPermissionsForCollection($collection_id);
     * if ($permissions_result) {
     *  foreach ($permissions_result->data as $permission) {
     *    $user = reset($permission->users);
     *    echo "Permission {$permission->id}: {$user->first_name} {$user->last_name} ({$user->email}) "
     *      . "has permission level {$permission->attributes->permission_level} <br/>";
     *  }
     * }
     *
     * ```
     *
     * @param string $collection_id
     * @param array|null $query_params
     *
     * @return object|bool
     *
     * @see https://developers.brandfolder.com/docs/#list-user-permissions
     */
    public function listUserPermissionsForCollection(string $collection_id, ?array $query_params = []): object|bool
    {
        $result = $this->request('GET', "/collections/$collection_id/user_permissions", $query_params);

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * Fetch a user permission by ID.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $permission_result = $bf->fetchUserPermission($user_permission_id);
     * if ($permission_result) {
     *   $user = reset($permission_result->data->users);
     *   echo "Permission {$permission_result->data->id}: {$user->first_name} {$user->last_name} ({$user->email}) "
     *      . "has permission level {$permission_result->data->attributes->permission_level} <br/>";
     * }
     *
     * ```
     *
     * @param string $user_permission_id
     *
     * @return object|false
     *
     * @see https://developers.brandfolder.com/docs/#fetch-a-user-permission
     */
    public function fetchUserPermission(string $user_permission_id): object|false
    {
        $result = $this->request('GET', "/user_permissions/$user_permission_id");

        if ($result) {
            $this->processResultData($result);
        }

        return $result;
    }

    /**
     * Delete a user permission.
     *
     * Note: you may receive a "403: access denied" response if you attempt to
     * delete a permission for the same user whose API key is being used to make
     * the request.
     *
     * ```php
     *
     * $bf = new BrandfolderClient($api_key);
     *
     * $result = $bf->deleteUserPermission($user_permission_id);
     * if ($result) {
     *  echo "User permission $user_permission_id successfully deleted.";
     * }
     *
     * ```
     *
     * @param string $user_permission_id
     *
     * @return bool true if the user permission was deleted successfully;
     *  false otherwise.
     *
     * @see https://developers.brandfolder.com/docs/#delete-a-user-permission-revoke-access
     */
    public function deleteUserPermission(string $user_permission_id): bool
    {
        $result = $this->request('DELETE', "/user_permissions/$user_permission_id");
        $is_success = $result !== false;

        return $is_success;
    }


    /*****************************************************************************
     * Logging *
     ****************************************************************************/

    /**
     * Activate verbose logging mode.
     */
    public function enableVerboseLogging(): void
    {
        $this->verbose_logging_mode = true;
    }

    /**
     * Deactivate verbose logging mode.
     */
    public function disableVerboseLogging(): void
    {
        $this->verbose_logging_mode = false;
    }

    /**
     * Determine whether verbose logging is enabled.
     *
     * @return bool
     */
    public function verboseLoggingIsEnabled(): bool
    {
        return $this->verbose_logging_mode;
    }

    /**
     * Add log data.
     *
     * @param string $entry
     *
     * @todo: More structured log entries, with levels, implementing a common interface, etc.
     */
    protected function log(string $entry): void
    {
        $entry = str_replace($this->api_key, '[[API-KEY-REDACTED]]', $entry);
        $this->log_data[] = $entry;
    }

    /**
     * Get log data.
     *
     * @return array
     */
    public function getLogData(): array
    {
        $this->log_data = array_filter($this->log_data);

        return $this->log_data;
    }

    /**
     * Clear log data.
     */
    public function clearLogData(): void
    {
        $this->log_data = [];
    }


    /*****************************************************************************
     * API Requests and Post-Processing *
     ****************************************************************************/

    /**
     * Makes a request to the Brandfolder API, processes the result, and handles
     * any errors that may arise.
     *
     * @param string $method
     *  The HTTP method to use for the request.
     * @param string $path
     *  The unique component of the API endpoint to use for this request.
     * @param array|null $query_params
     *  Associative array of URL query parameters to add to the request.
     * @param array|null $body
     *  Associative array of data to be sent as the body of the requests. This
     *  will be converted to JSON.
     *
     * @return object|false
     *  An object representing JSON API response on success. This will
     *  typically consist of a `data` property containing the requested data,
     *  a `meta` property containing metadata about the response/context, and
     *  sometimes an `included` property containing additional data when
     *  available and when requested (via the `include` query param).
     *  `false` on failure.
     */
    public function request(string $method, string $path, ?array $query_params = [], array $body = null): object|false
    {

        // Reset status and message.
        $this->status = 0;
        $this->message = '';

        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Host' => 'brandfolder.com',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];

        if (count($query_params) > 0) {
            $options['query'] = $query_params;
        }

        if (!is_null($body)) {
            $options['json'] = $body;
        }

        $url = $this->endpoint . $path;
        $result = false;

        if ($this->verbose_logging_mode) {
            $options_string = $this->jsonEncode($options);
        }

        try {
            $response = $this->http_client->request($method, $url, $options);
            $status_code = $response->getStatusCode();
            $this->status = $status_code;
            $this->message = $response->getReasonPhrase();

            if ($this->verbose_logging_mode) {
                $log_entry = "Brandfolder request. Method: $method. Requested URL: $url. Options: $options_string. "
                    . "Response code: $status_code.";
                $this->log($log_entry);
            }

            // @todo: Consider handling 3xx redirects.
            if ($status_code >= 200 && $status_code < 300) {
                $body = $response->getBody()->getContents();
                if (empty($body)) {
                    $result = new stdClass();
                } else {
                    $result = $this->jsonDecode($body);

                    if ($result === false && $this->verbose_logging_mode) {
                        $log_entry = "Brandfolder request. Could not JSON-decode response body. Method: $method. "
                            . "Requested URL: $url. Options: $options_string. Response code: $status_code.";
                        $this->log($log_entry);
                    }
                }
            }
        } catch (GuzzleException $e) {
            $this->status = $e->getCode();
            $this->message = $e->getMessage();

            if ($this->verbose_logging_mode) {
                $log_entry = "Exception occurred during Brandfolder request. Method: $method. Requested URL: $url. "
                    . "Options: $options_string. Exception code: {$e->getCode()}. "
                    . "Exception message: {$e->getMessage()}.";
                $this->log($log_entry);
            }
        }

        return $result;
    }

    /**
     * Helper method for GET requests. Compiles data from all pages of
     * results for a given request into a single object.
     *
     * Note: Use with caution. Using this method with endpoints that return
     * a large number of results can be resource-intensive.
     *
     * @param string $path
     * @param array $query_params
     *
     * @return object|false Object containing aggregated response data for
     *  successful requests. This will always include a `data` array, and, where
     *  applicable, an `included` array with supplementary data.
     *  `false` on failure.
     */
    public function getAll(string $path, array $query_params = []): object|false
    {
        $data = [];
        $included = [];
        if (!isset($query_params['per'])) {
            $query_params['per'] = $this->default_items_per_page;
        }
        $query_params['page'] = 1;
        $request_limit = 100;
        $request_count = 0;

        do {
            $result = $this->request('GET', $path, $query_params);
            $request_count++;
            if ($result && isset($result->data) && is_array($result->data)) {
                // Improve the result data. It's more performant to do this here, per
                // page, rather than waiting until all pages have been retrieved and
                // then iterating through a potentially massive data set.
                $this->processResultData($result);
                if (isset($result->included) && is_array($result->included)) {
                    $included = array_merge($included, $result->included);
                }
                $data = array_merge($data, $result->data);

                $next_page = $result->meta->next_page ?? null;
                $query_params['page'] = $next_page;
            } else {
                return false;
            }
        } while ($request_count <= $request_limit && $next_page);

        $result = new stdClass();
        $result->data = $data;
        if (!empty($included)) {
            $result->included = $included;
        }

        return $result;
    }

    /**
     * Improve the usefulness of data returned from GET requests.
     *
     * @param object $result
     */
    protected function processResultData(object &$result): void
    {
        // All of this processing is only relevant if there is "included" data.
        if (isset($result->included) && is_array($result->included)) {
            // Make the "included" data array itself more useful.
            $this->restructureIncludedData($result);
            if (!empty($result->data)) {
                // Data might be a single object (e.g. for a fetchAsset call) or an
                // array of such objects (e.g. for a listAssets call).
                if (is_array($result->data)) {
                    // Update each entity to contain more useful data (about related
                    // entities/attributes, etc.).
                    array_walk($result->data, function ($entity) use ($result) {
                        $this->processRelationships($entity, $result->included);
                    });
                } elseif (is_object($result->data)) {
                    $this->processRelationships($result->data, $result->included);
                }
            }
        }
    }

    /**
     * Structure included data as an associative array of items grouped by
     * type and indexed therein by ID.
     *
     * @param object $result
     */
    protected function restructureIncludedData(object &$result): void
    {
        $included = [];
        if (isset($result->included) && is_array($result->included)) {
            foreach ($result->included as $item) {
                $included[$item->type][$item->id] = $item->attributes;
            }
        }
        $result->included = $included;
    }

    /**
     * Restructure an entity to contain useful values for each included
     * attribute rather than just a list of items with IDs that need to be
     * cross-referenced with the "included" data array returned by the API.
     * Also restructure custom field and attachment data (these will often be
     * present if $entity corresponds to an asset).
     *
     * @param object $entity The item with relationships to be processed. This
     *  will typically be a member of the "data" array returned from an API
     *  request, and could correspond to an asset, attachment, Brandfolder, etc.
     * @param array $included_data The "included" array returned from an API
     *  request for which you provided the "include" query param.
     */
    protected function processRelationships(object &$entity, array $included_data): void
    {
        if (!empty($entity->relationships)) {
            if (!empty($included_data['custom_field_values'])) {
                $custom_field_ids_and_names = $this->listCustomFields(null, false, true);
                $custom_field_names_and_ids = array_flip($custom_field_ids_and_names);
            }
            // @todo: Consider special treatment for assets (e.g. when listing attachments with '?include=asset'),
            //      which tend to have the type "generic_files" whereas the $type_label here would be "asset."
            //      The latter would be friendlier, imo. Determine whether there are other asset types, and, if so,
            //      whether we want to key the "included" array by those types or just group everything into an array
            //      with the "asset" key.
            foreach ($entity->relationships as $type_label => $data) {
                // Data here will either be an array of objects or a single object.
                // In the latter case, wrap in an array for consistency.
                $items = is_array($data->data) ? $data->data : [$data->data];
                foreach ($items as $item) {
                    $type = $item->type;
                    if (isset($included_data[$type][$item->id])) {
                        $attributes = $included_data[$type][$item->id];
                        // For custom field values, set up convenient arrays containing
                        // field values.
                        if ($type == 'custom_field_values') {
                            // First, populate an array keyed by the Brandfolder key of each
                            // custom field (this is the textual name of the field, which is
                            // displayed in the Brandfolder web app).
                            $key = $attributes->key;
                            if (!empty($entity->{'custom_field_values'}[$key])) {
                                // If there are multiple values for a given key, make
                                // sure we have an array to hold them.
                                if (!is_array($entity->{'custom_field_values'}[$key])) {
                                    $entity->{'custom_field_values'}[$key] = [$entity->{'custom_field_values'}[$key]];
                                }
                                // Add the new value to the array.
                                $entity->{'custom_field_values'}[$key][] = $attributes->value;
                            } else {
                                $entity->{'custom_field_values'}[$key] = $attributes->value;
                            }
                            // Also populate an array keyed by the ID of each custom field
                            // (i.e. the global ID of the field - not to be confused with
                            // the ID of the asset/attachment:field relationship).
                            if (isset($custom_field_names_and_ids[$key])) {
                                $custom_field_id = $custom_field_names_and_ids[$key];
                                if (!empty($entity->{'custom_field_values_by_id'}[$custom_field_id])) {
                                    // If there are multiple values for a given ID, make
                                    // sure we have an array to hold them.
                                    if (!is_array($entity->{'custom_field_values_by_id'}[$custom_field_id])) {
                                        $entity->{'custom_field_values_by_id'}[$custom_field_id] =
                                            [$entity->{'custom_field_values_by_id'}[$custom_field_id]];
                                    }
                                    // Add the new value to the array.
                                    $entity->{'custom_field_values_by_id'}[$custom_field_id][] = $attributes->value;
                                } else {
                                    $entity->{'custom_field_values_by_id'}[$custom_field_id] = $attributes->value;
                                }
                            }
                        } else {
                            $attributes->id = $item->id;
                            $entity->{$type}[$item->id] = $attributes;
                        }
                    }
                }
            }

            // Sort attachments by position. Retain the useful ID keys.
            if (!empty($entity->attachments)) {
                $ordered_attachments = [];
                $ordered_attachment_ids = [];
                foreach ($entity->attachments as $attachment) {
                    $ordered_attachments[$attachment->position] = $attachment;
                    $ordered_attachment_ids[$attachment->position] = $attachment->id;
                }
                ksort($ordered_attachments);
                ksort($ordered_attachment_ids);
                $entity->attachments = array_combine($ordered_attachment_ids, $ordered_attachments);
            }
        }
    }

    /**
     * Modify an asset object to contain useful values for each included
     * attribute rather than just a list of items with IDs, and to clean up
     * custom field and attachment data.
     *
     * @param object $asset The item with relationships to be processed. This
     *  will typically be a member of the `data` array returned from an API
     *  request, and could correspond to an asset, attachment, Brandfolder, etc.
     * @param array $included_data The `included` array returned from an API
     *  request for which you provided the `include` query param.
     *
     * Note: This is retained mainly for backwards compatibility. It could be
     *  expanded to include more asset-specific functionality, but all such
     *  functionality is currently contained within `processRelationships()`.
     */
    protected function decorateAsset(object &$asset, array $included_data): void
    {
        $this->processRelationships($asset, $included_data);
    }

    /**
     * Update an attachment to contain useful values for each included
     * attribute rather than just a list of items with IDs.
     *
     * @param object $attachment The item with relationships to be processed. This
     *  will typically be a member of the `data` array returned from an API
     *  request, and could correspond to an asset, attachment, Brandfolder, etc.
     * @param array $included_data The `included` array returned from an API
     *  request for which you provided the `include` query param.
     *
     * Note: This is retained mainly for backwards compatibility. It could be
     *  expanded to include more attachment-specific functionality.
     */
    protected function decorateAttachment(object &$attachment, array $included_data): void
    {
        $this->processRelationships($attachment, $included_data);
    }


    /***************************************************************************
     * Utility Methods *
     ***************************************************************************/

    /**
     * Accept various types of date/time inputs and produce a date/time string
     * in a format suitable for the Brandfolder API.
     *
     * @param string|int|\DateTime $date_time
     *  An English date/time string, timestamp, or DateTime object.
     *
     * @return string
     */
    protected function formatDateTime(string|int|\DateTime $date_time): string
    {
        if (!($date_time instanceof \DateTime)) {
            try {
                $date_time = new \DateTime($date_time);
            } catch (\Exception $e) {
                return false;
            }
        }

        $brandfolder_api_date_format = 'Y-m-d\TH:i:s.v\Z';

        return $date_time->format($brandfolder_api_date_format);
    }

    /**
     * Compatibility wrapper to support various json-encode methods.
     *
     * @param $data
     *
     * @return false|string
     */
    protected function jsonEncode($data): false|string
    {
        $result = false;
        try {
            if (method_exists('\GuzzleHttp\Utils', 'jsonEncode')) {
                $result = GuzzleUtils::jsonEncode($data);
            } elseif (function_exists('json_encode')) {
                $result = json_encode($data);
            } else {
                $this->status = 0;
                $this->message = 'No JSON encoding function found.';
            }
        } catch (InvalidArgumentException $e) {
            $this->status = 0;
            $this->message = $e->getMessage();
        }

        return $result;
    }

    /**
     * Compatibility wrapper to support various json-decode methods.
     *
     * @param $data
     *
     * @return mixed
     */
    protected function jsonDecode($data): mixed
    {
        $result = false;
        try {
            if (method_exists('\GuzzleHttp\Utils', 'jsonDecode')) {
                $result = GuzzleUtils::jsonDecode($data);
            } elseif (function_exists('json_decode')) {
                $result = json_decode($data);
            } else {
                $this->status = 0;
                $this->message = 'No JSON encoding function found.';
            }
        } catch (InvalidArgumentException $e) {
            $this->status = 0;
            $this->message = $e->getMessage();
        }

        return $result;
    }
}
