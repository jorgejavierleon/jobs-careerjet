<?php namespace Jorgejavierleon\Careerjet\Test;

use JobBrander\Jobs\Client\Collection;
use JobBrander\Jobs\Client\Job;
use Jorgejavierleon\Careerjet\Careerjet;
use Mockery as m;

class CareerjetTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->params = [
            'affid'    => '0afaf0173305e4b9',
        ];

        $this->client = new Careerjet($this->params);
    }

    public function testDefaultUrlAfterConfig()
    {
        $url = $this->client->getUrl();

        $this->assertContains('affid='.$this->params['affid'], $url);
    }

    public function testItWillUseJsonFormatWhenFormatNotProvided()
    {
        $format = $this->client->getFormat();

        $this->assertEquals('json', $format);
    }

    public function testItWillUseJsonFormatWhenInvalidFormatProvided()
    {
        $formatAttempt = uniqid();

        $format = $this->client->setFormat($formatAttempt)->getFormat();

        $this->assertEquals('json', $format);
    }

    public function testItWillUseXmlFormatWhenProvided()
    {
        $formatAttempt = 'xml';

        $format = $this->client->setFormat($formatAttempt)->getFormat();

        $this->assertEquals($formatAttempt, $format);
    }

    public function testItWillUseGetHttpVerb()
    {
        $verb = $this->client->getVerb();

        $this->assertEquals('GET', $verb);
    }

    public function testListingPath()
    {
        $path = $this->client->getListingsPath();

        $this->assertEquals('jobs', $path);
    }

    public function testUrlContainsSearchParametersWhenProvided()
    {
        $client = new \ReflectionClass(Careerjet::class);
        $property = $client->getProperty("queryMap");
        $property->setAccessible(true);
        $queryMap = $property->getValue($this->client);
        $queryParameters = array_values($queryMap);
        $params = [];


        array_map(function ($item) use (&$params) {
            $params[$item] = uniqid();
        }, $queryParameters);

        $newClient = new Careerjet(array_merge($this->params, $params));

        $url = $newClient->getUrl();

        array_walk($params, function ($v, $k) use ($url) {
            $this->assertContains($k.'='.$v, $url);
        });
    }

    public function testUrlContainsSearchParametersWhenSet()
    {
        $client = new \ReflectionClass(Careerjet::class);
        $property = $client->getProperty("queryMap");
        $property->setAccessible(true);
        $queryMap = $property->getValue($this->client);

        array_walk($queryMap, function ($v, $k) {
            $value = uniqid();
            $url = $this->client->$k($value)->getUrl();

            $this->assertContains($v.'='.$value, $url);
        });
    }

    public function testItWillIncludeUserIpIfAvailableAndNotProvided()
    {
        $ip = uniqid();
        $_SERVER['REMOTE_ADDR'] = $ip;
        $client = new Careerjet;

        $url = $client->getUrl();

        $this->assertContains('user_ip='.$ip, $url);
    }

    public function testItWillIncludeUserAgentIfAvailableAndNotProvided()
    {
        $agent = uniqid();
        $_SERVER['HTTP_USER_AGENT'] = $agent;
        $client = new Careerjet;

        $url = $client->getUrl();

        $this->assertContains('user_agent='.$agent, $url);
    }

    public function testItCanCreateJobFromPayload()
    {
        $payload = $this->createJobArray();
        $results = $this->client->createJobObject($payload);

        $this->assertEquals($payload['title'], $results->getTitle(), 'no title');
        $this->assertEquals($payload['title'], $results->getName(), 'no name');
        $this->assertEquals($payload['description'], $results->getDescription(), 'no description');
        $this->assertEquals($payload['company'], $results->getCompany(), 'no company');
        $this->assertEquals($payload['salary'], $results->getBaseSalary(), 'no salary');
        $this->assertEquals($payload['date'], $results->getDatePosted(), 'no date');
        $this->assertEquals($payload['locations'], $results->getLocation(), 'no location');
        $this->assertEquals($payload['locations'], $results->getJobLocation(), 'no location');
        $this->assertEquals($payload['url'], $results->getUrl(), 'no url');
    }

    public function testItCanConnect()
    {
        $provider = $this->getProviderAttributes();

        for ($i = 0; $i < $provider['jobs_count']; $i++) {
            $payload['jobs'][] = $this->createJobArray();
        }

        $responseBody = json_encode($payload);

        $job = m::mock(Job::class);

        $job->shouldReceive('setQuery')->with($provider['keyword'])
            ->times($provider['jobs_count'])->andReturnSelf();

        $job->shouldReceive('setSource')->with($provider['source'])
            ->times($provider['jobs_count'])->andReturnSelf();
        $response = m::mock('GuzzleHttp\Message\Response');

        $response->shouldReceive('getBody')->once()->andReturn($responseBody);

        $http = m::mock('GuzzleHttp\Client');

        $http->shouldReceive(strtolower($this->client->getVerb()))
            ->with($this->client->getUrl(), $this->client->getHttpClientOptions())
            ->once()
            ->andReturn($response);

        $this->client->setClient($http);

        $results = $this->client->getJobs();

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount($provider['jobs_count'], $results);
    }

    private function createJobArray()
    {
        return [
            'title' => uniqid(),
            'name' => uniqid(),
            'description' => uniqid(),
            'company' => uniqid(),
            'salary' => uniqid(),
            'date' => '2015-07-'.rand(1, 31),
            'locations' => uniqid().', '.uniqid(),
            'url' => uniqid(),
        ];
    }

    private function getProviderAttributes($attributes = [])
    {
        $defaults = [
            'path' => uniqid(),
            'format' => 'json',
            'keyword' => uniqid(),
            'source' => uniqid(),
            'params' => [uniqid()],
            'jobs_count' => rand(2, 10),

        ];

        return array_replace($defaults, $attributes);
    }
}