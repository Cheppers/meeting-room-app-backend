<?php

namespace App\Utils\GoogleAPI;

use App\Utils\Cache\AbstractCache;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GoogleApiCalls
{
    const DEFAULT_CUSTOMER = 'my_customer';

    /**
     * @var ParameterBagInterface
     */
    protected $parameterBag;

    /**
     * @var AbstractCache
     */
    protected $appCache;

    public function __construct(ParameterBagInterface $parameterBag, AbstractCache $appCache)
    {
        $this->parameterBag = $parameterBag;
        $this->appCache = $appCache;
    }
}
