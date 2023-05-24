<?php

namespace Brandfolder;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Utils as GuzzleUtils;
use stdClass;

/**
 * Brandfolder library.
 *
 * @package Brandfolder
 */
class Brandfolder {

  const VERSION = '1.0.0';

  /**
   * API version.
   *
   * @var string $version
   */
  public string $version = self::VERSION;

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
   * @var null|ClientInterface $client
   */
  protected null|ClientInterface $client;

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
   * The collection to use for collection-specific requests, when no other
   * collection is specified.
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
   * Brandfolder constructor.
   *
   * @param string $api_key
   * @param string|null $brandfolder_id
   * @param \GuzzleHttp\ClientInterface|NULL $client
   */
  public function __construct(string $api_key, string $brandfolder_id = NULL, ClientInterface $client = NULL) {
    $this->api_key = $api_key;
    $this->default_brandfolder_id = $brandfolder_id;

    if (is_null($client)) {
      $client = new Client();
    }
    $this->client = $client;
  }

  /**
   * Compatibility wrapper to support various json-encode methods.
   *
   * @param $data
   *
   * @return false|string
   */
  protected function jsonEncode($data): false|string {
    $result = false;
    try {
      if (method_exists('\GuzzleHttp\Utils', 'jsonEncode')) {
        $result = GuzzleUtils::jsonEncode($data);
      }
      elseif (method_exists('\GuzzleHttp', 'json_encode')) {
        $result = \GuzzleHttp\json_encode($data);
      }
      elseif (function_exists('json_encode')) {
        $result = json_encode($data);
      }
      else {
        $this->status = 0;
        $this->message = 'No JSON encoding function found.';
      }
    }
    catch (InvalidArgumentException $e) {
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
  protected function jsonDecode($data): mixed {
    $result = false;
    try {
      if (method_exists('\GuzzleHttp\Utils', 'jsonDecode')) {
        $result = GuzzleUtils::jsonDecode($data);
      }
      elseif (method_exists('\GuzzleHttp', 'json_decode')) {
        $result = \GuzzleHttp\json_decode($data);
      }
      elseif (function_exists('json_decode')) {
        $result = json_decode($data);
      }
      else {
        $this->status = 0;
        $this->message = 'No JSON encoding function found.';
      }
    }
    catch (InvalidArgumentException $e) {
      $this->status = 0;
      $this->message = $e->getMessage();
    }

    return $result;
  }

  /**
   * Gets Brandfolder Organizations to which the current user belongs.
   *
   * @param array $query_params
   *
   * @return object|false
   *
   * @see https://developers.brandfolder.com/?http#list-organizations
   */
  public function getOrganizations(array $query_params = []): object|false {
    $result = $this->getAll('/organizations', $query_params);

    $this->processResultData($result);

    return $result;
  }

  /**
   * Gets Brandfolders to which the current user has access.
   *
   * @param array $query_params
   * @param bool $simple_format If true, return a flat array whose keys are
   *  Brandfolder IDs and whose values are Brandfolder names. If false, return
   *  the full result object with all core Brandfolder data and any included
   *  data.
   *
   * @return array|object|false
   *
   * @see https://developers.brandfolder.com/?http#list-brandfolders
   */
  public function getBrandfolders(array $query_params = [], bool $simple_format = true): object|bool|array {
    $bf_data = $this->getAll('/brandfolders', $query_params);
    if ($bf_data) {
      if ($simple_format) {
        $brandfolders = [];
        foreach ($bf_data->data as $brandfolder) {
          $brandfolders[$brandfolder->id] = $brandfolder->attributes->name;
        }

        return $brandfolders;
      }
      else {
        return $bf_data;
      }
    }

    return false;
  }

  /**
   * Gets Collections to which the current user has access.
   *
   * @param array $query_params
   *
   * @return object|false
   *
   * @see https://developers.brandfolder.com/?http#list-collections
   */
  public function getCollectionsForUser(array $query_params = []): object|false {
    return $this->getAll('/collections', $query_params);
  }

  /**
   * Gets Collections belonging to a certain Brandfolder.
   *
   * @param string|null $brandfolder_id
   * @param array $query_params
   * @param bool $simple_format If true, return a flat array whose keys are
   *  collection IDs and whose values are collection names. If false, return
   *  the full result object with all core collection data and any included
   *  data.
   *
   * @return array|object|false
   *
   * @see https://developers.brandfolder.com/?http#list-collections
   */
  public function getCollectionsInBrandfolder(string $brandfolder_id = NULL, array $query_params = [], bool $simple_format = true): array|object|false {
    if (is_null($brandfolder_id)) {
      // @todo $this->getBrandfolder().
      $brandfolder_id = $this->default_brandfolder_id;
    }

    $collection_result = $this->getAll("/brandfolders/$brandfolder_id/collections", $query_params);
    if ($collection_result) {
      if ($simple_format) {
        $collections = [];
        foreach ($collection_result->data as $collection) {
          $collections[$collection->id] = $collection->attributes->name;
        }

        return $collections;
      }
      else {

        return $collection_result;
      }
    }

    return false;
  }

  /**
   * Gets Sections defined in a given Brandfolder.
   *
   * @param string|null $brandfolder_id
   * @param array $query_params
   * @param bool $simple_format
   *  If true, return a flat array whose keys are section IDs and whose values
   *  are section names.
   *
   * @return array|object|false
   *
   * @see https://developers.brandfolder.com/?http#sections
   */
  public function listSectionsInBrandfolder(string $brandfolder_id = NULL, array $query_params = [], bool $simple_format = false): array|object|false {
    if (is_null($brandfolder_id)) {
      $brandfolder_id = $this->default_brandfolder_id;
    }

    $sections_result = $this->getAll("/brandfolders/$brandfolder_id/sections", $query_params);
    if ($sections_result) {
      if ($simple_format) {
        $sections = [];
        foreach ($sections_result->data as $section) {
          $sections[$section->id] = $section->attributes->name;
        }

        return $sections;
      }
      else {

        return $sections_result;
      }
    }

    return false;
  }

  /**
   * Gets custom field data for a Brandfolder.
   *
   * @param string|null $brandfolder_id
   * @param bool $include_values
   * @param bool $simple_format
   *  If true, return a simple array. The format of this array depends on the
   *  $include_values param. If true, return an array whose keys are custom
   *  field names and whose values are arrays of all the values that currently
   *  exist in Brandfolder for a given field. If false, return an array whose
   *  keys are custom field ids and whose values are custom field names.
   *
   * @return array|object|false
   *
   * @see https://developer.brandfolder.com/docs/#list-custom-field-keys-for-a-brandfolder
   */
  public function listCustomFields(string $brandfolder_id = NULL, bool $include_values = false, bool $simple_format = false): array|object|false {
    if (is_null($brandfolder_id)) {
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
        }
        // Array whose keys are custom field ids and whose values are custom
        // field names.
        else {
          foreach ($custom_field_result->data as $custom_field_data) {
            $custom_fields[$custom_field_data->id] = $custom_field_data->attributes->name;
          }
        }

        return $custom_fields;
      }
      else {

        return $custom_field_result;
      }
    }

    return false;
  }

