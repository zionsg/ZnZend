<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Mvc\Controller\Plugin;

use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Json\Json;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Controller plugin to consume REST web services which return JSON
 */
class ZnZendRestJson extends AbstractPlugin
{
    /**
     * JSON result from web API
     *
     * @var string
     */
    protected $result;

    /**
     * Response from web API
     *
     * @var Response
     */
    protected $response;

    /**
     * Decoded JSON object
     *
     * @var stdClass
     */
    protected $json;

    /**
     * Whether result is valid JSON
     *
     * @var bool
     */
    protected $isValid;

    /**
     * Error code from decoding JSON
     *
     * @var int
     */
    protected $jsonErrorCode;

    /**
     * Error message from decoding JSON
     *
     * @var string
     */
    protected $jsonErrorMessage;

    /**
     * Consume REST web service and store result
     *
     * This will return itself - results are to be retrieved via the other methods.
     *
     * @param  string $uri     Uri for the web API
     * @param  string $method  Optional HTTP method
     * @param  array  $data    Optional data to be sent with request
     * @param  array  $headers Optional headers to be sent with request
     * @return ZnZendRest
     */
    public function __invoke($uri, $method = Request::METHOD_GET, array $data = array(), array $headers = array())
    {
        $client = new Client();
        $client->setUri($uri)
               ->setMethod($method)
               ->setEncType(Client::ENC_FORMDATA); // this must be set for setParameterPost to work
        // $client->setAdapter(new Client\Adapter\Curl());

        if ($data) {
            if (Request::METHOD_GET == $method) {
                $client->setParameterGet($data); // GET
            } else {
                $client->setParameterPost($data); // POST, PUT, PATCH, DELETE, OPTIONS
            }
        }

        $requestHeaders = $client->getRequest()->getHeaders();
        $headerString = 'Accept: application/json';
        $requestHeaders->addHeaderLine($headerString);
        foreach ($headers as $header) {
            $requestHeaders->addHeaderLine($header);
        }

        $this->response = $client->send();
        // getContent() gives problems when a base64 encoded string is embedded, such as an inline image
        $this->result = $this->response->getBody();

        try {
            $this->json = Json::decode($this->result);
        } catch (\Exception $e) {
            $this->jsonErrorCode = json_last_error();
            $this->jsonErrorMessage = $e->getMessage();
            $this->isValid = false;
        }

        $this->isValid = true;
        return $this;
    }

    /**
     * Return JSON result as encoded string
     *
     * @return string
     */
    public function getJsonString()
    {
        return $this->result;
    }

    /**
     * Return JSON result as decoded object
     *
     * @return stdClass
     */
    public function getJsonObject()
    {
        return $this->json;
    }

    /**
     * Pretty print JSON result
     *
     * @return string
     */
    public function prettyPrint($options = array('indent' => "\t"))
    {
        return Json::prettyPrint($this->result, $options);
    }

    /**
     * Is JSON result valid?
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->isValid;
    }

    /**
     * Retrieve error code from decoding JSON
     *
     * @return int
     */
    public function getJsonErrorCode()
    {
        return $this->jsonErrorCode;
    }

    /**
     * Retrieve error message from decoding JSON
     *
     * @return string
     */
    public function getJsonErrorMessage()
    {
        return $this->jsonErrorMessage;
    }
}
