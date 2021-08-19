<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sunnydesign\Cognito\Providers;

use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\CacheInterface as PsrCacheInterface;

class StorageProvider
{
    /**
     * The cache repository contract.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;


    /**
     * The used cache tag.
     *
     * @var string
     */
    protected $tag = 'ellaisys.aws.cognito';


    /**
     * @var bool
     */
    protected $supportsTags;


    /**
     * @var string|null
     */
    protected $laravelVersion;


    /**
     * Constructor.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     *
     * @return void
     */
    public function __construct(string $provider='file')
    {
        $this->cache = Cache::store($provider);
        $this->supportsTags = false;
    }


    /**
     * Add a new item into storage.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $minutes
     *
     * @return void
     */
    public function add($key, $value, $duration=3600)
    {
        // If the laravel version is 5.8 or higher then convert minutes to seconds.
        if ($this->laravelVersion !== null
            && is_int($minutes)
            && version_compare($this->laravelVersion, '5.8', '<')
        ) {
            $duration = ($duration/60);
        }

        $this->cache()->put($key, $value, $duration);
    }


    /**
     * Add a new item into storage forever.
     *
     * @param  string  $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function forever($key, $value)
    {
        $this->cache()->forever($key, $value);
    }


    /**
     * Check for an item in storage.
     *
     * @param  string  $key
     *
     * @return bool
     */
    public function has($key)
    {
        return $this->cache()->has($key);
    }


    /**
     * Get an item from storage.
     *
     * @param  string  $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return $this->cache()->get($key);
    }


    /**
     * Remove an item from storage.
     *
     * @param  string  $key
     *
     * @return bool
     */
    public function destroy($key)
    {
        if ($this->has($key)) {
            return $this->cache()->forget($key);
        } //End if

        return false;
    } //Function ends


    /**
     * Remove all items associated with the tag.
     *
     * @return void
     */
    public function flush()
    {
        $this->cache()->flush();
    } //Function ends


    /**
     * Return the cache instance with tags attached.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function cache()
    {
        if ($this->supportsTags === null) {
            $this->determineTagSupport();
        } //End if

        if ($this->supportsTags) {
            return $this->cache->tags($this->tag);
        } //End if

        return $this->cache;
    } //Function ends


    /**
     * Set the laravel version.
     */
    public function setLaravelVersion($version)
    {
        $this->laravelVersion = $version;

        return $this;
    } //Function ends


    /**
     * Detect as best we can whether tags are supported with this repository & store,
     * and save our result on the $supportsTags flag.
     *
     * @return void
     */
    protected function determineTagSupport()
    {
        // Laravel >= 5.1.28
        if (method_exists($this->cache, 'tags') || $this->cache instanceof PsrCacheInterface) {
            try {
                // Attempt the repository tags command, which throws exceptions when unsupported
                $this->cache->tags($this->tag);
                $this->supportsTags = true;
            } catch (BadMethodCallException $ex) {
                $this->supportsTags = false;
            }
        } else {
            // Laravel <= 5.1.27
            if (method_exists($this->cache, 'getStore')) {
                // Check for the tags function directly on the store
                $this->supportsTags = method_exists($this->cache->getStore(), 'tags');
            } else {
                // Must be using custom cache repository without getStore(), and all bets are off,
                // or we are mocking the cache contract (in testing), which will not create a getStore method
                $this->supportsTags = false;
            }
        }
    } //Function ends
    
} //Class ends