  /**
   * Gets Labels defined in a given Brandfolder, structured as a nested
   * associative array.
   *
   * @param string|null $brandfolder_id
   * @param bool $simple_format
   *  If true, return a flat array keyed by label IDs and containing label
   *  names.
   *
   * @return array|false
   *
   * @see https://developers.brandfolder.com/?http#list-labels
   */
  public function listLabelsInBrandfolder(string $brandfolder_id = NULL, bool $simple_format = false): array|false {
    if (is_null($brandfolder_id)) {
      $brandfolder_id = $this->default_brandfolder_id;
    }

    $labels_result = $this->getAll("/brandfolders/$brandfolder_id/labels");

    if (!$labels_result) {

      return false;
    }

    $structured_labels = [];

    if ($simple_format) {
      foreach ($labels_result->data as $label) {
        $structured_labels[$label->id] = $label->attributes->name;
      }
    }
    else {
      // First, group labels by tier/depth, so we can then process with
      // confidence that any given label's ancestors have already been
      // placed in the structured array.
      $labels_by_tier = [];
      foreach ($labels_result->data as $label) {
        $unique_and_sortable_key = $label->attributes->position . '_' . $label->id;
        $labels_by_tier[$label->attributes->depth][$unique_and_sortable_key] = $label;
      }
      foreach ($labels_by_tier as $tier => $labels) {
        // Sort labels by position (it's OK if labels from various parents
        // are interspersed at this point).
        ksort($labels, SORT_NATURAL);
        foreach ($labels as $label) {
          $lineage = $label->attributes->path;
          // Remove the label itself from the end of the path array.
          array_pop($lineage);
          // Walk through the path/lineage and place the label in the
          // appropriate spot.
          $ancestor =& $structured_labels;
          while (count($lineage) > 0) {
            $younger_ancestor = array_shift($lineage);
            if (isset($ancestor['children'][$younger_ancestor])) {
              $ancestor =& $ancestor['children'][$younger_ancestor];
            }
            else {
              break;
            }
          }
          $label_item = [
            'label' => $label,
          ];
          $ancestor['children'][$label->id] = $label_item;
        }
      }
      // The top-level of the array should consist of label items. There is
      // no need for a "children" sub-array.
      if (isset($structured_labels['children']) && is_array($structured_labels['children'])) {
        $structured_labels = $structured_labels['children'];
      }
    }

    return $structured_labels;
  }

