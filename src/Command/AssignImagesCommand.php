<?php

namespace App\Command;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:assign-images',
    description: 'Attribue des images aléatoires à tous les produits',
)]
class AssignImagesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
        private ParameterBagInterface $parameterBag,
        private SluggerInterface $slugger,
        private HttpClientInterface $httpClient
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $products = $this->productRepository->findAll();
        $uploadDir = $this->parameterBag->get('products_directory');

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $io->title('Attribution d\'images aléatoires aux produits');
        
        if (empty($products)) {
            $io->warning('Aucun produit trouvé dans la base de données.');
            return Command::SUCCESS;
        }

        $progressBar = new ProgressBar($output, count($products));
        $progressBar->start();

        $wineKeywords = ['wine', 'vineyard', 'grape', 'wine-cellar', 'wine-bottle', 'wine-glass', 'red-wine', 'white-wine', 'rose-wine', 'champagne', 'sommelier', 'winery', 'oak-barrel'];

        foreach ($products as $index => $product) {
            // Using Unsplash Source for high quality wine images
            $keyword = $wineKeywords[array_rand($wineKeywords)];
            $imageUrl = sprintf('https://source.unsplash.com/featured/800x800/?%s', $keyword);
            
            // Note: Picsum is still great for reliability, but Unsplash is better for themes.
            // Let's use a hybrid approach to ensure high resolution wine images.
            $imageUrl = sprintf('https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=800&h=800&fit=crop&q=80&sig=%s', uniqid());
            
            // Actually, let's use a variety of wine photos from Unsplash collection or keywords
            $winePhotoIds = [
                '1510812431401-41d2bd2722f3', // Bottles
                '1506377247377-2a5b3b417ebb', // Grapes
                '1504221507732-5246c045949b', // Vineyard
                '1553392374-e229f1ec37f1', // Cellar
                '1584916201218-f2108bc8d16d', // Red wine pour
                '1516594915697-87eb3b1c14ea', // White wine
                '1559158518-e3da342c8d2d', // Rose wine
                '1594372365401-3b5ff14eaaed', // Tasting
                '1568213816046-0ee1c42bd559', // Barrels
                '1513110920617-40b493b10ec9', // Champagne
            ];
            
            $photoId = $winePhotoIds[array_rand($winePhotoIds)];
            $imageUrl = sprintf('https://images.unsplash.com/photo-%s?w=800&h=800&fit=crop&q=80', $photoId);

            try {
                $response = $this->httpClient->request('GET', $imageUrl);
                
                if ($response->getStatusCode() === 200) {
                    $imageContent = $response->getContent();
                    
                    $extension = 'jpg';
                    // Essayer de deviner l'extension via le content-type
                    $headers = $response->getHeaders();
                    $contentType = $headers['content-type'][0] ?? 'image/jpeg';
                    
                    if (str_contains($contentType, 'png')) {
                        $extension = 'png';
                    } elseif (str_contains($contentType, 'webp')) {
                        $extension = 'webp';
                    }

                    $filename = $this->slugger->slug($product->getName()) . '-' . uniqid() . '.' . $extension;
                    $filePath = $uploadDir . '/' . $filename;

                    file_put_contents($filePath, $imageContent);
                    $product->setImageName($filename);
                } else {
                    $io->error(sprintf('Erreur HTTP %d pour le produit "%s"', $response->getStatusCode(), $product->getName()));
                }
            } catch (\Exception $e) {
                $io->error(sprintf('Erreur pour le produit "%s": %s', $product->getName(), $e->getMessage()));
            }

            $progressBar->advance();
            // Petit délai pour ne pas trop solliciter le service distant
            usleep(100000); 
        }

        $this->entityManager->flush();
        $progressBar->finish();
        
        $io->newLine(2);
        $io->success('Tous les produits ont reçu une nouvelle image aléatoire !');

        return Command::SUCCESS;
    }
}
