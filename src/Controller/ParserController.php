<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ParserController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(Request $request, HttpClientInterface $httpClient): Response
    {
        $form = $this->createFormBuilder()
        ->add('url', TextType::class)
        ->add('submit', SubmitType::class, ['label' => 'Го'])
        ->getForm();

        $resetForm = $this->createFormBuilder()
        ->add('reset', SubmitType::class, ['label' => 'Сброс'])
        ->setAction($this->generateUrl('reset'))
        ->setMethod('GET')->getForm();

        $form->handleRequest($request);
        $resetForm->handleRequest($request);

        if ($resetForm->isSubmitted()) {
            return $this->redirectToRoute('reset');
        }

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
                'resetForm' => $resetForm->createView(),
            ]);
        }

        return $this->render('homepage.html.twig', [
            'form' => $form->createView(),
            'images' => [],
            'imageCount' => 0,
            'totalWeight' => 0,
            'resetForm' => $resetForm->createView(),
        ]);
    }

    #[Route('/reset', name: 'reset')]
    public function reset()
    {
        return $this->redirectToRoute('homepage');
    }

    /**
     * Extract images from HTML content
     */
    private function extractImages($content, $url)
    {
        $dom = new \DOMDocument;
        $dom->loadHTML($content);

        $images = [];
        $imgTags = $dom->getElementsByTagName('img');

        $parsedUrl = parse_url($url);
        $hostname = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

        foreach ($imgTags as $imgTag) {
            $src = $imgTag->getAttribute('src');

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