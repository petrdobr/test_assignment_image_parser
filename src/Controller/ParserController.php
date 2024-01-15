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
        $this->clearImgPath('../var/img/');
        return $this->redirectToRoute('homepage');
    }

    /**
     * Extract images from HTML content
     */
    public function extractImages($content, $url)
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

            $absolutePath = $this->makeAbsolutePath($src, $hostname);
            $images[] = $absolutePath;
        }

        return $images;
    }

    public function makeAbsolutePath($relativePath, $hostname)
    {
        // Check if the relative path is already an absolute URL
        if (filter_var($relativePath, FILTER_VALIDATE_URL)) {
            return $relativePath;
        }

        // Concatenate the relative path with the hostname
        return $hostname . $relativePath;
    }

    public function calculateTotalWeight($images, $httpClient, $path)
    {
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

    public function clearImgPath($path)
    {
        $files = glob($path . '*');
        foreach($files as $file){ 
            if(is_file($file)) {
                unlink($file); 
            }
        }
    }
}