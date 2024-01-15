<?php

namespace App\Tests;

use App\Controller\ParserController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ParserTest extends WebTestCase
{
    public function testHomepage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        // Test homepage works
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Загрузчик изображений');

/*
        // Test images are downloaded and shown on homepage
        $crawler = $client->submitForm('Го', [
            'form[url]' => 'https://example.com',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Загруженные изображения');
        $this->assertSelectorExists('table');
*/
        
        // Test reset button redirects to homepage
        $crawler = $client->submitForm('Сброс');
        $this->assertResponseRedirects('/');
    }

    public function testExtractImages()
    {
        // Test extracting images from webpage
        $httpClient = $this->createMock(HttpClientInterface::class);
        $content = '<html><body><img src="https://example.com/image.jpg"></body></html>';
        $response = $this->createMock(ResponseInterface::class);
        $httpClient->method('request')->willReturn($response);

        $imageDownloader = new ParserController();
        $images = $imageDownloader->extractImages($content, 'https://example.com');

        $expectedImages = ['https://example.com/image.jpg'];
        $this->assertEquals($expectedImages, $images);
    }

    public function testAbsolutePath()
    {
        // Test correct image path creation
        $relativePath = '/images/image.jpg';
        $hostname = 'https://example.com';

        $imageDownloader = new ParserController();
        $absolutePath = $imageDownloader->makeAbsolutePath($relativePath, $hostname);

        $expectedPath = 'https://example.com/images/image.jpg';
        $this->assertEquals($expectedPath, $absolutePath);
    }

    public function testCalculateWeight()
    {
        // Test extracting weight
        $httpClient = $this->createMock(HttpClientInterface::class);
        $expectedContent = ['content-length' => [1024 * 1024]];
        $response = $this->createMock(ResponseInterface::class);
        $httpClient->method('request')->willReturn($response);
        $response->method('getHeaders')->willReturn($expectedContent);


        $images = ['https://example.com/iamge.jpg'];
        $imageDownloader = new ParserController();
        $totalWeight = $imageDownloader->calculateTotalWeight($images, $httpClient);

        $expectedTotalWeight = 1.000;
        $this->assertEquals($expectedTotalWeight, $totalWeight);
    }
}
