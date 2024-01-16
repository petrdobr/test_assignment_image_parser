<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ParserController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(Request $request, HttpClientInterface $httpClient): Response
    {
        // Form for URL submition
        $form = $this->createFormBuilder()
        ->add('url', TextType::class)
        ->add('submit', SubmitType::class, ['label' => 'Го'])
        ->getForm();

        // Reset form
        $resetForm = $this->createFormBuilder()
        ->add('reset', SubmitType::class, ['label' => 'Сброс'])
        ->setAction($this->generateUrl('reset'))
        ->setMethod('GET')->getForm();

        $form->handleRequest($request);
        $resetForm->handleRequest($request);

        // Activate reset action
        if ($resetForm->isSubmitted()) {
            return $this->redirectToRoute('reset');
        }

        // URL submition
        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();
            $url = $data['url'];

            // Fetch images from the URL
            $response = $httpClient->request('GET', $url);
            $content = $response->getContent();
            $images = $this->extractImages($content, $url);

            // Count the number of fetched images
            $imageCount = count($images);
            $pathToCacheImg = '../var/img/';
            $totalWeight = $this->calculateTotalWeight($images, $httpClient, $pathToCacheImg);

            return $this->render('homepage.html.twig', [
                'form' => $form->createView(),
                'images' => $images,
                'imageCount' => $imageCount,
                'totalWeight' => $totalWeight,
                'resetForm' => $resetForm->createView(),
            ]);
        }

        // Clean page
        return $this->render('homepage.html.twig', [
            'form' => $form->createView(),
            'images' => [],
            'imageCount' => 0,
            'totalWeight' => 0,
            'resetForm' => $resetForm->createView(),
        ]);
    }

    /**
     * Reset route and action
     * Redirects to homepage, cleans images from cache
     */
    #[Route('/reset', name: 'reset')]
    public function reset(): RedirectResponse
    {
        $this->clearImgPath('../var/img/');
        return $this->redirectToRoute('homepage');
    }

    /**
     * Extract images from HTML content
     */
    public function extractImages(mixed $content, string $url): array
    {
        // Using DOM library
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($content);
        libxml_clear_errors();

        $images = [];
        $imgTags = $dom->getElementsByTagName('img');

        // Entered URLs should start with http:// or https://
        $parsedUrl = parse_url($url);
        $hostname = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

        // Create an array with absolute urls to images
        foreach ($imgTags as $imgTag) {
            $src = $imgTag->getAttribute('src');

            $absolutePath = $this->makeAbsolutePath($src, $hostname);
            $images[] = $absolutePath;
        }

        return $images;
    }

    /**
     * Create an absolute url to the image using relative path and hostname
     */
    public function makeAbsolutePath(string $relativePath, string $hostname): string
    {
        // Check if the relative path is already an absolute URL
        if (filter_var($relativePath, FILTER_VALIDATE_URL)) {
            return $relativePath;
        }

        // Concatenate the relative path with the hostname
        return $hostname . $relativePath;
    }

    /**
     * Calculate the weight (size) of all images extracted from the webpage
     */
    public function calculateTotalWeight(array $images, HttpClientInterface $httpClient, string $path): string
    {
        // If path does not exist create it
        $totalWeight = 0;
        if (!file_exists($path)) {
            mkdir($path);
        }

        foreach ($images as $image) {
            // Fetch the image content and calculate its size
            $response = $httpClient->request('GET', $image);
            $fileName = pathinfo($image, PATHINFO_BASENAME);
            $filePath = $path . $fileName;
            $imageSize = file_put_contents($filePath, $response->getContent());
            $totalWeight += $imageSize;
        }

        return number_format($totalWeight / 1024 / 1024, 3);
    }

    /**
     * Clear all files from the passed in path (clear cache)
     */
    public function clearImgPath(string $path): void
    {
        $files = glob($path . '*');
        foreach($files as $file){ 
            if(is_file($file)) {
                unlink($file); 
            }
        }
    }
}