<?php namespace Pckg\Payment\Adapter;

interface Environment
{

    public function validates($request, $rules = []);

    public function errorJsonResponse();

    public function config($key);

    public function request($key);

    public function url($slug, $params = []);

    public function fullUrl($slug, $params = []);

    public function redirect($url);

}