  /**
   * Fetches an individual asset.
   *
   * @param string $asset_id
   * @param array|null $query_params
   *
   * @return object|false
   *
   * @see https://developers.brandfolder.com/?python#fetch-an-asset
   */
  public function fetchAsset(string $asset_id, ?array $query_params = []): object|bool {
    $result = $this->request('GET', "/assets/$asset_id", $query_params);

    if ($result) {
      $this->processResultData($result);
    }

    return $result;
  }

  /**
   * Update an existing attachment.
   *
   * @param string $attachment_id
   * @param string|null $url
   * @param string|null $filename
   *
   * @return object|false
   *
   * @see https://developers.brandfolder.com/#update-an-attachment
   */
  public function updateAttachment(string $attachment_id, string $url = NULL, string $filename = NULL): object|false {
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
   * Fetch an existing attachment.
   *
   * @param string $attachment_id
   * @param array|null $params
   *
   * @return object|false
   *
   * @see https://developers.brandfolder.com/docs/#fetch-an-attachment
   */
  public function fetchAttachment(string $attachment_id, ?array $params = []): object|false {
    $result = $this->request('GET', "/attachments/$attachment_id", $params);

    if ($result) {
      $this->processResultData($result);
    }

    return $result;
  }

  /**
   * Delete an existing attachment.
   *
   * @param string $attachment_id
   *
   * @return bool true on successful deletion. false on failure.
   *
   * @see https://developers.brandfolder.com/docs/#delete-an-attachment
   */
  public function deleteAttachment(string $attachment_id): bool {
    $result = $this->request('DELETE', "/attachments/$attachment_id");
    $is_success = $result !== false;

    return $is_success;
  }

  /**
   * Create a new asset.
   *
   * @param string $name
   * @param array $attachments
   * @param string $section
   * @param string|null $description
   * @param string|null $brandfolder
   * @param string|null $collection
   *
   * @return object|false
   *
   * @see https://developer.brandfolder.com/#create-assets
   */
  public function createAsset(string $name, array $attachments, string $section, string $description = NULL, string $brandfolder = NULL, string $collection = NULL): object|false {
    $asset = [
      'name'        => $name,
      'attachments' => $attachments,
    ];
    if (!is_null($description)) {
      $asset['description'] = $description;
    }
    $assets = [$asset];

    $result = $this->createAssets($assets, $section, $brandfolder, $collection);
    if ($result && !empty($result->data)) {

      return $result->data[0];
    }

    return false;
  }

  /**
   * Create multiple new assets in one operation.
   *
   * @param array $assets
   *  Array of arrays with asset data. Each asset data array should consist of:
   *    'name' (string)
   *    'description' (optional string)
   *    'attachments' (array)
   * @param string $section
   * @param string|null $brandfolder
   * @param string|null $collection
   *
   * @return array|false
   *
   * @see https://developer.brandfolder.com/#create-assets
   */
  public function createAssets(array $assets, string $section, string $brandfolder = NULL, string $collection = NULL): array|false{
    if (is_null($brandfolder) && is_null($collection)) {
      if (!is_null($this->default_brandfolder_id)) {
        $brandfolder = $this->default_brandfolder_id;
      }
      elseif (!is_null($this->default_collection_id)) {
        $collection = $this->default_collection_id;
      }
    }
    if (!is_null($brandfolder)) {
      $endpoint = "/brandfolders/$brandfolder/assets";
    }
    elseif (!is_null($collection)) {
      $endpoint = "/collections/$collection/assets";
    }
    if (!isset($endpoint)) {
      $this->message = 'A Brandfolder or a collection must be specified when creating an asset.';

      return false;
    }

    $body = [
      "data"        => [
        "attributes" => $assets,
      ],
      "section_key" => $section,
    ];

    $result = $this->request('POST', $endpoint, [], $body);

    if ($result && !empty($result->data)) {

      return $result->data;
    }
    else {

      return false;
    }
  }

  /**
   * Update an existing asset.
   *
   * @param string $asset_id
   * @param null $name
   * @param null $description
   * @param null $attachments
   *
   * @return object|false
   *
   * @see https://developers.brandfolder.com/#update-an-asset
   */
  public function updateAsset(string $asset_id, $name = NULL, $description = NULL, $attachments = NULL): object|false {
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

    $body = [
      "data" => [
        "attributes" => $attributes,
      ],
    ];

    $result = $this->request('PUT', "/assets/$asset_id", [], $body);

    if ($result && !empty($result->data)) {

      return $result->data;
    }
    else {

      return false;
    }
  }

  /**
   * Add custom field values to an asset.
   *
   * @param string $asset_id
   * @param array $custom_field_values
   *  Associative array with key-value pairs. The key should identify the custom
   *  field (by name or ID, depending on the $field_key_type param), and the value
   *  should be the value you wish to store on the given asset for that field.
   * @param string|null $field_key_type If 'name' (default), the
   *  $custom_field_values array should be keyed by custom field names
   *  (e.g. "eye-color"). If 'id', the $custom_field_values array should be
   *  keyed by custom field IDs (e.g. "h3689xgmx2jx558xxnt16xp").
   *  Note: 'name' mode is more convenient, but
   *  (a) requires an extra API call to look up the custom field IDs, and
   *  (b) can cause functionality to break if you are invoking this method with
   *  a hard-coded name and the custom field's name is subsequently changed.
   *
   * @return bool true if all requests on success, false otherwise.
   *
   * @see https://developer.brandfolder.com/#create-custom-fields-for-an-asset
   *
   * Note: The API endpoint previously used by this method
   * (/custom_field_keys/{custom_field_key_id}/custom_field_values) was
   * seemingly removed on 2023-05-17. This method was then updated to work
   * with the new endpoint/pattern while maintaining backwards compatibility.
   *
   * @todo: Add a separate method such as "setCustomFieldValues()," designed to more directly work with the new endpoint, which is geared toward updating multiple assets with various values for the same custom field.
   */
  public function addCustomFieldsToAsset(string $asset_id, array $custom_field_values, ?string $field_key_type = 'name'): bool {
    $is_total_success = true;
    $messages = [];

    if ($field_key_type == 'name') {
      // First we need to look up custom field keys/IDs, so we can map the
      // friendly string names to IDs (which the Brandfolder API requires for
      // the subsequent POST request).
      $custom_field_ids_and_names = $this->listCustomFields(NULL, false, true);
      if (!$custom_field_ids_and_names) {
        $this->message = 'Could not retrieve custom fields from Brandfolder.';

        return false;
      }
      $relevant_field_ids_and_values = [];
      foreach ($custom_field_ids_and_names as $id => $name) {
        if (isset($custom_field_values[$name])) {
          $relevant_field_ids_and_values[$id] = $custom_field_values[$name];
        }
        else {
          $is_total_success = false;
          $messages[] = "Custom field '$name' appears to no longer exist in Brandfolder, so we cannot add a value for it.";
        }
      }
      $custom_field_values = $relevant_field_ids_and_values;
    }

    foreach ($custom_field_values as $field_id => $value) {
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
                  'id'   => $asset_id,
                ],
              ],
            ],
          ],
        ],
      ];

      $result = $this->request('POST', "/custom_field_keys/$field_id/custom_field_values", [], $body);

      if ($result) {
        $success_phrase = 'Successfully added';
      }
      else {
        $success_phrase = 'Failed to add';
        $is_total_success = false;
      }
      $messages[] = "$success_phrase custom field value for field ID $field_id to asset $asset_id.";
    }

    $this->message = implode(' ', $messages);

    return $is_total_success;
  }

  /**
   * Add one or more assets to a label.
   *
   * @param array $asset_ids
   * @param string $label
   *  The ID/key of the label to which the given assets should be added.
   *
   * @return object|false
   *
   * @todo: Allow users to provide the human-readable label name if desired.
   *
   * @todo: Add to online documentation? This is an undocumented but very useful endpoint. Tested and verified functional as of 2023-05-17.
   */
  public function addAssetsToLabel(array $asset_ids, string $label): object|false {
    $body = [
      "data" => [
        "asset_keys" => $asset_ids,
        "label_key"  => $label,
      ],
    ];
    $result = $this->request('POST', "/bulk_actions/assets/add_to_label", [], $body);

    return $result;
  }

  /**
   * Delete an asset.
   *
   * @param string $asset_id
   *
   * @return bool true if successfully deleted, false otherwise.
   *
   * @see https://developers.brandfolder.com/#delete-an-asset
   */
  public function deleteAsset(string $asset_id): bool {
    $result = $this->request('DELETE', "/assets/$asset_id");
    $is_success = $result !== false;

    return $is_success;
  }

  /**
   * List multiple assets.
   *
   * @param array|null $query_params
   * @param string|null $collection
   *  An ID of a collection within which to search for assets, or "all" to look
   *  throughout the entire Brandfolder. If this param is null, the operation
   *  will use the previously defined default collection, if applicable.
   * @param bool $should_get_all if true, retrieve all applicable assets - i.e.
   *  aggregate results from all pages of results. If false, the page size and
   *  number will be dictated by the params provided in $query_params ("page,"
   *  and "per"). If those are not provided, the default behavior will be to
   *  return the first page of results.
   *
   * @return object|false
   *
   * @see https://developers.brandfolder.com/?http#list-assets
   *
   * @todo: assets within Brandfolder vs collection vs org
   */
  public function listAssets(?array $query_params = [], string $collection = NULL, bool $should_get_all = false): object|bool {
    if (is_null($collection)) {
      $collection = $this->default_collection_id;
    }
    if (($collection == 'all' || is_null($collection)) && isset($this->default_brandfolder_id)) {
      $endpoint = "/brandfolders/$this->default_brandfolder_id/assets";
    }
    elseif (!is_null($collection)) {
      $endpoint = "/collections/$collection/assets";
    }
    if (!isset($endpoint)) {
      $this->status = 0;
      $this->message = 'Could not determine endpoint for listing assets. Please set a default Brandfolder or provide a collection ID.';

      return false;
    }

    // Use getAll() if specifically requested. Do not do this by default, for
    // backwards compatibility and to reduce performance impact for
    // unsuspecting users.
    if ($should_get_all) {
      $result = $this->getAll($endpoint, $query_params);
    }
    else {
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
   * Improve the usefulness of data returned from GET requests.
   *
   * @param object $result
   */
  protected function processResultData(object &$result): void {
    // All of this processing is only relevant if there is "included" data.
    if (isset($result->included) && is_array($result->included)) {
      // Make the "included" data array itself more useful.
      $this->restructureIncludedData($result);
      if (isset($result->data) && is_array($result->data)) {
        // Update each entity to contain more useful data (about related
        // entities/attributes, etc.).
        array_walk($result->data, function ($entity) use ($result) {
          $this->processRelationships($entity, $result->included);
        });
      }
    }
  }

  /**
   * Structure included data as an associative array of items grouped by
   * type and indexed therein by ID.
   *
   * @param object $result
   */
  protected function restructureIncludedData(object &$result): void {
    $included = [];
    if (isset($result->included) && is_array($result->included)) {
      foreach ($result->included as $item) {
        $included[$item->type][$item->id] = $item->attributes;
      }
    }
    $result->included = $included;
  }

  /**
   * Modify an asset object to contain useful values for each included
   * attribute rather than just a list of items with IDs, and to clean up
   * custom field and attachment data.
   *
   * @param object $asset The item with relationships to be processed. This
   *  will typically be a member of the "data" array returned from an API
   *  request, and could correspond to an asset, attachment, brandfolder, etc.
   * @param array $included_data The "included" array returned from an API
   *  request for which you provided the "include" query param.
   *
   * Note: This is retained mainly for backwards compatibility. It could be
   *  expanded to include more asset-specific functionality, but all such
   *  functionality is currently contained within processRelationships().
   */
  protected function decorateAsset(object &$asset, array $included_data): void {
    $this->processRelationships($asset, $included_data);
  }

  /**
   * Update an attachment to contain useful values for each included
   * attribute rather than just a list of items with IDs.
   *
   * @param object $attachment The item with relationships to be processed. This
   *  will typically be a member of the "data" array returned from an API
   *  request, and could correspond to an asset, attachment, brandfolder, etc.
   * @param array $included_data The "included" array returned from an API
   *  request for which you provided the "include" query param.
   *
   * Note: This is retained mainly for backwards compatibility. It could be
   *  expanded to include more attachment-specific functionality.
   */
  protected function decorateAttachment(object &$attachment, array $included_data): void {
    $this->processRelationships($attachment, $included_data);
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
   *  request, and could correspond to an asset, attachment, brandfolder, etc.
   * @param array $included_data The "included" array returned from an API
   *  request for which you provided the "include" query param.
   */
  protected function processRelationships(object &$entity, array $included_data): void {
    if (!empty($entity->relationships)) {
      foreach ($entity->relationships as $type_label => $data) {
        // Data here will either be an array of objects or a single object.
        // In the latter case, wrap in an array for consistency.
        $items = is_array($data->data) ? $data->data : [$data->data];
        foreach ($items as $item) {
          $type = $item->type;
          if (isset($included_data[$type][$item->id])) {
            $attributes = $included_data[$type][$item->id];
            // For custom field values, set up a convenient array keyed
            // by field keys and containing field values. If users
            // need to know the unique ID of a particular custom field
            // instance, they can still look in $entity->relationships.
            if ($type == 'custom_field_values') {
              $key = $attributes->key;
              $entity->{$type}[$key] = $attributes->value;
            }
            else {
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
   * Retrieves tags used in a Brandfolder.
   *
   * @param array|null $query_params
   * @param string|null $collection ID of a collection. I provided, only fetch tags
   *  associated with that collection.
   * @param bool $should_return_data_only If true, return only the "data" array
   *  nested within the API response. If false, return the entire response
   *  object. Defaults to true (data array only) for backwards compatibility.
   *
   * @return array|object|false On success, returns an array of tag items
   *  (if $should_return_data_only is true), or an object containing such an
   *  array plus a "meta" array with pagination data. false on failure.
   *
   * @see https://developers.brandfolder.com/docs/#list-tags
   */
  public function getTags(?array $query_params = [], string $collection = NULL, bool $should_return_data_only = true): array|object|false {
    if (!is_null($collection)) {
      $endpoint = "/collections/$collection/tags";
    }
    elseif (isset($this->default_brandfolder_id)) {
      $endpoint = "/brandfolders/$this->default_brandfolder_id/tags";
    }
    if (!isset($endpoint)) {
      $this->status = 0;
      $this->message = 'Could not determine endpoint for listing assets. Please set a default Brandfolder or provide a collection ID.';

      return false;
    }

    if (isset($query_params['page']) || isset($query_params['per'])) {
      $result = $this->request('GET', $endpoint, $query_params);
    }
    else {
      $result = $this->getAll($endpoint, $query_params);
    }

    // For backwards compatibility, return only the data array unless otherwise
    // requested. Note that the tags endpoint does not support any "included"
    // data, so the only difference here is the wrapping object that might also
    // contain a "meta" array.
    if ($should_return_data_only) {
      return $result->data;
    }
    else {
      return $result;
    }
  }

  /**
   * Lists Invitations to an Organization, Brandfolder, Collection, Portal,
   *  or Brandguide.
   *
   * @param array|null $query_params
   * @param string|null $organization
   * @param string|null $brandfolder
   * @param string|null $collection
   * @param string|null $portal
   * @param string|null $brandguide
   *
   * @return object|false
   *
   * @see https://developers.brandfolder.com/?http#list-invitations
   */
  public function listInvitations(?array $query_params = [], string $organization = NULL, string $brandfolder = NULL, string $collection = NULL, string $portal = NULL, string $brandguide = NULL): object|bool {
    if (!is_null($organization)) {
      // @todo: Store default organization.
      $endpoint = "/organizations/$organization/invitations";
    }
    elseif (!is_null($portal)) {
      $endpoint = "/portals/$portal/invitations";
    }
    elseif (!is_null($brandguide)) {
      $endpoint = "/brandguides/$brandguide/invitations";
    }
    else {
      if (is_null($brandfolder) && is_null($collection)) {
        if (!is_null($this->default_brandfolder_id)) {
          $brandfolder = $this->default_brandfolder_id;
        }
        elseif (!is_null($this->default_collection_id)) {
          $collection = $this->default_collection_id;
        }
      }
      if (!is_null($brandfolder)) {
        $endpoint = "/brandfolders/$brandfolder/invitations";
      }
      elseif (!is_null($collection)) {
        $endpoint = "/collections/$collection/invitations";
      }
    }
    if (isset($endpoint)) {
      if (isset($query_params['page']) || isset($query_params['per'])) {
        $result = $this->request('GET', $endpoint, $query_params);
      }
      else {
        $result = $this->getAll($endpoint, $query_params);
      }

      if ($result) {
        $this->processResultData($result);
      }

      return $result;
    }
    else {
      $this->status = 0;
      $this->message = 'Could not determine endpoint for listing invitations. Please specify an organization/Brandfolder/collection/portal/brandguide.';

      return false;
    }
  }

  /**
   * Helper method for GET requests. Compiles data from all pages of
   * results for a given request into a single object.
   *
   * @param string $path
   * @param array $query_params
   *
   * @return object|false Object containing aggregated response data for
   *  successful requests. This will always include a "data" array, and, where
   *  applicable, an "included" array with supplementary data.
   *  false on failure.
   */
  public function getAll(string $path, array $query_params = []): object|false {
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

        $next_page = $result->meta->next_page ?? NULL;
        $query_params['page'] = $next_page;
      }
      else {

        return false;
      }
    }
    while ($request_count <= $request_limit && $next_page);

    $result = new stdClass();
    $result->data = $data;
    if (!empty($included)) {
      $result->included = $included;
    }

    return $result;
  }

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
   *  typically consist of a "data" property containing the requested data,
   *  a "meta" property containing metadata about the response/context, and
   *  sometimes an "included" property containing additional data when
   *  available and when requested (via the "include" query param).
   *  false on failure.
   *
   * @code
   *    $assets = [];
   *    $result = $this->request('GET', "/brandfolders/{$brandfolder_id}/assets");
   *    if ($result && $result->meta->total_count > 0) {
   *      $assets = $result->data;
   *      if (!empty($result->meta->next_page)) {
   *        ...
   *      }
   *    }
   * @endcode
   */
  public function request(string $method, string $path, ?array $query_params = [], array $body = NULL) : object|false {

    // Reset status and message.
    $this->status = 0;
    $this->message = '';

    $options = [
      'headers' => [
        'Authorization' => 'Bearer ' . $this->api_key,
        'Host'          => 'brandfolder.com',
        'Accept'        => 'application/json',
        'Content-Type'  => 'application/json',
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
      $response = $this->client->request($method, $url, $options);
      $status_code = $response->getStatusCode();
      $this->status = $status_code;
      $this->message = $response->getReasonPhrase();

      if ($this->verbose_logging_mode) {
        $log_entry = "Brandfolder request. Method: $method. Requested URL: $url. Options: $options_string. Response code: $status_code.";
        $this->log($log_entry);
      }

      // @todo: Consider handling 3xx redirects.
      if ($status_code >= 200 && $status_code < 300) {
        $body = $response->getBody()->getContents();
        if (empty($body)) {
          $result = new stdClass();
        }
        else {
          $result = $this->jsonDecode($body);

          if ($result === false && $this->verbose_logging_mode) {
            $log_entry = "Brandfolder request. Could not JSON-decode response body. Method: $method. Requested URL: $url. Options: $options_string. Response code: $status_code.";
            $this->log($log_entry);
          }
        }
      }
    }
    catch (GuzzleException $e) {
      $this->status = $e->getCode();
      $this->message = $e->getMessage();

      if ($this->verbose_logging_mode) {
        $log_entry = "Exception occurred during Brandfolder request. Method: $method. Requested URL: $url. Options: $options_string. Exception code: {$e->getCode()}. Exception message: {$e->getMessage()}.";
        $this->log($log_entry);
      }
    }


    return $result;
  }

  /**
   * Activate verbose logging mode.
   */
  public function enableVerboseLogging(): void {
    $this->verbose_logging_mode = true;
  }

  /**
   * Deactivate verbose logging mode.
   */
  public function disableVerboseLogging(): void {
    $this->verbose_logging_mode = false;
  }

  /**
   * Determine whether verbose logging is enabled.
   *
   * @return bool
   */
  public function verboseLoggingIsEnabled(): bool {
    return $this->verbose_logging_mode;
  }

  /**
   * Add log data.
   *
   * @param string $entry
   *
   * @todo: More structured log entries, with levels, implementing a common interface, etc.
   */
  protected function log(string $entry): void {
    $entry = str_replace($this->api_key, '[[API-KEY-REDACTED]]', $entry);
    $this->log_data[] = $entry;
  }

  /**
   * Get log data.
   *
   * @return array
   */
  public function getLogData(): array {
    $this->log_data = array_filter($this->log_data);

    return $this->log_data;
  }

  /**
   * Clear log data.
   */
  public function clearLogData(): void {
    $this->log_data = [];
  }

}
