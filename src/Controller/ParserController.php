<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;

class ParserController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(Request $request, HttpClientInterface $httpClient): Response
    {
        $form = $this->createFormBuilder()
        ->add('url', TextType::class, ['label' => 'Enter URL'])
        ->add('submit', SubmitType::class, ['label' => 'Го', 'attr' => ['class' => 'btn btn-primary w-100']])
        ->add('reset', ResetType::class, ['label' => 'Сброс', 'attr' => ['class' => 'btn btn-secondary w-100']])
        ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $url = $data['url'];

            // Fetch images from the URL
            $response = $httpClient->request('GET', $url);
            $content = $response->getContent();
            $images = $this->extractImages($content, $url);

            // Count the number of fetched images
            $imageCount = count($images);

            $totalWeight = $this->calculateTotalWeight($images, $httpClient);

            return $this->render('homepage.html.twig', [
                'form' => $form->createView(),
                'images' => $images,
                'imageCount' => $imageCount,
                'totalWeight' => $totalWeight,
            ]);
        } 

        if ($form->get('reset')->isClicked()) {
            return $this->render('homepage.html.twig', [
                'form' => $form->createView(),
                'images' => [],
                'imageCount' => 0,
                'totalWeight' => 0,
            ]);
        }

        return $this->render('homepage.html.twig', [
            'form' => $form->createView(),
            'images' => [],
            'imageCount' => 0,
            'totalWeight' => 0,
        ]);
    }

    /**
     * Extract images from HTML content
     */
    private function extractImages($content, $url)
    {
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($content);
        libxml_clear_errors();

        $images = [];
        $imgTags = $dom->getElementsByTagName('img');

        $parsedUrl = parse_url($url);
        $hostname = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

        foreach ($imgTags as $imgTag) {
            $src = $imgTag->getAttribute('src');
            // You may want to manipulate the $src to get absolute URLs if needed
            $absolutePath = $this->makeAbsolutePath($src, $hostname);
            $images[] = $absolutePath;
        }

        return $images;
    }

    private function makeAbsolutePath($relativePath, $hostname)
    {
        // Check if the relative path is already an absolute URL
        if (filter_var($relativePath, FILTER_VALIDATE_URL)) {
            return $relativePath;
        }

        // Concatenate the relative path with the hostname
        return $hostname . $relativePath;
    }

    private function calculateTotalWeight($images, $httpClient)
    {
        $totalWeight = 0;

        foreach ($images as $image) {
            // Fetch the image content and calculate its size
            $imageContent = $httpClient->request('GET', $image)->getContent();
            $imageSize = mb_strlen($imageContent, '8bit');
            $totalWeight += $imageSize;
        }

        return number_format($totalWeight / 1024 / 1024, 3);
    }
}