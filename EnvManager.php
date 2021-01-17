<?php

class EnvManager
{
    protected $envFilePath;

    /**
     * EnvManager constructor.
     */
    public function __construct($envFilePath)
    {
        $this->envFilePath = $envFilePath;
    }

    /**
     * @return string
     */
    public function generateRandomKey(): string
    {
        return 'base64:'.base64_encode(
                \Illuminate\Encryption\Encrypter::generateKey('AES-256-CBC')
            );
    }

    /**
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function setValue(string $key, string $value): bool
    {
        $key = mb_strtoupper($key);

        $this->writeValue($key, $value);

        return true;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function writeValue(string $key, string $value)
    {
        file_put_contents($this->envFilePath, preg_replace(
            $this->keyReplacementPattern($key, $value),
            $key . '=' . $value,
            file_get_contents($this->envFilePath)
        ));
    }

    /**
     * @param string $key
     * @param null $value
     * @return string
     */
    public function keyReplacementPattern(string $key, $value = null): string
    {
//        $escaped = preg_quote('='.$value, '/');
//        return "/^$key.$escaped/m";

        return "/^$key=\S*$/m";
    }
}
