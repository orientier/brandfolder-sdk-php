<?php

namespace Brandfolder;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Utils as GuzzleUtils;
use stdClass;

/**
 * Class Brandfolder.
 *
 * @deprecated Use BrandfolderClient instead. The "Brandfolder" class name is
 * retained here for backward compatibility, but may be reallocated in a future
 * release.
 */
class Brandfolder extends BrandfolderClient {}
