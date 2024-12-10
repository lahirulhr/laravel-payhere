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
        if (empty($this->notify_url)) {
            $this->notify_url = route('payhere.callback', $this->getCallbackKey());
        }
        return $this->notify_url;
    }

    public function notifyUrl(string $url)
    {
        $this->notify_url = $url;

        return $this;
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

    public function getFormUrl(): string
    {
        $action = $this->getFullApiUrl();
        $data = $this->getFormData();

        $payload = encrypt([
            'action' => $action,
            'data' => $data,
        ]);

        return url('payhere/form?payload='.$payload);
    }

    public function renderView()
    {

        return redirect($this->getFormUrl());

    }

    public static function getCallbackKey()
    {
        return base64_encode(get_called_class());
    }

    public function getHashKey()
    {
        $vars = $this->authData();

        $amount = $this->required_data['amount'] ?? 0;

        return strtoupper(md5(
            $vars['merchant_id']
            .$this->required_data['order_id']
            .number_format($amount, 2, '.', '')
            .$this->required_data['currency']
            .strtoupper(md5(config('payhere.merchant_secret'))))
        );
    }
}
