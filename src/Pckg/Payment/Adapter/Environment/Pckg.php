<?php namespace Pckg\Payment\Adapter\Environment;

use Pckg\Payment\Adapter\Environment;

class Pckg implements Environment
{

    protected $validator;

    /**
     * @param        $request
     * @param array  $rules
     */
    public function validates($request, $rules = [])
    {
        return true;
    }

    public function errorJsonResponse()
    {
        return response()->respond(['error' => true, 'errors' => ['@T00D001']]);
    }

    public function config($key)
    {
        return config('pckg.payment.provider.' . $key);
    }

    public function request($key)
    {
        return request()->get($key);
    }

    public function post($key)
    {
        return request()->post($key);
    }

    public function get($key)
    {
        return request()->get($key);
    }

    public function url($slug, $params = [])
    {
        return url($slug, $params);
    }

    public function fullUrl($slug, $params = [])
    {
        return url($slug, $params, true);
    }

    public function redirect($url)
    {
        return response()->redirect($url);
    }

    public function flash($key, $val)
    {
        return flash($key, $val);
    }

}