<?php namespace CoandaCMS\Coanda\Pages;

class PageStaticCacher {

    /**
     * @var bool
     */
    private $enabled = false;
    /**
     * @var Cache
     */
    private $cache;
    /**
     * @var \Illuminate\Http\Request
     */
    private $request;
    /**
     * @var \Illuminate\Config\Repository
     */
    private $config;

    /**
     * @param \Illuminate\Cache\Repository $cache
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Config\Repository $config
     */
    public function __construct(\Illuminate\Cache\Repository $cache, \Illuminate\Http\Request $request, \Illuminate\Config\Repository $config)
    {
        $this->cache = $cache;
        $this->request = $request;
        $this->config = $config;

        $this->checkIfEnabled();
    }

    /**
     * @param $location_id
     * @return bool
     */
    public function hasLocationCache($location_id)
    {
        return $this->has($this->generateLocationCacheKey($location_id));
    }

    /**
     * @param $location_id
     * @return mixed
     */
    public function getLocationCache($location_id)
    {
        return $this->get($this->generateLocationCacheKey($location_id));
    }

    /**
     * @param $location_id
     * @param $content
     */
    public function putLocationCache($location_id, $content)
    {
        $this->put($this->generateLocationCacheKey($location_id), $content);
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return $this->enabled ? $this->cache->has($key) : false;
    }

    /**
     * @return mixed
     */
    public function get($key)
    {
        return $this->enabled ? $this->cache->get($key) : false;
    }

    /**
     * @param $content
     * @return mixed
     */
    public function put($key, $content)
    {
        return $this->cache->put($key, $content, $this->getCacheLifetime());
    }

    /**
     *
     */
    private function checkIfEnabled()
    {
        $this->enabled = $this->config->get('coanda::coanda.page_cache_enabled');
    }

    /**
     *
     */
    private function getCacheLifetime()
    {
        return $this->config->get('coanda::coanda.page_cache_lifetime');
    }

    /**
     * @param $location_id
     * @return string
     */
    private function generateLocationCacheKey($location_id)
    {
        return 'location-' . $location_id . '-' . md5(var_export($this->getInput(), true));
    }

    /**
     * @return mixed
     */
    private function getInput()
    {
        $all_input = $this->request->all();

        // If we are viewing ?page=1 - then this is cached the same as without it...
        if (isset($all_input['page']) && $all_input['page'] == 1)
        {
            unset($all_input['page']);
        }

        return $all_input;
    }
}
