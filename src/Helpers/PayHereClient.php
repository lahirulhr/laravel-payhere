<?php

namespace Lahirulhr\PayHere\Helpers;

class PayHereClient
{
    protected $url;

    protected $required_data = [];

    protected $optional_data = [];

    protected $success_url;

    protected $fail_url;

    protected $notify_url;

    public function data(array $array)
    {
        $this->required_data = $array;

        return $this;
    }

    public function successUrl($url)
    {
        $this->success_url = $url;

        return $this;
    }

    public function failUrl($url)
    {
        $this->fail_url = $url;

        return $this;
    }

    private function setNotifyUrl()
    {
        $this->notify_url = route('payhere.callback', $this->getCallbackKey());
    }

    public function setOptionalData($data)
    {
        $this->optional_data = $data;
    }

    private function authData()
    {
        return [
            'merchant_id' => config('payhere.merchant_id'),
            'return_url' => $this->success_url,
            'cancel_url' => $this->fail_url,
            'notify_url' => $this->notify_url,
        ];
    }

    public function getFormData()
    {
        $this->setNotifyUrl();
        $hash = ['hash' => $this->getHashKey()];

        return array_merge($this->authData(), $this->required_data, $this->optional_data, $hash);
    }

    public function getFullApiUrl()
    {
        return str(config('payhere.api_endpoint'))
            ->finish('/')
            ->append($this->url)
            ->toString();
    }

    public function renderView()
    {
        $action = $this->getFullApiUrl();
        $data = $this->getFormData();

        return view('payhere::recurring', compact('action', 'data'));
    }

    public static function getCallbackKey()
    {
        return base64_encode(get_called_class());
    }

    public function getHashKey()
    {
        $vars = $this->authData();

        return strtoupper(md5(
            $vars['merchant_id']
            .$this->required_data['order_id']
            .number_format($this->required_data['amount'], 2, '.', '')
            .$this->required_data['currency']
            .strtoupper(md5(config('payhere.merchant_secret'))))
        );
    }
}
