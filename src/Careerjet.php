<?php namespace Jorgejavierleon\Careerjet;

use JobBrander\Jobs\Client\Job;
use JobBrander\Jobs\Client\Providers\AbstractProvider;

class Careerjet extends AbstractProvider
{

    /**
     * Map of setter methods to query parameters
     *
     * @var array
     */
    protected $queryMap = [
        'setAffid' => 'affid',
        'setKeyword' => 'keywords',
        'setLocation' => 'location',
        'setSort' => 'sort',
        'setStartNum' => 'start_num',
        'setPageSize' => 'pagesize',
        'setPage' => 'page',
        'setContractType' => 'contracttype',
        'setContractPeriod' => 'contractperiod',
        'setFormat' => 'format',
        'setUserIp' => 'user_ip',
        'setUserAgent' => 'user_agent',
    ];

    /**
     * Query params
     *
     * @var array
     */
    protected $queryParams = [
        'affid' => null,
        'keywords' => null,
        'location' => null,
        'sort' => null,
        'start_num' => null,
        'pagesize' => null,
        'page' => null,
        'contracttype' => null,
        'contractperiod' => null,
        'format' => null,
        'user_ip' => null,
        'user_agent' => null,
    ];

    /**
     * Job defaults
     *
     * @var array
     */
    protected $jobDefaults = ['title','company','locations','salary',
        'date','description', 'date', 'location', 'url'
    ];


    /**
     * Careerjet constructor.
     *
     * @param array $parameters
     */
    public function __construct($parameters = [])
    {
        parent::__construct($parameters);

        $this->addDefaultUserInformationToParameters($parameters);

        array_walk($parameters, [$this, 'updateQuery']);
    }

    /**
     * Magic method to handle get and set methods for properties
     *
     * @param  string $method
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (isset($this->queryMap[$method], $parameters[0])) {
            $this->updateQuery($parameters[0], $this->queryMap[$method]);
        }

        return parent::__call($method, $parameters);
    }


    /**
     * Returns the standardized job object
     *
     * @param array|object $payload
     *
     * @return \JobBrander\Jobs\Client\Job
     */
    public function createJobObject($payload)
    {
        $payload = static::parseAttributeDefaults($payload, $this->jobDefaults);
        $job = $this->createJobFromPayload($payload);

        return $job;
    }

    /**
     * Create new job from given payload
     *
     * @param  array $payload
     *
     * @return Job
     */
    protected function createJobFromPayload($payload = [])
    {
        return new Job([
            'title' => $payload['title'],
            'name' => $payload['title'],
            'description' => $payload['description'],
            'company' => $payload['company'],
            'salary' => $payload['salary'],
            'date' => $payload['date'],
            'url' => $payload['url'],
            'location' => $payload['location'],
        ]);
    }

    /**
     * Get format
     *
     * @return  string Currently only 'json' and 'xml' supported
     */
    public function getFormat()
    {
        $validFormats = ['json', 'xml'];

        if (isset($this->queryParams['format'])
            && in_array(strtolower($this->queryParams['format']), $validFormats)) {
            return strtolower($this->queryParams['format']);
        }

        return 'json';
    }

    /**
     * Get listings path
     *
     * @return  string
     */
    public function getListingsPath()
    {
        return 'jobs';
    }

    /**
     * Get url
     *
     * @return  string
     */
    public function getUrl()
    {
        return 'http://public.api.careerjet.net/search?'.$this->getQueryString();
    }

    /**
     * Get http verb to use when making request
     *
     * @return  string
     */
    public function getVerb()
    {
        return 'GET';
    }

    /**
     * Get query string for client based on properties
     *
     * @return string
     */
    public function getQueryString()
    {
//        $location = $this->getLocation();
//
//        if (!empty($location)) {
//            $this->updateQuery($location, 'location');
//        }

        return http_build_query($this->queryParams);
    }

    /**
     * Attempts to apply default user information to parameters when none provided.
     *
     * @param array  $parameters
     *
     * @return void
     */
    protected function addDefaultUserInformationToParameters(&$parameters = [])
    {
        $defaultKeys = [
            'user_ip' => 'REMOTE_ADDR',
            'user_agent' => 'HTTP_USER_AGENT',
        ];

        array_walk($defaultKeys, function ($value, $key) use (&$parameters) {
            if (!isset($parameters[$key]) && isset($_SERVER[$value])) {
                $parameters[$key] = $_SERVER[$value];
            }
        });
    }

    /**
     * Attempts to update current query parameters.
     *
     * @param  string  $value
     * @param  string  $key
     *
     * @return Careerjet
     */
    protected function updateQuery($value, $key)
    {
        if (array_key_exists($key, $this->queryParams)) {
            $this->queryParams[$key] = $value;
        }

        return $this;
    }

//    private function getLocation()
//    {
//
//    }
}
