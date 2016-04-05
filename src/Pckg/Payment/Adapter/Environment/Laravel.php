<?php namespace Pckg\Payment\Adapter\Environment;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Validator;
use Pckg\Payment\Adapter\Environment;

class Laravel implements Environment
{

    protected $validator;

    /**
     * @param       HttpRequest $request
     * @param array             $rules
     */
    public function validates($request, $rules = [])
    {
        $this->validator = Validator::make($request->all(), $rules);

        return $this->validator->passes();
    }

    public function errorJsonResponse()
    {
        return new JsonResponse($this->validator->getMessageBag()->toArray(), 422);
    }

    public function config($key)
    {
        return config('payment.' . $key);
    }

    public function request($key)
    {
        return request($key);
    }

    public function url($slug, $params = [])
    {
        return url($slug, $params);
    }

    public function fullUrl($slug, $params = [])
    {
        return full_url($slug, $params);
    }

    public function redirect($url)
    {
        redirect()->away($url)->send();
    }

}