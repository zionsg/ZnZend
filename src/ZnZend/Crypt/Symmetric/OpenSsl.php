<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Crypt\Symmetric;

use Traversable;
use Zend\Crypt\Symmetric\Mcrypt;
use Zend\Stdlib\ArrayUtils;

/**
 * Symmetric encryption using the OpenSSL extension
 *
 * NOTE: DO NOT USE only this class to encrypt data.
 * This class doesn't provide authentication and integrity check over the data.
 * PLEASE USE Zend\Crypt\BlockCipher instead!
 */
class OpenSsl extends Mcrypt
{
    /**
     * Supported cipher algorithms accompanied by their key/block sizes in bytes
     *
     * OpenSSL has no equivalent of mcrypt_get_key_size() and mcrypt_get_block_size() hence sizes stored here.
     *
     * @var array
     */
    protected $supportedAlgos = array(
        'aes'      => array('name' => 'AES-256', 'keySize' => 32, 'blockSize' => 32),
        'blowfish' => array('name' => 'BF', 'keySize' => 16, 'blockSize' => 8),
        'des'      => array('name' => 'DES', 'keySize' => 7, 'blockSize' => 8),
        'des3'     => array('name' => 'DES-EDE3', 'keySize' => 21, 'blockSize' => 8), // 3 different 56-bit keys
        'cast'     => array('name' => 'CAST5', 'keySize' => 16, 'blockSize' => 8),
    );

    /**
     * Supported encryption modes
     *
     * @var array
     */
    protected $supportedModes = array(
        'cbc'  => 'CBC',
        'cfb'  => 'CFB',
        'ecb'  => 'ECB',
        'ofb'  => 'OFB',
    );

    /**
     * Constructor
     *
     * @param  array|Traversable                  $options
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($options = array())
    {
        if (!extension_loaded('openssl')) {
            throw new Exception\RuntimeException(
                'You cannot use ' . __CLASS__ . ' without the OpenSSL extension'
            );
        }
        if (!empty($options)) {
            if ($options instanceof Traversable) {
                $options = ArrayUtils::iteratorToArray($options);
            } elseif (!is_array($options)) {
                throw new Exception\InvalidArgumentException(
                    'The options parameter must be an array, a Zend\Config\Config object or a Traversable'
                );
            }
            foreach ($options as $key => $value) {
                switch (strtolower($key)) {
                    case 'algo':
                    case 'algorithm':
                        $this->setAlgorithm($value);
                        break;
                    case 'mode':
                        $this->setMode($value);
                        break;
                    case 'key':
                        $this->setKey($value);
                        break;
                    case 'iv':
                    case 'salt':
                        $this->setSalt($value);
                        break;
                    case 'padding':
                        $plugins       = static::getPaddingPluginManager();
                        $padding       = $plugins->get($value);
                        $this->padding = $padding;
                        break;
                }
            }
        }
        $this->setDefaultOptions($options);
    }

    /**
     * Get the maximum key size for the selected cipher and mode of operation
     *
     * @return int Value is in bytes
     */
    public function getKeySize()
    {
        return $this->supportedAlgos[$this->algo]['keySize'];
    }

    /**
     * Encrypt
     *
     * @param  string                             $data
     * @throws Exception\InvalidArgumentException
     * @return string
     */
    public function encrypt($data)
    {
        if (empty($data)) {
            throw new Exception\InvalidArgumentException('The data to encrypt cannot be empty');
        }
        if (null === $this->getKey()) {
            throw new Exception\InvalidArgumentException('No key specified for the encryption');
        }
        if (null === $this->getSalt()) {
            throw new Exception\InvalidArgumentException('The salt (IV) cannot be empty');
        }
        if (null === $this->getPadding()) {
            throw new Exception\InvalidArgumentException('You have to specify a padding method');
        }
        // padding
        $data = $this->padding->pad($data, $this->getBlockSize());
        $iv   = $this->getSalt();
        // encryption
        $result = openssl_encrypt(
            $data,
            $this->getMethod(),
            $this->getKey(),
            false,
            $iv
        );

        return $iv . $result;
    }

    /**
     * Decrypt
     *
     * @param  string                             $data
     * @throws Exception\InvalidArgumentException
     * @return string
     */
    public function decrypt($data)
    {
        if (empty($data)) {
            throw new Exception\InvalidArgumentException('The data to decrypt cannot be empty');
        }
        if (null === $this->getKey()) {
            throw new Exception\InvalidArgumentException('No key specified for the decryption');
        }
        if (null === $this->getPadding()) {
            throw new Exception\InvalidArgumentException('You have to specify a padding method');
        }
        $iv         = substr($data, 0, $this->getSaltSize());
        $ciphertext = substr($data, $this->getSaltSize());
        $result = openssl_decrypt(
            $ciphertext,
            $this->getMethod(),
            $this->getKey(),
            false,
            $iv
        );
        // unpadding
        return $this->padding->strip($result);
    }

    /**
     * Get the salt (IV) size
     *
     * @return int
     */
    public function getSaltSize()
    {
        return openssl_cipher_iv_length($this->getMethod());
    }

    /**
     * Get the block size
     *
     * @return int Value is in bytes
     */
    public function getBlockSize()
    {
        return $this->supportedAlgos[$this->algo]['blockSize'];
    }

    /**
     * Combine algorithm and mode to get cipher method
     *
     * The $method argument for openssl_encrypt() combines algorithm and mode, unlike
     * mcrypt_crypt() which separates them into 2 arguments.
     * Special case for DES-EDE3 ECB mode which is named DES-EDE3 instead of DES-EDE3-ECB.
     *
     * @return string
     */
    protected function getMethod()
    {
        $algo = $this->supportedAlgos[$this->algo]['name'];
        $mode = $this->supportedModes[$this->mode];
        if ('DES-EDE3' == $algo && 'ECB' == $mode) {
            return $algo;
        }
        return $algo . '-' . $mode;
    }
}
